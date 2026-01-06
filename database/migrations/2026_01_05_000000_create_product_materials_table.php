<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            // restrict evita que borren un hilo si ya está en una receta
            $table->foreignId('material_variant_id')->constrained('material_variants')->onDelete('restrict');

            // CANTIDAD: Lo que consume 1 unidad de producto
            $table->decimal('quantity', 12, 4);

            // SNAPSHOT FINANCIERO: Guardamos el costo promedio al momento de crear la ficha
            // Esto permite saber cuánto costaba producir el producto hoy, aunque mañana suba el hilo.
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->storedAs('quantity * unit_cost');

            $table->boolean('is_primary')->default(false);
            $table->string('notes')->nullable();

            $table->softDeletes(); // Implementación de SoftDeletes
            $table->timestamps();

            // Índices para velocidad de reportes
            $table->index(['product_id', 'is_primary']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_materials');
    }
};
