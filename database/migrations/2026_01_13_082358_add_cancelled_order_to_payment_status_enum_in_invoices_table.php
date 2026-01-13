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
        // Modify the enum to include 'Cancelled Order'
        // MySQL requires raw SQL to modify enum columns
        DB::statement("ALTER TABLE invoices MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'Cancelled Order') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE invoices MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'");
        
        // Update any invoices with 'Cancelled Order' status back to 'pending'
        DB::table('invoices')
            ->where('payment_status', 'Cancelled Order')
            ->update(['payment_status' => 'pending']);
    }
};
