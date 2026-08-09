<?php

namespace Tests\Unit;

use App\Services\WishlistAttributionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WishlistAttributionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('wishlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
            $table->unsignedBigInteger('product_id');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_status_id');
            $table->string('payment_email');
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
            $table->decimal('total', 15, 4);
            $table->timestamps();
        });

        config(['wishlist.attribution_days' => 30]);
    }

    public function testItCountsOnlyMatchingValidOrdersInsideAttributionWindow(): void
    {
        DB::table('wishlist')->insert([
            $this->wish(1, 'KUPAC@example.test', 10, '2026-08-01 10:00:00', '2026-08-02 10:00:00'),
            $this->wish(2, 'drugi@example.test', 20, '2026-08-01 10:00:00'),
            $this->wish(3, 'kasni@example.test', 30, '2026-08-01 10:00:00'),
            $this->wish(4, 'otkazano@example.test', 40, '2026-08-01 10:00:00'),
            $this->wish(5, 'stari@example.test', 50, null),
        ]);

        DB::table('orders')->insert([
            $this->order(101, 1, 'kupac@example.test', '2026-08-04 10:00:00'),
            $this->order(102, 3, 'drugi@example.test', '2026-08-10 10:00:00'),
            $this->order(103, 1, 'kasni@example.test', '2026-09-05 10:00:00'),
            $this->order(104, 5, 'otkazano@example.test', '2026-08-05 10:00:00'),
        ]);

        DB::table('order_products')->insert([
            $this->line(1001, 101, 10, 1, 10),
            $this->line(1002, 102, 20, 2, 40),
            $this->line(1003, 103, 30, 1, 30),
            $this->line(1004, 104, 40, 1, 40),
        ]);

        $stats = app(WishlistAttributionService::class)->statistics();

        $this->assertSame(4, $stats['tracked_sends']);
        $this->assertSame(1, $stats['clicked']);
        $this->assertSame(2, $stats['converted_messages']);
        $this->assertSame(2, $stats['orders_after_send']);
        $this->assertSame(1, $stats['orders_after_click']);
        $this->assertSame(3, $stats['items_after_send']);
        $this->assertSame(50.0, $stats['revenue_after_send']);
        $this->assertSame(50.0, $stats['conversion_rate']);
    }

    private function wish(int $id, string $email, int $productId, ?string $sentAt, ?string $clickedAt = null): array
    {
        return [
            'id' => $id,
            'email' => $email,
            'product_id' => $productId,
            'sent' => 1,
            'sent_at' => $sentAt,
            'clicked_at' => $clickedAt,
            'click_count' => $clickedAt ? 1 : 0,
        ];
    }

    private function order(int $id, int $status, string $email, string $createdAt): array
    {
        return [
            'id' => $id,
            'order_status_id' => $status,
            'payment_email' => $email,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function line(int $id, int $orderId, int $productId, int $quantity, float $total): array
    {
        return [
            'id' => $id,
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'total' => $total,
            'created_at' => '2026-08-04 10:00:00',
            'updated_at' => '2026-08-04 10:00:00',
        ];
    }
}
