<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('material_variant_id')->constrained('material_variants')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('conversion_factor', 12, 4)->default(1);
            $table->decimal('converted_quantity', 12, 4);
            $table->decimal('converted_unit_cost', 14, 4);
            $table->decimal('subtotal', 14, 4);
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->decimal('converted_quantity_received', 12, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('material_variant_id');
            $table->unique(['purchase_id', 'material_variant_id', 'unit_id'], 'purchase_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
