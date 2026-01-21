<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Material;
use App\Models\MaterialVariant;
use App\Models\MaterialCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderEvent;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\InventoryReservation;
use App\Models\Unit;
use App\Services\OrderService;
use App\Services\PurchaseService;
use App\Services\ReceptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLOQUE 7: PRUEBA END-TO-END OBLIGATORIA
 *
 * Simula el flujo completo:
 * 1) Alta cliente
 * 2) Alta proveedor
 * 3) Alta material
 * 4) Alta producto
 * 5) Pedido normal
 * 6) Pedido urgente
 * 7) Compra material
 * 8) Recepción parcial
 * 9) Producción
 * 10) Consumo inventario
 * 11) Entrega
 * 12) Trazabilidad completa visible
 */
class EndToEndTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected OrderService $orderService;
    protected PurchaseService $purchaseService;
    protected ReceptionService $receptionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Servicios
        $this->orderService = app(OrderService::class);
        $this->purchaseService = app(PurchaseService::class);
        $this->receptionService = app(ReceptionService::class);
    }

    /** @test */
    public function flujo_completo_cliente_pedido_produccion_entrega()
    {
        // ============================================
        // PASO 1: ALTA CLIENTE
        // ============================================
        $cliente = Cliente::create([
            'nombre' => 'María',
            'apellidos' => 'González López',
            'telefono' => '9991234567',
            'email' => 'maria@test.com',
            'direccion' => 'Calle 50 #123',
            'ciudad' => 'Mérida',
            'activo' => true,
            'busto' => 90,
            'cintura' => 70,
            'cadera' => 95,
        ]);

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'María',
            'email' => 'maria@test.com',
        ]);

        // ============================================
        // PASO 2: ALTA PROVEEDOR
        // ============================================
        $proveedor = Proveedor::create([
            'nombre_proveedor' => 'Telas del Sureste SA',
            'nombre_contacto' => 'Juan Pérez',
            'telefono_contacto' => '9997654321',
            'email_contacto' => 'ventas@telas.com',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('proveedors', [
            'nombre_proveedor' => 'Telas del Sureste SA',
        ]);

        // ============================================
        // PASO 3: ALTA MATERIAL
        // ============================================
        $category = MaterialCategory::create([
            'name' => 'Telas',
            'slug' => 'telas',
        ]);

        $unit = Unit::firstOrCreate(
            ['symbol' => 'm'],
            ['name' => 'Metro', 'type' => 'length', 'is_base' => true]
        );

        $material = Material::create([
            'name' => 'Tela Manta Cruda',
            'slug' => 'tela-manta-cruda',
            'material_category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'consumption_unit_id' => $unit->id,
            'activo' => true,
        ]);

        $variant = MaterialVariant::create([
            'material_id' => $material->id,
            'color' => 'Natural',
            'sku' => 'TMC-NAT-001',
            'current_stock' => 100,
            'min_stock_alert' => 20,
            'average_cost' => 50.00,
            'activo' => true,
        ]);

        $this->assertDatabaseHas('materials', ['name' => 'Tela Manta Cruda']);
        $this->assertDatabaseHas('material_variants', ['sku' => 'TMC-NAT-001']);

        // ============================================
        // PASO 4: ALTA PRODUCTO
        // ============================================
        $productCategory = ProductCategory::create([
            'name' => 'Hipiles',
            'slug' => 'hipiles',
        ]);

        $product = Product::create([
            'name' => 'Hipil Tradicional Bordado',
            'sku' => 'HIP-TRAD-001',
            'product_category_id' => $productCategory->id,
            'base_price' => 1500.00,
            'status' => 'active',
            'production_lead_time' => 7,
        ]);

        // Asociar material al producto (requiere 2.5m por pieza)
        $product->materials()->attach($variant->id, ['quantity' => 2.5]);

        $this->assertDatabaseHas('products', ['sku' => 'HIP-TRAD-001']);

        // ============================================
        // PASO 5: PEDIDO NORMAL
        // ============================================
        $orderData = [
            'cliente_id' => $cliente->id,
            'urgency_level' => Order::URGENCY_NORMAL,
            'requires_invoice' => false,
            'promised_date' => now()->addDays(14)->format('Y-m-d'),
            'notes' => 'Pedido de prueba E2E',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 1500.00,
                ],
            ],
        ];

        $orderNormal = $this->orderService->createOrder($orderData);

        $this->assertEquals(Order::STATUS_DRAFT, $orderNormal->status);
        $this->assertEquals(3000.00, $orderNormal->subtotal);

        // Verificar evento de creación
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderNormal->id,
            'event_type' => OrderEvent::TYPE_CREATED,
        ]);

        // ============================================
        // PASO 6: PEDIDO URGENTE
        // ============================================
        $orderUrgentData = [
            'cliente_id' => $cliente->id,
            'urgency_level' => Order::URGENCY_URGENTE,
            'requires_invoice' => true,
            'promised_date' => now()->addDays(5)->format('Y-m-d'),
            'notes' => 'URGENTE - Evento este fin de semana',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1800.00, // Precio con urgencia
                ],
            ],
        ];

        $orderUrgent = $this->orderService->createOrder($orderUrgentData);

        $this->assertEquals(Order::URGENCY_URGENTE, $orderUrgent->urgency_level);
        $this->assertTrue($orderUrgent->requires_invoice);

        // Verificar IVA calculado
        $expectedIva = round(1800.00 * 0.16, 2);
        $this->assertEquals($expectedIva, $orderUrgent->iva_amount);

        // ============================================
        // PASO 7: COMPRA MATERIAL
        // ============================================
        $purchaseData = [
            'proveedor_id' => $proveedor->id,
            'ordered_at' => now()->format('Y-m-d'),
            'expected_at' => now()->addDays(3)->format('Y-m-d'),
            'notes' => 'Reposición de manta cruda',
            'items' => [
                [
                    'material_variant_id' => $variant->id,
                    'quantity' => 50,
                    'unit_price' => 48.00,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $this->assertEquals('borrador', $purchase->status->value);
        $this->assertEquals(2400.00, $purchase->subtotal);

        // Confirmar compra
        $this->purchaseService->confirmPurchase($purchase);
        $purchase->refresh();

        $this->assertEquals('pendiente', $purchase->status->value);

        // ============================================
        // PASO 8: RECEPCIÓN PARCIAL
        // ============================================
        $stockBefore = $variant->fresh()->current_stock;

        $receptionData = [
            'received_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Recepción parcial - faltó 20m',
            'items' => [
                [
                    'purchase_item_id' => $purchase->items->first()->id,
                    'quantity_received' => 30, // Solo 30 de 50
                ],
            ],
        ];

        $reception = $this->receptionService->createReception($purchase, $receptionData);

        $purchase->refresh();
        $variant->refresh();

        $this->assertEquals('parcial', $purchase->status->value);
        $this->assertEquals($stockBefore + 30, $variant->current_stock);

        // ============================================
        // PASO 9: ENVIAR A PRODUCCIÓN
        // ============================================
        // Primero confirmar el pedido
        $orderNormal->update(['status' => Order::STATUS_CONFIRMED]);

        // Enviar a producción (debe reservar materiales)
        $this->orderService->transitionToProduction($orderNormal);
        $orderNormal->refresh();

        $this->assertEquals(Order::STATUS_IN_PRODUCTION, $orderNormal->status);

        // Verificar reserva de materiales (2 piezas × 2.5m = 5m)
        $reservation = InventoryReservation::where('order_id', $orderNormal->id)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals(5.0, $reservation->quantity);
        $this->assertEquals(InventoryReservation::STATUS_RESERVED, $reservation->status);

        // Verificar evento de producción iniciada
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderNormal->id,
            'event_type' => OrderEvent::TYPE_PRODUCTION_STARTED,
        ]);

        // ============================================
        // PASO 10: MARCAR LISTO
        // ============================================
        $orderNormal->update(['status' => Order::STATUS_READY]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderNormal->id,
            'event_type' => OrderEvent::TYPE_READY,
        ]);

        // ============================================
        // PASO 11: ENTREGA (CONSUMO DE INVENTARIO)
        // ============================================
        $stockBeforeDelivery = $variant->fresh()->current_stock;

        $this->orderService->deliverOrder($orderNormal);
        $orderNormal->refresh();
        $variant->refresh();

        $this->assertEquals(Order::STATUS_DELIVERED, $orderNormal->status);
        $this->assertNotNull($orderNormal->delivered_date);

        // Verificar consumo de inventario
        $this->assertEquals($stockBeforeDelivery - 5, $variant->current_stock);

        // Verificar reserva consumida
        $reservation->refresh();
        $this->assertEquals(InventoryReservation::STATUS_CONSUMED, $reservation->status);

        // Verificar evento de entrega
        $this->assertDatabaseHas('order_events', [
            'order_id' => $orderNormal->id,
            'event_type' => OrderEvent::TYPE_DELIVERED,
        ]);

        // ============================================
        // PASO 12: TRAZABILIDAD COMPLETA
        // ============================================
        $events = $orderNormal->events()->orderBy('created_at')->get();

        // Debe tener al menos: created, production_started, ready, delivered
        $this->assertGreaterThanOrEqual(4, $events->count());

        // Verificar secuencia de eventos
        $eventTypes = $events->pluck('event_type')->toArray();
        $this->assertContains(OrderEvent::TYPE_CREATED, $eventTypes);
        $this->assertContains(OrderEvent::TYPE_PRODUCTION_STARTED, $eventTypes);
        $this->assertContains(OrderEvent::TYPE_DELIVERED, $eventTypes);

        // Verificar que cada evento tiene usuario creador
        foreach ($events as $event) {
            $this->assertNotNull($event->created_by, "Evento {$event->event_type} sin usuario");
        }

        // Verificar metadata en eventos relevantes
        $deliveredEvent = $events->where('event_type', OrderEvent::TYPE_DELIVERED)->first();
        $this->assertNotNull($deliveredEvent->metadata);
        $this->assertArrayHasKey('consumed_materials', $deliveredEvent->metadata);
    }

    /** @test */
    public function trazabilidad_registra_todos_los_cambios_de_estado()
    {
        $cliente = Cliente::factory()->create();
        $product = Product::factory()->create(['base_price' => 500]);

        $orderData = [
            'cliente_id' => $cliente->id,
            'urgency_level' => Order::URGENCY_NORMAL,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 500],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        // Transiciones de estado
        $order->update(['status' => Order::STATUS_CONFIRMED]);
        OrderEvent::logConfirmed($order);

        $order->update(['status' => Order::STATUS_CANCELLED]);
        OrderEvent::logCancelled($order, 'Cliente canceló');

        // Verificar trazabilidad
        $events = $order->events()->pluck('event_type')->toArray();

        $this->assertContains(OrderEvent::TYPE_CREATED, $events);
        $this->assertContains(OrderEvent::TYPE_CONFIRMED, $events);
        $this->assertContains(OrderEvent::TYPE_CANCELLED, $events);

        // Verificar motivo de cancelación
        $cancelEvent = $order->events()->where('event_type', OrderEvent::TYPE_CANCELLED)->first();
        $this->assertStringContains('Cliente canceló', $cancelEvent->message);
    }

    /** @test */
    public function cola_produccion_muestra_pedidos_ordenados_por_prioridad()
    {
        $cliente = Cliente::factory()->create();
        $product = Product::factory()->create();

        // Crear pedidos con diferentes prioridades
        $orderLow = $this->createOrderWithPriority($cliente, $product, 75, Order::URGENCY_NORMAL);
        $orderHigh = $this->createOrderWithPriority($cliente, $product, 10, Order::URGENCY_EXPRESS);
        $orderMedium = $this->createOrderWithPriority($cliente, $product, 50, Order::URGENCY_URGENTE);

        // Confirmar pedidos
        foreach ([$orderLow, $orderHigh, $orderMedium] as $o) {
            $o->update(['status' => Order::STATUS_CONFIRMED]);
        }

        // Consultar cola ordenada
        $queue = Order::where('status', Order::STATUS_CONFIRMED)
            ->orderBy('priority', 'asc')
            ->get();

        $this->assertEquals($orderHigh->id, $queue->first()->id);
        $this->assertEquals($orderLow->id, $queue->last()->id);
    }

    protected function createOrderWithPriority($cliente, $product, $priority, $urgency): Order
    {
        $order = Order::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'order_number' => 'PED-TEST-' . rand(1000, 9999),
            'cliente_id' => $cliente->id,
            'status' => Order::STATUS_DRAFT,
            'urgency_level' => $urgency,
            'priority' => $priority,
            'subtotal' => 1000,
            'total' => 1000,
            'created_by' => $this->user->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'total' => 1000,
        ]);

        return $order;
    }
}
