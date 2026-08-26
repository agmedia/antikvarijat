<?php

namespace App\Models\Front\Checkout;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Services\GiftVoucherService;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class ShippingMethod
 * @package App\Models\Front\Checkout
 */
class PaymentMethod
{

    /**
     * @var array|false|Collection
     */
    protected $methods;

    /**
     * @var mixed|null
     */
    protected $method = null;

    /**
     * @var mixed|null
     */
    protected $response_methods = null;


    /**
     * PaymentMethod constructor.
     *
     * @param string|null $code
     */
    public function __construct(?string $code = null)
    {
        $this->methods = $this->list();
        $this->response_methods = collect();

        if ($code) {
            $this->method = $code === GiftVoucherService::PAYMENT_CODE
                ? collect([app(GiftVoucherService::class)->giftVoucherPaymentMethod()])
                : $this->methods->where('code', $code);
        }
    }


    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }


    /**
     * @return array|false|Collection
     */
    public function getMethods()
    {
        return $this->methods;
    }


    /**
     * @param bool $only_active
     *
     * @return array|false|Collection
     */
    public function list(bool $only_active = true)
    {
        return Settings::getList('payment', 'list.%', $only_active);
    }


    /**
     * @param int $id
     *
     * @return mixed
     */
    public function id(int $id)
    {
        return $this->methods->where('id', $id)->first();
    }


    /**
     * @param string $code
     *
     * @return mixed
     */
    public function find(string $code)
    {
        if ($code === GiftVoucherService::PAYMENT_CODE) {
            return app(GiftVoucherService::class)->giftVoucherPaymentMethod();
        }

        //Log::info($this->methods->where('code', $code)->first()->code);
        return $this->methods->where('code', $code)->first();
    }


    /**
     * @param int $zone
     *
     * @return $this
     */
    public function findGeo(int $zone)
    {
        $giftVouchers = app(GiftVoucherService::class);

        if ($giftVouchers->currentCartIsFullyCovered()) {
            $method = $giftVouchers->giftVoucherPaymentMethod();
            $this->response_methods = collect([$method->code => $method]);

            return $this;
        }

        if ($giftVouchers->currentCartContainsOnlyGiftVoucher()) {
            $allowed = $giftVouchers->allowedPurchasePaymentCodes();
            $this->response_methods = $this->methods
                ->filter(fn ($method) => in_array($method->code, $allowed, true))
                ->keyBy('code');

            return $this;
        }

        $geo = (new GeoZone())->findApplicableToAll();

        foreach ($this->methods as $method) {
            if ($method->geo_zone == $geo->id || ! $method->geo_zone) {
                $this->response_methods->put($method->code, $method);
            }
        }

        foreach ($this->methods as $method) {
            if ($method->geo_zone == $zone) {
                $this->response_methods->put($method->code, $method);
            }
        }

        return $this;
    }


    /**
     * @param string $shipping
     *
     * @return $this
     */
    public function checkShipping(string $shipping)
    {
        $giftVouchers = app(GiftVoucherService::class);

        if ($giftVouchers->currentCartIsFullyCovered()) {
            $method = $giftVouchers->giftVoucherPaymentMethod();
            $this->response_methods = collect([$method->code => $method]);

            return $this;
        }

        if ($giftVouchers->isGiftVoucherShipping($shipping)
            || $giftVouchers->currentCartContainsOnlyGiftVoucher()) {
            $allowed = $giftVouchers->allowedPurchasePaymentCodes();
            $this->response_methods = $this->methods
                ->filter(fn ($method) => in_array($method->code, $allowed, true))
                ->keyBy('code');

            return $this;
        }

        $corvusCodes = ['corvus', 'corvus_wallets'];
        $restrictedCodes = array_merge(['pickup', 'bank'], $corvusCodes);

        if ($shipping === 'pickup') {
            $this->response_methods = collect();
            $allowedCodes = array_merge(['pickup'], $corvusCodes);

            foreach ($this->methods as $method) {
                if (in_array($method->code, $allowedCodes, true)) {
                    $this->response_methods->put($method->code, $method);
                }
            }

            return $this;
        }

        if ($shipping === 'gls_eu') {
            $this->response_methods = collect();
            $allowedCodes = array_merge(['bank'], $corvusCodes);

            foreach ($this->methods as $method) {
                if (in_array($method->code, $allowedCodes, true)) {
                    $this->response_methods->put($method->code, $method);
                }
            }

            return $this;
        }

        if ($shipping === 'boxnow') {
            $this->response_methods = $this->response_methods
                ->filter(fn ($method) => in_array($method->code, $corvusCodes, true))
                ->keyBy('code');

            return $this;
        }

        if ($shipping === WoltDriveService::CARRIER) {
            $allowedCodes = array_merge(['bank'], $corvusCodes);

            if (app(WoltDriveSettingsService::class)->get()['cod_enabled']) {
                $allowedCodes[] = 'cod';
            }

            $this->response_methods = $this->response_methods
                ->filter(fn ($method) => in_array($method->code, $allowedCodes, true))
                ->keyBy('code');

            return $this;
        }

        foreach ($restrictedCodes as $code) {
            $this->response_methods->forget($code);
        }

        if (in_array($shipping, ['gls', 'gls_world'], true)) {
            $allowedCodes = array_merge(['bank'], $corvusCodes);

            foreach ($this->methods as $method) {
                if (in_array($method->code, $allowedCodes, true)) {
                    $this->response_methods->put($method->code, $method);
                }
            }
        }

        return $this;
    }


    /**
     * @return Collection
     */
    public function resolve(): Collection
    {
        return $this->response_methods;
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param $order
     *
     * @return mixed|null
     */
    public function resolveForm($order)
    {
        if ($this->method->count()) {
            $provider = $this->providers($this->method->first()->code);
            $payment = new $provider($order);

            return $payment->resolveFormView($this->method->collect());
        }

        return null;
    }


    /**
     * @param $order
     *
     * @return mixed|null
     */
    public function finish(\App\Models\Back\Orders\Order $order, Request $request)
    {
        if ($this->method->count()) {
            $provider = $this->providers($this->method->first()->code);
            $payment = new $provider($order);

            return $payment->finishOrder($order, $request);
        }

        return null;
    }


    /**
     * @param string|null $key
     *
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private function providers(?string $key = null)
    {
        $providers = config('settings.payment.providers');

        if ($key) {
            return $providers[$key];
        }

        return $providers;
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/


    /**
     * @return \Darryldecode\Cart\CartCondition|false
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    public static function condition($cart = null)
    {
        $payment = false;
        $condition = false;

        if (CheckoutSession::hasPayment()) {
            $payment = (new PaymentMethod())->find(CheckoutSession::getPayment());
        }

        if ($payment) {
            $value = $payment->data->price;

            if ($payment->code !== GiftVoucherService::PAYMENT_CODE
                && $cart->getTotal() > config('settings.free_shipping')) {
                $value = 0;
            }

            $condition = new \Darryldecode\Cart\CartCondition(array(
                'name' => __('front.email.total_payment'),
                'type' => 'payment',
                'target' => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value' => '+' . $value ?: 0,
                'attributes' => [
                    'description' => LocaleHelper::localizedSettingDataField($payment, 'short_description') ?: '',
                    'geo_zone' => $payment->geo_zone ?: 0
                ]
            ));
        }

        return $condition;
    }
}
