<?php

namespace App\Models\Back\Catalog;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Product\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'authors';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'author_id', 'id');
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
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
        $slug = isset($this->request->slug) ? Str::slug($this->request->slug) : Str::slug($this->request->title);

        $id = $this->insertGetId([
            'letter'           => Helper::resolveFirstLetter($this->request->title),
            'title'            => $this->request->title,
            'description'      => $this->request->description,
            'meta_title'       => $this->request->meta_title,
            'meta_description' => $this->request->meta_description,
            'title_en'         => $this->request->title_en ?: null,
            'description_en'   => $this->request->description_en ?: null,
            'meta_title_en'    => $this->request->meta_title_en ?: null,
            'meta_description_en' => $this->request->meta_description_en ?: null,
            'lang'             => 'hr',
            'sort_order'       => 0,
            'status'           => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'featured'         => (isset($this->request->featured) and $this->request->featured == 'on') ? 1 : 0,
            'slug'             => $slug,
            'url'              => config('settings.author_path') . '/' . $slug,
            'slug_en'          => $this->resolveSlugEn(),
            'url_en'           => $this->resolveUrlEn($slug),
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
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
        $slug = isset($this->request->slug) ? Str::slug($this->request->slug) : Str::slug($this->request->title);

        $id = $this->update([
            'letter'           => Helper::resolveFirstLetter($this->request->title),
            'title'            => $this->request->title,
            'description'      => $this->request->description,
            'meta_title'       => $this->request->meta_title,
            'meta_description' => $this->request->meta_description,
            'title_en'         => $this->request->title_en ?: null,
            'description_en'   => $this->request->description_en ?: null,
            'meta_title_en'    => $this->request->meta_title_en ?: null,
            'meta_description_en' => $this->request->meta_description_en ?: null,
            'lang'             => 'hr',
            'sort_order'       => 0,
            'status'           => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'featured'         => (isset($this->request->featured) and $this->request->featured == 'on') ? 1 : 0,
            'slug'             => $slug,
            'url'              => config('settings.author_path') . '/' . $slug,
            'slug_en'          => $this->resolveSlugEn('update'),
            'url_en'           => $this->resolveUrlEn($slug),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            return $this;
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

        return $slug !== '' ? Str::slug($slug) : null;
    }

    private function resolveUrlEn(string $fallbackSlug): string
    {
        $slug = $this->resolveSlugEn($this->exists ? 'update' : 'insert') ?: $fallbackSlug;

        return 'en/authors/' . $slug;
    }


    /**
     * @param Category $category
     *
     * @return bool
     */
    public function resolveImage(Author $author)
    {
        if ($this->request->hasFile('image')) {
            $name = Str::slug($author->title) . '.' . $this->request->image->extension();

            $this->request->image->storeAs('/', $name, 'publisher');

            return $author->update([
                'image' => config('filesystems.disks.author.url') . $name
            ]);
        }

        return false;
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @return int
     */
    public static function checkStatuses_CRON()
    {
        $log_start = microtime(true);

        $total = Author::query()->pluck('id');

        $authors_with = Author::query()->whereHas('products', function ($query) {
            $query->where('status', 1);
        })->pluck('id');

        $authors_without = $total->diff($authors_with);

        Author::query()->whereIn('id', $authors_with)->update(['status' => 1]);
        Author::query()->whereIn('id', $authors_without)->update(['status' => 0]);

        $log_end = microtime(true);
        Log::info('__Check Author Statuses - Total Execution Time: ' . number_format(($log_end - $log_start), 2, ',', '.') . ' sec.');

        return 1;
    }
}
