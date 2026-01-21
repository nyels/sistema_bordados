<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalization_time_multipliers', function (Blueprint $table) {
            $table->id();

            // Tipo de personalización
            $table->string('type', 50)->unique();

            // Multiplicador (1.0 = sin cambio, 1.5 = 50% más tiempo)
            $table->decimal('multiplier', 4, 2)->default(1.00);

            // Descripción para UI admin
            $table->string('description')->nullable();

            // Orden de prioridad (mayor = más prioritario en cálculo)
            $table->unsignedTinyInteger('priority')->default(0);

            // Activo
            $table->boolean('is_active')->default(true);

            // Auditoría
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });

        // Insertar valores por defecto
        DB::table('personalization_time_multipliers')->insert([
            [
                'type' => 'none',
                'multiplier' => 1.00,
                'description' => 'Sin personalización',
                'priority' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'text',
                'multiplier' => 1.20,
                'description' => 'Solo texto bordado',
                'priority' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'measurements',
                'multiplier' => 1.50,
                'description' => 'Medidas personalizadas',
                'priority' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'design',
                'multiplier' => 2.00,
                'description' => 'Diseño personalizado completo',
                'priority' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('personalization_time_multipliers');
    }
};
