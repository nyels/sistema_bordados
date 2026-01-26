<?php

namespace App\Http\Controllers;

use App\Models\FinishedGoodsMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
     */
    public function index(): View
    {
        // Obtener variantes activas con su producto
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
            ->filter(function ($variant) {
                // Solo mostrar variantes con stock > 0
                return $variant->stock_finished > 0;
            })
            ->values();

        return view('pos.index', compact('variants'));
    }

    /**
     * POST /pos/sale
     *
     * Registra salida de stock + Order terminal con trazabilidad de vendedor y fecha.
     */
    public function sale(Request $request): JsonResponse
    {
        // =====================================================================
        // VALIDACIÓN DEL REQUEST (CONTRATO DURO - EXACTO)
        // =====================================================================
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'unit_price_original' => 'required|numeric|min:0',
            'unit_price_final' => 'required|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'payment_method_note' => 'nullable|string|max:100',
            'apply_iva' => 'nullable|boolean',
        ], [
            'product_variant_id.required' => 'Se requiere el ID de la variante.',
            'product_variant_id.exists' => 'La variante no existe.',
            'quantity.required' => 'Se requiere la cantidad.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'unit_price_original.required' => 'Se requiere el precio original.',
            'unit_price_original.min' => 'El precio original no puede ser negativo.',
            'unit_price_final.required' => 'Se requiere el precio final.',
            'unit_price_final.min' => 'El precio final no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validación fallida',
                'details' => $validator->errors()->toArray(),
            ], 422);
        }

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

        $productVariantId = (int) $request->input('product_variant_id');
        $quantity = (int) $request->input('quantity');
        $unitPriceOriginal = (float) $request->input('unit_price_original');
        $unitPriceFinal = (float) $request->input('unit_price_final');
        $discountReason = $request->input('discount_reason');
        $paymentMethodNote = $request->input('payment_method_note');
        $applyIva = (bool) $request->input('apply_iva', false);

        // =====================================================================
        // TRANSACCIÓN ATÓMICA
        // =====================================================================
        try {
            $result = DB::transaction(function () use (
                $productVariantId,
                $quantity,
                $unitPriceOriginal,
                $unitPriceFinal,
                $discountReason,
                $paymentMethodNote,
                $sellerId,
                $sellerName,
                $saleTimestamp,
                $applyIva
            ) {

                // -----------------------------------------------------------------
                // PASO 1: Calcular stock real EXCLUSIVAMENTE desde ledger
                // INVARIANTE I1: Solo production_entry - sale_exit
                // -----------------------------------------------------------------
                $stockReal = $this->calculateRealStock($productVariantId);

                // -----------------------------------------------------------------
                // PASO 2: Validar stock suficiente
                // INVARIANTE I2: Si stock < quantity → ABORTAR
                // -----------------------------------------------------------------
                if ($stockReal < $quantity) {
                    throw new \Exception(
                        "Stock insuficiente. Disponible: {$stockReal}, Requerido: {$quantity}"
                    );
                }

                // -----------------------------------------------------------------
                // PASO 3: Calcular descuento SOLO aritmético
                // discount = (unit_price_original - unit_price_final) * quantity
                // -----------------------------------------------------------------
                $discountPerUnit = $unitPriceOriginal - $unitPriceFinal;
                $totalDiscount = $discountPerUnit * $quantity;

                // -----------------------------------------------------------------
                // PASO 4: Crear Order terminal (snapshot mínimo)
                // INVARIANTE I8: Order se crea DIRECTO en DELIVERED
                // INVARIANTE I14: created_by = vendedor autenticado
                // INVARIANTE I15: delivered_date = timestamp servidor
                // -----------------------------------------------------------------
                $subtotal = $unitPriceFinal * $quantity;

                // CIERRE POS: Cálculo de IVA como snapshot
                $ivaRate = $applyIva ? 16.00 : 0.00;
                $ivaAmount = $applyIva ? round($subtotal * 0.16, 2) : 0.00;
                $totalWithTax = $subtotal + $ivaAmount;

                // Construir notas con trazabilidad humana COMPLETA
                $notesLines = ['[VENTA POS MOSTRADOR]'];
                $notesLines[] = "Fecha/Hora: " . $saleTimestamp->format('Y-m-d H:i:s');
                $notesLines[] = "Vendedor: {$sellerName} (ID: {$sellerId})";
                $notesLines[] = "---";
                $notesLines[] = "Precio original: $" . number_format($unitPriceOriginal, 2);
                $notesLines[] = "Precio final: $" . number_format($unitPriceFinal, 2);

                if ($totalDiscount > 0) {
                    $notesLines[] = "Descuento aplicado: $" . number_format($totalDiscount, 2);
                    if ($discountReason) {
                        $notesLines[] = "Razón descuento: {$discountReason}";
                    }
                }

                if ($applyIva) {
                    $notesLines[] = "IVA ({$ivaRate}%): $" . number_format($ivaAmount, 2);
                    $notesLines[] = "Total con IVA: $" . number_format($totalWithTax, 2);
                }

                if ($paymentMethodNote) {
                    $notesLines[] = "Método de pago: {$paymentMethodNote}";
                }

                $notes = implode("\n", $notesLines);

                $order = new Order();
                $order->cliente_id = null;
                $order->status = Order::STATUS_DELIVERED;
                $order->payment_status = Order::PAYMENT_PAID;
                $order->urgency_level = Order::URGENCY_NORMAL;
                $order->priority = Order::PRIORITY_NORMAL;
                $order->subtotal = $subtotal;
                $order->discount = $totalDiscount;
                $order->requires_invoice = $applyIva;
                $order->iva_rate = $ivaRate;
                $order->iva_amount = $ivaAmount;
                $order->total_with_tax = $totalWithTax;
                $order->total = $totalWithTax; // Total siempre incluye IVA si aplica
                $order->amount_paid = $totalWithTax;
                $order->balance = 0;
                $order->delivered_date = $saleTimestamp;
                $order->created_by = $sellerId;
                $order->notes = $notes;
                $order->saveQuietly();

                // -----------------------------------------------------------------
                // PASO 5: Crear FinishedGoodsMovement TYPE_SALE_EXIT
                // INVARIANTE I9: Un sale_exit = un movimiento
                // Nota: created_by se asigna automáticamente en boot del modelo
                // -----------------------------------------------------------------
                $stockAfter = $stockReal - $quantity;

                $movement = new FinishedGoodsMovement();
                $movement->product_variant_id = $productVariantId;
                $movement->type = FinishedGoodsMovement::TYPE_SALE_EXIT;
                $movement->reference_type = Order::class;
                $movement->reference_id = $order->id;
                $movement->quantity = $quantity;
                $movement->stock_before = $stockReal;
                $movement->stock_after = $stockAfter;
                $movement->notes = "Venta POS #{$order->order_number} | Vendedor: {$sellerName}";
                $movement->save();

                // -----------------------------------------------------------------
                // PASO 6: Commit (implícito al retornar)
                // -----------------------------------------------------------------
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_uuid' => $order->uuid,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                    'unit_price_original' => number_format($unitPriceOriginal, 2, '.', ''),
                    'unit_price_final' => number_format($unitPriceFinal, 2, '.', ''),
                    'discount' => number_format($totalDiscount, 2, '.', ''),
                    'discount_display' => $totalDiscount > 0,
                    'subtotal' => number_format($subtotal, 2, '.', ''),
                    'iva_rate' => $ivaRate,
                    'iva_amount' => number_format($ivaAmount, 2, '.', ''),
                    'iva_display' => $applyIva,
                    'total' => number_format($totalWithTax, 2, '.', ''),
                    'stock_before' => $stockReal,
                    'stock_after' => $stockAfter,
                    'movement_id' => $movement->id,
                    'payment_method_note' => $paymentMethodNote,
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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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
                // PASO 2: Buscar el movimiento de salida original
                // -----------------------------------------------------------------
                $originalMovement = FinishedGoodsMovement::where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
                    ->first();

                if (!$originalMovement) {
                    throw new \Exception(
                        "No se encontró el movimiento de stock original para el pedido {$order->order_number}."
                    );
                }

                // -----------------------------------------------------------------
                // PASO 3: Calcular stock actual y crear movimiento de devolución
                // -----------------------------------------------------------------
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
                    'product_variant_id' => $productVariantId,
                    'quantity_returned' => $quantityToReturn,
                    'stock_before' => $currentStock,
                    'stock_after' => $stockAfterReturn,
                    'return_movement_id' => $returnMovement->id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta POS cancelada. Stock revertido.',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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

}
