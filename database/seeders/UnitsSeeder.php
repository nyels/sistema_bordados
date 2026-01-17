<?php

namespace Database\Seeders;

use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * =============================================================================
 * SEEDER EMPRESARIAL: UNIDADES DE MEDIDA
 * =============================================================================
 *
 * Arquitectura de Unidades:
 *
 * CANONICAL (Consumo):
 *   - Unidad en la que el material se GASTA durante producción
 *   - Ejemplos: METRO, PIEZA, LITRO, MINUTO, GRAMO
 *   - NO tiene compatible_base_unit_id
 *
 * LOGISTIC (Compra):
 *   - Unidad en la que el material se COMPRA del proveedor
 *   - Ejemplos: CONO, ROLLO, CAJA, PAQUETE, UNIDAD
 *   - Tiene compatible_base_unit_id → apunta a su CANONICAL equivalente
 *   - Es la ÚNICA permitida como base_unit de Material
 *
 * METRIC_PACK (Presentación):
 *   - Presentación con cantidad fija predefinida
 *   - Ejemplos: ROLLO 50M, CAJA 100PZ, GALÓN 4L
 *   - Tiene compatible_base_unit_id → apunta a su CANONICAL
 *   - Tiene default_conversion_factor → cantidad fija
 *
 * @see \App\Enums\UnitType
 * @see \App\Models\Unit
 */
