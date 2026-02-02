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
        // NOTA: Ahora las ventas POS pueden tener cliente_id (opcional)
        // La marca distintiva es '[VENTA POS MOSTRADOR' en las notas
        // ========================================
        $query = Order::query()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
            ->with(['creator', 'canceller', 'cliente'])
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
        // OBTENER TODOS LOS REGISTROS (DataTable maneja paginación)
        // Incluye conteo de movimientos de stock (POS no usa OrderItems)
        // ========================================
        $sales = $query->get()->map(function ($sale) {
            // Contar movimientos de venta (sale_exit) asociados al pedido
            $sale->movements_count = FinishedGoodsMovement::where('reference_type', Order::class)
                ->where('reference_id', $sale->id)
                ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
                ->count();
            return $sale;
        });

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
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR%');

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
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
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
                // PASO 1: Buscar TODOS los movimientos de salida originales
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
                // PASO 2: Crear movimiento de devolución por cada ítem
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
                    $returnMovement->notes = "CANCELACIÓN desde Historial POS #{$order->order_number} | " .
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
                    'items_returned' => count($returnedItems),
                    'returned_items' => $returnedItems,
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
     * GET /admin/pos-sales/{order}/items
     *
     * Obtener los items de un pedido POS via AJAX.
     * NOTA: POS no usa OrderItems, usa FinishedGoodsMovement (TYPE_SALE_EXIT)
     */
    public function items(Order $order): JsonResponse
    {
        // Validar que es venta POS
        if (!str_contains($order->notes ?? '', '[VENTA POS MOSTRADOR')) {
            return response()->json([
                'success' => false,
                'error' => 'Esta orden no es una venta POS.',
            ], 404);
        }

        // Obtener movimientos de venta (sale_exit) asociados al pedido
        $movements = FinishedGoodsMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
            ->with(['productVariant.product'])
            ->get();

        // Calcular precio proporcional basado en subtotal y cantidades
        $totalQuantity = $movements->sum(fn($m) => abs((float) $m->quantity));
        $orderSubtotal = (float) $order->subtotal;

        $items = $movements->map(function ($movement) use ($orderSubtotal, $totalQuantity) {
            $variant = $movement->productVariant;
            $product = $variant?->product;
            $quantity = abs((float) $movement->quantity);

            // Precio: usar price de variante, o calcular proporcional del subtotal
            $unitPrice = (float) ($variant?->price ?? $product?->price ?? 0);

            // Si el precio es 0, calcular proporcional del subtotal total
            if ($unitPrice <= 0 && $totalQuantity > 0) {
                $unitPrice = $orderSubtotal / $totalQuantity;
            }

            return [
                'id' => $movement->id,
                'product_name' => $product?->name ?? 'Producto eliminado',
                'variant_name' => $variant?->attributes_display ?? '-',
                'quantity' => (int) $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
                'image_url' => $product?->primary_image_url ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'items_count' => $items->count(),
                'items' => $items,
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount,
                'iva_amount' => (float) $order->iva_amount,
                'total' => (float) $order->total,
            ],
        ]);
    }

    /**
     * GET /admin/pos-sales/cliente/{cliente}
     *
     * Obtener información completa de un cliente via AJAX.
     */
    public function getCliente(\App\Models\Cliente $cliente): JsonResponse
    {
        // Cargar relaciones
        $cliente->load(['estado', 'recomendacion']);

        // Calcular estadísticas del cliente
        $stats = [
            'total_compras_pos' => Order::where('cliente_id', $cliente->id)
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
                ->whereIn('status', [Order::STATUS_DELIVERED])
                ->whereNull('cancelled_at')
                ->count(),
            'monto_total_pos' => Order::where('cliente_id', $cliente->id)
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
                ->whereIn('status', [Order::STATUS_DELIVERED])
                ->whereNull('cancelled_at')
                ->sum('total'),
            'ultima_compra_pos' => Order::where('cliente_id', $cliente->id)
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
                ->whereIn('status', [Order::STATUS_DELIVERED])
                ->whereNull('cancelled_at')
                ->orderBy('delivered_date', 'desc')
                ->first()?->delivered_date?->format('d/m/Y'),
            'total_pedidos' => Order::where('cliente_id', $cliente->id)
                ->whereNotNull('cliente_id')
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%[VENTA POS MOSTRADOR%')
                        ->orWhereNull('notes');
                })
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'nombre_completo' => trim($cliente->nombre . ' ' . $cliente->apellidos),
                'nombre' => $cliente->nombre,
                'apellidos' => $cliente->apellidos,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'rfc' => $cliente->rfc,
                'razon_social' => $cliente->razon_social,
                'direccion' => $cliente->direccion,
                'ciudad' => $cliente->ciudad,
                'codigo_postal' => $cliente->codigo_postal,
                'estado' => $cliente->estado?->nombre_estado ?? null,
                'activo' => $cliente->activo,
                'observaciones' => $cliente->observaciones,
                'recomendacion' => $cliente->recomendacion?->nombre ?? null,
                'created_at' => $cliente->created_at?->format('d/m/Y'),
                // Medidas
                'medidas' => [
                    'busto' => $cliente->busto,
                    'alto_cintura' => $cliente->alto_cintura,
                    'cintura' => $cliente->cintura,
                    'cadera' => $cliente->cadera,
                    'largo' => $cliente->largo,
                    'largo_vestido' => $cliente->largo_vestido,
                ],
                // Estadísticas
                'stats' => $stats,
            ],
        ]);
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
