{{-- Partial: Inventario del Pedido (Detalle operativo por producto) --}}
@php
    use App\Models\InventoryReservation;
    use App\Models\Order;

    // Mostrar para estados con contexto de inventario
    $showInventory = in_array($order->status, [
        Order::STATUS_CONFIRMED,
        Order::STATUS_IN_PRODUCTION,
        Order::STATUS_READY,
        Order::STATUS_DELIVERED,
        Order::STATUS_CANCELLED,
    ]) || $order->reservations()->exists();

    // Cargar reservas agrupadas por item (incluir variante del producto para mostrar Talla/Color)
    $itemsWithMaterials = $order->items()
        ->with([
            'product.materials.material',
            'variant.attributeValues.attribute',
            'reservations.materialVariant.material',
        ])
        ->get()
        ->map(function ($item) use ($order) {
            $materials = [];
            $totalMaterialCost = 0;
            $hasCostData = false;

            if ($item->product && $item->product->materials) {
                foreach ($item->product->materials as $variant) {
                    $requiredQty = ($variant->pivot->quantity ?? 0) * $item->quantity;

                    $reservation = $item->reservations
                        ->where('material_variant_id', $variant->id)
                        ->first();

                    // Estado semantico segun contexto
                    $status = 'pending';
                    $statusLabel = 'Pendiente';
                    $statusColor = 'secondary';
                    $consumedAt = null;
                    $consumedBy = null;

                    if ($reservation) {
                        switch ($reservation->status) {
                            case InventoryReservation::STATUS_RESERVED:
                                $status = 'reserved';
                                $statusLabel = 'Reservado';
                                $statusColor = 'info';
                                break;
                            case InventoryReservation::STATUS_CONSUMED:
                                $status = 'consumed';
                                $statusLabel = 'Consumido';
                                $statusColor = 'success';
                                $consumedAt = $reservation->consumed_at;
                                $consumedBy = $reservation->consumer;
                                break;
                            case InventoryReservation::STATUS_RELEASED:
                                $status = 'released';
                                $statusLabel = 'Liberado';
                                $statusColor = 'warning';
                                break;
                        }
                    }

                    // Guardar material_id para agrupar
                    $materialId = $variant->material?->id ?? 0;

                    // Calcular costo de esta linea usando datos existentes del pivot
                    $unitCost = $variant->pivot->unit_cost ?? $variant->average_cost ?? null;
                    $lineCost = null;
                    if ($unitCost !== null && $unitCost > 0) {
                        $lineCost = $unitCost * $requiredQty;
                        $totalMaterialCost += $lineCost;
                        $hasCostData = true;
                    }

                    // Identificador de variante del material (color)
                    $materialColor = $variant->color ?: null;

                    // Obtener Talla/Color del producto (ProductVariant)
                    $productVariant = $item->variant?->attributes_display;

                    // Formato: "ColorMaterial - Talla/Color" o solo "ColorMaterial" si no hay variante de producto
                    $variantIdentifier = $materialColor;
                    if ($materialColor && $productVariant) {
                        $variantIdentifier = $materialColor . ' - ' . $productVariant;
                    } elseif ($productVariant) {
                        $variantIdentifier = $productVariant;
                    }

                    $materials[] = [
                        'variant_id' => $variant->id,
                        'material_id' => $materialId,
                        'material_name' => $variant->material?->name ?? 'N/A',
                        'variant_identifier' => $variantIdentifier,
                        'variant_color' => $variant->color,
                        'product_variant' => $productVariant,
                        'variant_sku' => $variant->sku,
                        'display_name' => $variant->display_name,
                        'required_qty' => $requiredQty,
                        'unit' => $variant->material?->category?->baseUnit?->symbol ?? '',
                        'unit_cost' => $unitCost,
                        'line_cost' => $lineCost,
                        'status' => $status,
                        'status_label' => $statusLabel,
                        'status_color' => $statusColor,
                        'consumed_at' => $consumedAt,
                        'consumed_by' => $consumedBy,
                        'reservation_id' => $reservation?->id,
                    ];
                }
            }

            // Agrupar materiales por material_name (material base)
            $groupedMaterials = collect($materials)->groupBy('material_name')->map(function($variants, $materialName) {
                $groupCost = $variants->sum('line_cost');
                $hasGroupCost = $variants->contains(fn($v) => $v['line_cost'] !== null);

                return [
                    'material_name' => $materialName,
                    'variants' => $variants->values()->all(),
                    'variant_count' => $variants->count(),
                    'total_qty' => $variants->sum('required_qty'),
                    'total_cost' => $hasGroupCost ? $groupCost : null,
                    'unit' => $variants->first()['unit'] ?? '',
                    'all_consumed' => $variants->every(fn($v) => $v['status'] === 'consumed'),
                    'all_reserved' => $variants->every(fn($v) => in_array($v['status'], ['reserved', 'consumed'])),
                ];
            })->values()->all();

            // Obtener variante del producto (Talla/Color) si existe
            $productVariantDisplay = null;
            if ($item->variant) {
                $productVariantDisplay = $item->variant->attributes_display;
            }

            return [
                'item' => $item,
                'product_variant_display' => $productVariantDisplay,
                'materials' => $materials,
                'grouped_materials' => $groupedMaterials,
                'has_materials' => count($materials) > 0,
                'has_multiple_variants' => collect($groupedMaterials)->contains(fn($g) => $g['variant_count'] > 1),
                'total_material_cost' => $hasCostData ? $totalMaterialCost : null,
                'has_cost_data' => $hasCostData,
            ];
        })
        ->filter(fn($data) => $data['has_materials']);

    $hasMaterials = $itemsWithMaterials->isNotEmpty();
    $hasAnyMultipleVariants = $itemsWithMaterials->contains(fn($d) => $d['has_multiple_variants']);

    // Mensaje contextual segun estado (coherencia ERP)
    $inventoryContextMessage = match($order->status) {
        Order::STATUS_CONFIRMED => 'Pendiente: las reservas se crean al iniciar produccion.',
        Order::STATUS_IN_PRODUCTION => 'Materiales reservados para este pedido.',
        Order::STATUS_READY => 'Materiales consumidos del inventario.',
        Order::STATUS_DELIVERED => 'Operacion completada.',
        Order::STATUS_CANCELLED => 'Reservas liberadas por cancelacion.',
        default => null,
    };
