<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Estado;
use App\Models\FinishedGoodsMovement;
use App\Models\MotivoDescuento;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Recomendacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * POS Controller - Puerta Ultra Mínima de Salida de Stock
 *
 * RESPONSABILIDAD ÚNICA:
 * Registrar UNA salida de producto terminado desde stock
 * y crear UN Order terminal (snapshot operativo).
 *
 * INVARIANTES:
 * I1) Stock se calcula SOLO desde FinishedGoodsMovement
 * I2) Si stock < quantity → ABORTAR
 * I3) NO crear producción
 * I4) NO usar reservas
 * I5) NO crear OrderItem
 * I6) NO crear OrderPayment
 * I7) NO tocar ProductVariant.current_stock
 * I8) Order se crea DIRECTO en DELIVERED
 * I9) Un sale_exit = un movimiento
 * I10) Sin lógica fiscal
 * I11) Sin estados intermedios
 * I12) Método de pago SOLO como nota
 * I13) Descuento SOLO como trazabilidad
 * I14) VENDEDOR = usuario autenticado (snapshot)
 * I15) FECHA DE VENTA = delivered_date (snapshot servidor)
 */
class PosController extends Controller
{
    /**
     * GET /pos
     *
     * Vista principal del POS.
     * Carga variantes de producto activas con stock calculado desde ledger.
     * Muestra TODOS los productos (con y sin stock) - sin stock se muestran deshabilitados.
     */
    public function index(): View
    {
        // Obtener variantes activas con su producto (TODAS, incluyendo sin stock)
        $variants = ProductVariant::with(['product'])
            ->where('activo', true)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->get()
            ->map(function ($variant) {
                // Calcular stock real desde ledger
                $variant->stock_finished = $this->calculateRealStock($variant->id);
                return $variant;
            })
            // Ordenar alfabéticamente por nombre del producto
            ->sortBy(function ($variant) {
                return strtolower($variant->product?->name ?? '');
            })
            ->values();

        // Obtener motivos de descuento activos ordenados alfabéticamente
        $motivosDescuento = MotivoDescuento::where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get();

        // Obtener estados activos para el formulario de cliente rápido
        $estados = Estado::where('activo', true)
            ->orderBy('nombre_estado', 'asc')
            ->get();

        // Obtener recomendaciones activas para el formulario de cliente rápido
        $recomendaciones = Recomendacion::where('activo', true)
            ->orderBy('nombre_recomendacion', 'asc')
            ->get();

        // Tasa de IVA desde configuración del sistema
        $defaultTaxRate = Order::getDefaultTaxRate();

        return view('pos.index', compact('variants', 'motivosDescuento', 'estados', 'recomendaciones', 'defaultTaxRate'));
    }

