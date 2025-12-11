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
        Schema::create('medicine_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->enum('frequency', ['once', 'daily', 'weekly'])->default('daily');
            $table->time('reminder_time')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('days_of_week')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('family_id');
            $table->index('family_member_id');
            $table->index('prescription_id');
            $table->index('frequency');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_reminders');
    }
};

