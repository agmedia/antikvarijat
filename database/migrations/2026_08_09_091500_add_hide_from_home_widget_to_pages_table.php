<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideFromHomeWidgetToPagesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pages') || Schema::hasColumn('pages', 'hide_from_home_widget')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('hide_from_home_widget')->default(false)->after('featured');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('pages') || ! Schema::hasColumn('pages', 'hide_from_home_widget')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('hide_from_home_widget');
        });
    }
}
