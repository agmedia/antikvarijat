<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAuthorPublisherVisibilityIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('products', 'idx_products_author_visible', function (Blueprint $table) {
            $table->index(['author_id', 'status', 'quantity', 'price'], 'idx_products_author_visible');
        });

        $this->addIndexIfMissing('products', 'idx_products_publisher_visible', function (Blueprint $table) {
            $table->index(['publisher_id', 'status', 'quantity', 'price'], 'idx_products_publisher_visible');
        });

        $this->addIndexIfMissing('authors', 'idx_authors_status_letter', function (Blueprint $table) {
            $table->index(['status', 'letter'], 'idx_authors_status_letter');
        });

        $this->addIndexIfMissing('publishers', 'idx_publishers_status_letter', function (Blueprint $table) {
            $table->index(['status', 'letter'], 'idx_publishers_status_letter');
        });
    }

    public function down()
    {
        $this->dropIndexIfExists('products', 'idx_products_author_visible');
        $this->dropIndexIfExists('products', 'idx_products_publisher_visible');
        $this->dropIndexIfExists('authors', 'idx_authors_status_letter');
        $this->dropIndexIfExists('publishers', 'idx_publishers_status_letter');
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
