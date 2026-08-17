<?php

namespace App\Models\Back\Marketing;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class Blog extends Model
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
     * @var array
     */
    protected $casts = [
        'hide_from_home_widget' => 'boolean',
        'recommendation_product_ids' => 'array',
    ];

    /**
     * @var Request
     */
    protected $request;


    /**
     * Validate new category Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->merge([
            'recommendation_type' => $request->input('recommendation_type', 'none'),
        ]);

        $request->validate([
            'title' => 'required',
            'recommendation_type' => ['required', Rule::in(['none', 'author', 'products'])],
            'recommendation_author_id' => [
                'nullable',
                'integer',
                'required_if:recommendation_type,author',
                Rule::exists('authors', 'id'),
            ],
            'recommendation_product_ids' => [
                'nullable',
                'array',
                'required_if:recommendation_type,products',
                'max:20',
            ],
            'recommendation_product_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('products', 'id'),
            ],
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
        $recommendations = $this->recommendationData();

        $id = $this->insertGetId([
            'category_id'       => null,
            'group'             => 'blog',
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
            'publish_date'      => $this->request->publish_date ? Carbon::make($this->request->publish_date) : null,
            'keywords'          => false,
            'hide_from_home_widget' => $this->request->input('hide_from_home_widget') === 'on',
            'recommendation_type' => $recommendations['type'],
            'recommendation_author_id' => $recommendations['author_id'],
            'recommendation_product_ids' => $recommendations['product_ids']
                ? json_encode($recommendations['product_ids'])
                : null,
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
        $recommendations = $this->recommendationData();

        $id = $this->update([
            'category_id'       => null,
            'group'             => 'blog',
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
            'publish_date'      => $this->request->publish_date ? Carbon::make($this->request->publish_date) : null,
            'keywords'          => false,
            'hide_from_home_widget' => $this->request->input('hide_from_home_widget') === 'on',
            'recommendation_type' => $recommendations['type'],
            'recommendation_author_id' => $recommendations['author_id'],
            'recommendation_product_ids' => $recommendations['product_ids'] ?: null,
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
    public function resolveImage(Blog $blog)
    {
        if ($this->request->hasFile('image')) {
            $img = Image::make($this->request->image);
            $str = $blog->id . '/' . Str::slug($blog->title) . '-' . time() . '.';

            $path = $str . 'jpg';
            Storage::disk('blog')->put($path, $img->encode('jpg'));

            $path_webp = $str . 'webp';
            Storage::disk('blog')->put($path_webp, $img->encode('webp'));

            return $blog->update([
                'image' => config('filesystems.disks.blog.url') . $path
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

    private function recommendationData(): array
    {
        $type = (string) $this->request->input('recommendation_type', 'none');
        $productIds = collect($this->request->input('recommendation_product_ids', []))
            ->filter(fn ($id) => ctype_digit((string) $id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(20)
            ->values()
            ->all();

        return [
            'type' => $type,
            'author_id' => $type === 'author'
                ? (int) $this->request->input('recommendation_author_id')
                : null,
            'product_ids' => $type === 'products' ? $productIds : [],
        ];
    }
}
