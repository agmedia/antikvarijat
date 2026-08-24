<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Services\GiftVoucherService;

class GiftVoucher
{
    private $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function resolveFormView($paymentMethod = null)
    {
        return view('front.checkout.payment.gift-voucher', [
            'data' => ['order_id' => $this->order->id],
        ]);
    }

    public function finishOrder(Order $order, $request = null): bool
    {
        $giftVouchers = app(GiftVoucherService::class);

        if (! $giftVouchers->hasCoveringRedemption($order)) {
            return false;
        }

        $updated = $order->update([
            'order_status_id' => config('settings.order.status.paid'),
        ]);

        if ($updated) {
            $giftVouchers->completeCheckout($order->fresh());
        }

        return (bool) $updated;
    }
}
