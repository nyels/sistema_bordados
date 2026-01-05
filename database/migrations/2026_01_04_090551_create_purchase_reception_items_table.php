<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_reception_id')->constrained('purchase_receptions')->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->foreignId('material_variant_id')->constrained('material_variants')->restrictOnDelete();
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('converted_quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_reception_id');
            $table->index('purchase_item_id');
            $table->index('material_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_reception_items');
    }
};
