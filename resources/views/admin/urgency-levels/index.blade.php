@extends('adminlte::page')

@section('title', 'Niveles de Urgencia')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clock mr-2"></i> Niveles de Urgencia</h1>
        <a href="{{ route('admin.urgency-levels.create') }}" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> Nuevo
        </a>
    </div>
@stop

@section('content')
    {{-- Mensajes Flash --}}
    @foreach (['success', 'error', 'info'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
    @endforeach

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover" id="urgencyTable">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nombre</th>
                        <th style="width: 120px;" class="text-center">% Tiempo</th>
                        <th style="width: 120px;" class="text-center">Multiplicador</th>
                        <th style="width: 100px;" class="text-center">Color</th>
                        <th style="width: 80px;" class="text-center">Orden</th>
                        <th style="width: 150px;" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($urgencyLevels as $level)
                        <tr>
                            <td>{{ $level->id }}</td>
                            <td>
                                @if ($level->icon)
                                    <i class="fas {{ $level->icon }} mr-1" style="color: {{ $level->color }};"></i>
                                @endif
                                {{ $level->name }}
                                @if ($level->description)
                                    <small class="text-muted d-block">{{ $level->description }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $level->time_percentage }}%</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">x{{ number_format($level->price_multiplier, 2) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background-color: {{ $level->color }}; color: white; padding: 5px 15px;">
                                    {{ $level->color }}
                                </span>
                            </td>
                            <td class="text-center">{{ $level->sort_order }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.urgency-levels.edit', $level->id) }}"
                                   class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.urgency-levels.confirm_delete', $level->id) }}"
                                   class="btn btn-danger btn-sm" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No hay niveles de urgencia registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#urgencyTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[5, 'asc']],
                pageLength: 10
            });
        });
    </script>
@stop
