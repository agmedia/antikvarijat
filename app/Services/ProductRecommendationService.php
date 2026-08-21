<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use App\Models\Front\Catalog\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductRecommendationService
{
    public const BESTSELLER_DAYS = 30;

    public const BESTSELLER_LIMIT = 10;

    public const POPULAR_MIN_SOLD_QUANTITY = 5;

    public const MONTHLY_COLLECTION_LIMIT = 180;

    /** @var \Illuminate\Support\Collection<int, string|null>|null */
    private $salesBadgeLookup;

    public function recentBestSellers(int $days = 30, int $limit = 10, array $excludedProductIds = []): Collection
    {
        return $this->productsInOrder(
            $this->recentBestSellerIds($days, $limit, $excludedProductIds),
            $limit
        );
    }

    public function recentBestSellerIds(int $days = 30, int $limit = 10, array $excludedProductIds = []): Collection
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('orders') || ! Schema::hasTable('order_products')) {
            return collect();
        }

        $days = max(1, min($days, 365));
        $limit = max(1, min($limit, self::MONTHLY_COLLECTION_LIMIT));
        $excludedProductIds = array_values(array_unique(array_map(
            'intval',
            array_filter($excludedProductIds, static fn ($id) => (int) $id > 0)
        )));
        sort($excludedProductIds);
        $excludedAuthorIds = $this->excludedAuthorIds();
        $statuses = Order::dashboardCompletedStatusIds();
        $cacheKey = 'recommendations.recent-best-sellers.v1.' . sha1(json_encode([
            'days' => $days,
            'limit' => $limit,
            'statuses' => $statuses,
            'excluded_product_ids' => $excludedProductIds,
            'excluded_author_ids' => $excludedAuthorIds,
        ]));

        $productIds = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($days, $limit, $statuses, $excludedProductIds, $excludedAuthorIds) {
            $ranking = DB::table('order_products')
                ->join('orders', 'orders.id', '=', 'order_products.order_id')
                ->join('products', 'products.id', '=', 'order_products.product_id')
                ->whereIn('orders.order_status_id', $statuses)
                ->whereBetween('orders.created_at', [now()->subDays($days), now()])
                ->where('order_products.product_id', '>', 0)
                ->where('order_products.quantity', '>', 0)
                ->where('products.status', 1)
                ->where('products.quantity', '>', 0)
                ->where('products.price', '!=', 0)
                ->whereNotNull('products.image')
                ->where('products.image', '!=', '')
                ->select('order_products.product_id')
                ->selectRaw('SUM(order_products.quantity) AS sold_quantity')
                ->selectRaw('MAX(orders.created_at) AS last_sold_at')
                ->groupBy('order_products.product_id')
                ->orderByDesc('sold_quantity')
                ->orderByDesc('last_sold_at')
                ->orderByDesc('order_products.product_id');

            if (! empty($excludedProductIds)) {
                $ranking->whereNotIn('order_products.product_id', $excludedProductIds);
            }

            if (! empty($excludedAuthorIds)) {
                $ranking->whereNotIn('products.author_id', $excludedAuthorIds);
            }

            return $ranking->limit($limit)
                ->pluck('order_products.product_id')
                ->map(fn ($id) => (int) $id);
        });

        return collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function popularProductIds(int $minSoldQuantity = self::POPULAR_MIN_SOLD_QUANTITY): Collection
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_products')) {
            return collect();
        }

        $minSoldQuantity = max(1, $minSoldQuantity);
        $excludedAuthorIds = $this->excludedAuthorIds();
        $statuses = Order::dashboardCompletedStatusIds();
        $cacheKey = 'recommendations.popular-products.v1.' . sha1(json_encode([
            'min_sold_quantity' => $minSoldQuantity,
            'statuses' => $statuses,
            'excluded_author_ids' => $excludedAuthorIds,
        ]));

        $productIds = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($minSoldQuantity, $statuses, $excludedAuthorIds) {
            return DB::table('order_products')
                ->join('orders', 'orders.id', '=', 'order_products.order_id')
                ->join('products', 'products.id', '=', 'order_products.product_id')
                ->whereIn('orders.order_status_id', $statuses)
                ->where('order_products.product_id', '>', 0)
                ->where('order_products.quantity', '>', 0)
                ->when(! empty($excludedAuthorIds), function ($query) use ($excludedAuthorIds) {
                    $query->whereNotIn('products.author_id', $excludedAuthorIds);
                })
                ->select('order_products.product_id')
                ->groupBy('order_products.product_id')
                ->havingRaw('SUM(order_products.quantity) >= ?', [$minSoldQuantity])
                ->pluck('order_products.product_id');
        });

        return collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function salesBadgeTypes($productIds): Collection
    {
        $productIds = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        if ($this->salesBadgeLookup === null) {
            $this->salesBadgeLookup = $this->popularProductIds()
                ->mapWithKeys(fn (int $productId) => [$productId => 'popular']);

            foreach ($this->recentBestSellerIds(self::BESTSELLER_DAYS, self::BESTSELLER_LIMIT) as $productId) {
                $this->salesBadgeLookup->put((int) $productId, 'bestseller');
            }
        }

        return $productIds->mapWithKeys(function (int $productId) {
            return [$productId => $this->salesBadgeLookup->get($productId)];
        });
    }

    public function salesBadgeType(int $productId): ?string
    {
        return $this->salesBadgeTypes([$productId])->get($productId);
    }

    public function forUser(User $user, int $limit = 12): Collection
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('orders') || ! Schema::hasTable('order_products')) {
            return collect();
        }

        $orderIds = Order::query()
            ->whereIn('order_status_id', Order::reviewEligibleStatusIds())
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(payment_email) = ?', [mb_strtolower(trim((string) $user->email))]);
            })
            ->pluck('id');

        $purchasedProductIds = OrderProduct::query()
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '>', 0)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $candidateIds = collect();

        if ($purchasedProductIds->isNotEmpty()) {
            $relatedOrderIds = OrderProduct::query()
                ->whereIn('product_id', $purchasedProductIds)
                ->pluck('order_id')
                ->unique();

            $candidateIds = OrderProduct::query()
                ->select('product_id', DB::raw('COUNT(DISTINCT order_id) as recommendation_score'))
                ->whereIn('order_id', $relatedOrderIds)
                ->whereNotIn('product_id', $purchasedProductIds)
                ->where('product_id', '>', 0)
                ->groupBy('product_id')
                ->orderByDesc('recommendation_score')
                ->limit($limit * 3)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id);

            if (Schema::hasTable('product_category')) {
                $categoryIds = DB::table('product_category')
                    ->whereIn('product_id', $purchasedProductIds)
                    ->pluck('category_id')
                    ->unique();

                if ($categoryIds->isNotEmpty()) {
                    $categoryCandidates = DB::table('product_category')
                        ->select('product_id', DB::raw('COUNT(*) as category_score'))
                        ->whereIn('category_id', $categoryIds)
                        ->whereNotIn('product_id', $purchasedProductIds)
                        ->groupBy('product_id')
                        ->orderByDesc('category_score')
                        ->limit($limit * 3)
                        ->pluck('product_id')
                        ->map(fn ($id) => (int) $id);

                    $candidateIds = $candidateIds->merge($categoryCandidates);
                }
            }
        }

        $candidateIds = $candidateIds->unique()->values();
        $products = $this->productsInOrder($candidateIds, $limit);

        if ($products->count() < $limit) {
            $excludedIds = $products->pluck('id')->merge($purchasedProductIds)->unique();
            $fallback = $this->baseProductQuery()
                ->whereNotIn('id', $excludedIds)
                ->orderByDesc('viewed')
                ->orderByDesc('created_at')
                ->limit($limit - $products->count())
                ->get();

            $products = $products->concat($fallback);
        }

        return $products->take($limit)->values();
    }

    private function productsInOrder(Collection $ids, int $limit): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $positions = $ids->flip();

        return $this->baseProductQuery()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => $positions->get($product->id, PHP_INT_MAX))
            ->take($limit)
            ->values();
    }

    private function excludedAuthorIds(): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) config('settings.product_recommendations.excluded_author_ids', [])
        ))));
        sort($ids);

        return $ids;
    }

    private function baseProductQuery()
    {
        $query = Product::query()
            ->active()
            ->hasStock()
            ->hasImage()
            ->with(['author', 'action', 'categories']);

        if (Schema::hasTable('product_reviews')) {
            $query->withReviewSummary();
        }

        return $query;
    }
}
