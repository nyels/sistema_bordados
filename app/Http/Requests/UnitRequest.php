<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unitId = $this->route('unit')?->id ?? $this->route('unit');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('units', 'name')->ignore($unitId)->whereNull('deleted_at'),
            ],
            'symbol' => [
                'required',
                'string',
                'min:1',
                'max:10',
            ],
            'is_base' => [
                'sometimes',
                'boolean',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:999',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'symbol' => 'símbolo',
            'is_base' => 'unidad base',
            'is_active' => 'estado activo',
            'sort_order' => 'orden',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la unidad es obligatorio.',
            'name.unique' => 'Ya existe una unidad con este nombre.',
            'name.min' => 'El nombre debe tener al menos :min caracteres.',
            'name.max' => 'El nombre no puede exceder :max caracteres.',
            'symbol.required' => 'El símbolo es obligatorio.',
            'symbol.max' => 'El símbolo no puede exceder :max caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_base' => $this->boolean('is_base'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
