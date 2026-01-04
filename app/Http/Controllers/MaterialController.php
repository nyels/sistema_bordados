<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {
            $request->validate([
                'category' => ['nullable', 'integer', 'min:1', 'max:999999'],
                'search' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_]+$/u'],
            ]);

            $query = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->withCount(['activeVariants']);

            if ($request->filled('category')) {
                $query->where('material_category_id', (int) $request->input('category'));
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('composition', 'like', "%{$search}%");
                });
            }

            $materials = $query->ordered()->get();

            $categories = MaterialCategory::where('activo', true)
                ->ordered()
                ->get();

            return view('admin.materials.index', compact('materials', 'categories'));
        } catch (\Exception $e) {
            Log::error('Error al listar materiales: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return view('admin.materials.index', [
                'materials' => collect(),
                'categories' => collect(),
            ])->with('error', 'Error al cargar los materiales');
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
            $categories = MaterialCategory::where('activo', true)
                ->with('baseUnit')
                ->ordered()
                ->get();

            if ($categories->isEmpty()) {
                return redirect()->route('materials.index')
                    ->with('error', 'Debe crear al menos una categoría de material primero');
            }

            return view('admin.materials.create', compact('categories'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de material: ' . $e->getMessage(), [
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

    public function store(MaterialRequest $request)
    {
        try {
            DB::beginTransaction();

            $material = new Material();
            $material->uuid = (string) Str::uuid();
            $material->material_category_id = (int) $request->material_category_id;
            $material->name = mb_strtoupper(trim($request->name));
            $material->slug = Str::slug($request->name);
            $material->composition = $request->filled('composition')
                ? mb_strtoupper(trim($request->composition))
                : null;
            $material->description = $request->filled('description')
                ? trim($request->description)
                : null;
            $material->activo = true;
            $material->save();

            DB::commit();

            Log::info('Material creado', [
                'material_id' => $material->id,
                'name' => $material->name,
                'category_id' => $material->material_category_id,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('materials.index')
                ->with('success', 'Material creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear material: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->validated(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('materials.create')
                ->withInput()
                ->with('error', 'Error al crear el material');
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
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with('category')
                ->findOrFail((int) $id);

            $categories = MaterialCategory::where('activo', true)
                ->with('baseUnit')
                ->ordered()
                ->get();

            return view('admin.materials.edit', compact('material', 'categories'));
        } catch (\Exception $e) {
            Log::error('Error al cargar material para editar: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Material no encontrado');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(MaterialRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            DB::beginTransaction();

            $material = Material::where('activo', true)->findOrFail((int) $id);

            $oldName = $material->name;

            $material->material_category_id = (int) $request->material_category_id;
            $material->name = mb_strtoupper(trim($request->name));
            $material->slug = Str::slug($request->name);
            $material->composition = $request->filled('composition')
                ? mb_strtoupper(trim($request->composition))
                : null;
            $material->description = $request->filled('description')
                ? trim($request->description)
                : null;

            if (!$material->isDirty()) {
                return redirect()->route('materials.index')
                    ->with('info', 'No se realizaron cambios');
            }

            $material->save();

            DB::commit();

            Log::info('Material actualizado', [
                'material_id' => $material->id,
                'old_name' => $oldName,
                'new_name' => $material->name,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('materials.index')
                ->with('success', 'Material actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar material: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('materials.edit', $id)
                ->withInput()
                ->with('error', 'Error al actualizar el material');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM DELETE
    |--------------------------------------------------------------------------
    */

    public function confirmDelete($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'category.baseUnit'])
                ->withCount('activeVariants')
                ->findOrFail((int) $id);

            return view('admin.materials.delete', compact('material'));
        } catch (\Exception $e) {
            Log::error('Error al cargar material para eliminar: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Material no encontrado');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1 || $id > 999999999) {
                return redirect()->route('materials.index')
                    ->with('error', 'Material no válido');
            }

            DB::beginTransaction();

            $material = Material::where('activo', true)->findOrFail((int) $id);

            $validation = $material->canDelete();
            if (!$validation['can_delete']) {
                return redirect()->route('materials.index')
                    ->with('error', $validation['message']);
            }

            $materialName = $material->name;

            $material->activo = false;
            $material->save();

            DB::commit();

            Log::info('Material eliminado', [
                'material_id' => $material->id,
                'name' => $materialName,
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('materials.index')
                ->with('success', 'Material eliminado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar material: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('materials.index')
                ->with('error', 'Error al eliminar el material');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Obtener materiales por categoría
    |--------------------------------------------------------------------------
    */

    public function getByCategory(Request $request, $categoryId)
    {
        try {
            if (!is_numeric($categoryId) || $categoryId < 1) {
                return response()->json(['error' => 'Categoría no válida'], 400);
            }

            $materials = Material::where('activo', true)
                ->where('material_category_id', (int) $categoryId)
                ->ordered()
                ->get(['id', 'name', 'composition']);

            return response()->json($materials);
        } catch (\Exception $e) {
            Log::error('Error AJAX getByCategory: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener materiales'], 500);
        }
    }
}
