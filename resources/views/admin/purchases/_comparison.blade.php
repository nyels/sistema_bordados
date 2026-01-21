{{-- TABLA COMPARATIVA: PEDIDO VS RECIBIDO --}}
@if (in_array($purchase->status->value, ['pendiente', 'parcial', 'recibido']))
    @php
        $items = $purchase->items;
        $totalOrdered = $items->sum('quantity');
        $totalReceived = $items->sum('quantity_received');
        $percentage = $totalOrdered > 0 ? min(100, ($totalReceived / $totalOrdered) * 100) : 0;
    @endphp

    <div class="card card-outline card-info mt-3">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-balance-scale mr-2"></i>
                Comparativa: Pedido vs Recibido
            </h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0" style="font-size: 13px;">
                <thead class="bg-light">
                    <tr>
                        <th>Material</th>
                        <th>Color/SKU</th>
                        <th class="text-center">Pedido</th>
                        <th class="text-center">Recibido</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Impacto Inventario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php
                            $ordered = $item->quantity;
                            $received = $item->quantity_received;
                            $difference = $received - $ordered;
                            $isComplete = $item->is_fully_received;
                            $isPartial = $received > 0 && $received < $ordered;
                            $isPending = $received == 0;
                            $isOver = $received > $ordered;

                            // Impacto en inventario (cantidad en unidad base que entró)
                            $inventoryImpact = $item->converted_quantity_received ?? ($received * ($item->conversion_factor ?? 1));
                        @endphp
                        <tr class="{{ $isComplete ? 'table-success' : ($isPartial ? 'table-warning' : '') }}">
                            <td>
                                <strong>{{ $item->materialVariant->material->name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                @if($item->materialVariant->color)
                                    <span class="badge badge-secondary">{{ $item->materialVariant->color }}</span>
                                @endif
                                <code>{{ $item->materialVariant->sku ?? '-' }}</code>
                            </td>
                            <td class="text-center">
                                {{ number_format($ordered, 2) }}
                                <small class="text-muted">{{ $item->unit->symbol ?? '' }}</small>
                            </td>
                            <td class="text-center font-weight-bold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-muted') }}">
                                {{ number_format($received, 2) }}
                                <small class="text-muted">{{ $item->unit->symbol ?? '' }}</small>
                            </td>
                            <td class="text-center font-weight-bold {{ $difference > 0 ? 'text-success' : ($difference < 0 ? 'text-danger' : 'text-muted') }}">
                                @if($difference == 0)
                                    <span class="text-success"><i class="fas fa-check"></i></span>
                                @elseif($difference > 0)
                                    <span class="text-success">+{{ number_format($difference, 2) }}</span>
                                @else
                                    <span class="text-danger">{{ number_format($difference, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isComplete)
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Completo</span>
                                @elseif($isOver)
                                    <span class="badge badge-info"><i class="fas fa-plus"></i> Excedente</span>
                                @elseif($isPartial)
                                    <span class="badge badge-warning">
                                        {{ number_format(($received / $ordered) * 100, 0) }}% Parcial
                                    </span>
                                @else
                                    <span class="badge badge-secondary">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($received > 0)
                                    <span class="text-success">
                                        <i class="fas fa-arrow-up"></i>
                                        +{{ number_format($inventoryImpact, 2) }}
                                        {{ $item->materialVariant->material->consumptionUnit->symbol ?? '' }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="2" class="text-right">TOTALES:</td>
                        <td class="text-center">{{ number_format($totalOrdered, 2) }}</td>
                        <td class="text-center">{{ number_format($totalReceived, 2) }}</td>
                        <td class="text-center {{ ($totalReceived - $totalOrdered) >= 0 ? 'text-success' : 'text-danger' }}">
                            @if($totalReceived - $totalOrdered >= 0)
                                @if($totalReceived - $totalOrdered == 0)
                                    <i class="fas fa-check text-success"></i>
                                @else
                                    +{{ number_format($totalReceived - $totalOrdered, 2) }}
                                @endif
                            @else
                                {{ number_format($totalReceived - $totalOrdered, 2) }}
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $percentage >= 100 ? 'success' : ($percentage > 0 ? 'warning' : 'secondary') }}">
                                {{ number_format($percentage, 0) }}%
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer text-muted" style="font-size: 12px;">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Diferencia:</strong> Positiva = recibió más de lo pedido. Negativa = falta por recibir.
            <strong>Impacto:</strong> Cantidad agregada al inventario (en unidad base de consumo).
        </div>
    </div>
@endif
