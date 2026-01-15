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
        // Update existing invoices with status='cancelled' to have payment_status='Cancelled Order'
        // This fixes invoices that were cancelled before the payment status logic was implemented
        DB::table('invoices')
            ->where('status', 'cancelled')
            ->where('payment_status', '!=', 'Cancelled Order')
            ->update(['payment_status' => 'Cancelled Order']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert cancelled invoices payment_status back to 'pending'
        DB::table('invoices')
            ->where('status', 'cancelled')
            ->where('payment_status', 'Cancelled Order')
            ->update(['payment_status' => 'pending']);
    }
};
