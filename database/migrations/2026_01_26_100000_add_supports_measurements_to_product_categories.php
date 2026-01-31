<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: supports_measurements en product_categories
 *
 * CONTEXTO CANÓNICO:
 * - Medidas ≠ Personalización
 * - Medidas solo aplican a ALGUNAS categorías (ej: vestidos, faldas)
 * - Esta columna permite que el PEDIDO decida si usa medidas
 * - NO es un default automático, es una habilitación
 *
 * REGLA:
 * - Si supports_measurements = false → checkbox de medidas OCULTO en pedido
 * - Si supports_measurements = true → checkbox de medidas VISIBLE en pedido
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('supports_measurements')
                ->default(false)
                ->after('is_active')
                ->comment('Indica si los productos de esta categoría pueden requerir medidas en pedidos');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('supports_measurements');
        });
    }
};
