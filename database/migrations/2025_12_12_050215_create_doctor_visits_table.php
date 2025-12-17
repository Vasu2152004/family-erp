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
        if (Schema::hasTable('doctor_visits')) {
            return;
        }

        Schema::create('doctor_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->onDelete('set null');
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->string('doctor_name');
            $table->string('clinic_name')->nullable();
            $table->string('specialization')->nullable();
            $table->enum('visit_type', ['consultation', 'follow_up', 'emergency', 'routine_checkup', 'surgery', 'other'])->default('consultation');
            $table->text('chief_complaint')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_given')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('family_id');
            $table->index('family_member_id');
            $table->index('medical_record_id');
            $table->index('visit_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('doctor_visits')) {
            return;
        }

        Schema::dropIfExists('doctor_visits');
    }
};
