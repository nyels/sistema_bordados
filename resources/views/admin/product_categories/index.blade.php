@extends('adminlte::page')

@section('title', 'Categorías de Productos')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">

        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> CATEGORÍAS DE PRODUCTOS</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <a href="{{ route('admin.product_categories.create') }}" type="button" class="btn btn-primary">
                    Nuevo <i class="fas fa-plus"></i></a>
            </div>
            <hr>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th style="text-align: center;">Productos</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                    </td>
                                    <td>{{ Str::limit($category->description, 80) ?? '-' }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-{{ $category->products_count > 0 ? 'info' : 'secondary' }}">
                                            {{ $category->products_count }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <a href="{{ route('admin.product_categories.edit', $category->id) }}"
                                                class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <a href="{{ route('admin.product_categories.confirm_delete', $category->id) }}"
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
            flex-wrap: wrap;
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
            color: #212529;
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
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorías",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Categorías",
                    "infoFiltered": "(Filtrado de _MAX_ total Categorías)",
                    "lengthMenu": "Mostrar _MENU_ Categorías",
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
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-info'
                    }
                ]
            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
