<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('invoice_items')->nullOnDelete();
            $table->string('media_link')->nullable()->after('notes');
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropIndex(['invoice_id', 'status']);
            $table->dropConstrainedForeignId('invoice_item_id');
            $table->dropColumn('media_link');
        });
    }
};


