@extends('adminlte::page')

@section('title', 'Órdenes de Compra')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info', 'warning'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                <i class="fas fa-shopping-cart"></i> ÓRDENES DE COMPRA
            </h3>
        </div>

        <div class="card-body">
            {{-- ACCIONES --}}
            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Orden de Compra
                    </a>
                </div>
            </div>
            {{-- FILTROS --}}
            <form method="GET" action="{{ route('admin.purchases.index') }}" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="small text-muted">Estado</label>
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">Proveedor</label>
                        <select name="proveedor_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Todos los proveedores</option>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}"
                                    {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->nombre_proveedor }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Desde</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ request('date_from') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Hasta</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Buscar</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                placeholder="# OC, Referencia...">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        @if (request()->hasAny(['status', 'proveedor_id', 'date_from', 'date_to', 'search']))
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-sm btn-outline-secondary"
                                title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 120px;"># OC</th>
                            <th>Proveedor</th>
                            <th style="width: 100px;">Fecha</th>
                            <th style="width: 120px;">Estado</th>
                            <th style="width: 100px;" class="text-right">Items</th>
                            <th style="width: 130px;" class="text-right">Total</th>
                            <th style="width: 150px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="font-weight-bold">
                                        {{ $purchase->purchase_number }}
                                    </a>
                                    @if ($purchase->reference)
                                        <br><small class="text-muted">Ref: {{ $purchase->reference }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $purchase->proveedor->nombre_proveedor ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $purchase->ordered_at ? $purchase->ordered_at->format('d/m/Y') : '-' }}
                                    @if ($purchase->expected_at)
                                        <br><small class="text-muted">
                                            Espera: {{ $purchase->expected_at->format('d/m/Y') }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $purchase->status_color }}">
                                        <i class="{{ $purchase->status_icon }}"></i>
                                        {{ $purchase->status_label }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    {{ $purchase->items->count() }}
                                </td>
                                <td class="text-right font-weight-bold">
                                    {{ $purchase->formatted_total }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-info"
                                            title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if ($purchase->can_edit)
                                            <a href="{{ route('admin.purchases.edit', $purchase->id) }}"
                                                class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif

                                        @if ($purchase->can_receive)
                                            <a href="{{ route('admin.purchases.receive', $purchase->id) }}"
                                                class="btn btn-success" title="Recibir">
                                                <i class="fas fa-truck-loading"></i>
                                            </a>
                                        @endif

                                        @if ($purchase->can_cancel)
                                            <a href="{{ route('admin.purchases.cancel', $purchase->id) }}"
                                                class="btn btn-danger" title="Cancelar">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                                    No hay órdenes de compra registradas
                                    <br>
                                    <a href="{{ route('admin.purchases.create') }}" class="btn btn-info btn-sm mt-2">
                                        <i class="fas fa-plus"></i> Crear primera orden
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            @if ($purchases->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
    </div>
@stop
