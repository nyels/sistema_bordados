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
        // KPI: VENTAS CONFIRMADAS DEL MES
        // ========================================
        $mesActual = Carbon::now();
        $inicioMes = $mesActual->copy()->startOfMonth();
        $finMes = $mesActual->copy()->endOfMonth();

        // PASO 6: Solo ventas CON cliente (excluye producción para stock)
        $ventasDelMes = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('cliente_id')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->sum('total');

        // ========================================
        // KPI: INSUMOS EN RIESGO (Materiales bajo mínimo)
        // ========================================
        $insumosEnRiesgo = MaterialVariant::where('activo', true)
            ->whereColumn('current_stock', '<=', 'min_stock_alert')
            ->count();

        // ========================================
        // KPI: PRODUCTOS TERMINADOS BAJO STOCK (LEDGER-BASED)
        // Fuente: finished_goods_movements (SUM) vs stock_alert
        // REGLA ERP: Solo variantes CON movimientos reales cuentan
        // Si no existe ledger → NO existe inventario → NO cuenta
        // ========================================
        $productosBajoStock = ProductVariant::where('activo', true)
            ->whereHas('finishedGoodsMovements') // SOLO variantes con ledger real
            ->get()
            ->filter(function ($variant) {
                // Calcular stock real desde el ledger (fórmula canónica)
                $stockReal = FinishedGoodsMovement::where('product_variant_id', $variant->id)
                    ->selectRaw("
                        COALESCE(SUM(CASE WHEN type IN ('production_entry', 'return') THEN quantity ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN type = 'sale_exit' THEN quantity ELSE 0 END), 0)
                        + COALESCE(SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END), 0)
                        as stock_calculado
                    ")
                    ->value('stock_calculado') ?? 0;

                // Comparar con el umbral de alerta
                $stockAlert = $variant->stock_alert ?? 0;
                return (float) $stockReal <= (float) $stockAlert;
            })
            ->count();

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
            'insumosEnRiesgo',
            'productosBajoStock',
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

        // Verificar que el mes tiene ventas reales (excluye producción para stock)
        $tieneVentas = Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('cliente_id')
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
        // PASO 6: Solo meses con ventas reales (excluye producción para stock)
        return Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('cliente_id')
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

            // PASO 6: Solo ventas CON cliente (excluye producción para stock)
            $total = Order::where('status', Order::STATUS_DELIVERED)
                ->whereNotNull('cliente_id')
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->sum('total');

            return [
                'label' => Carbon::createFromDate($mes['year'], $mes['month'], 1)->translatedFormat('M Y'),
                'total' => (float) $total,
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
     */
    private function getVentasPorSemana(int $year, int $month): array
    {
        $inicioMes = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // PASO 6: Solo ventas CON cliente (excluye producción para stock)
        return Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('cliente_id')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->selectRaw('WEEK(created_at, 1) as week_num, MIN(created_at) as first_day, SUM(total) as total')
            ->groupBy('week_num')
            ->orderBy('week_num')
            ->get()
            ->map(function ($item, $index) {
                return [
                    'label' => 'Sem ' . ($index + 1),
                    'total' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * GRÁFICA CONTEXTUAL: Top 5 Productos Vendidos (mes específico)
     */
    private function getTopProductos(int $year, int $month): array
    {
        $inicioMes = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $finMes = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // PASO 6: Solo ventas CON cliente (excluye producción para stock)
        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->whereNotNull('orders.cliente_id')
            ->whereBetween('orders.created_at', [$inicioMes, $finMes])
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as cantidad, SUM(order_items.total) as valor')
            ->groupBy('order_items.product_name')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'producto' => $item->product_name,
                    'cantidad' => (int) $item->cantidad,
                    'valor' => (float) $item->valor,
                ];
            })
            ->toArray();
    }
}
