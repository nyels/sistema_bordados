<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u',
                Rule::unique('material_categories', 'name')->ignore($categoryId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\_\(\)]+$/u',
            ],
            'base_unit_id' => [
                'required',
                'integer',
                'min:1',
                'exists:units,id',
            ],
            'has_color' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'name.max' => 'El nombre no puede exceder 50 caracteres.',
            'description.regex' => 'La descripción contiene caracteres no permitidos.',
            'base_unit_id.required' => 'La unidad base es obligatoria.',
            'base_unit_id.exists' => 'La unidad seleccionada no es válida.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_color' => $this->boolean('has_color'),
        ]);
    }
}
