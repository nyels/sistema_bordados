<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgency_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->unsignedTinyInteger('time_percentage')->comment('Porcentaje de tiempo (ej: 100 = normal, 50 = mitad del tiempo)');
            $table->decimal('price_multiplier', 5, 2)->default(1.00)->comment('Multiplicador de precio (ej: 1.5 = 50% más caro)');
            $table->string('color', 20)->default('#6c757d')->comment('Color para badges/UI');
            $table->string('icon', 50)->nullable()->comment('Icono FontAwesome');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar niveles por defecto
        DB::table('urgency_levels')->insert([
            [
                'name' => 'Normal',
                'slug' => 'normal',
                'time_percentage' => 100,
                'price_multiplier' => 1.00,
                'color' => '#28a745',
                'icon' => 'fa-clock',
                'description' => 'Tiempo estándar de producción',
                'sort_order' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Urgente',
                'slug' => 'urgente',
                'time_percentage' => 50,
                'price_multiplier' => 1.25,
                'color' => '#ffc107',
                'icon' => 'fa-exclamation-triangle',
                'description' => 'Mitad del tiempo normal de producción',
                'sort_order' => 2,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Express (24h)',
                'slug' => 'express',
                'time_percentage' => 25,
                'price_multiplier' => 1.50,
                'color' => '#dc3545',
                'icon' => 'fa-bolt',
                'description' => 'Entrega en 24 horas',
                'sort_order' => 3,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('urgency_levels');
    }
};
