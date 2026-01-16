<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * =============================================================================
 * SEEDER DE DESARROLLO / DEMO - NO EJECUTAR EN PRODUCCIÓN
 * =============================================================================
 *
 * Este seeder establece relaciones predefinidas entre categorías de materiales
 * y sus unidades de compra permitidas.
 *
 * PROPÓSITO:
 * - Configuración inicial para entorno de desarrollo
 * - Datos de demostración para pruebas
 * - Referencia para configuración manual en producción
 *
 * EN PRODUCCIÓN:
 * - Las relaciones Categoría ↔ Unidad deben configurarse vía UI administrativa
 * - Cada empresa/tenant puede tener reglas diferentes
 * - Este seeder NO debe ejecutarse automáticamente
 *
 * NOTA: Este seeder NO está incluido en DatabaseSeeder.php intencionalmente.
 * Para ejecutar manualmente: php artisan db:seed --class=CategoryUnitSeeder
 *
 * @see \App\Models\MaterialCategory::allowedUnits()
 * @see \App\Http\Requests\MaterialRequest (validación cruzada)
 */
class CategoryUnitSeeder extends Seeder
{
    /**
     * Establece relaciones de ejemplo entre categorías y unidades permitidas.
     *
     * Reglas de negocio aplicadas:
     * - Solo se asignan unidades de compra/empaque (is_base = false)
     * - Cada categoría tiene unidades específicas según su naturaleza
     */
    public function run(): void
    {
        // 1. Obtener categorías
        $hilo = \App\Models\MaterialCategory::where('name', 'HILO')->first();
        $telas = \App\Models\MaterialCategory::where('name', 'TELAS')->first();
        $sustrato = \App\Models\MaterialCategory::where('name', 'SUSTRATO TEXTIL')->first();
        $decoracion = \App\Models\MaterialCategory::where('name', 'DECORACIÓN')->first();

        // 2. Obtener unidades (Solo base/compra)
        $cono = \App\Models\Unit::where('name', 'CONO')->first();
        $rollo = \App\Models\Unit::where('name', 'ROLLO')->first();
        $pieza = \App\Models\Unit::where('name', 'PIEZA')->first();
        $carrete = \App\Models\Unit::where('name', 'CARRETE')->first();

        // Asignación explícita
        // Si no existen las unidades o categorías, crear al menos las necesarias para la prueba.

        // HILO -> CONO (y Carrete si existe)
        if ($hilo && $cono) {
            $hilo->allowedUnits()->syncWithoutDetaching([$cono->id]);
        }
        if ($hilo && $carrete) {
            $hilo->allowedUnits()->syncWithoutDetaching([$carrete->id]);
        }

        // TELAS -> ROLLO
        if ($telas && $rollo) {
            $telas->allowedUnits()->syncWithoutDetaching([$rollo->id]);
        }

        // SUSTRATO -> PIEZA
        if ($sustrato && $pieza) {
            $sustrato->allowedUnits()->syncWithoutDetaching([$pieza->id]);
        }

        // DECORACIÓN -> ROLLO, PIEZA
        if ($decoracion) {
            if ($rollo) $decoracion->allowedUnits()->syncWithoutDetaching([$rollo->id]);
            if ($pieza) $decoracion->allowedUnits()->syncWithoutDetaching([$pieza->id]);
        }
    }
}
