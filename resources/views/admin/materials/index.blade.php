@extends('adminlte::page')

@section('title', 'Materiales')

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
                <i class="fas fa-boxes"></i> CATÁLOGO DE MATERIALES
            </h3>
        </div>

        <div class="card-body">
            {{-- FILTROS Y ACCIONES --}}
            <div class="row mb-3">
                <div class="col-md-12 d-flex align-items-center flex-wrap">
                    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary mr-2 mb-2">
                        Nuevo <i class="fas fa-plus"></i>
                    </a>
                    <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary mr-3 mb-2">
                        <i class="fas fa-layer-group"></i> Categorías
                    </a>

                    <form id="filterForm" method="GET" action="{{ route('admin.materials.index') }}"
                        class="form-inline mb-2">
                        <select name="category" class="form-control form-control-sm mr-2">
                            <option value="">Todas las categorías</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @if (request('category'))
                            <a href="{{ route('admin.materials.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Categoría</th>
                            <th>Nombre</th>

                            <th>Composición</th>
                            <th style="width: 100px;">Unidad</th>
                            <th style="width: 100px;">Variantes</th>
                            <th style="width: 120px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('admin.materials.partials.table_rows')
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
            color: #fff;
            border: none;
        }

        /* Ajuste para iconos blancos en botones warning */
        .btn-warning i {
            color: white !important;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var table;

            // Helper to get DataTables options
            function getTableOptions() {
                return {
                    "pageLength": 10,
                    "language": {
                        "emptyTable": "No hay información",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ Materiales",
                        "infoEmpty": "Mostrando 0 a 0 de 0 Materiales",
                        "infoFiltered": "(Filtrado de _MAX_ total Materiales)",
                        "lengthMenu": "Mostrar _MENU_ Materiales",
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
                    "buttons": [{
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
                };
            }

            // Initialize DataTable
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#example1')) {
                    table = $('#example1').DataTable();
                } else {
                    table = $("#example1").DataTable(getTableOptions());
                    table.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                }
            }

            initDataTable();

            // Function to fetch data via AJAX
            function fetchResults() {
                let form = $('#filterForm');
                let url = form.attr('action') + '?' + form.serialize();

                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        table.clear();

                        var newRows = $(response.html).filter('tr');
                        // Check for empty row if applicable, otherwise simple add
                        // In this case MaterialController returns ALL matching rows or empty list.
                        if (newRows.length > 0) {
                            table.rows.add(newRows.toArray());
                        }

                        table.draw();
                    },
                    error: function(xhr) {
                        console.error("Error loading data:", xhr);
                    }
                });
            }

            // Event Listeners
            $('#filterForm select[name="category"]').on('change', function() {
                fetchResults();

                // Toggle Clear Button
                if ($(this).val()) {
                    // Ideally we would show the clear button here, but it's server-rendered.
                    // For now, simpler to just filter.
                }
            });

            // Handle Clear Button if it exists (prevent default reload if desired? No, reload is fine for clearing)
        });
    </script>
@stop
