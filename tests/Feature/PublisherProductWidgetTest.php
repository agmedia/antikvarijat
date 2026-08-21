<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class PublisherProductWidgetTest extends TestCase
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
        Carbon::setTestNow('2026-08-21 12:00:00');

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('publisher_id');
            $table->unsignedBigInteger('author_id')->default(0);
            $table->string('name')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('image')->nullable();
            $table->unsignedInteger('viewed')->default(0);
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

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->string('status');
            $table->unsignedTinyInteger('rating');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function testAdminFormOffersPublishersAndBestSellingSort(): void
    {
        $source = file_get_contents(resource_path('views/back/widget/templates/product_carousel.blade.php'));

        $this->assertStringContainsString('<option value="publisher"', $source);
        $this->assertStringContainsString('name="best_selling"', $source);
        $this->assertStringContainsString('Prikaži 10 najprodavanijih u posljednjih 30 dana', $source);
    }

    public function testSelectedPublishersAreSortedByCompletedSaleQuantity(): void
    {
        $now = '2026-08-18 08:00:00';

        DB::table('products')->insert([
            ['id' => 1, 'publisher_id' => 10, 'image' => 'one.webp', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'publisher_id' => 10, 'image' => 'two.webp', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'publisher_id' => 20, 'image' => 'three.webp', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'publisher_id' => 10, 'image' => 'four.webp', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('orders')->insert([
            ['id' => 1, 'order_status_id' => config('settings.order.status.new'), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'order_status_id' => config('settings.order.status.canceled'), 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('order_products')->insert([
            ['order_id' => 1, 'product_id' => 1, 'quantity' => 2],
            ['order_id' => 1, 'product_id' => 2, 'quantity' => 5],
            ['order_id' => 1, 'product_id' => 3, 'quantity' => 100],
            ['order_id' => 2, 'product_id' => 4, 'quantity' => 50],
        ]);

        $query = $this->publisherWidgetQuery([
            'target' => 'publisher',
            'list' => [10],
            'new' => 'on',
            'popular' => 'on',
            'best_selling' => 'on',
        ]);

        $ids = $query->setEagerLoads([])->get()->pluck('id')->all();

        $this->assertSame([2, 1], $ids);
        $this->assertSame(15, $query->getQuery()->limit);
    }

    public function testProductWidgetShowsAtMostTenRecentBestSellersAndExcludesRestrictedProducts(): void
    {
        $products = [];
        $orderProducts = [];

        for ($id = 1; $id <= 12; $id++) {
            $products[] = [
                'id' => $id,
                'publisher_id' => 10,
                'author_id' => 0,
                'name' => 'Product ' . $id,
                'image' => $id . '.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $orderProducts[] = ['order_id' => 1, 'product_id' => $id, 'quantity' => $id];
        }

        $products[] = ['id' => 13, 'publisher_id' => 10, 'author_id' => 0, 'name' => 'Old product', 'image' => 'old.webp', 'created_at' => now(), 'updated_at' => now()];
        $products[] = ['id' => 14, 'publisher_id' => 10, 'author_id' => 1196, 'name' => 'Excluded author', 'image' => 'excluded.webp', 'created_at' => now(), 'updated_at' => now()];
        $products[] = ['id' => 15, 'publisher_id' => 10, 'author_id' => 0, 'name' => 'Mein Kampf - Moja borba', 'image' => 'excluded-title.webp', 'created_at' => now(), 'updated_at' => now()];

        DB::table('products')->insert($products);
        DB::table('orders')->insert([
            ['id' => 1, 'order_status_id' => config('settings.order.status.paid'), 'created_at' => now()->subDay(), 'updated_at' => now()],
            ['id' => 2, 'order_status_id' => config('settings.order.status.paid'), 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);
        DB::table('order_products')->insert(array_merge($orderProducts, [
            ['order_id' => 2, 'product_id' => 13, 'quantity' => 999],
            ['order_id' => 1, 'product_id' => 14, 'quantity' => 1000],
            ['order_id' => 1, 'product_id' => 15, 'quantity' => 1001],
        ]));

        $query = $this->productWidgetQuery([
            'target' => 'product',
            'best_selling' => 'on',
        ]);
        $ids = $query->setEagerLoads([])->get()->pluck('id')->all();

        $this->assertSame([12, 11, 10, 9, 8, 7, 6, 5, 4, 3], $ids);
        $this->assertSame(10, $query->getQuery()->limit);
    }

    public function testSalesRankingIsReusedFromCacheForTheSameSelection(): void
    {
        DB::enableQueryLog();

        $data = [
            'target' => 'publisher',
            'list' => [10],
            'best_selling' => 'on',
        ];

        $this->publisherWidgetQuery($data);
        $queriesAfterFirstRanking = count(DB::getQueryLog());

        $this->publisherWidgetQuery($data);

        $this->assertSame(1, $queriesAfterFirstRanking);
        $this->assertCount($queriesAfterFirstRanking, DB::getQueryLog());
    }

    private function publisherWidgetQuery(array $data)
    {
        $method = new ReflectionMethod(Helper::class, 'publisher');
        $method->setAccessible(true);

        return $method->invoke(null, $data);
    }

    private function productWidgetQuery(array $data)
    {
        $method = new ReflectionMethod(Helper::class, 'products');
        $method->setAccessible(true);

        return $method->invoke(null, $data);
    }
}
