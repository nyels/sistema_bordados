<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $materialId = $this->route('id');

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
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.]+$/u',
                Rule::unique('materials', 'name')
                    ->where('material_category_id', $this->input('material_category_id'))
                    ->ignore($materialId),
            ],
            'composition' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\%\/]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\.\,\-\_\(\)]+$/u',
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
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }

        if ($this->has('composition')) {
            $this->merge([
                'composition' => trim($this->input('composition')),
            ]);
        }
    }
}
