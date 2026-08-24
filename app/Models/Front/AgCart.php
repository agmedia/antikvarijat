<?php

namespace App\Models\Front;

use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Cart\Totals;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\ProductAction;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use App\Models\TagManager;
use App\Services\GiftVoucherService;
use Darryldecode\Cart\CartCondition;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgCart extends Model
{

    /**
     * @var string
     */
    private $cart_id;

    /**
     * @var
     */
    private $cart;


    /**
     * AgCart constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->cart_id = $id;
        $this->cart    = Cart::session($id);
    }


    /**
     * @return array
     */
    public function get()
    {
        $detail_conditions = $this->setCartConditions();
        $eur = $this->getEur();
        $giftVouchers = app(GiftVoucherService::class);
        $items = $this->cart->getContent();

        $response = [
            'id'         => $this->cart_id,
            'coupon'     => session()->has($this->couponSessionKey()) ? session($this->couponSessionKey()) : '',
            'items'      => $items,
            'count'      => $this->cart->getTotalQuantity(),
            'subtotal'   => $this->cart->getSubTotal(),
            'conditions' => $this->cart->getConditions(),
            'detail_con' => $detail_conditions,
            'total'      => $this->cart->getTotal(),
            'eur'        => $eur,
            'secondary_price' => $eur,
            'has_gift_voucher' => $items->contains(fn ($item) => $giftVouchers->isGiftVoucherItem($item)),
            'gift_voucher_only' => $items->isNotEmpty()
                && $items->every(fn ($item) => $giftVouchers->isGiftVoucherItem($item)),
        ];

        return $response;
    }


    /**
     * @param bool $just_basic
     *
     * @return Collection
     */
    public function getCartItems(bool $just_basic = false): Collection
    {
        $response = collect();

        foreach ($this->cart->getContent() as $item) {
            if ($just_basic) {
                $data = ['id' => $item->id, 'quantity' => $item->quantity];
                $response->push($data);
            } else {
                $response->push($item);
            }
        }

        return $response;
    }


    /**
     * @return null
     */
    public function getEur()
    {
        if (isset(Currency::secondary()->value)) {
            return Currency::secondary()->value;
        }

        if (isset($eur->status) && $eur->status) {
            return $eur->value;
        }

        return null;
    }


    /**
     * @param      $request
     * @param null $id
     *
     * @return array
     */
    public function check($request)
    {
        $ids = collect($request['ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();
        $products = Product::whereIn('id', $ids)->pluck('quantity', 'id');
        $message = null;

        foreach ($products as $id => $quantity) {
            if ( ! $quantity) {
                $this->remove(intval($id));

                $product = Product::where('id', intval($id))->first();

                // $message = 'Nažalost, knjiga ' . substr($product->name, 0, 150) . ' više nije dostupna.';
            }
        }

        return [
            'cart' => $this->get(),
            'message' => $message
        ];
    }


    /**
     * @param      $request
     * @param null $id
     *
     * @return array
     */
    public function add($request, $id = null)
    {
        $requestItem = $this->extractRequestItem($request);
        $giftVouchers = app(GiftVoucherService::class);
        $isGiftVoucher = ($requestItem['type'] ?? null) === GiftVoucherService::CART_ITEM_TYPE
            || ($requestItem['id'] ?? null) === GiftVoucherService::CART_ITEM_ID;

        if ($isGiftVoucher) {
            if ($this->cart->getContent()->contains(fn ($item) => ! $giftVouchers->isGiftVoucherItem($item))) {
                return ['error' => __('front.gift_voucher.errors.mixed_cart')];
            }

            if ($this->cart->has(GiftVoucherService::CART_ITEM_ID)) {
                $this->cart->remove(GiftVoucherService::CART_ITEM_ID);
            }

            return $this->addToCart(['item' => $requestItem]);
        }

        if ($this->cart->getContent()->contains(fn ($item) => $giftVouchers->isGiftVoucherItem($item))) {
            return ['error' => __('front.gift_voucher.errors.mixed_cart')];
        }

        // Updejtaj artikl sa apsolutnom količinom.
        foreach ($this->cart->getContent() as $item) {
            if ($item->id == ($requestItem['id'] ?? null)) {
                $quantity = (int) ($requestItem['quantity'] ?? 1);
                $product  = Product::where('id', $requestItem['id'])->first();

                if (! $product) {
                    return ['error' => __('front.gift_voucher.errors.unavailable')];
                }

                if (($quantity + $item->quantity) > $product->quantity) {
                    return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
                }

                if ($quantity == 1 && ($item->quantity == 1 || $item->quantity > $quantity)) {
                    if ( ! $id) {
                        $quantity = $item->quantity + 1;
                    }
                }

                $relative = false;

                if (! empty($requestItem['relative'])) {
                    $relative = true;
                }

                return $this->updateCartItem($item->id, $quantity, $relative);
            }
        }

        return $this->addToCart(['item' => $requestItem]);
    }


    /**
     * @param $id
     *
     * @return array
     */
    public function remove($id)
    {
        $this->cart->remove($id);

        return $this->get();
    }


    /**
     * @param $coupon
     *
     * @return array
     */
    public function coupon($coupon)
    {
        $giftVouchers = app(GiftVoucherService::class);
        $coupon = $giftVouchers->normalizeCode($coupon);

        if ($this->cart->getContent()->contains(fn ($item) => $giftVouchers->isGiftVoucherItem($item))) {
            session()->forget($this->couponSessionKey());

            return 0;
        }

        // refresh košarice…
        foreach ($this->cart->getContent() as $item) {
            $this->remove($item->id);
            $this->addToCart($this->resolveItemRequest($item));
        }

        if ($giftVouchers->isValidCodeForCurrentCart($coupon)) {
            session([$this->couponSessionKey() => $coupon]);

            return 1;
        }

        $has_coupon = ProductAction::active()->where('coupon', $coupon)->exists();

        if ($has_coupon) {
            session([$this->couponSessionKey() => $coupon]);
            return 1;
        }

        session()->forget($this->couponSessionKey());
        return 0;
    }


    /**
     *
     * @return array
     */
    public function flush()
    {
        $this->cart->clear();
        session()->forget($this->couponSessionKey());

        Helper::flushCache('cart', $this->cart_id);

        return $this;
    }


    /**
     * @param $item
     *
     * @return array[]
     */
    public function resolveItemRequest($item)
    {
        $giftVouchers = app(GiftVoucherService::class);

        if ($giftVouchers->isGiftVoucherItem($item)) {
            return $giftVouchers->buildCartItemRequest($giftVouchers->extractVoucherData($item));
        }

        return [
            'item' => [
                'id'       => $item['id'],
                'quantity' => $item['quantity']
            ]
        ];
    }


    /**
     * If user is logged store or update the DB session.
     *
     * @param $response
     */
    public function resolveDB(): void
    {
        $cart = $this->get();

        if (Auth::user()) {
            $has_cart = \App\Models\Cart::where('user_id', Auth::user()->id)->first();

            if ($has_cart) {
                \App\Models\Cart::edit($cart);
            } else {
                \App\Models\Cart::store($cart);
            }
        }
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    public function setCartConditions()
    {
        $this->cart->clearCartConditions();
        $giftVouchers = app(GiftVoucherService::class);
        $isGiftVoucherPurchase = $this->cart->getContent()
            ->contains(fn ($item) => $giftVouchers->isGiftVoucherItem($item));

        $shipping_method    = ShippingMethod::condition($this->cart);
        $payment_method     = PaymentMethod::condition($this->cart);
        $special_condition  = Helper::hasSpecialCartCondition($this->cart);
        $loyalty_conditions = Helper::hasLoyaltyCartConditions($this->cart, intval($this->loyalty));

        // UZMI kupon iz sessiona i osiguraj string
        $coupon_code = (string) (session($this->couponSessionKey()) ?? '');
        $hasGiftVoucherCode = ! $isGiftVoucherPurchase
            && $coupon_code !== ''
            && $giftVouchers->isValidCodeForCurrentCart($coupon_code);

        // --- apply conditions ---
        if ($payment_method) {
            $str = str_replace('+', '', $payment_method->getValue());
            if (number_format((float)$str) > 0) {
                $this->cart->condition($payment_method);
            }
        }

        if ($shipping_method) {
            $this->cart->condition($shipping_method);
        }

        if ($special_condition && ! $isGiftVoucherPurchase) {
            $this->cart->condition($special_condition);
        }

        if ($coupon_code !== '' && ! $hasGiftVoucherCode && ! $isGiftVoucherPurchase) {
            if ($coupon_conditions = Helper::hasCouponCartConditions($this->cart, $coupon_code)) {
                $this->cart->condition($coupon_conditions);
            }
        }

        if ($loyalty_conditions && ! $isGiftVoucherPurchase) {
            $this->cart->condition($loyalty_conditions);
        }

        if ($hasGiftVoucherCode && ($giftVoucherCondition = $giftVouchers->cartCondition($this->cart, $coupon_code))) {
            $this->cart->condition($giftVoucherCondition);
        }

        // Style response array …
        $response = [];
        foreach ($this->cart->getConditions() as $condition) {
            $response[] = [
                'name'       => $condition->getName(),
                'type'       => $condition->getType(),
                'target'     => 'total',
                'value'      => $condition->getValue(),
                'attributes' => $condition->getAttributes(),
            ];
        }

        return $response;
    }



    /**
     * @param $request
     *
     * @return array
     */
    private function addToCart($request): array
    {
        $item = $this->structureCartItem($request);

        if (isset($item['error'])) {
            return $item;
        }

        $this->cart->add($item);

        return $this->get();
    }


    /**
     * @param      $id
     * @param      $quantity
     * @param bool $relative
     *
     * @return array
     */
    private function updateCartItem($id, $quantity, bool $relative): array
    {
        $this->cart->update($id, [
            'quantity' => [
                'relative' => $relative,
                'value'    => $quantity
            ],
        ]);

        return $this->get();
    }


    /**
     * @param $request
     *
     * @return array
     */
    private function structureCartItem($request)
    {
        $requestItem = $this->extractRequestItem($request);

        if (($requestItem['type'] ?? null) === GiftVoucherService::CART_ITEM_TYPE
            || ($requestItem['id'] ?? null) === GiftVoucherService::CART_ITEM_ID) {
            return app(GiftVoucherService::class)->buildCartItem($requestItem);
        }

        $product = Product::where('id', $requestItem['id'] ?? 0)->first();

        if (! $product) {
            return ['error' => 'Artikl više nije dostupan.'];
        }

        $product->dataLayer = TagManager::getGoogleProductDataLayer($product);

        if (($requestItem['quantity'] ?? 1) > $product->quantity) {
            return ['error' => 'Nažalost nema dovoljnih količina artikla..!'];
        }

        $response = [
            'id'              => $product->id,
            'name'            => $product->name,
            'price'           => $product->price,
            'sec_price'       => $product->secondary_price,
            'quantity'        => (int) ($requestItem['quantity'] ?? 1),
            'associatedModel' => $product,
            'attributes'      => $this->structureCartItemAttributes($product)
        ];

        $conditions = $this->structureCartItemConditions($product);

        if ($conditions) {
            $response['conditions'] = $conditions;
        }

        return $response;
    }


    /**
     * @param $product
     *
     * @return string[]
     */
    private function structureCartItemAttributes($product)
    {
        return [
            'path' => $product->url,
            'tax' => $product->tax($product->tax_id)
        ];
    }


    /**
     * @param $product
     *
     * @return CartCondition|bool
     * @throws \Darryldecode\Cart\Exceptions\InvalidConditionException
     */
    private function structureCartItemConditions($product)
    {
        // Ako artikl ima akciju.
        if ($product->special()) {
            return new CartCondition([
                'name'  => 'Akcija',
                'type'  => 'promo',
                'value' => -($product->price - $product->special())
            ]);
        }

        // Ako nema akcije na artiklu.
        // Ako nije ispravan kupon.
        return false;
    }

    private function extractRequestItem($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            return (array) $request->input('item', []);
        }

        return (array) data_get($request, 'item', []);
    }

    private function couponSessionKey(): string
    {
        return config('session.cart') . '_coupon';
    }

}
