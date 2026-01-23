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
        // Shopping list items - frequently queried by family_id and is_purchased
        if (Schema::hasTable('shopping_list_items')) {
            Schema::table('shopping_list_items', function (Blueprint $table) {
                if (!$this->hasIndex('shopping_list_items', 'shopping_list_items_family_id_is_purchased_index')) {
                    $table->index(['family_id', 'is_purchased'], 'shopping_list_items_family_id_is_purchased_index');
                }
                if (!$this->hasIndex('shopping_list_items', 'shopping_list_items_inventory_item_id_index')) {
                    $table->index('inventory_item_id');
                }
            });
        }

        // Inventory items - frequently queried by family_id and min_qty
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                if (!$this->hasIndex('inventory_items', 'inventory_items_family_id_min_qty_index')) {
                    $table->index(['family_id', 'min_qty'], 'inventory_items_family_id_min_qty_index');
                }
            });
        }

        // Family user roles - frequently queried by family_id and user_id
        if (Schema::hasTable('family_user_roles')) {
            Schema::table('family_user_roles', function (Blueprint $table) {
                if (!$this->hasIndex('family_user_roles', 'family_user_roles_family_user_index')) {
                    $table->index(['family_id', 'user_id'], 'family_user_roles_family_user_index');
                }
            });
        }

        // Family member requests - frequently queried by requested_user_id and status
        if (Schema::hasTable('family_member_requests')) {
            Schema::table('family_member_requests', function (Blueprint $table) {
                if (!$this->hasIndex('family_member_requests', 'family_member_requests_user_status_index')) {
                    $table->index(['requested_user_id', 'status'], 'family_member_requests_user_status_index');
                }
            });
        }

        // Admin role requests - frequently queried by family_id and status
        if (Schema::hasTable('admin_role_requests')) {
            Schema::table('admin_role_requests', function (Blueprint $table) {
                if (!$this->hasIndex('admin_role_requests', 'admin_role_requests_family_status_index')) {
                    $table->index(['family_id', 'status'], 'admin_role_requests_family_status_index');
                }
            });
        }

        // Budgets - frequently queried by family_id, month, year, is_active
        if (Schema::hasTable('budgets')) {
            Schema::table('budgets', function (Blueprint $table) {
                if (!$this->hasIndex('budgets', 'budgets_family_month_year_active_index')) {
                    $table->index(['family_id', 'month', 'year', 'is_active'], 'budgets_family_month_year_active_index');
                }
            });
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
            "SELECT INDEX_NAME FROM information_schema.STATISTICS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$database, $table, $indexName]
        );
        return count($indexes) > 0;
    }
};

