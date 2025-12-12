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
        Schema::table('doctor_visits', function (Blueprint $table) {
            // Add visit_time if it doesn't exist
            if (!Schema::hasColumn('doctor_visits', 'visit_time')) {
                $table->time('visit_time')->nullable()->after('visit_date');
            }
            
            // Add visit_type if it doesn't exist
            if (!Schema::hasColumn('doctor_visits', 'visit_type')) {
                $table->enum('visit_type', ['consultation', 'follow_up', 'emergency', 'routine_checkup', 'surgery', 'other'])->default('consultation')->after('specialization');
            }
            
            // Add chief_complaint if it doesn't exist (rename from reason if exists)
            if (Schema::hasColumn('doctor_visits', 'reason') && !Schema::hasColumn('doctor_visits', 'chief_complaint')) {
                $table->renameColumn('reason', 'chief_complaint');
            } elseif (!Schema::hasColumn('doctor_visits', 'chief_complaint')) {
                $table->text('chief_complaint')->nullable()->after('visit_type');
            }
            
            // Add examination_findings if it doesn't exist
            if (!Schema::hasColumn('doctor_visits', 'examination_findings')) {
                $table->text('examination_findings')->nullable()->after('chief_complaint');
            }
            
            // Add treatment_given if it doesn't exist
            if (!Schema::hasColumn('doctor_visits', 'treatment_given')) {
                $table->text('treatment_given')->nullable()->after('diagnosis');
            }
            
            // Rename clinic to clinic_name if needed
            if (Schema::hasColumn('doctor_visits', 'clinic') && !Schema::hasColumn('doctor_visits', 'clinic_name')) {
                $table->renameColumn('clinic', 'clinic_name');
            } elseif (!Schema::hasColumn('doctor_visits', 'clinic_name')) {
                $table->string('clinic_name')->nullable()->after('doctor_name');
            }
            
            // Add next_visit_date if it doesn't exist (might already exist as follow_up_at)
            if (!Schema::hasColumn('doctor_visits', 'next_visit_date') && !Schema::hasColumn('doctor_visits', 'follow_up_at')) {
                $table->date('next_visit_date')->nullable()->after('notes');
            } elseif (Schema::hasColumn('doctor_visits', 'follow_up_at') && !Schema::hasColumn('doctor_visits', 'next_visit_date')) {
                // Copy follow_up_at to next_visit_date if needed, or just use follow_up_at
                $table->date('next_visit_date')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_visits', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_visits', 'visit_time')) {
                $table->dropColumn('visit_time');
            }
            if (Schema::hasColumn('doctor_visits', 'visit_type')) {
                $table->dropColumn('visit_type');
            }
            if (Schema::hasColumn('doctor_visits', 'examination_findings')) {
                $table->dropColumn('examination_findings');
            }
            if (Schema::hasColumn('doctor_visits', 'treatment_given')) {
                $table->dropColumn('treatment_given');
            }
            if (Schema::hasColumn('doctor_visits', 'next_visit_date')) {
                $table->dropColumn('next_visit_date');
            }
        });
    }
};
