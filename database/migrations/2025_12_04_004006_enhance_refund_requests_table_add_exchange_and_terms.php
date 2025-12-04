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
        Schema::table('refund_requests', function (Blueprint $table) {
            // Add request type: refund or exchange
            $table->enum('request_type', ['refund', 'exchange'])->default('refund')->after('status');
            
            // For exchange requests: product to exchange with
            $table->foreignId('exchange_product_id')->nullable()->after('product_id')->constrained('products')->nullOnDelete();
            $table->integer('exchange_quantity')->nullable()->after('exchange_product_id');
            
            // Terms for damaged items handling
            $table->text('damaged_items_terms')->nullable()->after('notes');
            
            // Index for exchange queries
            $table->index(['request_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropIndex(['request_type', 'status']);
            $table->dropConstrainedForeignId('exchange_product_id');
            $table->dropColumn(['request_type', 'exchange_product_id', 'exchange_quantity', 'damaged_items_terms']);
        });
    }
};
