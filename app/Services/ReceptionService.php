<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\ReceptionStatus;
use App\Exceptions\InventoryException;
use App\Exceptions\PurchaseException;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReception;
use App\Models\PurchaseReceptionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ReceptionService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Crear recepción (parcial o completa)
     */
    public function createReception(
        Purchase $purchase,
        array $itemsData,
        ?string $deliveryNote = null,
        ?string $notes = null
    ): PurchaseReception {
        if (!$purchase->can_receive) {
            throw PurchaseException::cannotReceive($purchase->id, $purchase->status->value);
        }

        return DB::transaction(function () use ($purchase, $itemsData, $deliveryNote, $notes) {
            // Crear registro de recepción
            $reception = PurchaseReception::create([
                'uuid' => (string) Str::uuid(),
                'purchase_id' => $purchase->id,
                'status' => ReceptionStatus::COMPLETED,
                'delivery_note' => $deliveryNote,
                'notes' => $notes,
                'received_at' => now(),
                'received_by' => Auth::id(),
            ]);

            // Procesar cada item
            foreach ($itemsData as $itemData) {
                if (empty($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue;
                }

                $purchaseItem = PurchaseItem::findOrFail($itemData['item_id']);

                // Validar cantidad pendiente
                if ($itemData['quantity'] > $purchaseItem->pending_quantity) {
                    throw PurchaseException::invalidItem(
                        "Cantidad excede lo pendiente para item {$purchaseItem->id}",
                        [
                            'item_id' => $purchaseItem->id,
                            'requested' => $itemData['quantity'],
                            'pending' => $purchaseItem->pending_quantity,
                        ]
                    );
                }

                $convertedQty = $itemData['quantity'] * $purchaseItem->conversion_factor;

                // Registrar entrada en inventario
                $movement = $this->inventoryService->registerEntry(
                    variantId: $purchaseItem->material_variant_id,
                    quantity: $convertedQty,
                    unitCost: $purchaseItem->converted_unit_cost,
                    referenceType: 'purchase_reception',
                    referenceId: $reception->id,
                    notes: "Recepción {$reception->reception_number} - OC: {$purchase->purchase_number}"
                );

                // Crear item de recepción
                PurchaseReceptionItem::create([
                    'purchase_reception_id' => $reception->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'material_variant_id' => $purchaseItem->material_variant_id,
                    'quantity_received' => $itemData['quantity'],
                    'converted_quantity' => $convertedQty,
                    'unit_cost' => $purchaseItem->converted_unit_cost,
                    'inventory_movement_id' => $movement->id,
                ]);

                // Actualizar cantidad recibida en purchase_item
                $purchaseItem->addReceivedQuantity($itemData['quantity']);
            }

            // Actualizar estado de la compra
            $this->updatePurchaseStatus($purchase);

            Log::channel('purchases')->info('Recepción creada', [
                'reception_id' => $reception->id,
                'reception_number' => $reception->reception_number,
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'items_count' => $reception->items()->count(),
                'user_id' => Auth::id(),
            ]);

            return $reception->fresh(['items', 'receiver']);
        }, 3);
    }

    /**
     * Anular recepción (reversar movimientos)
     */
    public function voidReception(PurchaseReception $reception, string $reason): PurchaseReception
    {
        if ($reception->is_voided) {
            throw PurchaseException::invalidItem('Esta recepción ya está anulada', [
                'reception_id' => $reception->id,
            ]);
        }

        return DB::transaction(function () use ($reception, $reason) {
            $purchase = $reception->purchase;

            // Reversar cada item
            foreach ($reception->items as $receptionItem) {
                // Validar que hay stock suficiente para reversar
                $variant = $receptionItem->materialVariant;

                if ($variant->current_stock < $receptionItem->converted_quantity) {
                    throw InventoryException::insufficientStock(
                        $variant->id,
                        $receptionItem->converted_quantity,
                        $variant->current_stock
                    );
                }

                // Crear movimiento de reversión
                $this->inventoryService->registerExit(
                    variantId: $receptionItem->material_variant_id,
                    quantity: $receptionItem->converted_quantity,
                    referenceType: 'reception_void',
                    referenceId: $reception->id,
                    notes: "Anulación recepción {$reception->reception_number}: {$reason}"
                );

                // Reversar cantidad recibida en purchase_item
                $purchaseItem = $receptionItem->purchaseItem;
                $purchaseItem->quantity_received -= $receptionItem->quantity_received;
                $purchaseItem->converted_quantity_received -= $receptionItem->converted_quantity;
                $purchaseItem->save();
            }

            // Marcar recepción como anulada
            $reception->status = ReceptionStatus::VOIDED;
            $reception->voided_at = now();
            $reception->voided_by = Auth::id();
            $reception->void_reason = $reason;
            $reception->save();

            // Recalcular estado de la compra
            $this->updatePurchaseStatus($purchase);

            Log::channel('purchases')->warning('Recepción anulada', [
                'reception_id' => $reception->id,
                'reception_number' => $reception->reception_number,
                'purchase_id' => $purchase->id,
                'reason' => $reason,
                'user_id' => Auth::id(),
            ]);

            return $reception->fresh();
        }, 3);
    }

    /**
     * Recalcular estado de la compra (método público para uso externo)
     */
    public function recalculatePurchaseStatus(Purchase $purchase): void
    {
        $this->updatePurchaseStatus($purchase);
    }

    /**
     * Actualizar estado de la compra según recepciones
     */
    protected function updatePurchaseStatus(Purchase $purchase): void
    {
        $purchase->refresh();

        $totalOrdered = (float) $purchase->items()->sum('quantity');
        $totalReceived = (float) $purchase->items()->sum('quantity_received');

        // Usar tolerancia para comparación de floats (evitar problemas de precisión)
        $tolerance = 0.0001;

        if ($totalReceived <= 0) {
            $purchase->status = PurchaseStatus::PENDING;
            $purchase->received_at = null;
            $purchase->received_by = null;
        } elseif ($totalReceived >= ($totalOrdered - $tolerance)) {
            // Si lo recibido es >= ordenado (con tolerancia), marcar como recibido
            $purchase->status = PurchaseStatus::RECEIVED;
            $purchase->received_at = $purchase->received_at ?? now();
            $purchase->received_by = $purchase->received_by ?? Auth::id();
        } else {
            $purchase->status = PurchaseStatus::PARTIAL;
        }

        $purchase->save();

        Log::channel('purchases')->debug('Estado de compra actualizado', [
            'purchase_id' => $purchase->id,
            'total_ordered' => $totalOrdered,
            'total_received' => $totalReceived,
            'new_status' => $purchase->status->value,
        ]);
    }

    /**
     * Obtener histórico de recepciones de una compra
     */
    public function getReceptionHistory(Purchase $purchase): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseReception::where('purchase_id', $purchase->id)
            ->with(['items.materialVariant.material', 'items.purchaseItem.unit', 'receiver', 'voidedByUser'])
            ->orderBy('received_at', 'desc')
            ->get();
    }
}
