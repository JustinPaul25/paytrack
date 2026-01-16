<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Delivery;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class FixDeliveryFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:delivery-fees {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix delivery fees that were incorrectly stored due to double conversion bug';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }
        
        $this->info('Scanning for deliveries with potentially incorrect fees...');
        
        // Get all deliveries
        $deliveries = Delivery::all();
        $fixed = 0;
        $skipped = 0;
        
        foreach ($deliveries as $delivery) {
            // Get the raw database value (in cents)
            $rawValue = DB::table('deliveries')
                ->where('id', $delivery->id)
                ->value('delivery_fee');
            
            // If the raw value is suspiciously large (likely double-converted)
            // A normal fee would be 50-2000 PHP = 5000-200000 cents
            // A double-converted fee would be 5000-200000 * 100 = 500000-20000000 cents
            if ($rawValue > 500000) {
                $originalFee = $rawValue / 100; // What it displays as now (wrong)
                // Reverse the double conversion: value was multiplied by 100 twice
                // So divide by 100 to get back to the correct cents value
                $correctedFeeInCents = $rawValue / 100; // This gives us the correct cents value
                $correctedFeeInDollars = $correctedFeeInCents / 100; // Convert to dollars for display
                
                $this->warn("Delivery ID {$delivery->id}:");
                $this->line("  Current (wrong): ₱" . number_format($originalFee, 2));
                $this->line("  Should be: ₱" . number_format($correctedFeeInDollars, 2));
                $this->line("  Raw DB value: {$rawValue} cents");
                
                if (!$dryRun) {
                    // Update directly in database to bypass the setter
                    DB::table('deliveries')
                        ->where('id', $delivery->id)
                        ->update(['delivery_fee' => (int) round($correctedFeeInCents)]);
                    
                    // Recalculate invoice total
                    if ($delivery->invoice_id) {
                        $invoice = Invoice::find($delivery->invoice_id);
                        if ($invoice) {
                            $allDeliveryFees = $invoice->deliveries()->sum('delivery_fee') / 100;
                            $newTotal = $invoice->subtotal_amount + $invoice->vat_amount - $invoice->withholding_tax_amount + $allDeliveryFees;
                            $invoice->total_amount = $newTotal;
                            $invoice->save();
                            $this->line("  ✓ Updated invoice #{$invoice->reference_number}");
                        }
                    }
                    
                    $this->info("  ✓ Fixed!");
                }
                
                $fixed++;
            } else {
                $skipped++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Deliveries with incorrect fees: {$fixed}");
        $this->line("  Deliveries with correct fees: {$skipped}");
        
        if ($dryRun && $fixed > 0) {
            $this->newLine();
            $this->warn("Run without --dry-run to apply these fixes:");
            $this->line("  php artisan fix:delivery-fees");
        } elseif (!$dryRun && $fixed > 0) {
            $this->newLine();
            $this->info("✓ All delivery fees have been corrected!");
        } else {
            $this->newLine();
            $this->info("✓ No incorrect delivery fees found!");
        }
        
        return 0;
    }
}
