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
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('withholding_tax_amount')->default(0)->after('vat_amount');
            $table->decimal('withholding_tax_rate', 5, 2)->default(1.00)->after('withholding_tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['withholding_tax_amount', 'withholding_tax_rate']);
        });
    }
};
