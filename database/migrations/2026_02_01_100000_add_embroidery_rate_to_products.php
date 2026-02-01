<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campo para guardar el precio por millar de puntadas definido al crear el producto.
 * Este valor es específico por producto, no global del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('embroidery_rate_per_thousand', 10, 4)
                  ->nullable()
                  ->after('embroidery_cost')
                  ->comment('Precio por millar de puntadas definido al crear el producto');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('embroidery_rate_per_thousand');
        });
    }
};
