<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductType;
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
            'items.*.extras_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.99',
            ],
            'items.*.is_customized' => [
                'nullable',
                'boolean',
            ],
            // Extras seleccionados (de BD)
            'items.*.extras' => [
                'nullable',
                'array',
            ],
            'items.*.extras.*.id' => [
                'nullable',
                'integer',
                'exists:product_extras,id',
            ],
            'items.*.extras.*.name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'items.*.extras.*.price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            // Medidas inline capturadas en el pedido (JSON)
            'items.*.measurements' => [
                'nullable',
                'array',
            ],
            'items.*.measurements.busto' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],
            'items.*.measurements.cintura' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],
            'items.*.measurements.cadera' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],
            'items.*.measurements.alto_cintura' => [
                'nullable',
                'numeric',
                'min:0',
                'max:200',
            ],
            'items.*.measurements.largo' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],
            'items.*.measurements.largo_vestido' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],

            // ================================================
            // === C) PAGO (OPCIONAL) ===
            // ================================================
            'payment_method' => [
                'nullable',
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
            $this->validateProductsHaveType($validator);
            $this->validateVariantsBelongToProducts($validator);
            $this->validateMeasurementBelongsToClient($validator);
            $this->validateMeasurementsPerProductType($validator);
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

    // === 2b. VALIDAR QUE TODOS LOS PRODUCTOS TENGAN TIPO ASIGNADO ===
    // CRÍTICO: Rechaza pedidos con productos mal configurados
    protected function validateProductsHaveType(Validator $validator): void
    {
        $items = $this->input('items', []);
        if (empty($items)) return;

        $productIds = array_filter(array_column($items, 'product_id'));
        if (empty($productIds)) return;

        // Obtener productos con sus tipos
        $products = Product::whereIn('id', $productIds)
            ->with('productType')
            ->get()
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            if (!$productId) continue;

            $product = $products->get($productId);
            if (!$product) continue;

            // REGLA DE NEGOCIO: Todo producto DEBE tener un tipo asignado
            if ($product->product_type_id === null) {
                $validator->errors()->add(
                    "items.{$index}.product_id",
                    "El producto \"{$product->name}\" no tiene tipo configurado. Contacte al administrador."
                );
            }

            // Validar que el tipo esté activo
            if ($product->productType && !$product->productType->active) {
                $validator->errors()->add(
                    "items.{$index}.product_id",
                    "El producto \"{$product->name}\" tiene un tipo deshabilitado. Contacte al administrador."
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

    // === 4b. VALIDAR MEDIDAS POR TIPO DE PRODUCTO ===
    // FASE 2: NEUTRALIZADO - NO BLOQUEA GUARDADO
    // Las medidas son OPCIONALES. Sin medidas → item.status = PENDING
    // La lógica de estados se maneja en OrderService::syncOrderItems()
    protected function validateMeasurementsPerProductType(Validator $validator): void
    {
        // NEUTRALIZADO: No bloquear guardado por falta de medidas
        // El pedido siempre se guarda, el estado del item refleja si tiene medidas o no
        return;
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
        $paymentMethod = $this->input('payment_method');

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

        // Validar que pago inicial no exceda total (solo si no es pago completo)
        if ($initialPayment > $total && !$payFull) {
            $validator->errors()->add('initial_payment', 'El anticipo no puede ser mayor al total del pedido.');
        }

        // Si pay_full está marcado, initial_payment debe ser 0 o igual al total
        if ($payFull && $initialPayment > 0 && abs($initialPayment - $total) > 0.01) {
            $validator->errors()->add('initial_payment', 'Al pagar el total, el anticipo debe quedar vacío o coincidir con el total.');
        }

        // Si hay anticipo o pago completo, debe especificar método de pago
        if (($initialPayment > 0 || $payFull) && empty($paymentMethod)) {
            $validator->errors()->add('payment_method', 'Debe seleccionar un método de pago para registrar el pago.');
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
            'items.*.extras_cost.numeric' => 'El costo de extras debe ser un número.',
            'items.*.extras_cost.min' => 'El costo de extras no puede ser negativo.',
            'items.*.extras.*.id.exists' => 'Uno de los extras seleccionados no existe.',

            // Pago
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
                    'extras_cost' => isset($item['extras_cost']) ? max(0, (float) $item['extras_cost']) : 0,
                    'is_customized' => !empty($item['is_customized']),
                ];

                // Sanitizar extras seleccionados (de BD)
                if (!empty($item['extras']) && is_array($item['extras'])) {
                    $sanitizedExtras = [];
                    foreach ($item['extras'] as $extra) {
                        if (!empty($extra['id'])) {
                            $sanitizedExtras[] = [
                                'id' => (int) $extra['id'],
                                'name' => isset($extra['name']) ? $this->sanitizeText($extra['name']) : '',
                                'price' => isset($extra['price']) ? (float) $extra['price'] : 0,
                            ];
                        }
                    }
                    $sanitizedItem['extras'] = $sanitizedExtras;
                }

                // Sanitizar medidas inline (JSON)
                if (!empty($item['measurements']) && is_array($item['measurements'])) {
                    $sanitizedMeasurements = [];
                    $measurementFields = ['busto', 'cintura', 'cadera', 'alto_cintura', 'largo', 'largo_vestido'];

                    foreach ($measurementFields as $field) {
                        if (isset($item['measurements'][$field]) && $item['measurements'][$field] !== '') {
                            $value = (float) $item['measurements'][$field];
                            if ($value > 0) {
                                $sanitizedMeasurements[$field] = $value;
                            }
                        }
                    }

                    // Solo asignar si hay al menos una medida válida
                    if (!empty($sanitizedMeasurements)) {
                        $sanitizedItem['measurements'] = $sanitizedMeasurements;
                    }
                }

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
