<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;
use App\Models\Estado;
use App\Models\Giro;

/**
 * =============================================================================
 * PROVEEDOR OPERATIVO: TEXTILES ARTESANALES
 * =============================================================================
 *
 * Proveedores REALES para operación de taller de bordados.
 *
 * DEPENDENCIAS:
 * - EstadoSeeder (estados mexicanos)
 * - GiroSeeder (giros comerciales)
 */
class ProveedorBordadosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO PROVEEDORES OPERATIVOS');
        $this->command->info('══════════════════════════════════════════════════');

        // Obtener estados y giros existentes
        $yucatan = Estado::where('nombre_estado', 'LIKE', '%YUCAT%')->first();
        $cdmx = Estado::where('nombre_estado', 'LIKE', '%CIUDAD DE M%')
            ->orWhere('nombre_estado', 'LIKE', '%MEXICO%')
            ->first();
        $oaxaca = Estado::where('nombre_estado', 'LIKE', '%OAXACA%')->first();

        if (!$yucatan) $yucatan = Estado::first();
        if (!$cdmx) $cdmx = Estado::skip(1)->first() ?? Estado::first();
        if (!$oaxaca) $oaxaca = Estado::skip(2)->first() ?? Estado::first();

        $giroTela = Giro::where('nombre_giro', 'LIKE', '%TELA%')
            ->where('nombre_giro', 'NOT LIKE', '%ACCESORIOS%')
            ->where('activo', true)
            ->first();
        $giroAccesorios = Giro::where('nombre_giro', 'LIKE', '%ACCESORIOS%')
            ->where('nombre_giro', 'NOT LIKE', '%TELA%')
            ->where('activo', true)
            ->first();
        $giroMixto = Giro::where('nombre_giro', 'LIKE', '%TELA Y ACCESORIOS%')
            ->where('activo', true)
            ->first();

        if (!$giroTela) $giroTela = Giro::first();
        if (!$giroAccesorios) $giroAccesorios = Giro::first();
        if (!$giroMixto) $giroMixto = Giro::first();

        $this->command->info('');
        $this->command->info('🏭 Creando proveedores...');

        // =============================================
        // PROVEEDOR 1: Textiles del Mayab (Telas + Hilos)
        // =============================================
        $prov1 = Proveedor::updateOrCreate(
            ['email' => 'ventas@textilesmayab.com.mx'],
            [
                'nombre_proveedor' => 'Textiles del Mayab S.A. de C.V.',
                'direccion' => 'Calle 60 #501 x 63 y 65, Centro',
                'codigo_postal' => '97000',
                'telefono' => '999-923-4567',
                'ciudad' => 'Mérida',
                'nombre_contacto' => 'María del Carmen González',
                'telefono_contacto' => '999-987-6543',
                'email_contacto' => 'mcarmen@textilesmayab.com.mx',
                'estado_id' => $yucatan->id,
                'giro_id' => $giroMixto->id,
                'activo' => true,
            ]
        );
        $this->command->info("   ✓ {$prov1->nombre_proveedor}");
        $this->command->info("     Giro: {$giroMixto->nombre_giro} | {$prov1->ciudad}");

        // =============================================
        // PROVEEDOR 2: Hilos Bordamex (Hilos especializados)
        // =============================================
        $prov2 = Proveedor::updateOrCreate(
            ['email' => 'pedidos@bordamex.com.mx'],
            [
                'nombre_proveedor' => 'Hilos Bordamex S.A. de C.V.',
                'direccion' => 'Av. Central #234, Zona Industrial',
                'codigo_postal' => '54030',
                'telefono' => '55-5555-1234',
                'ciudad' => 'Tlalnepantla',
                'nombre_contacto' => 'Roberto Sánchez',
                'telefono_contacto' => '55-5555-5678',
                'email_contacto' => 'rsanchez@bordamex.com.mx',
                'estado_id' => $cdmx->id,
                'giro_id' => $giroAccesorios->id,
                'activo' => true,
            ]
        );
        $this->command->info("   ✓ {$prov2->nombre_proveedor}");
        $this->command->info("     Giro: {$giroAccesorios->nombre_giro} | {$prov2->ciudad}");

        // =============================================
        // PROVEEDOR 3: Artesanías Oaxaqueñas (Yute, fibras naturales)
        // =============================================
        $prov3 = Proveedor::updateOrCreate(
            ['email' => 'contacto@artesaniasoax.com'],
            [
                'nombre_proveedor' => 'Artesanías y Fibras Oaxaqueñas',
                'direccion' => 'Calle Macedonio Alcalá #412, Centro',
                'codigo_postal' => '68000',
                'telefono' => '951-514-2233',
                'ciudad' => 'Oaxaca de Juárez',
                'nombre_contacto' => 'Josefina López',
                'telefono_contacto' => '951-514-4455',
                'email_contacto' => 'josefina@artesaniasoax.com',
                'estado_id' => $oaxaca->id,
                'giro_id' => $giroTela->id,
                'activo' => true,
            ]
        );
        $this->command->info("   ✓ {$prov3->nombre_proveedor}");
        $this->command->info("     Giro: {$giroTela->nombre_giro} | {$prov3->ciudad}");

        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   PROVEEDORES CREADOS: 3');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('');
    }
}
