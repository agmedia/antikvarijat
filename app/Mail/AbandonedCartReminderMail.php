<?php

namespace App\Mail;

use App\Models\Back\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $recoveryUrl;
    public int $sequence;

    public function __construct(Order $order, string $recoveryUrl, int $sequence)
    {
        $this->order = $order;
        $this->recoveryUrl = $recoveryUrl;
        $this->sequence = $sequence;
    }

    public function build()
    {
        return $this->subject(__('front.email.abandoned_cart_subject_' . $this->sequence))
            ->view('emails.abandoned-cart-reminder');
    }
}
