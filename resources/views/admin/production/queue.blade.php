@extends('adminlte::page')

@section('title', 'Cola de Produccion')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-industry mr-2"></i> Cola de Produccion</h1>
        <small class="text-muted">Actualizado: {{ now()->format('d/m/Y H:i') }}</small>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- RESUMEN DE COLA --}}
    <div class="row mb-3">
        <div class="col-lg-2 col-6">
            <div class="info-box bg-gradient-info mb-0">
                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Cola</span>
                    <span class="info-box-number">{{ $summary['total_queue'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="info-box bg-gradient-primary mb-0">
                <span class="info-box-icon"><i class="fas fa-clipboard-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Confirmados</span>
                    <span class="info-box-number">{{ $summary['confirmed'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="info-box bg-gradient-warning mb-0">
                <span class="info-box-icon"><i class="fas fa-cogs"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">En Produccion</span>
                    <span class="info-box-number">{{ $summary['in_production'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="info-box {{ $summary['urgent_count'] > 0 ? 'bg-gradient-danger' : 'bg-gradient-secondary' }} mb-0">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Urgentes</span>
                    <span class="info-box-number">{{ $summary['urgent_count'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="info-box {{ $summary['overdue_count'] > 0 ? 'bg-gradient-danger' : 'bg-gradient-secondary' }} mb-0">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vencidos</span>
                    <span class="info-box-number">{{ $summary['overdue_count'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="info-box {{ $summary['blocked_count'] > 0 ? 'bg-gradient-warning' : 'bg-gradient-success' }} mb-0">
                <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Bloqueados</span>
                    <span class="info-box-number">{{ $summary['blocked_count'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row align-items-center">
                <div class="col-auto">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos los estados</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmados</option>
                        <option value="in_production" {{ request('status') == 'in_production' ? 'selected' : '' }}>En Produccion</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="urgency" class="form-control form-control-sm">
                        <option value="">Todas las urgencias</option>
                        <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="urgente" {{ request('urgency') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        <option value="express" {{ request('urgency') == 'express' ? 'selected' : '' }}>Express</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="mb-0">
                        <input type="checkbox" name="blocked" value="1" {{ request('blocked') ? 'checked' : '' }}>
                        Solo bloqueados
                    </label>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.production.queue') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA DE COLA --}}
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped mb-0" style="font-size: 13px;">
                <thead class="bg-dark text-white">
                    <tr>
                        <th style="width: 40px;" data-toggle="tooltip" data-placement="top"
                            title="Prioridad: Numero menor = mas urgente. Se ordena automaticamente por urgencia, fecha compromiso y antiguedad.">Prio</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center" data-toggle="tooltip" data-placement="top"
                            title="Nivel de urgencia: Normal (sin prisa), Urgente (prioritario), Express (maximo)">Urgencia</th>
                        <th data-toggle="tooltip" data-placement="top"
                            title="Fecha prometida al cliente para entrega. Si esta vencida aparece en rojo.">Fecha Compromiso</th>
                        <th data-toggle="tooltip" data-placement="top"
                            title="Estado de materiales: OK = disponibles, Insuficiente = falta material para producir">Materiales</th>
                        <th class="text-center" data-toggle="tooltip" data-placement="top"
                            title="Bloqueos: Motivos que impiden iniciar produccion (ajustes pendientes, diseno sin aprobar, falta material)">Bloqueos</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $isOverdue = $order->promised_date && $order->promised_date->lt(now()->startOfDay());
                            $isUrgent = in_array($order->urgency_level, ['urgente', 'express']);
                            $hasBlockers = $order->has_blockers;
                            // === BLOQUEO POR INVENTARIO (PERSISTIDO) ===
                            $hasInventoryBlock = $order->status === \App\Models\Order::STATUS_CONFIRMED && $order->hasProductionInventoryBlock();
                            $canStartProduction = $order->status === \App\Models\Order::STATUS_CONFIRMED && !$hasBlockers;
                        @endphp
                        <tr class="{{ $hasBlockers ? 'table-warning' : ($isOverdue ? 'table-danger' : ($isUrgent ? 'table-info' : '')) }}">
                            <td class="text-center">
                                <span class="badge badge-{{ ($order->priority ?? 50) <= 25 ? 'danger' : (($order->priority ?? 50) <= 50 ? 'warning' : 'secondary') }}"
                                      style="font-size: 14px; min-width: 30px;" title="Prioridad {{ $order->priority ?? 50 }}">
                                    {{ $order->priority ?? 50 }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}?from=queue" class="font-weight-bold">
                                    {{ $order->order_number }}
                                </a>
                                @if($order->isAnnex())
                                    <span class="badge badge-info ml-1" title="Pedido Anexo"><i class="fas fa-link"></i></span>
                                @endif
                            </td>
                            <td>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $order->urgency_color }}">
                                    {{ $order->urgency_label }}
                                </span>
                            </td>
                            <td>
                                @if($order->promised_date)
                                    <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $order->promised_date->format('d/m/Y') }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="badge badge-danger ml-1">VENCIDO</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(count($order->material_requirements) > 0)
                                    @php
                                        $insufficient = collect($order->material_requirements)->filter(fn($m) => !$m['sufficient']);
                                        $sufficient = collect($order->material_requirements)->filter(fn($m) => $m['sufficient']);
                                    @endphp
                                    @if($insufficient->count() > 0)
                                        <span class="text-danger" title="{{ $insufficient->pluck('material_name')->implode(', ') }}">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{ $insufficient->count() }} insuficiente(s)
                                        </span>
                                    @else
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                            {{ $sufficient->count() }} OK
                                        </span>
                                    @endif
                                    <button type="button" class="btn btn-xs btn-outline-info ml-1"
                                            data-toggle="modal" data-target="#materialsModal{{ $order->id }}"
                                            title="Ver detalle de materiales">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                @else
                                    <span class="text-muted">Sin materiales</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($hasBlockers)
                                    <button type="button" class="btn btn-xs btn-danger"
                                            data-toggle="modal" data-target="#blockersModal{{ $order->id }}"
                                            title="Ver motivos de bloqueo">
                                        <i class="fas fa-ban"></i> {{ count($order->blocker_reasons) }}
                                    </button>
                                @elseif($hasInventoryBlock)
                                    {{-- BLOQUEO POR INVENTARIO (INTENTO PREVIO FALLIDO) --}}
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
                            <td class="text-right">
                                @if($canStartProduction)
                                    <form action="{{ route('admin.production.queue.start', $order) }}" method="POST" class="d-inline"
                                          data-confirm="produccion"
                                          data-confirm-title="¿Iniciar producción de {{ $order->order_number }}?"
                                          data-confirm-text="Se reservarán los materiales necesarios para este pedido."
                                          data-confirm-impact="Los materiales quedarán bloqueados hasta completar o cancelar el pedido.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-play"></i> Iniciar
                                        </button>
                                    </form>
                                @elseif($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
                                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-inline"
                                          data-confirm="listo"
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
                                    <a href="{{ route('admin.orders.show', $order) }}?from=queue#blockers-section" class="btn btn-sm btn-warning"
                                       title="Ver motivos de bloqueo y acciones requeridas">
                                        <i class="fas fa-search"></i> Ver Bloqueos
                                    </a>
                                @endif
                                <a href="{{ route('admin.orders.show', $order) }}?from=queue" class="btn btn-sm btn-info"
                                   title="Ver pedido">
                                    <i class="fas fa-eye"></i>
                                </a>
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
        @if($orders->hasPages())
            <div class="card-footer">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- MODALES DE MATERIALES --}}
    @foreach($orders as $order)
        @if(count($order->material_requirements) > 0)
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
                        <div class="modal-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Material</th>
                                        <th>Color/SKU</th>
                                        <th class="text-right">Requerido</th>
                                        <th class="text-right">Stock Fisico</th>
                                        <th class="text-right">Reservado Total</th>
                                        <th class="text-right">Disponible</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->material_requirements as $mat)
                                        <tr class="{{ !$mat['sufficient'] ? 'table-danger' : '' }}">
                                            <td><strong>{{ $mat['material_name'] }}</strong></td>
                                            <td>
                                                @if($mat['variant_color'])
                                                    <span class="badge badge-secondary">{{ $mat['variant_color'] }}</span>
                                                @endif
                                                <code>{{ $mat['variant_sku'] ?? '-' }}</code>
                                            </td>
                                            <td class="text-right font-weight-bold">{{ number_format($mat['required'], 2) }} {{ $mat['unit'] }}</td>
                                            <td class="text-right">{{ number_format($mat['current_stock'], 2) }}</td>
                                            <td class="text-right">
                                                {{ number_format($mat['total_reserved'], 2) }}
                                                @if($mat['reserved_for_this'] > 0)
                                                    <br><small class="text-success">({{ number_format($mat['reserved_for_this'], 2) }} este pedido)</small>
                                                @endif
                                            </td>
                                            <td class="text-right {{ $mat['available'] < $mat['needed'] ? 'text-danger font-weight-bold' : 'text-success' }}">
                                                {{ number_format($mat['available'], 2) }}
                                            </td>
                                            <td class="text-center">
                                                @if($mat['reserved_for_this'] >= $mat['required'])
                                                    <span class="badge badge-success">Reservado</span>
                                                @elseif($mat['sufficient'])
                                                    <span class="badge badge-info">Disponible</span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        Faltan {{ number_format($mat['needed'] - $mat['available'], 2) }} {{ $mat['unit'] }}
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
                                    <strong>Stock Fisico:</strong> Cantidad en almacen |
                                    <strong>Reservado:</strong> Bloqueado para otros pedidos |
                                    <strong>Disponible:</strong> Fisico - Reservado
                                </small>
                            </div>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL DE BLOQUEOS --}}
        @if($order->has_blockers && count($order->blocker_reasons) > 0)
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
                            <p class="text-muted mb-3">Este pedido no puede iniciar produccion por los siguientes motivos:</p>
                            <ul class="list-group">
                                @foreach($order->blocker_reasons as $reason)
                                    <li class="list-group-item list-group-item-danger">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        {{ $reason }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('admin.orders.show', $order) }}?from=queue#blockers-section" class="btn btn-warning">
                                <i class="fas fa-arrow-right mr-1"></i> Ver Detalle del Pedido
                            </a>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@stop

@section('css')
<style>
    .info-box {
        min-height: 70px;
    }
    .info-box-icon {
        width: 60px;
        font-size: 1.5rem;
    }
    .info-box-content {
        padding: 5px 10px;
    }
    .info-box-number {
        font-size: 1.5rem;
    }
    .table-warning {
        background-color: #fff3cd !important;
    }
</style>
@stop

@section('js')
@include('partials.notifications-config')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@stop
