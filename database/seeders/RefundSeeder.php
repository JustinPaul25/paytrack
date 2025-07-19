<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Refund;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;

class RefundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing data
        $invoices = Invoice::with(['invoiceItems.product', 'customer'])->get();
        $users = User::all();

        if ($invoices->isEmpty() || $users->isEmpty()) {
            $this->command->info('No invoices or users found. Skipping refund seeding.');
            return;
        }

        $refundTypes = ['full', 'partial', 'exchange'];
        $refundMethods = ['cash', 'bank_transfer', 'e-wallet', 'credit_note', 'exchange'];
        $statuses = ['pending', 'approved', 'processed', 'completed', 'cancelled'];

        $reasons = [
            'Product damaged during shipping',
            'Customer changed mind',
            'Wrong size/color received',
            'Product defect',
            'Duplicate order',
            'Customer not satisfied with quality',
            'Shipping delay',
            'Incorrect product received'
        ];

        // Create sample refunds
        foreach ($invoices->take(5) as $invoice) {
            foreach ($invoice->invoiceItems->take(2) as $invoiceItem) {
                // Skip if no product
                if (!$invoiceItem->product) continue;

                $refundType = $refundTypes[array_rand($refundTypes)];
                $refundMethod = $refundMethods[array_rand($refundMethods)];
                $status = $statuses[array_rand($statuses)];
                $quantityRefunded = rand(1, $invoiceItem->quantity);
                
                // Calculate refund amount based on quantity and type
                $refundAmount = $invoiceItem->price * $quantityRefunded;
                if ($refundType === 'partial') {
                    $refundAmount = $refundAmount * 0.5; // 50% refund for partial
                }

                $refund = Refund::create([
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'user_id' => $users->random()->id,
                    'quantity_refunded' => $quantityRefunded,
                    'refund_amount' => $refundAmount,
                    'refund_type' => $refundType,
                    'refund_method' => $refundMethod,
                    'status' => $status,
                    'reason' => $reasons[array_rand($reasons)],
                    'notes' => rand(0, 1) ? 'Additional notes for this refund.' : null,
                    'reference_number' => rand(0, 1) ? 'REF-' . strtoupper(uniqid()) : null,
                    'processed_at' => in_array($status, ['processed', 'completed']) ? now()->subDays(rand(1, 7)) : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 3)) : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(0, 7))
                ]);

                // Update product stock if refund is not exchange and is completed
                if ($refundType !== 'exchange' && $status === 'completed') {
                    $invoiceItem->product->increment('stock', $quantityRefunded);
                }
            }
        }

        $this->command->info('Refunds seeded successfully!');
    }
}
