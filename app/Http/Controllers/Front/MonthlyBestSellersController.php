<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonthlyBestSellersController extends Controller
{
    public function __invoke(Request $request, ProductRecommendationService $recommendations)
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 60;
        $sort = (string) $request->query('sort', 'best_selling');

        if (! in_array($sort, ['best_selling', 'novi', 'price_up', 'price_down', 'naziv_up', 'naziv_down'], true)) {
            $sort = 'best_selling';
        }

        $rankedProducts = $recommendations->recentBestSellers(
            ProductRecommendationService::BESTSELLER_DAYS,
            ProductRecommendationService::MONTHLY_COLLECTION_LIMIT
        );
        $badgeTypes = $recommendations->salesBadgeTypes($rankedProducts->pluck('id'));
        $rankedProducts = $rankedProducts
            ->filter(fn ($product) => $badgeTypes->get((int) $product->id))
            ->values();
        $rankedProducts = $this->sortProducts($rankedProducts, $sort);
        $products = new LengthAwarePaginator(
            $rankedProducts->forPage($page, $perPage)->values(),
            $rankedProducts->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );

        if ($page > 1 && $products->isEmpty()) {
            abort(404);
        }

        return view('front.catalog.featured.monthly-best-sellers', compact('products', 'sort'));
    }

    private function sortProducts(Collection $products, string $sort): Collection
    {
        switch ($sort) {
            case 'novi':
                return $products->sortByDesc(fn ($product) => optional($product->created_at)->getTimestamp() ?? 0)->values();
            case 'price_up':
                return $products->sortBy(fn ($product) => (float) $product->price)->values();
            case 'price_down':
                return $products->sortByDesc(fn ($product) => (float) $product->price)->values();
            case 'naziv_up':
                return $products->sortBy(fn ($product) => mb_strtolower((string) $product->name), SORT_NATURAL | SORT_FLAG_CASE)->values();
            case 'naziv_down':
                return $products->sortByDesc(fn ($product) => mb_strtolower((string) $product->name), SORT_NATURAL | SORT_FLAG_CASE)->values();
            default:
                return $products->values();
        }
    }
}
