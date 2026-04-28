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
    public function resolve(Request $request, $group, Category $cat = null, $subcat = null, Product $prod = null)
    {
        if ($subcat) {
            $sub_category = Category::where('slug', $subcat)->where('parent_id', $cat->id)->first();

            if (!$sub_category) {
                $prod = Product::where('slug', $subcat)->first();
            }

            $subcat = $sub_category;
        }

        if ($prod) {
            if (!$prod->status) {
                abort(404);
            }

            $prod->loadMissing(['author', 'publisher', 'images', 'action', 'categories']);

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
                    ->with(['author', 'action'])
                    ->get()
                    ->sortBy(fn ($p) => array_search($p->id, $recentIds))
                    ->values();
            }

            $relatedProducts = collect();
            if ($cat) {
                $relatedProducts = $cat->products()
                    ->where('products.id', '!=', $prod->id)
                    ->where('quantity', '>', 0)
                    ->with(['author', 'action'])
                    ->take(15)
                    ->get()
                    ->unique('id')
                    ->values();
            }

            $bc = new Breadcrumb();
            $crumbs = $bc->product($group, $cat, $subcat, $prod)->resolve();
            $bookscheme = $bc->productBookSchema($prod);

            $shipping_methods = Settings::getList('shipping', 'list.%', true);
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
                'recentProducts',
                'relatedProducts'
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

        $meta_tags = Seo::getMetaTags($request, 'filter');
        $crumbs = (new Breadcrumb())->category($group, $cat, $subcat)->resolve();

        return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'prod', 'crumbs', 'meta_tags'));
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

    public function resolveOldCategoryUrl(string $group = null, $cat = null, $subcat = null)
    {
        if ($group) {
            return redirect()->route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat]);
        }

        abort(404);
    }

    public function author(Request $request, Author $author = null, Category $cat = null, Category $subcat = null)
    {
        if (!$author) {
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
                    ->where('status', 1)
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
        $crumbs = null;

        return view('front.catalog.category.index', compact('author', 'letter', 'cat', 'subcat', 'seo', 'crumbs'));
    }

    public function publisher(Request $request, Publisher $publisher = null, Category $cat = null, Category $subcat = null)
    {
        if (!$publisher) {
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
                    ->where('status', 1)
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
        $crumbs = null;

        return view('front.catalog.category.index', compact('publisher', 'letter', 'cat', 'subcat', 'seo', 'crumbs'));
    }

    public function tag(Request $request)
    {
        $key = config('settings.search_keyword', 'pojam');
        $query = $request->input($key);

        if ($query === null) {
            return redirect()->back()->with(['error' => 'Nedostaje parametar pretrage.']);
        }
        if ($query === '') {
            return redirect()->back()->with(['error' => 'Oops..! Zaboravili ste upisati pojam za pretraživanje..!']);
        }

        $ids = Helper::getTags($query);
        $group = $cat = $subcat = $crumbs = null;

        return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'ids', 'crumbs'));
    }

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
                $path = '/' . $group . '/' . $slug;   // <<— koristi $group
                return [
                        'id'   => $c->id,
                    'name' => $c->title,               // JS očekuje "name"
                        'url'  => url($path),
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

        return response()->json(['error' => 'Greška kod pretrage..! Molimo pokušajte ponovo ili nas kotaktirajte! HVALA...']);
    }


    public function actions(Request $request, Category $cat = null, $subcat = null)
    {
        $ids = collect();
        $group = 'snizenja';
        $crumbs = null;

        return view('front.catalog.category.index', compact('group', 'cat', 'subcat', 'ids', 'crumbs'));
    }

    public function page(Page $page)
    {
        return view('front.page', compact('page'));
    }

    public function blog(Blog $blog)
    {
        if (!$blog->exists) {
            $blogs = Blog::active()->paginate(9);
            return view('front.blog', compact('blogs'));
        }

        $gdl = TagManager::getGoogleBlogDataLayer($blog);
        return view('front.blog', compact('blog', 'gdl'));
    }

    public function faq()
    {
        $faq = Faq::where('status', 1)->get();
        return view('front.faq', compact('faq'));
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
}
