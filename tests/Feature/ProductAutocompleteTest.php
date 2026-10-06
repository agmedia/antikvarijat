<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_admin_product_autocomplete(): void
    {
        $this->getJson(route('products.autocomplete', ['query' => 'book']))
            ->assertUnauthorized();
    }

    public function test_query_is_required_and_must_have_at_least_three_characters(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('products.autocomplete'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');

        $this->getJson(route('products.autocomplete', ['query' => 'ab']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');
    }

    public function test_results_are_small_limited_and_deterministically_sorted(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (range(25, 1) as $index) {
            $this->insertProduct([
                'name' => sprintf('Common title %02d', $index),
                'sku' => sprintf('SKU-%02d', $index),
                'description' => str_repeat('Large description ', 100),
            ]);
        }

        $response = $this->getJson(route('products.autocomplete', ['query' => 'Common']));

        $response->assertOk();
        $products = $response->json();

        $this->assertCount(20, $products);
        $this->assertSame('Common title 01', $products[0]['name']);
        $this->assertSame('Common title 20', $products[19]['name']);

        $keys = array_keys($products[0]);
        sort($keys);

        $this->assertSame(['id', 'name', 'price', 'sku'], $keys);
    }

    public function test_exact_sku_is_returned_without_unrelated_matches(): void
    {
        $this->actingAs(User::factory()->create());

        $exactId = $this->insertProduct([
            'name' => 'Exact code product',
            'sku' => 'CODE-123',
        ]);
        $this->insertProduct([
            'name' => 'Another CODE-123 mention',
            'sku' => 'OTHER-1',
        ]);

        $response = $this->getJson(route('products.autocomplete', ['query' => 'CODE-123']));

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($exactId, $response->json('0.id'));
    }

    public function test_like_wildcards_are_treated_as_literal_characters(): void
    {
        $this->actingAs(User::factory()->create());

        $literalId = $this->insertProduct([
            'name' => 'Literal %_x title',
            'sku' => 'LITERAL-1',
        ]);
        $this->insertProduct([
            'name' => 'Literal ABx title',
            'sku' => 'OTHER-2',
        ]);

        $response = $this->getJson(route('products.autocomplete', ['query' => '%_x']));

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame($literalId, $response->json('0.id'));
    }

    private function insertProduct(array $overrides = []): int
    {
        return (int) DB::table('products')->insertGetId(array_merge([
            'author_id' => 0,
            'publisher_id' => 0,
            'action_id' => 0,
            'name' => 'Autocomplete product',
            'sku' => 'AUTO-' . uniqid(),
            'slug' => 'autocomplete-' . uniqid(),
            'url' => 'knjige/autocomplete-' . uniqid(),
            'price' => 10,
            'quantity' => 1,
            'tax_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
