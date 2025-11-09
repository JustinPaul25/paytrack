<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $invoices = Invoice::with('customer')->get();

        if ($customers->isEmpty() || $invoices->isEmpty()) {
            $this->command?->warn('Skipping deliveries seeding: customers or invoices are missing.');
            return;
        }

        $timeWindows = [
            '09:00 AM - 12:00 PM',
            '01:00 PM - 04:00 PM',
            '04:00 PM - 07:00 PM',
        ];

        $statuses = ['pending', 'completed', 'pending', 'completed', 'cancelled'];

        foreach ($invoices as $index => $invoice) {
            $customer = $invoice->customer ?? $customers->random();

            $payload = [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'delivery_address' => $customer->address ?? 'To be confirmed',
                'contact_person' => $customer->name ?? 'Logistics Contact',
                'contact_phone' => $customer->phone ?? '+63-900-000-0000',
                'delivery_date' => Carbon::now()->addDays($index + 1),
                'delivery_time' => $timeWindows[$index % count($timeWindows)],
                'status' => $statuses[$index % count($statuses)],
                'notes' => 'Seeded delivery generated for demo purposes.',
                'delivery_fee' => 350 + ($index * 150),
            ];

            Delivery::updateOrCreate(
                ['invoice_id' => $invoice->id],
                $payload
            );
        }

        $this->command?->info('Deliveries seeded successfully!');
    }
}

