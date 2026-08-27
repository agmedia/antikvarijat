<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Product\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductDeliveryInSevenDaysTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('hr');

        $this->createProductPersistenceTables();

        $this->category = Category::query()->create([
            'parent_id' => 0,
            'title' => 'Književnost',
            'group' => 'Knjige',
            'status' => true,
            'slug' => 'knjizevnost',
        ]);
    }

    public function test_admin_create_persists_checked_and_unchecked_delivery_checkbox_values(): void
    {
        $flagged = (new Product())
            ->validateRequest($this->productRequest([
                'name' => 'Knjiga s odgođenom isporukom',
                'sku' => 'DELIVERY-7-1',
                'delivery_in_7_days' => '1',
            ]))
            ->create();

        $unflagged = (new Product())
            ->validateRequest($this->productRequest([
                'name' => 'Odmah dostupna knjiga',
                'sku' => 'DELIVERY-7-2',
                'delivery_in_7_days' => '0',
            ]))
            ->create();

        $this->assertDatabaseHas('products', [
            'id' => $flagged->id,
            'delivery_in_7_days' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $unflagged->id,
            'delivery_in_7_days' => 0,
        ]);
        $this->assertTrue($flagged->refresh()->delivery_in_7_days);
        $this->assertFalse($unflagged->refresh()->delivery_in_7_days);
    }

    public function test_admin_edit_can_set_and_clear_the_delivery_checkbox(): void
    {
        $product = (new Product())
            ->validateRequest($this->productRequest([
                'delivery_in_7_days' => '0',
            ]))
            ->create();

        $product->validateRequest($this->productRequest([
            'delivery_in_7_days' => '1',
        ]))->edit();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'delivery_in_7_days' => 1,
        ]);
        $this->assertTrue($product->refresh()->delivery_in_7_days);

        $product->validateRequest($this->productRequest([
            'delivery_in_7_days' => '0',
        ]))->edit();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'delivery_in_7_days' => 0,
        ]);
        $this->assertFalse($product->refresh()->delivery_in_7_days);
    }

    public function test_admin_form_has_explicit_checked_and_unchecked_checkbox_values(): void
    {
        $source = file_get_contents(resource_path('views/back/catalog/product/edit.blade.php'));

        $this->assertStringContainsString(
            '<input type="hidden" name="delivery_in_7_days" value="0">',
            $source
        );
        $this->assertStringContainsString(
            'id="delivery-in-7-days" name="delivery_in_7_days" value="1"',
            $source
        );
        $this->assertStringContainsString(
            "old('delivery_in_7_days', isset(\$product) ? \$product->delivery_in_7_days : false)",
            $source
        );
    }

    public function test_storefront_notice_is_below_the_cart_button_and_only_renders_for_flagged_products(): void
    {
        $source = file_get_contents(resource_path('views/front/catalog/product/index.blade.php'));
        $cartButtonPosition = strpos($source, '<add-to-cart-btn');
        $noticePosition = strpos($source, '@if ($prod->delivery_in_7_days)');

        $this->assertNotFalse($cartButtonPosition);
        $this->assertNotFalse($noticePosition);
        $this->assertGreaterThan($cartButtonPosition, $noticePosition);

        $flaggedHtml = $this->renderDeliveryNotice(true, $source);
        $unflaggedHtml = $this->renderDeliveryNotice(false, $source);

        $this->assertStringContainsString('Rok isporuke za ovaj artikl je 7 dana.', $flaggedHtml);
        $this->assertStringContainsString('role="alert"', $flaggedHtml);
        $this->assertStringNotContainsString('Rok isporuke za ovaj artikl je 7 dana.', $unflaggedHtml);
        $this->assertStringNotContainsString('role="alert"', $unflaggedHtml);
    }

    private function productRequest(array $overrides = []): Request
    {
        return Request::create('/admin/catalog/product', 'POST', array_merge([
            'name' => 'Testna knjiga',
            'sku' => 'DELIVERY-7',
            'price' => '19.90',
            'quantity' => '1',
            'category' => (string) $this->category->id,
            'status' => 'on',
        ], $overrides));
    }

    private function createProductPersistenceTables(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('group')->default('Knjige');
            $table->boolean('status')->default(true);
            $table->string('slug');
            $table->string('slug_en')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id')->default(0);
            $table->unsignedBigInteger('publisher_id')->default(0);
            $table->unsignedBigInteger('action_id')->default(0);
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('sku')->unique();
            $table->string('isbn')->nullable();
            $table->string('polica')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('slug');
            $table->string('slug_en')->nullable();
            $table->string('url')->nullable();
            $table->string('url_en')->nullable();
            $table->text('category_string')->nullable();
            $table->string('image')->nullable();
            $table->text('tags')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('skl')->nullable();
            $table->boolean('delivery_in_7_days')->default(false);
            $table->unsignedInteger('tax_id')->default(1);
            $table->decimal('special', 15, 4)->nullable();
            $table->timestamp('special_from')->nullable();
            $table->timestamp('special_to')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->string('pages')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('origin')->nullable();
            $table->string('letter')->nullable();
            $table->string('condition')->nullable();
            $table->string('binding')->nullable();
            $table->string('year')->nullable();
            $table->unsignedInteger('viewed')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('push')->default(false);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('product_category', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
        });

        Schema::create('translators', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('normalized_title')->unique();
            $table->timestamps();
        });

        Schema::create('product_translator', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('translator_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'translator_id']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('image');
            $table->timestamps();
        });
    }

    private function renderDeliveryNotice(bool $enabled, string $source): string
    {
        $matched = preg_match(
            '/@if\s*\(\$prod->delivery_in_7_days\).*?@endif/s',
            $source,
            $matches
        );

        $this->assertSame(1, $matched, 'Delivery notice Blade block was not found.');

        return Blade::render($matches[0], [
            'prod' => (object) ['delivery_in_7_days' => $enabled],
        ]);
    }
}
