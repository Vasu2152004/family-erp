<?php

declare(strict_types=1);

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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->onDelete('set null');
            
            // Basic info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('batch_number')->nullable();
            
            // Stock management
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50)->default('units');
            $table->decimal('min_stock_level', 10, 2)->nullable();
            
            // Expiry and purchase
            $table->date('expiry_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            
            // Prescription file (reusing health module pattern)
            $table->string('prescription_file_path')->nullable();
            $table->string('prescription_original_name')->nullable();
            $table->string('prescription_mime_type', 120)->nullable();
            $table->unsignedBigInteger('prescription_file_size')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['tenant_id', 'family_id']);
            $table->index('family_member_id');
            $table->index('prescription_id');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
