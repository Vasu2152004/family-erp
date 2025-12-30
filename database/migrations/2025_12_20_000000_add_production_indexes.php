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
        // Add indexes to frequently queried columns for better performance

        // Investments table
        if (Schema::hasTable('investments')) {
            Schema::table('investments', function (Blueprint $table) {
                if (!$this->hasIndex('investments', 'investments_created_at_index')) {
                    $table->index('created_at');
                }
                if (Schema::hasColumn('investments', 'is_hidden') && !$this->hasIndex('investments', 'investments_is_hidden_index')) {
                    $table->index('is_hidden');
                }
                if (Schema::hasColumn('investments', 'investment_type') && !$this->hasIndex('investments', 'investments_investment_type_index')) {
                    $table->index('investment_type');
                }
            });
        }

        // Assets table
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (!$this->hasIndex('assets', 'assets_created_at_index')) {
                    $table->index('created_at');
                }
                if (Schema::hasColumn('assets', 'is_locked') && !$this->hasIndex('assets', 'assets_is_locked_index')) {
                    $table->index('is_locked');
                }
                if (Schema::hasColumn('assets', 'asset_type') && !$this->hasIndex('assets', 'assets_asset_type_index')) {
                    $table->index('asset_type');
                }
            });
        }

        // Transactions table
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!$this->hasIndex('transactions', 'transactions_created_at_index')) {
                    $table->index('created_at');
                }
                if (Schema::hasColumn('transactions', 'transaction_type') && !$this->hasIndex('transactions', 'transactions_transaction_type_index')) {
                    $table->index('transaction_type');
                }
                if (!$this->hasIndex('transactions', 'transactions_family_member_id_index')) {
                    $table->index('family_member_id');
                }
            });
        }

        // Family members table
        if (Schema::hasTable('family_members')) {
            Schema::table('family_members', function (Blueprint $table) {
                if (!$this->hasIndex('family_members', 'family_members_user_id_index')) {
                    $table->index('user_id');
                }
                if (Schema::hasColumn('family_members', 'is_deceased') && !$this->hasIndex('family_members', 'family_members_is_deceased_index')) {
                    $table->index('is_deceased');
                }
            });
        }

        // Notes table
        if (Schema::hasTable('notes')) {
            Schema::table('notes', function (Blueprint $table) {
                if (!$this->hasIndex('notes', 'notes_created_at_index')) {
                    $table->index('created_at');
                }
                if (Schema::hasColumn('notes', 'is_locked') && !$this->hasIndex('notes', 'notes_is_locked_index')) {
                    $table->index('is_locked');
                }
            });
        }

        // Tasks table
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'status') && !$this->hasIndex('tasks', 'tasks_status_index')) {
                    $table->index('status');
                }
                if (Schema::hasColumn('tasks', 'assigned_to') && !$this->hasIndex('tasks', 'tasks_assigned_to_index')) {
                    $table->index('assigned_to');
                }
            });
        }

        // Documents table
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'expiry_date') && !$this->hasIndex('documents', 'documents_expiry_date_index')) {
                    $table->index('expiry_date');
                }
                if (Schema::hasColumn('documents', 'is_sensitive') && !$this->hasIndex('documents', 'documents_is_sensitive_index')) {
                    $table->index('is_sensitive');
                }
            });
        }

        // Vehicles table
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles', 'rc_expiry_date') && !$this->hasIndex('vehicles', 'vehicles_rc_expiry_date_index')) {
                    $table->index('rc_expiry_date');
                }
                if (Schema::hasColumn('vehicles', 'insurance_expiry_date') && !$this->hasIndex('vehicles', 'vehicles_insurance_expiry_date_index')) {
                    $table->index('insurance_expiry_date');
                }
            });
        }

        // Notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!$this->hasIndex('notifications', 'notifications_read_at_index')) {
                    $table->index('read_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes (safe to call even if they don't exist)
        $tables = [
            'investments' => ['created_at', 'is_hidden', 'investment_type'],
            'assets' => ['created_at', 'is_locked', 'asset_type'],
            'transactions' => ['created_at', 'transaction_type', 'family_member_id'],
            'family_members' => ['user_id', 'is_deceased'],
            'notes' => ['created_at', 'is_locked'],
            'tasks' => ['status', 'assigned_to'],
            'documents' => ['expiry_date', 'is_sensitive'],
            'vehicles' => ['rc_expiry_date', 'insurance_expiry_date'],
            'notifications' => ['read_at'],
        ];

        foreach ($tables as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $column) {
                        $indexName = "{$table}_{$column}_index";
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








