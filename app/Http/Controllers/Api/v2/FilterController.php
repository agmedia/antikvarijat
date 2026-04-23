<?php

namespace App\Http\Controllers\Api\v2;

use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FilterController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request)
    {
        if ( ! $request->input('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $response = [];
        $params = $request->input('params');

        $author = $params['author'] ? Author::where('slug', $params['author'])->first() : null;
        $publisher = $params['publisher'] ? Publisher::where('slug', $params['publisher'])->first() : null;

        if ( ! $params['cat'] && ! $params['subcat']) {
            // Ako je normal kategorija
            if ($params['group']) {
                $categories = Helper::resolveCache('categories')->remember($params['group'], config('cache.life'), function () use ($params) {
                    return Category::active()
                        ->topList($params['group'])
                        ->sortByName()
                        ->withCount(['products as products_count' => function ($query) {
                            $query->where('status', 1)
                                ->where('quantity', '>', 0);
                        }])
                        ->get()
                        ->toArray();
                });

                $response = $this->resolveCategoryArray($categories, 'categories');
            }

            // Ako je autor
            if ( ! $params['group'] && $params['author']) {
                $a_cats = $author->categories();
                $response = $this->resolveCategoryArray($a_cats, 'author', $author);
            }

            // Ako je nakladnik
            if ( ! $params['group'] && $params['publisher']) {
                $a_cats = $publisher->categories();
                $response = $this->resolveCategoryArray($a_cats, 'publisher', $publisher);
            }
        }
        //
        if ($params['cat'] && ! $params['subcat']) {
            $cat = Category::where('id', $params['cat'])->first();

            if ($params['group']) {
                $item = Helper::resolveCache('categories')->remember($cat['id'], config('cache.life'), function () use ($cat) {
                    return Category::active()
                        ->where('parent_id', $cat['id'])
                        ->sortByName()
                        ->withCount(['products as products_count' => function ($query) {
                            $query->where('status', 1)
                                ->where('quantity', '>', 0);
                        }])
                        ->get()
                        ->toArray();
                });

                $response = $this->resolveCategoryArray($item, 'categories', null, $cat['slug']);
            }

            // Ako je autor
            if ( ! $params['group'] && $params['author']) {
                $a_cats = (new Author())->categories($cat['id']);
                $response = $this->resolveCategoryArray($a_cats, 'author', $author, $cat['slug']);
            }

            // Ako je nakladnik
            if ( ! $params['group'] && $params['publisher']) {
                $a_cats = (new Publisher())->categories($cat['id']);
                $response = $this->resolveCategoryArray($a_cats, 'publisher', $publisher, $cat['slug']);
            }
        }

        if ($params['ids'] && $params['ids'] != '[]') {
            $_ids = collect(explode(',', substr($params['ids'], 1, -1)))->unique();

            $categories = Category::active()
                ->whereHas('products', function ($query) use ($_ids) {
                    $query->active()->hasStock()->whereIn('id', $_ids);
                })
                ->sortByName()
                ->withCount([
                    // filtrirani count (status = 1 i quantity > 0 + whereIn)
                    'products as products_count' => function ($query) use ($_ids) {
                        $query->active()->hasStock()->whereIn('id', $_ids);
                    }
                ])
                ->get()
                ->toArray();

            $response = $this->resolveCategoryArray($categories, 'categories');
        }

        return response()->json($response);
    }


    /**
     * @param             $categories
     * @param string      $type
     * @param null        $target
     * @param string|null $parent_slug
     *
     * @return array
     */
    private function resolveCategoryArray($categories, string $type, $target = null, ?string $parent_slug = null): array
    {
        $response = [];

        foreach ($categories as $category) {
            $url = $this->resolveCategoryUrl($category, $type, $target, $parent_slug);

            $response[] = [
                'id' => $category['id'],
                'title' => $category['title'],
                'count' => $category['products_count'],
                'url' => $url
            ];
        }

        return $response;
    }


    /**
     * @param             $category
     * @param string      $type
     * @param             $target
     * @param string|null $parent_slug
     *
     * @return string
     */
    private function resolveCategoryUrl($category, string $type, $target, ?string $parent_slug = null): string
    {
        if ($type == 'author') {
            return route('catalog.route.author', [
                'author' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } elseif ($type == 'publisher') {
            return route('catalog.route.publisher', [
                'publisher' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } else {
            return route('catalog.route', [
                'group' => Str::slug($category['group']),
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);
        }
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request)
    {
        if ( ! $request->has('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $params = $request->input('params');
        if (isset($params['autor']) && $params['autor']) {
            if (strpos($params['autor'], '+') !== false) {
                $arr = explode('+', $params['autor']);

                foreach ($arr as $item) {
                    $_author = Author::where('slug', $item)->first();
                    $this->authors[] = $_author;
                }

            } else {
                $_author = Author::where('slug', $params['autor'])->first();
                $this->authors[] = $_author;
            }
        }

        if (isset($params['nakladnik']) && $params['nakladnik']) {
            if (strpos($params['nakladnik'], '+') !== false) {
                $arr = explode('+', $params['nakladnik']);

                foreach ($arr as $item) {
                    $_publisher = Publisher::where('slug', $item)->first();
                    $this->publishers[] = $_publisher;
                }

            } else {
                $_publisher = Publisher::where('slug', $params['nakladnik'])->first();
                $this->publishers[] = $_publisher;
            }
        }

        $request_data = [];

        if (isset($params['ids']) && $params['ids'] != '') {
            $request_data['ids'] = $params['ids'];
        }

        if (isset($params['group']) && $params['group']) {
            $request_data['group'] = $params['group'];
        }

        if (isset($params['cat']) && $params['cat']) {
            $request_data['cat'] = $params['cat'];
        }

        if (isset($params['subcat']) && $params['subcat']) {
            $request_data['subcat'] = $params['subcat'];
        }

        if (isset($params['autor']) && $params['autor']) {
            $request_data['autor'] = $this->authors;
        }

        if (isset($params['nakladnik']) && $params['nakladnik']) {
            $request_data['nakladnik'] = $this->publishers;
        }

        if (isset($params['start']) && $params['start']) {
            $request_data['start'] = $params['start'];
        }

        if (isset($params['end']) && $params['end']) {
            $request_data['end'] = $params['end'];
        }

        if (isset($params['sort']) && $params['sort']) {
            $request_data['sort'] = $params['sort'];
        }

        $page = max((int) $request->input('page', 1), 1);
        $request_data['page'] = $page;
        $request = new Request($request_data);

        $products = (new Product())->filter($request)
            ->select([
                'id',
                'author_id',
                'action_id',
                'name',
                'url',
                'category_string',
                'image',
                'price',
                'special',
                'special_from',
                'special_to',
            ])
            ->with([
                'author:id,title,url',
                'action:id,status,coupon',
            ])
            ->paginate(config('settings.pagination.front'), ['*'], 'page', $page);

        $mainCurrency = Currency::main();
        $secondaryCurrency = Currency::secondary();

        $products->getCollection()->transform(function (Product $product) use ($mainCurrency, $secondaryCurrency) {
            $effectiveSpecial = $product->special();
            $hasSpecialPrice = $effectiveSpecial < (float) $product->price;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'url' => $product->url,
                'category_string' => $product->category_string,
                'image' => $product->image,
                'price' => number_format((float) $product->price, 4, '.', ''),
                'special' => $hasSpecialPrice ? number_format((float) $effectiveSpecial, 4, '.', '') : null,
                'main_price' => $this->resolveCurrencyPrice($mainCurrency, $product->price),
                'main_price_text' => $this->resolveCurrencyPrice($mainCurrency, $product->price, true),
                'main_special' => $this->resolveCurrencyPrice($mainCurrency, $effectiveSpecial),
                'main_special_text' => $this->resolveCurrencyPrice($mainCurrency, $effectiveSpecial, true),
                'secondary_price' => $this->resolveCurrencyPrice($secondaryCurrency, $product->price),
                'secondary_price_text' => $this->resolveCurrencyPrice($secondaryCurrency, $product->price, true),
                'secondary_special' => $this->resolveCurrencyPrice($secondaryCurrency, $effectiveSpecial),
                'secondary_special_text' => $this->resolveCurrencyPrice($secondaryCurrency, $effectiveSpecial, true),
                'author' => $product->author ? [
                    'title' => $product->author->title,
                    'url' => $product->author->url,
                ] : null,
            ];
        });

        return response()->json($products);
    }

    private function resolveCurrencyPrice($currency, $price, bool $formatted = false): ?string
    {
        if (! $currency || $price === null || $price === '') {
            return null;
        }

        $value = (float) $price * (float) $currency->value;

        if ($formatted) {
            $left = $currency->symbol_left ? $currency->symbol_left . ' ' : '';
            $right = $currency->symbol_right ? ' ' . $currency->symbol_right : '';

            return $left . number_format($value, $currency->decimal_places, ',', '.') . $right;
        }

        return number_format($value, $currency->decimal_places, '.', '');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function authors(Request $request)
    {
        if ($request->has('params')) {
            $params = $request->input('params');

            // Bazni upit
            $builder = (new Author())
                ->filter($params)           // tvoja postojeća logika (status, search_author, itd.)
                ->basicData()               // ako postoji; možeš maknuti ako nema
                ->withCount(['products as products_count' => function ($q) use ($params) {
                    $q->where('status', 1)->where('quantity', '>', 0);

                    // Filtriraj na kontekst kategorije/podkategorije
                    if (!empty($params['subcat'])) {
                        $q->whereHas('categories', fn($w) => $w->where('id', $params['subcat']));
                    } elseif (!empty($params['cat'])) {
                        $q->whereHas('categories', fn($w) => $w->where('id', $params['cat']));
                    }
                }]);

            // Ako NEMA pretrage po autoru -> limitiraj na 15, dosljedno publisherima
            if (empty($params['search_author'])) {
                $builder->orderBy('title')->limit(15);
            } else {
                // uz pretragu možeš i dalje limitirati (npr. 50) ako želiš:
                // $builder->orderBy('title')->limit(50);
                $builder->orderBy('title');
            }

            return response()->json($builder->get()->toArray());
        }

        // Featured fallback (izvan konteksta) – također filtrirani count + limit 15
        return response()->json(
            Helper::resolveCache('authors')->remember('featured', config('cache.life'), function () {
                return Author::query()->active()
                    ->featured()
                    ->basicData()
                    ->withCount(['products as products_count' => function ($q) {
                        $q->where('status', 1)->where('quantity', '>', 0);
                    }])
                    ->orderBy('title')
                    ->limit(15)
                    ->get()
                    ->toArray();
            })
        );
    }





    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function publishers(Request $request)
    {
        if ($request->has('params')) {
            $params = $request->input('params');

            $query = (new Publisher())->filter($params)
                ->basicData()
                ->withCount(['products as products_count' => function ($q) use ($params) {
                    $q->where('status', 1)->where('quantity', '>', 0);

                    if (!empty($params['subcat'])) {
                        $q->whereHas('categories', fn($w) => $w->where('id', $params['subcat']));
                    } elseif (!empty($params['cat'])) {
                        $q->whereHas('categories', fn($w) => $w->where('id', $params['cat']));
                    }
                }]);

            return response()->json($query->get()->toArray());
        }

        return response()->json(
            Helper::resolveCache('publishers')->remember('featured', config('cache.life'), function () {
                return Publisher::active()
                    ->featured()
                    ->basicData()
                    ->withCount(['products as products_count' => function ($q) {
                        $q->where('status', 1)->where('quantity', '>', 0);
                    }])
                    ->get()
                    ->toArray();
            })
        );
    }



}
