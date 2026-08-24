<?php

namespace App\Models\Front\Checkout;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Services\GiftVoucherService;
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

        //Log::info($this->methods->where('code', $code)->first()->code);
        return $this->methods->where('code', $code)->first();
    }


    /**
     * @param int $zone
     *
     * @return Collection
     */
    public function findGeo(int $zone): Collection
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

        return $methods;
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

        if ($shipping) {
            $value = $shipping->data->price;

            if (! app(GiftVoucherService::class)->isGiftVoucherShipping($shipping->code)
                && $cart->getTotal() > config('settings.free_shipping')) {
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
}
