<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\MaterialVariant;

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

            // RESOLVER product_type_id desde categoría (Zero-Trust - NO confiar en frontend)
            $productTypeId = $this->resolveProductTypeId($data['product_category_id']);

            // Crear producto
            $product = Product::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'] ?? 1,
                'product_category_id' => $data['product_category_id'],
                'product_type_id' => $productTypeId, // Resuelto por dominio desde categoría
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'specifications' => $specifications,
                'status' => $data['status'] ?? 'draft',
                // Pricing Fields
                'base_price' => $data['base_price'] ?? null,
                'production_cost' => $data['production_cost'] ?? null,
                'materials_cost' => $data['materials_cost'] ?? null,
                'embroidery_cost' => $data['embroidery_cost'] ?? null,
                'embroidery_rate_per_thousand' => $data['embroidery_rate_per_thousand'] ?? null,
                'labor_cost' => $data['labor_cost'] ?? null,
                'extra_services_cost' => $data['extra_services_cost'] ?? null,
                'suggested_price' => $data['suggested_price'] ?? null,
                'profit_margin' => $data['profit_margin'] ?? null,
                'production_lead_time' => $data['production_lead_time'] ?? null,
            ]);

            // Asignar diseños/producciones
            if (!empty($data['designs_list'])) {
                // Nueva estructura desde wizard (con export_id)
                $this->syncDesignsList($product, $data['designs_list']);
            } elseif (!empty($data['designs'])) {
                // Legacy (array de design_ids)
                $this->syncDesigns($product, $data['designs'], $data['design_applications'] ?? []);
            }

            // Asignar extras with SNAPSHOT logic (Audit History)
            if (!empty($data['extras'])) {
                $extrasWithPivot = [];
                // Fetch current catalog values to freeze them
                $catalogExtras = \App\Models\ProductExtra::whereIn('id', $data['extras'])->get()->keyBy('id');

                foreach ($data['extras'] as $extraId) {
                    if ($catalogExtra = $catalogExtras->get($extraId)) {
                        $extrasWithPivot[$extraId] = [
                            'snapshot_cost' => $catalogExtra->cost_addition,
                            'snapshot_price' => $catalogExtra->price_addition,
                            'snapshot_time' => $catalogExtra->minutes_addition,
                        ];
                    }
                }

                $product->extras()->sync($extrasWithPivot);

                // RECALCULATION: Use the SNAPSHOT prices for the total
                // This ensures consistency between the stored total and the pivot details
                $realExtrasCost = collect($extrasWithPivot)->sum('snapshot_price');
                $product->update(['extra_services_cost' => $realExtrasCost]);
            }

            // Asignar materiales (BOM)
            if (!empty($data['materials'])) {
                $this->syncMaterials($product, $data['materials']);
            }

            // Crear variante inicial si se proporcionó (Legacy)
            if (!empty($data['initial_variant'])) {
                $this->createVariant($product, $data['initial_variant']);
            }

            // CREAR VARIANTES (Wizard Step 2)
            if (!empty($data['variants']) && is_array($data['variants'])) {
                // Pre-fetch attribute IDs to avoid queries inside loop
                $tallaAttr = \App\Models\Attribute::where('slug', 'talla')->value('id');
                $colorAttr = \App\Models\Attribute::where('slug', 'color')->value('id');

                foreach ($data['variants'] as $v) {
                    $variantData = [
                        // SKU vacío = generar automático en createVariant()
                        'sku_variant' => !empty($v['sku_variant']) ? $v['sku_variant'] : (!empty($v['sku']) ? $v['sku'] : null),
                        'price' => $data['base_price'] ?? 0,
                        // Stock mínimo desde wizard (default 0 si no viene)
                        'stock_alert' => isset($v['stock_alert']) ? (int) $v['stock_alert'] : 0,
                        'attribute_values' => []
                    ];

                    // Map Attributes
                    if (!empty($v['size_id']) && $tallaAttr) {
                        $variantData['attribute_values'][$v['size_id']] = $tallaAttr;
                    }
                    if (!empty($v['color_id']) && $colorAttr) {
                        $variantData['attribute_values'][$v['color_id']] = $colorAttr;
                    }

                    // Create with unique SKU check logic handled inside createVariant potentially, 
                    // but here we trust the backend unique check or let it fail if duplicate
                    try {
                        $this->createVariant($product, $variantData);
                    } catch (\Exception $e) {
                        // Log duplicate SKU error but don't fail entire transaction? 
                        // Better to fail so user knows.
                        throw $e;
                    }
                }
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
                'product_type_id' => $data['product_type_id'] ?? $product->product_type_id, // UPDATE: mantener existente si no viene
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'specifications' => $specifications,
                'status' => $data['status'] ?? $product->status,
                'base_price' => $data['base_price'] ?? $product->base_price,
                'production_cost' => $data['production_cost'] ?? $product->production_cost,
                'materials_cost' => $data['materials_cost'] ?? $product->materials_cost,
                'embroidery_cost' => $data['embroidery_cost'] ?? $product->embroidery_cost,
                'embroidery_rate_per_thousand' => $data['embroidery_rate_per_thousand'] ?? $product->embroidery_rate_per_thousand,
                'labor_cost' => $data['labor_cost'] ?? $product->labor_cost,
                'extra_services_cost' => $data['extra_services_cost'] ?? $product->extra_services_cost,
                'suggested_price' => $data['suggested_price'] ?? $product->suggested_price,
                'profit_margin' => $data['profit_margin'] ?? $product->profit_margin,
                'production_lead_time' => $data['production_lead_time'] ?? $product->production_lead_time,
            ]);

            // Sincronizar diseños (Refactor Multi-Posición)
            if (isset($data['designs_list'])) {
                $this->syncDesignsList($product, $data['designs_list']);
            } elseif (isset($data['designs'])) {
                // Fallback Legacy
                $this->syncDesigns($product, $data['designs'], $data['design_applications'] ?? []);
            }

            // Sincronizar extras with SNAPSHOT logic
            if (isset($data['extras'])) {
                $extrasWithPivot = [];
                $catalogExtras = \App\Models\ProductExtra::whereIn('id', $data['extras'])->get()->keyBy('id');

                foreach ($data['extras'] as $extraId) {
                    if ($catalogExtra = $catalogExtras->get($extraId)) {
                        $extrasWithPivot[$extraId] = [
                            'snapshot_cost' => $catalogExtra->cost_addition,
                            'snapshot_price' => $catalogExtra->price_addition,
                            'snapshot_time' => $catalogExtra->minutes_addition,
                        ];
                    }
                }

                $product->extras()->sync($extrasWithPivot);

                // RECALCULATION: Use SNAPSHOT prices
                $realExtrasCost = collect($extrasWithPivot)->sum('snapshot_price');
                $product->update(['extra_services_cost' => $realExtrasCost]);
            }

            // Sincronizar materiales (BOM)
            if (isset($data['materials'])) {
                $this->syncMaterials($product, $data['materials']);
            }

            // Sincronizar variantes (Wizard Step 2)
            if (isset($data['variants']) && is_array($data['variants'])) {
                $this->syncVariants($product, $data['variants'], $data['base_price'] ?? $product->base_price);
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

                // Copiar atributos de variante (relación correcta: attributeValues)
                foreach ($variant->attributeValues as $attrValue) {
                    $newVariant->attributeValues()->attach($attrValue->id, [
                        'attribute_id' => $attrValue->pivot->attribute_id,
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
            // FIX: empty string debe generar SKU automático (clone mode envía '')
            $skuVariant = !empty($data['sku_variant'])
                ? $data['sku_variant']
                : $this->generateVariantSku($product, $data['attributes'] ?? []);

            $variant = ProductVariant::create([
                'uuid' => (string) Str::uuid(),
                'product_id' => $product->id,
                'sku_variant' => $skuVariant,
                'price' => $data['price'] ?? 0,
                'attribute_combinations' => $data['attributes'] ?? null,
                'stock_alert' => $data['stock_alert'] ?? 5,
            ]);

            // Asignar atributos (pivote) - relación correcta: attributeValues
            if (!empty($data['attribute_values'])) {
                foreach ($data['attribute_values'] as $attrValueId => $attrId) {
                    $variant->attributeValues()->attach($attrValueId, [
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

            // Sincronizar atributos - relación correcta: attributeValues
            if (isset($data['attribute_values'])) {
                $variant->attributeValues()->detach();
                foreach ($data['attribute_values'] as $attrValueId => $attrId) {
                    $variant->attributeValues()->attach($attrValueId, [
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

            return $variant->fresh(['attributeValues', 'designExports']);
        }, 3);
    }

    /**
     * Baja lógica de variante (no elimina físicamente)
     */
    public function deleteVariant(ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($variant) {
            $variantId = $variant->id;

            $variant->update(['activo' => false]);

            Log::info('Variante de producto desactivada', [
                'variant_id' => $variantId,
                'user_id' => Auth::id(),
            ]);

            return true;
        }, 3);
    }

    /**
     * Sincronizar variantes del producto (para UPDATE)
     * Estrategia: borrar existentes sin db_id, actualizar con db_id, crear nuevas
     */
    protected function syncVariants(Product $product, array $variants, float $basePrice = 0): void
    {
        $tallaAttr = \App\Models\Attribute::where('slug', 'talla')->value('id');
        $colorAttr = \App\Models\Attribute::where('slug', 'color')->value('id');

        // Recolectar IDs de variantes que vienen del frontend (las que tienen db_id)
        $incomingDbIds = collect($variants)
            ->filter(fn($v) => !empty($v['db_id']))
            ->pluck('db_id')
            ->toArray();

        // Baja lógica de variantes que ya no están en el frontend
        $product->variants()
            ->whereNotIn('id', $incomingDbIds)
            ->update(['activo' => false]);

        foreach ($variants as $v) {
            $attributeValues = [];
            if (!empty($v['size_id']) && $tallaAttr) {
                $attributeValues[$v['size_id']] = $tallaAttr;
            }
            if (!empty($v['color_id']) && $colorAttr) {
                $attributeValues[$v['color_id']] = $colorAttr;
            }

            if (!empty($v['db_id'])) {
                // Actualizar existente
                $existing = ProductVariant::find($v['db_id']);
                if ($existing && $existing->product_id === $product->id) {
                    $this->updateVariant($existing, [
                        'sku_variant' => !empty($v['sku']) ? $v['sku'] : $existing->sku_variant,
                        'price' => $v['price'] ?? $basePrice,
                        'stock_alert' => isset($v['stock_alert']) ? (int) $v['stock_alert'] : $existing->stock_alert,
                        'attribute_values' => $attributeValues,
                    ]);
                }
            } else {
                // Crear nueva
                $this->createVariant($product, [
                    'sku_variant' => !empty($v['sku']) ? $v['sku'] : null,
                    'price' => $v['price'] ?? $basePrice,
                    'stock_alert' => isset($v['stock_alert']) ? (int) $v['stock_alert'] : 0,
                    'attribute_values' => $attributeValues,
                ]);
            }
        }
    }

    /**
     * RESOLUCIÓN DE DOMINIO: Obtiene product_type_id desde la categoría.
     *
     * ZERO-TRUST: Ignora cualquier product_type_id del request.
     * El backend es la ÚNICA autoridad.
     *
     * @throws \InvalidArgumentException Si la categoría no existe o el tipo no se puede resolver
     */
    protected function resolveProductTypeId(int $categoryId): int
    {
        $category = ProductCategory::find($categoryId);

        if (!$category) {
            throw new \InvalidArgumentException(
                "La categoría con ID {$categoryId} no existe."
            );
        }

        $productType = $category->resolveProductType();

        Log::info('ProductType resuelto por dominio', [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'supports_measurements' => $category->supports_measurements,
            'resolved_type_id' => $productType->id,
            'resolved_type_code' => $productType->code,
        ]);

        return $productType->id;
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
    /**
     * Sincronizar diseños/producciones desde wizard
     * Soporta:
     * - export_id: ID del DesignExport (archivo de producción)
     * - scope: 'global' (todas las variantes) o 'specific' (variante específica)
     * - app_type_slug: tipo de aplicación (ubicación del bordado)
     * - target_variant: temp_id de la variante destino (si scope=specific)
     */
    protected function syncDesignsList(Product $product, array $designsList): void
    {
        // Limpiar relaciones previas
        $product->designs()->detach();

        foreach ($designsList as $item) {
            $exportId = $item['export_id'] ?? null;
            if (!$exportId) continue;

            // Obtener el DesignExport para sacar design_id y app_type
            $export = \App\Models\DesignExport::find($exportId);
            if (!$export) {
                Log::warning("DesignExport not found", ['export_id' => $exportId]);
                continue;
            }

            // Obtener application_type_id desde slug
            $appTypeSlug = $item['app_type_slug'] ?? $export->application_type ?? 'general';
            $appType = \App\Models\Application_types::where('slug', $appTypeSlug)->first();
            $appTypeId = $appType ? $appType->id : 1;

            // Guardar en product_design con design_id Y design_export_id
            $product->designs()->syncWithoutDetaching([
                $export->design_id => [
                    'application_type_id' => $appTypeId,
                    'design_export_id' => $export->id
                ]
            ]);

            // Si scope=specific, también guardar en product_variant_design
            // Nota: target_variant es temp_id del frontend, necesitamos mapear a variant real
            // Por ahora solo guardamos el global, el específico requiere variantes ya creadas
        }
    }

    /**
     * Sincronizar diseños con application_type (Legacy Wrapper)
     */
    protected function syncDesigns(Product $product, array $designIds, array $applications = []): void
    {
        $syncData = [];
        foreach ($designIds as $designId) {
            $appTypeId = $applications[$designId] ?? null;

            // FALLBACK: If no position specified, try to infer it from the Design Export metadata
            if (!$appTypeId) {
                // 1. Try to find the design and its approved export
                $design = \App\Models\Design::with(['generalExports' => fn($q) => $q->where('status', 'aprobado')])->find($designId);

                if ($design) {
                    $export = $design->generalExports->first();
                    // 2. If export exists and has an application type slug, look up the ID
                    if ($export && $export->application_type) {
                        $appType = \App\Models\Application_types::where('slug', $export->application_type)->first();
                        if ($appType) {
                            $appTypeId = $appType->id;
                        }
                    }
                }

                // 3. Absolute Fallback (Safety Net)
                if (!$appTypeId) {
                    $appTypeId = 1;
                    Log::warning("Design $designId has no position defined or inferable. Defaulting to $appTypeId", ['product_id' => $product->id]);
                }
            }
            // Ensure we don't continue if it's still null, but with fallback it shouldn't be
            if (!$appTypeId) {
                continue;
            }
            // Construct list format for the new method
            $syncData[] = [
                'design_id' => $designId,
                'application_type_id' => $appTypeId
            ];
        }
        $this->syncDesignsList($product, $syncData);
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

            // Logica de Trazabilidad por Variante (Enterprise)
            // REGLA: scope inferido desde targets
            // - targets vacío/null = aplica a todas (global)
            // - targets con elementos = aplica solo a esas variantes (específico)
            $activeForVariants = null;
            $targets = $material['targets'] ?? [];

            // Compatibilidad legacy: si viene scope='specific', también usarlo
            if (!empty($targets) || (isset($material['scope']) && $material['scope'] === 'specific' && !empty($material['targets']))) {
                $activeForVariants = json_encode($targets);
            }

            $syncData[$materialVariantId] = [
                'quantity' => (float) $material['quantity'],
                'unit_cost' => (float) ($material['price'] ?? $this->getMaterialSnapshotCost($materialVariantId)),
                'is_primary' => !empty($material['is_primary']),
                'notes' => $material['notes'] ?? null,
                'active_for_variants' => $activeForVariants,
            ];
        }

        $product->materials()->sync($syncData);
    }

    /**
     * Get the current cost of a material variant for snapshot
     */
    protected function getMaterialSnapshotCost(int $variantId): float
    {
        $variant = MaterialVariant::find($variantId);
        if (!$variant) return 0;

        return (float) ($variant->average_cost > 0 ? $variant->average_cost : $variant->last_purchase_cost);
    }
}
