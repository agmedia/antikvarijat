<?php

namespace App\Models\Back\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Page extends Model
{

    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'pages';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeSubgroups(Builder $query): Builder
    {
        return $query->groupBy('subgroup')->whereNotNull('subgroup');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeGroups(Builder $query): Builder
    {
        return $query->groupBy('group')->whereNotNull('group');
    }


    /**
     * Validate new category Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'title' => 'required'
        ]);

        $this->request = $request;

        return $this;
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $id = $this->insertGetId([
            'category_id'       => null,
            'group'             => 'page',
            'subgroup'          => $this->request->group ?: null,
            'title'             => $this->request->title,
            'short_description' => $this->request->short_description,
            'description'       => $this->request->description,
            'meta_title'        => $this->request->meta_title,
            'meta_description'  => $this->request->meta_description,
            'title_en'          => $this->request->title_en ?: null,
            'short_description_en' => $this->request->short_description_en ?: null,
            'description_en'    => $this->request->description_en ?: null,
            'meta_title_en'     => $this->request->meta_title_en ?: null,
            'meta_description_en' => $this->request->meta_description_en ?: null,
            'slug'              => isset($this->request->slug) ? Str::slug($this->request->slug) : Str::slug($this->request->title),
            'slug_en'           => $this->resolveSlugEn(),
            'keywords'          => null,
            'keywords_en'       => $this->request->keywords_en ?: null,
            'publish_date'      => null,
            'keywords'          => false,
            'status'            => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now()
        ]);

        if ($id) {
            return $this->find($id);
        }

        return false;
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        $id = $this->update([
            'category_id'       => null,
            'group'             => 'page',
            'subgroup'          => $this->request->group ?: null,
            'title'             => $this->request->title,
            'short_description' => $this->request->short_description,
            'description'       => $this->request->description,
            'meta_title'        => $this->request->meta_title,
            'meta_description'  => $this->request->meta_description,
            'title_en'          => $this->request->title_en ?: null,
            'short_description_en' => $this->request->short_description_en ?: null,
            'description_en'    => $this->request->description_en ?: null,
            'meta_title_en'     => $this->request->meta_title_en ?: null,
            'meta_description_en' => $this->request->meta_description_en ?: null,
            'slug'              => isset($this->request->slug) ? Str::slug($this->request->slug) : Str::slug($this->request->title),
            'slug_en'           => $this->resolveSlugEn('update'),
            'keywords'          => null,
            'keywords_en'       => $this->request->keywords_en ?: null,
            'publish_date'      => null,
            'keywords'          => false,
            'status'            => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'        => Carbon::now()
        ]);

        if ($id) {
            return $this->find($this->id);
        }

        return false;
    }


    /**
     * @param Category $category
     *
     * @return bool
     */
    public function resolveImage(Page $page)
    {
        if ($this->request->hasFile('image')) {
            $name = Str::slug($page->title) . '-' . Str::random(9) . '.' . $this->request->image->extension();

            $this->request->image->storeAs('/', $name, 'page');

            return $page->update([
                'image' => config('filesystems.disks.page.url') . $name
            ]);
        }

        return false;
    }

    private function resolveSlugEn(string $target = 'insert'): ?string
    {
        $slug = trim((string) $this->request->input('slug_en', ''));

        if ($slug === '' && $target === 'update') {
            $slug = (string) $this->getRawOriginal('slug_en');
        }

        if ($slug === '' && $this->request->filled('title_en')) {
            $slug = (string) $this->request->title_en;
        }

        if ($slug === '') {
            return null;
        }

        $slug = Str::slug($slug);
        $exist = $this->where('slug_en', $slug);

        if ($target === 'update') {
            $exist->where('id', '!=', $this->id);
        }

        if ($exist->exists()) {
            return $slug . '-' . time();
        }

        return $slug;
    }
}
