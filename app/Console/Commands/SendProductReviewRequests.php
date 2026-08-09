<?php

namespace App\Console\Commands;

use App\Mail\ProductReviewRequestMail;
use App\Models\Back\Orders\Order;
use App\Models\ProductReviewInvitation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SendProductReviewRequests extends Command
{
    protected $signature = 'reviews:send-requests
                            {--dry-run : Prikaži kvalificirane narudžbe bez upisa i slanja}
                            {--date= : Datum dnevnog pokretanja (YYYY-MM-DD), zadano danas}
                            {--limit= : Najveći broj narudžbi u ovom pokretanju}';

    protected $description = 'Šalje jednokratni zahtjev za recenziju nakon zadanog broja dana';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! config('reviews.request_emails_enabled') && ! $dryRun) {
            $this->warn('Slanje zahtjeva za recenziju je isključeno (REVIEW_REQUEST_EMAILS_ENABLED=false).');

            return 0;
        }

        $runDate = now()->startOfDay();
        if ($this->option('date')) {
            $date = (string) $this->option('date');

            try {
                $runDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            } catch (\Throwable $exception) {
                $this->error('Opcija --date mora biti valjan datum u formatu YYYY-MM-DD.');

                return 1;
            }

            if ($runDate->format('Y-m-d') !== $date) {
                $this->error('Opcija --date mora biti valjan datum u formatu YYYY-MM-DD.');

                return 1;
            }
        }
        $eligibleDay = $runDate->copy()->subDays(max(1, (int) config('reviews.request_delay_days', 30)));
        $eligibleFrom = $eligibleDay->copy()->startOfDay();
        $eligibleTo = $eligibleDay->copy()->endOfDay();
        $maxAttempts = max(1, (int) config('reviews.request_max_attempts', 3));

        $query = $this->eligibleOrders($eligibleFrom, $eligibleTo, $maxAttempts);
        if ($this->option('limit')) {
            $query->limit(max(1, min((int) $this->option('limit'), 1000)));
        }
        $orders = $query->get();

        if ($dryRun) {
            $this->table(
                ['Narudžba', 'Status', 'Kvalificirana od', 'E-mail'],
                $orders->map(fn (Order $order) => [
                    $order->id,
                    $order->order_status_id,
                    $this->eligibleAt($order)->format('d.m.Y. H:i'),
                    $this->maskedEmail((string) $order->payment_email),
                ])->all()
            );
            $this->info('Dry-run: ' . $orders->count() . ' kvalificiranih narudžbi; ništa nije poslano.');

            return 0;
        }

        $sent = 0;
        $failed = 0;

        foreach ($orders as $order) {
            $email = mb_strtolower(trim((string) $order->payment_email));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $plainToken = Str::random(64);
            $invitation = ProductReviewInvitation::query()->firstOrNew(['order_id' => $order->id]);
            if ($invitation->sent_at) {
                continue;
            }

            $invitation->forceFill([
                'token_hash' => ProductReviewInvitation::hashToken($plainToken),
                'recipient_email' => $email,
                'recipient_name' => trim($order->payment_fname . ' ' . $order->payment_lname),
                'locale' => $this->locale(),
                'eligible_at' => $this->eligibleAt($order),
                'attempts' => ((int) $invitation->attempts) + 1,
                'last_attempt_at' => now(),
                'last_error' => null,
            ])->save();

            $reviewUrl = URL::temporarySignedRoute(
                'product-review-invitations.show',
                now()->addDays(max(1, (int) config('reviews.request_link_days', 180))),
                ['token' => $plainToken]
            );

            try {
                Mail::to($email)
                    ->locale($invitation->locale)
                    ->send(new ProductReviewRequestMail($invitation, $reviewUrl));

                $invitation->forceFill([
                    'sent_at' => now(),
                    'last_error' => null,
                ])->save();
                $sent++;
            } catch (\Throwable $exception) {
                $failed++;
                $invitation->forceFill([
                    'last_error' => Str::limit($exception->getMessage(), 5000, ''),
                ])->save();

                Log::warning('Product review request mail failed.', [
                    'order_id' => $order->id,
                    'invitation_id' => $invitation->id,
                    'attempt' => $invitation->attempts,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Zahtjevi za recenziju: poslano {$sent}, neuspjelo {$failed}.");

        return $failed > 0 ? 1 : 0;
    }

    private function eligibleOrders(Carbon $eligibleFrom, Carbon $eligibleTo, int $maxAttempts): Builder
    {
        return Order::query()
            ->select('orders.*')
            ->addSelect([
                'sent_status_at' => DB::table('order_history')
                    ->selectRaw('MIN(created_at)')
                    ->whereColumn('order_history.order_id', 'orders.id')
                    ->where('order_history.status', (int) config('settings.order.status.send')),
            ])
            ->whereIn('orders.order_status_id', Order::reviewEligibleStatusIds())
            ->whereNotNull('orders.payment_email')
            ->whereRaw("TRIM(orders.payment_email) <> ''")
            ->where(function ($query) use ($eligibleFrom, $eligibleTo) {
                $query->whereExists(function ($history) use ($eligibleFrom, $eligibleTo) {
                    $history->select(DB::raw(1))
                        ->from('order_history')
                        ->whereColumn('order_history.order_id', 'orders.id')
                        ->where('order_history.status', (int) config('settings.order.status.send'))
                        ->whereBetween('order_history.created_at', [$eligibleFrom, $eligibleTo]);
                })->orWhere(function ($fallback) use ($eligibleFrom, $eligibleTo) {
                    $fallback->whereNotExists(function ($history) {
                        $history->select(DB::raw(1))
                            ->from('order_history')
                            ->whereColumn('order_history.order_id', 'orders.id')
                            ->where('order_history.status', (int) config('settings.order.status.send'));
                    })->whereRaw(
                        'COALESCE(orders.checkout_processed_at, orders.created_at) BETWEEN ? AND ?',
                        [$eligibleFrom, $eligibleTo]
                    );
                });
            })
            ->whereExists(function ($items) {
                $items->select(DB::raw(1))
                    ->from('order_products')
                    ->join('products', 'products.id', '=', 'order_products.product_id')
                    ->whereColumn('order_products.order_id', 'orders.id')
                    ->where('order_products.product_id', '>', 0)
                    ->whereNotExists(function ($reviews) {
                        $reviews->select(DB::raw(1))
                            ->from('product_reviews')
                            ->whereColumn('product_reviews.order_id', 'orders.id')
                            ->whereColumn('product_reviews.product_id', 'order_products.product_id');
                    });
            })
            ->where(function ($query) use ($maxAttempts) {
                $query->whereNotExists(function ($invitations) {
                    $invitations->select(DB::raw(1))
                        ->from('product_review_invitations')
                        ->whereColumn('product_review_invitations.order_id', 'orders.id');
                })->orWhereExists(function ($invitations) use ($maxAttempts) {
                    $invitations->select(DB::raw(1))
                        ->from('product_review_invitations')
                        ->whereColumn('product_review_invitations.order_id', 'orders.id')
                        ->whereNull('product_review_invitations.sent_at')
                        ->where('product_review_invitations.attempts', '<', $maxAttempts);
                });
            })
            ->orderByRaw('COALESCE(sent_status_at, orders.checkout_processed_at, orders.created_at) ASC');
    }

    private function eligibleAt(Order $order): Carbon
    {
        return Carbon::parse($order->sent_status_at ?: $order->checkout_processed_at ?: $order->created_at);
    }

    private function locale(): string
    {
        $locale = (string) config('reviews.default_locale', 'hr');

        return in_array($locale, ['hr', 'en'], true) ? $locale : 'hr';
    }

    private function maskedEmail(string $email): string
    {
        return preg_replace('/^(.).*(@.+)$/', '$1***$2', trim($email)) ?: '***';
    }
}
