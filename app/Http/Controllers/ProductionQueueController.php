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
        $query = Order::with([
            'cliente',
            'items.product.materials.material.consumptionUnit',
            'items.product.materials.material.baseUnit',
        ])
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])
            ->orderBy('priority', 'asc')
            ->orderBy('promised_date', 'asc')
            ->orderBy('created_at', 'asc');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency_level', $request->urgency);
        }

        if ($request->filled('blocked')) {
            // Filtrar solo bloqueados
            $query->where(function ($q) {
                $q->whereHas('items', function ($qi) {
                    $qi->where('has_pending_adjustments', true)
                        ->orWhere(function ($q2) {
                            $q2->where('personalization_type', 'design')
                                ->where('design_approved', false);
                        });
                });
            });
        }

        $orders = $query->paginate(20);

        // Calcular materiales requeridos para cada pedido
        $ordersWithMaterials = $orders->through(function ($order) {
            $order->material_requirements = $this->getMaterialRequirements($order);
            $order->has_blockers = $this->checkBlockers($order);
            $order->blocker_reasons = $this->getBlockerReasons($order);
            return $order;
        });

        // Resumen de cola
        $summary = [
            'total_queue' => Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])->count(),
            'confirmed' => Order::where('status', Order::STATUS_CONFIRMED)->count(),
            'in_production' => Order::where('status', Order::STATUS_IN_PRODUCTION)->count(),
            'urgent_count' => Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])
                ->whereIn('urgency_level', [Order::URGENCY_URGENTE, Order::URGENCY_EXPRESS])
                ->count(),
            'overdue_count' => Order::whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION])
                ->whereNotNull('promised_date')
                ->where('promised_date', '<', Carbon::today())
                ->count(),
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
     */
    protected function getMaterialRequirements(Order $order): array
    {
        $requirements = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if (!$product || !$product->relationLoaded('materials')) {
                $product?->load('materials.material.consumptionUnit', 'materials.material.baseUnit');
            }

            if (!$product) continue;

            foreach ($product->materials as $materialVariant) {
                $requiredQty = $materialVariant->pivot->quantity * $item->quantity;
                $variantId = $materialVariant->id;

                if (!isset($requirements[$variantId])) {
                    // Obtener stock actual y reservas
                    $currentStock = $materialVariant->current_stock;
                    $totalReserved = InventoryReservation::where('material_variant_id', $variantId)
                        ->where('status', InventoryReservation::STATUS_RESERVED)
                        ->sum('quantity');

                    // Reservas de ESTE pedido especifico
                    $reservedForThis = InventoryReservation::where('order_id', $order->id)
                        ->where('material_variant_id', $variantId)
                        ->where('status', InventoryReservation::STATUS_RESERVED)
                        ->sum('quantity');

                    $available = $currentStock - $totalReserved;

                    // Datos para conversión de unidades en frontend
                    $material = $materialVariant->material;
                    $conversionFactor = $material->conversion_factor ?? 1;
                    $consumptionUnit = $material->consumptionUnit->symbol ?? $material->baseUnit->symbol ?? 'u';
                    $baseUnit = $material->baseUnit->symbol ?? $consumptionUnit;

                    $requirements[$variantId] = [
                        'material_variant_id' => $variantId,
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
                    ];
                }

                $requirements[$variantId]['required'] += $requiredQty;
            }
        }

        // Calcular si hay suficiente
        foreach ($requirements as $variantId => &$req) {
            // Si ya esta reservado para este pedido, no necesita mas
            $needed = $req['required'] - $req['reserved_for_this'];
            $req['needed'] = max(0, $needed);
            $req['sufficient'] = $req['needed'] <= $req['available'];
        }

        return array_values($requirements);
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
        // Bloqueados por reglas de negocio
        $blockedByRules = Order::where('status', Order::STATUS_CONFIRMED)
            ->whereHas('items', function ($q) {
                $q->where('has_pending_adjustments', true)
                    ->orWhere(function ($q2) {
                        $q2->where('personalization_type', 'design')
                            ->where('design_approved', false);
                    });
            })->count();

        return $blockedByRules;
    }
}
