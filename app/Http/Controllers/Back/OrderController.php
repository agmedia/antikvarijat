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
use App\Models\AbandonedCartReminder;
use App\Services\AbandonedCartReminderService;
use App\Services\Shipping\BoxNowService;
use App\Services\Shipping\GlsTrackingService;
use App\Services\Shipping\OrderTrackingService;
use App\Services\Shipping\WoltDriveAmbiguousCreateException;
use App\Services\Shipping\WoltDriveException;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use App\Services\GiftVoucherService;
use App\Services\MailchimpOrderSynchronizer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private const BOXNOW_SHIPMENT_LOCK_SECONDS = 180;
    private const WOLT_DELIVERY_LOCK_SECONDS = 180;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Order $order, AbandonedCartReminderService $reminders)
    {
        $perPage = (int) config('settings.pagination.back');
        $select = [
            'id',
            'created_at',
            'order_status_id',
            'payment_method',
            'shipping_fname',
            'shipping_lname',
            'total',
            'printed',
            'shipping_method',
            'shipping_code',
            'shipping_carrier',
            'shipping_parcel_id',
            'tracking_code',
            'shipping_tracking_status_code',
            'shipping_tracking_status',
        ];

        if ($reminders->isAvailable()) {
            $select = array_merge($select, [
                'payment_email',
                'locale',
                'unfinished_at',
            ]);
        }

        $query = $order->filter($request)
            ->select($select)
            ->withCount('orderProducts');

        if ($reminders->isAvailable()) {
            $query->with('abandonedCartReminders');
        }

        $hasActiveFilters = $request->filled('status')
            || $request->filled('dashboard_group')
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

        if ($reminders->isAvailable()) {
            $orders->getCollection()->each(function (Order $listedOrder) use ($reminders) {
                $listedOrder->setAttribute('abandoned_cart_state', $reminders->adminState($listedOrder));
            });
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

        $canViewGiftVouchers = ! (auth()->check() && auth()->user()->isEditor())
            && Schema::hasTable('gift_vouchers')
            && Schema::hasTable('gift_voucher_redemptions');

        if ($canViewGiftVouchers) {
            $order->loadMissing([
                'giftVouchers.redemptions.order',
                'giftVoucherRedemptions.voucher.purchaseOrder',
            ]);
        }

        $statuses = Settings::get('order', 'statuses');
        $trackingEmailSentAt = app(OrderTrackingService::class)->trackingEmailSentAt($order);

        return view('back.order.show', compact('order', 'statuses', 'trackingEmailSentAt', 'canViewGiftVouchers'));
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

            app(MailchimpOrderSynchronizer::class)->markForSync((int) $updated->id);

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
    public function api_status_change(
        Request $request,
        GiftVoucherService $giftVouchers,
        MailchimpOrderSynchronizer $mailchimpOrders
    )
    {
        if ($request->has('orders')) {
            $orders = explode(',', substr($request->input('orders'), 1, -1));
            $statusId = (int) $request->input('selected');

            $updates = ['order_status_id' => $statusId];
            if ($statusId === (int) config('settings.order.status.unfinished', 8)
                && Schema::hasColumn('orders', 'unfinished_at')) {
                $updates['unfinished_at'] = now();
            }

            $changedOrderIds = [];
            foreach ($orders as $orderId) {
                $orderId = (int) $orderId;

                if ($this->changeOrderStatus($orderId, $statusId, $updates)) {
                    $changedOrderIds[$orderId] = true;
                }
            }

            $mailchimpOrders->markForSync($orders);

            if ($statusId) {
                Order::query()
                    ->whereIn('id', $orders)
                    ->get()
                    ->each(function (Order $order) use ($statusId, $giftVouchers, $changedOrderIds) {
                        $giftVouchers->handleStatusChange($order, $statusId);
                        $this->sendStatusNotification(
                            $order,
                            $statusId,
                            isset($changedOrderIds[(int) $order->id])
                        );
                    });
            }

            return response()->json(['message' => 'Statusi su uspješno promijenjeni..!']);
        }

        if ($request->has('order_id')) {
            $statusId = (int) $request->input('status');
            $orderId = (int) $request->input('order_id');
            $statusChanged = false;

            if ($request->has('status') && $request->input('status')) {
                $updates = ['order_status_id' => $statusId];
                if ($statusId === (int) config('settings.order.status.unfinished', 8)
                    && Schema::hasColumn('orders', 'unfinished_at')) {
                    $updates['unfinished_at'] = now();
                }

                $statusChanged = $this->changeOrderStatus($orderId, $statusId, $updates);
                $mailchimpOrders->markForSync($orderId);
            }

            $order = Order::query()->find($orderId);

            if ($order && $statusId) {
                $giftVouchers->handleStatusChange($order, $statusId);
            }

            $this->sendStatusNotification($order, $statusId, $statusChanged);

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

    public function sendAbandonedCartReminder(
        Order $order,
        AbandonedCartReminderService $reminders
    ) {
        try {
            $state = $reminders->adminState($order);
            $sequence = (int) ($state['next_sequence'] ?? 0);

            if (! ($state['available'] ?? false) || $sequence < 1) {
                return back()->with('error', $state['error'] ?? 'Podsjetnik nije moguće poslati.');
            }

            $sent = $reminders->send($order, $sequence, AbandonedCartReminder::SOURCE_MANUAL);

            return back()->with(
                'success',
                sprintf('%d. podsjetnik uspješno je poslan %s.', $sequence, $sent->recipient_email)
            );
        } catch (\Throwable $exception) {
            Log::warning('Manual abandoned cart reminder failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Podsjetnik nije poslan: ' . $exception->getMessage());
        }
    }

    private function changeOrderStatus(int $orderId, int $statusId, array $updates): bool
    {
        if ($orderId < 1 || $statusId < 1) {
            return false;
        }

        return Order::query()
            ->whereKey($orderId)
            ->where(function ($query) use ($statusId) {
                $query->whereNull('order_status_id')
                    ->orWhere('order_status_id', '!=', $statusId);
            })
            ->update($updates) === 1;
    }

    private function sendStatusNotification(
        ?Order $order,
        int $statusId,
        bool $statusChanged
    ): void
    {
        $paidStatusId = (int) config('settings.order.status.paid');
        $canceledStatusId = (int) config('settings.order.status.canceled');

        if (! $statusChanged
            || ! in_array($statusId, [$paidStatusId, $canceledStatusId], true)) {
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

        $locale = $order->resolvedLocale();

        dispatch(function () use ($order, $email, $locale, $statusId, $paidStatusId) {
            try {
                $mailable = $statusId === $paidStatusId
                    ? new StatusPaid($order)
                    : new StatusCanceled($order);

                Mail::to($email)->send($mailable->locale($locale));
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
    public function api_send_boxnow(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);

        $order = Order::query()->with('products')->find($request->input('order_id'));

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        if (! $this->isBoxNowOrder($order)) {
            return response()->json(['error' => 'Narudžba nema odabranu Box Now dostavu.'], 422);
        }

        return $this->sendBoxNowShipment($order);
    }

    public function api_send_gls(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);

        $order = Order::query()->with('products')->find($request->input('order_id'));

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        // Zaštita za stari UI/bookmark: Box Now narudžba nikada ne smije završiti u GLS API-ju.
        if ($this->isBoxNowOrder($order)) {
            return $this->sendBoxNowShipment($order);
        }

        // Stari admin bookmark/generički gumb ne smije Wolt narudžbu poslati
        // GLS-u. Preusmjeri je kroz isti zaštićeni Wolt tok.
        if ($this->isWoltOrder($order)) {
            return $this->api_send_wolt(
                $request,
                app(WoltDriveService::class),
                app(WoltDriveSettingsService::class),
                app(OrderTrackingService::class)
            );
        }

        if (! $this->isGlsOrder($order)) {
            return response()->json(['error' => 'Narudžba nema odabranu GLS dostavu.'], 422);
        }

        if ($this->hasExistingShipment($order)) {
            $shipmentId = $order->tracking_code ?: $order->shipping_parcel_id;

            return response()->json([
                'message' => $shipmentId
                    ? 'GLS pošiljka je već kreirana: ' . $shipmentId
                    : 'GLS pošiljka je već kreirana za ovu narudžbu.',
            ]);
        }

        $label = (new Gls($order))->resolve();
        $parcelId = data_get($label, 'ParcelIdList.0');
        $parcelNumber = data_get($label, 'ParcelNumberList.0');

        if ($parcelNumber) {
            $trackingPayload = $label;
            unset($trackingPayload['GetPrintedLabelsRequest']);

            app(OrderTrackingService::class)->apply($order, [
                'carrier' => GlsTrackingService::CARRIER,
                'parcel_id' => $parcelId ? (string) $parcelId : null,
                'tracking_code' => (string) $parcelNumber,
                'tracking_url' => app(GlsTrackingService::class)->trackingUrl((string) $parcelNumber),
                'status_code' => '51',
                'status' => 'Podaci o pošiljci su uneseni u GLS sustav; pošiljka još nije predana GLS-u.',
                'tracked_at' => now(),
                'payload' => $trackingPayload,
            ]);

            return response()->json(['message' => 'GLS je uspješno poslan s brojem: ' . $parcelNumber]);
        }

        if ($parcelId) {
            $trackingPayload = $label;
            unset($trackingPayload['GetPrintedLabelsRequest']);

            $order->forceFill([
                'shipping_carrier' => GlsTrackingService::CARRIER,
                'shipping_parcel_id' => (string) $parcelId,
                'shipping_tracking_status_code' => '51',
                'shipping_tracking_status' => 'Podaci o pošiljci su uneseni u GLS sustav; pošiljka još nije predana GLS-u.',
                'shipping_tracking_updated_at' => now(),
                'shipping_tracking_payload' => $trackingPayload,
                'printed' => true,
            ])->save();

            return response()->json([
                'message' => 'GLS je uspješno poslan s ID-em: ' . $parcelId . '. Tracking broj još nije dostupan.',
            ]);
        }

        Log::warning('GLS shipment did not return a parcel identifier.', [
            'order_id' => $order->id,
            'errors' => data_get($label, 'PrepareLabelsError'),
        ]);

        return response()->json(['error' => 'Greška..! Molimo pokušajte ponovo ili kontaktirajte administratora..']);
    }

    public function api_send_wolt(
        Request $request,
        WoltDriveService $wolt,
        WoltDriveSettingsService $settings,
        OrderTrackingService $trackingService
    ) {
        $this->authorizeWoltAdministrator($request);
        $request->validate(['order_id' => ['required', 'integer']]);
        $order = Order::query()->with(['products', 'totals'])->find($request->input('order_id'));

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        if (! $this->isWoltOrder($order)) {
            return response()->json(['error' => 'Narudžba nema odabranu Wolt Drive dostavu.'], 422);
        }

        if (! $settings->isEnabled() || ! $settings->isReady()) {
            return response()->json(['error' => 'Wolt Drive modul nije uključen ili konfiguracija nije potpuna.'], 422);
        }

        if (! $this->woltOrderCanBeDispatched($order, $settings)) {
            return response()->json([
                'error' => 'Narudžba još nije spremna za Wolt Drive. Prepaid narudžba mora biti plaćena, a otkazane i nedovršene narudžbe nije moguće poslati.',
            ], 422);
        }

        $lock = Cache::lock(
            'wolt-delivery-create:' . $order->id,
            self::WOLT_DELIVERY_LOCK_SECONDS
        );

        if (! $lock->get()) {
            return response()->json([
                'error' => 'Kreiranje Wolt Drive dostave za ovu narudžbu već je u tijeku.',
            ], 409);
        }

        try {
            $order->refresh();
            $order->load(['products', 'totals']);

            if ($this->hasExistingWoltDelivery($order)) {
                $identifier = $order->tracking_code ?: $order->shipping_parcel_id;

                return response()->json([
                    'message' => $identifier
                        ? 'Wolt Drive dostava već je kreirana: ' . $identifier
                        : 'Wolt Drive dostava već je kreirana za ovu narudžbu.',
                ]);
            }

            if (! $this->woltOrderCanBeDispatched($order, $settings)) {
                return response()->json(['error' => 'Narudžba više nije spremna za Wolt Drive.'], 422);
            }

            try {
                $tracking = $wolt->createDelivery($order);
                $trackingService->apply($order, $tracking);
                OrderHistory::store($order->id, new Request([
                    'status' => 0,
                    'comment' => 'Wolt Drive dostava kreirana. ID: '
                        . ($tracking['parcel_id'] ?? 'nije dostupan') . '.',
                ]));

                try {
                    $trackingService->sendTrackingAvailableMailManually($order->fresh());
                } catch (\Throwable $mailException) {
                    Log::warning('Wolt Drive tracking email could not be sent.', [
                        'order_id' => $order->id,
                        'message' => $mailException->getMessage(),
                    ]);
                }

                return response()->json([
                    'message' => 'Wolt Drive dostava uspješno je kreirana: '
                        . ($tracking['tracking_code'] ?? $tracking['parcel_id'] ?? $order->id),
                ]);
            } catch (WoltDriveAmbiguousCreateException $exception) {
                Log::critical('Wolt Drive delivery create has an ambiguous result.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Wolt nije potvrdio ishod zahtjeva. Novi zahtjev nije poslan kako se dostava ne bi duplicirala. Provjerite narudžbu u Wolt sustavu prije ponovnog pokušaja.',
                ], 409);
            } catch (WoltDriveException $exception) {
                Log::warning('Wolt Drive delivery create failed.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                return response()->json(['error' => 'Wolt Drive dostavu nije moguće kreirati. Provjerite postavke i podatke narudžbe.'], 422);
            } catch (\Throwable $exception) {
                Log::error('Unexpected Wolt Drive delivery create failure.', [
                    'order_id' => $order->id,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);

                return response()->json(['error' => 'Došlo je do neočekivane greške pri slanju u Wolt Drive.'], 500);
            }
        } finally {
            $lock->release();
        }
    }

    public function api_cancel_wolt(Request $request, WoltDriveService $wolt, OrderTrackingService $trackingService)
    {
        $this->authorizeWoltAdministrator($request);
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $order = Order::query()->find($validated['order_id']);

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        if (! $this->isWoltOrder($order) || ! $this->hasExistingWoltDelivery($order)) {
            return response()->json(['error' => 'Narudžba nema kreiranu Wolt Drive dostavu.'], 422);
        }

        if (in_array(Str::lower((string) $order->shipping_tracking_status_code), [
            'rejected',
            'order.rejected',
            'cancelled',
            'canceled',
        ], true)) {
            return response()->json(['message' => 'Wolt Drive dostava već je otkazana.']);
        }

        if (in_array(Str::lower((string) $order->shipping_tracking_status_code), [
            'delivered',
            'order.delivered',
        ], true)) {
            return response()->json(['error' => 'Dostavljena Wolt Drive pošiljka više se ne može otkazati.'], 422);
        }

        try {
            $tracking = $wolt->cancel($order, $validated['reason']);
            $trackingService->apply($order, $tracking);
            OrderHistory::store($order->id, new Request([
                'status' => 0,
                'comment' => 'Wolt Drive dostava otkazana. Razlog: ' . $validated['reason'],
            ]));

            return response()->json(['message' => 'Wolt Drive dostava je otkazana.']);
        } catch (WoltDriveException $exception) {
            Log::warning('Wolt Drive cancellation failed.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Wolt Drive dostavu nije moguće otkazati automatski. Ako je preuzimanje već počelo, kontaktirajte Wolt podršku.',
            ], 422);
        }
    }

    public function api_refresh_tracking(Request $request, OrderTrackingService $trackingService)
    {
        $request->validate(['order_id' => 'required|integer']);
        $order = Order::query()->find($request->input('order_id'));

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        try {
            $result = $trackingService->refresh($order);

            return response()->json([
                'message' => $result['message'],
                'tracking' => $result['tracking'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Manual shipment tracking refresh failed.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Greška..! ' . $e->getMessage()], 422);
        }
    }

    public function api_send_tracking_email(Request $request, OrderTrackingService $trackingService)
    {
        $request->validate(['order_id' => 'required|integer']);
        $order = Order::query()->find($request->input('order_id'));

        if (! $order) {
            return response()->json(['error' => 'Narudžba nije pronađena.'], 404);
        }

        try {
            $result = $trackingService->sendTrackingAvailableMailManually($order);
        } catch (\Throwable $e) {
            Log::warning('Manual shipment tracking email failed.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Slanje tracking emaila nije uspjelo.'], 422);
        }

        if (! empty($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json(['message' => $result['message'] ?? 'Tracking email je obrađen.']);
    }

    private function sendBoxNowShipment(Order $order)
    {
        $lock = Cache::lock(
            'boxnow-shipment-create:' . $order->id,
            self::BOXNOW_SHIPMENT_LOCK_SECONDS
        );

        if (! $lock->get()) {
            return response()->json([
                'error' => 'Kreiranje Box Now pošiljke za ovu narudžbu već je u tijeku.',
            ], 409);
        }

        try {
            // Narudžba je učitana prije stjecanja locka; osvježi je kako bi
            // drugi, upravo završeni zahtjev bio prepoznat bez novog API poziva.
            $order->refresh();
            $order->load('products');

            if ($this->hasExistingShipment($order)) {
                $shipmentId = $order->tracking_code ?: $order->shipping_parcel_id;

                return response()->json([
                    'message' => $shipmentId
                        ? 'Box Now pošiljka je već kreirana: ' . $shipmentId
                        : 'Box Now pošiljka je već kreirana za ovu narudžbu.',
                ]);
            }

            try {
                $tracking = app(BoxNowService::class)->createDeliveryRequest($order);
                app(OrderTrackingService::class)->apply($order, $tracking);
                $message = ! empty($tracking['recovered'])
                    ? 'Postojeća Box Now pošiljka pronađena je i spremljena s ID-em: ' . $tracking['parcel_id']
                    : 'Box Now pošiljka uspješno je kreirana s ID-em: ' . $tracking['parcel_id'];

                return response()->json(['message' => $message]);
            } catch (\Throwable $e) {
                Log::error('Box Now shipment failed.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Greška..! ' . $e->getMessage()], 422);
            }
        } finally {
            $lock->release();
        }
    }

    private function isBoxNowOrder(Order $order): bool
    {
        $shipping = Str::lower(
            (string) $order->shipping_carrier . ' '
            . (string) $order->shipping_method . ' '
            . (string) $order->shipping_code
        );

        return Str::contains($shipping, ['boxnow', 'box now']);
    }

    private function authorizeWoltAdministrator(Request $request): void
    {
        // Dispatch is a privileged browser action. Do not let a Sanctum bearer
        // token entering through a legacy shipping endpoint become equivalent
        // to an interactive administrator session.
        $user = auth('web')->user();

        abort_unless(
            $user
                && $request->user()
                && (int) $request->user()->getAuthIdentifier() === (int) $user->getAuthIdentifier()
                && $user->isAdministrator()
                && (bool) optional($user->details)->status,
            403
        );
    }

    private function isWoltOrder(Order $order): bool
    {
        $shipping = Str::lower(
            (string) $order->shipping_carrier . ' '
            . (string) $order->shipping_method . ' '
            . (string) $order->shipping_code
        );

        return Str::contains($shipping, ['wolt_drive', 'wolt drive', 'wolt']);
    }

    private function isGlsOrder(Order $order): bool
    {
        $shipping = Str::lower(
            (string) $order->shipping_carrier . ' '
            . (string) $order->shipping_method . ' '
            . (string) $order->shipping_code
        );

        return Str::contains($shipping, 'gls');
    }

    private function hasExistingWoltDelivery(Order $order): bool
    {
        return strtolower((string) $order->shipping_carrier) === WoltDriveService::CARRIER
            && (filled($order->shipping_parcel_id) || filled($order->tracking_code));
    }

    private function woltOrderCanBeDispatched(Order $order, WoltDriveSettingsService $settings): bool
    {
        $status = (int) $order->order_status_id;
        $blocked = [
            (int) config('settings.order.status.unfinished'),
            (int) config('settings.order.status.declined'),
            (int) config('settings.order.status.canceled'),
        ];

        if (in_array($status, $blocked, true)) {
            return false;
        }

        if (strtolower((string) $order->payment_code) === 'cod') {
            return (bool) $settings->get()['cod_enabled'] && in_array($status, [
                (int) config('settings.order.status.new'),
                (int) config('settings.order.status.paid'),
                (int) config('settings.order.status.send'),
            ], true);
        }

        return in_array($status, [
            (int) config('settings.order.status.paid'),
            (int) config('settings.order.status.send'),
        ], true);
    }

    private function hasExistingShipment(Order $order): bool
    {
        return filled($order->shipping_parcel_id)
            || filled($order->tracking_code)
            || (bool) $order->printed;
    }
}
