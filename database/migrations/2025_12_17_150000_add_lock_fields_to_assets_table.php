<?php

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
        if (!Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('notes');
            }

            if (!Schema::hasColumn('assets', 'pin_hash')) {
                $table->string('pin_hash')->nullable()->after('is_locked');
            }

            if (!Schema::hasColumn('assets', 'encrypted_notes')) {
                $table->text('encrypted_notes')->nullable()->after('pin_hash');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'encrypted_notes')) {
                $table->dropColumn('encrypted_notes');
            }
            if (Schema::hasColumn('assets', 'pin_hash')) {
                $table->dropColumn('pin_hash');
            }
            if (Schema::hasColumn('assets', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};










