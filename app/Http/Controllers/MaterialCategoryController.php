<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialCategoryRequest;
use App\Models\MaterialCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class MaterialCategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = MaterialCategory::where('activo', true)
                ->with(['allowedUnits', 'defaultInventoryUnit'])
                ->withCount(['materials' => fn($q) => $q->where('activo', true)])
                ->ordered()
                ->get();

            if (request()->ajax()) {
                return view('admin.material-categories.partials.table', compact('categories'));
            }

            $inventoryUnits = Unit::active()->canonical()->ordered()->get();

            return view('admin.material-categories.index', compact('categories', 'inventoryUnits'));
        } catch (Throwable $e) {
            Log::error('Error al listar categorías de materiales: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return view('admin.material-categories.index', ['categories' => collect()])
                ->with('error', 'Error al cargar las categorías');
        }
    }

    public function create()
    {
        try {
            // Cargar unidades de inventario (canonical) para el selector
            $inventoryUnits = Unit::active()->canonical()->ordered()->get();

            return view('admin.material-categories.create', compact('inventoryUnits'));
        } catch (Throwable $e) {
            Log::error('Error al cargar formulario de categoría: ' . $e->getMessage());
            return redirect()->route('material-categories.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    public function store(MaterialCategoryRequest $request): JsonResponse|RedirectResponse
    {
        try {
            DB::beginTransaction();

            $name = mb_strtoupper(trim($request->name));

            // Verificar si ya existe (activa o inactiva)
            $existing = MaterialCategory::where('name', $name)->first();

            if ($existing) {
                if ($existing->activo) {
                    $msg = 'Ya existe una categoría con este nombre.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'error' => $msg], 422);
                    }
                    return back()->withErrors(['name' => $msg])->withInput();
                }

                // Reactivar si existía pero estaba eliminada
                $existing->activo = true;
                $existing->description = $request->filled('description') ? trim($request->description) : null;
                $existing->default_inventory_unit_id = $request->filled('default_inventory_unit_id')
                    ? (int) $request->default_inventory_unit_id
                    : null;
                $existing->allow_unit_override = $request->boolean('allow_unit_override', true);
                $existing->save();

                DB::commit();

                $msg = 'Categoría reactivada exitosamente';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'data' => $existing]);
                }
                return redirect()->route('admin.material-categories.index')->with('success', $msg);
            }

            $category = new MaterialCategory();
            $category->name = $name;
            $category->slug = Str::slug($request->name);
            $category->description = $request->filled('description')
                ? trim($request->description)
                : null;

            // Nuevos campos UX V2
            $category->default_inventory_unit_id = $request->filled('default_inventory_unit_id')
                ? (int) $request->default_inventory_unit_id
                : null;
            $category->allow_unit_override = $request->boolean('allow_unit_override', true);

            $category->activo = true;
            $category->save();

            DB::commit();

            Log::info('Categoría de material creada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'default_inventory_unit_id' => $category->default_inventory_unit_id,
                'user_id' => Auth::id(),
            ]);

            $msg = 'Categoría creada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $category]);
            }
            return redirect()->route('admin.material-categories.index')->with('success', $msg);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear categoría de material: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->validated(),
            ]);

            $msg = 'Error al crear la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.material-categories.index')->with('error', $msg);
        }
    }

    public function edit($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', 'Categoría no válida');
            }

            $category = MaterialCategory::where('activo', true)
                ->with(['allowedUnits', 'defaultInventoryUnit'])
                ->findOrFail((int) $id);

            // Cargar unidades de inventario (canonical) para el selector
            $inventoryUnits = Unit::active()->canonical()->ordered()->get();

            return view('admin.material-categories.edit', compact('category', 'inventoryUnits'));
        } catch (Throwable $e) {
            Log::error('Error al cargar categoría para editar: ' . $e->getMessage());
            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Categoría no encontrada');
        }
    }

    public function update(MaterialCategoryRequest $request, $id): JsonResponse|RedirectResponse
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                $msg = 'Categoría no válida';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 400);
                }
                return redirect()->route('admin.material-categories.index')->with('error', $msg);
            }

            DB::beginTransaction();

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            $category->name = mb_strtoupper(trim($request->name));
            $category->slug = Str::slug($request->name);
            $category->description = $request->filled('description')
                ? trim($request->description)
                : null;

            // Nuevos campos UX V2
            $category->default_inventory_unit_id = $request->filled('default_inventory_unit_id')
                ? (int) $request->default_inventory_unit_id
                : null;
            $category->allow_unit_override = $request->boolean('allow_unit_override', true);

            if (!$category->isDirty()) {
                $msg = 'No se realizaron cambios';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $msg, 'type' => 'info']);
                }
                return redirect()->route('admin.material-categories.index')->with('info', $msg);
            }

            $category->save();

            DB::commit();

            Log::info('Categoría de material actualizada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'default_inventory_unit_id' => $category->default_inventory_unit_id,
                'user_id' => Auth::id(),
            ]);

            $msg = 'Categoría actualizada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success', 'data' => $category]);
            }
            return redirect()->route('admin.material-categories.index')->with('success', $msg);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);

            $msg = 'Error al actualizar la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.material-categories.index')->with('error', $msg);
        }
    }

    public function confirmDelete($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', 'Categoría no válida');
            }

            $category = MaterialCategory::where('activo', true)
                ->findOrFail((int) $id);

            return view('admin.material-categories.delete', compact('category'));
        } catch (Throwable $e) {
            Log::error('Error al cargar categoría para eliminar: ' . $e->getMessage());
            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Categoría no encontrada');
        }
    }

    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                $msg = 'Categoría no válida';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 400);
                }
                return redirect()->route('admin.material-categories.index')->with('error', $msg);
            }

            DB::beginTransaction();

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            $validation = $category->canDelete();
            if (!$validation['can_delete']) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $validation['message']], 422);
                }
                return redirect()->route('admin.material-categories.index')->with('error', $validation['message']);
            }

            $category->activo = false;
            $category->save();

            DB::commit();

            Log::info('Categoría de material eliminada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'user_id' => Auth::id(),
            ]);

            $msg = 'Categoría eliminada exitosamente';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'type' => 'success']);
            }
            return redirect()->route('admin.material-categories.index')->with('success', $msg);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al eliminar categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);

            $msg = 'Error al eliminar la categoría';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return redirect()->route('admin.material-categories.index')->with('error', $msg);
        }
    }
    /**
     * Cargar contenido del formulario de edición para modal AJAX
     */
    public function editContent($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return response('<div class="alert alert-danger m-3">Categoría no válida</div>', 400);
            }

            $category = MaterialCategory::where('activo', true)
                ->with(['allowedUnits', 'defaultInventoryUnit'])
                ->findOrFail((int) $id);

            $inventoryUnits = Unit::active()->canonical()->ordered()->get();

            return view('admin.material-categories.partials.edit-content', compact('category', 'inventoryUnits'));
        } catch (Throwable $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return response('<div class="alert alert-danger m-3">Error al cargar el formulario</div>', 500);
        }
    }

    /**
     * Cargar contenido de confirmación de eliminación para modal AJAX
     */
    public function deleteContent($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return response('<div class="alert alert-danger m-3">Categoría no válida</div>', 400);
            }

            $category = MaterialCategory::where('activo', true)
                ->withCount(['materials' => fn($q) => $q->where('activo', true)])
                ->findOrFail((int) $id);

            return view('admin.material-categories.partials.delete-content', compact('category'));
        } catch (Throwable $e) {
            Log::error('Error al cargar confirmación de eliminación: ' . $e->getMessage());
            return response('<div class="alert alert-danger m-3">Error al cargar</div>', 500);
        }
    }

    public function getMaterials($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return response()->json(['error' => 'Categoría no válida'], 400);
            }

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            $materials = $category->materials()
                ->where('activo', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($materials);
        } catch (Throwable $e) {
            Log::error('Error al obtener materiales de la categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Error al cargar materiales'], 500);
        }
    }
    public function getUnits($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return response()->json(['error' => 'Categoría no válida'], 400);
            }

            $category = MaterialCategory::where('activo', true)
                ->with(['allowedUnits' => function ($q) {
                    // Permitir todas las unidades asignadas (Logísticas, Canónicas, Packs)
                    $q->ordered();
                }])
                ->findOrFail((int) $id);

            // Si no tiene unidades asignadas, devolver vacío.
            // El frontend mostrará "Sin unidades".

            return response()->json($category->allowedUnits);
        } catch (Throwable $e) {
            Log::error('Error al obtener unidades de la categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Error al cargar unidades'], 500);
        }
    }
}
