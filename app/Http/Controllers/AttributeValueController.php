<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     * Redirige al index principal de atributos
     */
    public function index()
    {
        return redirect()->route('admin.attributes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $attributes = Attribute::orderBy('name', 'asc')->get();
            return view('admin.attributes.values.create', compact('attributes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de valor de atributo: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')->with('error', 'Error al cargar el formulario');
        }
    }

    /**
     * Store a newly created resource in storage.
     * Validaciones empresariales con protección contra XSS y SQL injection
     */
    public function store(Request $request)
    {
        // Obtener el tipo de atributo para validación condicional
        $attribute = Attribute::find($request->attribute_id);
        $isColorType = $attribute && $attribute->slug === 'color';

        // Reglas de validación base
        $rules = [
            'attribute_id' => ['required', 'exists:attributes,id'],
            'value' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\-]+$/'
            ],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ];

        // Agregar validación de hex_color si el atributo es tipo color
        if ($isColorType) {
            $rules['hex_color'] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        } else {
            $rules['hex_color'] = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $validated = $request->validate($rules, [
            'attribute_id.required' => 'Debe seleccionar un atributo.',
            'attribute_id.exists' => 'El atributo seleccionado no existe.',
            'value.required' => 'El valor es obligatorio.',
            'value.regex' => 'El valor solo puede contener letras, números, espacios y guiones.',
            'value.max' => 'El valor no puede exceder 100 caracteres.',
            'hex_color.required' => 'El código de color es obligatorio para atributos tipo color.',
            'hex_color.regex' => 'El código de color debe tener formato hexadecimal válido (#RRGGBB).',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor o igual a 0.'
        ]);

        DB::beginTransaction();
        try {
            $attributeValue = new AttributeValue();
            $attributeValue->attribute_id = $request->attribute_id;
            $attributeValue->value = mb_strtoupper(trim(htmlspecialchars($request->value, ENT_QUOTES, 'UTF-8')));
            $attributeValue->hex_color = $isColorType ? strtoupper(trim($request->hex_color)) : null;
            $attributeValue->order = $request->order ?? 0;
            $attributeValue->save();

            DB::commit();

            Log::info('Valor de atributo creado exitosamente: ' . $attributeValue->id);
            return redirect()->route('admin.attributes.index')->with('success', 'Valor de atributo creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear valor de atributo: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return redirect()->route('admin.attributes.index')->with('error', 'Error al crear el valor de atributo. Por favor intente nuevamente.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AttributeValue $attributeValue)
    {
        return redirect()->route('admin.attributes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $attributeValue = AttributeValue::with('attribute')->findOrFail($id);
            $attributes = Attribute::orderBy('name', 'asc')->get();
            return view('admin.attributes.values.edit', compact('attributeValue', 'attributes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar valor de atributo para edición: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')->with('error', 'Valor de atributo no encontrado');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 1. Verificación previa de existencia para ahorrar recursos
        $attributeValue = AttributeValue::findOrFail($id);

        // 2. Obtener el Atributo PADRE desde la BD (Fuente de verdad única)
        // No confiamos en lo que el front diga que es el atributo
        $attribute = Attribute::findOrFail($request->attribute_id);
        $isColorType = ($attribute->slug === 'color');

        // 3. Definición dinámica de Reglas de Validación
        $rules = [
            'attribute_id' => ['required', 'exists:attributes,id'],
            'value' => [
                'required',
                'string',
                'max:100',
                // Regex empresarial: permite letras, números, espacios, guiones y tildes
                'regex:/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\-]+$/'
            ],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999']
        ];

        // Lógica de validación cruzada: El HEX es obligatorio SI Y SOLO SI el atributo es Color
        if ($isColorType) {
            $rules['hex_color'] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        } else {
            // Si no es color, nos aseguramos de que no envíen basura
            $rules['hex_color'] = ['nullable'];
        }

        // Mensajes personalizados para una UX profesional
        $messages = [
            'value.regex' => 'El formato del nombre no es válido.',
            'hex_color.required' => 'El código de color es obligatorio para este tipo de atributo.',
            'hex_color.regex' => 'El formato de color hexadecimal es inválido.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            // 4. Asignación de Datos con Sanitización Profesional
            $attributeValue->attribute_id = $request->attribute_id;

            // Trim y Capitalización limpia (sin codificar HTML en BD para permitir búsquedas)
            $attributeValue->value = (mb_strtoupper(trim($request->value), 'UTF-8'));

            /** * BLINDAJE DE INTEGRIDAD:
             * Aunque el usuario manipule el HTML y envíe un HEX en una Talla, 
             * aquí forzamos la nulidad basándonos en la BD del servidor.
             */
            $attributeValue->hex_color = $isColorType ? strtoupper(trim($request->hex_color)) : null;

            $attributeValue->order = $request->order ?? 0;

            // 5. Verificación de cambios (isDirty) antes de persistir
            if (!$attributeValue->isDirty()) {
                DB::rollBack(); // Cerramos transacción sin cambios
                return redirect()->route('admin.attributes.index')->with('info', 'No se detectaron cambios en el valor.');
            }

            $attributeValue->save();

            DB::commit();

            Log::info("Valor de atributo actualizado por usuario ID: " . Auth::id(), [
                'value_id' => $attributeValue->id,
                'new_data' => $attributeValue->getChanges()
            ]);

            return redirect()->route('admin.attributes.index')->with('success', 'Valor de atributo actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fallo crítico en actualización de AttributeValue ID {$id}: " . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Hubo un problema técnico. La operación fue abortada para proteger los datos.');
        }
    }

    /**
     * Show confirmation page before deletion.
     */
    public function confirm_delete($id)
    {
        try {
            $attributeValue = AttributeValue::with('attribute')->findOrFail($id);
            return view('admin.attributes.values.delete', compact('attributeValue'));
        } catch (\Exception $e) {
            Log::error('Error al cargar valor de atributo para eliminación: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')->with('error', 'Valor de atributo no encontrado');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $attributeValue = AttributeValue::findOrFail($id);
            $valueName = $attributeValue->value;

            $attributeValue->delete();

            DB::commit();

            Log::info('Valor de atributo eliminado exitosamente: ' . $valueName . ' (ID: ' . $id . ')');
            return redirect()->route('admin.attributes.index')->with('success', 'Valor de atributo eliminado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar valor de atributo: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return redirect()->route('admin.attributes.index')->with('error', 'Error al eliminar el valor de atributo. Por favor intente nuevamente.');
        }
    }

    /**
     * Get attribute type via AJAX for conditional color picker display
     * @param int $attributeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttributeType($attributeId)
    {
        try {
            $attribute = Attribute::findOrFail($attributeId);
            return response()->json([
                'success' => true,
                'type' => $attribute->type
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener tipo de atributo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Atributo no encontrado'
            ], 404);
        }
    }
}
