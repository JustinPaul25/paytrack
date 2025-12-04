<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerAccountVerified extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $loginUrl;

    public function __construct(string $customerName, string $loginUrl)
    {
        $this->customerName = $customerName;
        $this->loginUrl = $loginUrl;
    }

    public function build(): self
    {
        return $this->subject('Your account has been verified')
            ->view('emails.customer_account_verified');
    }
}
