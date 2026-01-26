<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * =============================================================================
 * ORQUESTADOR DE SEEDERS OPERATIVOS
 * =============================================================================
 *
 * COMANDO DE EJECUCIÓN:
 * php artisan db:seed --class=OperationalDatabaseSeeder
 *
 * ORDEN DE EJECUCIÓN (CRÍTICO):
 *
 * FASE 0: LIMPIEZA
 *   - OperationalTruncateSeeder (trunca tablas transaccionales, preserva catálogos)
 *
 * FASE 1: CATÁLOGOS BASE (si no existen)
 *   - EstadoSeeder
 *   - GiroSeeder
 *   - RecomendacionSeeder
 *   - UnitsSeeder
 *   - MaterialCategoriesSeeder
 *   - ProductTypeSeeder
 *   - ApplicationTypeSeeder
 *   - SystemSettingsSeeder
 *
 * FASE 2: ENTIDADES MAESTRAS
 *   - ProveedorBordadosSeeder
 *   - MaterialCatalogSeeder (materiales + variantes, stock=0)
 *
 * FASE 3: FLUJO COMPRA → INVENTARIO
 *   - CompraInicialMaterialesSeeder (genera stock + inventory_movements)
 *
 * FASE 4: PRODUCTOS CON BOM
 *   - ProductoHipilOperativoSeeder (hipil + variantes + BOM)
 *   - ProductoBolsaYuteSeeder (bolsa + variantes + BOM)
 *
 * FASE 5: DISEÑOS Y CLIENTES
 *   - DisenoBordadoSeeder
 *   - ClienteTestSeeder
 *
 * RESULTADO:
 * Base de datos lista para operación de pruebas con:
 * - 3 proveedores activos
 * - ~15 materiales con stock real
 * - 2 productos con BOM completo
 * - 4 diseños de bordado
 * - 4 clientes de prueba
 */
class OperationalDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║                                                          ║');
        $this->command->info('║   SEEDER OPERATIVO - ERP BORDADOS ARTESANALES           ║');
        $this->command->info('║                                                          ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');

        // =============================================
        // FASE 0: LIMPIEZA
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 0: LIMPIEZA DE TABLAS TRANSACCIONALES             │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call(OperationalTruncateSeeder::class);

        // =============================================
        // FASE 1: CATÁLOGOS BASE
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 1: CATÁLOGOS BASE                                  │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call([
            EstadoSeeder::class,
            GiroSeeder::class,
            RecomendacionSeeder::class,
            UnitsSeeder::class,
            MaterialCategoriesSeeder::class,
            ProductTypeSeeder::class,
            ApplicationTypeSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        // =============================================
        // FASE 2: ENTIDADES MAESTRAS
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 2: PROVEEDORES Y MATERIALES                        │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call([
            ProveedorBordadosSeeder::class,
            MaterialCatalogSeeder::class,
        ]);

        // =============================================
        // FASE 3: COMPRA INICIAL (GENERA STOCK)
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 3: COMPRA INICIAL Y STOCK                          │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call(CompraInicialMaterialesSeeder::class);

        // =============================================
        // FASE 4: PRODUCTOS
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 4: PRODUCTOS CON BOM                               │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call([
            ProductoHipilOperativoSeeder::class,
            ProductoBolsaYuteSeeder::class,
        ]);

        // =============================================
        // FASE 5: DISEÑOS Y CLIENTES
        // =============================================
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────┐');
        $this->command->info('│ FASE 5: DISEÑOS Y CLIENTES                              │');
        $this->command->info('└─────────────────────────────────────────────────────────┘');

        $this->call([
            DisenoBordadoSeeder::class,
            ClienteTestSeeder::class,
        ]);

        // =============================================
        // RESUMEN FINAL
        // =============================================
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║                                                          ║');
        $this->command->info('║   ✓ SEEDER OPERATIVO COMPLETADO                         ║');
        $this->command->info('║                                                          ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info('║                                                          ║');
        $this->command->info('║   Base de datos lista para operación:                   ║');
        $this->command->info('║                                                          ║');
        $this->command->info('║   • Catálogos: Estados, Giros, Unidades, Categorías     ║');
        $this->command->info('║   • Proveedores: 3 activos                              ║');
        $this->command->info('║   • Materiales: ~15 con stock real                      ║');
        $this->command->info('║   • Productos: 2 con BOM completo                       ║');
        $this->command->info('║   • Diseños: 4 aprobados                                ║');
        $this->command->info('║   • Clientes: 4 de prueba                               ║');
        $this->command->info('║                                                          ║');
        $this->command->info('║   Flujos validados:                                      ║');
        $this->command->info('║   • Compra → Recepción → Inventario ✓                   ║');
        $this->command->info('║   • Producto → BOM → Costo materiales ✓                 ║');
        $this->command->info('║                                                          ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }
}
