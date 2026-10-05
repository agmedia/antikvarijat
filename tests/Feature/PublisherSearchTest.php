<?php

namespace Tests\Feature;

use App\Http\Livewire\Back\Layout\Search\PublisherSearch;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class PublisherSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_bounded_batches_and_keeps_an_exact_trimmed_match_first(): void
    {
        $expectedIds = [];

        foreach (range(1, 44) as $index) {
            $expectedIds[] = $this->createPublisher(
                sprintf('Naklada LOM %02d', $index),
                $index % 2 === 0
            )->id;
        }

        $exactMatch = $this->createPublisher('LOM', true);
        $expectedIds[] = $exactMatch->id;

        $component = Livewire::test(PublisherSearch::class)
            ->set('search', 'LOM ');

        $initialResults = $component->get('search_results');

        $this->assertCount(20, $initialResults);
        $this->assertSame(20, $component->get('result_limit'));
        $this->assertSame($exactMatch->id, $initialResults[0]['id']);
        $this->assertTrue($component->get('has_more_results'));
        $component->assertSee('Prikaži još');

        $component->call('loadMore');

        $this->assertCount(40, $component->get('search_results'));
        $this->assertSame(40, $component->get('result_limit'));
        $this->assertSame($exactMatch->id, $component->get('search_results')[0]['id']);
        $this->assertTrue($component->get('has_more_results'));

        $component->call('loadMore');

        $allResults = $component->get('search_results');

        $this->assertCount(45, $allResults);
        $this->assertSame(60, $component->get('result_limit'));
        $this->assertSame($exactMatch->id, $allResults[0]['id']);
        $this->assertFalse($component->get('has_more_results'));
        $this->assertEqualsCanonicalizing($expectedIds, array_column($allResults, 'id'));
        $component->assertDontSee('Prikaži još');

        $component->set('search', 'Naklada LOM 01');

        $this->assertSame(20, $component->get('result_limit'));
        $this->assertCount(1, $component->get('search_results'));
        $this->assertFalse($component->get('has_more_results'));
    }

    public function test_semantic_duplicate_lookup_does_not_hydrate_every_publisher(): void
    {
        foreach (range(1, 30) as $index) {
            $this->createPublisher(sprintf('Izdavač %02d', $index), true);
        }

        $expected = $this->createPublisher('  Naklada   Test  ', true);
        $retrievedPublishers = 0;

        Event::listen('eloquent.retrieved: ' . Publisher::class, function () use (&$retrievedPublishers): void {
            $retrievedPublishers++;
        });

        $match = Publisher::findSemanticallyEquivalentTitle('NAKLADA TEST');

        $this->assertNotNull($match);
        $this->assertSame($expected->id, $match->id);
        $this->assertSame(1, $retrievedPublishers);

        $retrievedPublishers = 0;

        $this->assertNull(Publisher::findSemanticallyEquivalentTitle('Potpuno novi izdavač'));
        $this->assertSame(0, $retrievedPublishers);
    }

    private function createPublisher(string $title, bool $active): Publisher
    {
        $slug = 'publisher-' . uniqid();

        return Publisher::query()->create([
            'letter' => 'P',
            'title' => $title,
            'description' => '',
            'meta_title' => $title,
            'meta_description' => '',
            'lang' => 'hr',
            'sort_order' => 0,
            'status' => $active,
            'slug' => $slug,
            'url' => 'izdavaci/' . $slug,
        ]);
    }
}
