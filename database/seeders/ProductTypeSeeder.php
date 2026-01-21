<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use Illuminate\Support\Str;

/**
 * Seeder OPCIONAL para tipos de producto predefinidos.
 *
 * NOTA: Este seeder es OPCIONAL y está diseñado para inicializar
 * el sistema con tipos de producto comunes. El sistema también
 * funciona si el administrador crea los tipos manualmente.
 *
 * CÓDIGOS INMUTABLES:
 * Los códigos (code) NO se pueden cambiar después de la creación.
 * Solo el display_name es editable por el usuario.
 *
 * USO:
 * php artisan db:seed --class=ProductTypeSeeder
 *
 * O descomentar en DatabaseSeeder.php:
 * $this->call([ProductTypeSeeder::class]);
 */
class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'GARMENT_CUSTOM',
                'display_name' => 'Prenda a Medida',
                'description' => 'Prendas confeccionadas según las medidas específicas del cliente (hipiles, blusas, vestidos tradicionales, etc.)',
                'requires_measurements' => true,
                'sort_order' => 10,
                'active' => true,
            ],
            [
                'code' => 'GARMENT_STANDARD',
                'display_name' => 'Prenda Estándar',
                'description' => 'Prendas con tallas estándar (S, M, L, XL) que no requieren medidas del cliente',
                'requires_measurements' => false,
                'sort_order' => 20,
                'active' => true,
            ],
            [
                'code' => 'ACCESSORY',
                'display_name' => 'Accesorio',
                'description' => 'Accesorios como bolsas, cosmetiqueras, monederos, fundas, etc.',
                'requires_measurements' => false,
                'sort_order' => 30,
                'active' => true,
            ],
            [
                'code' => 'SERVICE',
                'display_name' => 'Servicio',
                'description' => 'Servicios como bordado personalizado, reparaciones, ajustes, etc.',
                'requires_measurements' => false,
                'sort_order' => 40,
                'active' => true,
            ],
            [
                'code' => 'HOME_DECOR',
                'display_name' => 'Decoración del Hogar',
                'description' => 'Artículos decorativos como manteles, servilletas, caminos de mesa, cojines, etc.',
                'requires_measurements' => false,
                'sort_order' => 50,
                'active' => true,
            ],
        ];

        foreach ($types as $typeData) {
            // Usar updateOrCreate para idempotencia
            ProductType::updateOrCreate(
                ['code' => $typeData['code']],
                array_merge($typeData, ['uuid' => (string) Str::uuid()])
            );
        }

        $this->command->info('✓ Tipos de producto creados/actualizados: ' . count($types));
    }
}
