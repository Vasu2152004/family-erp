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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('instructions');
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('mime_type', 120)->nullable()->after('original_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_name', 'mime_type', 'file_size']);
        });
    }
};

