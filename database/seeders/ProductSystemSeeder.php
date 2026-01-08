<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductExtra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSystemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // --- SERVICIOS EXTRAS ESTANDARIZADOS (Contexto Artesanal / Hipil) ---

            // CÁLCULO REALISTA (Base 2025):
            // - Mano de Obra: ~$35 - $40 MXN / hora (incluyendo cargas sociales)
            // - Luz Comercial: ~$5.00 MXN / kWh
            // - Plancha Industrial: 1500W (1.5 kWh)

            $extras = [
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Planchado y Vaporizado (Delicado)',
                    // Tiempo: 20 min/prenda (Hipil lino). 
                    // Labor: $12.00 | Luz: $2.50 | Agua/Insumos: $0.50
                    'cost_addition' => 15.00,
                    'price_addition' => 45.00 // Margen x3 por riesgo de prenda
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Empaquetado Artesanal (Doblado + Bolsa)',
                    // Tiempo: 10 min.
                    // Labor: $6.00 | Bolsa/Cinta/Sticker: $4.00
                    'cost_addition' => 10.00,
                    'price_addition' => 25.00
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Servicio Express (Entrega < 24h)',
                    // Costo: 2 Horas Extra (Overtime) o Turno Nocturno
                    'cost_addition' => 100.00,
                    'price_addition' => 250.00 // Premium por urgencia
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Cosido de Etiqueta Personalizada',
                    // Tiempo: 5-8 min (Descoser, centrar, coser).
                    // Labor: $5.00 | Hilo/Desgaste: $1.00
                    'cost_addition' => 6.00,
                    'price_addition' => 18.00
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Lavado y Suavizado (Lino)',
                    // Proceso delicado, no industrial masivo.
                    // Jabón especial + Agua + Secado aire + Tiempo
                    'cost_addition' => 25.00,
                    'price_addition' => 65.00
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Caja de Regalo Rígida (Premium)',
                    // Costo directo proveedor (Caja + Papel China + Moño)
                    'cost_addition' => 45.00,
                    'price_addition' => 90.00
                ]
            ];

            foreach ($extras as $data) {
                ProductExtra::updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );
            }
        });
    }
}
