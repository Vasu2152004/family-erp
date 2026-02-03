<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for common query patterns (dashboard/reports, membership lookups).
     * MySQL/MariaDB only.
     */
    public function up(): void
    {
        $this->safeAddIndex('transactions', 'transactions_family_id_transaction_date_index', ['family_id', 'transaction_date']);
        $this->safeAddIndex('doctor_visits', 'doctor_visits_family_id_visit_date_index', ['family_id', 'visit_date']);
        $this->safeAddIndex('family_members', 'family_members_family_id_user_id_index', ['family_id', 'user_id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $drops = [
            'transactions' => ['transactions_family_id_transaction_date_index'],
            'doctor_visits' => ['doctor_visits_family_id_visit_date_index'],
            'family_members' => ['family_members_family_id_user_id_index'],
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
