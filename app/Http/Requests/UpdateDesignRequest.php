<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDesignRequest extends FormRequest
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
        $design = $this->route('design');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designs', 'name')
                    ->ignore($design->id)
                    ->whereNull('deleted_at'),
                'regex:/^[A-Za-z0-9ñÑáéíóúÁÉÍÓÚ\s\-]+$/u',
            ],
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ];

        // Solo validar imagen si se envía
        if ($this->hasFile('image')) {
            $rules['image'] = [
                'file',
                'max:51200',
                Rule::file()->types([
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'bmp',
                    'webp',
                    'avif',
                    'svg',
                    'svgz',
                    'pes',
                    'dst',
                    'exp',
                    'xxx',
                    'jef',
                    'vp3',
                    'hus',
                    'pec',
                    'phc',
                    'sew',
                    'shv',
                    'csd',
                    '10o',
                    'bro'
                ])
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del diseño es obligatorio.',
            'name.unique' => 'Ya existe un diseño con este nombre.',
            'name.regex' => 'El nombre solo puede contener letras, números, espacios y guiones.',

            'categories.required' => 'Selecciona al menos una categoría.',
            'categories.min' => 'Selecciona al menos una categoría.',

            'image.required' => 'La imagen del diseño es obligatoria.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'image.max' => 'La imagen no debe superar los 4 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'is_active' => 'estado',
            'categories' => 'categorías',
        ];
    }
}
