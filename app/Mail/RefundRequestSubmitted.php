<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public RefundRequest $refundRequest;

    /**
     * Create a new message instance.
     */
    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Your refund request has been received')
            ->view('emails.refund_request_submitted');
    }
}


