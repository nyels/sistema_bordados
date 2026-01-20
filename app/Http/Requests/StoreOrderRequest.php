<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cliente;
use App\Models\ClientMeasurement;
use Illuminate\Support\Carbon;

class StoreOrderRequest extends FormRequest
{
    // === REGEX WHITELIST ===
    // Texto humano: letras, acentos, números, espacios, puntuación básica
    private const REGEX_TEXT_SAFE = '/^[A-Za-z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\_\(\)\!\?\'\"\#\@\&\:\;\/]+$/u';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ================================================
            // === A) PEDIDO (ROOT) ===
            // ================================================

            // CLIENTE (OBLIGATORIO)
            'cliente_id' => [
                'required',
                'integer',
                'min:1',
                'exists:clientes,id',
            ],

            // MEDIDAS (OPCIONAL)
            'client_measurement_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:client_measurements,id',
            ],

            // URGENCIA (OBLIGATORIO)
            'urgency_level' => [
                'required',
                'string',
                'in:normal,urgente,express',
            ],

            // FACTURA/IVA
            'requires_invoice' => [
                'nullable',
                'boolean',
            ],

            // FECHA PROMETIDA (OBLIGATORIO)
            'promised_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            // NOTAS GENERALES
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            // DESCUENTO
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            // ================================================
            // === B) ITEMS DEL PEDIDO (OBLIGATORIO) ===
            // ================================================
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                'min:1',
                'exists:products,id',
            ],
            'items.*.product_variant_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:product_variants,id',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'items.*.embroidery_text' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.customization_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // ================================================
            // === C) PAGO (OPCIONAL) ===
            // ================================================
            'payment_method' => [
                'required',
                'string',
                'in:cash,transfer,card,other',
            ],
            'pay_full' => [
                'nullable',
                'boolean',
            ],
            'initial_payment' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    // ================================================
    // === VALIDACIONES DE NEGOCIO (SERVER-SIDE) ===
    // ================================================
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Solo validar si no hay errores previos en campos básicos
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateClienteActivo($validator);
            $this->validateProductsActivos($validator);
            $this->validateVariantsBelongToProducts($validator);
            $this->validateMeasurementBelongsToClient($validator);
            $this->validatePromisedDateVsLeadTime($validator);
            $this->validatePaymentAmount($validator);
        });
    }

    // === 1. VALIDAR QUE CLIENTE ESTÉ ACTIVO ===
    protected function validateClienteActivo(Validator $validator): void
    {
        $clienteId = $this->input('cliente_id');
        if (!$clienteId) return;

        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            $validator->errors()->add('cliente_id', 'El cliente seleccionado no existe.');
            return;
        }

        if (!$cliente->activo) {
            $validator->errors()->add('cliente_id', 'El cliente seleccionado está inactivo.');
        }
    }

    // === 2. VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN ACTIVOS ===
    protected function validateProductsActivos(Validator $validator): void
    {
        $items = $this->input('items', []);
        if (empty($items)) return;

        $productIds = array_filter(array_column($items, 'product_id'));
        if (empty($productIds)) return;

        $activeProducts = Product::whereIn('id', $productIds)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            if ($productId && !in_array($productId, $activeProducts)) {
                $validator->errors()->add(
                    "items.{$index}.product_id",
                    'El producto seleccionado no está disponible o fue descontinuado.'
                );
            }
        }
    }

    // === 3. VALIDAR QUE VARIANTES PERTENEZCAN AL PRODUCTO ===
    protected function validateVariantsBelongToProducts(Validator $validator): void
    {
        $items = $this->input('items', []);
        if (empty($items)) return;

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $variantId = $item['product_variant_id'] ?? null;

            // Si hay variante, validar que pertenezca al producto
            if ($productId && $variantId) {
                $variant = ProductVariant::find($variantId);

                if (!$variant) {
                    $validator->errors()->add(
                        "items.{$index}.product_variant_id",
                        'La variante seleccionada no existe.'
                    );
                    continue;
                }

                if ((int) $variant->product_id !== (int) $productId) {
                    $validator->errors()->add(
                        "items.{$index}.product_variant_id",
                        'La variante seleccionada no corresponde al producto.'
                    );
                }
            }
        }
    }

    // === 4. VALIDAR QUE MEDIDAS PERTENEZCAN AL CLIENTE ===
    protected function validateMeasurementBelongsToClient(Validator $validator): void
    {
        $clienteId = $this->input('cliente_id');
        $measurementId = $this->input('client_measurement_id');

        if (!$clienteId || !$measurementId) return;

        $measurement = ClientMeasurement::find($measurementId);
        if (!$measurement) {
            $validator->errors()->add('client_measurement_id', 'Las medidas seleccionadas no existen.');
            return;
        }

        if ((int) $measurement->cliente_id !== (int) $clienteId) {
            $validator->errors()->add('client_measurement_id', 'Las medidas seleccionadas no corresponden al cliente.');
        }
    }

    // === 5. VALIDAR FECHA PROMETIDA VS LEAD TIME (CRÍTICO) ===
    protected function validatePromisedDateVsLeadTime(Validator $validator): void
    {
        $items = $this->input('items', []);
        $promisedDate = $this->input('promised_date');

        if (empty($items) || empty($promisedDate)) return;

        // Obtener máximo lead time de los productos
        $productIds = array_filter(array_column($items, 'product_id'));
        if (empty($productIds)) return;

        $maxLeadTime = Product::whereIn('id', $productIds)
            ->where('status', 'active')
            ->max('production_lead_time') ?? 0;

        // Calcular fecha mínima según urgencia
        $urgency = $this->input('urgency_level', 'normal');
        $multiplier = Order::URGENCY_MULTIPLIERS[$urgency] ?? 1.0;
        $adjustedDays = (int) ceil($maxLeadTime * $multiplier);
        $minimumDate = now()->addDays($adjustedDays)->startOfDay();

        // Validar que la fecha prometida sea >= fecha mínima
        $promised = Carbon::parse($promisedDate)->startOfDay();

        if ($promised->lt($minimumDate)) {
            $validator->errors()->add(
                'promised_date',
                "La fecha de entrega debe ser a partir del {$minimumDate->format('d/m/Y')} según el tiempo de producción."
            );
        }
    }

    // === 6. VALIDAR MONTO DE PAGO ===
    protected function validatePaymentAmount(Validator $validator): void
    {
        $items = $this->input('items', []);
        $discount = (float) ($this->input('discount') ?? 0);
        $requiresInvoice = (bool) $this->input('requires_invoice', false);
        $payFull = (bool) $this->input('pay_full', false);
        $initialPayment = (float) ($this->input('initial_payment') ?? 0);

        if (empty($items)) return;

        // Calcular subtotal
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $subtotal += $qty * $price;
        }

        // Calcular IVA si aplica
        $subtotalAfterDiscount = max(0, $subtotal - $discount);
        $iva = $requiresInvoice ? $subtotalAfterDiscount * Order::IVA_RATE : 0;
        $total = $subtotalAfterDiscount + $iva;

        // Validar que descuento no exceda subtotal
        if ($discount > $subtotal) {
            $validator->errors()->add('discount', 'El descuento no puede ser mayor al subtotal.');
        }

        // Validar que pago inicial no exceda total
        if ($initialPayment > $total && !$payFull) {
            $validator->errors()->add('initial_payment', 'El anticipo no puede ser mayor al total del pedido.');
        }
    }

    // ================================================
    // === MENSAJES DE ERROR EN ESPAÑOL ===
    // ================================================
    public function messages(): array
    {
        return [
            // Cliente
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.integer' => 'El cliente seleccionado no es válido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',

            // Medidas
            'client_measurement_id.exists' => 'Las medidas seleccionadas no existen.',

            // Urgencia
            'urgency_level.required' => 'Debe seleccionar el nivel de urgencia.',
            'urgency_level.in' => 'El nivel de urgencia no es válido.',

            // Fecha prometida
            'promised_date.required' => 'Debe indicar la fecha de entrega prometida.',
            'promised_date.date' => 'La fecha de entrega no es válida.',
            'promised_date.after_or_equal' => 'La fecha de entrega debe ser hoy o posterior.',

            // Notas
            'notes.max' => 'Las notas no pueden exceder 2000 caracteres.',

            // Descuento
            'discount.numeric' => 'El descuento debe ser un número.',
            'discount.min' => 'El descuento no puede ser negativo.',
            'discount.max' => 'El descuento excede el límite permitido.',

            // Items
            'items.required' => 'Debe agregar al menos un producto.',
            'items.array' => 'Los productos no tienen formato válido.',
            'items.min' => 'Debe agregar al menos un producto.',
            'items.*.product_id.required' => 'Cada ítem debe tener un producto.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.product_variant_id.exists' => 'Una de las variantes seleccionadas no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad mínima es 1.',
            'items.*.quantity.max' => 'La cantidad máxima es 999.',
            'items.*.unit_price.required' => 'El precio unitario es obligatorio.',
            'items.*.unit_price.numeric' => 'El precio debe ser un número.',
            'items.*.unit_price.min' => 'El precio no puede ser negativo.',
            'items.*.embroidery_text.max' => 'El texto a bordar no puede exceder 255 caracteres.',
            'items.*.customization_notes.max' => 'Las notas de personalización no pueden exceder 1000 caracteres.',

            // Pago
            'payment_method.required' => 'Debe seleccionar un método de pago.',
            'payment_method.in' => 'El método de pago no es válido.',
            'initial_payment.numeric' => 'El anticipo debe ser un número.',
            'initial_payment.min' => 'El anticipo no puede ser negativo.',
            'payment_reference.max' => 'La referencia de pago no puede exceder 100 caracteres.',
        ];
    }

    // ================================================
    // === SANITIZACIÓN BACKEND (ANTES DE VALIDAR) ===
    // ================================================
    protected function prepareForValidation(): void
    {
        // Sanitizar notas generales
        if ($this->has('notes')) {
            $this->merge(['notes' => $this->sanitizeText($this->input('notes'))]);
        }

        // Convertir requires_invoice a boolean real
        $this->merge([
            'requires_invoice' => $this->boolean('requires_invoice'),
            'pay_full' => $this->boolean('pay_full'),
        ]);

        // Sanitizar items
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            $sanitizedItems = [];

            foreach ($items as $key => $item) {
                // Asegurar tipos correctos
                $sanitizedItem = [
                    'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                    'product_variant_id' => !empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
                    'unit_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : 0,
                    'embroidery_text' => isset($item['embroidery_text']) ? $this->sanitizeText($item['embroidery_text']) : null,
                    'customization_notes' => isset($item['customization_notes']) ? $this->sanitizeText($item['customization_notes']) : null,
                ];

                // Solo agregar items válidos (con product_id)
                if ($sanitizedItem['product_id']) {
                    $sanitizedItems[] = $sanitizedItem;
                }
            }

            $this->merge(['items' => $sanitizedItems]);
        }

        // Sanitizar referencia de pago
        if ($this->has('payment_reference')) {
            $this->merge(['payment_reference' => $this->sanitizeText($this->input('payment_reference'))]);
        }

        // Normalizar descuento y anticipo
        $this->merge([
            'discount' => $this->has('discount') ? max(0, (float) $this->input('discount')) : 0,
            'initial_payment' => $this->has('initial_payment') ? max(0, (float) $this->input('initial_payment')) : 0,
        ]);
    }

    // === LIMPIEZA DE TEXTO ===
    private function sanitizeText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Forzar UTF-8 válido
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        // Eliminar caracteres de control (excepto newlines para notas)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\xA0]/u', '', $value);

        // Eliminar zero-width characters
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);

        // Normalizar espacios múltiples
        $value = trim(preg_replace('/[ \t]+/', ' ', $value));

        // Normalizar saltos de línea múltiples
        $value = preg_replace('/\n{3,}/', "\n\n", $value);

        return $value === '' ? null : $value;
    }
}
