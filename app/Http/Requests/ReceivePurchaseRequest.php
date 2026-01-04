<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Purchase;

class ReceivePurchaseRequest extends FormRequest
{
    private const MAX_QUANTITY = 999999.9999;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receive_type' => [
                'required',
                'string',
                'in:complete,partial',
            ],
            'items' => [
                'required_if:receive_type,partial',
                'array',
            ],
            'items.*.item_id' => [
                'required_with:items',
                'integer',
                'min:1',
                'max:999999999',
            ],
            'items.*.quantity' => [
                'required_with:items',
                'numeric',
                'min:0',
                'max:' . self::MAX_QUANTITY,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receive_type.required' => 'Debe especificar el tipo de recepción.',
            'receive_type.in' => 'El tipo de recepción no es válido.',
            'items.required_if' => 'Debe especificar los items a recibir para recepción parcial.',
            'items.*.item_id.required_with' => 'El ID del item es obligatorio.',
            'items.*.quantity.required_with' => 'La cantidad a recibir es obligatoria.',
            'items.*.quantity.min' => 'La cantidad no puede ser negativa.',
            'items.*.quantity.max' => 'La cantidad excede el límite permitido.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && $this->input('notes') !== null) {
            $this->merge([
                'notes' => strip_tags(trim($this->input('notes'))),
            ]);
        }

        if ($this->has('items') && is_array($this->input('items'))) {
            $sanitizedItems = [];
            foreach ($this->input('items') as $index => $item) {
                $sanitizedItems[$index] = [
                    'item_id' => isset($item['item_id']) ? (int) $item['item_id'] : null,
                    'quantity' => isset($item['quantity']) ? (float) str_replace(',', '.', $item['quantity']) : 0,
                ];
            }
            $this->merge(['items' => $sanitizedItems]);
        }
    }

    /**
     * Validación adicional
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePurchaseCanReceive($validator);
            $this->validateQuantities($validator);
        });
    }

    /**
     * Validar que la compra puede recibirse
     */
    protected function validatePurchaseCanReceive($validator): void
    {
        $purchaseId = $this->route('id');

        if ($purchaseId) {
            $purchase = Purchase::find($purchaseId);

            if (!$purchase) {
                $validator->errors()->add('purchase', 'La compra no existe.');
                return;
            }

            if (!$purchase->can_receive) {
                $validator->errors()->add(
                    'purchase',
                    "No se puede recibir la compra en estado: {$purchase->status->label()}"
                );
            }
        }
    }

    /**
     * Validar cantidades no excedan pendiente
     */
    protected function validateQuantities($validator): void
    {
        if ($this->input('receive_type') !== 'partial') {
            return;
        }

        $purchaseId = $this->route('id');
        $items = $this->input('items', []);

        if (!$purchaseId || empty($items)) {
            return;
        }

        $purchase = Purchase::with('items')->find($purchaseId);

        if (!$purchase) {
            return;
        }

        foreach ($items as $index => $itemData) {
            if (empty($itemData['item_id']) || empty($itemData['quantity'])) {
                continue;
            }

            $purchaseItem = $purchase->items->firstWhere('id', $itemData['item_id']);

            if (!$purchaseItem) {
                $validator->errors()->add(
                    "items.{$index}.item_id",
                    'El item no pertenece a esta compra.'
                );
                continue;
            }

            if ($itemData['quantity'] > $purchaseItem->pending_quantity) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "La cantidad a recibir ({$itemData['quantity']}) excede lo pendiente ({$purchaseItem->pending_quantity})."
                );
            }
        }
    }
}
