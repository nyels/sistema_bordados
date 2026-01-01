<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para thumbnails optimizados manteniendo la imagen original.
     */
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // Thumbnail pequeño para listados/grids (150px)
            $table->string('thumbnail_small')->nullable()->after('file_path');
            // Thumbnail mediano para galerías/modales (400px)
            $table->string('thumbnail_medium')->nullable()->after('thumbnail_small');
            // Indicador de si la imagen ha sido optimizada
            $table->boolean('is_optimized')->default(false)->after('thumbnail_medium');
            // Tamaño original del archivo (para referencia)
            $table->unsignedBigInteger('original_size')->nullable()->after('is_optimized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_small', 'thumbnail_medium', 'is_optimized', 'original_size']);
        });
    }
};
