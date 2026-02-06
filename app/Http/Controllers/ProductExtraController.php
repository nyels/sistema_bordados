<?php

namespace App\Http\Controllers;

use App\Models\ProductExtra;
use App\Models\MaterialVariant;
use App\Models\ExtraCategory;
use App\Http\Requests\ProductExtraRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class ProductExtraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $extras = ProductExtra::with(['materials.material.consumptionUnit', 'materials.material.baseUnit', 'category'])
                ->orderBy('name')
                ->get();

            // Cargar variantes de material con sus unidades para el selector (ordenadas A-Z por nombre)
            $materialVariants = MaterialVariant::with(['material.consumptionUnit', 'material.baseUnit'])
                ->join('materials', 'material_variants.material_id', '=', 'materials.id')
                ->where('material_variants.activo', true)
                ->orderBy('materials.name', 'asc')
                ->orderBy('material_variants.color', 'asc')
                ->select('material_variants.*')
                ->get();

            // Categorías activas
            $categories = ExtraCategory::where('activo', true)->orderBy('nombre')->get();

            return view('admin.product_extras.index', compact('extras', 'materialVariants', 'categories'));
        } catch (Throwable $e) {
            Log::error('[ProductExtra@index] Error al cargar listado', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('error', 'Error al cargar el listado de extras');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Cargar variantes de material con sus unidades para el selector (ordenadas A-Z por nombre)
        $materialVariants = MaterialVariant::with(['material.consumptionUnit', 'material.baseUnit'])
            ->join('materials', 'material_variants.material_id', '=', 'materials.id')
            ->where('material_variants.activo', true)
            ->orderBy('materials.name', 'asc')
            ->orderBy('material_variants.color', 'asc')
            ->select('material_variants.*')
            ->get();

        // Categorías activas
        $categories = ExtraCategory::where('activo', true)->orderBy('nombre')->get();

        return view('admin.product_extras.create', compact('materialVariants', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductExtraRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $userId = Auth::id();
        $requestData = $request->validated();

        Log::info('[ProductExtra@store] Iniciando creación de extra', [
            'user_id' => $userId,
            'name' => $requestData['name'] ?? null,
            'consumes_inventory' => $request->boolean('consumes_inventory'),
            'materials_count' => count($request->input('materials', []))
        ]);

        try {
            $extra = null;

            DB::transaction(function () use ($request, $requestData, &$extra) {
                $extra = new ProductExtra();
                $extra->name = mb_strtoupper(trim($requestData['name']), 'UTF-8');
                $extra->extra_category_id = $requestData['extra_category_id'] ?? null;
                $extra->cost_addition = $requestData['cost_addition'];
                $extra->price_addition = $requestData['price_addition'];
                $extra->minutes_addition = $requestData['minutes_addition'] ?? 0;
                $extra->consumes_inventory = $request->boolean('consumes_inventory');
                $extra->save();

                // Sincronizar materiales si consume inventario
                if ($extra->consumesInventory()) {
                    $extra->syncMaterials($request->getMaterialsForSync());
                }
            });

            Log::info('[ProductExtra@store] Extra creado exitosamente', [
                'user_id' => $userId,
                'extra_id' => $extra->id,
                'extra_name' => $extra->name
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Extra creado exitosamente',
                    'extra_id' => $extra->id
                ]);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra creado exitosamente');

        } catch (Throwable $e) {
            Log::error('[ProductExtra@store] Error al crear extra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el extra. Por favor, intente nuevamente.'
                ], 500);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al crear el extra');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $extra = ProductExtra::with('materials.material.consumptionUnit')->findOrFail($id);
            return view('admin.product_extras.show', compact('extra'));
        } catch (ModelNotFoundException $e) {
            Log::warning('[ProductExtra@show] Extra no encontrado', [
                'extra_id' => $id,
                'user_id' => Auth::id()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'El extra solicitado no existe');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $extra = ProductExtra::with('materials')->findOrFail($id);

            // Cargar variantes de material con sus unidades (ordenadas A-Z por nombre)
            $materialVariants = MaterialVariant::with(['material.consumptionUnit', 'material.baseUnit'])
                ->join('materials', 'material_variants.material_id', '=', 'materials.id')
                ->where('material_variants.activo', true)
                ->orderBy('materials.name', 'asc')
                ->orderBy('material_variants.color', 'asc')
                ->select('material_variants.*')
                ->get();

            // Materiales existentes del extra
            $extraMaterials = $extra->materials;

            // Categorías activas
            $categories = ExtraCategory::where('activo', true)->orderBy('nombre')->get();

            return view('admin.product_extras.edit', compact('extra', 'materialVariants', 'extraMaterials', 'categories'));
        } catch (ModelNotFoundException $e) {
            Log::warning('[ProductExtra@edit] Extra no encontrado', [
                'extra_id' => $id,
                'user_id' => Auth::id()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'El extra solicitado no existe');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductExtraRequest $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $userId = Auth::id();
        $requestData = $request->validated();

        Log::info('[ProductExtra@update] Iniciando actualización de extra', [
            'user_id' => $userId,
            'extra_id' => $id,
            'name' => $requestData['name'] ?? null,
            'consumes_inventory' => $request->boolean('consumes_inventory'),
            'materials_count' => count($request->input('materials', []))
        ]);

        try {
            $extra = ProductExtra::findOrFail($id);
            $originalData = $extra->toArray();

            DB::transaction(function () use ($request, $requestData, $extra) {
                $extra->name = mb_strtoupper(trim($requestData['name']), 'UTF-8');
                $extra->extra_category_id = $requestData['extra_category_id'] ?? null;
                $extra->cost_addition = $requestData['cost_addition'];
                $extra->price_addition = $requestData['price_addition'];
                $extra->minutes_addition = $requestData['minutes_addition'] ?? 0;
                $extra->consumes_inventory = $request->boolean('consumes_inventory');
                $extra->save();

                // Sincronizar materiales
                $extra->syncMaterials($request->getMaterialsForSync());
            });

            Log::info('[ProductExtra@update] Extra actualizado exitosamente', [
                'user_id' => $userId,
                'extra_id' => $extra->id,
                'extra_name' => $extra->name,
                'changes' => array_diff_assoc($extra->toArray(), $originalData)
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Extra actualizado exitosamente'
                ]);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra actualizado exitosamente');

        } catch (ModelNotFoundException $e) {
            Log::warning('[ProductExtra@update] Extra no encontrado', [
                'extra_id' => $id,
                'user_id' => $userId
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El extra solicitado no existe'
                ], 404);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('error', 'El extra solicitado no existe');

        } catch (Throwable $e) {
            Log::error('[ProductExtra@update] Error al actualizar extra', [
                'user_id' => $userId,
                'extra_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el extra. Por favor, intente nuevamente.'
                ], 500);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al actualizar el extra');
        }
    }

    /**
     * Confirm delete page
     */
    public function confirm_delete($id)
    {
        try {
            $extra = ProductExtra::with('materials.material.consumptionUnit')->findOrFail($id);
            return view('admin.product_extras.delete', compact('extra'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'El extra solicitado no existe');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $userId = Auth::id();

        Log::info('[ProductExtra@destroy] Iniciando eliminación de extra', [
            'user_id' => $userId,
            'extra_id' => $id
        ]);

        try {
            $extra = ProductExtra::findOrFail($id);
            $extraName = $extra->name;

            // Verificar si tiene productos asociados
            $productsCount = $extra->products()->count();
            if ($productsCount > 0) {
                Log::warning('[ProductExtra@destroy] Intento de eliminar extra con productos asociados', [
                    'user_id' => $userId,
                    'extra_id' => $id,
                    'extra_name' => $extraName,
                    'products_count' => $productsCount
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede eliminar el extra porque tiene {$productsCount} producto(s) asociado(s)."
                    ], 422);
                }
                return redirect()->route('admin.product_extras.index')
                    ->with('error', 'No se puede eliminar el extra porque tiene productos asociados.');
            }

            // Eliminar materiales relacionados primero
            $extra->materials()->detach();
            $extra->delete();

            Log::info('[ProductExtra@destroy] Extra eliminado exitosamente', [
                'user_id' => $userId,
                'extra_id' => $id,
                'extra_name' => $extraName
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Extra eliminado exitosamente'
                ]);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra eliminado exitosamente');

        } catch (ModelNotFoundException $e) {
            Log::warning('[ProductExtra@destroy] Extra no encontrado', [
                'extra_id' => $id,
                'user_id' => $userId
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El extra solicitado no existe'
                ], 404);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('error', 'El extra solicitado no existe');

        } catch (Throwable $e) {
            Log::error('[ProductExtra@destroy] Error al eliminar extra', [
                'user_id' => $userId,
                'extra_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el extra. Por favor, intente nuevamente.'
                ], 500);
            }

            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al eliminar el extra');
        }
    }

    /**
     * Obtener datos de un extra para edición via AJAX
     */
    public function getExtra($id): JsonResponse
    {
        $userId = Auth::id();

        try {
            $extra = ProductExtra::with(['materials.material.consumptionUnit'])->findOrFail($id);

            $materials = $extra->materials->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->material->name . ($variant->color ? ' - ' . $variant->color : ''),
                    'quantity' => $variant->pivot->quantity_required,
                    'unit' => $variant->material->consumptionUnit->symbol ?? ''
                ];
            });

            Log::debug('[ProductExtra@getExtra] Datos del extra cargados', [
                'user_id' => $userId,
                'extra_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'extra' => [
                    'id' => $extra->id,
                    'name' => $extra->name,
                    'extra_category_id' => $extra->extra_category_id,
                    'cost_addition' => $extra->cost_addition,
                    'price_addition' => $extra->price_addition,
                    'minutes_addition' => $extra->minutes_addition,
                    'consumes_inventory' => $extra->consumes_inventory,
                    'materials' => $materials
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('[ProductExtra@getExtra] Extra no encontrado', [
                'extra_id' => $id,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El extra solicitado no existe'
            ], 404);

        } catch (Throwable $e) {
            Log::error('[ProductExtra@getExtra] Error al obtener datos del extra', [
                'user_id' => $userId,
                'extra_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del extra'
            ], 500);
        }
    }

    /**
     * D5: Obtener todos los extras activos para el modo "solo extras" en post-venta.
     * Esta ruta permite agregar extras sin necesidad de seleccionar un producto.
     */
    public function allActive(): JsonResponse
    {
        try {
            $extras = ProductExtra::orderBy('name')
                ->select('id', 'name', 'price_addition', 'cost_addition', 'minutes_addition', 'consumes_inventory')
                ->get()
                ->map(function ($extra) {
                    return [
                        'id' => $extra->id,
                        'name' => $extra->name,
                        'price_addition' => (float) $extra->price_addition,
                        'cost_addition' => (float) $extra->cost_addition,
                        'minutes_addition' => $extra->minutes_addition,
                        'consumes_inventory' => $extra->consumes_inventory,
                    ];
                });

            return response()->json([
                'success' => true,
                'extras' => $extras,
            ]);

        } catch (Throwable $e) {
            Log::error('[ProductExtra@allActive] Error al cargar extras activos', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar extras',
                'extras' => [],
            ], 500);
        }
    }
}
