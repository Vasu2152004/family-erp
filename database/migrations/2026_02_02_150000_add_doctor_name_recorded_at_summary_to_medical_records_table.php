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
        Schema::table('medical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_records', 'doctor_name')) {
                $table->string('doctor_name')->nullable()->after('category');
            }
            if (!Schema::hasColumn('medical_records', 'recorded_at')) {
                $table->date('recorded_at')->nullable()->after('follow_up_at');
            }
            if (!Schema::hasColumn('medical_records', 'summary')) {
                $table->text('summary')->nullable()->after('recorded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            if (Schema::hasColumn('medical_records', 'doctor_name')) {
                $table->dropColumn('doctor_name');
            }
            if (Schema::hasColumn('medical_records', 'recorded_at')) {
                $table->dropColumn('recorded_at');
            }
            if (Schema::hasColumn('medical_records', 'summary')) {
                $table->dropColumn('summary');
            }
        });
    }
};
