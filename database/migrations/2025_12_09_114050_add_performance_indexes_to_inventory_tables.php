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
        // Add composite indexes for common query patterns
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!$this->indexExists('inventory_items', 'inventory_items_family_category_index')) {
                $table->index(['family_id', 'category_id'], 'inventory_items_family_category_index');
            }
        });

        Schema::table('inventory_item_batches', function (Blueprint $table) {
            if (!$this->indexExists('inventory_item_batches', 'inventory_item_batches_item_expiry_index')) {
                $table->index(['inventory_item_id', 'expiry_date'], 'inventory_item_batches_item_expiry_index');
            }
        });

        Schema::table('shopping_list_items', function (Blueprint $table) {
            if (!$this->indexExists('shopping_list_items', 'shopping_list_items_family_purchased_index')) {
                $table->index(['family_id', 'is_purchased'], 'shopping_list_items_family_purchased_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            try {
                $table->dropIndex('inventory_items_family_category_index');
            } catch (\Throwable $e) {
                // ignore missing index in sqlite tests
            }
        });

        Schema::table('inventory_item_batches', function (Blueprint $table) {
            try {
                $table->dropIndex('inventory_item_batches_item_expiry_index');
            } catch (\Throwable $e) {
                // ignore missing index in sqlite tests
            }
        });

        Schema::table('shopping_list_items', function (Blueprint $table) {
            try {
                $table->dropIndex('shopping_list_items_family_purchased_index');
            } catch (\Throwable $e) {
                // ignore missing index in sqlite tests
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $index]
        );
        return $result[0]->count > 0;
    }
};
