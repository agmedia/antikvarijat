<?php

namespace Tests\Unit;

use App\Helpers\LocaleHelper;
use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Category as BackCategory;
use App\Models\Back\Catalog\Product\Product as BackProduct;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocaleHelperTest extends TestCase
{
    public function testEnglishProductPathRebuildsAStaleIdBasedUrlFromSlugs(): void
    {
        $category = new Category();
        $category->setRawAttributes([
            'id' => 2,
            'parent_id' => 0,
            'group' => 'Knjige',
            'slug' => 'hrvatska-rara',
            'slug_en' => null,
        ], true);
        $subcategory = new Category();
        $subcategory->setRawAttributes([
            'id' => 30,
            'parent_id' => 2,
            'group' => 'Knjige',
            'slug' => 'hrvatska-rara-16-stoljece',
            'slug_en' => null,
        ], true);
        $product = new Product();
        $product->setRawAttributes([
            'id' => 69860,
            'slug' => 'paralleli-militari-i-ii',
            'slug_en' => null,
            'url' => 'knjige/hrvatska-rara/hrvatska-rara-16-stoljece/paralleli-militari-i-ii',
            'url_en' => 'en/books/2/30/69860',
        ], true);
        $product->setRelation('categories', new Collection([$subcategory, $category]));

        $this->assertSame(
            'en/books/hrvatska-rara/hrvatska-rara-16-stoljece/paralleli-militari-i-ii',
            LocaleHelper::productPath($product, $product->getRawOriginal('url'), 'en')
        );
    }

    public function testRouteKeyUsesSlugsForBackOfficeModelsWhenBuildingFrontendUrls(): void
    {
        $category = new BackCategory();
        $category->setRawAttributes([
            'id' => 2,
            'slug' => 'hrvatska-rara',
            'slug_en' => 'croatian-rara',
        ], true);
        $product = new BackProduct();
        $product->setRawAttributes([
            'id' => 69860,
            'slug' => 'paralleli-militari-i-ii',
            'slug_en' => 'military-parallels-i-ii',
        ], true);

        $this->assertSame('croatian-rara', LocaleHelper::routeKey($category, 'en'));
        $this->assertSame('military-parallels-i-ii', LocaleHelper::routeKey($product, 'en'));
        $this->assertSame('hrvatska-rara', LocaleHelper::routeKey($category, 'hr'));
    }

    public function testBackOfficeProductUrlGeneratorPersistsSlugBasedEnglishUrls(): void
    {
        $category = new BackCategory();
        $category->setRawAttributes([
            'id' => 2,
            'group' => 'Knjige',
            'slug' => 'hrvatska-rara',
            'slug_en' => null,
        ], true);
        $subcategory = new BackCategory();
        $subcategory->setRawAttributes([
            'id' => 30,
            'parent_id' => 2,
            'slug' => 'hrvatska-rara-16-stoljece',
            'slug_en' => null,
        ], true);
        $product = new class extends BackProduct {
            public $testCategory;
            public $testSubcategory;

            public function category()
            {
                return $this->testCategory;
            }

            public function subcategory()
            {
                return $this->testSubcategory;
            }
        };
        $product->setRawAttributes([
            'id' => 69860,
            'slug' => 'paralleli-militari-i-ii',
            'slug_en' => null,
        ], true);
        $product->testCategory = $category;
        $product->testSubcategory = $subcategory;

        $this->assertSame(
            'en/books/hrvatska-rara/hrvatska-rara-16-stoljece/paralleli-militari-i-ii',
            ProductHelper::urlEn($product)
        );
    }

    public function testLegacyNumericEnglishRouteValuesFallBackToModelIds(): void
    {
        config(['database.connections.legacy_route_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        Schema::connection('legacy_route_test')->create('legacy_route_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('slug_en')->nullable();
        });

        DB::connection('legacy_route_test')->table('legacy_route_products')->insert([
            ['id' => 69860, 'slug' => 'paralleli-militari-i-ii', 'slug_en' => null],
            ['id' => 5521, 'slug' => 'der-adel-des-konigreichs-dalmatien', 'slug_en' => null],
            ['id' => 19331, 'slug' => 'wappenbuch-des-konigreichs-dalmatien', 'slug_en' => 'der-adel-des-konigreichs-dalmatien'],
        ]);

        $originalLocale = app()->getLocale();
        app()->setLocale('en');

        try {
            $provider = new class($this->app) extends RouteServiceProvider {
                public function resolveForTest(string $class, string $value)
                {
                    return $this->resolveFrontendSlug($class, $value);
                }
            };

            $resolved = $provider->resolveForTest(LegacyRouteProduct::class, '69860');

            $this->assertSame(69860, $resolved->id);
            $this->assertSame('paralleli-militari-i-ii', $resolved->slug);

            $localizedCollision = $provider->resolveForTest(
                LegacyRouteProduct::class,
                'der-adel-des-konigreichs-dalmatien'
            );

            $this->assertSame(19331, $localizedCollision->id);
        } finally {
            app()->setLocale($originalLocale);
            DB::purge('legacy_route_test');
        }
    }

    public function testProductAttributeValuesAreTranslatedOnlyOnEnglishPages(): void
    {
        $this->assertSame('Latin script', LocaleHelper::localizedProductAttribute('letter', 'Latinica', 'en'));
        $this->assertSame('Cyrillic script', LocaleHelper::localizedProductAttribute('letter', 'Ćirilica', 'en'));
        $this->assertSame('Arabic script', LocaleHelper::localizedProductAttribute('letter', 'Arapsko', 'en'));
        $this->assertSame('Bosančica (Bosnian Cyrillic)', LocaleHelper::localizedProductAttribute('letter', 'Bosančica', 'en'));
        $this->assertSame('Very good', LocaleHelper::localizedProductAttribute('condition', 'Vrlo dobro', 'en'));
        $this->assertSame('New book', LocaleHelper::localizedProductAttribute('condition', 'Nova knjiga', 'en'));
        $this->assertSame('Hardcover', LocaleHelper::localizedProductAttribute('binding', 'Tvrdi', 'en'));
        $this->assertSame('Hardcover with dust jacket', LocaleHelper::localizedProductAttribute('binding', 'Tvrdi s ovitkom', 'en'));
        $this->assertSame('Softcover / leather binding', LocaleHelper::localizedProductAttribute('binding', 'meki/kožni', 'en'));
        $this->assertSame('Spiral-bound', LocaleHelper::localizedProductAttribute('binding', 'Spiralni', 'en'));
        $this->assertSame('Vrlo dobro', LocaleHelper::localizedProductAttribute('condition', 'Vrlo dobro', 'hr'));
        $this->assertSame('Nepoznata vrijednost', LocaleHelper::localizedProductAttribute('condition', 'Nepoznata vrijednost', 'en'));
    }

    public function testCachedCategoryArraysAreLocalizedForTheCurrentLanguage(): void
    {
        $cachedCategory = [
            'title' => 'Povijest',
            'title_en' => 'History',
        ];

        $this->assertSame('History', LocaleHelper::localizedField($cachedCategory, 'title', true, 'en'));
        $this->assertSame('Povijest', LocaleHelper::localizedField($cachedCategory, 'title', true, 'hr'));
    }

    public function testSettingLocalizationTreatsLiteralNullAsMissing(): void
    {
        $payment = (object) [
            'title' => 'Apple Pay / Google Pay',
            'title_en' => 'null',
            'data' => (object) [
                'description' => 'Plaćanje putem Corvusa.',
                'description_en' => ' NULL ',
            ],
        ];

        $this->assertSame(
            'Apple Pay / Google Pay',
            LocaleHelper::localizedSettingField($payment, 'title', true, 'en')
        );
        $this->assertSame(
            'Plaćanje putem Corvusa.',
            LocaleHelper::localizedSettingDataField($payment, 'description', true, 'en')
        );
        $this->assertNull(LocaleHelper::localizedSettingField($payment, 'title', false, 'en'));
        $this->assertNull(LocaleHelper::localizedSettingField((object) ['title' => 'null'], 'title', true, 'hr'));
    }

    public function testWidgetUrlsConvertLegacyCroatianLinksToEnglishRoutes(): void
    {
        config(['app.url' => 'https://antlaravel.test']);

        $this->assertSame('en/books', LocaleHelper::localizedUrl('knjige', 'en'));
        $this->assertSame(
            'en/books',
            LocaleHelper::localizedUrl('https://www.antikvarijat-biblos.hr/knjige', 'en')
        );
        $this->assertSame(
            'en/maps-and-views?sort=novi#ponuda',
            LocaleHelper::localizedUrl('/zemljovidi-i-vedute?sort=novi#ponuda', 'en')
        );
        $this->assertSame('en/book-purchase', LocaleHelper::localizedUrl('/otkup-knjiga', 'en'));
        $this->assertSame('en/books', LocaleHelper::localizedUrl('/en/knjige', 'en'));
    }

    public function testWidgetUrlsLeaveExternalDestinationsUnchanged(): void
    {
        $external = 'https://www.google.com/search?q=Biblos';

        $this->assertSame($external, LocaleHelper::localizedUrl($external, 'en'));
        $this->assertSame('mailto:info@antikvarijat-biblos.hr', LocaleHelper::localizedUrl('mailto:info@antikvarijat-biblos.hr', 'en'));
    }
}

class LegacyRouteProduct extends Model
{
    protected $connection = 'legacy_route_test';
    protected $table = 'legacy_route_products';
    public $timestamps = false;

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
