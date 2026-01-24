<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderEvent;
use App\Models\Cliente;
use App\Models\Product;
use App\Models\ClientMeasurement;
use App\Models\DesignExport;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreOrderPaymentRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
        $this->middleware('auth');
    }

    // === LISTADO DE PEDIDOS ===
    public function index(Request $request)
    {
        // ========================================
        // QUERY CON FILTROS
        // ========================================
        $query = Order::with(['cliente', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('urgency')) {
            $query->where('urgency_level', $request->urgency);
        }
        if ($request->boolean('blocked')) {
            // Filtro de bloqueados: usar IDs pre-calculados con lógica canónica
            // para mantener coherencia con el KPI
            $blockedIds = Order::where('status', Order::STATUS_CONFIRMED)
                ->with(['items'])
                ->get()
                ->filter(function ($o) {
                    return !$o->canStartProduction() || $o->hasProductionInventoryBlock();
                })
                ->pluck('id');

            $query->whereIn('id', $blockedIds);
        }
        if ($request->boolean('delayed')) {
            $query->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
                ->whereDate('promised_date', '<', now());
        }

        $orders = $query->paginate(20);

        // ========================================
        // AJAX: Retornar solo la tabla
        // ========================================
        if ($request->ajax()) {
            return view('admin.orders._table', compact('orders'));
        }

        // ========================================
        // KPIs OPERATIVOS (solo para vista completa)
        // FUENTE CANÓNICA: canStartProduction() + hasProductionInventoryBlock()
        // ========================================

        // Cargar pedidos confirmados UNA VEZ y evaluar con métodos del modelo
        $confirmedOrders = Order::where('status', Order::STATUS_CONFIRMED)
            ->with(['items']) // Eager load para evitar N+1
            ->get();

        // Separar en "listos" y "bloqueados" usando la lógica canónica del modelo
        $paraProducir = 0;
        $bloqueados = 0;

        foreach ($confirmedOrders as $confirmedOrder) {
            // Un pedido está bloqueado si:
            // 1. canStartProduction() === false (reglas R2-R5)
            // 2. O hasProductionInventoryBlock() === true (intento previo fallido)
            $canStart = $confirmedOrder->canStartProduction();
            $hasInventoryBlock = $confirmedOrder->hasProductionInventoryBlock();

            if (!$canStart || $hasInventoryBlock) {
                $bloqueados++;
            } else {
                $paraProducir++;
            }
        }

        $kpis = [
            'borradores' => Order::where('status', Order::STATUS_DRAFT)->count(),
            'para_producir' => $paraProducir,
            'bloqueados' => $bloqueados,
            'en_produccion' => Order::where('status', Order::STATUS_IN_PRODUCTION)->count(),
            'para_entregar' => Order::where('status', Order::STATUS_READY)->count(),
            'retrasados' => Order::whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
                ->whereDate('promised_date', '<', now())
                ->count(),
        ];

        return view('admin.orders.index', compact('orders', 'kpis'));
    }

    // === FORMULARIO CREAR ===
    public function create(Request $request)
    {
        // Productos se cargan para el modal, clientes vía AJAX
        $products = Product::where('status', 'active')
            ->with(['variants', 'primaryImage'])
            ->orderBy('name')
            ->get();

        // POST-VENTA: Si viene con ?related_to=PED-XXXX, cargar pedido relacionado
        $relatedOrder = null;
        if ($request->has('related_to')) {
            $relatedOrder = Order::where('order_number', $request->get('related_to'))
                ->whereIn('status', [Order::STATUS_READY, Order::STATUS_DELIVERED])
                ->with('cliente')
                ->first();

            // Si el pedido no existe o no está en estado válido, ignorar
            if (!$relatedOrder) {
                return redirect()->route('admin.orders.create')
                    ->with('error', 'El pedido relacionado no existe o no está en estado válido para post-venta.');
            }
        }

        return view('admin.orders.create', compact('products', 'relatedOrder'));
    }

    // === GUARDAR NUEVO PEDIDO ===
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = $this->orderService->createOrder($validated);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Pedido {$order->order_number} creado exitosamente.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear pedido: ' . $e->getMessage())
                ->withInput();
        }
    }

    // === FASE 3: EDITAR PEDIDO (SOLO DRAFT) ===
    public function edit(Order $order)
    {
        // Bloquear si no está en draft
        if ($order->status !== Order::STATUS_DRAFT) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Solo se pueden editar pedidos en borrador.');
        }

        $order->load([
            'cliente',
            'items.product.primaryImage',
            'items.product.productType',
            'items.product.extras',
            'items.variant.attributeValues',
        ]);

        $products = Product::where('status', 'active')
            ->with(['variants', 'primaryImage', 'productType', 'extras'])
            ->orderBy('name')
            ->get();

        // Preparar items para JS
        $orderItems = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_sku' => $item->variant_sku,
                'variant_display' => $item->variant?->attributes_display ?? $item->variant_sku,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'embroidery_text' => $item->embroidery_text,
                'customization_notes' => $item->customization_notes,
                'requires_measurements' => $item->requires_measurements,
                'measurements' => $item->measurements,
                'status' => $item->status,
                'image_url' => $item->product?->primary_image_url,
                'product_type_name' => $item->product?->productType?->display_name,
                'lead_time' => $item->product?->production_lead_time ?? 0,
                'extras' => $item->product?->extras->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'price_addition' => (float) $e->price_addition,
                ]) ?? [],
            ];
        });

        return view('admin.orders.create', [
            'products' => $products,
            'order' => $order,
            'orderItems' => $orderItems,
            'isEdit' => true,
        ]);
    }

    // === FASE 3: ACTUALIZAR PEDIDO (SOLO DRAFT) ===
    public function update(StoreOrderRequest $request, Order $order)
    {
        // Bloquear si no está en draft
        if ($order->status !== Order::STATUS_DRAFT) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Solo se pueden editar pedidos en borrador.');
        }

        $validated = $request->validated();

        try {
            // Eliminar items existentes y recrear
            $order->items()->delete();

            // Actualizar datos del pedido
            $order->update([
                'cliente_id' => $validated['cliente_id'],
                'client_measurement_id' => $validated['client_measurement_id'] ?? null,
                'urgency_level' => $validated['urgency_level'],
                'promised_date' => $validated['promised_date'],
                'notes' => $validated['notes'] ?? null,
                'discount' => $validated['discount'] ?? 0,
                'requires_invoice' => $validated['requires_invoice'] ?? false,
                'updated_by' => Auth::id(),
            ]);

            // Recrear items (reutiliza lógica de syncOrderItems vía reflection o directamente)
            $this->orderService->syncOrderItemsPublic($order, $validated['items']);

            // Recalcular totales
            $order->recalculateTotals();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Pedido {$order->order_number} actualizado exitosamente.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar pedido: ' . $e->getMessage())
                ->withInput();
        }
    }

    // === VER DETALLE ===
    public function show(Order $order)
    {
        $order->load([
            'cliente',
            'measurement',
            'items.product.primaryImage',
            'items.product.productType',
            // FASE X-A: Cargar materiales del BOM para ajuste pre-producción
            'items.product.materials.material.category',
            'items.variant',
            'payments.receiver',
            'creator',
            'parentOrder',
            'annexOrders',
            'postSaleOrders',
            'relatedOrder',
        ]);

        // Determinar si puede agregar items anexos (confirmado o producción temprana)
        $canAddItems = $this->orderService->canAddItems($order);

        return view('admin.orders.show', compact('order', 'canAddItems'));
    }

    // === FORMULARIO CREAR ANEXO ===
    // REGLA R4 (v2): Anexos SOLO permitidos en CONFIRMED
    // PROHIBIDOS en IN_PRODUCTION, READY, DELIVERED, CANCELLED, DRAFT
    // Un anexo NUNCA existe si el pedido >= IN_PRODUCTION
    public function createAnnex(Order $order)
    {
        // Guard estricto: SOLO en CONFIRMED
        if ($order->status !== Order::STATUS_CONFIRMED) {
            $message = match($order->status) {
                Order::STATUS_DRAFT =>
                    'El pedido está en borrador. Confírmelo primero para crear anexos.',
                Order::STATUS_IN_PRODUCTION =>
                    'El pedido ya está en producción. No se permiten anexos una vez iniciada la producción.',
                Order::STATUS_READY =>
                    'El pedido ya está listo para entrega. No se permiten anexos.',
                Order::STATUS_DELIVERED =>
                    'El pedido ya fue entregado. Los anexos no son posibles.',
                Order::STATUS_CANCELLED =>
                    'El pedido está cancelado.',
                default => 'No se pueden crear anexos en este estado.'
            };

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $message);
        }

        // No permitir anexos de anexos
        if ($order->isAnnex()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'No se pueden crear anexos de un pedido anexo.');
        }

        $order->load('cliente');

        $products = Product::where('status', 'active')
            ->with(['variants', 'primaryImage'])
            ->orderBy('name')
            ->get();

        return view('admin.orders.create-annex', compact('order', 'products'));
    }

    // === GUARDAR ANEXO ===
    // REGLA R4 (v2): Guard autoritario - SOLO CONFIRMED
    // Un anexo NUNCA existe si el pedido >= IN_PRODUCTION
    public function storeAnnex(StoreOrderRequest $request, Order $order)
    {
        // Guard estricto: SOLO en CONFIRMED
        if ($order->status !== Order::STATUS_CONFIRMED) {
            abort(403, 'Solo se permiten anexos en pedidos confirmados. Una vez iniciada la producción, no se pueden crear anexos.');
        }

        // No permitir anexos de anexos
        if ($order->isAnnex()) {
            abort(403, 'No se pueden crear anexos de un pedido anexo.');
        }

        $validated = $request->validated();

        try {
            $annexOrder = $this->orderService->createAnnexOrder($order, $validated);

            return redirect()->route('admin.orders.show', $annexOrder)
                ->with('success', "Anexo {$annexOrder->order_number} creado exitosamente.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear anexo: ' . $e->getMessage())
                ->withInput();
        }
    }

    // === REGISTRAR PAGO ===
    public function storePayment(StoreOrderPaymentRequest $request, Order $order)
    {
        $validated = $request->validated();

        if ($validated['amount'] > $order->balance) {
            return redirect()->back()
                ->with('error', 'El monto excede el saldo pendiente.')
                ->withInput();
        }

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'payment_date' => $validated['payment_date'] ?? now(),
            'received_by' => Auth::id(),
        ]);

        // === EVENTO: PAGO RECIBIDO ===
        OrderEvent::logPayment($order->fresh(), $validated['amount'], $validated['payment_method']);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pago registrado exitosamente.');
    }

    // === ACTUALIZAR PAGO ===
    public function updatePayment(Request $request, OrderPayment $payment)
    {
        $order = $payment->order;

        // Bloquear si pedido está en estado final
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'No se pueden modificar pagos de pedidos finalizados o cancelados.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,card,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment->update($validated);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pago actualizado exitosamente.');
    }

    // === ELIMINAR PAGO ===
    public function destroyPayment(OrderPayment $payment)
    {
        $order = $payment->order;

        // Bloquear si pedido está en estado final
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'No se pueden eliminar pagos de pedidos finalizados o cancelados.');
        }

        $payment->delete();

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pago eliminado exitosamente.');
    }

    // === CAMBIAR ESTADO (CON TRIGGERS DE INVENTARIO) ===
    // AUDITORÍA: Transiciones controladas para preservar integridad de inventario
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:draft,confirmed,in_production,ready,delivered,cancelled',
        ]);

        $newStatus = $request->status;
        $currentStatus = $order->status;

        // ================================================================
        // VALIDACIÓN DE TRANSICIONES PERMITIDAS (INTEGRIDAD INVENTARIO)
        // ================================================================
        // Matriz de transiciones válidas (preserva integridad de reservas/stock)
        $allowedTransitions = [
            Order::STATUS_DRAFT => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
            Order::STATUS_CONFIRMED => [Order::STATUS_IN_PRODUCTION, Order::STATUS_CANCELLED],
            Order::STATUS_IN_PRODUCTION => [Order::STATUS_READY, Order::STATUS_CANCELLED],
            Order::STATUS_READY => [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
            Order::STATUS_DELIVERED => [], // Estado terminal - sin transiciones
            Order::STATUS_CANCELLED => [], // Estado terminal - sin transiciones
        ];

        // Validar que la transición es permitida
        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            Log::warning('ORDER_INVALID_TRANSITION', [
                'order_id' => $order->id,
                'from' => $currentStatus,
                'to' => $newStatus,
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()
                ->with('error', "Transición no permitida: {$currentStatus} → {$newStatus}. " .
                    "Esta restricción protege la integridad del inventario.");
        }

        // ================================================================
        // TRIGGER: Cancelación (usa OrderService::cancelOrder con rollback)
        // ================================================================
        if ($newStatus === Order::STATUS_CANCELLED) {
            try {
                $this->orderService->cancelOrder($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido cancelado. Reservas de inventario liberadas.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al cancelar: ' . $e->getMessage());
            }
        }

        // TRIGGER: Pasar a producción → RESERVA de inventario
        if ($newStatus === Order::STATUS_IN_PRODUCTION && $currentStatus === Order::STATUS_CONFIRMED) {
            try {
                $this->orderService->triggerProduction($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido enviado a producción. Materiales reservados.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al iniciar producción: ' . $e->getMessage());
            }
        }

        // TRIGGER: Marcar listo → CONSUMO de inventario
        if ($newStatus === Order::STATUS_READY && $currentStatus === Order::STATUS_IN_PRODUCTION) {
            try {
                $this->orderService->triggerReady($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Producción completada. Materiales consumidos del inventario.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al marcar listo: ' . $e->getMessage());
            }
        }

        // TRIGGER: Entregar pedido (solo cambio de estado, inventario ya consumido en READY)
        if ($newStatus === Order::STATUS_DELIVERED && $currentStatus === Order::STATUS_READY) {
            try {
                $this->orderService->triggerDelivery($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido entregado exitosamente.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al registrar entrega: ' . $e->getMessage());
            }
        }

        // Cambio de estado normal (sin triggers de inventario)
        $previousStatus = $order->status;
        $order->update([
            'status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        // === EVENTO: CAMBIO DE ESTADO ===
        if ($newStatus === Order::STATUS_CONFIRMED && $previousStatus === Order::STATUS_DRAFT) {
            OrderEvent::logConfirmed($order);
        } else {
            OrderEvent::log(
                $order,
                OrderEvent::TYPE_STATUS_CHANGED,
                "Estado cambiado de '{$previousStatus}' a '{$newStatus}'",
                ['previous_status' => $previousStatus, 'new_status' => $newStatus]
            );
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Estado actualizado.');
    }

    // === CANCELAR PEDIDO (CON LIBERACIÓN DE RESERVAS) ===
    public function cancel(Order $order)
    {
        try {
            $this->orderService->cancelOrder($order);
            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Pedido cancelado. Reservas de inventario liberadas.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al cancelar: ' . $e->getMessage());
        }
    }

    // === AJAX: BUSCAR CLIENTES (formato Select2) ===
    public function searchClientes(Request $request)
    {
        $term = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = Cliente::where('activo', true)
            ->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('telefono', 'like', "%{$term}%");
            });

        $total = $query->count();
        $clientes = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'nombre', 'apellidos', 'telefono']);

        // Formato Select2
        return response()->json([
            'results' => $clientes->map(fn($c) => [
                'id' => $c->id,
                'text' => "{$c->nombre} {$c->apellidos} - {$c->telefono}",
            ]),
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    // === AJAX: BUSCAR PRODUCTOS (formato Select2) ===
    public function searchProducts(Request $request)
    {
        $term = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 15;

        $query = Product::where('status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            })
            ->with(['variants.attributeValues', 'primaryImage', 'productType', 'extras']);

        $total = $query->count();
        $products = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'results' => $products->map(fn($p) => [
                'id' => $p->id,
                'text' => $p->name,
                'name' => $p->name,
                'sku' => $p->sku,
                'base_price' => $p->base_price,
                'lead_time' => $p->production_lead_time ?? 0,
                'image_url' => $p->primary_image_url,
                // Campos para lógica de medidas por tipo de producto
                'requires_measurements' => $p->productType?->requires_measurements ?? false,
                'product_type_name' => $p->productType?->display_name ?? null,
                // Extras del producto
                'extras' => $p->extras->map(fn($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'price_addition' => (float) $e->price_addition,
                ]),
                'variants' => $p->variants->map(function($v) use ($p) {
                    // Construir display de la variante
                    $display = $v->attributes_display;
                    if (empty($display)) {
                        $display = $v->sku_variant ?: "Variante #{$v->id}";
                    }

                    return [
                        'id' => $v->id,
                        'sku' => $v->sku_variant ?? '',
                        'price' => (float) ($v->price ?? $p->base_price),
                        'display' => $display,
                    ];
                }),
            ]),
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    // === AJAX: OBTENER PRODUCTO POR ID (para edición de items) ===
    public function getProduct(Product $product)
    {
        $product->load(['variants.attributeValues', 'primaryImage', 'productType', 'extras']);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'base_price' => $product->base_price,
            'lead_time' => $product->production_lead_time ?? 0,
            'image_url' => $product->primary_image_url,
            'requires_measurements' => $product->productType?->requires_measurements ?? false,
            'product_type_name' => $product->productType?->display_name ?? null,
            'extras' => $product->extras->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'price_addition' => (float) $e->price_addition,
            ]),
            'variants' => $product->variants->map(function($v) use ($product) {
                $display = $v->attributes_display;
                if (empty($display)) {
                    $display = $v->sku_variant ?: "Variante #{$v->id}";
                }

                return [
                    'id' => $v->id,
                    'sku' => $v->sku_variant ?? '',
                    'price' => (float) ($v->price ?? $product->base_price),
                    'display' => $display,
                ];
            }),
        ]);
    }

    // === AJAX: MEDIDAS DEL CLIENTE ===
    public function getClientMeasurements(Cliente $cliente)
    {
        // Buscar en tabla client_measurements (nuevo sistema)
        $measurements = ClientMeasurement::where('cliente_id', $cliente->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Si no hay registros, usar medidas legacy de tabla clientes
        if ($measurements->isEmpty() && $this->clientHasLegacyMeasures($cliente)) {
            return response()->json([
                [
                    'id' => 0,
                    'cliente_id' => $cliente->id,
                    'busto' => $cliente->busto,
                    'cintura' => $cliente->cintura,
                    'cadera' => $cliente->cadera,
                    'alto_cintura' => $cliente->alto_cintura,
                    'largo' => $cliente->largo,
                    'largo_vestido' => $cliente->largo_vestido,
                    'is_primary' => true,
                    'label' => 'Medidas registradas',
                    'notes' => null,
                ]
            ]);
        }

        return response()->json($measurements);
    }

    // Helper: verificar si cliente tiene medidas legacy
    private function clientHasLegacyMeasures(Cliente $cliente): bool
    {
        return $cliente->busto || $cliente->cintura || $cliente->cadera ||
               $cliente->alto_cintura || $cliente->largo || $cliente->largo_vestido;
    }

    // ========================================
    // FASE Y: HISTORIAL DE MEDIDAS DEL CLIENTE
    // ========================================

    /**
     * Obtener historial completo de medidas de un cliente.
     * Incluye medidas de todos los pedidos anteriores + capturas manuales.
     * Orden: más reciente primero.
     */
    public function getClientMeasurementHistory(Cliente $cliente)
    {
        $history = \App\Models\ClientMeasurementHistory::where('cliente_id', $cliente->id)
            ->with(['order:id,order_number', 'orderItem:id,product_name', 'product:id,name', 'creator:id,name'])
            ->orderBy('captured_at', 'desc')
            ->get();

        // Formatear para frontend
        $formatted = $history->map(function ($record) {
            $measurements = $record->measurements ?? [];
            $measurementCount = count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'));

            return [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'measurements' => $measurements,
                'measurement_count' => $measurementCount,
                'summary' => $record->summary,
                'source' => $record->source,
                'source_label' => $record->source_label,
                'order_number' => $record->order?->order_number,
                'product_name' => $record->orderItem?->product_name ?? $record->product?->name,
                'captured_at' => $record->captured_at?->format('d/m/Y H:i'),
                'captured_at_relative' => $record->captured_at?->diffForHumans(),
                'created_by_name' => $record->creator?->name ?? 'Sistema',
                'notes' => $record->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'count' => $formatted->count(),
        ]);
    }

    /**
     * Obtener una medida específica del historial por ID.
     */
    public function getMeasurementHistoryById(Cliente $cliente, $historyId)
    {
        $record = \App\Models\ClientMeasurementHistory::where('cliente_id', $cliente->id)
            ->where('id', $historyId)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de medidas no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'measurements' => $record->measurements,
                'summary' => $record->summary,
                'source' => $record->source,
                'source_label' => $record->source_label,
                'captured_at' => $record->captured_at?->format('d/m/Y H:i'),
                'notes' => $record->notes,
            ],
        ]);
    }

    /**
     * Crear nueva entrada de medidas en el historial (captura manual).
     * Se usa cuando el operador captura medidas FUERA del contexto de un pedido.
     */
    public function storeClientMeasurementHistory(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'measurements' => 'required|array',
            'measurements.busto' => 'nullable|numeric|min:0|max:300',
            'measurements.cintura' => 'nullable|numeric|min:0|max:300',
            'measurements.cadera' => 'nullable|numeric|min:0|max:300',
            'measurements.alto_cintura' => 'nullable|numeric|min:0|max:300',
            'measurements.largo' => 'nullable|numeric|min:0|max:300',
            'measurements.largo_vestido' => 'nullable|numeric|min:0|max:300',
            'notes' => 'nullable|string|max:500',
        ]);

        // Limpiar medidas vacías
        $cleanMeasurements = collect($validated['measurements'])
            ->filter(fn($value) => !empty($value) && $value !== '0' && $value !== 0)
            ->toArray();

        if (empty($cleanMeasurements)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe capturar al menos una medida.',
            ], 422);
        }

        $history = \App\Models\ClientMeasurementHistory::create([
            'cliente_id' => $cliente->id,
            'measurements' => $cleanMeasurements,
            'source' => 'manual',
            'notes' => $validated['notes'] ?? "Captura manual desde perfil del cliente",
            'created_by' => Auth::id(),
            'captured_at' => now(),
        ]);

        Log::channel('daily')->info('MEASUREMENT_HISTORY_CREATED', [
            'cliente_id' => $cliente->id,
            'history_id' => $history->id,
            'source' => 'manual',
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medidas registradas en el historial del cliente.',
            'data' => [
                'id' => $history->id,
                'uuid' => $history->uuid,
                'measurements' => $history->measurements,
                'summary' => $history->summary,
                'captured_at' => $history->captured_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    // === AJAX: GUARDAR MEDIDAS EN CLIENTE ===
    public function storeClientMeasurements(Request $request, Cliente $cliente)
    {
        // Actualizar medidas directamente en la tabla clientes
        $cliente->update([
            'busto' => $request->input('busto'),
            'cintura' => $request->input('cintura'),
            'cadera' => $request->input('cadera'),
            'alto_cintura' => $request->input('alto_cintura'),
            'largo' => $request->input('largo'),
            'largo_vestido' => $request->input('largo_vestido'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medidas guardadas en el perfil del cliente',
        ]);
    }

    // === AJAX: EXTRAS DE UN PRODUCTO ===
    public function getProductExtras(Product $product)
    {
        $extras = $product->extras()->get();

        return response()->json([
            'extras' => $extras->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'price_addition' => (float) $e->price_addition,
            ]),
        ]);
    }

    // === AJAX: VERIFICAR TIPO DE ANEXO PERMITIDO ===
    public function checkAnnexType(Order $order)
    {
        $annexType = $this->orderService->determineAnnexType($order);

        return response()->json([
            'annex_type' => $annexType,
            'can_add_items' => $annexType === 'item',
            'can_create_order' => $this->orderService->canCreateAnnexOrder($order),
            'message' => $annexType === 'item'
                ? 'Puede agregar productos a este pedido.'
                : 'Este pedido requiere crear un pedido anexo.',
        ]);
    }

    // === AJAX: AGREGAR ITEMS ANEXOS AL PEDIDO ===
    public function storeAnnexItems(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.embroidery_text' => 'nullable|string|max:255',
            'items.*.customization_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $items = $this->orderService->addAnnexItems($order, $request->items);

            return response()->json([
                'success' => true,
                'message' => count($items) . ' producto(s) agregado(s) al pedido.',
                'items_count' => count($items),
                'new_total' => $order->fresh()->total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // === AJAX: CREAR PEDIDO RAPIDO (MODAL) ===
    public function storeQuick(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = $this->orderService->createOrder($validated);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'redirect_url' => route('admin.orders.show', $order),
                'message' => "Pedido {$order->order_number} creado exitosamente.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 422);
        }
    }

    // ============================================================
    // VINCULAR DISEÑO TÉCNICO (DESIGN EXPORT) AL PEDIDO
    // Endpoint con seguridad de nivel bancario
    // ============================================================
    public function linkDesignExport(Request $request, Order $order)
    {
        // ========================================
        // 1. VALIDACIÓN DE ENTRADA ESTRICTA
        // ========================================
        $validated = $request->validate([
            'design_export_id' => [
                'required',
                'integer',
                'min:1',
                'exists:design_exports,id'
            ],
        ], [
            'design_export_id.required' => 'El ID del diseño es obligatorio.',
            'design_export_id.integer' => 'El ID del diseño debe ser un número entero.',
            'design_export_id.min' => 'El ID del diseño no es válido.',
            'design_export_id.exists' => 'El diseño especificado no existe en el sistema.',
        ]);

        $designExportId = (int) $validated['design_export_id'];
        $userId = Auth::id();
        $userIp = $request->ip();
        $userAgent = $request->userAgent();

        // ========================================
        // 2. LOG DE AUDITORÍA - INICIO DE OPERACIÓN
        // ========================================
        Log::channel('daily')->info('DESIGN_LINK_ATTEMPT', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'design_export_id' => $designExportId,
            'user_id' => $userId,
            'ip' => $userIp,
            'user_agent' => substr($userAgent, 0, 200),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            // ========================================
            // 3. VALIDACIONES DE NEGOCIO
            // ========================================

            // 3.1 Verificar que el pedido está en estado editable
            if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
                Log::channel('daily')->warning('DESIGN_LINK_BLOCKED_STATUS', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'success' => false,
                    'error_code' => 'ORDER_NOT_EDITABLE',
                    'message' => 'No se puede vincular diseño: el pedido está en estado "' .
                                 $order->status_label . '". Solo pedidos en Borrador o Confirmado pueden modificarse.',
                ], 422);
            }

            // 3.2 Obtener el DesignExport y verificar que existe
            $designExport = DesignExport::find($designExportId);

            if (!$designExport) {
                Log::channel('daily')->error('DESIGN_LINK_NOT_FOUND', [
                    'order_id' => $order->id,
                    'design_export_id' => $designExportId,
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'success' => false,
                    'error_code' => 'DESIGN_NOT_FOUND',
                    'message' => 'El diseño técnico especificado no fue encontrado.',
                ], 404);
            }

            // 3.3 Verificar que el DesignExport está APROBADO
            if ($designExport->status !== 'aprobado') {
                Log::channel('daily')->warning('DESIGN_LINK_NOT_APPROVED', [
                    'order_id' => $order->id,
                    'design_export_id' => $designExportId,
                    'design_status' => $designExport->status,
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'success' => false,
                    'error_code' => 'DESIGN_NOT_APPROVED',
                    'message' => 'Solo se pueden vincular diseños con estado "Aprobado". ' .
                                 'Estado actual: "' . ($designExport->translated_status ?? $designExport->status) . '".',
                ], 422);
            }

            // 3.4 Verificar que no se está reemplazando un diseño ya vinculado sin confirmación
            $previousDesignId = $order->design_export_id;
            $isReplacement = $previousDesignId !== null && $previousDesignId !== $designExportId;

            // ========================================
            // 4. TRANSACCIÓN ATÓMICA
            // ========================================
            DB::beginTransaction();

            try {
                // Actualizar el pedido
                $order->design_export_id = $designExportId;
                $order->updated_by = $userId;
                $order->save();

                // Registrar evento de auditoría en el pedido
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'design_linked',
                    'message' => $isReplacement
                        ? "Diseño técnico reemplazado: #{$previousDesignId} → #{$designExportId} ({$designExport->application_label})"
                        : "Diseño técnico vinculado: #{$designExportId} ({$designExport->application_label})",
                    'metadata' => [
                        'design_export_id' => $designExportId,
                        'design_name' => $designExport->application_label,
                        'design_id' => $designExport->design_id,
                        'previous_design_export_id' => $previousDesignId,
                        'stitches_count' => $designExport->stitches_count,
                        'dimensions' => $designExport->formatted_dimensions,
                        'file_format' => $designExport->file_format,
                    ],
                    'created_by' => $userId,
                ]);

                DB::commit();

                // ========================================
                // 5. LOG DE AUDITORÍA - OPERACIÓN EXITOSA
                // ========================================
                Log::channel('daily')->info('DESIGN_LINK_SUCCESS', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'design_export_id' => $designExportId,
                    'design_name' => $designExport->application_label,
                    'previous_design_id' => $previousDesignId,
                    'is_replacement' => $isReplacement,
                    'user_id' => $userId,
                    'ip' => $userIp,
                    'timestamp' => now()->toIso8601String(),
                ]);

                // ========================================
                // 6. RESPUESTA EXITOSA
                // ========================================
                return response()->json([
                    'success' => true,
                    'message' => $isReplacement
                        ? 'Diseño técnico reemplazado exitosamente.'
                        : 'Diseño técnico vinculado exitosamente.',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'design_export_id' => $designExportId,
                        'design_name' => $designExport->application_label ?? $designExport->design->name ?? 'Diseño #' . $designExportId,
                        'design_details' => [
                            'stitches' => $designExport->stitches_count,
                            'stitches_formatted' => number_format($designExport->stitches_count ?? 0),
                            'dimensions' => $designExport->formatted_dimensions,
                            'format' => strtoupper($designExport->file_format ?? ''),
                        ],
                        'was_replacement' => $isReplacement,
                        'previous_design_id' => $previousDesignId,
                    ],
                ]);

            } catch (\Exception $dbError) {
                DB::rollBack();
                throw $dbError;
            }

        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Re-throw validation exceptions
            throw $ve;

        } catch (\Exception $e) {
            // ========================================
            // LOG DE ERROR CRÍTICO
            // ========================================
            Log::channel('daily')->error('DESIGN_LINK_ERROR', [
                'order_id' => $order->id,
                'design_export_id' => $designExportId,
                'user_id' => $userId,
                'ip' => $userIp,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Error interno al vincular el diseño. El equipo técnico ha sido notificado.',
            ], 500);
        }
    }

    // ============================================================
    // DESVINCULAR DISEÑO TÉCNICO DEL PEDIDO
    // ============================================================
    public function unlinkDesignExport(Request $request, Order $order)
    {
        $userId = Auth::id();

        // Verificar estado editable
        if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
            return response()->json([
                'success' => false,
                'error_code' => 'ORDER_NOT_EDITABLE',
                'message' => 'No se puede desvincular: el pedido no está en estado editable.',
            ], 422);
        }

        // Verificar que hay diseño vinculado
        if (!$order->design_export_id) {
            return response()->json([
                'success' => false,
                'error_code' => 'NO_DESIGN_LINKED',
                'message' => 'El pedido no tiene un diseño técnico vinculado.',
            ], 422);
        }

        $previousDesignId = $order->design_export_id;

        DB::beginTransaction();
        try {
            $order->design_export_id = null;
            $order->updated_by = $userId;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'design_unlinked',
                'message' => "Diseño técnico desvinculado: #{$previousDesignId}",
                'metadata' => [
                    'previous_design_export_id' => $previousDesignId,
                ],
                'created_by' => $userId,
            ]);

            DB::commit();

            Log::channel('daily')->info('DESIGN_UNLINK_SUCCESS', [
                'order_id' => $order->id,
                'previous_design_id' => $previousDesignId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diseño técnico desvinculado del pedido.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('DESIGN_UNLINK_ERROR', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desvincular el diseño.',
            ], 500);
        }
    }

    // ========================================
    // VINCULAR DISEÑO A ITEM (MÚLTIPLES DISEÑOS POR ITEM)
    // ========================================

    /**
     * Vincula un DesignExport a un OrderItem específico.
     * Seguridad nivel bancario: validación, logging, transacciones.
     */
    public function linkDesignToItem(Request $request, Order $order, OrderItem $item)
    {
        // Validar que el item pertenece al pedido
        if ($item->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'El item no pertenece a este pedido.',
                'error_code' => 'ITEM_NOT_IN_ORDER',
            ], 403);
        }

        // Validación estricta
        $validated = $request->validate([
            'design_export_id' => 'required|integer|min:1|exists:design_exports,id',
            'position' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'design_export_id.required' => 'El ID del diseño es obligatorio.',
            'design_export_id.exists' => 'El diseño especificado no existe.',
        ]);

        $designExportId = (int) $validated['design_export_id'];
        $userId = Auth::id();

        // Log de auditoría
        Log::channel('daily')->info('ITEM_DESIGN_LINK_ATTEMPT', [
            'order_id' => $order->id,
            'item_id' => $item->id,
            'design_export_id' => $designExportId,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            // Validar que el pedido está en estado editable
            if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no puede ser modificado en su estado actual.',
                    'error_code' => 'ORDER_NOT_EDITABLE',
                ], 422);
            }

            // Validar que el item requiere diseños (es personalizado)
            if (!$item->requiresTechnicalDesigns()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto no requiere diseños técnicos (no es personalizado).',
                    'error_code' => 'ITEM_NOT_CUSTOMIZABLE',
                ], 422);
            }

            // Obtener el diseño y validar estado
            $designExport = DesignExport::findOrFail($designExportId);

            if ($designExport->status !== 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden vincular diseños con estado "aprobado".',
                    'error_code' => 'DESIGN_NOT_APPROVED',
                ], 422);
            }

            // Verificar si ya está vinculado
            if ($item->designExports()->where('design_export_id', $designExportId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este diseño ya está vinculado al producto.',
                    'error_code' => 'DESIGN_ALREADY_LINKED',
                ], 422);
            }

            DB::beginTransaction();

            // Obtener el siguiente orden
            $maxOrder = $item->designExports()->max('order_item_design_exports.sort_order') ?? -1;

            // Vincular diseño al item
            $item->designExports()->attach($designExportId, [
                'position' => $validated['position'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'application_type' => $designExport->applicationType?->name ?? null,
                'sort_order' => $maxOrder + 1,
                'created_by' => $userId,
            ]);

            // Registrar evento
            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'item_design_linked',
                'message' => "Diseño técnico vinculado al item: {$item->product_name}",
                'metadata' => [
                    'item_id' => $item->id,
                    'item_name' => $item->product_name,
                    'design_export_id' => $designExportId,
                    'design_name' => $designExport->application_label,
                ],
                'created_by' => $userId,
            ]);

            DB::commit();

            Log::channel('daily')->info('ITEM_DESIGN_LINK_SUCCESS', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'design_export_id' => $designExportId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diseño vinculado correctamente al producto.',
                'data' => [
                    'design_id' => $designExportId,
                    'design_name' => $designExport->application_label,
                    'total_designs' => $item->designExports()->count(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('ITEM_DESIGN_LINK_ERROR', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'design_export_id' => $designExportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al vincular el diseño.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Desvincula un DesignExport de un OrderItem.
     */
    public function unlinkDesignFromItem(Request $request, Order $order, OrderItem $item, $designExportId)
    {
        // Validar que el item pertenece al pedido
        if ($item->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'El item no pertenece a este pedido.',
                'error_code' => 'ITEM_NOT_IN_ORDER',
            ], 403);
        }

        $userId = Auth::id();

        Log::channel('daily')->info('ITEM_DESIGN_UNLINK_ATTEMPT', [
            'order_id' => $order->id,
            'item_id' => $item->id,
            'design_export_id' => $designExportId,
            'user_id' => $userId,
        ]);

        try {
            // Validar estado del pedido
            if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no puede ser modificado en su estado actual.',
                    'error_code' => 'ORDER_NOT_EDITABLE',
                ], 422);
            }

            // Verificar que el diseño está vinculado
            if (!$item->designExports()->where('design_export_id', $designExportId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El diseño no está vinculado a este producto.',
                    'error_code' => 'DESIGN_NOT_LINKED',
                ], 422);
            }

            DB::beginTransaction();

            // Desvincular
            $item->designExports()->detach($designExportId);

            // Registrar evento
            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'item_design_unlinked',
                'message' => "Diseño técnico desvinculado del item: {$item->product_name}",
                'metadata' => [
                    'item_id' => $item->id,
                    'item_name' => $item->product_name,
                    'design_export_id' => $designExportId,
                ],
                'created_by' => $userId,
            ]);

            DB::commit();

            Log::channel('daily')->info('ITEM_DESIGN_UNLINK_SUCCESS', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'design_export_id' => $designExportId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Diseño desvinculado correctamente.',
                'data' => [
                    'total_designs' => $item->designExports()->count(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('ITEM_DESIGN_UNLINK_ERROR', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desvincular el diseño.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    // ========================================
    // FASE Y: GESTIÓN DE MEDIDAS POR ITEM
    // ========================================

    /**
     * Obtener medidas actuales de un item específico.
     */
    public function getItemMeasurements(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'El item no pertenece a este pedido.',
            ], 403);
        }

        $measurements = $item->measurements ?? [];
        $measurementCount = count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'));

        // Cargar historial vinculado si existe
        $historyRecord = null;
        if ($item->measurement_history_id) {
            $historyRecord = \App\Models\ClientMeasurementHistory::find($item->measurement_history_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'item_id' => $item->id,
                'item_name' => $item->product_name,
                'requires_measurements' => $item->requires_measurements,
                'measurements' => $measurements,
                'measurement_count' => $measurementCount,
                'measurement_history_id' => $item->measurement_history_id,
                'history_record' => $historyRecord ? [
                    'id' => $historyRecord->id,
                    'uuid' => $historyRecord->uuid,
                    'source' => $historyRecord->source,
                    'source_label' => $historyRecord->source_label,
                    'captured_at' => $historyRecord->captured_at?->format('d/m/Y H:i'),
                    'notes' => $historyRecord->notes,
                ] : null,
            ],
        ]);
    }

    /**
     * Actualizar medidas de un item (captura nueva o selección de historial).
     * INMUTABLE: Siempre crea un nuevo registro en el historial.
     *
     * Modos:
     * - mode='capture': Captura nuevas medidas
     * - mode='select': Selecciona medidas existentes del historial
     */
    public function updateItemMeasurements(Request $request, Order $order, OrderItem $item)
    {
        // Validar que el item pertenece al pedido
        if ($item->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'El item no pertenece a este pedido.',
            ], 403);
        }

        // Solo permitir en estados editables
        if (!in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED])) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden modificar medidas en este estado del pedido.',
            ], 422);
        }

        // Validar que el item requiere medidas
        if (!$item->requires_measurements) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no requiere medidas.',
            ], 422);
        }

        $mode = $request->input('mode', 'capture');
        $userId = Auth::id();

        Log::channel('daily')->info('ITEM_MEASUREMENTS_UPDATE_ATTEMPT', [
            'order_id' => $order->id,
            'item_id' => $item->id,
            'mode' => $mode,
            'user_id' => $userId,
        ]);

        try {
            DB::beginTransaction();

            if ($mode === 'select') {
                // MODO SELECCIÓN: Usar medidas existentes del historial
                $historyId = $request->input('measurement_history_id');

                if (!$historyId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debe seleccionar un registro del historial.',
                    ], 422);
                }

                // Verificar que el historial existe y pertenece al cliente
                $historyRecord = \App\Models\ClientMeasurementHistory::where('id', $historyId)
                    ->where('cliente_id', $order->cliente_id)
                    ->first();

                if (!$historyRecord) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Registro de medidas no encontrado o no pertenece al cliente.',
                    ], 404);
                }

                // Copiar medidas al item (snapshot inmutable)
                $item->measurements = $historyRecord->measurements;
                $item->measurement_history_id = $historyRecord->id;
                $item->save();

                // Registrar evento
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'item_measurements_selected',
                    'message' => "Medidas seleccionadas del historial para: {$item->product_name}",
                    'metadata' => [
                        'item_id' => $item->id,
                        'history_id' => $historyRecord->id,
                        'history_uuid' => $historyRecord->uuid,
                        'source' => $historyRecord->source,
                        'captured_at' => $historyRecord->captured_at?->toIso8601String(),
                    ],
                    'created_by' => $userId,
                ]);

            } else {
                // MODO CAPTURA: Nuevas medidas
                $validated = $request->validate([
                    'measurements' => 'required|array',
                    'measurements.busto' => 'nullable|numeric|min:0|max:300',
                    'measurements.cintura' => 'nullable|numeric|min:0|max:300',
                    'measurements.cadera' => 'nullable|numeric|min:0|max:300',
                    'measurements.alto_cintura' => 'nullable|numeric|min:0|max:300',
                    'measurements.largo' => 'nullable|numeric|min:0|max:300',
                    'measurements.largo_vestido' => 'nullable|numeric|min:0|max:300',
                    'save_to_history' => 'nullable|boolean',
                    'notes' => 'nullable|string|max:500',
                ]);

                // Limpiar medidas vacías
                $cleanMeasurements = collect($validated['measurements'])
                    ->filter(fn($value) => !empty($value) && $value !== '0' && $value !== 0)
                    ->toArray();

                if (empty($cleanMeasurements)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debe capturar al menos una medida.',
                    ], 422);
                }

                // Actualizar medidas en el item
                $item->measurements = $cleanMeasurements;

                // Crear registro en historial (siempre, por trazabilidad)
                $saveToHistory = $request->boolean('save_to_history', true);

                if ($saveToHistory) {
                    $historyRecord = \App\Models\ClientMeasurementHistory::create([
                        'cliente_id' => $order->cliente_id,
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'measurements' => $cleanMeasurements,
                        'source' => 'order',
                        'notes' => $validated['notes'] ?? "Capturado en pedido {$order->order_number} - {$item->product_name}",
                        'created_by' => $userId,
                        'captured_at' => now(),
                    ]);

                    $item->measurement_history_id = $historyRecord->id;
                }

                $item->save();

                // Registrar evento
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'item_measurements_captured',
                    'message' => "Nuevas medidas capturadas para: {$item->product_name}",
                    'metadata' => [
                        'item_id' => $item->id,
                        'measurement_count' => count($cleanMeasurements),
                        'saved_to_history' => $saveToHistory,
                        'history_id' => $historyRecord->id ?? null,
                    ],
                    'created_by' => $userId,
                ]);
            }

            // Si el diseño estaba aprobado y se cambiaron medidas, invalidar (R4)
            if ($item->design_approved && $item->hasMeasurementsChangedAfterApproval()) {
                $item->design_approved = false;
                $item->save();

                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'design_invalidated',
                    'message' => "Diseño invalidado por cambio de medidas: {$item->product_name}",
                    'metadata' => [
                        'item_id' => $item->id,
                        'reason' => 'measurements_changed',
                    ],
                    'created_by' => $userId,
                ]);
            }

            DB::commit();

            Log::channel('daily')->info('ITEM_MEASUREMENTS_UPDATE_SUCCESS', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'mode' => $mode,
                'user_id' => $userId,
            ]);

            $measurements = $item->measurements ?? [];
            $measurementCount = count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'));

            return response()->json([
                'success' => true,
                'message' => $mode === 'select'
                    ? 'Medidas del historial aplicadas correctamente.'
                    : 'Medidas capturadas correctamente.',
                'data' => [
                    'item_id' => $item->id,
                    'measurements' => $measurements,
                    'measurement_count' => $measurementCount,
                    'measurement_history_id' => $item->measurement_history_id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('ITEM_MEASUREMENTS_UPDATE_ERROR', [
                'order_id' => $order->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar las medidas.',
            ], 500);
        }
    }

    /**
     * Obtiene los diseños vinculados a un item (para renderizar en UI).
     */
    public function getItemDesigns(Order $order, OrderItem $item)
    {
        if ($item->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'El item no pertenece a este pedido.',
            ], 403);
        }

        $designs = $item->designExports()->with(['design', 'applicationType'])->get();

        return response()->json([
            'success' => true,
            'data' => $designs->map(function ($export) {
                return [
                    'id' => $export->id,
                    'name' => $export->application_label ?? $export->export_name,
                    'design_name' => $export->design?->name,
                    'stitches' => $export->stitches,
                    'stitches_formatted' => $export->stitches ? number_format($export->stitches) : null,
                    'dimensions' => $export->width_mm && $export->height_mm
                        ? "{$export->width_mm}×{$export->height_mm}mm"
                        : null,
                    'file_format' => $export->file_format,
                    'status' => $export->status,
                    'svg_content' => $export->svg_content,
                    'image_url' => $export->image_url,
                    'application_type' => $export->applicationType?->name,
                    'pivot' => [
                        'position' => $export->pivot->position,
                        'notes' => $export->pivot->notes,
                        'sort_order' => $export->pivot->sort_order,
                    ],
                ];
            }),
            'requires_designs' => $item->requiresTechnicalDesigns(),
            'is_complete' => $item->hasRequiredTechnicalDesigns(),
        ]);
    }
}
