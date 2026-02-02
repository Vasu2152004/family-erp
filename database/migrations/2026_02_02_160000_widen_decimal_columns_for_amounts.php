<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Widen decimal(5,2) columns that could truncate values like 10000.
     * interest_rate and alert_threshold store percentages; widening to support edge cases.
     */
    public function up(): void
    {
        if (Schema::hasTable('investments') && Schema::hasColumn('investments', 'interest_rate')) {
            DB::statement('ALTER TABLE investments MODIFY interest_rate DECIMAL(8,2) NULL');
        }

        if (Schema::hasTable('budgets') && Schema::hasColumn('budgets', 'alert_threshold')) {
            DB::statement('ALTER TABLE budgets MODIFY alert_threshold DECIMAL(8,2) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('investments') && Schema::hasColumn('investments', 'interest_rate')) {
            DB::statement('ALTER TABLE investments MODIFY interest_rate DECIMAL(5,2) NULL');
        }

        if (Schema::hasTable('budgets') && Schema::hasColumn('budgets', 'alert_threshold')) {
            DB::statement('ALTER TABLE budgets MODIFY alert_threshold DECIMAL(5,2) NULL');
        }
    }
};
