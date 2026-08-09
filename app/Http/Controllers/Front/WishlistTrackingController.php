<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\Wishlist;
use App\Models\Front\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WishlistTrackingController extends Controller
{
    public function __invoke(Request $request, Wishlist $wishlist)
    {
        if ($wishlist->sent
            && $wishlist->sent_at
            && Schema::hasColumn('wishlist', 'clicked_at')
            && Schema::hasColumn('wishlist', 'click_count')) {
            $updates = [
                'click_count' => DB::raw('click_count + 1'),
                'updated_at' => now(),
            ];

            if (! $wishlist->clicked_at) {
                $updates['clicked_at'] = now();
            }

            Wishlist::query()->whereKey($wishlist->id)->update($updates);
        }

        $locale = in_array($request->query('locale'), LocaleHelper::locales(), true)
            ? $request->query('locale')
            : LocaleHelper::DEFAULT_LOCALE;

        app()->setLocale($locale);
        config(['app.locale' => $locale]);

        $product = Product::query()->whereKey($wishlist->product_id)->active()->first();
        if (! $product) {
            return redirect()->to(LocaleHelper::route('index', [], true, $locale));
        }

        $productUrl = url($product->url);
        $query = http_build_query([
            'utm_source' => 'wishlist',
            'utm_medium' => 'email',
            'utm_campaign' => 'back_in_stock',
        ]);

        return redirect()->to($productUrl . (strpos($productUrl, '?') === false ? '?' : '&') . $query);
    }
}
