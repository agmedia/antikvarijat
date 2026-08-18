<?php

namespace App\Mail;

use App\Models\Back\Orders\Order;
use App\Services\Shipping\OrderTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShippingTrackingAvailable extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Order */
    public $order;

    /** @var string */
    public $carrierLabel;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $trackingService = app(OrderTrackingService::class);
        $this->carrierLabel = $trackingService->carrierLabel($trackingService->resolveCarrier($order));
    }

    public function build()
    {
        return $this->subject('Vaša pošiljka je poslana - Antikvarijat Biblos')
            ->view('emails.shipping-tracking-available');
    }
}
