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
        Schema::create('family_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['OWNER', 'ADMIN', 'MEMBER', 'VIEWER']);
            $table->boolean('is_backup_admin')->default(false);
            $table->timestamps();

            $table->unique(['family_id', 'user_id']);
            $table->index(['tenant_id', 'family_id']);
            $table->index('user_id');
            $table->index('role');
            $table->index('is_backup_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_user_roles');
    }
};
