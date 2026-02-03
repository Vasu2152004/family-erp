<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for Dashboard, PerformanceHelper, and common query patterns.
     * Optimized for serverless (MySQL/MariaDB).
     */
    public function up(): void
    {
        // PerformanceHelper: user_id lookups for family access
        $this->safeAddIndex('family_user_roles', 'family_user_roles_user_id_index', ['user_id']);
        $this->safeAddIndex('family_members', 'family_members_user_id_index', ['user_id']);

        // Admin role requests: family_id + user_id + status
        $this->safeAddIndex('admin_role_requests', 'admin_role_requests_family_user_status_index', ['family_id', 'user_id', 'status']);

        // Family member deceased votes
        $this->safeAddIndex('family_member_deceased_votes', 'family_member_deceased_votes_member_id_index', ['family_member_id']);

        // Families: tenant_id + name for duplicate check
        $this->safeAddIndex('families', 'families_tenant_id_name_index', ['tenant_id', 'name']);

        // Dashboard: tenant_id + family_id (vehicles/tasks already have this from create migrations)
        $this->safeAddIndex('transactions', 'transactions_tenant_family_index', ['tenant_id', 'family_id']);
        $this->safeAddIndex('inventory_items', 'inventory_items_tenant_family_index', ['tenant_id', 'family_id']);
        $this->safeAddIndex('calendar_events', 'calendar_events_tenant_family_index', ['tenant_id', 'family_id']);
        $this->safeAddIndex('doctor_visits', 'doctor_visits_tenant_family_index', ['tenant_id', 'family_id']);

        // Finance summary: family_id + type + transaction_date
        $this->safeAddIndex('transactions', 'transactions_family_type_date_index', ['family_id', 'type', 'transaction_date']);

        // Finance accounts: family_id + is_active
        $this->safeAddIndex('finance_accounts', 'finance_accounts_family_active_index', ['family_id', 'is_active']);
    }

    public function down(): void
    {
        $drops = [
            'family_user_roles' => ['family_user_roles_user_id_index'],
            'family_members' => ['family_members_user_id_index'],
            'admin_role_requests' => ['admin_role_requests_family_user_status_index'],
            'family_member_deceased_votes' => ['family_member_deceased_votes_member_id_index'],
            'families' => ['families_tenant_id_name_index'],
            'transactions' => ['transactions_tenant_family_index', 'transactions_family_type_date_index'],
            'inventory_items' => ['inventory_items_tenant_family_index'],
            'calendar_events' => ['calendar_events_tenant_family_index'],
            'doctor_visits' => ['doctor_visits_tenant_family_index'],
            'finance_accounts' => ['finance_accounts_family_active_index'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $indexName) {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Throwable $e) {
                            // Index may not exist
                        }
                    }
                });
            }
        }
    }

    private function safeAddIndex(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName)) {
            return;
        }
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'duplicate key')) {
                return;
            }
            throw $e;
        }
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, $tableName, $indexName]
        );
        return count($result) > 0;
    }
};
