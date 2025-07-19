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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers')->after('id');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->after('customer_id');
            $table->string('delivery_address', 500)->after('invoice_id');
            $table->string('contact_person')->after('delivery_address');
            $table->string('contact_phone', 20)->after('contact_person');
            $table->date('delivery_date')->after('contact_phone');
            $table->string('delivery_time', 50)->after('delivery_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->after('delivery_time');
            $table->text('notes')->nullable()->after('status');
            $table->bigInteger('delivery_fee')->default(0)->after('notes'); // store in cents
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropColumn([
                'customer_id', 'invoice_id', 'delivery_address', 'contact_person',
                'contact_phone', 'delivery_date', 'delivery_time', 'status', 'notes', 'delivery_fee'
            ]);
        });
    }
};
