<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class RecalculateExistingInvoicesSeeder extends Seeder
{
    /**
     * Recalculate VAT and withholding tax for existing invoices.
     * This seeder updates invoices that were created before the VAT/withholding tax update.
     */
    public function run(): void
    {
        $this->command->info('Starting recalculation of existing invoices...');
        
        // Get all invoices that have vat_amount = 0 (old invoices before the update)
        $invoices = Invoice::where('vat_amount', 0)
            ->orWhere('withholding_tax_amount', 0)
            ->with('invoiceItems', 'deliveries')
            ->get();
        
        if ($invoices->isEmpty()) {
            $this->command->info('No invoices need recalculation.');
            return;
        }
        
        $this->command->info("Found {$invoices->count()} invoices to recalculate.");
        
        DB::beginTransaction();
        try {
            foreach ($invoices as $invoice) {
                // Recalculate subtotal from invoice items
                $subtotalAmount = 0;
                foreach ($invoice->invoiceItems as $item) {
                    $subtotalAmount += $item->quantity * $item->price;
                }
                
                // Calculate 12% VAT on subtotal
                $vatRate = 12.00;
                $vatAmount = $subtotalAmount * ($vatRate / 100);
                
                // Calculate 1% withholding tax on (subtotal + VAT)
                $withholdingTaxRate = 1.00;
                $withholdingTaxAmount = ($subtotalAmount + $vatAmount) * ($withholdingTaxRate / 100);
                
                // Calculate delivery fees
                $deliveryFees = 0;
                if ($invoice->deliveries && $invoice->deliveries->count() > 0) {
                    foreach ($invoice->deliveries as $delivery) {
                        $deliveryFees += $delivery->delivery_fee / 100; // Convert from cents to dollars
                    }
                }
                
                // Total = Subtotal + VAT - Withholding Tax + Delivery Fees
                $totalAmount = $subtotalAmount + $vatAmount - $withholdingTaxAmount + $deliveryFees;
                
                // Update invoice
                $invoice->subtotal_amount = $subtotalAmount;
                $invoice->vat_amount = $vatAmount;
                $invoice->vat_rate = $vatRate;
                $invoice->withholding_tax_amount = $withholdingTaxAmount;
                $invoice->withholding_tax_rate = $withholdingTaxRate;
                $invoice->total_amount = $totalAmount;
                $invoice->save();
                
                $this->command->info("Updated Invoice #{$invoice->id} ({$invoice->reference_number})");
            }
            
            DB::commit();
            $this->command->info("Successfully recalculated {$invoices->count()} invoices.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error recalculating invoices: " . $e->getMessage());
            throw $e;
        }
    }
}
