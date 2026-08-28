<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MailchimpOrderSynchronizer
{
    /** @var MailchimpEcommerceService */
    private $mailchimp;

    /** @var bool|null */
    private $columnsAvailable;

    public function __construct(MailchimpEcommerceService $mailchimp)
    {
        $this->mailchimp = $mailchimp;
    }

    public function isAvailable(): bool
    {
        return $this->columnsAreAvailable() && $this->mailchimp->isConfigured();
    }

    /**
     * Sync one already-completed, campaign-attributed order. All exceptions
     * are contained here so Mailchimp can never change the checkout outcome.
     *
     * @return array{ok:bool,skipped:bool,error:?string,stop:bool}
     */
    public function syncOrderId(int $orderId): array
    {
        if ($orderId < 1 || ! $this->columnsAreAvailable()) {
            return $this->result(false, true, 'Mailchimp e-commerce migracija nije dostupna.', true);
        }

        if (! $this->mailchimp->isConfigured()) {
            return $this->result(false, true, 'Mailchimp e-commerce nije konfiguriran.', true);
        }

        try {
            $order = Order::query()->find($orderId);

            if (! $order || ! $order->checkout_processed_at || ! $order->mailchimp_campaign_id) {
                return $this->result(true, true, null, false);
            }

            $financialStatus = $this->mailchimp->financialStatusForStatusId((int) $order->order_status_id);
            if ($financialStatus === null) {
                return $this->result(true, true, null, false);
            }

            if ($order->mailchimp_ecommerce_synced_at
                && (string) $order->mailchimp_ecommerce_financial_status === $financialStatus) {
                return $this->result(true, true, null, false);
            }

            $attemptedAt = now();
            DB::table('orders')->where('id', $order->id)->update([
                'mailchimp_ecommerce_last_attempt_at' => $attemptedAt,
            ]);

            $response = $this->mailchimp->syncOrder($order);

            if ($response['ok']) {
                DB::table('orders')->where('id', $order->id)->update([
                    'mailchimp_ecommerce_synced_at' => now(),
                    'mailchimp_ecommerce_financial_status' => $response['financial_status'] ?? $financialStatus,
                    'mailchimp_ecommerce_last_attempt_at' => $attemptedAt,
                    'mailchimp_ecommerce_last_error' => null,
                ]);

                return $this->result(true, false, null, false);
            }

            $error = $this->sanitizeError($response['error'] ?? null);
            DB::table('orders')->where('id', $order->id)->update([
                'mailchimp_ecommerce_synced_at' => null,
                'mailchimp_ecommerce_last_attempt_at' => $attemptedAt,
                'mailchimp_ecommerce_last_error' => $error,
            ]);

            Log::warning('Mailchimp e-commerce order sync failed.', [
                'order_id' => $order->id,
                'stop' => ! empty($response['stop']),
                'error' => $error,
            ]);

            return $this->result(false, false, $error, ! empty($response['stop']));
        } catch (Throwable $e) {
            Log::warning('Mailchimp e-commerce order sync could not run.', [
                'order_id' => $orderId,
                'exception' => get_class($e),
            ]);

            return $this->result(
                false,
                false,
                'Mailchimp e-commerce trenutno nije dostupan.',
                true
            );
        }
    }

    /**
     * Mark attributed orders as pending without touching any business column
     * or the order's updated_at timestamp.
     *
     * @param int|array<int|string> $orderIds
     */
    public function markForSync($orderIds): void
    {
        if (! $this->columnsAreAvailable()) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($orderIds) ? $orderIds : [$orderIds]
        ))));

        if ($ids === []) {
            return;
        }

        try {
            DB::table('orders')
                ->whereIn('id', $ids)
                ->whereNotNull('mailchimp_campaign_id')
                ->update([
                    'mailchimp_ecommerce_synced_at' => null,
                    'mailchimp_ecommerce_last_attempt_at' => null,
                    'mailchimp_ecommerce_last_error' => null,
                ]);
        } catch (Throwable $e) {
            Log::warning('Mailchimp e-commerce order could not be queued.', [
                'order_count' => count($ids),
                'exception' => get_class($e),
            ]);
        }
    }

    /**
     * Return only new campaign-attributed orders or previously-synced orders
     * whose local financial status changed. Historical unattributed orders are
     * intentionally excluded to prevent an unsafe automatic backfill.
     */
    public function pendingOrders(int $limit = 5): Collection
    {
        if (! $this->isAvailable()) {
            return new Collection();
        }

        $limit = max(1, min($limit, 25));
        $statusMap = $this->statusMap();
        $statusIds = array_keys($statusMap);
        $caseParts = [];
        $bindings = [];

        foreach ($statusMap as $statusId => $financialStatus) {
            $caseParts[] = 'WHEN order_status_id = ? THEN ?';
            $bindings[] = $statusId;
            $bindings[] = $financialStatus;
        }

        $statusCase = 'CASE ' . implode(' ', $caseParts) . " ELSE '' END";

        return Order::query()
            ->whereNotNull('checkout_processed_at')
            ->whereNotNull('mailchimp_campaign_id')
            ->whereIn('order_status_id', $statusIds)
            ->where(function ($query) {
                $query->whereNull('mailchimp_ecommerce_last_attempt_at')
                    ->orWhere('mailchimp_ecommerce_last_attempt_at', '<=', now()->subMinutes(15));
            })
            ->where(function ($query) use ($statusCase, $bindings) {
                $query->whereNull('mailchimp_ecommerce_synced_at')
                    ->orWhereRaw(
                        "COALESCE(mailchimp_ecommerce_financial_status, '') <> {$statusCase}",
                        $bindings
                    );
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /** @return array<int,string> */
    private function statusMap(): array
    {
        $map = [];

        foreach ([
            config('settings.order.status.new', 1),
            config('settings.order.status.awaiting_payment', 2),
            config('settings.order.status.paid', 3),
            config('settings.order.status.send', 4),
            config('settings.order.status.canceled', 5),
            config('settings.order.status.refunded', 6),
            config('settings.order.status.declined', 7),
        ] as $statusId) {
            $financialStatus = $this->mailchimp->financialStatusForStatusId((int) $statusId);

            if ($financialStatus !== null) {
                $map[(int) $statusId] = $financialStatus;
            }
        }

        return $map;
    }

    private function sanitizeError($error): string
    {
        $error = trim(strip_tags((string) $error));
        $error = (string) preg_replace(
            '/[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+/i',
            '[redacted-email]',
            $error
        );

        return Str::limit($error !== '' ? $error : 'Neočekivana Mailchimp greška.', 1000, '…');
    }

    /** @return array{ok:bool,skipped:bool,error:?string,stop:bool} */
    private function result(bool $ok, bool $skipped, ?string $error, bool $stop): array
    {
        return compact('ok', 'skipped', 'error', 'stop');
    }

    private function columnsAreAvailable(): bool
    {
        if ($this->columnsAvailable !== null) {
            return $this->columnsAvailable;
        }

        try {
            $required = [
                'mailchimp_campaign_id',
                'mailchimp_ecommerce_synced_at',
                'mailchimp_ecommerce_financial_status',
                'mailchimp_ecommerce_last_attempt_at',
                'mailchimp_ecommerce_last_error',
            ];

            $this->columnsAvailable = Schema::hasTable('orders');

            foreach ($required as $column) {
                $this->columnsAvailable = $this->columnsAvailable
                    && Schema::hasColumn('orders', $column);
            }
        } catch (Throwable $e) {
            $this->columnsAvailable = false;
        }

        return $this->columnsAvailable;
    }
}
