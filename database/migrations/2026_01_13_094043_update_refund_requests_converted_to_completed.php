<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing 'converted' records to 'completed' first
        DB::table('refund_requests')
            ->where('status', 'converted')
            ->update(['status' => 'completed']);

        // Update the enum values - need to use raw SQL as Laravel doesn't support enum modification directly
        DB::statement("ALTER TABLE refund_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
        
        // Rename the column using raw SQL for better compatibility
        // First, drop the foreign key constraint if it exists
        try {
            DB::statement("ALTER TABLE refund_requests DROP FOREIGN KEY refund_requests_converted_refund_id_foreign");
        } catch (\Exception $e) {
            // Constraint might not exist or have a different name, try to find and drop it
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'refund_requests' AND COLUMN_NAME = 'converted_refund_id'");
            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE refund_requests DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                } catch (\Exception $e2) {
                    // Ignore if constraint doesn't exist
                }
            }
        }
        
        // Rename the column
        DB::statement("ALTER TABLE refund_requests CHANGE converted_refund_id completed_refund_id BIGINT UNSIGNED NULL");
        
        // Re-add the foreign key constraint with new name
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_completed_refund_id_foreign FOREIGN KEY (completed_refund_id) REFERENCES refunds(id) ON DELETE SET NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint
        try {
            DB::statement("ALTER TABLE refund_requests DROP FOREIGN KEY refund_requests_completed_refund_id_foreign");
        } catch (\Exception $e) {
            // Constraint might not exist or have a different name
            $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'refund_requests' AND COLUMN_NAME = 'completed_refund_id'");
            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE refund_requests DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                } catch (\Exception $e2) {
                    // Ignore if constraint doesn't exist
                }
            }
        }
        
        // Rename the column back using raw SQL
        DB::statement("ALTER TABLE refund_requests CHANGE completed_refund_id converted_refund_id BIGINT UNSIGNED NULL");
        
        // Revert enum
        DB::statement("ALTER TABLE refund_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'converted') DEFAULT 'pending'");
        
        // Update existing 'completed' records back to 'converted'
        DB::table('refund_requests')
            ->where('status', 'completed')
            ->update(['status' => 'converted']);
        
        // Restore foreign key constraint
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_converted_refund_id_foreign FOREIGN KEY (converted_refund_id) REFERENCES refunds(id) ON DELETE SET NULL");
    }
};
