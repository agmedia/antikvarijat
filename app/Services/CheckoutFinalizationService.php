<?php

namespace App\Services;

use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Orders\Order;
use App\Models\OrderNotificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CheckoutFinalizationService
{
    /** @var GiftVoucherService */
    private $giftVouchers;

    /** @var OrderNotificationService */
    private $notifications;

    public function __construct(
        GiftVoucherService $giftVouchers,
        OrderNotificationService $notifications
    ) {
        $this->giftVouchers = $giftVouchers;
        $this->notifications = $notifications;
    }

    /**
     * Persist every order-side checkout effect before the payment return redirects.
     *
     * The checkout marker is the idempotency claim. Stock and the notification
     * outbox are written in the same transaction, so a repeated payment return
     * cannot decrement stock or enqueue a message twice.
     */
    public function finalize(
        Order $order,
        array $notificationKinds = [
            OrderNotificationDelivery::KIND_ADMIN,
            OrderNotificationDelivery::KIND_CUSTOMER,
        ]
    ): bool {
        $this->ensureConfirmed($order);
        $this->giftVouchers->completeCheckout($order);

        $processedNow = DB::transaction(function () use ($order, $notificationKinds) {
            $claimed = Order::query()
                ->where('id', $order->id)
                ->whereNull('checkout_processed_at')
                ->update([
                    'checkout_processed_at' => now(),
                    'updated_at' => now(),
                ]);

            if (! $claimed) {
                return false;
            }

            $persistedOrder = Order::query()->findOrFail((int) $order->id);

            $this->notifications->enqueue($persistedOrder, $notificationKinds);
            $this->decreaseStock($persistedOrder);

            return true;
        }, 3);

        if ($processedNow) {
            try {
                NewsletterSubscriber::attachOrderToEmail(
                    (string) $order->payment_email,
                    (int) $order->id
                );
            } catch (Throwable $exception) {
                // Newsletter attribution is non-critical and must never turn a
                // completed payment into a partially finalized checkout.
                Log::warning('Newsletter order attribution failed after checkout finalization.', [
                    'order_id' => $order->id,
                    'exception' => get_class($exception),
                ]);
            }
        }

        return $processedNow;
    }

    /**
     * Recover successful Corvus returns that died after payment was recorded
     * but before the checkout side effects were committed.
     */
    public function recoverIncompleteCorvusOrders(int $limit = 25): array
    {
        $limit = max(1, $limit);
        $afterOrderId = max(0, (int) config(
            'order_notifications.checkout_recovery_after_order_id',
            27046
        ));
        $graceSeconds = max(60, (int) config(
            'order_notifications.checkout_recovery_grace_seconds',
            60
        ));
        $summary = [
            'selected' => 0,
            'recovered' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $orders = Order::query()
            ->where('id', '>', $afterOrderId)
            ->whereNull('checkout_processed_at')
            ->where('order_status_id', (int) config('settings.order.status.paid'))
            ->whereIn('payment_code', ['corvus', 'corvus_wallets'])
            ->where('updated_at', '<=', now()->subSeconds($graceSeconds))
            ->whereHas('transactions', function ($query) {
                $query->where('success', 1);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $summary['selected'] = $orders->count();

        foreach ($orders as $order) {
            try {
                if ($this->finalize($order)) {
                    $summary['recovered']++;
                } else {
                    $summary['skipped']++;
                }
            } catch (Throwable $exception) {
                $summary['failed']++;

                Log::error('Automatic Corvus checkout recovery failed.', [
                    'order_id' => $order->id,
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    private function decreaseStock(Order $order): void
    {
        $lines = DB::table('order_products')
            ->where('order_id', (int) $order->id)
            ->get(['product_id', 'quantity']);

        foreach ($lines as $line) {
            $productId = (int) $line->product_id;
            $orderedQuantity = max(0, (int) $line->quantity);

            if ($productId < 1 || $orderedQuantity < 1) {
                continue;
            }

            $product = DB::table('products')
                ->where('id', $productId)
                ->lockForUpdate()
                ->first(['id', 'quantity']);

            if (! $product) {
                Log::warning('Checkout stock item no longer exists.', [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'ordered_quantity' => $orderedQuantity,
                ]);

                continue;
            }

            $currentQuantity = max(0, (int) $product->quantity);
            $remainingQuantity = max(0, $currentQuantity - $orderedQuantity);

            if ($currentQuantity < $orderedQuantity) {
                Log::warning('Checkout stock was already lower than the ordered quantity.', [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'ordered_quantity' => $orderedQuantity,
                    'current_quantity' => $currentQuantity,
                ]);
            }

            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'quantity' => $remainingQuantity,
                    'updated_at' => now(),
                ]);
        }
    }

    private function ensureConfirmed(Order $order): void
    {
        $confirmedStatuses = array_values(array_unique([
            (int) config('settings.order.status.new'),
            (int) config('settings.order.status.paid'),
            (int) config('settings.order.status.send'),
        ]));

        if (! $order->exists || ! $order->id || ! in_array((int) $order->order_status_id, $confirmedStatuses, true)) {
            throw new RuntimeException('Checkout can only be finalized for a confirmed order.');
        }
    }
}
