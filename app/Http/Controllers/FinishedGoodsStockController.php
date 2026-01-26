<?php

namespace App\Http\Controllers;

use App\Models\FinishedGoodsMovement;
use App\Models\ProductVariant;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * FinishedGoodsStockController - Vista de Stock de Producto Terminado
 *
 * RESPONSABILIDAD ÚNICA:
 * Mostrar consulta READ-ONLY del stock de productos terminados.
 *
 * INVARIANTES (INVIOLABLES):
 * I1) Fuente única de stock = FinishedGoodsMovement (ledger)
 * I2) Cálculo = calculateRealStock() (misma fórmula que PosController)
 * I3) Nivel de agregación = ProductVariant
 * I4) CERO operaciones (solo lectura)
 * I5) CERO uso de ProductVariant.current_stock
 * I6) CERO inferencia desde orders
 * I7) CERO movimientos individuales visibles
 * I8) CERO datos financieros
 * I9) CERO botones de acción
 */
class FinishedGoodsStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /admin/finished-goods-stock
     *
     * Vista de consulta de stock de productos terminados.
     * SOLO LECTURA - SIN ACCIONES.
     */
    public function index(Request $request): View
    {
        // =====================================================================
        // QUERY: ProductVariant ACTIVAS con relaciones necesarias
        // =====================================================================
        $query = ProductVariant::with(['product.category'])
            ->where('activo', true)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            });

        // =====================================================================
        // FILTRO: Búsqueda por texto (producto / SKU)
        // =====================================================================
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('sku_variant', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        // =====================================================================
        // FILTRO: Por categoría de producto
        // =====================================================================
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_category_id', $request->input('category_id'));
            });
        }

        // =====================================================================
        // OBTENER VARIANTES Y CALCULAR STOCK DESDE LEDGER
        // =====================================================================
        $variantsRaw = $query->orderBy('sku_variant')->get();

        // Calcular stock real desde FinishedGoodsMovement para cada variante
        $variants = $variantsRaw->map(function ($variant) {
            // INVARIANTE I1 + I2: Stock calculado SOLO desde ledger
            $variant->calculated_stock = $this->calculateRealStock($variant->id);

            // Determinar estado de stock
            $stockAlert = $variant->stock_alert ?? 0;
            if ($variant->calculated_stock <= 0) {
                $variant->stock_status = 'agotado';
                $variant->stock_status_label = 'Agotado';
                $variant->stock_status_color = 'danger';
            } elseif ($variant->calculated_stock <= $stockAlert) {
                $variant->stock_status = 'bajo';
                $variant->stock_status_label = 'Bajo';
                $variant->stock_status_color = 'warning';
            } else {
                $variant->stock_status = 'ok';
                $variant->stock_status_label = 'OK';
                $variant->stock_status_color = 'success';
            }

            return $variant;
        });

        // =====================================================================
        // FILTRO: Por estado de stock (post-cálculo)
        // =====================================================================
        if ($request->filled('stock_status')) {
            $statusFilter = $request->input('stock_status');
            $variants = $variants->filter(function ($variant) use ($statusFilter) {
                return $variant->stock_status === $statusFilter;
            })->values();
        }

        // =====================================================================
        // TOTALES PARA RESUMEN (SIN DATOS FINANCIEROS - I8)
        // =====================================================================
        $totals = [
            'total_variants' => $variants->count(),
            'total_stock' => $variants->sum('calculated_stock'),
            'low_stock' => $variants->where('stock_status', 'bajo')->count(),
            'out_of_stock' => $variants->where('stock_status', 'agotado')->count(),
        ];

        // =====================================================================
        // CATEGORÍAS PARA FILTRO
        // =====================================================================
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.finished-goods-stock.index', compact('variants', 'totals', 'categories'));
    }

    /**
     * Calcula stock REAL desde ledger.
     *
     * FÓRMULA CANÓNICA (idéntica a PosController::calculateRealStock):
     * stock = Σ(production_entry + return + adjustment_positivo)
     *       - Σ(sale_exit + adjustment_negativo)
     *
     * @param int $productVariantId
     * @return float
     */
    private function calculateRealStock(int $productVariantId): float
    {
        // Entradas: producción + devoluciones
        $productionEntries = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_PRODUCTION_ENTRY)
            ->sum('quantity');

        $returns = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_RETURN)
            ->sum('quantity');

        // Salidas: ventas
        $saleExits = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_SALE_EXIT)
            ->sum('quantity');

        // Ajustes: pueden ser positivos o negativos
        $adjustments = (float) FinishedGoodsMovement::where('product_variant_id', $productVariantId)
            ->where('type', FinishedGoodsMovement::TYPE_ADJUSTMENT)
            ->sum('quantity');

        return $productionEntries + $returns - $saleExits + $adjustments;
    }

    /**
     * GET /admin/finished-goods-stock/{variant}/kardex
     *
     * Kardex / historial de movimientos de una variante PT.
     * SOLO LECTURA - SIN ACCIONES DE EDICIÓN.
     */
    public function kardex(Request $request, int $variant): View
    {
        $productVariant = ProductVariant::with(['product.category'])->findOrFail($variant);

        $query = FinishedGoodsMovement::byVariant($variant)
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        $movements = $query->paginate(50)->withQueryString();

        $currentStock = $this->calculateRealStock($variant);

        return view('admin.finished-goods-stock.kardex', compact('productVariant', 'movements', 'currentStock'));
    }

    /**
     * GET /admin/finished-goods-stock/{variant}/adjustment
     *
     * Formulario para ajuste manual de stock PT.
     */
    public function adjustmentForm(int $variant): View
    {
        $productVariant = ProductVariant::with(['product.category'])->findOrFail($variant);
        $currentStock = $this->calculateRealStock($variant);

        return view('admin.finished-goods-stock.adjustment', compact('productVariant', 'currentStock'));
    }

    /**
     * POST /admin/finished-goods-stock/{variant}/adjustment
     *
     * Registra ajuste de inventario PT.
     * REGLA: Crea FinishedGoodsMovement::TYPE_ADJUSTMENT
     * Motivo OBLIGATORIO, usuario desde sesión.
     */
    public function storeAdjustment(Request $request, int $variant): RedirectResponse
    {
        $request->validate([
            'physical_stock' => 'required|numeric|min:0',
            'adjustment_reason' => 'required|string|min:10|max:255',
        ], [
            'physical_stock.required' => 'Se requiere el stock físico contado.',
            'physical_stock.min' => 'El stock físico no puede ser negativo.',
            'adjustment_reason.required' => 'El motivo del ajuste es OBLIGATORIO.',
            'adjustment_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'adjustment_reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ]);

        $productVariant = ProductVariant::findOrFail($variant);
        $physicalStock = (float) $request->input('physical_stock');
        $adjustmentReason = trim($request->input('adjustment_reason'));

        try {
            DB::transaction(function () use ($variant, $physicalStock, $adjustmentReason) {
                $systemStock = $this->calculateRealStock($variant);
                $difference = $physicalStock - $systemStock;

                // Si no hay diferencia, no crear movimiento
                if (abs($difference) < 0.0001) {
                    return;
                }

                $movement = new FinishedGoodsMovement();
                $movement->product_variant_id = $variant;
                $movement->type = FinishedGoodsMovement::TYPE_ADJUSTMENT;
                $movement->reference_type = null;
                $movement->reference_id = null;
                $movement->quantity = $difference;
                $movement->stock_before = $systemStock;
                $movement->stock_after = $physicalStock;
                $movement->notes = "AJUSTE ADMIN | Motivo: {$adjustmentReason} | " .
                    "Sistema: {$systemStock} → Físico: {$physicalStock} | " .
                    "Usuario: " . Auth::user()->name;
                $movement->created_by = Auth::id();
                $movement->save();
            });

            return redirect()
                ->route('admin.finished-goods-stock.kardex', $variant)
                ->with('success', 'Ajuste de inventario registrado correctamente.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al registrar ajuste: ' . $e->getMessage());
        }
    }
}
