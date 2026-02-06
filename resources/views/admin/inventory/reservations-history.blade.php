@extends('adminlte::page')

@section('title', 'Historial de Reservas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-history mr-2"></i>Historial de Reservas</h1>
        <a href="{{ route('admin.inventory.reservations') }}" class="btn btn-warning">
            <i class="fas fa-lock mr-1"></i> Ver Activas
        </a>
    </div>
@stop

@section('content')
{{-- FILTROS --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.inventory.reservations.history') }}" class="row align-items-center">
            <div class="col-md-2">
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">-- Estado --</option>
                    <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reservado</option>
                    <option value="consumed" {{ request('status') == 'consumed' ? 'selected' : '' }}>Consumido</option>
                    <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Liberado</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}" placeholder="Desde">
            </div>
            <div class="col-md-2">
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}" placeholder="Hasta">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                <a href="{{ route('admin.inventory.reservations.history') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-eraser mr-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- TABLA --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <thead class="bg-light">
                <tr>
                    <th>Pedido</th>
                    <th>Material</th>
                    <th class="text-right">Cantidad</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Consumido/Liberado</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $res)
                    @php
                        $statusColors = [
                            'reserved' => 'warning',
                            'consumed' => 'success',
                            'released' => 'secondary',
                        ];
                        $statusLabels = [
                            'reserved' => 'Reservado',
                            'consumed' => 'Consumido',
                            'released' => 'Liberado',
                        ];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $res->order_id) }}">
                                {{ $res->order?->order_number ?? 'N/A' }}
                            </a>
                        </td>
                        <td>
                            {{ $res->materialVariant?->material?->name ?? 'N/A' }}
                            @if($res->materialVariant?->color)
                                <span class="badge badge-light">{{ $res->materialVariant->color }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($res->quantity, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $statusColors[$res->status] ?? 'secondary' }}">
                                {{ $statusLabels[$res->status] ?? $res->status }}
                            </span>
                        </td>
                        <td>{{ $res->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($res->consumed_at)
                                {{ $res->consumed_at->format('d/m/Y H:i') }}
                            @elseif($res->released_at)
                                {{ $res->released_at->format('d/m/Y H:i') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($res->status === 'consumed')
                                {{ $res->consumer?->name ?? 'Sistema' }}
                            @else
                                {{ $res->creator?->name ?? 'Sistema' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No hay registros
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservations->hasPages())
        <div class="card-footer">
            {{ $reservations->links() }}
        </div>
    @endif
</div>
@stop
