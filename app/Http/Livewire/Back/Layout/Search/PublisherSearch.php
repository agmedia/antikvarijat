<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Publisher;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use Livewire\Component;

class PublisherSearch extends Component
{

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

        if (mb_strlen(trim($this->search)) >= 2) {
            $this->search_results = (new Publisher())->where('title', 'LIKE', '%' . $this->search . '%')
                                                  ->orderBy('title')
                                                  ->limit(6)
                                                  ->get();
        }
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
