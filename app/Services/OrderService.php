<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAdjustment;
use App\Models\OrderPayment;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\ProductVariant;
use App\Models\ProductVariantReservation;
use App\Models\ClientMeasurementHistory;
use App\Models\PersonalizationTimeMultiplier;
use App\Enums\MovementType;
use App\Events\OrderStatusChanged;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderService
{
    // === CREAR PEDIDO CON ITEMS Y PAGO INICIAL ===
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Crear pedido base
            // NOTA: cliente_id puede ser NULL para producción para stock (for_stock=true)
            $order = Order::create([
                'order_parent_id' => $data['order_parent_id'] ?? null,
                'related_order_id' => $data['related_order_id'] ?? null, // POST-VENTA
                'cliente_id' => $data['cliente_id'] ?? null, // NULL = Producción para stock
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

            // Registrar pago inicial si existe
            if ($this->shouldRecordPayment($data)) {
                $this->recordInitialPayment($order, $data);
            }

            // Recalcular totales
            $order->recalculateTotals();

            // === EVENTO: PEDIDO CREADO ===
            OrderEvent::logCreated($order);

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

        $annexOrder = $this->createOrder($data);

        // === EVENTO: ANEXO CREADO ===
        OrderEvent::log(
            $parentOrder,
            OrderEvent::TYPE_ANNEX_CREATED,
            "Pedido anexo {$annexOrder->order_number} creado",
            ['annex_order_id' => $annexOrder->id, 'annex_order_number' => $annexOrder->order_number]
        );

        return $annexOrder;
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
    // Aplica la misma lógica de medidas por tipo de producto que syncOrderItems
    public function addAnnexItems(Order $order, array $items): array
    {
        $annexType = $this->determineAnnexType($order);

        if ($annexType !== 'item') {
            throw new \Exception('Este pedido no permite agregar items. Debe crear un pedido anexo.');
        }

        return DB::transaction(function () use ($order, $items) {
            $createdItems = [];

            // Obtener medidas del pedido para items que las requieran
            $orderMeasurementId = $order->client_measurement_id;

            foreach ($items as $itemData) {
                $product = Product::with(['primaryImage', 'category'])->find($itemData['product_id']);
                $variant = isset($itemData['product_variant_id']) && $itemData['product_variant_id']
                    ? $product->variants->find($itemData['product_variant_id'])
                    : null;

                $subtotal = $itemData['unit_price'] * $itemData['quantity'];

                // LÓGICA DE MEDIDAS: Decisión del PEDIDO, capacidad de CATEGORÍA
                // El item requiere medidas si el frontend las envió (decisión tomada en pedido)
                $requiresMeasurements = !empty($itemData['measurements']);
                $itemMeasurementId = null; // Legacy FK - medidas ahora son inline

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_variant_id' => $itemData['product_variant_id'] ?? null,
                    // SNAPSHOT del tipo de producto
                    'product_type_id' => $product->product_type_id,
                    'requires_measurements' => $requiresMeasurements,
                    'client_measurement_id' => $itemMeasurementId,
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

            // Recalcular totales del pedido (incluye fechas)
            $order->recalculateTotals();

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
    // REGLAS DE PRODUCCIÓN (R1-R8):
    // R1: Status debe ser 'confirmed'
    // R2: No puede haber ajustes pendientes
    // R3: Todos los items con diseño deben estar aprobados
    // R4: Las medidas no pueden haber cambiado después de aprobación del diseño
    // R5: Debe haber inventario disponible (físico - reservado) suficiente
    // R6: La reserva de inventario debe ser atómica con locks
    // R7: Debe ser concurrency-safe (SELECT FOR UPDATE)
    // R8: La transición debe quedar registrada con usuario y timestamp
    //
    // FLUJO CORRECTO:
    // - PRODUCCIÓN → RESERVA (no descuenta stock físico, bloquea disponibilidad)
    // - ENTREGA → CONSUMO (descuenta stock físico)
    // - CANCELACIÓN → LIBERA RESERVAS
    public function triggerProduction(Order $order): void
    {
        // === R1: VALIDAR STATUS ===
        if ($order->status !== Order::STATUS_CONFIRMED) {
            throw new \Exception('R1: Solo pedidos confirmados pueden pasar a producción.');
        }

        // === R2: VALIDAR AJUSTES PENDIENTES ===
        if ($order->hasPendingAdjustments()) {
            throw new \Exception('R2: No se puede pasar a producción: hay ajustes de precio pendientes de aprobación.');
        }

        // === R3: VALIDAR DISEÑOS APROBADOS ===
        if ($order->hasItemsPendingDesignApproval()) {
            $blockingItems = $order->getItemsBlockingForDesign();
            $itemNames = $blockingItems->pluck('product_name')->implode(', ');
            throw new \Exception("R3: No se puede pasar a producción: diseño pendiente de aprobación en: {$itemNames}");
        }

        // === R4: VALIDAR MEDIDAS POST-APROBACIÓN ===
        $itemsWithChangedMeasurements = $order->items()
            ->where('design_approved', true)
            ->get()
            ->filter(fn($item) => $item->hasMeasurementsChangedAfterApproval());

        if ($itemsWithChangedMeasurements->isNotEmpty()) {
            $itemNames = $itemsWithChangedMeasurements->pluck('product_name')->implode(', ');
            throw new \Exception("R4: No se puede pasar a producción: medidas modificadas después de aprobación en: {$itemNames}. Requiere re-aprobación del diseño.");
        }

        // === R5, R6, R7: VALIDAR Y RESERVAR INVENTARIO CON LOCKS ===
        // NOTA: El bloqueo por inventario se registra FUERA de la transacción
        // para que persista incluso si la transacción falla (rollback)
        try {
            DB::transaction(function () use ($order) {
                // Calcular requerimientos totales de materiales
                $materialRequirements = $this->calculateMaterialRequirements($order);

                // Validar DISPONIBILIDAD (físico - reservado) y bloquear con SELECT FOR UPDATE
                // Las variantes quedan bloqueadas con lockForUpdate() dentro de este método
                $this->validateAvailableInventory($materialRequirements);

                // === SNAPSHOT DE COSTO INTERNO (INMUTABLE) ===
                // Calcular costo de materiales usando average_cost vigente
                // Se registra ANTES de crear reservas para capturar el costo exacto
                $materialsCostSnapshot = $this->calculateMaterialsCostSnapshot($materialRequirements);

                // === FASE 3.5: SNAPSHOT DE COSTO DE BORDADO (PUNTADAS) ===
                // Calcular puntadas totales y costo de bordado usando tarifa vigente
                $totalStitches = $order->calculateTotalStitches();
                $costPerThousand = $order->getEmbroideryCostPerThousand();
                $embroideryCost = $order->calculateEmbroideryCost($costPerThousand);

                // CREAR RESERVAS DE MATERIALES (NO descuenta stock físico)
                foreach ($order->items as $item) {
                    $this->createReservationsForItem($item);
                }

                // === v2.2: RESERVAR STOCK DE PRODUCTOS TERMINADOS ===
                // Si hay stock disponible del ProductVariant, se RESERVA.
                // Si NO hay stock, el Order sigue como MAKE-TO-ORDER normal.
                $stockReservationsSummary = $this->reserveFinishedGoodsStock($order);

                // === R8: REGISTRAR TRANSICIÓN CON SNAPSHOTS ===
                $order->update([
                    'status' => Order::STATUS_IN_PRODUCTION,
                    'materials_cost_snapshot' => $materialsCostSnapshot,
                    'total_stitches_snapshot' => $totalStitches,
                    'embroidery_cost_snapshot' => $embroideryCost,
                    'cost_per_thousand_snapshot' => $costPerThousand,
                    'updated_by' => Auth::id(),
                ]);

                // === EVENTO: PRODUCCIÓN INICIADA ===
                $reservationsSummary = InventoryReservation::where('order_id', $order->id)
                    ->where('status', InventoryReservation::STATUS_RESERVED)
                    ->with('materialVariant.material')
                    ->get()
                    ->map(fn($r) => [
                        'material' => $r->materialVariant->display_name ?? 'N/A',
                        'quantity' => $r->quantity,
                    ])
                    ->toArray();

                // Incluir resumen de reservas de productos terminados en el evento
                $eventMetadata = [
                    'material_reservations' => $reservationsSummary,
                    'stock_reservations' => $stockReservationsSummary,
                ];

                OrderEvent::logProductionStarted($order, $eventMetadata);

                // === BROADCAST: Notificar cambio de estado en tiempo real ===
                event(new OrderStatusChanged(
                    $order,
                    Order::STATUS_CONFIRMED,
                    Order::STATUS_IN_PRODUCTION
                ));
            });
        } catch (\Exception $e) {
            // === PERSISTENCIA DE BLOQUEO POR INVENTARIO (FUERA DE TRANSACCIÓN) ===
            // El evento se guarda en una transacción separada (commit independiente)

            if ($e instanceof InsufficientInventoryException) {
                // Excepción tipada: incluir materiales faltantes en metadata
                OrderEvent::log(
                    $order,
                    OrderEvent::TYPE_PRODUCTION_BLOCKED,
                    $e->getMessage(),
                    [
                        'reason' => 'inventory_insufficient',
                        'missing_materials' => $e->getMissingMaterials(),
                        'blocked_at' => now()->toIso8601String(),
                        'user_id' => Auth::id(),
                    ]
                );
            } elseif (str_contains($e->getMessage(), 'Inventario insuficiente')) {
                // Fallback para compatibilidad
                OrderEvent::log(
                    $order,
                    OrderEvent::TYPE_PRODUCTION_BLOCKED,
                    $e->getMessage(),
                    [
                        'reason' => 'inventory_insufficient',
                        'blocked_at' => now()->toIso8601String(),
                        'user_id' => Auth::id(),
                    ]
                );
            }

            // Re-lanzar la excepción para que el controlador la maneje
            throw $e;
        }
    }

    // === TRIGGER: MARCAR LISTO (in_production → ready) ===
    // Al marcar listo: Reservas → Consumidas, Stock físico → Descontado
    // REGLA CANÓNICA: El inventario se DESCUENTA al marcar READY (producción terminada)
    public function triggerReady(Order $order): void
    {
        if ($order->status !== Order::STATUS_IN_PRODUCTION) {
            throw new \Exception('Solo pedidos en producción pueden marcarse como listos.');
        }

        DB::transaction(function () use ($order) {
            // Obtener todas las reservas activas del pedido
            $reservations = InventoryReservation::where('order_id', $order->id)
                ->where('status', InventoryReservation::STATUS_RESERVED)
                ->lockForUpdate()
                ->get();

            $consumedMaterials = [];

            foreach ($reservations as $reservation) {
                // Bloquear variante para actualización
                $variant = \App\Models\MaterialVariant::where('id', $reservation->material_variant_id)
                    ->lockForUpdate()
                    ->first();

                $stockBefore = $variant->current_stock;
                $avgCostBefore = $variant->average_cost;
                $valueBefore = $variant->current_value;

                // Reducir stock físico
                $variant->reduceStock($reservation->quantity);

                // Crear movimiento de salida
                InventoryMovement::create([
                    'material_variant_id' => $variant->id,
                    'type' => MovementType::EXIT,
                    'quantity' => $reservation->quantity,
                    'unit_cost' => $avgCostBefore,
                    'total_cost' => $reservation->quantity * $avgCostBefore,
                    'stock_before' => $stockBefore,
                    'stock_after' => $variant->current_stock,
                    'average_cost_before' => $avgCostBefore,
                    'average_cost_after' => $variant->average_cost,
                    'value_before' => $valueBefore,
                    'value_after' => $variant->current_value,
                    'reference_type' => 'order_ready',
                    'reference_id' => $order->id,
                    'notes' => "Producción completada - Pedido {$order->order_number}",
                    'created_by' => Auth::id(),
                ]);

                // Marcar reserva como consumida
                $reservation->markConsumed(Auth::id());

                $consumedMaterials[] = [
                    'material_variant_id' => $reservation->material_variant_id,
                    'quantity' => $reservation->quantity,
                    'material_name' => $variant->display_name,
                    'source_type' => $reservation->source_type,
                    'source_id' => $reservation->source_id,
                ];
            }

            // === v2.2: CONSUMIR RESERVAS DE PRODUCTOS TERMINADOS ===
            $consumedProducts = $this->consumeFinishedGoodsReservations($order);

            // Actualizar estado del pedido
            $order->update([
                'status' => Order::STATUS_READY,
                'updated_by' => Auth::id(),
            ]);

            // === EVENTO: PRODUCCIÓN COMPLETADA, INVENTARIO CONSUMIDO ===
            OrderEvent::log(
                $order,
                OrderEvent::TYPE_READY,
                "Producción completada. Materiales consumidos del inventario.",
                [
                    'consumed_materials' => $consumedMaterials,
                    'consumed_products' => $consumedProducts,
                ]
            );

            // === BROADCAST: Notificar producción completada ===
            event(new OrderStatusChanged(
                $order,
                Order::STATUS_IN_PRODUCTION,
                Order::STATUS_READY
            ));
        });
    }

    /**
     * Consume las reservas de productos terminados al marcar READY.
     * REGLA v2.2: Reduce AMBOS current_stock Y reserved_stock.
     *
     * @param Order $order
     * @return array Resumen de productos consumidos
     */
    protected function consumeFinishedGoodsReservations(Order $order): array
    {
        $consumedProducts = [];

        // Obtener reservas activas de productos terminados
        $stockReservations = ProductVariantReservation::where('order_id', $order->id)
            ->where('status', ProductVariantReservation::STATUS_RESERVED)
            ->lockForUpdate()
            ->get();

        foreach ($stockReservations as $reservation) {
            // Bloquear variante para actualización
            $variant = ProductVariant::where('id', $reservation->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                continue;
            }

            $stockBefore = $variant->current_stock;

            // Consumir stock reservado (reduce current_stock Y reserved_stock)
            $variant->consumeReservedStock($reservation->quantity);

            // Marcar reserva como consumida
            $reservation->markConsumed(Auth::id());

            // Registrar movimiento de productos terminados
            \App\Models\FinishedGoodsMovement::create([
                'product_variant_id' => $variant->id,
                'type' => \App\Models\FinishedGoodsMovement::TYPE_SALE_EXIT,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'quantity' => $reservation->quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $variant->current_stock,
                'notes' => "Producción completada - Pedido {$order->order_number}",
                'created_by' => Auth::id(),
            ]);

            $consumedProducts[] = [
                'product_variant_id' => $variant->id,
                'sku' => $variant->sku_variant,
                'quantity' => $reservation->quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $variant->current_stock,
            ];
        }

        return $consumedProducts;
    }

    /**
     * Libera las reservas de productos terminados al cancelar un Order.
     * REGLA v2.2: Devuelve la cantidad a available_stock reduciendo reserved_stock.
     *
     * @param Order $order
     * @return int Cantidad de reservas liberadas
     */
    protected function releaseFinishedGoodsReservations(Order $order): int
    {
        $releasedCount = 0;

        // Obtener reservas activas de productos terminados
        $stockReservations = ProductVariantReservation::where('order_id', $order->id)
            ->where('status', ProductVariantReservation::STATUS_RESERVED)
            ->lockForUpdate()
            ->get();

        foreach ($stockReservations as $reservation) {
            // Bloquear variante para actualización
            $variant = ProductVariant::where('id', $reservation->product_variant_id)
                ->lockForUpdate()
                ->first();

            if ($variant) {
                // Liberar stock reservado (devuelve a available_stock)
                $variant->releaseReservedStock($reservation->quantity);
            }

            // Marcar reserva como liberada
            $reservation->markReleased();
            $releasedCount++;
        }

        return $releasedCount;
    }

    // =========================================================================
    // === TRIGGER: ENTREGAR PEDIDO (ready → delivered) — CIERRE FINAL v2.3 ===
    // =========================================================================
    //
    // REGLAS ERP SELLADAS:
    // - Entregar ≠ producir (producción ya terminó en READY)
    // - Entregar ≠ consumir stock (ya ocurrió en triggerReady)
    // - Entregar = cierre logístico y contable DEFINITIVO
    // - Un Order DELIVERED es INMUTABLE (estado terminal)
    //
    // BIFURCACIÓN POR TIPO DE PEDIDO v2.6:
    // - SALE: Solo cierre logístico (entrega a cliente)
    // - STOCK_PRODUCTION: Entrada a inventario PT + cierre
    //
    // PROTECCIONES:
    // - Solo READY puede transicionar a DELIVERED
    // - Pedidos ya entregados NO pueden re-entregarse
    // - El timestamp delivered_date es ÚNICO e inmutable
    //
    public function triggerDelivery(Order $order): void
    {
        // === GATE v2.3: VALIDACIÓN ESTRICTA ===

        // Protección contra re-entrega (idempotencia)
        if ($order->isDelivered()) {
            throw new \Exception(
                "El pedido {$order->order_number} ya fue entregado el " .
                $order->delivered_date?->format('d/m/Y') . ". " .
                "Un pedido entregado es INMUTABLE y no puede re-procesarse."
            );
        }

        // Protección contra entrega desde estado inválido
        if (!$order->canBeDelivered()) {
            throw new \Exception(
                "Solo pedidos en estado READY pueden ser entregados. " .
                "Estado actual: {$order->status_label}."
            );
        }

        DB::transaction(function () use ($order) {
            // =================================================================
            // STOCK_PRODUCTION: Entrada a inventario de producto terminado
            // =================================================================
            if ($order->isStockProduction()) {
                $this->registerFinishedGoodsEntry($order);
            }

            // === CIERRE DEFINITIVO ===
            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'delivered_date' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Refrescar para obtener valores actualizados
            $order->refresh();

            // === EVENTO: PEDIDO ENTREGADO (CIERRE FINAL v2.3) ===
            OrderEvent::logDelivered($order);

            // === v2.5: CIERRE CONTABLE SI ESTÁ TOTALMENTE PAGADO ===
            // Si el pedido está DELIVERED + PAID → cierre contable automático
            if ($order->isFinanciallyClosed()) {
                OrderEvent::logFinanciallyClosed($order);
            }

            // === BROADCAST: Notificar entrega ===
            event(new OrderStatusChanged(
                $order,
                Order::STATUS_READY,
                Order::STATUS_DELIVERED
            ));
        });
    }

    // =========================================================================
    // === STOCK_PRODUCTION: Entrada a inventario de producto terminado ===
    // =========================================================================
    //
    // REGLA ERP: Cuando un pedido de producción para stock se completa,
    // los productos fabricados INGRESAN al inventario de producto terminado.
    //
    // FUENTE DE VERDAD ÚNICA: finished_goods_movements (ledger)
    // El stock se calcula: SUM(entradas) - SUM(salidas)
    // NO se actualiza ProductVariant.current_stock directamente.
    //
    protected function registerFinishedGoodsEntry(Order $order): void
    {
        // Cargar items con sus variantes
        $order->load(['items.variant']);

        foreach ($order->items as $item) {
            // Solo procesar items con variante definida
            if (!$item->product_variant_id || !$item->variant) {
                continue;
            }

            // === IDEMPOTENCIA: Verificar movimiento previo ===
            $existingMovement = \App\Models\FinishedGoodsMovement::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('product_variant_id', $item->product_variant_id)
                ->where('type', \App\Models\FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY)
                ->exists();

            if ($existingMovement) {
                Log::warning("STOCK_PRODUCTION: Movimiento ya existe para Order #{$order->id}, Variant #{$item->product_variant_id}");
                continue;
            }

            // === CALCULAR STOCK DESDE LEDGER ===
            $quantityToAdd = (float) $item->quantity;

            // Stock antes = SUM(entradas) - SUM(salidas) del ledger
            $stockBefore = (float) \App\Models\FinishedGoodsMovement::where('product_variant_id', $item->product_variant_id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('production_entry', 'return') THEN quantity ELSE 0 END), 0) -
                             COALESCE(SUM(CASE WHEN type = 'sale_exit' THEN quantity ELSE 0 END), 0) as stock")
                ->value('stock');

            $stockAfter = $stockBefore + $quantityToAdd;

            // === REGISTRAR MOVIMIENTO EN LEDGER (ÚNICA FUENTE DE VERDAD) ===
            \App\Models\FinishedGoodsMovement::create([
                'product_variant_id' => $item->product_variant_id,
                'type' => \App\Models\FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'quantity' => $quantityToAdd,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => "Producción para stock - Pedido {$order->order_number}",
                'created_by' => Auth::id(),
            ]);

            Log::info("STOCK_PRODUCTION: Entrada de {$quantityToAdd} unidades. " .
                "Stock: {$stockBefore} → {$stockAfter}. ProductVariant #{$item->product_variant_id}. Pedido: {$order->order_number}");
        }
    }

    // =========================================================================
    // === CIERRE CANÓNICO: CANCELACIÓN DE PEDIDO ===
    // =========================================================================
    //
    // DEFINICIÓN: Cancelar pedido es un ACTO ADMINISTRATIVO que:
    // - Marca el pedido como CANCELLED
    // - Registra auditoría completa (quién, cuándo, por qué)
    // - Libera reservas de inventario (NO descuenta stock)
    //
    // PROHIBIDO:
    // - NO genera merma automática
    // - NO revierte movimientos de inventario
    // - NO borra nada (soft delete via status)
    //
    // ESTADOS PERMITIDOS: DRAFT, CONFIRMED, IN_PRODUCTION
    // ESTADOS PROHIBIDOS: DELIVERED, CANCELLED
    //
    public function cancelOrder(Order $order, string $reason): void
    {
        // === GATE: Validar que el pedido puede cancelarse ===
        if (!$order->canCancel()) {
            throw new \Exception(
                $order->getCancelBlockReason() ?? 'El pedido no puede cancelarse.'
            );
        }

        // Capturar estado previo para auditoría
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $reason, $previousStatus) {
            // === LIBERAR RESERVAS DE MATERIALES (NO descuenta stock) ===
            $materialReservations = InventoryReservation::where('order_id', $order->id)
                ->where('status', InventoryReservation::STATUS_RESERVED)
                ->get();

            $releasedMaterialCount = 0;
            foreach ($materialReservations as $reservation) {
                $reservation->markReleased();
                $releasedMaterialCount++;
            }

            // === LIBERAR RESERVAS DE PRODUCTOS TERMINADOS ===
            $releasedProductCount = $this->releaseFinishedGoodsReservations($order);

            // === ACTUALIZAR PEDIDO CON AUDITORÍA COMPLETA ===
            $order->status = Order::STATUS_CANCELLED;
            $order->cancelled_at = now();
            $order->cancelled_by = Auth::id();
            $order->cancel_reason = $reason;
            $order->updated_by = Auth::id();
            $order->saveQuietly();

            // === EVENTO: PEDIDO CANCELADO CON METADATA COMPLETA ===
            OrderEvent::log(
                $order,
                OrderEvent::TYPE_CANCELLED,
                "Pedido cancelado: {$reason}",
                [
                    'reason' => $reason,
                    'previous_status' => $previousStatus,
                    'cancelled_by' => Auth::id(),
                    'cancelled_at' => now()->toIso8601String(),
                    'released_material_reservations' => $releasedMaterialCount,
                    'released_product_reservations' => $releasedProductCount,
                    // NOTA EXPLÍCITA: La cancelación NO genera merma
                    'generates_waste' => false,
                    'reverts_inventory' => false,
                ]
            );

            // === BROADCAST: Notificar cancelación ===
            event(new OrderStatusChanged(
                $order,
                $previousStatus,
                Order::STATUS_CANCELLED
            ));
        });
    }

    /**
     * Calcula los requerimientos totales de materiales para un pedido.
     * INCLUYE: Materiales del producto base + Materiales de extras con inventario.
     *
     * @return array [material_variant_id => required_quantity]
     */
    protected function calculateMaterialRequirements(Order $order): array
    {
        $requirements = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            // === MATERIALES DEL PRODUCTO BASE ===
            if (!$product->relationLoaded('materials')) {
                $product->load('materials');
            }

            foreach ($product->materials as $materialVariant) {
                $requiredQty = $materialVariant->pivot->quantity * $item->quantity;
                $variantId = $materialVariant->id;

                if (!isset($requirements[$variantId])) {
                    $requirements[$variantId] = 0;
                }
                $requirements[$variantId] += $requiredQty;
            }

            // === MATERIALES DE EXTRAS CON INVENTARIO ===
            $extraRequirements = $this->calculateExtraMaterialRequirements($item);
            foreach ($extraRequirements as $variantId => $requiredQty) {
                if (!isset($requirements[$variantId])) {
                    $requirements[$variantId] = 0;
                }
                $requirements[$variantId] += $requiredQty;
            }
        }

        return $requirements;
    }

    /**
     * Calcula los requerimientos de materiales de extras para un item.
     * Solo considera extras con consumes_inventory = true.
     *
     * @param OrderItem $item
     * @return array [material_variant_id => required_quantity]
     */
    protected function calculateExtraMaterialRequirements(OrderItem $item): array
    {
        $requirements = [];
        $product = $item->product;

        // Cargar extras con sus materiales
        if (!$product->relationLoaded('extras')) {
            $product->load(['extras.materials']);
        }

        foreach ($product->extras as $extra) {
            // Solo procesar extras que consumen inventario
            if (!$extra->consumesInventory()) {
                continue;
            }

            // Obtener requerimientos del extra multiplicados por cantidad del item
            $extraReqs = $extra->getMaterialRequirements($item->quantity);

            foreach ($extraReqs as $variantId => $requiredQty) {
                if (!isset($requirements[$variantId])) {
                    $requirements[$variantId] = 0;
                }
                $requirements[$variantId] += $requiredQty;
            }
        }

        return $requirements;
    }

    /**
     * Valida DISPONIBILIDAD de inventario (físico - reservado) con lock FOR UPDATE.
     * REGLA: TODO O NADA - si falta un material, NO se reserva nada.
     *
     * @throws \Exception Si el inventario disponible es insuficiente
     */
    protected function validateAvailableInventory(array $requirements): void
    {
        if (empty($requirements)) {
            return;
        }

        // Cargar variantes con lock FOR UPDATE para evitar race conditions
        $materialVariants = \App\Models\MaterialVariant::whereIn('id', array_keys($requirements))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Obtener reservas activas por material
        $activeReservations = InventoryReservation::whereIn('material_variant_id', array_keys($requirements))
            ->where('status', InventoryReservation::STATUS_RESERVED)
            ->selectRaw('material_variant_id, SUM(quantity) as total_reserved')
            ->groupBy('material_variant_id')
            ->pluck('total_reserved', 'material_variant_id');

        $insufficientMaterials = [];

        foreach ($requirements as $variantId => $requiredQty) {
            $variant = $materialVariants->get($variantId);

            if (!$variant) {
                $insufficientMaterials[] = "Material ID {$variantId} no encontrado";
                continue;
            }

            $reserved = (float) $activeReservations->get($variantId, 0);
            $available = $variant->current_stock - $reserved;

            if ($available < $requiredQty) {
                $missing = round($requiredQty - $available, 2);
                $unit = $variant->material?->category?->baseUnit?->symbol ?? '';
                $insufficientMaterials[] = "{$variant->display_name}: faltan {$missing} {$unit}";
            }
        }

        if (!empty($insufficientMaterials)) {
            throw new InsufficientInventoryException($insufficientMaterials);
        }
    }

    /**
     * Calcula el costo total de materiales usando average_cost vigente.
     * SNAPSHOT INMUTABLE: Se registra al iniciar producción y NO se recalcula.
     *
     * @param array $requirements [material_variant_id => required_quantity]
     * @return float Costo total de materiales
     */
    protected function calculateMaterialsCostSnapshot(array $requirements): float
    {
        if (empty($requirements)) {
            return 0.0;
        }

        // Las variantes ya están cargadas con lock en validateAvailableInventory()
        // Pero las recargamos para obtener average_cost actualizado
        $materialVariants = \App\Models\MaterialVariant::whereIn('id', array_keys($requirements))
            ->get()
            ->keyBy('id');

        $totalCost = 0.0;

        foreach ($requirements as $variantId => $requiredQty) {
            $variant = $materialVariants->get($variantId);
            if ($variant) {
                $totalCost += (float) $requiredQty * (float) $variant->average_cost;
            }
        }

        return round($totalCost, 4);
    }

    /**
     * Crea reservas de inventario para un item (NO descuenta stock físico).
     * INCLUYE: Materiales del producto base + Materiales de extras con inventario.
     * TRAZABILIDAD: Cada reserva indica su origen (source_type + source_id).
     */
    protected function createReservationsForItem(OrderItem $item): void
    {
        $product = $item->product;

        // === RESERVAS DE MATERIALES DEL PRODUCTO BASE ===
        if (!$product->relationLoaded('materials')) {
            $product->load('materials');
        }

        foreach ($product->materials as $materialVariant) {
            $requiredQty = $materialVariant->pivot->quantity * $item->quantity;

            if ($requiredQty <= 0) {
                continue;
            }

            // Crear reserva con trazabilidad: PRODUCTO
            InventoryReservation::create([
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'material_variant_id' => $materialVariant->id,
                'source_type' => InventoryReservation::SOURCE_PRODUCT,
                'source_id' => $product->id,
                'quantity' => $requiredQty,
                'status' => InventoryReservation::STATUS_RESERVED,
                'created_by' => Auth::id(),
            ]);
        }

        // === RESERVAS DE MATERIALES DE EXTRAS CON INVENTARIO ===
        if (!$product->relationLoaded('extras')) {
            $product->load(['extras.materials']);
        }

        foreach ($product->extras as $extra) {
            // Solo procesar extras que consumen inventario
            if (!$extra->consumesInventory()) {
                continue;
            }

            // Obtener requerimientos del extra multiplicados por cantidad del item
            $extraRequirements = $extra->getMaterialRequirements($item->quantity);

            foreach ($extraRequirements as $variantId => $requiredQty) {
                if ($requiredQty <= 0) {
                    continue;
                }

                // Crear reserva con trazabilidad: EXTRA
                InventoryReservation::create([
                    'order_id' => $item->order_id,
                    'order_item_id' => $item->id,
                    'material_variant_id' => $variantId,
                    'source_type' => InventoryReservation::SOURCE_EXTRA,
                    'source_id' => $extra->id,
                    'quantity' => $requiredQty,
                    'status' => InventoryReservation::STATUS_RESERVED,
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }

    // =========================================================================
    // === v2.2: RESERVA DE STOCK DE PRODUCTOS TERMINADOS ===
    // =========================================================================

    /**
     * Reserva stock de productos terminados para un Order.
     *
     * REGLAS ERP v2.2:
     * - Reservar ≠ descontar (current_stock NO cambia, reserved_stock SÍ)
     * - Si NO hay stock suficiente, el Order sigue como MAKE-TO-ORDER normal
     * - Un OrderItem solo puede tener UNA reserva (idempotencia por unique constraint)
     * - NO bloquea producción si no hay stock
     *
     * @param Order $order
     * @return array Resumen de reservas creadas
     */
    protected function reserveFinishedGoodsStock(Order $order): array
    {
        $reservationsSummary = [];

        foreach ($order->items as $item) {
            // Solo procesar items que tienen ProductVariant asociado
            if (!$item->product_variant_id) {
                continue;
            }

            // IDEMPOTENCIA: Verificar que no exista ya una reserva para este item
            if ($item->hasActiveStockReservation()) {
                continue;
            }

            // Obtener variante con lock FOR UPDATE
            $variant = ProductVariant::where('id', $item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                continue;
            }

            // Verificar si hay stock disponible
            $requiredQty = (float) $item->quantity;

            if (!$variant->canReserve($requiredQty)) {
                // NO hay stock suficiente → Order sigue como MAKE-TO-ORDER
                // NO es un error, simplemente no se reserva
                $reservationsSummary[] = [
                    'order_item_id' => $item->id,
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku_variant,
                    'requested' => $requiredQty,
                    'available' => $variant->available_stock,
                    'reserved' => false,
                    'reason' => 'insufficient_stock',
                ];
                continue;
            }

            // RESERVAR stock (actualiza reserved_stock en ProductVariant)
            $variant->reserveStock($requiredQty);

            // Crear registro de reserva
            ProductVariantReservation::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'product_variant_id' => $variant->id,
                'quantity' => $requiredQty,
                'status' => ProductVariantReservation::STATUS_RESERVED,
                'created_by' => Auth::id(),
            ]);

            $reservationsSummary[] = [
                'order_item_id' => $item->id,
                'product_variant_id' => $variant->id,
                'sku' => $variant->sku_variant,
                'quantity' => $requiredQty,
                'reserved' => true,
            ];
        }

        return $reservationsSummary;
    }

    // === SINCRONIZAR ITEMS CON SNAPSHOTS ===
    // IMPORTANTE: Aquí se aplica la lógica de medidas por tipo de producto
    // - Si product_type.requires_measurements = TRUE → asignar client_measurement_id del pedido
    // - Si product_type.requires_measurements = FALSE → FORZAR client_measurement_id = NULL
    // FASE 3: Expuesto como público para update de pedidos
    public function syncOrderItemsPublic(Order $order, array $items): void
    {
        $this->syncOrderItems($order, $items);
    }

    protected function syncOrderItems(Order $order, array $items): void
    {
        // Obtener medidas del pedido para items que las requieran (legacy FK)
        $orderMeasurementId = $order->client_measurement_id;

        foreach ($items as $itemData) {
            $product = Product::with(['primaryImage', 'category'])->find($itemData['product_id']);
            $variant = isset($itemData['product_variant_id']) && $itemData['product_variant_id']
                ? $product->variants->find($itemData['product_variant_id'])
                : null;

            $subtotal = $itemData['unit_price'] * $itemData['quantity'];

            // LÓGICA DE MEDIDAS: Decisión del PEDIDO, capacidad de CATEGORÍA
            // El item requiere medidas si el frontend las envió (decisión tomada en pedido)
            $inlineMeasurements = !empty($itemData['measurements']) ? $itemData['measurements'] : null;
            $requiresMeasurements = !empty($inlineMeasurements);

            // FK a client_measurements (legacy - ya no se usa)
            $itemMeasurementId = null;

            // FASE 2: Calcular estado inicial basado en medidas
            // REGLA: requires_measurements=true Y measurements=NULL → PENDING
            //        Cualquier otro caso → PENDING (listo para producción)
            $initialStatus = OrderItem::STATUS_PENDING;

            // === CALCULAR TIPO DE PERSONALIZACIÓN Y TIEMPO ESTIMADO ===
            $personalizationType = PersonalizationTimeMultiplier::determineType([
                'embroidery_text' => $itemData['embroidery_text'] ?? null,
                'measurements' => $inlineMeasurements,
                'customization_notes' => $itemData['customization_notes'] ?? null,
            ]);

            $timeMultiplier = PersonalizationTimeMultiplier::getMultiplier($personalizationType);
            $baseLeadTime = $product->production_lead_time ?? 0;
            $estimatedLeadTime = (int) ceil($baseLeadTime * $timeMultiplier);

            // Diseño requiere aprobación explícita
            $designApproved = $personalizationType !== OrderItem::PERSONALIZATION_DESIGN;

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $itemData['product_id'],
                'product_variant_id' => $itemData['product_variant_id'] ?? null,
                // SNAPSHOT del tipo de producto para auditoría histórica
                'product_type_id' => $product->product_type_id,
                'requires_measurements' => $requiresMeasurements,
                // Medidas: prioridad inline > FK
                'client_measurement_id' => $itemMeasurementId,
                'measurements' => $inlineMeasurements, // JSON inline (FASE 1)
                'product_name' => $product->name,
                'variant_sku' => $variant?->sku_variant,
                'unit_price' => $itemData['unit_price'],
                'quantity' => $itemData['quantity'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'embroidery_text' => $itemData['embroidery_text'] ?? null,
                'customization_notes' => $itemData['customization_notes'] ?? null,
                'status' => $initialStatus,
                // === CAMPOS DE PERSONALIZACIÓN Y TIEMPO ===
                'personalization_type' => $personalizationType,
                'design_approved' => $designApproved,
                'time_multiplier_snapshot' => $timeMultiplier,
                'estimated_lead_time' => $estimatedLeadTime,
            ]);

            // === HISTORIAL DE MEDIDAS ===
            // Si tiene medidas inline y el usuario solicitó guardar en perfil del cliente
            // CANÓNICO: Solo si hay cliente (producción para stock NO guarda historial de cliente)
            $saveToClient = $inlineMeasurements['save_to_client'] ?? false;
            $hasCliente = $order->cliente_id !== null;
            if ($requiresMeasurements && !empty($inlineMeasurements) && $saveToClient && $hasCliente) {
                $measurementHistory = $this->saveMeasurementHistory(
                    $order,
                    $orderItem,
                    $product,
                    $inlineMeasurements
                );
                // Vincular el historial al item
                $orderItem->update(['measurement_history_id' => $measurementHistory->id]);
            }
        }
    }

    // === GUARDAR HISTORIAL DE MEDIDAS ===
    protected function saveMeasurementHistory(
        Order $order,
        OrderItem $orderItem,
        Product $product,
        array $measurements
    ): ClientMeasurementHistory {
        // Limpiar campos que no son medidas
        $cleanMeasurements = collect($measurements)
            ->except(['save_to_client'])
            ->filter(fn($value) => !empty($value) && $value !== '0')
            ->toArray();

        return ClientMeasurementHistory::create([
            'cliente_id' => $order->cliente_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'measurements' => $cleanMeasurements,
            'source' => 'order',
            'notes' => "Capturado en pedido {$order->order_number}",
            'created_by' => Auth::id(),
            'captured_at' => now(),
        ]);
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

    // ========================================
    // === GESTIÓN DE AJUSTES DE PRECIO ===
    // ========================================

    /**
     * Crear un ajuste de precio para un item.
     * Usado cuando se detecta diferencia entre costo estimado y real.
     */
    public function createAdjustment(
        OrderItem $item,
        string $type,
        float $estimatedCost,
        ?float $realCost = null,
        ?string $reason = null,
        ?int $designExportId = null
    ): OrderItemAdjustment {
        return DB::transaction(function () use ($item, $type, $estimatedCost, $realCost, $reason, $designExportId) {
            $adjustment = OrderItemAdjustment::create([
                'order_item_id' => $item->id,
                'design_export_id' => $designExportId,
                'type' => $type,
                'estimated_cost' => $estimatedCost,
                'real_cost' => $realCost,
                'status' => OrderItemAdjustment::STATUS_PENDING,
                'reason' => $reason,
                'created_by' => Auth::id(),
            ]);

            // El flag se actualiza automáticamente via boot() del modelo

            return $adjustment;
        });
    }

    /**
     * Aprobar un ajuste de precio.
     * REGLA: Solo ajustes pendientes pueden ser aprobados.
     */
    public function approveAdjustment(OrderItemAdjustment $adjustment, ?float $realCost = null): OrderItemAdjustment
    {
        if (!$adjustment->isPending()) {
            throw new \Exception('Solo se pueden aprobar ajustes pendientes.');
        }

        return DB::transaction(function () use ($adjustment, $realCost) {
            // Si se proporciona un costo real, actualizarlo
            if ($realCost !== null) {
                $adjustment->real_cost = $realCost;
            }

            // Validar que tenga costo real para aprobar
            if ($adjustment->real_cost === null) {
                throw new \Exception('Debe especificar el costo real para aprobar el ajuste.');
            }

            $adjustment->status = OrderItemAdjustment::STATUS_APPROVED;
            $adjustment->approved_by = Auth::id();
            $adjustment->approved_at = now();
            $adjustment->save();

            // Recalcular costos del item
            $adjustment->orderItem->recalculateRealExtrasCost();

            // Recalcular totales del pedido
            $adjustment->orderItem->order->recalculateTotals();

            return $adjustment;
        });
    }

    /**
     * Rechazar un ajuste de precio.
     * REGLA: Solo ajustes pendientes pueden ser rechazados.
     */
    public function rejectAdjustment(OrderItemAdjustment $adjustment, ?string $reason = null): OrderItemAdjustment
    {
        if (!$adjustment->isPending()) {
            throw new \Exception('Solo se pueden rechazar ajustes pendientes.');
        }

        return DB::transaction(function () use ($adjustment, $reason) {
            $adjustment->status = OrderItemAdjustment::STATUS_REJECTED;
            $adjustment->rejection_reason = $reason;
            $adjustment->approved_by = Auth::id();
            $adjustment->approved_at = now();
            $adjustment->save();

            // Flag se actualiza automáticamente via boot()

            return $adjustment;
        });
    }

    /**
     * Crear ajuste automático desde DesignExport aprobado.
     * Llamado cuando se aprueba un diseño con costo diferente al estimado.
     */
    public function createAdjustmentFromDesignExport(
        OrderItem $item,
        int $designExportId,
        float $estimatedCost,
        float $realCost,
        ?string $reason = null
    ): OrderItemAdjustment {
        // Solo crear ajuste si hay diferencia
        if (abs($realCost - $estimatedCost) < 0.01) {
            // No hay diferencia significativa, no crear ajuste
            return $this->createAdjustment(
                $item,
                OrderItemAdjustment::TYPE_DESIGN,
                $estimatedCost,
                $realCost,
                $reason ?? 'Diseño aprobado sin ajuste',
                $designExportId
            );
        }

        return $this->createAdjustment(
            $item,
            OrderItemAdjustment::TYPE_DESIGN,
            $estimatedCost,
            $realCost,
            $reason ?? 'Ajuste por costo real de diseño',
            $designExportId
        );
    }

    /**
     * Obtener resumen de ajustes de un pedido.
     */
    public function getAdjustmentsSummary(Order $order): array
    {
        $items = $order->items()->with(['adjustments'])->get();

        $totalEstimated = 0;
        $totalReal = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($items as $item) {
            foreach ($item->adjustments as $adj) {
                $totalEstimated += (float) $adj->estimated_cost;

                if ($adj->isApproved()) {
                    $totalReal += (float) $adj->real_cost;
                    $approvedCount++;
                } elseif ($adj->isPending()) {
                    $pendingCount++;
                } else {
                    $rejectedCount++;
                }
            }
        }

        return [
            'total_estimated' => $totalEstimated,
            'total_real' => $totalReal,
            'difference' => $totalReal - $totalEstimated,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'has_pending' => $pendingCount > 0,
        ];
    }

    // ========================================
    // === GESTIÓN DE APROBACIÓN DE DISEÑO ===
    // ========================================

    /**
     * Aprobar diseño de un item.
     * REGLA: Solo items con personalization_type='design' pueden ser aprobados.
     * Al aprobar:
     * - Guarda snapshot del hash de medidas (para detectar cambios post-aprobación)
     * - Recalcula fechas del pedido
     */
    public function approveItemDesign(OrderItem $item): OrderItem
    {
        if (!$item->requiresDesignApproval()) {
            throw new \Exception('Este item no requiere aprobación de diseño.');
        }

        if ($item->design_approved) {
            throw new \Exception('El diseño ya está aprobado.');
        }

        return DB::transaction(function () use ($item) {
            // Snapshot del hash de medidas para R4 validation
            $item->snapshotMeasurementsForApproval();

            $item->design_approved = true;
            $item->design_approved_at = now();
            $item->design_approved_by = Auth::id();
            $item->saveQuietly();

            // Recalcular fechas del pedido (puede cambiar minimum_date)
            $item->order->recalculateDates();
            $item->order->saveQuietly();

            return $item->fresh();
        });
    }

    /**
     * Invalidar diseño de un item.
     * REGLA: Cuando cambian medidas/texto/notas de personalización, el diseño debe re-aprobarse.
     */
    public function invalidateItemDesign(OrderItem $item, string $reason = null): OrderItem
    {
        if (!$item->requiresDesignApproval()) {
            return $item; // No aplica
        }

        return DB::transaction(function () use ($item, $reason) {
            $item->design_approved = false;
            $item->design_approved_at = null;
            $item->design_approved_by = null;

            // Opcionalmente agregar nota sobre invalidación
            if ($reason) {
                $existingNotes = $item->customization_notes ?? '';
                $item->customization_notes = trim($existingNotes . "\n[Diseño invalidado: {$reason}]");
            }

            $item->saveQuietly();

            return $item->fresh();
        });
    }

    // =========================================================================
    // === FASE v2.4: SISTEMA DE PAGOS Y BALANCE ===
    // =========================================================================

    /**
     * Registrar un pago para un pedido.
     *
     * GATE CONTABLE v2.4:
     * - Valida que el pedido pueda recibir pagos (no CANCELLED, no sobrepagado)
     * - Valida que el monto sea positivo y no exceda el balance
     * - Registra el pago con auditoría completa
     * - Recalcula automáticamente amount_paid, balance y payment_status
     *
     * @param Order $order Pedido a abonar
     * @param float $amount Monto del pago
     * @param string $paymentMethod Método de pago (cash, transfer, card, other)
     * @param string|null $reference Referencia externa (voucher, folio, etc)
     * @param string|null $notes Notas adicionales
     * @return OrderPayment
     * @throws \Exception Si el pago no es válido
     */
    public function registerPayment(
        Order $order,
        float $amount,
        string $paymentMethod = OrderPayment::METHOD_CASH,
        ?string $reference = null,
        ?string $notes = null
    ): OrderPayment {
        // === GATE 1: ESTADO DEL PEDIDO ===
        if (!$order->canReceivePayment()) {
            throw new \Exception($order->getPaymentBlockReason() ?? 'El pedido no puede recibir pagos.');
        }

        // === GATE 2: VALIDACIÓN DE MONTO ===
        $validation = $order->validatePaymentAmount($amount);
        if (!$validation['valid']) {
            throw new \Exception($validation['error']);
        }

        // === GATE 3: MÉTODO DE PAGO VÁLIDO ===
        $validMethods = [
            OrderPayment::METHOD_CASH,
            OrderPayment::METHOD_TRANSFER,
            OrderPayment::METHOD_CARD,
            OrderPayment::METHOD_OTHER,
        ];
        if (!in_array($paymentMethod, $validMethods)) {
            throw new \Exception("Método de pago inválido: {$paymentMethod}");
        }

        return DB::transaction(function () use ($order, $amount, $paymentMethod, $reference, $notes) {
            // Snapshot del balance ANTES del pago (para auditoría)
            $balanceBefore = (float) $order->balance;

            // Crear registro de pago
            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes,
                'received_by' => Auth::id(),
                'payment_date' => now(),
            ]);

            // El hook saved() de OrderPayment ya llama a recalculateTotals()
            // Pero refrescamos el order para obtener valores actualizados
            $order->refresh();

            // === v2.5: EVENTO DE AUDITORÍA CONTABLE COMPLETO ===
            OrderEvent::logPaymentRegistered(
                $order,
                $amount,
                $paymentMethod,
                $balanceBefore,
                (float) $order->balance
            );

            // === v2.5: CIERRE CONTABLE SI APLICA ===
            // Si el pedido quedó DELIVERED + PAID → cierre contable automático
            if ($order->isFinanciallyClosed()) {
                OrderEvent::logFinanciallyClosed($order);
            }

            return $payment;
        });
    }

    /**
     * Obtiene el resumen de pagos de un pedido.
     *
     * @param Order $order
     * @return array
     */
    public function getPaymentsSummary(Order $order): array
    {
        $payments = $order->payments()->orderBy('payment_date', 'desc')->get();

        $byMethod = $payments->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        });

        return [
            'total_paid' => (float) $order->amount_paid,
            'balance' => (float) $order->balance,
            'total' => (float) $order->total,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->payment_status_label,
            'payments_count' => $payments->count(),
            'can_receive_payment' => $order->canReceivePayment(),
            'max_payable' => $order->getMaxPayableAmount(),
            'by_method' => $byMethod->toArray(),
            'payments' => $payments->map(fn($p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'method_label' => $p->method_label,
                'reference' => $p->reference,
                'notes' => $p->notes,
                'received_by' => $p->receiver?->name,
                'payment_date' => $p->payment_date?->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }

    /**
     * Actualizar item con validación de diseño.
     * Si cambian campos de personalización → invalidar diseño.
     */
    public function updateItemWithDesignValidation(
        OrderItem $item,
        array $data
    ): OrderItem {
        $changesRequireReapproval = false;
        $changeReasons = [];

        // Detectar cambios que invalidan diseño
        if (isset($data['embroidery_text']) && $data['embroidery_text'] !== $item->embroidery_text) {
            $changesRequireReapproval = true;
            $changeReasons[] = 'texto modificado';
        }

        if (isset($data['measurements']) && $data['measurements'] !== $item->measurements) {
            $changesRequireReapproval = true;
            $changeReasons[] = 'medidas modificadas';
        }

        if (isset($data['customization_notes'])) {
            // Solo invalidar si las notas cambian sustancialmente (no si solo se agrega info)
            $oldNotes = $item->customization_notes ?? '';
            $newNotes = $data['customization_notes'] ?? '';
            if (strlen($newNotes) < strlen($oldNotes) || !str_contains($newNotes, $oldNotes)) {
                $changesRequireReapproval = true;
                $changeReasons[] = 'notas de personalización modificadas';
            }
        }

        return DB::transaction(function () use ($item, $data, $changesRequireReapproval, $changeReasons) {
            // Actualizar campos
            $item->fill($data);
            $item->save();

            // Invalidar diseño si hubo cambios significativos
            if ($changesRequireReapproval && $item->requiresDesignApproval()) {
                $this->invalidateItemDesign($item, implode(', ', $changeReasons));
            }

            return $item->fresh();
        });
    }
}
