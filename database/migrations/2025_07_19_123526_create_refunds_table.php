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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('user_id')->constrained('users'); // User who processed the refund
            $table->string('refund_number')->unique(); // Unique refund identifier
            $table->integer('quantity_refunded'); // Quantity being refunded
            $table->integer('refund_amount'); // Amount being refunded (in cents)
            $table->enum('refund_type', ['full', 'partial', 'exchange'])->default('full');
            $table->enum('refund_method', ['cash', 'bank_transfer', 'e-wallet', 'credit_note', 'exchange'])->default('cash');
            $table->enum('status', ['pending', 'approved', 'processed', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable(); // Reason for refund
            $table->text('notes')->nullable(); // Additional notes
            $table->string('reference_number')->nullable(); // External reference number
            $table->timestamp('processed_at')->nullable(); // When refund was processed
            $table->timestamp('completed_at')->nullable(); // When refund was completed
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['invoice_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['refund_number']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
