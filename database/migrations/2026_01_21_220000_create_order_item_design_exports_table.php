<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pivot para vincular múltiples DesignExports a cada OrderItem.
 *
 * Reglas de negocio:
 * - Solo items con personalization_type = 'design' o 'text' pueden tener diseños vinculados
 * - Un item puede tener múltiples diseños (ej: logo + nombre)
 * - Solo se pueden vincular DesignExports con status = 'aprobado'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_design_exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->foreignId('design_export_id')
                ->constrained('design_exports')
                ->cascadeOnDelete();

            // Tipo de aplicación en el item (logo, nombre, etc.)
            $table->string('application_type', 50)->nullable();

            // Posición/ubicación en la prenda (pecho, espalda, manga, etc.)
            $table->string('position', 100)->nullable();

            // Notas específicas para este diseño en este item
            $table->text('notes')->nullable();

            // Orden de aplicación (si importa el orden)
            $table->unsignedTinyInteger('sort_order')->default(0);

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Índices
            $table->unique(['order_item_id', 'design_export_id'], 'item_design_unique');
            $table->index('order_item_id');
            $table->index('design_export_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_design_exports');
    }
};
