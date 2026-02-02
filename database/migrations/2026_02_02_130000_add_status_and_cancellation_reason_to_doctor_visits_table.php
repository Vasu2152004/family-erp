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
            if (!Schema::hasColumn('doctor_visits', 'status')) {
                $table->string('status', 20)->default('scheduled')->after('visit_time');
            }
            if (!Schema::hasColumn('doctor_visits', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_visits', function (Blueprint $table) {
            if (Schema::hasColumn('doctor_visits', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
            if (Schema::hasColumn('doctor_visits', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
