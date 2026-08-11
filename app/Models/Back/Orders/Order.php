<?php

namespace App\Models\Back\Orders;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Models\AbandonedCartReminder;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Users\Client;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Bouncer;
use Illuminate\Support\Facades\Log;

use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class Order extends Model
{

    /**
     * @var string
     */
    protected $table = 'orders';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'unfinished_at' => 'datetime',
        'shipping_tracking_updated_at' => 'datetime',
        'shipping_tracking_email_sent_at' => 'datetime',
        'shipping_tracking_payload' => 'array',
    ];

    /**
     * @var Request
     */
    protected $request;


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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }



    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id')->with('product');
    }


    /**
     * Lightweight relation used for counts and list views.
     */
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function history()
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->orderBy('created_at');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id')->orderBy('sort_order');
    }

    public function abandonedCartReminders()
    {
        return $this->hasMany(AbandonedCartReminder::class, 'order_id')->orderBy('sequence');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_id')->latest('id');
    }

    public function resolvedLocale(): string
    {
        $locale = mb_strtolower(trim((string) $this->getAttribute('locale')));

        if (in_array($locale, [LocaleHelper::DEFAULT_LOCALE, LocaleHelper::ENGLISH_LOCALE], true)) {
            return $locale;
        }

        $transaction = $this->relationLoaded('transactions')
            ? $this->transactions->first()
            : $this->transactions()->first();
        $transactionLocale = mb_strtolower(trim((string) optional($transaction)->lang));

        if (in_array($transactionLocale, [LocaleHelper::DEFAULT_LOCALE, LocaleHelper::ENGLISH_LOCALE], true)) {
            return $transactionLocale;
        }

        // Narudžbe nastale prije spremanja locale polja još uvijek možemo
        // prepoznati po lokaliziranom nazivu odabranog plaćanja ili dostave.
        try {
            $titles = [
                [
                    'stored' => (string) $this->payment_method,
                    'english' => LocaleHelper::paymentTitle($this->payment_code, null, LocaleHelper::ENGLISH_LOCALE),
                    'croatian' => LocaleHelper::paymentTitle($this->payment_code, null, LocaleHelper::DEFAULT_LOCALE),
                ],
                [
                    'stored' => (string) $this->shipping_method,
                    'english' => LocaleHelper::shippingTitle($this->shipping_code, null, LocaleHelper::ENGLISH_LOCALE),
                    'croatian' => LocaleHelper::shippingTitle($this->shipping_code, null, LocaleHelper::DEFAULT_LOCALE),
                ],
            ];

            foreach ($titles as $title) {
                if ($title['english'] !== ''
                    && $title['english'] !== $title['croatian']
                    && trim($title['stored']) === trim($title['english'])) {
                    return LocaleHelper::ENGLISH_LOCALE;
                }
            }
        } catch (\Throwable $exception) {
            // Na instalaciji bez tablice postavki siguran je hrvatski fallback.
        }

        return LocaleHelper::DEFAULT_LOCALE;
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePaid($query)
    {
        return $query->where('order_status_id', 3)->orWhere('order_status_id', 4);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeLast($query, $count = 9)
    {
        return $query
            ->whereIn('order_status_id', static::latestDashboardStatusIds())
            ->orderByDesc('created_at')
            ->limit($count);
    }

    public function scopeFinished($query, $count = 9)
    {
        return $query
            ->whereIn('order_status_id', static::dashboardCompletedStatusIds())
            ->orderByDesc('created_at')
            ->limit($count);
    }

    /**
     * Business definition of a completed/valid dashboard sale.
     *
     * COD orders stay in "Novo" until they are processed, while card orders
     * become "Plaćeno". Both must therefore count as completed sales.
     */
    public static function dashboardCompletedStatusIds(): array
    {
        return static::statusIds([
            config('settings.order.status.new'),
            config('settings.order.status.paid'),
            config('settings.order.status.send'),
        ]);
    }

    public static function reviewEligibleStatusIds(): array
    {
        return static::statusIds([
            config('settings.order.status.new'),
            config('settings.order.status.paid'),
            config('settings.order.status.send'),
        ]);
    }

    public static function latestDashboardStatusIds(): array
    {
        return static::statusIds(array_merge([
            config('settings.order.status.awaiting_payment', 2),
        ], static::dashboardCompletedStatusIds()));
    }

    public function scopeDashboardSales(Builder $query): Builder
    {
        return $query->whereIn('order_status_id', static::dashboardCompletedStatusIds());
    }

    private static function statusIds(array $statuses): array
    {
        return array_values(array_unique(array_map(
            'intval',
            array_filter($statuses, static fn ($status) => $status !== null)
        )));
    }



    /**
     * @param       $query
     * @param array $params
     *
     * @return mixed
     */
    public function scopeChartData($query, array $params)
    {
        return $query
            ->whereBetween('created_at', [$params['from'], $params['to']])
            ->dashboardSales()
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($val) use ($params) {
                return \Illuminate\Support\Carbon::parse($val->created_at)->format($params['group']);
            });
    }


    /**
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'payment'  => 'required',
            'shipping' => 'required',
            'fname'           => 'required',
            'lname'           => 'required',
            'address'         => 'required',
            'city'            => 'required',
            'state'            => 'required',
            'zip'             => 'required',
            'email'           => 'required',
            'items'           => 'required',
            'sums'            => 'required',
            'shipping_amount'=> 'nullable|numeric',
            'payment_amount' => 'nullable|numeric',   // DODANO
        ]);

        $this->setRequest($request);

        return $this;
    }


    /**
     * @param Request $request
     *
     * @return $this
     */
    public function validateMakeRequest(Request $request)
    {
        $request->validate([
            'order_data'   => 'required',
            'payment'      => 'required',
            'fname'        => 'required',
            'lname'        => 'required',
            'address'      => 'required',
            'zip'          => 'required',
            'city'         => 'required',
            'email'        => 'required',
            'phone'        => 'required',
            'ship_fname'   => 'required',
            'ship_lname'   => 'required',
            'ship_address' => 'required',
            'ship_zip'     => 'required',
            'ship_city'    => 'required',
            'ship_email'   => 'required',
            'ship_phone'   => 'required',
        ]);

        $this->setRequest($request);

        return $this;
    }


    /**
     *
     * @return bool
     */
    public function make()
    {
        $id = $this->insertGetId([
            'user_id'          => auth()->user() ? auth()->user()->id : 0,
            'order_status_id'  => 1,
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => $this->request->payment,
            'payment_code'     => null,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => $this->request->shipping,
            'shipping_code'    => $this->request->shipping,
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            (new OrderProduct())->make($this->request, $id);
            (new OrderTotal())->make($this->request, $id);

            OrderHistory::store($id);

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param null $id
     *
     * @return bool
     */
    public function store($id = null)
    {
        $order = $id ? $this->updateData($id) : $this->storeData();

        if (!$order) {
            return false;
        }

        // 1) Artikli
        OrderProduct::store(json_decode($this->request->items), $order->id);

        // 2) Totals iz forme (ako je JSON razbijen, dobit ćemo null -> napravimo prazan niz)
        $totals = json_decode($this->request->sums, true) ?: [];

        // 3) Ako je poslan iznos dostave, resolve-aj title iz Settings i upiši/override-aj "shipping"
        if ($this->request->filled('shipping_amount')) {
            // title iz postavki za kod dostave
            $shippingSetting = Settings::get('shipping', 'list.' . $this->request->shipping)->first();
            $shippingTitle   = $shippingSetting ? LocaleHelper::localizedSettingField($shippingSetting, 'title') : __('front.email.total_shipping');

            // normalizacija broja (podržava i "55,00")
            $shippingValue = (float) str_replace(',', '.', $this->request->shipping_amount);

            $found = false;
            foreach ($totals as &$t) {
                // podrži i array i object
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                if ($code === 'shipping') {
                    if (is_array($t)) {
                        $t['value'] = $shippingValue;
                        $t['title'] = $t['title'] ?? ($t['name'] ?? $shippingTitle);
                    } else {
                        $t->value = $shippingValue;
                        $t->title = $t->title ?? ($t->name ?? $shippingTitle);
                    }
                    $found = true;
                    break;
                }
            }
            unset($t);

            if (!$found) {
                // dodaj shipping red
                $totals[] = [
                    'code'  => 'shipping',
                    'title' => $shippingTitle,
                    'value' => $shippingValue,
                ];
            }
        }

        // 3b) Ako je payment COD i poslan iznos naknade, upiši/override-aj "payment"
        if (strtolower($this->request->payment) === 'cod' && $this->request->filled('payment_amount')) {
            $paymentTitle = __('front.email.total_payment');
            $paymentValue = (float) str_replace(',', '.', $this->request->payment_amount);

            $foundPayment = false;
            foreach ($totals as &$t) {
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                if ($code === 'payment') {
                    if (is_array($t)) {
                        $t['value'] = $paymentValue;
                        $t['title'] = $t['title'] ?? ($t['name'] ?? $paymentTitle);
                    } else {
                        $t->value = $paymentValue;
                        $t->title = $t->title ?? ($t->name ?? $paymentTitle);
                    }
                    $foundPayment = true;
                    break;
                }
            }
            unset($t);

            if (!$foundPayment) {
                $totals[] = [
                    'code'  => 'payment',
                    'title' => $paymentTitle,
                    'value' => $paymentValue,
                ];
            }
        } else {
            // (Opcionalno) ako payment NIJE COD, ukloni eventualni 'payment' red iz totals
            $totals = array_values(array_filter($totals, function ($t) {
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                return $code !== 'payment';
            }));
        }

        // 4) Spremi totals
        OrderTotal::store($totals, $order->id);

        return $order;
    }



    /**
     * @return bool
     */
    private function storeData()
    {
        $payment = Settings::get('payment', 'list.' . $this->request->payment)->first();
        $shipping = Settings::get('shipping', 'list.' . $this->request->shipping)->first();

        $id = $this->insertGetId([
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => LocaleHelper::localizedSettingField($payment, 'title'),
            'payment_code'     => $payment->code,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => LocaleHelper::localizedSettingField($shipping, 'title'),
            'shipping_code'    => $shipping->code,
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            OrderHistory::store($id);

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param $id
     *
     * @return bool
     */
    private function updateData($id)
    {
        $payment = Settings::get('payment', 'list.' . $this->request->payment)->first();
        $shipping = Settings::get('shipping', 'list.' . $this->request->shipping)->first();

        $updated = $this->where('id', $id)->update([
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => LocaleHelper::localizedSettingField($payment, 'title'),
            'payment_code'     => $payment->code,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => LocaleHelper::localizedSettingField($shipping, 'title'),
            'shipping_code'    => $shipping->code,
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'updated_at'       => Carbon::now()
        ]);

        if ($updated) {
            $order = $this->find($id);

            $request = new Request([
                'status' => 0,
                'comment' => 'Izmjenjeni podaci narudžbe.!'
            ]);

            OrderHistory::store($id, $request);

            return $order;
        }

        return false;
    }


    /**
     * Set Model request variable.
     *
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getLatest($count = 15)
    {
        $query = (new Order())->newQuery();

        return $query->with('status')->orderBy('id', 'desc')->limit($count)->get();
    }


    /**
     * @param Request $request
     *
     * @return Builder
     */
    public function filter(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->newQuery();

        if ($request->input('dashboard_group') === 'sales') {
            $query->dashboardSales();
        }

        // STATUS
        if ($request->filled('status')) {
            $query->where('order_status_id', $request->input('status'));
        }

        // SEARCH (kupac, email, id narudžbe + kupljeni artikl)
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $isNumericSearch = ctype_digit($s);

            if ($isNumericSearch) {
                $query->where(function ($q) use ($s) {
                    $q->where('id', (int) $s)
                        ->orWhereExists(function ($sub) use ($s) {
                            $sub->from('order_products as op')
                                ->whereColumn('op.order_id', 'orders.id')
                                ->where('op.product_id', (int) $s);
                        });
                });
            } else {
                $query->where(function ($q) use ($s) {
                    $q->where('payment_fname', 'like', "%{$s}%")
                        ->orWhere('payment_lname', 'like', "%{$s}%")
                        ->orWhere('payment_email', 'like', "%{$s}%");

                    // pretraga po kupljenom artiklu (order_products)
                    $q->orWhereExists(function ($sub) use ($s) {
                        $sub->from('order_products as op')
                            ->whereColumn('op.order_id', 'orders.id')
                            ->where('op.name', 'like', "%{$s}%");
                    });

                    // pretraga preko products.category_string i products.tags
                    $q->orWhereExists(function ($sub) use ($s) {
                        $sub->from('order_products as op')
                            ->join('products as p', 'p.id', '=', 'op.product_id')
                            ->whereColumn('op.order_id', 'orders.id')
                            ->where(function ($w) use ($s) {
                                $w->where('p.category_string', 'like', "%{$s}%")
                                    ->orWhere('p.tags', 'like', "%{$s}%");
                            });
                    });
                });
            }
        }


        // DATE RANGE (bootstrap-datepicker: dd.mm.yyyy)
        if ($request->filled('date_from')) {
            try {
                $from = \Carbon\Carbon::createFromFormat('d.m.Y', $request->input('date_from'))->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Exception $e) {}
        }
        if ($request->filled('date_to')) {
            try {
                $to = \Carbon\Carbon::createFromFormat('d.m.Y', $request->input('date_to'))->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Exception $e) {}
        }

        return $query->orderBy('created_at', 'desc');
    }



    /**
     * @param $products
     *
     * @return $this
     */
    public function decreaseCartItems($products)
    {
        foreach ($products as $product) {
            $product->real->decrement('quantity', $product->quantity);

            /*if ( ! $product->real->quantity) {
                $product->real->update([
                    'status' => 0
                ]);
            }*/
        }

        return $this;
    }


    /**
     * @return bool
     */
    public function forgetSession()
    {
        CheckoutSession::forgetOrder();
        CheckoutSession::forgetStep();
        CheckoutSession::forgetPayment();
        CheckoutSession::forgetShipping();
        CheckoutSession::forgetComment();
        CheckoutSession::forgetNapomena();

        return $this;
    }


    /**
     * @param $id
     *
     * @return mixed
     */
    public static function trashComplete($id)
    {
        OrderProduct::where('order_id', $id)->delete();
        OrderTotal::where('order_id', $id)->delete();
        Transaction::where('order_id', $id)->delete();

        return self::where('id', $id)->delete();
    }
}
