<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename admin_bypass to allow_admin_bypass if it exists
        if (Schema::hasColumn('documents', 'admin_bypass') && !Schema::hasColumn('documents', 'allow_admin_bypass')) {
            DB::statement('ALTER TABLE documents CHANGE admin_bypass allow_admin_bypass TINYINT(1) NOT NULL DEFAULT 0');
        } elseif (!Schema::hasColumn('documents', 'allow_admin_bypass')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->boolean('allow_admin_bypass')->default(false)->after('is_sensitive');
            });
        }

        // Rename storage_path to file_path if it exists
        if (Schema::hasColumn('documents', 'storage_path') && !Schema::hasColumn('documents', 'file_path')) {
            DB::statement('ALTER TABLE documents CHANGE storage_path file_path VARCHAR(255) NOT NULL');
        } elseif (!Schema::hasColumn('documents', 'file_path')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->string('file_path')->after('original_name');
            });
        }

        // Rename size_bytes to file_size if it exists
        if (Schema::hasColumn('documents', 'size_bytes') && !Schema::hasColumn('documents', 'file_size')) {
            DB::statement('ALTER TABLE documents CHANGE size_bytes file_size BIGINT UNSIGNED NOT NULL');
        } elseif (!Schema::hasColumn('documents', 'file_size')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedBigInteger('file_size')->after('mime_type');
            });
        }

        // Rename reminder_sent_at to last_notified_at if it exists
        if (Schema::hasColumn('documents', 'reminder_sent_at') && !Schema::hasColumn('documents', 'last_notified_at')) {
            DB::statement('ALTER TABLE documents CHANGE reminder_sent_at last_notified_at TIMESTAMP NULL');
        } elseif (!Schema::hasColumn('documents', 'last_notified_at')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->timestamp('last_notified_at')->nullable()->after('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse renames if needed
        if (Schema::hasColumn('documents', 'allow_admin_bypass') && !Schema::hasColumn('documents', 'admin_bypass')) {
            DB::statement('ALTER TABLE documents CHANGE allow_admin_bypass admin_bypass TINYINT(1) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('documents', 'file_path') && !Schema::hasColumn('documents', 'storage_path')) {
            DB::statement('ALTER TABLE documents CHANGE file_path storage_path VARCHAR(255) NOT NULL');
        }

        if (Schema::hasColumn('documents', 'file_size') && !Schema::hasColumn('documents', 'size_bytes')) {
            DB::statement('ALTER TABLE documents CHANGE file_size size_bytes BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('documents', 'last_notified_at') && !Schema::hasColumn('documents', 'reminder_sent_at')) {
            DB::statement('ALTER TABLE documents CHANGE last_notified_at reminder_sent_at TIMESTAMP NULL');
        }
    }
};
