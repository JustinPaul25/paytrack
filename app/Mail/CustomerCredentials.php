<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $email;
    public string $plainPassword;

    public function __construct(string $customerName, string $email, string $plainPassword)
    {
        $this->customerName = $customerName;
        $this->email = $email;
        $this->plainPassword = $plainPassword;
    }

    public function build(): self
    {
        return $this->subject('Your account credentials')
            ->view('emails.customer_credentials');
    }
}


