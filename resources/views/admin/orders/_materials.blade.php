{{-- MATERIALES REQUERIDOS / RESERVADOS / CONSUMIDOS --}}
@php
    use App\Models\InventoryReservation;

    $materialsSummary = [];
    $hasReservations = false;
    $hasConsumptions = false;

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
                // Reservas de este pedido
                $reservations = InventoryReservation::where('order_id', $order->id)
                    ->where('material_variant_id', $variantId)
                    ->get();

                $reserved = $reservations->where('status', InventoryReservation::STATUS_RESERVED)->sum('quantity');
                $consumed = $reservations->where('status', InventoryReservation::STATUS_CONSUMED)->sum('quantity');

                if ($reserved > 0) $hasReservations = true;
                if ($consumed > 0) $hasConsumptions = true;

                $materialsSummary[$variantId] = [
                    'material_name' => $materialVariant->material->name ?? 'N/A',
                    'variant_color' => $materialVariant->color,
                    'variant_sku' => $materialVariant->sku,
                    'unit' => $materialVariant->material->consumptionUnit->symbol ?? 'u',
                    'required' => 0,
                    'reserved' => $reserved,
                    'consumed' => $consumed,
                    'current_stock' => $materialVariant->current_stock,
                ];
            }

            $materialsSummary[$variantId]['required'] += $requiredQty;
        }
    }
@endphp

@if(count($materialsSummary) > 0 && ($order->isInProduction() || $order->status === \App\Models\Order::STATUS_CONFIRMED))
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-boxes mr-2"></i> Materiales
                @if($hasConsumptions)
                    <span class="badge badge-success ml-2">Consumidos</span>
                @elseif($hasReservations)
                    <span class="badge badge-warning ml-2">Reservados</span>
                @else
                    <span class="badge badge-secondary ml-2">Requeridos</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size: 13px;">
                <thead class="bg-light">
                    <tr>
                        <th>Material</th>
                        <th>Color/SKU</th>
                        <th class="text-right">Requerido</th>
                        <th class="text-right">Reservado</th>
                        <th class="text-right">Consumido</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialsSummary as $mat)
                        @php
                            $isFullyConsumed = $mat['consumed'] >= $mat['required'];
                            $isFullyReserved = $mat['reserved'] >= $mat['required'];
                        @endphp
                        <tr class="{{ $isFullyConsumed ? 'table-success' : ($isFullyReserved ? 'table-warning' : '') }}">
                            <td><strong>{{ $mat['material_name'] }}</strong></td>
                            <td>
                                @if($mat['variant_color'])
                                    <span class="badge badge-secondary">{{ $mat['variant_color'] }}</span>
                                @endif
                                <code>{{ $mat['variant_sku'] ?? '-' }}</code>
                            </td>
                            <td class="text-right">{{ number_format($mat['required'], 2) }} {{ $mat['unit'] }}</td>
                            <td class="text-right {{ $mat['reserved'] > 0 ? 'text-warning font-weight-bold' : 'text-muted' }}">
                                {{ number_format($mat['reserved'], 2) }}
                            </td>
                            <td class="text-right {{ $mat['consumed'] > 0 ? 'text-success font-weight-bold' : 'text-muted' }}">
                                {{ number_format($mat['consumed'], 2) }}
                            </td>
                            <td class="text-center">
                                @if($isFullyConsumed)
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Consumido</span>
                                @elseif($isFullyReserved)
                                    <span class="badge badge-warning"><i class="fas fa-lock"></i> Reservado</span>
                                @elseif($mat['reserved'] > 0 || $mat['consumed'] > 0)
                                    <span class="badge badge-info">Parcial</span>
                                @else
                                    <span class="badge badge-secondary">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted" style="font-size: 12px;">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Reservado:</strong> Bloqueado para este pedido (no descuenta stock).
            <strong>Consumido:</strong> Descontado del inventario al entregar.
        </div>
    </div>
@endif
