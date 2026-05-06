<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToOrderTotalAndOrderHistoryTables extends Migration
{
    public function up()
    {
        Schema::table('order_total', function (Blueprint $table) {
            $table->index(['order_id', 'sort_order'], 'idx_order_total_order_sort');
        });

        Schema::table('order_history', function (Blueprint $table) {
            $table->index(['order_id', 'created_at'], 'idx_order_history_order_created');
            $table->index('user_id', 'idx_order_history_user_id');
        });
    }

    public function down()
    {
        Schema::table('order_total', function (Blueprint $table) {
            $table->dropIndex('idx_order_total_order_sort');
        });

        Schema::table('order_history', function (Blueprint $table) {
            $table->dropIndex('idx_order_history_order_created');
            $table->dropIndex('idx_order_history_user_id');
        });
    }
}
