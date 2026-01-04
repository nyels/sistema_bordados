<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Exceptions\PurchaseException;
use App\Models\MaterialUnitConversion;
use App\Models\MaterialVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PurchaseService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Crear nueva compra
     */
    public function create(array $data, array $items): Purchase
    {
        return DB::transaction(function () use ($data, $items) {
            $purchase = Purchase::create([
                'uuid' => (string) Str::uuid(),
                'purchase_number' => Purchase::generatePurchaseNumber(),
                'proveedor_id' => $data['proveedor_id'],
                'status' => PurchaseStatus::DRAFT,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'reference' => $data['reference'] ?? null,
                'ordered_at' => $data['ordered_at'] ?? null,
                'expected_at' => $data['expected_at'] ?? null,
                'created_by' => Auth::id(),
                'activo' => true,
            ]);

            foreach ($items as $itemData) {
                $this->addItem($purchase, $itemData);
            }

            $purchase->recalculateTotals();

            Log::channel('purchases')->info('Compra creada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'proveedor_id' => $purchase->proveedor_id,
                'items_count' => count($items),
                'total' => $purchase->total,
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh(['items', 'proveedor']);
        }, 3);
    }

    /**
     * Actualizar compra existente
     */
    public function update(Purchase $purchase, array $data, array $items): Purchase
    {
        if (!$purchase->can_edit) {
            throw PurchaseException::cannotModify($purchase->id, $purchase->status->value);
        }

        return DB::transaction(function () use ($purchase, $data, $items) {
            $purchase->update([
                'proveedor_id' => $data['proveedor_id'],
                'tax_rate' => $data['tax_rate'] ?? $purchase->tax_rate,
                'discount_amount' => $data['discount_amount'] ?? $purchase->discount_amount,
                'notes' => $data['notes'] ?? $purchase->notes,
                'reference' => $data['reference'] ?? $purchase->reference,
                'ordered_at' => $data['ordered_at'] ?? $purchase->ordered_at,
                'expected_at' => $data['expected_at'] ?? $purchase->expected_at,
                'updated_by' => Auth::id(),
            ]);

            // Eliminar items existentes y recrear
            $purchase->items()->delete();

            foreach ($items as $itemData) {
                $this->addItem($purchase, $itemData);
            }

            $purchase->recalculateTotals();

            Log::channel('purchases')->info('Compra actualizada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'items_count' => count($items),
                'total' => $purchase->total,
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh(['items', 'proveedor']);
        }, 3);
    }

    /**
     * Agregar item a compra
     */
    protected function addItem(Purchase $purchase, array $data): PurchaseItem
    {
        $variant = MaterialVariant::with(['material', 'material.category'])
            ->findOrFail($data['material_variant_id']);

        $conversionFactor = $this->getConversionFactor(
            $variant->material_id,
            $data['unit_id'],
            $variant->material->category->base_unit_id
        );

        return PurchaseItem::create([
            'uuid' => (string) Str::uuid(),
            'purchase_id' => $purchase->id,
            'material_variant_id' => $data['material_variant_id'],
            'unit_id' => $data['unit_id'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'conversion_factor' => $conversionFactor,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Obtener factor de conversión
     */
    protected function getConversionFactor(int $materialId, int $fromUnitId, int $toUnitId): float
    {
        if ($fromUnitId === $toUnitId) {
            return 1;
        }

        $conversion = MaterialUnitConversion::where('material_id', $materialId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();

        return $conversion ? $conversion->conversion_factor : 1;
    }

    /**
     * Confirmar compra (pasar a pendiente)
     */
    public function confirm(Purchase $purchase): Purchase
    {
        if ($purchase->status !== PurchaseStatus::DRAFT) {
            throw PurchaseException::cannotModify($purchase->id, $purchase->status->value);
        }

        if ($purchase->items()->count() === 0) {
            throw PurchaseException::invalidItem('La compra debe tener al menos un item', [
                'purchase_id' => $purchase->id,
            ]);
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->markAsPending();

            Log::channel('purchases')->info('Compra confirmada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'status' => $purchase->status->value,
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh();
        });
    }

    /**
     * Recibir compra completa
     */
    public function receiveComplete(Purchase $purchase): Purchase
    {
        if (!$purchase->can_receive) {
            throw PurchaseException::cannotReceive($purchase->id, $purchase->status->value);
        }

        return DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                if (!$item->is_fully_received) {
                    $pendingQty = $item->pending_quantity;
                    $pendingConverted = $item->pending_converted_quantity;

                    // Registrar entrada en inventario
                    $this->inventoryService->registerEntry(
                        variantId: $item->material_variant_id,
                        quantity: $pendingConverted,
                        unitCost: $item->converted_unit_cost,
                        referenceType: 'purchase_item',
                        referenceId: $item->id,
                        notes: "Recepción completa OC: {$purchase->purchase_number}"
                    );

                    // Actualizar cantidad recibida
                    $item->addReceivedQuantity($pendingQty);
                }
            }

            $purchase->markAsReceived();

            Log::channel('purchases')->info('Compra recibida completamente', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'items_count' => $purchase->items->count(),
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh(['items']);
        }, 3);
    }

    /**
     * Recibir parcialmente un item
     */
    public function receivePartial(Purchase $purchase, int $itemId, float $quantity): Purchase
    {
        if (!$purchase->can_receive) {
            throw PurchaseException::cannotReceive($purchase->id, $purchase->status->value);
        }

        return DB::transaction(function () use ($purchase, $itemId, $quantity) {
            $item = $purchase->items()->findOrFail($itemId);

            if ($quantity > $item->pending_quantity) {
                throw PurchaseException::invalidItem(
                    'La cantidad a recibir excede lo pendiente',
                    [
                        'item_id' => $itemId,
                        'requested' => $quantity,
                        'pending' => $item->pending_quantity,
                    ]
                );
            }

            $convertedQty = $quantity * $item->conversion_factor;

            // Registrar entrada en inventario
            $this->inventoryService->registerEntry(
                variantId: $item->material_variant_id,
                quantity: $convertedQty,
                unitCost: $item->converted_unit_cost,
                referenceType: 'purchase_item',
                referenceId: $item->id,
                notes: "Recepción parcial OC: {$purchase->purchase_number}"
            );

            // Actualizar cantidad recibida
            $item->addReceivedQuantity($quantity);

            // Actualizar estado de la compra
            if ($purchase->isFullyReceived()) {
                $purchase->markAsReceived();
            } else {
                $purchase->markAsPartial();
            }

            Log::channel('purchases')->info('Recepción parcial de compra', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'item_id' => $itemId,
                'quantity_received' => $quantity,
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh(['items']);
        }, 3);
    }

    /**
     * Cancelar compra
     */
    public function cancel(Purchase $purchase, string $reason): Purchase
    {
        if (!$purchase->can_cancel) {
            throw PurchaseException::cannotCancel(
                $purchase->id,
                'Estado actual no permite cancelación'
            );
        }

        if ($purchase->hasReceivedItems()) {
            throw PurchaseException::cannotCancel(
                $purchase->id,
                'La compra tiene items ya recibidos'
            );
        }

        return DB::transaction(function () use ($purchase, $reason) {
            $purchase->markAsCancelled($reason);

            Log::channel('purchases')->info('Compra cancelada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'reason' => $reason,
                'user_id' => Auth::id(),
            ]);

            return $purchase->fresh();
        });
    }

    /**
     * Eliminar compra (soft delete)
     */
    public function delete(Purchase $purchase): bool
    {
        if ($purchase->status !== PurchaseStatus::DRAFT) {
            throw PurchaseException::cannotModify(
                $purchase->id,
                'Solo se pueden eliminar compras en borrador'
            );
        }

        return DB::transaction(function () use ($purchase) {
            $purchaseNumber = $purchase->purchase_number;

            $purchase->items()->delete();
            $purchase->activo = false;
            $purchase->save();
            $purchase->delete();

            Log::channel('purchases')->info('Compra eliminada', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchaseNumber,
                'user_id' => Auth::id(),
            ]);

            return true;
        });
    }
}
