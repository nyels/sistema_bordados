<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('telefono');
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->string('observaciones')->nullable();
            $table->string('busto')->nullable();
            $table->string('alto_cintura')->nullable();
            $table->string('cintura')->nullable();
            $table->string('cadera')->nullable();
            $table->string('largo')->nullable();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->foreignId('recomendacion_id')->constrained('recomendacion')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('clientes');
        Schema::enableForeignKeyConstraints();
    }
};
