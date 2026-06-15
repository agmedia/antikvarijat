<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnglishLocalizationFields extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $this->stringColumn($table, 'products', 'name_en', 'name');
            $this->longTextColumn($table, 'products', 'description_en', 'description');
            $this->stringColumn($table, 'products', 'slug_en', 'slug', true);
            $this->stringColumn($table, 'products', 'url_en', 'url');
            $this->stringColumn($table, 'products', 'meta_title_en', 'meta_title');
            $this->stringColumn($table, 'products', 'meta_description_en', 'meta_description');
        });

        Schema::table('categories', function (Blueprint $table) {
            $this->stringColumn($table, 'categories', 'title_en', 'title');
            $this->longTextColumn($table, 'categories', 'description_en', 'description');
            $this->textColumn($table, 'categories', 'meta_title_en', 'meta_title');
            $this->textColumn($table, 'categories', 'meta_description_en', 'meta_description');
            $this->stringColumn($table, 'categories', 'slug_en', 'slug', true);
        });

        Schema::table('pages', function (Blueprint $table) {
            $this->stringColumn($table, 'pages', 'title_en', 'title');
            $this->textColumn($table, 'pages', 'short_description_en', 'short_description');
            $this->longTextColumn($table, 'pages', 'description_en', 'description');
            $this->stringColumn($table, 'pages', 'meta_title_en', 'meta_title');
            $this->stringColumn($table, 'pages', 'meta_description_en', 'meta_description');
            $this->stringColumn($table, 'pages', 'slug_en', 'slug', true);
            $this->stringColumn($table, 'pages', 'keywords_en', 'keywords');
        });

        Schema::table('faq', function (Blueprint $table) {
            $this->stringColumn($table, 'faq', 'title_en', 'title');
            $this->longTextColumn($table, 'faq', 'description_en', 'description');
        });

        Schema::table('widgets', function (Blueprint $table) {
            $this->stringColumn($table, 'widgets', 'title_en', 'title');
            $this->textColumn($table, 'widgets', 'subtitle_en', 'subtitle');
            $this->longTextColumn($table, 'widgets', 'description_en', 'description');
            $this->stringColumn($table, 'widgets', 'url_en', 'url');
            $this->stringColumn($table, 'widgets', 'badge_en', 'badge');
        });

        Schema::table('widget_groups', function (Blueprint $table) {
            $this->stringColumn($table, 'widget_groups', 'title_en', 'title');
            $this->stringColumn($table, 'widget_groups', 'slug_en', 'slug', true);
        });

        Schema::table('authors', function (Blueprint $table) {
            $this->stringColumn($table, 'authors', 'title_en', 'title');
            $this->longTextColumn($table, 'authors', 'description_en', 'description');
            $this->textColumn($table, 'authors', 'meta_title_en', 'meta_title');
            $this->textColumn($table, 'authors', 'meta_description_en', 'meta_description');
            $this->stringColumn($table, 'authors', 'slug_en', 'slug', true);
            $this->stringColumn($table, 'authors', 'url_en', 'url');
        });

        Schema::table('publishers', function (Blueprint $table) {
            $this->stringColumn($table, 'publishers', 'title_en', 'title');
            $this->longTextColumn($table, 'publishers', 'description_en', 'description');
            $this->textColumn($table, 'publishers', 'meta_title_en', 'meta_title');
            $this->textColumn($table, 'publishers', 'meta_description_en', 'meta_description');
            $this->stringColumn($table, 'publishers', 'slug_en', 'slug', true);
            $this->stringColumn($table, 'publishers', 'url_en', 'url');
        });
    }

    public function down()
    {
        foreach ([
            'products' => ['name_en', 'description_en', 'slug_en', 'url_en', 'meta_title_en', 'meta_description_en'],
            'categories' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en', 'slug_en'],
            'pages' => ['title_en', 'short_description_en', 'description_en', 'meta_title_en', 'meta_description_en', 'slug_en', 'keywords_en'],
            'faq' => ['title_en', 'description_en'],
            'widgets' => ['title_en', 'subtitle_en', 'description_en', 'url_en', 'badge_en'],
            'widget_groups' => ['title_en', 'slug_en'],
            'authors' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en', 'slug_en', 'url_en'],
            'publishers' => ['title_en', 'description_en', 'meta_title_en', 'meta_description_en', 'slug_en', 'url_en'],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));

                if (! empty($existing)) {
                    $blueprint->dropColumn($existing);
                }
            });
        }
    }

    private function stringColumn(Blueprint $table, string $tableName, string $column, string $after, bool $index = false): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        $definition = $table->string($column)->nullable()->after($after);

        if ($index) {
            $definition->index();
        }
    }

    private function textColumn(Blueprint $table, string $tableName, string $column, string $after): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            $table->text($column)->nullable()->after($after);
        }
    }

    private function longTextColumn(Blueprint $table, string $tableName, string $column, string $after): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            $table->longText($column)->nullable()->after($after);
        }
    }
}
