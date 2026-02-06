<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * VENTAS CONSOLIDADAS - MÓDULO ERP CANÓNICO
 *
 * RESPONSABILIDAD ÚNICA:
 * Consolidar y exponer TODAS las ventas reales (POS + PEDIDOS)
 * desde UNA SOLA fuente de verdad: tabla `orders`.
 *
 * REGLAS CONTABLES INVARIANTES:
 * - SOLO órdenes con status = DELIVERED son ventas reales
 * - Venta Neta = subtotal - discount (IVA NO es ingreso)
 * - IVA siempre separado (informativo)
 * - Total = subtotal - discount + iva_amount
 *
 * EXCLUSIONES:
 * - Producción para stock (cliente_id = NULL sin marca POS)
 *   → No es venta real, es producción interna para inventario
 *
 * INCLUYE:
 * - POS sin cliente (Venta Libre) → ES venta real al público
 * - Pedidos con cliente → ES venta real
 *
 * NO MODIFICA:
 * - POS (intacto)
 * - Pedidos (intactos)
 * - Dashboard (intacto)
 * - Inventarios (no toca)
 */
class SalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /admin/sales
     *
     * Listado consolidado de TODAS las ventas (POS + PEDIDOS).
     * Fuente única: orders WHERE status = 'delivered'
     */
    public function index(Request $request): View
    {
        // =====================================================================
        // QUERY BASE CANÓNICA: SOLO VENTAS REALES (DELIVERED)
        // EXCLUYE: Producción para stock (cliente_id = NULL sin marca POS)
        // INCLUYE: POS sin cliente (Venta Libre real) + Pedidos con cliente
        // =====================================================================
        $query = Order::query()
            ->where('status', Order::STATUS_DELIVERED)
            ->where(function ($q) {
                // Incluir: Pedidos con cliente asignado
                $q->whereNotNull('cliente_id')
                    // Incluir: Ventas POS (aunque no tengan cliente = Venta Libre)
                    ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
            })
            ->with(['cliente', 'creator'])
            ->select([
                'id',
                'uuid',
                'order_number',
                'cliente_id',
                'status',
                'subtotal',
                'discount',
                'discount_reason',
                'iva_amount',
                'total',
                'payment_method',
                'payment_status',
                'delivered_date',
                'sold_at',
                'seller_name',
                'created_by',
                'notes',
                'created_at',
            ]);

        // =====================================================================
        // FILTRO 1: RANGO DE FECHAS (server-side)
        // Prioridad: sold_at (POS) → delivered_date (PEDIDO)
        // =====================================================================
        if ($request->filled('fecha_desde')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('sold_at', '>=', $request->fecha_desde)
                    ->orWhere(function ($q2) use ($request) {
                        $q2->whereNull('sold_at')
                            ->whereDate('delivered_date', '>=', $request->fecha_desde);
                    });
            });
        }

        if ($request->filled('fecha_hasta')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('sold_at', '<=', $request->fecha_hasta)
                    ->orWhere(function ($q2) use ($request) {
                        $q2->whereNull('sold_at')
                            ->whereDate('delivered_date', '<=', $request->fecha_hasta);
                    });
            });
        }

        // =====================================================================
        // FILTRO 2: VENDEDOR (created_by)
        // =====================================================================
        if ($request->filled('vendedor_id')) {
            $query->where('created_by', $request->vendedor_id);
        }

        // =====================================================================
        // FILTRO 3: ORIGEN (POS vs PEDIDO)
        // POS: notes LIKE '%[VENTA POS MOSTRADOR%'
        // PEDIDO: NOT LIKE o NULL
        // =====================================================================
        if ($request->filled('origen')) {
            if ($request->origen === 'POS') {
                $query->where('notes', 'like', '%[VENTA POS MOSTRADOR%');
            } elseif ($request->origen === 'PEDIDO') {
                $query->where(function ($q) {
                    $q->where('notes', 'not like', '%[VENTA POS MOSTRADOR%')
                        ->orWhereNull('notes');
                });
            }
        }

        // =====================================================================
        // FILTRO 4: CLIENTE
        // cliente_id específico o NULL (Venta Libre)
        // =====================================================================
        if ($request->filled('cliente_id')) {
            if ($request->cliente_id === 'null') {
                $query->whereNull('cliente_id');
            } else {
                $query->where('cliente_id', $request->cliente_id);
            }
        }

        // =====================================================================
        // ORDENAMIENTO: Por fecha de venta descendente
        // Prioridad: sold_at (POS) → delivered_date (PEDIDO)
        // =====================================================================
        $query->orderByRaw('COALESCE(sold_at, delivered_date) DESC');

        // =====================================================================
        // OBTENER RESULTADOS
        // =====================================================================
        $sales = $query->get();

        // =====================================================================
        // CALCULAR KPIs CONTABLES (ERP Canónico)
        // EXCLUYE: Producción para stock (no son ventas reales)
        // =====================================================================
        $today = now()->toDateString();

        // Query base para KPIs (reutilizable) - EXCLUYE stock production
        $baseKpiQuery = Order::query()
            ->where('status', Order::STATUS_DELIVERED)
            ->where(function ($q) {
                $q->whereNotNull('cliente_id')
                    ->orWhere('notes', 'like', '%[VENTA POS MOSTRADOR%');
            });

        // KPIs del día actual
        $kpis = [
            // Ventas del día (conteo)
            'ventas_hoy' => (clone $baseKpiQuery)
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->count(),

            // Venta Neta del día (subtotal - discount) - NO incluye IVA
            'venta_neta_hoy' => (clone $baseKpiQuery)
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->selectRaw('COALESCE(SUM(subtotal - discount), 0) as total')
                ->value('total'),

            // IVA cobrado hoy (informativo, NO es ingreso)
            'iva_hoy' => (clone $baseKpiQuery)
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->sum('iva_amount'),

            // Total cobrado hoy (informativo)
            'total_cobrado_hoy' => (clone $baseKpiQuery)
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->sum('total'),

            // Ventas POS del día
            'ventas_pos_hoy' => (clone $baseKpiQuery)
                ->where('notes', 'like', '%[VENTA POS MOSTRADOR%')
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->count(),

            // Pedidos entregados hoy (excluye POS y stock production)
            'pedidos_hoy' => (clone $baseKpiQuery)
                ->whereNotNull('cliente_id') // Solo pedidos CON cliente (excluye stock)
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%[VENTA POS MOSTRADOR%')
                        ->orWhereNull('notes');
                })
                ->where(function ($q) use ($today) {
                    $q->whereDate('sold_at', $today)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNull('sold_at')
                                ->whereDate('delivered_date', $today);
                        });
                })
                ->count(),

            // Total histórico de ventas
            'total_historico' => (clone $baseKpiQuery)->count(),
        ];

        // =====================================================================
        // DATOS PARA FILTROS
        // =====================================================================

        // Vendedores que han realizado ventas
        $vendedores = User::whereIn('id', function ($q) {
            $q->select('created_by')
                ->from('orders')
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNotNull('created_by')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);

        // Clientes con ventas (para autocomplete futuro)
        $clientes = Cliente::whereIn('id', function ($q) {
            $q->select('cliente_id')
                ->from('orders')
                ->where('status', Order::STATUS_DELIVERED)
                ->whereNotNull('cliente_id')
                ->distinct();
        })->orderBy('nombre')->get(['id', 'nombre', 'apellidos']);

        // =====================================================================
        // AJAX: Retornar solo la tabla
        // =====================================================================
        if ($request->ajax()) {
            return view('admin.sales._table', compact('sales'));
        }

        return view('admin.sales.index', compact('sales', 'kpis', 'vendedores', 'clientes'));
    }

    /**
     * GET /admin/sales/{order}
     *
     * Detalle de una venta específica.
     */
    public function show(Order $order): View
    {
        // Validar que es una venta real (DELIVERED)
        if ($order->status !== Order::STATUS_DELIVERED) {
            abort(404, 'Venta no encontrada.');
        }

        // Cargar relaciones necesarias
        $order->load(['cliente', 'creator', 'items.product', 'payments']);

        // Determinar origen
        $origen = str_contains($order->notes ?? '', '[VENTA POS MOSTRADOR') ? 'POS' : 'PEDIDO';

        return view('admin.sales.show', compact('order', 'origen'));
    }
}
