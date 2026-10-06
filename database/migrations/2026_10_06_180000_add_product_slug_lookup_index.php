<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProductSlugLookupIndex extends Migration
{
    private const INDEX = 'idx_products_slug';

    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'slug') || $this->hasIndex()) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $column = DB::selectOne(
                'SELECT CHARACTER_MAXIMUM_LENGTH AS max_length FROM information_schema.COLUMNS '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [DB::connection()->getTablePrefix() . 'products', 'slug']
            );
            // A prefix also supports equality lookups and stays within older utf8mb4 key limits.
            $slugColumn = '`slug`' . ((int) ($column->max_length ?? 0) > 191 ? '(191)' : '');
            $this->onlineAlter('ADD INDEX `' . self::INDEX . '` (' . $slugColumn . ')');

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->index('slug', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! $this->hasIndex()) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $this->onlineAlter('DROP INDEX `' . self::INDEX . '`');

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('products')"))->contains('name', self::INDEX);
        }

        return DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [DB::connection()->getTablePrefix() . 'products', self::INDEX]
        ) !== null;
    }

    private function onlineAlter(string $operation): void
    {
        $originalTimeout = (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS timeout_value')->timeout_value;
        $table = DB::connection()->getQueryGrammar()->wrapTable('products');
        DB::statement('SET SESSION lock_wait_timeout = 5');

        try {
            // Refuse a blocking/copy fallback if the server cannot perform this online.
            DB::statement('ALTER TABLE ' . $table . ' ' . $operation . ', ALGORITHM=INPLACE, LOCK=NONE');
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = ' . $originalTimeout);
        }
    }
}
