@extends('adminlte::page')

@section('title', 'Cola de Produccion')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-industry mr-2"></i> Cola de Produccion</h1>
        <small class="text-muted">Actualizado: {{ now()->format('d/m/Y H:i') }}</small>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- RESUMEN DE COLA --}}
    <div class="row mb-3">
        {{-- 1. TOTAL (Info/Cyan) - Clickeable para mostrar todos --}}
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info mb-0 kpi-filter" data-show-all="1" style="cursor: pointer;">
                <div class="inner">
                    <h3>{{ $summary['total_queue'] }}</h3>
                    <p>Total Cola</p>
                </div>
                <div class="icon"><i class="fas fa-list"></i></div>
            </div>
        </div>

        {{-- 2. CONFIRMADOS (Primary/Blue) --}}
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary mb-0 kpi-filter" data-status="confirmed" style="cursor: pointer;">
                <div class="inner">
                    <h3>{{ $summary['confirmed'] }}</h3>
                    <p>Confirmados</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            </div>
        </div>

        {{-- 3. EN PRODUCCION (Purple - Matches Index) --}}
        <div class="col-lg-2 col-6">
            <div class="small-box mb-0 kpi-filter" data-status="in_production"
                style="background-color: #6610f2; color: white; cursor: pointer;">
                <div class="inner">
                    <h3>{{ $summary['in_production'] }}</h3>
                    <p>En Produccion</p>
                </div>
                <div class="icon"><i class="fas fa-cogs"></i></div>
            </div>
        </div>

        {{-- 4. BLOQUEADOS (Danger/Red) --}}
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger mb-0 kpi-filter" data-blocked="1" style="cursor: pointer;">
                <div class="inner">
                    <h3>{{ $summary['blocked_count'] }}</h3>
                    <p>Bloqueados</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
            </div>
        </div>

        {{-- 5. RETRASADOS (Warning/Yellow with White Text) --}}
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning text-white mb-0 kpi-filter" data-overdue="1" style="cursor: pointer;">
                <div class="inner">
                    <h3 class="text-white">{{ $summary['overdue_count'] }}</h3>
                    <p class="text-white">Retrasados</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>

        {{-- 6. URGENTES (Orange - Priority) --}}
        <div class="col-lg-2 col-6">
            <div class="small-box mb-0 kpi-filter" data-urgency="urgent_express"
                style="background-color: #fd7e14; color: white; cursor: pointer;">
                <div class="inner">
                    <h3>{{ $summary['urgent_count'] }}</h3>
                    <p>Urgentes</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <select id="filter-status" class="form-control form-control-sm">
                        <option value="">Todos los estados</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmados
                        </option>
                        <option value="in_production" {{ request('status') == 'in_production' ? 'selected' : '' }}>En
                            Produccion</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select id="filter-urgency" class="form-control form-control-sm">
                        <option value="">Todas las prioridades</option>
                        <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="urgente" {{ request('urgency') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        <option value="express" {{ request('urgency') == 'express' ? 'selected' : '' }}>Express</option>
                    </select>
                </div>

                <div class="col-auto">
                    <a href="#" id="filter-clear" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE COLA (Contenedor AJAX) --}}
    <div id="queue-table-container">
        @include('admin.production._queue-table')
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

        .table-warning {
            background-color: #fff3cd !important;
        }

        #queue-table-container {
            position: relative;
            min-height: 200px;
        }

        #queue-table-container.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 10;
        }

        #queue-table-container.loading::before {
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
        #queueTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        #queueTable_wrapper .dt-buttons .btn {
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
        #queueTable_wrapper .dataTables_paginate {
            display: none;
        }
    </style>
@stop

