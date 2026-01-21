<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderEvent;
use App\Models\Cliente;
use App\Models\Product;
use App\Models\ClientMeasurement;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreOrderPaymentRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $query->where('status', Order::STATUS_CONFIRMED)
                ->where(function ($q) {
                    $q->whereHas('items', fn($i) => $i->where('has_pending_adjustments', true))
                      ->orWhereHas('items', fn($i) => $i->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN)
                                                        ->where('design_approved', false));
                });
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
        // ========================================
        $kpis = [
            'para_producir' => Order::where('status', Order::STATUS_CONFIRMED)
                ->whereDoesntHave('items', fn($q) => $q->where('has_pending_adjustments', true))
                ->whereDoesntHave('items', fn($q) => $q->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN)
                                                       ->where('design_approved', false))
                ->count(),

            'bloqueados' => Order::where('status', Order::STATUS_CONFIRMED)
                ->where(function ($q) {
                    $q->whereHas('items', fn($i) => $i->where('has_pending_adjustments', true))
                      ->orWhereHas('items', fn($i) => $i->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN)
                                                        ->where('design_approved', false));
                })
                ->count(),

            'en_produccion' => Order::where('status', Order::STATUS_IN_PRODUCTION)->count(),

            'para_entregar' => Order::where('status', Order::STATUS_READY)->count(),

            'retrasados' => Order::whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
                ->whereDate('promised_date', '<', now())
                ->count(),
        ];

        return view('admin.orders.index', compact('orders', 'kpis'));
    }

    // === FORMULARIO CREAR ===
    public function create()
    {
        // Productos se cargan para el modal, clientes vía AJAX
        $products = Product::where('status', 'active')
            ->with(['variants', 'primaryImage'])
            ->orderBy('name')
            ->get();

        return view('admin.orders.create', compact('products'));
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
            'items.variant',
            'payments.receiver',
            'creator',
            'parentOrder',
            'annexOrders',
        ]);

        // Determinar si puede agregar items anexos (confirmado o producción temprana)
        $canAddItems = $this->orderService->canAddItems($order);

        return view('admin.orders.show', compact('order', 'canAddItems'));
    }

    // === FORMULARIO CREAR ANEXO ===
    public function createAnnex(Order $order)
    {
        if (!$order->isInProduction()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Solo se pueden crear anexos para pedidos en producción o posterior.');
        }

        $order->load('cliente');

        $products = Product::where('status', 'active')
            ->with(['variants', 'primaryImage'])
            ->orderBy('name')
            ->get();

        return view('admin.orders.create-annex', compact('order', 'products'));
    }

    // === GUARDAR ANEXO ===
    public function storeAnnex(StoreOrderRequest $request, Order $order)
    {
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
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:draft,confirmed,in_production,ready,delivered,cancelled',
        ]);

        $newStatus = $request->status;

        // TRIGGER: Pasar a producción → RESERVA de inventario
        if ($newStatus === Order::STATUS_IN_PRODUCTION && $order->status === Order::STATUS_CONFIRMED) {
            try {
                $this->orderService->triggerProduction($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido enviado a producción. Materiales reservados.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al iniciar producción: ' . $e->getMessage());
            }
        }

        // TRIGGER: Entregar pedido → CONSUMO de inventario
        if ($newStatus === Order::STATUS_DELIVERED && $order->status === Order::STATUS_READY) {
            try {
                $this->orderService->triggerDelivery($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido entregado. Materiales descontados del inventario.');
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
        } elseif ($newStatus === Order::STATUS_READY) {
            OrderEvent::logReady($order);
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
}
