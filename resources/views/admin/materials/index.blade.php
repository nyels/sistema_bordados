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
                    <a href="{{ route('admin.materials.create-wizard') }}" class="btn btn-primary mr-2 mb-2">
                        <i class="fas fa-magic"></i> Nuevo Material
                    </a>
                    <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary mr-2 mb-2">
                        <i class="fas fa-layer-group"></i> Categorías
                    </a>
                    <a href="{{ route('admin.units.index') }}" class="btn btn-secondary mr-3 mb-2">
                        <i class="fas fa-ruler"></i> Unidades
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
                            <th style="width: 100px;">U. Compra</th>
                            <th style="width: 150px;" title="Cómo se convierte la unidad de compra a inventario">Cómo se compra</th>
                            <th style="width: 80px;">Variantes</th>
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

    {{-- MODAL: Vista rápida de variantes --}}
    <div class="modal fade" id="modalVariants" tabindex="-1" role="dialog" aria-labelledby="modalVariantsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title" id="modalVariantsLabel" style="font-weight: bold; font-size: 18px;">
                        <i class="fas fa-palette"></i> VARIANTES DE: <span id="modal-material-name">-</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="variants-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                        <p class="mt-2 text-muted">Cargando variantes...</p>
                    </div>
                    <div id="variants-content" style="display: none;">
                        <div class="px-3 py-2 bg-dark text-white border-bottom">
                            <span style="font-size: 1rem;">
                                <i class="fas fa-info-circle"></i>
                                Categoría: <strong id="modal-category">-</strong> |
                                Unidad: <strong id="modal-unit">-</strong> |
                                Total: <strong id="modal-total">0</strong> variantes
                            </span>
                        </div>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm mb-0" id="variants-table">
                                <thead class="thead-dark" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">#</th>
                                        <th class="text-center">SKU</th>
                                        <th class="text-center">Color</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Mín.</th>
                                        <th class="text-center">Costo Prom.</th>
                                        <th class="text-center">Valor</th>
                                        <th class="text-center" style="width: 60px;">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="variants-table-body">
                                    {{-- Filas cargadas via AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="variants-empty" class="text-center py-4" style="display: none;">
                        <i class="fas fa-inbox fa-3x text-muted"></i>
                        <p class="mt-2 text-muted">No hay variantes registradas</p>
                    </div>
                    <div id="variants-error" class="text-center py-4" style="display: none;">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                        <p class="mt-2 text-danger">Error al cargar variantes</p>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <a href="#" id="btn-go-variants" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt"></i> Ir a Variantes
                    </a>
                    <button type="button" class="btn btn-dark btn-sm" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
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

            // =====================================================
            // MODAL: Vista rápida de variantes
            // =====================================================
            var currentMaterialId = null;

            $(document).on('click', '.btn-variants-modal', function() {
                var materialId = $(this).data('material-id');
                var materialName = $(this).data('material-name');
                currentMaterialId = materialId;

                // Reset modal state
                $('#modal-material-name').text(materialName);
                $('#variants-loading').show();
                $('#variants-content').hide();
                $('#variants-empty').hide();
                $('#variants-error').hide();
                $('#variants-table-body').empty();
                $('#btn-go-variants').attr('href', '/admin/materials/' + materialId + '/variants');

                // Show modal
                $('#modalVariants').modal('show');

                // Fetch variants via AJAX
                $.ajax({
                    url: '/admin/materials/' + materialId + '/variants-modal',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#variants-loading').hide();

                        if (!response.success) {
                            $('#variants-error').show();
                            return;
                        }

                        if (response.variants.length === 0) {
                            $('#variants-empty').show();
                            return;
                        }

                        // Update header info
                        $('#modal-category').text(response.material.category);
                        $('#modal-unit').text(response.material.unit_symbol);
                        $('#modal-total').text(response.total);

                        // Build table rows
                        var html = '';
                        response.variants.forEach(function(v, index) {
                            var statusBadge = v.stock_status === 'low'
                                ? '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Bajo</span>'
                                : '<span class="badge badge-success"><i class="fas fa-check"></i> OK</span>';

                            html += '<tr class="' + (v.stock_status === 'low' ? 'table-warning' : '') + '">';
                            html += '<td class="text-center align-middle">' + (index + 1) + '</td>';
                            html += '<td class="text-center align-middle"><code>' + v.sku + '</code></td>';
                            html += '<td class="text-center align-middle">' + v.color + '</td>';
                            html += '<td class="text-center align-middle">' + formatNumber(v.current_stock) + ' ' + response.material.unit_symbol + '</td>';
                            html += '<td class="text-center align-middle">' + formatNumber(v.min_stock_alert) + ' ' + response.material.unit_symbol + '</td>';
                            html += '<td class="text-center align-middle">$' + formatNumber(v.average_cost, 2) + '</td>';
                            html += '<td class="text-center align-middle">$' + formatNumber(v.current_value, 2) + '</td>';
                            html += '<td class="text-center align-middle">' + statusBadge + '</td>';
                            html += '</tr>';
                        });

                        $('#variants-table-body').html(html);
                        $('#variants-content').show();
                    },
                    error: function(xhr) {
                        console.error('Error loading variants:', xhr);
                        $('#variants-loading').hide();
                        $('#variants-error').show();
                    }
                });
            });

            // Helper: Format number
            function formatNumber(num, decimals) {
                decimals = decimals !== undefined ? decimals : 2;
                return parseFloat(num).toLocaleString('es-MX', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }
        });
    </script>
@stop
