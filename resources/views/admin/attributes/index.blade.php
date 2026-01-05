@extends('adminlte::page')

@section('title', 'Atributos')

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

    <div class="row">

        {{-- ================== COLUMNA ATRIBUTOS ================== --}}
        <div class="col-12 col-lg-6">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">ATRIBUTOS</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('admin.attributes.create') }}" class="btn btn-info">
                            Nuevo Atributo <i class="fas fa-plus"></i>
                        </a>
                    </div>

                    <table id="tableAtributos" class="table table-bordered table-hover text-center">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributes as $attribute)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $attribute->name }}</td>
                                    <td><code>{{ $attribute->slug }}</code></td>
                                    <td>
                                        @switch($attribute->type)
                                            @case('select')
                                                <span class="badge badge-primary">Selector</span>
                                            @break

                                            @case('color')
                                                <span class="badge badge-warning">Color</span>
                                            @break

                                            @case('text')
                                                <span class="badge badge-secondary">Texto</span>
                                            @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.attributes.edit', $attribute->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="{{ route('admin.attributes.confirm_delete', $attribute->id) }}"
                                            class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        {{-- ================== COLUMNA VALORES ================== --}}
        <div class="col-12 col-lg-6">

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">VALORES DE ATRIBUTOS</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('admin.attribute-values.create') }}" class="btn btn-success">
                            Nuevo Valor <i class="fas fa-plus"></i>
                        </a>
                    </div>

                    <table id="tableValores" class="table table-bordered table-hover text-center">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Atributo</th>
                                <th>Valor</th>
                                <th>Color</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributeValues as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $value->attribute->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $value->value }}</td>
                                    <td>
                                        @if ($value->hex_color)
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span
                                                    style="
                                                width: 22px;
                                                height: 22px;
                                                background-color: {{ $value->hex_color }};
                                                border: 2px solid #333;
                                                border-radius: 4px;
                                                margin-right: 6px;">
                                                </span>
                                                <code>{{ $value->hex_color }}</code>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.attribute-values.edit', $value->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="{{ route('admin.attribute-values.confirm_delete', $value->id) }}"
                                            class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Fondo transparente y sin borde en el contenedor */
        #tableAtributos_wrapper .dt-buttons,
        #tableValores_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        /* Estilo personalizado para los botones */
        #tableAtributos_wrapper .btn,
        #tableValores_wrapper .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        /* Colores por tipo de botón */
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
            color: #212529;
            border: none;
        }

        .gap-1 {
            gap: 0.5rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // DataTable para Atributos
            $("#tableAtributos").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Atributos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Atributos",
                    "infoFiltered": "(Filtrado de _MAX_ total Atributos)",
                    "lengthMenu": "Mostrar _MENU_ Atributos",
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
            }).buttons().container().appendTo('#tableAtributos_wrapper .row:eq(0)');

            // DataTable para Valores
            $("#tableValores").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Valores",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Valores",
                    "infoFiltered": "(Filtrado de _MAX_ total Valores)",
                    "lengthMenu": "Mostrar _MENU_ Valores",
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
            }).buttons().container().appendTo('#tableValores_wrapper .row:eq(0)');
        });
    </script>
@stop
