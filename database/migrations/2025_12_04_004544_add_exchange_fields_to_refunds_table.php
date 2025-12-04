<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            // For exchange refunds: product being exchanged for
            $table->foreignId('exchange_product_id')->nullable()->after('product_id')->constrained('products')->nullOnDelete();
            $table->integer('exchange_quantity')->nullable()->after('exchange_product_id');
            
            // Index for exchange queries
            $table->index(['refund_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex(['refund_type', 'status']);
            $table->dropConstrainedForeignId('exchange_product_id');
            $table->dropColumn(['exchange_product_id', 'exchange_quantity']);
        });
    }
};
