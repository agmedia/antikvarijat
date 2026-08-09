<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOrderPerformanceIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('order_products', 'idx_order_products_order_id', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_products_order_id');
        });

        $this->addIndexIfMissing('order_products', 'idx_order_products_product_created', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'idx_order_products_product_created');
        });

        $this->addIndexIfMissing('order_total', 'idx_order_total_order_sort', function (Blueprint $table) {
            $table->index(['order_id', 'sort_order'], 'idx_order_total_order_sort');
        });

        $this->addIndexIfMissing('order_history', 'idx_order_history_order_created', function (Blueprint $table) {
            $table->index(['order_id', 'created_at'], 'idx_order_history_order_created');
        });

        $this->addIndexIfMissing('order_history', 'idx_order_history_user_id', function (Blueprint $table) {
            $table->index('user_id', 'idx_order_history_user_id');
        });
    }

    public function down()
    {
        $this->dropIndexIfExists('order_products', 'idx_order_products_order_id');
        $this->dropIndexIfExists('order_products', 'idx_order_products_product_created');
        $this->dropIndexIfExists('order_total', 'idx_order_total_order_sort');
        $this->dropIndexIfExists('order_history', 'idx_order_history_order_created');
        $this->dropIndexIfExists('order_history', 'idx_order_history_user_id');
    }

    private function addIndexIfMissing(string $table, string $indexName, callable $callback): void
    {
        if ($this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("pragma index_list('{$table}')"))
                ->contains(function ($index) use ($indexName) {
                    return ($index->name ?? null) === $indexName;
                });
        }

        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'select count(*) as aggregate from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
}
