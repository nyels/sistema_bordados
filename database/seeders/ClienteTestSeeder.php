<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Cliente;
use App\Models\ClientMeasurement;
use App\Models\Estado;
use App\Models\Recomendacion;
use App\Models\User;

/**
 * =============================================================================
 * CLIENTES DE PRUEBA OPERATIVOS
 * =============================================================================
 *
 * Clientes REALES con:
 * - Datos de contacto completos
 * - Medidas corporales (para prendas a medida)
 * - Historial de medidas
 *
 * DEPENDENCIAS:
 * - EstadoSeeder
 * - RecomendacionSeeder
 */
class ClienteTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════');
        $this->command->info('   SEMBRANDO CLIENTES DE PRUEBA');
        $this->command->info('══════════════════════════════════════════════════');

        DB::transaction(function () {
            // Obtener dependencias
            $yucatan = Estado::where('nombre_estado', 'LIKE', '%YUCAT%')->first();
            $quintanaRoo = Estado::where('nombre_estado', 'LIKE', '%QUINTANA%')->first();
            $campeche = Estado::where('nombre_estado', 'LIKE', '%CAMPECHE%')->first();

            if (!$yucatan) $yucatan = Estado::first();
            if (!$quintanaRoo) $quintanaRoo = Estado::skip(1)->first() ?? Estado::first();
            if (!$campeche) $campeche = Estado::skip(2)->first() ?? Estado::first();

            $recInstagram = Recomendacion::where('nombre_recomendacion', 'LIKE', '%INSTAGRAM%')->first();
            $recFacebook = Recomendacion::where('nombre_recomendacion', 'LIKE', '%FACEBOOK%')->first();
            $recPersonal = Recomendacion::where('nombre_recomendacion', 'LIKE', '%PERSONAL%')->first();

            if (!$recInstagram) $recInstagram = Recomendacion::first();
            if (!$recFacebook) $recFacebook = Recomendacion::first();
            if (!$recPersonal) $recPersonal = Recomendacion::first();

            $user = User::first();

            $this->command->info('');
            $this->command->info('👥 Creando clientes...');

            // =============================================
            // CLIENTE 1: Con medidas completas (para hipil)
            // =============================================
            $cliente1 = Cliente::updateOrCreate(
                ['email' => 'ana.martinez@ejemplo.com'],
                [
                    'nombre' => 'Ana María',
                    'apellidos' => 'Martínez López',
                    'telefono' => '999-234-5678',
                    'direccion' => 'Calle 45 #123 x 22 y 24, Col. Centro',
                    'ciudad' => 'Mérida',
                    'codigo_postal' => '97000',
                    'observaciones' => 'Cliente frecuente. Prefiere bordado tradicional en tonos rojos.',
                    'estado_id' => $yucatan->id,
                    'recomendacion_id' => $recInstagram->id,
                    'activo' => true,
                    // Medidas legacy (compatibilidad)
                    'busto' => 95.5,
                    'alto_cintura' => 42.0,
                    'cintura' => 78.5,
                    'cadera' => 102.0,
                    'largo' => 105.0,
                ]
            );

            // Crear medidas en tabla nueva
            if ($user) {
                ClientMeasurement::updateOrCreate(
                    ['cliente_id' => $cliente1->id, 'label' => 'Medidas Principales'],
                    [
                        'uuid' => (string) Str::uuid(),
                        'busto' => 95.5,
                        'cintura' => 78.5,
                        'cadera' => 102.0,
                        'alto_cintura' => 42.0,
                        'largo' => 105.0,
                        'hombro' => 38.5,
                        'espalda' => 40.0,
                        'largo_manga' => 58.0,
                        'is_primary' => true,
                        'notes' => 'Medidas tomadas en taller. Cliente de talla M.',
                        'created_by' => $user->id,
                    ]
                );
            }

            $this->command->info("   ✓ {$cliente1->nombre} {$cliente1->apellidos}");
            $this->command->info("     Tel: {$cliente1->telefono} | {$cliente1->ciudad}");
            $this->command->info("     Medidas: Busto {$cliente1->busto}cm, Cintura {$cliente1->cintura}cm");

            // =============================================
            // CLIENTE 2: Para accesorios (sin medidas)
            // =============================================
            $cliente2 = Cliente::updateOrCreate(
                ['email' => 'carlos.gonzalez@ejemplo.com'],
                [
                    'nombre' => 'Carlos',
                    'apellidos' => 'González Pérez',
                    'telefono' => '998-876-5432',
                    'direccion' => 'Av. Kukulcán km 12.5, Zona Hotelera',
                    'ciudad' => 'Cancún',
                    'codigo_postal' => '77500',
                    'observaciones' => 'Compra bolsas para regalo. Prefiere empaque especial.',
                    'estado_id' => $quintanaRoo->id,
                    'recomendacion_id' => $recFacebook->id,
                    'activo' => true,
                ]
            );

            $this->command->info("   ✓ {$cliente2->nombre} {$cliente2->apellidos}");
            $this->command->info("     Tel: {$cliente2->telefono} | {$cliente2->ciudad}");

            // =============================================
            // CLIENTE 3: Con medidas especiales (talla grande)
            // =============================================
            $cliente3 = Cliente::updateOrCreate(
                ['email' => 'elena.pool@ejemplo.com'],
                [
                    'nombre' => 'Elena',
                    'apellidos' => 'Pool Canul',
                    'telefono' => '981-123-4567',
                    'direccion' => 'Calle 10 #45, Col. San Román',
                    'ciudad' => 'Campeche',
                    'codigo_postal' => '24040',
                    'observaciones' => 'Clienta para eventos especiales. Talla XL.',
                    'estado_id' => $campeche->id,
                    'recomendacion_id' => $recPersonal->id,
                    'activo' => true,
                    'busto' => 115.0,
                    'alto_cintura' => 48.0,
                    'cintura' => 98.5,
                    'cadera' => 120.0,
                    'largo' => 110.0,
                ]
            );

            if ($user) {
                ClientMeasurement::updateOrCreate(
                    ['cliente_id' => $cliente3->id, 'label' => 'Medidas Principales'],
                    [
                        'uuid' => (string) Str::uuid(),
                        'busto' => 115.0,
                        'cintura' => 98.5,
                        'cadera' => 120.0,
                        'alto_cintura' => 48.0,
                        'largo' => 110.0,
                        'hombro' => 42.0,
                        'espalda' => 44.0,
                        'largo_manga' => 60.0,
                        'is_primary' => true,
                        'notes' => 'Talla XL. Preferencia por largo 110cm.',
                        'created_by' => $user->id,
                    ]
                );
            }

            $this->command->info("   ✓ {$cliente3->nombre} {$cliente3->apellidos}");
            $this->command->info("     Tel: {$cliente3->telefono} | {$cliente3->ciudad}");
            $this->command->info("     Medidas: Busto {$cliente3->busto}cm, Cintura {$cliente3->cintura}cm");

            // =============================================
            // CLIENTE 4: Mayorista
            // =============================================
            $cliente4 = Cliente::updateOrCreate(
                ['email' => 'tienda.artesanias@ejemplo.com'],
                [
                    'nombre' => 'Artesanías',
                    'apellidos' => 'Yucatecas S.A.',
                    'telefono' => '999-111-2233',
                    'direccion' => 'Calle 60 #500, Centro',
                    'ciudad' => 'Mérida',
                    'codigo_postal' => '97000',
                    'observaciones' => 'CLIENTE MAYORISTA. Descuento 15%. Factura requerida.',
                    'estado_id' => $yucatan->id,
                    'recomendacion_id' => $recPersonal->id,
                    'activo' => true,
                ]
            );

            $this->command->info("   ✓ {$cliente4->nombre} {$cliente4->apellidos}");
            $this->command->info("     Tel: {$cliente4->telefono} | MAYORISTA");

            // =============================================
            // RESUMEN
            // =============================================
            $totalClientes = Cliente::count();
            $totalMedidas = ClientMeasurement::count();

            $this->command->info('');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info('   CLIENTES CREADOS');
            $this->command->info('══════════════════════════════════════════════════');
            $this->command->info("   Total clientes:  {$totalClientes}");
            $this->command->info("   Con medidas:     {$totalMedidas}");
            $this->command->info('');
        });
    }
}
