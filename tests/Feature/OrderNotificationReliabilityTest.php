<?php

namespace Tests\Feature;

use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Orders\Order;
use App\Models\OrderNotificationDelivery;
use App\Services\OrderNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class OrderNotificationReliabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-28 10:00:00');

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedInteger('order_status_id');
            $table->string('locale', 5)->nullable();
            $table->string('payment_fname')->nullable();
            $table->string('payment_lname')->nullable();
            $table->string('payment_email')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_code')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('shipping_code')->nullable();
            $table->timestamp('checkout_processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_total', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('order_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('lang', 5)->nullable();
            $table->timestamps();
        });

        $this->createDeliveryTable();

        config([
            'mail.admin' => 'admin@example.test',
            'settings.order.status.new' => 1,
            'settings.order.status.paid' => 3,
            'settings.order.status.send' => 4,
            'order_notifications.max_attempts' => 0,
            'order_notifications.base_retry_minutes' => 2,
            'order_notifications.max_retry_minutes' => 60,
            'order_notifications.stale_claim_minutes' => 10,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enqueue_creates_both_delivery_rows_once_and_preserves_recipient_snapshots(): void
    {
        $order = $this->insertOrder(101, 'buyer@example.test', 'en');
        $service = app(OrderNotificationService::class);

        $service->enqueue($order);

        DB::table('orders')->where('id', 101)->update([
            'payment_email' => 'changed@example.test',
            'locale' => 'hr',
        ]);
        config(['mail.admin' => 'changed-admin@example.test']);

        $service->enqueue($order);

        $this->assertSame(2, DB::table('order_notification_deliveries')->count());
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 101,
            'kind' => OrderNotificationDelivery::KIND_ADMIN,
            'recipient_email' => 'admin@example.test',
            'locale' => 'en',
            'attempts' => 0,
        ]);
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 101,
            'kind' => OrderNotificationDelivery::KIND_CUSTOMER,
            'recipient_email' => 'buyer@example.test',
            'locale' => 'en',
            'attempts' => 0,
        ]);
    }

    public function test_admin_only_send_does_not_create_or_send_a_customer_delivery(): void
    {
        Mail::fake();
        $order = $this->insertOrder(102);

        $results = app(OrderNotificationService::class)->sendForOrder(
            $order,
            [OrderNotificationDelivery::KIND_ADMIN]
        );

        $this->assertSame('sent', $results[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame(1, DB::table('order_notification_deliveries')->count());
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 102,
            'kind' => OrderNotificationDelivery::KIND_ADMIN,
        ]);
        $this->assertDatabaseMissing('order_notification_deliveries', [
            'order_id' => 102,
            'kind' => OrderNotificationDelivery::KIND_CUSTOMER,
        ]);
        Mail::assertSent(OrderReceived::class, 1);
        Mail::assertNotSent(OrderSent::class);
    }

    public function test_successful_deliveries_are_not_sent_again_on_a_repeated_call(): void
    {
        Mail::fake();
        $order = $this->insertOrder(103);
        $service = app(OrderNotificationService::class);

        $first = $service->sendForOrder($order);
        $second = $service->sendForOrder($order);

        $this->assertSame('sent', $first[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame('sent', $first[OrderNotificationDelivery::KIND_CUSTOMER]['status']);
        $this->assertSame('already_sent', $second[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame('already_sent', $second[OrderNotificationDelivery::KIND_CUSTOMER]['status']);
        $this->assertSame(2, DB::table('order_notification_deliveries')->count());
        $this->assertSame(
            [1, 1],
            DB::table('order_notification_deliveries')->orderBy('kind')->pluck('attempts')->map('intval')->all()
        );
        Mail::assertSent(OrderReceived::class, 1);
        Mail::assertSent(OrderSent::class, 1);
    }

    public function test_transient_failure_releases_claim_and_retries_only_after_backoff(): void
    {
        $order = $this->insertOrder(104);
        $service = app(OrderNotificationService::class);

        Mail::shouldReceive('to')
            ->once()
            ->with('admin@example.test')
            ->andThrow(new RuntimeException('SMTP connection timed out'));

        $failed = $service->sendForOrder($order, [OrderNotificationDelivery::KIND_ADMIN]);
        $delivery = OrderNotificationDelivery::query()->firstOrFail();

        $this->assertSame('failed', $failed[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNull($delivery->claimed_at);
        $this->assertNull($delivery->failed_at);
        $this->assertSame('SMTP connection timed out', $delivery->last_error);
        $this->assertSame('2026-08-28 10:02:00', $delivery->available_at->format('Y-m-d H:i:s'));

        $deferred = $service->sendForOrder($order, [OrderNotificationDelivery::KIND_ADMIN]);
        $this->assertSame('deferred', $deferred[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame(1, $delivery->fresh()->attempts);

        Carbon::setTestNow('2026-08-28 10:02:00');
        Mail::fake();

        $retried = $service->sendForOrder($order, [OrderNotificationDelivery::KIND_ADMIN]);

        $this->assertSame('sent', $retried[OrderNotificationDelivery::KIND_ADMIN]['status']);
        $this->assertSame(2, $delivery->fresh()->attempts);
        $this->assertNotNull($delivery->fresh()->sent_at);
        Mail::assertSent(OrderReceived::class, 1);
    }

    public function test_incident_seed_marks_only_the_verified_customer_delivery_as_sent(): void
    {
        $this->insertOrder(26030, 'customer26030@example.test');
        $this->insertOrder(26031, 'customer26031@example.test');
        Schema::drop('order_notification_deliveries');

        require_once database_path('migrations/2026_08_28_090000_create_order_notification_deliveries_table.php');
        (new \CreateOrderNotificationDeliveriesTable())->up();

        $this->assertSame(4, DB::table('order_notification_deliveries')->count());
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 26030,
            'kind' => OrderNotificationDelivery::KIND_CUSTOMER,
            'attempts' => 1,
            'sent_at' => '2026-08-27 23:47:59',
        ]);
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 26030,
            'kind' => OrderNotificationDelivery::KIND_ADMIN,
            'attempts' => 0,
            'sent_at' => null,
        ]);
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 26031,
            'kind' => OrderNotificationDelivery::KIND_ADMIN,
            'attempts' => 0,
            'sent_at' => null,
        ]);
        $this->assertDatabaseHas('order_notification_deliveries', [
            'order_id' => 26031,
            'kind' => OrderNotificationDelivery::KIND_CUSTOMER,
            'attempts' => 0,
            'sent_at' => null,
        ]);

        Mail::fake();
        $summary = app(OrderNotificationService::class)->processPending(10, 10);

        $this->assertSame(3, $summary['sent']);
        Mail::assertSent(OrderReceived::class, 2);
        Mail::assertSent(OrderSent::class, function (OrderSent $mail) {
            return (int) $mail->order->id === 26031;
        });
        Mail::assertSent(OrderSent::class, 1);
        $this->assertSame(
            1,
            (int) DB::table('order_notification_deliveries')
                ->where('order_id', 26030)
                ->where('kind', OrderNotificationDelivery::KIND_CUSTOMER)
                ->value('attempts')
        );
    }

    private function insertOrder(
        int $id,
        string $email = 'buyer@example.test',
        string $locale = 'hr'
    ): Order {
        DB::table('orders')->insert([
            'id' => $id,
            'user_id' => 0,
            'order_status_id' => 1,
            'locale' => $locale,
            'payment_fname' => 'Test',
            'payment_lname' => 'Buyer',
            'payment_email' => $email,
            'payment_method' => 'Bank transfer',
            'payment_code' => 'bank',
            'shipping_method' => 'GLS',
            'shipping_code' => 'gls',
            'checkout_processed_at' => '2026-08-28 09:59:00',
            'created_at' => '2026-08-28 09:58:00',
            'updated_at' => '2026-08-28 09:59:00',
        ]);

        return Order::query()->findOrFail($id);
    }

    private function createDeliveryTable(): void
    {
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
            $table->unique(['order_id', 'kind']);
        });
    }
}
