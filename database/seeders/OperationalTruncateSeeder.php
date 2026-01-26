<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * =============================================================================
 * LIMPIEZA OPERATIVA POR NIVELES
 * =============================================================================
 *
 * Trunca tablas transaccionales SIN tocar catálogos.
 *
 * NIVEL 3 (Primero): Tablas dependientes más profundas
 * NIVEL 2: Tablas intermedias
 * NIVEL 1: Tablas principales transaccionales
 *
 * CATÁLOGOS INTOCABLES:
 * - estados, giros, recomendacion
 * - units, material_categories, product_types
 * - application_types, categories, product_categories
 * - attributes, attribute_values
 * - system_settings
 */
class OperationalTruncateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   LIMPIEZA OPERATIVA (TRUNCATE POR NIVELES)');
        $this->command->info('══════════════════════════════════════════════════');

        // Deshabilitar FK checks temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // =============================================
        // NIVEL 3: Tablas más profundas (hijas de hijas)
        // =============================================
        $this->command->info('');
        $this->command->info('🔴 NIVEL 3: Tablas dependientes profundas...');

        $nivel3 = [
            'order_item_design_exports',
            'order_item_adjustments',
            'order_messages',
            'order_events',
            'order_payments',
            'inventory_reservations',
            'purchase_reception_items',
            'client_measurement_history',
            'product_variant_attribute',
            'product_design',
            'product_extra_assignment',
            'product_extra_materials',
        ];

        foreach ($nivel3 as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("   ✓ {$table}");
            }
        }

        // =============================================
        // NIVEL 2: Tablas intermedias
        // =============================================
        $this->command->info('');
        $this->command->info('🟠 NIVEL 2: Tablas intermedias...');

        $nivel2 = [
            'order_items',
            'purchase_receptions',
            'purchase_items',
            'inventory_movements',
            'product_materials',
            'product_variants',
            'material_unit_conversions',
            'material_variants',
            'design_exports',
            'design_variants',
            'client_measurements',
            'images',
        ];

        foreach ($nivel2 as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("   ✓ {$table}");
            }
        }

        // =============================================
        // NIVEL 1: Tablas principales transaccionales
        // =============================================
        $this->command->info('');
        $this->command->info('🟡 NIVEL 1: Tablas principales...');

        $nivel1 = [
            'orders',
            'purchases',
            'clientes',
            'products',
            'product_extras',
            'materials',
            'designs',
            'proveedors',
        ];

        foreach ($nivel1 as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("   ✓ {$table}");
            }
        }

        // Rehabilitar FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   LIMPIEZA COMPLETADA');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   Catálogos preservados:');
        $this->command->info('   • estados, giros, recomendacion');
        $this->command->info('   • units, material_categories, product_types');
        $this->command->info('   • application_types, attributes');
        $this->command->info('');
    }
}
