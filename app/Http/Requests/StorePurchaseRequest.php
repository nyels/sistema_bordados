<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Unit;
use App\Models\MaterialVariant;
use App\Models\MaterialUnitConversion;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Máximo de items por compra
     */
    private const MAX_ITEMS = 100;

    /**
     * Límites numéricos
     */
    private const MAX_QUANTITY = 999999.9999;
    private const MAX_PRICE = 99999999.9999;
    private const MAX_TAX_RATE = 100;
    private const MAX_DISCOUNT = 99999999.99;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Cabecera
            'proveedor_id' => [
                'required',
                'integer',
                'min:1',
                'max:999999999',
                Rule::exists('proveedors', 'id')->where('activo', true),
            ],
            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . self::MAX_TAX_RATE,
            ],
            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . self::MAX_DISCOUNT,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\#\/]+$/u',
            ],
            'ordered_at' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'expected_at' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            // Items
            'items' => [
                'required',
                'array',
                'min:1',
                'max:' . self::MAX_ITEMS,
            ],
            'items.*.material_variant_id' => [
                'required',
                'integer',
                'min:1',
                'max:999999999',
                Rule::exists('material_variants', 'id')->where('activo', true),
            ],
            'items.*.unit_id' => [
                'required',
                'integer',
                'min:1',
                'max:999999999',
                Rule::exists('units', 'id')->where('activo', true),
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
                'max:' . self::MAX_QUANTITY,
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0.0001',
                'max:' . self::MAX_PRICE,
            ],
            'items.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // Cabecera
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe o está inactivo.',
            'tax_rate.max' => 'El porcentaje de impuesto no puede exceder 100%.',
            'discount_amount.max' => 'El descuento excede el límite permitido.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'reference.regex' => 'La referencia contiene caracteres no permitidos.',
            'reference.max' => 'La referencia no puede exceder 100 caracteres.',
            'ordered_at.before_or_equal' => 'La fecha de orden no puede ser futura.',
            'expected_at.after_or_equal' => 'La fecha esperada debe ser hoy o posterior.',

            // Items
            'items.required' => 'Debe agregar al menos un item a la compra.',
            'items.min' => 'Debe agregar al menos un item a la compra.',
            'items.max' => 'No puede agregar más de ' . self::MAX_ITEMS . ' items.',
            'items.*.material_variant_id.required' => 'El material es obligatorio en todos los items.',
            'items.*.material_variant_id.exists' => 'Uno de los materiales seleccionados no existe o está inactivo.',
            'items.*.unit_id.required' => 'La unidad es obligatoria en todos los items.',
            'items.*.unit_id.exists' => 'Una de las unidades seleccionadas no existe o está inactiva.',
            'items.*.quantity.required' => 'La cantidad es obligatoria en todos los items.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.quantity.max' => 'La cantidad excede el límite permitido.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio en todos los items.',
            'items.*.unit_price.min' => 'El precio unitario debe ser mayor a 0.',
            'items.*.unit_price.max' => 'El precio unitario excede el límite permitido.',
            'items.*.notes.max' => 'Las notas del item no pueden exceder 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'tax_rate' => 'tasa de impuesto',
            'discount_amount' => 'descuento',
            'ordered_at' => 'fecha de orden',
            'expected_at' => 'fecha esperada',
            'items.*.material_variant_id' => 'material',
            'items.*.unit_id' => 'unidad',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_price' => 'precio unitario',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitizar notas
        if ($this->has('notes') && $this->input('notes') !== null) {
            $this->merge([
                'notes' => strip_tags(trim($this->input('notes'))),
            ]);
        }

        // Sanitizar referencia
        if ($this->has('reference') && $this->input('reference') !== null) {
            $this->merge([
                'reference' => strip_tags(trim($this->input('reference'))),
            ]);
        }

        // Sanitizar items
        if ($this->has('items') && is_array($this->input('items'))) {
            $sanitizedItems = [];
            foreach ($this->input('items') as $index => $item) {
                $sanitizedItems[$index] = [
                    'material_variant_id' => isset($item['material_variant_id']) ? (int) $item['material_variant_id'] : null,
                    'unit_id' => isset($item['unit_id']) ? (int) $item['unit_id'] : null,
                    'quantity' => isset($item['quantity']) ? (float) str_replace(',', '.', $item['quantity']) : null,
                    'unit_price' => isset($item['unit_price']) ? (float) str_replace(',', '.', $item['unit_price']) : null,
                    'notes' => isset($item['notes']) ? strip_tags(trim($item['notes'])) : null,
                ];
            }
            $this->merge(['items' => $sanitizedItems]);
        }

        // Sanitizar valores numéricos
        if ($this->has('tax_rate')) {
            $this->merge([
                'tax_rate' => (float) str_replace(',', '.', $this->input('tax_rate') ?? 0),
            ]);
        }

        if ($this->has('discount_amount')) {
            $this->merge([
                'discount_amount' => (float) str_replace(',', '.', $this->input('discount_amount') ?? 0),
            ]);
        }
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateNoDuplicateItems($validator);
            $this->validateUnitCompatibility($validator);
        });
    }

    /**
     * Validar que no hay items duplicados
     */
    protected function validateNoDuplicateItems($validator): void
    {
        $items = $this->input('items', []);
        $seen = [];

        foreach ($items as $index => $item) {
            $key = ($item['material_variant_id'] ?? '') . '-' . ($item['unit_id'] ?? '');

            if (in_array($key, $seen)) {
                $validator->errors()->add(
                    "items.{$index}.material_variant_id",
                    'Este material con esta unidad ya está agregado en otro item.'
                );
            }

            $seen[] = $key;
        }
    }

    /**
     * Validar compatibilidad de unidades
     */
    protected function validateUnitCompatibility($validator): void
    {
        $items = $this->input('items', []);

        foreach ($items as $index => $item) {
            if (empty($item['material_variant_id']) || empty($item['unit_id'])) {
                continue;
            }

            $variant = MaterialVariant::with(['material.category.baseUnit'])
                ->find($item['material_variant_id']);

            if (!$variant) {
                continue;
            }

            $baseUnitId = $variant->material->category->base_unit_id ?? null;
            $unitId = $item['unit_id'];

            // Si es la unidad base, es válido
            if ($unitId == $baseUnitId) {
                continue;
            }

            // Verificar si existe conversión o es unidad compatible
            $unit = Unit::find($unitId);
            if ($unit && $unit->compatible_base_unit_id != $baseUnitId) {
                $validator->errors()->add(
                    "items.{$index}.unit_id",
                    "La unidad '{$unit->name}' no es compatible con el material seleccionado."
                );
            }

            // Verificar si existe conversión configurada
            $hasConversion = MaterialUnitConversion::where('material_id', $variant->material_id)
                ->where('from_unit_id', $unitId)
                ->exists();

            $isBaseUnit = $unitId == $baseUnitId;

            if (!$hasConversion && !$isBaseUnit) {
                $validator->errors()->add(
                    "items.{$index}.unit_id",
                    "No existe conversión configurada para esta unidad en el material seleccionado."
                );
            }
        }
    }
}
