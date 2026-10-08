<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Product\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProductSearchTest extends TestCase
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

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->string('isbn')->nullable();
            $table->string('polica')->nullable();
            $table->string('year')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('author_id')->default(1);
            $table->unsignedBigInteger('publisher_id')->default(1);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('title');
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
        });

        Schema::create('translators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('product_translator', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('translator_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_full_title_finds_article_15915_with_a_legacy_eth_character(): void
    {
        // The real title contains U+00D0 (Ð), followed by a correct U+0111 (đ).
        $this->createProduct(5854, ['name' => 'Ignjat Ðurđević', 'sku' => '15915']);
        $this->createProduct(5855, ['name' => 'Ignjat Mažuranić']);

        foreach (['Ignjat Đurđević', 'Ignjat Ðurđević', 'Ignjat Đurðević', 'Ignjat Ðurðević'] as $search) {
            $this->assertSame([5854], $this->searchIds($search), $search);
        }

        $this->assertSame([5854, 5855], $this->searchIds('Ignjat'));
        $this->assertSame('Ignjat Ðurđević', DB::table('products')->where('sku', '15915')->value('name'));
    }

    public function test_legacy_queries_also_find_a_correctly_stored_croatian_title(): void
    {
        $this->createProduct(1, ['name' => 'Ignjat Đurđević']);
        $this->createProduct(2, ['name' => 'Ignjat Mažuranić']);

        foreach (['Ignjat Ðurđević', 'Ignjat Đurðević', 'Ignjat Ðurðević'] as $search) {
            $this->assertSame([1], $this->searchIds($search), $search);
        }
    }

    public function test_lowercase_eth_is_equivalent_to_croatian_d_with_stroke(): void
    {
        $this->createProduct(1, ['name' => 'ignjat ðurðević']);
        $this->createProduct(2, ['name' => 'ignjat đurđević']);
        $this->createProduct(3, ['name' => 'ignjat mažuranić']);

        foreach (['ignjat đurđević', 'ignjat ðurđević', 'ignjat đurðević'] as $search) {
            $this->assertSame([1, 2], $this->searchIds($search), $search);
        }
    }

    public function test_description_and_translator_search_handle_legacy_eth(): void
    {
        $this->createProduct(1, ['description' => 'Studija o pjesniku Ignjat Ðurđević.']);
        $this->createProduct(2);
        $this->createProduct(3, ['description' => 'Studija o pjesniku Ignjat Mažuranić.']);
        $this->addTranslator(2, 'Ignjat Ðurðević');

        foreach (['Ignjat Đurđević', 'Ignjat Ðurđević'] as $search) {
            $this->assertSame([1, 2], $this->searchIds($search), $search);
        }
    }

    public function test_normalized_search_still_respects_all_selected_filters(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => 0, 'title' => 'Književnost'],
            ['id' => 2, 'parent_id' => 0, 'title' => 'Povijest'],
        ]);

        $this->createProduct(1, ['name' => 'Ignjat Ðurđević']);
        $this->createProduct(2, ['name' => 'Ignjat Ðurđević', 'quantity' => 0]);
        $this->createProduct(3, ['description' => 'Ignjat Ðurđević', 'author_id' => 2]);
        $this->createProduct(4, ['publisher_id' => 2]);
        $this->createProduct(5, ['name' => 'Ignjat Ðurđević']);
        $this->createProduct(6, ['name' => 'Druga knjiga']);
        $this->addTranslator(4, 'Ignjat Ðurđević');

        foreach ([1, 2, 3, 4, 6] as $productId) {
            DB::table('product_category')->insert(['product_id' => $productId, 'category_id' => 1]);
        }
        DB::table('product_category')->insert(['product_id' => 5, 'category_id' => 2]);

        $this->assertSame([1], $this->searchIds('Ignjat Đurđević', [
            'status' => 'available',
            'author' => 1,
            'publisher' => 1,
            'category' => 1,
        ]));
    }

    public function test_sku_isbn_shelf_and_year_search_remain_available(): void
    {
        $this->createProduct(1, ['sku' => '15915']);
        $this->createProduct(2, ['isbn' => '9780306406157']);
        $this->createProduct(3, ['polica' => 'POLICA-HR']);
        $this->createProduct(4, ['year' => '1971']);
        $this->createProduct(5, ['name' => 'Druga knjiga']);

        foreach ([
            '15915' => [1],
            '978-0-306-40615-7' => [2],
            'POLICA-HR' => [3],
            '1971' => [4],
        ] as $search => $expected) {
            $this->assertSame($expected, $this->searchIds((string) $search), (string) $search);
        }
    }

    public function test_croatian_diacritics_are_preserved_when_handling_eth(): void
    {
        $this->createProduct(1, ['name' => 'Đak Čaj']);
        $this->createProduct(2, ['name' => 'Đak Ćaj']);
        $this->createProduct(3, ['name' => 'Đak Caj']);

        $this->assertSame([1], $this->searchIds('Ðak Čaj'));
        $this->assertSame([2], $this->searchIds('Ðak Ćaj'));
    }

    private function createProduct(int $id, array $attributes = []): void
    {
        DB::table('products')->insert(array_merge([
            'id' => $id,
            'name' => 'Knjiga ' . $id,
            'sku' => 'SKU-' . $id,
            'quantity' => 1,
            'author_id' => 1,
            'publisher_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function addTranslator(int $productId, string $title): void
    {
        $translatorId = DB::table('translators')->insertGetId(['title' => $title]);
        DB::table('product_translator')->insert([
            'product_id' => $productId,
            'translator_id' => $translatorId,
            'sort_order' => 0,
        ]);
    }

    private function searchIds(string $search, array $filters = []): array
    {
        return (new Product())
            ->filter(new Request(array_merge($filters, ['search' => $search])))
            ->reorder('products.id')
            ->pluck('products.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
