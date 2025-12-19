<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum column to ensure it has the correct values
        // This fixes any mismatch between the migration definition and actual database schema
        DB::statement("ALTER TABLE investments MODIFY COLUMN interest_period ENUM('YEARLY', 'MONTHLY', 'QUARTERLY') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous state if needed
        // Note: This might fail if the column didn't exist before
        // In that case, you may need to manually adjust this
        DB::statement("ALTER TABLE investments MODIFY COLUMN interest_period ENUM('YEARLY', 'MONTHLY', 'QUARTERLY') NULL");
    }
};
