<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_category_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('product_categories', 'id')->where('is_active', true),
            ],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:200',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\/]+$/u',
            ],
            'sku' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Z0-9\-\_]+$/u',
                'unique:products,sku',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'status' => [
                'required',
                'string',
                'in:draft,active,discontinued',
            ],

            // Especificaciones (JSON dinámico)
            'specifications' => [
                'nullable',
                'array',
            ],
            'specifications.*.key' => [
                'required_with:specifications',
                'string',
                'max:100',
            ],
            'specifications.*.value' => [
                'required_with:specifications',
                'string',
                'max:500',
            ],

            // Diseños asignados
            'designs' => [
                'nullable',
                'array',
            ],
            'designs.*' => [
                'integer',
                'exists:designs,id',
            ],

            // Extras
            'extras' => [
                'nullable',
                'array',
            ],
            'extras.*' => [
                'integer',
                'exists:product_extras,id',
            ],

            // Variante inicial (opcional)
            'initial_variant' => [
                'nullable',
                'array',
            ],
            'initial_variant.price' => [
                'required_with:initial_variant',
                'numeric',
                'min:0',
                'max:9999999.99',
            ],
            'initial_variant.attributes' => [
                'nullable',
                'array',
            ],

            // Materiales (BOM) - Fase 3
            'materials' => [
                'nullable',
                'array',
            ],
            'materials.*.material_variant_id' => [
                'required_with:materials',
                'integer',
                'exists:material_variants,id',
            ],
            'materials.*.quantity' => [
                'required_with:materials',
                'numeric',
                'min:0.0001',
            ],
            'materials.*.is_primary' => [
                'nullable',
                'boolean',
            ],
            'materials.*.notes' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_category_id.required' => 'La categoría es obligatoria.',
            'product_category_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede exceder 200 caracteres.',
            'name.regex' => 'El nombre contiene caracteres no permitidos. No se aceptan caracteres especiales.',
            'sku.required' => 'El SKU es obligatorio.',
            'sku.regex' => 'El SKU solo puede contener letras mayúsculas, números, guiones y guiones bajos.',
            'sku.unique' => 'Este SKU ya está registrado.',
            'description.max' => 'La descripción no puede exceder 2000 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strip_tags(trim($this->input('name'))),
            ]);
        }

        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper(strip_tags(trim($this->input('sku')))),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => strip_tags(trim($this->input('description'))),
            ]);
        }
    }
}
