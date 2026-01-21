<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega FK product_type_id a la tabla products.
     *
     * IMPORTANTE: Esta columna es NULLABLE temporalmente para permitir
     * migración gradual de productos existentes. Una vez que todos los
     * productos tengan un tipo asignado, considerar hacerla NOT NULL.
     *
     * La validación de negocio en StoreOrderRequest RECHAZARÁ pedidos
     * con productos sin tipo asignado (NULL).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // FK nullable para migración gradual
            $table->foreignId('product_type_id')
                ->nullable()
                ->after('product_category_id')
                ->constrained('product_types')
                ->nullOnDelete(); // Si se elimina el tipo, el producto queda sin tipo

            // Índice para queries de reportes
            $table->index('product_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropColumn('product_type_id');
        });
    }
};
