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
        if (Schema::hasTable('investment_unlock_access')) {
            return;
        }
        
        Schema::create('investment_unlock_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('unlocked_at');
            $table->enum('unlocked_via', ['request_auto', 'request_approved', 'manual'])->default('manual');
            $table->foreignId('request_id')->nullable()->constrained('investment_unlock_requests')->onDelete('set null');
            $table->timestamps();

            $table->unique(['investment_id', 'user_id']);
            $table->index('investment_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_unlock_access');
    }
};
