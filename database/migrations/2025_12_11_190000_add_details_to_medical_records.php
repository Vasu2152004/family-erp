<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->string('primary_condition')->nullable()->after('doctor_name');
            $table->enum('severity', ['mild', 'moderate', 'severe', 'critical'])->nullable()->after('primary_condition');
            $table->text('symptoms')->nullable()->after('severity');
            $table->text('diagnosis')->nullable()->after('symptoms');
            $table->text('treatment_plan')->nullable()->after('diagnosis');
            $table->date('follow_up_at')->nullable()->after('treatment_plan');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn([
                'primary_condition',
                'severity',
                'symptoms',
                'diagnosis',
                'treatment_plan',
                'follow_up_at',
            ]);
        });
    }
};

