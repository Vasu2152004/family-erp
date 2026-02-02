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
        if (Schema::hasTable('medical_records') && !$this->hasIndex('medical_records', 'medical_records_family_tenant_index')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->index(['family_id', 'tenant_id'], 'medical_records_family_tenant_index');
            });
        }

        if (Schema::hasTable('prescriptions') && !$this->hasIndex('prescriptions', 'prescriptions_family_tenant_status_index')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->index(['family_id', 'tenant_id', 'status'], 'prescriptions_family_tenant_status_index');
            });
        }

        if (Schema::hasTable('doctor_visits') && !$this->hasIndex('doctor_visits', 'doctor_visits_family_tenant_index')) {
            Schema::table('doctor_visits', function (Blueprint $table) {
                $table->index(['family_id', 'tenant_id'], 'doctor_visits_family_tenant_index');
            });
        }
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

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, $tableName, $indexName]
        );
        return count($result) > 0;
    }
};