@endphp

@if($showInventory && $hasMaterials)
<style>
/* UX Inventario - Tipografia legible y jerarquia visual */
.inventory-product-header {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.inventory-product-header:hover {
    background: #e9ecef;
}
.inventory-product-header.item-all-consumed {
    border-left-color: #28a745;
}
.inventory-product-header.item-all-reserved {
    border-left-color: #17a2b8;
}
.inventory-product-name {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}
.inventory-product-qty {
    font-size: 14px;
    color: #495057;
    font-weight: 500;
}
.inventory-product-variant {
    font-size: 14px;
    color: #007bff;
    font-weight: 600;
    background: #e7f3ff;
    padding: 2px 8px;
    border-radius: 4px;
}
.inventory-product-cost {
    font-size: 13px;
    color: #155724;
    background: #d4edda;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}
.inventory-product-cost.no-cost {
    color: #856404;
    background: #fff3cd;
    font-weight: 500;
}
.inventory-toggle-icon {
    font-size: 14px;
    color: #495057;
    transition: transform 0.2s ease;
    width: 20px;
    text-align: center;
}
.inventory-toggle[aria-expanded="true"] .inventory-toggle-icon {
    transform: rotate(90deg);
}
.inventory-table {
    font-size: 14px;
}
.inventory-table th {
    font-weight: 600;
    color: #212529;
    background: #e9ecef;
    border-bottom: 2px solid #dee2e6;
}
.inventory-table td {
    color: #212529;
    vertical-align: middle;
}
/* Agrupacion por material base */
.material-group-header {
    background: #f0f4f8;
    border-left: 3px solid #6c757d;
}
.material-group-header td {
    font-weight: 600;
    color: #212529;
    padding-top: 10px;
    padding-bottom: 10px;
}
.material-group-header.group-consumed {
    border-left-color: #28a745;
    background: #e8f5e9;
}
.material-group-header.group-reserved {
    border-left-color: #17a2b8;
    background: #e3f2fd;
}
.material-variant-row {
    background: #ffffff;
}
.material-variant-row td:first-child {
    padding-left: 28px;
}
.variant-indicator {
    color: #495057;
    margin-right: 6px;
}
.inventory-material-name {
    font-weight: 600;
    color: #212529;
}
.inventory-material-variant {
    color: #495057;
    font-weight: 500;
}
.variant-full-id {
    font-weight: 600;
    color: #212529;
}
.inventory-qty {
    font-weight: 600;
    color: #212529;
}
.inventory-unit {
    color: #495057;
    font-weight: 500;
}
.inventory-info {
    font-size: 13px;
    color: #495057;
}
.inventory-info-warning {
    color: #856404;
    font-weight: 500;
}
.inventory-context-bar {
    background: #e3f2fd;
    font-size: 14px;
    color: #0d47a1;
}
.inventory-legend {
    font-size: 13px;
}
.inventory-summary {
    font-size: 14px;
    color: #212529;
}
.variant-note {
    background: #fff8e1;
    border-left: 3px solid #ffc107;
    font-size: 13px;
    color: #5d4037;
}
</style>

{{-- 4. INVENTARIO --}}
<div class="card card-section-inventario">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h5 class="mb-0" style="font-size: 16px;">
            <i class="fas fa-boxes mr-2"></i> Inventario del Pedido
            <span class="ml-2 font-weight-normal" style="font-size: 13px; opacity: 0.9;">(detalle por producto)</span>
        </h5>
        <div>
            <button type="button" class="btn btn-sm btn-light px-2" id="btn-expand-all" title="Expandir todo">
                <i class="fas fa-expand-alt"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light px-2" id="btn-collapse-all" title="Colapsar todo">
                <i class="fas fa-compress-alt"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Mensaje contextual de estado --}}
        @if($inventoryContextMessage)
            <div class="px-3 py-2 border-bottom inventory-context-bar">
                <i class="fas fa-info-circle mr-1"></i>
                <span>{{ $inventoryContextMessage }}</span>
            </div>
        @endif

        {{-- Nota explicativa de variantes (solo si hay multiples) --}}
        @if($hasAnyMultipleVariants)
            <div class="px-3 py-2 border-bottom variant-note">
                <i class="fas fa-layer-group mr-1"></i>
                <strong>Nota:</strong> Las cantidades se muestran por tramo/variante operativa. El total logico por material se refleja en el resumen agregado.
            </div>
        @endif

        {{-- Leyenda de estados --}}
        <div class="px-3 py-2 border-bottom bg-light inventory-legend">
            <span class="mr-2"><strong>Estados:</strong></span>
            <span class="badge badge-secondary mr-1">Pendiente</span>
            <span class="badge badge-info mr-1">Reservado</span>
            <span class="badge badge-success mr-1">Consumido</span>
            <span class="badge badge-warning">Liberado</span>
        </div>

        <div class="accordion" id="inventoryAccordion">
            @foreach($itemsWithMaterials as $index => $data)
                @php
                    $item = $data['item'];
                    $productVariantDisplay = $data['product_variant_display'];
                    $groupedMaterials = $data['grouped_materials'];
                    $allMaterials = $data['materials'];
                    $allConsumed = collect($allMaterials)->every(fn($m) => $m['status'] === 'consumed');
                    $allReserved = collect($allMaterials)->every(fn($m) => in_array($m['status'], ['reserved', 'consumed']));
                    $itemStatusClass = $allConsumed ? 'item-all-consumed' : ($allReserved ? 'item-all-reserved' : '');
                    $itemStatusColor = $allConsumed ? 'success' : ($allReserved ? 'info' : 'secondary');
                    $hasVariants = $data['has_multiple_variants'];
                    $totalMaterialCost = $data['total_material_cost'];
                    $hasCostData = $data['has_cost_data'];
                @endphp
                <div class="border-bottom">
                    <div class="inventory-product-header {{ $itemStatusClass }} py-3 px-3" id="heading{{ $index }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center inventory-toggle"
                                    type="button"
                                    data-toggle="collapse"
                                    data-target="#collapse{{ $index }}"
                                    aria-expanded="false"
                                    aria-controls="collapse{{ $index }}">
                                <i class="fas fa-chevron-right inventory-toggle-icon mr-3"></i>
                                <span class="inventory-product-name">{{ $item->product_name }}</span>
                                @if($productVariantDisplay)
                                    <span class="inventory-product-variant ml-2">{{ $productVariantDisplay }}</span>
                                @endif
                                <span class="inventory-product-qty ml-2">(x{{ $item->quantity }})</span>
                            </button>
                            <div class="d-flex align-items-center">
                                {{-- COSTO TOTAL DE MATERIALES --}}
                                @if($hasCostData)
                                    <span class="inventory-product-cost mr-2">
                                        <i class="fas fa-coins mr-1"></i> ${{ number_format($totalMaterialCost, 2) }}
                                    </span>
                                @else
                                    <span class="inventory-product-cost no-cost mr-2">
                                        <i class="fas fa-coins mr-1"></i> No calculado
                                    </span>
                                @endif
                                @if($hasVariants)
                                    <span class="badge badge-light mr-1" style="color: #495057; font-size: 11px;">
                                        <i class="fas fa-code-branch"></i> variantes
                                    </span>
                                @endif
                                <span class="badge badge-{{ $itemStatusColor }}" style="font-size: 13px;">
                                    {{ count($allMaterials) }} material{{ count($allMaterials) > 1 ? 'es' : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div id="collapse{{ $index }}"
                         class="collapse"
                         aria-labelledby="heading{{ $index }}"
                         data-parent="#inventoryAccordion">
                        <div class="px-3 pb-3">
                            @if($hasVariants)
                                <div class="mb-2 px-2 py-1" style="font-size: 13px; color: #5d4037; background: #fff8e1; border-radius: 4px;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong>Variantes del producto:</strong> Los materiales estan agrupados por tipo. Cada fila es un tramo operativo.
                                </div>
                            @endif
                            <table class="table table-bordered mb-0 inventory-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Material</th>
                                        <th style="width: 20%;" class="text-right">Cantidad</th>
                                        <th style="width: 15%;" class="text-center">Estado</th>
                                        <th style="width: 25%;">Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupedMaterials as $group)
                                        @php
                                            $hasMultipleVariants = $group['variant_count'] > 1;
                                            $groupStatusClass = $group['all_consumed'] ? 'group-consumed' : ($group['all_reserved'] ? 'group-reserved' : '');
                                        @endphp

                                        @if($hasMultipleVariants)
                                            {{-- Header del grupo de material --}}
                                            <tr class="material-group-header {{ $groupStatusClass }}">
                                                <td colspan="4">
                                                    <i class="fas fa-cube mr-2" style="color: #495057;"></i>
                                                    {{ $group['material_name'] }}
                                                    <span style="font-weight: normal; color: #495057; margin-left: 8px;">
                                                        ({{ $group['variant_count'] }} variantes · Total: {{ number_format($group['total_qty'], 2) }} {{ $group['unit'] }})
                                                    </span>
                                                </td>
                                            </tr>
                                            {{-- Filas de variantes con identificacion clara --}}
                                            @foreach($group['variants'] as $material)
                                                <tr class="material-variant-row">
                                                    <td>
                                                        <span class="variant-indicator"><i class="fas fa-angle-right"></i></span>
                                                        {{-- IDENTIFICACION CLARA DE VARIANTE --}}
                                                        @if($material['variant_identifier'])
                                                            <span class="variant-full-id">{{ $material['variant_identifier'] }}</span>
                                                        @else
                                                            <span class="inventory-material-variant">Variante #{{ $loop->iteration }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        <span class="inventory-qty">{{ number_format($material['required_qty'], 2) }}</span>
                                                        <span class="inventory-unit ml-1">{{ $material['unit'] }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-{{ $material['status_color'] }}" style="font-size: 13px;">
                                                            {{ $material['status_label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="inventory-info">
                                                        @if($material['consumed_at'])
                                                            {{ $material['consumed_at']->format('d/m/Y H:i') }}
                                                            @if($material['consumed_by'])
                                                                - {{ $material['consumed_by']->name }}
                                                            @endif
                                                        @elseif($material['status'] === 'released')
                                                            <span class="inventory-info-warning">Cancelado</span>
                                                        @elseif($material['status'] === 'pending')
                                                            Espera produccion
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            {{-- Material sin variantes multiples - fila simple con identificacion completa --}}
                                            @php $material = $group['variants'][0]; @endphp
                                            <tr>
                                                <td>
                                                    <span class="inventory-material-name">{{ $material['material_name'] }}</span>
                                                    {{-- Mostrar variante si existe --}}
                                                    @if($material['variant_identifier'])
                                                        <span class="inventory-material-variant"> — {{ $material['variant_identifier'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    <span class="inventory-qty">{{ number_format($material['required_qty'], 2) }}</span>
                                                    <span class="inventory-unit ml-1">{{ $material['unit'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-{{ $material['status_color'] }}" style="font-size: 13px;">
                                                        {{ $material['status_label'] }}
                                                    </span>
                                                </td>
                                                <td class="inventory-info">
                                                    @if($material['consumed_at'])
                                                        {{ $material['consumed_at']->format('d/m/Y H:i') }}
                                                        @if($material['consumed_by'])
                                                            - {{ $material['consumed_by']->name }}
                                                        @endif
                                                    @elseif($material['status'] === 'released')
                                                        <span class="inventory-info-warning">Cancelado</span>
                                                    @elseif($material['status'] === 'pending')
                                                        Espera produccion
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Resumen totales --}}
        @php
            $totalMaterials = $itemsWithMaterials->sum(fn($d) => count($d['materials']));
            $totalReserved = $itemsWithMaterials->sum(fn($d) => collect($d['materials'])->where('status', 'reserved')->count());
            $totalConsumed = $itemsWithMaterials->sum(fn($d) => collect($d['materials'])->where('status', 'consumed')->count());
            $totalPending = $itemsWithMaterials->sum(fn($d) => collect($d['materials'])->where('status', 'pending')->count());
            $grandTotalCost = $itemsWithMaterials->sum(fn($d) => $d['total_material_cost'] ?? 0);
            $hasAnyCost = $itemsWithMaterials->contains(fn($d) => $d['has_cost_data']);
        @endphp
        <div class="px-3 py-2 bg-light border-top inventory-summary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Total:</strong> {{ $totalMaterials }} registros operativos
                    @if($totalPending > 0)
                        <span class="badge badge-secondary ml-2">{{ $totalPending }} pendientes</span>
                    @endif
                    @if($totalReserved > 0)
                        <span class="badge badge-info ml-2">{{ $totalReserved }} reservados</span>
                    @endif
                    @if($totalConsumed > 0)
                        <span class="badge badge-success ml-2">{{ $totalConsumed }} consumidos</span>
                    @endif
                </div>
                @if($hasAnyCost)
                    <div>
                        <strong style="color: #155724;">Costo Total Materiales: ${{ number_format($grandTotalCost, 2) }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($hasMaterials)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Expandir todo
    document.getElementById('btn-expand-all')?.addEventListener('click', function() {
        document.querySelectorAll('#inventoryAccordion .collapse').forEach(function(el) {
            $(el).collapse('show');
        });
    });

    // Colapsar todo
    document.getElementById('btn-collapse-all')?.addEventListener('click', function() {
        document.querySelectorAll('#inventoryAccordion .collapse').forEach(function(el) {
            $(el).collapse('hide');
        });
    });

    // Click en header completo para expandir/colapsar
    document.querySelectorAll('.inventory-product-header').forEach(function(header) {
        header.addEventListener('click', function(e) {
            if (!e.target.closest('.inventory-toggle')) {
                var btn = header.querySelector('.inventory-toggle');
                if (btn) btn.click();
            }
        });
    });
});
</script>
@endif
@endif
