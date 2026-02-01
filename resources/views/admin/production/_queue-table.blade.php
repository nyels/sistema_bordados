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
                    <th data-toggle="tooltip" data-placement="top" title="Producto/Diseño del pedido">Diseño / Linaje</th>
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
                        <td>
                            @if($order->cliente)
                                {{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}
                            @else
                                <span style="color: #212529;"><i class="fas fa-warehouse mr-1"></i> Stock</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}?from=queue" class="font-weight-bold">
                                {{ $order->order_number }}
                            </a>
                            @if ($order->isAnnex())
                                <span class="badge badge-info ml-1" title="Pedido Anexo"><i
                                        class="fas fa-link"></i></span>
                            @endif
                        </td>
                        <td>
                            @php
                                $firstItem = $order->items->first();
                            @endphp
                            @if($firstItem)
                                <div class="font-weight-bold" style="color: #111827;">
                                    {{ $firstItem->product_name }}
                                </div>
                                @if($order->items->count() > 1)
                                    <small style="color: #6b7280;">
                                        +{{ $order->items->count() - 1 }} más
                                    </small>
                                @endif
                            @else
                                <span style="color: #6b7280;">-</span>
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
                                <span style="color: #212529;">-</span>
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
                                        {{ $sufficient->count() }}
                                    </span>
                                @endif
                                <button type="button" class="btn btn-xs btn-outline-info ml-1" data-toggle="modal"
                                    data-target="#materialsModal{{ $order->id }}" title="Ver detalle de materiales">
                                    <i class="fas fa-eye"></i>
                                </button>
                            @else
                                <span style="color: #212529;">Sin materiales</span>
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
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            @else
                                <span style="color: #212529;">-</span>
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
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                    data-target="#modalProductosQueue{{ $order->id }}"
                                    title="Marcar productos como terminados">
                                    <i class="fas fa-check"></i> Listo
                                </button>
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
                        <td colspan="10" class="text-center py-4 text-muted">
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
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="max-height: 90vh;">
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-boxes mr-2"></i>
                            Materiales Requeridos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-3" style="overflow-y: auto;">
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
                                                <small style="color: #212529;">{{ $mat['variant_sku'] ?? '-' }}</small>
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
                            <small style="color: #212529;">
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

    {{-- MODAL DE PRODUCTOS PARA MARCAR LISTO --}}
    @if ($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
        <div class="modal fade" id="modalProductosQueue{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-tasks mr-2"></i>Productos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3" style="font-size: 14px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Marque cada producto como <strong>Terminado</strong> conforme se complete su producción.
                        </div>

                        {{-- Barra de progreso --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size: 14px; font-weight: 600;">Progreso de Producción</span>
                                <span class="progress-label-{{ $order->id }}" style="font-size: 14px; font-weight: 600;">
                                    {{ $order->items->where('production_completed', true)->count() }} / {{ $order->items->count() }}
                                </span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                @php
                                    $completedCount = $order->items->where('production_completed', true)->count();
                                    $totalCount = $order->items->count();
                                    $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                                @endphp
                                <div class="progress-bar-{{ $order->id }} progress-bar bg-success" role="progressbar"
                                    style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- Lista de productos --}}
                        <div class="list-group lista-productos-{{ $order->id }}">
                            @foreach($order->items as $item)
                                @php
                                    $isCompleted = $item->production_completed ?? false;
                                @endphp
                                <div class="list-group-item d-flex justify-content-between align-items-center producto-item-queue {{ $isCompleted ? 'bg-light' : '' }}"
                                     data-item-id="{{ $item->id }}"
                                     data-order-id="{{ $order->id }}"
                                     data-completed="{{ $isCompleted ? '1' : '0' }}">
                                    <div>
                                        <strong style="font-size: 15px;">{{ $item->product->name ?? $item->product_name ?? 'Producto' }}</strong>
                                        <br>
                                        <span style="font-size: 13px; color: #6c757d;">
                                            Cantidad: <strong>{{ $item->quantity }}</strong>
                                            @if($item->product && $item->product->category)
                                                &bull; {{ $item->product->category->name }}
                                            @endif
                                        </span>
                                    </div>
                                    <div>
                                        @if($isCompleted)
                                            <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                                                <i class="fas fa-check mr-1"></i> Terminado
                                            </span>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-sm btn-marcar-terminado-queue"
                                                    data-item-id="{{ $item->id }}"
                                                    data-order-id="{{ $order->id }}">
                                                <i class="fas fa-check mr-1"></i> Terminado
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST"
                              class="d-inline form-finalizar-{{ $order->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="ready">
                            <button type="submit" class="btn btn-success btn-finalizar-{{ $order->id }}"
                                    {{ $completedCount < $totalCount ? 'disabled' : '' }}>
                                <i class="fas fa-box-open mr-1"></i> Finalizar Pedido
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- JAVASCRIPT PARA MODALES DE PRODUCTOS --}}
{{-- v2.2: reverseButtons + reinit forms --}}
<script>
(function() {
    'use strict';
    // Evitar múltiples inicializaciones de event listeners
    if (window.queueProductosInitialized) return;
    window.queueProductosInitialized = true;

    // Manejar clicks en botones "Terminado" de la cola
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-marcar-terminado-queue');
        if (!btn) return;

        const itemId = btn.dataset.itemId;
        const orderId = btn.dataset.orderId;
        const itemEl = btn.closest('.producto-item-queue');

        // Deshabilitar botón mientras procesa
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';

        // AJAX para marcar como terminado
        fetch('/admin/orders/' + orderId + '/mark-item-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ item_id: itemId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar UI del item
                itemEl.dataset.completed = '1';
                itemEl.classList.add('bg-light');
                btn.outerHTML = '<span class="badge badge-success px-3 py-2" style="font-size: 14px;"><i class="fas fa-check mr-1"></i> Terminado</span>';

                // Actualizar barra de progreso
                updateQueueProgress(orderId);

                // Toast de éxito
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    Toast.fire({ icon: 'success', title: 'Producto marcado como terminado' });
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terminado';
                alert(data.error || 'Error al marcar producto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terminado';
            alert('Error de conexión');
        });
    });

    // Función para actualizar progreso
    function updateQueueProgress(orderId) {
        const lista = document.querySelector('.lista-productos-' + orderId);
        if (!lista) return;

        const items = lista.querySelectorAll('.producto-item-queue');
        const completados = lista.querySelectorAll('.producto-item-queue[data-completed="1"]').length;
        const total = items.length;
        const porcentaje = total > 0 ? Math.round((completados / total) * 100) : 0;

        // Actualizar barra
        const progressBar = document.querySelector('.progress-bar-' + orderId);
        if (progressBar) {
            progressBar.style.width = porcentaje + '%';
            progressBar.setAttribute('aria-valuenow', porcentaje);

            if (completados === total) {
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('bg-primary', 'progress-bar-striped', 'progress-bar-animated');
            }
        }

        // Actualizar label
        const progressLabel = document.querySelector('.progress-label-' + orderId);
        if (progressLabel) {
            progressLabel.textContent = completados + ' / ' + total;
        }

        // Habilitar/deshabilitar botón finalizar
        const btnFinalizar = document.querySelector('.btn-finalizar-' + orderId);
        if (btnFinalizar) {
            btnFinalizar.disabled = (completados < total);
        }
    }

    // Confirmación al finalizar pedido (Event Delegation para contenido AJAX)
    document.addEventListener('submit', function(e) {
        var form = e.target;
        // Verificar si es un formulario de finalización
        if (!form.className || !form.className.includes('form-finalizar-')) return;

        e.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Finalizar pedido?',
                html: '<p>Esta acción:</p><ul style="text-align:left;"><li>Consume los materiales reservados del inventario</li><li>Marca el pedido como <strong>LISTO</strong></li></ul>',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Sí, finalizar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            if (confirm('¿Finalizar el pedido? Esto consumirá los materiales del inventario.')) {
                form.submit();
            }
        }
    });

    // Actualizar progreso al abrir modal
    document.querySelectorAll('[id^="modalProductosQueue"]').forEach(function(modal) {
        $(modal).on('shown.bs.modal', function() {
            const orderId = this.id.replace('modalProductosQueue', '');
            updateQueueProgress(orderId);
        });
    });
})();
</script>
