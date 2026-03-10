<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_record_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 10, 2);
            $table->string('unit')->nullable();
            $table->string('description');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_record_items');
    }
};
