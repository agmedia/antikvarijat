<?php

namespace Tests\Feature;

use App\Http\Livewire\Back\Layout\Search\TranslatorSearch;
use App\Models\Back\Catalog\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TranslatorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_selected_translators_once_and_preserves_their_order(): void
    {
        $first = Translator::findOrCreateByTitle('Prvi prevoditelj');
        $second = Translator::findOrCreateByTitle('Drugi prevoditelj');

        Livewire::test(TranslatorSearch::class, [
            'translator_ids' => [$second->id, (string) $first->id, $second->id, 'neispravno'],
        ])
            ->assertSet('translator_ids', [$second->id, $first->id])
            ->assertSet('selected_translators.0.id', $second->id)
            ->assertSet('selected_translators.1.id', $first->id)
            ->assertSeeInOrder(['Drugi prevoditelj', 'Prvi prevoditelj']);
    }

    public function test_it_searches_unselected_translators_and_prevents_duplicates(): void
    {
        $selected = Translator::findOrCreateByTitle('Ana Horvat');
        $available = Translator::findOrCreateByTitle('Ivana Horvat');

        $component = Livewire::test(TranslatorSearch::class, [
            'translator_ids' => [$selected->id],
        ])
            ->set('search', 'Horvat')
            ->assertSet('search_results.0.id', $available->id)
            ->call('addTranslator', $available->id)
            ->call('addTranslator', $selected->id)
            ->assertSet('translator_ids', [$selected->id, $available->id]);

        $this->assertCount(2, $component->get('selected_translators'));
    }

    public function test_it_reuses_a_normalized_name_instead_of_creating_a_duplicate(): void
    {
        $translator = Translator::findOrCreateByTitle('Zlatko Crnković');

        Livewire::test(TranslatorSearch::class)
            ->set('new_title', "  ZLATKO\t  CRNKOVIĆ ")
            ->call('makeNewTranslator')
            ->assertHasNoErrors('new_title')
            ->assertSet('translator_ids', [$translator->id])
            ->assertSet('selected_translators.0.title', 'Zlatko Crnković');

        $this->assertSame(1, Translator::query()->count());
    }

    public function test_croatian_diacritics_remain_distinct_in_normalized_names(): void
    {
        $plain = Translator::findOrCreateByTitle('Cedo Prijevod');
        $accented = Translator::findOrCreateByTitle('Čedo Prijevod');

        $this->assertNotSame($plain->id, $accented->id);
        $this->assertSame(2, Translator::query()->count());
    }

    public function test_it_reuses_composed_and_decomposed_unicode_names(): void
    {
        $composed = Translator::findOrCreateByTitle('Željko Klaić');
        $decomposed = Translator::findOrCreateByTitle("Z\u{030C}eljko Klaic\u{0301}");

        $this->assertSame($composed->id, $decomposed->id);
        $this->assertSame(1, Translator::query()->count());
        $this->assertSame('željko klaić', $composed->normalized_title);
    }

    public function test_it_can_create_remove_and_reorder_translators_for_submission(): void
    {
        $first = Translator::findOrCreateByTitle('Prvi prevoditelj');
        $second = Translator::findOrCreateByTitle('Drugi prevoditelj');

        Livewire::test(TranslatorSearch::class, [
            'translator_ids' => [$first->id],
        ])
            ->call('addTranslator', $second->id)
            ->call('moveTranslator', $second->id, -1)
            ->assertSet('translator_ids', [$second->id, $first->id])
            ->assertSeeHtml('name="translator_ids[]" value="' . $second->id . '"')
            ->assertSeeHtml('name="translator_ids[]" value="' . $first->id . '"')
            ->assertSeeHtml('name="translator_ids_present" value="1"')
            ->call('removeTranslator', $first->id)
            ->assertSet('translator_ids', [$second->id]);

        Livewire::test(TranslatorSearch::class)
            ->set('new_title', 'Novi prevoditelj')
            ->call('makeNewTranslator')
            ->assertHasNoErrors('new_title');

        $this->assertDatabaseHas('translators', [
            'title' => 'Novi prevoditelj',
            'normalized_title' => 'novi prevoditelj',
        ]);
    }

    public function test_new_translator_name_is_validated(): void
    {
        Livewire::test(TranslatorSearch::class)
            ->set('new_title', ' ')
            ->call('makeNewTranslator')
            ->assertHasErrors(['new_title' => 'required']);

        Livewire::test(TranslatorSearch::class)
            ->set('new_title', str_repeat('a', 192))
            ->call('makeNewTranslator')
            ->assertHasErrors(['new_title' => 'max']);
    }
}
