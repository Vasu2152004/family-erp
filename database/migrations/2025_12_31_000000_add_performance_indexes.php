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
        $this->safeAddIndex('shopping_list_items', 'shopping_list_items_family_id_is_purchased_index', ['family_id', 'is_purchased']);
        $this->safeAddIndex('shopping_list_items', 'shopping_list_items_inventory_item_id_index', ['inventory_item_id']);
        $this->safeAddIndex('inventory_items', 'inventory_items_family_id_min_qty_index', ['family_id', 'min_qty']);
        $this->safeAddIndex('family_user_roles', 'family_user_roles_family_user_index', ['family_id', 'user_id']);
        $this->safeAddIndex('family_member_requests', 'family_member_requests_user_status_index', ['requested_user_id', 'status']);
        $this->safeAddIndex('admin_role_requests', 'admin_role_requests_family_status_index', ['family_id', 'status']);
        $this->safeAddIndex('budgets', 'budgets_family_month_year_active_index', ['family_id', 'month', 'year', 'is_active']);
    }

    private function safeAddIndex(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName)) {
            return;
        }
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                if (count($columns) === 1) {
                    $table->index($columns[0], $indexName);
                } else {
                    $table->index($columns, $indexName);
                }
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'duplicate key')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'shopping_list_items' => ['shopping_list_items_family_id_is_purchased_index', 'shopping_list_items_inventory_item_id_index'],
            'inventory_items' => ['inventory_items_family_id_min_qty_index'],
            'family_user_roles' => ['family_user_roles_family_user_index'],
            'family_member_requests' => ['family_member_requests_user_status_index'],
            'admin_role_requests' => ['admin_role_requests_family_status_index'],
            'budgets' => ['budgets_family_month_year_active_index'],
        ];

        foreach ($tables as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $indexName) {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index doesn't exist, continue
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $indexes = $connection->select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, $table, $indexName]
        );
        if (count($indexes) > 0) {
            return true;
        }
        $prefix = $table . '_';
        $indexes = $connection->select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, $table, $prefix . $indexName]
        );
        return count($indexes) > 0;
    }
};

