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
use App\Models\MaterialUnitConversion;
use App\Services\ReceptionService;
use App\Exceptions\InventoryException;
use App\Models\PurchaseReception;

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

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('admin.purchases.partials.table_rows', compact('purchases'))->render(),
                    'pagination' => (string) $purchases->links()
                ]);
            }

            return view('admin.purchases.index', compact('purchases', 'proveedores', 'statuses'));
        } catch (ValidationException $e) {
            return redirect()->route('admin.purchases.index')
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Debe registrar al menos un proveedor antes de crear compras');
            }

            $categories = MaterialCategory::where('activo', true)
                ->ordered()
                ->get();

            // Preparar items desde old() para recuperar después de error de validación
            $oldItemsForJs = [];
            if (old('items')) {
                foreach (old('items') as $idx => $item) {
                    $variant = MaterialVariant::with(['material.baseUnit', 'material.category'])
                        ->find($item['material_variant_id']);

                    if ($variant) {
                        $unit = Unit::find($item['unit_id']);
                        $conversion = MaterialUnitConversion::where('material_id', $variant->material_id)
                            ->where('from_unit_id', $item['unit_id'])
                            ->first();

                        $conversionFactor = $conversion ? $conversion->conversion_factor : 1;
                        $quantity = (float) $item['quantity'];
                        $unitPrice = (float) $item['unit_price'];
                        $subtotal = $quantity * $unitPrice;
                        $convertedQuantity = $quantity * $conversionFactor;

                        $oldItemsForJs[] = [
                            'variant_id' => $variant->id,
                            'variant_sku' => $variant->sku,
                            'variant_color' => $variant->color ?? '',
                            'material_name' => $variant->material->name ?? '',
                            'category_name' => $variant->material->category->name ?? '',
                            'unit_id' => $item['unit_id'],
                            'unit_symbol' => $unit->symbol ?? '',
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'conversion_factor' => $conversionFactor,
                            'converted_quantity' => $convertedQuantity,
                            'base_unit_symbol' => $variant->material->baseUnit->symbol ?? '',
                            'subtotal' => $subtotal,
                        ];
                    }
                }
            }

            return view('admin.purchases.create', compact('proveedores', 'categories', 'oldItemsForJs'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de compra: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
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

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} creada exitosamente");
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al crear compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.create')
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error al crear compra: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.purchases.create')
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
                'items.materialVariant.material.baseUnit',
                'items.materialVariant.material.category',
                'items.unit',
                'creator',
                'receiver',
            ])->where('activo', true)->findOrFail((int) $id);

            // Cargar historial de recepciones
            $receptionService = app(ReceptionService::class);
            $receptions = $receptionService->getReceptionHistory($purchase);

            return view('admin.purchases.show', compact('purchase', 'receptions'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al mostrar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with([
                'proveedor',
                'items.materialVariant.material.baseUnit',
                'items.materialVariant.material.category',
                'items.unit',
            ])->where('activo', true)->findOrFail((int) $id);

            if (!$purchase->can_edit) {
                return redirect()->route('admin.purchases.show', $purchase->id)
                    ->with('error', "No se puede editar la compra en estado: {$purchase->status->label()}");
            }

            // --- NUEVA LÓGICA PARA EVITAR ParseError EN BLADE ---
            $itemsForJs = $purchase->items->map(function ($item) {
                return [
                    'variant_id'         => $item->material_variant_id,
                    'variant_sku'        => $item->materialVariant->sku ?? '',
                    'variant_color'      => $item->materialVariant->color ?? '',
                    'material_name'      => $item->materialVariant->material->name ?? '',
                    'category_name'      => $item->materialVariant->material->category->name ?? '',
                    'unit_id'            => $item->unit_id,
                    'unit_symbol'        => $item->unit->symbol ?? '',
                    'quantity'           => (float) $item->quantity,
                    'unit_price'         => (float) $item->unit_price,
                    'conversion_factor'  => (float) $item->conversion_factor,
                    'converted_quantity' => (float) $item->converted_quantity,
                    'converted_unit_cost' => $item->converted_quantity > 0 ? ((float) $item->subtotal / (float) $item->converted_quantity) : 0,
                    'base_unit_symbol'   => $item->materialVariant->material->baseUnit->symbol ?? '',
                    'subtotal'           => (float) $item->subtotal,
                ];
            })->values();
            // ----------------------------------------------------

            $proveedores = Proveedor::where('activo', true)
                ->orderBy('nombre_proveedor')
                ->get(['id', 'nombre_proveedor']);

            $categories = MaterialCategory::where('activo', true)
                ->ordered()
                ->get();

            // Agregamos 'itemsForJs' al compact
            return view('admin.purchases.edit', compact('purchase', 'proveedores', 'categories', 'itemsForJs'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar compra para editar: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id'     => Auth::id(),
                'trace'       => $e->getTraceAsString(), // Agregué el trace para mejor depuración
            ]);

            return redirect()->route('admin.purchases.index')
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
                return redirect()->route('admin.purchases.index')
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

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} actualizada exitosamente");
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al actualizar compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.edit', $id)
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al actualizar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.purchases.edit', $id)
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);

            $purchase = $this->purchaseService->confirm($purchase);

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} confirmada exitosamente");
        } catch (PurchaseException $e) {
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al confirmar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.show', $id)
                ->with('error', 'Error al confirmar la compra');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM AND RECEIVE (Borrador → Recibido en un solo paso)
    |--------------------------------------------------------------------------
    */

    public function confirmAndReceive($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with('items')->where('activo', true)->findOrFail((int) $id);

            // Verificar que está en borrador
            if ($purchase->status !== PurchaseStatus::DRAFT) {
                return redirect()->route('admin.purchases.show', $id)
                    ->with('error', 'Solo se pueden confirmar órdenes en estado borrador');
            }

            // Verificar que tiene items
            if ($purchase->items->isEmpty()) {
                return redirect()->route('admin.purchases.show', $id)
                    ->with('error', 'La orden debe tener al menos un item');
            }

            DB::beginTransaction();

            // 1. Confirmar la orden (borrador → pendiente)
            $purchase = $this->purchaseService->confirm($purchase);

            // 2. Crear recepción completa usando ReceptionService
            $receptionService = app(ReceptionService::class);

            $itemsData = [];
            foreach ($purchase->items as $item) {
                if ($item->pending_quantity > 0) {
                    $itemsData[] = [
                        'item_id' => $item->id,
                        'quantity' => $item->pending_quantity,
                    ];
                }
            }

            if (!empty($itemsData)) {
                $reception = $receptionService->createReception(
                    purchase: $purchase,
                    itemsData: $itemsData,
                    deliveryNote: null,
                    notes: 'Recepción automática - Orden confirmada y recibida en un paso'
                );
            }

            DB::commit();

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Compra {$purchase->purchase_number} confirmada y recibida completamente");
        } catch (PurchaseException $e) {
            DB::rollBack();
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (InventoryException $e) {
            DB::rollBack();
            Log::error('Error de inventario al confirmar y recibir compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', 'Error de inventario: ' . $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar y recibir compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.purchases.show', $id)
                ->with('error', 'Error al confirmar y recibir la compra');
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with([
                'proveedor',
                'items.materialVariant.material.baseUnit',  // baseUnit está en material, no en category
                'items.unit',
            ])->where('activo', true)->findOrFail((int) $id);

            if (!$purchase->can_receive) {
                return redirect()->route('admin.purchases.show', $purchase->id)
                    ->with('error', "No se puede recibir la compra en estado: {$purchase->status->label()}");
            }

            return view('admin.purchases.receive', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar recepción de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
                ->with('error', 'Error al cargar la recepción');
        }
    }

    public function receive(ReceivePurchaseRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $validated = $request->validated();

            // Usar ReceptionService para crear registro de recepción con historial
            $receptionService = app(ReceptionService::class);

            // Preparar items para recepción
            $itemsData = [];

            if ($validated['receive_type'] === 'complete') {
                // Recepción completa: agregar todas las cantidades pendientes
                foreach ($purchase->items as $item) {
                    if ($item->pending_quantity > 0) {
                        $itemsData[] = [
                            'item_id' => $item->id,
                            'quantity' => $item->pending_quantity,
                        ];
                    }
                }
                $message = "Compra {$purchase->purchase_number} recibida completamente";
            } else {
                // Recepción parcial: usar las cantidades del formulario
                foreach ($validated['items'] as $itemData) {
                    if (!empty($itemData['quantity']) && $itemData['quantity'] > 0) {
                        $itemsData[] = [
                            'item_id' => $itemData['item_id'],
                            'quantity' => $itemData['quantity'],
                        ];
                    }
                }
                $message = "Recepción parcial registrada para {$purchase->purchase_number}";
            }

            // Crear la recepción si hay items
            if (!empty($itemsData)) {
                $reception = $receptionService->createReception(
                    purchase: $purchase,
                    itemsData: $itemsData,
                    deliveryNote: $validated['delivery_note'] ?? null,
                    notes: $validated['notes'] ?? null
                );
                $message = "Recepción {$reception->reception_number} registrada exitosamente";
            } else {
                return redirect()->route('admin.purchases.receive', $id)
                    ->with('error', 'Debe especificar al menos una cantidad a recibir');
            }

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', $message);
        } catch (PurchaseException $e) {
            Log::warning('Error de negocio al recibir compra: ' . $e->getMessage(), [
                'context' => $e->getContext(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.receive', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al recibir compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.purchases.receive', $id)
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with(['proveedor'])
                ->where('activo', true)
                ->findOrFail((int) $id);

            if (!$purchase->can_cancel) {
                return redirect()->route('admin.purchases.show', $purchase->id)
                    ->with('error', "No se puede cancelar la compra en estado: {$purchase->status->label()}");
            }

            return view('admin.purchases.cancel', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar cancelación de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
                ->with('error', 'Error al cargar la cancelación');
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('admin.purchases.index')
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

            return redirect()->route('admin.purchases.index')
                ->with('success', "Compra {$purchase->purchase_number} cancelada");
        } catch (PurchaseException $e) {
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            return redirect()->route('admin.purchases.cancel', $id)
                ->withInput()
                ->withErrors($e->errors());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cancelar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.show', $id)
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
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::with(['proveedor', 'items'])
                ->where('activo', true)
                ->findOrFail((int) $id);

            if ($purchase->status !== PurchaseStatus::DRAFT) {
                return redirect()->route('admin.purchases.show', $purchase->id)
                    ->with('error', 'Solo se pueden eliminar compras en borrador');
            }

            return view('admin.purchases.delete', compact('purchase'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al cargar eliminación de compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
                ->with('error', 'Error al cargar la eliminación');
        }
    }

    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('admin.purchases.index')
                    ->with('error', 'Compra no válida');
            }

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $purchaseNumber = $purchase->purchase_number;

            $this->purchaseService->delete($purchase);

            return redirect()->route('admin.purchases.index')
                ->with('success', "Compra {$purchaseNumber} eliminada exitosamente");
        } catch (PurchaseException $e) {
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.purchases.index')
                ->with('error', 'Compra no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al eliminar compra: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.index')
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

            // Cargar material con su unidad base (de inventario)
            $material = Material::with(['baseUnit', 'category.allowedUnits'])->findOrFail((int) $materialId);

            // Unidad base del material (unidad de inventario)
            $baseUnitId = $material->base_unit_id;

            // Unidad base siempre disponible
            $units = collect();
            if ($material->baseUnit) {
                $units->push([
                    'id' => $material->baseUnit->id,
                    'name' => $material->baseUnit->name,
                    'symbol' => $material->baseUnit->symbol,
                    'conversion_factor' => 1,
                    'is_base' => true,
                ]);
            }

            // Unidades de compra con conversión configurada para este material
            $conversions = MaterialUnitConversion::where('material_id', $material->id)
                ->with('fromUnit')
                ->get();

            foreach ($conversions as $conversion) {
                if ($conversion->fromUnit) {
                    $units->push([
                        'id' => $conversion->from_unit_id,
                        'name' => $conversion->fromUnit->name,
                        'symbol' => $conversion->fromUnit->symbol,
                        'conversion_factor' => $conversion->conversion_factor,
                        'is_base' => false,
                    ]);
                }
            }

            return response()->json([
                'material_id' => $material->id,
                'material_name' => $material->name,
                'base_unit_id' => $baseUnitId,
                'base_unit_symbol' => $material->baseUnit->symbol ?? '',
                'units' => $units,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Material no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error AJAX getUnitsForMaterial: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener unidades'], 500);
        }
    }

    /**
     * Anular una recepción
     */
    public function voidReception(Request $request, $id, $receptionId)
    {
        try {
            $validated = $request->validate([
                'void_reason' => [
                    'required',
                    'string',
                    'min:10',
                    'max:500',
                ],
            ], [
                'void_reason.required' => 'El motivo de anulación es obligatorio.',
                'void_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            ]);

            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);
            $reception = PurchaseReception::where('purchase_id', $purchase->id)
                ->findOrFail((int) $receptionId);

            $receptionService = app(ReceptionService::class);
            $receptionService->voidReception($reception, strip_tags(trim($validated['void_reason'])));

            return redirect()->route('admin.purchases.show', $purchase->id)
                ->with('success', "Recepción {$reception->reception_number} anulada exitosamente");
        } catch (InventoryException $e) {
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (PurchaseException $e) {
            return redirect()->route('admin.purchases.show', $id)
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error al anular recepción: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'reception_id' => $receptionId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.show', $id)
                ->with('error', 'Error al anular la recepción');
        }
    }

    /**
     * Recalcular estado de una compra (útil para corregir inconsistencias)
     */
    public function recalculateStatus($id)
    {
        try {
            $purchase = Purchase::where('activo', true)->findOrFail((int) $id);

            $receptionService = app(ReceptionService::class);
            $receptionService->recalculatePurchaseStatus($purchase);

            return redirect()->route('admin.purchases.show', $id)
                ->with('success', "Estado de {$purchase->purchase_number} recalculado: {$purchase->fresh()->status->label()}");
        } catch (\Exception $e) {
            Log::error('Error al recalcular estado: ' . $e->getMessage(), [
                'purchase_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.purchases.show', $id)
                ->with('error', 'Error al recalcular el estado');
        }
    }
}
