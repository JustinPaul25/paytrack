<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceDueDateReminder extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public int $daysUntilDue;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, int $daysUntilDue = 0)
    {
        $this->invoice = $invoice;
        $this->daysUntilDue = $daysUntilDue;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $subject = $this->daysUntilDue === 0 
            ? 'Invoice Payment Due Today' 
            : ($this->daysUntilDue < 0 
                ? 'Invoice Payment Overdue' 
                : "Invoice Payment Due in {$this->daysUntilDue} Day(s)");

        return $this->subject($subject)
            ->view('emails.invoice_due_date_reminder');
    }
}
