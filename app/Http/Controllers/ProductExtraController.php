<?php

namespace App\Http\Controllers;

use App\Models\ProductExtra;
use App\Models\MaterialVariant;
use App\Http\Requests\ProductExtraRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductExtraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $extras = ProductExtra::orderBy('name')->get();
        return view('admin.product_extras.index', compact('extras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Cargar variantes de material con sus unidades para el selector (ordenadas A-Z por nombre)
        // Cargar consumptionUnit y baseUnit como fallback
        $materialVariants = MaterialVariant::with(['material.consumptionUnit', 'material.baseUnit'])
            ->join('materials', 'material_variants.material_id', '=', 'materials.id')
            ->where('material_variants.activo', true)
            ->orderBy('materials.name', 'asc')
            ->orderBy('material_variants.color', 'asc')
            ->select('material_variants.*')
            ->get();

        return view('admin.product_extras.create', compact('materialVariants'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductExtraRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $extra = new ProductExtra();
                $extra->name = strtoupper(trim($request->name));
                $extra->cost_addition = $request->cost_addition;
                $extra->price_addition = $request->price_addition;
                $extra->minutes_addition = $request->minutes_addition ?? 0;
                $extra->consumes_inventory = $request->boolean('consumes_inventory');
                $extra->save();

                // Sincronizar materiales si consume inventario
                if ($extra->consumesInventory()) {
                    $extra->syncMaterials($request->getMaterialsForSync());
                }
            });

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra creado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@store] Error al crear extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al crear el extra');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $extra = ProductExtra::with('materials.material.consumptionUnit')->findOrFail($id);
        return view('admin.product_extras.show', compact('extra'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $extra = ProductExtra::with('materials')->findOrFail($id);

        // Cargar variantes de material con sus unidades (ordenadas A-Z por nombre)
        // Cargar consumptionUnit y baseUnit como fallback
        $materialVariants = MaterialVariant::with(['material.consumptionUnit', 'material.baseUnit'])
            ->join('materials', 'material_variants.material_id', '=', 'materials.id')
            ->where('material_variants.activo', true)
            ->orderBy('materials.name', 'asc')
            ->orderBy('material_variants.color', 'asc')
            ->select('material_variants.*')
            ->get();

        // Materiales existentes del extra
        $extraMaterials = $extra->materials;

        return view('admin.product_extras.edit', compact('extra', 'materialVariants', 'extraMaterials'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductExtraRequest $request, $id)
    {
        $extra = ProductExtra::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $extra) {
                $extra->name = strtoupper(trim($request->name));
                $extra->cost_addition = $request->cost_addition;
                $extra->price_addition = $request->price_addition;
                $extra->minutes_addition = $request->minutes_addition ?? 0;
                $extra->consumes_inventory = $request->boolean('consumes_inventory');
                $extra->save();

                // Sincronizar materiales
                $extra->syncMaterials($request->getMaterialsForSync());
            });

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@update] Error al actualizar extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al actualizar el extra');
        }
    }

    /**
     * Confirm delete page
     */
    public function confirm_delete($id)
    {
        $extra = ProductExtra::with('materials.material.consumptionUnit')->findOrFail($id);
        return view('admin.product_extras.delete', compact('extra'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $extra = ProductExtra::findOrFail($id);

        try {
            // Verificar si tiene productos asociados
            if ($extra->products()->count() > 0) {
                return redirect()->route('admin.product_extras.index')
                    ->with('error', 'No se puede eliminar el extra porque tiene productos asociados.');
            }

            $extra->delete();

            return redirect()->route('admin.product_extras.index')
                ->with('success', 'Extra eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('[ProductExtra@destroy] Error al eliminar extra: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return redirect()->route('admin.product_extras.index')
                ->with('error', 'Error al eliminar el extra');
        }
    }

    /**
     * D5: Obtener todos los extras activos para el modo "solo extras" en post-venta.
     * Esta ruta permite agregar extras sin necesidad de seleccionar un producto.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allActive()
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
        } catch (\Exception $e) {
            Log::error('[ProductExtra@allActive] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar extras',
                'extras' => [],
            ], 500);
        }
    }
}
