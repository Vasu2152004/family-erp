<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('family_members')) {
            return;
        }

        Schema::table('family_members', function (Blueprint $table) {
            if (!Schema::hasColumn('family_members', 'is_deceased_pending')) {
                $table->boolean('is_deceased_pending')->default(false)->after('is_deceased');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('family_members')) {
            return;
        }

        Schema::table('family_members', function (Blueprint $table) {
            if (Schema::hasColumn('family_members', 'is_deceased_pending')) {
                $table->dropColumn('is_deceased_pending');
            }
        });
    }
};









