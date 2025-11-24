<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

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

        foreach ($customers as $customer) {
            // For each customer create three invoices with different statuses
            $statusSets = [
                ['status' => 'completed', 'payment_method' => 'cash', 'payment_reference' => 'SEED-CASH-'.uniqid()],
                ['status' => 'pending', 'payment_method' => 'credit', 'payment_reference' => 'SEED-CREDIT-'.uniqid()],
                ['status' => 'cancelled', 'payment_method' => 'credit', 'payment_reference' => 'SEED-CREDIT-'.uniqid()],
            ];

            foreach ($statusSets as $meta) {
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

                // VAT (12%)
                $vatRate = 12.00;
                $vatAmount = $subtotalAmount * ($vatRate / 100);
                $totalAmount = $subtotalAmount + $vatAmount;

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
                    'total_amount' => $totalAmount,
                ]);

                foreach ($items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                    ]);
                }
            }
        }

        $this->command->info('Invoices seeded successfully!');
    }
} 