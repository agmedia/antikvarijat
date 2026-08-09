<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductReviewsAndIsbn extends Migration
{
    public function up()
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'isbn')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('isbn', 20)->nullable()->after('ean')->index();
            });
        }

        if (! Schema::hasTable('product_review_invitations')) {
            Schema::create('product_review_invitations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('order_id')->unique();
                $table->char('token_hash', 64)->unique();
                $table->string('recipient_email', 191);
                $table->string('recipient_name', 191);
                $table->string('locale', 5)->default('hr');
                $table->timestamp('eligible_at')->index();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_reviews')) {
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('order_product_id')->nullable()->unique();
                $table->unsignedBigInteger('invitation_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('reviewer_name', 191);
                $table->string('reviewer_email', 191)->nullable();
                $table->unsignedTinyInteger('rating');
                $table->string('title', 191)->nullable();
                $table->text('body');
                $table->string('locale', 5)->default('hr');
                $table->string('status', 20)->default('pending')->index();
                $table->boolean('is_verified_purchase')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamps();

                $table->index(['product_id', 'status', 'approved_at'], 'product_reviews_visible_index');
                $table->index(['status', 'created_at'], 'product_reviews_moderation_index');
                $table->unique(['order_id', 'product_id'], 'product_reviews_order_product_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_review_invitations');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'isbn')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['isbn']);
                $table->dropColumn('isbn');
            });
        }
    }
}
