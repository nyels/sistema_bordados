@extends('adminlte::page')

@section('title', 'Unidades de Medida')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">

        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> UNIDADES DE MEDIDA</h3>
        </div>

        <div class="card-body">

            {{-- MENSAJES FLASH --}}
            @foreach (['success', 'error', 'info'] as $msg)
                @if (session($msg))
                    <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show"
                        role="alert">
                        {{ session($msg) }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
            @endforeach

            <div class="row">
                <a href="{{ route('admin.units.create') }}" type="button" class="btn btn-primary">
                    Nuevo <i class="fas fa-plus"></i>
                </a>
            </div>
            <hr>
            <div class="col-12">
                <table id="example1" class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Símbolo</th>
                            <th>Tipo</th>
                            <th>Compatible con</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $unit->name }}</td>
                                <td><code>{{ $unit->symbol }}</code></td>
                                <td>
                                    @if ($unit->is_base)
                                        <span class="badge badge-info">Base (Consumo)</span>
                                    @else
                                        <span class="badge badge-secondary">Compra</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($unit->is_base)
                                        <span class="text-muted">-</span>
                                    @elseif ($unit->compatibleBaseUnit)
                                        <span class="badge badge-success">
                                            <i class="fas fa-link mr-1"></i>
                                            {{ $unit->compatibleBaseUnit->name }}
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Sin configurar
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="{{ route('admin.units.edit', $unit->id) }}" class="btn btn-warning btn-sm"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.units.confirm_delete', $unit->id) }}" class="btn btn-danger btn-sm"
                                            title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        #example1_wrapper .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-default {
            background-color: #6e7176;
            color: #fff;
            border: none;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Unidades",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Unidades",
                    "infoFiltered": "(Filtrado de _MAX_ total Unidades)",
                    "lengthMenu": "Mostrar _MENU_ Unidades",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                buttons: [{
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default'
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger'
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info'
                    },
                    {
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-default'
                    }
                ]
            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
