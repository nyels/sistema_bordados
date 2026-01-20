<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
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
        $query = Order::with(['cliente', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->paginate(20);

        return view('admin.orders.index', compact('orders'));
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

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pago registrado exitosamente.');
    }

    // === CAMBIAR ESTADO (CON TRIGGER DE PRODUCCIÓN) ===
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:draft,confirmed,in_production,ready,delivered,cancelled',
        ]);

        $newStatus = $request->status;

        // TRIGGER: Pasar a producción deduce inventario
        if ($newStatus === Order::STATUS_IN_PRODUCTION && $order->status === Order::STATUS_CONFIRMED) {
            try {
                $this->orderService->triggerProduction($order);
                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Pedido enviado a producción. Materiales deducidos del inventario.');
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Error al iniciar producción: ' . $e->getMessage());
            }
        }

        // Cambio de estado normal
        $order->update([
            'status' => $newStatus,
            'delivered_date' => $newStatus === Order::STATUS_DELIVERED ? now() : $order->delivered_date,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Estado actualizado.');
    }

    // === CANCELAR PEDIDO ===
    public function cancel(Order $order)
    {
        if ($order->status === Order::STATUS_DELIVERED) {
            return redirect()->back()
                ->with('error', 'No se puede cancelar un pedido entregado.');
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Pedido cancelado.');
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
            ->with(['variants.attributeValues', 'primaryImage']);

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
