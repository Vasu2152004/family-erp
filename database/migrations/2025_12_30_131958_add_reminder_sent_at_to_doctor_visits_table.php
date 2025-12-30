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
        if (!Schema::hasTable('doctor_visits')) {
            return;
        }

        if (Schema::hasColumn('doctor_visits', 'reminder_sent_at')) {
            return;
        }

        Schema::table('doctor_visits', function (Blueprint $table) {
            $table->dateTime('reminder_sent_at')->nullable()->after('next_visit_date');
            $table->index(['family_id', 'reminder_sent_at']);
            $table->index('next_visit_date');
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

        if (Schema::hasColumn('doctor_visits', 'reminder_sent_at')) {
            Schema::table('doctor_visits', function (Blueprint $table) {
                $table->dropIndex(['family_id', 'reminder_sent_at']);
                $table->dropIndex(['next_visit_date']);
                $table->dropColumn('reminder_sent_at');
            });
        }
    }
};
