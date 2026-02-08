{{-- Partial: Tabla de pedidos (para AJAX) - Solo la tabla, los filtros están en index --}}
<div class="card-body table-responsive p-0">
    <table id="ordersTable" class="table table-hover table-striped mb-0" style="font-size: 16px;">
        <thead style="background: #343a40; color: white;">
            <tr>
                <th class="text-center align-middle" style="color: white;">Fecha</th>
                <th class="text-center align-middle" style="color: white;">Prioridad</th>
                <th class="text-center align-middle" style="color: white;">Cliente</th>
                <th class="text-center align-middle" style="color: white;">Num. Pedido</th>
                <th class="text-center align-middle" style="color: white;">Tipo</th>
                <th class="text-center align-middle" style="color: white;">Items</th>
                <th class="text-center align-middle" style="color: white;">Total</th>
                <th class="text-center align-middle" style="color: white;">Pago</th>
                <th class="text-center align-middle" style="color: white;">Estado</th>
                <th class="text-center align-middle" style="color: white;">Entrega</th>
                <th class="text-center align-middle" style="color: white;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @php
                    $isDelayed =
                        $order->promised_date &&
                        $order->promised_date->lt(now()->startOfDay()) &&
                        !in_array($order->status, [
                            \App\Models\Order::STATUS_DELIVERED,
                            \App\Models\Order::STATUS_CANCELLED,
                        ]);

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
                <tr class="{{ $isBlocked || $hasInventoryBlock ? 'table-warning' : ($isDelayed ? 'table-danger' : '') }}">
                    <td class="text-center align-middle" style="color: #212529; white-space: nowrap;">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge badge-{{ $order->urgency_color }}" style="font-size: 16px;">
                            {{ $order->urgency_label }}
                        </span>
                    </td>
                    <td class="text-center align-middle" style="color: #212529;">
                        @if($order->cliente)
                            {{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}
                        @else
                            <span style="color: #212529;"><i class="fas fa-warehouse mr-1"></i> Stock</span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-weight-bold" style="font-size: 16px;">
                            {{ $order->order_number }}
                        </a>
                        @if ($order->isAnnex())
                            <span class="badge badge-info ml-1" title="Pedido Anexo de {{ $order->parentOrder?->order_number }}">
                                <i class="fas fa-link"></i> Anexo
                            </span>
                        @endif
                        @if ($order->isPostSale())
                            <span class="badge badge-purple ml-1" style="background: #6f42c1; color: white;"
                                title="Post-venta de {{ $order->relatedOrder?->order_number }}">
                                <i class="fas fa-plus"></i> {{ $order->relatedOrder?->order_number }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($order->isCustomOrder())
                            <span style="font-size: 16px; color: #212529;" title="Pedido con personalización (diseño, texto o medidas)">
                                <i class="fas fa-palette"></i> Personalizado
                            </span>
                        @else
                            <span style="font-size: 16px; color: #212529;" title="Producto estándar sin personalización">
                                <i class="fas fa-box"></i> Estándar
                            </span>
                        @endif
                    </td>
                    <td class="text-center align-middle font-weight-bold" style="color: #212529;">
                        {{ $order->items->count() }}
                    </td>
                    <td class="text-center align-middle font-weight-bold" style="font-size: 17px;">
                        ${{ number_format($order->total, 2) }}
                    </td>
                    <td class="text-center align-middle">
                        @if($order->isStockProduction())
                            <span style="font-size: 14px; color: #212529;">—</span>
                        @else
                            <span class="badge badge-{{ $order->payment_status_color }}" style="font-size: 16px;">
                                {{ $order->payment_status_label }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($isBlocked)
                            {{-- CONFIRMADO BLOQUEADO (REGLAS R2-R5) --}}
                            <span class="badge badge-danger" style="font-size: 16px; cursor: help;"
                                title="BLOQUEADO: {{ implode('. ', $blockerReasons) }}">
                                {{ $order->status_label }} <i class="fas fa-ban ml-1"></i>
                            </span>
                        @elseif($hasInventoryBlock)
                            {{-- CONFIRMADO CON BLOQUEO POR INVENTARIO (INTENTO PREVIO FALLIDO) --}}
                            <span class="badge badge-warning text-dark" style="font-size: 16px; cursor: help;"
                                title="INVENTARIO INSUFICIENTE: {{ $order->getLastProductionBlockReason() }}">
                                Falta Material <i class="fas fa-boxes ml-1"></i>
                            </span>
                        @elseif($isConfirmed)
                            {{-- CONFIRMADO OK --}}
                            <span class="badge badge-primary" style="font-size: 16px; cursor: help;"
                                title="Listo para producción. El inventario se valida al iniciar.">
                                {{ $order->status_label }} <i class="fas fa-check ml-1"></i>
                            </span>
                        @else
                            {{-- OTROS ESTADOS --}}
                            @php
                                $statusConfig = match ($order->status) {
                                    \App\Models\Order::STATUS_DRAFT => [
                                        'class' => 'badge-secondary',
                                        'title' => 'Pedido en captura',
                                    ],
                                    \App\Models\Order::STATUS_IN_PRODUCTION => [
                                        'class' => '',
                                        'style' => 'background-color: #6610f2; color: white;',
                                        'title' => 'Inventario reservado. Producción en curso.',
                                    ],
                                    \App\Models\Order::STATUS_READY => [
                                        'class' => 'badge-success',
                                        'title' => 'Producción finalizada. Listo para entrega.',
                                    ],
                                    \App\Models\Order::STATUS_DELIVERED => [
                                        'class' => 'badge-dark',
                                        'title' => 'Pedido entregado al cliente.',
                                    ],
                                    \App\Models\Order::STATUS_CANCELLED => [
                                        'class' => 'badge-danger',
                                        'title' => 'Pedido cancelado.',
                                    ],
                                    default => [
                                        'class' => 'badge-secondary',
                                        'title' => '',
                                    ],
                                };
                            @endphp
                            <span class="badge {{ $statusConfig['class'] }}"
                                style="font-size: 16px; cursor: help; {{ $statusConfig['style'] ?? '' }}"
                                title="{{ $statusConfig['title'] }}">
                                {{ $order->status_label }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($order->promised_date)
                            @if ($isDelayed)
                                <span class="text-danger font-weight-bold">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $order->promised_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span style="color: #212529;">{{ $order->promised_date->format('d/m/Y') }}</span>
                            @endif
                        @else
                            <span style="color: #212529;">—</span>
                        @endif
                    </td>
                    <td class="text-left align-middle text-nowrap">
                        {{-- ACCIÓN CONTEXTUAL --}}
                        @if ($isBlocked)
                            <a href="{{ route('admin.orders.show', $order) }}#blockers-section"
                                class="btn btn-sm btn-danger"
                                title="Ver bloqueos: {{ implode(', ', $blockerReasons) }}">
                                <i class="fas fa-search"></i> Bloqueos
                            </a>
                        @elseif($hasInventoryBlock)
                            <a href="{{ route('admin.orders.show', $order) }}#blockers-section"
                                class="btn btn-sm btn-warning" title="Ver bloqueo por inventario insuficiente">
                                <i class="fas fa-search"></i> Ver Bloqueo
                            </a>
                        @else
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                        @endif

                        {{-- Botón confirmar rápido: Solo DRAFT --}}
                        @if ($order->status === \App\Models\Order::STATUS_DRAFT)
                            <button type="button" class="btn btn-sm btn-primary btn-quick-confirm"
                                data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}"
                                title="Confirmar pedido">
                                <i class="fas fa-check"></i>
                            </button>
                        @endif

                        {{-- Botón pago rápido: Solo ventas (NO stock_production), en CONFIRMED, IN_PRODUCTION o READY --}}
                        @if ($order->balance > 0 && !$order->isStockProduction() && in_array($order->status, [\App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_IN_PRODUCTION, \App\Models\Order::STATUS_READY]))
                            <button type="button" class="btn btn-sm btn-success btn-quick-payment"
                                data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}"
                                data-balance="{{ $order->balance }}" title="Registrar Pago">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                        @endif

                        {{-- Botón entrega rápida: READY + Pagado --}}
                        @if ($order->status === \App\Models\Order::STATUS_READY && $order->payment_status === \App\Models\Order::PAYMENT_PAID)
                            <button type="button" class="btn btn-sm btn-primary btn-quick-delivery"
                                data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}"
                                title="Registrar Entrega">
                                <i class="fas fa-truck"></i>
                            </button>
                        @endif

                        @if ($order->status === \App\Models\Order::STATUS_DRAFT)
                            <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        @endif
                        {{-- POST-VENTA: Solo en READY o DELIVERED --}}
                        @if ($order->canHavePostSale())
                            <a href="{{ route('admin.orders.create', ['related_to' => $order->order_number]) }}"
                                class="btn btn-sm btn-outline-purple"
                                style="border-color: #6f42c1; color: #6f42c1;"
                                title="Crear pedido post-venta relacionado con {{ $order->order_number }}">
                                <i class="fas fa-plus"></i> Post-venta
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-4" style="color: #212529; font-size: 15px;">
                        No hay pedidos que coincidan con los filtros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($orders->hasPages())
    <div class="card-footer">
        {{ $orders->withQueryString()->links() }}
    </div>
@endif

{{-- LEYENDA VISUAL --}}
<div class="card-footer py-2 border-top" style="background: #f8f9fa;">
    <div class="d-flex flex-wrap align-items-center" style="gap: 20px; font-size: 15px;">
        <span style="color: #212529;"><strong>Leyenda:</strong></span>
        <span>
            <span class="badge badge-secondary" style="font-size: 14px;">Borrador</span>
        </span>
        <span>
            <span class="badge badge-primary" style="font-size: 14px;">Confirmado <i class="fas fa-check ml-1"></i></span>
        </span>
        <span>
            <span class="badge" style="background-color: #6610f2; color: white; font-size: 14px;">En Producción</span>
        </span>
        <span>
            <span class="badge badge-success" style="font-size: 14px;">Listo</span>
        </span>
        <span>
            <span class="badge badge-dark" style="font-size: 14px;">Entregado</span>
        </span>
        <span style="color: #212529;">|</span>
        <span>
            <span class="badge badge-danger" style="font-size: 14px;"><i class="fas fa-ban"></i></span>
            Bloqueado
        </span>
        <span>
            <span class="badge badge-warning text-dark" style="font-size: 14px;"><i class="fas fa-boxes"></i></span>
            Falta Material
        </span>
        <span style="color: #212529;">|</span>
        <span>
            <i class="fas fa-square text-warning mr-1"></i> Fila bloqueada
        </span>
        <span>
            <i class="fas fa-square text-danger mr-1"></i> Fila retrasada
        </span>
        <span>
            <span class="btn btn-sm btn-outline-purple"
                style="border-color: #6f42c1; color: #6f42c1; cursor: default; font-size: 14px;">
                <i class="fas fa-plus"></i> Post-venta
            </span>
        </span>
    </div>
</div>
