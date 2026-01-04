<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Clase para la validación de variantes de materiales.
 * Mantiene la lógica original con estándares de Laravel 12.
 */
class MaterialVariantRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del negocio.
     */
    public function rules(): array
    {
        $variantId = $this->route('id');
        // Mantenemos tu lógica de obtención de materialId
        $materialId = $this->route('materialId') ?? $this->input('material_id');

        return [
            'material_id' => [
                'required',
                'integer',
                'min:1',
                'exists:materials,id',
            ],
            'color' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\#]+$/u',
            ],
            'sku' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('material_variants', 'sku')->ignore($variantId),
            ],
            'min_stock_alert' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.9999',
            ],
            'current_stock' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999999999.9999',
            ],
            'average_cost' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999999999.9999',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados (Mantenidos íntegramente).
     */
    public function messages(): array
    {
        return [
            'material_id.required'    => 'El material es obligatorio.',
            'material_id.exists'      => 'El material seleccionado no es válido.',
            'color.regex'             => 'El color contiene caracteres no permitidos.',
            'color.max'               => 'El color no puede exceder 50 caracteres.',
            'sku.required'            => 'El SKU es obligatorio.',
            'sku.regex'               => 'El SKU solo puede contener letras mayúsculas, números y guiones.',
            'sku.unique'              => 'Este SKU ya está registrado.',
            'sku.min'                 => 'El SKU debe tener al menos 3 caracteres.',
            'sku.max'                 => 'El SKU no puede exceder 30 caracteres.',
            'min_stock_alert.required' => 'El stock mínimo es obligatorio.',
            'min_stock_alert.numeric'  => 'El stock mínimo debe ser un número.',
            'min_stock_alert.min'      => 'El stock mínimo no puede ser negativo.',
            'current_stock.numeric'    => 'El stock actual debe ser un número.',
            'current_stock.min'        => 'El stock actual no puede ser negativo.',
            'average_cost.numeric'     => 'El costo promedio debe ser un número.',
            'average_cost.min'         => 'El costo promedio no puede ser negativo.',
        ];
    }

    /**
     * Preparación de datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        // Normalización de SKU
        if ($this->filled('sku')) {
            $this->merge([
                'sku' => mb_strtoupper(trim(preg_replace('/[^A-Za-z0-9\-]/', '', (string) $this->input('sku')))),
            ]);
        }

        // Normalización de Color
        if ($this->filled('color')) {
            $this->merge([
                'color' => trim((string) $this->input('color')),
            ]);
        }

        // Casting de Stock mínimo
        if ($this->has('min_stock_alert')) {
            $this->merge([
                'min_stock_alert' => is_numeric($this->input('min_stock_alert'))
                    ? (float) $this->input('min_stock_alert')
                    : $this->input('min_stock_alert'),
            ]);
        }
    }
}
