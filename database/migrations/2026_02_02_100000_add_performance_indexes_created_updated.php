<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for ORDER BY created_at / updated_at on list queries.
     * Composite (family_id, created_at) or (family_id, updated_at) where used in list views.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        $this->addIndexIfMissing('vehicles', 'vehicles_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('tasks', 'tasks_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('documents', 'documents_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('notes', 'notes_family_updated_index', ['family_id', 'updated_at']);
        $this->addIndexIfMissing('transactions', 'transactions_family_date_index', ['family_id', 'transaction_date', 'created_at']);
        $this->addIndexIfMissing('investments', 'investments_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('assets', 'assets_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('finance_accounts', 'finance_accounts_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('family_members', 'family_members_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('medicines', 'medicines_family_created_index', ['family_id', 'created_at']);
        $this->addIndexIfMissing('calendar_events', 'calendar_events_family_start_index', ['family_id', 'start_at']);
        $this->addIndexIfMissing('notifications', 'notifications_user_created_index', ['user_id', 'created_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $drops = [
            'vehicles' => ['vehicles_family_created_index'],
            'tasks' => ['tasks_family_created_index'],
            'documents' => ['documents_family_created_index'],
            'notes' => ['notes_family_updated_index'],
            'transactions' => ['transactions_family_date_index'],
            'investments' => ['investments_family_created_index'],
            'assets' => ['assets_family_created_index'],
            'finance_accounts' => ['finance_accounts_family_created_index'],
            'family_members' => ['family_members_family_created_index'],
            'medicines' => ['medicines_family_created_index'],
            'calendar_events' => ['calendar_events_family_start_index'],
            'notifications' => ['notifications_user_created_index'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $indexName) {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Throwable $e) {
                            // Index may not exist (e.g. SQLite)
                        }
                    }
                });
            }
        }
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }
        if ($this->hasIndex($tableName, $indexName)) {
            return;
        }
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate') || str_contains($msg, 'already exists') || str_contains($msg, 'duplicate key')) {
                return;
            }
            throw $e;
        }
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return false;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$database, $tableName, $indexName]
            );
            if (count($result) > 0) {
                return true;
            }
            $prefix = $tableName . '_';
            $result = $connection->select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$database, $tableName, $prefix . $indexName]
            );
            return count($result) > 0;
        }

        if ($driver === 'pgsql') {
            $result = $connection->select(
                "SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename = ? AND indexname = ?",
                [$tableName, $indexName]
            );
            return count($result) > 0;
        }

        return false;
    }
};
