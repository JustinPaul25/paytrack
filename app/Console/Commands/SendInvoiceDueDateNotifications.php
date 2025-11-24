<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Mail\InvoiceDueDateReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendInvoiceDueDateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-due-date-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email notifications for unpaid invoices with upcoming or past due dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        // Get unpaid invoices that are due today, overdue, or due within 7 days
        $invoices = Invoice::whereIn('status', ['pending', 'draft'])
            ->whereNotNull('due_date')
            ->whereNotNull('customer_id')
            ->where(function ($query) use ($today) {
                // Due today, overdue, or due within 7 days
                $query->whereDate('due_date', '<=', $today->copy()->addDays(7))
                      ->whereDate('due_date', '>=', $today->copy()->subDays(7)); // Also check invoices due in the past 7 days
            })
            ->with('customer')
            ->get();

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($invoices as $invoice) {
            // Skip if customer doesn't have an email
            if (empty($invoice->customer->email)) {
                $skippedCount++;
                continue;
            }

            // Calculate days until due (negative if overdue)
            $daysUntilDue = Carbon::parse($invoice->due_date)->diffInDays($today, false);
            
            // Only send notification if due within 7 days (including overdue)
            if ($daysUntilDue <= 7) {
                try {
                    Mail::to($invoice->customer->email)->send(
                        new InvoiceDueDateReminder($invoice, $daysUntilDue)
                    );
                    $sentCount++;
                    $this->info("Sent reminder for invoice {$invoice->reference_number} to {$invoice->customer->email}");
                } catch (\Exception $e) {
                    $this->error("Failed to send email for invoice {$invoice->reference_number}: {$e->getMessage()}");
                    $skippedCount++;
                }
            }
        }

        $this->info("Sent {$sentCount} email notifications. Skipped {$skippedCount} invoices.");
        return Command::SUCCESS;
    }
}
