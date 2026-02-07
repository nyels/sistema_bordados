<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest para validación de ProductExtra.
 *
 * REGLAS DE NEGOCIO:
 * 1. Nombre único, solo caracteres seguros
 * 2. Costo y precio obligatorios, positivos
 * 3. Si consumes_inventory = true, debe tener al menos 1 material válido
 * 4. Materiales deben existir en material_variants
 * 5. Cantidades deben ser positivas
 */
class ProductExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $extraId = $this->route('id') ?? $this->route('product_extra');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\-\_\.\,]+$/',
                Rule::unique('product_extras', 'name')->ignore($extraId),
            ],
            'extra_category_id' => ['required', 'integer', 'exists:extra_categories,id'],
            'cost_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_addition' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minutes_addition' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'consumes_inventory' => ['nullable', 'boolean'],
        ];

        // Validación condicional de materiales
        if ($this->boolean('consumes_inventory')) {
            $rules['materials'] = ['required', 'array', 'min:1'];
            $rules['materials.*.variant_id'] = ['required', 'integer', 'exists:material_variants,id'];
            $rules['materials.*.quantity'] = ['required', 'numeric', 'min:0.0001', 'max:999999.9999'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del extra es obligatorio.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'name.unique' => 'Ya existe un extra con este nombre.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'extra_category_id.required' => 'La categoría es obligatoria.',
            'extra_category_id.exists' => 'La categoría seleccionada no existe.',
            'cost_addition.required' => 'El costo adicional es obligatorio.',
            'cost_addition.numeric' => 'El costo debe ser un número válido.',
            'cost_addition.min' => 'El costo no puede ser negativo.',
            'price_addition.required' => 'El precio adicional es obligatorio.',
            'price_addition.numeric' => 'El precio debe ser un número válido.',
            'price_addition.min' => 'El precio no puede ser negativo.',
            'minutes_addition.integer' => 'El tiempo debe ser un número entero.',
            'minutes_addition.min' => 'El tiempo no puede ser negativo.',
            'materials.required' => 'Debe agregar al menos un material si el extra consume inventario.',
            'materials.min' => 'Debe agregar al menos un material.',
            'materials.*.variant_id.required' => 'Debe seleccionar un material.',
            'materials.*.variant_id.exists' => 'El material seleccionado no existe.',
            'materials.*.quantity.required' => 'La cantidad es obligatoria.',
            'materials.*.quantity.numeric' => 'La cantidad debe ser un número válido.',
            'materials.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }

    /**
     * Prepara los datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        // Convertir checkbox a booleano
        $this->merge([
            'consumes_inventory' => $this->has('consumes_inventory') && $this->input('consumes_inventory') == '1',
        ]);

        // Filtrar materiales vacíos
        if ($this->has('materials')) {
            $materials = collect($this->input('materials'))
                ->filter(fn($m) => !empty($m['variant_id']) && !empty($m['quantity']))
                ->values()
                ->toArray();

            $this->merge(['materials' => $materials]);
        }
    }

    /**
     * Obtiene los materiales validados en formato para syncMaterials().
     *
     * @return array [variant_id => quantity]
     */
    public function getMaterialsForSync(): array
    {
        if (!$this->boolean('consumes_inventory')) {
            return [];
        }

        $materials = [];
        foreach ($this->input('materials', []) as $material) {
            if (!empty($material['variant_id']) && !empty($material['quantity'])) {
                $materials[(int) $material['variant_id']] = (float) $material['quantity'];
            }
        }

        return $materials;
    }
}
