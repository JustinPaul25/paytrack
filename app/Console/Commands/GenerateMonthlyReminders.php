<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Customer;
use Carbon\Carbon;

class GenerateMonthlyReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:generate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly reminders for bills, customer dues, and credit term payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating monthly reminders...');
        
        $generated = 0;
        
        // 1. Generate reminders for bills (expenses) - monthly recurring
        $generated += $this->generateBillReminders();
        
        // 2. Generate reminders for customer due amounts
        $generated += $this->generateCustomerDueReminders();
        
        // 3. Generate reminders for credit term payments
        $generated += $this->generateCreditTermReminders();
        
        $this->info("Generated {$generated} reminders successfully.");
        
        return Command::SUCCESS;
    }

    /**
     * Generate reminders for monthly bill payments (expenses)
     */
    private function generateBillReminders(): int
    {
        $count = 0;
        
        // Get expenses from the current month that haven't been paid yet
        // For recurring bills, we'll create reminders for the next month
        $nextMonth = Carbon::now()->addMonth();
        
        // Get expenses that are typically recurring (you can add a 'is_recurring' field later)
        // For now, we'll create reminders for all expenses in the current month
        $expenses = Expense::whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->get();
        
        foreach ($expenses as $expense) {
            // Check if reminder already exists for this expense this month
            $existingReminder = Reminder::where('type', 'bill_payment')
                ->where('expense_id', $expense->id)
                ->whereMonth('due_date', $nextMonth->month)
                ->whereYear('due_date', $nextMonth->year)
                ->where('status', 'pending')
                ->first();
            
            if (!$existingReminder) {
                Reminder::create([
                    'type' => 'bill_payment',
                    'title' => "Bill Payment: {$expense->expense_type}",
                    'description' => $expense->description ?? "Monthly payment for {$expense->expense_type}",
                    'due_date' => $nextMonth->copy()->day($expense->date->day),
                    'amount' => $expense->amount,
                    'currency' => 'USD',
                    'priority' => 'medium',
                    'status' => 'pending',
                    'is_read' => false,
                    'remindable_type' => Expense::class,
                    'remindable_id' => $expense->id,
                    'expense_id' => $expense->id,
                ]);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Generate reminders for customer due amounts
     */
    private function generateCustomerDueReminders(): int
    {
        $count = 0;
        
        // Get invoices with pending payment status and due dates within the next 30 days
        $invoices = Invoice::where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays(30)->toDateString()
            ])
            ->with('customer')
            ->get();
        
        foreach ($invoices as $invoice) {
            // Check if reminder already exists for this invoice
            $existingReminder = Reminder::where('type', 'customer_due')
                ->where('invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$existingReminder && $invoice->customer) {
                $daysUntilDue = Carbon::now()->diffInDays($invoice->due_date);
                $priority = $daysUntilDue <= 7 ? 'high' : ($daysUntilDue <= 14 ? 'medium' : 'low');
                
                Reminder::create([
                    'type' => 'customer_due',
                    'title' => "Customer Payment Due: {$invoice->customer->name}",
                    'description' => "Invoice {$invoice->reference_number} - Amount: $" . number_format($invoice->total_amount, 2),
                    'due_date' => $invoice->due_date,
                    'amount' => $invoice->total_amount,
                    'currency' => 'USD',
                    'priority' => $priority,
                    'status' => 'pending',
                    'is_read' => false,
                    'remindable_type' => Invoice::class,
                    'remindable_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                ]);
                $count++;
            }
        }
        
        // Also check orders with credit terms
        $orders = Order::where('status', 'approved')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays(30)->toDateString()
            ])
            ->with('customer')
            ->get();
        
        foreach ($orders as $order) {
            $existingReminder = Reminder::where('type', 'customer_due')
                ->where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$existingReminder && $order->customer) {
                $daysUntilDue = Carbon::now()->diffInDays($order->due_date);
                $priority = $daysUntilDue <= 7 ? 'high' : ($daysUntilDue <= 14 ? 'medium' : 'low');
                
                Reminder::create([
                    'type' => 'customer_due',
                    'title' => "Customer Payment Due: {$order->customer->name}",
                    'description' => "Order {$order->reference_number} - Amount: $" . number_format($order->total_amount, 2),
                    'due_date' => $order->due_date,
                    'amount' => $order->total_amount,
                    'currency' => 'USD',
                    'priority' => $priority,
                    'status' => 'pending',
                    'is_read' => false,
                    'remindable_type' => Order::class,
                    'remindable_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                ]);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Generate reminders for credit term payments (30 days from payment date)
     */
    private function generateCreditTermReminders(): int
    {
        $count = 0;
        
        // Get invoices with credit terms that are due within the next 30 days
        $invoices = Invoice::where('payment_method', '!=', 'cash')
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('credit_term_days')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays(30)->toDateString()
            ])
            ->with('customer')
            ->get();
        
        foreach ($invoices as $invoice) {
            // Check if reminder already exists for this invoice's credit term
            $existingReminder = Reminder::where('type', 'credit_term')
                ->where('invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$existingReminder && $invoice->customer) {
                $daysUntilDue = Carbon::now()->diffInDays($invoice->due_date);
                $priority = $daysUntilDue <= 7 ? 'high' : ($daysUntilDue <= 14 ? 'medium' : 'low');
                
                Reminder::create([
                    'type' => 'credit_term',
                    'title' => "Credit Term Payment Due: {$invoice->customer->name}",
                    'description' => "Invoice {$invoice->reference_number} - {$invoice->credit_term_days} days credit term. Payment method: {$invoice->payment_method}",
                    'due_date' => $invoice->due_date,
                    'amount' => $invoice->total_amount,
                    'currency' => 'USD',
                    'priority' => $priority,
                    'status' => 'pending',
                    'is_read' => false,
                    'remindable_type' => Invoice::class,
                    'remindable_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                ]);
                $count++;
            }
        }
        
        // Also check orders with credit terms
        $orders = Order::where('payment_method', '!=', 'cash')
            ->where('status', 'approved')
            ->whereNotNull('credit_term_days')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays(30)->toDateString()
            ])
            ->with('customer')
            ->get();
        
        foreach ($orders as $order) {
            $existingReminder = Reminder::where('type', 'credit_term')
                ->where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$existingReminder && $order->customer) {
                $daysUntilDue = Carbon::now()->diffInDays($order->due_date);
                $priority = $daysUntilDue <= 7 ? 'high' : ($daysUntilDue <= 14 ? 'medium' : 'low');
                
                Reminder::create([
                    'type' => 'credit_term',
                    'title' => "Credit Term Payment Due: {$order->customer->name}",
                    'description' => "Order {$order->reference_number} - {$order->credit_term_days} days credit term. Payment method: {$order->payment_method}",
                    'due_date' => $order->due_date,
                    'amount' => $order->total_amount,
                    'currency' => 'USD',
                    'priority' => $priority,
                    'status' => 'pending',
                    'is_read' => false,
                    'remindable_type' => Order::class,
                    'remindable_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                ]);
                $count++;
            }
        }
        
        return $count;
    }
}
