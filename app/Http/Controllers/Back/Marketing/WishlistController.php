<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = in_array($request->input('tab'), ['wishlists', 'top-products', 'statistics'], true)
            ? $request->input('tab')
            : 'wishlists';
        $search = trim((string) $request->input('search'));
        $stock = (string) $request->input('stock', '');

        $query = Wishlist::query()
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'sku', 'image', 'url', 'quantity', 'status');
            }]);

        if ($search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($stock === 'unsent') {
            $query->unsent();
        } elseif ($stock === 'sent') {
            $query->sent();
        } elseif ($stock === 'ready') {
            $query->active()->unsent()->whereHas('product', fn ($product) => $product->active()->available());
        } elseif ($stock === 'waiting') {
            $query->active()->unsent()->where(function ($waiting) {
                $waiting->whereDoesntHave('product')
                    ->orWhereHas('product', fn ($product) => $product->where('status', 0)->orWhere('quantity', '<=', 0));
            });
        }

        $topProducts = Wishlist::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN sent = 0 THEN 1 ELSE 0 END) as unsent_total, SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent_total')
            ->when($search !== '', function ($top) use ($search) {
                $top->whereHas('product', function ($product) use ($search) {
                    $product->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'sku', 'image', 'quantity', 'status');
            }])
            ->paginate(30, ['*'], 'top_page');

        $readyQuery = Wishlist::query()
            ->active()
            ->unsent()
            ->whereHas('product', fn ($product) => $product->active()->available());

        $stats = [
            'total' => Wishlist::query()->count(),
            'unsent' => Wishlist::query()->unsent()->count(),
            'ready' => (clone $readyQuery)->count(),
            'sent' => Wishlist::query()->sent()->count(),
            'unique_emails' => Wishlist::query()->selectRaw('LOWER(TRIM(email)) AS normalized_email')->distinct()->count('email'),
            'this_month' => Wishlist::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $wishlists = $query->orderByDesc('created_at')->paginate(30, ['*'], 'wishlist_page');

        return view('back.marketing.wishlist.index', compact(
            'activeTab',
            'search',
            'stock',
            'stats',
            'wishlists',
            'topProducts'
        ));
    }

    public function send(Wishlist $wishlist)
    {
        $result = $wishlist->sendNow();
        Cache::forget('admin.notification_counts');

        return back()->with($result['sent'] ? 'success' : 'error', $result['message']);
    }
}
