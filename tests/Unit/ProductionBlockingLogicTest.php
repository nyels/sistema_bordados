<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Tests unitarios para validar la lógica de bloqueo de producción.
 * Estos tests no requieren base de datos.
 */
class ProductionBlockingLogicTest extends TestCase
{
    /**
     * TEST: Orden en producción tiene mutaciones bloqueadas
     */
    public function test_order_in_production_has_mutations_blocked(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_IN_PRODUCTION;

        $this->assertTrue($order->isMutationBlocked());
        $this->assertTrue($order->isInProduction());
        $this->assertFalse($order->canModifyItems());
    }

    /**
     * TEST: Orden ready tiene mutaciones bloqueadas
     */
    public function test_order_ready_has_mutations_blocked(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_READY;

        $this->assertTrue($order->isMutationBlocked());
        $this->assertTrue($order->isInProduction());
        $this->assertFalse($order->canModifyItems());
    }

    /**
     * TEST: Orden delivered tiene mutaciones bloqueadas
     */
    public function test_order_delivered_has_mutations_blocked(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_DELIVERED;

        $this->assertTrue($order->isMutationBlocked());
        $this->assertTrue($order->isInProduction());
        $this->assertFalse($order->canModifyItems());
    }

    /**
     * TEST: Orden draft NO tiene mutaciones bloqueadas
     */
    public function test_order_draft_allows_mutations(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_DRAFT;

        $this->assertFalse($order->isMutationBlocked());
        $this->assertFalse($order->isInProduction());
        $this->assertTrue($order->canModifyItems());
    }

    /**
     * TEST: Orden confirmed NO tiene mutaciones bloqueadas
     */
    public function test_order_confirmed_allows_mutations(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_CONFIRMED;

        $this->assertFalse($order->isMutationBlocked());
        $this->assertFalse($order->isInProduction());
        $this->assertTrue($order->canModifyItems());
    }

    /**
     * TEST: validateMutationAllowed lanza excepción para campos inmutables
     */
    public function test_validate_mutation_throws_for_immutable_fields(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_IN_PRODUCTION;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Mutación bloqueada.*producción/');

        $order->validateMutationAllowed(['discount' => 100]);
    }

    /**
     * TEST: validateMutationAllowed NO lanza excepción para campos mutables
     */
    public function test_validate_mutation_allows_mutable_fields(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_IN_PRODUCTION;

        // Esto NO debe lanzar excepción
        $order->validateMutationAllowed(['notes' => 'Nueva nota']);

        $this->assertTrue(true); // Si llegamos aquí, el test pasó
    }

    /**
     * TEST: validateMutationAllowed permite todo en status draft
     */
    public function test_validate_mutation_allows_all_in_draft(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_DRAFT;

        // Esto NO debe lanzar excepción aunque sea campo inmutable
        $order->validateMutationAllowed(['discount' => 100, 'cliente_id' => 5]);

        $this->assertTrue(true);
    }

    /**
     * TEST: Lista de campos inmutables está definida
     */
    public function test_immutable_fields_list_is_complete(): void
    {
        $expectedFields = [
            'cliente_id',
            'client_measurement_id',
            'urgency_level',
            'subtotal',
            'discount',
            'requires_invoice',
            'iva_amount',
            'total',
            'promised_date',
            'minimum_date',
        ];

        foreach ($expectedFields as $field) {
            $this->assertContains(
                $field,
                Order::IMMUTABLE_FIELDS_POST_PRODUCTION,
                "Campo {$field} falta en la lista de inmutables"
            );
        }
    }

    /**
     * TEST: getMutationBlockReason retorna mensaje correcto
     */
    public function test_get_mutation_block_reason_returns_message(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_IN_PRODUCTION;

        $reason = $order->getMutationBlockReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('producción', $reason);
    }

    /**
     * TEST: getMutationBlockReason retorna null si no está bloqueado
     */
    public function test_get_mutation_block_reason_returns_null_if_not_blocked(): void
    {
        $order = new Order();
        $order->status = Order::STATUS_DRAFT;

        $reason = $order->getMutationBlockReason();

        $this->assertNull($reason);
    }

    /**
     * TEST: OrderItem constantes de personalización existen
     */
    public function test_order_item_personalization_constants_exist(): void
    {
        $this->assertEquals('none', OrderItem::PERSONALIZATION_NONE);
        $this->assertEquals('text', OrderItem::PERSONALIZATION_TEXT);
        $this->assertEquals('measurements', OrderItem::PERSONALIZATION_MEASUREMENTS);
        $this->assertEquals('design', OrderItem::PERSONALIZATION_DESIGN);
    }

