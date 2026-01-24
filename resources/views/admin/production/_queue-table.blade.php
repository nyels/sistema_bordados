{{-- TABLA DE COLA --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table id="queueTable" class="table table-hover table-striped mb-0" style="font-size: 16px;">
            <thead class="bg-dark text-white">
                <tr>
                    <th class="text-center" data-toggle="tooltip" data-placement="top"
                        title="Fecha en que se creó el pedido">
                        Fecha Creado</th>
                    <th class="text-center" data-toggle="tooltip" data-placement="top" title="Normal, Urgente, Express">
                        Prioridad</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center" data-toggle="tooltip" data-placement="top"
                        title="Fecha prometida al cliente.">Fecha
                        Compromiso
                    </th>
                    <th class="text-center" data-toggle="tooltip" data-placement="top"
                        title="Disponibilidad de materiales">Materiales
                    </th>
                    <th class="text-center" data-toggle="tooltip" data-placement="top"
                        title="Motivos que impiden iniciar produccion">Bloqueos</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $isOverdue = $order->promised_date && $order->promised_date->lt(now()->startOfDay());
                        $isUrgent = in_array($order->urgency_level, ['urgente', 'express']);

                        // === ESTADOS BASICOS ===
                        $isConfirmed = $order->status === \App\Models\Order::STATUS_CONFIRMED;

                        // === BLOQUEOS ===
                        // Usar lógica existente de la vista
                        $hasBlockers = $order->has_blockers;
                        $hasInventoryBlock =
                            $order->status === \App\Models\Order::STATUS_CONFIRMED &&
                            $order->hasProductionInventoryBlock();

                        // Normalizar variables para coincidir con lógica de Orders Index
                        $isBlocked = $hasBlockers;

                        // Obtener razones solo si está bloqueado (máx 2 para tooltip)
                        $blockerReasons = [];
                        if ($isBlocked) {
                            $blockerReasons = $order->blocker_reasons; // Ya es un array en este controller/vista
                        }

                        $canStartProduction = $order->status === \App\Models\Order::STATUS_CONFIRMED && !$hasBlockers;
                    @endphp
                    <tr
                        class="{{ $hasBlockers ? 'table-warning' : ($isOverdue ? 'table-danger' : ($isUrgent ? 'table-info' : '')) }}">
                        <td class="text-center align-middle">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-{{ $order->urgency_color }}" style="font-size: 16px;">
                                {{ $order->urgency_label }}
                            </span>
                        </td>
                        <td>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}?from=queue" class="font-weight-bold">
                                {{ $order->order_number }}
                            </a>
                            @if ($order->isAnnex())
                                <span class="badge badge-info ml-1" title="Pedido Anexo"><i
                                        class="fas fa-link"></i></span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($isBlocked)
                                {{-- CONFIRMADO BLOQUEADO --}}
                                <span class="badge badge-danger" style="font-size: 16px; cursor: help;"
                                    title="BLOQUEADO: {{ implode('. ', $blockerReasons) }}">
                                    Falta Material <i class="fas fa-ban ml-1"></i>
                                </span>
                            @elseif($hasInventoryBlock)
                                {{-- CONFIRMADO CON BLOQUEO POR INVENTARIO --}}
                                <span class="badge badge-warning text-dark" style="font-size: 16px; cursor: help;"
                                    title="INVENTARIO INSUFICIENTE: {{ $order->getLastProductionBlockReason() }}">
                                    Falta Material <i class="fas fa-boxes ml-1"></i>
                                </span>
                            @elseif($isConfirmed)
                                {{-- CONFIRMADO OK --}}
                                <span class="badge badge-primary" style="font-size: 16px; cursor: help;"
                                    title="Listo para producción. El inventario se espera disponible.">
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

                        <td class="text-center">
                            @if ($order->promised_date)
                                <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $order->promised_date->format('d/m/Y') }}
                                </span>
                                @if ($isOverdue)
                                    <span class="badge badge-danger ml-1">RETRASADO</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if (count($order->material_requirements) > 0)
                                @php
                                    $insufficient = collect($order->material_requirements)->filter(
                                        fn($m) => !$m['sufficient'],
                                    );
                                    $sufficient = collect($order->material_requirements)->filter(
                                        fn($m) => $m['sufficient'],
                                    );
                                @endphp
                                @if ($insufficient->count() > 0)
                                    <span class="text-danger"
                                        title="{{ $insufficient->pluck('material_name')->implode(', ') }}">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ $insufficient->count() }} insuficiente(s)
                                    </span>
                                @else
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i>
                                        {{ $sufficient->count() }} OK
                                    </span>
                                @endif
                                <button type="button" class="btn btn-xs btn-outline-info ml-1" data-toggle="modal"
                                    data-target="#materialsModal{{ $order->id }}" title="Ver detalle de materiales">
                                    <i class="fas fa-eye"></i>
                                </button>
                            @else
                                <span class="text-muted">Sin materiales</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($hasBlockers)
                                <button type="button" class="btn btn-xs btn-danger" data-toggle="modal"
                                    data-target="#blockersModal{{ $order->id }}" title="Ver motivos de bloqueo">
                                    <i class="fas fa-ban"></i> {{ count($order->blocker_reasons) }}
                                </button>
                            @elseif($hasInventoryBlock)
                                <span class="badge badge-warning text-dark" style="cursor: help;"
                                    title="{{ $order->getLastProductionBlockReason() }}">
                                    <i class="fas fa-boxes"></i> Inventario
                                </span>
                            @elseif($order->status === \App\Models\Order::STATUS_CONFIRMED)
                                <span class="text-success" title="Listo para iniciar produccion">
                                    <i class="fas fa-check-circle"></i> OK
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-left d-flex justify-content-start align-items-center gap-1">
                            <a href="{{ route('admin.orders.show', $order) }}?from=queue"
                                class="btn btn-sm btn-info mr-1" title="Ver pedido">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if ($canStartProduction)
                                <form action="{{ route('admin.production.queue.start', $order) }}" method="POST"
                                    class="d-inline" data-confirm="produccion"
                                    data-confirm-title="¿Iniciar producción de {{ $order->order_number }}?"
                                    data-confirm-text="Se reservarán los materiales necesarios para este pedido."
                                    data-confirm-impact="Los materiales quedarán bloqueados hasta completar o cancelar el pedido.">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-play"></i> Iniciar
                                    </button>
                                </form>
                            @elseif($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
                                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST"
                                    class="d-inline" data-confirm="listo"
                                    data-confirm-title="¿Marcar {{ $order->order_number }} como listo?"
                                    data-confirm-text="El pedido pasará a estado 'Listo para entregar'."
                                    data-confirm-impact="El cliente podrá recoger su pedido.">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="ready">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Listo
                                    </button>
                                </form>
                            @elseif($hasBlockers)
                                <a href="{{ route('admin.orders.show', $order) }}?from=queue#blockers-section"
                                    class="btn btn-sm btn-warning"
                                    title="Ver motivos de bloqueo y acciones requeridas">
                                    <i class="fas fa-search"></i> Ver Bloqueos
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0">No hay pedidos en cola de produccion</p>
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
</div>

{{-- Modal de conversiones de unidades --}}
@include('partials._unit-conversion-modal')

{{-- MODALES DE MATERIALES --}}
@foreach ($orders as $order)
    @if (count($order->material_requirements) > 0)
        <div class="modal fade" id="materialsModal{{ $order->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-boxes mr-2"></i>
                            Materiales Requeridos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-3">
                        {{-- Toggle de unidades --}}
                        <div class="d-flex justify-content-end mb-2">
                            <div class="btn-group btn-group-sm unit-toggle" role="group">
                                <button type="button" class="btn btn-primary active" data-unit-mode="consumption">
                                    Consumo
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-unit-mode="base">
                                    Compra
                                </button>
                            </div>
                        </div>
                        <table class="table table-sm table-striped mb-0 materials-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>Material</th>
                                    <th>Color</th>
                                    <th class="text-center">Requerido</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Reservado</th>
                                    <th class="text-center">Stock Disponible</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->material_requirements as $mat)
                                    <tr class="{{ !$mat['sufficient'] ? 'table-danger' : '' }}">
                                        <td><strong>{{ $mat['material_name'] }}</strong></td>
                                        <td>
                                            @if ($mat['variant_color'])
                                                <span class="badge badge-secondary">{{ $mat['variant_color'] }}</span>
                                                <small class="text-muted d-block"
                                                    style="line-height: 1.2;">{{ $mat['variant_sku'] ?? '' }}</small>
                                            @else
                                                <small class="text-muted">{{ $mat['variant_sku'] ?? '-' }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold unit-convertible"
                                            data-material-id="{{ $mat['material_id'] ?? 0 }}"
                                            data-material-name="{{ $mat['material_name'] ?? 'N/A' }}"
                                            data-qty="{{ $mat['required'] }}"
                                            data-factor="{{ $mat['conversion_factor'] ?? 1 }}"
                                            data-unit-consumption="{{ $mat['unit'] }}"
                                            data-unit-base="{{ $mat['unit_base'] ?? $mat['unit'] }}">
                                            <span
                                                class="qty-value font-weight-bold">{{ number_format($mat['required'], 2) }}</span>
                                            <span class="unit-symbol font-weight-bold ml-1">{{ $mat['unit'] }}</span>
                                        </td>
                                        <td class="text-center unit-convertible"
                                            data-material-id="{{ $mat['material_id'] ?? 0 }}"
                                            data-material-name="{{ $mat['material_name'] ?? 'N/A' }}"
                                            data-qty="{{ $mat['current_stock'] }}"
                                            data-factor="{{ $mat['conversion_factor'] ?? 1 }}"
                                            data-unit-consumption="{{ $mat['unit'] }}"
                                            data-unit-base="{{ $mat['unit_base'] ?? $mat['unit'] }}">
                                            <span
                                                class="qty-value font-weight-bold">{{ number_format($mat['current_stock'], 2) }}</span>
                                            <span class="unit-symbol font-weight-bold ml-1">{{ $mat['unit'] }}</span>
                                        </td>
                                        <td class="text-center unit-convertible"
                                            data-material-id="{{ $mat['material_id'] ?? 0 }}"
                                            data-material-name="{{ $mat['material_name'] ?? 'N/A' }}"
                                            data-qty="{{ $mat['total_reserved'] }}"
                                            data-factor="{{ $mat['conversion_factor'] ?? 1 }}"
                                            data-unit-consumption="{{ $mat['unit'] }}"
                                            data-unit-base="{{ $mat['unit_base'] ?? $mat['unit'] }}"
                                            data-reserved-this="{{ $mat['reserved_for_this'] }}">
                                            <span
                                                class="qty-value font-weight-bold">{{ number_format($mat['total_reserved'], 2) }}</span>
                                            <span class="unit-symbol font-weight-bold ml-1">{{ $mat['unit'] }}</span>
                                            @if ($mat['reserved_for_this'] > 0)
                                                <br><small class="text-success reserved-this-text">
                                                    (<span
                                                        class="reserved-this-qty font-weight-bold">{{ number_format($mat['reserved_for_this'], 2) }}</span>
                                                    <span
                                                        class="unit-symbol font-weight-bold">{{ $mat['unit'] }}</span>
                                                    este pedido)
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center unit-convertible {{ $mat['available'] < $mat['needed'] ? 'text-danger font-weight-bold' : 'text-success' }}"
                                            data-material-id="{{ $mat['material_id'] ?? 0 }}"
                                            data-material-name="{{ $mat['material_name'] ?? 'N/A' }}"
                                            data-qty="{{ $mat['available'] }}"
                                            data-factor="{{ $mat['conversion_factor'] ?? 1 }}"
                                            data-unit-consumption="{{ $mat['unit'] }}"
                                            data-unit-base="{{ $mat['unit_base'] ?? $mat['unit'] }}">
                                            <span
                                                class="qty-value font-weight-bold">{{ number_format($mat['available'], 2) }}</span>
                                            <span class="unit-symbol font-weight-bold ml-1">{{ $mat['unit'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($mat['reserved_for_this'] >= $mat['required'])
                                                <span class="badge badge-success">Reservado</span>
                                            @elseif($mat['sufficient'])
                                                <span class="badge badge-info">Disponible</span>
                                            @else
                                                <span class="badge badge-danger unit-convertible-badge"
                                                    data-material-id="{{ $mat['material_id'] ?? 0 }}"
                                                    data-material-name="{{ $mat['material_name'] ?? 'N/A' }}"
                                                    data-qty="{{ $mat['needed'] - $mat['available'] }}"
                                                    data-factor="{{ $mat['conversion_factor'] ?? 1 }}"
                                                    data-unit-consumption="{{ $mat['unit'] }}"
                                                    data-unit-base="{{ $mat['unit_base'] ?? $mat['unit'] }}">
                                                    Faltan <span
                                                        class="qty-value font-weight-bold">{{ number_format($mat['needed'] - $mat['available'], 2) }}</span>
                                                    <span class="unit-symbol">{{ $mat['unit'] }}</span>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <div class="mr-auto">
                            <small class="text-muted">
                                <strong>Stock:</strong> Cantidad en almacen |
                                <strong>Reservado:</strong> Bloqueado para otros pedidos |
                                <strong>Stock Disponible:</strong> Stock - Reservado
                            </small>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE BLOQUEOS --}}
    @if ($order->has_blockers && count($order->blocker_reasons) > 0)
        <div class="modal fade" id="blockersModal{{ $order->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-ban mr-2"></i>
                            Bloqueos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Este pedido no puede iniciar produccion por los siguientes motivos:
                        </p>
                        <ul class="list-group">
                            @foreach ($order->blocker_reasons as $reason)
                                <li class="list-group-item list-group-item-danger">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ $reason }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('admin.orders.show', $order) }}?from=queue#blockers-section"
                            class="btn btn-warning">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Detalle del Pedido
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
