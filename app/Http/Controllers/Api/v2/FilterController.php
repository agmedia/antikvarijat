<?php

namespace App\Http\Controllers\Api\v2;

use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Helpers\LocaleHelper;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Http\Controllers\Controller;
use App\Services\ProductRecommendationService;
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
        $this->applyRequestedLocale($request);

        if ( ! $request->input('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $response = [];
        $params = $request->input('params');
        $searchTerm = trim((string) ($params[config('settings.search_keyword', 'pojam')] ?? ''));
        $exactSkuIds = $searchTerm !== ''
            ? Product::query()->active()->where('sku', $searchTerm)->pluck('id')
            : collect();

        $author = !empty($params['author']) ? $this->resolveAuthorBySlug($params['author']) : null;
        $publisher = !empty($params['publisher']) ? $this->resolvePublisherBySlug($params['publisher']) : null;

        if ( ! $params['cat'] && ! $params['subcat']) {
            // Ako je normal kategorija
            if ($params['group']) {
                $cacheKey = LocaleHelper::current() . '.group.' . $params['group'];
                $categories = Helper::resolveCache('categories')->remember($cacheKey, config('cache.life'), function () use ($params) {
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
            if ( ! $params['group'] && !empty($params['author']) && $author) {
                $a_cats = $author->categories();
                $response = $this->resolveCategoryArray($a_cats, 'author', $author);
            }

            // Ako je nakladnik
            if ( ! $params['group'] && !empty($params['publisher']) && $publisher) {
                $a_cats = $publisher->categories();
                $response = $this->resolveCategoryArray($a_cats, 'publisher', $publisher);
            }
        }
        //
        if ($params['cat'] && ! $params['subcat']) {
            $cat = Category::where('id', $params['cat'])->first();

            if ($params['group']) {
                $cacheKey = LocaleHelper::current() . '.parent.' . $cat['id'];
                $item = Helper::resolveCache('categories')->remember($cacheKey, config('cache.life'), function () use ($cat) {
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
            if ( ! $params['group'] && !empty($params['author']) && $author) {
                $a_cats = $author->categories($cat['id']);
                $response = $this->resolveCategoryArray($a_cats, 'author', $author, $cat['slug']);
            }

            // Ako je nakladnik
            if ( ! $params['group'] && !empty($params['publisher']) && $publisher) {
                $a_cats = $publisher->categories($cat['id']);
                $response = $this->resolveCategoryArray($a_cats, 'publisher', $publisher, $cat['slug']);
            }
        }

        if ($params['ids'] && $params['ids'] != '[]') {
            $_ids = collect(explode(',', substr($params['ids'], 1, -1)))->unique();

            $categories = Category::active()
                ->whereHas('products', function ($query) use ($_ids, $exactSkuIds) {
                    $query->active()->whereIn('id', $_ids);

                    if ($exactSkuIds->isNotEmpty()) {
                        $query->where(function ($query) use ($exactSkuIds) {
                            $query->hasStock()->orWhereIn('id', $exactSkuIds);
                        });
                    } else {
                        $query->hasStock();
                    }
                })
                ->sortByName()
                ->withCount([
                    // Filtrirani count prati istu vidljivost kao SKU search rezultati.
                    'products as products_count' => function ($query) use ($_ids, $exactSkuIds) {
                        $query->active()->whereIn('id', $_ids);

                        if ($exactSkuIds->isNotEmpty()) {
                            $query->where(function ($query) use ($exactSkuIds) {
                                $query->hasStock()->orWhereIn('id', $exactSkuIds);
                            });
                        } else {
                            $query->hasStock();
                        }
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
                'title' => LocaleHelper::localizedField($category, 'title'),
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
            return LocaleHelper::route('catalog.route.author', [
                'author' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } elseif ($type == 'publisher') {
            return LocaleHelper::route('catalog.route.publisher', [
                'publisher' => $target,
                'cat' => $parent_slug ?: $category['slug'],
                'subcat' => $parent_slug ? $category['slug'] : null
            ]);

        } else {
            return LocaleHelper::route('catalog.route', [
                'group' => LocaleHelper::groupSlug((string) $category['group']),
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
    public function products(Request $request, ProductRecommendationService $recommendations)
    {
        $this->applyRequestedLocale($request);

        if ( ! $request->has('params')) {
            return response()->json(['status' => 300, 'message' => 'Error!']);
        }

        $params = $request->input('params');
        $authors = [];
        $publishers = [];

        if (isset($params['autor']) && $params['autor']) {
            if (strpos($params['autor'], '+') !== false) {
                $arr = explode('+', $params['autor']);

                foreach ($arr as $item) {
                    $authors[] = $this->resolveAuthorBySlug($item);
                }

            } else {
                $authors[] = $this->resolveAuthorBySlug($params['autor']);
            }

            $authors = array_values(array_filter($authors));
        }

        if (isset($params['nakladnik']) && $params['nakladnik']) {
            if (strpos($params['nakladnik'], '+') !== false) {
                $arr = explode('+', $params['nakladnik']);

                foreach ($arr as $item) {
                    $publishers[] = $this->resolvePublisherBySlug($item);
                }

            } else {
                $publishers[] = $this->resolvePublisherBySlug($params['nakladnik']);
            }

            $publishers = array_values(array_filter($publishers));
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
            $request_data['autor'] = $authors;
        }

        if (isset($params['nakladnik']) && $params['nakladnik']) {
            $request_data['nakladnik'] = $publishers;
        }

        if (isset($params['start']) && $params['start']) {
            $request_data['start'] = $params['start'];
        }

        if (isset($params['end']) && $params['end']) {
            $request_data['end'] = $params['end'];
        }

        foreach (['pismo', 'stanje', 'uvez'] as $attribute) {
            if (isset($params[$attribute]) && is_scalar($params[$attribute]) && $params[$attribute] !== '') {
                $request_data[$attribute] = (string) $params[$attribute];
            }
        }

        if (isset($params['sort']) && $params['sort']) {
            $request_data['sort'] = $params['sort'];
        }

        if (isset($params['pojam']) && $params['pojam']) {
            $request_data[config('settings.search_keyword', 'pojam')] = $params['pojam'];
        }

        $page = max((int) $request->input('page', 1), 1);
        $request_data['page'] = $page;

        if ($this->shouldDefaultBooksRootToLatest($request_data)) {
            $request_data['_default_sort_latest'] = true;
        }

        $request = new Request($request_data);

        $products = (new Product())->filter($request)
            ->select([
                'id',
                'author_id',
                'publisher_id',
                'action_id',
                'name',
                'name_en',
                'slug',
                'slug_en',
                'url',
                'url_en',
                'category_string',
                'image',
                'price',
                'special',
                'special_from',
                'special_to',
            ])
            ->withReviewSummary()
            ->with([
                'author:id,title,title_en,slug,slug_en,url,url_en',
                'publisher:id,title,title_en,slug,slug_en,url,url_en',
                'categories:id,parent_id,title,title_en,group,slug,slug_en',
                'action:id,status,coupon',
            ])
            ->paginate(config('settings.pagination.front'), ['*'], 'page', $page);

        $mainCurrency = Currency::main();
        $secondaryCurrency = Currency::secondary();
        $salesBadgeTypes = $recommendations->salesBadgeTypes($products->getCollection()->pluck('id'));

        $products->getCollection()->transform(function (Product $product) use ($mainCurrency, $secondaryCurrency, $salesBadgeTypes) {
            $effectiveSpecial = $product->special();
            $hasSpecialPrice = $effectiveSpecial < (float) $product->price;
            $category = $product->category();
            $subcategory = $product->subcategory();
            $cardCategory = $subcategory ?: $category;

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
                'approved_reviews_count' => (int) ($product->approved_reviews_count ?? 0),
                'approved_reviews_average' => round((float) ($product->approved_reviews_average ?? 0), 2),
                'sales_badge_type' => $salesBadgeTypes->get((int) $product->id),
                'card_category' => $category && $cardCategory ? [
                    'title' => $cardCategory->title,
                    'url' => LocaleHelper::categoryUrl($category, $subcategory),
                ] : null,
                'author' => $product->author && Author::hasMeaningfulTitle($product->author->title) ? [
                    'title' => $product->author->title,
                    'url' => $product->author->url,
                ] : null,
                'publisher' => $product->publisher ? [
                    'title' => $product->publisher->title,
                    'url' => $product->publisher->url,
                ] : null,
            ];
        });

        return response()->json($products);
    }

    private function shouldDefaultBooksRootToLatest(array $requestData): bool
    {
        $group = Str::slug((string) ($requestData['group'] ?? ''));

        if (! in_array($group, ['knjige', 'books'], true)) {
            return false;
        }

        foreach (['ids', 'cat', 'subcat', 'autor', 'nakladnik', 'start', 'end', 'pismo', 'stanje', 'uvez', 'sort', config('settings.search_keyword', 'pojam')] as $key) {
            if (!empty($requestData[$key])) {
                return false;
            }
        }

        return true;
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
        $this->applyRequestedLocale($request);

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

            return response()->json(
                $builder->get()
                    ->filter(fn (Author $author) => Author::hasMeaningfulTitle($author->title))
                    ->values()
                    ->toArray()
            );
        }

        // Featured fallback (izvan konteksta) – također filtrirani count + limit 15
        return response()->json(
            Helper::resolveCache('authors')->remember('authors.featured.visible', config('cache.life'), function () {
                return Author::query()->active()
                    ->featured()
                    ->basicData()
                    ->withCount(['products as products_count' => function ($q) {
                        $q->where('status', 1)->where('quantity', '>', 0);
                    }])
                    ->orderBy('title')
                    ->limit(15)
                    ->get()
                    ->filter(fn (Author $author) => Author::hasMeaningfulTitle($author->title))
                    ->values()
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
        $this->applyRequestedLocale($request);

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
            Helper::resolveCache('publishers')->remember('publishers.featured', config('cache.life'), function () {
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

    private function applyRequestedLocale(Request $request): void
    {
        $params = $request->input('params', []);

        if (($params['locale'] ?? null) === LocaleHelper::ENGLISH_LOCALE) {
            app()->setLocale(LocaleHelper::ENGLISH_LOCALE);
            config(['app.locale' => LocaleHelper::ENGLISH_LOCALE]);
        }
    }

    private function resolveAuthorBySlug(string $slug): ?Author
    {
        return Author::query()
            ->where('slug', $slug)
            ->orWhere('slug_en', $slug)
            ->first();
    }

    private function resolvePublisherBySlug(string $slug): ?Publisher
    {
        return Publisher::query()
            ->where('slug', $slug)
            ->orWhere('slug_en', $slug)
            ->first();
    }



}
