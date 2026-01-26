<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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
            'product_type_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('product_types', 'id')->where('active', true),
            ],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:200',
                'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\_\.\/\(\)]+$/u',
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

            // Diseños asignados (legacy)
            'designs' => [
                'nullable',
                'array',
            ],
            'designs.*' => [
                'integer',
                'exists:designs,id',
            ],
            // Producciones de bordado (nuevo wizard)
            'designs_list' => [
                'nullable',
                'array',
            ],
            'designs_list.*.export_id' => [
                'required_with:designs_list',
                'integer',
                'exists:design_exports,id',
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
            // Listado de Variantes (Frontend Wizard)
            'variants' => [
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

            // Pricing & Financials
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'production_cost' => ['nullable', 'numeric', 'min:0'],
            'materials_cost' => ['nullable', 'numeric', 'min:0'],
            'embroidery_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'extra_services_cost' => ['nullable', 'numeric', 'min:0'],
            'suggested_price' => ['nullable', 'numeric', 'min:0'],
            'profit_margin' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'production_lead_time' => ['required', 'integer', 'min:1', 'max:365'],
            'primary_image' => ['nullable', 'image', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'product_category_id.required' => 'La categoría es obligatoria.',
            'product_category_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'product_type_id.required' => 'El tipo de producto es obligatorio.',
            'product_type_id.exists' => 'El tipo de producto seleccionado no existe o está inactivo.',
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
            'production_lead_time.required' => 'El tiempo de producción es obligatorio para el seguimiento.',
            'production_lead_time.min' => 'El tiempo de producción debe ser de al menos 1 día.',
            'production_lead_time.max' => 'El tiempo de producción no puede exceder los 365 días.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Illuminate\Support\Facades\Log::error('Validation Failed for Store Product', [
            'errors' => $validator->errors()->toArray(),
            'inputs' => $this->all()
        ]);
        parent::failedValidation($validator);
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

        // --- JSON DECODING FOR WIZARD INPUTS ---

        // 1. Materials (BOM)
        if ($this->has('materials_json')) {
            $bomData = json_decode($this->input('materials_json'), true);
            if (is_array($bomData)) {
                $formattedMaterials = array_map(function ($item) {
                    // Map Frontend keys to Backend Validation keys
                    return [
                        'material_variant_id' => $item['material_id'] ?? null,
                        'quantity' => $item['qty'] ?? 0,
                        'is_primary' => $item['is_primary'] ?? false,
                        'notes' => $item['notes'] ?? null,
                        'price' => $item['price'] ?? null, // Snapshot cost from frontend
                        // Enterprise fields
                        'scope' => $item['scope'] ?? 'global',
                        'targets' => $item['targets'] ?? [],
                    ];
                }, $bomData);
                $this->merge(['materials' => $formattedMaterials]);
            }
        }

        // 2. Extras
        if ($this->has('extras_json')) {
            $extrasData = json_decode($this->input('extras_json'), true);
            if (is_array($extrasData)) {
                // Backend expects simple array of IDs
                $this->merge(['extras' => array_column($extrasData, 'id')]);
            }
        }

        // 3. Embroideries (Producciones de bordado)
        // Frontend envía: export_id, app_type_slug, scope, target_variant
        if ($this->has('embroideries_json')) {
            $designsData = json_decode($this->input('embroideries_json'), true);
            if (is_array($designsData)) {
                // Nueva estructura para ProductService::syncDesignsList
                $designsList = array_map(function ($d) {
                    return [
                        'export_id' => $d['export_id'] ?? $d['id'] ?? null,
                        'app_type_slug' => $d['app_type_slug'] ?? null,
                        'scope' => $d['scope'] ?? 'global',
                        'target_variant' => $d['target_variant'] ?? null,
                    ];
                }, array_filter($designsData, fn($d) => !empty($d['export_id']) || !empty($d['id'])));

                $this->merge(['designs_list' => $designsList]);
            }
        }

        // 4. Variants (Wizard Step 2)
        if ($this->has('variants_json')) {
            $variantsData = json_decode($this->input('variants_json'), true);
            if (is_array($variantsData) && count($variantsData) > 0) {
                $this->merge(['variants' => $variantsData]);
            }
        }

        // 5. Financials (Optional - mainly for logging or custom pricing)
        if ($this->has('financials_json')) {
            $finData = json_decode($this->input('financials_json'), true);
            if (is_array($finData)) {
                if (isset($finData['price'])) {
                    $this->merge(['base_price' => $finData['price']]);
                }
                if (isset($finData['total_cost'])) {
                    $this->merge(['production_cost' => $finData['total_cost']]);
                }
                if (isset($finData['material_cost'])) {
                    $this->merge(['materials_cost' => $finData['material_cost']]);
                }
                if (isset($finData['embroidery_cost'])) {
                    $this->merge(['embroidery_cost' => $finData['embroidery_cost']]);
                }
                if (isset($finData['labor_cost'])) {
                    $this->merge(['labor_cost' => $finData['labor_cost']]);
                }
                if (isset($finData['extras_cost'])) {
                    $this->merge(['extra_services_cost' => $finData['extras_cost']]);
                }
                if (isset($finData['suggested_price'])) {
                    $this->merge(['suggested_price' => $finData['suggested_price']]);
                }
                if (isset($finData['margin'])) {
                    $this->merge(['profit_margin' => $finData['margin']]);
                }
                if (isset($finData['lead_time'])) {
                    $this->merge(['production_lead_time' => $finData['lead_time']]);
                }
                // Calculate suggested price if not explicitly provided (or just pass it if frontend does)
                // The frontend 'financials' object we saw in create.blade.php uses these names
            }
        }
    }
}
