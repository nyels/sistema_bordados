@extends('adminlte::page')

@section('title', 'Historial Ventas POS')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-history mr-2"></i> Historial Ventas POS</h1>
        <a href="{{ route('pos.index') }}" class="btn btn-primary">
            <i class="fas fa-cash-register"></i> Ir al POS
        </a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ========================================
         KPIs DEL DIA
    ======================================== --}}
    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpis['ventas_hoy'] }}</h3>
                    <p>Ventas Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>${{ number_format($kpis['total_hoy'], 2) }}</h3>
                    <p>Total Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $kpis['canceladas_hoy'] }}</h3>
                    <p>Canceladas Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($kpis['total_historico']) }}</h3>
                    <p>Total Historico</p>
                </div>
                <div class="icon"><i class="fas fa-archive"></i></div>
            </div>
        </div>
    </div>

    {{-- ========================================
         FILTROS
    ======================================== --}}
    <div class="card card-outline card-primary collapsed-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row">
                <div class="col-md-3">
                    <label for="filter-fecha-desde">Fecha Desde</label>
                    <input type="date" class="form-control" id="filter-fecha-desde" name="fecha_desde"
                        value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-3">
                    <label for="filter-fecha-hasta">Fecha Hasta</label>
                    <input type="date" class="form-control" id="filter-fecha-hasta" name="fecha_hasta"
                        value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-3">
                    <label for="filter-vendedor">Vendedor</label>
                    <select class="form-control" id="filter-vendedor" name="vendedor_id">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}"
                                {{ request('vendedor_id') == $vendedor->id ? 'selected' : '' }}>
                                {{ $vendedor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-estado">Estado</label>
                    <select class="form-control" id="filter-estado" name="estado">
                        <option value="">Todas</option>
                        <option value="activas" {{ request('estado') == 'activas' ? 'selected' : '' }}>
                            Activas
                        </option>
                        <option value="canceladas" {{ request('estado') == 'canceladas' ? 'selected' : '' }}>
                            Canceladas
                        </option>
                    </select>
                </div>
                <div class="col-12 mt-3 text-right">
                    <button type="button" id="filter-clear" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================
         TABLA DE VENTAS POS
    ======================================== --}}
    <div class="card">
        <div class="card-body p-0">
            <div id="pos-sales-table-container">
                @include('admin.pos-sales._table')
            </div>
        </div>
    </div>

    {{-- ========================================
         MODAL DE CANCELACION
    ======================================== --}}
    <div class="modal fade" id="modalCancelSale" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Cancelar Venta POS
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>ADVERTENCIA:</strong> Cancelar una venta POS revierte el inventario de productos terminados.
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <p class="mb-1"><strong>Pedido:</strong> <span id="cancel-order-number">--</span></p>
                        <p class="mb-1"><strong>Fecha:</strong> <span id="cancel-order-date">--</span></p>
                        <p class="mb-1"><strong>Total:</strong> $<span id="cancel-order-total">--</span></p>
                        <p class="mb-0"><strong>Vendedor:</strong> <span id="cancel-order-seller">--</span></p>
                    </div>

                    <input type="hidden" id="cancel-order-id" value="">

                    <div class="form-group">
                        <label for="cancel-reason">
                            Motivo de Cancelacion <span class="text-danger">*</span>
                            <small class="text-muted">(minimo 10 caracteres)</small>
                        </label>
                        <textarea class="form-control" id="cancel-reason" rows="3" maxlength="255"
                            placeholder="Explique el motivo de la cancelacion..."></textarea>
                        <small class="text-muted"><span id="cancel-reason-count">0</span>/255</small>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="cancel-confirm-checkbox">
                        <label class="custom-control-label text-danger" for="cancel-confirm-checkbox">
                            Entiendo que esta accion revertira el stock y no puede deshacerse.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger" id="btn-execute-cancel" disabled>
                        <i class="fas fa-ban mr-1"></i> Cancelar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box .inner h3 {
            font-size: 2.2rem;
            font-weight: bold;
        }

        .small-box .inner p {
            font-size: 15px;
        }

        #pos-sales-table-container {
            position: relative;
            min-height: 200px;
        }

        #pos-sales-table-container.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 10;
        }

        #pos-sales-table-container.loading::before {
            content: 'Cargando...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 11;
            font-weight: bold;
            color: #333;
        }

        .badge-cancelled {
            background-color: #dc3545;
            color: white;
        }

        .badge-active {
            background-color: #28a745;
            color: white;
        }
    </style>
@stop

