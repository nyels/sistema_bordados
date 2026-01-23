@extends('adminlte::page')

@section('title', 'Reservas Activas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-lock mr-2"></i>Reservas de Inventario Activas</h1>
        <div>
            <a href="{{ route('admin.inventory.reservations.history') }}" class="btn btn-secondary">
                <i class="fas fa-history mr-1"></i> Historial
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-info">
                <i class="fas fa-warehouse mr-1"></i> Inventario
            </a>
        </div>
    </div>
@stop

@section('content')
{{-- RESUMEN --}}
<div class="row mb-3">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totals['total_reservations'] }}</h3>
                <p>Reservas Activas</p>
            </div>
            <div class="icon"><i class="fas fa-lock"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($totals['total_quantity'], 2) }}</h3>
                <p>Unidades Reservadas</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totals['orders_with_reservations'] }}</h3>
                <p>Pedidos con Reservas</p>
            </div>
            <div class="icon"><i class="fas fa-clipboard-list"></i></div>
        </div>
    </div>
</div>

{{-- TABLA --}}
<div class="card">
    <div class="card-header bg-warning">
        <h5 class="mb-0 text-dark"><i class="fas fa-list mr-2"></i>Reservas Activas por Pedido</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size: 13px;">
            <thead class="bg-light">
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Material</th>
                    <th class="text-right">Cantidad</th>
                    <th>Fecha Reserva</th>
                    <th>Estado Pedido</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $res)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $res->order_id) }}" class="font-weight-bold">
                                {{ $res->order?->order_number ?? 'N/A' }}
                            </a>
                        </td>
                        <td>{{ $res->order?->cliente?->nombre_completo ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $res->materialVariant?->material?->name ?? 'N/A' }}</strong>
                            @if($res->materialVariant?->color)
                                <span class="badge badge-secondary">{{ $res->materialVariant->color }}</span>
                            @endif
                            <br><small class="text-muted">{{ $res->materialVariant?->sku }}</small>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format($res->quantity, 2) }}</strong>
                            <small class="text-muted">{{ $res->materialVariant?->material?->consumptionUnit?->symbol ?? $res->materialVariant?->material?->baseUnit?->symbol }}</small>
                        </td>
                        <td>{{ $res->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $status = $res->order?->status ?? 'unknown';
                                $statusColors = [
                                    'in_production' => 'info',
                                    'ready' => 'success',
                                    'confirmed' => 'primary',
                                ];
                            @endphp
                            <span class="badge badge-{{ $statusColors[$status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.orders.show', $res->order_id) }}" class="btn btn-xs btn-info" title="Ver Pedido">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.inventory.kardex', $res->material_variant_id) }}" class="btn btn-xs btn-secondary" title="Ver Kardex">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                            No hay reservas activas
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

{{-- EXPLICACIÓN --}}
<div class="alert alert-info mt-3">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Reservas de Inventario:</strong> El stock reservado se bloquea al iniciar produccion de un pedido.
    El stock fisico NO se descuenta hasta que el pedido sea entregado.
    Si el pedido se cancela, las reservas se liberan automaticamente.
</div>
@stop
