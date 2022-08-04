<?php

namespace App\Http\Livewire\Front;

use App\Helpers\Country;
use App\Helpers\Helper;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Models\Front\Checkout\GeoZone;
use App\Models\Front\Checkout\PaymentMethod;
use App\Models\Front\Checkout\ShippingMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Checkout extends Component
{

    /**
     * @var string
     */
    public $step = '';

    /**
     * @var array
     */
    public $login = [
        'email' => '',
        'pass' => '',
        'remember' => false
    ];

    /**
     * @var string[]
     */
    public $address = [
        'fname' => '',
        'lname' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'zip' => '',
        'company' => '',
        'oib' => '',
        'state' => '',
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
     * @var int|null
     */
    public $eur = null;

    /**
     * @var string[]
     */
    protected $address_rules = [
        'address.fname' => 'required',
        'address.lname' => 'required',
        'address.email' => 'required|email',
        'address.phone' => 'required',
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

        if (CheckoutSession::hasShipping()) {
            $this->shipping = CheckoutSession::getShipping();
        }

        if (CheckoutSession::hasPayment()) {
            $this->payment = CheckoutSession::getPayment();
        }

        $this->eur = Helper::getEur();

        $this->changeStep($this->step);
    }


    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authUser()
    {
        $validated = Validator::make([
            'email' => $this->login['email'],
            'password' => $this->login['pass'],
        ],[
            'email' => ['required', 'email'],
            'password' => ['required'],
        ])->validate();

        if (Auth::attempt($validated, $this->login['remember'])) {
            session()->regenerate();
            $this->setAddress();

            session()->flash('login_success', 'Uspješno ste se prijavili na vaš račun...');
        }

        session()->flash('error', 'Upisani podaci ne odgovaraju našim korisnicima...');
    }


    /**
     * @param string $step
     */
    public function changeStep(string $step = '')
    {
        // Podaci
        if ($step == '') {
            $step = 'podaci';

            if (CheckoutSession::hasStep()) {
                $step = CheckoutSession::getStep();
            }
        }

        // Dostava
        if (in_array($step, ['dostava', 'placanje'])) {
            $this->setAddress($this->address);
            $this->validate($this->address_rules);
        }

        // Plaćanje
        if ($step == 'placanje') {
            $this->validate($this->shipping_rules);
        }

        $this->step = $step;

        CheckoutSession::setStep($step);
    }


    /**
     * @param string $state
     */
    public function stateSelected($state)
    {
        $this->setAddress(['state' => $state], true);

        CheckoutSession::forgetShipping();
        $this->shipping = '';
        CheckoutSession::forgetPayment();
        $this->payment = '';

        $this->render();
    }


    /**
     * @param string $shipping
     */
    public function selectShipping(string $shipping)
    {
        $this->shipping = $shipping;

        CheckoutSession::setShipping($shipping);

        return redirect()->route('naplata', ['step' => 'dostava']);
    }


    /**
     * @param string $payment
     */
    public function selectPayment(string $payment)
    {
        $this->payment = $payment;

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
                    'address' => $value['address'],
                    'city' => $value['city'],
                    'company' => $value['company'],
                    'oib' => $value['oib'],
                    'zip' => $value['zip'],
                    'state' => $value['state'],
                ];
            }
        } else {
            if (auth()->user()) {
                $this->address = [
                    'fname' => auth()->user()->details->fname,
                    'lname' => auth()->user()->details->lname,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->details->phone,
                    'address' => auth()->user()->details->address,
                    'city' => auth()->user()->details->city,
                    'company' => auth()->user()->details->company,
                    'oib' => auth()->user()->details->oib,
                    'zip' => auth()->user()->details->zip,
                    'state' => auth()->user()->details->state
                ];
            }
        }

        CheckoutSession::setAddress($this->address);

        /*CheckoutSession::setGeoZone(
            GeoZone::findState($this->address['state'])
        );*/

        //dd($this->address);

        return $this->address;
    }
}
