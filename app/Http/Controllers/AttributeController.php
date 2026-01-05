<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra atributos y valores en la misma vista con dos DataTables
     */
    public function index()
    {
        try {
            $attributes = Attribute::orderBy('order', 'asc')->get();
            $attributeValues = AttributeValue::with('attribute')->orderBy('order', 'asc')->get();

            return view('admin.attributes.index', compact('attributes', 'attributeValues'));
        } catch (\Exception $e) {
            Log::error('Error al cargar atributos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los atributos');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     * Validaciones empresariales con protección contra XSS y SQL injection
     */
    public function store(Request $request)
    {
        // Validación con regex para prevenir inyecciones
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:attributes,name'
            ],
            'type' => ['nullable', 'in:select,color,text'],
            'is_required' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ], [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe un atributo con este nombre.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'type.required' => 'El tipo de atributo es obligatorio.',
            'type.in' => 'El tipo de atributo no es válido.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.'
        ]);

        DB::beginTransaction();
        try {
            $attribute = new Attribute();
            $attribute->name = mb_strtoupper(trim(htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8')));
            $attribute->slug = Str::slug($request->name);
            $attribute->type = 'select';
            $attribute->is_required = $request->has('is_required') ? true : false;
            $attribute->order = $request->order ?? 0;
            $attribute->save();

            DB::commit();

            Log::info('Atributo creado exitosamente: ' . $attribute->id);
            return redirect()->route('admin.attributes.index')->with('success', 'Atributo creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear atributo: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return redirect()->route('admin.attributes.index')->with('error', 'Error al crear el atributo. Por favor intente nuevamente.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Attribute $attribute)
    {
        return redirect()->route('admin.attributes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $attribute = Attribute::findOrFail($id);
            return view('admin.attributes.edit', compact('attribute'));
        } catch (\Exception $e) {
            Log::error('Error al cargar atributo para edición: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')->with('error', 'Atributo no encontrado');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validación con regex para prevenir inyecciones
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/',
                'unique:attributes,name,' . $id
            ],
            'type' => ['in:select,color,text'],
            'is_required' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ], [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe un atributo con este nombre.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'type.required' => 'El tipo de atributo es obligatorio.',
            'type.in' => 'El tipo de atributo no es válido.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.'
        ]);

        DB::beginTransaction();
        try {
            $attribute = Attribute::findOrFail($id);

            $attribute->name = mb_strtoupper(trim(htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8')));
            $attribute->slug = Str::slug($request->name);
            $attribute->type = 'select';
            $attribute->is_required = $request->has('is_required') ? true : false;
            $attribute->order = $request->order ?? 0;

            if (!$attribute->isDirty()) {
                return redirect()->route('admin.attributes.index')->with('info', 'No se realizaron cambios');
            }

            $attribute->save();
            DB::commit();

            Log::info('Atributo actualizado exitosamente: ' . $attribute->id);
            return redirect()->route('admin.attributes.index')->with('success', 'Atributo actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar atributo: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return redirect()->route('admin.attributes.index')->with('error', 'Error al actualizar el atributo. Por favor intente nuevamente.');
        }
    }

    /**
     * Show confirmation page before deletion.
     */
    public function confirm_delete($id)
    {
        try {
            $attribute = Attribute::with('values')->findOrFail($id);
            return view('admin.attributes.delete', compact('attribute'));
        } catch (\Exception $e) {
            Log::error('Error al cargar atributo para eliminación: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')->with('error', 'Atributo no encontrado');
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina el atributo y sus valores en cascada (definido en la migración)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // 1. Obtener el atributo con sus valores cargados para optimizar
            $attribute = Attribute::with('values')->findOrFail($id);
            $attributeName = $attribute->name;

            // 2. BLINDAJE DE INTEGRIDAD EMPRESARIAL
            // Verificamos si alguno de los valores de este atributo está en uso 
            // en la tabla pivote de variantes (usando la relación que vimos antes)
            $valuesIds = $attribute->values->pluck('id');

            $isInUse = DB::table('design_variant_attributes')
                ->whereIn('attribute_value_id', $valuesIds)
                ->exists();

            if ($isInUse) {
                DB::rollBack();
                return redirect()->route('admin.attributes.index')
                    ->with('error', "No se puede eliminar '$attributeName' porque sus valores están asignados a variantes de diseño activas.");
            }

            /**
             * 3. CASCADA MANUAL PARA SOFT DELETES
             * Al usar Eloquent sobre la relación, Laravel marca cada hijo con deleted_at
             */
            if ($attribute->values()->exists()) {
                $attribute->values()->delete();
            }

            // 4. Soft Delete del padre
            $attribute->delete();

            DB::commit();

            // 5. Logging Detallado
            Log::info("Atributo y valores eliminados (Soft Delete) por usuario ID: " . Auth::id(), [
                'attribute_id' => $id,
                'name' => $attributeName,
                'affected_values_count' => $valuesIds->count()
            ]);

            return redirect()->route('admin.attributes.index')
                ->with('success', 'Atributo y sus valores asociados eliminados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fallo al eliminar Atributo ID {$id}: " . $e->getMessage());

            return redirect()->route('admin.attributes.index')
                ->with('error', 'Error técnico al intentar eliminar el atributo.');
        }
    }
}
