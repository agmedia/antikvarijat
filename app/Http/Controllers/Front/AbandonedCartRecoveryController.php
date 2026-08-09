<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\Front\AgCart;
use App\Models\Front\Catalog\Product;
use App\Services\AbandonedCartReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AbandonedCartRecoveryController extends Controller
{
    public function __invoke(Order $order, AbandonedCartReminderService $reminders): RedirectResponse
    {
        if (! $reminders->canRecover($order)) {
            return redirect(LocaleHelper::route('index'))
                ->with('error', __('front.email.abandoned_cart_unavailable'));
        }

        $order->loadMissing('orderProducts');
        $cartKey = (string) config('session.cart');
        $cartId = session($cartKey);

        if (! is_string($cartId) || $cartId === '') {
            $cartId = Str::random(16);
            session([$cartKey => $cartId]);
        }

        $cart = new AgCart($cartId);
        $existingIds = $cart->getCartItems(true)->pluck('id')->map(fn ($id) => (int) $id);
        $restored = 0;
        $alreadyPresent = 0;

        foreach ($order->orderProducts as $item) {
            if ($existingIds->contains((int) $item->product_id)) {
                $alreadyPresent++;
                continue;
            }

            $product = Product::query()
                ->whereKey($item->product_id)
                ->where('status', 1)
                ->where('quantity', '>', 0)
                ->first();

            if (! $product) {
                continue;
            }

            $quantity = min((int) $item->quantity, (int) $product->quantity);
            if ($quantity < 1) {
                continue;
            }

            $response = $cart->add(['item' => [
                'id' => (int) $product->id,
                'quantity' => $quantity,
            ]]);

            if (! isset($response['error'])) {
                $restored++;
                $existingIds->push((int) $product->id);
            }
        }

        if (($restored + $alreadyPresent) < 1) {
            return redirect(LocaleHelper::route('index'))
                ->with('error', __('front.email.abandoned_cart_no_stock'));
        }

        CheckoutSession::setOrder(['id' => (int) $order->id]);
        CheckoutSession::setAddress([
            'fname' => (string) $order->payment_fname,
            'lname' => (string) $order->payment_lname,
            'email' => (string) $order->payment_email,
            'phone' => (string) $order->payment_phone,
            'birthday_year' => (string) ($order->birthday_year ?? ''),
            'address' => (string) $order->payment_address,
            'city' => (string) $order->payment_city,
            'zip' => (string) $order->payment_zip,
            'company' => (string) ($order->company ?? ''),
            'oib' => (string) ($order->oib ?? ''),
            'state' => (string) ($order->payment_state ?: 'Croatia'),
        ]);

        if ($order->shipping_code) {
            CheckoutSession::setShipping((string) $order->shipping_code);
        }
        if ($order->payment_code) {
            CheckoutSession::setPayment((string) $order->payment_code);
        }
        CheckoutSession::setComment((string) ($order->comment ?? ''));
        CheckoutSession::setNapomena((string) ($order->napomena ?? ''));

        return redirect(LocaleHelper::route('kosarica'))
            ->with('success', __('front.email.abandoned_cart_restored'));
    }
}
