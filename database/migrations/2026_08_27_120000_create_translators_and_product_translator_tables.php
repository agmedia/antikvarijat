<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('translators')) {
            $useBinaryNameCollation = DB::getDriverName() === 'mysql';

            Schema::create('translators', function (Blueprint $table) use ($useBinaryNameCollation) {
                $table->bigIncrements('id');
                $table->string('title', 191);
                $normalizedTitle = $table->string('normalized_title', 191);

                // MySQL's default unicode_ci collation conflates Croatian
                // diacritics (for example c/č/ć). Case is normalized in PHP,
                // so a binary key safely keeps those distinct.
                if ($useBinaryNameCollation) {
                    $normalizedTitle->collation('utf8mb4_bin');
                }

                $table->timestamps();

                $table->unique('normalized_title', 'translators_normalized_title_unique');
                $table->index('title', 'translators_title_index');
            });
        }

        if (! Schema::hasTable('product_translator')) {
            // Product and Translator model delete hooks detach pivot rows. We
            // intentionally avoid coupling this new table to a legacy table's
            // storage engine through a foreign-key requirement.
            Schema::create('product_translator', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('translator_id');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(
                    ['product_id', 'translator_id'],
                    'product_translator_product_translator_unique'
                );
                $table->index(
                    ['product_id', 'sort_order'],
                    'product_translator_product_sort_index'
                );
                $table->index('translator_id', 'product_translator_translator_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_translator');
        Schema::dropIfExists('translators');
    }
};
