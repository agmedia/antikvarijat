<?php

namespace Tests\Feature;

use App\Helpers\Import;
use App\Http\Livewire\Back\Layout\Search\AuthorSearch;
use App\Http\Livewire\Back\Layout\Search\PublisherSearch;
use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorPublisherSearchDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['authors', 'publishers'] as $table) {
            if (! Schema::hasColumn($table, 'featured')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->boolean('featured')->default(false);
                });
            }
        }
    }

    public function test_author_picker_reuses_a_unicode_case_and_whitespace_equivalent_record(): void
    {
        $author = $this->createAuthor('Ivan Čović');

        Livewire::test(AuthorSearch::class)
            ->set('new.title', "  IVAN\u{00A0}\tČOVIĆ  ")
            ->call('makeNewAuthor')
            ->assertHasNoErrors('new.title')
            ->assertSet('author_id', $author->id)
            ->assertSet('search', 'Ivan Čović')
            ->assertEmitted('success_alert', ['message' => 'Postojeći autor je odabran.']);

        $this->assertSame(1, Author::query()->count());
    }

    public function test_publisher_picker_reuses_a_unicode_case_and_whitespace_equivalent_record(): void
    {
        $publisher = $this->createPublisher('Školska knjiga');

        Livewire::test(PublisherSearch::class)
            ->set('new.title', "  ŠKOLSKA\u{2003}  KNJIGA  ")
            ->call('makeNewPublisher')
            ->assertHasNoErrors('new.title')
            ->assertSet('publisher_id', $publisher->id)
            ->assertSet('search', 'Školska knjiga')
            ->assertEmitted('success_alert', ['message' => 'Postojeći izdavač je odabran.']);

        $this->assertSame(1, Publisher::query()->count());
    }

    public function test_c_caron_and_c_acute_remain_distinct_for_authors_and_publishers(): void
    {
        $plainAuthor = $this->createAuthor('Ivo Corić');
        $plainPublisher = $this->createPublisher('Naklada Corić');

        $authorComponent = Livewire::test(AuthorSearch::class)
            ->set('new.title', "  Ivo\t Čorić  ")
            ->call('makeNewAuthor')
            ->assertHasNoErrors('new.title')
            ->assertSet('search', 'Ivo Čorić');

        $publisherComponent = Livewire::test(PublisherSearch::class)
            ->set('new.title', '  Naklada   Ćorić  ')
            ->call('makeNewPublisher')
            ->assertHasNoErrors('new.title')
            ->assertSet('search', 'Naklada Ćorić');

        $this->assertSame(2, Author::query()->count());
        $this->assertSame(2, Publisher::query()->count());
        $this->assertNotSame($plainAuthor->id, $authorComponent->get('author_id'));
        $this->assertNotSame($plainPublisher->id, $publisherComponent->get('publisher_id'));
        $this->assertSame('Ivo Čorić', Author::findOrFail($authorComponent->get('author_id'))->title);
        $this->assertSame('Naklada Ćorić', Publisher::findOrFail($publisherComponent->get('publisher_id'))->title);

        $this->assertNotSame(
            Author::normalizeSemanticTitle('Ivo Corić'),
            Author::normalizeSemanticTitle('Ivo Čorić')
        );
        $this->assertNotSame(
            Author::normalizeSemanticTitle('Ivo Čorić'),
            Author::normalizeSemanticTitle('Ivo Ćorić')
        );
    }

    public function test_existing_duplicates_are_not_changed_and_the_oldest_record_is_reused(): void
    {
        $firstAuthor = $this->createAuthor('Dupli Autor');
        $secondAuthor = $this->createAuthor('  DUPLI   AUTOR  ');
        $firstPublisher = $this->createPublisher('Dupli izdavač');
        $secondPublisher = $this->createPublisher('DUPLI  IZDAVAČ');

        Livewire::test(AuthorSearch::class)
            ->set('new.title', 'dupli autor')
            ->call('makeNewAuthor')
            ->assertSet('author_id', $firstAuthor->id);

        Livewire::test(PublisherSearch::class)
            ->set('new.title', 'dupli izdavač')
            ->call('makeNewPublisher')
            ->assertSet('publisher_id', $firstPublisher->id);

        $this->assertSame(2, Author::query()->count());
        $this->assertSame(2, Publisher::query()->count());
        $this->assertSame('  DUPLI   AUTOR  ', $secondAuthor->fresh()->title);
        $this->assertSame('DUPLI  IZDAVAČ', $secondPublisher->fresh()->title);
    }

    public function test_picker_titles_are_validated_and_search_enter_is_prevented(): void
    {
        Livewire::test(AuthorSearch::class)
            ->set('new.title', str_repeat('a', 191))
            ->call('makeNewAuthor')
            ->assertHasNoErrors('new.title');

        Livewire::test(PublisherSearch::class)
            ->set('new.title', str_repeat('b', 191))
            ->call('makeNewPublisher')
            ->assertHasNoErrors('new.title');

        Livewire::test(AuthorSearch::class)
            ->set('new.title', " \u{00A0} ")
            ->call('makeNewAuthor')
            ->assertHasErrors(['new.title' => 'required'])
            ->assertSeeHtml('wire:keydown.enter.prevent');

        Livewire::test(AuthorSearch::class)
            ->set('new.title', str_repeat('a', 192))
            ->call('makeNewAuthor')
            ->assertHasErrors(['new.title' => 'max']);

        Livewire::test(PublisherSearch::class)
            ->set('new.title', str_repeat('b', 192))
            ->call('makeNewPublisher')
            ->assertHasErrors(['new.title' => 'max'])
            ->assertSeeHtml('wire:keydown.enter.prevent');
    }

    public function test_explicit_crud_rejects_another_semantically_equal_record(): void
    {
        $author = $this->createAuthor('Ivana Brlić Mažuranić');
        $publisher = $this->createPublisher('Školska knjiga');

        try {
            (new Author())->validateRequest($this->entityRequest(" IVANA\u{00A0}BRLIĆ  MAŽURANIĆ "));
            $this->fail('Author duplicate validation did not fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }

        try {
            $publisher->validateRequest($this->entityRequest('Drugi izdavač'));
            $this->createPublisher('DRUGI  IZDAVAČ');
            $publisher->edit();
            $this->fail('Publisher atomic duplicate recheck did not fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }

        $this->assertSame('Ivana Brlić Mažuranić', $author->fresh()->title);
        $this->assertSame('Školska knjiga', $publisher->fresh()->title);
    }

    public function test_atomic_create_rechecks_after_validation_and_legacy_duplicates_remain_editable(): void
    {
        $pending = (new Author())->validateRequest($this->entityRequest('Novi Autor'));
        $this->createAuthor(" NOVI\u{2003}AUTOR ");

        try {
            $pending->create();
            $this->fail('Author atomic duplicate recheck did not fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title', $exception->errors());
        }

        $first = $this->createPublisher('Legacy duplikat');
        $second = $this->createPublisher(' LEGACY   DUPLIKAT ');
        $updated = $second
            ->validateRequest($this->entityRequest('legacy duplikat', ['description' => 'Novi opis']))
            ->edit();

        $this->assertSame($second->id, $updated->id);
        $this->assertSame('Novi opis', $second->fresh()->description);
        $this->assertSame(2, Publisher::query()->count());
        $this->assertSame('Legacy duplikat', $first->fresh()->title);
    }

    public function test_import_reuses_semantically_equal_authors_and_publishers(): void
    {
        $author = $this->createAuthor('Željko Čović');
        $publisher = $this->createPublisher('Naklada Ćorić');
        $import = new Import();

        $this->assertSame($author->id, $import->resolveAuthor(" z\u{030C}ELJKO\u{2003} C\u{030C}OVIĆ "));
        $this->assertSame($publisher->id, $import->resolvePublisher(" NAKLADA\tĆORIĆ "));
        $this->assertSame(1, Author::query()->count());
        $this->assertSame(1, Publisher::query()->count());
    }

    public function test_semantic_reuse_helpers_reject_titles_over_191_characters(): void
    {
        foreach ([Author::class, Publisher::class] as $modelClass) {
            try {
                $modelClass::findOrCreateBySemanticTitle(str_repeat('x', 192), []);
                $this->fail($modelClass . ' accepted a title longer than 191 characters.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('191', $exception->getMessage());
            }
        }

        $this->assertSame(0, Author::query()->count());
        $this->assertSame(0, Publisher::query()->count());
    }

    private function createAuthor(string $title): Author
    {
        $slug = 'author-' . uniqid();

        return Author::query()->create([
            'letter' => 'A',
            'title' => $title,
            'description' => '',
            'meta_title' => $title,
            'meta_description' => '',
            'lang' => 'hr',
            'sort_order' => 0,
            'status' => 1,
            'slug' => $slug,
            'url' => 'autori/' . $slug,
        ]);
    }

    private function createPublisher(string $title): Publisher
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
            'status' => 1,
            'slug' => $slug,
            'url' => 'izdavaci/' . $slug,
        ]);
    }

    private function entityRequest(string $title, array $overrides = []): Request
    {
        return new Request(array_merge([
            'title' => $title,
            'description' => '',
            'meta_title' => $title,
            'meta_description' => '',
            'title_en' => '',
            'description_en' => '',
            'meta_title_en' => '',
            'meta_description_en' => '',
            'status' => 'on',
        ], $overrides));
    }
}
