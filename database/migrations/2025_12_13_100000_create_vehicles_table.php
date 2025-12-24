<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained()->onDelete('set null');
            $table->string('make');
            $table->string('model');
            $table->year('year');
            $table->string('registration_number')->unique();
            $table->date('rc_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('puc_expiry_date')->nullable();
            $table->string('color')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid'])->default('petrol');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('family_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};










