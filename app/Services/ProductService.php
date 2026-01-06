<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProductService
{
    /**
     * Crear producto con relaciones
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Preparar especificaciones
            $specifications = $this->prepareSpecifications($data['specifications'] ?? []);

            // Crear producto
            $product = Product::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'] ?? 1,
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'specifications' => $specifications,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Asignar diseños
            if (!empty($data['designs'])) {
                $this->syncDesigns($product, $data['designs'], $data['design_applications'] ?? []);
            }

            // Asignar extras
            if (!empty($data['extras'])) {
                $product->extras()->sync($data['extras']);
            }

            // Asignar materiales (BOM)
            if (!empty($data['materials'])) {
                $this->syncMaterials($product, $data['materials']);
            }

            // Crear variante inicial si se proporcionó
            if (!empty($data['initial_variant'])) {
                $this->createVariant($product, $data['initial_variant']);
            }

            Log::info('Producto creado', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'user_id' => Auth::id(),
            ]);

            return $product->fresh(['category', 'variants', 'designs', 'extras']);
        }, 3);
    }

    /**
     * Actualizar producto
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $specifications = $this->prepareSpecifications($data['specifications'] ?? []);

            $product->update([
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'specifications' => $specifications,
                'status' => $data['status'] ?? $product->status,
            ]);

            // Sincronizar diseños
            if (isset($data['designs'])) {
                $this->syncDesigns($product, $data['designs'], $data['design_applications'] ?? []);
            }

            // Sincronizar extras
            if (isset($data['extras'])) {
                $product->extras()->sync($data['extras']);
            }

            // Sincronizar materiales (BOM)
            if (isset($data['materials'])) {
                $this->syncMaterials($product, $data['materials']);
            }

            Log::info('Producto actualizado', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'user_id' => Auth::id(),
            ]);

            return $product->fresh(['category', 'variants', 'designs', 'extras']);
        }, 3);
    }

    /**
     * Eliminar producto
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $productId = $product->id;
            $sku = $product->sku;

            // Eliminar variantes
            $product->variants()->delete();

            // Desvincular relaciones
            $product->designs()->detach();
            $product->extras()->detach();

            // Soft delete
            $product->delete();

            Log::info('Producto eliminado', [
                'product_id' => $productId,
                'sku' => $sku,
                'user_id' => Auth::id(),
            ]);

            return true;
        }, 3);
    }

    /**
     * Duplicar producto
     */
    public function duplicateProduct(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $newProduct = $product->replicate();
            $newProduct->uuid = (string) Str::uuid();
            $newProduct->sku = $product->sku . '-COPY-' . Str::upper(Str::random(4));
            $newProduct->name = $product->name . ' (Copia)';
            $newProduct->status = 'draft';
            $newProduct->save();

            // Copiar diseños
            foreach ($product->designs as $design) {
                $newProduct->designs()->attach($design->id, [
                    'application_type_id' => $design->pivot->application_type_id,
                ]);
            }

            // Copiar extras
            $newProduct->extras()->sync($product->extras->pluck('id'));

            // Copiar variantes
            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate();
                $newVariant->uuid = (string) Str::uuid();
                $newVariant->product_id = $newProduct->id;
                $newVariant->sku_variant = $variant->sku_variant . '-COPY';
                $newVariant->save();

                // Copiar atributos de variante
                foreach ($variant->attributes as $attr) {
                    $newVariant->attributes()->attach($attr->id, [
                        'attribute_id' => $attr->pivot->attribute_id,
                    ]);
                }
            }

            Log::info('Producto duplicado', [
                'original_id' => $product->id,
                'new_id' => $newProduct->id,
                'user_id' => Auth::id(),
            ]);

            return $newProduct->fresh(['category', 'variants', 'designs', 'extras']);
        }, 3);
    }

    /**
     * Crear variante de producto
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $skuVariant = $data['sku_variant'] ?? $this->generateVariantSku($product, $data['attributes'] ?? []);

            $variant = ProductVariant::create([
                'uuid' => (string) Str::uuid(),
                'product_id' => $product->id,
                'sku_variant' => $skuVariant,
                'price' => $data['price'] ?? 0,
                'attribute_combinations' => $data['attributes'] ?? null,
                'stock_alert' => $data['stock_alert'] ?? 5,
            ]);

            // Asignar atributos (pivote)
            if (!empty($data['attribute_values'])) {
                foreach ($data['attribute_values'] as $attrValueId => $attrId) {
                    $variant->attributes()->attach($attrValueId, [
                        'attribute_id' => $attrId,
                    ]);
                }
            }

            // Asignar design exports
            if (!empty($data['design_exports'])) {
                foreach ($data['design_exports'] as $exportId => $exportData) {
                    $variant->designExports()->attach($exportId, [
                        'application_type_id' => $exportData['application_type_id'] ?? null,
                        'notes' => $exportData['notes'] ?? null,
                    ]);
                }
            }

            Log::info('Variante de producto creada', [
                'variant_id' => $variant->id,
                'product_id' => $product->id,
                'sku_variant' => $variant->sku_variant,
                'user_id' => Auth::id(),
            ]);

            return $variant;
        }, 3);
    }

    /**
     * Actualizar variante
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            $variant->update([
                'sku_variant' => $data['sku_variant'] ?? $variant->sku_variant,
                'price' => $data['price'] ?? $variant->price,
                'attribute_combinations' => $data['attributes'] ?? $variant->attribute_combinations,
                'stock_alert' => $data['stock_alert'] ?? $variant->stock_alert,
            ]);

            // Sincronizar atributos
            if (isset($data['attribute_values'])) {
                $variant->attributes()->detach();
                foreach ($data['attribute_values'] as $attrValueId => $attrId) {
                    $variant->attributes()->attach($attrValueId, [
                        'attribute_id' => $attrId,
                    ]);
                }
            }

            // Sincronizar design exports
            if (isset($data['design_exports'])) {
                $syncData = [];
                foreach ($data['design_exports'] as $exportId => $exportData) {
                    $syncData[$exportId] = [
                        'application_type_id' => $exportData['application_type_id'] ?? null,
                        'notes' => $exportData['notes'] ?? null,
                    ];
                }
                $variant->designExports()->sync($syncData);
            }

            Log::info('Variante de producto actualizada', [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'user_id' => Auth::id(),
            ]);

            return $variant->fresh(['attributes', 'designExports']);
        }, 3);
    }

    /**
     * Eliminar variante
     */
    public function deleteVariant(ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($variant) {
            $variantId = $variant->id;

            $variant->attributes()->detach();
            $variant->designExports()->detach();
            $variant->delete();

            Log::info('Variante de producto eliminada', [
                'variant_id' => $variantId,
                'user_id' => Auth::id(),
            ]);

            return true;
        }, 3);
    }

    /**
     * Preparar especificaciones desde array de key-value
     */
    protected function prepareSpecifications(array $specs): array
    {
        $result = [];
        foreach ($specs as $spec) {
            if (!empty($spec['key']) && !empty($spec['value'])) {
                $result[strip_tags(trim($spec['key']))] = strip_tags(trim($spec['value']));
            }
        }
        return $result;
    }

    /**
     * Sincronizar diseños con application_type
     */
    protected function syncDesigns(Product $product, array $designIds, array $applications = []): void
    {
        $syncData = [];
        foreach ($designIds as $designId) {
            $syncData[$designId] = [
                'application_type_id' => $applications[$designId] ?? null,
            ];
        }
        $product->designs()->sync($syncData);
    }

    /**
     * Generar SKU de variante
     */
    protected function generateVariantSku(Product $product, array $attributes = []): string
    {
        $parts = [$product->sku];

        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $parts[] = Str::upper(Str::substr(Str::slug($value, ''), 0, 3));
            }
        }

        if (count($parts) === 1) {
            $parts[] = Str::upper(Str::random(4));
        }

        return implode('-', $parts);
    }

    /**
     * Sincronizar materiales del producto (BOM)
     */
    protected function syncMaterials(Product $product, array $materials): void
    {
        $syncData = [];

        foreach ($materials as $material) {
            // Validar estructura básica
            if (!isset($material['material_variant_id']) || !isset($material['quantity'])) {
                continue;
            }

            $materialVariantId = $material['material_variant_id'];

            $syncData[$materialVariantId] = [
                'quantity' => (float) $material['quantity'],
                'is_primary' => !empty($material['is_primary']),
                'notes' => $material['notes'] ?? null,
            ];
        }

        $product->materials()->sync($syncData);
    }
}
