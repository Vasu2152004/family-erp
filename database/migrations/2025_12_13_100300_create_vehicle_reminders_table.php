<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->enum('reminder_type', ['rc_expiry', 'insurance_expiry', 'puc_expiry']);
            $table->date('remind_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('vehicle_id');
            $table->index('remind_at');
            $table->unique(['vehicle_id', 'reminder_type', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_reminders');
    }
};










