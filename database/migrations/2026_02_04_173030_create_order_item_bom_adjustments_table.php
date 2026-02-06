<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes de BOM por item del pedido.
     * Permite modificar cantidades de materiales según medidas del cliente.
     */
    public function up(): void
    {
        Schema::create('order_item_bom_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('material_variant_id')->constrained('material_variants')->cascadeOnDelete();
            $table->decimal('base_quantity', 10, 4)->comment('Cantidad original del BOM');
            $table->decimal('adjusted_quantity', 10, 4)->comment('Cantidad ajustada');
            $table->decimal('unit_cost', 10, 4)->nullable()->comment('Costo unitario al momento del ajuste');
            $table->text('notes')->nullable()->comment('Notas del ajuste');
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un material solo puede tener un ajuste por item
            $table->unique(['order_item_id', 'material_variant_id'], 'unique_item_material_adjustment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_bom_adjustments');
    }
};
