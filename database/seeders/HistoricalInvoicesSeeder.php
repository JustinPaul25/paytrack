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

        // Generate invoices for last 60 days
        $days = 60;
        for ($i = $days; $i >= 1; $i--) {
            $day = Carbon::now()->subDays($i)->setTime(rand(8, 17), rand(0, 59), rand(0, 59));

            // 0–3 invoices per day
            $count = rand(0, 3);
            for ($k = 0; $k < $count; $k++) {
                $customer = $customers->random();

                // Weighted statuses: completed most frequently
                $status = Arr::random(['completed', 'completed', 'completed', 'pending', 'cancelled']);
                $paymentMethod = Arr::random(['cash', 'credit']);

                // 1–4 products
                $chosen = $products->random(min($products->count(), rand(1, 4)));
                $subtotal = 0;
                $items = [];
                foreach ($chosen as $product) {
                    $qty = rand(1, 5);
                    $price = $product->selling_price;
                    $total = $qty * $price;
                    $subtotal += $total;
                    $items[] = ['product' => $product, 'quantity' => $qty, 'price' => $price, 'total' => $total];
                }

                $vatRate = 12.00;
                $vatAmount = $subtotal * ($vatRate / 100);
                $grandTotal = $subtotal + $vatAmount;

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'user_id' => $creator->id,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => 'HIST-'.strtoupper(substr(md5($day->timestamp.rand()), 0, 8)),
                    'notes' => 'Historical seeded invoice ('.$status.')',
                    'subtotal_amount' => $subtotal,
                    'vat_amount' => $vatAmount,
                    'vat_rate' => $vatRate,
                    'total_amount' => $grandTotal,
                    'created_at' => $day,
                    'updated_at' => $day,
                ]);

                foreach ($items as $it) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $it['product']->id,
                        'quantity' => $it['quantity'],
                        'price' => $it['price'],
                        'total' => $it['total'],
                        'created_at' => $day,
                        'updated_at' => $day,
                    ]);
                }
            }
        }

        $this->command->info('Historical invoices (last 60 days) seeded.');
    }
}










