<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderMessage;
use App\Models\InventoryReservation;
use App\Models\MaterialVariant;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionQueueController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
        $this->middleware('auth');
    }

    /**
     * Vista de cola de produccion
     * Muestra pedidos confirmados y en produccion ordenados por prioridad
     */
    public function index(Request $request)
    {
        // Pedidos en cola: Confirmados (listos para iniciar) + En Produccion (en progreso)
        // Orden: Urgentes/Express primero, luego por fecha de creación descendente
        $query = Order::with([
            'cliente',
            'items.product.materials.material.consumptionUnit',
            'items.product.materials.material.baseUnit',
            'items.bomAdjustments', // Cargar ajustes de BOM guardados
            'items.extras.productExtra', // Cargar extras del item con su nombre
        ])
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])
            ->orderByRaw("CASE
                WHEN urgency_level = 'express' THEN 1
                WHEN urgency_level = 'urgente' THEN 2
                ELSE 3
            END")
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('urgency')) {
            if ($request->urgency === 'urgent_express') {
                $query->whereIn('urgency_level', [\App\Models\Order::URGENCY_URGENTE, \App\Models\Order::URGENCY_EXPRESS]);
            } else {
                $query->where('urgency_level', $request->urgency);
            }
        }

        if ($request->filled('blocked')) {
            // Filtrar todos y luego filtrar en colección para precisión total (considera inventario dinámico)
            $orders = $query->get();
            $filteredIds = $orders->filter(fn($o) => $this->checkBlockers($o))->pluck('id');
            $query = Order::whereIn('id', $filteredIds);
        }

        if ($request->filled('overdue')) {
            $query->whereNotNull('promised_date')
                ->where('promised_date', '<', now()->startOfDay());
        }

        $orders = $query->paginate(20);

        // Calcular materiales requeridos para cada pedido
        $ordersWithMaterials = $orders->through(function ($order) {
            $order->material_requirements = $this->getMaterialRequirements($order);
            $order->has_blockers = $this->checkBlockers($order);
            $order->blocker_reasons = $this->getBlockerReasons($order);
            return $order;
        });

        // Si es AJAX, retornar solo la tabla
        if ($request->ajax()) {
            return view('admin.production._queue-table', compact('orders'));
        }

        // Resumen de cola
        $summary = [
            'total_queue' => Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])->count(),
            'confirmed' => Order::where('status', Order::STATUS_CONFIRMED)->count(),
            'in_production' => Order::where('status', Order::STATUS_IN_PRODUCTION)->count(),
            'urgent_count' => Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])
                ->whereIn('urgency_level', [Order::URGENCY_URGENTE, Order::URGENCY_EXPRESS])
                ->count(),
            'overdue_count' => app(\App\Services\OrderCalendarReadService::class)
                ->countOverdueOrdersByStatuses([Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION]),
            'blocked_count' => $this->countBlockedOrders(),
        ];

        return view('admin.production.queue', compact('orders', 'summary'));
    }

    /**
     * API: Obtener materiales requeridos para un pedido
     */
    public function getMaterialsForOrder(Order $order)
    {
        $requirements = $this->getMaterialRequirements($order);

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'materials' => $requirements,
        ]);
    }

    /**
     * API: Cambiar prioridad de pedido
     */
    public function updatePriority(Request $request, Order $order)
    {
        $request->validate([
            'priority' => 'required|integer|min:1|max:100',
        ]);

        if (!in_array($order->status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede cambiar prioridad de pedidos en cola.',
            ], 422);
        }

        $oldPriority = $order->priority;
        $order->update(['priority' => $request->priority]);

        return response()->json([
            'success' => true,
            'message' => 'Prioridad actualizada.',
            'old_priority' => $oldPriority,
            'new_priority' => $order->priority,
        ]);
    }

    /**
     * Iniciar produccion de un pedido
     * Valida inventario, crea reservas y registra eventos
     */
    public function startProduction(Order $order)
    {
        if ($order->status !== Order::STATUS_CONFIRMED) {
            return redirect()->back()->with('error', 'Solo pedidos confirmados pueden iniciar produccion.');
        }

        // Verificar bloqueos
        if ($order->hasPendingAdjustments()) {
            return redirect()->back()->with('error', 'Pedido tiene ajustes de precio pendientes.');
        }

        if ($order->hasItemsPendingDesignApproval()) {
            return redirect()->back()->with('error', 'Pedido tiene disenos pendientes de aprobacion.');
        }

        // Validar inventario disponible
        $requirements = $this->getMaterialRequirements($order);
        $insufficientMaterials = collect($requirements)->filter(fn($r) => !$r['sufficient']);

        if ($insufficientMaterials->isNotEmpty()) {
            // Registrar bloqueo por inventario
            $missingMaterials = $insufficientMaterials->map(fn($m) => [
                'name' => $m['material_name'] . ' (' . $m['variant_color'] . ')',
                'quantity' => $m['needed'],
                'unit' => $m['unit'],
            ])->values()->toArray();

            OrderEvent::logProductionBlocked($order, $missingMaterials);

            // Crear mensaje operativo visible
            $materialsList = collect($missingMaterials)->map(fn($m) => "{$m['quantity']} {$m['unit']} de {$m['name']}")->implode(', ');
            OrderMessage::create([
                'order_id' => $order->id,
                'message' => "PRODUCCION DETENIDA: Faltan materiales.\n\nMateriales faltantes:\n- " . str_replace(', ', "\n- ", $materialsList),
                'visibility' => 'both',
                'created_by' => auth()->id(),
            ]);

            return redirect()->back()->with('error', "Produccion detenida: faltan {$materialsList}");
        }

        try {
            // Usar el servicio para la transicion atomica
            $this->orderService->triggerProduction($order);

            return redirect()->back()->with('success', 'Pedido enviado a produccion. Materiales reservados.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Calcular materiales requeridos para un pedido
     * Incluye materiales del BOM (producto base) y materiales de extras
     */
    protected function getMaterialRequirements(Order $order): array
    {
        $requirements = [];
        $extraRequirements = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if (!$product || !$product->relationLoaded('materials')) {
                $product?->load('materials.material.consumptionUnit', 'materials.material.baseUnit');
            }

            // Cargar ajustes de BOM si no están cargados
            if (!$item->relationLoaded('bomAdjustments')) {
                $item->load('bomAdjustments');
            }

            // Cargar extras del item si no están cargados
            if (!$item->relationLoaded('extras')) {
                $item->load('extras.productExtra.materials.material.consumptionUnit', 'extras.productExtra.materials.material.baseUnit');
            }

            // Indexar ajustes por material_variant_id para búsqueda rápida
            $bomAdjustments = $item->bomAdjustments->keyBy('material_variant_id');

            if (!$product) continue;

            // === MATERIALES DEL PRODUCTO BASE (BOM) ===
            foreach ($product->materials as $materialVariant) {
                $variantId = $materialVariant->id;

                // Usar cantidad ajustada si existe, sino usar BOM base
                $baseQty = (float) $materialVariant->pivot->quantity;
                $adjustment = $bomAdjustments->get($variantId);
                $adjustedQty = $adjustment ? (float) $adjustment->adjusted_quantity : $baseQty;

                $requiredQty = $adjustedQty * $item->quantity;

                if (!isset($requirements[$variantId])) {
                    $requirements[$variantId] = $this->buildMaterialRequirement($materialVariant, $order->id);
                }

                $requirements[$variantId]['required'] += $requiredQty;

                // Marcar si este material tiene ajuste de BOM
                if ($adjustment && $adjustment->hasChange()) {
                    $requirements[$variantId]['has_bom_adjustment'] = true;
                }
            }

            // === MATERIALES DE EXTRAS CON INVENTARIO ===
            foreach ($item->extras as $orderItemExtra) {
                $extra = $orderItemExtra->productExtra;

                if (!$extra || !$extra->consumesInventory()) {
                    continue;
                }

                // Cargar materiales del extra si no están cargados
                if (!$extra->relationLoaded('materials')) {
                    $extra->load('materials.material.consumptionUnit', 'materials.material.baseUnit');
                }

                $totalExtraQuantity = $orderItemExtra->quantity * $item->quantity;

                foreach ($extra->materials as $materialVariant) {
                    $variantId = $materialVariant->id;
                    $requiredQty = (float) $materialVariant->pivot->quantity * $totalExtraQuantity;

                    if (!isset($extraRequirements[$variantId])) {
                        $extraRequirements[$variantId] = $this->buildMaterialRequirement($materialVariant, $order->id);
                        $extraRequirements[$variantId]['extra_name'] = $extra->name;
                        $extraRequirements[$variantId]['is_extra'] = true;
                    }

                    $extraRequirements[$variantId]['required'] += $requiredQty;
                }
            }
        }

        // Calcular si hay suficiente para materiales del BOM
        foreach ($requirements as $variantId => &$req) {
            $needed = $req['required'] - $req['reserved_for_this'];
            $req['needed'] = max(0, $needed);
            $req['sufficient'] = $req['needed'] <= $req['available'];
        }

        // Calcular si hay suficiente para materiales de extras
        foreach ($extraRequirements as $variantId => &$req) {
            $needed = $req['required'] - $req['reserved_for_this'];
            $req['needed'] = max(0, $needed);
            $req['sufficient'] = $req['needed'] <= $req['available'];
        }

        // Combinar ambos arrays con marcador de tipo
        $bomMaterials = array_values($requirements);
        $extraMaterials = array_values($extraRequirements);

        // Agregar marcador para diferenciar en la vista
        foreach ($bomMaterials as &$mat) {
            $mat['source_type'] = 'bom';
        }
        foreach ($extraMaterials as &$mat) {
            $mat['source_type'] = 'extra';
        }

        return array_merge($bomMaterials, $extraMaterials);
    }

    /**
     * Construir estructura de requerimiento de material
     */
    protected function buildMaterialRequirement($materialVariant, int $orderId): array
    {
        $currentStock = $materialVariant->current_stock;
        $totalReserved = InventoryReservation::where('material_variant_id', $materialVariant->id)
            ->where('status', InventoryReservation::STATUS_RESERVED)
            ->sum('quantity');

        $reservedForThis = InventoryReservation::where('order_id', $orderId)
            ->where('material_variant_id', $materialVariant->id)
            ->where('status', InventoryReservation::STATUS_RESERVED)
            ->sum('quantity');

        $available = $currentStock - $totalReserved;

        $material = $materialVariant->material;
        $conversionFactor = $material->conversion_factor ?? 1;
        $consumptionUnit = $material->consumptionUnit->symbol ?? $material->baseUnit->symbol ?? 'u';
        $baseUnit = $material->baseUnit->symbol ?? $consumptionUnit;

        return [
            'material_variant_id' => $materialVariant->id,
            'material_id' => $material->id ?? 0,
            'material_name' => $material->name ?? 'N/A',
            'variant_color' => $materialVariant->color,
            'variant_sku' => $materialVariant->sku,
            'unit' => $consumptionUnit,
            'unit_base' => $baseUnit,
            'conversion_factor' => $conversionFactor,
            'required' => 0,
            'current_stock' => $currentStock,
            'total_reserved' => $totalReserved,
            'reserved_for_this' => $reservedForThis,
            'available' => $available,
            'sufficient' => true,
            'has_bom_adjustment' => false,
            'is_extra' => false,
        ];
    }

    /**
     * Verificar si pedido tiene bloqueos
     */
    protected function checkBlockers(Order $order): bool
    {
        if ($order->status !== Order::STATUS_CONFIRMED) {
            return false;
        }

        // Verificar bloqueos de reglas de negocio
        if ($order->hasPendingAdjustments() || $order->hasItemsPendingDesignApproval()) {
            return true;
        }

        // Verificar medidas cambiadas post-aprobacion
        if ($order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval())) {
            return true;
        }

        // Verificar inventario insuficiente
        $requirements = $this->getMaterialRequirements($order);
        if (collect($requirements)->contains(fn($r) => !$r['sufficient'])) {
            return true;
        }

        return false;
    }

    /**
     * Obtener razones de bloqueo
     */
    protected function getBlockerReasons(Order $order): array
    {
        $reasons = [];

        if ($order->hasPendingAdjustments()) {
            $reasons[] = 'Ajustes de precio pendientes de aprobar';
        }

        if ($order->hasItemsPendingDesignApproval()) {
            $reasons[] = 'Diseno pendiente de aprobacion del cliente';
        }

        $itemsWithChangedMeasures = $order->items->filter(fn($i) => $i->hasMeasurementsChangedAfterApproval());
        if ($itemsWithChangedMeasures->isNotEmpty()) {
            $reasons[] = 'Medidas modificadas despues de aprobar diseno';
        }

        // Verificar inventario
        $requirements = $this->getMaterialRequirements($order);
        $insufficientMaterials = collect($requirements)->filter(fn($r) => !$r['sufficient']);
        if ($insufficientMaterials->isNotEmpty()) {
            foreach ($insufficientMaterials as $m) {
                $reasons[] = "Falta material: {$m['needed']} {$m['unit']} de {$m['material_name']} ({$m['variant_color']})";
            }
        }

        return $reasons;
    }

    /**
     * Contar pedidos bloqueados en cola
     */
    protected function countBlockedOrders(): int
    {
        $orders = Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])->get();
        $count = 0;
        foreach ($orders as $order) {
            if ($this->checkBlockers($order)) {
                $count++;
            }
        }
        return $count;
    }
}
