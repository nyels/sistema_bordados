<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;

/**
 * CIERRE CANÓNICO: Request para cancelación de pedidos.
 *
 * REGLAS INVIOLABLES:
 * - cancel_reason es OBLIGATORIO (mínimo 5 caracteres)
 * - El pedido debe estar en estado cancelable
 * - NO se puede cancelar un pedido DELIVERED
 */
class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Debe especificar el motivo de cancelación.',
            'cancel_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'cancel_reason.max' => 'El motivo no puede exceder 255 caracteres.',
        ];
    }

    /**
     * Validación adicional: verificar que el pedido puede cancelarse.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Order $order */
            $order = $this->route('order');

            if (!$order) {
                $validator->errors()->add('order', 'Pedido no encontrado.');
                return;
            }

            if (!$order->canCancel()) {
                $validator->errors()->add(
                    'order',
                    $order->getCancelBlockReason() ?? 'El pedido no puede cancelarse.'
                );
            }
        });
    }

    /**
     * Sanitizar el motivo antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('cancel_reason')) {
            $reason = $this->input('cancel_reason');
            $reason = trim(strip_tags($reason));
            $reason = preg_replace('/\s+/', ' ', $reason);
            $this->merge(['cancel_reason' => $reason]);
        }
    }
}
