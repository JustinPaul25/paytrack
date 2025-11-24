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
        // Update invoices table
        DB::statement("ALTER TABLE invoices MODIFY COLUMN payment_method ENUM('cash', 'credit') DEFAULT 'cash'");
        
        // Update orders table
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'credit') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert invoices table
        DB::statement("ALTER TABLE invoices MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'e-wallet', 'other') DEFAULT 'cash'");
        
        // Revert orders table
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'bank_transfer', 'e-wallet', 'other') DEFAULT 'cash'");
    }
};
