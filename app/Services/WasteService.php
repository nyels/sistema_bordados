<?php

namespace App\Services;

use App\Exceptions\InventoryException;
use App\Models\WasteEvent;
use App\Models\WasteMaterialItem;
use App\Models\MaterialVariant;
use App\Models\ProductVariant;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de Merma (Waste Service)
 *
 * Orquestador de eventos de merma.
 * Usa InventoryService como motor puro de inventario.
 */
class WasteService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Registrar merma de materiales (materia prima).
     *
     * @param array $materials Array de ['material_variant_id' => int, 'quantity' => float, 'notes' => ?string]
     * @param string $reason Motivo de la merma (obligatorio)
     * @param int|null $orderId Pedido relacionado (opcional)
     * @param string|null $evidencePath Ruta a evidencia (opcional)
     * @return WasteEvent
     * @throws \InvalidArgumentException Si faltan datos requeridos
     * @throws InventoryException Si no hay stock suficiente
     */
    public function registerMaterialWaste(
        array $materials,
        string $reason,
        ?int $orderId = null,
        ?string $evidencePath = null
    ): WasteEvent {
        return DB::transaction(function () use ($materials, $reason, $orderId, $evidencePath) {
            // Validaciones
            if (empty($materials)) {
                throw new \InvalidArgumentException(
                    'Debe especificar al menos un material para registrar la merma.'
                );
            }

            if (empty(trim($reason))) {
                throw new \InvalidArgumentException(
                    'El motivo de la merma es obligatorio.'
                );
            }

            // Preparar y validar items
            $totalCost = 0;
            $itemsData = [];

            foreach ($materials as $material) {
                $variantId = $material['material_variant_id'] ?? null;
                $quantity = (float) ($material['quantity'] ?? 0);
                $notes = $material['notes'] ?? null;

                if (!$variantId || $quantity <= 0) {
                    continue;
                }

                $variant = MaterialVariant::lockForUpdate()->findOrFail($variantId);

                // Validar stock suficiente
                if ($variant->current_stock < $quantity) {
                    throw InventoryException::insufficientStock(
                        $variantId,
                        $quantity,
                        $variant->current_stock
                    );
                }

                $unitCost = (float) $variant->average_cost;
                $itemTotalCost = $quantity * $unitCost;
                $totalCost += $itemTotalCost;

                $itemsData[] = [
                    'material_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_cost_snapshot' => $unitCost,
                    'total_cost' => $itemTotalCost,
                    'unit_symbol' => $variant->material?->consumptionUnit?->symbol
                        ?? $variant->material?->baseUnit?->symbol
                        ?? null,
                    'notes' => $notes,
                ];
            }

            if (empty($itemsData)) {
                throw new \InvalidArgumentException(
                    'No hay items válidos para registrar la merma.'
                );
            }

            // Crear WasteEvent (registro contable)
            $wasteEvent = WasteEvent::create([
                'waste_type' => WasteEvent::TYPE_MATERIAL,
                'order_id' => $orderId,
                'product_variant_id' => null,
                'quantity' => 0,
                'total_cost' => $totalCost,
                'reason' => trim($reason),
                'evidence_path' => $evidencePath,
                'created_by' => Auth::id(),
            ]);

            // Crear items + ajustes negativos de inventario
            foreach ($itemsData as $itemData) {
                WasteMaterialItem::create([
                    'waste_event_id' => $wasteEvent->id,
                    'material_variant_id' => $itemData['material_variant_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost_snapshot' => $itemData['unit_cost_snapshot'],
                    'total_cost' => $itemData['total_cost'],
                    'unit_symbol' => $itemData['unit_symbol'],
                    'notes' => $itemData['notes'],
                ]);

                // Ajuste negativo via motor puro
                $movement = $this->inventoryService->registerAdjustment(
                    variantId: $itemData['material_variant_id'],
                    quantity: $itemData['quantity'],
                    unitCost: $itemData['unit_cost_snapshot'],
                    isPositive: false,
                    notes: "Merma: " . trim($reason),
                    userId: Auth::id()
                );

                // Asignar referencia para trazabilidad (orquestador)
                $movement->update([
                    'reference_type' => 'App\\Models\\WasteEvent',
                    'reference_id' => $wasteEvent->id,
                ]);
            }

            return $wasteEvent->load('materialItems.materialVariant.material');
        });
    }

    /**
     * Registrar merma de producto terminado.
     *
     * CASO DE USO:
     * - PT dañado en almacén
     * - PT defectuoso post-producción
     * - PT perdido/robado
     *
     * IMPORTANTE:
     * - NO reduce ProductVariant.current_stock
     * - SOLO registra el evento con costo estimado
     *
     * @param int $productVariantId ID de la variante de producto
     * @param float $quantity Cantidad de unidades perdidas
     * @param string $reason Motivo de la merma
     * @param int|null $orderId Pedido relacionado (opcional)
     * @param string|null $evidencePath Ruta a evidencia (opcional)
     * @return WasteEvent
     */
    public function registerFinishedProductWaste(
        int $productVariantId,
        float $quantity,
        string $reason,
        ?int $orderId = null,
        ?string $evidencePath = null
    ): WasteEvent {
        return DB::transaction(function () use ($productVariantId, $quantity, $reason, $orderId, $evidencePath) {
            // Validaciones
            if ($quantity <= 0) {
                throw new \InvalidArgumentException(
                    'La cantidad de merma debe ser mayor a cero.'
                );
            }

            if (empty(trim($reason))) {
                throw new \InvalidArgumentException(
                    'El motivo de la merma es obligatorio.'
                );
            }

            $variant = ProductVariant::findOrFail($productVariantId);

            // Calcular costo estimado del PT
            // Usamos el precio como proxy del costo (sin costo real disponible)
            $estimatedCost = $this->calculateFinishedProductCost($variant, $quantity);

            // Crear evento de merma
            $wasteEvent = WasteEvent::create([
                'waste_type' => WasteEvent::TYPE_FINISHED_PRODUCT,
                'order_id' => $orderId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'total_cost' => $estimatedCost,
                'reason' => trim($reason),
                'evidence_path' => $evidencePath,
                'created_by' => Auth::id(),
            ]);

            return $wasteEvent->load('productVariant.product');
        });
    }

    /**
     * Registrar merma en proceso (WIP).
     *
     * @param int $orderId ID del pedido donde ocurrió la falla
     * @param array $materials Array de materiales perdidos
     * @param string $reason Motivo de la merma
     * @param string|null $evidencePath Ruta a evidencia
     * @return WasteEvent
     * @throws InventoryException Si no hay stock suficiente
     */
    public function registerWipWaste(
        int $orderId,
        array $materials,
        string $reason,
        ?string $evidencePath = null
    ): WasteEvent {
        return DB::transaction(function () use ($orderId, $materials, $reason, $evidencePath) {
            $order = Order::findOrFail($orderId);

            if (empty(trim($reason))) {
                throw new \InvalidArgumentException(
                    'El motivo de la merma es obligatorio.'
                );
            }

            $totalCost = 0;
            $itemsData = [];

            foreach ($materials as $material) {
                $variantId = $material['material_variant_id'] ?? null;
                $quantity = (float) ($material['quantity'] ?? 0);
                $notes = $material['notes'] ?? null;

                if (!$variantId || $quantity <= 0) {
                    continue;
                }

                $variant = MaterialVariant::lockForUpdate()->findOrFail($variantId);

                if ($variant->current_stock < $quantity) {
                    throw InventoryException::insufficientStock(
                        $variantId,
                        $quantity,
                        $variant->current_stock
                    );
                }

                $unitCost = (float) $variant->average_cost;
                $itemTotalCost = $quantity * $unitCost;
                $totalCost += $itemTotalCost;

                $itemsData[] = [
                    'material_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_cost_snapshot' => $unitCost,
                    'total_cost' => $itemTotalCost,
                    'unit_symbol' => $variant->material?->consumptionUnit?->symbol
                        ?? $variant->material?->baseUnit?->symbol
                        ?? null,
                    'notes' => $notes,
                ];
            }

            $wasteEvent = WasteEvent::create([
                'waste_type' => WasteEvent::TYPE_WIP,
                'order_id' => $orderId,
                'product_variant_id' => null,
                'quantity' => 0,
                'total_cost' => $totalCost,
                'reason' => trim($reason),
                'evidence_path' => $evidencePath,
                'created_by' => Auth::id(),
            ]);

            foreach ($itemsData as $itemData) {
                WasteMaterialItem::create([
                    'waste_event_id' => $wasteEvent->id,
                    'material_variant_id' => $itemData['material_variant_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost_snapshot' => $itemData['unit_cost_snapshot'],
                    'total_cost' => $itemData['total_cost'],
                    'unit_symbol' => $itemData['unit_symbol'],
                    'notes' => $itemData['notes'],
                ]);

                // Ajuste negativo via motor puro
                $movement = $this->inventoryService->registerAdjustment(
                    variantId: $itemData['material_variant_id'],
                    quantity: $itemData['quantity'],
                    unitCost: $itemData['unit_cost_snapshot'],
                    isPositive: false,
                    notes: "Merma WIP (Pedido #{$order->order_number}): " . trim($reason),
                    userId: Auth::id()
                );

                // Asignar referencia para trazabilidad (orquestador)
                $movement->update([
                    'reference_type' => 'App\\Models\\WasteEvent',
                    'reference_id' => $wasteEvent->id,
                ]);
            }

            return $wasteEvent->load(['order', 'materialItems.materialVariant.material']);
        });
    }

    /**
     * Calcular costo estimado de producto terminado.
     *
     * ESTRATEGIA:
     * 1. Si el producto tiene BOM, calcular costo de materiales
     * 2. Si no, usar el precio como proxy (margen asumido 0)
     *
     * @param ProductVariant $variant
     * @param float $quantity
     * @return float
     */
    public function calculateFinishedProductCost(ProductVariant $variant, float $quantity): float
    {
        $product = $variant->product;

        if (!$product) {
            return (float) $variant->price * $quantity;
        }

        // Intentar calcular desde BOM
        $bomCost = $this->calculateBomCost($product);

        if ($bomCost > 0) {
            return $bomCost * $quantity;
        }

        // Fallback: usar precio de la variante
        return (float) $variant->price * $quantity;
    }

    /**
     * Calcular costo de BOM de un producto.
     *
     * @param \App\Models\Product $product
     * @return float
     */
    protected function calculateBomCost($product): float
    {
        if (!$product->relationLoaded('materials')) {
            $product->load('materials.material.variants');
        }

        $totalCost = 0;

        foreach ($product->materials as $productMaterial) {
            $materialVariant = $productMaterial->material?->activeVariants?->first();

            if (!$materialVariant) {
                continue;
            }

            $quantity = (float) ($productMaterial->quantity ?? 0);
            $unitCost = (float) ($materialVariant->average_cost ?? 0);
            $totalCost += $quantity * $unitCost;
        }

        return $totalCost;
    }

    /**
     * Obtener resumen de merma por período.
     *
     * @param string|null $from Fecha inicio (Y-m-d)
     * @param string|null $to Fecha fin (Y-m-d)
     * @return array
     */
    public function getWasteSummary(?string $from = null, ?string $to = null): array
    {
        $query = WasteEvent::query();

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $byType = $query->clone()
            ->selectRaw('waste_type, COUNT(*) as count, SUM(total_cost) as total_cost')
            ->groupBy('waste_type')
            ->get()
            ->keyBy('waste_type');

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'by_type' => [
                'material' => [
                    'count' => $byType->get(WasteEvent::TYPE_MATERIAL)?->count ?? 0,
                    'total_cost' => (float) ($byType->get(WasteEvent::TYPE_MATERIAL)?->total_cost ?? 0),
                ],
                'wip' => [
                    'count' => $byType->get(WasteEvent::TYPE_WIP)?->count ?? 0,
                    'total_cost' => (float) ($byType->get(WasteEvent::TYPE_WIP)?->total_cost ?? 0),
                ],
                'finished_product' => [
                    'count' => $byType->get(WasteEvent::TYPE_FINISHED_PRODUCT)?->count ?? 0,
                    'total_cost' => (float) ($byType->get(WasteEvent::TYPE_FINISHED_PRODUCT)?->total_cost ?? 0),
                ],
            ],
            'totals' => [
                'count' => $byType->sum('count'),
                'total_cost' => (float) $byType->sum('total_cost'),
            ],
        ];
    }

    /**
     * Obtener merma por material específico.
     *
     * @param int $materialVariantId
     * @param string|null $from
     * @param string|null $to
     * @return array
     */
    public function getWasteByMaterial(
        int $materialVariantId,
        ?string $from = null,
        ?string $to = null
    ): array {
        $query = WasteMaterialItem::where('material_variant_id', $materialVariantId);

        if ($from || $to) {
            $query->whereHas('wasteEvent', function ($q) use ($from, $to) {
                if ($from) {
                    $q->whereDate('created_at', '>=', $from);
                }
                if ($to) {
                    $q->whereDate('created_at', '<=', $to);
                }
            });
        }

        $items = $query->with('wasteEvent')->get();

        return [
            'material_variant_id' => $materialVariantId,
            'total_quantity' => (float) $items->sum('quantity'),
            'total_cost' => (float) $items->sum('total_cost'),
            'events_count' => $items->pluck('waste_event_id')->unique()->count(),
            'items' => $items,
        ];
    }
}
