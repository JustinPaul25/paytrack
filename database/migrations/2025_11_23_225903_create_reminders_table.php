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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'bill_payment', 'customer_due', 'credit_term'
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'completed', 'dismissed'])->default('pending');
            $table->boolean('is_read')->default(false);
            
            // Polymorphic relationship for related entity
            $table->nullableMorphs('remindable');
            
            // Additional context fields
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'status', 'due_date']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
