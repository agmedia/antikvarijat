<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Models\Front\AgCart;
use App\Services\GiftVoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftVoucherController extends Controller
{
    public function create(GiftVoucherService $giftVouchers)
    {
        $amounts = $giftVouchers->availableAmounts();
        $defaults = [
            'sender_name' => auth()->check() ? trim((string) auth()->user()->name) : '',
        ];

        return view('front.gift-vouchers.create', compact('amounts', 'defaults'));
    }

    public function store(Request $request, GiftVoucherService $giftVouchers): RedirectResponse
    {
        $min = (int) config('gift_vouchers.min_amount', 10);
        $max = (int) config('gift_vouchers.max_amount', 300);
        $step = (int) config('gift_vouchers.amount_step', 10);

        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:' . $min,
                'max:' . $max,
                function ($attribute, $value, $fail) use ($min, $step) {
                    if ((((int) $value - $min) % $step) !== 0) {
                        $fail(__('front.gift_voucher.errors.amount_step', ['step' => $step]));
                    }
                },
            ],
            'recipient_name' => ['required', 'string', 'max:191'],
            'recipient_email' => ['required', 'email:rfc', 'max:191'],
            'sender_name' => ['required', 'string', 'max:191'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->shoppingCart();
        $cartData = $cart->get();

        if ($giftVouchers->cartHasRegularItems($cartData)) {
            return back()
                ->withInput()
                ->with('error', __('front.gift_voucher.errors.separate_purchase'));
        }

        foreach ($cartData['items'] ?? [] as $item) {
            if ($giftVouchers->isGiftVoucherItem($item)) {
                $cart->remove($item->id);
            }
        }

        session()->forget(config('session.cart') . '_coupon');
        CheckoutSession::forgetCheckout();
        $validated['locale'] = app()->getLocale();

        $response = $cart->add($giftVouchers->buildCartItemRequest($validated));

        if (isset($response['error'])) {
            return back()->withInput()->with('error', $response['error']);
        }

        $cart->resolveDB();

        return redirect()
            ->to(LocaleHelper::route('kosarica'))
            ->with('success', __('front.gift_voucher.added_to_cart'));
    }

    private function shoppingCart(): AgCart
    {
        $key = config('session.cart');

        if (! session()->has($key)) {
            session([$key => Str::random(8)]);
        }

        return new AgCart((string) session($key));
    }
}
