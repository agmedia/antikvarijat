<?php

namespace App\Http\Controllers\Back;

use App\Helpers\Helper;
use App\Helpers\Import;
use App\Helpers\ProductHelper;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Mjerilo;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\Back\Catalog\Publisher;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderProduct;
use App\Models\Back\Settings\Settings;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Bouncer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DashboardController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $canViewSales = ! (auth()->check() && Bouncer::is(auth()->user())->an('editor'));
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $yearStart = $now->copy()->startOfYear();
        $yearEnd = $now->copy()->endOfYear();

        $todayOrders = Order::query()
            ->whereBetween('orders.created_at', [$todayStart, $todayEnd])
            ->dashboardSales();
        $finishedOrders = Order::query()
            ->whereBetween('orders.created_at', [$yearStart, $yearEnd])
            ->dashboardSales();
        $thisMonthOrders = Order::query()
            ->whereBetween('orders.created_at', [$monthStart, $monthEnd])
            ->dashboardSales();

        $todayStats = $this->orderStats($todayOrders);
        $finishedStats = $this->orderStats($finishedOrders);
        $thisMonthStats = $this->orderStats($thisMonthOrders);

        $data['today'] = $todayStats['orders'];
        $data['today_total'] = $todayStats['total'];
        $data['today_items_average'] = $this->averageItems(
            $this->productItemQuantity($todayOrders),
            $todayStats['orders']
        );
        $data['finished'] = $finishedStats['orders'];
        $data['finished_total'] = $finishedStats['total'];
        $data['finished_items_average'] = $this->averageItems(
            $this->productItemQuantity($finishedOrders),
            $finishedStats['orders']
        );
        $data['this_month'] = $thisMonthStats['orders'];
        $data['this_month_total'] = $thisMonthStats['total'];
        $data['this_month_items_average'] = $this->averageItems(
            $this->productItemQuantity($thisMonthOrders),
            $thisMonthStats['orders']
        );

        $orders = Order::query()
            ->select(['id', 'payment_fname', 'payment_lname', 'total', 'order_status_id', 'created_at'])
            ->last(10)
            ->get();

        $products = OrderProduct::query()
            ->select(['id', 'product_id', 'name', 'price', 'created_at'])
            ->where('product_id', '>', 0)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $yearsWithOrders = Cache::remember('dashboard.years_with_orders', now()->addMinutes(30), function () {
            return Order::query()
                ->selectRaw('YEAR(created_at) as year')
                ->whereNotNull('created_at')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->map(function ($year) {
                    return (int) $year;
                });
        });

        if (! $yearsWithOrders->contains((int) $now->year)) {
            $yearsWithOrders->prepend((int) $now->year);
        }

        return view('back.dashboard', compact(
            'data',
            'orders',
            'products',
            'yearsWithOrders',
            'canViewSales'
        ));
    }


    /**
     * Import initialy from excel files.
     *
     * @param Request $request
     */
    public function import(Request $request)
    {
        $spread = IOFactory::load(public_path('assets/artikli.csv'));
        $sheet  = $spread->getActiveSheet();
        $list   = array(1, $sheet->toArray(null, true, true, true))[1];

        $import = new Import();
        $count  = 0;

        $unknown_author_id    = 6;
        $unknown_publisher_id = 2;

        for ($n = 0; $n < 1; $n++) {
            for ($i = 2; $i < count($list); $i++) {
                //  $attributes = $import->setAttributes($list[$i]);
                //$author     = $import->resolveAuthor($attributes['author']);
                $author = $import->resolveAuthor($list[$i]['AX']);
                //$publisher  = $import->resolvePublisher($attributes['publisher']);

                $list[$i]['BM'] = substr($list[$i]['BM'], 0, strpos($list[$i]['BM'], "("));
                $publisher  = $import->resolvePublisher($list[$i]['BM']);



                $name = $list[$i]['A'];
                $action = ($list[$i]['S'] == $list[$i]['T']) ? null : $list[$i]['T'];

                $product_id = Product::insertGetId([
                    'author_id'        => $author ?: $unknown_author_id,
                    'publisher_id'     => $publisher ?: $unknown_publisher_id,
                    'action_id'        => 0,
                    'name'             => $name,
                    'sku'              => $list[$i]['M'] ?: '0',
                    'description'      => '<p>' . str_replace('\n', '<br>', $list[$i]['F']) . '</p>',
                    'slug'             => Str::slug($name),
                    'price'            => $list[$i]['S'] ?: '0',
                    'quantity'         => $list[$i]['R'] ?: '0',
                    'tax_id'           => 1,
                    'special'          => $action,
                    'special_from'     => null,
                    'special_to'       => null,
                    'meta_title'       => $name,
                    'meta_description' => $name,
                    'pages'            => $list[$i]['BA'],
                    'dimensions'       => $list[$i]['BD'],
                    'origin'           => $list[$i]['BP'],
                    'letter'           => $list[$i]['BS'],
                    'condition'        => $list[$i]['BV'],
                    'binding'          => $list[$i]['BY'],
                    'year'             => $list[$i]['BJ'],
                    'viewed'           => 0,
                    'sort_order'       => 0,
                    'push'             => 0,
                    'status'           => $list[$i]['R'] ? 1 : 0,
                    'created_at'       => $list[$i]['J'],
                    'updated_at'       => Carbon::now()
                ]);

                if ($product_id) {

                    $images = explode('|', $list[$i]['AP']);

                    $data2=array();
                    foreach ($images as $dat){

                        $data2[] = array_map('trim',explode('!', $dat));
                    }

                    $data2 = array_column($data2, 0);



                    $images   = $import->resolveImages($data2, $name, $product_id);

                    if ($list[$i]['AU'] == '') {
                        $list[$i]['AU'] = [];
                    } else {
                        $list[$i]['AU'] = explode('|', $list[$i]['AU']);
                    }

                    $categories = $import->resolveCategories($list[$i]['AU']);


                    if ($images) {
                        for ($k = 0; $k < count($images); $k++) {
                            if ($k == 0) {
                                Product::where('id', $product_id)->update([
                                    'image' => $images[$k]
                                ]);
                            } else {
                                ProductImage::insert([
                                    'product_id' => $product_id,
                                    'image'      => $images[$k],
                                    'alt'        => $name,
                                    'published'  => 1,
                                    'sort_order' => $k,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]);
                            }
                        }
                    }

                    if ($categories) {
                        foreach ($categories as $category) {
                            ProductCategory::insert([
                                'product_id'  => $product_id,
                                'category_id' => $category
                            ]);
                        }
                    }

                    $product = Product::find($product_id);

                    $product->update([
                        'url' => ProductHelper::url($product),
                        'category_string' => ProductHelper::categoryString($product)
                    ]);

                    $count++;
                }
            }
        }

        return redirect()->route('dashboard')->with(['success' => 'Import je uspješno obavljen..! ' . $count . ' proizvoda importano.']);
    }


    /**
     * Set up roles. Should be done once only.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setRoles()
    {
        if ( ! auth()->user()->can('*')) {
            abort(401);
        }

        $superadmin = Bouncer::role()->firstOrCreate([
            'name'  => 'superadmin',
            'title' => 'Super Administrator',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'admin',
            'title' => 'Administrator',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'editor',
            'title' => 'Editor',
        ]);

        Bouncer::role()->firstOrCreate([
            'name'  => 'customer',
            'title' => 'Customer',
        ]);

        Bouncer::allow($superadmin)->everything();

        Bouncer::ability()->firstOrCreate([
            'name'  => 'set-super',
            'title' => 'Postavi korisnika kao Superadmina.'
        ]);

        $users = User::whereIn('email', ['filip@agmedia.hr', 'tomislav@agmedia.hr'])->get();

        foreach ($users as $user) {
            $user->assign($superadmin);
        }

        return redirect()->route('dashboard');
    }


    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function letters()
    {
        $authors = Author::all();

        foreach ($authors as $author) {
            $letter = Helper::resolveFirstLetter($author->title);

            $author->update([
                'letter' => Str::ucfirst($letter)
            ]);
        }

        //
        $publishers = Publisher::all();

        foreach ($publishers as $publisher) {
            $letter = Helper::resolveFirstLetter($publisher->title);

            $publisher->update([
                'letter' => Str::ucfirst($letter)
            ]);
        }

        return redirect()->route('dashboard');
    }


    /**
     *
     */
    public function slugs()
    {
        $slugs = Product::query()->groupBy('slug')->havingRaw('COUNT(id) > 1')->pluck('slug', 'id')->toArray();

        foreach ($slugs as $slug) {
            $products = Product::where('slug', $slug)->get();

            if ($products) {
                foreach ($products as $product) {
                    $time = Str::random(9);
                    $product->update([
                        'slug' => $product->slug . '-' . $time,
                        'url' => $product->url . '-' . $time,
                    ]);
                }
            }
        }

        return redirect()->route('dashboard');
    }


    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function statuses()
    {
        // AUTHORS
        $products = Product::query()
                           ->where('quantity', '>', 0)
                           ->select('author_id', DB::raw('count(*) as total'))
                           ->groupBy('author_id')
                           ->pluck('author_id')
                           ->unique();

        $authors = Author::query()->pluck('id')->diff($products)->flatten();

        Author::whereIn('id', $authors)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Author::whereNotIn('id', $authors)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        // PUBLISHERS
        $products = Product::query()
                           ->where('quantity', '>', 0)
                           ->select('publisher_id', DB::raw('count(*) as total'))
                           ->groupBy('publisher_id')
                           ->pluck('publisher_id')
                           ->unique();

        $publishers = Publisher::query()->pluck('id')->diff($products)->flatten();

        Publisher::whereIn('id', $publishers)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Publisher::whereNotIn('id', $publishers)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        // CATEGORIES
        $categories_off = Category::query()->select('id')->withCount('products')->having('products_count', '<', 1)->get()->toArray();

        if ($categories_off) {
            foreach ($categories_off as $category) {
                Category::where('id', $category['id'])->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);
            }
        }

        $categories_on = Category::query()->select('id')->withCount('products')->having('products_count', '>', 0)->get()->toArray();

        if ($categories_on) {
            foreach ($categories_on as $category) {
                Category::where('id', $category['id'])->update([
                    'status' => 1,
                    'updated_at' => now()
                ]);
            }
        }

        // PRODUCTS
        $products = Product::where('quantity', 0)->pluck('id');

        Product::whereIn('id', $products)->update([
            'status' => 0,
            'updated_at' => now()
        ]);

        Product::whereNotIn('id', $products)->update([
            'status' => 1,
            'updated_at' => now()
        ]);

        return redirect()->route('dashboard');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mailing(Request $request)
    {
        $order = Order::where('id', 16)->first();

        dispatch(function () use ($order) {
            Mail::to(config('mail.admin'))->send(new OrderReceived($order));
            Mail::to($order->payment_email)->send(new OrderSent($order));
        });

        return redirect()->route('dashboard');
    }


    /**
     *
     */
    public function duplicate(string $target = null)
    {
        // Duplicate images
        if ($target === 'images') {
            $paths = ProductImage::query()->groupBy('image')->havingRaw('COUNT(id) > 1')->pluck('image', 'id')->toArray();

            foreach ($paths as $path) {
                $first = ProductImage::where('image', $path)->first();

                ProductImage::where('image', $path)->where('id', '!=', $first->id)->delete();
            }
        }

        // Duplicate publishers
        if ($target === 'publishers') {
            $paths = Publisher::query()->groupBy('title')->havingRaw('COUNT(id) > 1')->pluck('title', 'id')->toArray();

            foreach ($paths as $id => $path) {
                $group = Publisher::where('title', $path)->get();

                foreach ($group as $item) {
                    if ($item->id != $id) {
                        foreach ($item->products()->get() as $product) {
                            Product::where('id', $product->id)->update([
                                'publisher_id' => $id
                            ]);
                        }

                        Publisher::where('id', $item->id)->delete();
                    }
                }
            }
        }

        return redirect()->route('dashboard');
    }


    /**
     * Return turnover and order counts per day for a selected month.
     */
    public function chartByMonth(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $month = max(1, min(12, (int) $request->get('month', Carbon::now()->month)));

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $monthOrders = Order::query()
            ->whereBetween('orders.created_at', [$from, $to])
            ->dashboardSales();

        $days = (clone $monthOrders)
            ->selectRaw('DAY(orders.created_at) as day, SUM(orders.total) as total, COUNT(orders.id) as orders')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $itemQuantities = (clone $monthOrders)
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->where('order_products.product_id', '>', 0)
            ->selectRaw('DAY(orders.created_at) as day, SUM(order_products.quantity) as item_quantity')
            ->groupBy('day')
            ->pluck('item_quantity', 'day');

        $days = $days->map(function ($row) use ($itemQuantities) {
            $row->item_quantity = (int) ($itemQuantities[(int) $row->day] ?? 0);
            $row->avg_items = $row->orders > 0
                ? round($row->item_quantity / $row->orders, 2)
                : 0;

            return $row;
        });

        return response()->json([
            'days' => $days,
            'summary' => $this->salesSummary($monthOrders),
        ]);
    }

    /**
     * Return turnover and order counts per month for a selected year.
     */
    public function chartByYear(Request $request)
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $from = Carbon::create($year, 1, 1)->startOfYear();
        $to = $from->copy()->endOfYear();

        $yearOrders = Order::query()
            ->whereBetween('orders.created_at', [$from, $to])
            ->dashboardSales();

        $months = (clone $yearOrders)
            ->selectRaw('MONTH(orders.created_at) as month, SUM(orders.total) as total, COUNT(orders.id) as orders')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'months' => $months,
            'summary' => $this->salesSummary($yearOrders),
        ]);
    }

    public function chartByDay(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());
        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $data = Order::query()
            ->selectRaw('HOUR(created_at) as hour, SUM(total) as total, COUNT(id) as orders')
            ->whereBetween('created_at', [$from, $to])
            ->dashboardSales()
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json($data);
    }

    public function chartByRange(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $data = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(id) as orders')
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->dashboardSales()
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    private function orderStats($ordersQuery): array
    {
        $stats = (clone $ordersQuery)
            ->selectRaw('COUNT(orders.id) as orders_count, COALESCE(SUM(orders.total), 0) as total')
            ->first();

        return [
            'orders' => (int) ($stats->orders_count ?? 0),
            'total' => (float) ($stats->total ?? 0),
        ];
    }

    private function averageItems(int $itemQuantity, int $orders): float
    {
        return $orders > 0 ? round($itemQuantity / $orders, 2) : 0;
    }

    private function productItemQuantity($ordersQuery): int
    {
        $filteredOrders = (clone $ordersQuery)->select('orders.id');

        return (int) DB::query()
            ->fromSub($filteredOrders, 'filtered_orders')
            ->join('order_products', 'order_products.order_id', '=', 'filtered_orders.id')
            ->where('order_products.product_id', '>', 0)
            ->sum('order_products.quantity');
    }

    private function salesSummary($ordersQuery): array
    {
        $stats = $this->orderStats($ordersQuery);
        $items = $this->productItemQuantity($ordersQuery);

        return [
            'total' => $stats['total'],
            'orders' => $stats['orders'],
            'item_quantity' => $items,
            'avg_items' => $this->averageItems($items, $stats['orders']),
            'payment_methods' => $this->breakdown($ordersQuery, 'orders.payment_method'),
            'shipping_methods' => $this->breakdown($ordersQuery, 'orders.shipping_method'),
            'statuses' => $this->statusBreakdown($ordersQuery),
        ];
    }

    private function breakdown($ordersQuery, string $column): array
    {
        $label = "COALESCE(NULLIF(TRIM({$column}), ''), 'Nepoznato')";

        return (clone $ordersQuery)
            ->selectRaw("{$label} as label, COUNT(orders.id) as orders, SUM(orders.total) as total")
            ->groupByRaw($label)
            ->orderByDesc('orders')
            ->orderBy('label')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => (string) $row->label,
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ];
            })
            ->values()
            ->all();
    }

    private function statusBreakdown($ordersQuery): array
    {
        $statuses = collect(Settings::get('order', 'statuses'))->keyBy(function ($status) {
            return (int) ($status->id ?? 0);
        });

        return (clone $ordersQuery)
            ->selectRaw('orders.order_status_id as status_id, COUNT(orders.id) as orders, SUM(orders.total) as total')
            ->groupBy('orders.order_status_id')
            ->orderByDesc('orders')
            ->get()
            ->map(function ($row) use ($statuses) {
                $status = $statuses->get((int) $row->status_id);

                return [
                    'label' => $status->title ?? ('Status #' . $row->status_id),
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ];
            })
            ->values()
            ->all();
    }

}
