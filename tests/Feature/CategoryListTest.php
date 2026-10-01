<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Category;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryListTest extends TestCase
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

        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('group');
            $table->unsignedBigInteger('parent_id')->default(0);
        });

        DB::table('categories')->insert([
            ['id' => 1, 'title' => 'Povijest', 'group' => 'Knjige', 'parent_id' => 0],
            ['id' => 2, 'title' => 'Hrvatska', 'group' => 'Knjige', 'parent_id' => 1],
            ['id' => 3, 'title' => 'Književnost', 'group' => 'Knjige', 'parent_id' => 0],
            ['id' => 4, 'title' => 'Zemljovidi', 'group' => 'Zemljovidi i vedute', 'parent_id' => 0],
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_compact_category_list_keeps_sorted_groups_and_children_without_loading_products(): void
    {
        $categories = (new Category())->getList(false);

        $this->assertSame([
            3 => ['title' => 'Književnost', 'subs' => []],
            1 => ['title' => 'Povijest', 'subs' => [2 => ['title' => 'Hrvatska']]],
        ], $categories->get('Knjige'));
        $this->assertSame([
            4 => ['title' => 'Zemljovidi', 'subs' => []],
        ], $categories->get('Zemljovidi i vedute'));
    }

    public function test_full_category_list_still_includes_product_counts_and_child_models(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
        });
        Schema::create('product_category', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('product_id');
        });
        DB::table('products')->insert(['id' => 1]);
        DB::table('product_category')->insert(['category_id' => 1, 'product_id' => 1]);

        $categories = (new Category())->getList();
        $history = $categories->get('Knjige')->firstWhere('id', 1);

        $this->assertSame(1, $history->products_count);
        $this->assertTrue($history->relationLoaded('subcategories'));
        $this->assertSame([2], $history->subcategories->pluck('id')->all());
    }
}
