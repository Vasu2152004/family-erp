<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for frequent WHERE (family_id, tenant_id) and similar filters.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->safeAddIndex('medical_records', 'medical_records_family_tenant_index', ['family_id', 'tenant_id']);
        $this->safeAddIndex('prescriptions', 'prescriptions_family_tenant_status_index', ['family_id', 'tenant_id', 'status']);
        $this->safeAddIndex('doctor_visits', 'doctor_visits_family_tenant_index', ['family_id', 'tenant_id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $drops = [
            'medical_records' => ['medical_records_family_tenant_index'],
            'prescriptions' => ['prescriptions_family_tenant_status_index'],
            'doctor_visits' => ['doctor_visits_family_tenant_index'],
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
