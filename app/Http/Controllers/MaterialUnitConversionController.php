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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
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

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit', 'consumptionUnit'])
                ->findOrFail((int) $materialId);

            // =============================================
            // ARQUITECTURA EMPRESARIAL:
            // Determinar la unidad de consumo (canonical)
            // =============================================
            $consumptionUnit = $material->consumptionUnit
                ?? $material->baseUnit; // Fallback directo a la unidad base (ej: Metro)

            if (!$consumptionUnit) {
                return redirect()->route('admin.material-conversions.index', $material->id)
                    ->with('error', 'Este material no tiene configurada una unidad base válida.');
            }

            // =============================================
            // Filtrar unidades de compra compatibles:
            // - Tipo LOGISTIC o METRIC_PACK
            // - Que sean compatibles con la unidad de consumo
            // - Excluir la unidad base del material (ya es la principal)
            // =============================================
            // =============================================
            // Filtrar unidades de compra compatibles:
            // - Tipo LOGISTIC o METRIC_PACK
            // - Que sean compatibles con la unidad de consumo O sean genéricas (null)
            // - Excluir la unidad base del material (ya es la principal)
            // =============================================
            // =============================================
            // Filtrar unidades de compra compatibles:
            // - Que pertenezcan a la CATEGORIA del material (Whitelist)
            // - Tipo LOGISTIC o METRIC_PACK
            // - Que sean compatibles con la unidad de consumo O sean genéricas (null)
            // - Excluir la unidad base del material (ya es la principal)
            // =============================================

            // Si la categoría no tiene unidades asignadas, fallback a todas las compatibles
            // (Opcional: puedes ser estricto y no mostrar nada si no hay asignadas)
            $categoryUnitsQuery = $material->category->allowedUnits();

            if ($categoryUnitsQuery->count() > 0) {
                $baseQuery = $categoryUnitsQuery;
            } else {
                $baseQuery = Unit::query();
            }

            $purchaseUnits = $baseQuery->where('activo', true)
                ->where(function ($query) use ($consumptionUnit) {
                    $query->where('compatible_base_unit_id', $consumptionUnit->id)
                        ->orWhereNull('compatible_base_unit_id');
                })
                ->where('units.id', '!=', $material->base_unit_id) // Excluir la unidad principal (desambiguar ID)
                ->whereIn('unit_type', ['logistic', 'metric_pack'])
                ->orderBy('unit_type') // Primero logistic, luego metric_pack
                ->orderBy('name')
                ->get();

            // if ($purchaseUnits->isEmpty()) {
            //     return redirect()->route('admin.material-conversions.index', $material->id)
            //         ->with('error', "No hay unidades de compra disponibles. Cree unidades de compra o presentaciones.");
            // }

            // Conversiones existentes (para usarlas como intermediarias)
            // Ej: Si ya existe Cono -> Metro, podemos usar Cono para definir Caja
            $existingConversions = MaterialUnitConversion::where('material_id', $material->id)
                ->with('fromUnit')
                ->get();

            // IDs ya usados (para no repetir origen)
            $usedUnitIds = $existingConversions->pluck('from_unit_id')->toArray();

            return view('admin.material-conversions.create', compact(
                'material',
                'purchaseUnits',
                'usedUnitIds',
                'consumptionUnit',
                'existingConversions'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de conversión: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)->findOrFail((int) $materialId);

            DB::beginTransaction();

            $conversion = new MaterialUnitConversion();
            $conversion->material_id = $material->id;
            $conversion->from_unit_id = (int) $request->from_unit_id;
            $conversion->to_unit_id = (int) $request->to_unit_id;
            $conversion->conversion_factor = (float) $request->conversion_factor;
            $conversion->label = $request->label;

            // Guardar el modo de conversión
            $conversionMode = $request->input('conversion_mode', MaterialUnitConversion::MODE_MANUAL);
            $conversion->conversion_mode = in_array($conversionMode, [MaterialUnitConversion::MODE_MANUAL, MaterialUnitConversion::MODE_POR_CONTENIDO])
                ? $conversionMode
                : MaterialUnitConversion::MODE_MANUAL;

            // Campos de desglose (si se usó la calculadora/wizard - Por Contenido)
            if ($request->filled('intermediate_unit_id') && $request->filled('intermediate_qty')) {
                $conversion->intermediate_unit_id = (int) $request->intermediate_unit_id;
                $conversion->intermediate_qty = (float) $request->intermediate_qty;
                // Forzar modo por_contenido si tiene datos de desglose
                $conversion->conversion_mode = MaterialUnitConversion::MODE_POR_CONTENIDO;

                // VALIDACIÓN DE INTEGRIDAD (ARQUITECTURA EMPRESARIAL)
                // Si la unidad intermedia es la misma que la unidad de consumo (Base),
                // el valor unitario IMPLÍCITO debe ser 1.
                // Por tanto, el Factor Total debe ser igual a la Cantidad Intermedia.
                $consumptionUnit = $material->consumptionUnit ?? $material->baseUnit;
                if ($consumptionUnit && $conversion->intermediate_unit_id == $consumptionUnit->id) {
                    $diff = abs($conversion->conversion_factor - $conversion->intermediate_qty);
                    if ($diff > 0.001) {
                        throw new \Exception('Error de integridad: Si selecciona la unidad base como contenido, el valor unitario debe ser 1.');
                    }
                }
            } else {
                // Si no hay datos de desglose, limpiar campos intermedios
                $conversion->intermediate_unit_id = null;
                $conversion->intermediate_qty = null;
            }

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

            return redirect()->route('admin.material-conversions.create', $material->id)
                ->with('success', 'Conversión guardada. Ahora puede agregar otra referencia (ej. Caja).');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear conversión de unidad: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.material-conversions.create', $materialId)
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit', 'consumptionUnit'])
                ->findOrFail((int) $materialId);

            $conversion = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->findOrFail((int) $id);

            // Determinar unidad de consumo
            $consumptionUnit = $material->consumptionUnit
                ?? $material->baseUnit; // Fallback a la unidad base

            // Unidades de compra compatibles
            $purchaseUnits = Unit::where('activo', true)
                ->where(function ($query) use ($consumptionUnit) {
                    $query->where('compatible_base_unit_id', $consumptionUnit?->id)
                        ->orWhereNull('compatible_base_unit_id');
                })
                ->where('id', '!=', $material->base_unit_id)
                ->whereIn('unit_type', ['logistic', 'metric_pack'])
                ->orderBy('unit_type')
                ->orderBy('name')
                ->get();

            // Unidades ya usadas (excepto la actual)
            $usedUnitIds = MaterialUnitConversion::where('material_id', $material->id)
                ->where('id', '!=', $conversion->id)
                ->pluck('from_unit_id')
                ->toArray();

            return view('admin.material-conversions.edit', compact(
                'material',
                'conversion',
                'purchaseUnits',
                'usedUnitIds',
                'consumptionUnit'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar conversión para editar: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
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
            $conversion->label = $request->label;

            // Actualizar modo de conversión y campos de desglose
            $conversionMode = $request->input('conversion_mode', MaterialUnitConversion::MODE_MANUAL);
            $conversion->conversion_mode = in_array($conversionMode, [MaterialUnitConversion::MODE_MANUAL, MaterialUnitConversion::MODE_POR_CONTENIDO])
                ? $conversionMode
                : MaterialUnitConversion::MODE_MANUAL;

            if ($request->filled('intermediate_unit_id') && $request->filled('intermediate_qty')) {
                $conversion->intermediate_unit_id = (int) $request->intermediate_unit_id;
                $conversion->intermediate_qty = (float) $request->intermediate_qty;
                $conversion->conversion_mode = MaterialUnitConversion::MODE_POR_CONTENIDO;
            } else {
                $conversion->intermediate_unit_id = null;
                $conversion->intermediate_qty = null;
            }

            if (!$conversion->isDirty()) {
                return redirect()->route('admin.material-conversions.index', $material->id)
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

            return redirect()->route('admin.material-conversions.index', $material->id)
                ->with('success', 'Conversión actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar conversión: ' . $e->getMessage(), [
                'material_id' => $materialId,
                'conversion_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.material-conversions.edit', [$materialId, $id])
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Parámetros no válidos');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
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

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
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

            return redirect()->route('admin.material-conversions.index', $material->id)
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
    | AJAX: Obtener todas las conversiones de un material (para modal)
    |--------------------------------------------------------------------------
    */

    public function getConversionsForModal(Request $request, $materialId)
    {
        try {
            if (!is_numeric($materialId) || $materialId < 1) {
                return response()->json(['error' => 'Material no válido'], 400);
            }

            $material = Material::with(['consumptionUnit', 'baseUnit'])
                ->find((int) $materialId);

            if (!$material) {
                return response()->json(['error' => 'Material no encontrado'], 404);
            }

            $conversions = MaterialUnitConversion::where('material_id', $material->id)
                ->with(['fromUnit', 'toUnit'])
                ->orderBy('label')
                ->get();

            $consumptionUnit = $material->consumptionUnit ?? $material->baseUnit;

            $data = $conversions->map(function ($conv) use ($consumptionUnit) {
                return [
                    'id' => $conv->id,
                    'label' => $conv->label ?: $conv->fromUnit->name,
                    'from_unit_id' => $conv->from_unit_id,
                    'from_unit_name' => $conv->fromUnit->name ?? '',
                    'from_unit_symbol' => $conv->fromUnit->symbol ?? '',
                    'to_unit_id' => $conv->to_unit_id,
                    'to_unit_symbol' => $conv->toUnit->symbol ?? '',
                    'conversion_factor' => (float) $conv->conversion_factor,
                    'display' => "1 {$conv->fromUnit->name} = {$conv->conversion_factor} {$consumptionUnit->symbol}",
                ];
            });

            return response()->json([
                'success' => true,
                'material_id' => $material->id,
                'material_name' => $material->name,
                'consumption_unit' => $consumptionUnit->symbol ?? 'u',
                'conversions' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error AJAX getConversionsForModal: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener conversiones'], 500);
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
