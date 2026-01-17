<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * =============================================================================
 * CONTROLADOR DE GESTIÓN: CATEGORÍA ↔ UNIDADES PERMITIDAS
 * =============================================================================
 *
 * Módulo administrativo para configurar qué unidades de compra/empaque
 * están permitidas para cada categoría de material.
 *
 * REGLAS DE NEGOCIO:
 * - Solo unidades con is_base = false (compra/empaque) pueden asignarse
 * - Cada categoría debe tener al menos 1 unidad asignada para permitir materiales
 * - No se puede eliminar una unidad si hay materiales usándola como base
 *
 * @see \App\Models\MaterialCategory::allowedUnits()
 * @see \App\Http\Requests\MaterialRequest (validación cruzada)
 */
class MaterialCategoryUnitController extends Controller
{
    /**
     * Mostrar listado de categorías con sus unidades asignadas.
     * Vista principal del módulo administrativo.
     */
    public function index()
    {
        try {
            // Categorías activas con conteo de unidades y materiales
            $categories = MaterialCategory::where('activo', true)
                ->with(['allowedUnits' => fn($q) => $q->orderBy('name')])
                ->withCount(['materials' => fn($q) => $q->where('activo', true)])
                ->ordered()
                ->get();

            // Unidades disponibles para asignar (todas)
            $availableUnits = Unit::active()
                ->where(function ($q) {
                    $q->whereIn('unit_type', ['logistic', 'canonical', 'metric_pack'])
                        ->orWhereNull('unit_type');
                })
                ->ordered()
                ->get();

            return view('admin.material-categories.units', compact('categories', 'availableUnits'));
        } catch (\Exception $e) {
            Log::error('Error al cargar gestión de unidades por categoría: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Error al cargar la configuración de unidades');
        }
    }

