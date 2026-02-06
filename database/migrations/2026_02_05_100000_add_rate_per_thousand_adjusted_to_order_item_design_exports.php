<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campo para guardar el precio por millar ajustado en pre-producción.
 *
 * REGLA DE NEGOCIO:
 * - El precio por millar puede ajustarse por diseño durante la etapa CONFIRMED
 * - Si rate_per_thousand_adjusted es NULL, se usa el valor del producto
 * - Al pasar a producción, calculateEmbroideryCost() usa este valor para el snapshot
 * - Si se elimina el diseño, el ajuste se pierde (comportamiento correcto por diseño)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_item_design_exports', function (Blueprint $table) {
            $table->decimal('rate_per_thousand_adjusted', 10, 4)
                ->nullable()
                ->after('sort_order')
                ->comment('Precio por millar ajustado en pre-producción (NULL = usar valor del producto)');
        });
    }

    public function down(): void
    {
        Schema::table('order_item_design_exports', function (Blueprint $table) {
            $table->dropColumn('rate_per_thousand_adjusted');
        });
    }
};
