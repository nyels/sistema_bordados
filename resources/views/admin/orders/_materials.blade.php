{{-- RESUMEN AGREGADO DE MATERIALES (Vista consolidada) --}}
@php
    use App\Models\InventoryReservation;
    use App\Models\Order;

    // SIEMPRE mostrar en estados con materiales (incluyendo delivered)
    $showMaterialsSummary = in_array($order->status, [
        Order::STATUS_DRAFT,
        Order::STATUS_CONFIRMED,
        Order::STATUS_IN_PRODUCTION,
        Order::STATUS_READY,
        Order::STATUS_DELIVERED,
    ]);

    // Titulo dinamico segun estado
    $isHistorical = $order->status === Order::STATUS_DELIVERED;
    $sectionTitle = $isHistorical ? 'Resumen Historico de Materiales' : 'Resumen de Materiales';

    $bomMaterialsSummary = [];
    $extraMaterialsSummary = [];
    $hasReservations = false;
    $hasConsumptions = false;

    if ($showMaterialsSummary) {
        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product || !$product->relationLoaded('materials')) {
                $product?->load('materials.material.consumptionUnit', 'materials.material.baseUnit');
            }

            // Cargar ajustes de BOM si no están cargados
            if (!$item->relationLoaded('bomAdjustments')) {
                $item->load('bomAdjustments');
            }

            // Cargar extras del item si no están cargados
            if (!$item->relationLoaded('extras')) {
                $item->load('extras.productExtra.materials.material.consumptionUnit', 'extras.productExtra.materials.material.baseUnit');
            }

            if (!$product) continue;

            // Indexar ajustes por material_variant_id para búsqueda rápida
            $bomAdjustments = $item->bomAdjustments->keyBy('material_variant_id');

            // === MATERIALES DEL PRODUCTO BASE (BOM) ===
            foreach ($product->materials as $materialVariant) {
                $variantId = $materialVariant->id;

                // Usar cantidad ajustada si existe, sino usar BOM base
                $baseQty = (float) $materialVariant->pivot->quantity;
                $adjustment = $bomAdjustments->get($variantId);
                $adjustedQty = $adjustment ? (float) $adjustment->adjusted_quantity : $baseQty;
                $hasAdjustment = $adjustment && $adjustment->hasChange();

                $requiredQty = $adjustedQty * $item->quantity;

                if (!isset($bomMaterialsSummary[$variantId])) {
                    // Asegurar que la relación material con sus unidades esté cargada
                    if (!$materialVariant->relationLoaded('material')) {
                        $materialVariant->load('material.consumptionUnit', 'material.baseUnit');
                    } elseif ($materialVariant->material) {
                        if (!$materialVariant->material->relationLoaded('consumptionUnit')) {
                            $materialVariant->material->load('consumptionUnit');
                        }
                        if (!$materialVariant->material->relationLoaded('baseUnit')) {
                            $materialVariant->material->load('baseUnit');
                        }
                    }

                    $reservations = InventoryReservation::where('order_id', $order->id)
                        ->where('material_variant_id', $variantId)
                        ->get();

                    $reserved = $reservations->where('status', InventoryReservation::STATUS_RESERVED)->sum('quantity');
                    $consumed = $reservations->where('status', InventoryReservation::STATUS_CONSUMED)->sum('quantity');

                    if ($reserved > 0) $hasReservations = true;
                    if ($consumed > 0) $hasConsumptions = true;

                    // Identificador de variante: solo COLOR
                    $variantLabel = $materialVariant->color ?: null;

                    // Obtener unidad de consumo del material (consumptionUnit, si no existe usar baseUnit)
                    $material = $materialVariant->material;
                    $consumptionUnitSymbol = $material?->consumptionUnit?->symbol
                        ?? $material?->baseUnit?->symbol
                        ?? 'u';

                    $bomMaterialsSummary[$variantId] = [
                        'material_name' => $materialVariant->material->name ?? 'N/A',
                        'variant_label' => $variantLabel,
                        'variant_color' => $materialVariant->color,
                        'variant_sku' => $materialVariant->sku,
                        'unit' => $consumptionUnitSymbol,
                        'required' => 0,
                        'reserved' => $reserved,
                        'consumed' => $consumed,
                        'has_adjustment' => false,
                    ];
                }

                $bomMaterialsSummary[$variantId]['required'] += $requiredQty;
                if ($hasAdjustment) {
                    $bomMaterialsSummary[$variantId]['has_adjustment'] = true;
                }
            }

            // === MATERIALES DE EXTRAS CON INVENTARIO ===
            foreach ($item->extras as $orderItemExtra) {
                $extra = $orderItemExtra->productExtra;

                if (!$extra || !$extra->consumesInventory()) {
                    continue;
                }

                // Cargar materiales del extra si no están cargados
                if (!$extra->relationLoaded('materials')) {
                    $extra->load('materials.material.consumptionUnit', 'materials.material.baseUnit');
                }

                $totalExtraQuantity = $orderItemExtra->quantity * $item->quantity;

                foreach ($extra->materials as $materialVariant) {
                    $variantId = $materialVariant->id;
                    $requiredQty = (float) $materialVariant->pivot->quantity * $totalExtraQuantity;

                    if (!isset($extraMaterialsSummary[$variantId])) {
                        // Asegurar que la relación material con sus unidades esté cargada
                        if (!$materialVariant->relationLoaded('material')) {
                            $materialVariant->load('material.consumptionUnit', 'material.baseUnit');
                        }

                        $reservations = InventoryReservation::where('order_id', $order->id)
                            ->where('material_variant_id', $variantId)
                            ->get();

                        $reserved = $reservations->where('status', InventoryReservation::STATUS_RESERVED)->sum('quantity');
                        $consumed = $reservations->where('status', InventoryReservation::STATUS_CONSUMED)->sum('quantity');

                        if ($reserved > 0) $hasReservations = true;
                        if ($consumed > 0) $hasConsumptions = true;

                        $variantLabel = $materialVariant->color ?: null;
                        $material = $materialVariant->material;
                        $consumptionUnitSymbol = $material?->consumptionUnit?->symbol
                            ?? $material?->baseUnit?->symbol
                            ?? 'u';

                        $extraMaterialsSummary[$variantId] = [
                            'material_name' => $materialVariant->material->name ?? 'N/A',
                            'variant_label' => $variantLabel,
                            'variant_color' => $materialVariant->color,
                            'variant_sku' => $materialVariant->sku,
                            'unit' => $consumptionUnitSymbol,
                            'required' => 0,
                            'reserved' => $reserved,
                            'consumed' => $consumed,
                            'extra_name' => $extra->name,
                        ];
                    }

                    $extraMaterialsSummary[$variantId]['required'] += $requiredQty;
                }
            }
        }
    }

    // Determinar mensaje contextual segun estado
    $stateExplanation = match($order->status) {
        Order::STATUS_DRAFT => 'Vista previa. Los materiales se reservaran al confirmar e iniciar produccion.',
        Order::STATUS_CONFIRMED => 'Los materiales se reservaran al iniciar produccion.',
        Order::STATUS_IN_PRODUCTION => 'Los materiales estan reservados. Se consumiran al marcar listo.',
        Order::STATUS_READY => 'Materiales consumidos del inventario.',
        Order::STATUS_DELIVERED => 'Registro historico de materiales utilizados en este pedido.',
        default => '',
    };

    // Color del header segun estado
    $headerBgClass = $isHistorical ? 'bg-dark' : 'bg-secondary';

    // Ordenar materiales alfabéticamente por nombre
    uasort($bomMaterialsSummary, fn($a, $b) => strcasecmp($a['material_name'], $b['material_name']));
    uasort($extraMaterialsSummary, fn($a, $b) => strcasecmp($a['material_name'], $b['material_name']));

    $totalMaterials = count($bomMaterialsSummary) + count($extraMaterialsSummary);
