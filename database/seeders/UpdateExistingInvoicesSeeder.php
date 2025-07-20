<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class UpdateExistingInvoicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            // Calculate subtotal from invoice items
            $subtotalAmount = $invoice->invoiceItems->sum(function ($item) {
                return $item->quantity * $item->price;
            });

            // Calculate VAT (12%)
            $vatRate = 12.00;
            $vatAmount = $subtotalAmount * ($vatRate / 100);
            $totalAmount = $subtotalAmount + $vatAmount;

            // Generate reference number if not exists
            if (empty($invoice->reference_number)) {
                $invoice->reference_number = Invoice::generateReferenceNumber();
            }

            // Update invoice with VAT calculations
            $invoice->update([
                'subtotal_amount' => $subtotalAmount,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
                'total_amount' => $totalAmount,
            ]);
        }

        $this->command->info('Existing invoices updated with VAT calculations and reference numbers!');
    }
}
