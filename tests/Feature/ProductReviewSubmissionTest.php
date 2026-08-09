<?php

namespace Tests\Feature;

use App\Models\ProductReview;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('invitation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reviewer_name');
            $table->string('reviewer_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('locale', 5)->default('hr');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_verified_purchase')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        DB::table('products')->insert(['id' => 10, 'name' => 'Knjiga']);
    }

    public function test_public_review_is_stored_pending_and_not_verified(): void
    {
        $response = $this->from('/knjige/test')->post(route('product-reviews.store'), [
            'product_id' => 10,
            'reviewer_name' => 'Ana Horvat',
            'reviewer_email' => 'ANA@example.test',
            'rating' => 5,
            'title' => 'Odlično',
            'body' => 'Knjiga je stigla u opisanom stanju.',
            'recaptcha' => 'local-bypass',
            'website' => '',
        ]);

        $response->assertRedirect('/knjige/test#reviews');
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => 10,
            'reviewer_email' => 'ana@example.test',
            'rating' => 5,
            'status' => ProductReview::STATUS_PENDING,
            'is_verified_purchase' => 0,
        ]);
    }
}
