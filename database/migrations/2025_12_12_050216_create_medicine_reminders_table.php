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
        if (Schema::hasTable('medicine_reminders')) {
            return;
        }

        Schema::create('medicine_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->time('reminder_time');
            $table->json('days_of_week')->nullable(); // ['monday', 'tuesday', ...] or null for daily
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('family_id');
            $table->index('family_member_id');
            $table->index('prescription_id');
            $table->index('is_active');
            $table->index('next_run_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('medicine_reminders')) {
            return;
        }

        Schema::dropIfExists('medicine_reminders');
    }
};
