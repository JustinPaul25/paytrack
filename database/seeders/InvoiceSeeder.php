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
        // Get some customers, products, and users for seeding
        $customers = Customer::all();
        $products = Product::all();
        $users = User::all();

        if ($customers->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping invoice seeding: No customers, products, or users found.');
            return;
        }

        // Create some sample invoices
        $invoices = [
            [
                'customer_id' => $customers->first()->id,
                'user_id' => $users->first()->id,
                'status' => 'completed',
                'payment_method' => 'cash',
                'payment_reference' => 'REF-001',
                'notes' => 'Sample completed invoice',
            ],
            [
                'customer_id' => $customers->first()->id,
                'user_id' => $users->first()->id,
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'REF-002',
                'notes' => 'Sample pending invoice',
            ],
            [
                'customer_id' => $customers->first()->id,
                'user_id' => $users->first()->id,
                'status' => 'draft',
                'payment_method' => 'e-wallet',
                'notes' => 'Sample draft invoice',
            ],
        ];

        foreach ($invoices as $invoiceData) {
            // Add 1-3 random products to calculate total first
            $randomProducts = $products->random(rand(1, 3));
            $totalAmount = 0;

            foreach ($randomProducts as $product) {
                $quantity = rand(1, 5);
                $price = $product->selling_price;
                $total = $quantity * $price;
                $totalAmount += $total;
            }

            // Create invoice with total_amount
            $invoice = Invoice::create(array_merge($invoiceData, ['total_amount' => $totalAmount]));

            // Create invoice items
            foreach ($randomProducts as $product) {
                $quantity = rand(1, 5);
                $price = $product->selling_price;
                $total = $quantity * $price;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ]);
            }
        }

        $this->command->info('Invoices seeded successfully!');
    }
} 