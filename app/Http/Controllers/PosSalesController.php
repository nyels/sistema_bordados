<?php

namespace App\Http\Controllers;

use App\Models\FinishedGoodsMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * HISTORIAL DE VENTAS POS - BACKOFFICE ADMIN
 *
 * PROPÓSITO:
 * Vista administrativa del historial de ventas POS.
 * Permite filtrar, consultar y CANCELAR ventas desde el backoffice.
 *
 * REGLAS INVARIANTES:
 * - SOLO muestra Orders donde isPosOrder() === true
 * - NO permite editar ventas (inmutables)
 * - NO permite eliminar ventas (solo cancelar)
 * - Cancelación con auditoría obligatoria
 *
 * IDENTIFICACIÓN DE VENTA POS:
 * - cliente_id = NULL
 * - status = DELIVERED (o CANCELLED si fue cancelada)
 * - notes CONTAINS '[VENTA POS MOSTRADOR]'
 */
class PosSalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /admin/pos-sales
     *
     * Listado de historial de ventas POS con filtros.
     */
    public function index(Request $request): View
    {
        // ========================================
        // QUERY BASE: Solo ventas POS
        // ========================================
        $query = Order::query()
            ->whereNull('cliente_id')
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR]%')
            ->with(['creator', 'canceller'])
            ->orderBy('delivered_date', 'desc');

        // ========================================
        // FILTRO POR FECHAS
        // ========================================
        if ($request->filled('fecha_desde')) {
            $query->whereDate('delivered_date', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('delivered_date', '<=', $request->fecha_hasta);
        }

        // ========================================
        // FILTRO POR VENDEDOR
        // ========================================
        if ($request->filled('vendedor_id')) {
            $query->where('created_by', $request->vendedor_id);
        }

        // ========================================
        // FILTRO POR ESTADO (activas/canceladas)
        // ========================================
        if ($request->filled('estado')) {
            if ($request->estado === 'activas') {
                $query->where('status', Order::STATUS_DELIVERED)
                    ->whereNull('cancelled_at');
            } elseif ($request->estado === 'canceladas') {
                $query->where(function ($q) {
                    $q->where('status', Order::STATUS_CANCELLED)
                        ->orWhereNotNull('cancelled_at');
                });
            }
        }

        // ========================================
        // PAGINACIÓN
        // ========================================
        $sales = $query->paginate(20);

        // ========================================
        // AJAX: Retornar solo la tabla
        // ========================================
        if ($request->ajax()) {
            return view('admin.pos-sales._table', compact('sales'));
        }

        // ========================================
        // KPIs
        // ========================================
        $baseQuery = Order::query()
            ->whereNull('cliente_id')
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR]%');

        // KPIs del día
        $today = now()->toDateString();

        $kpis = [
            'ventas_hoy' => (clone $baseQuery)
                ->whereDate('delivered_date', $today)
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNull('cancelled_at')
                ->count(),

            'total_hoy' => (clone $baseQuery)
                ->whereDate('delivered_date', $today)
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNull('cancelled_at')
                ->sum('total'),

            'canceladas_hoy' => (clone $baseQuery)
                ->whereDate('cancelled_at', $today)
                ->count(),

            'total_historico' => (clone $baseQuery)
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNull('cancelled_at')
                ->count(),
        ];

        // ========================================
        // VENDEDORES PARA FILTRO
        // ========================================
        $vendedores = User::whereIn('id', function ($q) {
            $q->select('created_by')
                ->from('orders')
                ->whereNull('cliente_id')
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR]%')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);

        return view('admin.pos-sales.index', compact('sales', 'kpis', 'vendedores'));
    }

    /**
     * GET /admin/pos-sales/{order}
     *
     * Detalle de una venta POS.
     */
    public function show(Order $order): View
    {
        // Validar que es venta POS
        if (!$order->isPosOrder() && $order->status !== Order::STATUS_CANCELLED) {
            abort(404, 'Venta POS no encontrada.');
        }

        // Cargar movimiento de stock asociado
        $movement = FinishedGoodsMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
            ->with('productVariant.product')
            ->first();

        // Cargar movimiento de devolución si existe
        $returnMovement = null;
        if ($order->isCancelled()) {
            $returnMovement = FinishedGoodsMovement::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('type', FinishedGoodsMovement::TYPE_RETURN)
                ->first();
        }

        $order->load(['creator', 'canceller']);

        return view('admin.pos-sales.show', compact('order', 'movement', 'returnMovement'));
    }

    /**
     * PATCH /admin/pos-sales/{order}/cancel
     *
     * Cancelar venta POS desde el backoffice.
     * Reutiliza la lógica de PosController::cancelSale.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        // ========================================
        // VALIDACIÓN DEL REQUEST
        // ========================================
        $request->validate([
            'cancel_reason' => 'required|string|min:10|max:255',
        ], [
            'cancel_reason.required' => 'El motivo de cancelación es OBLIGATORIO.',
            'cancel_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'cancel_reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ]);

        // Validar que es venta POS cancelable
        if (!$order->isPosOrder()) {
            return response()->json([
                'success' => false,
                'error' => 'Esta orden no es una venta POS.',
            ], 422);
        }

        if (!$order->canCancelPosOrder()) {
            return response()->json([
                'success' => false,
                'error' => 'Esta venta ya fue cancelada.',
            ], 422);
        }

        $cancelReason = trim($request->cancel_reason);
        $canceller = Auth::user();
        $cancelTimestamp = now();

        try {
            $result = DB::transaction(function () use (
                $order,
                $cancelReason,
                $canceller,
                $cancelTimestamp
            ) {
                // -----------------------------------------------------------------
                // PASO 1: Buscar el movimiento de salida original
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
                // PASO 2: Calcular stock actual y crear movimiento de devolución
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
                $returnMovement->notes = "CANCELACIÓN desde Historial POS #{$order->order_number} | " .
                    "Motivo: {$cancelReason} | " .
                    "Cancelado por: {$canceller->name}";
                $returnMovement->created_by = $canceller->id;
                $returnMovement->save();

                // -----------------------------------------------------------------
                // PASO 3: Actualizar el pedido
                // -----------------------------------------------------------------
                Order::withoutEvents(function () use ($order, $cancelTimestamp, $canceller, $cancelReason) {
                    $order->update([
                        'status' => Order::STATUS_CANCELLED,
                        'cancelled_at' => $cancelTimestamp,
                        'cancelled_by' => $canceller->id,
                        'cancel_reason' => $cancelReason,
                    ]);
                });

                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'cancelled_at' => $cancelTimestamp->format('Y-m-d H:i:s'),
                    'cancelled_by_name' => $canceller->name,
                    'cancel_reason' => $cancelReason,
                    'quantity_returned' => $quantityToReturn,
                    'stock_after' => $stockAfterReturn,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta POS cancelada. Stock revertido.',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Calcula stock REAL desde ledger.
     * (Copiado de PosController para independencia)
     */
    private function calculateRealStock(int $productVariantId): float
    {
        $productionEntries = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY)
            ->sum('quantity');

        $returns = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_RETURN)
            ->sum('quantity');

        $saleExits = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
            ->sum('quantity');

        $adjustments = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_ADJUSTMENT)
            ->sum('quantity');

        return $productionEntries + $returns - $saleExits + $adjustments;
    }
}
