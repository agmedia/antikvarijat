<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Product\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductQuickEditHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_quick_quantity_edit_is_recorded_in_product_history(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct(['quantity' => 4]);
        Carbon::setTestNow('2026-08-11 14:30:00');

        $response = $this->actingAs($admin)->postJson(route('products.update.item'), [
            'product' => [
                'item' => $product->toArray(),
                'target' => 'quantity',
                'new_value' => '20',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => 200,
                'value_1' => 20,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 20,
            'updated_at' => '2026-08-11 14:30:00',
        ]);

        $history = DB::table('history_log')
            ->where('target', 'product')
            ->where('target_id', $product->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($admin->id, $history->user_id);
        $this->assertStringContainsString('Promjenjena količina: <b>4</b> u <b>20</b>', $history->changes);
    }

    public function test_quick_edit_does_not_write_history_when_value_is_unchanged(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct([
            'quantity' => 4,
            'updated_at' => '2026-08-10 10:00:00',
        ]);
        Carbon::setTestNow('2026-08-11 14:30:00');

        $this->actingAs($admin)->postJson(route('products.update.item'), [
            'product' => [
                'item' => $product->toArray(),
                'target' => 'quantity',
                'new_value' => '4',
            ],
        ])->assertOk()->assertJson(['error' => 300]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 4,
            'updated_at' => '2026-08-10 10:00:00',
        ]);
        $this->assertDatabaseMissing('history_log', [
            'target' => 'product',
            'target_id' => $product->id,
        ]);
    }

    public function test_quick_text_edit_is_recorded_in_product_history(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct(['year' => '1999']);

        $this->actingAs($admin)->postJson(route('products.update.item'), [
            'product' => [
                'item' => $product->toArray(),
                'target' => 'year',
                'new_value' => '2003',
            ],
        ])->assertOk()->assertJson([
            'success' => 200,
            'value_1' => '2003',
        ]);

        $history = DB::table('history_log')
            ->where('target', 'product')
            ->where('target_id', $product->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Promjenjena godina izdavanja: <b>1999</b> u <b>2003</b>', $history->changes);
    }

    public function test_status_change_from_product_list_is_recorded_in_history(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct([
            'status' => 1,
            'quantity' => 4,
        ]);

        $this->actingAs($admin)->postJson(route('products.change.status'), [
            'id' => $product->id,
            'value' => false,
        ])->assertOk()->assertJson(['success' => 200]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 0,
            'quantity' => 0,
        ]);

        $history = DB::table('history_log')
            ->where('target', 'product')
            ->where('target_id', $product->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Promjenjena količina: <b>4</b> u <b>0</b>', $history->changes);
        $this->assertStringContainsString('Promjenjena status vidljivosti: <b>Deaktiviran</b>', $history->changes);
    }

    private function createProduct(array $overrides = []): Product
    {
        $attributes = array_merge([
            'author_id' => 0,
            'publisher_id' => 0,
            'action_id' => 0,
            'name' => 'Testna knjiga',
            'sku' => 'TEST-QUICK-1',
            'slug' => 'testna-knjiga',
            'url' => 'knjige/testna-knjiga',
            'price' => 27,
            'quantity' => 4,
            'tax_id' => 1,
            'status' => 1,
            'created_at' => '2026-08-10 10:00:00',
            'updated_at' => '2026-08-10 10:00:00',
        ], $overrides);

        $timestamps = [
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
        ];
        unset($attributes['created_at'], $attributes['updated_at']);

        $product = Product::query()->create($attributes);
        $product->forceFill($timestamps)->save();

        return $product->refresh();
    }
}
