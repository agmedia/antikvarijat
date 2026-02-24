<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookPurchaseMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var array
     */
    private $payload;

    /**
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return $this
     */
    public function build()
    {
        return $this->subject('Nova prijava: Otkup knjiga')
            ->view('emails.book-purchase')
            ->with(['requestData' => $this->payload]);
    }
}
