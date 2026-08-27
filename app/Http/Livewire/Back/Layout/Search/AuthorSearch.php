<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Author;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;
use Livewire\Component;

class AuthorSearch extends Component
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
    public $author_id = 0;

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
        if ($this->author_id) {
            $author = Author::find($this->author_id);

            if ($author) {
                $this->search = $author->title;
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
            $this->new['title'] = Author::cleanSemanticTitle((string) $this->search);
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
        $this->author_id = 0;

        if (mb_strlen(trim($this->search)) >= 2) {
            $this->search_results = (new Author())->where('title', 'LIKE', '%' . $this->search . '%')
                                                  ->orderBy('title')
                                                  ->limit(6)
                                                  ->get();
        }
    }


    /**
     * @param $id
     */
    public function addAuthor($id)
    {
        $author = (new Author())->where('id', $id)->first();

        if ( ! $author) {
            return;
        }

        $this->search_results = [];
        $this->search         = $author->title;
        $this->author_id     = $author->id;

        if ($this->list) {
            return $this->emit('authorSelect', ['author' => $author->toArray()]);
        }
    }


    /**
     *
     */
    public function makeNewAuthor()
    {
        $this->new['title'] = Author::cleanSemanticTitle(
            is_string($this->new['title'] ?? null) ? $this->new['title'] : ''
        );

        $this->validate([
            'new.title' => ['required', 'string', 'max:' . Author::semanticTitleMaxLength()],
        ], [
            'new.title.required' => 'Ime autora je obvezno.',
            'new.title.max' => 'Ime autora ne smije imati više od 191 znaka.',
        ]);

        $slug = Str::slug($this->new['title']);

        try {
            $author = Author::findOrCreateBySemanticTitle($this->new['title'], [
                'letter'           => Helper::resolveFirstLetter($this->new['title']),
                'description'      => '',
                'meta_title'       => $this->new['title'],
                'meta_description' => '',
                'lang'             => 'hr',
                'sort_order'       => 0,
                'status'           => 1,
                'slug'             => $slug,
                'url'              => config('settings.author_path') . '/' . $slug,
            ]);
        } catch (LockTimeoutException $exception) {
            return $this->emit('error_alert', [
                'message' => 'Autor se trenutačno sprema. Molimo pokušajte ponovno.',
            ]);
        }

        $created = $author->wasRecentlyCreated;

        $this->show_add_window = false;
        $this->author_id = $author->id;
        $this->search = $author->title;
        $this->new['title'] = '';
        $this->resetValidation('new.title');

        return $this->emit('success_alert', [
            'message' => $created
                ? 'Autor je uspješno dodan.'
                : 'Postojeći autor je odabran.',
        ]);
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ($this->search == '') {
            $this->author_id = 0;

            if ($this->list) {
                $this->emit('authorSelect', ['author' => ['id' => '']]);
            }
        }

        return view('livewire.back.layout.search.author-search');
    }
}
