<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryIn7DaysToProductsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'delivery_in_7_days')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('delivery_in_7_days')->default(false)->after('skl');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'delivery_in_7_days')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('delivery_in_7_days');
        });
    }
}
