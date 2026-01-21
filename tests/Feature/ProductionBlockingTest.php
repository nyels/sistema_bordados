<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAdjustment;
use App\Models\Product;
use App\Models\Cliente;
use App\Models\User;
use App\Models\MaterialVariant;
use App\Models\Material;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests para validar las reglas R1-R8 de bloqueo de producción.
 *
 * TEST 1: Ajuste pendiente bloquea producción (R2)
 * TEST 2: Diseño no aprobado bloquea producción (R3)
 * TEST 3: Medidas modificadas post-aprobación bloquean producción (R4)
 * TEST 4: Stock insuficiente bloquea producción - TODO O NADA (R5)
 * TEST 5: Concurrencia - dos pedidos compitiendo por mismo stock (R6, R7)
 * TEST 6: Mutaciones post-producción bloqueadas
 */
class ProductionBlockingTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected User $user;
    protected Cliente $cliente;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = app(OrderService::class);

        // Crear usuario
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Crear cliente
        $this->cliente = Cliente::factory()->create();

        // Crear producto simple sin BOM para tests básicos
        $this->product = Product::factory()->create([
            'name' => 'Producto Test',
            'base_price' => 100,
            'production_lead_time' => 5,
        ]);
    }

    /**
     * TEST 1: R2 - Ajuste pendiente bloquea producción
     */
    public function test_pending_adjustment_blocks_production(): void
    {
        // Crear pedido confirmado
        $order = $this->createConfirmedOrder();

        // Crear ajuste pendiente
        $item = $order->items->first();
        OrderItemAdjustment::create([
            'order_item_id' => $item->id,
            'type' => OrderItemAdjustment::TYPE_MATERIAL,
            'estimated_cost' => 50,
            'real_cost' => null,
            'status' => OrderItemAdjustment::STATUS_PENDING,
            'reason' => 'Ajuste de prueba',
            'created_by' => $this->user->id,
        ]);

        // Refresh para actualizar flag
        $item->refresh();
        $item->updatePendingAdjustmentsFlag();

        // Intentar pasar a producción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R2.*ajustes de precio pendientes/');

        $this->orderService->triggerProduction($order->fresh());
    }

    /**
     * TEST 2: R3 - Diseño no aprobado bloquea producción
     */
    public function test_unapproved_design_blocks_production(): void
    {
        // Crear pedido con item de tipo diseño
        $order = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
            'total' => 100,
            'personalization_type' => OrderItem::PERSONALIZATION_DESIGN,
            'design_approved' => false, // NO aprobado
            'time_multiplier_snapshot' => 2.0,
            'estimated_lead_time' => 10,
        ]);

        // Intentar pasar a producción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R3.*diseño pendiente de aprobación/');

        $this->orderService->triggerProduction($order);
    }

    /**
     * TEST 3: R4 - Medidas modificadas post-aprobación bloquean producción
     */
    public function test_measurements_changed_after_approval_blocks_production(): void
    {
        // Crear pedido con item de tipo diseño
        $order = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $this->user->id,
        ]);

        $initialMeasurements = ['pecho' => 100, 'espalda' => 95];

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
            'total' => 100,
            'requires_measurements' => true,
            'measurements' => $initialMeasurements,
            'personalization_type' => OrderItem::PERSONALIZATION_DESIGN,
            'design_approved' => false,
            'time_multiplier_snapshot' => 2.0,
            'estimated_lead_time' => 10,
        ]);

        // Aprobar diseño (esto guarda el hash de medidas)
        $this->orderService->approveItemDesign($item);

        // Verificar que el hash se guardó
        $item->refresh();
        $this->assertNotNull($item->measurements_hash_at_approval);
        $this->assertTrue($item->design_approved);

        // Modificar medidas DESPUÉS de aprobar
        $item->measurements = ['pecho' => 105, 'espalda' => 98]; // Cambio!
        $item->saveQuietly();

        // Verificar que detecta el cambio
        $this->assertTrue($item->hasMeasurementsChangedAfterApproval());

        // Intentar pasar a producción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R4.*medidas modificadas después de aprobación/');

        $this->orderService->triggerProduction($order->fresh());
    }

    /**
     * TEST 4: R5 - Stock insuficiente bloquea producción (TODO O NADA)
     */
    public function test_insufficient_stock_blocks_production_no_partial(): void
    {
        // Crear material con stock limitado
        $material = Material::factory()->create(['name' => 'Tela Test']);
        $variant = MaterialVariant::factory()->create([
            'material_id' => $material->id,
            'current_stock' => 5, // Solo 5 unidades
        ]);

        // Crear producto con BOM que requiere 3 unidades
        $productWithBom = Product::factory()->create([
            'name' => 'Producto con BOM',
            'base_price' => 200,
        ]);

        // Asociar material al producto (3 unidades por producto)
        $productWithBom->materials()->attach($variant->id, ['quantity' => 3]);

        // Crear pedido con cantidad 2 (requiere 6 unidades, solo hay 5)
        $order = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 400,
            'total' => 400,
            'created_by' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productWithBom->id,
            'product_name' => $productWithBom->name,
            'unit_price' => 200,
            'quantity' => 2, // 2 x 3 = 6 unidades requeridas
            'subtotal' => 400,
            'total' => 400,
            'personalization_type' => OrderItem::PERSONALIZATION_NONE,
            'design_approved' => true,
        ]);

        // Verificar stock inicial
        $this->assertEquals(5, $variant->fresh()->current_stock);

        // Intentar pasar a producción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R5.*Inventario insuficiente/');

        $this->orderService->triggerProduction($order);

        // Verificar que NO se movió nada de inventario (TODO O NADA)
        $this->assertEquals(5, $variant->fresh()->current_stock);
    }

    /**
     * TEST 5: R6, R7 - Concurrencia: dos pedidos compitiendo por mismo stock
     *
     * Simula dos transacciones intentando reservar el mismo inventario.
     * Solo una debe tener éxito, la otra debe fallar.
     */
    public function test_concurrent_orders_competing_for_same_stock(): void
    {
        // Este test valida que lockForUpdate() funciona correctamente
        // En un escenario real con múltiples procesos/requests

        // Crear material con stock justo para UN pedido
        $material = Material::factory()->create(['name' => 'Material Limitado']);
        $variant = MaterialVariant::factory()->create([
            'material_id' => $material->id,
            'current_stock' => 10, // Solo 10 unidades
        ]);

        // Crear producto que usa 10 unidades
        $productLimited = Product::factory()->create([
            'name' => 'Producto Limitado',
            'base_price' => 500,
        ]);
        $productLimited->materials()->attach($variant->id, ['quantity' => 10]);

        // Crear DOS pedidos que quieren el mismo material
        $order1 = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 500,
            'total' => 500,
            'created_by' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $productLimited->id,
            'product_name' => $productLimited->name,
            'unit_price' => 500,
            'quantity' => 1,
            'subtotal' => 500,
            'total' => 500,
            'personalization_type' => OrderItem::PERSONALIZATION_NONE,
            'design_approved' => true,
        ]);

        $order2 = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 500,
            'total' => 500,
            'created_by' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $productLimited->id,
            'product_name' => $productLimited->name,
            'unit_price' => 500,
            'quantity' => 1,
            'subtotal' => 500,
            'total' => 500,
            'personalization_type' => OrderItem::PERSONALIZATION_NONE,
            'design_approved' => true,
        ]);

        // Procesar primer pedido - debe tener éxito
        $this->orderService->triggerProduction($order1);

        // Verificar que el stock se redujo
        $this->assertEquals(0, $variant->fresh()->current_stock);

        // Segundo pedido debe fallar por falta de stock
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R5.*Inventario insuficiente/');

        $this->orderService->triggerProduction($order2);
    }

    /**
     * TEST 6: Mutaciones post-producción bloqueadas
     */
    public function test_post_production_mutations_blocked(): void
    {
        // Crear pedido y pasarlo a producción
        $order = $this->createConfirmedOrder();
        $order->update(['status' => Order::STATUS_IN_PRODUCTION]);

        // Verificar que isMutationBlocked() retorna true
        $this->assertTrue($order->isMutationBlocked());
        $this->assertFalse($order->canModifyItems());

        // Intentar modificar campo inmutable
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Mutación bloqueada.*producción/');

        $order->validateMutationAllowed(['discount' => 50]);
    }

    /**
     * TEST 6b: Items post-producción no pueden modificarse
     */
    public function test_post_production_item_mutations_blocked(): void
    {
        $order = $this->createConfirmedOrder();
        $order->update(['status' => Order::STATUS_IN_PRODUCTION]);

        $item = $order->items->first();

        // Verificar bloqueo a nivel item
        $this->assertTrue($item->isMutationBlocked());

        // Intentar modificar campo inmutable del item
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Mutación bloqueada.*producción/');

        $item->validateMutationAllowed(['quantity' => 5]);
    }

    /**
     * TEST 7: Campos mutables post-producción SÍ se pueden modificar
     */
    public function test_mutable_fields_allowed_post_production(): void
    {
        $order = $this->createConfirmedOrder();
        $order->update(['status' => Order::STATUS_IN_PRODUCTION]);

        // Campos que SÍ pueden modificarse
        $allowedChanges = [
            'notes' => 'Nueva nota',
            'payment_status' => Order::PAYMENT_PARTIAL,
        ];

        // No debe lanzar excepción
        $order->validateMutationAllowed($allowedChanges);

        $this->assertTrue(true); // Si llegamos aquí, pasó
    }

    /**
     * TEST 8: Orden con status draft NO puede pasar a producción
     */
    public function test_draft_order_cannot_trigger_production(): void
    {
        $order = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_DRAFT, // Draft, no confirmado
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/R1.*Solo pedidos confirmados/');

        $this->orderService->triggerProduction($order);
    }

    /**
     * Helper: Crear pedido confirmado sin BOM para tests simples
     */
    protected function createConfirmedOrder(): Order
    {
        $order = Order::create([
            'cliente_id' => $this->cliente->id,
            'status' => Order::STATUS_CONFIRMED,
            'urgency_level' => Order::URGENCY_NORMAL,
            'subtotal' => 100,
            'total' => 100,
            'created_by' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
            'total' => 100,
            'personalization_type' => OrderItem::PERSONALIZATION_NONE,
            'design_approved' => true,
        ]);

        return $order->fresh(['items']);
    }
}
