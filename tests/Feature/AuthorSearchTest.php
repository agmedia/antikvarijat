<?php

namespace Tests\Feature;

use App\Http\Livewire\Back\Layout\Search\AuthorSearch;
use App\Models\Back\Catalog\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_a_small_minimal_result_set(): void
    {
        foreach (range(1, 9) as $index) {
            $this->createAuthor(sprintf('Testni Autor %02d', $index));
        }

        $component = Livewire::test(AuthorSearch::class)
            ->set('search', '  Testni   Autor ');

        $results = $component->get('search_results');

        $this->assertCount(6, $results);
        $this->assertSame(['id', 'title'], array_keys($results[0]));
        $this->assertSame('Testni Autor 01', $results[0]['title']);
        $component->assertSee('Testni Autor 01');
    }

    public function test_semantic_duplicate_lookup_does_not_hydrate_every_author(): void
    {
        foreach (range(1, 30) as $index) {
            $this->createAuthor(sprintf('Autor %02d', $index));
        }

        $expected = $this->createAuthor('  Ivana   Horvat  ');
        $retrievedAuthors = 0;

        Event::listen('eloquent.retrieved: ' . Author::class, function () use (&$retrievedAuthors): void {
            $retrievedAuthors++;
        });

        $match = Author::findSemanticallyEquivalentTitle('IVANA HORVAT');

        $this->assertNotNull($match);
        $this->assertSame($expected->id, $match->id);
        $this->assertSame(1, $retrievedAuthors);

        $retrievedAuthors = 0;

        $this->assertNull(Author::findSemanticallyEquivalentTitle('Potpuno novi autor'));
        $this->assertSame(0, $retrievedAuthors);
    }

    private function createAuthor(string $title): Author
    {
        $slug = 'author-' . uniqid();

        return Author::query()->create([
            'letter' => 'T',
            'title' => $title,
            'description' => str_repeat('Opis autora. ', 100),
            'meta_title' => $title,
            'meta_description' => str_repeat('Meta opis. ', 100),
            'lang' => 'hr',
            'sort_order' => 0,
            'status' => 1,
            'slug' => $slug,
            'url' => 'autori/' . $slug,
        ]);
    }
}
