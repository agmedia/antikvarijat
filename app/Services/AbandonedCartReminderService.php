<?php

namespace App\Services;

use App\Helpers\LocaleHelper;
use App\Mail\AbandonedCartReminderMail;
use App\Models\AbandonedCartReminder;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class AbandonedCartReminderService
{
    private ?bool $available = null;

    public function isAvailable(): bool
    {
        if ($this->available === null) {
            $this->available = Schema::hasTable('abandoned_cart_reminders')
                && Schema::hasColumn('orders', 'locale')
                && Schema::hasColumn('orders', 'unfinished_at');
        }

        return $this->available;
    }

    public function adminState(Order $order): array
    {
        if (! $this->isAvailable()) {
            return ['available' => false, 'complete' => false];
        }

        $sent = $this->sentReminders($order);
        $nextSequence = $this->nextSequenceFrom($sent);
        $error = $this->baseEligibilityError($order);

        return [
            'available' => $error === null && $nextSequence !== null,
            'complete' => $nextSequence === null,
            'next_sequence' => $nextSequence,
            'next_scheduled_at' => $nextSequence ? $this->scheduledFor($order, $nextSequence) : null,
            'first_sent_at' => optional($sent->get(1))->sent_at,
            'second_sent_at' => optional($sent->get(2))->sent_at,
            'error' => $error,
        ];
    }

    public function candidatesForSequence(int $sequence, int $limit): Collection
    {
        if (! $this->isAvailable() || ! config('abandoned_cart.enabled', true)) {
            return collect();
        }

        $delay = $this->delayMinutes($sequence);
        if ($delay === null) {
            return collect();
        }

        $query = Order::query()
            ->where('order_status_id', (int) config('settings.order.status.unfinished', 8))
            ->where('created_at', '>=', $this->startsAt())
            ->whereRaw('COALESCE(unfinished_at, created_at) <= ?', [now()->subMinutes($delay)])
            ->whereNotNull('payment_email')
            ->whereRaw("TRIM(payment_email) <> ''")
            ->whereHas('orderProducts')
            ->whereDoesntHave('abandonedCartReminders', function (Builder $reminders) use ($sequence) {
                $reminders->where('sequence', $sequence)->whereNotNull('sent_at');
            });

        if ($sequence > 1) {
            $query->whereHas('abandonedCartReminders', function (Builder $reminders) use ($sequence) {
                $reminders->where('sequence', $sequence - 1)->whereNotNull('sent_at');
            });
        }

        return $query
            ->with(['products.product', 'totals', 'transactions', 'abandonedCartReminders'])
            ->orderByRaw('COALESCE(unfinished_at, created_at) ASC')
            ->limit(max(1, $limit))
            ->get();
    }

    public function send(Order $order, int $sequence, string $source): AbandonedCartReminder
    {
        if (! in_array($source, [AbandonedCartReminder::SOURCE_AUTOMATIC, AbandonedCartReminder::SOURCE_MANUAL], true)) {
            throw new RuntimeException('Nepoznat izvor podsjetnika.');
        }

        $order->loadMissing(['products.product', 'totals', 'transactions', 'abandonedCartReminders']);

        if ($error = $this->baseEligibilityError($order)) {
            throw new RuntimeException($error);
        }

        $sent = $this->sentReminders($order);
        $nextSequence = $this->nextSequenceFrom($sent);
        if ($nextSequence === null) {
            throw new RuntimeException('Za ovu narudžbu već su poslana oba podsjetnika.');
        }

        if ($sequence !== $nextSequence) {
            throw new RuntimeException('Podsjetnici se moraju slati redoslijedom.');
        }

        if ($source === AbandonedCartReminder::SOURCE_AUTOMATIC && now()->lt($this->scheduledFor($order, $sequence))) {
            throw new RuntimeException('Vrijeme za automatsko slanje još nije nastupilo.');
        }

        $email = mb_strtolower(trim((string) $order->payment_email));
        $locale = $order->resolvedLocale();
        $scheduledFor = $this->scheduledFor($order, $sequence);
        $claim = $this->claim($order, $sequence, $scheduledFor, $email, $locale, $source);
        $recoveryUrl = $this->recoveryUrl($order, $locale);

        try {
            Mail::to($email)->send((new AbandonedCartReminderMail(
                $order,
                $recoveryUrl,
                $sequence
            ))->locale($locale));

            $claim->forceFill(['sent_at' => now()])->save();
            $this->storeHistory($order, $sequence, $source, $email);

            Log::info('Abandoned cart reminder sent', [
                'order_id' => $order->id,
                'sequence' => $sequence,
                'source' => $source,
                'email' => $email,
                'locale' => $locale,
            ]);

            return $claim->fresh();
        } catch (\Throwable $exception) {
            $claim->delete();

            Log::warning('Abandoned cart reminder failed', [
                'order_id' => $order->id,
                'sequence' => $sequence,
                'source' => $source,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function sendTest(
        Order $order,
        string $recipient,
        string $locale = LocaleHelper::DEFAULT_LOCALE,
        int $sequence = 1
    ): void
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Testni primatelj nije valjana e-mail adresa.');
        }

        $locale = in_array($locale, [LocaleHelper::DEFAULT_LOCALE, LocaleHelper::ENGLISH_LOCALE], true)
            ? $locale
            : LocaleHelper::DEFAULT_LOCALE;
        $sequence = in_array($sequence, [1, 2], true) ? $sequence : 1;
        $order->loadMissing(['products.product', 'totals']);

        Mail::to($recipient)->send((new AbandonedCartReminderMail(
            $order,
            LocaleHelper::route('kosarica', [], true, $locale),
            $sequence
        ))->locale($locale));
    }

    public function canRecover(Order $order): bool
    {
        return (int) $order->order_status_id === (int) config('settings.order.status.unfinished', 8)
            && Carbon::parse($order->created_at)->gte($this->startsAt());
    }

    public function scheduledFor(Order $order, int $sequence): Carbon
    {
        $delay = $this->delayMinutes($sequence);
        if ($delay === null) {
            throw new RuntimeException('Nepoznat redni broj podsjetnika.');
        }

        return Carbon::parse($order->unfinished_at ?: $order->created_at)->addMinutes($delay);
    }

    private function claim(
        Order $order,
        int $sequence,
        Carbon $scheduledFor,
        string $email,
        string $locale,
        string $source
    ): AbandonedCartReminder {
        $isNewClaim = false;
        $claim = AbandonedCartReminder::query()
            ->where('order_id', $order->id)
            ->where('sequence', $sequence)
            ->first();

        if (! $claim) {
            try {
                $claim = AbandonedCartReminder::query()->create([
                    'order_id' => $order->id,
                    'sequence' => $sequence,
                    'scheduled_for' => $scheduledFor,
                    'source' => $source,
                    'recipient_email' => $email,
                    'locale' => $locale,
                    'sent_by' => auth()->id(),
                ]);
                $isNewClaim = true;
            } catch (QueryException $exception) {
                $claim = AbandonedCartReminder::query()
                    ->where('order_id', $order->id)
                    ->where('sequence', $sequence)
                    ->first();

                if (! $claim) {
                    throw $exception;
                }
            }
        }

        if ($claim->sent_at) {
            throw new RuntimeException('Ovaj podsjetnik već je poslan.');
        }

        $claimTimeout = max(1, (int) config('abandoned_cart.claim_timeout_minutes', 15));
        if (! $isNewClaim && $claim->updated_at && $claim->updated_at->gt(now()->subMinutes($claimTimeout))) {
            throw new RuntimeException('Slanje ovog podsjetnika već je u tijeku.');
        }

        $claim->forceFill([
            'scheduled_for' => $scheduledFor,
            'source' => $source,
            'recipient_email' => $email,
            'locale' => $locale,
            'sent_by' => auth()->id(),
            'updated_at' => now(),
        ])->save();

        return $claim;
    }

    private function recoveryUrl(Order $order, string $locale): string
    {
        $routeName = $locale === LocaleHelper::ENGLISH_LOCALE
            ? 'en.abandoned-cart.restore'
            : 'abandoned-cart.restore';

        return URL::temporarySignedRoute(
            $routeName,
            now()->addDays(max(1, (int) config('abandoned_cart.recovery_link_days', 7))),
            ['order' => $order->id]
        );
    }

    private function storeHistory(Order $order, int $sequence, string $source, string $email): void
    {
        OrderHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => auth()->id() ?: 0,
            'status' => (int) $order->order_status_id,
            'comment' => sprintf(
                '%d. podsjetnik za nedovršenu košaricu poslan je %s na %s.',
                $sequence,
                $source === AbandonedCartReminder::SOURCE_MANUAL ? 'ručno' : 'automatski',
                $email
            ),
        ]);
    }

    private function baseEligibilityError(Order $order): ?string
    {
        if (! $this->isAvailable()) {
            return 'Evidencija podsjetnika još nije instalirana u bazi.';
        }

        if (! config('abandoned_cart.enabled', true)) {
            return 'Podsjetnici za nedovršene košarice su isključeni.';
        }

        if ((int) $order->order_status_id !== (int) config('settings.order.status.unfinished', 8)) {
            return 'Podsjetnik se može poslati samo za nedovršenu narudžbu.';
        }

        if (! $order->created_at || Carbon::parse($order->created_at)->lt($this->startsAt())) {
            return 'Podsjetnici se ne šalju retroaktivno za stare narudžbe.';
        }

        if (! filter_var(trim((string) $order->payment_email), FILTER_VALIDATE_EMAIL)) {
            return 'Narudžba nema valjanu e-mail adresu kupca.';
        }

        $productsCount = $order->relationLoaded('products')
            ? $order->products->count()
            : (int) ($order->order_products_count ?? $order->orderProducts()->count());

        return $productsCount > 0 ? null : 'Narudžba nema artikala za podsjetnik.';
    }

    private function sentReminders(Order $order): Collection
    {
        $reminders = $order->relationLoaded('abandonedCartReminders')
            ? $order->abandonedCartReminders
            : $order->abandonedCartReminders()->get();

        return $reminders->whereNotNull('sent_at')->keyBy('sequence');
    }

    private function nextSequenceFrom(Collection $sent): ?int
    {
        for ($sequence = 1; $sequence <= (int) config('abandoned_cart.max_reminders', 2); $sequence++) {
            if (! $sent->has($sequence)) {
                return $sequence;
            }
        }

        return null;
    }

    private function delayMinutes(int $sequence): ?int
    {
        $delay = config('abandoned_cart.delays_minutes.' . $sequence);

        return $delay !== null ? max(0, (int) $delay) : null;
    }

    private function startsAt(): Carbon
    {
        return Carbon::parse(
            (string) config('abandoned_cart.starts_at', '2026-08-09 00:00:00'),
            (string) config('app.timezone', 'Europe/Zagreb')
        );
    }
}
