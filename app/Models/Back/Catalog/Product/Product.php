<?php

namespace App\Models\Back\Catalog\Product;

use App\Helpers\Helper;
use App\Helpers\ProductHelper;
use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Publisher;
use App\Models\Back\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Back\Catalog\Product\ProductHistory;
use App\Models\ProductReview;

class Product extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'products';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;

    protected $casts = [
        'tags'               => 'array',
        'delivery_in_7_days' => 'boolean',
    ];

    /**
     * @var null
     */
    protected $old_product = null;

    /**
     * @return Relation
     */
    public function categories()
    {
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', '=', 0);
    }

    /**
     * @return Relation
     */
    public function subcategories()
    {
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', '!=', 0);
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function category()
    {
        return $this->hasOneThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', '=', 0)
            ->first();
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function subcategory()
    {
        return $this->hasOneThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', '!=', 0)
            ->first();
    }

    /**
     * @return Relation
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function publisher()
    {
        return $this->belongsTo(Publisher::class, 'publisher_id', 'id');
    }

    /**
     * @return Relation
     */
    public function all_actions()
    {
        return $this->hasOne(ProductAction::class, 'product_id');
    }

    public function historyLogs()
    {
        return $this->hasMany(ProductHistory::class, 'target_id')
            ->where('target', 'product')
            ->orderByDesc('created_at');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    /**
     * @return false|mixed
     */
    public function special()
    {
        if ($this->special) {
            $from = now()->subDay();
            $to   = now()->addDay();

            if ($this->special_from) {
                $from = Carbon::make($this->special_from);
            }
            if ($this->special_to) {
                $to = Carbon::make($this->special_to);
            }

            if ($from <= now() && now() <= $to) {
                return $this->special;
            }
        }

        return false;
    }

    public function imageName()
    {
        $from   = strrpos($this->image, '/') + 1;
        $length = strrpos($this->image, '-') - $from;

        return substr($this->image, $from, $length);
    }

    public function getThumbAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_ends_with($this->image, '.webp')) {
            return str_replace('.webp', '-thumb.webp', $this->image);
        }

        return preg_replace('/\.(jpe?g|png)$/i', '-thumb.webp', $this->image) ?: $this->image;
    }

    /**
     * Validate New Product Request.
     */
    public function validateRequest(Request $request)
    {
        $currentId = $this->id
            ?? $request->route('product')
            ?? $request->route('id')
            ?? $request->input('id');

        $request->validate([
            'name'     => ['required'],
            'sku'      => [
                'required',
                Rule::unique('products', 'sku')->ignore($currentId),
            ],
            'price'    => ['required'],
            'category' => ['required'],
            'skl'      => ['nullable', 'integer', 'min:0'],
            'delivery_in_7_days' => ['boolean'],
            'isbn'     => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && trim((string) $value) !== '' && ! static::isValidIsbn((string) $value)) {
                        $fail('ISBN mora biti ispravan ISBN-10 ili ISBN-13.');
                    }
                },
            ],
            'tags'     => ['nullable'], // može biti string ili array
        ]);

        $this->setRequest($request);

        return $this;
    }

    /**
     * Create and return new Product Model.
     */
    public function create()
    {
        $slug = $this->resolveSlug();

        $product = new self();

        $product->author_id        = $this->request->author_id ?: 6;
        $product->publisher_id     = $this->request->publisher_id ?: 2;
        $product->action_id        = $this->request->action ?: 0;
        $product->name             = $this->request->name;
        $product->sku              = $this->request->sku;
        $product->isbn             = static::normalizeIsbn($this->request->isbn);
        $product->polica           = $this->request->polica;
        $product->description      = $this->cleanHTML($this->request->description);
        $product->slug             = $slug;
        $product->name_en          = $this->request->name_en ?: null;
        $product->description_en   = $this->cleanHTML($this->request->description_en);
        $product->slug_en          = $this->resolveSlugEn();

        // Mutator sprema kao JSON array
        $product->tags             = $this->request->tags;

        $product->price            = $this->request->price;
        $product->quantity         = $this->request->quantity ?: 0;
        $product->skl              = $this->resolveSkl();
        $product->delivery_in_7_days = $this->request->boolean('delivery_in_7_days');
        $product->tax_id           = $this->request->tax_id ?: 1;
        $product->special          = $this->request->special;
        $product->special_from     = $this->request->special_from ? Carbon::make($this->request->special_from) : null;
        $product->special_to       = $this->request->special_to ? Carbon::make($this->request->special_to) : null;
        $product->meta_title       = $this->request->meta_title ?: $this->request->name;
        $product->meta_description = $this->request->meta_description;
        $product->meta_title_en    = $this->request->meta_title_en ?: null;
        $product->meta_description_en = $this->request->meta_description_en ?: null;
        $product->pages            = $this->request->pages;
        $product->dimensions       = $this->request->dimensions;
        $product->origin           = $this->request->origin;
        $product->letter           = $this->request->letter;
        $product->condition        = $this->request->condition;
        $product->binding          = $this->request->binding;
        $product->year             = $this->request->year;
        $product->viewed           = 0;
        $product->sort_order       = 0;
        $product->push             = 0;
        $product->status           = (isset($this->request->status) && $this->request->status == 'on') ? 1 : 0;
        $product->created_at       = Carbon::now();
        $product->updated_at       = Carbon::now();

        $product->save();

        $this->resolveCategories($product->id);

        $product->update([
            'url'             => ProductHelper::url($product),
            'url_en'          => ProductHelper::urlEn($product),
            'category_string' => ProductHelper::categoryString($product),
        ]);

        return $product;
    }

    /**
     * Update and return new Product Model.
     */
    public function edit()
    {
        $this->old_product = $this->historySnapshot();

        $slug = $this->resolveSlug('update');

        $updated = $this->update([
            'author_id'        => $this->request->author_id ?: 6,
            'publisher_id'     => $this->request->publisher_id ?: 2,
            'action_id'        => $this->request->action ?: 0,
            'name'             => $this->request->name,
            'sku'              => $this->request->sku,
            'isbn'             => static::normalizeIsbn($this->request->isbn),
            'polica'           => $this->request->polica,
            'description'      => $this->cleanHTML($this->request->description),
            'slug'             => $slug,
            'name_en'          => $this->request->name_en ?: null,
            'description_en'   => $this->cleanHTML($this->request->description_en),
            'slug_en'          => $this->resolveSlugEn('update'),
            'tags'             => $this->request->tags,
            'price'            => isset($this->request->price) ? $this->request->price : 0,
            'quantity'         => $this->request->quantity ?: 0,
            'skl'              => $this->resolveSkl(),
            'delivery_in_7_days' => $this->request->boolean('delivery_in_7_days'),
            'tax_id'           => $this->request->tax_id ?: 1,
            'special'          => $this->request->special,
            'special_from'     => $this->request->special_from ? Carbon::make($this->request->special_from) : null,
            'special_to'       => $this->request->special_to ? Carbon::make($this->request->special_to) : null,
            'meta_title'       => $this->request->meta_title ?: $this->request->name,
            'meta_description' => $this->request->meta_description,
            'meta_title_en'    => $this->request->meta_title_en ?: null,
            'meta_description_en' => $this->request->meta_description_en ?: null,
            'pages'            => $this->request->pages,
            'dimensions'       => $this->request->dimensions,
            'origin'           => $this->request->origin,
            'letter'           => $this->request->letter,
            'condition'        => $this->request->condition,
            'binding'          => $this->request->binding,
            'year'             => $this->request->year,
            'viewed'           => 0,
            'sort_order'       => 0,
            'push'             => 0,
            'status'           => (isset($this->request->status) && $this->request->status == 'on') ? 1 : 0,
            'updated_at'       => Carbon::now(),
        ]);

        if ($updated) {
            $this->resolveCategories($this->id);

            $this->update([
                'url'             => ProductHelper::url($this),
                'url_en'          => ProductHelper::urlEn($this),
                'category_string' => ProductHelper::categoryString($this),
            ]);

            return $this;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getRelationsData(bool $withImages = true): array
    {
        return [
            'categories' => (new Category())->getList(false),
            'images'     => $withImages ? ProductImage::getAdminList($this->id) : collect(),
            'letters'    => Settings::get('product', 'letter_styles'),
            'conditions' => Settings::get('product', 'condition_styles'),
            'bindings'   => Settings::get('product', 'binding_styles'),
            'taxes'      => Settings::get('tax', 'list'),
        ];
    }

    public function checkSettings()
    {
        Settings::setProduct('letter_styles', $this->request->letter);
        Settings::setProduct('condition_styles', $this->request->condition);
        Settings::setProduct('binding_styles', $this->request->binding);

        return $this;
    }

    public function storeImages(Product $product)
    {
        return (new ProductImage())->store($product, $this->request);
    }

    public function addHistoryData(string $type, ?array $oldProduct = null)
    {
        $new = $this->historySnapshot();

        $history = new ProductHistory([], $new, $oldProduct ?? $this->old_product);

        return $history->addData($type);
    }

    /**
     * Capture the complete product state used by the admin change history.
     */
    public function historySnapshot(): array
    {
        $product = $this->newQuery()->findOrFail($this->id);

        $response                = $product->toArray();
        $response['category']    = $product->category() ? $product->category()->toArray() : [];
        $response['subcategory'] = $product->subcategory() ? $product->subcategory()->toArray() : [];
        $response['images']      = $product->images()->get()->toArray();

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return Builder
     */
    public function filter(Request $request): Builder
    {
        $query = (new Product())->newQuery();

        // Pretraga po nazivu, opisu, sku, polici, godini
        if ($request->has('search') && !empty($request->input('search'))) {
            $searchTerm = $request->input('search');
            $normalizedIsbn = static::normalizeIsbn($searchTerm);

            $query->where(function ($q) use ($searchTerm, $normalizedIsbn) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('sku', 'like', '%' . $searchTerm . '%');

                if ($normalizedIsbn !== null && $normalizedIsbn !== '') {
                    $q->orWhere('isbn', 'like', '%' . $normalizedIsbn . '%');
                }

                $q
                    ->orWhere('polica', 'like', '%' . $searchTerm . '%')
                    ->orWhere('year', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter po kategoriji
        if ($request->has('category') && !empty($request->input('category'))) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('id', $request->input('category'));
            });
        }

        // Filter po autoru
        if ($request->has('author') && !empty($request->input('author'))) {
            $query->where('author_id', $request->input('author'));
        }

        // Filter po izdavaču
        if ($request->has('publisher') && !empty($request->input('publisher'))) {
            $query->where('publisher_id', $request->input('publisher'));
        }

        // Filter po statusu
        if ($request->has('status')) {
            if ($request->input('status') == 'available') {
                $query->where('quantity', '>', 0);
            }
            if ($request->input('status') == 'unavailable') {
                $query->where('quantity', '<=', 0);
            }
        }

        // Sortiranje
        if ($request->has('sort')) {
            switch ($request->input('sort')) {
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'old':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'price_up':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_down':
                    $query->orderBy('price', 'desc');
                    break;
                case 'az':
                    $query->orderBy('name', 'asc');
                    break;
                case 'za':
                    $query->orderBy('name', 'desc');
                    break;
                case 'qty_up':
                    $query->orderBy('quantity', 'asc');
                    break;
                case 'qty_down':
                    $query->orderBy('quantity', 'desc');
                    break;
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return $query;
    }

    public static function normalizeIsbn($isbn): ?string
    {
        if ($isbn === null || trim((string) $isbn) === '') {
            return null;
        }

        return strtoupper((string) preg_replace('/[^0-9X]/i', '', (string) $isbn));
    }

    public static function isValidIsbn(string $isbn): bool
    {
        $isbn = static::normalizeIsbn($isbn);

        if ($isbn === null) {
            return true;
        }

        if (strlen($isbn) === 10 && preg_match('/^\d{9}[\dX]$/', $isbn)) {
            $sum = 0;
            for ($index = 0; $index < 10; $index++) {
                $value = $isbn[$index] === 'X' ? 10 : (int) $isbn[$index];
                $sum += $value * (10 - $index);
            }

            return $sum % 11 === 0;
        }

        if (strlen($isbn) === 13 && ctype_digit($isbn)) {
            $sum = 0;
            for ($index = 0; $index < 13; $index++) {
                $sum += (int) $isbn[$index] * ($index % 2 === 0 ? 1 : 3);
            }

            return $sum % 10 === 0;
        }

        return false;
    }

    private function setRequest($request)
    {
        $this->request = $request;
    }

    private function cleanHTML($description = null): string
    {
        $clean = preg_replace('/ style=("|\')(.*?)("|\')/', '', $description ?: '');

        return preg_replace('/ face=("|\')(.*?)("|\')/', '', $clean);
    }

    private function resolveSkl(): ?int
    {
        $value = $this->request->input('skl');

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Mutator za spremanje tags kao JSON.
     */
    public function setTagsAttribute($value): void
    {
        $arr = is_array($value) ? $value : explode(',', (string)$value);

        $arr = collect($arr)
            ->map(fn($t) => trim(mb_strtolower($t)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->attributes['tags'] = empty($arr) ? null : json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Accessor za prikaz tagova kao comma-separated string.
     */
    public function getTagsStringAttribute(): string
    {
        $arr = $this->tags ?? [];
        return implode(',', $arr);
    }

    private function resolveCategories(int $product_id)
    {
        if ($this->request->category) {
            return ProductCategory::storeData(intval($this->request->category), $product_id);
        }

        return false;
    }

    private function resolveSlug(string $target = 'insert', ?Request $request = null): string
    {
        $slug = null;

        if ($request) {
            $this->request = $request;
        }

        if ($target == 'update') {
            $product = Product::where('id', $this->id)->first();
            if ($product) {
                $slug = $product->slug;
            }
        }

        $slug  = $slug ?: Str::slug($this->request->name);
        $exist = $this->where('slug', $slug)->count();
        $cat_exist = Category::where('slug', $slug)->count();

        if (($cat_exist || $exist > 1) && $target == 'update') {
            return $slug . '-' . time();
        }

        if (($cat_exist || $exist) && $target == 'insert') {
            return $slug . '-' . time();
        }

        return $slug;
    }

    private function resolveSlugEn(string $target = 'insert'): ?string
    {
        $slug = trim((string) $this->request->input('slug_en', ''));

        if ($slug === '' && $target === 'update') {
            $slug = (string) $this->getRawOriginal('slug_en');
        }

        if ($slug === '' && $this->request->filled('name_en')) {
            $slug = (string) $this->request->name_en;
        }

        if ($slug === '') {
            return null;
        }

        $slug = Str::slug($slug);
        $exist = $this->where('slug_en', $slug);

        if ($target === 'update') {
            $exist->where('id', '!=', $this->id);
        }

        if ($exist->exists() || Category::where('slug_en', $slug)->exists()) {
            return $slug . '-' . time();
        }

        return $slug;
    }
}
