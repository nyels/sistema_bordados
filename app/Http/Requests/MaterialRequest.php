<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtener ID del material para edición (puede venir como 'id' o 'material')
        $materialId = $this->route('id') ?? $this->route('material');

        // Si es un modelo, extraer el ID
        if ($materialId instanceof \App\Models\Material) {
            $materialId = $materialId->id;
        }

        return [
            'material_category_id' => [
                'required',
                'integer',
                'min:1',
                'exists:material_categories,id',
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\:%\:\(\)\/\.\,]+$/u',
                // Validación de nombre único por categoría (solo entre materiales activos)
                function (string $attribute, mixed $value, \Closure $fail) use ($materialId) {
                    $categoryId = $this->input('material_category_id');

                    // Query para buscar duplicados SOLO entre materiales activos
                    $query = \App\Models\Material::where('name', $value)
                        ->where('material_category_id', $categoryId)
                        ->where('activo', true);  // Solo materiales activos

                    // Si estamos editando, excluir el material actual
                    if ($materialId) {
                        $query->where('id', '!=', (int) $materialId);
                    }

                    if ($query->exists()) {
                        $fail('Ya existe un material activo con este nombre en la categoría.');
                    }
                },
            ],
            'composition' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\,\.\%\:\;]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\_\(\)\%\:\;]+$/u',
            ],
            'base_unit_id' => [
                'required',
                'integer',
                'exists:units,id',
                // Validación personalizada
                function (string $attribute, mixed $value, \Closure $fail) {
                    $unit = \App\Models\Unit::find($value);
                    if (!$unit) {
                        $fail("La unidad seleccionada no existe.");
                        return;
                    }

                    if (!$unit->activo) {
                        $fail("La unidad seleccionada no está activa.");
                        return;
                    }

                    // Para EDICIÓN: Permitir la unidad actual del material
                    $materialId = $this->route('id');
                    if ($materialId) {
                        $material = \App\Models\Material::find($materialId);
                        if ($material && $material->base_unit_id == $value) {
                            // Es la misma unidad, permitir sin más validaciones
                            return;
                        }
                    }

                    // Para CREACIÓN o cambio de unidad: Verificar que sea válida para la categoría
                    $categoryId = $this->input('material_category_id');
                    $category = \App\Models\MaterialCategory::find($categoryId);

                    if ($category) {
                        // Permitir si es la unidad de inventario por defecto de la categoría
                        if ($category->default_inventory_unit_id == $value) {
                            return;
                        }

                        // O si está en las unidades permitidas de la categoría
                        $isAllowed = $category->allowedUnits()->where('units.id', $value)->exists();
                        if ($isAllowed) {
                            return;
                        }

                        $fail("La unidad seleccionada no está permitida para esta categoría.");
                    }
                },
            ],
            'has_color' => [
                'sometimes',
                'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'material_category_id.required' => 'La categoría es obligatoria.',
            'material_category_id.exists' => 'La categoría seleccionada no es válida.',
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe un material con este nombre en la categoría.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'composition.regex' => 'La composición contiene caracteres no permitidos.',
            'composition.max' => 'La composición no puede exceder 100 caracteres.',
            'description.regex' => 'La descripción contiene caracteres no permitidos.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'base_unit_id.required' => 'La unidad base (compra) es obligatoria.',
            'base_unit_id.exists' => 'La unidad seleccionada no está permitida para esta categoría.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('name')) {
            $mergeData['name'] = trim($this->input('name'));
        }

        if ($this->has('composition')) {
            $mergeData['composition'] = trim($this->input('composition'));
        }

        // Manejar checkbox has_color: si no está presente, es false
        $mergeData['has_color'] = $this->boolean('has_color');

        $this->merge($mergeData);
    }
}
