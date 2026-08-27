<?php

namespace Tests\Feature;

use App\Helpers\Breadcrumb;
use App\Helpers\Helper;
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
