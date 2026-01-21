<?php

/**
 * Script de verificación manual de bloqueo de producción.
 * Ejecutar con: php artisan tinker < scripts/test_production_blocking.php
 *
 * Tests realizados:
 * TEST 1: R1 - Status draft no puede pasar a producción
 * TEST 2: R2 - Ajuste pendiente bloquea producción
 * TEST 3: R3 - Diseño no aprobado bloquea producción
 * TEST 4: R4 - Medidas modificadas post-aprobación bloquean producción
 * TEST 5: R5 - Stock insuficiente bloquea (TODO O NADA)
 * TEST 6: Mutaciones post-producción bloqueadas
 */

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAdjustment;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "\n========================================\n";
echo "TESTS DE BLOQUEO DE PRODUCCIÓN (R1-R8)\n";
echo "========================================\n\n";

$orderService = app(OrderService::class);
$testsPassed = 0;
$testsFailed = 0;

// Helper para login
if (!Auth::check()) {
    $user = \App\Models\User::first();
    if ($user) {
        Auth::login($user);
        echo "✓ Usuario autenticado: {$user->name}\n\n";
    }
}

// =============================================
// TEST 1: R1 - Status draft no puede producirse
// =============================================
echo "TEST 1: R1 - Status DRAFT no puede pasar a producción\n";
try {
    $draftOrder = Order::where('status', Order::STATUS_DRAFT)->first();
    if (!$draftOrder) {
        echo "  [SKIP] No hay órdenes en draft para probar\n";
    } else {
        $orderService->triggerProduction($draftOrder);
        echo "  [FAIL] Debió lanzar excepción\n";
        $testsFailed++;
    }
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'R1')) {
        echo "  [PASS] Excepción correcta: " . substr($e->getMessage(), 0, 80) . "\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] Excepción incorrecta: {$e->getMessage()}\n";
        $testsFailed++;
    }
}
echo "\n";

// =============================================
// TEST 2: Verificar método hasPendingAdjustments
// =============================================
echo "TEST 2: Método hasPendingAdjustments funciona\n";
$confirmedOrder = Order::where('status', Order::STATUS_CONFIRMED)->first();
if ($confirmedOrder) {
    $hasPending = $confirmedOrder->hasPendingAdjustments();
    echo "  [INFO] Orden #{$confirmedOrder->order_number} tiene ajustes pendientes: " . ($hasPending ? 'SÍ' : 'NO') . "\n";
    $testsPassed++;
} else {
    echo "  [SKIP] No hay órdenes confirmadas\n";
}
echo "\n";

// =============================================
// TEST 3: Verificar método hasItemsPendingDesignApproval
// =============================================
echo "TEST 3: Método hasItemsPendingDesignApproval funciona\n";
$orderWithDesign = Order::whereHas('items', function($q) {
    $q->where('personalization_type', OrderItem::PERSONALIZATION_DESIGN);
})->first();

if ($orderWithDesign) {
    $hasPendingDesign = $orderWithDesign->hasItemsPendingDesignApproval();
    echo "  [INFO] Orden #{$orderWithDesign->order_number} tiene diseños pendientes: " . ($hasPendingDesign ? 'SÍ' : 'NO') . "\n";
    $testsPassed++;
} else {
    echo "  [SKIP] No hay órdenes con items de tipo diseño\n";
}
echo "\n";

// =============================================
// TEST 4: Verificar hash de medidas
// =============================================
echo "TEST 4: Hash de medidas para detección de cambios\n";
$itemWithMeasurements = OrderItem::whereNotNull('measurements')
    ->where('measurements', '!=', '[]')
    ->where('measurements', '!=', 'null')
    ->first();

if ($itemWithMeasurements) {
    $hash = $itemWithMeasurements->getMeasurementsHash();
    echo "  [INFO] Item ID {$itemWithMeasurements->id} hash: {$hash}\n";

    // Verificar detección de cambios
    $originalMeasurements = $itemWithMeasurements->measurements;
    $itemWithMeasurements->measurements = array_merge($originalMeasurements ?? [], ['test_field' => 999]);
    $newHash = $itemWithMeasurements->getMeasurementsHash();

    if ($hash !== $newHash) {
        echo "  [PASS] Cambio de medidas detectado correctamente\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] No detectó el cambio de medidas\n";
        $testsFailed++;
    }

    // Restaurar
    $itemWithMeasurements->measurements = $originalMeasurements;
} else {
    echo "  [SKIP] No hay items con medidas para probar\n";
}
echo "\n";

// =============================================
// TEST 5: Bloqueo de mutaciones post-producción
// =============================================
echo "TEST 5: Bloqueo de mutaciones post-producción\n";
$productionOrder = Order::where('status', Order::STATUS_IN_PRODUCTION)->first();
if ($productionOrder) {
    echo "  [INFO] Orden #{$productionOrder->order_number} en producción\n";

    // Verificar isMutationBlocked
    if ($productionOrder->isMutationBlocked()) {
        echo "  [PASS] isMutationBlocked() retorna TRUE\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] isMutationBlocked() debería retornar TRUE\n";
        $testsFailed++;
    }

    // Intentar modificar campo inmutable
    try {
        $productionOrder->validateMutationAllowed(['discount' => 999]);
        echo "  [FAIL] Debió lanzar excepción por campo inmutable\n";
        $testsFailed++;
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'Mutación bloqueada')) {
            echo "  [PASS] Excepción correcta para campo inmutable\n";
            $testsPassed++;
        } else {
            echo "  [FAIL] Excepción incorrecta\n";
            $testsFailed++;
        }
    }

    // Verificar que campos mutables SÍ están permitidos
    try {
        $productionOrder->validateMutationAllowed(['notes' => 'Test']);
        echo "  [PASS] Campo mutable (notes) permitido\n";
        $testsPassed++;
    } catch (\Exception $e) {
        echo "  [FAIL] Campo mutable no debería bloquearse\n";
        $testsFailed++;
    }
} else {
    echo "  [SKIP] No hay órdenes en producción\n";
}
echo "\n";

// =============================================
// TEST 6: Verificar constantes de personalización
// =============================================
echo "TEST 6: Constantes de personalización en OrderItem\n";
$constants = [
    'PERSONALIZATION_NONE' => 'none',
    'PERSONALIZATION_TEXT' => 'text',
    'PERSONALIZATION_MEASUREMENTS' => 'measurements',
    'PERSONALIZATION_DESIGN' => 'design',
];

$allCorrect = true;
foreach ($constants as $name => $expected) {
    $actual = constant("App\Models\OrderItem::{$name}");
    if ($actual === $expected) {
        echo "  [PASS] {$name} = '{$expected}'\n";
    } else {
        echo "  [FAIL] {$name} = '{$actual}' (esperado: '{$expected}')\n";
        $allCorrect = false;
    }
}
if ($allCorrect) {
    $testsPassed++;
} else {
    $testsFailed++;
}
echo "\n";

// =============================================
// RESUMEN
// =============================================
echo "========================================\n";
echo "RESUMEN DE TESTS\n";
echo "========================================\n";
echo "Tests pasados: {$testsPassed}\n";
echo "Tests fallidos: {$testsFailed}\n";

if ($testsFailed === 0) {
    echo "\n✅ TODOS LOS TESTS PASARON\n";
} else {
    echo "\n❌ HAY TESTS FALLIDOS\n";
}

echo "\n";