    /**
     * POST /pos/sale
     *
     * Registra salida de stock + Order terminal con trazabilidad de vendedor y fecha.
     * Soporta MÚLTIPLES ÍTEMS en una sola transacción (carrito POS profesional).
     *
     * Formato request:
     * - items[]: Array de productos con { product_variant_id, quantity, unit_price_original, unit_price_final }
     * - discount_reason: Motivo del descuento (opcional)
     * - payment_method_note: Método de pago (opcional)
     * - apply_iva: Aplicar IVA (opcional)
     */
    public function sale(Request $request): JsonResponse
    {
        // =====================================================================
        // NORMALIZAR: Detectar formato antiguo (1 item) vs nuevo (múltiples)
        // =====================================================================
        $items = $request->input('items');

        // Si no hay 'items', convertir formato antiguo a array
        if (!$items && $request->has('product_variant_id')) {
            $items = [[
                'product_variant_id' => $request->input('product_variant_id'),
                'quantity' => $request->input('quantity'),
                'unit_price_original' => $request->input('unit_price_original'),
                'unit_price_final' => $request->input('unit_price_final'),
            ]];
        }

        if (!$items || !is_array($items) || count($items) === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Se requiere al menos un producto en el carrito.',
            ], 422);
        }

        // =====================================================================
        // VALIDACIÓN DE CADA ÍTEM
        // =====================================================================
        $validatedItems = [];
        foreach ($items as $index => $item) {
            $itemValidator = Validator::make($item, [
                'product_variant_id' => 'required|integer|exists:product_variants,id',
                'quantity' => 'required|integer|min:1',
                'unit_price_original' => 'required|numeric|min:0',
                'unit_price_final' => 'required|numeric|min:0',
            ], [
                'product_variant_id.required' => "Ítem #{$index}: Se requiere el ID de la variante.",
                'product_variant_id.exists' => "Ítem #{$index}: La variante no existe.",
                'quantity.required' => "Ítem #{$index}: Se requiere la cantidad.",
                'quantity.min' => "Ítem #{$index}: La cantidad debe ser al menos 1.",
                'unit_price_original.required' => "Ítem #{$index}: Se requiere el precio original.",
                'unit_price_final.required' => "Ítem #{$index}: Se requiere el precio final.",
            ]);

            if ($itemValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validación fallida',
                    'details' => $itemValidator->errors()->toArray(),
                ], 422);
            }

            $validatedItems[] = [
                'product_variant_id' => (int) $item['product_variant_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price_original' => (float) $item['unit_price_original'],
                'unit_price_final' => (float) $item['unit_price_final'],
            ];
        }

        // Parámetros globales de la venta
        $discountReason = $request->input('discount_reason') ?: null; // null si vacío
        $discountType = $request->input('discount_type') ?: null; // 'fixed' o 'percent'
        $discountValue = $request->input('discount_value'); // Valor ingresado
        $discountValue = ($discountValue !== null && $discountValue !== '') ? (float) $discountValue : null;
        $paymentMethod = $request->input('payment_method') ?: null; // efectivo, tarjeta, transferencia
        $applyIva = (bool) $request->input('apply_iva', false);
        $clienteId = $request->input('cliente_id') ?: null;

        // =====================================================================
        // INVARIANTE I14: VENDEDOR = usuario autenticado
        // =====================================================================
        /** @var User $seller */
        $seller = Auth::user();
        $sellerId = $seller->id;
        $sellerName = $seller->name;

        // =====================================================================
        // INVARIANTE I15: FECHA DE VENTA = now() del servidor
        // =====================================================================
        $saleTimestamp = now();

        // =====================================================================
        // TRANSACCIÓN ATÓMICA - MÚLTIPLES ÍTEMS
        // =====================================================================
        try {
            $result = DB::transaction(function () use (
                $validatedItems,
                $discountReason,
                $discountType,
                $discountValue,
                $paymentMethod,
                $clienteId,
                $sellerId,
                $sellerName,
                $saleTimestamp,
                $applyIva
            ) {
                // -----------------------------------------------------------------
                // PASO 1: Validar stock de TODOS los ítems antes de procesar
                // -----------------------------------------------------------------
                $stockChecks = [];
                foreach ($validatedItems as $item) {
                    $stockReal = $this->calculateRealStock($item['product_variant_id']);
                    if ($stockReal < $item['quantity']) {
                        $variant = ProductVariant::with('product')->find($item['product_variant_id']);
                        $productName = $variant ? ($variant->product->name ?? 'Producto') . ' - ' . $variant->name : 'Variante #' . $item['product_variant_id'];
                        throw new \Exception(
                            "Stock insuficiente para '{$productName}'. Disponible: {$stockReal}, Requerido: {$item['quantity']}"
                        );
                    }
                    $stockChecks[$item['product_variant_id']] = $stockReal;
                }

                // -----------------------------------------------------------------
                // PASO 2: Calcular totales consolidados
                // -----------------------------------------------------------------
                $subtotalGeneral = 0;
                $itemsDetails = [];

                foreach ($validatedItems as $item) {
                    $itemSubtotal = $item['unit_price_final'] * $item['quantity'];

                    $subtotalGeneral += $itemSubtotal;

                    $variant = ProductVariant::with('product')->find($item['product_variant_id']);
                    $itemsDetails[] = [
                        'variant_id' => $item['product_variant_id'],
                        'name' => $variant ? ($variant->product->name ?? '') . ' - ' . $variant->name : 'Variante',
                        'quantity' => $item['quantity'],
                        'price_original' => $item['unit_price_original'],
                        'price_final' => $item['unit_price_final'],
                        'subtotal' => $itemSubtotal,
                        'stock_before' => $stockChecks[$item['product_variant_id']],
                    ];
                }

                // -----------------------------------------------------------------
                // PASO 2.5: Calcular descuento global ($ fijo o % porcentaje)
                // -----------------------------------------------------------------
                $discountGeneral = 0;
                if ($discountValue !== null && $discountValue > 0) {
                    if ($discountType === 'percent') {
                        // Porcentaje: limitar a 100%
                        $discountGeneral = round($subtotalGeneral * (min($discountValue, 100) / 100), 2);
                    } else {
                        // Fijo ($): limitar al subtotal
                        $discountGeneral = round(min($discountValue, $subtotalGeneral), 2);
                    }
                }

                $subtotalAfterDiscount = $subtotalGeneral - $discountGeneral;

                // CIERRE POS: Cálculo de IVA como snapshot (sobre subtotal - descuento)
                // Obtener tasa de IVA desde configuración del sistema
                $defaultTaxRate = Order::getDefaultTaxRate();
                $ivaRate = $applyIva ? $defaultTaxRate : 0.00;
                $ivaAmount = $applyIva ? round($subtotalAfterDiscount * ($defaultTaxRate / 100), 2) : 0.00;
                $totalWithTax = $subtotalAfterDiscount + $ivaAmount;

                // -----------------------------------------------------------------
                // PASO 3: Construir notas con trazabilidad humana COMPLETA
                // -----------------------------------------------------------------
                $notesLines = ['[VENTA POS MOSTRADOR - MULTI-ITEM]'];
                $notesLines[] = "Fecha/Hora: " . $saleTimestamp->format('Y-m-d H:i:s');
                $notesLines[] = "Vendedor: {$sellerName} (ID: {$sellerId})";
                $notesLines[] = "Productos: " . count($validatedItems);
                $notesLines[] = "---";

                foreach ($itemsDetails as $idx => $detail) {
                    $notesLines[] = ($idx + 1) . ". {$detail['name']} x{$detail['quantity']} @ \${$detail['price_final']} = \$" . number_format($detail['subtotal'], 2);
                }

                $notesLines[] = "---";
                $notesLines[] = "Subtotal: \$" . number_format($subtotalGeneral, 2);

                if ($discountGeneral > 0 || $discountReason) {
                    $discountTypeLabel = $discountType === 'percent' ? '%' : '$';
                    $notesLines[] = "Descuento: {$discountTypeLabel}" . ($discountValue ?? 0) . " = \$" . number_format($discountGeneral, 2);
                    if ($discountReason) {
                        $notesLines[] = "Motivo: {$discountReason}";
                    }
                }

                if ($applyIva) {
                    $notesLines[] = "IVA ({$ivaRate}%): \$" . number_format($ivaAmount, 2);
                }

                $notesLines[] = "TOTAL: \$" . number_format($totalWithTax, 2);

                if ($paymentMethod) {
                    $notesLines[] = "Método de pago: {$paymentMethod}";
                }

                $notes = implode("\n", $notesLines);

                // -----------------------------------------------------------------
                // PASO 4: Crear Order terminal (snapshot consolidado)
                // NOTA: Generamos uuid y order_number manualmente porque
                // saveQuietly() salta los eventos del modelo (creating)
                // -----------------------------------------------------------------
                $order = new Order();
                $order->uuid = (string) Str::uuid();
                $order->order_number = Order::generateOrderNumber();
                $order->cliente_id = $clienteId;
                $order->status = Order::STATUS_DELIVERED;
                $order->payment_status = Order::PAYMENT_PAID;
                $order->urgency_level = Order::URGENCY_NORMAL;
                $order->priority = Order::PRIORITY_NORMAL;
                $order->subtotal = $subtotalGeneral;
                $order->discount = $discountGeneral;
                // Nuevos campos POS para descuento detallado
                $order->discount_reason = $discountReason;
                $order->discount_type = $discountType;
                $order->discount_value = $discountValue;
                $order->payment_method = $paymentMethod;
                $order->requires_invoice = $applyIva;
                $order->iva_rate = $ivaRate;
                $order->iva_amount = $ivaAmount;
                $order->total_with_tax = $totalWithTax;
                $order->total = $totalWithTax;
                $order->amount_paid = $totalWithTax;
                $order->balance = 0;
                $order->delivered_date = $saleTimestamp;
                $order->sold_at = $saleTimestamp; // Fecha y hora exacta
                $order->created_by = $sellerId;
                $order->seller_name = $sellerName; // Snapshot del vendedor
                $order->notes = $notes;
                $order->saveQuietly();

                // -----------------------------------------------------------------
                // PASO 5: Crear FinishedGoodsMovement por cada ítem
                // -----------------------------------------------------------------
                $movements = [];
                foreach ($itemsDetails as $detail) {
                    $stockAfter = $detail['stock_before'] - $detail['quantity'];

                    $movement = new FinishedGoodsMovement();
                    $movement->product_variant_id = $detail['variant_id'];
                    $movement->type = FinishedGoodsMovement::TYPE_SALE_EXIT;
                    $movement->reference_type = Order::class;
                    $movement->reference_id = $order->id;
                    $movement->quantity = $detail['quantity'];
                    $movement->stock_before = $detail['stock_before'];
                    $movement->stock_after = $stockAfter;
                    $movement->notes = "Venta POS #{$order->order_number} | {$detail['name']} | Vendedor: {$sellerName}";
                    $movement->save();

                    $movements[] = [
                        'movement_id' => $movement->id,
                        'variant_id' => $detail['variant_id'],
                        'name' => $detail['name'],
                        'quantity' => $detail['quantity'],
                        'stock_before' => $detail['stock_before'],
                        'stock_after' => $stockAfter,
                    ];
                }

                // -----------------------------------------------------------------
                // PASO 6: Retornar resultado consolidado
                // -----------------------------------------------------------------
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_uuid' => $order->uuid,
                    'items_count' => count($validatedItems),
                    'items' => $movements,
                    'subtotal' => number_format($subtotalGeneral, 2, '.', ''),
                    'discount' => number_format($discountGeneral, 2, '.', ''),
                    'discount_display' => $discountGeneral > 0,
                    'iva_rate' => $ivaRate,
                    'iva_amount' => number_format($ivaAmount, 2, '.', ''),
                    'iva_display' => $applyIva,
                    'total' => number_format($totalWithTax, 2, '.', ''),
                    'payment_method' => $paymentMethod,
                    'discount_reason' => $discountReason,
                    'seller_id' => $sellerId,
                    'seller_name' => $sellerName,
                    'sale_datetime' => $saleTimestamp->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta POS registrada.',
                'data' => $result,
            ], 201);

        } catch (\Exception $e) {
            // Trazabilidad completa del error para debugging
            $errorTrace = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_summary' => collect(array_slice($e->getTrace(), 0, 5))->map(function ($t) {
                    return ($t['file'] ?? 'unknown') . ':' . ($t['line'] ?? '?') . ' → ' . ($t['function'] ?? '?');
                })->toArray(),
            ];

            // Log para el servidor (completo)
            \Log::error('POS SALE ERROR', $errorTrace);

            // Respuesta al cliente (con info útil para debugging)
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'location' => basename($e->getFile()) . ':' . $e->getLine(),
                    'trace' => $errorTrace['trace_summary'],
                ],
            ], 400);
        }
    }

    /**
     * Calcula stock REAL desde ledger.
     *
     * FÓRMULA ACTUALIZADA (CIERRE POS):
     * stock = Σ(production_entry + return + adjustment_positivo)
     *       - Σ(sale_exit + adjustment_negativo)
     *
     * INCLUYE: return y adjustment para cálculo correcto post-cancelación.
     */
    private function calculateRealStock(int $productVariantId): float
    {
        // Entradas: producción + devoluciones
        $productionEntries = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY)
            ->sum('quantity');

        $returns = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_RETURN)
            ->sum('quantity');

        // Salidas: ventas
        $saleExits = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
            ->sum('quantity');

        // Ajustes: pueden ser positivos o negativos
        $adjustments = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_ADJUSTMENT)
            ->sum('quantity');

        return $productionEntries + $returns - $saleExits + $adjustments;
    }

    /**
     * POST /pos/cancel
     *
     * Cancela una venta POS con reversa de stock.
     *
     * REGLAS INVARIANTES:
     * - Solo Orders POS (cliente_id = null, status = delivered, notas POS)
     * - Motivo OBLIGATORIO
     * - Genera FinishedGoodsMovement::TYPE_RETURN
     * - Order.status = CANCELLED
     * - Transacción atómica (cualquier error = rollback)
     */
    public function cancelSale(Request $request): JsonResponse
    {
        // =====================================================================
        // VALIDACIÓN DEL REQUEST
        // =====================================================================
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'cancel_reason' => 'required|string|min:10|max:255',
        ], [
            'order_id.required' => 'Se requiere el ID del pedido.',
            'order_id.exists' => 'El pedido no existe.',
            'cancel_reason.required' => 'El motivo de cancelación es OBLIGATORIO.',
            'cancel_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'cancel_reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validación fallida',
                'details' => $validator->errors()->toArray(),
            ], 422);
        }

        $orderId = (int) $request->input('order_id');
        $cancelReason = trim($request->input('cancel_reason'));

        /** @var User $canceller */
        $canceller = Auth::user();
        $cancelTimestamp = now();

        try {
            $result = DB::transaction(function () use (
                $orderId,
                $cancelReason,
                $canceller,
                $cancelTimestamp
            ) {
                // -----------------------------------------------------------------
                // PASO 1: Cargar y validar el pedido
                // -----------------------------------------------------------------
                $order = Order::lockForUpdate()->findOrFail($orderId);

                // Validar que es venta POS
                if (!$order->isPosOrder()) {
                    throw new \Exception(
                        "El pedido {$order->order_number} NO es una venta POS. " .
                        "Solo se pueden cancelar ventas de mostrador."
                    );
                }

                // Validar que puede cancelarse
                if (!$order->canCancelPosOrder()) {
                    throw new \Exception(
                        "El pedido {$order->order_number} no puede cancelarse. " .
                        "Ya está cancelado o en estado incompatible."
                    );
                }

                // -----------------------------------------------------------------
                // PASO 2: Buscar TODOS los movimientos de salida originales
                // (ventas multi-item tienen varios movimientos)
                // -----------------------------------------------------------------
                $originalMovements = FinishedGoodsMovement::where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
                    ->get();

                if ($originalMovements->isEmpty()) {
                    throw new \Exception(
                        "No se encontraron movimientos de stock para el pedido {$order->order_number}."
                    );
                }

                // -----------------------------------------------------------------
                // PASO 3: Crear movimiento de devolución por cada ítem
                // -----------------------------------------------------------------
                $returnedItems = [];
                foreach ($originalMovements as $originalMovement) {
                    $productVariantId = $originalMovement->product_variant_id;
                    $quantityToReturn = (float) $originalMovement->quantity;
                    $currentStock = $this->calculateRealStock($productVariantId);
                    $stockAfterReturn = $currentStock + $quantityToReturn;

                    $returnMovement = new FinishedGoodsMovement();
                    $returnMovement->product_variant_id = $productVariantId;
                    $returnMovement->type = FinishedGoodsMovement::TYPE_RETURN;
                    $returnMovement->reference_type = Order::class;
                    $returnMovement->reference_id = $order->id;
                    $returnMovement->quantity = $quantityToReturn;
                    $returnMovement->stock_before = $currentStock;
                    $returnMovement->stock_after = $stockAfterReturn;
                    $returnMovement->notes = "CANCELACIÓN Venta POS #{$order->order_number} | " .
                        "Motivo: {$cancelReason} | " .
                        "Cancelado por: {$canceller->name}";
                    $returnMovement->created_by = $canceller->id;
                    $returnMovement->save();

                    $returnedItems[] = [
                        'variant_id' => $productVariantId,
                        'quantity' => $quantityToReturn,
                        'stock_after' => $stockAfterReturn,
                    ];
                }

                // -----------------------------------------------------------------
                // PASO 4: Actualizar el pedido (sin usar saveQuietly para registrar)
                // -----------------------------------------------------------------
                // Bypass de la validación de transición usando update directo
                Order::withoutEvents(function () use ($order, $cancelTimestamp, $canceller, $cancelReason) {
                    $order->update([
                        'status' => Order::STATUS_CANCELLED,
                        'cancelled_at' => $cancelTimestamp,
                        'cancelled_by' => $canceller->id,
                        'cancel_reason' => $cancelReason,
                    ]);
                });

                // -----------------------------------------------------------------
                // PASO 5: Retornar resultado
                // -----------------------------------------------------------------
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'cancelled_at' => $cancelTimestamp->toIso8601String(),
                    'cancelled_by_id' => $canceller->id,
                    'cancelled_by_name' => $canceller->name,
                    'cancel_reason' => $cancelReason,
                    'items_returned' => count($returnedItems),
                    'returned_items' => $returnedItems,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta POS cancelada. Stock revertido.',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            // Trazabilidad completa del error
            $errorTrace = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_summary' => collect(array_slice($e->getTrace(), 0, 5))->map(function ($t) {
                    return ($t['file'] ?? 'unknown') . ':' . ($t['line'] ?? '?') . ' → ' . ($t['function'] ?? '?');
                })->toArray(),
            ];

            \Log::error('POS CANCEL ERROR', $errorTrace);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'location' => basename($e->getFile()) . ':' . $e->getLine(),
                    'trace' => $errorTrace['trace_summary'],
                ],
            ], 400);
        }
    }

    /**
     * POST /pos/adjustment
     *
     * Ajuste de inventario por conteo físico.
     *
     * REGLAS INVARIANTES:
     * - El usuario ingresa stock físico contado
     * - Sistema calcula diferencia: stock_fisico - stock_sistema
     * - Si difference != 0: crea FinishedGoodsMovement::TYPE_ADJUSTMENT
     * - Motivo OBLIGATORIO
     * - Guarda fecha de conteo y usuario responsable
     */
    public function adjustment(Request $request): JsonResponse
    {
        // =====================================================================
        // VALIDACIÓN DEL REQUEST
        // =====================================================================
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'physical_stock' => 'required|numeric|min:0',
            'count_date' => 'required|date',
            'adjustment_reason' => 'required|string|min:10|max:255',
        ], [
            'product_variant_id.required' => 'Se requiere el ID de la variante.',
            'product_variant_id.exists' => 'La variante no existe.',
            'physical_stock.required' => 'Se requiere el stock físico contado.',
            'physical_stock.min' => 'El stock físico no puede ser negativo.',
            'count_date.required' => 'Se requiere la fecha de conteo.',
            'count_date.date' => 'Fecha de conteo inválida.',
            'adjustment_reason.required' => 'El motivo del ajuste es OBLIGATORIO.',
            'adjustment_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'adjustment_reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validación fallida',
                'details' => $validator->errors()->toArray(),
            ], 422);
        }

        $productVariantId = (int) $request->input('product_variant_id');
        $physicalStock = (float) $request->input('physical_stock');
        $countDate = $request->input('count_date');
        $adjustmentReason = trim($request->input('adjustment_reason'));

        /** @var User $adjuster */
        $adjuster = Auth::user();
        $adjustmentTimestamp = now();

        try {
            $result = DB::transaction(function () use (
                $productVariantId,
                $physicalStock,
                $countDate,
                $adjustmentReason,
                $adjuster,
                $adjustmentTimestamp
            ) {
                // -----------------------------------------------------------------
                // PASO 1: Calcular stock actual del sistema
                // -----------------------------------------------------------------
                $systemStock = $this->calculateRealStock($productVariantId);

                // -----------------------------------------------------------------
                // PASO 2: Calcular diferencia
                // -----------------------------------------------------------------
                $difference = $physicalStock - $systemStock;

                // Si no hay diferencia, no crear movimiento
                if (abs($difference) < 0.0001) {
                    return [
                        'adjustment_needed' => false,
                        'product_variant_id' => $productVariantId,
                        'system_stock' => $systemStock,
                        'physical_stock' => $physicalStock,
                        'difference' => 0,
                        'message' => 'Stock coincide. No se requiere ajuste.',
                    ];
                }

                // -----------------------------------------------------------------
                // PASO 3: Crear movimiento de ajuste
                // -----------------------------------------------------------------
                $stockAfter = $physicalStock; // El resultado es el stock físico contado

                $adjustmentMovement = new FinishedGoodsMovement();
                $adjustmentMovement->product_variant_id = $productVariantId;
                $adjustmentMovement->type = FinishedGoodsMovement::TYPE_ADJUSTMENT;
                $adjustmentMovement->reference_type = null; // Ajuste manual, sin referencia
                $adjustmentMovement->reference_id = null;
                $adjustmentMovement->quantity = $difference; // Puede ser positivo o negativo
                $adjustmentMovement->stock_before = $systemStock;
                $adjustmentMovement->stock_after = $stockAfter;
                $adjustmentMovement->notes = "AJUSTE POR CONTEO FÍSICO | " .
                    "Fecha conteo: {$countDate} | " .
                    "Motivo: {$adjustmentReason} | " .
                    "Sistema: {$systemStock} → Físico: {$physicalStock} | " .
                    "Responsable: {$adjuster->name}";
                $adjustmentMovement->created_by = $adjuster->id;
                $adjustmentMovement->save();

                // -----------------------------------------------------------------
                // PASO 4: Retornar resultado
                // -----------------------------------------------------------------
                $differenceType = $difference > 0 ? 'FALTANTE (se agrega)' : 'SOBRANTE (se resta)';

                return [
                    'adjustment_needed' => true,
                    'product_variant_id' => $productVariantId,
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference' => $difference,
                    'difference_type' => $differenceType,
                    'stock_after' => $stockAfter,
                    'count_date' => $countDate,
                    'adjustment_reason' => $adjustmentReason,
                    'adjusted_by_id' => $adjuster->id,
                    'adjusted_by_name' => $adjuster->name,
                    'adjusted_at' => $adjustmentTimestamp->toIso8601String(),
                    'movement_id' => $adjustmentMovement->id,
                ];
            });

            $message = $result['adjustment_needed']
                ? 'Ajuste de inventario registrado.'
                : 'Stock coincide. No se requirió ajuste.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ], $result['adjustment_needed'] ? 201 : 200);

        } catch (\Exception $e) {
            // Trazabilidad completa del error
            $errorTrace = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_summary' => collect(array_slice($e->getTrace(), 0, 5))->map(function ($t) {
                    return ($t['file'] ?? 'unknown') . ':' . ($t['line'] ?? '?') . ' → ' . ($t['function'] ?? '?');
                })->toArray(),
            ];

            \Log::error('POS ADJUSTMENT ERROR', $errorTrace);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'location' => basename($e->getFile()) . ':' . $e->getLine(),
                    'trace' => $errorTrace['trace_summary'],
                ],
            ], 400);
        }
    }

    /**
     * GET /pos/stock/{productVariantId}
     *
     * Consulta el stock actual de una variante.
     * Útil para UI y validaciones previas.
     */
    public function getStock(int $productVariantId): JsonResponse
    {
        try {
            $stock = $this->calculateRealStock($productVariantId);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_variant_id' => $productVariantId,
                    'current_stock' => $stock,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /pos/clientes/search
     *
     * Búsqueda de clientes por nombre, apellidos o teléfono.
     * Búsqueda robusta: desde 1 caracter, soporta singular/plural.
     */
    public function searchClientes(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 1) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // Normalizar búsqueda: remover 's' final para buscar singular/plural
        $searchTerms = [$query];
        if (strlen($query) > 1 && str_ends_with(strtolower($query), 's')) {
            $searchTerms[] = substr($query, 0, -1); // Sin 's' final
        } else {
            $searchTerms[] = $query . 's'; // Con 's' final
        }

        $clientes = Cliente::where('activo', true)
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('nombre', 'LIKE', "%{$term}%")
                      ->orWhere('apellidos', 'LIKE', "%{$term}%")
                      ->orWhere('telefono', 'LIKE', "%{$term}%");
                }
            })
            ->select('id', 'nombre', 'apellidos', 'telefono', 'email')
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clientes,
        ]);
    }

    /**
     * POST /pos/clientes
     *
     * Crear cliente rápido desde el POS.
     * Campos mínimos: nombre, teléfono.
     */
    public function storeCliente(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'estado_id' => 'required|exists:estados,id',
            'recomendacion_id' => 'required|exists:recomendacion,id',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'estado_id.required' => 'El estado es obligatorio.',
            'estado_id.exists' => 'El estado seleccionado no es válido.',
            'recomendacion_id.required' => 'La recomendación es obligatoria.',
            'recomendacion_id.exists' => 'La recomendación seleccionada no es válida.',
        ]);

        try {
            $cliente = Cliente::create([
                'nombre' => $validated['nombre'],
                'apellidos' => $validated['apellidos'] ?? null,
                'telefono' => $validated['telefono'],
                'email' => $validated['email'] ?? null,
                'estado_id' => $validated['estado_id'],
                'recomendacion_id' => $validated['recomendacion_id'],
                'activo' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente.',
                'data' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'apellidos' => $cliente->apellidos,
                    'telefono' => $cliente->telefono,
                    'email' => $cliente->email,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al crear el cliente: ' . $e->getMessage(),
            ], 500);
        }
    }

}
