<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Models\Back\Catalog\Translator;
use Illuminate\Support\Collection;
use Livewire\Component;

class TranslatorSearch extends Component
{
    /**
     * Current search term.
     *
     * @var string
     */
    public $search = '';

    /**
     * Search results serialized for Livewire.
     *
     * @var array<int, array{id: int, title: string}>
     */
    public $search_results = [];

    /**
     * Selected translator IDs, kept in submission/display order.
     *
     * @var array<int, int>
     */
    public $translator_ids = [];

    /**
     * Selected translator data, aligned with translator_ids.
     *
     * @var array<int, array{id: int, title: string}>
     */
    public $selected_translators = [];

    /**
     * Whether the inline create panel is visible.
     *
     * @var bool
     */
    public $show_add_window = false;

    /**
     * Title entered in the inline create panel.
     *
     * @var string
     */
    public $new_title = '';

    /**
     * @param array<int, int|string>|Collection $translator_ids
     */
    public function mount($translator_ids = []): void
    {
        // Livewire assigns matching public properties before mount(). Rebuild both
        // arrays from the submitted IDs so stale/duplicate values cannot drift
        // away from the selected translator data rendered by the component.
        $this->translator_ids = [];
        $this->selected_translators = [];

        if ($translator_ids instanceof Collection) {
            $translator_ids = $translator_ids->all();
        }

        if (! is_array($translator_ids)) {
            $translator_ids = $translator_ids === null || $translator_ids === ''
                ? []
                : [$translator_ids];
        }

        $ids = collect($translator_ids)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $translators = Translator::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'title'])
            ->keyBy('id');

        foreach ($ids as $id) {
            $translator = $translators->get($id);

            if ($translator) {
                $this->appendTranslator($translator);
            }
        }
    }

    public function updatedSearch($value): void
    {
        $this->search_results = [];
        $this->show_add_window = false;
        $this->resetValidation('new_title');

        $term = $this->normalizeInput((string) $value);

        if (mb_strlen($term) < 2) {
            return;
        }

        $query = Translator::query()
            ->select(['id', 'title'])
            ->where('title', 'LIKE', '%' . addcslashes($term, '\\%_') . '%')
            ->orderBy('title')
            ->limit(8);

        if (! empty($this->translator_ids)) {
            $query->whereNotIn('id', $this->translator_ids);
        }

        $this->search_results = $query->get()
            ->map(fn (Translator $translator) => [
                'id' => (int) $translator->id,
                'title' => (string) $translator->title,
            ])
            ->all();
    }

    public function toggleAddWindow(): void
    {
        $this->show_add_window = ! $this->show_add_window;
        $this->resetValidation('new_title');

        if ($this->show_add_window) {
            $this->new_title = $this->normalizeInput($this->search);
            $this->search_results = [];
        }
    }

    public function closePanels(): void
    {
        $this->show_add_window = false;
        $this->search_results = [];
        $this->resetValidation('new_title');
    }

    public function addTranslator($id): void
    {
        $translator = Translator::query()->find((int) $id);

        if (! $translator) {
            return;
        }

        $this->appendTranslator($translator);
        $this->resetPicker();
    }

    public function makeNewTranslator(): void
    {
        $this->new_title = $this->normalizeInput($this->new_title);

        $this->validate([
            'new_title' => ['required', 'string', 'min:2', 'max:191'],
        ], [
            'new_title.required' => 'Ime prevoditelja je obvezno.',
            'new_title.min' => 'Ime prevoditelja mora imati najmanje 2 znaka.',
            'new_title.max' => 'Ime prevoditelja ne smije imati više od 191 znaka.',
        ]);

        try {
            $translator = Translator::findOrCreateByTitle($this->new_title);
        } catch (\InvalidArgumentException $exception) {
            $this->addError('new_title', $exception->getMessage());

            return;
        }

        $created = $translator->wasRecentlyCreated;
        $added = $this->appendTranslator($translator);

        $this->resetPicker();

        if (! $added) {
            $this->emit('error_alert', ['message' => 'Prevoditelj je već odabran.']);

            return;
        }

        $this->emit('success_alert', [
            'message' => $created
                ? 'Prevoditelj je uspješno dodan i odabran.'
                : 'Postojeći prevoditelj je odabran.',
        ]);
    }

    public function removeTranslator($id): void
    {
        $index = array_search((int) $id, $this->translator_ids, true);

        if ($index === false) {
            return;
        }

        array_splice($this->translator_ids, $index, 1);
        array_splice($this->selected_translators, $index, 1);
    }

    public function moveTranslator($id, $direction): void
    {
        $direction = (int) $direction;

        if (! in_array($direction, [-1, 1], true)) {
            return;
        }

        $index = array_search((int) $id, $this->translator_ids, true);

        if ($index === false) {
            return;
        }

        $target = $index + $direction;

        if ($target < 0 || $target >= count($this->translator_ids)) {
            return;
        }

        [$this->translator_ids[$index], $this->translator_ids[$target]] = [
            $this->translator_ids[$target],
            $this->translator_ids[$index],
        ];
        [$this->selected_translators[$index], $this->selected_translators[$target]] = [
            $this->selected_translators[$target],
            $this->selected_translators[$index],
        ];

        $this->translator_ids = array_values($this->translator_ids);
        $this->selected_translators = array_values($this->selected_translators);
    }

    public function render()
    {
        return view('livewire.back.layout.search.translator-search');
    }

    private function appendTranslator(Translator $translator): bool
    {
        $id = (int) $translator->getKey();

        if (in_array($id, $this->translator_ids, true)) {
            return false;
        }

        $this->translator_ids[] = $id;
        $this->selected_translators[] = [
            'id' => $id,
            'title' => (string) $translator->title,
        ];

        return true;
    }

    private function resetPicker(): void
    {
        $this->search = '';
        $this->search_results = [];
        $this->show_add_window = false;
        $this->new_title = '';
        $this->resetValidation('new_title');
    }

    private function normalizeInput(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === null ? trim($value) : $normalized;
    }
}
