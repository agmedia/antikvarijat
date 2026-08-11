<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsFeaturedToProductReviewsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_reviews') || Schema::hasColumn('product_reviews', 'is_featured')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_verified_purchase');
            $table->index(
                ['is_featured', 'status', 'approved_at'],
                'product_reviews_featured_index'
            );
        });
    }

    public function down()
    {
        if (! Schema::hasTable('product_reviews') || ! Schema::hasColumn('product_reviews', 'is_featured')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex('product_reviews_featured_index');
            $table->dropColumn('is_featured');
        });
    }
}
