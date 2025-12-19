@extends('adminlte::page')

@section('title', 'CLIENTES')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary" bis_skin_checked="1">

        <div class="card-header" bis_skin_checked="1">

            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> CLIENTES</h3>
            <!-- /.card-tools -->
        </div>
        <!-- /.card-header -->
        <div class="card-body" bis_skin_checked="1">
            <div class="row">
                <a href="{{ route('admin.clientes.create') }}" type="button" class="btn btn-info">
                    Nuevo <i class="fas fa-plus"></i></a>
            </div>
            <hr>
            <div class="col-12">
                <table id="example1" class="table table-bordered table-hover ">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Telefono</th>
                            <th>Email</th>
                            <th>Direccion</th>
                            <th>Estado</th>
                            <th>Recomendacion</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($clientes as $cliente)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $cliente->nombre }}</td>
                                <td>{{ $cliente->apellidos }}</td>
                                <td>{{ $cliente->telefono }}</td>
                                <td>{{ $cliente->email }}</td>
                                <td>{{ $cliente->direccion }}</td>
                                <td>{{ $cliente->estado->nombre_estado }}</td>
                                <td>{{ $cliente->recomendacion->nombre_recomendacion }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="#" class="btn btn-info btn-sm" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.clientes.edit', $cliente->id) }}"
                                            class="btn btn-warning btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="{{ route('admin.clientes.confirm_delete', $cliente->id) }}"
                                            class="btn btn-danger btn-sm" title="Eliminar">
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
        <!-- /.card-body -->
    </div>
@stop

@section('css')
    <style>
        /* Fondo transparente y sin borde en el contenedor */
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            /* Centrar los botones */
            gap: 10px;
            /* Espaciado entre botones */
            margin-bottom: 15px;
            /* Separar botones de la tabla */
        }

        /* Estilo personalizado para los botones */
        #example1_wrapper .btn {
            color: #fff;
            /* Color del texto en blanco */
            border-radius: 4px;
            /* Bordes redondeados */
            padding: 5px 15px;
            /* Espaciado interno */
            font-size: 14px;
            /* TamaÃ±o de fuente */
        }

        /* Colores por tipo de botÃ³n */
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
    </style> {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Clientes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Clientes",
                    "infoFiltered": "(Filtrado de _MAX_ total Clientes)",
                    "lengthMenu": "Mostrar _MENU_ Clientes",
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
