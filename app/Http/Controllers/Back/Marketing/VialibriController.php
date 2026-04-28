<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Marketing\VialibriBook;
use App\Services\GoogleTranslateService;
use App\Services\VialibriFeedService;
use Illuminate\Http\Request;

class VialibriController extends Controller
{
    /**
     * @return \Illuminate\View\View
     */
    public function index(Request $request, VialibriFeedService $feedService)
    {
        $selectedBooks = VialibriBook::query()
            ->with([
                'product.author',
                'product.publisher',
                'product.categories',
                'product.images',
            ])
            ->orderByDesc('updated_at')
            ->paginate(20, ['*'], 'selected_page')
            ->appends($request->query());

        $exportableCount = $feedService->getExportableBooks()->count();

        return view('back.marketing.vialibri.index', compact(
            'selectedBooks',
            'exportableCount'
        ));
    }

    /**
     * @return \Illuminate\View\View
     */
    public function config(VialibriFeedService $feedService)
    {
        $accessCode = trim((string) config('services.vialibri.access_code', ''));
        $syncUrl = $accessCode !== ''
            ? route('vialibri.feed.sync', ['access_code' => $accessCode])
            : route('vialibri.feed.sync');
        $dataUrl = $accessCode !== ''
            ? route('vialibri.feed.data', ['access_code' => $accessCode])
            : route('vialibri.feed.data');
        $exportableCount = $feedService->getExportableBooks()->count();

        return view('back.marketing.vialibri.config', compact(
            'syncUrl',
            'dataUrl',
            'accessCode',
            'exportableCount'
        ));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        $query = trim((string) $request->input('query'));

        if (mb_strlen($query) < 3) {
            return response()->json([
                'items' => [],
            ]);
        }

        $products = Product::query()
            ->with(['author', 'categories'])
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('sku', 'like', '%' . $query . '%')
                    ->orWhereHas('author', function ($authorQuery) use ($query) {
                        $authorQuery->where('title', 'like', '%' . $query . '%');
                    });
            })
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        $selected = VialibriBook::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->keyBy('product_id');

        $items = $products->map(function (Product $product) use ($selected) {
            $selectedItem = $selected->get($product->id);
            $isSaleable = (bool) $product->status && (int) $product->quantity > 0 && (float) $product->price > 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'author_title' => optional($product->author)->title,
                'image_url' => $product->image ? asset($product->image) : asset('media/avatars/avatar0.jpg'),
                'price_text' => number_format((float) $product->price, 2, ',', '.') . ' EUR',
                'quantity' => (int) $product->quantity,
                'categories' => $product->categories->pluck('title')->values()->all(),
                'is_saleable' => $isSaleable,
                'is_added' => (bool) $selectedItem,
                'store_url' => route('vialibri.store', ['product' => $product]),
                'edit_url' => $selectedItem ? route('vialibri.edit', ['vialibriBook' => $selectedItem]) : null,
            ];
        })->values()->all();

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function edit(VialibriBook $vialibriBook, VialibriFeedService $feedService)
    {
        $vialibriBook->load([
            'product.author',
            'product.publisher',
            'product.categories',
            'product.images',
        ]);

        $product = $vialibriBook->product;
        $sourceDescription = $product ? $feedService->buildSourceDescription($product) : '';
        $translationIsOutdated = $vialibriBook->translated_at
            && $product
            && $product->updated_at
            && $product->updated_at->gt($vialibriBook->translated_at);

        return view('back.marketing.vialibri.edit', compact(
            'vialibriBook',
            'product',
            'sourceDescription',
            'translationIsOutdated'
        ));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Product $product)
    {
        $item = VialibriBook::firstOrCreate([
            'product_id' => $product->id,
        ]);

        if ($item->wasRecentlyCreated) {
            return redirect()->route('vialibri.edit', ['vialibriBook' => $item])
                ->with('success', 'Naslov je dodan u ViaLibri listu.');
        }

        return redirect()->route('vialibri.edit', ['vialibriBook' => $item])
            ->with('warning', 'Naslov je već u ViaLibri listi.');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, VialibriBook $vialibriBook)
    {
        $data = $request->validate([
            'translated_title' => 'nullable|string|max:255',
            'translated_description' => 'nullable|string',
            'edition' => 'nullable|string|max:255',
            'keywords' => 'nullable|string',
            'first_edition' => 'nullable|in:0,1',
            'signed' => 'nullable|in:0,1',
            'dust_jacket' => 'nullable|in:0,1',
        ]);

        $vialibriBook->update([
            'translated_title' => $this->nullableString($data['translated_title'] ?? null),
            'translated_description' => $this->nullableString($data['translated_description'] ?? null),
            'edition' => $this->nullableString($data['edition'] ?? null),
            'keywords' => $this->nullableString($data['keywords'] ?? null),
            'first_edition' => $this->nullableBoolean($request->input('first_edition')),
            'signed' => $this->nullableBoolean($request->input('signed')),
            'dust_jacket' => $this->nullableBoolean($request->input('dust_jacket')),
        ]);

        return redirect()->route('vialibri.index')->with('success', 'ViaLibri zapis je snimljen.');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function translate(VialibriBook $vialibriBook, GoogleTranslateService $translate, VialibriFeedService $feedService)
    {
        $product = $vialibriBook->product()->with(['author', 'publisher', 'categories', 'images'])->first();

        if (! $product) {
            return redirect()->back()->with('error', 'Povezani artikl nije pronađen.');
        }

        $titleTranslation = $translate->translateText((string) $product->name);

        if (! $titleTranslation['ok']) {
            return redirect()->back()->with('error', 'Prijevod naslova nije uspio: ' . $titleTranslation['error']);
        }

        $descriptionTranslation = $translate->translateText($feedService->buildSourceDescription($product));

        if (! $descriptionTranslation['ok']) {
            return redirect()->back()->with('error', 'Prijevod opisa nije uspio: ' . $descriptionTranslation['error']);
        }

        $vialibriBook->update([
            'translated_title' => $titleTranslation['text'],
            'translated_description' => $descriptionTranslation['text'],
            'translated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Google Translate prijevod je dohvaćen i snimljen.');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VialibriBook $vialibriBook)
    {
        $vialibriBook->delete();

        return redirect()->route('vialibri.index')->with('success', 'Naslov je maknut iz ViaLibri liste.');
    }

    /**
     * @return string|null
     */
    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return bool|null
     */
    private function nullableBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value === '1';
    }
}
