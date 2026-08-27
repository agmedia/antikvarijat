<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Breadcrumb;
use App\Helpers\Helper;
use App\Helpers\LocaleHelper;
use App\Helpers\StructuredData;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Blog;
use App\Models\Front\Page;
use App\Models\Front\Faq;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Models\Seo;
use App\Models\TagManager;
use App\Models\ProductReview;
use App\Services\ProductRecommendationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CatalogRouteController extends Controller
{
    public function resolve(Request $request, $group, ?Category $cat = null, $subcat = null, ?Product $prod = null)
    {
        $group = LocaleHelper::internalGroup((string) $group);

        if ($subcat) {
            $sub_category = LocaleHelper::isEnglish()
                ? Category::query()
                    ->where('slug_en', $subcat)
                    ->where('parent_id', $cat->id)
                    ->first()
                : null;

            $sub_category = $sub_category ?: Category::query()
                ->where('slug', $subcat)
                ->where('parent_id', $cat->id)
                ->first();

            if (! $sub_category && ctype_digit((string) $subcat)) {
                $sub_category = Category::query()
                    ->whereKey((int) $subcat)
                    ->where('parent_id', $cat->id)
                    ->first();
            }

            if (! $sub_category && ! $prod) {
                $prod = LocaleHelper::isEnglish()
                    ? Product::query()->where('slug_en', $subcat)->first()
                    : null;

                $prod = $prod ?: Product::query()->where('slug', $subcat)->first();

                if (! $prod && ctype_digit((string) $subcat)) {
                    $prod = Product::query()->whereKey((int) $subcat)->first();
                }
            }

            $subcat = $sub_category;
        }

        if ($prod) {
            if (!$prod->status) {
                abort(404);
            }

            $prod->loadMissing(['author', 'publisher', 'translators:id,title', 'images', 'action', 'categories']);

            $canonicalProductUrl = url(LocaleHelper::productPath(
                $prod,
                $prod->getRawOriginal('url'),
                app()->getLocale()
            ));
            if ($redirect = $this->redirectToCanonicalPath($request, $canonicalProductUrl)) {
                return $redirect;
            }
            $request->attributes->set('seo.product', $prod);

            $prod->timestamps = false;
            $prod->increment('viewed');
            $prod->timestamps = true;

            $seo = Seo::getProductData($prod);
            $gdl = TagManager::getGoogleProductDataLayer($prod);

            $recent = collect(session('recent_products', []));
            $recent = $recent->prepend($prod->id)->unique()->values();
            $recent = $recent->take(50);
            session(['recent_products' => $recent->all()]);

            $recentIds = $recent->filter(fn ($id) => (int)$id !== (int)$prod->id)
                ->take(15)
                ->values()
                ->all();

            $recentProducts = collect();
            if (!empty($recentIds)) {
                $recentProducts = Product::whereIn('id', $recentIds)
                    ->where('status', 1)
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->get()
                    ->sortBy(fn ($p) => array_search($p->id, $recentIds))
                    ->values();
            }

            $relatedProducts = collect();
            $relatedCategory = $subcat instanceof Category ? $subcat : $cat;
            if ($relatedCategory) {
                $relatedProducts = $relatedCategory->products()
                    ->where('products.id', '!=', $prod->id)
                    ->where('quantity', '>', 0)
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->take(15)
                    ->get()
                    ->unique('id')
                    ->values();
            }

            $authorTitle = trim((string) optional($prod->author)->title);
            $hasAuthor = $prod->author && Author::hasMeaningfulTitle($authorTitle);

            $translatorNames = $prod->translators
                ->pluck('title')
                ->map(fn ($title) => trim((string) $title))
                ->filter(fn ($title) => Author::hasMeaningfulTitle($title))
                ->unique(fn ($title) => Str::lower($title))
                ->values();
            $hasTranslators = $translatorNames->isNotEmpty();

            $authorProducts = $hasAuthor
                ? $prod->author->products()
                    ->where('products.id', '!=', $prod->id)
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->latest('products.created_at')
                    ->take(15)
                    ->get()
                : collect();

            $publisherTitle = trim((string) optional($prod->publisher)->title);
            $hasPublisher = $prod->publisher && $publisherTitle !== '' && $publisherTitle !== '-';

            $publisherProducts = $hasPublisher
                ? $prod->publisher->products()
                    ->where('products.id', '!=', $prod->id)
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->latest('products.created_at')
                    ->take(15)
                    ->get()
                : collect();

            $reviews = ProductReview::query()
                ->approved()
                ->where('product_id', $prod->id)
                ->latest('approved_at')
                ->take(20)
                ->get();

            $reviewStatsRow = ProductReview::query()
                ->approved()
                ->where('product_id', $prod->id)
                ->selectRaw('COUNT(*) AS review_count, AVG(rating) AS rating_average')
                ->first();
            $reviewDistribution = ProductReview::query()
                ->approved()
                ->where('product_id', $prod->id)
                ->selectRaw('rating, COUNT(*) AS review_count')
                ->groupBy('rating')
                ->pluck('review_count', 'rating')
                ->map(fn ($count) => (int) $count)
                ->all();
            $reviewStats = [
                'count' => (int) ($reviewStatsRow->review_count ?? 0),
                'average' => round((float) ($reviewStatsRow->rating_average ?? 0), 2),
                'distribution' => array_replace(array_fill_keys(range(1, 5), 0), $reviewDistribution),
            ];

            $bc = new Breadcrumb();
            $crumbs = $bc->product($group, $cat, $subcat, $prod)->resolve();
            $shipping_methods = Settings::getList('shipping', 'list.%', true);
            $geo_zones = Settings::getList('geo_zone', 'list', true);
            $bookscheme = $bc->productBookSchema(
                $prod,
                $reviews,
                $reviewStats,
                $shipping_methods,
                $geo_zones
            );
            $payment_methods = Settings::getList('payment', 'list.%', true);

            return view('front.catalog.product.index', compact(
                'prod',
                'group',
                'cat',
                'subcat',
                'seo',
                'crumbs',
                'bookscheme',
                'shipping_methods',
                'payment_methods',
                'gdl',
                'reviews',
                'reviewStats',
                'recentProducts',
                'relatedProducts',
                'authorProducts',
                'publisherProducts',
                'hasAuthor',
                'hasPublisher',
                'translatorNames',
                'hasTranslators'
            ));
        }

        if ($group && !$cat && !$subcat) {
            if ($group == 'zemljovidi-i-vedute') {
                $group = 'Zemljovidi i vedute';
            }

            $categories = Category::where('group', $group)->first('id');

            if (!$categories) {
                abort(404);
            }
        }

        $canonicalGroup = $cat
            ? (string) $cat->getRawOriginal('group')
            : $group;
        $canonicalCategoryUrl = LocaleHelper::route('catalog.route', array_filter([
            'group' => $canonicalGroup,
            'cat' => $cat,
            'subcat' => $subcat,
        ]));
        if ($redirect = $this->redirectToCanonicalPath($request, $canonicalCategoryUrl)) {
            return $redirect;
        }

        // FILTRIRANI COUNT ZA CAT I SUBCAT
        if ($cat) {
            $cat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $cat->setAttribute('count', (int)$cat->visible_products_count);
        }
        if ($subcat) {
            $subcat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $subcat->setAttribute('count', (int)$subcat->visible_products_count);
        }

        $crumbs = (new Breadcrumb())->category($group, $cat, $subcat)->resolve();

        return view('front.catalog.category.index', array_merge(
            compact('group', 'cat', 'subcat', 'prod', 'crumbs'),
            $this->categoryIndexBootstrap($request, $group, $cat, $subcat)
        ));
    }

    public function resolveOldUrl($prod = null)
    {
        if ($prod) {
            $prod = substr($prod, 0, strrpos($prod, '-'));
            $prod = Product::where('slug', 'LIKE', $prod . '%')->first();

            if ($prod) {
                return redirect()->to(url($prod->url), 301);
            }
        }

        abort(404);
    }

    public function resolveOldCategoryUrl(?string $group = null, $cat = null, $subcat = null)
    {
        if ($group) {
            return redirect(LocaleHelper::route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat]));
        }

        abort(404);
    }

    public function author(Request $request, ?Author $author = null, ?Category $cat = null, ?Category $subcat = null)
    {
        if (!$author) {
            $letters = Helper::resolveCache('authors')->remember('authors.letters', config('cache.life'), function () {
                return Author::letters();
            });
            $letter = $this->checkLetter($letters);

            if ($request->has('letter')) {
                $letter = $request->input('letter');
            }

            $currentPage = request()->get('page', 1);

            $authors = Helper::resolveCache('authors')->remember('authors.index.' . $letter . '.' . $currentPage, config('cache.life'), function () use ($letter) {
                return Author::query()->select('id', 'title', 'title_en', 'slug', 'slug_en', 'url', 'url_en')
                    ->where('status', 1)
                    ->where('letter', $letter)
                    ->orderBy('title')
                    ->withCount('products')
                    ->paginate(36)
                    ->appends(request()->query());
            });

            return view('front.catalog.authors.index', compact('authors', 'letters', 'letter'));
        }

        $letter = null;

        if (! $author->status) {
            abort(404);
        }

        $canonicalAuthorUrl = LocaleHelper::route('catalog.route.author', array_filter([
            'author' => $author,
            'cat' => $cat,
            'subcat' => $subcat,
        ]));
        if ($redirect = $this->redirectToCanonicalPath($request, $canonicalAuthorUrl)) {
            return $redirect;
        }

        // FILTRIRANI COUNT ZA CAT I SUBCAT
        if ($cat) {
            $cat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $cat->setAttribute('count', (int)$cat->visible_products_count);
        }
        if ($subcat) {
            $subcat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $subcat->setAttribute('count', (int)$subcat->visible_products_count);
        }

        $seo = Seo::getAuthorData($author, $cat, $subcat);
        $crumbs = (new Breadcrumb())->author($author, $cat, $subcat)->resolve();

        $bootstrap = $this->categoryIndexBootstrap($request, null, $cat, $subcat, null, $author);
        $entityIndexable = ! $cat && ! $subcat && Seo::shouldIndexCatalogEntity(
            $author,
            (int) $bootstrap['initialProductsPaginator']->total(),
            app()->getLocale()
        );

        return view('front.catalog.category.index', array_merge(
            compact('author', 'letter', 'cat', 'subcat', 'seo', 'crumbs', 'entityIndexable'),
            $bootstrap
        ));
    }

    public function publisher(Request $request, ?Publisher $publisher = null, ?Category $cat = null, ?Category $subcat = null)
    {
        if (!$publisher) {
            $letters = Helper::resolveCache('publishers')->remember('publishers.letters', config('cache.life'), function () {
                return Publisher::letters();
            });
            $letter = $this->checkLetter($letters);

            if ($request->has('letter')) {
                $letter = $request->input('letter');
            }

            $currentPage = request()->get('page', 1);

            $publishers = Helper::resolveCache('publishers')->remember('publishers.index.' . $letter . '.' . $currentPage, config('cache.life'), function () use ($letter) {
                return Publisher::query()->select('id', 'title', 'title_en', 'slug', 'slug_en', 'url', 'url_en')
                    ->where('status', 1)
                    ->where('letter', $letter)
                    ->orderBy('title')
                    ->withCount('products')
                    ->paginate(36)
                    ->appends(request()->query());
            });

            return view('front.catalog.publishers.index', compact('publishers', 'letters', 'letter'));
        }

        $letter = null;

        if (! $publisher->status) {
            abort(404);
        }

        $canonicalPublisherUrl = LocaleHelper::route('catalog.route.publisher', array_filter([
            'publisher' => $publisher,
            'cat' => $cat,
            'subcat' => $subcat,
        ]));
        if ($redirect = $this->redirectToCanonicalPath($request, $canonicalPublisherUrl)) {
            return $redirect;
        }

        // FILTRIRANI COUNT ZA CAT I SUBCAT
        if ($cat) {
            $cat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $cat->setAttribute('count', (int)$cat->visible_products_count);
        }
        if ($subcat) {
            $subcat->loadCount(['products as visible_products_count' => function ($q) {
                $q->where('status', 1)->where('quantity', '>', 0);
            }]);
            $subcat->setAttribute('count', (int)$subcat->visible_products_count);
        }

        $seo = Seo::getPublisherData($publisher, $cat, $subcat);
        $crumbs = (new Breadcrumb())->publisher($publisher, $cat, $subcat)->resolve();

        $bootstrap = $this->categoryIndexBootstrap($request, null, $cat, $subcat, null, null, $publisher);
        $entityIndexable = ! $cat && ! $subcat && Seo::shouldIndexCatalogEntity(
            $publisher,
            (int) $bootstrap['initialProductsPaginator']->total(),
            app()->getLocale()
        );

        return view('front.catalog.category.index', array_merge(
            compact('publisher', 'letter', 'cat', 'subcat', 'seo', 'crumbs', 'entityIndexable'),
            $bootstrap
        ));
    }

    public function tag(Request $request)
    {
        $key = config('settings.search_keyword', 'pojam');
        $query = $request->input($key);

        if ($query === null) {
            return redirect()->back()->with(['error' => __('front.search.missing_query')]);
        }
        if ($query === '') {
            return redirect()->back()->with(['error' => __('front.search.empty_query')]);
        }

        $ids = Helper::getTags($query);
        $group = $cat = $subcat = $crumbs = null;

        return view('front.catalog.category.index', array_merge(
            compact('group', 'cat', 'subcat', 'ids', 'crumbs'),
            $this->categoryIndexBootstrap($request, $group, $cat, $subcat, $ids)
        ));
    }

    public function search(Request $request)
    {
        if ($request->input('locale') === LocaleHelper::ENGLISH_LOCALE) {
            app()->setLocale(LocaleHelper::ENGLISH_LOCALE);
            config(['app.locale' => LocaleHelper::ENGLISH_LOCALE]);
        }

        // web stranica s rezultatima (ne diramo)
        if ($request->has(config('settings.search_keyword'))) {
            if (!$request->input(config('settings.search_keyword'))) {
                return redirect()->back()->with(['error' => __('front.search.empty_query')]);
            }

            $group = null; $cat = null; $subcat = null;

            $ids = Helper::search(
                $request->input(config('settings.search_keyword'))
            );

            $crumbs = null;

            return view('front.catalog.category.index', array_merge(
                compact('group', 'cat', 'subcat', 'ids', 'crumbs'),
                $this->categoryIndexBootstrap($request, $group, $cat, $subcat, $ids)
            ));
        }

        // API autocomplete – structured JSON: counts + products + categories
        if ($request->has(config('settings.search_keyword') . '_api')) {

            $q = (string) $request->input(config('settings.search_keyword') . '_api', '');

            // >>> UZMI $group IZ REQUESTA ILI STAVI DEFAULT
            $group = trim((string) $request->input('group', 'knjige'), '/');

                // --- PROIZVODI ---
            $search = Helper::search($q, true, true);
            $totalProducts = (int) ($search['total'] ?? 0);
            $productIds    = $search['products'];

                $items = Product::query()
                ->with(['author'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

            $productsPayload = [];
                foreach ($productIds as $id) {
                    $p = $items->get($id);
                    if (!$p) continue;

                $productsPayload[] = [
                    'id'                 => $p->id,
                    'sku'                => $p->sku,
                    'name'               => $p->name,
                    'url'                => url($p->url),
                    'main_price'         => $p->main_price,
                    'main_price_text'    => $p->main_price_text,
                    'main_special'       => $p->main_special,
                    'main_special_text'  => $p->main_special_text,
                    'image'              => $p->thumb,
                    'author_title'       => optional($p->author)->title,
                    'quantity'           => $p->quantity,          // dodano!
                ];
                }

                // --- KATEGORIJE ---
                $catsBase = Category::query()
                ->when(method_exists(Category::class, 'scopeActive'), fn ($q2) => $q2->active())
                ->where(function ($w) use ($q) {
                    $w->where('title', 'like', '%' . $q . '%');
                    // dodaj druge kolone samo ako postoje
                    if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'description')) {
                        $w->orWhere('description', 'like', '%' . $q . '%');
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'meta_description')) {
                        $w->orWhere('meta_description', 'like', '%' . $q . '%');
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'content')) {
                        $w->orWhere('content', 'like', '%' . $q . '%');
                }
                });

                $totalCategories = (clone $catsBase)->count();

                $categories = $catsBase
                    ->orderBy('title')
                    ->limit(10)
                    ->get();

            $categoriesPayload = $categories->map(function ($c) use ($group) {
                // NE koristiti $c->url accessor (imaš metodu url())
                $slug = $c->slug ?: $c->id;
                return [
                        'id'   => $c->id,
                    'name' => $c->title,               // JS očekuje "name"
                    'url'  => LocaleHelper::route('catalog.route', [
                        'group' => LocaleHelper::groupSlug($group),
                        'cat' => $slug,
                    ]),
                ];
            })->values()->all();

                // --- AUTORI ---
            // --- AUTORI ---
            $rawQ = trim((string)$q);

            // razbij na riječi (razmaci, točke, zarezi, crtice), očisti duplikate
            $tokens = collect(preg_split('/[\s\.,\-_\|]+/u', $rawQ, -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn($t) => Str::lower($t))
                    ->unique()
                ->take(5)               // sigurnosni limit da ne generiramo pretežak upit
                    ->values();

                $authorsBase = Author::query()
                    ->select('id', 'title', 'url')
                    ->where('status', 1)
                // pokaži samo autore koji imaju barem jedan vidljiv artikal; makni ako želiš sve autore
                ->whereHas('products', function ($q2) {
                    $q2->where('status', 1)->where('quantity', '>', 0);
                })
                // Kriterij: SVAKA riječ iz upita mora se pojaviti u title (redoslijed nebitan)
                ->when($tokens->isNotEmpty(), function ($qA) use ($tokens) {
                    $qA->where(function ($w) use ($tokens) {
                        foreach ($tokens as $t) {
                            $w->where('title', 'like', '%' . $t . '%');
                        }
                    });
                });

                // opcionalno: dodatni OR na slug ako ga koristiš za pretragu
                // ->orWhere(function($w) use ($tokens){
                //     foreach ($tokens as $t) { $w->where('slug', 'like', '%' . $t . '%'); }
                // });

                $totalAuthors = (clone $authorsBase)->count();

                $authors = $authorsBase
                    ->orderBy('title')
                    ->limit(10)
                    ->get();

            $authorsPayload = $authors->map(fn($a) => [
                        'id'   => $a->id,
                        'name' => $a->title,
                        'url'  => url($a->url),
            ])->values()->all();



            // --- STRUCTURED PAYLOAD + X-Total-Count ---
            $payload = [
                'counts'     => [
                        'products'   => $totalProducts,
                        'authors'    => $totalAuthors,
                        'categories' => $totalCategories,
                ],
                    'products'   => $productsPayload,
                    'categories' => $categoriesPayload,
                    'authors'    => $authorsPayload,
            ];

            $totalAll = $payload['counts']['products']
                + $payload['counts']['authors']
                + $payload['counts']['categories'];

            return response()->json($payload)
                ->header('X-Total-Count', $totalAll);
        }

        return response()->json(['error' => __('front.search.error')]);
    }


    public function actions(Request $request, ?Category $cat = null, $subcat = null)
    {
        $ids = collect();
        $group = 'snizenja';
        $crumbs = null;

        return view('front.catalog.category.index', array_merge(
            compact('group', 'cat', 'subcat', 'ids', 'crumbs'),
            $this->categoryIndexBootstrap($request, $group, $cat, $subcat, $ids)
        ));
    }

    public function page(Page $page)
    {
        return view('front.page', compact('page'));
    }

    public function blog(Blog $blog)
    {
        if (!$blog->exists) {
            $blogs = Blog::query()
                ->active()
                ->select([
                    'id',
                    'slug',
                    'slug_en',
                    'title',
                    'title_en',
                    'short_description',
                    'short_description_en',
                    'image',
                    'publish_date',
                    'created_at',
                ])
                ->paginate(9);

            return view('front.blog', compact('blogs'));
        }

        $blogSchema = StructuredData::blogPosting(
            $blog,
            LocaleHelper::route('catalog.route.blog', ['blog' => $blog]),
            app()->getLocale()
        );

        $plainText = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            strip_tags(html_entity_decode((string) $blog->description, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        ));
        $wordCount = count(preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY));
        $readingMinutes = max(1, (int) ceil($wordCount / 200));
        $articleLead = Str::limit(trim((string) preg_replace(
            '/\s+/u',
            ' ',
            strip_tags(html_entity_decode((string) $blog->short_description, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        )), 280, '…');

        $navigationColumns = ['id', 'slug', 'slug_en', 'title', 'title_en', 'created_at'];
        $newerBlog = Blog::query()
            ->active()
            ->where('created_at', '>', $blog->created_at)
            ->reorder('created_at')
            ->first($navigationColumns);
        $olderBlog = Blog::query()
            ->active()
            ->where('created_at', '<', $blog->created_at)
            ->reorder('created_at', 'desc')
            ->first($navigationColumns);

        $recommendationProducts = collect();
        $recommendationTitle = null;

        if ($blog->recommendation_type === 'author' && $blog->recommendation_author_id) {
            $recommendationAuthor = Author::query()->find($blog->recommendation_author_id);

            if ($recommendationAuthor && Author::hasMeaningfulTitle($recommendationAuthor->title)) {
                $recommendationProducts = $recommendationAuthor->products()
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->latest('products.created_at')
                    ->take(20)
                    ->get();
                $recommendationTitle = __('front.blog.books_by_author', [
                    'author' => $recommendationAuthor->title,
                ]);
            }
        } elseif ($blog->recommendation_type === 'products') {
            $productIds = collect($blog->recommendation_product_ids)
                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->take(20)
                ->values();

            if ($productIds->isNotEmpty()) {
                $recommendationProducts = Product::query()
                    ->active()
                    ->hasStock()
                    ->withReviewSummary()
                    ->with(['author', 'action'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->sortBy(fn (Product $product) => $productIds->search((int) $product->id))
                    ->values();
                $recommendationTitle = __('front.blog.view_selection');
            }
        }

        return view('front.blog', compact(
            'blog',
            'blogSchema',
            'articleLead',
            'readingMinutes',
            'newerBlog',
            'olderBlog',
            'recommendationProducts',
            'recommendationTitle'
        ));
    }

    public function faq()
    {
        $faq = Faq::where('status', 1)->get();

        return view('front.faq', compact('faq'));
    }

    private function redirectToCanonicalPath(Request $request, string $canonicalUrl)
    {
        if ($canonicalUrl === '') {
            return null;
        }

        $currentPath = '/' . trim(rawurldecode($request->getPathInfo()), '/');
        $canonicalPath = '/' . trim(rawurldecode((string) parse_url($canonicalUrl, PHP_URL_PATH)), '/');

        if ($currentPath === $canonicalPath) {
            return null;
        }

        if ($request->getQueryString()) {
            $canonicalUrl .= '?' . $request->getQueryString();
        }

        return redirect()->to($canonicalUrl, 301);
    }

    private function checkLetter(Collection $letters): string
    {
        foreach ($letters->all() as $letter) {
            if ($letter['active']) {
                return $letter['value'];
            }
        }
        return 'A';
    }

    private function categoryIndexBootstrap(
        Request $request,
        ?string $group = null,
        ?Category $cat = null,
        ?Category $subcat = null,
        $ids = null,
        ?Author $author = null,
        ?Publisher $publisher = null
    ): array {
        $initialCategories = $this->resolveInitialCategories($request, $group, $cat, $subcat, $ids, $author, $publisher);
        $initialProductsPaginator = $this->resolveInitialProductsPaginator($request, $group, $cat, $subcat, $ids, $author, $publisher);

        return [
            'initialCategories' => $initialCategories,
            'initialAttributes' => $this->resolveInitialProductAttributes(
                $group,
                $cat,
                $subcat,
                $ids,
                $author,
                $publisher
            ),
            'initialProductsPaginator' => $initialProductsPaginator,
            'initialProductsData' => $this->mapProductsPaginator($initialProductsPaginator),
        ];
    }

    private function resolveInitialProductAttributes(
        ?string $group = null,
        ?Category $cat = null,
        ?Category $subcat = null,
        $ids = null,
        ?Author $author = null,
        ?Publisher $publisher = null
    ): array {
        $requestData = $this->buildProductRequestData(
            new Request(),
            $group,
            $cat,
            $subcat,
            $ids,
            $author,
            $publisher
        );

        unset($requestData['page'], $requestData['sort'], $requestData['_default_sort_latest']);

        $cacheContext = [
            'group' => $group,
            'cat' => optional($cat)->id,
            'subcat' => optional($subcat)->id,
            'ids' => $this->normalizeIdsParam($ids),
            'author' => optional($author)->id,
            'publisher' => optional($publisher)->id,
        ];
        $cacheKey = 'catalog.filter.product-attributes.v3.' . sha1(json_encode($cacheContext));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($requestData) {
            $attributes = [];

            foreach ([
                'letter' => 'pismo',
                'condition' => 'stanje',
                'binding' => 'uvez',
            ] as $column => $parameter) {
                $attributeRequestData = $requestData;
                unset($attributeRequestData[$parameter]);

                $attributes[$column] = (new Product())
                    ->filter(new Request($attributeRequestData))
                    ->reorder()
                    ->whereNotNull($column)
                    ->where($column, '<>', '')
                    ->select($column)
                    ->selectRaw('COUNT(*) as products_count')
                    ->groupBy($column)
                    ->orderByDesc('products_count')
                    ->get()
                    ->map(function (Product $product) use ($column) {
                        return [
                            'value' => $product->{$column},
                            'count' => (int) $product->products_count,
                        ];
                    })
                    ->values()
                    ->all();
            }

            return $attributes;
        });
    }

    private function resolveInitialProductsPaginator(
        Request $request,
        ?string $group = null,
        ?Category $cat = null,
        ?Category $subcat = null,
        $ids = null,
        ?Author $author = null,
        ?Publisher $publisher = null
    ): LengthAwarePaginator {
        $queryRequest = new Request($this->buildProductRequestData($request, $group, $cat, $subcat, $ids, $author, $publisher));
        $page = max((int) $queryRequest->input('page', 1), 1);

        return (new Product())->filter($queryRequest)
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
            ->paginate(config('settings.pagination.front'), ['*'], 'page', $page)
            ->appends($request->query());
    }

    private function buildProductRequestData(
        Request $request,
        ?string $group = null,
        ?Category $cat = null,
        ?Category $subcat = null,
        $ids = null,
        ?Author $author = null,
        ?Publisher $publisher = null
    ): array {
        $query = $request->query();
        $requestData = [];

        if ($idsParam = $this->normalizeIdsParam($ids)) {
            $requestData['ids'] = $idsParam;
        }

        if ($group) {
            $requestData['group'] = $group;
        }

        if ($cat) {
            $requestData['cat'] = $cat->id;
        }

        if ($subcat) {
            $requestData['subcat'] = $subcat->id;
        }

        if ($author) {
            $requestData['autor'] = [$author];
        } elseif (!empty($query['autor'])) {
            $requestData['autor'] = $this->resolveSelectedAuthors($query['autor']);
        }

        if ($publisher) {
            $requestData['nakladnik'] = [$publisher];
        } elseif (!empty($query['nakladnik'])) {
            $requestData['nakladnik'] = $this->resolveSelectedPublishers($query['nakladnik']);
        }

        if (! empty($query['prevoditelj'])) {
            $translatorIds = is_array($query['prevoditelj'])
                ? $query['prevoditelj']
                : explode('+', (string) $query['prevoditelj']);

            $requestData['prevoditelj'] = collect($translatorIds)
                ->map(function ($translatorId) {
                    if (is_object($translatorId) && isset($translatorId->id)) {
                        return (int) $translatorId->id;
                    }

                    if (is_array($translatorId) && isset($translatorId['id'])) {
                        return (int) $translatorId['id'];
                    }

                    return is_numeric($translatorId) ? (int) $translatorId : null;
                })
                ->filter(fn ($translatorId) => $translatorId > 0)
                ->unique()
                ->values()
                ->all();
        }

        if (!empty($query['start']) && strlen((string) $query['start']) === 4) {
            $requestData['start'] = $query['start'];
        }

        if (!empty($query['end']) && strlen((string) $query['end']) === 4) {
            $requestData['end'] = $query['end'];
        }

        foreach (['pismo', 'stanje', 'uvez'] as $attribute) {
            if (!empty($query[$attribute]) && is_scalar($query[$attribute])) {
                $requestData[$attribute] = (string) $query[$attribute];
            }
        }

        if (!empty($query['sort'])) {
            $requestData['sort'] = $query['sort'];
        }

        if ($request->has(config('settings.search_keyword'))) {
            $requestData[config('settings.search_keyword')] = $request->input(config('settings.search_keyword'));
        }

        if (!empty($query['page'])) {
            $requestData['page'] = max((int) $query['page'], 1);
        }

        if ($this->shouldDefaultBooksRootToLatest($requestData)) {
            $requestData['_default_sort_latest'] = true;
        }

        return $requestData;
    }

    private function shouldDefaultBooksRootToLatest(array $requestData): bool
    {
        $group = Str::slug((string) ($requestData['group'] ?? ''));

        if (! in_array($group, ['knjige', 'books'], true)) {
            return false;
        }

        foreach (['ids', 'cat', 'subcat', 'autor', 'nakladnik', 'prevoditelj', 'start', 'end', 'pismo', 'stanje', 'uvez', 'sort', config('settings.search_keyword', 'pojam')] as $key) {
            if (!empty($requestData[$key])) {
                return false;
            }
        }

        return true;
    }

    private function mapProductsPaginator(LengthAwarePaginator $paginator): array
    {
        $salesBadgeTypes = app(ProductRecommendationService::class)
            ->salesBadgeTypes($paginator->getCollection()->pluck('id'));

        $data = $paginator->getCollection()->map(function (Product $product) use ($salesBadgeTypes) {
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
                'main_price' => $product->main_price,
                'main_price_text' => $product->main_price_text,
                'main_special' => $product->main_special,
                'main_special_text' => $product->main_special_text,
                'secondary_price' => $product->secondary_price,
                'secondary_price_text' => $product->secondary_price_text,
                'secondary_special' => $product->secondary_special,
                'secondary_special_text' => $product->secondary_special_text,
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
        })->values()->all();

        return [
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    private function resolveInitialCategories(
        Request $request,
        ?string $group = null,
        ?Category $cat = null,
        ?Category $subcat = null,
        $ids = null,
        ?Author $author = null,
        ?Publisher $publisher = null
    ): array {
        if ($idsParam = $this->normalizeIdsParam($ids)) {
            $resolvedIds = collect(explode(',', substr($idsParam, 1, -1)))->filter()->unique();
            $exactSkuIds = $this->resolveExactSkuIds($request);

            $categories = Category::active()
                ->whereHas('products', function ($query) use ($resolvedIds, $exactSkuIds) {
                    $query->active()->whereIn('id', $resolvedIds);

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
                    'products as products_count' => function ($query) use ($resolvedIds, $exactSkuIds) {
                        $query->active()->whereIn('id', $resolvedIds);

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

            return $this->resolveCategoryArray($categories, 'categories');
        }

        if (!$cat && !$subcat) {
            if ($group) {
                $normalizedGroup = $this->normalizeCategoryGroup($group);

                $cacheKey = LocaleHelper::current() . '.group.' . $normalizedGroup;
                $categories = Helper::resolveCache('categories')->remember($cacheKey, config('cache.life'), function () use ($normalizedGroup) {
                    return Category::active()
                        ->topList($normalizedGroup)
                        ->sortByName()
                        ->withCount(['products as products_count' => function ($query) {
                            $query->where('status', 1)->where('quantity', '>', 0);
                        }])
                        ->get()
                        ->toArray();
                });

                return $this->resolveCategoryArray($categories, 'categories');
            }

            if ($author) {
                return $this->resolveCategoryArray($author->categories(), 'author', $author);
            }

            if ($publisher) {
                return $this->resolveCategoryArray($publisher->categories(), 'publisher', $publisher);
            }
        }

        if ($cat && !$subcat) {
            if ($group) {
                $cacheKey = LocaleHelper::current() . '.parent.' . $cat->id;
                $items = Helper::resolveCache('categories')->remember($cacheKey, config('cache.life'), function () use ($cat) {
                    return Category::active()
                        ->where('parent_id', $cat->id)
                        ->sortByName()
                        ->withCount(['products as products_count' => function ($query) {
                            $query->where('status', 1)->where('quantity', '>', 0);
                        }])
                        ->get()
                        ->toArray();
                });

                return $this->resolveCategoryArray($items, 'categories', null, $cat->slug);
            }

            if ($author) {
                return $this->resolveCategoryArray($author->categories($cat->id), 'author', $author, $cat->slug);
            }

            if ($publisher) {
                return $this->resolveCategoryArray($publisher->categories($cat->id), 'publisher', $publisher, $cat->slug);
            }
        }

        return [];
    }

    private function resolveCategoryArray($categories, string $type, $target = null, ?string $parentSlug = null): array
    {
        return collect($categories)->map(function ($category) use ($type, $target, $parentSlug) {
            $item = is_array($category) ? $category : $category->toArray();

            return [
                'id' => $item['id'],
                'title' => LocaleHelper::localizedField($item, 'title'),
                'count' => $item['products_count'] ?? 0,
                'url' => $this->resolveCategoryUrl($item, $type, $target, $parentSlug),
            ];
        })->values()->all();
    }

    private function resolveCategoryUrl(array $category, string $type, $target = null, ?string $parentSlug = null): string
    {
        if ($type === 'author') {
            return LocaleHelper::route('catalog.route.author', [
                'author' => $target,
                'cat' => $parentSlug ?: $category['slug'],
                'subcat' => $parentSlug ? $category['slug'] : null,
            ]);
        }

        if ($type === 'publisher') {
            return LocaleHelper::route('catalog.route.publisher', [
                'publisher' => $target,
                'cat' => $parentSlug ?: $category['slug'],
                'subcat' => $parentSlug ? $category['slug'] : null,
            ]);
        }

        return LocaleHelper::route('catalog.route', [
            'group' => LocaleHelper::groupSlug((string) $category['group']),
            'cat' => $parentSlug ?: $category['slug'],
            'subcat' => $parentSlug ? $category['slug'] : null,
        ]);
    }

    private function normalizeIdsParam($ids): ?string
    {
        if ($ids === null || $ids === '' || $ids === [] || $ids === '[]') {
            return null;
        }

        if ($ids instanceof Collection) {
            $collection = $ids;
        } elseif (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $collection = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? collect($decoded)
                : collect(explode(',', trim($ids, '[]')));
        } else {
            $collection = collect($ids);
        }

        $normalized = $collection->flatten()->filter(function ($id) {
            return $id !== null && $id !== '';
        })->map(function ($id) {
            return is_numeric($id) ? (int) $id : $id;
        })->unique()->values();

        if ($normalized->isEmpty()) {
            return null;
        }

        return '[' . $normalized->implode(',') . ']';
    }

    private function resolveExactSkuIds(Request $request): Collection
    {
        $searchTerm = trim((string) $request->input(config('settings.search_keyword', 'pojam'), ''));

        if ($searchTerm === '') {
            return collect();
        }

        return Product::query()->active()->where('sku', $searchTerm)->pluck('id');
    }

    private function resolveSelectedAuthors(string $slugs): array
    {
        $items = collect(explode('+', $slugs))->filter()->values();

        if ($items->isEmpty()) {
            return [];
        }

        return Author::query()
            ->where(function (Builder $query) use ($items) {
                $query->whereIn('slug', $items)->orWhereIn('slug_en', $items);
            })
            ->get()
            ->sortBy(function (Author $author) use ($items) {
                $position = $items->search($author->slug);

                return $position === false ? $items->search($author->slug_en) : $position;
            })
            ->values()
            ->all();
    }

    private function resolveSelectedPublishers(string $slugs): array
    {
        $items = collect(explode('+', $slugs))->filter()->values();

        if ($items->isEmpty()) {
            return [];
        }

        return Publisher::query()
            ->where(function (Builder $query) use ($items) {
                $query->whereIn('slug', $items)->orWhereIn('slug_en', $items);
            })
            ->get()
            ->sortBy(function (Publisher $publisher) use ($items) {
                $position = $items->search($publisher->slug);

                return $position === false ? $items->search($publisher->slug_en) : $position;
            })
            ->values()
            ->all();
    }

    private function normalizeCategoryGroup(?string $group): ?string
    {
        if ($group) {
            return LocaleHelper::internalGroup($group);
        }

        if ($group === 'zemljovidi-i-vedute') {
            return 'Zemljovidi i vedute';
        }

        return $group;
    }
}
