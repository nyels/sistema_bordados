@extends('adminlte::page')

@section('title', 'Categorías de Materiales')

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
                <i class="fas fa-layer-group"></i> CATEGORÍAS DE MATERIALES
            </h3>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('admin.material-categories.create') }}" class="btn btn-primary">
                        Nuevo <i class="fas fa-plus"></i>
                    </a>
                    <a href="{{ route('admin.material-category-units.index') }}" class="btn btn-info">
                        <i class="fas fa-link"></i> Gestionar Unidades Permitidas
                    </a>
                </div>
            </div>
            <hr>

            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover text-center">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Materiales</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $category->name }}</td>

                                <td>
                                    <button class="btn btn-info btn-sm btn-show-materials" data-id="{{ $category->id }}"
                                        data-name="{{ $category->name }}" style="font-size: 1rem; font-weight: bold;">
                                        {{ $category->materials_count }}
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="{{ route('admin.material-categories.edit', $category->id) }}"
                                            class="btn btn-warning btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.material-categories.confirm_delete', $category->id) }}"
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
            flex-wrap: wrap;
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
    {{-- Modal para listar materiales --}}
    <div class="modal fade" id="materialsModal" tabindex="-1" role="dialog" aria-labelledby="materialsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: none; overflow: hidden;">
                <div class="modal-header bg-primary text-white justify-content-center position-relative">
                    <h5 class="modal-title font-weight-bold" id="materialsModalLabel" style="font-size: 1.5rem;">NOMBRE
                        CATEGORIA</h5>
                    <button type="button" class="close position-absolute text-white" data-dismiss="modal"
                        aria-label="Close" style="right: 15px; top: 15px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="text-center text-muted mb-3 font-weight-bold"
                        style="text-transform: uppercase; letter-spacing: 1px;">
                        Materiales Asociados
                    </h6>
                    <div class="table-responsive d-flex justify-content-center">
                        <table class="table table-bordered table-striped table-hover text-center" id="materialsTable"
                            style="width: 80%;">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 10%;">#</th>
                                    <th>Nombre del Material</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Contenido dinámico --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary px-5 rounded-pill shadow-sm" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Lógica para abrir el modal y cargar materiales
            $('.btn-show-materials').on('click', function() {
                var categoryId = $(this).data('id');
                var categoryName = $(this).data('name');

                // Configurar titulo
                $('#materialsModalLabel').text(categoryName);

                // Limpiar tabla
                var tbody = $('#materialsTable tbody');
                tbody.html(
                    '<tr><td colspan="2"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');

                // Abrir modal
                $('#materialsModal').modal('show');

                // Petición AJAX (Necesitamos una ruta para esto, la crearemos en breve)
                // Por ahora simulare la carga o usare una ruta si puedo deducirla, 
                // pero lo correcto es crear un endpoint dedicated o usar uno existente.
                // Usaremos: /admin/material-categories/{id}/materials
                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/get-materials',
                    type: 'GET',
                    success: function(response) {
                        tbody.empty();
                        if (response.length > 0) {
                            $.each(response, function(index, material) {
                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td class="font-weight-bold text-dark">${material.name}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            tbody.html(
                                '<tr><td colspan="2" class="text-muted">Sin materiales asociados</td></tr>'
                            );
                        }
                    },
                    error: function() {
                        tbody.html(
                            '<tr><td colspan="2" class="text-danger">Error al cargar datos</td></tr>'
                        );
                    }
                });
            });

            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
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
