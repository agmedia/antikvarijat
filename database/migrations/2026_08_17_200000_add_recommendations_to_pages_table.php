<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecommendationsToPagesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'recommendation_type')) {
                $table->string('recommendation_type', 20)->default('none');
            }

            if (! Schema::hasColumn('pages', 'recommendation_author_id')) {
                $table->unsignedBigInteger('recommendation_author_id')->nullable();
            }

            if (! Schema::hasColumn('pages', 'recommendation_product_ids')) {
                $table->text('recommendation_product_ids')->nullable();
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('pages', 'recommendation_product_ids') ? 'recommendation_product_ids' : null,
                Schema::hasColumn('pages', 'recommendation_author_id') ? 'recommendation_author_id' : null,
                Schema::hasColumn('pages', 'recommendation_type') ? 'recommendation_type' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}
