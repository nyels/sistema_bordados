<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    // === REGEX WHITELIST STAFF ===
    // Letras A-Z, acentos latinos, espacios, guión, punto
    private const REGEX_HUMAN_NAME = '/^[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
                'regex:' . self::REGEX_HUMAN_NAME,
            ],
            'position' => [
                'nullable',
                'string',
                'max:100',
                'regex:' . self::REGEX_HUMAN_NAME,
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder 150 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, espacios, puntos y guiones.',
            'position.max' => 'El puesto no puede exceder 100 caracteres.',
            'position.regex' => 'El puesto solo puede contener letras, espacios, puntos y guiones.',
        ];
    }

    // === SANITIZACIÓN BACKEND ===
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeHumanInput($this->input('name')),
            'position' => $this->sanitizeHumanInput($this->input('position')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    // === LIMPIEZA DE INPUT HUMANO ===
    private function sanitizeHumanInput(?string $value): ?string
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
