<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;

/**
 * PASO 12: FUENTE TRANSVERSAL ÚNICA DE AGENDA (READ-ONLY)
 *
 * Servicio canónico para lectura de agenda de pedidos.
 * TODAS las consultas de visualización de pedidos por fecha
 * deben pasar por este servicio.
 *
 * REGLAS INVIOLABLES:
 * - SOLO LECTURA. No modifica datos.
 * - No valida capacidad (eso es ProductionCapacityService).
 * - No reprograma fechas (eso es OrderService).
 * - Excluye CANCELLED y promised_date NULL por contrato.
 * - Contrato de datos canónico (no se fragmenta por módulo).
 *
 * FUENTE DE VERDAD: Order.promised_date + Order.status
 */
class OrderCalendarReadService
{
    public function __construct(
        protected ProductionCapacityService $capacityService
    ) {}

    // =========================================================================
    // CONTRATO PRINCIPAL: EVENTOS POR RANGO
    // =========================================================================

    /**
     * Retorna pedidos con promised_date dentro de un rango.
     * Contrato canónico — estructura idéntica sin importar el consumidor.
     *
     * @param Carbon $start Inicio del rango (inclusive)
     * @param Carbon $end Fin del rango (inclusive)
     * @return array Lista de eventos con estructura canónica
     */
    public function getEventsByRange(Carbon $start, Carbon $end): array
    {
        $orders = Order::whereNotNull('promised_date')
            ->whereBetween('promised_date', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->with('cliente:id,nombre,apellidos')
            ->select([
                'id', 'order_number', 'cliente_id', 'status', 'urgency_level',
                'promised_date', 'total', 'priority',
            ])
            ->orderBy('promised_date')
            ->orderBy('priority')
            ->get();

        return $orders->map(function (Order $order) {
            return $this->formatEvent($order);
        })->values()->all();
    }

    // =========================================================================
    // CONTRATO PRINCIPAL: UTILIZACIÓN SEMANAL
    // =========================================================================

    /**
     * Retorna snapshot de capacidad semanal para un rango de fechas.
     * Delega a ProductionCapacityService (fuente autoritaria de capacidad).
     *
     * @param Carbon $start Inicio del rango
     * @param Carbon $end Fin del rango
     * @return array Mapa [YYYY-WW => snapshot] de capacidad semanal
     */
    public function getWeekUtilizationSnapshot(Carbon $start, Carbon $end): array
    {
        $weekCapacities = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);

        while ($cursor->lte($end)) {
            $y = (int) $cursor->isoWeekYear;
            $w = (int) $cursor->isoWeek;
            $key = "{$y}-W{$w}";

            if (!isset($weekCapacities[$key])) {
                $snapshot = $this->capacityService->getCapacitySnapshot($y, $w);
                $weekCapacities[$key] = [
                    'year' => $snapshot['year'],
                    'week' => $snapshot['week'],
                    'week_start' => $snapshot['week_start'],
                    'week_end' => $snapshot['week_end'],
                    'used' => $snapshot['used'],
                    'max' => $snapshot['max'],
                    'available' => $snapshot['available'],
                    'utilization_percent' => $snapshot['utilization_percent'],
                    'is_full' => $snapshot['is_full'],
                ];
            }

            $cursor->addWeek();
        }

        return $weekCapacities;
    }

    // =========================================================================
    // MÉTODO COMBINADO: EVENTOS + UTILIZACIÓN
    // =========================================================================

    /**
     * Retorna el contrato completo (eventos + utilización) para un rango.
     * Este es el método que deben consumir todos los módulos.
     *
     * @param Carbon $start Inicio del rango
     * @param Carbon $end Fin del rango
     * @return array { events: array, week_utilization: array }
     */
    public function getCalendarData(Carbon $start, Carbon $end): array
    {
        return [
            'events' => $this->getEventsByRange($start, $end),
            'week_utilization' => $this->getWeekUtilizationSnapshot($start, $end),
        ];
    }

    // =========================================================================
    // MÉTRICAS TRANSVERSALES (READ-ONLY)
    // =========================================================================

    /**
     * Cuenta pedidos con promised_date vencida (retrasados).
     * Fuente única para: Dashboard, Orders Index, Production Queue.
     *
     * REGLA: Solo pedidos activos (no delivered, no cancelled) con
     *        promised_date anterior a hoy.
     *
     * @return int Número de pedidos retrasados
     */
    public function countOverdueOrders(): int
    {
        return Order::overdue()->count();
    }

    /**
     * Cuenta pedidos retrasados filtrados por estados específicos.
     * Usado por ProductionQueueController (solo confirmed + in_production).
     *
     * @param array $statuses Estados a incluir en el conteo
     * @return int Número de pedidos retrasados en esos estados
     */
    public function countOverdueOrdersByStatuses(array $statuses): int
    {
        return Order::whereIn('status', $statuses)
            ->whereNotNull('promised_date')
            ->whereDate('promised_date', '<', today())
            ->count();
    }

    // =========================================================================
    // FORMATO CANÓNICO DE EVENTO
    // =========================================================================

    /**
     * Formatea un pedido al contrato de datos canónico.
     * Esta estructura es LEY — no se fragmenta por módulo.
     */
    private function formatEvent(Order $order): array
    {
        $clientName = $order->cliente
            ? "{$order->cliente->nombre} {$order->cliente->apellidos}"
            : null;

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'promised_date' => $order->promised_date->format('Y-m-d'),
            'status' => $order->status,
            'status_label' => $order->status_label,
            'urgency' => $order->urgency_level,
            'urgency_label' => $order->urgency_label,
            'client' => $clientName,
            'total' => (float) $order->total,
            'can_reschedule' => $order->canReschedulePromisedDate(),
            'show_url' => route('admin.orders.show', $order->id),
        ];
    }
}
