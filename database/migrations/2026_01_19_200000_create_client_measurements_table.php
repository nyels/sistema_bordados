<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === HISTORIAL DE MEDIDAS POR CLIENTE ===
        // Permite múltiples registros de medidas en el tiempo
        Schema::create('client_measurements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Medidas en centímetros (decimal para precisión)
            $table->decimal('busto', 6, 2)->nullable();
            $table->decimal('cintura', 6, 2)->nullable();
            $table->decimal('cadera', 6, 2)->nullable();
            $table->decimal('alto_cintura', 6, 2)->nullable();
            $table->decimal('largo', 6, 2)->nullable();

            // Medidas adicionales opcionales
            $table->decimal('hombro', 6, 2)->nullable();
            $table->decimal('espalda', 6, 2)->nullable();
            $table->decimal('largo_manga', 6, 2)->nullable();

            // Metadata
            $table->string('label', 50)->nullable(); // "Medidas Boda 2024", "Talla actual"
            $table->boolean('is_primary')->default(false); // Medidas principales/actuales
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Índice para búsqueda rápida
            $table->index(['cliente_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_measurements');
    }
};
