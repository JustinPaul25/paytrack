<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundRequestDecision extends Mailable
{
    use Queueable, SerializesModels;

    public RefundRequest $refundRequest;
    public string $decision; // 'approved' | 'rejected'

    /**
     * Create a new message instance.
     */
    public function __construct(RefundRequest $refundRequest, string $decision)
    {
        $this->refundRequest = $refundRequest;
        $this->decision = $decision;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = $this->decision === 'approved'
            ? 'Your refund request was approved'
            : 'Your refund request was declined';

        return $this->subject($subject)
            ->view('emails.refund_request_decision');
    }
}


