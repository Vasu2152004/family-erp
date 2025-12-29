<?php

declare(strict_types=1);

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
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->after('notes');
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->onDelete('set null')->after('amount');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null')->after('budget_id');
            
            $table->index('budget_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['transaction_id']);
            $table->dropIndex(['budget_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropColumn(['amount', 'budget_id', 'transaction_id']);
        });
    }
};
