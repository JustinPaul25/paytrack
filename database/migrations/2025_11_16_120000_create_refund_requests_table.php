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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            // Public requester details
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // Linkage hints
            $table->string('invoice_reference')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // Requested details
            $table->integer('quantity')->default(1);
            $table->integer('amount_requested')->nullable(); // in cents; optional for exchanges
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            // Workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('converted_refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['invoice_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};


