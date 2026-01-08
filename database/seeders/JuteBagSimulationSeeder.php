<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class JuteBagSimulationSeeder extends Seeder
{
    public function run()
    {
        // 1. Ensure Units Exist
        $units = [
            ['name' => 'Metro', 'slug' => 'metro', 'symbol' => 'mt', 'is_base' => true],
            ['name' => 'Par', 'slug' => 'par', 'symbol' => 'par', 'is_base' => true],
            ['name' => 'Pieza', 'slug' => 'pieza', 'symbol' => 'pza', 'is_base' => true],
        ];

        foreach ($units as $u) {
            DB::table('units')->updateOrInsert(
                ['slug' => $u['slug']],
                array_merge($u, ['uuid' => Str::uuid(), 'created_at' => now(), 'updated_at' => now()])
            );
        }

        $mtId = DB::table('units')->where('slug', 'metro')->value('id');
        $parId = DB::table('units')->where('slug', 'par')->value('id');
        $pzaId = DB::table('units')->where('slug', 'pieza')->value('id');

        // 2. Material Categories
        $cats = [
            'Telas' => $mtId,
            'Avíos' => $parId,
            'Etiquetas' => $pzaId,
            'Empaque' => $pzaId
        ];
        $catIds = [];
        foreach ($cats as $cName => $unitId) {
            $existing = DB::table('material_categories')->where('slug', Str::slug($cName))->first();
            if ($existing) {
                $catIds[$cName] = $existing->id;
            } else {
                $catIds[$cName] = DB::table('material_categories')->insertGetId([
                    'name' => $cName,
                    'slug' => Str::slug($cName),
                    'base_unit_id' => $unitId,
                    'has_color' => true,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 3. Materials
        $materials = [
            [
                'name' => 'Tela Yute Natural',
                'slug' => 'tela-yute-natural',
                'category' => 'Telas',
                'variants' => [
                    ['color' => 'Natural', 'sku' => 'MAT-YUT-NAT', 'cost' => 35.00, 'stock' => 50]
                ]
            ],
            [
                'name' => 'Tela Forro Popelina',
                'slug' => 'tela-forro-popelina',
                'category' => 'Telas',
                'variants' => [
                    ['color' => 'Blanco', 'sku' => 'MAT-POP-BLA', 'cost' => 25.00, 'stock' => 50]
                ]
            ],
            [
                'name' => 'Asas de Algodón Acolchadas',
                'slug' => 'asas-algodon-acolchadas',
                'category' => 'Avíos',
                'variants' => [
                    ['color' => 'Natural', 'sku' => 'AVI-ASA-NAT', 'cost' => 15.00, 'stock' => 100] // 100 units = 50 pairs if unit is pair? No, stock is usually in base unit. Let's assume stock is in 'Par'.
                ]
            ],
            [
                'name' => 'Etiqueta Bordada Marca',
                'slug' => 'etiqueta-bordada-marca',
                'category' => 'Etiquetas',
                'variants' => [
                    ['color' => 'Negro/Blanco', 'sku' => 'ETI-MAR-001', 'cost' => 2.00, 'stock' => 500]
                ]
            ],
            [
                'name' => 'Bolsa Celofán 35x35',
                'slug' => 'bolsa-celofan-35x35',
                'category' => 'Empaque',
                'variants' => [
                    ['color' => 'Transparente', 'sku' => 'EMP-CEL-3535', 'cost' => 1.50, 'stock' => 1000]
                ]
            ]
        ];

        $purchaseItems = [];

        foreach ($materials as $m) {
            $baseSlug = $m['slug'];
            // Check if material with similar slug exists
            $existingMat = DB::table('materials')->where('slug', 'like', $baseSlug . '%')->first();

            if ($existingMat) {
                $matId = $existingMat->id;
            } else {
                $matId = DB::table('materials')->insertGetId([
                    'uuid' => Str::uuid(),
                    'material_category_id' => $catIds[$m['category']],
                    'name' => $m['name'],
                    'slug' => $baseSlug . '-' . uniqid(),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            foreach ($m['variants'] as $v) {
                $existingVar = DB::table('material_variants')->where('sku', $v['sku'])->first();

                if ($existingVar) {
                    $varId = $existingVar->id;
                } else {
                    $varId = DB::table('material_variants')->insertGetId([
                        'uuid' => Str::uuid(),
                        'material_id' => $matId,
                        'color' => $v['color'],
                        'sku' => $v['sku'],
                        'current_stock' => $v['stock'],
                        'current_value' => $v['stock'] * $v['cost'],
                        'average_cost' => $v['cost'],
                        'last_purchase_cost' => $v['cost'],
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Prepare for Purchase (Add only if not exists?? No, purchases can be multiple)
                // But for simulation, let's just add items. 
                // Wait, if I re-run seeder, I might duplicate purchase.
                // Let's allow duplicate purchase for now as "another intake".

                $unitId = ($m['category'] == 'Telas') ? $mtId : (($m['category'] == 'Avíos') ? $parId : $pzaId);

                $purchaseItems[] = [
                    'material_variant_id' => $varId,
                    'unit_id' => $unitId,
                    'quantity' => $v['stock'],
                    'unit_price' => $v['cost'],
                    'subtotal' => $v['stock'] * $v['cost']
                ];
            }
        }

        // 4. Create a Supplier
        // Ensure dependencies exist
        $estadoId = DB::table('estados')->value('id');
        if (!$estadoId) {
            $estadoId = DB::table('estados')->insertGetId(['nombre' => 'Estado Generico', 'activo' => true]);
        }

        $giroId = DB::table('giros')->value('id');
        if (!$giroId) {
            $giroId = DB::table('giros')->insertGetId(['nombre' => 'Textil', 'activo' => true]);
        }

        $provider = DB::table('proveedors')->where('email', 'ventas@textilesmx.com')->first();
        if ($provider) {
            $providerId = $provider->id;
        } else {
            $providerId = DB::table('proveedors')->insertGetId([
                'nombre_proveedor' => 'Textiles de México S.A. de C.V.',
                'telefono' => '5512345678',
                'email' => 'ventas@textilesmx.com',
                'estado_id' => $estadoId,
                'giro_id' => $giroId,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 5. Create Purchase Record (Simulating "Ingreso")
        $userId = DB::table('users')->first()->id ?? 1;

        $subtotal = array_sum(array_column($purchaseItems, 'subtotal'));
        $tax = $subtotal * 0.16;
        $total = $subtotal + $tax;

        DB::table('purchases')->insert([
            'uuid' => Str::uuid(),
            'purchase_number' => 'PUR-' . date('ymd') . '-' . mt_rand(1000, 9999),
            'proveedor_id' => $providerId,
            'status' => 'received', // Simulating received stock
            'subtotal' => $subtotal,
            'tax_rate' => 16.00,
            'tax_amount' => $tax,
            'total' => $total,
            'ordered_at' => now()->subDays(5),
            'received_at' => now(),
            'created_by' => $userId,
            'activo' => true,
            'created_at' => now()->subDays(5),
            'updated_at' => now()
        ]);

        // Note: For full realism we should insert purchase items but purchase_id isn't returned by generic insert if we don't catch it.
        // Let's just create the Purchase header effectively for now to log the cost.
        // Or fetch the last ID.
        $purchaseId = DB::getPdo()->lastInsertId();

        // 6. Insert Purchase Items
        foreach ($purchaseItems as $item) {
            DB::table('purchase_items')->insert([
                'uuid' => Str::uuid(),
                'purchase_id' => $purchaseId,
                'material_variant_id' => $item['material_variant_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
                'quantity_received' => $item['quantity'],
                'converted_quantity' => $item['quantity'], // Assuming 1:1 conversion for simplification
                'converted_unit_cost' => $item['unit_price'],
                'converted_quantity_received' => $item['quantity'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 7. Seed Design
        $designName = 'Ramo Floral Vintage';
        $existingDesign = DB::table('designs')->where('name', $designName)->first();
        if (!$existingDesign) {
            $designId = DB::table('designs')->insertGetId([
                'name' => $designName,
                // 'sku' => 'DES-FLO-VIN-001', // SKU not in schema
                // 'stitch_count' => 15000, // Not in schema, in exports
                // 'estimated_time_minutes' => 45, // Not in schema? Remove if fails, but check migration 2025_12_19_172849 showed limited fields. 
                // Migration 2025_12_19_172849 only has: id, name, slug, description, is_active.
                // So I must remove the other fields too.
                'slug' => Str::slug($designName) . '-' . uniqid(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create Design Export (Technical Data)
            DB::table('design_exports')->insert([
                'design_id' => $designId,
                'application_label' => 'Bordado General',
                'file_name' => 'ramo_floral.dst',
                'file_path' => 'designs/ramo_floral.dst',
                'file_format' => 'dst',
                'stitches_count' => 15000,
                'colors_count' => 4,
                'width_mm' => 150,
                'height_mm' => 150,
                'status' => 'aprobado',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