@section('js')
    <script>
        (function() {
            'use strict';

            var baseUrl = '{{ route('admin.pos-sales.index') }}';
            var container = document.getElementById('pos-sales-table-container');

            // ========================================
            // FUNCION AJAX PARA CARGAR TABLA
            // ========================================
            function loadTable(filters) {
                filters = filters || {};

                var params = new URLSearchParams();
                Object.keys(filters).forEach(function(key) {
                    if (filters[key]) {
                        params.append(key, filters[key]);
                    }
                });

                var url = baseUrl + (params.toString() ? '?' + params.toString() : '');

                container.classList.add('loading');

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(function(response) {
                        return response.text();
                    })
                    .then(function(html) {
                        container.innerHTML = html;
                        window.history.replaceState({}, '', url);
                        initDataTable();
                        bindCancelButtons();
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                    })
                    .finally(function() {
                        container.classList.remove('loading');
                    });
            }

            // ========================================
            // OBTENER FILTROS DEL FORM
            // ========================================
            function getFiltersFromForm() {
                var filters = {};
                var fechaDesde = document.getElementById('filter-fecha-desde');
                var fechaHasta = document.getElementById('filter-fecha-hasta');
                var vendedor = document.getElementById('filter-vendedor');
                var estado = document.getElementById('filter-estado');

                if (fechaDesde && fechaDesde.value) filters.fecha_desde = fechaDesde.value;
                if (fechaHasta && fechaHasta.value) filters.fecha_hasta = fechaHasta.value;
                if (vendedor && vendedor.value) filters.vendedor_id = vendedor.value;
                if (estado && estado.value) filters.estado = estado.value;

                return filters;
            }

            // ========================================
            // INICIALIZAR DATATABLE
            // ========================================
            function initDataTable() {
                var table = container.querySelector('table');
                if (!table) return;

                if (!table.id) {
                    table.id = 'posSalesTable';
                }

                var tableId = '#' + table.id;

                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }

                $(tableId).DataTable({
                    "paging": false,
                    "language": {
                        "emptyTable": "No hay ventas POS",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ ventas",
                        "infoEmpty": "Mostrando 0 a 0 de 0 ventas",
                        "infoFiltered": "(Filtrado de _MAX_ total ventas)",
                        "search": "Buscador:",
                        "zeroRecords": "Sin resultados encontrados"
                    },
                    "responsive": true,
                    "lengthChange": false,
                    "autoWidth": false,
                    buttons: [{
                            text: '<i class="fas fa-file-excel"></i> EXCEL',
                            extend: 'excel',
                            className: 'btn btn-success',
                            title: 'Historial Ventas POS',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-print"></i> IMPRIMIR',
                            extend: 'print',
                            className: 'btn btn-default',
                            title: 'Historial Ventas POS',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        }
                    ]
                }).buttons().container().appendTo(tableId + '_wrapper .row:eq(0)');
            }

            // ========================================
            // BIND BOTONES DE CANCELACION
            // ========================================
            function bindCancelButtons() {
                document.querySelectorAll('.btn-cancel-sale').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var orderId = this.dataset.orderId;
                        var orderNumber = this.dataset.orderNumber;
                        var orderDate = this.dataset.orderDate;
                        var orderTotal = this.dataset.orderTotal;
                        var orderSeller = this.dataset.orderSeller;

                        document.getElementById('cancel-order-id').value = orderId;
                        document.getElementById('cancel-order-number').textContent = orderNumber;
                        document.getElementById('cancel-order-date').textContent = orderDate;
                        document.getElementById('cancel-order-total').textContent = orderTotal;
                        document.getElementById('cancel-order-seller').textContent = orderSeller;

                        // Reset
                        document.getElementById('cancel-reason').value = '';
                        document.getElementById('cancel-reason-count').textContent = '0';
                        document.getElementById('cancel-confirm-checkbox').checked = false;
                        document.getElementById('btn-execute-cancel').disabled = true;

                        $('#modalCancelSale').modal('show');
                    });
                });
            }

            // ========================================
            // EVENTOS
            // ========================================
            document.addEventListener('DOMContentLoaded', function() {
                // Formulario de filtros
                document.getElementById('filter-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    loadTable(getFiltersFromForm());
                });

                // Limpiar filtros
                document.getElementById('filter-clear').addEventListener('click', function() {
                    document.getElementById('filter-fecha-desde').value = '';
                    document.getElementById('filter-fecha-hasta').value = '';
                    document.getElementById('filter-vendedor').value = '';
                    document.getElementById('filter-estado').value = '';
                    loadTable({});
                });

                // Paginacion AJAX
                container.addEventListener('click', function(e) {
                    var link = e.target.closest('.pagination a');
                    if (link) {
                        e.preventDefault();
                        var url = new URL(link.href);
                        var params = {};
                        url.searchParams.forEach(function(value, key) {
                            params[key] = value;
                        });
                        loadTable(params);
                    }
                });

                // Inicializar
                initDataTable();
                bindCancelButtons();

                // ========================================
                // LOGICA DE CANCELACION
                // ========================================
                var cancelReason = document.getElementById('cancel-reason');
                var cancelReasonCount = document.getElementById('cancel-reason-count');
                var cancelConfirm = document.getElementById('cancel-confirm-checkbox');
                var btnExecute = document.getElementById('btn-execute-cancel');

                function updateCancelButtonState() {
                    var hasReason = cancelReason.value.length >= 10;
                    var hasConfirm = cancelConfirm.checked;
                    btnExecute.disabled = !(hasReason && hasConfirm);
                }

                cancelReason.addEventListener('input', function() {
                    cancelReasonCount.textContent = this.value.length;
                    updateCancelButtonState();
                });

                cancelConfirm.addEventListener('change', updateCancelButtonState);

                btnExecute.addEventListener('click', async function() {
                    var orderId = document.getElementById('cancel-order-id').value;
                    var reason = cancelReason.value;

                    if (!orderId || reason.length < 10 || !cancelConfirm.checked) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';

                    try {
                        var response = await fetch('/admin/pos-sales/' + orderId + '/cancel', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                cancel_reason: reason
                            })
                        });

                        var data = await response.json();

                        $('#modalCancelSale').modal('hide');

                        if (data.success) {
                            alert('Venta cancelada correctamente.\nStock revertido: ' + data
                                .data.quantity_returned + ' unidades.');
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }

                    } catch (error) {
                        alert('Error de conexion: ' + error.message);
                    }

                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-ban mr-1"></i> Cancelar Venta';
                });
            });
        })();
    </script>
@stop
