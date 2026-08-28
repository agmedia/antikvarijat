<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Exceptions\GiftVoucherUnavailableException;
use App\Exceptions\ShippingUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Orders\Order as BackOrder;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\Order;
use App\Models\TagManager;
use App\Services\ProductRecommendationService;
use App\Services\GiftVoucherService;
use App\Services\MailchimpAttributionService;
use App\Services\OrderNotificationService;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use App\Models\Front\Checkout\GeoZone;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    private const CART_BEST_SELLER_EXCLUDED_PRODUCT_IDS = [36754];

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function cart(Request $request, ProductRecommendationService $recommendations)
    {
        $gdl = TagManager::getGoogleCartDataLayer($this->shoppingCart()->get());
        $bestSellers = $recommendations->recentBestSellers(
            30,
            10,
            self::CART_BEST_SELLER_EXCLUDED_PRODUCT_IDS
        );

        return view('front.checkout.cart', compact('gdl', 'bestSellers'));
    }


    /**
     * @param Request $request
     * @param string  $step
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function checkout(Request $request)
    {
        $step = '';

        if ($request->has('step')) {
            $step = $request->input('step');
        }

        $is_free_shipping = (config('settings.free_shipping') < $this->shoppingCart()->get()['total']) ? true : false;

        return view('front.checkout.checkout', compact('step', 'is_free_shipping'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view(Request $request)
    {
        $data = $this->checkSession();

        if (empty($data)) {
            if ( ! session()->has(config('session.cart'))) {
                return redirect(LocaleHelper::route('kosarica'));
            }

            return redirect(LocaleHelper::route('naplata', ['step' => 'podaci']));
        }

        try {
            $this->validateShippingCheckout($data);
            $data = $this->collectData($data, config('settings.order.status.unfinished'));
            $this->validateGiftVoucherCheckout($data);
        } catch (ShippingUnavailableException $exception) {
            return redirect(LocaleHelper::route('naplata', ['step' => 'dostava']))
                ->with('error', $exception->getMessage());
        } catch (GiftVoucherUnavailableException $exception) {
            return redirect(LocaleHelper::route('kosarica'))->with('error', $exception->getMessage());
        }

        $order = new Order();

        try {
            if (CheckoutSession::hasOrder()) {
                $data['id'] = CheckoutSession::getOrder()['id'];

                $order->updateData($data);
                $order->setData($data['id']);

            } else {
                $order->createFrom($data);
            }
        } catch (GiftVoucherUnavailableException $exception) {
            return redirect(LocaleHelper::route('kosarica'))->with('error', $exception->getMessage());
        }

        if ($order->isCreated()) {
            CheckoutSession::setOrder($order->getData());
            $data['id'] = CheckoutSession::getOrder()['id'];
        }

        if (! empty($data['id'])) {
            // Tracking metadata is attached only after marketing consent and
            // never participates in order creation or payment resolution.
            app(MailchimpAttributionService::class)->attachToOrder((int) $data['id'], $request);
        }

        if (! empty($data['newsletter']) && ! empty($data['address']['email']) && ! empty($data['id'])) {
            NewsletterSubscriber::subscribe([
                'email'      => $data['address']['email'],
                'first_name' => $data['address']['fname'] ?? null,
                'last_name'  => $data['address']['lname'] ?? null,
                'user_id'    => auth()->id() ?? 0,
                'order_id'   => (int) $data['id'],
                'source'     => 'checkout',
                'gdpr'       => true,
            ]);
        }

        if (! empty($data['address']['email']) && ! empty($data['id'])) {
            NewsletterSubscriber::attachOrderToEmail($data['address']['email'], (int) $data['id']);
        }

        $data['payment_form'] = $order->resolvePaymentForm();

        return view('front.checkout.view', compact('data'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function order(Request $request)
    {
        Log::info('Payment return hit', [
            'method' => $request->method(),
            'query'  => $request->query(),
            'all'    => $request->all(),
        ]);

        // Generic: try the common identifiers
        $orderNumber = $request->input('order_number')
            ?? $request->input('OrderNumber')
            ?? $request->input('provjera');

        if (! $orderNumber) {
            // Ako neka druga metoda šalje drugi parametar, lako ga dodaš ovdje,
            // ali bez orderNumber nema smisla dalje.
            Log::warning('Payment return without order identifier', ['all' => $request->all()]);
            return redirect(LocaleHelper::route('checkout.error'));
        }

        $order = new Order();
        $order->setData($orderNumber);

        $ok = $order->finish($request);

        // Fallback za success page (session zna puknuti nakon payment redirecta)
        if ($ok && ! CheckoutSession::hasOrder()) {
            CheckoutSession::setOrder(['id' => (int) $orderNumber]);
        }

        return $ok
            ? redirect(LocaleHelper::route('checkout.success', ['order_number' => $orderNumber]))
            : redirect(LocaleHelper::route('checkout.error', ['order_number' => $orderNumber]));
    }



    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function success(Request $request)
    {



        $data['order'] = CheckoutSession::getOrder();

        // Generic fallback if session died after payment redirect
        if (! $data['order'] && $request->filled('order_number')) {
            $data['order'] = ['id' => (int) $request->input('order_number')];
        }

        if (! $data['order']) {
            return redirect(LocaleHelper::route('index'));
        }

        $order = \App\Models\Back\Orders\Order::where('id', $data['order']['id'])->first();

        if ($order) {
            // Re-check consent immediately before checkout is finalized. If
            // marketing consent was withdrawn on the payment review screen,
            // the cleared cookie removes attribution while the order is still
            // unfinished. This remains a fail-open metadata-only operation.
            app(MailchimpAttributionService::class)->attachToOrder((int) $order->id, $request);

            if (! in_array((int) $order->order_status_id, [
                (int) config('settings.order.status.new'),
                (int) config('settings.order.status.paid'),
                (int) config('settings.order.status.send'),
            ], true)) {
                Log::warning('Checkout success rejected for an unconfirmed order.', [
                    'order_id' => $order->id,
                    'order_status_id' => $order->order_status_id,
                ]);

                return redirect(LocaleHelper::route('checkout.error', ['order_number' => $order->id]));
            }

            try {
                app(GiftVoucherService::class)->completeCheckout($order);
            } catch (GiftVoucherUnavailableException $exception) {
                Log::warning('Gift voucher checkout completion failed.', [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]);

                return redirect(LocaleHelper::route('checkout.error', ['order_number' => $order->id]))
                    ->with('error', $exception->getMessage());
            }

            $notifications = app(OrderNotificationService::class);

            // Once the outbox is installed, the checkout marker and both
            // notification rows are committed together. A killed request can
            // therefore never leave a completed order without a durable admin
            // notification waiting to be sent.
            $processedNow = DB::transaction(function () use ($order, $notifications) {
                $processed = BackOrder::query()
                    ->where('id', $order->id)
                    ->whereNull('checkout_processed_at')
                    ->update([
                        'checkout_processed_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($processed) {
                    $order->refresh();
                    $notifications->enqueue($order);
                }

                return $processed;
            });

            if ($processedNow) {
                NewsletterSubscriber::attachOrderToEmail((string) $order->payment_email, (int) $order->id);

                $order->decreaseCartItems($order->products)
                      ->forgetSession();

                $this->shoppingCart()
                     ->flush()
                     ->resolveDB();

                app()->terminating(function () use ($order, $notifications) {
                    try {
                        $notifications->sendForOrder($order);
                    } catch (\Throwable $exception) {
                        // The scheduler will retry the durable rows. Never let
                        // a terminating callback hide the original response.
                        Log::warning('Immediate order notification attempt failed.', [
                            'order_id' => $order->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                });
            } else {
                Log::info('Checkout success already processed', [
                    'order_id' => $order->id,
                ]);
            }

            $data['order'] = $order->toArray();
            $data['google_tag_manager'] = TagManager::getGoogleSuccessDataLayer($order);

            return view('front.checkout.success', compact('data'));
        }

        return redirect(LocaleHelper::route('naplata', ['step' => '']));
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function error(Request $request)
    {
        $orderId = (int) ($request->input('order_number') ?: data_get(CheckoutSession::getOrder(), 'id'));

        if ($orderId > 0) {
            $order = BackOrder::query()->find($orderId);

            if ($order && in_array((int) $order->order_status_id, [
                (int) config('settings.order.status.declined'),
                (int) config('settings.order.status.canceled'),
            ], true)) {
                app(GiftVoucherService::class)->handleStatusChange($order, (int) $order->order_status_id);
            }
        }

        return view('front.checkout.error');
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @return array
     */
    private function checkSession(): array
    {
        if (CheckoutSession::hasAddress() && CheckoutSession::hasShipping() && CheckoutSession::hasPayment()) {
            return [
                'address'  => CheckoutSession::getAddress(),
                'shipping' => CheckoutSession::getShipping(),
                'payment'  => CheckoutSession::getPayment(),
                'comment'  => CheckoutSession::getComment(),
                'napomena'  => CheckoutSession::getNapomena(),
                'newsletter' => CheckoutSession::getNewsletter(),
                ];
        }

        return [];
    }


    /**
     * @param array $data
     * @param int   $order_status_id
     *
     * @return array
     */
    private function collectData(array $data, int $order_status_id): array
    {
        $shipping = (new ShippingMethod())->find((string) $data['shipping']);
        $payment  = (new PaymentMethod())->find((string) $data['payment']);

        if (! $shipping) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.shipping'));
        }

        if (! $payment) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.payment'));
        }

        $response                    = [];
        $response['address']         = $data['address'];
        $response['shipping']        = $shipping;
        $response['payment']         = $payment;
        $response['comment']         = isset($data['comment']) ? $data['comment'] : '';
        $response['napomena']         = isset($data['napomena']) ? $data['napomena'] : '';
        $response['cart']            = $this->shoppingCart()->get();
        $response['order_status_id'] = $order_status_id;

        return $response;
    }

    private function validateShippingCheckout(array $data): void
    {
        $shippingCode = (string) ($data['shipping'] ?? '');
        $cart = $this->shoppingCart()->get();

        if ($shippingCode === GiftVoucherService::SHIPPING_CODE) {
            if (app(GiftVoucherService::class)->cartContainsOnlyGiftVoucher($cart)) {
                return;
            }

            throw new ShippingUnavailableException(__('front.checkout.shipping_unavailable'));
        }

        $address = (array) ($data['address'] ?? []);
        $geo = (new GeoZone())->findState((string) ($address['state'] ?? 'Croatia'));
        $method = (new ShippingMethod())
            ->findGeo((int) $geo->id, $address, $cart)
            ->first(fn ($candidate) => (string) $candidate->code === $shippingCode);

        if (! $method) {
            throw new ShippingUnavailableException(__('front.checkout.shipping_unavailable'));
        }

        if ($shippingCode !== WoltDriveService::CARRIER) {
            return;
        }

        $paymentCode = (string) ($data['payment'] ?? '');
        $allowedPayments = (new PaymentMethod())
            ->findGeo((int) $geo->id)
            ->checkShipping($shippingCode)
            ->resolve();

        if (! $allowedPayments->contains(fn ($payment) => (string) $payment->code === $paymentCode)) {
            throw new ShippingUnavailableException(__('front.checkout.payment_unavailable'));
        }

        if ($paymentCode === 'cod' && ! app(WoltDriveSettingsService::class)->get()['cod_enabled']) {
            throw new ShippingUnavailableException(__('front.checkout.wolt_cod_unavailable'));
        }

        // Wolt uses cash details when calculating applicable fees. Keeping the
        // payment in the quote fingerprint also prevents reuse of a pre-COD
        // quote during final server-side validation.
        $cart['payment_code'] = $paymentCode;
        $quote = app(WoltDriveService::class)->quote($address, $cart);

        if (! ($quote['available'] ?? false)) {
            throw new ShippingUnavailableException(
                (string) ($quote['message'] ?? __('front.checkout.wolt_unavailable'))
            );
        }
    }

    private function validateGiftVoucherCheckout(array $data): void
    {
        $giftVouchers = app(GiftVoucherService::class);
        $cart = $data['cart'];
        $containsGiftVoucher = $giftVouchers->cartContainsGiftVoucher($cart);
        $giftVoucherOnly = $giftVouchers->cartContainsOnlyGiftVoucher($cart);

        if ($containsGiftVoucher && ! $giftVoucherOnly) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.mixed_cart'));
        }

        if ($giftVoucherOnly) {
            if (! $giftVouchers->isGiftVoucherShipping(data_get($data, 'shipping.code'))) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.shipping'));
            }

            if (! $giftVouchers->isAllowedPurchasePaymentCode(data_get($data, 'payment.code'))) {
                throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.payment'));
            }

            return;
        }

        $fullyCovered = $giftVouchers->currentCartIsFullyCovered();
        $paymentCode = (string) data_get($data, 'payment.code');

        if ($fullyCovered && $paymentCode !== GiftVoucherService::PAYMENT_CODE) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.payment'));
        }

        if (! $fullyCovered && $paymentCode === GiftVoucherService::PAYMENT_CODE) {
            throw new GiftVoucherUnavailableException(__('front.gift_voucher.errors.payment'));
        }
    }


    /**
     * @return AgCart
     */
    private function shoppingCart(): AgCart
    {
        if (session()->has(config('session.cart'))) {
            return new AgCart(session(config('session.cart')));
        }

        return new AgCart(config('session.cart'));
    }

}
