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
        // Update existing 'completed' records to 'approved'
        DB::table('refund_requests')
            ->where('status', 'completed')
            ->update(['status' => 'approved']);

        // Update the enum values to remove 'completed' - need to use raw SQL as Laravel doesn't support enum modification directly
        DB::statement("ALTER TABLE refund_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum to include 'completed'
        DB::statement("ALTER TABLE refund_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
        
        // Note: We don't revert the data changes (approved back to completed) 
        // as we can't determine which 'approved' records were originally 'completed'
    }
};
