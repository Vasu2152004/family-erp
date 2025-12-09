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
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->onDelete('set null');
            $table->string('name');
            $table->decimal('qty', 10, 2)->default(1);
            $table->enum('unit', ['piece', 'kg', 'liter', 'gram', 'ml', 'pack', 'box', 'bottle', 'other'])->default('piece');
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('purchased_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('purchased_at')->nullable();
            $table->boolean('is_purchased')->default(false);
            $table->boolean('is_auto_added')->default(false);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('family_id');
            $table->index('inventory_item_id');
            $table->index('is_purchased');
            $table->index('added_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
