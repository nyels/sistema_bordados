@extends('adminlte::page')

@section('title', 'Kardex PT - ' . $productVariant->sku_variant)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-history mr-2"></i>Kardex Producto Terminado</h1>
            <small class="text-muted">{{ $productVariant->product->name ?? 'N/A' }} - <code>{{ $productVariant->sku_variant }}</code></small>
        </div>
        <div>
            <a href="{{ route('admin.finished-goods-stock.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            <a href="{{ route('admin.finished-goods-stock.adjustment', $productVariant->id) }}" class="btn btn-warning">
                <i class="fas fa-balance-scale mr-1"></i> Ajuste
            </a>
        </div>
    </div>
@stop

@section('content')
    {{-- RESUMEN --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($currentStock, 0) }}</h3>
                    <p>Stock Actual</p>
                </div>
                <div class="icon"><i class="fas fa-cubes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $movements->total() }}</h3>
                    <p>Total Movimientos</p>
                </div>
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-light"><i class="fas fa-box"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Producto</span>
                    <span class="info-box-number">{{ $productVariant->product->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-light"><i class="fas fa-tag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Categoria</span>
                    <span class="info-box-number">{{ $productVariant->product->category->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.finished-goods-stock.kardex', $productVariant->id) }}">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select name="type" class="form-control form-control-sm">
                            <option value="">-- Tipo Movimiento --</option>
                            <option value="production_entry" {{ request('type') == 'production_entry' ? 'selected' : '' }}>Entrada Produccion</option>
                            <option value="sale_exit" {{ request('type') == 'sale_exit' ? 'selected' : '' }}>Salida Venta</option>
                            <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste</option>
                            <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Devolucion</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control form-control-sm"
                               placeholder="Desde" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control form-control-sm"
                               placeholder="Hasta" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.finished-goods-stock.kardex', $productVariant->id) }}" class="btn btn-sm btn-outline-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA KARDEX (SOLO LECTURA) --}}
    <div class="card">
        <div class="card-header py-2">
            <span class="font-weight-bold">Historial de Movimientos</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0" style="font-size: 14px;">
                <thead style="background-color: #343a40; color: #fff;">
                    <tr>
                        <th style="width: 140px;">Fecha / Hora</th>
                        <th style="width: 130px;">Tipo</th>
                        <th class="text-right" style="width: 90px;">Cantidad</th>
                        <th class="text-right" style="width: 90px;">Stock Antes</th>
                        <th class="text-right" style="width: 90px;">Stock Despues</th>
                        <th>Notas</th>
                        <th style="width: 130px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                        <tr>
                            <td>
                                <small>{{ $mov->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $mov->type_color }}">
                                    <i class="{{ $mov->type_icon }} mr-1"></i>{{ $mov->type_label }}
                                </span>
                            </td>
                            <td class="text-right font-weight-bold {{ $mov->is_entry ? 'text-success' : ($mov->type === 'adjustment' ? ($mov->quantity >= 0 ? 'text-success' : 'text-danger') : 'text-danger') }}">
                                {{ $mov->formatted_quantity }}
                            </td>
                            <td class="text-right text-muted">
                                {{ number_format($mov->stock_before, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($mov->stock_after, 2) }}
                            </td>
                            <td>
                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($mov->notes, 80) }}</small>
                            </td>
                            <td>
                                <small>{{ $mov->creator->name ?? 'Sistema' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
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

    {{-- NOTA FUENTE --}}
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            Datos leidos desde <code>finished_goods_movements</code> (ledger). Solo lectura.
        </small>
    </div>
@stop
