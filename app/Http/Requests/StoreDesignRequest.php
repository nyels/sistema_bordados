<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     * Si es una solicitud AJAX, responder con JSON en lugar de redirigir.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->ajax() || $this->wantsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422));
        }

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('designs', 'name')
                    ->where(fn($q) => $q->where('is_active', 1)->whereNull('deleted_at')),
                'regex:/^[A-Za-z0-9ñÑáéíóúÁÉÍÓÚ\s\-.,]+$/u',
            ],

            'categories' => [
                'required',
                'array',
                'min:1',
            ],
            'categories.*' => [
                'required',
                'exists:categories,id',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // ⭐ SOLO VALIDAR PRESENCIA Y TAMAÑO
            'image' => [
                'required',
                'file',
                'max:51200', // 50MB
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del diseño es obligatorio.',
            'name.unique' => 'Ya existe un diseño activo con este nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, espacios, guiones, puntos y comas.',
            'name.max' => 'El nombre no puede tener más de 150 caracteres.',

            'categories.required' => 'Debes seleccionar al menos una categoría.',
            'categories.min' => 'Debes seleccionar al menos una categoría.',
            'categories.*.exists' => 'Una o más categorías seleccionadas no existen.',

            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',

            'image.required' => 'Debes subir un archivo para el diseño.',
            'image.file' => 'El archivo subido no es válido.',
            'image.max' => 'El archivo no puede superar los 50 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('categories') && !is_array($this->categories)) {
            $this->merge(['categories' => (array) $this->categories]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim($this->name)]);
        }
    }
}
