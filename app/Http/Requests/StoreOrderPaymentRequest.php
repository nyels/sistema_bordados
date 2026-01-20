<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPaymentRequest extends FormRequest
{
    // === REGEX WHITELIST ===
    // Referencia: alfanumérico + guiones
    private const REGEX_REFERENCE = '/^[A-Za-z0-9\-\_]+$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:cash,transfer,card,other',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
                'regex:' . self::REGEX_REFERENCE,
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'payment_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto mínimo es $0.01.',
            'payment_method.required' => 'Seleccione un método de pago.',
            'payment_method.in' => 'Método de pago no válido.',
            'reference.regex' => 'La referencia solo puede contener letras, números y guiones.',
            'payment_date.before_or_equal' => 'La fecha de pago no puede ser futura.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reference')) {
            $value = $this->input('reference');
            if ($value !== null) {
                $value = strtoupper(trim($value));
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
                $this->merge(['reference' => $value === '' ? null : $value]);
            }
        }

        if ($this->has('notes')) {
            $value = $this->input('notes');
            if ($value !== null) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $value);
                $value = trim(preg_replace('/\s+/', ' ', $value));
                $this->merge(['notes' => $value === '' ? null : $value]);
            }
        }
    }
}
