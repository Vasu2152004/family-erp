<?php

declare(strict_types=1);

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
        Schema::create('medicine_expiry_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->date('remind_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('medicine_id');
            $table->index('remind_at');
            $table->unique(['medicine_id', 'remind_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_expiry_reminders');
    }
};
