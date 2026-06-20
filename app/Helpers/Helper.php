<?php


namespace App\Helpers;

use App\Models\Back\Settings\Settings;
use App\Models\Back\Widget\WidgetGroup;
use App\Models\Front\Blog;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Back\Marketing\Action;
use App\Models\Front\Catalog\Publisher;
use App\Models\Front\Loyalty;
use Darryldecode\Cart\CartCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\False_;

class Helper
{

    /**
     * @param float $price
     * @param int   $discount
     *
     * @return float|int
     */
    public static function calculateDiscountPrice(float $price, int $discount)
    {
        return $price - ($price * ($discount / 100));
    }


    /**
     * @param $list_price
     * @param $seling_price
     *
     * @return float|int
     */
    public static function calculateDiscount($list_price, $seling_price)
    {
        if (is_string($list_price)) {
            $list_price = str_replace('.', '', $list_price);
            $list_price = str_replace(',', '.', $list_price);
        }
        if (is_string($seling_price)) {
            $seling_price = str_replace('.', '', $seling_price);
            $seling_price = str_replace(',', '.', $seling_price);
        }

        return (($list_price - $seling_price) / $list_price) * 100;;
    }


    /**
     * @return string[]
     */
    public static function abc()
    {
        return ['A', 'B', 'C', 'Ć', 'Č', 'D', 'Đ', 'Dž', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'Lj', 'M', 'N', 'Nj', 'O', 'P', 'R', 'S', 'Š', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ž'];
    }


    /**
     * @param string $price
     *
     * @return string
     */
    public static function priceString($price): string
    {
        if (is_float($price)) {
            $price = '"' . number_format($price, 2) . '"';
        }

        if ( ! is_string($price)) {
            return 'Not a number.!';
        }

        $set = explode('.', $price);

        if ( ! isset($set[1])) {
            $set[1] = '00';
        }

        return number_format($price, 0, '', '.') . ',<small>' . substr($set[1], 0, 2) . 'kn</small>';
    }

    /**
     * Filter products by JSON tags column.
     *
     * @param string|array $tags      One tag ("avantura") ili niz tagova (['avantura','pustolovina'])
     * @param bool         $builder   Ako true -> vrati Collection ('products','total'); inače JSON niz ID-eva
     * @param bool         $api       Ako true -> ograniči rezultate na 15, ali zadrži total
     * @param string       $operator  'and' (svi tagovi) ili 'or' (bilo koji tag)
     *
     * @return \Illuminate\Support\Collection|string|false
     */
    public static function getTags($tags, bool $builder = false, bool $api = false, string $operator = 'and')
    {
        // Normalizacija ulaza
        if (is_string($tags)) {
            $tags = trim($tags);
            if ($tags === '') {
                return false;
            }
            // dozvoli "tag1, tag2" kao string
            $tags = str_contains($tags, ',')
                ? array_values(array_filter(array_map('trim', explode(',', $tags))))
                : [$tags];
        } elseif (is_array($tags)) {
            $tags = array_values(array_filter(array_map('trim', $tags)));
            if (empty($tags)) {
                return false;
            }
        } else {
            return false;
        }

        // Query
        $query = Product::query()->active();

        // AND: svaki tag mora postojati u JSON polju
        if (strtolower($operator) === 'and') {
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }
        // OR: barem jedan od navedenih tagova
        else {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $i => $tag) {
                    $i === 0
                        ? $q->whereJsonContains('tags', $tag)
                        : $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Uzmemo samo ID-eve
        $ids = $query->pluck('id');
        $uniqueIds = $ids->unique()->values();
        $totalAll  = $uniqueIds->count();

        $limitedIds = $api ? $uniqueIds->take(15) : $uniqueIds;

        $response = collect([
            'products' => $limitedIds->flatten(),
            'total'    => $totalAll,
        ]);

        \Log::info($response);

        if ($builder) {
            return $response;
        }

        // Back-compat: vrati samo JSON niz ID-eva
        return $response['products']->toJson();
    }


    /**
     * @param string $target
     * @param bool   $builder
     *
     * @return array|false|Collection
     */
    public static function search(string $target = '', bool $builder = false, bool $api = false)
    {
        if ($target === '') {
            return false;
        }

        $response = collect();

        // proizvodi po nazivu/sku/opisu
        $products = Product::query()
            ->active()
            ->where(function ($q) use ($target) {
                $q->where('name', 'like', "%{$target}%")
                    ->orWhere('sku', 'like', "%{$target}%");

                if (LocaleHelper::isEnglish()) {
                    $q->orWhere('name_en', 'like', "%{$target}%")
                        ->orWhere('description_en', 'like', "%{$target}%");
                }
            })
            ->pluck('id');

        if (! $products->count()) {
            $products = collect();
        }

        // autori -> merge njihovih proizvoda
        $preg = explode(' ', $target, 3);

        if (isset($preg[1]) && in_array($preg[1], $preg) && !isset($preg[2])) {
            $authors = Author::active()
                ->where(function ($query) use ($preg) {
                    $query->where('title', 'like', '%' . $preg[0] . '%' . $preg[1] . '%')
                        ->orWhere('title', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%');

                    if (LocaleHelper::isEnglish()) {
                        $query->orWhere('title_en', 'like', '%' . $preg[0] . '%' . $preg[1] . '%')
                            ->orWhere('title_en', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%');
                    }
                })
                ->with('products')->get();
        } elseif (isset($preg[2]) && in_array($preg[2], $preg)) {
            $authors = Author::active()
                ->where(function ($query) use ($preg) {
                    $query->where('title', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%')
                        ->orWhere('title', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%')
                        ->orWhere('title', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%')
                        ->orWhere('title', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%')
                        ->orWhere('title', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%');

                    if (LocaleHelper::isEnglish()) {
                        $query->orWhere('title_en', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%')
                            ->orWhere('title_en', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%')
                            ->orWhere('title_en', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%')
                            ->orWhere('title_en', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%')
                            ->orWhere('title_en', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%');
                    }
                })
                ->with('products')->get();
        } else {
            $authors = Author::active()
                ->where(function ($query) use ($preg) {
                    $query->where('title', 'like', '%' . $preg[0] . '%');

                    if (LocaleHelper::isEnglish()) {
                        $query->orWhere('title_en', 'like', '%' . $preg[0] . '%');
                    }
                })
                ->with('products')->get();
        }

        foreach ($authors as $author) {
            $products = $products->merge($author->products->pluck('id'));
        }

        // jedinstveni popis i ukupno
        $uniqueIds = $products->unique()->values();
        $totalAll  = $uniqueIds->count();

        // ako je API – ograniči na 15, ali zadrži totalAll
        $limitedIds = $api ? $uniqueIds->take(15) : $uniqueIds;

        $response->put('products', $limitedIds->flatten());
        $response->put('total', $totalAll);

        Log::info($response);

        if ($builder) {
            return $response;
        }

        // Back-compat: kad se ne traži builder, vrati samo niz ID-eva kao JSON
        return $response['products']->toJson();
    }



    /**
     * @param Builder $query
     * @param string  $search
     *
     * @return Builder
     */
    public static function searchByTitle(Builder $query, string $search): Builder
    {
        $preg = explode(' ', $search, 3);

        if (isset ($preg[1]) && in_array($preg[1], $preg) && ! isset($preg[2])) {
            $query->where('title', 'like', '%' . $preg[0] . '%' . $preg[1] . '%')
                  ->orWhere('title', 'like', '%' . $preg[1] . '% ' . $preg[0] . '%');

        } elseif (isset ($preg[2]) && in_array($preg[2], $preg)) {
            $query->where('title', 'like', $preg[0] . '%' . $preg[1] . '%' . $preg[2] . '%')
                  ->orWhere('title', 'like', $preg[2] . '%' . $preg[1] . '% ' . $preg[0] . '%')
                  ->orWhere('title', 'like', $preg[0] . '%' . $preg[2] . '% ' . $preg[1] . '%')
                  ->orWhere('title', 'like', $preg[1] . '%' . $preg[0] . '% ' . $preg[2] . '%')
                  ->orWhere('title', 'like', $preg[1] . '%' . $preg[2] . '% ' . $preg[0] . '%');

        } else {
            $query->where('title', 'like', '%' . $preg[0] . '%');
        }

        return $query;
    }


    /**
     * @param string $description
     *
     * @return false|string
     */
    public static function setDescription(string $description)
    {
        $iterator = substr_count($description, '++');
        $offset = 0;
        $ids = [];

        for ($i = 0; $i < $iterator / 2; $i++) {
            $from = strpos($description, '++', $offset) + 2;
            $to = strpos($description, '++', $from + 2);
            $ids[] = substr($description, $from, $to - $from);

            $offset = $to + 2;
        }

        $wgs = WidgetGroup::where(function ($query) use ($ids) {
            $query->whereIn('id', $ids)
                ->orWhereIn('slug', $ids)
                ->orWhereIn('slug_en', $ids);
        })->where('status', 1)->with('widgets')->get();

        foreach ($ids as $id) {
            $description = static::resolveDescription($wgs, $description, $id);
        }

        return substr($description, 3, -4);
    }


    /**
     * @param Collection $wgs
     * @param string     $description
     * @param string     $id
     *
     * @return string
     */
    private static function resolveDescription(Collection $wgs, string $description, string $id): string
    {
        $wg = $wgs->where('id', $id)->first();

        if ( ! $wg) {
            $wg = $wgs->where('slug', $id)->first();
        }

        if ( ! $wg) {
            return str_replace('++' . $id . '++', '', $description);
        }

        $widgets = [];
        $loadedWidgets = $wg->relationLoaded('widgets')
            ? $wg->widgets->sortBy('sort_order')
            : $wg->widgets()->orderBy('sort_order')->get();

        if ($wg->template == 'product_carousel' || $wg->template == 'page_carousel' ) {
            $widget = $loadedWidgets->first();
            $data = $widget ? static::decodeWidgetData($widget->data) : [];
            $items = collect();
            $tablename = $data['target'] ?? ($data['group'] ?? '');

            if (static::isDescriptionTarget($data, 'product')) {
                $items     = static::products($data)->get();
                $tablename = 'product';
            }

            if (static::isDescriptionTarget($data, 'blog')) {
                $items     = static::blogs($data)->get();
                $tablename = 'blog';


            }

            if (static::isDescriptionTarget($data, 'category')) {
                $items     = static::category($data)->get();


                $tablename = 'category';
            }

            if (static::isDescriptionTarget($data, 'product_category')) {
                $items     = static::product_category($data)->get();


                $tablename = 'product_category';
            }


            if (static::isDescriptionTarget($data, 'author')) {
                $items     = static::author($data)->get();
                $tablename = 'author';
            }

            if (static::isDescriptionTarget($data, 'reviews')) {
                $items     = static::dummyReviews();
                $tablename = 'reviews';
            }

            $widgets = [
                'title'      => $widget ? LocaleHelper::localizedField($widget, 'title') : '',
                'subtitle'   => $widget ? LocaleHelper::localizedField($widget, 'subtitle') : '',
                'url'        => $widget ? (LocaleHelper::isEnglish() ? ($widget->url_en ?: LocaleHelper::localizedUrl($widget->url, LocaleHelper::ENGLISH_LOCALE)) : $widget->url) : '/',
                'tablename'  => $tablename,
                'css'        => $data['css'] ?? null,
                'container'  => (isset($data['container']) && $data['container'] == 'on') ? 1 : null,
                'background' => (isset($data['background']) && $data['background'] == 'on') ? 1 : null,
                'items'      => $items
            ];

        } else {
            foreach ($loadedWidgets as $widget) {
                $data = static::decodeWidgetData($widget->data);



                $widgets[] = [
                    'title'    => LocaleHelper::localizedField($widget, 'title'),
                    'subtitle' => LocaleHelper::localizedField($widget, 'subtitle'),
                    'color'    => LocaleHelper::localizedField($widget, 'badge'),
                    'url'      => LocaleHelper::isEnglish() ? ($widget->url_en ?: LocaleHelper::localizedUrl($widget->url, LocaleHelper::ENGLISH_LOCALE)) : $widget->url,
                    'image'    => $widget->thumb,
                    'width'    => $widget->width,
                    'right'    => (isset($data['right']) && $data['right'] == 'on') ? 1 : null,
                ];
            }
        }



        return str_replace(
            '++' . $id . '++',
            view('front.layouts.widget.widget_' . $wg->template, ['data' => $widgets]),
            $description
        );
    }


    /**
     * @param string|null $data
     *
     * @return array
     */
    private static function decodeWidgetData(?string $data): array
    {
        if ( ! $data) {
            return [];
        }

        $decoded = @unserialize($data, ['allowed_classes' => false]);

        return is_array($decoded) ? $decoded : [];
    }


    /**
     * @param array  $data
     * @param string $target
     *
     * @return bool
     */
    public static function isDescriptionTarget(array $data, string $target): bool
    {
        if (isset($data['target']) && $data['target'] == $target) { return true; }
        if (isset($data['group']) && $data['group'] == $target) { return true; }

        return false;
    }


    /**
     * @param string $text
     *
     * @return string
     */
    public static function resolveFirstLetter(string $text): string
    {
        $letter = substr($text, 0, 1);

        if (in_array(substr($text, 0, 2), ['Nj', 'Lj', 'Š', 'Č', 'Ć', 'Ž', 'Đ'])) {
            $letter = substr($text, 0, 2);
        }

        if (in_array(substr($text, 0, 3), ['Dž', 'Đ'])) {
            $letter = substr($text, 0, 3);
        }

        return $letter;
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function products(array $data): Builder
    {
        $prods = (new Product())->newQuery();

        $prods->active()->available();

        if (isset($data['new']) && $data['new'] == 'on') {
            $prods->orderBy('created_at', 'desc')->orderBy('id', 'desc')->limit(12);
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $prods->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $prods->whereIn('id', $data['list']);
        }

        return $prods->with(['author', 'action']);
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function blogs(array $data): Builder
    {
        $blogs = (new Blog())->newQuery();

        $blogs->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $blogs->last();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $blogs->popular();
        }

        if (isset($data['list']) && $data['list']) {
            $blogs->whereIn('id', $data['list']);
        }

        return $blogs;
    }

    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function category(array $data): Builder
    {
        $category = (new \App\Models\Back\Catalog\Category())->newQuery();

        $category->active();

        if (isset($data['new']) && $data['new'] == 'on') {
            $category->latest();
        }

        if (isset($data['popular']) && $data['popular'] == 'on') {
            $category->latest();
        }

        if (isset($data['list']) && $data['list']) {
            $category->whereIn('id', $data['list']);
        }

        return $category;
    }

    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function author(array $data): Builder
    {
        $author = (new Author())->newQuery();

        $author->active();

        if (isset($data['list']) && $data['list']) {
            $author->whereIn('id', $data['list']);
        }

        return $author->orderBy('title');
    }


    /**
     * @param array $data
     *
     * @return Builder
     */
    private static function categories(array $data): Builder
    {
        $categories = (new Category())->newQuery();

        $categories->active();

        if (isset($data['list']) && $data['list']) {
            $categories->whereIn('id', $data['list']);
        }

        return $categories;
    }

    private static function product_category(array $data): Builder
    {
        $product = (new Product())->newQuery();

        $product->where('status', 1);

        // Filtriraj po kategorijama
        if (!empty($data['list'])) {
            $product->whereHas('categories', function (Builder $query) use ($data) {
                $query->whereIn('categories.id', $data['list']);
            });
        }

        // Novi proizvodi
        if (!empty($data['new']) && $data['new'] === 'on') {
            $product->orderBy('created_at', 'desc');
        }

        // Popularni proizvodi – ovo pretpostavlja da postoji kolona `views` ili slično
        if (!empty($data['popular']) && $data['popular'] === 'on') {
            $product->orderBy('viewed', 'desc'); // prilagodi prema tvojoj logici popularnosti
        }
        return $product->with(['author', 'action'])->limit(15);
    }



    /**
     * @param string $tag
     *
     * @return \Illuminate\Cache\TaggedCache|mixed|object
     */
    public static function resolveCache(string $tag): ?object
    {
        if (app()->environment('local')) {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag]);
    }


    /**
     * @param string $tag
     * @param string $key
     *
     * @return object|bool|mixed|null
     */
    public static function flushCache(string $tag, string $key)
    {
        if (app()->environment('local')) {
            return Cache::getFacadeRoot();
        }

        return Cache::tags([$tag])->forget($key);
    }


    /**
     * @return null
     */
    public static function getEur()
    {
        $eur = Settings::get('currency', 'list')->where('code', 'EUR')->first();

        if (isset($eur->status) && $eur->status) {
            return $eur->value;
        }

        return null;
    }


    /**
     * @param bool $slug
     *
     * @return string
     */
    public static function categoryGroupPath(bool $slug = false): string
    {
        if ($slug) {
            return Str::slug(config('settings.group_path'));
        }

        return config('settings.group_path');
    }

    /**
     * @param array  $data
     * @param string $tag
     * @param        $target
     *
     * @return string
     */
    public static function resolveSlug(array $data, string $tag = 'title', $target = null): string
    {
        $slug = null;

        if ($target) {
            $product = Product::where('id', $target)->first();

            if ($product) {
                $slug = $product->slug;
            }
        }

        $slug  = $slug ?: Str::slug($data[$tag]);
        $exist = Product::where('slug', $slug)->count();

        $cat_exist = Category::where('slug', $slug)->count();

        if (($cat_exist || $exist > 1) && $target) {
            return $slug . '-' . time();
        }

        if (($cat_exist || $exist) && ! $target) {
            return $slug . '-' . time();
        }

        return $slug;
    }

    /**
     * @param $cart
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasSpecialCartCondition($cart = null)
    {
        $condition     = false;
        $has_condition = false;

        if ($cart->getTotal() > 50) {
            $has_condition = 10;
        }
        if ($cart->getTotal() > 100) {
            $has_condition = 15;
        }
        if ($cart->getTotal() > 200) {
            $has_condition = 20;
        }

        if ($has_condition && self::isDateBetween()) {
            $value    = self::calculateDiscountPrice($cart->getTotal(), $has_condition, 'P');
            $discount = $cart->getTotal() - $value;

            $condition = new CartCondition(array(
                'name'       => config('settings.special_action.title'),
                'type'       => 'special',
                'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value'      => '-' . $discount,
                'attributes' => [
                    'description' => '',
                    'geo_zone'    => ''
                ]
            ));
        }

        return $condition;
    }


    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasCouponCartConditions($cart, string $coupon = '')
    {
        $condition = false;
        $actions   = Action::query()->where('group', 'total')->get();

        if ($actions->count()) {
            foreach ($actions as $action) {
                if ($action->isValid($coupon)) {
                    $value    = self::calculateDiscountPrice($cart->getTotal(), $action->discount, $action->type);
                    $discount = $cart->getTotal() - $value;

                    $condition = new CartCondition(array(
                        'name'       => $action->title,
                        'type'       => 'special',
                        'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                        'value'      => '-' . $discount,
                        'attributes' => $action->setConditionAttributes($coupon)
                    ));
                }
            }
        }

        return $condition;
    }

    /**
     * @param        $cart
     * @param string $coupon
     *
     * @return CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function hasLoyaltyCartConditions($cart, int $loyalty = 0)
    {
        $condition = false;
        $has_loyalty   = Loyalty::hasLoyalty();

        if ($has_loyalty) {
            $discount = Loyalty::calculateLoyalty($loyalty);

            if ($cart->getTotal() > $discount) {
                $condition = new CartCondition(array(
                    'name'       => 'Loyalty',
                    'type'       => 'special',
                    'target'     => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                    'value'      => '-' . $discount,
                    'attributes' => [
                        'type'        => 'loyalty',
                        'description' => 'Loyalty Program'
                    ]
                ));
            }
        }

        return $condition;
    }


    /**
     * @param $cart
     *
     * @return false|mixed
     */
    public static function isCouponUsed($cart)
    {
        $coupon = false;
        $items = $cart->getContent();

        foreach ($items as $item) {
            if ($item->getConditions()->getType() == 'coupon') {
                $coupon = $item->getConditions()->getTarget();
            }
        }

        foreach ($cart->getConditions() as $condition) {
            if (isset($condition->getAttributes()['type']) && $condition->getAttributes()['type'] == 'coupon' && floatval($condition->getValue()) < 0) {
                $coupon = $condition->getAttributes()['description'];
            }
        }



        return $coupon;
    }


    /**
     * @param $date
     *
     * @return bool
     */
    public static function isDateBetween($date = null): bool
    {
        if (config('settings.special_action.start')) {
            $now   = $date ?: Carbon::now();
            $start = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.start'));
            $end   = Carbon::createFromFormat('d/m/Y H:i:s', config('settings.special_action.end'));

            if ($now->isBetween($start, $end)) {
                return true;
            }
        }

        return false;
    }

    private static function dummyReviews()
    {
        return collect([
            (object)[
                'id' => 1,
                'product_id' => 101,
                'order_id' => 1001,
                'user_id' => 501,
                'lang' => 'hr',
                'fname' => 'Dino ',
                'lname' => 'Jevšnik Nedeljković',
                'email' => '',
                'avatar' => 'avatars/ana.jpg',
                'message' => 'Ugodna atmosfera začinjena mirisom starih knjiga i svježinom novih, dok je gospodin koji tamo radi, a ujedno je i vlasnik antikvarijata, srdačan, veseo i susretljiv.☺️',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-01 10:15:00',
                'updated_at' => '2025-08-01 10:15:00',
            ],
            (object)[
                'id' => 2,
                'product_id' => 102,
                'order_id' => 1002,
                'user_id' => 502,
                'lang' => 'hr',
                'fname' => 'Lucija ',
                'lname' => 'Ivančić',
                'email' => 'ivan.maric@example.com',
                'avatar' => 'avatars/ivan.png',
                'message' => 'Najdraži antikvarijat, uvijek pronađem nešto zanimljivo. Pozdrav najuslužnijoj ekipi, vidimo se uskoro! P.S. novi prostor je odličan!🍀💛',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 0,
                'status' => 1,
                'created_at' => '2025-08-02 11:00:00',
                'updated_at' => '2025-08-02 11:00:00',
            ],
            (object)[
                'id' => 3,
                'product_id' => 103,
                'order_id' => 1003,
                'user_id' => 503,
                'lang' => 'hr',
                'fname' => 'Mican ',
                'lname' => 'Torban',
                'email' => 'maja.kovac@example.com',
                'avatar' => 'avatars/maja.jpg',
                'message' => 'Super uređena stranica, lako se može pronaći ono što nekoga zanima.
Kupio sam knjigu iz Biblosa, vrlo sam zadovoljan uslugom, isporukom i ljubaznim djelatnicima.
Sve pohvale. 🙂👍',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-03 09:45:00',
                'updated_at' => '2025-08-03 09:45:00',
            ],
            (object)[
                'id' => 4,
                'product_id' => 104,
                'order_id' => 1004,
                'user_id' => 504,
                'lang' => 'hr',
                'fname' => 'Milivoj',
                'lname' => 'Vodopij',
                'email' => 'petar.novak@example.com',
                'avatar' => 'avatars/petar.png',
                'message' => 'Najbolji izbor rabljenih knjiga u Zagrebu, a posebno starih i rijetkih izdanja, te starih zemljovida našeg područja. Može se dobiti procjena i ekspertiza za hrvatski, kao i srpski antikvarni materijal. 👍.',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 0,
                'status' => 1,
                'created_at' => '2025-08-04 13:20:00',
                'updated_at' => '2025-08-04 13:20:00',
            ],
            (object)[
                'id' => 5,
                'product_id' => 105,
                'order_id' => 1005,
                'user_id' => 505,
                'lang' => 'hr',
                'fname' => 'Aleksandar ',
                'lname' => 'Funduk',
                'email' => 'lucija.peric@example.com',
                'avatar' => 'avatars/lucija.jpeg',
                'message' => 'Vrlo ugodno iskustvo. Naručio knjigu jučer, danas je stigla. Znači u manje od 24 sata. U moru on line trgovina ima još uvijek odgovornih ljudi koji ozbiljno rade svoj posao.',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-05 08:10:00',
                'updated_at' => '2025-08-05 08:10:00',
            ],
            (object)[
                'id' => 5,
                'product_id' => 105,
                'order_id' => 1005,
                'user_id' => 505,
                'lang' => 'hr',
                'fname' => 'Josip ',
                'lname' => 'Ukalovic',
                'email' => 'lucija.peric@example.com',
                'avatar' => 'avatars/lucija.jpeg',
                'message' => 'Odličan antikvarijat s jako dobrim izborom knjiga. Osoblje je susretljivo i spremno pomoći, vidi se da vole to što rade. Sve preporuke!',
                'stars' => 5.00,
                'sort_order' => 0,
                'featured' => 1,
                'status' => 1,
                'created_at' => '2025-08-05 08:10:00',
                'updated_at' => '2025-08-05 08:10:00',
            ],


        ]);
    }


    /**
     * @param string $text
     *
     * @return string
     */
    public static function resolveYouTubeFrame(string $text): string
    {
       // preg_match_all('/(\bhttps?:)?\/\/[^,\s()<>]+(?:\(\w+\)|(?:[^,[:punct:]\s]|\/))/s', $text, $matches);

        preg_match_all('/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})\W/',  $text, $matches);


        $text = preg_replace('/<oembed.*?<\/oembed>/i','', $text);
        $has = [$text, $matches[0]];

            if(isset($has[1][0])){
                $text = str_replace('<figure class="media"></figure>', '<iframe width="100%" height="450" src="https://' . $has[1][0] . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', $text);
            }






        return $text;
    }


    /**
     * @param string|null $path
     *
     * @return string|null
     */
    public static function resolveOptimizedPublicImagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = ltrim($path, '/');
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path) ?: $path;

        if ($webpPath !== $path && file_exists(public_path($webpPath))) {
            return $webpPath;
        }

        return $path;
    }


    /**
     * @param string $html
     *
     * @return string
     */
    public static function optimizeRichContentMedia(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $html = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) {
            $tag = $matches[0];
            $src = static::getTagAttribute($tag, 'src');

            if ($src) {
                $optimizedSrc = static::resolveRichContentImageUrl($src);

                if ($optimizedSrc !== $src) {
                    $tag = static::setTagAttribute($tag, 'src', $optimizedSrc, true);
                }
            }

            $tag = static::setTagAttribute($tag, 'loading', 'lazy');
            $tag = static::setTagAttribute($tag, 'decoding', 'async');
            $tag = static::setTagAttribute($tag, 'fetchpriority', 'low');

            return static::appendTagClass($tag, 'img-fluid');
        }, $html);

        return preg_replace_callback('/<iframe\b[^>]*>/i', function (array $matches) {
            $tag = $matches[0];
            $tag = static::setTagAttribute($tag, 'loading', 'lazy');

            return static::setTagAttribute($tag, 'referrerpolicy', 'strict-origin-when-cross-origin');
        }, $html);
    }


    /**
     * @param string $tag
     * @param string $attribute
     *
     * @return string|null
     */
    private static function getTagAttribute(string $tag, string $attribute): ?string
    {
        $pattern = '/\b' . preg_quote($attribute, '/') . '\s*=\s*(["\'])(.*?)\1/i';

        if (preg_match($pattern, $tag, $matches)) {
            return html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
        }

        return null;
    }


    /**
     * @param string $tag
     * @param string $attribute
     * @param string $value
     * @param bool   $replace
     *
     * @return string
     */
    private static function setTagAttribute(string $tag, string $attribute, string $value, bool $replace = false): string
    {
        $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $pattern = '/\s' . preg_quote($attribute, '/') . '\s*=\s*(?:"[^"]*"|\'[^\']*\')/i';

        if (preg_match($pattern, $tag)) {
            if (! $replace) {
                return $tag;
            }

            return preg_replace($pattern, ' ' . $attribute . '="' . $escapedValue . '"', $tag, 1) ?: $tag;
        }

        return preg_replace('/\s*(\/?>)$/', ' ' . $attribute . '="' . $escapedValue . '"$1', $tag, 1) ?: $tag;
    }


    /**
     * @param string $tag
     * @param string $className
     *
     * @return string
     */
    private static function appendTagClass(string $tag, string $className): string
    {
        $class = static::getTagAttribute($tag, 'class');

        if (! $class) {
            return static::setTagAttribute($tag, 'class', $className);
        }

        $classes = preg_split('/\s+/', trim($class)) ?: [];

        if (in_array($className, $classes, true)) {
            return $tag;
        }

        $classes[] = $className;

        return static::setTagAttribute($tag, 'class', implode(' ', array_filter($classes)), true);
    }


    /**
     * @param string $src
     *
     * @return string
     */
    private static function resolveRichContentImageUrl(string $src): string
    {
        $source = trim($src);

        if ($source === '' || str_starts_with($source, 'data:') || str_contains($source, '/cache/thumb')) {
            return $src;
        }

        $path = static::normalizeLocalPublicPath($source);

        if (! $path || ! Str::startsWith($path, 'media/img/blog/')) {
            return $src;
        }

        $optimizedPath = static::resolveOptimizedPublicImagePath($path);

        if (! $optimizedPath) {
            return $src;
        }

        return url('cache/thumb?size=1200x1200&src=' . urlencode($optimizedPath));
    }


    /**
     * @param string $src
     *
     * @return string|null
     */
    private static function normalizeLocalPublicPath(string $src): ?string
    {
        $imagesDomain = rtrim((string) config('settings.images_domain'), '/');
        $appUrl = rtrim((string) config('app.url'), '/');

        foreach (array_filter([$imagesDomain, $appUrl]) as $prefix) {
            if (str_starts_with($src, $prefix . '/')) {
                return ltrim(substr($src, strlen($prefix)), '/');
            }
        }

        if (preg_match('/^https?:\/\//i', $src)) {
            return null;
        }

        return ltrim($src, '/');
    }



}
