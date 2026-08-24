<?php

namespace App\Models\Front\Checkout;

use App\Helpers\Helper;
use App\Helpers\LocaleHelper;
use App\Models\Back\Orders\OrderHistory;
use App\Models\Back\Orders\OrderProduct;
use App\Models\Back\Orders\OrderTotal;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Catalog\Product;
use App\Services\GiftVoucherService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{

    protected $casts = [
        'shipping_tracking_updated_at' => 'datetime',
        'shipping_tracking_payload' => 'array',
    ];

    /**
     * @var array
     */
    public $order = [];

    /**
     * @var null|array
     */
    protected $oc_data = null;


    /**
     * Order constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->order = $data;
    }


    /**
     * @return mixed
     */
    public function getStatusAttribute()
    {
        return $this->status($this->order_status_id);
    }


    /**
     * @param int $id
     *
     * @return mixed
     */
    public function status(int $id)
    {
        $statuses = Settings::get('order', 'statuses');

        return $statuses->where('id', $id)->first();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id')->with('product');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id')->orderBy('sort_order');
    }


    /**
     * @param int $id
     *
     * @return $this
     */
    public function setData(string $id)
    {

        $data = \App\Models\Back\Orders\Order::where('id', $id)->first();

        if ($data) {
            $this->oc_data = $data;
        }

        return $this;
    }


    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->oc_data;
    }


    /**
     * @param array $data
     *
     * @return bool
     */
    public function createFrom(array $data = [])
    {
        if ( ! empty($data)) {
            $this->order = $data;
        }

        if ( ! empty($this->order) && isset($this->order['cart'])) {
            $user_id = auth()->user() ? auth()->user()->id : 0;

            $orderData = [
                'user_id'          => $user_id,
                'affiliate_id'     => 0,
                'order_status_id'  => $this->order['order_status_id'],
                'invoice'          => '',
                'total'            => $this->order['cart']['total'],
                'payment_fname'    => $this->order['address']['fname'],
                'payment_lname'    => $this->order['address']['lname'],
                'payment_address'  => $this->order['address']['address'],
                'payment_zip'      => $this->order['address']['zip'],
                'payment_city'     => $this->order['address']['city'],
                'payment_state'    => $this->order['address']['state'],
                'payment_phone'    => $this->order['address']['phone'] ?: null,
                'birthday_year'    => $this->order['address']['birthday_year'] ?: null,
                'payment_email'    => $this->order['address']['email'],
                'payment_method'   => LocaleHelper::localizedSettingField($this->order['payment'], 'title'),
                'payment_code'     => $this->order['payment']->code,
                'payment_card'     => '',
                'payment_installment' => '',
                'shipping_fname'   => $this->order['address']['fname'],
                'shipping_lname'   => $this->order['address']['lname'],
                'shipping_address' => $this->order['address']['address'],
                'shipping_zip'     => $this->order['address']['zip'],
                'shipping_city'    => $this->order['address']['city'],
                'shipping_state'   => $this->order['address']['state'],
                'shipping_phone'   => $this->order['address']['phone'] ?: null,
                'shipping_email'   => $this->order['address']['email'],
                'shipping_method'  => LocaleHelper::localizedSettingField($this->order['shipping'], 'title'),
                'shipping_code'    => $this->order['shipping']->code,
                'company'          => $this->order['address']['company'],
                'oib'              => $this->order['address']['oib'],
                'comment'          => $this->order['comment'],
                'napomena'          => $this->order['napomena'],
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ];

            if (Schema::hasColumn('orders', 'locale')) {
                $orderData['locale'] = LocaleHelper::current();
            }
            if (Schema::hasColumn('orders', 'unfinished_at')) {
                $orderData['unfinished_at'] = Carbon::now();
            }

            $order_id = DB::transaction(function () use ($orderData, $user_id) {
                $orderId = \App\Models\Back\Orders\Order::insertGetId($orderData);

                OrderHistory::insert([
                    'order_id'   => $orderId,
                    'user_id'    => $user_id,
                    'comment'    => config('settings.order.made_text'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $this->updateProducts($orderId);
                $this->updateTotal($orderId);
                app(GiftVoucherService::class)->syncOrder($orderId, $this->order);

                return $orderId;
            }, 3);

            $this->oc_data = \App\Models\Back\Orders\Order::where('id', $order_id)->first();
        }

        return $this;
    }


    /**
     * @param array $data
     *
     * @return $this|null
     */
    public function updateData(array $data)
    {
        if ( ! empty($data)) {
            $this->order = $data;
        }

        $orderData = [
            'payment_fname'    => $this->order['address']['fname'],
            'payment_lname'    => $this->order['address']['lname'],
            'payment_address'  => $this->order['address']['address'],
            'payment_zip'      => $this->order['address']['zip'],
            'payment_city'     => $this->order['address']['city'],
            'payment_state'    => $this->order['address']['state'],
            'payment_phone'    => $this->order['address']['phone'] ?: null,
            'birthday_year'    => $this->order['address']['birthday_year'] ?: null,
            'payment_email'    => $this->order['address']['email'],
            'payment_method'   => LocaleHelper::localizedSettingField($this->order['payment'], 'title'),
            'payment_code'     => $this->order['payment']->code,
            'payment_card'     => '',
            'payment_installment' => '',
            'shipping_fname'   => $this->order['address']['fname'],
            'shipping_lname'   => $this->order['address']['lname'],
            'shipping_address' => $this->order['address']['address'],
            'shipping_zip'     => $this->order['address']['zip'],
            'shipping_city'    => $this->order['address']['city'],
            'shipping_state'   => $this->order['address']['state'],
            'shipping_phone'   => $this->order['address']['phone'] ?: null,
            'shipping_email'   => $this->order['address']['email'],
            'shipping_method'  => LocaleHelper::localizedSettingField($this->order['shipping'], 'title'),
            'shipping_code'    => $this->order['shipping']->code,
            'company'          => $this->order['address']['company'],
            'comment'          => $this->order['comment'],
            'napomena'          => $this->order['napomena'],
            'oib'              => $this->order['address']['oib'],
            'updated_at'       => Carbon::now()
        ];

        if (auth()->check()) {
            $orderData['user_id'] = auth()->id();
        }

        if (Schema::hasColumn('orders', 'locale')) {
            $orderData['locale'] = LocaleHelper::current();
        }

        $orderExists = \App\Models\Back\Orders\Order::where('id', $data['id'])->exists();

        if ($orderExists) {
            DB::transaction(function () use ($data, $orderData) {
                \App\Models\Back\Orders\Order::where('id', $data['id'])->update($orderData);

            if (auth()->check()) {
                OrderHistory::where('order_id', $data['id'])->update([
                    'user_id' => auth()->id(),
                ]);
            }

            $this->updateProducts($data['id']);
            $this->updateTotal($data['id']);
                app(GiftVoucherService::class)->syncOrder((int) $data['id'], $this->order);
            }, 3);

            return $this->setData($data['id']);
        }

        return null;
    }


    /**
     * @param int $order_id
     *
     * @return bool
     */
    private function updateProducts(int $order_id)
    {
        OrderProduct::where('order_id', $order_id)->delete();

        // PRODUCTS
        foreach ($this->order['cart']['items'] as $item) {
            if (app(GiftVoucherService::class)->isGiftVoucherItem($item)) {
                continue;
            }

            $discount = 0;
            $price    = $item->price;

            if ($this->checkSpecial($item->associatedModel)) {
                $price    = $item->associatedModel->special;
                $discount = Helper::calculateDiscount($item->price, $price);
            }

            OrderProduct::insert([
                'order_id'   => $order_id,
                'product_id' => $item->id,
                'name'       => $item->name,
                'quantity'   => $item->quantity,
                'org_price'  => $item->price,
                'discount'   => $discount ? number_format($discount, 2) : 0,
                'price'      => $price,
                'total'      => $item->quantity * $price,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        return true;
    }


    /**
     * @param int $order_id
     */
    private function updateTotal(int $order_id)
    {
        OrderTotal::where('order_id', $order_id)->delete();

        // SUBTOTAL
        OrderTotal::insert([
            'order_id'   => $order_id,
            'code'       => 'subtotal',
            'title'      => __('front.email.total_subtotal'),
            'value'      => $this->order['cart']['subtotal'],
            'sort_order' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // CONDITIONS on Total
        foreach ($this->order['cart']['conditions'] as $name => $condition) {
            if ($condition->getType() == 'payment') {
                OrderTotal::insert([
                    'order_id'   => $order_id,
                    'code'       => 'payment',
                    'title'      => $name,
                    'value'      => $condition->parsedRawValue,
                    'sort_order' => $condition->getOrder(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            if ($condition->getType() == 'shipping') {
                OrderTotal::insert([
                    'order_id'   => $order_id,
                    'code'       => 'shipping',
                    'title'      => $name,
                    'value'      => $condition->parsedRawValue,
                    'sort_order' => $condition->getOrder(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            if ($condition->getType() === 'gift_voucher') {
                OrderTotal::insert([
                    'order_id'   => $order_id,
                    'code'       => 'gift_voucher',
                    'title'      => $condition->getName(),
                    'value'      => (float) $condition->getValue(),
                    'sort_order' => 4,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
        }

        // TOTAL
        OrderTotal::insert([
            'order_id'   => $order_id,
            'code'       => 'total',
            'title'      => __('front.email.total_total'),
            'value'      => $this->order['cart']['total'],
            'sort_order' => 5,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        \App\Models\Back\Orders\Order::where('id', $order_id)->update([
            'total' => $this->order['cart']['total']
        ]);
    }


    /**
     * @param Product $model
     *
     * @return bool
     */
    public function checkSpecial(Product $model): bool
    {
        if ($model->special) {
            $from = now()->subDay();
            $to = now()->addDay();

            if ($model->special_from && $model->special_from != '0000-00-00 00:00:00') {
                $from = Carbon::make($model->special_from);
            }
            if ($model->special_to && $model->special_to != '0000-00-00 00:00:00') {
                $to = Carbon::make($model->special_to);
            }

            if ($from <= now() && now() <= $to) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return mixed|null
     */
    public function resolvePaymentForm()
    {
        if ($this->isCreated()) {
            $method = new PaymentMethod($this->oc_data['payment_code']);

            return $method->resolveForm($this->oc_data);
        }

        return null;
    }


    /**
     * @param Request $request
     *
     * @return mixed|null
     */
    public function finish(Request $request)
    {
        if ($this->isCreated()) {
            $method = new PaymentMethod($this->oc_data['payment_code']);

            return $method->finish($this->oc_data, $request);
        }

        return null;
    }


    /**
     * @return bool
     */
    public function isCreated(): bool
    {
        if ($this->oc_data) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function paymentNotRequired(): bool
    {
        if (in_array($this->oc_data->payment_code, ['cod', 'bank', GiftVoucherService::PAYMENT_CODE])) {
            return true;
        }

        return false;
    }
}
