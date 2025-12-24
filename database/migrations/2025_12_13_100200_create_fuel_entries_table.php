<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->date('fill_date');
            $table->unsignedInteger('odometer_reading');
            $table->decimal('fuel_amount', 8, 2);
            $table->decimal('cost', 10, 2);
            $table->enum('fuel_type', ['petrol', 'diesel'])->default('petrol');
            $table->string('fuel_station_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('calculated_mileage', 8, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('vehicle_id');
            $table->index('fill_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_entries');
    }
};










