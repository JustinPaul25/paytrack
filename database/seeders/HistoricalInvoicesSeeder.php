<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class HistoricalInvoicesSeeder extends Seeder
{
    /**
     * Seed a range of historical invoices to drive trend charts.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $users = User::all();

        if ($customers->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping historical invoice seeding: missing customers/products/users.');
            return;
        }

        $creator = $users->first();

        // Define date range: Last 6 months to provide better trend data
        // This ensures we have enough historical data for all charts
        // Use yesterday as max date so newly created invoices appear first
        $yesterday = Carbon::yesterday();
        $startDate = Carbon::now()->subMonths(6)->startOfMonth(); // 6 months ago
        $endDate = $yesterday->copy()->endOfDay();
        
        // Ensure we don't go into the future
        if ($endDate->isFuture()) {
            $endDate = $yesterday->copy()->endOfDay();
        }
        
        $daysRange = $startDate->diffInDays($endDate);

        // Generate invoices for September, October, November (up to yesterday)
        for ($i = 0; $i <= $daysRange; $i++) {
            $day = $startDate->copy()->addDays($i);
            // Ensure the date doesn't exceed yesterday
            if ($day->isAfter($yesterday)) {
                $day = $yesterday->copy();
            }

            // More realistic invoice distribution: 3-8 invoices per day (weighted towards weekdays)
            $dayOfWeek = $day->dayOfWeek;
            $isWeekend = in_array($dayOfWeek, [0, 6]); // Sunday or Saturday
            $baseCount = $isWeekend ? rand(2, 5) : rand(4, 8);
            
            // Add some mid-month boost (around 15th)
            $dayOfMonth = $day->day;
            if ($dayOfMonth >= 12 && $dayOfMonth <= 18) {
                $baseCount += rand(1, 3);
            }
            
            $count = $baseCount;

            for ($k = 0; $k < $count; $k++) {
                // Randomize time throughout business hours
                $invoiceTime = $day->copy()->setTime(rand(8, 17), rand(0, 59), rand(0, 59));
                
                $customer = $customers->random();

                // Weighted statuses: completed most frequently (85% completed, 10% pending, 5% cancelled)
                $statusRand = rand(1, 100);
                if ($statusRand <= 85) {
                    $status = 'completed';
                } elseif ($statusRand <= 95) {
                    $status = 'pending';
                } else {
                    $status = 'cancelled';
                }
                
                $paymentMethod = Arr::random(['cash', 'cash', 'credit']); // 66% cash, 33% credit

                // 1–5 products (weighted towards 2-3 items)
                $itemCount = Arr::random([1, 2, 2, 3, 3, 4, 5]);
                $chosen = $products->random(min($products->count(), $itemCount));
                $subtotal = 0;
                $items = [];
                foreach ($chosen as $product) {
                    $qty = rand(1, 8); // Increased quantity range
                    $price = $product->selling_price;
                    $total = $qty * $price;
                    $subtotal += $total;
                    $items[] = ['product' => $product, 'quantity' => $qty, 'price' => $price, 'total' => $total];
                }

                // Calculate VAT (12%) and withholding tax (1%)
                $vatRate = 12.00;
                $vatAmount = $subtotal * ($vatRate / 100);
                
                // Calculate 1% withholding tax on (subtotal + VAT)
                $withholdingTaxRate = 1.00;
                $withholdingTaxAmount = ($subtotal + $vatAmount) * ($withholdingTaxRate / 100);
                
                // Total = Subtotal + VAT - Withholding Tax
                $grandTotal = $subtotal + $vatAmount - $withholdingTaxAmount;

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'user_id' => $creator->id,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => 'HIST-'.strtoupper(substr(md5($invoiceTime->timestamp.rand()), 0, 8)),
                    'notes' => 'Historical seeded invoice ('.$status.')',
                    'subtotal_amount' => $subtotal,
                    'vat_amount' => $vatAmount,
                    'vat_rate' => $vatRate,
                    'withholding_tax_amount' => $withholdingTaxAmount,
                    'withholding_tax_rate' => $withholdingTaxRate,
                    'total_amount' => $grandTotal,
                    'created_at' => $invoiceTime,
                    'updated_at' => $invoiceTime,
                ]);

                foreach ($items as $it) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $it['product']->id,
                        'quantity' => $it['quantity'],
                        'price' => $it['price'],
                        'total' => $it['total'],
                        'created_at' => $invoiceTime,
                        'updated_at' => $invoiceTime,
                    ]);
                }
            }
        }

        $invoiceCount = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();
        $this->command->info("Historical invoices seeded: {$invoiceCount} invoices from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
    }
}










