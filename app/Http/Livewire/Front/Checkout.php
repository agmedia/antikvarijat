<?php

namespace App\Http\Livewire\Front;

use App\Helpers\Country;
use App\Helpers\Currency;
use App\Helpers\Helper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\GeoZone;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use App\Models\TagManager;
use App\Services\AddressDirectoryService;
use App\Services\CheckoutAccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Checkout extends Component
{

    /**
     * @var string
     */
    public $step = '';

    /**
     * @var string
     */
    public $is_free_shipping = '';

    /**
     * @var string[]
     */
    public $address = [
        'fname' => '',
        'lname' => '',
        'email' => '',
        'phone' => '',
        'birthday_year' => '',
        'address' => '',
        'city' => '',
        'zip' => '',
        'company' => '',
        'oib' => '',
        'state' => 'Croatia',
    ];

    public $birthday = [
        'day' => '',
        'month' => '',
        'year' => '',
    ];

    /**
     * @var string
     */
    public $shipping = '';

    /**
     * @var string
     */
    public $payment = '';

    /**
     * @var int|bool
     */
    public $secondary_price = false;

    /**
     * @var array
     */
    public $gdl = [];

    public $gdl_event = '';

    public $gdl_shipping = false;

    public $gdl_payment = false;

    protected $cart = false;

    public $comment = '';

    public $napomena = '';

    public $view_comment = false;

    public $newsletter = false;

    public $register_account = false;

    public $r1_invoice = false;

    public $registration = [
        'password' => '',
        'password_confirmation' => '',
    ];

    /**
     * @var string[]
     */
    protected $address_rules = [
        'address.fname' => 'required',
        'address.lname' => 'required',
        'address.email' => 'bail|required|email|max:190',
        'address.phone' => 'required',
        'address.birthday_year' => 'nullable|date',
        'address.address' => 'required',
        'address.city' => 'required',
        'address.zip' => 'required',
        'address.state' => 'required',
    ];

    /**
     * @var string[]
     */
    protected $shipping_rules = [
        'shipping' => 'required',
    ];


    protected $comment_rules = [

        'comment'=> 'required',
    ];

    /**
     * @var string[]
     */
    protected $payment_rules = [
        'payment' => 'required',
    ];

    /**
     * @var \string[][]
     */
    protected $queryString = ['step' => ['except' => '']];

    protected function validationAttributes(): array
    {
        return [
            'address.fname' => __('front.checkout.first_name'),
            'address.lname' => __('front.checkout.last_name'),
            'address.email' => __('front.checkout.email_address'),
            'address.phone' => __('front.checkout.phone'),
            'address.birthday_year' => __('front.checkout.birthday'),
            'address.address' => __('front.checkout.address'),
            'address.city' => __('front.checkout.city'),
            'address.zip' => __('front.checkout.zip'),
            'address.state' => __('front.checkout.country'),
            'shipping' => __('front.checkout.shipping'),
            'payment' => __('front.checkout.payment'),
            'registration.password' => __('front.checkout.password'),
            'registration.password_confirmation' => __('front.checkout.password_confirmation'),
        ];
    }


    /**
     *
     */
    public function mount()
    {
        if (CheckoutSession::hasAddress()) {
            $this->setAddress(CheckoutSession::getAddress());
        } else {
            $this->setAddress();
        }

        $this->setBirthdayParts($this->address['birthday_year'] ?? '');

        $this->r1_invoice = ! empty($this->address['company']) || ! empty($this->address['oib']);

        if (CheckoutSession::hasShipping()) {
            $this->shipping = CheckoutSession::getShipping();
        }

        if (CheckoutSession::hasPayment()) {
            $this->payment = CheckoutSession::getPayment();
        }

        if (CheckoutSession::hasComment()) {
            $this->comment = CheckoutSession::getComment();
        }

        if (CheckoutSession::hasNapomena()) {
            $this->napomena = CheckoutSession::getNapomena();
        }

        if (CheckoutSession::hasNewsletter()) {
            $this->newsletter = (bool) CheckoutSession::getNewsletter();
        }

        $this->secondary_price = Currency::secondary() ? Currency::secondary()->value : false;

        if (session()->has(config('session.cart'))) {
            $this->cart = new AgCart(session(config('session.cart')));
        }

        $this->changeStep($this->step);
    }

    public function updatingComment($value)
    {
        $this->comment = $value;

        CheckoutSession::setComment($this->comment);
    }


    public function updatingNapomena($value)
    {
        $this->napomena = $value;

        CheckoutSession::setNapomena($this->napomena);
    }

    public function updatingNewsletter($value)
    {
        $this->newsletter = (bool) $value;

        CheckoutSession::setNewsletter($this->newsletter);
    }

    public function updatedAddress($value, $key)
    {
        if (! in_array($key, ['zip', 'city'], true)) {
            CheckoutSession::setAddress($this->address);

            return;
        }

        $this->autofillAddressField($key, (string) $value);
    }

    public function updatedRegisterAccount($value)
    {
        $this->register_account = (bool) $value;

        if (! $this->register_account) {
            $this->registration = [
                'password' => '',
                'password_confirmation' => '',
            ];
            $this->resetValidation([
                'address.email',
                'registration.password',
                'registration.password_confirmation',
            ]);
        }
    }

    public function updatedR1Invoice($value)
    {
        $this->r1_invoice = (bool) $value;

        if (! $this->r1_invoice) {
            $this->address['company'] = '';
            $this->address['oib'] = '';
            CheckoutSession::setAddress($this->address);
        }
    }
    /**
     * @param string $step
     */
    public function changeStep(string $step = '')
    {
        try {
            /*if ( ! $this->cart) {
                return redirect()->route('kosarica');
            }*/

            $this->checkCart();

            if (in_array($step, ['', 'podaci']) && $this->cart) {
                $this->gdl = TagManager::getGoogleCartDataLayer($this->cart->get());
                $this->gdl_event = 'begin_checkout';
                $this->gdl_shipping = false;
                $this->gdl_payment = false;
            }
            // Podaci
            if ($step == '') {
                $step = 'podaci';

                if (CheckoutSession::hasStep()) {
                    $step = CheckoutSession::getStep();
                }
            }

            // Dostava
            if (in_array($step, ['dostava', 'placanje']) && $this->cart) {
                $this->syncBirthdayDate();
                $this->setAddress($this->address);
                $this->validate($this->address_rules);
                $this->registerCheckoutAccount();
                $this->syncNewsletterSubscription();

                if ($step == 'dostava' && $this->shipping != '') {
                    $this->checkShipping($this->shipping);
                    $this->gdl = TagManager::getGoogleCartDataLayer($this->cart->get());
                    $this->gdl_event = 'add_shipping_info';
                }

                if ($step == 'placanje' && $this->payment != '') {
                    $this->checkPayment($this->payment);
                    $this->gdl = TagManager::getGoogleCartDataLayer($this->cart->get());
                    $this->gdl_event = 'add_payment_info';
                }
            }

            // Plaćanje
            if ($step == 'placanje') {
                $this->validate($this->shipping_rules);
            }

            if ($step == 'placanje' and $this->shipping == 'gls_eu') {
                $this->validate($this->comment_rules);
            }

            $this->step = $step;

            CheckoutSession::setStep($step);

            $this->dispatchBrowserEvent('checkout-step-changed', [
                'step' => $step,
            ]);
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('checkout-validation-failed', [
                'step' => $step,
            ]);

            throw $e;
        }
    }

    private function syncNewsletterSubscription(): void
    {
        if (! $this->newsletter || empty($this->address['email'])) {
            return;
        }

        try {
            NewsletterSubscriber::subscribe([
                'email'      => $this->address['email'],
                'first_name' => $this->address['fname'] ?? null,
                'last_name'  => $this->address['lname'] ?? null,
                'user_id'    => auth()->id() ?? 0,
                'source'     => 'checkout',
                'gdpr'       => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Newsletter subscribe failed during checkout', [
                'email' => $this->address['email'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param string $state
     */
    public function stateSelected($state)
    {
        $this->setAddress(['state' => $state], true);

        if ($this->isCroatia((string) $this->address['state'])) {
            if (! empty($this->address['zip'])) {
                $this->autofillAddressField('zip', (string) $this->address['zip']);
            } elseif (! empty($this->address['city'])) {
                $this->autofillAddressField('city', (string) $this->address['city']);
            }
        }

        CheckoutSession::forgetShipping();
        $this->shipping = '';
        $this->comment = '';
        CheckoutSession::forgetPayment();
        $this->payment = '';
    }


    /**
     * @param string $shipping
     */
    public function selectShipping(string $shipping)
    {
        $this->shipping = $shipping;

        $this->checkShipping($shipping);

        CheckoutSession::setShipping($shipping);

        CheckoutSession::forgetPayment();
        $this->payment = '';

        return redirect()->route('naplata', ['step' => 'dostava']);
    }


    /**
     * @param string $payment
     */
    public function selectPayment(string $payment)
    {
        $this->payment = $payment;

        $this->checkPayment($payment);

        CheckoutSession::setPayment($payment);
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $geo = (new GeoZone())->findState($this->address['state'] ?: 'Croatia');

        return view('livewire.front.checkout', [
            'shippingMethods' => (new ShippingMethod())->findGeo($geo->id),
            'paymentMethods' => (new PaymentMethod())->findGeo($geo->id)->checkShipping($this->shipping)->resolve(),
            'countries' => Country::list()
        ]);
    }


    /**
     * @return void
     */
    private function checkCart(): void
    {
        if (session()->has(config('session.cart'))) {
            $this->cart = new AgCart(session(config('session.cart')));
        }
    }


    /**
     * @param array $value
     *
     * @return array
     */
    private function setAddress(array $value = [], bool $only_state = false)
    {
        if ( ! empty($value)) {
            $value['state'] = isset($value['state']) ? $value['state'] : 'Croatia';

            if ($only_state) {
                $this->address['state'] = $value['state'];

            } else {
                $this->address = [
                    'fname' => $value['fname'],
                    'lname' => $value['lname'],
                    'email' => $value['email'],
                    'phone' => $value['phone'],
                    'birthday_year' => $value['birthday_year'] ?? '',
                    'address' => $value['address'],
                    'city' => $value['city'],
                    'company' => $value['company'],
                    'oib' => $value['oib'],
                    'zip' => $value['zip'],
                    'state' => $value['state'] ?: 'Croatia',
                ];
            }
        } else {
            if (auth()->user()) {
                $this->address = [
                    'fname' => auth()->user()->details->fname,
                    'lname' => auth()->user()->details->lname,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->details->phone,
                    'birthday_year' => '',
                    'address' => auth()->user()->details->address,
                    'city' => auth()->user()->details->city,
                    'company' => auth()->user()->details->company,
                    'oib' => auth()->user()->details->oib,
                    'zip' => auth()->user()->details->zip,
                    'state' => auth()->user()->details->state ?: 'Croatia'
                ];
            } else {
                $this->address['state'] = $this->address['state'] ?: 'Croatia';
            }
        }

        CheckoutSession::setAddress($this->address);

        /*CheckoutSession::setGeoZone(
            GeoZone::findState($this->address['state'])
        );*/

        //dd($this->address);

        return $this->address;
    }

    private function setBirthdayParts(?string $value): void
    {
        $timestamp = $value ? strtotime($value) : false;

        if ($timestamp === false) {
            $this->birthday = ['day' => '', 'month' => '', 'year' => ''];

            return;
        }

        $this->birthday = [
            'day' => date('d', $timestamp),
            'month' => date('m', $timestamp),
            'year' => date('Y', $timestamp),
        ];
    }

    private function syncBirthdayDate(): void
    {
        $day = trim((string) ($this->birthday['day'] ?? ''));
        $month = trim((string) ($this->birthday['month'] ?? ''));
        $year = trim((string) ($this->birthday['year'] ?? ''));

        if ($day === '' && $month === '' && $year === '') {
            $this->address['birthday_year'] = '';

            return;
        }

        if (! ctype_digit($day) || ! ctype_digit($month) || ! ctype_digit($year) || strlen($year) !== 4) {
            $this->address['birthday_year'] = implode('.', [$day, $month, $year]);

            return;
        }

        $formatted = sprintf('%02d.%02d.%04d', (int) $day, (int) $month, (int) $year);
        $date = \DateTimeImmutable::createFromFormat('!d.m.Y', $formatted);
        $errors = \DateTimeImmutable::getLastErrors();

        if (! $date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('d.m.Y') !== $formatted) {
            $this->address['birthday_year'] = $formatted;

            return;
        }

        $this->address['birthday_year'] = $date->format('Y-m-d');
    }

    private function registerCheckoutAccount(): void
    {
        if (Auth::check() || ! $this->register_account) {
            return;
        }

        $this->validate([
            'address.email' => 'bail|required|email|max:190|unique:users,email',
            'registration.password' => 'required|string|min:8|confirmed',
            'registration.password_confirmation' => 'required|string',
        ], [
            'address.email.unique' => __('front.checkout.registration_email_exists'),
            'registration.password.required' => __('front.checkout.registration_password_required'),
            'registration.password.min' => __('front.checkout.registration_password_min'),
            'registration.password.confirmed' => __('front.checkout.registration_password_confirmed'),
            'registration.password_confirmation.required' => __('front.checkout.registration_password_confirmation_required'),
        ]);

        $user = app(CheckoutAccountService::class)->create(
            $this->address,
            (string) $this->registration['password']
        );

        Auth::login($user);
        session()->regenerate();
        $this->register_account = false;
        $this->registration = [
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    private function autofillAddressField(string $field, string $value): void
    {
        $directory = app(AddressDirectoryService::class);
        $country = (string) ($this->address['state'] ?? 'Croatia');

        $place = $field === 'zip'
            ? $directory->findByPostal($value, $country)
            : $directory->findByCity($value, $country);

        if ($place) {
            $this->address['zip'] = $place['postal_code'];
            $this->address['city'] = $place['city'];
            $this->address['state'] = 'Croatia';
        }

        CheckoutSession::setAddress($this->address);
    }

    private function isCroatia(string $country): bool
    {
        return in_array(strtolower(trim($country)), ['croatia', 'hr', 'hrvatska'], true);
    }


    /**
     * @param string $shipping
     *
     * @return void
     */
    private function checkShipping(string $shipping): void
    {
        if ($shipping == 'pickup') {
            $this->gdl_shipping = 'osobno preuzimanje';
        } else {
            $this->gdl_shipping = 'dostava';
        }

        if ($shipping == 'gls_eu') {
            $this->view_comment = true;
        } else {
            $this->view_comment = false;
        }
    }


    /**
     * @param string $payment
     *
     * @return void
     */
    private function checkPayment(string $payment): void
    {
        Log::info('$payment');
        Log::info($payment);

        if ($payment == 'bank') {
            $this->gdl_payment = 'uplatnica';
        } elseif ($payment == 'cod') {
            $this->gdl_payment = 'pouzeće';
        } else {
            $this->gdl_payment = 'kartica';
        }
    }
}
