<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de índice de búsqueda denormalizado.
     * Permite búsquedas rápidas con texto normalizado y stemmed.
     */
    public function up(): void
    {
        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            
            // Referencia polimórfica al modelo indexado
            $table->string('searchable_type', 100); // 'Design', 'Category', etc.
            $table->unsignedBigInteger('searchable_id');
            
            // Texto original (para mostrar en resultados)
            $table->string('original_title', 500);
            $table->text('original_content')->nullable();
            
            // Texto normalizado para búsqueda
            // (lowercase, sin acentos, sin caracteres especiales)
            $table->string('normalized_title', 500);
            $table->text('normalized_content')->nullable();
            
            // Tokens stemmed (raíces de palabras)
            // Ejemplo: "perros dorados" → "perr dorad"
            $table->text('stemmed_tokens');
            
            // Metadata para filtros y ordenamiento
            $table->json('metadata')->nullable(); // categorías, tags, etc.
            $table->boolean('is_active')->default(true);
            $table->float('boost', 3, 2)->default(1.00); // Factor de relevancia
            
            // Auditoría
            $table->timestamps();
            
            // Índices compuestos para queries eficientes
            $table->unique(['searchable_type', 'searchable_id'], 'search_idx_unique_model');
            $table->index(['searchable_type', 'is_active'], 'search_idx_type_active');
            $table->index('is_active', 'search_idx_active');
            $table->index('updated_at', 'search_idx_updated');
        });

        // Crear índice FULLTEXT para búsqueda de texto
        // MySQL 5.7+ / MariaDB 10.0.5+ soporta FULLTEXT en InnoDB
        DB::statement('ALTER TABLE search_index ADD FULLTEXT search_ft_normalized (normalized_title, normalized_content)');
        DB::statement('ALTER TABLE search_index ADD FULLTEXT search_ft_stemmed (stemmed_tokens)');
        DB::statement('ALTER TABLE search_index ADD FULLTEXT search_ft_title (normalized_title)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_index');
    }
};
