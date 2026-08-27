<?php

namespace App\Models\Back\Catalog;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Concerns\FindsSemanticallyEquivalentTitles;
use App\Models\Back\Catalog\Product\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Author extends Model
{
    use FindsSemanticallyEquivalentTitles;
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
        if (is_string($request->input('title'))) {
            $request->merge([
                'title' => static::cleanSemanticTitle($request->input('title')),
            ]);
        }

        $titleRules = [
            'required',
            'string',
            'max:' . static::semanticTitleMaxLength(),
        ];

        if (
            is_string($request->input('title'))
            && $this->semanticTitleDiffersFromOriginal($request->input('title'))
        ) {
            $titleRules[] = static::uniqueSemanticTitleRule(
                $this->exists ? $this->getKey() : null,
                'Autor s ovim imenom već postoji.'
            );
        }

        $request->validate([
            'title' => $titleRules,
        ], [
            'title.required' => 'Ime autora je obvezno.',
            'title.max' => 'Ime autora ne smije imati više od 191 znaka.',
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

        return static::withSemanticTitleLock($this->request->title, function () use ($slug) {
            static::assertSemanticTitleIsAvailable(
                $this->request->title,
                null,
                'Autor s ovim imenom već postoji.'
            );

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
                return static::query()->useWritePdo()->find($id);
            }

            return false;
        });
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        $slug = isset($this->request->slug) ? Str::slug($this->request->slug) : Str::slug($this->request->title);

        return static::withSemanticTitleLock($this->request->title, function () use ($slug) {
            if ($this->semanticTitleDiffersFromOriginal($this->request->title)) {
                static::assertSemanticTitleIsAvailable(
                    $this->request->title,
                    $this->getKey(),
                    'Autor s ovim imenom već postoji.'
                );
            }

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
        });
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

        $activated = Author::query()
            ->where('status', 0)
            ->whereIn('id', self::visibleProductAuthorIds())
            ->update(['status' => 1]);

        $deactivated = Author::query()
            ->where('status', 1)
            ->whereNotIn('id', self::visibleProductAuthorIds())
            ->update(['status' => 0]);

        self::flushIndexCache();
        $log_end = microtime(true);

        Log::info(
            '__Check Author Statuses - Activated: ' . $activated .
            ' Deactivated: ' . $deactivated .
            ' Total Execution Time: ' . number_format(($log_end - $log_start), 2, ',', '.') . ' sec.'
        );

        return 1;
    }

    private static function visibleProductAuthorIds()
    {
        return DB::table('products')
            ->select('author_id')
            ->where('author_id', '>', 0)
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('price', '!=', 0)
            ->distinct();
    }

    private static function flushIndexCache(): void
    {
        if (app()->environment('local')) {
            return;
        }

        Cache::tags(['authors'])->flush();
    }
}
