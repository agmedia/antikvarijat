<?php

namespace Tests\Feature;

use App\Http\Livewire\Back\Layout\Search\PublisherSearch;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublisherSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_matching_publishers_are_returned_for_trimmed_search_with_exact_match_first(): void
    {
        $expectedIds = [];

        foreach (range('A', 'F') as $prefix) {
            $expectedIds[] = $this->createPublisher($prefix . ' LOM', true)->id;
        }

        $exactMatch = $this->createPublisher('LOM', true);
        $expectedIds[] = $exactMatch->id;

        $component = Livewire::test(PublisherSearch::class)
            ->set('search', 'LOM ');

        $results = $component->get('search_results');

        $this->assertCount(7, $results);
        $this->assertSame($exactMatch->id, $results->first()->id);
        $this->assertSame(1, $results->first()->status);
        $this->assertEqualsCanonicalizing($expectedIds, $results->pluck('id')->all());
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
