<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliveryChecklistTestSeeder extends Seeder
{
    /**
     * Creates deliveries scheduled for TODAY so you can test the "Today's Deliveries Checklist"
     * on the dashboard (Admin/Staff).
     *
     * Run with: php artisan db:seed --class=DeliveryChecklistTestSeeder
     */
    public function run(): void
    {
        $today = Carbon::today();

        $customers = Customer::limit(10)->get();
        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Run CustomerSeeder first.');
            return;
        }

        // Invoices with customer (optional; we can create deliveries without invoice for testing)
        $invoices = Invoice::whereNotNull('customer_id')->with('customer')->limit(10)->get();

        $timeSlots = [
            '08:00 AM - 10:00 AM',
            '10:00 AM - 12:00 PM',
            '01:00 PM - 03:00 PM',
            '03:00 PM - 05:00 PM',
            '05:00 PM - 07:00 PM',
        ];

        $statuses = ['pending', 'pending', 'completed', 'completed', 'cancelled'];
        $types = ['order', 'order', 'order', 'order', 'return'];

        $created = 0;
        $maxDeliveries = 8;
        $usedInvoices = [];

        for ($i = 0; $i < $maxDeliveries; $i++) {
            $customer = $customers[$i % $customers->count()];
            $invoice = $invoices->isNotEmpty() ? $invoices[$i % $invoices->count()] : null;

            // Avoid duplicate invoice_id if we're linking to invoices
            if ($invoice && in_array($invoice->id, $usedInvoices)) {
                $invoice = $invoices->first(fn ($inv) => ! in_array($inv->id, $usedInvoices));
            }
            if ($invoice) {
                $usedInvoices[] = $invoice->id;
            }

            $contactPhone = $customer->phone ?? '+63-900-000-0000';
            if ($contactPhone && strpos($contactPhone, '+63') !== 0) {
                $digits = preg_replace('/[^0-9]/', '', $contactPhone);
                $contactPhone = substr($digits, 0, 2) === '63' ? '+' . $digits : '+63' . ltrim($digits, '0');
            }

            Delivery::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice?->id,
                'delivery_address' => $customer->address ?? '123 Test St, Metro Manila',
                'contact_person' => $customer->name ?? 'Contact Person',
                'contact_phone' => $contactPhone,
                'delivery_date' => $today,
                'delivery_time' => $timeSlots[$i % count($timeSlots)],
                'status' => $statuses[$i % count($statuses)],
                'type' => $types[$i % count($types)],
                'notes' => 'Seeded for delivery checklist test.',
                'delivery_fee' => (50 + $i * 25), // dollars; model stores in cents
            ]);
            $created++;
        }

        $this->command->info("Created {$created} deliveries for today ({$today->toDateString()}). Visit the dashboard as Admin/Staff to see the checklist.");
    }
}