    /**
     * TEST: OrderItem campos inmutables están definidos
     */
    public function test_order_item_immutable_fields_defined(): void
    {
        $expectedFields = [
            'product_id',
            'quantity',
            'unit_price',
            'measurements',
        ];

        foreach ($expectedFields as $field) {
            $this->assertContains(
                $field,
                OrderItem::IMMUTABLE_FIELDS_POST_PRODUCTION,
                "Campo {$field} falta en la lista de inmutables de OrderItem"
            );
        }
    }

    /**
     * TEST: OrderItem requiresDesignApproval identifica correctamente
     */
    public function test_order_item_requires_design_approval(): void
    {
        $item = new OrderItem();

        $item->personalization_type = OrderItem::PERSONALIZATION_DESIGN;
        $this->assertTrue($item->requiresDesignApproval());

        $item->personalization_type = OrderItem::PERSONALIZATION_TEXT;
        $this->assertFalse($item->requiresDesignApproval());

        $item->personalization_type = OrderItem::PERSONALIZATION_NONE;
        $this->assertFalse($item->requiresDesignApproval());
    }

    /**
     * TEST: OrderItem isDesignApproved funciona correctamente
     */
    public function test_order_item_is_design_approved(): void
    {
        $item = new OrderItem();

        // Items sin tipo design siempre retornan true
        $item->personalization_type = OrderItem::PERSONALIZATION_TEXT;
        $item->design_approved = false;
        $this->assertTrue($item->isDesignApproved());

        // Items con tipo design dependen del flag
        $item->personalization_type = OrderItem::PERSONALIZATION_DESIGN;
        $item->design_approved = false;
        $this->assertFalse($item->isDesignApproved());

        $item->design_approved = true;
        $this->assertTrue($item->isDesignApproved());
    }

    /**
     * TEST: OrderItem blocksProductionForDesign funciona correctamente
     */
    public function test_order_item_blocks_production_for_design(): void
    {
        $item = new OrderItem();

        // Item con tipo design NO aprobado -> bloquea
        $item->personalization_type = OrderItem::PERSONALIZATION_DESIGN;
        $item->design_approved = false;
        $this->assertTrue($item->blocksProductionForDesign());

        // Item con tipo design aprobado -> NO bloquea
        $item->design_approved = true;
        $this->assertFalse($item->blocksProductionForDesign());

        // Item sin tipo design -> NO bloquea
        $item->personalization_type = OrderItem::PERSONALIZATION_TEXT;
        $item->design_approved = false;
        $this->assertFalse($item->blocksProductionForDesign());
    }

    /**
     * TEST: Hash de medidas es consistente
     */
    public function test_measurements_hash_is_consistent(): void
    {
        $item = new OrderItem();

        $measurements = ['pecho' => 100, 'espalda' => 95, 'cintura' => 80];
        $item->measurements = $measurements;

        $hash1 = $item->getMeasurementsHash();
        $hash2 = $item->getMeasurementsHash();

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(32, strlen($hash1)); // MD5 = 32 chars
    }

    /**
     * TEST: Hash de medidas detecta cambios
     */
    public function test_measurements_hash_detects_changes(): void
    {
        $item = new OrderItem();

        $item->measurements = ['pecho' => 100, 'espalda' => 95];
        $hash1 = $item->getMeasurementsHash();

        $item->measurements = ['pecho' => 105, 'espalda' => 95]; // Cambio
        $hash2 = $item->getMeasurementsHash();

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * TEST: Hash de medidas es ordenado (independiente del orden de keys)
     */
    public function test_measurements_hash_is_order_independent(): void
    {
        $item1 = new OrderItem();
        $item2 = new OrderItem();

        $item1->measurements = ['pecho' => 100, 'espalda' => 95];
        $item2->measurements = ['espalda' => 95, 'pecho' => 100]; // Mismo contenido, diferente orden

        $this->assertEquals($item1->getMeasurementsHash(), $item2->getMeasurementsHash());
    }

    /**
     * TEST: Hash de medidas vacías es null
     */
    public function test_measurements_hash_empty_is_null(): void
    {
        $item = new OrderItem();
        $item->measurements = null;

        $this->assertNull($item->getMeasurementsHash());

        $item->measurements = [];
        $this->assertNull($item->getMeasurementsHash());
    }
}