    /**
     * Agregar una unidad a una categoría.
     * Validaciones:
     * - La unidad debe ser de tipo compra (is_base = false)
     * - No debe existir ya la relación
     */
    public function store(Request $request, $categoryId)
    {
        try {
            // Validación básica
            $request->validate([
                'unit_id' => 'required|integer|min:1|exists:units,id',
            ], [
                'unit_id.required' => 'Debe seleccionar una unidad.',
                'unit_id.exists' => 'La unidad seleccionada no existe.',
            ]);

            $category = MaterialCategory::where('activo', true)->findOrFail($categoryId);
            $unit = Unit::where('activo', true)->findOrFail($request->unit_id);

            // VALIDACIÓN: Se eliminó la restricción estricta de solo logísticas.
            // Ahora se permite asignar Metros (Canónica), Rollos (MetricPack), etc.
            if (!$unit->activo) {
                return response()->json([
                    'success' => false,
                    'message' => "La unidad seleccionada no está activa."
                ], 422);
            }

            // Verificar si ya existe la relación
            if ($category->allowedUnits()->where('unit_id', $unit->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta unidad ya está asignada a la categoría.'
                ], 422);
            }

            DB::beginTransaction();

            $category->allowedUnits()->attach($unit->id);

            DB::commit();

            Log::info('Unidad asignada a categoría', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Unidad '{$unit->name}' asignada correctamente.",
                'unit' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría o unidad no encontrada.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al asignar unidad a categoría: ' . $e->getMessage(), [
                'category_id' => $categoryId,
                'unit_id' => $request->unit_id ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar la unidad.'
            ], 500);
        }
    }

    /**
     * Eliminar una unidad de una categoría.
     * Validaciones:
     * - No se puede eliminar si hay materiales activos usando esta unidad como base
     * - Se recomienda mantener al menos 1 unidad por categoría
     */
    public function destroy(Request $request, $categoryId, $unitId)
    {
        try {
            $category = MaterialCategory::where('activo', true)->findOrFail($categoryId);
            $unit = Unit::findOrFail($unitId);

            // VALIDACIÓN CRÍTICA: Verificar si hay materiales usando esta unidad
            $materialsUsingUnit = Material::where('material_category_id', $categoryId)
                ->where('base_unit_id', $unitId)
                ->where('activo', true)
                ->count();

            if ($materialsUsingUnit > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar: {$materialsUsingUnit} material(es) activo(s) usan esta unidad como base.",
                    'materials_count' => $materialsUsingUnit,
                ], 422);
            }

            // ADVERTENCIA: Si es la última unidad
            $currentUnitsCount = $category->allowedUnits()->count();
            if ($currentUnitsCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: la categoría debe tener al menos 1 unidad permitida.',
                    'is_last_unit' => true,
                ], 422);
            }

            DB::beginTransaction();

            $category->allowedUnits()->detach($unitId);

            DB::commit();

            Log::info('Unidad removida de categoría', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Unidad '{$unit->name}' removida correctamente.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría o unidad no encontrada.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al remover unidad de categoría: ' . $e->getMessage(), [
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al remover la unidad.'
            ], 500);
        }
    }

    /**
     * Obtener unidades asignadas a una categoría (AJAX).
     */
    public function getAssignedUnits($categoryId)
    {
        try {
            $category = MaterialCategory::where('activo', true)
                ->with(['allowedUnits' => fn($q) => $q->orderBy('name')])
                ->findOrFail($categoryId);

            $units = $category->allowedUnits->map(function ($unit) use ($categoryId) {
                // Contar materiales que usan esta unidad en esta categoría
                $materialsCount = Material::where('material_category_id', $categoryId)
                    ->where('base_unit_id', $unit->id)
                    ->where('activo', true)
                    ->count();

                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                    'materials_using' => $materialsCount,
                    'can_remove' => $materialsCount === 0,
                ];
            });

            return response()->json([
                'success' => true,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'units' => $units,
                'total_units' => $units->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las unidades.'
            ], 500);
        }
    }

    /**
     * Obtener unidades disponibles para asignar a una categoría (AJAX).
     * Excluye las ya asignadas.
     */
    public function getAvailableUnits($categoryId)
    {
        try {
            $category = MaterialCategory::where('activo', true)->findOrFail($categoryId);

            // IDs de unidades ya asignadas
            $assignedUnitIds = $category->allowedUnits()->pluck('units.id')->toArray();

            // Unidades disponibles (cualquier tipo: logísticas, canónicas, packs)
            $availableUnits = Unit::active()
                ->where(function ($q) {
                    $q->whereIn('unit_type', ['logistic', 'canonical', 'metric_pack'])
                        ->orWhereNull('unit_type'); // Por seguridad
                })
                ->whereNotIn('id', $assignedUnitIds)
                ->ordered()
                ->get(['id', 'name', 'symbol']);

            return response()->json([
                'success' => true,
                'units' => $availableUnits,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener unidades disponibles.'
            ], 500);
        }
    }

    /**
     * Verificar integridad de una categoría.
     * Útil para diagnósticos y auditorías.
     */
    public function checkIntegrity($categoryId)
    {
        try {
            $category = MaterialCategory::where('activo', true)
                ->with(['allowedUnits', 'materials.baseUnit'])
                ->findOrFail($categoryId);

            $issues = [];

            // Verificar si tiene unidades asignadas
            if ($category->allowedUnits->isEmpty()) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => 'La categoría no tiene unidades de compra asignadas.',
                ];
            }

            // Verificar materiales con unidades inválidas
            $allowedUnitIds = $category->allowedUnits->pluck('id')->toArray();

            $materialsWithInvalidUnit = Material::where('material_category_id', $categoryId)
                ->where('activo', true)
                ->whereNotIn('base_unit_id', $allowedUnitIds)
                ->get(['id', 'name', 'base_unit_id']);

            if ($materialsWithInvalidUnit->isNotEmpty()) {
                $issues[] = [
                    'type' => 'error',
                    'message' => "{$materialsWithInvalidUnit->count()} material(es) tienen una unidad base no permitida.",
                    'materials' => $materialsWithInvalidUnit->toArray(),
                ];
            }

            return response()->json([
                'success' => true,
                'category' => $category->name,
                'has_issues' => !empty($issues),
                'issues' => $issues,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar integridad.'
            ], 500);
        }
    }
}
