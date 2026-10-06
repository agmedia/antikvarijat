<?php

namespace App\Services;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductDetailDataService
{
    private const CAROUSEL_LIMIT = 15;

    public function recordView(Product $product, Request $request): void
    {
        if ($request->attributes->get('stateless_crawler') === true) {
            return;
        }

        $timestamps = $product->timestamps;
        $product->timestamps = false;
        try {
            $product->increment('viewed');
        } finally {
            $product->timestamps = $timestamps;
        }
    }

    public function recommendations(Product $product, ?Category $category, bool $hasAuthor, bool $hasPublisher, array $recentIds): array
    {
        // Cache the selection, not product models: prices, stock and promotions remain live.
        $relatedIds = $category
            ? $this->rememberIds('category', (int) $category->id, fn () => $category->products()
                ->where('quantity', '>', 0)
                ->limit(self::CAROUSEL_LIMIT + 1)
                ->pluck('products.id'))
            : collect();
        $authorIds = $hasAuthor
            ? $this->rememberIds('author', (int) $product->author_id, fn () => $product->author->products()
                ->latest('products.created_at')
                ->limit(self::CAROUSEL_LIMIT + 1)
                ->pluck('products.id'))
            : collect();
        $publisherIds = $hasPublisher
            ? $this->rememberIds('publisher', (int) $product->publisher_id, fn () => $product->publisher->products()
                ->latest('products.created_at')
                ->limit(self::CAROUSEL_LIMIT + 1)
                ->pluck('products.id'))
            : collect();
        $selections = [
            'recentProducts' => collect($recentIds),
            'relatedProducts' => $relatedIds,
            'authorProducts' => $authorIds,
            'publisherProducts' => $publisherIds,
        ];
        $allIds = collect($selections)->flatten()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $product->id)
            ->unique()->values();
        $products = $allIds->isEmpty()
            ? collect()
            : Product::query()->whereIn('id', $allIds)->where('status', 1)
                ->withReviewSummary()
                ->with(['author', 'publisher', 'action', 'categories'])
                ->get()->keyBy('id');

        return collect($selections)->map(function (Collection $ids, string $name) use ($products, $product, $category) {
            return $ids->map(fn ($id) => $products->get((int) $id))
                ->filter(function ($candidate) use ($name, $product, $category) {
                    if (! $candidate) {
                        return false;
                    }

                    if ($name !== 'recentProducts' && (int) $candidate->quantity < 1) {
                        return false;
                    }

                    if ($name === 'relatedProducts' && ! $candidate->categories->contains('id', (int) $category->id)) {
                        return false;
                    }

                    if ($name === 'authorProducts' && (int) $candidate->author_id !== (int) $product->author_id) {
                        return false;
                    }

                    if ($name === 'publisherProducts' && (int) $candidate->publisher_id !== (int) $product->publisher_id) {
                        return false;
                    }

                    return ! in_array($name, ['authorProducts', 'publisherProducts'], true)
                        || (float) $candidate->price !== 0.0;
                })
                ->unique('id')->take(self::CAROUSEL_LIMIT)->values();
        })->all();
    }

    public function reviewStats(int $productId): array
    {
        $distribution = ProductReview::query()->approved()->where('product_id', $productId)
            ->selectRaw('rating, COUNT(*) AS review_count')
            ->groupBy('rating')->pluck('review_count', 'rating')
            ->map(fn ($count) => (int) $count);
        $count = $distribution->sum();
        $ratingTotal = $distribution->reduce(fn ($sum, $count, $rating) => $sum + (int) $rating * $count, 0);

        return [
            'count' => (int) $count,
            'average' => $count ? round($ratingTotal / $count, 2) : 0.0,
            'distribution' => array_replace(array_fill_keys(range(1, 5), 0), $distribution->all()),
        ];
    }

    private function rememberIds(string $type, int $id, callable $resolver): Collection
    {
        return collect(Cache::remember('product.detail.recommendation-ids.v1.' . $type . '.' . $id,
            now()->addMinutes(5), $resolver));
    }
}
