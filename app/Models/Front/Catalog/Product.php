<?php

namespace App\Models\Front\Catalog;

use App\Models\Back\Catalog\Product\ProductAction;
use App\Models\Back\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Bouncer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Product extends Model
{

    /**
     * @var string
     */
    protected $table = 'products';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getImageAttribute($value)
    {
        return config('settings.images_domain') . str_replace('.jpg', '.webp', $value);
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getThumbAttribute($value)
    {
        return str_replace('.webp', '-thumb.webp', $this->image);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->where('published', 1)->orderBy('sort_order');
    }


    /**
     * @return false|float|int|mixed
     */
    public function special()
    {
        // If special is set, return special.
        if ($this->special) {
            $from = now()->subDay();
            $to = now()->addDay();

            if ($this->special_from && $this->special_from != '0000-00-00 00:00:00') {
                $from = Carbon::make($this->special_from);
            }
            if ($this->special_to && $this->special_to != '0000-00-00 00:00:00') {
                $to = Carbon::make($this->special_to);
            }

            if ($from <= now() && now() <= $to) {
                return $this->special;
            }
        }

        return false;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function author()
    {
        return $this->hasOne(Author::class, 'id', 'author_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function publisher()
    {
        return $this->hasOne(Publisher::class, 'id', 'publisher_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function categories()
    {
        return $this->hasManyThrough(Category::class, CategoryProducts::class, 'product_id', 'id', 'id', 'category_id');
    }


    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function category()
    {
        return $this->hasOneThrough(Category::class, CategoryProducts::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', 0)
            ->first();
    }


    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOneThrough|\Illuminate\Database\Query\Builder|mixed|object|null
     */
    public function subcategory()
    {
        return $this->hasOneThrough(Category::class, CategoryProducts::class, 'product_id', 'id', 'id', 'category_id')
            ->where('parent_id', '!=', 0)
            ->first();
    }


    /**
     * @return string
     */
    public function priceString(string $price = null)
    {
        if ($price) {
            $set = explode('.', $price);

            if ( ! isset($set[1])) {
                $set[1] = '00';
            }

            return number_format($price, 0, '', '.') . ',<small>' . substr($set[1], 0, 2) . 'kn</small>';
        }

        $set = explode('.', $this->price);

        return number_format($this->price, 0, '', '.') . ',<small>' . substr($set[1], 0, 2) . 'kn</small>';
    }


    /**
     * @param int $id
     *
     * @return mixed
     */
    public function tax(int $id)
    {
        return Settings::get('tax', 'list')->where('id', $id)->first();
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeOnAction($query)
    {
        $actions = ProductAction::active()->pluck('product_id');

        if ($actions->count() < 8) {
            $count = 8 - $actions->count();

            for ($i = 0; $i < $count; $i++) {
                $product = Product::whereNotIn('id', $actions)->inRandomOrder()->limit(1)->pluck('id');
                $actions->push($product[0]);
            }
        }

        return $query->whereIn('id', $actions)->with('action');
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
     * @param $query
     *
     * @return mixed
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeHasStock($query)
    {
        return $query->where('quantity', '!=', 0);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeLast($query, $count = 9)
    {
        return $query->where('status', 1)->orderBy('updated_at', 'desc')->limit($count);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeCreated($query, $count = 9)
    {
        return $query->where('status', 1)->orderBy('created_at', 'desc')->limit($count);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity', '!=', 0);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePopular($query, $count = 9)
    {
        return $query->where('status', 1)->orderBy('viewed', 'desc')->limit($count);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeTopPonuda($query, $count = 9)
    {
        return $query->where('status', 1)->where('topponuda', 1)->orderBy('updated_at', 'desc')->limit($count);
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param Request         $request
     * @param Collection|null $ids
     *
     * @return Builder
     */
    public function filter(Request $request, Collection $ids = null): Builder
    {
        $query = (new Product())->newQuery();

        if ($ids->count() && ! \request()->has('pojam')) {
            $query->whereIn('id', $ids->unique());
        }

        if ($ids && \request()->has('pojam')) {
            $query->whereIn('id', $ids->unique());
        }

        if ($request->has('group')) {
            // Akcije
            if ($request->input('group') == 'snizenja') {
                $query->where('special', '!=', '')
                      ->where(function ($query) {
                          $query->whereDate('special_from', '<=', now())->orWhereNull('special_from');
                      })
                      ->where(function ($query) {
                          $query->whereDate('special_to', '>=', now())->orWhereNull('special_to');
                      });
            } else {
                // Kategorija...
                $group = $request->input('group');

                if ($group == 'zemljovidi-i-vedute') {
                    $group = 'Zemljovidi i vedute';
                }

                $query->whereHas('categories', function ($query) use ($request, $group) {
                    $query->where('group', 'like', '%' . $group . '%');
                });
            }
        }

        if ($request->has('cat')) {
            $query->whereHas('categories', function ($query) use ($request) {
                $query->where('category_id', $request->input('cat')['id']);
            });
        }

        if ($request->has('subcat')) {
            $query->whereHas('categories', function ($query) use ($request) {
                $query->where('category_id', $request->input('subcat')['id']);
            });
        }

        if ($request->has('autor')) {
            $auts = [];

            foreach ($request->input('autor') as $key => $item) {
                if (isset($item->id)) {
                    array_push($auts, $item->id);
                } else {
                    array_push($auts, $key);
                }
            }

            $query->whereIn('author_id', $auts);
        }

        if ($request->has('nakladnik')) {
            $pubs = [];

            foreach ($request->input('nakladnik') as $key => $item) {
                if (isset($item->id)) {
                    array_push($pubs, $item->id);
                } else {
                    array_push($pubs, $key);
                }
            }

            $query->whereIn('publisher_id', $pubs);
        }

        if ($request->has('start')) {
            $query->where(function ($query) use ($request) {
                $query->where('year', '>=', $request->input('start'))->orWhereNull('year');
            });
        }

        if ($request->has('end')) {
            $query->where(function ($query) use ($request) {
                $query->where('year', '<=', $request->input('end'))->orWhereNull('year');
            });
        }

        $query->active()->where('quantity', '!=', 0);

        if ($request->has('sort')) {
            $sort = $request->input('sort');

            if ($sort == 'novi') {
                $query->orderBy('created_at', 'desc');
            }

            if ($sort == 'price_up') {
                $query->orderBy('price');
            }

            if ($sort == 'price_down') {
                $query->orderBy('price', 'desc');
            }

            if ($sort == 'naziv_up') {
                $query->orderBy('name');
            }

            if ($sort == 'naziv_down') {
                $query->orderBy('name', 'desc');
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return $query;
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // Static functions

    /**
     * @return mixed
     */
    public static function getMenu()
    {
        return self::where('status', 1)->select('id', 'name')->get();
    }


    /**
     * Return the list usually for
     * select or autocomplete html element.
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function list()
    {
        $query = (new self())->newQuery();

        return $query->where('status', 1)->select('id', 'name')->get();
    }

}
