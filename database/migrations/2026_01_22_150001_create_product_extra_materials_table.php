<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: Tabla de materiales para extras de producto
 *
 * PROPÓSITO:
 * Relacionar extras de producto con variantes de material del inventario.
 * Solo aplica cuando product_extras.consumes_inventory = true.
 *
 * MODELO:
 * Un Extra puede consumir múltiples materiales.
 * Cada material tiene una cantidad requerida por unidad del extra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_extra_materials', function (Blueprint $table) {
            $table->id();

            // Relación con el extra
            $table->foreignId('product_extra_id')
                ->constrained('product_extras')
                ->onDelete('cascade')
                ->comment('Extra que consume este material');

            // Relación con variante de material (inventario)
            $table->foreignId('material_variant_id')
                ->constrained('material_variants')
                ->onDelete('restrict')
                ->comment('Variante de material consumida');

            // Cantidad requerida por unidad del extra
            $table->decimal('quantity_required', 15, 4)
                ->comment('Cantidad de material requerida por cada unidad del extra');

            $table->timestamps();

            // Índice único: un extra no puede tener el mismo material duplicado
            $table->unique(['product_extra_id', 'material_variant_id'], 'pem_extra_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_extra_materials');
    }
};
