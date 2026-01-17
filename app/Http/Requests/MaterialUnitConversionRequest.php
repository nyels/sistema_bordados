<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para la gestión de conversiones de unidades de materiales.
 * Implementa normalización de factores numéricos y reglas de integridad referencial.
 */
class MaterialUnitConversionRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permisos para esta acción.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        // Soporte para múltiples convenciones de nombres de ruta (Resource vs Custom)
        $conversionId = $this->route('id') ?? $this->route('material_unit_conversion');
        $materialId = $this->route('materialId') ?? $this->input('material_id');

        return [
            'material_id' => [
                'required',
                'integer',
                'min:1',
                'exists:materials,id',
            ],
            'from_unit_id' => [
                'required',
                'integer',
                'min:1',
                'exists:units,id',
                // Validación de integridad: No duplicar la misma unidad base para el mismo material
                Rule::unique('material_unit_conversions', 'from_unit_id')
                    ->where('material_id', $materialId)
                    ->ignore($conversionId),
                // REGLA ELIMINADA: Ya no se exige que coincida con base_unit_id antiguo
                // REGLA ELIMINADA: Ya no se exige solo logístico estricto
            ],
            'to_unit_id' => [
                'required',
                'integer',
                'min:1',
                'exists:units,id',
                'different:from_unit_id',
                // REGLA ELIMINADA: Se validará en controlador que sea compatible
                // function (string $attribute, mixed $value, \Closure $fail) { ... }
            ],
            'conversion_factor' => [
                'required',
                'numeric',
                'min:0.0001',
                'max:999999999.9999',
            ],
        ];
    }

    /**
     * Mensajes de error (Mantenidos y optimizados).
     */
    public function messages(): array
    {
        return [
            'material_id.required'         => 'El material es obligatorio.',
            'material_id.exists'           => 'El material seleccionado no es válido.',
            'from_unit_id.required'        => 'La unidad de compra es obligatoria.',
            'from_unit_id.exists'          => 'La unidad de compra seleccionada no es válida.',
            'from_unit_id.unique'          => 'Ya existe una conversión configurada para esta unidad en este material.',
            'to_unit_id.required'          => 'La unidad destino es obligatoria.',
            'to_unit_id.exists'            => 'La unidad destino seleccionada no es válida.',
            'to_unit_id.different'         => 'La unidad destino debe ser diferente a la unidad de compra.',
            'conversion_factor.required'   => 'El factor de conversión es obligatorio.',
            'conversion_factor.numeric'    => 'El factor de conversión debe ser un valor numérico.',
            'conversion_factor.min'        => 'El factor de conversión debe ser mayor a 0.',
            'conversion_factor.max'        => 'El factor de conversión excede la capacidad del sistema.',
        ];
    }

    /**
     * Normalización de datos previa a la validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('conversion_factor')) {
            $value = $this->input('conversion_factor');

            // Solo procesamos si es un string o número, evitando errores con arrays u otros tipos
            if (is_scalar($value)) {
                // Normalización de formato decimal europeo/latino a estándar SQL (.)
                $normalizedValue = str_replace(',', '.', (string) $value);

                $this->merge([
                    'conversion_factor' => is_numeric($normalizedValue)
                        ? (float) $normalizedValue
                        : $value,
                ]);
            }
        }
    }
}
