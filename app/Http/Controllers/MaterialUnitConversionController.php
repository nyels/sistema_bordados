<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialUnitConversionRequest;
use App\Models\Material;
use App\Models\MaterialUnitConversion;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MaterialUnitConversionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX - Listar conversiones de un material
    |--------------------------------------------------------------------------
    */

    public function index($materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->findOrFail((int) $materialId);

            $conversions = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->orderBy('id')
                ->get();

            return view('admin.material-conversions.index', compact('material', 'conversions'));
        } catch (\Exception $e) {
            Log::error('Error al listar conversiones de material: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Error al cargar las conversiones');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create($materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->findOrFail((int) $materialId);

            $baseUnitId = $material->category->base_unit_id;

            // Solo unidades de compra compatibles con la unidad base
            $purchaseUnits = Unit::getPurchaseUnitsFor($baseUnitId);

            if ($purchaseUnits->isEmpty()) {
                return redirect()->route('material-conversions.index', $material->id)
                    ->with('error', 'No hay unidades de compra configuradas para ' . ($material->category->baseUnit->name ?? 'esta unidad base'));
            }

            // Unidades ya usadas
            $usedUnitIds = MaterialUnitConversion::where('material_id', $material->id)
                ->pluck('from_unit_id')
                ->toArray();

            return view('admin.material-conversions.create', compact(
                'material',
                'purchaseUnits',
                'usedUnitIds'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de conversión: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(MaterialUnitConversionRequest $request, $materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || $materialId > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $conversion = new MaterialUnitConversion();
            $conversion->material_id = $material->id;
            $conversion->from_unit_id = (int) $request->from_unit_id;
            $conversion->to_unit_id = (int) $request->to_unit_id;
            $conversion->conversion_factor = (float) $request->conversion_factor;
            $conversion->save();

            DB::commit();

            Log::info('Conversión de unidad creada', [
                'conversion_id' => $conversion->id,
                'material_id' => $material->id,
                'material_name' => $material->name,
                'from_unit_id' => $conversion->from_unit_id,
                'to_unit_id' => $conversion->to_unit_id,
                'factor' => $conversion->conversion_factor,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('material-conversions.index', $material->id)
                ->with('success', 'Conversión creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear conversión de unidad: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('material-conversions.create', $materialId)
                ->withInput()
                ->with('error', 'Error al crear la conversión');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->findOrFail((int) $materialId);

            $conversion = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->findOrFail((int) $id);

            $baseUnitId = $material->category->base_unit_id;

            // Solo unidades de compra compatibles
            $purchaseUnits = Unit::getPurchaseUnitsFor($baseUnitId);

            // Unidades ya usadas (excepto la actual)
            $usedUnitIds = MaterialUnitConversion::where('material_id', $material->id)
                ->where('id', '!=', $conversion->id)
                ->pluck('from_unit_id')
                ->toArray();

            return view('admin.material-conversions.edit', compact(
                'material',
                'conversion',
                'purchaseUnits',
                'usedUnitIds'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar conversión para editar: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Conversión no encontrada');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(MaterialUnitConversionRequest $request, $materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $conversion = MaterialUnitConversion::where('material_id', $material->id)
                ->findOrFail((int) $id);

            $oldFactor = $conversion->conversion_factor;

            $conversion->from_unit_id = (int) $request->from_unit_id;
            $conversion->to_unit_id = (int) $request->to_unit_id;
            $conversion->conversion_factor = (float) $request->conversion_factor;

            if (!$conversion->isDirty()) {
                return redirect()->route('material-conversions.index', $material->id)
                    ->with('info', 'No se realizaron cambios');
            }

            $conversion->save();

            DB::commit();

            Log::info('Conversión de unidad actualizada', [
                'conversion_id' => $conversion->id,
                'material_id' => $material->id,
                'old_factor' => $oldFactor,
                'new_factor' => $conversion->conversion_factor,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('material-conversions.index', $material->id)
                ->with('success', 'Conversión actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar conversión: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('material-conversions.edit', [$materialId, $id])
                ->withInput()
                ->with('error', 'Error al actualizar la conversión');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM DELETE
    |--------------------------------------------------------------------------
    */

    public function confirmDelete($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->findOrFail((int) $materialId);

            $conversion = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->findOrFail((int) $id);

            return view('admin.material-conversions.delete', compact('material', 'conversion'));
        } catch (\Exception $e) {
            Log::error('Error al cargar conversión para eliminar: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Conversión no encontrada');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($materialId, $id)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1 || !is_numeric($id) || $id < 1) {
                return redirect()->route('materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $conversion = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->findOrFail((int) $id);

            $conversionInfo = $conversion->display_conversion;

            $conversion->delete();

            DB::commit();

            Log::info('Conversión de unidad eliminada', [
                'conversion_id' => $id,
                'material_id' => $material->id,
                'material_name' => $material->name,
                'conversion_info' => $conversionInfo,
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('material-conversions.index', $material->id)
                ->with('success', 'Conversión eliminada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar conversión: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('material-conversions.index', $materialId)
                ->with('error', 'Error al eliminar la conversión');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Obtener factor de conversión
    |--------------------------------------------------------------------------
    */

    public function getConversionFactor(Request $request, $materialId, $fromUnitId)
    {
        try {
            if (!is_numeric($materialId) || !is_numeric($fromUnitId)) {
                return response()->json(['error' => 'Parámetros no válidos'], 400);
            }

            $conversion = MaterialUnitConversion::where('material_id', (int) $materialId)
                ->where('from_unit_id', (int) $fromUnitId)
                ->with(['fromUnit', 'toUnit'])
                ->first();

            if (!$conversion) {
                return response()->json([
                    'found' => false,
                    'message' => 'No existe conversión configurada',
                ]);
            }

            return response()->json([
                'found' => true,
                'conversion_factor' => $conversion->conversion_factor,
                'from_unit' => $conversion->fromUnit->symbol ?? '',
                'to_unit' => $conversion->toUnit->symbol ?? '',
                'display' => $conversion->display_conversion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error AJAX getConversionFactor: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener conversión'], 500);
        }
    }
}
