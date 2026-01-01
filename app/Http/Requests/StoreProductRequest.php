<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // En el futuro, aquí validarás si el usuario tiene permiso
    }

    public function rules(): array
    {
        return [
            // Validación del Maestro
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'specifications' => 'nullable|array',
            'extra_ids' => 'nullable|array',
            'extra_ids.*' => 'exists:product_extras,id',

            // Validación de Variantes (Array)
            'variants' => 'required|array|min:1',
            'variants.*.sku_variant' => 'required|string|unique:product_variants,sku_variant',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_alert' => 'nullable|integer|min:0',

            // Validación de Atributos de la Variante
            'variants.*.attribute_values' => 'nullable|array',
            'variants.*.attribute_values.*.attribute_id' => 'required|exists:attributes,id',
            'variants.*.attribute_values.*.value_id' => 'required|exists:attribute_values,id',

            // Validación Técnica (Bordados/Exports)
            'variants.*.design_exports' => 'nullable|array',
            'variants.*.design_exports.*.design_export_id' => 'required|exists:design_exports,id',
            'variants.*.design_exports.*.application_type_id' => 'required|exists:application_types,id',
            'variants.*.design_exports.*.notes' => 'nullable|string|max:500',
        ];
    }
}
