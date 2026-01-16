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

class MaterialCategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = MaterialCategory::where('activo', true)
                ->withCount(['materials' => fn($q) => $q->where('activo', true)])
                ->ordered()
                ->get();

            return view('admin.material-categories.index', compact('categories'));
        } catch (\Exception $e) {
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
            return view('admin.material-categories.create');
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de categoría: ' . $e->getMessage());
            return redirect()->route('material-categories.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    public function store(MaterialCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $category = new MaterialCategory();
            $category->name = mb_strtoupper(trim($request->name));
            $category->slug = Str::slug($request->name);
            $category->description = $request->filled('description')
                ? trim($request->description)
                : null;
            $category->activo = true;
            $category->save();

            DB::commit();

            Log::info('Categoría de material creada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('success', 'Categoría creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear categoría de material: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->validated(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Error al crear la categoría');
        }
    }

    public function edit($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', 'Categoría no válida');
            }

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            return view('admin.material-categories.edit', compact('category'));
        } catch (\Exception $e) {
            Log::error('Error al cargar categoría para editar: ' . $e->getMessage());
            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Categoría no encontrada');
        }
    }

    public function update(MaterialCategoryRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', 'Categoría no válida');
            }

            DB::beginTransaction();

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            $category->name = mb_strtoupper(trim($request->name));
            $category->slug = Str::slug($request->name);
            $category->description = $request->filled('description')
                ? trim($request->description)
                : null;

            if (!$category->isDirty()) {
                return redirect()->route('admin.material-categories.index')
                    ->with('info', 'No se realizaron cambios');
            }

            $category->save();

            DB::commit();

            Log::info('Categoría de material actualizada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('success', 'Categoría actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Error al actualizar la categoría');
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
        } catch (\Exception $e) {
            Log::error('Error al cargar categoría para eliminar: ' . $e->getMessage());
            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Categoría no encontrada');
        }
    }

    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', 'Categoría no válida');
            }

            DB::beginTransaction();

            $category = MaterialCategory::where('activo', true)->findOrFail((int) $id);

            $validation = $category->canDelete();
            if (!$validation['can_delete']) {
                return redirect()->route('admin.material-categories.index')
                    ->with('error', $validation['message']);
            }

            $category->activo = false;
            $category->save();

            DB::commit();

            Log::info('Categoría de material eliminada', [
                'category_id' => $category->id,
                'name' => $category->name,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('success', 'Categoría eliminada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.material-categories.index')
                ->with('error', 'Error al eliminar la categoría');
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
        } catch (\Exception $e) {
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
                    // Filtrar SOLO unidades logísticas puras (unit_type = 'logistic')
                    $q->logistic()->ordered();
                }])
                ->findOrFail((int) $id);

            // Si no tiene unidades asignadas, devolver vacío.
            // El frontend mostrará "Sin unidades".

            return response()->json($category->allowedUnits);
        } catch (\Exception $e) {
            Log::error('Error al obtener unidades de la categoría: ' . $e->getMessage(), [
                'category_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Error al cargar unidades'], 500);
        }
    }
}
