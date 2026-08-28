<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderNotificationDeliveriesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('order_notification_deliveries')) {
            Schema::create('order_notification_deliveries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id');
                $table->string('kind', 20);
                $table->string('recipient_email', 191);
                $table->string('locale', 5)->default('hr');
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('available_at')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['order_id', 'kind'], 'order_notification_order_kind_unique');
                $table->index('order_id', 'order_notification_order_index');
                $table->index(
                    ['sent_at', 'failed_at', 'available_at'],
                    'order_notification_pending_index'
                );
            });
        }

        $this->seedVerifiedMissingDeliveries();
    }

    public function down()
    {
        // Delivery/audit history is intentionally retained on rollback.
    }

    /**
     * Recover the two gaps verified in SMTP2GO activity on 28 August 2026.
     *
     * #26030: customer confirmation was accepted; admin notification was not.
     * #26031: neither notification reached SMTP2GO.
     */
    private function seedVerifiedMissingDeliveries(): void
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'checkout_processed_at')
            || ! Schema::hasTable('order_notification_deliveries')) {
            return;
        }

        $hasLocale = Schema::hasColumn('orders', 'locale');
        $columns = ['id', 'payment_email'];

        if ($hasLocale) {
            $columns[] = 'locale';
        }

        $orders = DB::table('orders')
            ->whereIn('id', [26030, 26031])
            ->whereNotNull('checkout_processed_at')
            ->whereIn('order_status_id', array_values(array_unique([
                (int) config('settings.order.status.new', 1),
                (int) config('settings.order.status.paid', 3),
                (int) config('settings.order.status.send', 4),
            ])))
            ->get($columns)
            ->keyBy('id');

        foreach ([26030, 26031] as $orderId) {
            $order = $orders->get($orderId);

            if (! $order) {
                continue;
            }

            $locale = $hasLocale && in_array($order->locale, ['hr', 'en'], true)
                ? $order->locale
                : 'hr';

            $this->insertDelivery(
                $orderId,
                'admin',
                (string) config('mail.admin'),
                $locale
            );

            $this->insertDelivery(
                $orderId,
                'customer',
                trim((string) $order->payment_email),
                $locale,
                $orderId === 26030 ? '2026-08-27 23:47:59' : null
            );
        }
    }

    private function insertDelivery(
        int $orderId,
        string $kind,
        string $email,
        string $locale,
        ?string $sentAt = null
    ): void {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $now = now();

        DB::table('order_notification_deliveries')->insertOrIgnore([
            'order_id' => $orderId,
            'kind' => $kind,
            'recipient_email' => $email,
            'locale' => $locale,
            'attempts' => $sentAt ? 1 : 0,
            'available_at' => $now,
            'last_attempt_at' => $sentAt,
            'sent_at' => $sentAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
