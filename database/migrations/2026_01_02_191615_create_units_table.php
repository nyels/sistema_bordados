<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de unidades de medida para el sistema de inventario.
     * Soporta unidades base (metro, pieza) y unidades de compra (rollo, cono, caja).
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            // Llave primaria
            $table->id();

            // UUID para exposición externa (APIs, URLs públicas)
            $table->uuid('uuid')->unique();

            // Datos principales
            $table->string('name', 50);                    // "Metro", "Rollo", "Cono"
            $table->string('slug', 50)->unique();          // "metro", "rollo", "cono"
            $table->string('symbol', 10);                  // "m", "pz", "cono"

            // Clasificación
            $table->boolean('is_base')->default(false);    // true = unidad de consumo (metro, pieza)
            $table->boolean('activo')->default(true);   // Soft toggle sin eliminar


            // Auditoría
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index('activo');
            $table->index('is_base');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
