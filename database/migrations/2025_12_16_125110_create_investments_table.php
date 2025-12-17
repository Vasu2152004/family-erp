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
        if (Schema::hasTable('investments')) {
            return;
        }
        
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_member_id')->nullable()->constrained('family_members')->onDelete('set null');
            $table->enum('investment_type', ['FD', 'RD', 'SIP', 'MUTUAL_FUND', 'STOCK', 'CRYPTO', 'OTHER'])->default('OTHER');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->string('pin_hash')->nullable();
            $table->text('encrypted_details')->nullable();
            $table->text('details')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('family_id');
            $table->index('family_member_id');
            $table->index('is_hidden');
            $table->index(['tenant_id', 'family_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
