<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Translator;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductTranslatorPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // The legacy production schema allows the first save before Product::create()
        // calculates the public URL, while the historical test migration does not.
        Schema::table('products', function (Blueprint $table) {
            $table->string('url', 255)->nullable()->change();
        });

        $missingPolica = ! Schema::hasColumn('products', 'polica');
        $missingTags = ! Schema::hasColumn('products', 'tags');

        if ($missingPolica || $missingTags) {
            Schema::table('products', function (Blueprint $table) use ($missingPolica, $missingTags) {
                if ($missingPolica) {
                    $table->string('polica')->nullable();
                }

                if ($missingTags) {
                    $table->text('tags')->nullable();
                }
            });
        }

        $this->category = Category::query()->create([
            'parent_id' => 0,
            'title' => 'Književnost',
            'group' => 'Knjige',
            'lang' => 'hr',
            'sort_order' => 0,
            'status' => true,
            'slug' => 'knjizevnost',
        ]);
    }

    public function test_product_create_edit_and_clear_preserve_translator_selection_order(): void
    {
        $first = Translator::findOrCreateByTitle('Prvi prevoditelj');
        $second = Translator::findOrCreateByTitle('Drugi prevoditelj');

        $product = (new Product())
            ->validateRequest($this->productRequest([
                'translator_ids' => [$second->id, $first->id],
            ]))
            ->create();

        $this->assertSame(
            [$second->id, $first->id],
            $product->translators()->pluck('translators.id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(
            [0, 1],
            DB::table('product_translator')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->pluck('sort_order')
                ->map(fn ($sortOrder) => (int) $sortOrder)
                ->all()
        );

        $product->validateRequest($this->productRequest([
            'translator_ids' => [$first->id],
        ]))->edit();

        $this->assertSame(
            [$first->id],
            $product->refresh()->translators()->pluck('translators.id')->map(fn ($id) => (int) $id)->all()
        );

        $product->validateRequest($this->productRequest([
            'translator_ids' => [],
        ]))->edit();

        $this->assertSame(0, $product->refresh()->translators()->count());
        $this->assertDatabaseMissing('product_translator', ['product_id' => $product->id]);
    }

    public function test_product_validation_rejects_unknown_and_duplicate_translator_ids(): void
    {
        $translator = Translator::findOrCreateByTitle('Postojeći prevoditelj');

        try {
            (new Product())->validateRequest($this->productRequest([
                'translator_ids' => [$translator->id, $translator->id, 999999],
            ]));

            $this->fail('Očekivana je greška validacije prevoditelja.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            $this->assertArrayHasKey('translator_ids.1', $errors);
            $this->assertArrayHasKey('translator_ids.2', $errors);
        }
    }

    public function test_explicitly_null_translator_selection_is_treated_as_empty(): void
    {
        $emptyProduct = (new Product())
            ->validateRequest($this->productRequest([
                'sku' => 'PREV-NULL-EMPTY',
                'translator_ids' => null,
            ]))
            ->create();

        $this->assertSame(0, $emptyProduct->translators()->count());

        $translator = Translator::findOrCreateByTitle('Prevoditelj za brisanje');
        $product = (new Product())
            ->validateRequest($this->productRequest([
                'sku' => 'PREV-NULL-CLEAR',
                'translator_ids' => [$translator->id],
            ]))
            ->create();

        $product->validateRequest($this->productRequest([
            'sku' => 'PREV-NULL-CLEAR',
            'translator_ids' => null,
        ]))->edit();

        $this->assertSame(0, $product->refresh()->translators()->count());
    }

    public function test_deleting_products_and_translators_cleans_up_pivot_rows(): void
    {
        $first = Translator::findOrCreateByTitle('Prvi za brisanje');
        $second = Translator::findOrCreateByTitle('Drugi za brisanje');
        $product = (new Product())
            ->validateRequest($this->productRequest([
                'sku' => 'PREV-DELETE',
                'translator_ids' => [$first->id, $second->id],
            ]))
            ->create();

        $first->delete();

        $this->assertDatabaseMissing('product_translator', [
            'product_id' => $product->id,
            'translator_id' => $first->id,
        ]);
        $this->assertDatabaseHas('product_translator', [
            'product_id' => $product->id,
            'translator_id' => $second->id,
        ]);

        $product->delete();

        $this->assertDatabaseMissing('product_translator', [
            'product_id' => $product->id,
        ]);
    }

    public function test_translator_changes_are_recorded_and_escaped_in_product_history(): void
    {
        $this->actingAs(User::factory()->create());

        $original = Translator::findOrCreateByTitle('Stari prevoditelj');
        $unsafe = Translator::findOrCreateByTitle('<script>alert(1)</script>');
        $product = (new Product())
            ->validateRequest($this->productRequest([
                'translator_ids' => [$original->id],
            ]))
            ->create();
        $oldSnapshot = $product->historySnapshot();

        $product->syncTranslators([$unsafe->id]);
        $product->refresh()->addHistoryData('change', $oldSnapshot);

        $changes = (string) DB::table('history_log')
            ->where('target', 'product')
            ->where('target_id', $product->id)
            ->value('changes');

        $this->assertStringContainsString('Promijenjeni prevoditelji', $changes);
        $this->assertStringContainsString('Stari prevoditelj', $changes);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $changes);
        $this->assertStringNotContainsString('<script>', $changes);
    }

    private function productRequest(array $overrides = []): Request
    {
        return Request::create('/admin/catalog/product', 'POST', array_merge([
            'name' => 'Testna knjiga',
            'sku' => 'PREV-001',
            'price' => '19.90',
            'quantity' => '1',
            'category' => (string) $this->category->id,
            'delivery_in_7_days' => '0',
            'status' => 'on',
        ], $overrides));
    }
}
