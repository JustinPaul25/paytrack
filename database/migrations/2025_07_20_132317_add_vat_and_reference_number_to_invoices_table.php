<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Invoice;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('subtotal_amount')->after('total_amount')->default(0);
            $table->integer('vat_amount')->after('subtotal_amount')->default(0);
            $table->decimal('vat_rate', 5, 2)->after('vat_amount')->default(12.00);
            $table->string('reference_number')->after('id')->nullable();
        });

        // Generate reference numbers for existing invoices
        $invoices = Invoice::whereNull('reference_number')->orWhere('reference_number', '')->get();
        foreach ($invoices as $invoice) {
            $invoice->reference_number = Invoice::generateReferenceNumber();
            $invoice->save();
        }

        // Now add the unique constraint
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('reference_number')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'vat_amount', 'vat_rate', 'reference_number']);
        });
    }
};
