<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MaterialVariant;
use App\Models\ProductVariant;
use App\Models\FinishedGoodsMovement;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HomeController - Dashboard ERP MVP + Analytics de Ventas
 *
 * PRINCIPIO: Solo métricas matemáticamente correctas y auditables.
 * REGLA: Solo pedidos con status = 'delivered' se consideran ventas.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * REGLAS CONTABLES ERP (INVARIANTES)
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * FÓRMULA CANÓNICA:
 *   VENTA NETA = subtotal - discount
 *   total = subtotal - discount + iva_amount (SOLO informativo, NO usar en KPIs)
 *
 * REGLA 1: TODAS las métricas de "ventas" usan: (subtotal - discount)
 * REGLA 2: IVA es impuesto TRASLADADO, NO es ingreso
 * REGLA 3: El campo "total" incluye IVA, por lo tanto NO se usa en KPIs de ventas
 *
 * MÉTRICAS CORREGIDAS:
 *   - ventasPosDelMes: SUM(subtotal - discount)
 *   - ventasPedidosDelMes: SUM(subtotal - discount)
 *   - getVentasPorMesFijo(): SUM(subtotal - discount)
 *   - getVentasPorSemana(): SUM(subtotal - discount)
 *   - getTopProductos(): Distribución proporcional de (subtotal - discount)
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * ARQUITECTURA DE DATASETS:
 * - FIJO (solo index): ventasPorMes (4 meses históricos, no cambia con selector)
 * - DINÁMICO (AJAX): ventasPorSemana, topProductos (cambian con selector)
 */
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // ========================================
        // KPIs OPERATIVOS - FUENTE CANÓNICA
        // Usa la misma lógica que OrderController@index
        // ========================================
        $confirmedOrders = Order::where('status', Order::STATUS_CONFIRMED)
            ->with(['items'])
            ->get();

        $paraProducir = 0;
        $bloqueados = 0;

        foreach ($confirmedOrders as $order) {
            // Pedido bloqueado si:
            // 1. canStartProduction() === false (reglas R2-R5)
            // 2. O hasProductionInventoryBlock() === true (inventario insuficiente)
            $canStart = $order->canStartProduction();
            $hasInventoryBlock = $order->hasProductionInventoryBlock();

            if (!$canStart || $hasInventoryBlock) {
                $bloqueados++;
            } else {
                $paraProducir++;
            }
        }

        $enProduccion = Order::where('status', Order::STATUS_IN_PRODUCTION)->count();
        $paraEntregar = Order::where('status', Order::STATUS_READY)->count();
        $retrasados = Order::whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->whereDate('promised_date', '<', now())
            ->count();

        // KPIs operativos para la vista
        $kpis = [
            'para_producir' => $paraProducir,
            'bloqueados' => $bloqueados,
            'en_produccion' => $enProduccion,
            'para_entregar' => $paraEntregar,
            'retrasados' => $retrasados,
        ];

        // ========================================
        // KPI: VENTAS DEL MES - SEGMENTADAS POR CANAL
        // ========================================
        $mesActual = Carbon::now();
        $inicioMes = $mesActual->copy()->startOfMonth();
        $finMes = $mesActual->copy()->endOfMonth();

        // Ventas POS (con o sin cliente) - identificadas por marca en notas
        // ERP: VENTA NETA = subtotal - discount (NO incluye IVA)
        $ventasPosDelMes = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->selectRaw('COALESCE(SUM(subtotal - discount), 0) as venta_neta')
            ->value('venta_neta') ?? 0;

        // Pedidos normales (con cliente, NO son POS)
        // ERP: VENTA NETA = subtotal - discount (NO incluye IVA)
        $ventasPedidosDelMes = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->whereNotNull('cliente_id')
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere('notes', 'not like', '%[VENTA POS MOSTRADOR%');
            })
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->selectRaw('COALESCE(SUM(subtotal - discount), 0) as venta_neta')
            ->value('venta_neta') ?? 0;

        // Total combinado
        $ventasDelMes = $ventasPosDelMes + $ventasPedidosDelMes;

        // Array segmentado para la vista
        $ventasSegmentadas = [
            'total' => $ventasDelMes,
            'pedidos' => $ventasPedidosDelMes,
            'pos' => $ventasPosDelMes,
        ];

        // ========================================
        // KPI: INSUMOS EN RIESGO (Materiales bajo mínimo)
        // ========================================
        $insumosEnRiesgo = MaterialVariant::where('activo', true)
            ->whereColumn('current_stock', '<=', 'min_stock_alert')
            ->count();

        // ========================================
        // KPI: PRODUCTOS TERMINADOS EN RIESGO (BAJO STOCK + AGOTADOS)
        // Fuente: finished_goods_movements (SUM) vs stock_alert
        // REGLA: Misma lógica que FinishedGoodsStockController
        // Incluye: Bajo Stock (0 < stock <= alert) + Agotados (stock <= 0)
        // ========================================
        $variantesActivas = ProductVariant::where('activo', true)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->get()
            ->map(function ($variant) {
                // Calcular stock real desde el ledger (fórmula canónica)
                $stockReal = FinishedGoodsMovement::where('product_variant_id', $variant->id)
                    ->selectRaw("
                        COALESCE(SUM(CASE WHEN type IN ('production_entry', 'return') THEN quantity ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN type = 'sale_exit' THEN quantity ELSE 0 END), 0)
                        + COALESCE(SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END), 0)
                        as stock_calculado
                    ")
                    ->value('stock_calculado') ?? 0;

                $variant->stock_real = (float) $stockReal;
                return $variant;
            });

        // Contar: Stock Bajo (stock > 0 Y stock <= alert)
        // Solo muestra cuando hay stock pero está en el límite de alerta
        $productosBajoStock = $variantesActivas->filter(function ($variant) {
            $stockAlert = $variant->stock_alert ?? 0;
            // Tiene stock > 0 pero está en o bajo el umbral de alerta
            return $variant->stock_real > 0 && $variant->stock_real <= $stockAlert;
        })->count();

        // Contar: Agotados (stock <= 0)
        $productosAgotados = $variantesActivas->filter(function ($variant) {
            return $variant->stock_real <= 0;
        })->count();

        // ========================================
        // ANALYTICS: MESES CON VENTAS (para selector)
        // ========================================
        $mesesConVentas = $this->getMesesConVentas();

        // ========================================
        // DATASET FIJO: VENTAS POR MES (4 meses históricos)
        // Se calcula UNA sola vez, NO cambia con el selector
        // ========================================
        $ventasPorMesFijo = $this->getVentasPorMesFijo($mesesConVentas);

        // ========================================
        // DATASETS CONTEXTUALES INICIALES
        // ========================================
        $mesSeleccionado = $mesesConVentas->first();
        $analyticsContextual = $mesSeleccionado
            ? $this->getAnalyticsContextual($mesSeleccionado['year'], $mesSeleccionado['month'])
            : $this->getEmptyContextual();

        // ========================================
        // DATOS AUXILIARES
        // ========================================
        $nombreMes = $mesActual->translatedFormat('F Y');

        // Combinar para la vista
        $analyticsData = array_merge(
            ['ventasPorMes' => $ventasPorMesFijo],
            $analyticsContextual
        );

        return view('home', compact(
            'kpis',
            'ventasDelMes',
            'ventasSegmentadas',
            'insumosEnRiesgo',
            'productosBajoStock',
            'productosAgotados',
            'nombreMes',
            'mesesConVentas',
            'analyticsData'
        ));
    }

    /**
     * Endpoint AJAX para obtener analytics CONTEXTUALES de un mes específico.
     * NO retorna ventasPorMes (ese dataset es FIJO).
     *
     * GET /home/analytics?year=2026&month=1
     */
    public function analytics(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = (int) $request->year;
        $month = (int) $request->month;

        // Verificar que el mes tiene ventas reales (excluye producción para stock y canceladas)
        $tieneVentas = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNotNull('cliente_id')
                  ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
            })
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->exists();

        if (!$tieneVentas) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ventas registradas para este período',
            ], 404);
        }

        // SOLO datos contextuales, NO ventasPorMes
        $data = $this->getAnalyticsContextual($year, $month);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtiene los últimos 4 meses con ventas reales.
     *
     * SQL: SELECT DISTINCT YEAR(created_at) as year, MONTH(created_at) as month
     *      FROM orders WHERE status = 'delivered'
     *      ORDER BY year DESC, month DESC LIMIT 4
     */
    private function getMesesConVentas(): \Illuminate\Support\Collection
    {
        // Meses con ventas reales (con cliente O ventas POS), excluye canceladas
        return Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNotNull('cliente_id')
                  ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
            })
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(4)
            ->get()
            ->map(function ($item) {
                $fecha = Carbon::createFromDate($item->year, $item->month, 1);
                return [
                    'year' => (int) $item->year,
                    'month' => (int) $item->month,
                    'label' => $fecha->translatedFormat('F Y'),
                    'value' => "{$item->year}-{$item->month}",
                ];
            });
    }

    /**
     * DATASET FIJO: Ventas por Mes (últimos 4 meses con ventas reales)
     *
     * CONTRATO:
     * - Se calcula SOLO en index()
     * - NO se recalcula en AJAX
     * - NO depende del selector
     * - Exactamente 4 elementos, orden cronológico
     *
     * @param \Illuminate\Support\Collection $mesesValidos Los 4 meses del selector (orden DESC)
     * @return array Exactamente 4 elementos, orden antiguo → reciente
     */
    private function getVentasPorMesFijo(\Illuminate\Support\Collection $mesesValidos): array
    {
        if ($mesesValidos->isEmpty()) {
            return [];
        }

        // Invertir para orden cronológico (antiguo → reciente)
        $mesesCronologicos = $mesesValidos->reverse()->values();

        return $mesesCronologicos->map(function ($mes) {
            $inicioMes = Carbon::createFromDate($mes['year'], $mes['month'], 1)->startOfMonth();
            $finMes = Carbon::createFromDate($mes['year'], $mes['month'], 1)->endOfMonth();

            // Ventas con cliente O ventas POS (con o sin cliente), excluye canceladas
            // ERP: VENTA NETA = subtotal - discount (NO incluye IVA)
            $ventaNeta = Order::where('status', Order::STATUS_DELIVERED)
                ->whereNull('cancelled_at')
                ->where(function ($q) {
                    $q->whereNotNull('cliente_id')
                      ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
                })
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->selectRaw('COALESCE(SUM(subtotal - discount), 0) as venta_neta')
                ->value('venta_neta') ?? 0;

            return [
                'label' => Carbon::createFromDate($mes['year'], $mes['month'], 1)->translatedFormat('M Y'),
                'total' => (float) $ventaNeta,
            ];
        })->toArray();
    }

    /**
     * DATASETS CONTEXTUALES: Datos que cambian según el mes seleccionado.
     * Se usa tanto en index() como en analytics().
     */
    private function getAnalyticsContextual(int $year, int $month): array
    {
        return [
            'mesSeleccionado' => [
                'year' => $year,
                'month' => $month,
                'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            ],
            'ventasPorSemana' => $this->getVentasPorSemana($year, $month),
            'topProductos' => $this->getTopProductos($year, $month),
        ];
    }

    /**
     * Retorna estructura vacía cuando no hay datos.
     */
    private function getEmptyContextual(): array
    {
        return [
            'mesSeleccionado' => null,
            'ventasPorSemana' => [],
            'topProductos' => [],
        ];
    }

    /**
     * GRÁFICA CONTEXTUAL: Ventas por Semana (mes específico)
     * Muestra el rango de días de cada semana: "Sem 1 (1-7)"
     */
    private function getVentasPorSemana(int $year, int $month): array
    {
        $inicioMes = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Ventas con cliente O ventas POS (con o sin cliente), excluye canceladas
        // ERP: VENTA NETA = subtotal - discount (NO incluye IVA)
        return Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNotNull('cliente_id')
                  ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
            })
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->selectRaw('WEEK(created_at, 1) as week_num, MIN(created_at) as first_day, MAX(created_at) as last_day, SUM(subtotal - discount) as venta_neta')
            ->groupBy('week_num')
            ->orderBy('week_num')
            ->get()
            ->map(function ($item, $index) use ($inicioMes, $finMes) {
                // Calcular inicio de semana (lunes) y fin de semana (domingo)
                $firstDay = Carbon::parse($item->first_day);
                $weekStart = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
                $weekEnd = $firstDay->copy()->endOfWeek(Carbon::SUNDAY);

                // Limitar al mes actual (no mostrar días de otros meses)
                $dayStart = max($weekStart->day, $inicioMes->day);
                $dayEnd = min($weekEnd->day, $finMes->day);

                // Si la semana cruza meses, ajustar
                if ($weekStart->month != $inicioMes->month) {
                    $dayStart = 1;
                }
                if ($weekEnd->month != $finMes->month) {
                    $dayEnd = $finMes->day;
                }

                return [
                    'label' => 'Sem ' . ($index + 1) . ' (' . $dayStart . '-' . $dayEnd . ')',
                    'total' => (float) $item->venta_neta,
                ];
            })
            ->toArray();
    }

    /**
     * GRÁFICA CONTEXTUAL: Top 5 Productos Vendidos (mes específico)
     *
     * COMBINA DOS FUENTES:
     * 1. OrderItems: Pedidos normales (con cliente, flujo completo)
     * 2. FinishedGoodsMovement: Ventas POS (sale_exit directo desde stock)
     *
     * IMPORTANTE: Para ventas POS, el valor se calcula proporcionalmente
     * desde Order.subtotal (valor real cobrado), NO desde precios de catálogo.
     */
    private function getTopProductos(int $year, int $month): array
    {
        $inicioMes = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // ========================================
        // FUENTE 1: OrderItems (Pedidos normales - NO POS)
        // Usa order_items.total que ya tiene el valor real vendido
        // ========================================
        $fromOrderItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->whereNull('orders.cancelled_at')
            ->whereNotNull('orders.cliente_id')
            ->where(function ($q) {
                $q->whereNull('orders.notes')
                  ->orWhere('orders.notes', 'not like', '%[VENTA POS MOSTRADOR%');
            })
            ->whereBetween('orders.created_at', [$inicioMes, $finMes])
            ->selectRaw('order_items.product_name as producto, SUM(order_items.quantity) as cantidad, SUM(order_items.total) as valor')
            ->groupBy('order_items.product_name')
            ->get();

        // ========================================
        // FUENTE 2: Ventas POS - Valor REAL NETO (subtotal - discount)
        // ERP: NO incluir IVA en métricas de ventas
        // ========================================
        $posOrders = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNull('cancelled_at')
            ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->get(['id', 'subtotal', 'discount']);

        $fromPosData = collect();

        foreach ($posOrders as $order) {
            // Obtener movimientos de este pedido POS
            $movements = FinishedGoodsMovement::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
                ->with('productVariant.product')
                ->get();

            if ($movements->isEmpty()) {
                continue;
            }

            // Calcular cantidad total del pedido para distribución proporcional
            $totalQty = $movements->sum(fn($m) => abs((float) $m->quantity));
            // ERP: VENTA NETA = subtotal - discount (NO incluye IVA)
            $orderVentaNeta = (float) $order->subtotal - (float) $order->discount;

            foreach ($movements as $movement) {
                $product = $movement->productVariant?->product;
                if (!$product) {
                    continue;
                }

                $qty = abs((float) $movement->quantity);
                // Valor proporcional = (cantidad / total_cantidad) * venta_neta_order
                $valorProporcional = $totalQty > 0 ? ($qty / $totalQty) * $orderVentaNeta : 0;

                $key = $product->name;
                if ($fromPosData->has($key)) {
                    $existing = $fromPosData->get($key);
                    $fromPosData->put($key, [
                        'producto' => $key,
                        'cantidad' => $existing['cantidad'] + (int) $qty,
                        'valor' => $existing['valor'] + $valorProporcional,
                    ]);
                } else {
                    $fromPosData->put($key, [
                        'producto' => $key,
                        'cantidad' => (int) $qty,
                        'valor' => $valorProporcional,
                    ]);
                }
            }
        }

        // ========================================
        // COMBINAR AMBAS FUENTES
        // ========================================
        $combined = collect();

        foreach ($fromOrderItems as $item) {
            $key = $item->producto;
            if ($combined->has($key)) {
                $existing = $combined->get($key);
                $combined->put($key, [
                    'producto' => $key,
                    'cantidad' => $existing['cantidad'] + (int) $item->cantidad,
                    'valor' => $existing['valor'] + (float) $item->valor,
                ]);
            } else {
                $combined->put($key, [
                    'producto' => $key,
                    'cantidad' => (int) $item->cantidad,
                    'valor' => (float) $item->valor,
                ]);
            }
        }

        foreach ($fromPosData as $key => $item) {
            if ($combined->has($key)) {
                $existing = $combined->get($key);
                $combined->put($key, [
                    'producto' => $key,
                    'cantidad' => $existing['cantidad'] + $item['cantidad'],
                    'valor' => $existing['valor'] + $item['valor'],
                ]);
            } else {
                $combined->put($key, $item);
            }
        }

        // Ordenar por cantidad y tomar top 5
        return $combined->sortByDesc('cantidad')
            ->take(5)
            ->values()
            ->toArray();
    }
}
