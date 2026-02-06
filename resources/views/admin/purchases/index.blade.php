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
                    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary mr-2">
                        <i class="fas fa-plus"></i> Nueva Orden de Compra
                    </a>
                    <a href="{{ route('admin.proveedores.create') }}" class="btn btn-secondary">
                        <i class="fas fa-truck"></i> Crear Proveedor
                    </a>
                </div>
            </div>

            {{-- FILTROS (Mantenidos por si se requiere filtrado del servidor, 
                 aunque DataTables ofrece búsqueda local en la página actual) --}}
            <form method="GET" action="{{ route('admin.purchases.index') }}" id="filterForm" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label class="small text-muted">Estado</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Todos los estados</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted">Proveedor</label>
                        <select name="proveedor_id" class="form-control form-control-sm">
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
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Hasta</label>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        @if (request()->hasAny(['status', 'proveedor_id', 'date_from', 'date_to']))
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-sm"
                                title="Limpiar filtros">
                                <i class="fas fa-eraser"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                {{-- ID agregado para DataTables --}}
                <table id="example1" class="table table-bordered table-hover table-sm text-center">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th style="width: 120px;"># OC</th>
                            <th>Proveedor</th>
                            <th style="width: 100px;">Fecha</th>
                            <th style="width: 120px;">Estado</th>
                            <th style="width: 100px;" class="text-center">Items</th>
                            <th style="width: 130px;" class="text-center">Total</th>
                            <th style="width: 150px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('admin.purchases.partials.table_rows')
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN SERVER-SIDE --}}
            <div id="pagination-container" class="d-flex justify-content-center mt-3">
                @if ($purchases->hasPages())
                    {{ $purchases->links() }}
                @endif
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
            var table;

            // Helper to get DataTables options
            function getTableOptions() {
                return {
                    "pageLength": 25,
                    "language": {
                        "emptyTable": "No hay informacion",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ Compras",
                        "infoEmpty": "Mostrando 0 a 0 de 0 Compras",
                        "infoFiltered": "(Filtrado de _MAX_ total Compras)",
                        "lengthMenu": "Mostrar _MENU_ Compras",
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
                    "searching": true,
                    "paging": true,
                    "info": true,
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
                };
            }

            // Initialize DataTable
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#example1')) {
                    table = $('#example1').DataTable();
                } else {
                    table = $("#example1").DataTable(getTableOptions());
                    table.buttons().container().appendTo('#example1_wrapper .row:eq(0)');
                }
            }

            // Initial Load
            initDataTable();

            // Function to fetch data via AJAX
            function fetchResults(url) {
                let fetchUrl = url || "{{ route('admin.purchases.index') }}?" + $('#filterForm').serialize();

                $.ajax({
                    url: fetchUrl,
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {

                        // 1. Clear the table via API
                        table.clear();

                        // 2. Convert HTML to DOM nodes
                        var newRows = $(response.html).filter('tr');

                        // 3. Smart Add: Check if the returned row is a "No records" colspan row
                        var isEmptyRow = newRows.length === 1 && newRows.find('td').attr('colspan');

                        if (!isEmptyRow) {
                            // DataTables expects an Array of nodes, not a jQuery object
                            table.rows.add(newRows.toArray());
                        }

                        // 4. Redraw
                        table.draw();

                        // 5. Update Pagination
                        $('#pagination-container').html(response.pagination);
                    },
                    error: function(xhr) {
                        console.error("Error loading data:", xhr);
                    }
                });
            }

            // Event Listeners for Filters
            $('#filterForm select, #filterForm input[type="date"]').on('change', function() {
                fetchResults();
            });

            // Debounce for Text Search
            let timeout = null;
            $('#filterForm input[name="search"]').on('keyup', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    fetchResults();
                }, 500);
            });

            // Handle Pagination Links Click
            $(document).on('click', '#pagination-container a', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                if (url) {
                    fetchResults(url);
                }
            });

            // Prevent Form Submit (Enter key)
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                fetchResults();
            });
        });
    </script>
@stop
