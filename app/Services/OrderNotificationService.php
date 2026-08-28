<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Orders\Order;
use App\Models\OrderNotificationDelivery;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class OrderNotificationService
{
    /** @var bool|null */
    private $available;

    private const REQUIRED_COLUMNS = [
        'order_id',
        'kind',
        'recipient_email',
        'locale',
        'attempts',
        'available_at',
        'claimed_at',
        'last_attempt_at',
        'sent_at',
        'failed_at',
        'last_error',
    ];

    /**
     * Check whether the durable delivery outbox has been installed.
     */
    public function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        if (! Schema::hasTable('order_notification_deliveries')) {
            return $this->available = false;
        }

        $columns = Schema::getColumnListing('order_notification_deliveries');

        return $this->available = array_diff(self::REQUIRED_COLUMNS, $columns) === [];
    }

    /**
     * Persist one immutable recipient snapshot for each notification kind.
     */
    public function enqueue(
        Order $order,
        array $kinds = [OrderNotificationDelivery::KIND_ADMIN, OrderNotificationDelivery::KIND_CUSTOMER]
    ): void
    {
        if (! $this->isAvailable()) {
            Log::warning('Order notification outbox is unavailable; delivery was not enqueued.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        // checkout_processed_at is commonly written with a query builder just
        // before this method is called, leaving the captured Eloquent instance
        // stale. Always snapshot the authoritative database row.
        $persistedOrder = Order::query()->find((int) $order->id);
        if (! $persistedOrder) {
            throw new RuntimeException('Order was not found.');
        }

        $order = $persistedOrder;
        $eligibilityError = $this->eligibilityError($order);
        if ($eligibilityError !== null) {
            throw new RuntimeException($eligibilityError);
        }

        $kinds = $this->normalizeKinds($kinds);
        $locale = $this->resolvedLocale($order);
        $recipients = [
            OrderNotificationDelivery::KIND_ADMIN => trim((string) config('mail.admin')),
            OrderNotificationDelivery::KIND_CUSTOMER => trim((string) $order->payment_email),
        ];

        // Deliberately enqueue admin first. The unique key makes retries and
        // repeated checkout callbacks idempotent without changing snapshots.
        // The transaction also prevents a process death between the admin and
        // customer inserts from leaving a half-enqueued order.
        DB::transaction(function () use ($kinds, $recipients, $order, $locale) {
            foreach ($kinds as $kind) {
                try {
                    OrderNotificationDelivery::query()->firstOrCreate(
                        [
                            'order_id' => (int) $order->id,
                            'kind' => $kind,
                        ],
                        [
                            'recipient_email' => $recipients[$kind],
                            'locale' => $locale,
                            'attempts' => 0,
                            'available_at' => now(),
                        ]
                    );
                } catch (QueryException $exception) {
                    // A concurrent callback can win the unique(order_id, kind)
                    // insert. That is success as long as its row now exists.
                    $exists = OrderNotificationDelivery::query()
                        ->where('order_id', (int) $order->id)
                        ->where('kind', $kind)
                        ->exists();

                    if (! $exists) {
                        throw $exception;
                    }
                }
            }
        });
    }

    /**
     * Enqueue missing rows and immediately attempt the selected deliveries.
     *
     * If the outbox migration has not been deployed yet, both messages are
     * still sent through the legacy direct path so checkout never silently
     * loses notifications during a rolling deployment.
     */
    public function sendForOrder(
        Order $order,
        array $kinds = [OrderNotificationDelivery::KIND_ADMIN, OrderNotificationDelivery::KIND_CUSTOMER],
        bool $force = false
    ): array {
        $kinds = $this->normalizeKinds($kinds);

        if (! $this->isAvailable()) {
            return $this->sendLegacy($order, $kinds);
        }

        $this->enqueue($order, $kinds);
        $results = [];

        foreach ($kinds as $kind) {
            $delivery = OrderNotificationDelivery::query()
                ->where('order_id', (int) $order->id)
                ->where('kind', $kind)
                ->first();

            if (! $delivery) {
                $results[$kind] = $this->result($kind, 'missing', null, 'Delivery row was not found.');
                continue;
            }

            $results[$kind] = $this->deliver($delivery, $force);
        }

        return $results;
    }

    /**
     * Process due outbox rows. The optional kind filter is used by the CLI;
     * callers using the documented two arguments continue to process both.
     */
    public function processPending(
        int $limit,
        int $maxSeconds,
        array $kinds = [OrderNotificationDelivery::KIND_ADMIN, OrderNotificationDelivery::KIND_CUSTOMER]
    ): array {
        $limit = max(1, $limit);
        $maxSeconds = max(1, $maxSeconds);
        $kinds = $this->normalizeKinds($kinds);
        $startedAt = microtime(true);
        $summary = [
            'available' => $this->isAvailable(),
            'selected' => 0,
            'attempted' => 0,
            'sent' => 0,
            'failed' => 0,
            'deferred' => 0,
            'skipped' => 0,
            'results' => [],
        ];

        if (! $summary['available']) {
            return $summary;
        }

        $staleBefore = now()->subMinutes($this->staleClaimMinutes());
        $deliveries = OrderNotificationDelivery::query()
            ->whereIn('kind', $kinds)
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->where(function ($query) {
                $query->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->where(function ($query) use ($staleBefore) {
                $query->whereNull('claimed_at')->orWhere('claimed_at', '<=', $staleBefore);
            })
            ->orderByRaw("CASE WHEN kind = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('order_id')
            ->limit($limit)
            ->get();

        $summary['selected'] = $deliveries->count();

        foreach ($deliveries as $delivery) {
            if ((microtime(true) - $startedAt) >= $maxSeconds) {
                break;
            }

            $result = $this->deliver($delivery, false);
            $summary['results'][] = $result;

            if (! empty($result['attempted'])) {
                $summary['attempted']++;
            }

            if ($result['status'] === 'sent') {
                $summary['sent']++;
            } elseif ($result['status'] === 'failed' || $result['status'] === 'permanent_failure') {
                $summary['failed']++;
            } elseif ($result['status'] === 'busy' || $result['status'] === 'deferred') {
                $summary['deferred']++;
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    /**
     * Return an operator-friendly status without creating or sending anything.
     */
    public function statusFor(Order $order): array
    {
        $status = [
            'available' => $this->isAvailable(),
            'order_id' => (int) $order->id,
            'deliveries' => [],
        ];

        if (! $status['available']) {
            return $status;
        }

        $deliveries = OrderNotificationDelivery::query()
            ->where('order_id', (int) $order->id)
            ->get()
            ->keyBy('kind');

        foreach ($this->normalizeKinds([]) as $kind) {
            $delivery = $deliveries->get($kind);
            $status['deliveries'][$kind] = $delivery
                ? $this->deliveryState($delivery)
                : [
                    'kind' => $kind,
                    'status' => 'missing',
                    'recipient_email' => null,
                    'locale' => null,
                    'attempts' => 0,
                    'available_at' => null,
                    'claimed_at' => null,
                    'last_attempt_at' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                    'last_error' => null,
                ];
        }

        return $status;
    }

    private function deliver(OrderNotificationDelivery $delivery, bool $force): array
    {
        $claimed = $this->claim($delivery, $force);

        if (! $claimed) {
            $fresh = $delivery->fresh();
            $state = $fresh ? $this->deliveryState($fresh) : ['status' => 'missing'];
            $status = $state['status'];

            if ($status === 'sent') {
                $status = 'already_sent';
            } elseif ($status === 'pending') {
                $status = 'busy';
            }

            return $this->result(
                (string) $delivery->kind,
                $status,
                $fresh,
                isset($state['last_error']) ? $state['last_error'] : null
            );
        }

        $kind = (string) $claimed->kind;
        $recipient = trim((string) $claimed->recipient_email);

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return $this->recordFailure($claimed, 'Recipient e-mail address is invalid.', true);
        }

        $order = Order::query()
            ->with(['products.product', 'totals', 'transactions'])
            ->find((int) $claimed->order_id);

        if (! $order) {
            return $this->recordFailure($claimed, 'Order was not found.', true);
        }

        try {
            $mail = $kind === OrderNotificationDelivery::KIND_ADMIN
                ? new OrderReceived($order)
                : new OrderSent($order);

            Mail::to($recipient)->send($mail->locale($this->normalizedLocale($claimed->locale)));

            $sentAt = now();
            OrderNotificationDelivery::query()
                ->whereKey($claimed->id)
                ->update([
                    'sent_at' => $sentAt,
                    'failed_at' => null,
                    'claimed_at' => null,
                    'available_at' => $sentAt,
                    'last_error' => null,
                    'updated_at' => $sentAt,
                ]);

            $claimed = $claimed->fresh();

            Log::info('Order notification delivered.', [
                'order_id' => $order->id,
                'kind' => $kind,
                'delivery_id' => $delivery->id,
                'recipient_email' => $recipient,
                'attempts' => $claimed ? $claimed->attempts : null,
            ]);

            return $this->result($kind, 'sent', $claimed, null, true);
        } catch (\Throwable $exception) {
            return $this->recordFailure($claimed, $exception->getMessage(), false);
        }
    }

    /**
     * Atomically reserve one row across web callbacks and scheduler workers.
     */
    private function claim(OrderNotificationDelivery $delivery, bool $force)
    {
        $now = now();
        $staleBefore = $now->copy()->subMinutes($this->staleClaimMinutes());
        $query = OrderNotificationDelivery::query()
            ->whereKey($delivery->id)
            ->where(function ($claim) use ($staleBefore) {
                $claim->whereNull('claimed_at')->orWhere('claimed_at', '<=', $staleBefore);
            });

        if (! $force) {
            $query
                ->whereNull('sent_at')
                ->whereNull('failed_at')
                ->where(function ($available) use ($now) {
                    $available->whereNull('available_at')->orWhere('available_at', '<=', $now);
                });
        }

        $claimed = $query->update([
            'claimed_at' => $now,
            'last_attempt_at' => $now,
            'attempts' => DB::raw('attempts + 1'),
            'last_error' => null,
            'failed_at' => $force ? null : $delivery->failed_at,
            'updated_at' => $now,
        ]);

        return $claimed === 1 ? $delivery->fresh() : null;
    }

    private function recordFailure(
        OrderNotificationDelivery $delivery,
        string $message,
        bool $permanent
    ): array {
        $delivery = $delivery->fresh() ?: $delivery;
        $attempts = max(1, (int) $delivery->attempts);
        $maxAttempts = max(0, (int) config('order_notifications.max_attempts', 0));
        $terminal = $permanent || ($maxAttempts > 0 && $attempts >= $maxAttempts);
        $failedAt = $terminal ? now() : null;
        $availableAt = $terminal ? now() : now()->addMinutes($this->retryDelayMinutes($attempts));
        $error = Str::limit(trim($message) !== '' ? trim($message) : 'Unknown mail delivery error.', 2000, '');

        OrderNotificationDelivery::query()
            ->whereKey($delivery->id)
            ->update([
                'claimed_at' => null,
                'available_at' => $availableAt,
                'failed_at' => $failedAt,
                'last_error' => $error,
                'updated_at' => now(),
            ]);

        $delivery = $delivery->fresh();

        Log::warning('Order notification delivery failed.', [
            'order_id' => $delivery ? $delivery->order_id : null,
            'kind' => $delivery ? $delivery->kind : null,
            'delivery_id' => $delivery ? $delivery->id : null,
            'recipient_email' => $delivery ? $delivery->recipient_email : null,
            'attempts' => $attempts,
            'terminal' => $terminal,
            'available_at' => $delivery ? $delivery->available_at : null,
            'error' => $error,
        ]);

        return $this->result(
            $delivery ? (string) $delivery->kind : '',
            $terminal ? 'permanent_failure' : 'failed',
            $delivery,
            $error,
            true
        );
    }

    private function sendLegacy(Order $order, array $kinds): array
    {
        $results = [];
        $locale = $this->resolvedLocale($order);
        $order->loadMissing(['products.product', 'totals', 'transactions']);

        foreach ($kinds as $kind) {
            $recipient = $kind === OrderNotificationDelivery::KIND_ADMIN
                ? trim((string) config('mail.admin'))
                : trim((string) $order->payment_email);

            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $results[$kind] = $this->result($kind, 'permanent_failure', null, 'Recipient e-mail address is invalid.');
                continue;
            }

            try {
                $mail = $kind === OrderNotificationDelivery::KIND_ADMIN
                    ? new OrderReceived($order)
                    : new OrderSent($order);

                Mail::to($recipient)->send($mail->locale($locale));
                $results[$kind] = $this->result($kind, 'sent_legacy', null, null, true);
            } catch (\Throwable $exception) {
                Log::warning('Legacy order notification delivery failed.', [
                    'order_id' => $order->id,
                    'kind' => $kind,
                    'recipient_email' => $recipient,
                    'error' => $exception->getMessage(),
                ]);

                $results[$kind] = $this->result($kind, 'failed', null, $exception->getMessage(), true);
            }
        }

        return $results;
    }

    private function deliveryState(OrderNotificationDelivery $delivery): array
    {
        if ($delivery->sent_at) {
            $state = 'sent';
        } elseif ($delivery->failed_at) {
            $state = 'permanent_failure';
        } elseif ($delivery->claimed_at
            && $delivery->claimed_at->gt(now()->subMinutes($this->staleClaimMinutes()))) {
            $state = 'busy';
        } elseif ($delivery->available_at && $delivery->available_at->isFuture()) {
            $state = 'deferred';
        } else {
            $state = 'pending';
        }

        return [
            'kind' => (string) $delivery->kind,
            'status' => $state,
            'recipient_email' => (string) $delivery->recipient_email,
            'locale' => (string) $delivery->locale,
            'attempts' => (int) $delivery->attempts,
            'available_at' => $delivery->available_at,
            'claimed_at' => $delivery->claimed_at,
            'last_attempt_at' => $delivery->last_attempt_at,
            'sent_at' => $delivery->sent_at,
            'failed_at' => $delivery->failed_at,
            'last_error' => $delivery->last_error,
        ];
    }

    private function result(
        string $kind,
        string $status,
        $delivery = null,
        $error = null,
        bool $attempted = false
    ): array {
        return [
            'delivery_id' => $delivery ? (int) $delivery->id : null,
            'order_id' => $delivery ? (int) $delivery->order_id : null,
            'kind' => $kind,
            'status' => $status,
            'attempted' => $attempted,
            'recipient_email' => $delivery ? (string) $delivery->recipient_email : null,
            'locale' => $delivery ? (string) $delivery->locale : null,
            'attempts' => $delivery ? (int) $delivery->attempts : 0,
            'available_at' => $delivery ? $delivery->available_at : null,
            'sent_at' => $delivery ? $delivery->sent_at : null,
            'failed_at' => $delivery ? $delivery->failed_at : null,
            'error' => $error,
        ];
    }

    private function normalizeKinds(array $kinds): array
    {
        if ($kinds === []) {
            return [
                OrderNotificationDelivery::KIND_ADMIN,
                OrderNotificationDelivery::KIND_CUSTOMER,
            ];
        }

        $requested = [];

        foreach ($kinds as $kind) {
            $kind = mb_strtolower(trim((string) $kind));
            if (! in_array($kind, [
                OrderNotificationDelivery::KIND_ADMIN,
                OrderNotificationDelivery::KIND_CUSTOMER,
            ], true)) {
                throw new RuntimeException('Unknown order notification kind: ' . $kind);
            }

            $requested[$kind] = true;
        }

        // Always return admin first, independently of caller input order.
        return array_values(array_filter(
            [
                OrderNotificationDelivery::KIND_ADMIN,
                OrderNotificationDelivery::KIND_CUSTOMER,
            ],
            function ($kind) use ($requested) {
                return isset($requested[$kind]);
            }
        ));
    }

    private function eligibilityError(Order $order)
    {
        if (! $order->exists || ! $order->id) {
            return 'Order must be persisted before notifications are enqueued.';
        }

        $confirmedStatuses = array_values(array_unique([
            (int) config('settings.order.status.new', 1),
            (int) config('settings.order.status.paid', 3),
            (int) config('settings.order.status.send', 4),
        ]));

        if (! in_array((int) $order->order_status_id, $confirmedStatuses, true)) {
            return 'Notifications can only be enqueued for a confirmed order.';
        }

        $attributes = $order->getAttributes();
        if (array_key_exists('checkout_processed_at', $attributes) && ! $order->checkout_processed_at) {
            return 'Notifications can only be enqueued after checkout is processed.';
        }

        return null;
    }

    private function resolvedLocale(Order $order): string
    {
        try {
            return $this->normalizedLocale($order->resolvedLocale());
        } catch (\Throwable $exception) {
            return 'hr';
        }
    }

    private function normalizedLocale($locale): string
    {
        $locale = mb_strtolower(trim((string) $locale));

        return in_array($locale, ['hr', 'en'], true) ? $locale : 'hr';
    }

    private function staleClaimMinutes(): int
    {
        return max(1, (int) config('order_notifications.stale_claim_minutes', 10));
    }

    private function retryDelayMinutes(int $attempts): int
    {
        $base = max(1, (int) config('order_notifications.base_retry_minutes', 2));
        $maximum = max($base, (int) config('order_notifications.max_retry_minutes', 60));
        $exponent = min(10, max(0, $attempts - 1));

        return min($maximum, $base * (2 ** $exponent));
    }
}
