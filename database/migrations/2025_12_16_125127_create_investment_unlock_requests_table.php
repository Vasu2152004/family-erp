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
        if (Schema::hasTable('investment_unlock_requests')) {
            return;
        }
        
        Schema::create('investment_unlock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->integer('request_count')->default(1);
            $table->timestamp('last_requested_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'auto_unlocked', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['investment_id', 'requested_by']);
            $table->index(['tenant_id', 'family_id']);
            $table->index('investment_id');
            $table->index('requested_by');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_unlock_requests');
    }
};
