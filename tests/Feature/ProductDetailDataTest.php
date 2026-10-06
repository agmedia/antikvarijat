<?php

namespace Tests\Feature;

use App\Helpers\LocaleHelper;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Services\ProductDetailDataService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductDetailDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
        app()->setLocale('hr');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('author_id')->default(1);
            $table->unsignedInteger('publisher_id')->default(1);
            $table->unsignedInteger('action_id')->default(0);
            $table->string('name');
            $table->string('slug');
            $table->string('url');
            $table->decimal('price', 15, 4)->default(10);
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->timestamps();
        });
        foreach (['authors', 'publishers'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('title');
            });
            DB::table($tableName)->insert(['id' => 1, 'title' => 'Test']);
        }
        Schema::create('product_actions', function (Blueprint $table) {
            $table->id();
            $table->boolean('status');
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_id')->default(0);
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('group');
            $table->string('slug');
            $table->string('slug_en')->nullable();
        });
        Schema::create('product_category', function (Blueprint $table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('category_id');
        });
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('status');
            $table->unsignedTinyInteger('rating');
        });
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => 0, 'title' => 'Knjige', 'title_en' => 'Books', 'group' => 'Knjige', 'slug' => 'knjige', 'slug_en' => 'books'],
            ['id' => 2, 'parent_id' => 1, 'title' => 'Romani', 'title_en' => 'Novels', 'group' => 'Knjige', 'slug' => 'romani', 'slug_en' => 'novels'],
        ]);
        foreach (range(1, 18) as $id) {
            DB::table('products')->insert([
                'id' => $id, 'name' => 'Product ' . $id, 'slug' => 'product-' . $id,
                'url' => 'knjige/knjige/romani/product-' . $id,
                'sort_order' => $id, 'created_at' => '2026-10-06 09:' . str_pad((string) $id, 2, '0', STR_PAD_LEFT) . ':00',
            ]);
            DB::table('product_category')->insert([
                ['product_id' => $id, 'category_id' => 1],
                ['product_id' => $id, 'category_id' => 2],
            ]);
        }
    }

    public function test_cached_selections_share_one_live_product_query_and_keep_prices_stock_and_status_current(): void
    {
        $product = Product::query()->with(['author', 'publisher'])->findOrFail(1);
        $category = Category::findOrFail(1);
        $service = app(ProductDetailDataService::class);
        $service->recommendations($product, $category, true, true, [4, 2]);

        DB::table('products')->where('id', 2)->update(['price' => 27.5]);
        DB::table('products')->where('id', 3)->update(['quantity' => 0]);
        DB::table('products')->where('id', 4)->update(['status' => 0]);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $service->recommendations($product, $category, true, true, [4, 2]);

        $this->assertSame([2], $result['recentProducts']->pluck('id')->all());
        $this->assertSame(27.5, (float) $result['recentProducts']->first()->price);
        $this->assertFalse($result['relatedProducts']->contains('id', 3));
        $this->assertFalse($result['publisherProducts']->contains('id', 4));
        $this->assertSame(5, count(DB::getQueryLog()));
        $productQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from "products"'));
        $this->assertCount(1, $productQueries);
        $this->assertTrue($result['relatedProducts']->first() === $result['recentProducts']->first());
    }

    public function test_author_and_publisher_selection_cache_is_shared_between_product_pages_without_recommending_the_current_product(): void
    {
        $service = app(ProductDetailDataService::class);
        $first = Product::query()->with(['author', 'publisher'])->findOrFail(18);
        $second = Product::query()->with(['author', 'publisher'])->findOrFail(17);
        $service->recommendations($first, null, true, true, []);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $service->recommendations($second, null, true, true, []);

        $this->assertCount(15, $result['authorProducts']);
        $this->assertSame(18, $result['authorProducts']->first()->id);
        $this->assertFalse($result['authorProducts']->contains('id', 17));
        $this->assertSame(5, count(DB::getQueryLog()));
    }

    public function test_cached_selections_revalidate_author_publisher_and_category_membership_without_extra_queries(): void
    {
        $service = app(ProductDetailDataService::class);
        $product = Product::query()->with(['author', 'publisher'])->findOrFail(1);
        $category = Category::findOrFail(1);
        $service->recommendations($product, $category, true, true, []);
        DB::table('products')->where('id', 10)->update(['author_id' => 9, 'publisher_id' => 8]);
        DB::table('product_category')->where('product_id', 11)->where('category_id', 1)->delete();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $service->recommendations($product, $category, true, true, []);

        $this->assertFalse($result['authorProducts']->contains('id', 10));
        $this->assertFalse($result['publisherProducts']->contains('id', 10));
        $this->assertTrue($result['relatedProducts']->contains('id', 10));
        $this->assertFalse($result['relatedProducts']->contains('id', 11));
        $this->assertTrue($result['authorProducts']->contains('id', 11));
        $this->assertSame(5, count(DB::getQueryLog()));
    }

    public function test_recent_products_preserve_history_order_and_sold_out_products(): void
    {
        $product = Product::findOrFail(1);
        DB::table('products')->where('id', 3)->update(['quantity' => 0]);

        $result = app(ProductDetailDataService::class)->recommendations($product, null, false, false, [3, 2, 3, 1]);

        $this->assertSame([3, 2], $result['recentProducts']->pluck('id')->all());
        $this->assertTrue($result['authorProducts']->isEmpty());
        $this->assertTrue($result['publisherProducts']->isEmpty());
        $this->assertTrue($result['relatedProducts']->isEmpty());
    }

    public function test_english_card_urls_and_categories_do_not_issue_queries_per_recommendation(): void
    {
        $product = Product::query()->with(['author', 'publisher'])->findOrFail(1);
        $result = app(ProductDetailDataService::class)->recommendations($product, Category::findOrFail(1), true, true, []);
        app()->setLocale('en');
        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach ($result['relatedProducts'] as $candidate) {
            $this->assertStringContainsString('en/books/books/novels/', LocaleHelper::productPath($candidate));
            $this->assertStringContainsString('Novels', LocaleHelper::categoryString($candidate));
        }

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_review_stats_are_calculated_by_one_query_and_exclude_unapproved_reviews(): void
    {
        DB::table('product_reviews')->insert([
            ['product_id' => 1, 'status' => 'approved', 'rating' => 5],
            ['product_id' => 1, 'status' => 'approved', 'rating' => 5],
            ['product_id' => 1, 'status' => 'approved', 'rating' => 3],
            ['product_id' => 1, 'status' => 'pending', 'rating' => 1],
            ['product_id' => 2, 'status' => 'approved', 'rating' => 1],
        ]);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $stats = app(ProductDetailDataService::class)->reviewStats(1);

        $this->assertSame(['count' => 3, 'average' => 4.33, 'distribution' => [1 => 0, 2 => 0, 3 => 1, 4 => 0, 5 => 2]], $stats);
        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame(0.0, app(ProductDetailDataService::class)->reviewStats(999)['average']);
    }

    public function test_stateless_crawlers_do_not_write_view_counts_but_normal_visitors_still_do(): void
    {
        $product = Product::findOrFail(1);
        $request = Request::create('/knjige/product-1');
        $request->attributes->set('stateless_crawler', true);
        $service = app(ProductDetailDataService::class);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $service->recordView($product, $request);

        $this->assertCount(0, DB::getQueryLog());
        $this->assertSame(0, (int) $product->refresh()->viewed);
        $request->attributes->remove('stateless_crawler');
        $service->recordView($product, $request);
        $this->assertSame(1, (int) $product->refresh()->viewed);
        $this->assertTrue($product->timestamps);
    }
}
