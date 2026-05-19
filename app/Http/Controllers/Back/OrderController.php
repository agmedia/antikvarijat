<?php

namespace App\Http\Controllers\Back;

use App\Exports\OrdersExport;
use App\Helpers\Country;
use App\Http\Controllers\Controller;
use App\Mail\StatusCanceled;
use App\Mail\StatusPaid;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderHistory;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Checkout\Shipping\Gls;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Back\Orders\OrderTotal;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Order $order)
    {
        $perPage = (int) config('settings.pagination.back');
        $query = $order->filter($request)
            ->select([
                'id',
                'created_at',
                'order_status_id',
                'payment_method',
                'shipping_fname',
                'shipping_lname',
                'total',
                'printed',
            ])
            ->withCount('orderProducts');

        $hasActiveFilters = $request->filled('status')
            || $request->filled('search')
            || $request->filled('date_from')
            || $request->filled('date_to');

        if ($hasActiveFilters) {
            $orders = $query
                ->paginate($perPage)
                ->appends($request->query());
        } else {
            $page = LengthAwarePaginator::resolveCurrentPage();
            $total = Cache::remember('admin.orders.total', now()->addMinutes(10), function () {
                return Order::query()->count();
            });

            $orders = new LengthAwarePaginator(
                $query->forPage($page, $perPage)->get(),
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        $statuses = Settings::get('order', 'statuses');

        return view('back.order.index', compact('orders', 'statuses'));
    }

    public function export(Request $request, Order $order)
    {
        $fileName = 'orders_' . now()->format('Ymd_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OrdersExport($request, $order), $fileName);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.order.edit');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $order = new Order();

        $stored = $order->validateRequest($request)->store();

        if ($stored) {
            return redirect()->route('orders.edit', ['order' => $stored])->with(['success' => 'Narudžba je snimljena!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Dogodila se greška prilikom snimanja.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Order $order
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $order->loadMissing([
            'products.product',
            'totals',
            'history.user',
        ]);

        $statuses = Settings::get('order', 'statuses');

        return view('back.order.show', compact('order', 'statuses'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Order $order
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        $order->loadMissing([
            'products.product',
            'totals',
            'history.user',
        ]);

        $countries = Country::list();
        $statuses = Settings::get('order', 'statuses');
        $shippings = Settings::getList('shipping');
        $payments = Settings::getList('payment');
        $shippingAmount = optional($order->totals->firstWhere('code', 'shipping'))->value;
        $paymentAmount = optional($order->totals->firstWhere('code', 'payment'))->value;

        return view('back.order.edit', compact(
            'order',
            'countries',
            'statuses',
            'shippings',
            'payments',
            'shippingAmount',
            'paymentAmount'
        ));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Order                    $order
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $updated = $order->validateRequest($request)->store($order->id);

        if ($updated) {
            // 1) Uzmi naslov dostave iz postavki za odabrani shipping code
            $shippingSetting = Settings::get('shipping', 'list.' . $request->shipping)->first();
            $shippingTitle   = $shippingSetting ? $shippingSetting->title : ($request->shipping_title ?? 'Dostava');

            // 2) Updejtaj redak u order_total za shipping
            OrderTotal::where('order_id', $updated->id)
                ->where('code', 'shipping')
                ->update(['title' => $shippingTitle]);

            return redirect()->route('orders.edit', ['order' => $updated])
                ->with(['success' => 'Narudžba je snimljena!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Dogodila se greška prilikom snimanja.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {}


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_status_change(Request $request)
    {
        if ($request->has('orders')) {
            $orders = explode(',', substr($request->input('orders'), 1, -1));
            $statusId = (int) $request->input('selected');

            Order::whereIn('id', $orders)->update([
                'order_status_id' => $statusId
            ]);

            if ($statusId) {
                Order::query()
                    ->whereIn('id', $orders)
                    ->get()
                    ->each(function (Order $order) use ($statusId) {
                        $this->sendStatusNotification($order, $statusId);
                    });
            }

            return response()->json(['message' => 'Statusi su uspješno promijenjeni..!']);
        }

        if ($request->has('order_id')) {
            $statusId = (int) $request->input('status');
            $orderId = (int) $request->input('order_id');

            if ($request->has('status') && $request->input('status')) {
                Order::where('id', $orderId)->update([
                    'order_status_id' => $statusId
                ]);
            }

            $order = Order::query()->find($orderId);

            $this->sendStatusNotification($order, $statusId);

            OrderHistory::store($orderId, $request);

            $historyRecord = OrderHistory::query()
                ->with('user')
                ->where('order_id', $orderId)
                ->latest('id')
                ->first();

            return response()->json([
                'message' => 'Status je uspješno promijenjen..!',
                'history_html' => ($order && $historyRecord)
                    ? view('back.order.partials.history-row', [
                        'record' => $historyRecord,
                        'order' => $order,
                    ])->render()
                    : null,
                'status_id' => $statusId,
            ]);
        }

        return response()->json(['error' => 'Greška..! Molimo pokušajte ponovo ili kontaktirajte administratora..']);
    }

    private function sendStatusNotification(?Order $order, int $statusId): void
    {
        $paidStatusId = (int) config('settings.order.status.paid');
        $canceledStatusId = (int) config('settings.order.status.canceled');

        if (! in_array($statusId, [$paidStatusId, $canceledStatusId], true)) {
            return;
        }

        if (! config('mail.order_status_notifications_enabled')) {
            Log::warning('Order status notification skipped because notifications are disabled', [
                'order_id' => $order ? $order->id : null,
                'status_id' => $statusId,
            ]);

            return;
        }

        if (! $order) {
            Log::warning('Order status notification skipped because order was not found', [
                'status_id' => $statusId,
            ]);

            return;
        }

        $email = trim((string) $order->payment_email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Order status notification skipped because email is invalid', [
                'order_id' => $order->id,
                'status_id' => $statusId,
                'email' => $order->payment_email,
            ]);

            return;
        }

        dispatch(function () use ($order, $email, $statusId, $paidStatusId) {
            try {
                $mailable = $statusId === $paidStatusId
                    ? new StatusPaid($order)
                    : new StatusCanceled($order);

                Mail::to($email)->send($mailable);
            } catch (\Throwable $e) {
                Log::warning('Order status notification failed', [
                    'order_id' => $order->id,
                    'status_id' => $statusId,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_send_gls(Request $request)
    {
        $request->validate(['order_id' => 'required']);

        $order = Order::where('id', $request->input('order_id'))->first();

        $gls = new Gls($order);
        $label = $gls->resolve();

        if (isset($label['ParcelIdList'])) {
            Log::info('GLS label: ' . print_r($label, true));
            return response()->json(['message' => 'GLS je uspješno poslan sa ID: ' . $label['ParcelIdList'][0]]);
        }

        return response()->json(['error' => 'Greška..! Molimo pokušajte ponovo ili kontaktirajte administratora..']);
    }
}
