<?php

namespace App\Models\Front\Checkout;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Services\GiftVoucherService;
use App\Services\Shipping\ShippingRuleService;
use App\Services\Shipping\WoltDriveService;
use App\Services\Shipping\WoltDriveSettingsService;
use Illuminate\Support\Collection;

/**
 * Class ShippingMethod
 * @package App\Models\Front\Checkout
 */
class ShippingMethod
{

    /**
     * @var array|false|Collection
     */
    protected $methods;


    /**
     * ShippingMethod constructor.
     */
    public function __construct()
    {
        $this->methods = $this->list();
    }


    /**
     * @param bool $only_active
     *
     * @return array|false|Collection
     */
    public function list(bool $only_active = true)
    {
        return Settings::getList('shipping', 'list.%', $only_active);
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
        if (app(GiftVoucherService::class)->isGiftVoucherShipping($code)) {
            return app(GiftVoucherService::class)->shippingMethod();
        }

        $method = $this->methods->where('code', $code)->first();

        if ($method && $method->code === WoltDriveService::CARRIER && ! $this->woltIsAvailable()) {
            return null;
        }

        return $method;
    }


    /**
     * @param int $zone
     *
     * @return Collection
     */
    public function findGeo(int $zone, array $address = [], array $cart = []): Collection
    {
        if (app(GiftVoucherService::class)->currentCartContainsOnlyGiftVoucher()) {
            return collect([app(GiftVoucherService::class)->shippingMethod()]);
        }

        $methods = collect();

        foreach ($this->methods as $method) {
            if ($method->geo_zone == $zone) {
                $methods->push($method);
            }
        }

        $methods = $methods->filter(function ($method) {
            return $method->code !== WoltDriveService::CARRIER || $this->woltIsAvailable();
        });

        return app(ShippingRuleService::class)->filter($methods, $address, $cart);
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    public static function condition($cart = null)
    {
        $shipping = false;
        $condition = false;

        if (CheckoutSession::hasShipping()) {
            $shipping = (new ShippingMethod())->find(CheckoutSession::getShipping());
        }

        if ($shipping && $cart) {
            $cartContext = [
                'subtotal' => (float) $cart->getSubTotal(),
                'total' => (float) $cart->getTotal(),
                'count' => (int) $cart->getTotalQuantity(),
                'items' => $cart->getContent(),
                'payment_code' => (string) (CheckoutSession::getPayment() ?: ''),
            ];

            $address = (array) (CheckoutSession::getAddress() ?: []);
            $availability = app(ShippingRuleService::class)->evaluate($shipping, $address, $cartContext);

            if (! $availability['available']) {
                return false;
            }

            $quotePrice = null;

            if ($shipping->code === WoltDriveService::CARRIER) {
                $quote = app(WoltDriveService::class)->checkoutQuote($address, $cartContext);

                if (! $quote) {
                    return false;
                }

                $quotePrice = isset($quote['price']) ? (float) $quote['price'] : null;
            }

            $value = app(ShippingRuleService::class)->priceFor(
                $shipping,
                (float) $cartContext['subtotal'],
                $quotePrice
            );

            if (app(GiftVoucherService::class)->isGiftVoucherShipping($shipping->code)) {
                $value = 0;
            }

            $condition = new \Darryldecode\Cart\CartCondition(array(
                'name' => LocaleHelper::localizedSettingField($shipping, 'title'),
                'type' => 'shipping',
                'target' => 'total', // this condition will be applied to cart's subtotal when getSubTotal() is called.
                'value' => '+' . $value,
                'attributes' => [
                    'description' => LocaleHelper::localizedSettingDataField($shipping, 'short_description'),
                    'geo_zone' => $shipping->geo_zone
                ]
            ));
        }

        return $condition;
    }

    private function woltIsAvailable(): bool
    {
        try {
            $settings = app(WoltDriveSettingsService::class);

            return $settings->isEnabled() && $settings->isReady();
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
