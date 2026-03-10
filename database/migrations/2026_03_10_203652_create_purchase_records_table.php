<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_records', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('supplier_name');
            $table->string('supplier_tin')->nullable();
            $table->string('supplier_address')->nullable();
            $table->string('receipt_number')->nullable()->comment("Supplier's own invoice/receipt number");
            $table->date('date');
            $table->enum('payment_type', ['cash', 'credit'])->default('cash');
            $table->string('buyer_name')->nullable();
            $table->decimal('vatable_sales', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('withholding_tax', 12, 2)->default(0);
            $table->decimal('total_amount_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_records');
    }
};
