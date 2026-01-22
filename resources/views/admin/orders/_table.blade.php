{{-- Partial: Tabla de pedidos (para AJAX) --}}
<div class="card mb-0">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0" style="font-size: 15px;">
            <thead style="background: #343a40; color: white;">
                <tr>
                    <th style="color: white;">Pedido</th>
                    <th style="color: white;">Cliente</th>
                    <th style="color: white;">Items</th>
                    <th class="text-right" style="color: white;">Total</th>
                    <th class="text-center" style="color: white;">Estado</th>
                    <th class="text-center" style="color: white;">Pago</th>
                    <th style="color: white;">Entrega</th>
                    <th class="text-right" style="color: white;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $isDelayed = $order->promised_date &&
                                     $order->promised_date->lt(now()->startOfDay()) &&
                                     !in_array($order->status, [\App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_CANCELLED]);

                        // === BLOQUEO: Usar método canónico del modelo ===
                        $isConfirmed = $order->status === \App\Models\Order::STATUS_CONFIRMED;
                        $isBlocked = $isConfirmed && !$order->canStartProduction();

                        // === BLOQUEO POR INVENTARIO (PERSISTIDO) ===
                        $hasInventoryBlock = $isConfirmed && $order->hasProductionInventoryBlock();

                        // Obtener razones solo si está bloqueado (máx 2 para tooltip)
                        $blockerReasons = [];
                        if ($isBlocked || $hasInventoryBlock) {
                            $blockers = $order->getProductionBlockers();
                            $blockerReasons = array_slice(array_column($blockers, 'message'), 0, 2);
                        }
                    @endphp
                    <tr class="{{ ($isBlocked || $hasInventoryBlock) ? 'table-warning' : ($isDelayed ? 'table-danger' : '') }}">
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-weight-bold" style="font-size: 15px;">
                                {{ $order->order_number }}
                            </a>
                            @if($order->isAnnex())
                                <span class="badge badge-info ml-1" title="Pedido Anexo">
                                    <i class="fas fa-link"></i>
                                </span>
                            @endif
                            @if($order->urgency_level !== 'normal')
                                <span class="badge badge-{{ $order->urgency_color }} ml-1">
                                    {{ $order->urgency_label }}
                                </span>
                            @endif
                        </td>
                        <td style="color: #212529;">{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</td>
                        <td style="color: #212529;">{{ $order->items->count() }}</td>
                        <td class="text-right font-weight-bold" style="font-size: 16px;">${{ number_format($order->total, 2) }}</td>
                        <td class="text-center">
                            @if($isBlocked)
                                {{-- CONFIRMADO BLOQUEADO (REGLAS R2-R5) --}}
                                <span class="badge badge-danger" style="font-size: 13px; cursor: help;"
                                      title="BLOQUEADO: {{ implode('. ', $blockerReasons) }}">
                                    {{ $order->status_label }} <i class="fas fa-ban ml-1"></i>
                                </span>
                            @elseif($hasInventoryBlock)
                                {{-- CONFIRMADO CON BLOQUEO POR INVENTARIO (INTENTO PREVIO FALLIDO) --}}
                                <span class="badge badge-warning text-dark" style="font-size: 13px; cursor: help;"
                                      title="INVENTARIO INSUFICIENTE: {{ $order->getLastProductionBlockReason() }}">
                                    {{ $order->status_label }} <i class="fas fa-boxes ml-1"></i>
                                </span>
                            @elseif($isConfirmed)
                                {{-- CONFIRMADO OK --}}
                                <span class="badge badge-success" style="font-size: 13px; cursor: help;"
                                      title="Listo para producción. El inventario se valida al iniciar.">
                                    {{ $order->status_label }} <i class="fas fa-check ml-1"></i>
                                </span>
                            @else
                                {{-- OTROS ESTADOS --}}
                                @php
                                    $statusTooltip = match($order->status) {
                                        \App\Models\Order::STATUS_DRAFT => 'Pedido en captura',
                                        \App\Models\Order::STATUS_IN_PRODUCTION => 'Inventario reservado. Producción en curso.',
                                        \App\Models\Order::STATUS_READY => 'Producción finalizada. Listo para entrega.',
                                        \App\Models\Order::STATUS_DELIVERED => 'Pedido entregado al cliente.',
                                        \App\Models\Order::STATUS_CANCELLED => 'Pedido cancelado.',
                                        default => '',
                                    };
                                @endphp
                                <span class="badge badge-{{ $order->status_color }}" style="font-size: 13px; cursor: help;" title="{{ $statusTooltip }}">
                                    {{ $order->status_label }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $order->payment_status_color }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </td>
                        <td>
                            @if($order->promised_date)
                                @if($isDelayed)
                                    <span class="text-danger font-weight-bold">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $order->promised_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span style="color: #212529;">{{ $order->promised_date->format('d/m/Y') }}</span>
                                @endif
                            @else
                                <span style="color: #495057;">—</span>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            @if($order->balance > 0 && $order->status !== \App\Models\Order::STATUS_CANCELLED)
                                <button type="button"
                                        class="btn btn-sm btn-success btn-quick-payment"
                                        data-order-id="{{ $order->id }}"
                                        data-order-number="{{ $order->order_number }}"
                                        data-balance="{{ $order->balance }}"
                                        title="Registrar Pago">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                            @endif
                            @if($order->status === \App\Models\Order::STATUS_DRAFT)
                                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                            {{-- ACCIÓN CONTEXTUAL --}}
                            @if($isBlocked)
                                <a href="{{ route('admin.orders.show', $order) }}#blockers-section"
                                   class="btn btn-sm btn-danger"
                                   title="Ver bloqueos: {{ implode(', ', $blockerReasons) }}">
                                    <i class="fas fa-search"></i> Bloqueos
                                </a>
                            @elseif($hasInventoryBlock)
                                <a href="{{ route('admin.orders.show', $order) }}#blockers-section"
                                   class="btn btn-sm btn-warning"
                                   title="Ver bloqueo por inventario insuficiente">
                                    <i class="fas fa-search"></i> Ver Bloqueo
                                </a>
                            @else
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4" style="color: #495057; font-size: 15px;">
                            No hay pedidos que coincidan con los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->withQueryString()->links() }}
        </div>
    @endif
    {{-- LEYENDA VISUAL --}}
    <div class="card-footer py-2 border-top" style="background: #f8f9fa;">
        <div class="d-flex flex-wrap align-items-center" style="gap: 20px; font-size: 13px;">
            <span class="text-muted"><strong>Leyenda:</strong></span>
            <span>
                <span class="badge badge-success" style="font-size: 11px;">Confirmado <i class="fas fa-check"></i></span>
                Listo para producir
            </span>
            <span>
                <span class="badge badge-danger" style="font-size: 11px;">Confirmado <i class="fas fa-ban"></i></span>
                Bloqueado (ver detalle)
            </span>
            <span>
                <span class="badge badge-warning text-dark" style="font-size: 11px;">Confirmado <i class="fas fa-boxes"></i></span>
                Inventario insuficiente
            </span>
            <span>
                <i class="fas fa-square text-warning mr-1"></i> Fila bloqueada
            </span>
            <span>
                <i class="fas fa-square text-danger mr-1"></i> Fila retrasada
            </span>
        </div>
    </div>
</div>
