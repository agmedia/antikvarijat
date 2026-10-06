<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductSlugIndexMigrationTest extends TestCase
{
    public function test_migration_is_repeatable_and_slug_lookup_uses_the_index(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('sku')->index();
        });
        DB::table('products')->insert([
            ['slug' => 'prva-knjiga', 'sku' => 'SKU-1'],
            ['slug' => 'trazena-knjiga', 'sku' => 'SKU-2'],
        ]);
        require_once database_path('migrations/2026_10_06_180000_add_product_slug_lookup_index.php');
        $migration = new \AddProductSlugLookupIndex();

        $migration->up();
        $migration->up();

        $indexes = collect(DB::select("PRAGMA index_list('products')"));
        $this->assertSame(1, $indexes->where('name', 'idx_products_slug')->count());
        $plan = DB::select('EXPLAIN QUERY PLAN SELECT id FROM products WHERE slug = ? LIMIT 1', ['trazena-knjiga']);
        $this->assertStringContainsString('idx_products_slug', $plan[0]->detail);
        $this->assertSame(2, DB::table('products')->where('slug', 'trazena-knjiga')->value('id'));

        $migration->down();
        $migration->down();

        $remainingIndexes = collect(DB::select("PRAGMA index_list('products')"));
        $this->assertFalse($remainingIndexes->contains('name', 'idx_products_slug'));
        $this->assertTrue($remainingIndexes->contains('name', 'products_sku_index'));
        $this->assertSame(2, DB::table('products')->count());
    }
}
