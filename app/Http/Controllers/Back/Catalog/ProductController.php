<?php

namespace App\Http\Controllers\Back\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductAction;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\Back\Catalog\Publisher;
use App\Models\Front\Catalog\Product as FrontProduct;
use App\Services\GoogleTranslateService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Exports\ProductsZeroQuantityExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request, Product $product)
    {
        // osnovni upit + subselecti
        $query = $product->filter($request)
            ->with(['categories', 'subcategories'])
            ->select('products.*')
            ->addSelect([
                // ID zadnje narudžbe u kojoj se artikal pojavio (po datumu stavke)
                'last_order_id' => DB::table('order_products')
                    ->whereColumn('order_products.product_id', 'products.id')
                    ->orderByDesc('order_products.created_at')
                    ->limit(1)
                    ->select('order_products.order_id'),

                // Datum te zadnje stavke (može ti koristiti u listi/tooltipu)
                'last_order_at' => DB::table('order_products')
                    ->whereColumn('order_products.product_id', 'products.id')
                    ->orderByDesc('order_products.created_at')
                    ->limit(1)
                    ->select('order_products.created_at'),
            ]);

        // Ako IMAŠ polje "number" na orders, možeš dodati i ovo:
        // ->addSelect([
        //     'last_order_number' => DB::table('order_products')
        //         ->join('orders', 'orders.id', '=', 'order_products.order_id')
        //         ->whereColumn('order_products.product_id', 'products.id')
        //         ->orderByDesc('order_products.created_at') // ili orders.created_at
        //         ->limit(1)
        //         ->select('orders.number'),
        // ])

        $products = $query->paginate(20)->appends($request->query());

        // postojeći filter with_action/without_action – idealno bi to prebacio u SQL,
        // ali zadržavam tvoju logiku; ako želiš, možeš primijeniti WHERE uvjete umjesto collect().
        if ($request->has('status')) {
            if ($request->input('status') == 'with_action' || $request->input('status') == 'without_action') {

                // Napravi bazni upit opet sa subselectima (bez full-load u memoriju)
                $base = Product::query()
                    ->with(['categories', 'subcategories'])
                    ->select('products.*')
                    ->addSelect([
                        'last_order_id' => DB::table('order_products')
                            ->whereColumn('order_products.product_id', 'products.id')
                            ->orderByDesc('order_products.created_at')
                            ->limit(1)
                            ->select('order_products.order_id'),
                        'last_order_at' => DB::table('order_products')
                            ->whereColumn('order_products.product_id', 'products.id')
                            ->orderByDesc('order_products.created_at')
                            ->limit(1)
                            ->select('order_products.created_at'),
                    ]);

                if ($request->input('status') === 'with_action') {
                    $base->whereNotNull('special')
                        ->where('special', '>', 0)
                        ->where(function ($q) {
                            $q->whereNull('special_from')->orWhere('special_from', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('special_to')->orWhere('special_to', '>=', now());
                        });
                } else {
                    $base->where(function ($q) {
                        $q->whereNull('special')
                            ->orWhere('special', '<=', 0)
                            ->orWhere('special_from', '>', now())
                            ->orWhere('special_to', '<', now());
                    });
                }

                $products = $base->paginate(20)->appends($request->query());
            }
        }

        $categories = (new Category())->getList(false);
        $counts = [];

        return view('back.catalog.product.index', compact('products', 'categories', 'counts'));
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $product = new Product();
        $logs = collect();
        $data = $product->getRelationsData(false);
        $active_actions = ProductAction::active()->get();
        $selectedCategoryIds = [];
        $selectedSubcategoryId = null;
        $existingImagesCount = 0;

        $allTags = $this->getAllTags();

        return view('back.catalog.product.edit', compact(
            'data',
            'active_actions',
            'allTags',
            'logs',
            'selectedCategoryIds',
            'selectedSubcategoryId',
            'existingImagesCount'
        ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = new Product();

        $stored = $product->validateRequest($request)->create();

        if ($stored) {
            $product->checkSettings()
                    ->storeImages($stored);

            Cache::forget('admin.products.all_tags');
            $this->refreshFrontendProductCache($stored);

            return redirect()->route('products.edit', ['product' => $stored])->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Product $product
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $data = $product->getRelationsData(false);

        $logs = $product->historyLogs()->with('user:id,name')->get();
        $selectedCategoryIds = $product->categories()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedSubcategoryId = optional($product->subcategory())->id;
        $existingImagesCount = ProductImage::where('product_id', $product->id)->count();
        $allTags = $this->getAllTags();

        return view('back.catalog.product.edit', compact(
            'product',
            'data',
            'logs',
            'allTags',
            'selectedCategoryIds',
            'selectedSubcategoryId',
            'existingImagesCount'
        ));
    }

    public function photos(Product $product)
    {
        $images = ProductImage::getAdminList($product->id);

        return view('back.catalog.product.partials.existing-photos', compact('product', 'images'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Product                  $product
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $originalSlug = $product->slug;

        $updated = $product->validateRequest($request)->edit();

        if ($updated) {
            $product->checkSettings()
                    ->storeImages($updated);

            $product->addHistoryData('change');
            Cache::forget('admin.products.all_tags');
            $this->refreshFrontendProductCache($updated, $originalSlug);

            return redirect()->route('products.edit', ['product' => $updated])->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }

    public function translateDescription(Request $request, GoogleTranslateService $translate)
    {
        $data = $request->validate([
            'description' => ['required', 'string'],
        ]);

        if (! $translate->hasOfficialApiKey() && ! $translate->isPublicEndpointEnabled()) {
            return response()->json([
                'message' => 'Google Translate nije konfiguriran. Postavite GOOGLE_TRANSLATE_API_KEY u .env.',
            ], 422);
        }

        $result = $translate->translateText($data['description'], 'hr', 'en', 'html');

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['error'] ?? 'Prijevod nije uspio.',
            ], 422);
        }

        return response()->json([
            'text' => $result['text'],
            'provider' => $result['provider'] ?? null,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Product $product)
    {
        $originalSlug = $product->slug;

        ProductImage::where('product_id', $product->id)->delete();
        ProductCategory::where('product_id', $product->id)->delete();

        Storage::deleteDirectory(config('filesystems.disks.products.root') . $product->id);

        $destroyed = Product::destroy($product->id);

        if ($destroyed) {
            Cache::forget('admin.products.all_tags');
            $this->refreshFrontendProductCache($product, $originalSlug);
            return redirect()->route('products')->with(['success' => 'Artikl je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Ops..! Greška prilikom snimanja.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApi(Request $request)
    {
        if ($request->has('id')) {
            $id = $request->input('id');
            $product = Product::query()->find($id);
            $originalSlug = optional($product)->slug;

            ProductImage::where('product_id', $id)->delete();
            ProductCategory::where('product_id', $id)->delete();

            Storage::deleteDirectory(config('filesystems.disks.products.root') . $id);

            $destroyed = Product::destroy($id);

            if ($destroyed) {
                Cache::forget('admin.products.all_tags');
                if ($product) {
                    $this->refreshFrontendProductCache($product, $originalSlug);
                }
                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 300]);
    }


    /**
     * @param       $items
     * @param int   $perPage
     * @param null  $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginateColl($items, $perPage = 20, $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function exportZero()
    {
        $fileName = 'products_zero_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new ProductsZeroQuantityExport(), $fileName);
    }

    private function refreshFrontendProductCache(Product $product, ?string $originalSlug = null): void
    {
        Cache::store(config('cache.default'))->forget('front.shared.products_count');

        FrontProduct::forgetCachedRouteBinding($originalSlug);
        FrontProduct::forgetCachedRouteBinding($product->slug);
    }

    private function getAllTags(): Collection
    {
        return Cache::remember('admin.products.all_tags', now()->addMinutes(30), function () {
            return Product::query()
                ->select('tags')
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->map(fn ($tag) => mb_strtolower(trim((string) $tag)))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        });
    }
}
