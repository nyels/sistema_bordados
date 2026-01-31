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
                Rule::unique('material_categories', 'name')
                    ->where('activo', true)
                    ->ignore($categoryId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\_\(\)\:]+$/u',
            ],
            // Nuevos campos UX V2
            'default_inventory_unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],
            'allow_unit_override' => [
                'nullable',
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
        ];
    }
}
