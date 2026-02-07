<?php

namespace App\Http\Controllers;

use App\Models\MaterialVariant;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
        $this->middleware('auth');
    }

    // === VISTA 1: INVENTARIO GENERAL ===
    public function index(Request $request)
    {
        $query = MaterialVariant::with(['material.category', 'material.baseUnit'])
            ->where('activo', true);

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->whereHas('material', fn($q) => $q->where('material_category_id', $request->category_id));
        }

        // Filtro por estado de stock
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereColumn('current_stock', '<=', 'min_stock_alert');
            } elseif ($request->stock_status === 'ok') {
                $query->whereColumn('current_stock', '>', 'min_stock_alert');
            } elseif ($request->stock_status === 'zero') {
                $query->where('current_stock', '<=', 0);
            }
        }

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$search}%"));
            });
        }

        $variants = $query->orderBy('material_id')->paginate(25)->withQueryString();

        // Calcular totales de entradas y salidas para cada variante (para tooltip)
        // Tipos que suman stock: entrada, ajuste_positivo, devolucion_produccion
        // Tipos que restan stock: salida, ajuste_negativo, devolucion_proveedor
        $variantIds = $variants->pluck('id')->toArray();

        $entryTypes = ['entrada', 'ajuste_positivo', 'devolucion_produccion'];
        $exitTypes = ['salida', 'ajuste_negativo', 'devolucion_proveedor'];

        $entriesByVariant = InventoryMovement::selectRaw('material_variant_id, SUM(ABS(quantity)) as total')
            ->whereIn('material_variant_id', $variantIds)
            ->whereIn('type', $entryTypes)
            ->groupBy('material_variant_id')
            ->pluck('total', 'material_variant_id')
            ->toArray();

        $exitsByVariant = InventoryMovement::selectRaw('material_variant_id, SUM(ABS(quantity)) as total')
            ->whereIn('material_variant_id', $variantIds)
            ->whereIn('type', $exitTypes)
            ->groupBy('material_variant_id')
            ->pluck('total', 'material_variant_id')
            ->toArray();

        // Combinar en un solo array para pasar a la vista
        $inventoryTotals = [];
        foreach ($variantIds as $id) {
            $inventoryTotals[$id] = [
                'entries' => $entriesByVariant[$id] ?? 0,
                'exits' => $exitsByVariant[$id] ?? 0,
            ];
        }

        // Totales para resumen
        $totals = [
            'total_value' => MaterialVariant::where('activo', true)->sum('current_value'),
            'total_items' => MaterialVariant::where('activo', true)->count(),
            'low_stock' => MaterialVariant::where('activo', true)->whereColumn('current_stock', '<=', 'min_stock_alert')->count(),
            'total_reserved' => InventoryReservation::where('status', 'reserved')->sum('quantity'),
        ];

        $categories = \App\Models\MaterialCategory::orderBy('name')->get();

        return view('admin.inventory.index', compact('variants', 'totals', 'categories', 'inventoryTotals'));
    }

    // === VISTA 2: KARDEX POR MATERIAL ===
    public function kardex(Request $request, MaterialVariant $variant)
    {
        $query = InventoryMovement::where('material_variant_id', $variant->id)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtro por fecha
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $movements = $query->paginate(50)->withQueryString();

        // Resumen del material
        $summary = [
            'total_entries' => InventoryMovement::where('material_variant_id', $variant->id)->entries()->sum('quantity'),
            'total_exits' => InventoryMovement::where('material_variant_id', $variant->id)->exits()->sum('quantity'),
            'reserved' => $variant->reserved_stock,
            'available' => $variant->available_stock,
        ];

        return view('admin.inventory.kardex', compact('variant', 'movements', 'summary'));
    }

    // === VISTA 3: RESERVAS ACTIVAS ===
    public function reservations(Request $request)
    {
        $query = InventoryReservation::with(['order', 'orderItem', 'materialVariant.material'])
            ->where('status', 'reserved')
            ->orderBy('created_at', 'desc');

        // Filtro por pedido
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        // Filtro por material
        if ($request->filled('material_variant_id')) {
            $query->where('material_variant_id', $request->material_variant_id);
        }

        $reservations = $query->paginate(50)->withQueryString();

        // Totales
        $totals = [
            'total_reservations' => InventoryReservation::where('status', 'reserved')->count(),
            'total_quantity' => InventoryReservation::where('status', 'reserved')->sum('quantity'),
            'orders_with_reservations' => InventoryReservation::where('status', 'reserved')->distinct('order_id')->count('order_id'),
        ];

        return view('admin.inventory.reservations', compact('reservations', 'totals'));
    }

    // === AJUSTE MANUAL DE INVENTARIO ===
    public function adjustmentForm(MaterialVariant $variant)
    {
        return view('admin.inventory.adjustment', compact('variant'));
    }

    public function storeAdjustment(Request $request, MaterialVariant $variant)
    {
        $request->validate([
            'type' => 'required|in:positive,negative',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required_if:type,positive|numeric|min:0',
            'notes' => 'required|string|min:10|max:500',
        ], [
            'notes.required' => 'El motivo del ajuste es obligatorio (mínimo 10 caracteres).',
            'notes.min' => 'El motivo debe tener al menos 10 caracteres para auditoría.',
        ]);

        $isPositive = $request->type === 'positive';
        $quantity = (float) $request->quantity;
        $unitCost = $isPositive ? (float) $request->unit_cost : $variant->average_cost;

        // Validar stock suficiente para ajuste negativo
        if (!$isPositive && $variant->available_stock < $quantity) {
            return back()->with('error', "Stock disponible insuficiente. Disponible: {$variant->available_stock}");
        }

        try {
            $this->inventoryService->registerAdjustment(
                variantId: $variant->id,
                quantity: $quantity,
                unitCost: $unitCost,
                isPositive: $isPositive,
                notes: "[AJUSTE MANUAL] {$request->notes}",
                userId: Auth::id()
            );

            return redirect()->route('admin.inventory.kardex', $variant)
                ->with('success', 'Ajuste registrado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar ajuste: ' . $e->getMessage());
        }
    }

    // === HISTORIAL DE RESERVAS (TODAS) ===
    public function reservationsHistory(Request $request)
    {
        $query = InventoryReservation::with(['order', 'orderItem', 'materialVariant.material', 'creator', 'consumer'])
            ->orderBy('created_at', 'desc');

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por fecha
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $reservations = $query->paginate(50)->withQueryString();

        return view('admin.inventory.reservations-history', compact('reservations'));
    }

    // === DETALLES DE COMPRA PARA MODAL EN KARDEX ===
    public function getPurchaseDetails(Request $request): JsonResponse
    {
        $referenceType = $request->input('reference_type');
        $referenceId = $request->input('reference_id');

        $purchase = null;
        $purchaseItem = null;

        if ($referenceType === 'App\Models\PurchaseItem') {
            $purchaseItem = PurchaseItem::with(['purchase.proveedor', 'purchase.creator', 'purchase.receiver', 'materialVariant.material', 'unit'])
                ->find($referenceId);
            $purchase = $purchaseItem?->purchase;
        } elseif ($referenceType === 'App\Models\Purchase') {
            $purchase = Purchase::with(['proveedor', 'creator', 'receiver', 'items.materialVariant.material', 'items.unit'])
                ->find($referenceId);
        }

        if (!$purchase) {
            return response()->json(['error' => 'Compra no encontrada'], 404);
        }

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'reference' => $purchase->reference,
                'status' => $purchase->status_label,
                'status_color' => $purchase->status_color,
                'ordered_at' => $purchase->ordered_at?->format('d/m/Y'),
                'received_at' => $purchase->received_at?->format('d/m/Y H:i'),
                'subtotal' => number_format($purchase->subtotal, 2),
                'tax_amount' => number_format($purchase->tax_amount, 2),
                'discount_amount' => number_format($purchase->discount_amount, 2),
                'total' => number_format($purchase->total, 2),
                'notes' => $purchase->notes,
                'proveedor' => $purchase->proveedor ? [
                    'name' => $purchase->proveedor->name,
                    'contact' => $purchase->proveedor->contact_name,
                    'phone' => $purchase->proveedor->phone,
                    'email' => $purchase->proveedor->email,
                ] : null,
                'creator' => $purchase->creator?->name,
                'receiver' => $purchase->receiver?->name,
            ],
            'highlighted_item_id' => $purchaseItem?->id,
            'items' => $purchase->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'material' => $item->materialVariant?->material?->name ?? 'N/A',
                    'variant' => $item->materialVariant?->color ?? '-',
                    'sku' => $item->materialVariant?->sku ?? '-',
                    'quantity' => number_format($item->quantity, 2),
                    'unit' => $item->unit?->symbol ?? '-',
                    'unit_price' => number_format($item->unit_price, 2),
                    'subtotal' => number_format($item->subtotal, 2),
                    'quantity_received' => number_format($item->quantity_received, 2),
                ];
            }),
        ]);
    }
}
