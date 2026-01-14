<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some customers, products, and a staff/admin user for seeding
        $customers = Customer::all();
        $products = Product::all();
        $users = User::all();

        if ($customers->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping invoice seeding: No customers, products, or users found.');
            return;
        }

        // Prefer a Staff/Admin user as the creator of seeded invoices
        $creator = $users->first();

        // Define date range: September, October, November (current year)
        // Ensure dates don't exceed today
        $currentYear = Carbon::now()->year;
        $today = Carbon::today();
        $startDate = Carbon::create($currentYear, 9, 1)->startOfDay(); // September 1
        $maxEndDate = Carbon::create($currentYear, 11, 30)->endOfDay(); // November 30
        $endDate = $today->copy()->endOfDay()->isBefore($maxEndDate) ? $today->copy()->endOfDay() : $maxEndDate;
        $daysRange = $startDate->diffInDays($endDate);

        foreach ($customers as $customer) {
            // For each customer create three invoices with different statuses
            $statusSets = [
                ['status' => 'completed', 'payment_method' => 'cash', 'payment_reference' => 'SEED-CASH-'.uniqid()],
                ['status' => 'pending', 'payment_method' => 'credit', 'payment_reference' => 'SEED-CREDIT-'.uniqid()],
                ['status' => 'cancelled', 'payment_method' => 'credit', 'payment_reference' => 'SEED-CREDIT-'.uniqid()],
            ];

            foreach ($statusSets as $meta) {
                // Generate a random date within September, October, November (not exceeding today)
                $randomDays = rand(0, max(0, $daysRange));
                $invoiceDate = $startDate->copy()->addDays($randomDays);
                // Ensure the date doesn't exceed today
                if ($invoiceDate->isAfter($today)) {
                    $invoiceDate = $today->copy();
                }
                $invoiceDate->setTime(rand(8, 17), rand(0, 59), rand(0, 59));
                // Add 1-3 random products to calculate totals
                $randomProducts = $products->random(min(3, max(1, rand(1, $products->count()))));
                $subtotalAmount = 0;

                // Build items first to compute invoice totals
                $items = [];
                foreach ($randomProducts as $product) {
                    $quantity = rand(1, 5);
                    $price = $product->selling_price;
                    $total = $quantity * $price;
                    $subtotalAmount += $total;
                    $items[] = ['product' => $product, 'quantity' => $quantity, 'price' => $price, 'total' => $total];
                }

                // Calculate VAT (12%) and withholding tax (1%)
                $vatRate = 12.00;
                $vatAmount = $subtotalAmount * ($vatRate / 100);
                
                // Calculate 1% withholding tax on (subtotal + VAT)
                $withholdingTaxRate = 1.00;
                $withholdingTaxAmount = ($subtotalAmount + $vatAmount) * ($withholdingTaxRate / 100);
                
                // Total = Subtotal + VAT - Withholding Tax
                $totalAmount = $subtotalAmount + $vatAmount - $withholdingTaxAmount;

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'user_id' => $creator->id,
                    'status' => $meta['status'],
                    'payment_method' => $meta['payment_method'],
                    'payment_reference' => $meta['payment_reference'],
                    'notes' => 'Seeded invoice ('.$meta['status'].') for '.$customer->name,
                    'subtotal_amount' => $subtotalAmount,
                    'vat_amount' => $vatAmount,
                    'vat_rate' => $vatRate,
                    'withholding_tax_amount' => $withholdingTaxAmount,
                    'withholding_tax_rate' => $withholdingTaxRate,
                    'total_amount' => $totalAmount,
                    'created_at' => $invoiceDate,
                    'updated_at' => $invoiceDate,
                ]);

                foreach ($items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                        'created_at' => $invoiceDate,
                        'updated_at' => $invoiceDate,
                    ]);
                }
            }
        }

        $this->command->info('Invoices seeded successfully!');
    }
} 