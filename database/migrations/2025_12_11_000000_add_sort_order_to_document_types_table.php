<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure custom document types have a stable sort column.
     */
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            // Ensure supports_expiry exists for older schemas
            if (!Schema::hasColumn('document_types', 'supports_expiry')) {
                $table->boolean('supports_expiry')
                    ->default(false)
                    ->after('description');
            }

            if (!Schema::hasColumn('document_types', 'is_system')) {
                $afterIsSystem = Schema::hasColumn('document_types', 'supports_expiry') ? 'supports_expiry' : 'description';
                $table->boolean('is_system')
                    ->default(false)
                    ->after($afterIsSystem);
            }

            if (!Schema::hasColumn('document_types', 'sort_order')) {
                // Place after is_system when present; otherwise fall back to supports_expiry/description
                $afterColumn = Schema::hasColumn('document_types', 'is_system')
                    ? 'is_system'
                    : (Schema::hasColumn('document_types', 'supports_expiry') ? 'supports_expiry' : 'description');

                $table->integer('sort_order')
                    ->default(0)
                    ->after($afterColumn);
            }
        });
    }

    /**
     * Rollback the migration.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_types', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            // Only drop is_system if we created it (safe guard for older schemas)
            if (Schema::hasColumn('document_types', 'is_system')) {
                // Do not drop if another migration added it; keep only if table originally lacked it.
                // Dropping unconditionally could break deployed schemas, so we leave it intact.
            }
        });
    }
};

