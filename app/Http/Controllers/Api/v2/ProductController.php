<?php

namespace App\Http\Controllers\Api\v2;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductImage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private const QUICK_EDIT_FIELDS = [
        'polica',
        'skl',
        'year',
        'dimensions',
        'price',
        'quantity',
    ];

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        if (is_string($request->input('query'))) {
            $request->merge(['query' => trim($request->input('query'))]);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $term = $validated['query'];
        $columns = ['id', 'name', 'sku', 'price'];

        // A complete product code is the common admin use case and can use the
        // existing SKU index without scanning the catalogue.
        $exactProduct = Product::query()
            ->select($columns)
            ->where('sku', $term)
            ->first();

        if ($exactProduct) {
            return response()->json([$exactProduct]);
        }

        // Treat LIKE metacharacters as ordinary input. The explicit escape
        // character works consistently with both MySQL and the SQLite tests.
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';

        $products = Product::query()
            ->select($columns)
            ->where(function ($query) use ($like) {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$like]);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(20)
            ->get();

        return response()->json($products);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyImage(Request $request)
    {
        $image = ProductImage::where('id', $request->input('data'))->first();

        if (isset($image->image)) {
            $path = str_replace(config('filesystems.disks.products.url'), '', $image->image);
            // Obriši staru sliku
            Storage::disk('products')->delete($path);

            if (ProductImage::where('id', $request->input('data'))->delete()) {
                ProductImage::where('image', $image->image)->delete();

                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 400]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(Request $request)
    {
        if ($request->has('id')) {
            $product = Product::query()->find($request->input('id'));

            if ($product) {
                if ($request->input('value')) {
                    $product->status = 1;
                    $product->quantity = $product->quantity ?: 1;
                } else {
                    $product->status = 0;
                    $product->quantity = 0;
                }

                if ($product->isDirty(['status', 'quantity'])) {
                    DB::transaction(function () use ($product) {
                        $oldProduct = $product->historySnapshot();

                        $product->save();
                        $product->refresh()->addHistoryData('change', $oldProduct);
                    });
                }

                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 400]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request)
    {
        if ($request->has('product')) {
            $payload = $request->input('product');
            $target = $payload['target'] ?? null;
            $productId = $payload['item']['id'] ?? null;

            if (! in_array($target, self::QUICK_EDIT_FIELDS, true) || ! $productId) {
                return response()->json(['error' => 422], 422);
            }

            $product = Product::query()->find($productId);

            if (! $product) {
                return response()->json(['error' => 404], 404);
            }

            $currentValue = $product->getAttribute($target);
            $newValue = $payload['new_value'] ?? null;

            if ($newValue === '...') {
                $newValue = '';
            }

            if (in_array($target, ['skl', 'quantity'], true)) {
                $currentValue = ($currentValue === null || $currentValue === '') ? null : (int) $currentValue;
                $newValue = $newValue === null ? null : trim((string) $newValue);

                if ($newValue !== null && $newValue !== '' && ! ctype_digit($newValue)) {
                    return response()->json(['error' => 422], 422);
                }

                $newValue = ($newValue === null || $newValue === '')
                    ? ($target === 'quantity' ? 0 : null)
                    : (int) $newValue;
            }

            if ($target === 'price') {
                if (! is_numeric($newValue) || (float) $newValue < 0) {
                    return response()->json(['error' => 422], 422);
                }

                $newValue = (float) $newValue;
            }

            $hasChanged = $target === 'skl'
                ? $currentValue !== $newValue
                : $currentValue != $newValue;

            if ($hasChanged) {
                $updates = [$target => $newValue];

                if ($target === 'price' && ! empty($payload['item']['special'])) {
                    $discount = Helper::calculateDiscount($product->price, $product->special);
                    $new_special = Helper::calculateDiscountPrice($newValue, $discount);
                    $updates['special'] = $new_special;
                }

                DB::transaction(function () use ($product, $updates) {
                    $oldProduct = $product->historySnapshot();

                    $product->update($updates);
                    $product->refresh()->addHistoryData('change', $oldProduct);
                });

                return response()->json([
                    'success' => 200,
                    'value_1' => $newValue,
                    'value_2' => isset($new_special) ? $new_special : null
                ]);
            }
        }

        return response()->json(['error' => 300]);
    }
}
