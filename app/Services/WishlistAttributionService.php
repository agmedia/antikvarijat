<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WishlistAttributionService
{
    public function statistics(): array
    {
        $empty = $this->emptyStatistics();

        if (! Schema::hasTable('wishlist')
            || ! Schema::hasTable('orders')
            || ! Schema::hasTable('order_products')
            || ! Schema::hasColumn('wishlist', 'sent_at')) {
            return $empty;
        }

        $attributionDays = max(1, (int) config('wishlist.attribution_days', 30));
        $hasClickTracking = Schema::hasColumn('wishlist', 'clicked_at');
        $trackedSends = DB::table('wishlist')
            ->where('sent', 1)
            ->whereNotNull('sent_at')
            ->count();
        $clicked = $hasClickTracking
            ? DB::table('wishlist')->where('sent', 1)->whereNotNull('clicked_at')->count()
            : 0;

        $matches = DB::table('wishlist as wishlist')
            ->join('orders as orders', function ($join) {
                $join->whereRaw('LOWER(TRIM(orders.payment_email)) = LOWER(TRIM(wishlist.email))');
            })
            ->join('order_products as order_products', function ($join) {
                $join->on('order_products.order_id', '=', 'orders.id')
                    ->on('order_products.product_id', '=', 'wishlist.product_id');
            })
            ->where('wishlist.sent', 1)
            ->whereNotNull('wishlist.sent_at')
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->whereColumn('orders.created_at', '>=', 'wishlist.sent_at')
            ->select([
                'wishlist.id as wishlist_id',
                'wishlist.sent_at',
                'orders.id as order_id',
                'orders.created_at as order_created_at',
                'order_products.id as order_product_id',
                'order_products.quantity',
                'order_products.total',
            ])
            ->when($hasClickTracking, function ($query) {
                $query->addSelect('wishlist.clicked_at');
            }, function ($query) {
                $query->selectRaw('NULL AS clicked_at');
            })
            ->get();

        $validMatches = $matches->filter(function ($match) use ($attributionDays) {
            $sentAt = Carbon::parse($match->sent_at);
            $orderAt = Carbon::parse($match->order_created_at);

            return $orderAt->lessThanOrEqualTo($sentAt->copy()->addDays($attributionDays));
        });

        // One purchased line can be attributed to only one wishlist message.
        // If legacy duplicate rows exist, the most recently sent matching message wins.
        $attributedLines = $validMatches
            ->sortByDesc(function ($match) {
                return Carbon::parse($match->sent_at)->timestamp;
            })
            ->unique('order_product_id')
            ->values();

        $clickedLines = $attributedLines->filter(function ($match) use ($attributionDays) {
            if (! $match->clicked_at) {
                return false;
            }

            $clickedAt = Carbon::parse($match->clicked_at);
            $orderAt = Carbon::parse($match->order_created_at);

            return $orderAt->greaterThanOrEqualTo($clickedAt)
                && $orderAt->lessThanOrEqualTo($clickedAt->copy()->addDays($attributionDays));
        });

        $convertedMessages = $attributedLines->unique('wishlist_id')->count();

        return [
            'days' => $attributionDays,
            'tracked_sends' => $trackedSends,
            'clicked' => $clicked,
            'click_rate' => $this->percentage($clicked, $trackedSends),
            'converted_messages' => $convertedMessages,
            'conversion_rate' => $this->percentage($convertedMessages, $trackedSends),
            'orders_after_send' => $attributedLines->unique('order_id')->count(),
            'orders_after_click' => $clickedLines->unique('order_id')->count(),
            'items_after_send' => (int) $attributedLines->sum('quantity'),
            'revenue_after_send' => round((float) $attributedLines->sum('total'), 2),
        ];
    }

    private function percentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }

    private function emptyStatistics(): array
    {
        return [
            'days' => max(1, (int) config('wishlist.attribution_days', 30)),
            'tracked_sends' => 0,
            'clicked' => 0,
            'click_rate' => 0.0,
            'converted_messages' => 0,
            'conversion_rate' => 0.0,
            'orders_after_send' => 0,
            'orders_after_click' => 0,
            'items_after_send' => 0,
            'revenue_after_send' => 0.0,
        ];
    }
}