@endphp

@if($showMaterialsSummary && $totalMaterials > 0)
    {{-- 5. RESUMEN MATERIALES --}}
    <div class="card card-section-materiales">
        <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="font-size: 15px;">
                    @if($isHistorical)
                        <i class="fas fa-history mr-2"></i>
                    @else
                        <i class="fas fa-layer-group mr-2"></i>
                    @endif
                    {{ $sectionTitle }}
                    <span class="ml-2 font-weight-normal" style="font-size: 14px;">(agregado)</span>
                </h6>
                @if($hasConsumptions)
                    <span class="badge badge-light text-success">Consumidos</span>
                @elseif($hasReservations)
                    <span class="badge badge-light text-info">Reservados</span>
                @else
                    <span class="badge badge-light text-secondary">Pendientes</span>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($stateExplanation)
                <div class="px-3 py-2 border-bottom" style="font-size: 14px; background: #e8f4fd;">
                    <i class="fas fa-info-circle text-primary mr-1"></i>
                    <span style="color: #0d47a1;">{{ $stateExplanation }}</span>
                </div>
            @endif
            <table class="table table-sm mb-0" style="font-size: 15px;">
                <thead style="background: #e9ecef;">
                    <tr>
                        <th style="color: #212529;">Material</th>
                        <th class="text-right" style="color: #212529;">Requerido</th>
                        <th class="text-right" style="color: #212529;">Reservado</th>
                        <th class="text-right" style="color: #212529;">Consumido</th>
                        <th class="text-center" style="width: 60px; color: #212529;"></th>
                    </tr>
                </thead>
                <tbody>
                    {{-- ======================== MATERIALES DEL BOM ======================== --}}
                    @if(count($bomMaterialsSummary) > 0)
                        <tr style="background: #e3f2fd;">
                            <td colspan="5" class="py-2">
                                <strong style="color: #1565c0;">
                                    <i class="fas fa-layer-group mr-1"></i> Materiales del Producto (BOM)
                                </strong>
                            </td>
                        </tr>
                        @foreach($bomMaterialsSummary as $mat)
                            @php
                                $isFullyConsumed = $mat['consumed'] >= $mat['required'];
                                $isFullyReserved = $mat['reserved'] >= $mat['required'];
                            @endphp
                            <tr>
                                <td>
                                    <strong style="color: #212529;">{{ $mat['material_name'] }}</strong>
                                    @if($mat['variant_label'])
                                        <span style="color: #212529;"> — {{ $mat['variant_label'] }}</span>
                                    @endif
                                    @if(!empty($mat['has_adjustment']))
                                        <span class="badge badge-info ml-1" title="Cantidad ajustada según medidas del cliente">
                                            <i class="fas fa-ruler"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span style="color: #212529; font-weight: 600;">{{ number_format($mat['required'], 2) }}</span>
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-right {{ $mat['reserved'] > 0 ? 'text-info font-weight-bold' : '' }}" style="{{ $mat['reserved'] == 0 ? 'color: #212529;' : '' }}">
                                    {{ number_format($mat['reserved'], 2) }}
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-right {{ $mat['consumed'] > 0 ? 'text-success font-weight-bold' : '' }}" style="{{ $mat['consumed'] == 0 ? 'color: #212529;' : '' }}">
                                    {{ number_format($mat['consumed'], 2) }}
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-center">
                                    @if($isFullyConsumed)
                                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    @elseif($isFullyReserved)
                                        <span class="badge badge-info"><i class="fas fa-lock"></i></span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-clock"></i></span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- ======================== MATERIALES DE EXTRAS ======================== --}}
                    @if(count($extraMaterialsSummary) > 0)
                        <tr style="background: #e1f5fe;">
                            <td colspan="5" class="py-2">
                                <strong style="color: #0277bd;">
                                    <i class="fas fa-plus-circle mr-1"></i> Materiales de Extras
                                </strong>
                            </td>
                        </tr>
                        @foreach($extraMaterialsSummary as $mat)
                            @php
                                $isFullyConsumed = $mat['consumed'] >= $mat['required'];
                                $isFullyReserved = $mat['reserved'] >= $mat['required'];
                            @endphp
                            <tr>
                                <td>
                                    <strong style="color: #212529;">{{ $mat['material_name'] }}</strong>
                                    @if($mat['variant_label'])
                                        <span style="color: #212529;"> — {{ $mat['variant_label'] }}</span>
                                    @endif
                                    @if(!empty($mat['extra_name']))
                                        <br><small style="color: #0277bd;">
                                            <i class="fas fa-tag mr-1"></i>{{ $mat['extra_name'] }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span style="color: #212529; font-weight: 600;">{{ number_format($mat['required'], 2) }}</span>
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-right {{ $mat['reserved'] > 0 ? 'text-info font-weight-bold' : '' }}" style="{{ $mat['reserved'] == 0 ? 'color: #212529;' : '' }}">
                                    {{ number_format($mat['reserved'], 2) }}
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-right {{ $mat['consumed'] > 0 ? 'text-success font-weight-bold' : '' }}" style="{{ $mat['consumed'] == 0 ? 'color: #212529;' : '' }}">
                                    {{ number_format($mat['consumed'], 2) }}
                                    <small style="color: #212529;">{{ $mat['unit'] }}</small>
                                </td>
                                <td class="text-center">
                                    @if($isFullyConsumed)
                                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    @elseif($isFullyReserved)
                                        <span class="badge badge-info"><i class="fas fa-lock"></i></span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-clock"></i></span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2" style="font-size: 14px; background: #f8f9fa;">
            <i class="fas fa-layer-group mr-1" style="color: #212529;"></i>
            <span style="color: #212529;">
                Este es un resumen agregado. El detalle por producto y variante esta en "Inventario del Pedido".
            </span>
        </div>
    </div>
@endif
