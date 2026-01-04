<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Exceptions\PurchaseException;
use App\Http\Requests\ReceivePurchaseRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialVariant;
use App\Models\Proveedor;
use App\Models\Purchase;
use App\Models\Unit;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    protected PurchaseService $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'status' => ['nullable', 'string', 'in:' . implode(',', PurchaseStatus::values())],
                'proveedor_id' => ['nullable', 'integer', 'min:1', 'max:999999999'],
                'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
                'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
                'search' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\#]+$/u'],
            ]);

            $query = Purchase::with(['proveedor', 'creator'])
                ->where('activo', true)
                ->orderBy('created_at', 'desc');

            // Filtro por estado
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Filtro por proveedor
            if (!empty($validated['proveedor_id'])) {
                $query->where('proveedor_id', (int) $validated['proveedor_id']);
            }

            // Filtro por rango de fechas
            if (!empty($validated['date_from'])) {
                $query->whereDate('ordered_at', '>=', $validated['date_from']);
            }
            if (!empty($validated['date_to'])) {
                $query->whereDate('ordered_at', '<=', $validated['date_to']);
            }

            // Búsqueda
            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('proveedor', function ($pq) use ($search) {
                            $pq->where('nombre_proveedor', 'like', "%{$search}%");
                        });
                });
            }

            $purchases = $query->paginate(15)->withQueryString();

            $proveedores = Proveedor::where('activo', true)
                ->orderBy('nombre_proveedor')
                ->get(['id', 'nombre_proveedor']);

            $statuses = PurchaseStatus::options();

            return view('admin.purchases.index', compact('purchases', 'proveedores', 'statuses'));
        } catch (ValidationException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Parámetros de filtro no válidos');
        } catch (\Exception $e) {
            Log::error('Error al listar compras: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('admin.purchases.index', [
                'purchases' => collect(),
                'proveedores' => collect(),
                'statuses' => [],
            ])->with('error', 'Error al cargar las compras');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        try {
            $proveedores = Proveedor::where('activo', true)
                ->orderBy('nombre_proveedor')
                ->get(['id', 'nombre_proveedor']);

            if ($proveedores->isEmpty()) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Debe registrar al menos un proveedor antes de crear compras');
            }

            $categories = MaterialCategory::where('activo', true)
                ->with('baseUnit')
                ->ordered()
                ->get();

            return view('admin.purchases.create', compact('proveedores', 'categories'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de compra: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(StorePurchaseRequest $request)
    {
        try {
            $validated = $request->validated();

            $purchase = $this->purchaseService->create(
                data: [
                    'proveedor_id' => $validated['proveedor_id'],
                    'tax_rate' => $validated['tax_rate'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'reference' => $validated['reference'] ?? null,
                    'ordered_at' => $validated['ordered_at'] ?? null,
                    'expected_at' => $validated['expected_at'] ?? null,
                ],
                items: $validated['items']
            );

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} creada exitosamente");
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al crear compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.create')
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error al crear compra: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('purchases.create')
                ->withInput()
                ->with('error', 'Error al crear la compra. Intente nuevamente.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with([
                'proveedor',
                'items.materialVariant.material.category.baseUnit',
                'items.unit',
                'creator',
                'receiver',
            ])->where('activo', true)->findOrFail((int) $id);

            return view('admin.purchases.show', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al mostrar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with([
                'proveedor',
                'items.materialVariant.material.category.baseUnit',
                'items.unit',
            ])->where('activo', true)->findOrFail((int) $id);

            if (!$purchase->can_edit) {
                return redirect()->route('purchases.show', $purchase->id)
                    ->with('error', "No se puede editar la compra en estado: {$purchase->status->label()}");
            }

            $proveedores = Proveedor::where('activo', true)
                ->orderBy('nombre_proveedor')
                ->get(['id', 'nombre_proveedor']);

            $categories = MaterialCategory::where('activo', true)
                ->with('baseUnit')
                ->ordered()
                ->get();

            return view('admin.purchases.edit', compact('purchase', 'proveedores', 'categories'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar compra para editar: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(UpdatePurchaseRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $validated = $request->validated();

            $purchase = $this->purchaseService->update(
                purchase: $purchase,
                data: [
                    'proveedor_id' => $validated['proveedor_id'],
                    'tax_rate' => $validated['tax_rate'] ?? $purchase->tax_rate,
                    'discount_amount' => $validated['discount_amount'] ?? $purchase->discount_amount,
                    'notes' => $validated['notes'] ?? null,
                    'reference' => $validated['reference'] ?? null,
                    'ordered_at' => $validated['ordered_at'] ?? null,
                    'expected_at' => $validated['expected_at'] ?? null,
                ],
                items: $validated['items']
            );

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} actualizada exitosamente");
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al actualizar compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.edit', $id)
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al actualizar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('purchases.edit', $id)
                ->withInput()
                ->with('error', 'Error al actualizar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM (Borrador → Pendiente)
    |--------------------------------------------------------------------------
    */

    public function confirm($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);

            $purchase = $this->purchaseService->confirm($purchase);

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} confirmada exitosamente");
        } catch (PurchaseException $e) {
            return redirect()->route('purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al confirmar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.show', $id)
                ->with('error', 'Error al confirmar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RECEIVE
    |--------------------------------------------------------------------------
    */

    public function showReceive($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with([
                'proveedor',
                'items.materialVariant.material.category.baseUnit',
                'items.unit',
            ])->where('activo', true)->findOrFail((int) $id);

            if (!$purchase->can_receive) {
                return redirect()->route('purchases.show', $purchase->id)
                    ->with('error', "No se puede recibir la compra en estado: {$purchase->status->label()}");
            }

            return view('admin.purchases.receive', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar recepción de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar la recepción');
        }
    }

    public function receive(ReceivePurchaseRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $validated = $request->validated();

            if ($validated['receive_type'] === 'complete') {
                $purchase = $this->purchaseService->receiveComplete($purchase);
                $message = "Compra {$purchase->purchase_number} recibida completamente";
            } else {
                foreach ($validated['items'] as $itemData) {
                    if ($itemData['quantity'] > 0) {
                        $purchase = $this->purchaseService->receivePartial(
                            purchase: $purchase,
                            itemId: $itemData['item_id'],
                            quantity: $itemData['quantity']
                        );
                    }
                }
                $message = "Recepción parcial registrada para {$purchase->purchase_number}";
            }

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', $message);
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al recibir compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.receive', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al recibir compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('purchases.receive', $id)
                ->with('error', 'Error al procesar la recepción');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function showCancel($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with(['proveedor'])
                ->where('activo', true)
                ->findOrFail((int) $id);

            if (!$purchase->can_cancel) {
                return redirect()->route('purchases.show', $purchase->id)
                    ->with('error', "No se puede cancelar la compra en estado: {$purchase->status->label()}");
            }

            return view('admin.purchases.cancel', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar cancelación de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar la cancelación');
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $validated = $request->validate([
                'cancellation_reason' => [
                    'required',
                    'string',
                    'min:10',
                    'max:500',
                    'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\_]+$/u',
                ],
            ], [
                'cancellation_reason.required' => 'El motivo de cancelación es obligatorio.',
                'cancellation_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
                'cancellation_reason.max' => 'El motivo no puede exceder 500 caracteres.',
                'cancellation_reason.regex' => 'El motivo contiene caracteres no permitidos.',
            ]);

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);

            $purchase = $this->purchaseService->cancel(
                purchase: $purchase,
                reason: strip_tags(trim($validated['cancellation_reason']))
            );

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} cancelada");
        } catch (PurchaseException $e) {
            return redirect()->route('purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            return redirect()->route('purchases.cancel', $id)
                ->withInput()
                ->withErrors($e->errors());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cancelar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.show', $id)
                ->with('error', 'Error al cancelar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE (Solo borradores)
    |--------------------------------------------------------------------------
    */

    public function confirmDelete($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with(['proveedor', 'items'])
                ->where('activo', true)
                ->findOrFail((int) $id);

            if ($purchase->status !== PurchaseStatus::DRAFT) {
                return redirect()->route('purchases.show', $purchase->id)
                    ->with('error', 'Solo se pueden eliminar compras en borrador');
            }

            return view('admin.purchases.delete', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar eliminación de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al cargar la eliminación');
        }
    }

    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $purchaseNumber = $purchase->purchase_number;

            $this->purchaseService->delete($purchase);

            return redirect()->route('purchases.index')
                ->with('success', "Compra {$purchaseNumber} eliminada exitosamente");
        } catch (PurchaseException $e) {
            return redirect()->route('purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al eliminar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                ->with('error', 'Error al eliminar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX ENDPOINTS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener materiales por categoría
     */
    public function getMaterialsByCategory(Request $request, $categoryId)
    {
        try {
            if (!is_numeric($categoryId) || $categoryId < 1) {
                return response()->json(['error' => 'Categoría no válida'], 400);
            }

            $materials = Material::where('activo', true)
                ->where('material_category_id', (int) $categoryId)
                ->with(['activeVariants:id,material_id,sku,color,current_stock'])
                ->ordered()
                ->get(['id', 'name', 'composition']);

            return response()->json($materials);
        } catch (\Exception $e) {
            Log::error('Error AJAX getMaterialsByCategory: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener materiales'], 500);
        }
    }

    /**
     * Obtener variantes por material
     */
    public function getVariantsByMaterial(Request $request, $materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1) {
                return response()->json(['error' => 'Material no válido'], 400);
            }

            $variants = MaterialVariant::where('activo', true)
                ->where('material_id', (int) $materialId)
                ->ordered()
                ->get(['id', 'sku', 'color', 'current_stock', 'average_cost']);

            return response()->json($variants);
        } catch (\Exception $e) {
            Log::error('Error AJAX getVariantsByMaterial: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener variantes'], 500);
        }
    }

    /**
     * Obtener unidades de compra para un material
     */
    public function getUnitsForMaterial(Request $request, $materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1) {
                return response()->json(['error' => 'Material no válido'], 400);
            }

            $material = Material::with(['category.baseUnit'])->findOrFail((int) $materialId);
            $baseUnitId = $material->category->base_unit_id;

            // Unidad base siempre disponible
            $units = collect();
            if ($material->category->baseUnit) {
                $units->push([
                    'id' => $material->category->baseUnit->id,
                    'name' => $material->category->baseUnit->name,
                    'symbol' => $material->category->baseUnit->symbol,
                    'conversion_factor' => 1,
                    'is_base' => true,
                ]);
            }

            // Unidades de compra con conversión configurada
            $conversions = \App\Models\MaterialUnitConversion::where('material_id', $material->id)
                ->with('fromUnit')
                ->get();

            foreach ($conversions as $conversion) {
                $units->push([
                    'id' => $conversion->from_unit_id,
                    'name' => $conversion->fromUnit->name,
                    'symbol' => $conversion->fromUnit->symbol,
                    'conversion_factor' => $conversion->conversion_factor,
                    'is_base' => false,
                ]);
            }

            return response()->json([
                'material_id' => $material->id,
                'material_name' => $material->name,
                'base_unit_id' => $baseUnitId,
                'base_unit_symbol' => $material->category->baseUnit->symbol ?? '',
                'units' => $units,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error AJAX getUnitsForMaterial: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener unidades'], 500);
        }
    }
}
