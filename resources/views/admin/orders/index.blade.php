@extends('adminlte::page')

@section('title', 'Pedidos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clipboard-list mr-2"></i> Control de Pedidos</h1>
        <div>
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </a>
        </div>
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
         KPIs OPERATIVOS
    ======================================== --}}
    <div class="row">
        {{-- 0. BORRADORES (Gray) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-secondary kpi-filter" data-status="draft" style="cursor: pointer;"
                title="Pedidos en borrador, pendientes de confirmar.">
                <div class="inner">
                    <h3>{{ $kpis['borradores'] ?? 0 }}</h3>
                    <p>Borradores</p>
                </div>
                <div class="icon"><i class="fas fa-edit"></i></div>
            </div>
        </div>

        {{-- 1. CONFIRMADOS (Blue) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-primary kpi-filter" data-status="confirmed" style="cursor: pointer;"
                title="Requisitos comerciales completos. El inventario se valida al iniciar producción.">
                <div class="inner">
                    <h3>{{ $kpis['para_producir'] }}</h3>
                    <p>Confirmados</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            </div>
        </div>

        {{-- 2. EN PRODUCCION (Purple) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box kpi-filter" data-status="in_production"
                style="background-color: #6610f2; color: white; cursor: pointer;">
                <div class="inner">
                    <h3>{{ $kpis['en_produccion'] }}</h3>
                    <p>En Produccion</p>
                </div>
                <div class="icon"><i class="fas fa-cogs"></i></div>
            </div>
        </div>

        {{-- 3. PARA ENTREGAR (Green) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-success kpi-filter" data-status="ready" style="cursor: pointer;">
                <div class="inner">
                    <h3>{{ $kpis['para_entregar'] }}</h3>
                    <p>Listo para entregar</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
            </div>
        </div>

        {{-- 4. BLOQUEADOS (Red) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-danger kpi-filter" data-blocked="1" style="cursor: pointer;">
                <div class="inner">
                    <h3>{{ $kpis['bloqueados'] }}</h3>
                    <p>Bloqueados</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
            </div>
        </div>

        {{-- 5. RETRASADOS (Warning/Yellow with White Text) --}}
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-warning kpi-filter text-white" data-delayed="1" style="cursor: pointer;">
                <div class="inner">
                    <h3 class="text-white">{{ $kpis['retrasados'] }}</h3>
                    <p class="text-white">Retrasados</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
    </div>

    {{-- ========================================
         CARD CON FILTROS + TABLA
    ======================================== --}}
    <div class="card card-outline card-primary mb-0">
        {{-- FILTROS (fijos, no se recargan con AJAX) --}}
        <div class="card-header py-2" style="background: #fff;">
            <div class="row align-items-center">
                {{-- Prioridad --}}
                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select id="filter-urgency" class="form-control form-control-sm" style="font-size: 14px;">
                        <option value="">-- Prioridad --</option>
                        <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="urgente" {{ request('urgency') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        <option value="express" {{ request('urgency') == 'express' ? 'selected' : '' }}>Express</option>
                    </select>
                </div>

                {{-- Estado --}}
                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select id="filter-status" class="form-control form-control-sm" style="font-size: 14px;">
                        <option value="">-- Estado --</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                        <option value="in_production" {{ request('status') == 'in_production' ? 'selected' : '' }}>En Producción</option>
                        <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Listo</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Entregado</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>

                {{-- Pago --}}
                <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                    <select id="filter-payment" class="form-control form-control-sm" style="font-size: 14px;">
                        <option value="">-- Pago --</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Parcial</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Pagado</option>
                    </select>
                </div>

                {{-- Limpiar --}}
                <div class="col-md-auto col-sm-6 mb-2 mb-md-0">
                    <button type="button" id="filter-clear" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        {{-- TABLA (se recarga via AJAX) --}}
        <div id="orders-table-container">
            @include('admin.orders._table')
        </div>
    </div>

    {{-- Modal de Pago (Reutilizado del show) --}}
    @include('admin.orders._payment-modal')

    {{-- Modal de Entrega Rapida --}}
    @include('admin.orders._delivery-modal')
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

        #orders-table-container {
            position: relative;
            min-height: 200px;
        }

        #orders-table-container.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 10;
        }

        #orders-table-container.loading::before {
            content: 'Cargando...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 11;
            font-weight: bold;
            color: #333;
        }

        .filter-toggle.active {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.5);
        }

        /* DataTables - Botones de exportación (igual que proveedores) */
        #ordersTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        #ordersTable_wrapper .dt-buttons .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        .btn-default {
            background-color: #6e7176;
            color: #fff;
            border: none;
        }

        /* Ocultar paginación de DataTables (usamos la del servidor) */
        #ordersTable_wrapper .dataTables_paginate {
            display: none;
        }
    </style>
@stop

@section('js')
    <script>
        (function() {
            'use strict';

            var baseUrl = '{{ route('admin.orders.index') }}';
            var container = document.getElementById('orders-table-container');
            var currentFilters = {};

            // ========================================
            // FUNCIÓN AJAX PARA CARGAR TABLA
            // ========================================
            function loadTable(filters) {
                filters = filters || {};
                currentFilters = filters;

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
                        // Actualizar URL sin recargar
                        window.history.replaceState({}, '', url);
                        // Reinicializar DataTable
                        initDataTable();
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                    })
                    .finally(function() {
                        container.classList.remove('loading');
                    });
            }

            // ========================================
            // FUNCIÓN PARA OBTENER FILTROS ACTUALES
            // ========================================
            function getFiltersFromForm() {
                var filters = {};
                var status = document.getElementById('filter-status');
                var payment = document.getElementById('filter-payment');
                var urgency = document.getElementById('filter-urgency');
                var blocked = document.getElementById('filter-blocked');
                var delayed = document.getElementById('filter-delayed');

                if (status && status.value) filters.status = status.value;
                if (payment && payment.value) filters.payment_status = payment.value;
                if (urgency && urgency.value) filters.urgency = urgency.value;
                if (blocked && blocked.classList.contains('active')) filters.blocked = '1';
                if (delayed && delayed.classList.contains('active')) filters.delayed = '1';

                return filters;
            }

            // ========================================
            // INICIALIZAR DATATABLE (igual que proveedores)
            // ========================================
            function initDataTable() {
                var table = container.querySelector('table');
                if (!table) return;

                if (!table.id) {
                    table.id = 'ordersTable';
                }

                var tableId = '#' + table.id;

                // Destruir instancia anterior si existe
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }

                // Verificar que la tabla tenga filas válidas (no solo la fila vacía con colspan)
                var tbody = table.querySelector('tbody');
                var rows = tbody ? tbody.querySelectorAll('tr') : [];
                var hasValidRows = false;

                for (var i = 0; i < rows.length; i++) {
                    var cells = rows[i].querySelectorAll('td');
                    // Si tiene 11 celdas (todas las columnas), es una fila válida
                    if (cells.length === 11) {
                        hasValidRows = true;
                        break;
                    }
                }

                // Si no hay filas válidas, no inicializar DataTables para evitar error
                if (!hasValidRows) {
                    console.log('[DataTable] Sin filas válidas, omitiendo inicialización');
                    return;
                }

                // Inicializar DataTable igual que proveedores
                // Nota: paging:false porque usamos paginación del servidor via AJAX
                $(tableId).DataTable({
                    "paging": false,
                    "language": {
                        "emptyTable": "No hay pedidos",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ Pedidos",
                        "infoEmpty": "Mostrando 0 a 0 de 0 Pedidos",
                        "infoFiltered": "(Filtrado de _MAX_ total Pedidos)",
                        "lengthMenu": "Mostrar _MENU_ Pedidos",
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
                    "lengthChange": false,
                    "autoWidth": false,
                    buttons: [{
                            text: '<i class="fas fa-copy"></i> COPIAR',
                            extend: 'copy',
                            className: 'btn btn-default'
                        },
                        {
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            extend: 'pdf',
                            className: 'btn btn-danger',
                            title: 'Control de Pedidos',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-file-csv"></i> CSV',
                            extend: 'csv',
                            className: 'btn btn-info',
                            title: 'Control de Pedidos',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-file-excel"></i> EXCEL',
                            extend: 'excel',
                            className: 'btn btn-success',
                            title: 'Control de Pedidos',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-print"></i> IMPRIMIR',
                            extend: 'print',
                            className: 'btn btn-default',
                            title: 'Control de Pedidos',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        }
                    ]
                }).buttons().container().appendTo(tableId + '_wrapper .row:eq(0)');
            }

            // ========================================
            // EVENTOS DE FILTROS
            // ========================================
            document.addEventListener('DOMContentLoaded', function() {
                // Selects
                ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('change', function() {
                            loadTable(getFiltersFromForm());
                        });
                    }
                });

                // Toggles
                ['filter-blocked', 'filter-delayed'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('click', function(e) {
                            e.preventDefault();
                            this.classList.toggle('active');
                            loadTable(getFiltersFromForm());
                        });
                    }
                });

                // Limpiar filtros
                var clearBtn = document.getElementById('filter-clear');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Reset selects
                        ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(id) {
                            var el = document.getElementById(id);
                            if (el) el.value = '';
                        });
                        // Reset toggles
                        ['filter-blocked', 'filter-delayed'].forEach(function(id) {
                            var el = document.getElementById(id);
                            if (el) el.classList.remove('active');
                        });
                        loadTable({});
                    });
                }

                // KPI clicks
                document.querySelectorAll('.kpi-filter').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        var filters = {};

                        // Reset form controls
                        ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(
                            id) {
                            var input = document.getElementById(id);
                            if (input) input.value = '';
                        });
                        ['filter-blocked', 'filter-delayed'].forEach(function(id) {
                            var input = document.getElementById(id);
                            if (input) input.classList.remove('active');
                        });

                        // Aplicar filtro del KPI
                        if (this.dataset.status) {
                            filters.status = this.dataset.status;
                            var statusSelect = document.getElementById('filter-status');
                            if (statusSelect) statusSelect.value = this.dataset.status;
                        }
                        if (this.dataset.blocked) {
                            filters.blocked = '1';
                            var blockedBtn = document.getElementById('filter-blocked');
                            if (blockedBtn) blockedBtn.classList.add('active');
                        }
                        if (this.dataset.delayed) {
                            filters.delayed = '1';
                            var delayedBtn = document.getElementById('filter-delayed');
                            if (delayedBtn) delayedBtn.classList.add('active');
                        }

                        loadTable(filters);
                    });
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

                // Inicializar DataTable
                initDataTable();

                // ========================================
                // BOTÓN DE PAGO RÁPIDO (Delegación)
                // ========================================
                container.addEventListener('click', function(e) {
                    var btn = e.target.closest('.btn-quick-payment');
                    if (btn) {
                        e.preventDefault();
                        var orderId = btn.dataset.orderId;
                        var orderNumber = btn.dataset.orderNumber;
                        var balance = parseFloat(btn.dataset.balance);

                        initPaymentModal(orderId, orderNumber, balance);
                        $('#modalPayment').modal('show');
                    }
                });

                // ========================================
                // BOTÓN DE ENTREGA RÁPIDA (Delegación)
                // ========================================
                container.addEventListener('click', function(e) {
                    var btn = e.target.closest('.btn-quick-delivery');
                    if (btn) {
                        e.preventDefault();
                        var orderId = btn.dataset.orderId;
                        var orderNumber = btn.dataset.orderNumber;

                        if (typeof window.initDeliveryModal === 'function') {
                            window.initDeliveryModal(orderId, orderNumber);
                        }
                        $('#modalDelivery').modal('show');
                    }
                });
            });

            // ========================================
            // FUNCIÓN PARA INICIALIZAR MODAL DE PAGO
            // ========================================
            function initPaymentModal(orderId, orderNumber, balance) {
                var form = document.getElementById('paymentForm');
                var numberEl = document.getElementById('paymentOrderNumber');
                var amountEl = document.getElementById('paymentAmount');
                var referenceEl = document.getElementById('paymentReference');
                var notesEl = document.getElementById('paymentNotes');
                var methodEl = form ? form.querySelector('[name="payment_method"]') : null;

                if (form) {
                    form.action = '/admin/orders/' + orderId + '/payments';
                }
                if (numberEl) {
                    numberEl.textContent = orderNumber;
                }
                if (amountEl) {
                    amountEl.value = '';
                    amountEl.max = balance;
                }
                // Reset campos
                if (referenceEl) referenceEl.value = '';
                if (notesEl) notesEl.value = '';
                if (methodEl) methodEl.value = 'cash';

                // Establecer saldo original, orderId y modo AJAX para validación en tiempo real
                if (typeof window.setPaymentOriginalBalance === 'function') {
                    window.setPaymentOriginalBalance(balance, orderId, true); // true = usar AJAX
                }
            }
        })();
    </script>
@stop
