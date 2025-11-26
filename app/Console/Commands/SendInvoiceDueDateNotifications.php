<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
    protected $description = 'Create dashboard reminders for unpaid invoices with upcoming or past due dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        // Get unpaid invoices that are due today, overdue, or due within 7 days
        $invoices = Invoice::where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->whereNotNull('customer_id')
            ->where(function ($query) use ($today) {
                // Due today, overdue, or due within 7 days
                $query->whereDate('due_date', '<=', $today->copy()->addDays(7))
                      ->whereDate('due_date', '>=', $today->copy()->subDays(7)); // Also check invoices due in the past 7 days
            })
            ->with('customer')
            ->get();

        $createdCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;

        foreach ($invoices as $invoice) {
            // Skip if customer doesn't exist
            if (!$invoice->customer) {
                $skippedCount++;
                continue;
            }

            // Calculate days until due (negative if overdue)
            $daysUntilDue = Carbon::parse($invoice->due_date)->diffInDays($today, false);
            
            // Only create reminder if due within 7 days (including overdue)
            if ($daysUntilDue <= 7) {
                try {
                    // Check if a reminder already exists for this invoice
                    $existingReminder = Reminder::where('invoice_id', $invoice->id)
                        ->where('type', 'customer_due')
                        ->where('status', 'pending')
                        ->first();

                    if ($existingReminder) {
                        // Update existing reminder to reflect current status
                        $existingReminder->update([
                            'due_date' => $invoice->due_date,
                            'amount' => $invoice->total_amount, // Already in currency format (accessor divides by 100)
                            'priority' => $daysUntilDue < 0 ? 'high' : ($daysUntilDue <= 3 ? 'high' : 'medium'),
                            'title' => $this->generateReminderTitle($invoice, $daysUntilDue),
                            'description' => $this->generateReminderDescription($invoice, $daysUntilDue),
                            'is_read' => false, // Mark as unread when updated
                        ]);
                        $updatedCount++;
                        $this->info("Updated reminder for invoice {$invoice->reference_number}");
                    } else {
                        // Create new reminder
                        Reminder::create([
                            'type' => 'customer_due',
                            'title' => $this->generateReminderTitle($invoice, $daysUntilDue),
                            'description' => $this->generateReminderDescription($invoice, $daysUntilDue),
                            'due_date' => $invoice->due_date,
                            'amount' => $invoice->total_amount, // Already in currency format (accessor divides by 100)
                            'currency' => 'PHP',
                            'priority' => $daysUntilDue < 0 ? 'high' : ($daysUntilDue <= 3 ? 'high' : 'medium'),
                            'status' => 'pending',
                            'is_read' => false,
                            'remindable_type' => Invoice::class,
                            'remindable_id' => $invoice->id,
                            'customer_id' => $invoice->customer_id,
                            'invoice_id' => $invoice->id,
                        ]);
                        $createdCount++;
                        $this->info("Created reminder for invoice {$invoice->reference_number}");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to create reminder for invoice {$invoice->reference_number}: {$e->getMessage()}");
                    \Log::error('Failed to create invoice reminder', [
                        'invoice_id' => $invoice->id,
                        'invoice_reference' => $invoice->reference_number,
                        'customer_id' => $invoice->customer_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $skippedCount++;
                }
            }
        }

        $this->info("Created {$createdCount} reminders. Updated {$updatedCount} reminders. Skipped {$skippedCount} invoices.");
        return Command::SUCCESS;
    }

    /**
     * Generate reminder title based on invoice and days until due
     */
    private function generateReminderTitle(Invoice $invoice, int $daysUntilDue): string
    {
        if ($daysUntilDue < 0) {
            return "Invoice {$invoice->reference_number} - Overdue by " . abs($daysUntilDue) . " day(s)";
        } elseif ($daysUntilDue === 0) {
            return "Invoice {$invoice->reference_number} - Due Today";
        } else {
            return "Invoice {$invoice->reference_number} - Due in {$daysUntilDue} day(s)";
        }
    }

    /**
     * Generate reminder description based on invoice and days until due
     */
    private function generateReminderDescription(Invoice $invoice, int $daysUntilDue): string
    {
        $amount = number_format($invoice->total_amount, 2); // Already in currency format (accessor divides by 100)
        $dueDate = Carbon::parse($invoice->due_date)->format('F d, Y');
        
        $description = "Payment of ₱{$amount} is ";
        
        if ($daysUntilDue < 0) {
            $description .= "overdue by " . abs($daysUntilDue) . " day(s). Due date was {$dueDate}.";
        } elseif ($daysUntilDue === 0) {
            $description .= "due today ({$dueDate}).";
        } else {
            $description .= "due in {$daysUntilDue} day(s) ({$dueDate}).";
        }
        
        return $description;
    }
}
