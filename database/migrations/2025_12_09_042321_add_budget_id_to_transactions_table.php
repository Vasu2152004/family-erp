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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'budget_id')) {
                // Add column after is_shared or at the end if budget_allocation doesn't exist
                if (Schema::hasColumn('transactions', 'budget_allocation')) {
                    $table->foreignId('budget_id')->nullable()->after('budget_allocation')->constrained('budgets')->onDelete('set null');
                } else {
                    $table->foreignId('budget_id')->nullable()->after('is_shared')->constrained('budgets')->onDelete('set null');
                }
                $table->index('budget_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'budget_id')) {
                $table->dropForeign(['budget_id']);
                $table->dropIndex(['budget_id']);
                $table->dropColumn('budget_id');
            }
        });
    }
};
