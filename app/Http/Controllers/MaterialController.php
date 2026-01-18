<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUnitConversion;
use App\Models\Unit;
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
                ->with(['category', 'baseUnit', 'unitConversions.fromUnit', 'unitConversions.intermediateUnit'])
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

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('admin.materials.partials.table_rows', compact('materials'))->render(),
                ]);
            }

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
                ->ordered()
                ->get();

            if ($categories->isEmpty()) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Debe crear al menos una categoría de material primero');
            }

            return view('admin.materials.create', compact('categories'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de material: ' . $e->getMessage(), [
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

    public function store(MaterialRequest $request)
    {
        try {
            DB::beginTransaction();

            $material = new Material();
            $material->uuid = (string) Str::uuid();
            $material->material_category_id = (int) $request->material_category_id;
            $material->base_unit_id = (int) $request->base_unit_id;
            $material->consumption_unit_id = null;
            $material->conversion_factor = 1.0;
            $material->has_color = $request->boolean('has_color');
            $material->name = mb_strtoupper(trim($request->name));
            // Slug se genera automáticamente en el modelo (único)
            $material->composition = $request->filled('composition')
                ? mb_strtoupper(trim($request->composition))
                : null;
            $material->description = $request->filled('description')
                ? trim($request->description)
                : null;
            $material->activo = true;
            $material->save();

            DB::commit();

            Log::info('Material creado with properties', [
                'material_id' => $material->id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('success', 'Material creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear material: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->validated(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.materials.create')
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit', 'consumptionUnit'])
                ->findOrFail((int) $id);

            $categories = MaterialCategory::where('activo', true)
                ->ordered()
                ->get();

            $baseUnits = $material->category->allowedUnits()->ordered()->get();

            return view('admin.materials.edit', compact('material', 'categories', 'baseUnits'));
        } catch (\Exception $e) {
            Log::error('Error al cargar material para editar: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            DB::beginTransaction();

            $material = Material::where('activo', true)->findOrFail((int) $id);

            $oldName = $material->name;

            $material->material_category_id = (int) $request->material_category_id;
            $material->base_unit_id = (int) $request->base_unit_id;
            // $material->consumption_unit_id = null; // Mantener valor anterior o resetear? Resetear si eliminamos el campo UI.
            $material->consumption_unit_id = null;
            $material->conversion_factor = 1.0;
            $material->has_color = $request->boolean('has_color');
            $material->name = mb_strtoupper(trim($request->name));
            // Slug se genera automáticamente en el modelo si el nombre cambia
            $material->composition = $request->filled('composition')
                ? mb_strtoupper(trim($request->composition))
                : null;
            $material->description = $request->filled('description')
                ? trim($request->description)
                : null;

            if (!$material->isDirty()) {
                return redirect()->route('admin.materials.index')
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

            return redirect()->route('admin.materials.index')
                ->with('success', 'Material actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar material: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.materials.edit', $id)
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            $material = Material::where('activo', true)
                ->with(['category', 'baseUnit'])
                ->withCount('activeVariants')
                ->findOrFail((int) $id);

            return view('admin.materials.delete', compact('material'));
        } catch (\Exception $e) {
            Log::error('Error al cargar material para eliminar: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
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
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Material no válido');
            }

            DB::beginTransaction();

            $material = Material::where('activo', true)->findOrFail((int) $id);

            $validation = $material->canDelete();
            if (!$validation['can_delete']) {
                return redirect()->route('admin.materials.index')
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

            return redirect()->route('admin.materials.index')
                ->with('success', 'Material eliminado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar material: ' . $e->getMessage(), [
                'material_id' => $id,
                'user_id' => Auth::id(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.materials.index')
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

    /*
    |--------------------------------------------------------------------------
    | CREATE WIZARD (UX V2)
    |--------------------------------------------------------------------------
    */

    public function createWizard()
    {
        try {
            $categories = MaterialCategory::where('activo', true)
                ->with(['defaultInventoryUnit', 'allowedUnits'])
                ->ordered()
                ->get();

            if ($categories->isEmpty()) {
                return redirect()->route('admin.materials.index')
                    ->with('error', 'Debe crear al menos una categoría de material primero');
            }

            // Cargar unidades de inventario (canonical)
            $inventoryUnits = Unit::active()->canonical()->ordered()->get();

            return view('admin.materials.create-wizard', compact('categories', 'inventoryUnits'));
        } catch (\Exception $e) {
            Log::error('Error al cargar wizard de material: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('admin.materials.index')
                ->with('error', 'Error al cargar el formulario');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE WIZARD (UX V2 - Guardado Transaccional)
    |--------------------------------------------------------------------------
    */

    public function storeWizard(Request $request)
    {
        // Validación
        $request->validate([
            'material_category_id' => ['required', 'integer', 'exists:material_categories,id'],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                \Illuminate\Validation\Rule::unique('materials', 'name')->where(function ($query) {
                    return $query->where('activo', true);
                })
            ],
            'composition' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'has_color' => ['nullable', 'boolean'],
            'consumption_unit_id' => ['required', 'integer', 'exists:units,id'],
            'conversions' => ['nullable', 'array'],
            'conversions.*.from_unit_id' => ['required_with:conversions', 'integer', 'exists:units,id'],
            'conversions.*.conversion_factor' => ['required_with:conversions', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'conversions.*.intermediate_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'conversions.*.intermediate_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'conversions.*.label' => ['nullable', 'string', 'max:50'],
            'skip_conversions' => ['nullable', 'boolean'],
        ], [
            'material_category_id.required' => 'Debe seleccionar una categoría',
            'name.required' => 'El nombre es obligatorio',
            'name.unique' => 'Ya existe un material con este nombre',
            'consumption_unit_id.required' => 'Debe seleccionar una unidad de inventario',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear el material
            $material = new Material();
            $material->uuid = (string) Str::uuid();
            $material->material_category_id = (int) $request->material_category_id;

            // FIXED: La unidad base SIEMPRE debe ser la unidad de inventario seleccionada explícitamente.
            $material->base_unit_id = (int) $request->consumption_unit_id;

            $material->consumption_unit_id = (int) $request->consumption_unit_id;
            $material->conversion_factor = 1.0;
            $material->has_color = $request->boolean('has_color');
            $material->name = mb_strtoupper(trim($request->name));

            // Robust Slug Generation
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $counter = 1;

            while (Material::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $material->slug = $slug;

            $material->composition = $request->filled('composition')
                ? mb_strtoupper(trim($request->composition))
                : null;
            $material->description = $request->filled('description')
                ? trim($request->description)
                : null;
            $material->activo = true;
            $material->save();

            // 2. Crear las conversiones (si no se omitieron)
            $conversionsCreated = 0;
            if (!$request->boolean('skip_conversions') && $request->has('conversions')) {
                foreach ($request->conversions as $conversionData) {
                    // Validar que la unidad origen no sea la de inventario
                    if ($conversionData['from_unit_id'] == $request->consumption_unit_id) {
                        continue;
                    }

                    $conversion = new MaterialUnitConversion();
                    $conversion->material_id = $material->id;
                    $conversion->from_unit_id = (int) $conversionData['from_unit_id'];
                    $conversion->to_unit_id = (int) $request->consumption_unit_id;
                    $conversion->conversion_factor = (float) $conversionData['conversion_factor'];

                    // Nuevos campos para el desglose inteligente
                    if (!empty($conversionData['intermediate_unit_id'])) {
                        $conversion->intermediate_unit_id = (int) $conversionData['intermediate_unit_id'];
                        $conversion->intermediate_qty = (float) ($conversionData['intermediate_qty'] ?? 0);
                    }

                    if (!empty($conversionData['label'])) {
                        $conversion->label = trim($conversionData['label']);
                    }

                    $conversion->save();

                    $conversionsCreated++;
                }
            }

            DB::commit();

            Log::info('Material creado via Wizard', [
                'material_id' => $material->id,
                'name' => $material->name,
                'consumption_unit_id' => $material->consumption_unit_id,
                'conversions_created' => $conversionsCreated,
                'user_id' => Auth::id(),
            ]);

            $message = 'Material creado exitosamente';
            if ($conversionsCreated > 0) {
                $message .= " con {$conversionsCreated} presentación(es) de compra";
            }

            return redirect()->route('admin.materials.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear material via Wizard: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.materials.create-wizard')
                ->withInput()
                ->with('error', 'Error al crear el material: ' . $e->getMessage());
        }
    }
}
