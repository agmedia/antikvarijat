<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class WishlistArrived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var array
     */
    private $product;

    /**
     * @var int|null
     */
    private $wishlistId;

    /**
     * Create a new message instance.
     *
     * @param $contact
     */
    public function __construct($product, $wishlist = null)
    {
        $this->product = $product;
        $this->wishlistId = $wishlist ? (int) $wishlist->id : null;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $trackingUrl = $this->wishlistId
            ? URL::signedRoute('wishlist.track', [
                'wishlist' => $this->wishlistId,
                'locale' => app()->getLocale(),
            ])
            : url($this->product['url']);

        return $this->subject(__('front.email.wishlist_subject', [
            'product' => $this->product['name'],
        ]))
            ->view('emails.wishlist-arrived')
            ->with([
                'product' => $this->product,
                'trackingUrl' => $trackingUrl,
            ]);
    }
}
