<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use Livewire\Component;

class PublisherSearch extends Component
{
    private const RESULTS_PER_PAGE = 20;

    /**
     * @var string
     */
    public $search = '';

    /**
     * @var array
     */
    public $search_results = [];

    /**
     * @var int
     */
    public $result_limit = self::RESULTS_PER_PAGE;

    /**
     * @var bool
     */
    public $has_more_results = false;

    /**
     * @var int
     */
    public $publisher_id = 0;

    /**
     * @var bool
     */
    public $show_add_window = false;

    /**
     * @var null|bool
     */
    public $list = null;

    /**
     * @var array
     */
    public $new = [
        'title' => ''
    ];


    /**
     *
     */
    public function mount()
    {
        if ($this->publisher_id) {
            $publisher = Publisher::find($this->publisher_id);

            if ($publisher) {
                $this->search = $publisher->title;
            }
        }
    }


    /**
     *
     */
    public function viewAddWindow()
    {
        $this->show_add_window = ! $this->show_add_window;
        $this->resetValidation('new.title');

        if ($this->show_add_window) {
            $this->new['title'] = Publisher::cleanSemanticTitle((string) $this->search);
            $this->search_results = [];
            $this->has_more_results = false;
        }
    }


    /**
     *
     */
    public function updatingSearch($value)
    {
        $this->search         = $value;
        $this->search_results = [];
        $this->show_add_window = false;
        $this->publisher_id = 0;
        $this->result_limit = self::RESULTS_PER_PAGE;

        $this->refreshSearchResults($value);
    }


    /**
     * Load the next group of matching publishers without rendering the whole
     * catalog on every keystroke.
     */
    public function loadMore()
    {
        if (! $this->has_more_results) {
            return;
        }

        $this->result_limit += self::RESULTS_PER_PAGE;
        $this->refreshSearchResults($this->search);
    }


    /**
     * @param mixed $value
     */
    private function refreshSearchResults($value): void
    {
        $search = Publisher::cleanSemanticTitle((string) $value);

        if (mb_strlen($search) < 2) {
            $this->has_more_results = false;

            return;
        }

        $publishers = Publisher::query()
            ->where('title', 'LIKE', '%' . $search . '%')
            ->orderByRaw(
                'CASE WHEN LOWER(TRIM(title)) = LOWER(?) THEN 0 '
                . 'WHEN LOWER(TRIM(title)) LIKE LOWER(?) THEN 1 ELSE 2 END',
                [$search, $search . '%']
            )
            ->orderBy('title')
            ->orderBy('id')
            ->limit($this->result_limit + 1)
            ->get(['id', 'title']);

        $this->has_more_results = $publishers->count() > $this->result_limit;
        $this->search_results = $publishers
            ->take($this->result_limit)
            ->map(function (Publisher $publisher): array {
                return [
                    'id' => (int) $publisher->id,
                    'title' => (string) $publisher->title,
                ];
            })
            ->values()
            ->all();
    }


    /**
     * @param $id
     */
    public function addPublisher($id)
    {
        $publisher = (new Publisher())->where('id', $id)->first();

        if ( ! $publisher) {
            return;
        }

        $this->search_results = [];
        $this->has_more_results = false;
        $this->search         = $publisher->title;
        $this->publisher_id     = $publisher->id;

        if ($this->list) {
            return $this->emit('publisherSelect', ['publisher' => $publisher->toArray()]);
        }
    }


    /**
     *
     */
    public function makeNewPublisher()
    {
        $this->new['title'] = Publisher::cleanSemanticTitle(
            is_string($this->new['title'] ?? null) ? $this->new['title'] : ''
        );

        $this->validate([
            'new.title' => ['required', 'string', 'max:' . Publisher::semanticTitleMaxLength()],
        ], [
            'new.title.required' => 'Naziv izdavača je obvezan.',
            'new.title.max' => 'Naziv izdavača ne smije imati više od 191 znaka.',
        ]);

        $slug = Str::slug($this->new['title']);

        try {
            $publisher = Publisher::findOrCreateBySemanticTitle($this->new['title'], [
                'letter'           => Helper::resolveFirstLetter($this->new['title']),
                'description'      => '',
                'meta_title'       => $this->new['title'],
                'meta_description' => '',
                'lang'             => 'hr',
                'sort_order'       => 0,
                'status'           => 1,
                'slug'             => $slug,
                'url'              => config('settings.publisher_path') . '/' . $slug,
            ]);
        } catch (LockTimeoutException $exception) {
            return $this->emit('error_alert', [
                'message' => 'Izdavač se trenutačno sprema. Molimo pokušajte ponovno.',
            ]);
        }

        $created = $publisher->wasRecentlyCreated;

        $this->show_add_window = false;
        $this->publisher_id = $publisher->id;
        $this->search = $publisher->title;
        $this->new['title'] = '';
        $this->resetValidation('new.title');

        return $this->emit('success_alert', [
            'message' => $created
                ? 'Izdavač je uspješno dodan.'
                : 'Postojeći izdavač je odabran.',
        ]);
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ($this->search == '') {
            $this->publisher_id = 0;

            if ($this->list) {
                $this->emit('publisherSelect', ['publisher' => ['id' => '']]);
            }
        }

        return view('livewire.back.layout.search.publisher-search');
    }
}
