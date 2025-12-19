@extends('adminlte::page')

@section('title', 'CLIENTES')

@section('content_header')
@stop

@section('content')
    @extends('adminlte::page')

@section('title', 'DISEÑO')

@section('content_header')
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Crear diseño</h3>
        </div>

        <div class="card-body">
            <div class="form-group">
                <label>Nombre del diseño</label>
                <input type="text" class="form-control" placeholder="Ej: Mariposa Floral">
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Categorías</label>
                <div>
                    <label class="mr-3"><input type="checkbox"> Flores</label>
                    <label class="mr-3"><input type="checkbox"> Animales</label>
                </div>
            </div>

            <div class="form-group">
                <label>Imagen principal</label>
                <input type="file" class="form-control-file">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <div>
                    <label class="mr-3"><input type="radio" checked> Activo</label>
                    <label><input type="radio"> Inactivo</label>
                </div>
            </div>
        </div>

        <div class="card-footer text-right">
            <a href="#" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary">Guardar</button>
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
