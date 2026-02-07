<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Services\ProductionCapacityService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    private const REGEX_TEXT_SAFE = '/^[A-Za-z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\_\(\)\!\?]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // === ESTADO DEL PEDIDO ===
            'status' => [
                'sometimes',
                'string',
                'in:draft,confirmed,in_production,ready,delivered,cancelled',
            ],

            // === DATOS EDITABLES ===
            'promised_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            // === ITEMS (si se permite editar) ===
            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'items.*.id' => [
                'nullable',
                'integer',
                'exists:order_items,id',
            ],
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                'exists:products,id',
            ],
            'items.*.product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
            'items.*.quantity' => [
                'required_with:items',
                'integer',
                'min:1',
                'max:999',
            ],
            'items.*.unit_price' => [
                'required_with:items',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'items.*.embroidery_text' => [
                'nullable',
                'string',
                'max:255',
                'regex:' . self::REGEX_TEXT_SAFE,
            ],
            'items.*.customization_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items.*.status' => [
                'sometimes',
                'string',
                'in:pending,in_progress,completed,cancelled',
            ],
        ];
    }

    /**
     * PASO 10: Validación post-reglas para promised_date.
     * GATE ERP: Rechaza reprogramación si el estado no lo permite.
     * CAPACIDAD: Valida contra ProductionCapacityService si cambia de semana.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePromisedDateReschedule($validator);
        });
    }

    /**
     * Valida que promised_date solo se modifique en estados permitidos
     * y que la semana destino tenga capacidad disponible.
     */
    private function validatePromisedDateReschedule($validator): void
    {
        if (!$this->has('promised_date') || $this->input('promised_date') === null) {
            return;
        }

        /** @var Order|null $order */
        $order = $this->route('order');
        if (!$order) {
            return;
        }

        // === GATE DE ESTADO ===
        if (!$order->canReschedulePromisedDate()) {
            $validator->errors()->add(
                'promised_date',
                $order->getRescheduleBlockReason() ?? 'No se puede modificar la fecha en este estado.'
            );
            return;
        }

        // === VALIDACIÓN DE CAPACIDAD (solo si el pedido ya ocupa capacidad) ===
        if ($order->status === Order::STATUS_CONFIRMED) {
            $capacityService = app(ProductionCapacityService::class);
            $newDate = \Carbon\Carbon::parse($this->input('promised_date'));

            $validation = $capacityService->validateDateChange($order, $newDate);

            if (!$validation['valid']) {
                $suggestion = $capacityService->suggestPromisedDate(1);
                $suggestedLabel = $suggestion['suggested_date'] ?? 'N/D';

                $validator->errors()->add(
                    'promised_date',
                    $validation['error'] . " Siguiente semana disponible: {$suggestedLabel}."
                );
            }
        }
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Estado de pedido no válido.',
            'promised_date.after_or_equal' => 'La fecha prometida no puede ser una fecha pasada.',
            'items.min' => 'El pedido debe tener al menos un producto.',
            'items.*.embroidery_text.regex' => 'El texto a bordar contiene caracteres no permitidos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge(['notes' => $this->sanitizeText($this->input('notes'))]);
        }

        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as $key => $item) {
                if (isset($item['embroidery_text'])) {
                    $items[$key]['embroidery_text'] = $this->sanitizeText($item['embroidery_text']);
                }
                if (isset($item['customization_notes'])) {
                    $items[$key]['customization_notes'] = $this->sanitizeText($item['customization_notes']);
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    private function sanitizeText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $value);
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return $value === '' ? null : $value;
    }
}
