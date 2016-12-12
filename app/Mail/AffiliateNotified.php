<?php

namespace App\Mail;

use App\UndesirableAffiliate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AffiliateNotified extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var Order
     */
    public $undesirableAffiliate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(UndesirableAffiliate $undesirableAffiliate)
    {
        $this->undesirableAffiliate = $undesirableAffiliate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('test@mail.com')
            ->view('emails.notified');
    }
}
