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
        Schema::table('fuel_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_entries', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('cost')->constrained('transactions')->onDelete('set null');
            }
        });

        Schema::table('service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('service_logs', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->after('cost')->constrained('transactions')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_entries', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
            }
        });

        Schema::table('service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('service_logs', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
            }
        });
    }
};
