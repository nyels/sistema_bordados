@extends('adminlte::page')

@section('title', 'Kardex - ' . $variant->display_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-history mr-2"></i>Kardex</h1>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
{{-- INFO DEL MATERIAL --}}
<div class="card card-outline card-info mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5 class="mb-1">{{ $variant->material?->name }}</h5>
                <p class="mb-0 text-muted">
                    <code>{{ $variant->sku }}</code>
                    @if($variant->color)
                        <span class="badge badge-secondary ml-2">{{ $variant->color }}</span>
                    @endif
                </p>
            </div>
            <div class="col-md-2 text-center border-left">
                <small class="text-muted d-block">Stock Fisico</small>
                <h4 class="mb-0">{{ number_format($variant->current_stock, 2) }}</h4>
            </div>
            <div class="col-md-2 text-center border-left">
                <small class="text-muted d-block">Reservado</small>
                <h4 class="mb-0 text-warning">{{ number_format($summary['reserved'], 2) }}</h4>
            </div>
            <div class="col-md-2 text-center border-left">
                <small class="text-muted d-block">Disponible</small>
                <h4 class="mb-0 text-success">{{ number_format($summary['available'], 2) }}</h4>
            </div>
            <div class="col-md-2 text-center border-left">
                <small class="text-muted d-block">Valor</small>
                <h4 class="mb-0">${{ number_format($variant->current_value, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- RESUMEN HISTORICO --}}
<div class="row mb-3">
    <div class="col-md-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Entradas (historico)</span>
                <span class="info-box-number">{{ number_format($summary['total_entries'], 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Salidas (historico)</span>
                <span class="info-box-number">{{ number_format($summary['total_exits'], 2) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- FILTROS --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.inventory.kardex', $variant) }}" class="row align-items-center">
            <div class="col-md-2">
                <select name="type" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">-- Tipo --</option>
                    <option value="entrada" {{ request('type') == 'entrada' ? 'selected' : '' }}>Entradas</option>
                    <option value="salida" {{ request('type') == 'salida' ? 'selected' : '' }}>Salidas</option>
                    <option value="ajuste_positivo" {{ request('type') == 'ajuste_positivo' ? 'selected' : '' }}>Ajuste +</option>
                    <option value="ajuste_negativo" {{ request('type') == 'ajuste_negativo' ? 'selected' : '' }}>Ajuste -</option>
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
                <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('admin.inventory.adjustment', $variant) }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit mr-1"></i> Registrar Ajuste
                </a>
            </div>
        </form>
    </div>
</div>

{{-- TABLA KARDEX --}}
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Movimientos</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <thead class="bg-secondary text-white">
                <tr>
                    <th style="width: 140px;">Fecha</th>
                    <th style="width: 100px;">Tipo</th>
                    <th class="text-right" style="width: 100px;">Cantidad</th>
                    <th class="text-right" style="width: 100px;">Costo Unit.</th>
                    <th class="text-right" style="width: 100px;">Costo Total</th>
                    <th class="text-right" style="width: 100px;">Saldo</th>
                    <th>Referencia</th>
                    <th style="width: 120px;">Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mov)
                    @php
                        $isEntry = in_array($mov->type->value, ['entrada', 'ajuste_positivo', 'devolucion_produccion']);
                    @endphp
                    <tr>
                        <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge badge-{{ $mov->type_color }}">
                                <i class="fas fa-{{ $mov->type_icon }} mr-1"></i>{{ $mov->type_label }}
                            </span>
                        </td>
                        <td class="text-right {{ $isEntry ? 'text-success' : 'text-danger' }}">
                            {{ $isEntry ? '+' : '-' }}{{ number_format($mov->quantity, 2) }}
                        </td>
                        <td class="text-right">${{ number_format($mov->unit_cost, 4) }}</td>
                        <td class="text-right">${{ number_format($mov->total_cost, 2) }}</td>
                        <td class="text-right"><strong>{{ number_format($mov->stock_after, 2) }}</strong></td>
                        <td>
                            <small>
                                <span class="badge badge-light">{{ $mov->reference_type }}</span>
                                @if($mov->reference_id)
                                    #{{ $mov->reference_id }}
                                @endif
                                @if($mov->notes)
                                    <br><span class="text-muted">{{ Str::limit($mov->notes, 50) }}</span>
                                @endif
                            </small>
                        </td>
                        <td>
                            <small>{{ $mov->creator?->name ?? 'Sistema' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                            No hay movimientos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
        <div class="card-footer">
            {{ $movements->links() }}
        </div>
    @endif
</div>
@stop
