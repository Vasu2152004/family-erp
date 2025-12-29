<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->unsignedInteger('odometer_reading');
            $table->decimal('cost', 10, 2);
            $table->string('service_center_name')->nullable();
            $table->string('service_center_contact')->nullable();
            $table->enum('service_type', ['regular_service', 'major_service', 'repair', 'other'])->default('regular_service');
            $table->text('description')->nullable();
            $table->date('next_service_due_date')->nullable();
            $table->unsignedInteger('next_service_odometer')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('vehicle_id');
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};













