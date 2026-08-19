<?php

namespace Tests\Feature;

use App\Services\ProductRecommendationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecentBestSellersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Cache::flush();
        Carbon::setTestNow('2026-08-19 12:00:00');

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id')->default(0);
            $table->unsignedBigInteger('action_id')->default(0);
            $table->boolean('status')->default(true);
            $table->decimal('price', 15, 4)->default(10);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_status_id');
            $table->timestamps();
        });

        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create('product_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('status')->default(true);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function testItRanksOnlyAvailableProductsFromCompletedOrdersInTheLastThirtyDays(): void
    {
        $now = now();

        DB::table('products')->insert([
            ['id' => 1, 'image' => 'one.jpg', 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'image' => 'two.jpg', 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'image' => 'three.jpg', 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'image' => 'four.jpg', 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'image' => 'five.jpg', 'quantity' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'image' => null, 'quantity' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('orders')->insert([
            ['id' => 1, 'order_status_id' => config('settings.order.status.paid'), 'created_at' => $now->copy()->subDays(2), 'updated_at' => $now],
            ['id' => 2, 'order_status_id' => config('settings.order.status.canceled'), 'created_at' => $now->copy()->subDay(), 'updated_at' => $now],
            ['id' => 3, 'order_status_id' => config('settings.order.status.send'), 'created_at' => $now->copy()->subDays(31), 'updated_at' => $now],
            ['id' => 4, 'order_status_id' => config('settings.order.status.new'), 'created_at' => $now->copy()->subHours(4), 'updated_at' => $now],
        ]);

        DB::table('order_products')->insert([
            ['order_id' => 1, 'product_id' => 1, 'quantity' => 2],
            ['order_id' => 1, 'product_id' => 2, 'quantity' => 5],
            ['order_id' => 2, 'product_id' => 3, 'quantity' => 100],
            ['order_id' => 3, 'product_id' => 4, 'quantity' => 100],
            ['order_id' => 4, 'product_id' => 5, 'quantity' => 20],
            ['order_id' => 4, 'product_id' => 6, 'quantity' => 20],
        ]);

        $ids = app(ProductRecommendationService::class)
            ->recentBestSellers(30, 10)
            ->pluck('id')
            ->all();

        $this->assertSame([2, 1], $ids);
    }

    public function testItHonoursTheRequestedMaximum(): void
    {
        $now = now();

        DB::table('products')->insert([
            ['id' => 1, 'image' => 'one.jpg', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'image' => 'two.jpg', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('orders')->insert([
            'id' => 1,
            'order_status_id' => config('settings.order.status.paid'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('order_products')->insert([
            ['order_id' => 1, 'product_id' => 1, 'quantity' => 2],
            ['order_id' => 1, 'product_id' => 2, 'quantity' => 5],
        ]);

        $products = app(ProductRecommendationService::class)->recentBestSellers(30, 1);

        $this->assertCount(1, $products);
        $this->assertSame(2, $products->first()->id);
    }

    public function testItExcludesSelectedProductsFromTheRanking(): void
    {
        $now = now();

        DB::table('products')->insert([
            ['id' => 1, 'image' => 'one.jpg', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'image' => 'two.jpg', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('orders')->insert([
            'id' => 1,
            'order_status_id' => config('settings.order.status.paid'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('order_products')->insert([
            ['order_id' => 1, 'product_id' => 1, 'quantity' => 10],
            ['order_id' => 1, 'product_id' => 2, 'quantity' => 5],
        ]);

        $ids = app(ProductRecommendationService::class)
            ->recentBestSellers(30, 10, [1])
            ->pluck('id')
            ->all();

        $this->assertSame([2], $ids);
    }
}
