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
        Schema::create('medicine_intake_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained('family_members')->onDelete('set null');
            
            $table->time('reminder_time');
            $table->enum('frequency', ['daily', 'weekly', 'custom'])->default('daily');
            $table->json('days_of_week')->nullable(); // ['monday', 'tuesday', ...] for weekly
            $table->json('selected_dates')->nullable(); // ['2025-12-25', '2025-12-30', ...] for custom
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('medicine_id');
            $table->index('family_member_id');
            $table->index('status');
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_intake_reminders');
    }
};
