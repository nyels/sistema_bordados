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
                // REGLA DE ORO: from_unit_id DEBE ser igual a material.base_unit_id
                function (string $attribute, mixed $value, \Closure $fail) {
                    $material = \App\Models\Material::find($this->input('material_id'));
                    if ($material && $material->base_unit_id != $value) {
                        $fail('La unidad origen debe ser la unidad base (compra) del material.');
                    }
                },
                // Validación semántica: from_unit debe ser logística
                function (string $attribute, mixed $value, \Closure $fail) {
                    $unit = \App\Models\Unit::find($value);
                    if ($unit && !$unit->isLogistic()) {
                        $fail('La unidad origen debe ser de tipo logístico (compra).');
                    }
                },
            ],
            'to_unit_id' => [
                'required',
                'integer',
                'min:1',
                'exists:units,id',
                'different:from_unit_id',
                // REGLA DE ORO: to_unit_id DEBE ser una unidad canónica de consumo
                function (string $attribute, mixed $value, \Closure $fail) {
                    $unit = \App\Models\Unit::find($value);
                    if ($unit && !$unit->isCanonical()) {
                        $typeLabel = $unit->unit_type?->label() ?? 'desconocido';
                        $fail("La unidad destino debe ser canónica de consumo (metro, pieza, etc.). Tipo actual: {$typeLabel}.");
                    }
                },
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
