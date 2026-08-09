<?php

namespace App\Http\Controllers\Back;

use App\Helpers\Country;
use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\Back\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatisticsController extends Controller
{
    public function index()
    {
        $latestOrderDate = Order::query()->max('created_at');
        $defaultTo = $latestOrderDate ? Carbon::parse($latestOrderDate) : Carbon::today();
        $defaultTo = $defaultTo->isFuture() ? Carbon::today() : $defaultTo;
        $defaultFrom = $defaultTo->copy()->subDays(29);

        return view('back.statistics.index', compact('defaultFrom', 'defaultTo'));
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['to'])->endOfDay();
        $days = $from->diffInDays($to) + 1;
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $orders = $this->salesOrders($from, $to);
        $previousOrders = $this->salesOrders($previousFrom, $previousTo);
        $summary = $this->summary($orders, $from, $to);
        $previousSummary = $this->summary($previousOrders, $previousFrom, $previousTo);

        return response()->json([
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
                'previous_from' => $previousFrom->toDateString(),
                'previous_to' => $previousTo->toDateString(),
            ],
            'summary' => $summary,
            'comparison' => $this->comparison($summary, $previousSummary),
            'trend' => $this->trend($from, $to, $days),
            'heatmap' => $this->heatmap($from, $to),
            'geography' => $this->geography($from, $to),
            'products' => $this->productAnalytics($from, $to),
            'customers' => $this->customerAnalytics($from, $to),
            'operations' => $this->operations($from, $to),
        ]);
    }

    private function salesOrders(Carbon $from, Carbon $to): Builder
    {
        return DB::table('orders')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds());
    }

    private function summary(Builder $orders, Carbon $from, Carbon $to): array
    {
        $stats = (clone $orders)
            ->selectRaw('COUNT(orders.id) as orders, COALESCE(SUM(orders.total), 0) as total')
            ->first();
        $orderCount = (int) ($stats->orders ?? 0);
        $total = (float) ($stats->total ?? 0);
        $items = (int) DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->where('order_products.product_id', '>', 0)
            ->sum('order_products.quantity');
        $emailExpression = $this->normalizedText('orders.payment_email');
        $customers = (int) (clone $orders)
            ->whereNotNull('orders.payment_email')
            ->whereRaw("TRIM(orders.payment_email) <> ''")
            ->distinct()
            ->count(DB::raw($emailExpression));

        return [
            'total' => $total,
            'orders' => $orderCount,
            'items' => $items,
            'average_order' => $orderCount ? round($total / $orderCount, 2) : 0.0,
            'average_items' => $orderCount ? round($items / $orderCount, 2) : 0.0,
            'customers' => $customers,
        ];
    }

    private function comparison(array $current, array $previous): array
    {
        $result = [];

        foreach (['total', 'orders', 'items', 'average_order', 'average_items', 'customers'] as $metric) {
            $old = (float) ($previous[$metric] ?? 0);
            $new = (float) ($current[$metric] ?? 0);
            $result[$metric] = [
                'value' => $previous[$metric] ?? 0,
                'change' => $old == 0.0 ? ($new == 0.0 ? 0.0 : null) : round((($new - $old) / abs($old)) * 100, 1),
            ];
        }

        return $result;
    }

    private function trend(Carbon $from, Carbon $to, int $days): array
    {
        $group = $days <= 120 ? 'day' : 'month';
        $periodExpression = $this->dateBucket('orders.created_at', $group);

        $orders = $this->salesOrders($from, $to)
            ->selectRaw("{$periodExpression} as period, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->groupByRaw($periodExpression)
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $items = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->where('order_products.product_id', '>', 0)
            ->selectRaw("{$periodExpression} as period, SUM(order_products.quantity) as items")
            ->groupByRaw($periodExpression)
            ->pluck('items', 'period');

        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $series = [];

        while ($cursor <= $end) {
            $key = $group === 'day' ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $row = $orders->get($key);
            $series[] = [
                'period' => $key,
                'label' => $group === 'day' ? $cursor->format('d.m.') : $cursor->translatedFormat('M Y.'),
                'orders' => (int) ($row->orders ?? 0),
                'total' => (float) ($row->total ?? 0),
                'items' => (int) ($items[$key] ?? 0),
            ];
            $cursor = $group === 'day' ? $cursor->addDay() : $cursor->addMonth()->startOfMonth();
        }

        return ['group' => $group, 'series' => $series];
    }

    private function heatmap(Carbon $from, Carbon $to): array
    {
        $weekday = $this->weekdayExpression('orders.created_at');
        $hour = $this->hourExpression('orders.created_at');

        return $this->salesOrders($from, $to)
            ->selectRaw("{$weekday} as weekday, {$hour} as hour, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->groupByRaw("{$weekday}, {$hour}")
            ->orderBy('weekday')
            ->orderBy('hour')
            ->get()
            ->map(static function ($row) {
                return [
                    'weekday' => (int) $row->weekday,
                    'hour' => (int) $row->hour,
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    private function geography(Carbon $from, Carbon $to): array
    {
        $countryExpression = $this->normalizedText('orders.shipping_state');
        $countryItems = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->where('order_products.product_id', '>', 0)
            ->selectRaw("{$countryExpression} as location_key, SUM(order_products.quantity) as items")
            ->groupByRaw($countryExpression)
            ->pluck('items', 'location_key');

        $countries = $this->salesOrders($from, $to)
            ->selectRaw("{$countryExpression} as location_key, MAX(TRIM(orders.shipping_state)) as label, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->whereNotNull('orders.shipping_state')
            ->whereRaw("TRIM(orders.shipping_state) <> ''")
            ->groupByRaw($countryExpression)
            ->orderByDesc('total')
            ->get();

        $countryCodes = $this->countryCodeMap();
        $countryRows = $countries->map(function ($row) use ($countryCodes, $countryItems) {
            $key = (string) $row->location_key;
            $orders = (int) $row->orders;

            return [
                'name' => (string) $row->label,
                'code' => $countryCodes[$key] ?? null,
                'orders' => $orders,
                'total' => (float) $row->total,
                'items' => (int) ($countryItems[$key] ?? 0),
                'average_order' => $orders ? round(((float) $row->total) / $orders, 2) : 0.0,
            ];
        })->values();

        $cityExpression = $this->normalizedText('orders.shipping_city');
        $cityRows = $this->salesOrders($from, $to)
            ->whereRaw("{$countryExpression} IN (?, ?)", ['croatia', 'hrvatska'])
            ->whereNotNull('orders.shipping_city')
            ->whereRaw("TRIM(orders.shipping_city) <> ''")
            ->selectRaw("{$cityExpression} as location_key, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->groupByRaw($cityExpression)
            ->get();

        $cityItems = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->whereRaw("{$countryExpression} IN (?, ?)", ['croatia', 'hrvatska'])
            ->where('order_products.product_id', '>', 0)
            ->whereNotNull('orders.shipping_city')
            ->selectRaw("{$cityExpression} as location_key, SUM(order_products.quantity) as items")
            ->groupByRaw($cityExpression)
            ->pluck('items', 'location_key');

        $cities = [];
        foreach ($cityRows as $row) {
            $city = $this->canonicalCity((string) $row->location_key);
            if ($city === '') {
                continue;
            }
            if (! isset($cities[$city])) {
                $cities[$city] = ['orders' => 0, 'total' => 0.0, 'items' => 0];
            }
            $cities[$city]['orders'] += (int) $row->orders;
            $cities[$city]['total'] += (float) $row->total;
            $cities[$city]['items'] += (int) ($cityItems[$row->location_key] ?? 0);
        }

        $coordinates = config('statistics.croatian_city_coordinates', []);
        $cityCollection = collect($cities)->map(function ($values, $city) use ($coordinates) {
            $coordinateKey = Str::lower(Str::ascii($city));
            $orders = (int) $values['orders'];
            return [
                'name' => $this->cityDisplayName($city),
                'orders' => $orders,
                'total' => round((float) $values['total'], 2),
                'items' => (int) $values['items'],
                'average_order' => $orders ? round(((float) $values['total']) / $orders, 2) : 0.0,
                'lat_lng' => $coordinates[$coordinateKey] ?? null,
            ];
        })->sortByDesc('total')->values();

        return [
            'countries' => $countryRows->all(),
            'cities' => $cityCollection->all(),
        ];
    }

    private function productAnalytics(Carbon $from, Carbon $to): array
    {
        $base = function () use ($from, $to) {
            return DB::table('order_products')
                ->join('orders', 'orders.id', '=', 'order_products.order_id')
                ->whereBetween('orders.created_at', [$from, $to])
                ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
                ->where('order_products.product_id', '>', 0);
        };

        $products = $base()
            ->selectRaw('order_products.product_id as id, MAX(order_products.name) as name, COUNT(DISTINCT orders.id) as orders, SUM(order_products.quantity) as items, SUM(order_products.total) as total')
            ->groupBy('order_products.product_id')
            ->orderByDesc('total')
            ->limit(25)
            ->get();

        $categories = $base()
            ->join('product_category', 'product_category.product_id', '=', 'order_products.product_id')
            ->join('categories', 'categories.id', '=', 'product_category.category_id')
            ->where('categories.parent_id', 0)
            ->selectRaw('categories.id, categories.title as name, COUNT(DISTINCT orders.id) as orders, SUM(order_products.quantity) as items, SUM(order_products.total) as total')
            ->groupBy('categories.id', 'categories.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $authors = $base()
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('authors', 'authors.id', '=', 'products.author_id')
            ->whereNotNull('authors.title')
            ->whereRaw("TRIM(authors.title) NOT IN ('', '/')")
            ->selectRaw('authors.id, authors.title as name, COUNT(DISTINCT orders.id) as orders, SUM(order_products.quantity) as items, SUM(order_products.total) as total')
            ->groupBy('authors.id', 'authors.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $publishers = $base()
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('publishers', 'publishers.id', '=', 'products.publisher_id')
            ->selectRaw('publishers.id, publishers.title as name, COUNT(DISTINCT orders.id) as orders, SUM(order_products.quantity) as items, SUM(order_products.total) as total')
            ->groupBy('publishers.id', 'publishers.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $wishlist = DB::table('wishlist')
            ->join('products', 'products.id', '=', 'wishlist.product_id')
            ->where('wishlist.status', 1)
            ->selectRaw('products.id, products.name, products.quantity as stock, COUNT(wishlist.id) as wishes')
            ->groupBy('products.id', 'products.name', 'products.quantity')
            ->orderByDesc('wishes')
            ->limit(10)
            ->get();

        $discount = $base()
            ->selectRaw('COUNT(DISTINCT CASE WHEN order_products.org_price > order_products.price THEN orders.id END) as discounted_orders, COALESCE(SUM(CASE WHEN order_products.org_price > order_products.price THEN (order_products.org_price - order_products.price) * order_products.quantity ELSE 0 END), 0) as amount')
            ->first();

        return [
            'top_products' => $this->rankingRows($products, 'products.edit'),
            'categories' => $this->rankingRows($categories, 'category.edit'),
            'authors' => $this->rankingRows($authors, 'authors.edit'),
            'publishers' => $this->rankingRows($publishers, 'publishers.edit'),
            'wishlist' => $wishlist->map(static function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'wishes' => (int) $row->wishes,
                    'stock' => (int) $row->stock,
                    'url' => route('products.edit', ['product' => $row->id]),
                ];
            })->all(),
            'discounts' => [
                'orders' => (int) ($discount->discounted_orders ?? 0),
                'amount' => (float) ($discount->amount ?? 0),
            ],
        ];
    }

    private function customerAnalytics(Carbon $from, Carbon $to): array
    {
        $email = $this->normalizedText('orders.payment_email');
        $periodCustomers = $this->salesOrders($from, $to)
            ->whereNotNull('orders.payment_email')
            ->whereRaw("TRIM(orders.payment_email) <> ''")
            ->selectRaw("{$email} as email, COUNT(orders.id) as period_orders, SUM(orders.total) as period_total")
            ->groupByRaw($email);
        $lifetimeCustomers = DB::table('orders')
            ->where('orders.created_at', '<=', $to)
            ->whereIn('orders.order_status_id', Order::dashboardCompletedStatusIds())
            ->whereNotNull('orders.payment_email')
            ->whereRaw("TRIM(orders.payment_email) <> ''")
            ->selectRaw("{$email} as email, MIN(orders.created_at) as first_order_at, COUNT(orders.id) as lifetime_orders")
            ->groupByRaw($email);

        $customers = DB::query()
            ->fromSub($periodCustomers, 'period_customers')
            ->joinSub($lifetimeCustomers, 'lifetime_customers', 'lifetime_customers.email', '=', 'period_customers.email')
            ->select(['period_customers.email', 'period_customers.period_orders', 'period_customers.period_total', 'lifetime_customers.first_order_at', 'lifetime_customers.lifetime_orders'])
            ->get();

        $new = $customers->filter(static fn ($customer) => Carbon::parse($customer->first_order_at)->between($from, $to))->count();
        $returning = $customers->count() - $new;
        $repeat = $customers->where('lifetime_orders', '>', 1)->count();
        $registeredOrders = (int) $this->salesOrders($from, $to)->where('orders.user_id', '>', 0)->count();
        $allOrders = (int) $this->salesOrders($from, $to)->count();

        return [
            'unique' => $customers->count(),
            'new' => $new,
            'returning' => $returning,
            'repeat' => $repeat,
            'repeat_rate' => $customers->count() ? round(($repeat / $customers->count()) * 100, 1) : 0.0,
            'registered_orders' => $registeredOrders,
            'guest_orders' => max(0, $allOrders - $registeredOrders),
        ];
    }

    private function operations(Carbon $from, Carbon $to): array
    {
        $sales = $this->salesOrders($from, $to);

        return [
            'payment_methods' => $this->breakdown($sales, 'orders.payment_method'),
            'shipping_methods' => $this->breakdown($sales, 'orders.shipping_method'),
            'statuses' => $this->statusBreakdown($from, $to),
        ];
    }

    private function breakdown(Builder $orders, string $column): array
    {
        $label = "COALESCE(NULLIF(TRIM({$column}), ''), 'Nepoznato')";

        return (clone $orders)
            ->selectRaw("{$label} as label, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->groupByRaw($label)
            ->orderByDesc('orders')
            ->get()
            ->map(static function ($row) {
                return [
                    'label' => (string) $row->label,
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ];
            })->all();
    }

    private function statusBreakdown(Carbon $from, Carbon $to): array
    {
        $statuses = collect(Settings::get('order', 'statuses'))->keyBy(static function ($status) {
            return (int) ($status->id ?? 0);
        });
        $included = Order::dashboardCompletedStatusIds();

        return DB::table('orders')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('orders.order_status_id as status_id, COUNT(orders.id) as orders, SUM(orders.total) as total')
            ->groupBy('orders.order_status_id')
            ->orderByDesc('orders')
            ->get()
            ->map(static function ($row) use ($statuses, $included) {
                $status = $statuses->get((int) $row->status_id);
                return [
                    'id' => (int) $row->status_id,
                    'label' => $status->title ?? ('Status #' . $row->status_id),
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                    'included_in_sales' => in_array((int) $row->status_id, $included, true),
                ];
            })->all();
    }

    private function rankingRows(Collection $rows, string $route): array
    {
        return $rows->map(static function ($row) use ($route) {
            $parameter = strpos($route, 'category') !== false
                ? 'category'
                : (strpos($route, 'authors') !== false
                    ? 'author'
                    : (strpos($route, 'publishers') !== false ? 'publisher' : 'product'));

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'orders' => (int) $row->orders,
                'items' => (int) $row->items,
                'total' => (float) $row->total,
                'url' => route($route, [$parameter => $row->id]),
            ];
        })->all();
    }

    private function countryCodeMap(): array
    {
        $map = [];

        foreach (Country::list() as $country) {
            $map[Str::lower(trim($country['name']))] = $country['iso_code_2'];
        }

        $map['hrvatska'] = 'HR';
        $map['usa'] = 'US';
        $map['united states of america'] = 'US';

        return $map;
    }

    private function canonicalCity(string $city): string
    {
        $city = Str::lower(trim($city));
        $city = preg_replace('/\b\d{5}\b/u', ' ', $city);
        $city = preg_replace('/\b(croatia|hrvatska)\b/u', ' ', $city);
        $city = preg_replace('/[^\pL\s\-]/u', ' ', $city);
        $city = preg_replace('/\s+/u', ' ', trim($city));

        return $city ?: '';
    }

    private function cityDisplayName(string $city): string
    {
        return mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');
    }

    private function normalizedText(string $column): string
    {
        return "LOWER(TRIM({$column}))";
    }

    private function dateBucket(string $column, string $group): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return $group === 'day' ? "strftime('%Y-%m-%d', {$column})" : "strftime('%Y-%m', {$column})";
        }

        return $group === 'day' ? "DATE_FORMAT({$column}, '%Y-%m-%d')" : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function weekdayExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "((CAST(strftime('%w', {$column}) AS INTEGER) + 6) % 7)"
            : "WEEKDAY({$column})";
    }

    private function hourExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', {$column}) AS INTEGER)"
            : "HOUR({$column})";
    }
}
