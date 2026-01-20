<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientMeasurementRequest extends FormRequest
{
    // === REGEX WHITELIST ===
    // Etiqueta: letras, números, espacios
    private const REGEX_LABEL = '/^[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s\-]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],

            // === MEDIDAS (en cm, decimal) ===
            'busto' => [
                'nullable',
                'numeric',
                'min:30',
                'max:200',
            ],
            'cintura' => [
                'nullable',
                'numeric',
                'min:30',
                'max:200',
            ],
            'cadera' => [
                'nullable',
                'numeric',
                'min:30',
                'max:200',
            ],
            'alto_cintura' => [
                'nullable',
                'numeric',
                'min:10',
                'max:100',
            ],
            'largo' => [
                'nullable',
                'numeric',
                'min:30',
                'max:200',
            ],
            'largo_vestido' => [
                'nullable',
                'numeric',
                'min:30',
                'max:200',
            ],
            'hombro' => [
                'nullable',
                'numeric',
                'min:10',
                'max:100',
            ],
            'espalda' => [
                'nullable',
                'numeric',
                'min:20',
                'max:100',
            ],
            'largo_manga' => [
                'nullable',
                'numeric',
                'min:20',
                'max:100',
            ],

            // === METADATA ===
            'label' => [
                'nullable',
                'string',
                'max:50',
                'regex:' . self::REGEX_LABEL,
            ],
            'is_primary' => [
                'nullable',
                'boolean',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente no existe.',
            // Busto: 30-200 cm
            'busto.min' => 'Busto: debe ser mínimo 30 cm.',
            'busto.max' => 'Busto: debe ser máximo 200 cm.',
            'busto.numeric' => 'Busto: debe ser un número válido.',
            // Cintura: 30-200 cm
            'cintura.min' => 'Cintura: debe ser mínimo 30 cm.',
            'cintura.max' => 'Cintura: debe ser máximo 200 cm.',
            'cintura.numeric' => 'Cintura: debe ser un número válido.',
            // Cadera: 30-200 cm
            'cadera.min' => 'Cadera: debe ser mínimo 30 cm.',
            'cadera.max' => 'Cadera: debe ser máximo 200 cm.',
            'cadera.numeric' => 'Cadera: debe ser un número válido.',
            // Alto Cintura: 10-100 cm
            'alto_cintura.min' => 'Alto Cintura: debe ser mínimo 10 cm.',
            'alto_cintura.max' => 'Alto Cintura: debe ser máximo 100 cm.',
            'alto_cintura.numeric' => 'Alto Cintura: debe ser un número válido.',
            // Largo Blusa: 30-200 cm
            'largo.min' => 'Largo Blusa: debe ser mínimo 30 cm.',
            'largo.max' => 'Largo Blusa: debe ser máximo 200 cm.',
            'largo.numeric' => 'Largo Blusa: debe ser un número válido.',
            // Largo Vestido: 30-200 cm
            'largo_vestido.min' => 'Largo Vestido: debe ser mínimo 30 cm.',
            'largo_vestido.max' => 'Largo Vestido: debe ser máximo 200 cm.',
            'largo_vestido.numeric' => 'Largo Vestido: debe ser un número válido.',
            // Hombro: 10-100 cm
            'hombro.min' => 'Hombro: debe ser mínimo 10 cm.',
            'hombro.max' => 'Hombro: debe ser máximo 100 cm.',
            'hombro.numeric' => 'Hombro: debe ser un número válido.',
            // Espalda: 20-100 cm
            'espalda.min' => 'Espalda: debe ser mínimo 20 cm.',
            'espalda.max' => 'Espalda: debe ser máximo 100 cm.',
            'espalda.numeric' => 'Espalda: debe ser un número válido.',
            // Largo Manga: 20-100 cm
            'largo_manga.min' => 'Largo Manga: debe ser mínimo 20 cm.',
            'largo_manga.max' => 'Largo Manga: debe ser máximo 100 cm.',
            'largo_manga.numeric' => 'Largo Manga: debe ser un número válido.',
            // Label
            'label.regex' => 'La etiqueta contiene caracteres no permitidos.',
            'label.max' => 'La etiqueta no puede exceder 50 caracteres.',
            // Notes
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalizar label
        if ($this->has('label')) {
            $value = $this->input('label');
            if ($value !== null) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
                $value = trim(preg_replace('/\s+/', ' ', $value));
                $this->merge(['label' => $value === '' ? null : $value]);
            }
        }

        // Normalizar notes
        if ($this->has('notes')) {
            $value = $this->input('notes');
            if ($value !== null) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $value);
                $value = trim(preg_replace('/\s+/', ' ', $value));
                $this->merge(['notes' => $value === '' ? null : $value]);
            }
        }

        // Convertir checkbox
        $this->merge(['is_primary' => $this->boolean('is_primary', false)]);
    }
}
