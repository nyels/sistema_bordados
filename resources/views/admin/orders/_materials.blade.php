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

    $materialsSummary = [];
    $hasReservations = false;
    $hasConsumptions = false;

    if ($showMaterialsSummary) {
        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product || !$product->relationLoaded('materials')) {
                $product?->load('materials');
            }
            if (!$product) continue;

            foreach ($product->materials as $materialVariant) {
                $requiredQty = $materialVariant->pivot->quantity * $item->quantity;
                $variantId = $materialVariant->id;

                if (!isset($materialsSummary[$variantId])) {
                    $reservations = InventoryReservation::where('order_id', $order->id)
                        ->where('material_variant_id', $variantId)
                        ->get();

                    $reserved = $reservations->where('status', InventoryReservation::STATUS_RESERVED)->sum('quantity');
                    $consumed = $reservations->where('status', InventoryReservation::STATUS_CONSUMED)->sum('quantity');

                    if ($reserved > 0) $hasReservations = true;
                    if ($consumed > 0) $hasConsumptions = true;

                    // Identificador de variante: solo COLOR
                    $variantLabel = $materialVariant->color ?: null;

                    $materialsSummary[$variantId] = [
                        'material_name' => $materialVariant->material->name ?? 'N/A',
                        'variant_label' => $variantLabel,
                        'variant_color' => $materialVariant->color,
                        'variant_sku' => $materialVariant->sku,
                        'unit' => $materialVariant->material->consumptionUnit->symbol ?? 'u',
                        'required' => 0,
                        'reserved' => $reserved,
                        'consumed' => $consumed,
                    ];
                }

                $materialsSummary[$variantId]['required'] += $requiredQty;
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
@endphp

@if($showMaterialsSummary && count($materialsSummary) > 0)
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
                        <th class="text-center" style="width: 90px; color: #212529;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialsSummary as $mat)
                        @php
                            $isFullyConsumed = $mat['consumed'] >= $mat['required'];
                            $isFullyReserved = $mat['reserved'] >= $mat['required'];
                        @endphp
                        <tr>
                            <td>
                                <strong style="color: #212529;">{{ $mat['material_name'] }}</strong>
                                @if($mat['variant_label'])
                                    <span style="color: #495057;"> — {{ $mat['variant_label'] }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span style="color: #212529; font-weight: 600;">{{ number_format($mat['required'], 2) }}</span>
                                <small style="color: #495057;">{{ $mat['unit'] }}</small>
                            </td>
                            <td class="text-right {{ $mat['reserved'] > 0 ? 'text-info font-weight-bold' : '' }}" style="{{ $mat['reserved'] == 0 ? 'color: #495057;' : '' }}">
                                {{ number_format($mat['reserved'], 2) }}
                            </td>
                            <td class="text-right {{ $mat['consumed'] > 0 ? 'text-success font-weight-bold' : '' }}" style="{{ $mat['consumed'] == 0 ? 'color: #495057;' : '' }}">
                                {{ number_format($mat['consumed'], 2) }}
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
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2" style="font-size: 14px; background: #f8f9fa;">
            <i class="fas fa-layer-group mr-1" style="color: #495057;"></i>
            <span style="color: #495057;">
                Este es un resumen agregado. El detalle por producto y variante esta en "Inventario del Pedido".
            </span>
        </div>
    </div>
@endif
