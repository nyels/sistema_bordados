{{-- TABLA DE COLA --}}
<div class="card">
    <div class="card-body table-responsive p-0">
        <table id="queueTable" class="table table-hover table-striped mb-0" style="font-size: 15px;">
            <thead class="bg-dark text-white">
                <tr>
                    <th class="text-center" style="width: 90px;">Fecha</th>
                    <th class="text-center" style="width: 80px;">Prioridad</th>
                    <th class="text-center" style="min-width: 120px;">Cliente</th>
                    <th class="text-center" style="width: 120px;">Pedido</th>
                    <th class="text-center" style="min-width: 130px;">Productos</th>
                    <th class="text-center" style="width: 110px;">Estado</th>
                    <th class="text-center" style="width: 100px;">Compromiso</th>
                    <th class="text-center" style="width: 100px;">Materiales</th>
                    <th class="text-center" style="width: 80px;">Bloqueos</th>
                    <th class="text-center" style="width: 100px;">Acciones</th>
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
                        <td class="text-center align-middle">
                            @if($order->cliente)
                                {{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}
                            @else
                                <span style="color: #212529;"><i class="fas fa-warehouse mr-1"></i> Stock</span>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <a href="{{ route('admin.orders.show', $order) }}?from=queue" class="font-weight-bold">
                                {{ $order->order_number }}
                            </a>
                            @if ($order->isAnnex())
                                <span class="badge badge-info ml-1" title="Pedido Anexo"><i
                                        class="fas fa-link"></i></span>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            @php
                                $itemsCount = $order->items->count();
                                $totalPiezas = $order->items->sum('quantity');
                                $hasExtras = $order->items->contains(fn($i) => $i->extras && $i->extras->count() > 0);
                                $completedItemsTable = $order->items->where('production_completed', true)->count();
                                $progressPercentTable = $itemsCount > 0 ? round(($completedItemsTable / $itemsCount) * 100) : 0;
                            @endphp
                            @if($itemsCount > 0)
                                <a href="#" data-toggle="modal" data-target="#modalProductosDetalle{{ $order->id }}"
                                   style="color: #1565c0; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-box mr-1"></i>
                                    {{ $itemsCount }} {{ $itemsCount === 1 ? 'producto' : 'productos' }}
                                    <span style="color: #6b7280; font-weight: 500;">· {{ $totalPiezas }} pz</span>
                                    @if($hasExtras)
                                        <i class="fas fa-plus-circle ml-1" style="color: #0277bd; font-size: 12px;" title="Con extras"></i>
                                    @endif
                                </a>
                                {{-- Barra de progreso de producción --}}
                                @if($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
                                    <div class="mt-2">
                                        <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 12px; color: #495057; font-weight: 600;">
                                            <span class="progress-label-table-{{ $order->id }}">{{ $completedItemsTable }} / {{ $itemsCount }}</span>
                                            <span class="progress-percent-table-{{ $order->id }}">{{ $progressPercentTable }}%</span>
                                        </div>
                                        <div class="progress" style="height: 12px; border-radius: 6px; background: #e9ecef;">
                                            <div class="progress-bar progress-bar-table-{{ $order->id }} {{ $completedItemsTable === $itemsCount ? 'bg-success' : 'bg-primary' }}"
                                                 role="progressbar"
                                                 style="width: {{ $progressPercentTable }}%; border-radius: 6px;"
                                                 aria-valuenow="{{ $progressPercentTable }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span style="color: #6b7280;">-</span>
                            @endif
                        </td>
                        <td class="text-center align-middle">
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

                        <td class="text-center align-middle">
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
                        <td class="text-center align-middle">
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
                        <td class="text-center align-middle">
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
                        <td class="text-center align-middle">
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
                                    <button type="submit" class="btn btn-sm btn-success" title="Iniciar producción">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </form>
                            @elseif($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                    data-target="#modalProductosQueue{{ $order->id }}"
                                    title="Marcar productos como terminados">
                                    <i class="fas fa-check"></i>
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

    {{-- Paginación --}}
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
        @php
            // Separar materiales del BOM y de extras
            $bomMaterials = collect($order->material_requirements)->filter(fn($m) => ($m['source_type'] ?? 'bom') === 'bom');
            $extraMaterials = collect($order->material_requirements)->filter(fn($m) => ($m['source_type'] ?? 'bom') === 'extra');
        @endphp
        <div class="modal fade" id="materialsModal{{ $order->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-boxes mr-2"></i>
                            Materiales Requeridos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>

                    {{-- BARRA FIJA: Toggle + Encabezados de tabla --}}
                    <div style="background: #fff; border-bottom: 2px solid #dee2e6; flex-shrink: 0;">
                        {{-- Toggle de unidades --}}
                        <div class="d-flex justify-content-end p-2" style="background: #f8f9fa;">
                            <div class="btn-group btn-group-sm unit-toggle" role="group">
                                <button type="button" class="btn btn-primary active" data-unit-mode="consumption">
                                    Consumo
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-unit-mode="base">
                                    Compra
                                </button>
                            </div>
                        </div>
                        {{-- Encabezados de tabla fijos --}}
                        <table class="table table-sm mb-0 materials-table" style="table-layout: fixed;">
                            <thead>
                                <tr style="background: #e9ecef;">
                                    <th style="width: 22%;">Material</th>
                                    <th style="width: 12%;">Color</th>
                                    <th class="text-center" style="width: 13%;">Requerido</th>
                                    <th class="text-center" style="width: 13%;">Stock</th>
                                    <th class="text-center" style="width: 13%;">Reservado</th>
                                    <th class="text-center" style="width: 13%;">Stock Disp.</th>
                                    <th class="text-center" style="width: 14%;">Estado</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- CUERPO CON SCROLL --}}
                    <div class="modal-body p-0" style="overflow-y: auto; flex: 1;">
                        {{-- TABLA UNIFICADA DE MATERIALES (sin thead) --}}
                        <table class="table table-sm table-striped mb-0 materials-table" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 22%;">
                                <col style="width: 12%;">
                                <col style="width: 13%;">
                                <col style="width: 13%;">
                                <col style="width: 13%;">
                                <col style="width: 13%;">
                                <col style="width: 14%;">
                            </colgroup>
                            <tbody>
                                {{-- ======================== MATERIALES DEL BOM ======================== --}}
                                @if($bomMaterials->count() > 0)
                                <tr style="background: #e3f2fd;">
                                    <td colspan="7" class="py-2">
                                        <strong style="color: #1565c0;">
                                            <i class="fas fa-layer-group mr-1"></i> Materiales del Producto (BOM)
                                        </strong>
                                    </td>
                                </tr>
                                @foreach ($bomMaterials as $mat)
                                    <tr class="{{ !$mat['sufficient'] ? 'table-danger' : '' }}">
                                        <td>
                                            <strong>{{ $mat['material_name'] }}</strong>
                                            @if(!empty($mat['has_bom_adjustment']))
                                                <span class="badge badge-info ml-1" title="Cantidad ajustada según medidas del cliente">
                                                    <i class="fas fa-ruler"></i> Ajustado
                                                </span>
                                            @endif
                                        </td>
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
                                @endif

                                {{-- ======================== MATERIALES DE EXTRAS ======================== --}}
                                @if($extraMaterials->count() > 0)
                                <tr style="background: #e1f5fe;">
                                    <td colspan="7" class="py-2">
                                        <strong style="color: #0277bd;">
                                            <i class="fas fa-plus-circle mr-1"></i> Materiales de Extras
                                        </strong>
                                    </td>
                                </tr>
                                @foreach ($extraMaterials as $mat)
                                    <tr class="{{ !$mat['sufficient'] ? 'table-danger' : '' }}">
                                        <td>
                                            <strong>{{ $mat['material_name'] }}</strong>
                                            @if(!empty($mat['extra_name']))
                                                <br><small style="color: #0277bd;">
                                                    <i class="fas fa-tag mr-1"></i>{{ $mat['extra_name'] }}
                                                </small>
                                            @endif
                                        </td>
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
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer" style="flex-shrink: 0;">
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

    {{-- MODAL DETALLE DE PRODUCTOS --}}
    <div class="modal fade" id="modalProductosDetalle{{ $order->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                {{-- Header con info del pedido --}}
                <div class="modal-header py-3" style="background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); border: none;">
                    <div class="d-flex align-items-center w-100">
                        <div class="mr-3">
                            <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clipboard-list text-white" style="font-size: 22px;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title text-white mb-0 font-weight-bold">{{ $order->order_number }}</h5>
                            <small class="text-white-50">
                                @if($order->cliente)
                                    <i class="fas fa-user mr-1"></i>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}
                                @else
                                    <i class="fas fa-warehouse mr-1"></i>Produccion para Stock
                                @endif
                            </small>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-{{ $order->urgency_color }}" style="font-size: 13px; padding: 6px 12px;">
                                {{ $order->urgency_label }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="close text-white ml-2" data-dismiss="modal" style="opacity: 0.8;">&times;</button>
                </div>

                {{-- Body con tabla de productos --}}
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-center" style="font-size: 15px;">
                            <thead style="background: #37474f; color: white;">
                                <tr>
                                    <th class="align-middle text-center" style="width: 60px;"></th>
                                    <th class="align-middle text-center">Producto</th>
                                    <th class="align-middle text-center" style="width: 90px;">Cantidad</th>
                                    <th class="align-middle text-center">Medidas</th>
                                    <th class="align-middle text-center">Extras</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    @php
                                        $product = $item->product;
                                        $productVariant = $item->productVariant;
                                        $productImage = $product?->primary_image_url;
                                        $measurements = is_array($item->measurements) ? $item->measurements : [];
                                        $hasMeasurements = !empty($measurements) && count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0' && $v !== 'save_to_client')) > 0;
                                        $extrasCount = $item->extras ? $item->extras->count() : 0;
                                        $measurementLabels = [
                                            'busto' => 'Busto', 'cintura' => 'Cintura', 'cadera' => 'Cadera',
                                            'largo' => 'Largo', 'largo_vestido' => 'L. Vestido', 'alto_cintura' => 'A. Cintura',
                                        ];
                                        // Atributos de variante
                                        $variantAttributes = [];
                                        if ($productVariant && $productVariant->attributeValues) {
                                            foreach ($productVariant->attributeValues as $attrValue) {
                                                $variantAttributes[] = [
                                                    'value' => $attrValue->value,
                                                    'color' => $attrValue->color_hex ?? null,
                                                ];
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        {{-- Imagen --}}
                                        <td class="align-middle text-center">
                                            @if($productImage)
                                                <img src="{{ $productImage }}" alt="{{ $item->product_name }}"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e0e0e0;">
                                            @else
                                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-box" style="font-size: 20px; color: #1976d2;"></i>
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Producto --}}
                                        <td class="align-middle text-center">
                                            <strong style="font-size: 15px;">{{ $item->product_name }}</strong>
                                            @if(count($variantAttributes) > 0)
                                                <div class="mt-1">
                                                    @foreach($variantAttributes as $attr)
                                                        @if($attr['color'])
                                                            <span class="badge mr-1" style="background: {{ $attr['color'] }}; color: {{ \App\Helpers\ColorHelper::getContrastColor($attr['color']) }}; font-size: 11px;">
                                                                {{ $attr['value'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary mr-1" style="font-size: 11px;">{{ $attr['value'] }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Cantidad --}}
                                        <td class="align-middle text-center">
                                            <span class="badge badge-primary" style="font-size: 16px; padding: 8px 14px;">
                                                {{ $item->quantity }}
                                            </span>
                                        </td>
                                        {{-- Medidas --}}
                                        <td class="align-middle text-center">
                                            @if($hasMeasurements)
                                                @php
                                                    $filteredMeasurements = array_filter($measurements, fn($v, $k) => !empty($v) && $v !== '0' && $k !== 'save_to_client', ARRAY_FILTER_USE_BOTH);
                                                @endphp
                                                <div class="d-flex flex-wrap justify-content-center" style="gap: 4px;">
                                                    @foreach($filteredMeasurements as $key => $value)
                                                        <span class="badge" style="background: #fff3e0; color: #e65100; font-size: 11px; padding: 3px 6px;">
                                                            {{ $measurementLabels[$key] ?? ucfirst($key) }}: {{ $value }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        {{-- Extras --}}
                                        <td class="align-middle text-center">
                                            @if($extrasCount > 0)
                                                <div class="d-flex flex-wrap justify-content-center" style="gap: 4px;">
                                                    @foreach($item->extras as $extra)
                                                        <span class="badge" style="background: #e3f2fd; color: #1565c0; font-size: 11px; padding: 3px 6px;">
                                                            {{ $extra->productExtra->name ?? 'Extra' }} x{{ $extra->quantity }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer con resumen --}}
                <div class="modal-footer py-2" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <span style="font-size: 14px; color: #495057;">
                                <i class="fas fa-box mr-1"></i>
                                <strong>{{ $order->items->count() }}</strong> producto(s)
                                <span class="mx-2">|</span>
                                <i class="fas fa-cubes mr-1"></i>
                                <strong>{{ $order->items->sum('quantity') }}</strong> piezas
                            </span>
                        </div>
                        <div>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-primary btn-sm mr-2">
                                <i class="fas fa-eye mr-1"></i>Ver Pedido
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE PRODUCTOS PARA MARCAR LISTO --}}
    @if ($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
        <div class="modal fade" id="modalProductosQueue{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="max-height: 60vh;">
                    <div class="modal-header bg-success text-white py-2">
                        <h5 class="modal-title">
                            <i class="fas fa-tasks mr-2"></i>Productos: {{ $order->order_number }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-3" style="overflow-y: auto; max-height: calc(60vh - 120px);">
                        {{-- Barra de progreso compacta --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size: 13px; font-weight: 600;">Progreso de Producción</span>
                                <span class="progress-label-{{ $order->id }}" style="font-size: 13px; font-weight: 600;">
                                    @php
                                        $completedCount = $order->items->where('production_completed', true)->count();
                                        $totalCount = $order->items->count();
                                        $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                                    @endphp
                                    {{ $completedCount }} / {{ $totalCount }}
                                </span>
                            </div>
                            <div class="progress" style="height: 14px;">
                                <div class="progress-bar progress-bar-{{ $order->id }} bg-success" role="progressbar"
                                    style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- Lista de productos con formato mejorado --}}
                        <div class="list-group lista-productos-{{ $order->id }}">
                            @foreach($order->items as $item)
                                @php
                                    $isCompleted = $item->production_completed ?? false;
                                    $product = $item->product;
                                    $productVariant = $item->productVariant;

                                    // Imagen del producto
                                    $productImage = $product?->primary_image_url;

                                    // Atributos de la variante
                                    $variantAttributes = [];
                                    if ($productVariant && $productVariant->attributeValues) {
                                        foreach ($productVariant->attributeValues as $attrValue) {
                                            $variantAttributes[] = [
                                                'name' => $attrValue->attribute->name ?? '',
                                                'value' => $attrValue->value,
                                                'color' => $attrValue->color_hex ?? null,
                                            ];
                                        }
                                    }

                                    // Diseños asignados
                                    $itemDesigns = $item->designExports ?? collect();
                                    $totalStitches = $itemDesigns->sum('stitches_count');
                                    $embroideryCost = 0;
                                    foreach ($itemDesigns as $design) {
                                        $rate = $design->pivot->rate_per_thousand_adjusted ?? ($product->embroidery_rate_per_thousand ?? 1);
                                        $embroideryCost += (($design->stitches_count ?? 0) / 1000) * $rate * $item->quantity;
                                    }
                                @endphp
                                <div class="list-group-item producto-item-queue py-3 px-3 {{ $isCompleted ? 'bg-light' : '' }}"
                                     data-item-id="{{ $item->id }}"
                                     data-order-id="{{ $order->id }}"
                                     data-completed="{{ $isCompleted ? '1' : '0' }}"
                                     style="border-left: 4px solid {{ $isCompleted ? '#28a745' : '#dee2e6' }};">
                                    <div class="d-flex align-items-center">
                                        {{-- Foto del producto --}}
                                        <div class="mr-3" style="flex-shrink: 0;">
                                            @if($productImage)
                                                <img src="{{ $productImage }}" alt="{{ $item->product_name }}"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e0e0e0;">
                                            @else
                                                <div style="width: 60px; height: 60px; background: #f5f5f5; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid #e0e0e0;">
                                                    <i class="fas fa-box" style="font-size: 22px; color: #bdbdbd;"></i>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Información del producto --}}
                                        <div class="flex-grow-1">
                                            {{-- Nombre, cantidad y botón en una línea --}}
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong style="font-size: 16px; color: #212529;">
                                                        {{ $item->product_name }}
                                                    </strong>
                                                    @if($productVariant && $productVariant->sku_suffix)
                                                        <span class="ml-1" style="color: #f57c00; font-weight: 600; font-size: 14px;">
                                                            {{ $productVariant->sku_suffix }}
                                                        </span>
                                                    @endif
                                                    <span class="ml-2 badge badge-secondary" style="font-size: 13px; padding: 5px 10px;">x{{ $item->quantity }}</span>
                                                    @if($product && $product->category)
                                                        <span class="ml-1" style="font-size: 13px; color: #6c757d;">{{ $product->category->name }}</span>
                                                    @endif
                                                </div>
                                                {{-- Botón Terminado --}}
                                                <div>
                                                    @if($isCompleted)
                                                        <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                                                            <i class="fas fa-check mr-1"></i> Terminado
                                                        </span>
                                                    @else
                                                        <button type="button" class="btn btn-outline-success btn-marcar-terminado-queue"
                                                                data-item-id="{{ $item->id }}"
                                                                data-order-id="{{ $order->id }}"
                                                                style="font-size: 14px; padding: 8px 16px;">
                                                            <i class="fas fa-check mr-1"></i> Terminado
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Atributos de variante --}}
                                            @if(count($variantAttributes) > 0)
                                                <div class="mt-2">
                                                    @foreach($variantAttributes as $attr)
                                                        @if($attr['color'])
                                                            <span class="badge mr-1" style="background: {{ $attr['color'] }}; color: {{ \App\Helpers\ColorHelper::getContrastColor($attr['color']) }}; font-size: 12px; padding: 4px 8px;">
                                                                <i class="fas fa-palette mr-1" style="font-size: 10px;"></i>{{ $attr['value'] }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary mr-1" style="font-size: 12px; padding: 4px 8px;">
                                                                <i class="fas fa-tag mr-1" style="font-size: 10px;"></i>{{ $attr['value'] }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Diseños asignados --}}
                                            @if($itemDesigns->count() > 0)
                                                <div class="mt-2">
                                                    <a href="#" class="text-decoration-none" style="color: #2e7d32; font-size: 13px; font-weight: 600;"
                                                       data-toggle="collapse" data-target="#designs-{{ $order->id }}-{{ $item->id }}"
                                                       onclick="event.preventDefault(); $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        {{ $itemDesigns->count() }} diseño(s)
                                                        <span style="color: #6c757d; font-weight: 500;">
                                                            &bull; {{ number_format($totalStitches) }} pts &bull; Est. ${{ number_format($embroideryCost, 2) }}
                                                        </span>
                                                        <i class="fas fa-chevron-down collapse-icon ml-1" style="font-size: 10px;"></i>
                                                    </a>
                                                    <div class="collapse mt-1" id="designs-{{ $order->id }}-{{ $item->id }}">
                                                        @foreach($itemDesigns as $design)
                                                            <div class="d-flex align-items-center py-1 pl-3" style="font-size: 13px;">
                                                                <i class="fas fa-file-code mr-2" style="color: #7b1fa2;"></i>
                                                                <span style="color: #212529;">{{ $design->file_name ?? 'Diseño' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Extras del producto (colapsable) --}}
                                            @if($item->extras && $item->extras->count() > 0)
                                                @php $extrasCount = $item->extras->count(); @endphp
                                                <div class="mt-2">
                                                    <a href="#" class="text-decoration-none" style="color: #0277bd; font-size: 13px; font-weight: 600;"
                                                       data-toggle="collapse" data-target="#extras-{{ $order->id }}-{{ $item->id }}"
                                                       onclick="event.preventDefault(); $(this).find('.collapse-icon').toggleClass('fa-chevron-down fa-chevron-up');">
                                                        <i class="fas fa-plus-circle mr-1"></i>
                                                        {{ $extrasCount }} extra(s)
                                                        <i class="fas fa-chevron-down collapse-icon ml-1" style="font-size: 10px;"></i>
                                                    </a>
                                                    <div class="collapse mt-1" id="extras-{{ $order->id }}-{{ $item->id }}">
                                                        @foreach($item->extras as $extra)
                                                            <div class="d-flex align-items-center py-1 pl-3" style="font-size: 13px;">
                                                                <i class="fas fa-concierge-bell mr-2" style="color: #0277bd;"></i>
                                                                <span style="color: #212529;">{{ $extra->productExtra->name ?? 'Extra' }}</span>
                                                                <span class="ml-1" style="color: #6c757d;">(x{{ $extra->quantity }})</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
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

        // Actualizar barra del modal
        const progressBar = document.querySelector('.progress-bar-' + orderId);
        if (progressBar) {
            progressBar.style.width = porcentaje + '%';
            progressBar.setAttribute('aria-valuenow', porcentaje);

            if (completados === total) {
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('bg-primary', 'progress-bar-striped', 'progress-bar-animated');
            }
        }

        // Actualizar label del modal
        const progressLabel = document.querySelector('.progress-label-' + orderId);
        if (progressLabel) {
            progressLabel.textContent = completados + ' / ' + total;
        }

        // === ACTUALIZAR BARRA DE PROGRESO EN LA TABLA ===
        const progressBarTable = document.querySelector('.progress-bar-table-' + orderId);
        if (progressBarTable) {
            progressBarTable.style.width = porcentaje + '%';
            progressBarTable.setAttribute('aria-valuenow', porcentaje);

            // Cambiar color según completado
            if (completados === total) {
                progressBarTable.classList.remove('bg-primary');
                progressBarTable.classList.add('bg-success');
            } else {
                progressBarTable.classList.remove('bg-success');
                progressBarTable.classList.add('bg-primary');
            }
        }

        // Actualizar label de la tabla
        const progressLabelTable = document.querySelector('.progress-label-table-' + orderId);
        if (progressLabelTable) {
            progressLabelTable.textContent = completados + ' / ' + total;
        }

        // Actualizar porcentaje de la tabla
        const progressPercentTable = document.querySelector('.progress-percent-table-' + orderId);
        if (progressPercentTable) {
            progressPercentTable.textContent = porcentaje + '%';
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
