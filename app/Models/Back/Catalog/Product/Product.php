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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Bouncer;
use Illuminate\Validation\ValidationException;
use App\Models\Back\Catalog\Product\ProductHistory;

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
        'tags' => 'array',
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
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')->where('parent_id', '==', 0);
    }


    /**
     * @return Relation
     */
    public function subcategories()
    {
        return $this->hasManyThrough(Category::class, ProductCategory::class, 'product_id', 'id', 'id', 'category_id')->where('parent_id', '=!', 0);
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


    /**
     * @return false|mixed
     */
    public function special()
    {
        // If special is set, return special.
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


    /**
     * Validate New Product Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        // Validate the request.
        $request->validate([
            'name'     => 'required',
            'sku'      => 'required',
            'price'    => 'required',
            'category' => 'required',
            'tags'     => 'nullable|string', // prima "a,b,c"
        ]);

        // Set Product Model request variable
        $this->setRequest($request);

        if ($this->isDuplicateSku()) {
            throw ValidationException::withMessages(['sku_dupl' => $this->request->sku . ' - Šifra već postoji...']);
        }

        return $this;
    }


    /**
     * Create and return new Product Model.
     *
     * @return mixed
     */
    public function create()
    {
        $slug = $this->resolveSlug();

        $id = $this->insertGetId([
            'author_id'        => $this->request->author_id ?: 6,
            'publisher_id'     => $this->request->publisher_id ?: 2,
            'action_id'        => $this->request->action ?: 0,
            'name'             => $this->request->name,
            'sku'              => $this->request->sku,
            'polica'           => $this->request->polica,
            'description'      => $this->cleanHTML($this->request->description),
            'slug'             => $slug,
            'tags'             => $this->request->tags,
            'price'            => $this->request->price,
            'quantity'         => $this->request->quantity ?: 0,
            'tax_id'           => $this->request->tax_id ?: 1,
            'special'          => $this->request->special,
            'special_from'     => $this->request->special_from ? Carbon::make($this->request->special_from) : null,
            'special_to'       => $this->request->special_to ? Carbon::make($this->request->special_to) : null,
            'meta_title'       => $this->request->meta_title ?: $this->request->name/* . ($author ? '-' . $author->title : '')*/,
            'meta_description' => $this->request->meta_description,
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
            'status'           => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            $this->resolveCategories($id);

            $product = $this->find($id);

            $product->update([
                'url'             => ProductHelper::url($product),
                'category_string' => ProductHelper::categoryString($product)
            ]);

            return $product;
        }

        return false;
    }


    /**
     * Update and return new Product Model.
     *
     * @return mixed
     */
    public function edit()
    {
        $this->old_product = $this->setHistoryProduct();

        $slug = $this->resolveSlug('update');

        $updated = $this->update([
            'author_id'        => $this->request->author_id ?: 6,
            'publisher_id'     => $this->request->publisher_id ?: 2,
            'action_id'        => $this->request->action ?: 0,
            'name'             => $this->request->name,
            'sku'              => $this->request->sku,
            'polica'           => $this->request->polica,
            'description'      => $this->cleanHTML($this->request->description),
            'slug'             => $slug,
            'tags'             => $this->request->tags,
            'price'            => isset($this->request->price) ? $this->request->price : 0,
            'quantity'         => $this->request->quantity ?: 0,
            'tax_id'           => $this->request->tax_id ?: 1,
            'special'          => $this->request->special,
            'special_from'     => $this->request->special_from ? Carbon::make($this->request->special_from) : null,
            'special_to'       => $this->request->special_to ? Carbon::make($this->request->special_to) : null,
            'meta_title'       => $this->request->meta_title ?: $this->request->name/* . '-' . ($author ? '-' . $author->title : '')*/,
            'meta_description' => $this->request->meta_description,
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
            'status'           => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'       => Carbon::now()
        ]);

        if ($updated) {
            $this->resolveCategories($this->id);

            $this->update([
                'url'             => ProductHelper::url($this),
                'category_string' => ProductHelper::categoryString($this)
            ]);

            return $this;
        }

        return false;
    }


    /**
     * @return array
     */
    public function getRelationsData(): array
    {
        return [
            'categories' => (new Category())->getList(false),
            'images'     => ProductImage::getAdminList($this->id),
            'letters'    => Settings::get('product', 'letter_styles'),
            'conditions' => Settings::get('product', 'condition_styles'),
            'bindings'   => Settings::get('product', 'binding_styles'),
            'taxes'      => Settings::get('tax', 'list')
        ];
    }


    /**
     * @return $this
     */
    public function checkSettings()
    {
        Settings::setProduct('letter_styles', $this->request->letter);
        Settings::setProduct('condition_styles', $this->request->condition);
        Settings::setProduct('binding_styles', $this->request->binding);

        return $this;
    }


    /**
     * @param Product $product
     *
     * @return mixed
     */
    public function storeImages(Product $product)
    {
      // return (new ProductImage())->store($product, $this->request);
    }


    /**
     * @param string  $type
     * @param Product $product
     *
     * @return false
     */
    public function addHistoryData(string $type)
    {
        $new = $this->setHistoryProduct();

        $history = new ProductHistory([],$new, $this->old_product);

        return $history->addData($type);
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

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('sku', 'like', '%' . $searchTerm . '%')
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



    /**
     * Set Product Model request variable.
     *
     * @param $request
     */
    private function setRequest($request)
    {
        $this->request = $request;
    }


    /**
     * @return mixed
     */
    private function setHistoryProduct()
    {
        $product = $this->where('id', $this->id)->first();

        $response             = $product->toArray();
        $response['category'] = [];

        if ($product->category()) {
            $response['category'] = $product->category()->toArray();
        }

        $response['subcategory'] = $product->subcategory() ? $product->subcategory()->toArray() : [];
        $response['images']      = $product->images()->get()->toArray();

        return $response;
    }


    /**
     * @param null $description
     *
     * @return string
     */
    private function cleanHTML($description = null): string
    {
        $clean = preg_replace('/ style=("|\')(.*?)("|\')/', '', $description ?: '');

        return preg_replace('/ face=("|\')(.*?)("|\')/', '', $clean);
    }

    /**
     * Pretvara ulaz (string "a,b,c" ili array) u normalizirani array i sprema u JSON.
     */
    public function setTagsAttribute($value): void
    {
        $arr = is_array($value)
            ? $value
            : explode(',', (string)$value);

        $arr = collect($arr)
            ->map(fn($t) => trim(mb_strtolower($t)))
            ->filter()     // ukloni praznine
            ->unique()     // jedinstveni
            ->values()
            ->all();

        // prazno -> null (čistiji JSON u bazi)
        $this->attributes['tags'] = empty($arr) ? null : json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper za prikaz u formi (comma-separated string iz JSON-a).
     * Primjer u Bladeu: value="{{ old('tags', $product->tags_string) }}"
     */
    public function getTagsStringAttribute(): string
    {
        $arr = $this->tags ?? [];
        return implode(',', $arr);
    }



    /**
     * @param int $product_id
     *
     * @return array
     */
    private function resolveCategories(int $product_id)
    {
        if ($this->request->category) {
            return ProductCategory::storeData(intval($this->request->category), $product_id);
        }

        return false;
    }


    /**
     * @param Request|null $request
     *
     * @return string
     */
    private function resolveSlug(string $target = 'insert', Request $request = null): string
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


    /**
     * @return bool
     */
    private function isDuplicateSku(): bool
    {
        $exist = $this->where('sku', $this->request->sku)->first();

        if (isset($this->id) && $exist && $exist->id != $this->id) {
            return true;
        }

        return false;
    }

}
