<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductService
{
    public function createProduct(array $data)
    {
        // Iniciamos la transacción para asegurar atomicidad
        DB::beginTransaction();

        try {
            Log::info("Iniciando creación de producto SKU: " . ($data['sku'] ?? 'S/N'));

            // 1. Crear el Maestro del Producto
            $product = Product::create([
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'specifications' => $data['specifications'] ?? [],
                'status' => $data['status'] ?? 'active',
                'tenant_id' => 1
            ]);

            // 2. Asignar Extras (Alforzas, etc.)
            if (!empty($data['extra_ids'])) {
                $product->extras()->attach($data['extra_ids']);
            }

            // 3. Procesar Variantes
            foreach ($data['variants'] as $index => $variantData) {
                $variant = $product->variants()->create([
                    'sku_variant' => $variantData['sku_variant'],
                    'price' => $variantData['price'],
                    'stock_alert' => $variantData['stock_alert'] ?? 5,
                ]);

                // 4. Vincular Atributos
                if (!empty($variantData['attribute_values'])) {
                    $variant->attributes()->attach(
                        collect($variantData['attribute_values'])->mapWithKeys(function ($attr) {
                            return [$attr['value_id'] => ['attribute_id' => $attr['attribute_id']]];
                        })->toArray()
                    );
                }

                // 5. Vincular Archivos Técnicos (DesignExports)
                if (!empty($variantData['design_exports'])) {
                    foreach ($variantData['design_exports'] as $export) {
                        $variant->designExports()->attach($export['design_export_id'], [
                            'application_type_id' => $export['application_type_id'],
                            'notes' => $export['notes'] ?? null
                        ]);
                    }
                }
            }

            DB::commit();
            Log::info("Producto ID: {$product->id} creado exitosamente.");

            return $product->load('variants.designExports', 'extras');
        } catch (Exception $e) {
            DB::rollBack();

            // Trazabilidad crítica: Logueamos el error exacto con el archivo y línea
            Log::error("Error al crear producto: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $data // Guardamos qué datos causaron el fallo
            ]);

            // Re-lanzamos la excepción para que el Controller la maneje
            throw $e;
        }
    }
}
