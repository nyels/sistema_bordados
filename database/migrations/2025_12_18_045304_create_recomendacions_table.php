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
        Schema::create('recomendacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_recomendacion');
            $table->string('descripcion_recomendacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recomendacion');
    }
};
