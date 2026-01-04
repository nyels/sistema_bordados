<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('material_variant_id')->constrained('material_variants')->restrictOnDelete();
            $table->string('type', 30);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->decimal('stock_before', 12, 4);
            $table->decimal('stock_after', 12, 4);
            $table->decimal('average_cost_before', 14, 4);
            $table->decimal('average_cost_after', 14, 4);
            $table->decimal('value_before', 14, 4);
            $table->decimal('value_after', 14, 4);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('material_variant_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
