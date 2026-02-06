<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================================
 * SERVICIO: Motor de Capacidad de Producción Semanal
 * ============================================================================
 *
 * PROPÓSITO:
 * Gestiona la capacidad finita de producción del taller de bordados.
 * Permite consultar, validar y planificar la carga semanal de pedidos.
 *
 * UNIDAD DE PLANEACIÓN: Semana ISO (año + número de semana)
 *
 * REGLAS INVIOLABLES:
 * 1. Un pedido ocupa 1 SLOT completo (independiente de complejidad)
 * 2. El slot se asigna según la semana de promised_date
 * 3. Solo cuentan pedidos con estado: confirmed, in_production, ready
 * 4. NO cuentan: draft, delivered, cancelled
 *
 * ESTADOS ACTIVOS (ocupan capacidad):
 * - confirmed: Pedido comprometido, esperando producción
 * - in_production: En proceso de fabricación
 * - ready: Producido, esperando entrega
 *
 * ESTADOS INACTIVOS (NO ocupan capacidad):
 * - draft: Borrador, no compromiso
 * - delivered: Ya entregado, liberó capacidad
 * - cancelled: Cancelado, liberó capacidad
 *
 * ============================================================================
 */
class ProductionCapacityService
{
    /**
     * Estados de pedido que OCUPAN capacidad de producción.
     * FUENTE CANÓNICA: Solo estos estados cuentan contra el límite semanal.
     */
    public const ACTIVE_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_IN_PRODUCTION,
        Order::STATUS_READY,
    ];

    /**
     * Key del setting de capacidad máxima en system_settings.
     */
    public const SETTING_KEY = 'production_max_orders_per_week';

    /**
     * Valor por defecto si el setting no existe.
     */
    public const DEFAULT_MAX_CAPACITY = 10;

    /**
     * TTL del cache para consultas de capacidad (en segundos).
     * 5 minutos = 300 segundos
     */
    public const CACHE_TTL = 300;

    // =========================================================================
    // === MÉTODOS DE CONFIGURACIÓN ===
    // =========================================================================

    /**
     * Obtiene la capacidad máxima de pedidos por semana.
     *
     * FUENTE: system_settings.production_max_orders_per_week
     * FALLBACK: DEFAULT_MAX_CAPACITY (10)
     *
     * @return int Número máximo de pedidos permitidos por semana
     */
    public function getWeeklyCapacity(): int
    {
        $value = SystemSetting::getValue(
            self::SETTING_KEY,
            self::DEFAULT_MAX_CAPACITY
        );

        return max(1, (int) $value);
    }

    // =========================================================================
    // === MÉTODOS DE CONSULTA DE CAPACIDAD ===
    // =========================================================================

    /**
     * Cuenta pedidos activos asignados a una semana específica.
     *
     * LÓGICA:
     * - Filtra por promised_date dentro de la semana ISO
     * - Solo cuenta estados ACTIVE_STATUSES
     * - Excluye soft-deleted
     *
     * @param int $year Año ISO (ej: 2026)
     * @param int $week Semana ISO (1-53)
     * @return int Número de pedidos ocupando la semana
     */
    public function getUsedCapacityForWeek(int $year, int $week): int
    {
        // Validar rango de semana ISO
        $week = max(1, min(53, $week));

        // Calcular inicio y fin de la semana ISO
        $weekStart = $this->getWeekStartDate($year, $week);
        $weekEnd = $this->getWeekEndDate($year, $week);

        return Order::whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('promised_date')
            ->whereBetween('promised_date', [$weekStart, $weekEnd])
            ->count();
    }

    /**
     * Verifica si una semana tiene capacidad disponible.
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return bool TRUE si hay al menos 1 slot disponible
     */
    public function hasAvailableCapacity(int $year, int $week): bool
    {
        $used = $this->getUsedCapacityForWeek($year, $week);
        $max = $this->getWeeklyCapacity();

        return $used < $max;
    }

    /**
     * Obtiene snapshot completo de capacidad para una semana.
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return array {
     *     year: int,
     *     week: int,
     *     week_start: string (Y-m-d),
     *     week_end: string (Y-m-d),
     *     max: int,
     *     used: int,
     *     available: int,
     *     is_full: bool,
     *     utilization_percent: float
     * }
     */
    public function getCapacitySnapshot(int $year, int $week): array
    {
        $max = $this->getWeeklyCapacity();
        $used = $this->getUsedCapacityForWeek($year, $week);
        $available = max(0, $max - $used);
        $isFull = $available === 0;
        $utilizationPercent = $max > 0 ? round(($used / $max) * 100, 1) : 0;

        $weekStart = $this->getWeekStartDate($year, $week);
        $weekEnd = $this->getWeekEndDate($year, $week);

        return [
            'year' => $year,
            'week' => $week,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'max' => $max,
            'used' => $used,
            'available' => $available,
            'is_full' => $isFull,
            'utilization_percent' => $utilizationPercent,
        ];
    }

    // =========================================================================
    // === MÉTODOS DE CONSULTA PARA RANGOS ===
    // =========================================================================

    /**
     * Obtiene capacidad de múltiples semanas consecutivas.
     * Útil para mostrar panorama de carga en dashboard.
     *
     * @param int $weeksAhead Número de semanas a consultar desde hoy
     * @return array Lista de snapshots de capacidad
     */
    public function getCapacityForecast(int $weeksAhead = 8): array
    {
        $forecast = [];
        $now = Carbon::now();

        for ($i = 0; $i < $weeksAhead; $i++) {
            $targetDate = $now->copy()->addWeeks($i);
            $year = (int) $targetDate->isoWeekYear;
            $week = (int) $targetDate->isoWeek;

            $forecast[] = $this->getCapacitySnapshot($year, $week);
        }

        return $forecast;
    }

    /**
     * Encuentra la próxima semana con capacidad disponible.
     *
     * @param int|null $startYear Año desde donde buscar (default: actual)
     * @param int|null $startWeek Semana desde donde buscar (default: actual)
     * @param int $maxWeeksToSearch Límite de búsqueda (default: 52)
     * @return array|null Snapshot de la semana disponible o NULL si no hay
     */
    public function findNextAvailableWeek(
        ?int $startYear = null,
        ?int $startWeek = null,
        int $maxWeeksToSearch = 52
    ): ?array {
        $now = Carbon::now();
        $startYear = $startYear ?? (int) $now->isoWeekYear;
        $startWeek = $startWeek ?? (int) $now->isoWeek;

        $searchDate = Carbon::now()
            ->setISODate($startYear, $startWeek)
            ->startOfWeek();

        for ($i = 0; $i < $maxWeeksToSearch; $i++) {
            $year = (int) $searchDate->isoWeekYear;
            $week = (int) $searchDate->isoWeek;

            if ($this->hasAvailableCapacity($year, $week)) {
                return $this->getCapacitySnapshot($year, $week);
            }

            $searchDate->addWeek();
        }

        return null;
    }

    // =========================================================================
    // === MÉTODOS DE VALIDACIÓN ===
    // =========================================================================

    /**
     * Valida si una fecha puede asignarse como promised_date.
     *
     * REGLAS:
     * - La fecha debe ser futura (o hoy)
     * - La semana de la fecha debe tener capacidad disponible
     *
     * @param Carbon|string $date Fecha a validar
     * @return array {
     *     valid: bool,
     *     error: string|null,
     *     year: int,
     *     week: int,
     *     capacity: array (snapshot)
     * }
     */
    public function validatePromisedDate($date): array
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $year = (int) $carbon->isoWeekYear;
        $week = (int) $carbon->isoWeek;
        $capacity = $this->getCapacitySnapshot($year, $week);

        // Validar fecha no pasada
        if ($carbon->startOfDay()->lt(Carbon::today())) {
            return [
                'valid' => false,
                'error' => 'La fecha prometida no puede ser en el pasado.',
                'year' => $year,
                'week' => $week,
                'capacity' => $capacity,
            ];
        }

        // Validar capacidad disponible
        if ($capacity['is_full']) {
            return [
                'valid' => false,
                'error' => "La semana {$week} del {$year} está a capacidad máxima ({$capacity['max']} pedidos). " .
                           "Seleccione otra fecha.",
                'year' => $year,
                'week' => $week,
                'capacity' => $capacity,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'year' => $year,
            'week' => $week,
            'capacity' => $capacity,
        ];
    }

    /**
     * Valida si un pedido puede cambiar su promised_date a una nueva fecha.
     * Considera que el pedido ya podría estar ocupando un slot.
     *
     * @param Order $order Pedido a mover
     * @param Carbon|string $newDate Nueva fecha deseada
     * @return array {
     *     valid: bool,
     *     error: string|null,
     *     current_week: array|null,
     *     new_week: array
     * }
     */
    public function validateDateChange(Order $order, $newDate): array
    {
        $newCarbon = $newDate instanceof Carbon ? $newDate : Carbon::parse($newDate);
        $newYear = (int) $newCarbon->isoWeekYear;
        $newWeek = (int) $newCarbon->isoWeek;

        $currentWeek = null;

        // Si el pedido tiene promised_date y está en estado activo,
        // ver de qué semana viene
        if ($order->promised_date && in_array($order->status, self::ACTIVE_STATUSES)) {
            $currentCarbon = Carbon::parse($order->promised_date);
            $currentYear = (int) $currentCarbon->isoWeekYear;
            $currentWeekNum = (int) $currentCarbon->isoWeek;
            $currentWeek = $this->getCapacitySnapshot($currentYear, $currentWeekNum);

            // Si es la misma semana, no hay cambio de capacidad
            if ($currentYear === $newYear && $currentWeekNum === $newWeek) {
                return [
                    'valid' => true,
                    'error' => null,
                    'current_week' => $currentWeek,
                    'new_week' => $currentWeek,
                    'same_week' => true,
                ];
            }
        }

        // Validar la nueva semana
        $validation = $this->validatePromisedDate($newCarbon);

        return [
            'valid' => $validation['valid'],
            'error' => $validation['error'],
            'current_week' => $currentWeek,
            'new_week' => $validation['capacity'],
            'same_week' => false,
        ];
    }

    // =========================================================================
    // === MÉTODOS DE SUGERENCIA ===
    // =========================================================================

    /**
     * Sugiere una fecha óptima basada en lead time del pedido.
     *
     * LÓGICA:
     * 1. Calcula fecha mínima según lead time
     * 2. Busca la primera semana con capacidad desde esa fecha
     * 3. Retorna el primer día hábil de esa semana
     *
     * @param int $leadTimeDays Días de producción necesarios
     * @return array {
     *     suggested_date: string (Y-m-d),
     *     is_minimum: bool,
     *     week_snapshot: array
     * }
     */
    public function suggestPromisedDate(int $leadTimeDays): array
    {
        // Fecha mínima = hoy + lead time
        $minimumDate = Carbon::today()->addDays($leadTimeDays);
        $minimumYear = (int) $minimumDate->isoWeekYear;
        $minimumWeek = (int) $minimumDate->isoWeek;

        // Buscar semana con capacidad desde la fecha mínima
        $availableWeek = $this->findNextAvailableWeek(
            $minimumYear,
            $minimumWeek
        );

        if (!$availableWeek) {
            // Sin capacidad en 52 semanas (escenario extremo)
            return [
                'suggested_date' => null,
                'is_minimum' => false,
                'week_snapshot' => null,
                'error' => 'No hay capacidad disponible en las próximas 52 semanas.',
            ];
        }

        // La fecha sugerida es el inicio de la semana disponible
        // o la fecha mínima si cae en esa semana
        $weekStartDate = Carbon::parse($availableWeek['week_start']);

        if ($minimumDate->gte($weekStartDate) && $minimumDate->lte(Carbon::parse($availableWeek['week_end']))) {
            // La fecha mínima cae en la semana disponible
            $suggestedDate = $minimumDate;
            $isMinimum = true;
        } else {
            // La semana disponible es posterior a la mínima
            $suggestedDate = $weekStartDate;
            $isMinimum = false;
        }

        return [
            'suggested_date' => $suggestedDate->toDateString(),
            'is_minimum' => $isMinimum,
            'week_snapshot' => $availableWeek,
            'error' => null,
        ];
    }

    // =========================================================================
    // === MÉTODOS AUXILIARES ===
    // =========================================================================

    /**
     * Obtiene la fecha de inicio de una semana ISO (lunes).
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return Carbon Fecha del lunes de esa semana
     */
    public function getWeekStartDate(int $year, int $week): Carbon
    {
        return Carbon::now()
            ->setISODate($year, $week)
            ->startOfWeek();
    }

    /**
     * Obtiene la fecha de fin de una semana ISO (domingo).
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return Carbon Fecha del domingo de esa semana
     */
    public function getWeekEndDate(int $year, int $week): Carbon
    {
        return Carbon::now()
            ->setISODate($year, $week)
            ->endOfWeek();
    }

    /**
     * Obtiene año y semana ISO de una fecha.
     *
     * @param Carbon|string $date Fecha a convertir
     * @return array {year: int, week: int}
     */
    public function getIsoWeek($date): array
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return [
            'year' => (int) $carbon->isoWeekYear,
            'week' => (int) $carbon->isoWeek,
        ];
    }

    /**
     * Formatea el identificador de semana para display.
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return string Ej: "2026-W05" o "Semana 5, 2026"
     */
    public function formatWeekLabel(int $year, int $week, bool $verbose = false): string
    {
        if ($verbose) {
            return "Semana {$week}, {$year}";
        }

        return sprintf('%d-W%02d', $year, $week);
    }

    // =========================================================================
    // === MÉTODOS DE ANÁLISIS (REPORTES) ===
    // =========================================================================

    /**
     * Obtiene estadísticas de utilización histórica.
     * Útil para análisis de capacidad y reportes.
     *
     * @param int $weeksBack Semanas hacia atrás a analizar
     * @return array {
     *     avg_utilization: float,
     *     max_utilization: float,
     *     weeks_at_capacity: int,
     *     total_orders: int,
     *     data: array
     * }
     */
    public function getUtilizationStats(int $weeksBack = 12): array
    {
        $stats = [];
        $now = Carbon::now();
        $totalOrders = 0;
        $totalUtilization = 0;
        $maxUtilization = 0;
        $weeksAtCapacity = 0;

        for ($i = $weeksBack; $i >= 0; $i--) {
            $targetDate = $now->copy()->subWeeks($i);
            $year = (int) $targetDate->isoWeekYear;
            $week = (int) $targetDate->isoWeek;

            $snapshot = $this->getCapacitySnapshot($year, $week);
            $stats[] = $snapshot;

            $totalOrders += $snapshot['used'];
            $totalUtilization += $snapshot['utilization_percent'];
            $maxUtilization = max($maxUtilization, $snapshot['utilization_percent']);

            if ($snapshot['is_full']) {
                $weeksAtCapacity++;
            }
        }

        $weekCount = $weeksBack + 1;

        return [
            'avg_utilization' => round($totalUtilization / $weekCount, 1),
            'max_utilization' => $maxUtilization,
            'weeks_at_capacity' => $weeksAtCapacity,
            'total_orders' => $totalOrders,
            'weeks_analyzed' => $weekCount,
            'data' => $stats,
        ];
    }

    /**
     * Lista pedidos asignados a una semana específica.
     * Útil para drill-down en reportes.
     *
     * @param int $year Año ISO
     * @param int $week Semana ISO
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersForWeek(int $year, int $week)
    {
        $weekStart = $this->getWeekStartDate($year, $week);
        $weekEnd = $this->getWeekEndDate($year, $week);

        return Order::whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('promised_date')
            ->whereBetween('promised_date', [$weekStart, $weekEnd])
            ->with(['cliente', 'items'])
            ->orderBy('promised_date')
            ->orderBy('priority')
            ->get();
    }
}
