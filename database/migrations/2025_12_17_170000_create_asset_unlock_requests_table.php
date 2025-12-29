<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_unlock_requests')) {
            return;
        }

        Schema::create('asset_unlock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('request_count')->default(1);
            $table->timestamp('last_requested_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_unlocked'])->default('pending');
            $table->timestamps();

            $table->index(['family_id', 'asset_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_unlock_requests');
    }
};








