<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Orders\Order as BackOrder;
use App\Models\Back\Settings\Settings;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\Order;
use App\Models\TagManager;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $data = $this->collectData($data, config('settings.order.status.unfinished'));

        $order = new Order();

        if (CheckoutSession::hasOrder()) {
            $data['id'] = CheckoutSession::getOrder()['id'];

            $order->updateData($data);
            $order->setData($data['id']);

        } else {
            $order->createFrom($data);
        }

        if ($order->isCreated()) {
            CheckoutSession::setOrder($order->getData());
            $data['id'] = CheckoutSession::getOrder()['id'];
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
            $processedNow = \App\Models\Back\Orders\Order::query()
                ->where('id', $order->id)
                ->whereNull('checkout_processed_at')
                ->update([
                    'checkout_processed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($processedNow) {
                NewsletterSubscriber::attachOrderToEmail((string) $order->payment_email, (int) $order->id);

                $order->decreaseCartItems($order->products)
                      ->forgetSession();

                $this->shoppingCart()
                     ->flush()
                     ->resolveDB();

                app()->terminating(function () use ($order) {
                    $this->sendOrderNotifications($order);
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
    public function error()
    {
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
        $shipping = Settings::getList('shipping')->where('code', $data['shipping'])->first();
        $payment  = Settings::getList('payment')->where('code', $data['payment'])->first();

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


    /**
     * @param BackOrder $order
     *
     * @return void
     */
    private function sendOrderNotifications(BackOrder $order): void
    {
        $orderId = $order->id;
        $order = BackOrder::query()->find($orderId);

        if (! $order) {
            Log::warning('Order notifications skipped because order was not found', [
                'order_id' => $orderId,
            ]);

            return;
        }

        $this->sendAdminOrderNotification($order);
        $this->sendCustomerOrderNotification($order);
    }


    /**
     * @param BackOrder $order
     *
     * @return void
     */
    private function sendAdminOrderNotification(BackOrder $order): void
    {
        $email = config('mail.admin');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Admin order notification skipped because admin email is invalid', [
                'order_id' => $order->id,
                'email' => $email,
            ]);

            return;
        }

        try {
            Log::info('Admin order notification sending', [
                'order_id' => $order->id,
                'email' => $email,
            ]);

            Mail::to($email)->send(new OrderReceived($order));

            Log::info('Admin order notification sent', [
                'order_id' => $order->id,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Admin order notification failed', [
                'order_id' => $order->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * @param BackOrder $order
     *
     * @return void
     */
    private function sendCustomerOrderNotification(BackOrder $order): void
    {
        $email = trim((string) $order->payment_email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Customer order confirmation skipped because email is invalid', [
                'order_id' => $order->id,
                'email' => $order->payment_email,
            ]);

            return;
        }

        try {
            Mail::to($email)->send(new OrderSent($order));
        } catch (\Throwable $e) {
            Log::warning('Customer order notification failed', [
                'order_id' => $order->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
