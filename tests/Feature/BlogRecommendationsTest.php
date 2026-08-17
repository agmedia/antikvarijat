<?php

namespace Tests\Feature;

use App\Models\Back\Marketing\Blog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BlogRecommendationsTest extends TestCase
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

        Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('group')->default('blog');
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('title_en')->nullable();
            $table->text('short_description_en')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->string('slug');
            $table->string('slug_en')->nullable();
            $table->string('keywords')->nullable();
            $table->string('keywords_en')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->boolean('hide_from_home_widget')->default(false);
            $table->string('recommendation_type', 20)->default('none');
            $table->unsignedBigInteger('recommendation_author_id')->nullable();
            $table->text('recommendation_product_ids')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });

        DB::table('authors')->insert(['id' => 5, 'title' => 'Franz Kafka']);
        DB::table('products')->insert([
            ['id' => 8, 'name' => 'Proces'],
            ['id' => 12, 'name' => 'Zamak'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function testManualProductsAreStoredInTheSelectedOrder(): void
    {
        $blog = (new Blog())->validateRequest(new Request([
            'title' => 'Kafka na blogu',
            'recommendation_type' => 'products',
            'recommendation_product_ids' => ['12', '8'],
            'status' => 'on',
        ]))->create();

        $this->assertSame('products', $blog->recommendation_type);
        $this->assertNull($blog->recommendation_author_id);
        $this->assertSame([12, 8], $blog->recommendation_product_ids);
    }

    public function testSwitchingToAuthorClearsManualProducts(): void
    {
        $blog = (new Blog())->validateRequest(new Request([
            'title' => 'Kafka na blogu',
            'recommendation_type' => 'products',
            'recommendation_product_ids' => ['12', '8'],
        ]))->create();

        $updated = $blog->validateRequest(new Request([
            'title' => 'Kafka na blogu',
            'recommendation_type' => 'author',
            'recommendation_author_id' => '5',
        ]))->edit();

        $this->assertSame('author', $updated->recommendation_type);
        $this->assertSame(5, (int) $updated->recommendation_author_id);
        $this->assertNull($updated->recommendation_product_ids);
    }
}
