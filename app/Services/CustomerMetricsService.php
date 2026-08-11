<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use Illuminate\Support\Facades\DB;

class CustomerMetricsService
{
    /**
     * Count people who placed at least one valid order, including guest buyers.
     */
    public function uniqueBuyers(): int
    {
        $emails = DB::table('orders')
            ->selectRaw('LOWER(TRIM(payment_email)) as normalized_email')
            ->whereIn('order_status_id', Order::dashboardCompletedStatusIds())
            ->whereNotNull('payment_email')
            ->whereRaw("TRIM(payment_email) <> ''")
            ->distinct();

        return (int) DB::query()
            ->fromSub($emails, 'unique_buyers')
            ->count();
    }
}
