<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campo image_id para vincular producción con imagen específica.
     */
    public function up(): void
    {
        Schema::table('design_exports', function (Blueprint $table) {
            // Campo para vincular producción a una imagen específica
            // Nullable porque puede ser producción general sin imagen específica
            $table->foreignId('image_id')
                ->nullable()
                ->after('design_variant_id')
                ->constrained('images')
                ->nullOnDelete();

            // Índice para búsquedas por imagen
            $table->index('image_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('design_exports', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropIndex(['image_id']);
            $table->dropColumn('image_id');
        });
    }
};