@section('js')
    <script>
        (function() {
            'use strict';

            var baseUrl = '{{ route('admin.production.queue') }}';
            var container = document.getElementById('queue-table-container');
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
                        // Reinicializar tooltips
                        $('[data-toggle="tooltip"]').tooltip();
                        // Reinicializar unit toggles para modales nuevos
                        initUnitToggles();
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
                var urgency = document.getElementById('filter-urgency');
                var blocked = document.getElementById('filter-blocked');

                if (status && status.value) filters.status = status.value;
                if (urgency && urgency.value) filters.urgency = urgency.value;
                if (blocked && blocked.checked) filters.blocked = '1';

                return filters;
            }

            // ========================================
            // INICIALIZAR UNIT TOGGLES (para modales dinámicos con selección)
            // ========================================
            var queueAppliedConversions = {};

            function initUnitToggles() {
                document.querySelectorAll('.unit-toggle').forEach(function(toggleGroup) {
                    const modal = toggleGroup.closest('.modal');
                    if (!modal) return;

                    // Clonar y reemplazar botones para limpiar listeners anteriores
                    toggleGroup.querySelectorAll('[data-unit-mode]').forEach(function(btn) {
                        var newBtn = btn.cloneNode(true);
                        btn.parentNode.replaceChild(newBtn, btn);
                    });

                    const buttons = toggleGroup.querySelectorAll('[data-unit-mode]');
                    const btnConsumption = toggleGroup.querySelector('[data-unit-mode="consumption"]');
                    const btnCompra = toggleGroup.querySelector('[data-unit-mode="base"]');

                    // Obtener materiales únicos (padres) del modal
                    function getUniqueMaterials() {
                        const materialsMap = new Map();
                        modal.querySelectorAll('[data-material-id]').forEach(function(el) {
                            const id = parseInt(el.dataset.materialId);
                            if (id > 0 && !materialsMap.has(id)) {
                                // Usar data-material-name que contiene el nombre del material padre
                                const name = el.dataset.materialName || 'Material #' + id;
                                materialsMap.set(id, { id: id, name: name });
                            }
                        });
                        return Array.from(materialsMap.values());
                    }

                    // Click en Consumo
                    if (btnConsumption) {
                        btnConsumption.addEventListener('click', function() {
                            buttons.forEach(function(b) {
                                b.classList.remove('btn-primary', 'active');
                                b.classList.add('btn-outline-primary');
                            });
                            this.classList.remove('btn-outline-primary');
                            this.classList.add('btn-primary', 'active');

                            // Restaurar valores originales
                            modal.querySelectorAll('.unit-convertible, .unit-convertible-badge').forEach(function(el) {
                                const qty = parseFloat(el.dataset.qty) || 0;
                                const unitConsumption = el.dataset.unitConsumption || 'u';
                                const reservedThis = parseFloat(el.dataset.reservedThis) || 0;

                                const qtyEl = el.querySelector('.qty-value');
                                const unitEl = el.querySelector('.unit-symbol');
                                if (qtyEl) qtyEl.textContent = formatNumber(qty);
                                if (unitEl) unitEl.textContent = unitConsumption;

                                const reservedThisQty = el.querySelector('.reserved-this-qty');
                                if (reservedThisQty && reservedThis > 0) {
                                    reservedThisQty.textContent = formatNumber(reservedThis);
                                }
                            });
                        });
                    }

                    // Click en Compra: abrir modal
                    if (btnCompra) {
                        btnCompra.addEventListener('click', function() {
                            const materials = getUniqueMaterials();

                            if (materials.length === 0) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Sin materiales',
                                    text: 'No hay materiales en este modal',
                                    confirmButtonColor: '#3085d6'
                                });
                                return;
                            }

                            openUnitConversionModal(materials, function(materialId, conversion) {
                                queueAppliedConversions[materialId] = {
                                    factor: conversion.conversion_factor,
                                    unitSymbol: conversion.from_unit_symbol || conversion.label
                                };

                                buttons.forEach(function(b) {
                                    b.classList.remove('btn-primary', 'active');
                                    b.classList.add('btn-outline-primary');
                                });
                                btnCompra.classList.remove('btn-outline-primary');
                                btnCompra.classList.add('btn-primary', 'active');

                                // Aplicar conversión solo al material seleccionado
                                modal.querySelectorAll('[data-material-id="' + materialId + '"].unit-convertible, [data-material-id="' + materialId + '"].unit-convertible-badge').forEach(function(el) {
                                    const qty = parseFloat(el.dataset.qty) || 0;
                                    const factor = conversion.conversion_factor || 1;
                                    const reservedThis = parseFloat(el.dataset.reservedThis) || 0;
                                    const displayQty = qty / factor;
                                    const displayUnit = conversion.from_unit_symbol || conversion.label;

                                    const qtyEl = el.querySelector('.qty-value');
                                    const unitEl = el.querySelector('.unit-symbol');
                                    if (qtyEl) qtyEl.textContent = formatNumber(displayQty);
                                    if (unitEl) unitEl.textContent = displayUnit;

                                    const reservedThisQty = el.querySelector('.reserved-this-qty');
                                    if (reservedThisQty && reservedThis > 0) {
                                        reservedThisQty.textContent = formatNumber(reservedThis / factor);
                                    }
                                });
                            });
                        });
                    }
                });
            }

            function formatNumber(num) {
                return num.toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // ========================================
            // INICIALIZAR DATATABLE (igual que proveedores)
            // ========================================
            function initDataTable() {
                var table = container.querySelector('table');
                if (!table) return;

                if (!table.id) {
                    table.id = 'queueTable';
                }

                var tableId = '#' + table.id;

                // Destruir instancia anterior si existe
                if ($.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }

                // Inicializar DataTable igual que proveedores
                // Nota: paging:false porque usamos paginación del servidor via AJAX
                $(tableId).DataTable({
                    "paging": false,
                    "language": {
                        "emptyTable": "No hay pedidos en cola",
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
                            title: 'Cola de Produccion',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-file-csv"></i> CSV',
                            extend: 'csv',
                            className: 'btn btn-info',
                            title: 'Cola de Produccion',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-file-excel"></i> EXCEL',
                            extend: 'excel',
                            className: 'btn btn-success',
                            title: 'Cola de Produccion',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        },
                        {
                            text: '<i class="fas fa-print"></i> IMPRIMIR',
                            extend: 'print',
                            className: 'btn btn-default',
                            title: 'Cola de Produccion',
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
                // Inicializar tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // Selects con evento change (AJAX)
                ['filter-status', 'filter-urgency'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('change', function() {
                            loadTable(getFiltersFromForm());
                        });
                    }
                });

                // Checkbox bloqueados con evento change (AJAX)
                var blockedCheck = document.getElementById('filter-blocked');
                if (blockedCheck) {
                    blockedCheck.addEventListener('change', function() {
                        loadTable(getFiltersFromForm());
                    });
                }

                // Limpiar filtros
                var clearBtn = document.getElementById('filter-clear');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Reset selects
                        ['filter-status', 'filter-urgency'].forEach(function(id) {
                            var el = document.getElementById(id);
                            if (el) el.value = '';
                        });
                        // Reset checkbox
                        if (blockedCheck) blockedCheck.checked = false;
                        loadTable({});
                    });
                }

                // KPI clicks (AJAX)
                document.querySelectorAll('.kpi-filter').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        var filters = {};

                        // Reset form controls
                        ['filter-status', 'filter-urgency'].forEach(function(id) {
                            var input = document.getElementById(id);
                            if (input) input.value = '';
                        });
                        var blockedInput = document.getElementById('filter-blocked');
                        if (blockedInput) blockedInput.checked = false;

                        // Aplicar filtro del KPI
                        if (this.dataset.showAll) {
                            // "Total Cola" - mostrar todos sin filtros
                            loadTable({});
                            return;
                        }
                        if (this.dataset.status) {
                            filters.status = this.dataset.status;
                            var statusSelect = document.getElementById('filter-status');
                            if (statusSelect) statusSelect.value = this.dataset.status;
                        }
                        if (this.dataset.blocked) {
                            filters.blocked = '1';
                            if (blockedInput) blockedInput.checked = true;
                        }
                        if (this.dataset.urgency) {
                            filters.urgency = this.dataset.urgency;
                            var urgencySelect = document.getElementById('filter-urgency');
                            if (urgencySelect) urgencySelect.value = this.dataset.urgency;
                        }
                        if (this.dataset.overdue) {
                            filters.overdue = '1';
                        }

                        loadTable(filters);
                    });
                });

                // Paginación AJAX (delegación de eventos)
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

                // Inicializar unit toggles
                initUnitToggles();

                // Inicializar DataTable
                initDataTable();
            });
        })();
    </script>
@stop
