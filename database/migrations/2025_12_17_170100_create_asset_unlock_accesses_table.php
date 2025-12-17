<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_unlock_accesses')) {
            return;
        }

        Schema::create('asset_unlock_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('unlocked_at')->nullable();
            $table->string('unlocked_via')->nullable(); // manual, request_auto, request_approved
            $table->foreignId('request_id')->nullable()->constrained('asset_unlock_requests')->onDelete('set null');
            $table->timestamps();

            $table->index(['asset_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_unlock_accesses');
    }
};
