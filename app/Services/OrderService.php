<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Enums\MovementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    // === CREAR PEDIDO CON ITEMS Y PAGO INICIAL ===
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Crear pedido base
            $order = Order::create([
                'order_parent_id' => $data['order_parent_id'] ?? null,
                'cliente_id' => $data['cliente_id'],
                'client_measurement_id' => $data['client_measurement_id'] ?? null,
                'urgency_level' => $data['urgency_level'] ?? Order::URGENCY_NORMAL,
                'promised_date' => $data['promised_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'requires_invoice' => $data['requires_invoice'] ?? false,
                'status' => Order::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            // Crear items con snapshots
            $this->syncOrderItems($order, $data['items']);

            // Calcular y guardar fecha mínima
            $this->calculateAndSetMinimumDate($order);

            // Registrar pago inicial si existe
            if ($this->shouldRecordPayment($data)) {
                $this->recordInitialPayment($order, $data);
            }

            // Recalcular totales
            $order->recalculateTotals();

            return $order->fresh();
        });
    }

    // === CREAR PEDIDO ANEXO (SUB-PEDIDO VINCULADO) ===
    public function createAnnexOrder(Order $parentOrder, array $data): Order
    {
        if (!$parentOrder->isInProduction()) {
            throw new \Exception('Solo se pueden crear anexos para pedidos en producción.');
        }

        $data['order_parent_id'] = $parentOrder->id;
        $data['cliente_id'] = $parentOrder->cliente_id;

        return $this->createOrder($data);
    }

    // === DETERMINAR TIPO DE ANEXO PERMITIDO ===
    public function determineAnnexType(Order $order): string
    {
        // REGLA: El sistema decide, no el usuario
        if ($order->status === Order::STATUS_CONFIRMED) {
            return 'item'; // Puede agregar items directamente
        }

        if ($order->status === Order::STATUS_IN_PRODUCTION) {
            // Producción temprana: verificar si hay items sin iniciar
            $pendingItems = $order->items()->where('status', OrderItem::STATUS_PENDING)->count();
            $totalItems = $order->items()->count();

            // Si más del 50% de items están pendientes = producción temprana
            if ($totalItems > 0 && ($pendingItems / $totalItems) > 0.5) {
                return 'item'; // Todavía puede anexar items
            }

            return 'order'; // Producción avanzada = crear sub-pedido
        }

        // Ready, Delivered, etc = solo sub-pedido
        return 'order';
    }

    // === AGREGAR ITEMS ANEXOS AL PEDIDO EXISTENTE ===
    public function addAnnexItems(Order $order, array $items): array
    {
        $annexType = $this->determineAnnexType($order);

        if ($annexType !== 'item') {
            throw new \Exception('Este pedido no permite agregar items. Debe crear un pedido anexo.');
        }

        return DB::transaction(function () use ($order, $items) {
            $createdItems = [];

            foreach ($items as $itemData) {
                $product = Product::with('primaryImage')->find($itemData['product_id']);
                $variant = isset($itemData['product_variant_id']) && $itemData['product_variant_id']
                    ? $product->variants->find($itemData['product_variant_id'])
                    : null;

                $subtotal = $itemData['unit_price'] * $itemData['quantity'];

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    'product_name' => $product->name,
                    'variant_sku' => $variant?->sku_variant,
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'total' => $subtotal,
                    'embroidery_text' => $itemData['embroidery_text'] ?? null,
                    'customization_notes' => $itemData['customization_notes'] ?? null,
                    'is_annex' => true,
                    'annexed_at' => now(),
                    'status' => OrderItem::STATUS_PENDING,
                ]);

                $createdItems[] = $item;
            }

            // Recalcular totales del pedido
            $order->recalculateTotals();

            // Recalcular fecha mínima
            $this->calculateAndSetMinimumDate($order);

            return $createdItems;
        });
    }

    // === VERIFICAR SI PUEDE AGREGAR ITEMS ===
    public function canAddItems(Order $order): bool
    {
        return in_array($this->determineAnnexType($order), ['item']);
    }

    // === VERIFICAR SI PUEDE CREAR SUB-PEDIDO ===
    public function canCreateAnnexOrder(Order $order): bool
    {
        return $order->isInProduction() && !$order->isAnnex();
    }

    // === TRIGGER: PASAR A PRODUCCIÓN ===
    public function triggerProduction(Order $order): void
    {
        if ($order->status !== Order::STATUS_CONFIRMED) {
            throw new \Exception('Solo pedidos confirmados pueden pasar a producción.');
        }

        DB::transaction(function () use ($order) {
            // Deducir materiales del inventario
            foreach ($order->items as $item) {
                $this->deductMaterialsForItem($item);
            }

            // Actualizar estado
            $order->update([
                'status' => Order::STATUS_IN_PRODUCTION,
                'updated_by' => Auth::id(),
            ]);
        });
    }

    // === SINCRONIZAR ITEMS CON SNAPSHOTS ===
    protected function syncOrderItems(Order $order, array $items): void
    {
        foreach ($items as $itemData) {
            $product = Product::with('primaryImage')->find($itemData['product_id']);
            $variant = isset($itemData['product_variant_id']) && $itemData['product_variant_id']
                ? $product->variants->find($itemData['product_variant_id'])
                : null;

            $subtotal = $itemData['unit_price'] * $itemData['quantity'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $itemData['product_id'],
                'product_variant_id' => $itemData['product_variant_id'] ?? null,
                'product_name' => $product->name,
                'variant_sku' => $variant?->sku_variant,
                'unit_price' => $itemData['unit_price'],
                'quantity' => $itemData['quantity'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'embroidery_text' => $itemData['embroidery_text'] ?? null,
                'customization_notes' => $itemData['customization_notes'] ?? null,
            ]);
        }
    }

    // === CALCULAR Y GUARDAR FECHA MÍNIMA ===
    protected function calculateAndSetMinimumDate(Order $order): void
    {
        $minimumDate = $order->calculateMinimumDate();
        $order->update(['minimum_date' => $minimumDate]);
    }

    // === REGISTRAR PAGO INICIAL ===
    protected function recordInitialPayment(Order $order, array $data): void
    {
        $amount = $data['initial_payment'] ?? 0;

        // Si se marcó pago completo, se calculará después del recálculo de totales
        if (!empty($data['pay_full'])) {
            // Pago completo: se registrará el total después
            // Por ahora guardamos el método, el monto se ajustará
            $order->_pendingFullPayment = true;
            $order->_paymentMethod = $data['payment_method'] ?? 'cash';
            return;
        }

        if ($amount > 0) {
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['payment_reference'] ?? null,
                'received_by' => Auth::id(),
            ]);
        }
    }

    // === DEDUCIR MATERIALES DE UN ITEM ===
    protected function deductMaterialsForItem(OrderItem $item): void
    {
        $product = $item->product;

        // Verificar si el producto tiene materiales asociados
        if (!$product->relationLoaded('materials')) {
            $product->load('materials');
        }

        foreach ($product->materials as $materialVariant) {
            $requiredQty = $materialVariant->pivot->quantity * $item->quantity;

            // Registrar movimiento de salida
            if ($requiredQty > 0 && $materialVariant->current_stock >= $requiredQty) {
                $stockBefore = $materialVariant->current_stock;
                $avgCostBefore = $materialVariant->average_cost;
                $valueBefore = $materialVariant->current_value;

                // Reducir stock
                $materialVariant->reduceStock($requiredQty);

                // Crear registro de movimiento
                InventoryMovement::create([
                    'material_variant_id' => $materialVariant->id,
                    'type' => MovementType::EXIT,
                    'quantity' => $requiredQty,
                    'unit_cost' => $avgCostBefore,
                    'total_cost' => $requiredQty * $avgCostBefore,
                    'stock_before' => $stockBefore,
                    'stock_after' => $materialVariant->current_stock,
                    'average_cost_before' => $avgCostBefore,
                    'average_cost_after' => $materialVariant->average_cost,
                    'value_before' => $valueBefore,
                    'value_after' => $materialVariant->current_value,
                    'reference_type' => 'order_item',
                    'reference_id' => $item->id,
                    'notes' => "Pedido {$item->order->order_number}",
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }

    // === VERIFICAR SI DEBE REGISTRAR PAGO ===
    protected function shouldRecordPayment(array $data): bool
    {
        if (empty($data['payment_method'])) {
            return false;
        }

        return !empty($data['initial_payment']) || !empty($data['pay_full']);
    }
}
