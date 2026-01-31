<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;
use App\Models\MaterialVariant;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth; // <--- IMPORTACIÓN DE LA FACADE

class InventoryService
{
    /**
     * Registrar entrada de inventario (compra)
     */
    public function registerEntry(
        int $variantId,
        float $quantity,
        float $unitCost,
        string $referenceType,
        int $referenceId,
        ?string $notes = null,
        ?int $userId = null,
        ?float $totalCost = null
    ): InventoryMovement {
        return $this->createMovement(
            variantId: $variantId,
            type: MovementType::ENTRY,
            quantity: $quantity,
            unitCost: $unitCost,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes,
            userId: $userId,
            totalCost: $totalCost
        );
    }

    /**
     * Registrar salida de inventario (producción/consumo)
     */
    public function registerExit(
        int $variantId,
        float $quantity,
        string $referenceType,
        int $referenceId,
        ?string $notes = null,
        ?int $userId = null
    ): InventoryMovement {
        $variant = MaterialVariant::findOrFail($variantId);

        if ($variant->current_stock < $quantity) {
            throw InventoryException::insufficientStock(
                $variantId,
                $quantity,
                $variant->current_stock
            );
        }

        return $this->createMovement(
            variantId: $variantId,
            type: MovementType::EXIT,
            quantity: $quantity,
            unitCost: $variant->average_cost,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes,
            userId: $userId
        );
    }

    /**
     * Registrar ajuste de inventario
     *
     * @param int $variantId ID de la variante de material
     * @param float $quantity Cantidad a ajustar
     * @param float $unitCost Costo unitario
     * @param bool $isPositive true = entrada, false = salida
     * @param string|null $notes Notas del ajuste
     * @param int|null $userId Usuario que realiza el ajuste
     * @return InventoryMovement
     */
    public function registerAdjustment(
        int $variantId,
        float $quantity,
        float $unitCost,
        bool $isPositive,
        ?string $notes = null,
        ?int $userId = null
    ): InventoryMovement {
        $type = $isPositive
            ? MovementType::ADJUSTMENT_POSITIVE
            : MovementType::ADJUSTMENT_NEGATIVE;

        if (!$isPositive) {
            $variant = MaterialVariant::findOrFail($variantId);
            if ($variant->current_stock < $quantity) {
                throw InventoryException::insufficientStock(
                    $variantId,
                    $quantity,
                    $variant->current_stock
                );
            }
        }

        return $this->createMovement(
            variantId: $variantId,
            type: $type,
            quantity: $quantity,
            unitCost: $unitCost,
            referenceType: 'adjustment',
            referenceId: null,
            notes: $notes,
            userId: $userId
        );
    }

    /**
     * Crear movimiento y actualizar stock
     */
    protected function createMovement(
        int $variantId,
        MovementType $type,
        float $quantity,
        float $unitCost,
        string $referenceType,
        ?int $referenceId,
        ?string $notes,
        ?int $userId = null,
        ?float $totalCost = null
    ): InventoryMovement {
        // Resolvemos el ID del usuario: prioridad parámetro, luego sesión actual
        $finalUserId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $variantId,
            $type,
            $quantity,
            $unitCost,
            $referenceType,
            $referenceId,
            $notes,
            $finalUserId
        ) {
            $variant = MaterialVariant::lockForUpdate()->findOrFail($variantId);

            $stockBefore = $variant->current_stock;
            $valueBefore = $variant->current_value;
            $avgCostBefore = $variant->average_cost;

            $actualMovementTotalCost = $totalCost ?? ($quantity * $unitCost);
            $stockChange = $quantity * $type->affectsStock();

            $newStock = $stockBefore + $stockChange;

            // Calculamos el nuevo valor total
            if ($type->affectsStock() > 0) {
                // Entrada: sumamos el costo total real (evita errores de redondeo)
                $newValue = $valueBefore + $actualMovementTotalCost;
            } else {
                // Salida: usamos el costo según método (PMP)
                $newValue = $this->calculateNewValue($variant, $type, $quantity, $unitCost);
                // Si es salida, ajustamos el totalCost del movimiento para el log basado en el valor consumido
                $actualMovementTotalCost = $valueBefore - $newValue;
            }

            $newAvgCost = $newStock > 0 ? ($newValue / $newStock) : 0;

            // Actualizar variante
            $variant->current_stock = max(0, $newStock);
            $variant->current_value = max(0, $newValue);
            $variant->average_cost = $newAvgCost;

            if ($type === MovementType::ENTRY) {
                $variant->last_purchase_cost = $unitCost;
            }

            $variant->save();

            // Crear movimiento
            $movement = InventoryMovement::create([
                'uuid' => (string) Str::uuid(),
                'material_variant_id' => $variantId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $actualMovementTotalCost,
                'stock_before' => $stockBefore,
                'stock_after' => $variant->current_stock,
                'average_cost_before' => $avgCostBefore,
                'average_cost_after' => $variant->average_cost,
                'value_before' => $valueBefore,
                'value_after' => $variant->current_value,
                'notes' => $notes,
                'created_by' => $finalUserId,
            ]);

            Log::channel('inventory')->info('Movimiento de inventario registrado', [
                'movement_id' => $movement->id,
                'variant_id' => $variantId,
                'type' => $type->value,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'stock_before' => $stockBefore,
                'stock_after' => $variant->current_stock,
                'user_id' => $finalUserId,
            ]);

            return $movement;
        }, 3);
    }

    /**
     * Calcular nuevo valor según método de costeo
     */
    protected function calculateNewValue(
        MaterialVariant $variant,
        MovementType $type,
        float $quantity,
        float $unitCost
    ): float {
        $costingMethod = SystemSetting::getValue('inventory_costing_method', 'average');

        if ($type->affectsStock() > 0) {
            // Entrada: siempre suma al valor
            return $variant->current_value + ($quantity * $unitCost);
        }

        // Salida: usar costo según método
        return match ($costingMethod) {
            'average' => $variant->current_value - ($quantity * $variant->average_cost),
            'last_cost' => $variant->current_value - ($quantity * $variant->last_purchase_cost),
            default => $variant->current_value - ($quantity * $variant->average_cost),
        };
    }

    /**
     * Obtener historial de movimientos de una variante
     */
    public function getMovementHistory(int $variantId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryMovement::where('material_variant_id', $variantId)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Validar que hay stock suficiente
     */
    public function validateStock(int $variantId, float $requiredQuantity): bool
    {
        $variant = MaterialVariant::find($variantId);

        if (!$variant) {
            return false;
        }

        return $variant->current_stock >= $requiredQuantity;
    }
}
