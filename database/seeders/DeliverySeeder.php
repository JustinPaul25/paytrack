<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Deliveries must come from invoices that are created from approved customer orders.
     * Only creates deliveries for orders with delivery_type = 'delivery' (not 'pickup').
     */
    public function run(): void
    {
        // Get approved orders that have invoices and require delivery (not pickup)
        $orders = Order::where('status', 'approved')
            ->whereNotNull('invoice_id')
            ->where('delivery_type', 'delivery')
            ->with(['invoice.customer', 'customer'])
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->warn('Skipping deliveries seeding: no approved orders with delivery_type="delivery" found.');
            return;
        }

        $timeWindows = [
            '09:00 AM - 12:00 PM',
            '01:00 PM - 04:00 PM',
            '04:00 PM - 07:00 PM',
        ];

        $statuses = ['pending', 'completed', 'pending', 'completed', 'cancelled'];

        foreach ($orders as $index => $order) {
            // Get customer from order or invoice
            $customer = $order->customer ?? $order->invoice?->customer;
            
            if (!$customer) {
                $this->command?->warn("Skipping order {$order->reference_number}: no customer found.");
                continue;
            }

            // Ensure invoice exists
            if (!$order->invoice) {
                $this->command?->warn("Skipping order {$order->reference_number}: no invoice found.");
                continue;
            }

            // Format phone number if it exists - ensure it's in +63XXXXXXXXXX format
            $contactPhone = $customer->phone ?? '+63-900-000-0000';
            // Normalize phone to +63XXXXXXXXXX format if it exists
            if ($contactPhone && strpos($contactPhone, '+63') !== 0) {
                // Remove any existing formatting and add +63 prefix
                $digits = preg_replace('/[^0-9]/', '', $contactPhone);
                if (substr($digits, 0, 2) === '63') {
                    $contactPhone = '+' . $digits;
                } elseif (substr($digits, 0, 1) === '0') {
                    $contactPhone = '+63' . substr($digits, 1);
                } else {
                    $contactPhone = '+63' . $digits;
                }
            }

            $payload = [
                'customer_id' => $customer->id,
                'invoice_id' => $order->invoice->id,
                'delivery_address' => $customer->address ?? 'To be confirmed',
                'contact_person' => $customer->name ?? 'Logistics Contact',
                'contact_phone' => $contactPhone,
                'delivery_date' => Carbon::now()->addDays($index + 1),
                'delivery_time' => $timeWindows[$index % count($timeWindows)],
                'status' => $statuses[$index % count($statuses)],
                'notes' => "Delivery for order {$order->reference_number} and invoice {$order->invoice->reference_number}",
                'delivery_fee' => (350 + ($index * 150)) * 100, // Store in cents
            ];

            Delivery::updateOrCreate(
                ['invoice_id' => $order->invoice->id],
                $payload
            );
        }

        $this->command?->info("Deliveries seeded successfully! Created/updated " . Delivery::count() . " deliveries.");
    }
}