class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO UNIDADES DE MEDIDA (ARQUITECTURA ERP)');
        $this->command->info('══════════════════════════════════════════════════');

        // =============================================
        // PASO 1: UNIDADES CANONICAL (CONSUMO)
        // =============================================
        $this->command->info('');
        $this->command->info('📏 Creando unidades CANONICAL (consumo)...');

        $canonicalUnits = [
            ['name' => 'METRO', 'symbol' => 'm', 'description' => 'Unidad de longitud'],
            ['name' => 'PIEZA', 'symbol' => 'pz', 'description' => 'Unidad discreta'],
            ['name' => 'LITRO', 'symbol' => 'l', 'description' => 'Unidad de volumen'],
            ['name' => 'MILILITRO', 'symbol' => 'ml', 'description' => 'Unidad de volumen pequeña'],
            ['name' => 'MINUTO', 'symbol' => 'min', 'description' => 'Unidad de tiempo'],
            ['name' => 'GRAMO', 'symbol' => 'g', 'description' => 'Unidad de peso'],
            ['name' => 'KILOGRAMO', 'symbol' => 'kg', 'description' => 'Unidad de peso'],
        ];

        foreach ($canonicalUnits as $index => $data) {
            Unit::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'is_base' => true,
                    'unit_type' => UnitType::CANONICAL,
                    'compatible_base_unit_id' => null,
                    'default_conversion_factor' => null,
                    'sort_order' => $index + 1,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ {$data['name']} ({$data['symbol']}) - {$data['description']}");
        }

        // Obtener IDs de unidades canonical
        $metroId = Unit::where('slug', 'metro')->value('id');
        $piezaId = Unit::where('slug', 'pieza')->value('id');
        $litroId = Unit::where('slug', 'litro')->value('id');
        $mlId = Unit::where('slug', 'mililitro')->value('id');
        $gramoId = Unit::where('slug', 'gramo')->value('id');
        $kgId = Unit::where('slug', 'kilogramo')->value('id');

        // =============================================
        // PASO 2: UNIDADES LOGISTIC (COMPRA)
        // =============================================
        $this->command->info('');
        $this->command->info('📦 Creando unidades LOGISTIC (compra)...');

        $logisticUnits = [
            // Compatible con METRO (para telas, hilos, cintas)
            ['name' => 'CONO', 'symbol' => 'cono', 'compatible' => $metroId, 'desc' => 'Cono de hilo → METRO'],
            ['name' => 'ROLLO', 'symbol' => 'rollo', 'compatible' => $metroId, 'desc' => 'Rollo de tela → METRO'],

            // Compatible con PIEZA (para botones, agujas, accesorios)
            ['name' => 'CAJA', 'symbol' => 'caja', 'compatible' => $piezaId, 'desc' => 'Caja genérica → PIEZA'],
            ['name' => 'PAQUETE', 'symbol' => 'paq', 'compatible' => $piezaId, 'desc' => 'Paquete → PIEZA'],
            ['name' => 'BOLSA', 'symbol' => 'bolsa', 'compatible' => $piezaId, 'desc' => 'Bolsa → PIEZA'],
            ['name' => 'UNIDAD', 'symbol' => 'und', 'compatible' => $piezaId, 'desc' => 'Unidad individual → PIEZA'],

            // Compatible con LITRO (para tintas, pegamentos)
            ['name' => 'GALÓN', 'symbol' => 'gal', 'compatible' => $litroId, 'desc' => 'Galón → LITRO'],
            ['name' => 'BOTE', 'symbol' => 'bote', 'compatible' => $mlId, 'desc' => 'Bote pequeño → MILILITRO'],

            // Compatible con KG (para resinas, polvos)
            ['name' => 'SACO', 'symbol' => 'saco', 'compatible' => $kgId, 'desc' => 'Saco → KILOGRAMO'],
            ['name' => 'COSTAL', 'symbol' => 'costal', 'compatible' => $kgId, 'desc' => 'Costal → KILOGRAMO'],
        ];

        foreach ($logisticUnits as $index => $data) {
            Unit::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'is_base' => false,
                    'unit_type' => UnitType::LOGISTIC,
                    'compatible_base_unit_id' => $data['compatible'],
                    'default_conversion_factor' => null,
                    'sort_order' => 100 + $index,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ {$data['name']} ({$data['symbol']}) - {$data['desc']}");
        }

        // =============================================
        // PASO 3: UNIDADES METRIC_PACK (PRESENTACIÓN)
        // =============================================
        $this->command->info('');
        $this->command->info('🎁 Creando unidades METRIC_PACK (presentación)...');

        $metricPackUnits = [
            // Presentaciones de METRO
            ['name' => 'ROLLO 25M', 'symbol' => 'r25m', 'compatible' => $metroId, 'factor' => 25],
            ['name' => 'ROLLO 50M', 'symbol' => 'r50m', 'compatible' => $metroId, 'factor' => 50],
            ['name' => 'ROLLO 100M', 'symbol' => 'r100m', 'compatible' => $metroId, 'factor' => 100],

            // Presentaciones de PIEZA
            ['name' => 'CAJA 10PZ', 'symbol' => 'cj10', 'compatible' => $piezaId, 'factor' => 10],
            ['name' => 'CAJA 50PZ', 'symbol' => 'cj50', 'compatible' => $piezaId, 'factor' => 50],
            ['name' => 'CAJA 100PZ', 'symbol' => 'cj100', 'compatible' => $piezaId, 'factor' => 100],
            ['name' => 'BOLSA 25PZ', 'symbol' => 'bl25', 'compatible' => $piezaId, 'factor' => 25],

            // Presentaciones de LITRO
            ['name' => 'GALÓN 4L', 'symbol' => 'gal4l', 'compatible' => $litroId, 'factor' => 4],
            ['name' => 'BOTE 500ML', 'symbol' => 'bt500', 'compatible' => $mlId, 'factor' => 500],
            ['name' => 'BOTE 1L', 'symbol' => 'bt1l', 'compatible' => $litroId, 'factor' => 1],

            // Presentaciones de KG
            ['name' => 'SACO 25KG', 'symbol' => 'sc25', 'compatible' => $kgId, 'factor' => 25],
            ['name' => 'SACO 50KG', 'symbol' => 'sc50', 'compatible' => $kgId, 'factor' => 50],
        ];

        foreach ($metricPackUnits as $index => $data) {
            Unit::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'is_base' => false,
                    'unit_type' => UnitType::METRIC_PACK,
                    'compatible_base_unit_id' => $data['compatible'],
                    'default_conversion_factor' => $data['factor'],
                    'sort_order' => 200 + $index,
                    'activo' => true,
                ]
            );
            $this->command->info("   ✓ {$data['name']} ({$data['symbol']}) = {$data['factor']} unidades");
        }

        // =============================================
        // RESUMEN
        // =============================================
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   RESUMEN DE UNIDADES CREADAS');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   CANONICAL (Consumo):    ' . count($canonicalUnits));
        $this->command->info('   LOGISTIC (Compra):      ' . count($logisticUnits));
        $this->command->info('   METRIC_PACK (Present.): ' . count($metricPackUnits));
        $this->command->info('   ─────────────────────────────────');
        $this->command->info('   TOTAL:                  ' . (count($canonicalUnits) + count($logisticUnits) + count($metricPackUnits)));
        $this->command->info('');
    }
}
