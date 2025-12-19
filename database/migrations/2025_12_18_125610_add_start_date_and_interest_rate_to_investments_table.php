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
        Schema::table('investments', function (Blueprint $table) {
            // Check if columns already exist before adding them
            if (!Schema::hasColumn('investments', 'start_date')) {
                $table->date('start_date')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('investments', 'interest_rate')) {
                $table->decimal('interest_rate', 5, 2)->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('investments', 'interest_period')) {
                $table->enum('interest_period', ['YEARLY', 'MONTHLY', 'QUARTERLY'])->nullable()->after('interest_rate');
            } else {
                // Column exists but enum values might be wrong - fix it
                DB::statement("ALTER TABLE investments MODIFY COLUMN interest_period ENUM('YEARLY', 'MONTHLY', 'QUARTERLY') NULL");
            }
            if (!Schema::hasColumn('investments', 'monthly_premium')) {
                $table->decimal('monthly_premium', 15, 2)->nullable()->after('interest_period');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'interest_rate', 'interest_period', 'monthly_premium']);
        });
    }
};
