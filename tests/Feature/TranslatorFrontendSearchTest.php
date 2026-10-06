<?php

namespace Tests\Feature;

use App\Helpers\Breadcrumb;
use App\Helpers\Helper;
use App\Http\Controllers\Front\CatalogRouteController;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Translator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class TranslatorFrontendSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.locale' => 'hr',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        app()->setLocale('hr');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('author_id')->default(0);
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('slug')->nullable();
            $table->string('url')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('status')->default(true);
            $table->decimal('price', 15, 4)->default(10);
            $table->decimal('special', 15, 4)->nullable();
            $table->dateTime('special_from')->nullable();
            $table->dateTime('special_to')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('title_en')->nullable();
            $table->boolean('status')->default(true);
        });

        Schema::create('translators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('normalized_title');
            $table->timestamps();
        });

        Schema::create('product_translator', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('translator_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('products')->insert([
            [
                'id' => 1,
                'name' => 'Prva knjiga',
                'description' => 'Opis prve knjige.',
                'slug' => 'prva-knjiga',
                'url' => 'knjige/prva-knjiga',
                'sku' => 'PRVA-1',
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Druga knjiga',
                'description' => 'Opis druge knjige.',
                'slug' => 'druga-knjiga',
                'url' => 'knjige/druga-knjiga',
                'sku' => 'DRUGA-2',
                'status' => 1,
                'price' => 12,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('translators')->insert([
            [
                'id' => 1,
                'title' => 'Ana Horvat',
                'normalized_title' => 'ana horvat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'title' => 'Ivo Ivić',
                'normalized_title' => 'ivo ivić',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('product_translator')->insert([
            [
                'product_id' => 1,
                'translator_id' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'translator_id' => 2,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_translators_are_ordered_and_searchable(): void
    {
        $product = Product::query()->with('translators:id,title')->findOrFail(1);

        $this->assertSame(['Ivo Ivić', 'Ana Horvat'], $product->translators->pluck('title')->all());

        $search = Helper::search('Ana Horvat', true);

        $this->assertSame([1], $search->get('products')->all());
    }

    public function test_author_search_keeps_available_products_for_each_name_format(): void
    {
        DB::table('authors')->insert([
            'id' => 1,
            'title' => 'Ana Marija Horvat',
            'status' => 1,
        ]);
        DB::table('products')->whereIn('id', [1, 2])->update(['author_id' => 1]);
        DB::table('products')->where('id', 2)->update(['quantity' => 0]);

        foreach (['Ana', 'Ana Horvat', 'Ana Marija Horvat'] as $name) {
            $search = Helper::search($name, true);

            $this->assertSame([1], $search->get('products')->all(), $name);
            $this->assertSame(1, $search->get('total'), $name);
        }
    }

    public function test_autocomplete_skips_queries_shorter_than_three_characters(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $search = Helper::search('ab', true, true);

        $this->assertSame([], $search->get('products')->all());
        $this->assertSame(0, $search->get('total'));
        $this->assertSame([], DB::getQueryLog());

        $key = config('settings.search_keyword') . '_api';
        $response = (new CatalogRouteController())->search(
            Request::create('/pretrazi/autocomplete', 'GET', [$key => 'ab'])
        );

        $this->assertSame([
            'counts' => [
                'products' => 0,
                'authors' => 0,
                'categories' => 0,
            ],
            'products' => [],
            'categories' => [],
            'authors' => [],
        ], $response->getData(true));
        $this->assertSame('0', $response->headers->get('X-Total-Count'));
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_autocomplete_treats_sql_wildcards_as_literal_characters(): void
    {
        DB::table('products')->insert([
            [
                'id' => 3,
                'name' => 'Popust 100%',
                'description' => null,
                'slug' => 'popust-100-posto',
                'url' => 'knjige/popust-100-posto',
                'sku' => 'LITERAL_1',
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Popust 100X',
                'description' => null,
                'slug' => 'popust-100-x',
                'url' => 'knjige/popust-100-x',
                'sku' => 'LITERALX1',
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $percentSearch = Helper::search('100%', true, true);
        $underscoreSearch = Helper::search('LITERAL_', true, true);

        $this->assertSame([3], $percentSearch->get('products')->all());
        $this->assertSame(1, $percentSearch->get('total'));
        $this->assertSame([3], $underscoreSearch->get('products')->all());
        $this->assertSame(1, $underscoreSearch->get('total'));
    }

    public function test_autocomplete_uses_the_indexed_exact_sku_path(): void
    {
        DB::table('products')->insert([
            [
                'id' => 3,
                'name' => 'Traženi proizvod',
                'description' => null,
                'slug' => 'trazeni-proizvod',
                'url' => 'knjige/trazeni-proizvod',
                'sku' => 'CODE-123',
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Proizvod koji spominje CODE-123',
                'description' => null,
                'slug' => 'spominje-code-123',
                'url' => 'knjige/spominje-code-123',
                'sku' => 'OTHER-4',
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $search = Helper::search('CODE-123', true, true);

        $this->assertSame([3], $search->get('products')->all());
        $this->assertSame(1, $search->get('total'));
        $this->assertCount(1, DB::getQueryLog());
        $this->assertStringContainsString('"sku" = ?', DB::getQueryLog()[0]['query']);
    }

    public function test_autocomplete_limits_product_ids_before_hydration_and_keeps_total(): void
    {
        $products = [];
        foreach (range(10, 29) as $id) {
            $products[] = [
                'id' => $id,
                'name' => 'Limit test ' . $id,
                'description' => null,
                'slug' => 'limit-test-' . $id,
                'url' => 'knjige/limit-test-' . $id,
                'sku' => 'LIMIT-' . $id,
                'status' => 1,
                'price' => 10,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('products')->insert($products);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $search = Helper::search('Limit test', true, true);

        $this->assertCount(15, $search->get('products'));
        $this->assertSame(20, $search->get('total'));
        $this->assertTrue(collect(DB::getQueryLog())->contains(function (array $query) {
            $sql = strtolower($query['query']);

            return str_contains($sql, 'products') && str_contains($sql, 'limit 15');
        }));
    }

    public function test_catalogue_query_can_filter_by_translator_id(): void
    {
        $ids = (new Product())
            ->filter(new Request(['prevoditelj' => [2]]))
            ->pluck('id')
            ->all();

        $this->assertSame([1], $ids);
    }

    public function test_book_schema_lists_each_translator_as_a_person(): void
    {
        $product = new Product([
            'name' => 'Primjer knjige',
            'description' => 'Opis knjige.',
            'slug' => 'primjer-knjige',
            'url' => 'knjige/primjer-knjige',
            'sku' => 'PRIMJER-1',
            'price' => 10,
            'special' => null,
            'special_from' => null,
            'special_to' => null,
            'quantity' => 1,
            'isbn' => null,
            'year' => null,
            'pages' => null,
            'image' => null,
        ]);
        $product->setRelation('action', null);
        $product->setRelation('author', null);
        $product->setRelation('publisher', null);
        $product->setRelation('translators', collect([
            new Translator(['title' => 'Ana Horvat']),
            new Translator(['title' => 'Ivo Ivić']),
        ]));
        $product->syncOriginal();

        $schema = (new Breadcrumb())->productBookSchema($product);

        $this->assertSame([
            ['@type' => 'Person', 'name' => 'Ana Horvat'],
            ['@type' => 'Person', 'name' => 'Ivo Ivić'],
        ], $schema['translator']);
    }

    public function test_product_view_derives_translator_state_without_controller_variables(): void
    {
        $product = Product::query()->with('translators:id,title')->findOrFail(1);
        $product->setRelation('images', collect());

        $source = file_get_contents(resource_path('views/front/catalog/product/index.blade.php'));
        $matched = preg_match('/@php.*?@endphp/s', $source, $matches);

        $this->assertSame(1, $matched, 'Product view setup block was not found.');

        $html = Blade::render($matches[0].PHP_EOL.'{{ $hasTranslators ? $translatorNames->implode("|") : "none" }}', [
            'prod' => $product,
            'errors' => new ViewErrorBag(),
            'reviewStats' => ['average' => 0, 'count' => 0],
            'subcat' => null,
            'cat' => null,
            'authorProducts' => collect(),
            'publisherProducts' => collect(),
            'relatedProducts' => collect(),
        ]);

        $this->assertSame('Ivo Ivić|Ana Horvat', trim($html));

        $legacyProduct = new class {
            public $images;

            public function __construct()
            {
                $this->images = collect();
            }

            public function getRawOriginal(string $key)
            {
                return null;
            }
        };
        $legacyHtml = Blade::render($matches[0].PHP_EOL.'{{ $hasTranslators ? "unexpected" : "none" }}', [
            'prod' => $legacyProduct,
            'errors' => new ViewErrorBag(),
            'reviewStats' => ['average' => 0, 'count' => 0],
            'subcat' => null,
            'cat' => null,
            'authorProducts' => collect(),
            'publisherProducts' => collect(),
            'relatedProducts' => collect(),
        ]);

        $this->assertSame('none', trim($legacyHtml));
    }
}
