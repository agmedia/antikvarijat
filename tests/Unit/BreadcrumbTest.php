<?php

namespace Tests\Unit;

use App\Helpers\Breadcrumb;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Publisher;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('hr');
        config(['app.locale' => 'hr']);
    }

    public function testAuthorTrailMatchesTheVisibleHierarchy(): void
    {
        $author = $this->model(Author::class, 1, 'Ivo Andrić', 'ivo-andric');
        $category = $this->model(Category::class, 2, 'Književnost', 'knjizevnost');
        $subcategory = $this->model(Category::class, 3, 'Romani', 'romani');

        $schema = (new Breadcrumb())->author($author, $category, $subcategory)->resolve();
        $items = $schema['itemListElement'];

        $this->assertSame([1, 2, 3, 4, 5], array_column($items, 'position'));
        $this->assertSame(['Naslovnica', 'Autori', 'Ivo Andrić', 'Književnost', 'Romani'], array_column($items, 'name'));
    }

    public function testPublisherTrailIncludesThePublisherDirectory(): void
    {
        $publisher = $this->model(Publisher::class, 4, 'Matica hrvatska', 'matica-hrvatska');

        $schema = (new Breadcrumb())->publisher($publisher)->resolve();
        $items = $schema['itemListElement'];

        $this->assertSame([1, 2, 3], array_column($items, 'position'));
        $this->assertSame(['Naslovnica', 'Nakladnici', 'Matica hrvatska'], array_column($items, 'name'));
    }

    private function model(string $class, int $id, string $title, string $slug)
    {
        $model = new $class();
        $model->setRawAttributes([
            'id' => $id,
            'title' => $title,
            'title_en' => null,
            'slug' => $slug,
            'slug_en' => null,
        ], true);

        return $model;
    }
}
