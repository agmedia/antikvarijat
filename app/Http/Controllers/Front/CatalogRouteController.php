<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Breadcrumb;
use App\Helpers\Helper;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CatalogRouteController extends Controller
{

    /**
     * Resolver for the Groups, categories and products routes.
     * Route::get('{group}/{cat?}/{subcat?}/{prod?}', 'Front\GCP_RouteController::resolve()')->name('gcp_route');
     *
     * @param               $group
     * @param Category|null $cat
     * @param Category|null $subcat
     * @param Product|null  $prod
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function resolve(Request $request, $group, Category $cat = null, $subcat = null, Product $prod = null)
    {
        //
        if ($subcat) {
            $sub_category = Category::where('slug', $subcat)->where('parent_id', $cat->id)->first();

            if ( ! $sub_category) {
                $prod = Product::where('slug', $subcat)->first();
            }

            $subcat = $sub_category;
        }

        // Check if there is Product set.
        if ($prod) {
            if ( ! $prod->status) {
                abort(404);
            }

            $seo = Seo::getProductData($prod);
            $gdl = TagManager::getGoogleProductDataLayer($prod);

            // --- Recently viewed (session) ---
            $recent = collect(session('recent_products', []));

        // Uvijek stavi trenutni proizvod na početak, bez duplikata
            $recent = $recent->prepend($prod->id)->unique()->values();

        // (Opcionalno) ne čuvaj beskonačno – npr. do 50 posljednjih
            $recent = $recent->take(50);
            session(['recent_products' => $recent->all()]);

            // Za prikaz: uzmi do 15 posljednjih, isključi trenutni proizvod
            $recentIds = $recent->filter(fn ($id) => (int)$id !== (int)$prod->id)
                ->take(15)
                ->values()
                ->all();

            // Dohvati proizvode tim redoslijedom
            $recentProducts = collect();
            if (!empty($recentIds)) {
                $recentProducts = Product::whereIn('id', $recentIds)
                    ->where('status', 1)
                    ->get()
                    // sortBy prema redoslijedu u $recentIds
                    ->sortBy(fn ($p) => array_search($p->id, $recentIds))
                    ->values();
            }


            $bc = new Breadcrumb();
            $crumbs = $bc->product($group, $cat, $subcat, $prod)->resolve();
            $bookscheme = $bc->productBookSchema($prod);

            $shipping_methods = Settings::getList('shipping', 'list.%', true);
            $payment_methods = Settings::getList('payment', 'list.%', true);

            return view('front.catalog.product.index', compact('prod', 'group', 'cat', 'subcat', 'seo', 'crumbs', 'bookscheme','shipping_methods','payment_methods', 'gdl', 'recentProducts'));
        }

        // If only group...
        if ($group && ! $cat && ! $subcat) {
            if ($group == 'zemljovidi-i-vedute') {
                $group = 'Zemljovidi i vedute';
            }

            $categories = Category::where('group', $group)->first('id');

            if ( ! $categories) {
                abort(404);
            }
        }

        if ($cat) {
            $cat->count = $cat->products()->count();
        }
        if ($subcat) {
            $subcat->count = $subcat->products()->count();
        }

        $meta_tags = Seo::getMetaTags($request, 'filter');

        $crumbs = (new Breadcrumb())->category($group, $cat, $subcat)->resolve();

        return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'prod', 'crumbs', 'meta_tags'));
    }


    /**
     * @param null $prod
     *
     * @return \Illuminate\Http\RedirectResponse
     */
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


    /**
     * @param null $prod
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolveOldCategoryUrl(string $group = null, $cat = null, $subcat = null)
    {
        if ($group) {
            return redirect()->route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat]);
        }

        abort(404);
    }


    /**
     *
     *
     * @param Author $author
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function author(Request $request, Author $author = null, Category $cat = null, Category $subcat = null)
    {
        if ( ! $author) {
            $letters = Helper::resolveCache('authors')->remember('letters', config('cache.life'), function () {
                return Author::letters();
            });
            $letter = $this->checkLetter($letters);

            if ($request->has('letter')) {
                $letter = $request->input('letter');
            }

            $currentPage = request()->get('page', 1);

            $authors = Helper::resolveCache('authors')->remember($letter . '.' . $currentPage, config('cache.life'), function () use ($letter) {
                return Author::query()->select('id', 'title', 'url')
                                      ->where('status',  1)
                                      ->where('letter', $letter)
                                      ->orderBy('title')
                                      ->withCount('products')
                                      ->paginate(36)
                                      ->appends(request()->query());
            });

            $meta_tags = Seo::getMetaTags($request, 'ap_filter');

            return view('front.catalog.authors.index', compact('authors', 'letters', 'letter', 'meta_tags'));
        }

        $letter = null;

        if ($cat) { $cat->count = $cat->products()->count(); }
        if ($subcat) { $subcat->count = $subcat->products()->count(); }

        $seo = Seo::getAuthorData($author, $cat, $subcat);

        $crumbs = null;

        return view('front.catalog.category.index', compact('author', 'letter', 'cat', 'subcat', 'seo', 'crumbs'));
    }


    /**
     *
     *
     * @param Publisher $publisher
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function publisher(Request $request, Publisher $publisher = null, Category $cat = null, Category $subcat = null)
    {
        if ( ! $publisher) {
            $letters = Helper::resolveCache('publishers')->remember('letters', config('cache.life'), function () {
                return Publisher::letters();
            });
            $letter = $this->checkLetter($letters);

            if ($request->has('letter')) {
                $letter = $request->input('letter');
            }

            $currentPage = request()->get('page', 1);

            $publishers = Helper::resolveCache('publishers')->remember($letter . '.' . $currentPage, config('cache.life'), function () use ($letter) {
                return Publisher::query()->select('id', 'title', 'url')
                                         ->where('status',  1)
                                         ->where('letter', $letter)
                                         ->orderBy('title')
                                         ->withCount('products')
                                         ->paginate(36)
                                         ->appends(request()->query());
            });

            $meta_tags = Seo::getMetaTags($request, 'ap_filter');

            return view('front.catalog.publishers.index', compact('publishers', 'letters', 'letter', 'meta_tags'));
        }

        $letter = null;

        if ($cat) { $cat->count = $cat->products()->count(); }
        if ($subcat) { $subcat->count = $subcat->products()->count(); }

        $seo = Seo::getPublisherData($publisher, $cat, $subcat);

        $crumbs = null;

        return view('front.catalog.category.index', compact('publisher', 'letter', 'cat', 'subcat', 'seo', 'crumbs'));
    }


    /**
     *
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
   /* public function search(Request $request)
    {
        if ($request->has(config('settings.search_keyword'))) {
            if ( ! $request->input(config('settings.search_keyword'))) {
                return redirect()->back()->with(['error' => 'Oops..! Zaboravili ste upisati pojam za pretraživanje..!']);
            }

            $group = null; $cat = null; $subcat = null;

            $ids = Helper::search(
                $request->input(config('settings.search_keyword'))
            );

            $crumbs = null;

            return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'ids', 'crumbs'));
        }

        if ($request->has(config('settings.search_keyword') . '_api')) {
            $search = Helper::search(
                $request->input(config('settings.search_keyword') . '_api')
            );

            return response()->json($search);
        }

        return response()->json(['error' => 'Greška kod pretrage..! Molimo pokušajte ponovo ili nas kotaktirajte! HVALA...']);
    }*/



    public function search(Request $request)
    {
        // web stranica s rezultatima (ne diramo)
        if ($request->has(config('settings.search_keyword'))) {
            if (!$request->input(config('settings.search_keyword'))) {
                return redirect()->back()->with(['error' => 'Oops..! Zaboravili ste upisati pojam za pretraživanje..!']);
            }

            $group = null; $cat = null; $subcat = null;

            $ids = Helper::search(
                $request->input(config('settings.search_keyword'))
            );

            $crumbs = null;

            return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'ids', 'crumbs'));
        }

        // API autocomplete – structured JSON: counts + products + categories
        // API autocomplete – structured JSON: counts + products + categories
        if ($request->has(config('settings.search_keyword') . '_api')) {

            $q = (string) $request->input(config('settings.search_keyword') . '_api', '');

            // >>> UZMI $group IZ REQUESTA ILI STAVI DEFAULT
            $group = trim((string) $request->input('group', 'kategorija'), '/');

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
                $path = '/' . $group . '/' . $slug;   // <<— koristi $group
                return [
                    'id'   => $c->id,
                    'name' => $c->title,               // JS očekuje "name"
                    'url'  => url($path),
                ];
            })->values()->all();

            // --- STRUCTURED PAYLOAD + X-Total-Count ---
            $payload = [
                'counts'     => [
                    'products'   => $totalProducts,
                    'authors'    => 0,
                    'categories' => $totalCategories,
                ],
                'products'   => $productsPayload,
                'categories' => $categoriesPayload,
            ];

            $totalAll = $payload['counts']['products']
                + $payload['counts']['authors']
                + $payload['counts']['categories'];

            return response()->json($payload)
                ->header('X-Total-Count', $totalAll);
        }


        return response()->json(['error' => 'Greška kod pretrage..! Molimo pokušajte ponovo ili nas kotaktirajte! HVALA...']);
    }



    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function actions(Request $request, Category $cat = null, $subcat = null)
    {
        $ids = collect();
        $group = 'snizenja';

        $crumbs = null;

        return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'ids', 'crumbs'));
    }


    /**
     * @param Page $page
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function page(Page $page)
    {
        return view('front.page', compact('page'));
    }


    /**
     * @param Blog $blog
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function blog(Blog $blog)
    {
        if (! $blog->exists) {
            $blogs = Blog::active()->paginate(9);

            return view('front.blog', compact('blogs'));
        }

        $gdl = TagManager::getGoogleBlogDataLayer($blog);

        return view('front.blog', compact('blog', 'gdl'));
    }


    /**
     * @param Faq $faq
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function faq()
    {
        $faq = Faq::where('status', 1)->get();
        return view('front.faq', compact('faq'));
    }


    /**
     * @param array $letters
     *
     * @return string
     */
    private function checkLetter(Collection $letters): string
    {
        foreach ($letters->all() as $letter) {
            if ($letter['active']) {
                return $letter['value'];
            }
        }

        return 'A';
    }

}
