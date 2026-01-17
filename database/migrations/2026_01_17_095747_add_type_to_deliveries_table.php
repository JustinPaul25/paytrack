<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->enum('type', ['order', 'return'])->default('order')->after('status');
        });

        // Update existing records based on notes
        DB::table('deliveries')
            ->where(function ($query) {
                $query->where('notes', 'like', '%Return pickup for refund request%')
                      ->orWhere('notes', 'like', '%Delivery for refund%')
                      ->orWhere('notes', 'like', '%return pickup%')
                      ->orWhere('notes', 'like', '%refund request%');
            })
            ->update(['type' => 'return']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
