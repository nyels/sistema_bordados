@extends('adminlte::page')

@section('title', 'Registro de Actividad')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
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
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-history"></i> REGISTRO DE ACTIVIDAD
            </h3>
        </div>

        <div class="card-body">
            {{-- FILTROS --}}
            <form method="GET" action="{{ route('activity-logs.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Usuario</label>
                            <select name="user_id" class="form-control form-control-sm">
                                <option value="">Todos</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Acción</label>
                            <select name="action" class="form-control form-control-sm">
                                <option value="">Todas</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}"
                                        {{ request('action') == $action ? 'selected' : '' }}>
                                        {{ ucfirst($action) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Desde</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Hasta</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                value="{{ request('search') }}" placeholder="Descripción...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 150px;">Fecha</th>
                            <th style="width: 120px;">Usuario</th>
                            <th style="width: 100px;">Acción</th>
                            <th>Descripción</th>
                            <th style="width: 100px;">IP</th>
                            <th style="width: 80px; text-align: center;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                                </td>
                                <td>{{ $log->user_name ?? 'Sistema' }}</td>
                                <td>
                                    <i class="{{ $log->action_icon }}"></i>
                                    {{ $log->action_label }}
                                </td>
                                <td>
                                    {{ $log->description }}
                                    @if ($log->model_name)
                                        <br><small class="text-muted">{{ $log->short_model_type }}:
                                            {{ $log->model_name }}</small>
                                    @endif
                                </td>
                                <td><small>{{ $log->ip_address }}</small></td>
                                <td class="text-center">
                                    <a href="{{ route('activity-logs.show', $log->uuid) }}" class="btn btn-info btn-sm"
                                        title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No hay registros de actividad
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@stop
