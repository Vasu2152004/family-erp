<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->unique('user_id', 'family_members_user_unique');
        });

        Schema::table('family_user_roles', function (Blueprint $table) {
            $table->unique('user_id', 'family_user_roles_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropUnique('family_members_user_unique');
        });

        Schema::table('family_user_roles', function (Blueprint $table) {
            $table->dropUnique('family_user_roles_user_unique');
        });
    }
};

