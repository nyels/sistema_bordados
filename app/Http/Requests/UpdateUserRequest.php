<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    // === REGEX WHITELIST USER ===
    // Letras A-Z, números, espacios, guión, guión bajo, punto
    private const REGEX_ACCOUNT_NAME = '/^[A-Za-z0-9\s\.\-\_]+$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:' . self::REGEX_ACCOUNT_NAME,
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email,' . $userId,
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
            'staff_id' => [
                'nullable',
                'integer',
                'exists:staff,id',
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
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, números, espacios, puntos, guiones y guiones bajos.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato de email no es válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'staff_id.exists' => 'El personal seleccionado no existe.',
        ];
    }

    // === SANITIZACIÓN BACKEND ===
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeAccountName($this->input('name')),
            'email' => $this->sanitizeEmail($this->input('email')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    // === LIMPIEZA DE NOMBRE DE CUENTA ===
    private function sanitizeAccountName(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = mb_convert_encoding($value, 'ASCII', 'UTF-8');
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return $value === '' ? null : $value;
    }

    // === LIMPIEZA DE EMAIL ===
    private function sanitizeEmail(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return $value === '' ? null : $value;
    }
}
