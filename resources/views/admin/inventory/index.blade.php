@extends('adminlte::page')

@section('title', 'Inventario General')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-warehouse mr-2"></i>Inventario General</h1>
        <a href="{{ route('admin.inventory.reservations') }}" class="btn btn-info">
            <i class="fas fa-lock mr-1"></i> Ver Reservas Activas
        </a>
    </div>
@stop

@section('content')
    {{-- RESUMEN --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totals['total_items'] }}</h3>
                    <p>Variantes Activas</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($totals['total_value'], 2) }}</h3>
                    <p>Valor Total Inventario</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $totals['low_stock'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $totals['low_stock'] }}</h3>
                    <p>Bajo Stock Minimo</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totals['total_reserved'], 2) }}</h3>
                    <p>Unidades Reservadas</p>
                </div>
                <div class="icon"><i class="fas fa-lock"></i></div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="filter-category" class="form-control form-control-sm">
                        <option value="">-- Categoria --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter-stock-status" class="form-control form-control-sm">
                        <option value="">-- Estado Stock --</option>
                        <option value="BAJO">Bajo Minimo</option>
                        <option value="OK">OK</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" id="filter-search" class="form-control"
                            placeholder="Buscar SKU, color, material...">
                        <div class="input-group-append">
                            <button type="button" id="btn-search" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-clear-filters" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA PRINCIPAL --}}
    <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">Listado de Materiales</span>
            {{-- Toggle de unidades --}}
            <div class="btn-group btn-group-sm unit-toggle" role="group">
                <button type="button" class="btn btn-primary active" data-unit-mode="consumption">
                    Consumo
                </button>
                <button type="button" class="btn btn-outline-primary" data-unit-mode="base">
                    Compra
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table id="inventoryTable" class="table table-hover table-sm mb-0 materials-table" style="font-size: 16px;">
                <thead style="background-color: #000; color: #fff;">
                    <tr>
                        <th>Material</th>
                        <th>SKU</th>
                        <th>Color</th>
                        <th class="text-right">Stock Fisico</th>
                        <th class="text-right">Reservado</th>
                        <th class="text-right">Disponible</th>
                        <th class="text-right">Valor</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center" style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        @php
                            $reserved = $variant->reserved_stock;
                            $available = $variant->available_stock;
                            $isLow = $variant->current_stock <= $variant->min_stock_alert;
                            // Datos para conversión de unidades
                            $material = $variant->material;
                            $conversionFactor = $material?->conversion_factor ?? 1;
                            $unitConsumption = $material?->consumptionUnit?->symbol
                                            ?? $material?->baseUnit?->symbol
                                            ?? '';
                            $unitBase = $material?->baseUnit?->symbol ?? $unitConsumption;
                        @endphp
                        <tr class="{{ $isLow ? 'table-warning' : '' }}" data-material-id="{{ $material?->id ?? 0 }}" data-material-name="{{ $material?->name ?? 'N/A' }}">
                            <td>
                                <strong>{{ $variant->material?->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $variant->material?->category?->name ?? '' }}</small>
                            </td>
                            <td><code>{{ $variant->sku }}</code></td>
                            <td>
                                @if ($variant->color)
                                    <span class="badge badge-secondary">{{ $variant->color }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right unit-convertible"
                                data-qty="{{ $variant->current_stock }}"
                                data-factor="{{ $conversionFactor }}"
                                data-unit-consumption="{{ $unitConsumption }}"
                                data-unit-base="{{ $unitBase }}">
                                <span class="qty-value">{{ number_format($variant->current_stock, 2) }}</span>
                                <span class="unit-symbol">{{ $unitConsumption }}</span>
                            </td>
                            <td class="text-right">
                                @if ($reserved > 0)
                                    <span style="color: #fd7e14; font-weight: bold;" class="unit-convertible"
                                        data-qty="{{ $reserved }}"
                                        data-factor="{{ $conversionFactor }}"
                                        data-unit-consumption="{{ $unitConsumption }}"
                                        data-unit-base="{{ $unitBase }}">
                                        <span class="qty-value">{{ number_format($reserved, 2) }}</span>
                                        <i class="fas fa-lock ml-1"></i>
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <strong class="{{ $available <= $variant->min_stock_alert ? 'text-danger' : 'text-success' }} unit-convertible"
                                    data-qty="{{ $available }}"
                                    data-factor="{{ $conversionFactor }}"
                                    data-unit-consumption="{{ $unitConsumption }}"
                                    data-unit-base="{{ $unitBase }}">
                                    <span class="qty-value">{{ number_format($available, 2) }}</span>
                                    <span class="unit-symbol">{{ $unitConsumption }}</span>
                                </strong>
                            </td>
                            <td class="text-right">${{ number_format($variant->current_value, 2) }}</td>
                            <td class="text-center">
                                @if ($isLow)
                                    <span class="badge badge-danger">BAJO</span>
                                @else
                                    <span class="badge badge-success">OK</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-sm btn-info"
                                    title="Ver Kardex">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.inventory.adjustment', $variant) }}"
                                    class="btn btn-sm btn-warning" title="Ajuste Manual">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-waste-material"
                                        title="Registrar Merma"
                                        data-toggle="modal"
                                        data-target="#modalWasteMaterial"
                                        data-variant-id="{{ $variant->id }}"
                                        data-name="{{ $variant->material?->name ?? 'N/A' }} {{ $variant->color ? '(' . $variant->color . ')' : '' }}"
                                        data-stock="{{ number_format($variant->current_stock, 2) }} {{ $unitConsumption }}"
                                        data-cost="${{ number_format($variant->average_cost ?? 0, 4) }}"
                                        data-unit="{{ $unitConsumption }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay variantes de material
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Paginación manejada por DataTables --}}
    </div>

    {{-- Modal de conversiones de unidades --}}
    @include('partials._unit-conversion-modal')

    {{-- Modal de Merma de Material --}}
    @include('admin.waste._modal-material')
@stop

@section('css')
    <style>
        /* DataTables - Botones de exportación (igual que proveedores) */
        #inventoryTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        #inventoryTable_wrapper .dt-buttons .btn {
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
    </style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // INICIALIZAR DATATABLE (igual que proveedores)
    // =====================================================
    var table = $('#inventoryTable').DataTable({
        "pageLength": 50,
        "language": {
            "emptyTable": "No hay materiales en inventario",
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
        buttons: [{
                text: '<i class="fas fa-copy"></i> COPIAR',
                extend: 'copy',
                className: 'btn btn-default'
            },
            {
                text: '<i class="fas fa-file-pdf"></i> PDF',
                extend: 'pdf',
                className: 'btn btn-danger',
                title: 'Inventario General',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                text: '<i class="fas fa-file-csv"></i> CSV',
                extend: 'csv',
                className: 'btn btn-info',
                title: 'Inventario General',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                text: '<i class="fas fa-file-excel"></i> EXCEL',
                extend: 'excel',
                className: 'btn btn-success',
                title: 'Inventario General',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                text: '<i class="fas fa-print"></i> IMPRIMIR',
                extend: 'print',
                className: 'btn btn-default',
                title: 'Inventario General',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            }
        ]
    });
    table.buttons().container().appendTo('#inventoryTable_wrapper .row:eq(0)');

    // =====================================================
    // FILTROS - Filtrado por DataTables (sin recargar página)
    // =====================================================
    var filterCategory = document.getElementById('filter-category');
    var filterStockStatus = document.getElementById('filter-stock-status');
    var filterSearch = document.getElementById('filter-search');
    var btnSearch = document.getElementById('btn-search');
    var btnClear = document.getElementById('btn-clear-filters');

    function applyFilters() {
        // Columna 0: Material (incluye categoría en small)
        // Columna 7: Estado (BAJO/OK)
        var categoryVal = filterCategory.value;
        var stockVal = filterStockStatus.value;
        var searchVal = filterSearch.value;

        // Filtro por categoría (columna 0 contiene nombre + categoría)
        table.column(0).search(categoryVal, false, true);

        // Filtro por estado de stock (columna 7)
        table.column(7).search(stockVal, false, true);

        // Búsqueda general
        table.search(searchVal);

        table.draw();
    }

    // Eventos de filtros
    filterCategory.addEventListener('change', applyFilters);
    filterStockStatus.addEventListener('change', applyFilters);
    filterSearch.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
    btnSearch.addEventListener('click', applyFilters);

    // Limpiar filtros
    btnClear.addEventListener('click', function() {
        filterCategory.value = '';
        filterStockStatus.value = '';
        filterSearch.value = '';
        table.search('').columns().search('').draw();
    });

    // =====================================================
    // UNIT TOGGLE - Conversión con modal de selección
    // =====================================================
    // Estado de conversiones aplicadas por material: {materialId: {factor, unitSymbol}}
    var appliedConversions = {};

    document.querySelectorAll('.unit-toggle').forEach(function(toggleGroup) {
        const container = toggleGroup.closest('.card') || document;
        const buttons = toggleGroup.querySelectorAll('[data-unit-mode]');
        const btnConsumption = toggleGroup.querySelector('[data-unit-mode="consumption"]');
        const btnCompra = toggleGroup.querySelector('[data-unit-mode="base"]');

        // Obtener materiales únicos (padres) de la tabla
        function getUniqueMaterials() {
            const materialsMap = new Map();
            container.querySelectorAll('tr[data-material-id]').forEach(function(row) {
                const id = parseInt(row.dataset.materialId);
                if (id > 0 && !materialsMap.has(id)) {
                    // Usar data-material-name que contiene el nombre del material padre
                    const name = row.dataset.materialName || 'Material #' + id;
                    materialsMap.set(id, { id: id, name: name });
                }
            });
            return Array.from(materialsMap.values());
        }

        // Click en Consumo: restaurar valores originales
        if (btnConsumption) {
            btnConsumption.addEventListener('click', function() {
                // Actualizar estado visual
                buttons.forEach(function(b) {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-primary');
                });
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary', 'active');

                // Limpiar conversiones aplicadas
                appliedConversions = {};

                // Restaurar valores originales
                container.querySelectorAll('.unit-convertible').forEach(function(el) {
                    const qty = parseFloat(el.dataset.qty) || 0;
                    const unitConsumption = el.dataset.unitConsumption || 'u';

                    const qtyEl = el.querySelector('.qty-value');
                    const unitEl = el.querySelector('.unit-symbol');
                    if (qtyEl) qtyEl.textContent = formatNumber(qty);
                    if (unitEl) unitEl.textContent = unitConsumption;
                });
            });
        }

        // Click en Compra: abrir modal de selección
        if (btnCompra) {
            btnCompra.addEventListener('click', function() {
                const materials = getUniqueMaterials();

                if (materials.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin materiales',
                        text: 'No hay materiales en la tabla',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Abrir modal con lista de materiales
                openUnitConversionModal(materials, function(materialId, conversion) {
                    // Guardar conversión aplicada
                    appliedConversions[materialId] = {
                        factor: conversion.conversion_factor,
                        unitSymbol: conversion.from_unit_symbol || conversion.label
                    };

                    // Actualizar estado visual del toggle
                    buttons.forEach(function(b) {
                        b.classList.remove('btn-primary', 'active');
                        b.classList.add('btn-outline-primary');
                    });
                    btnCompra.classList.remove('btn-outline-primary');
                    btnCompra.classList.add('btn-primary', 'active');

                    // Aplicar conversión solo a las celdas del material seleccionado
                    container.querySelectorAll('tr[data-material-id="' + materialId + '"] .unit-convertible').forEach(function(el) {
                        const qty = parseFloat(el.dataset.qty) || 0;
                        const factor = conversion.conversion_factor || 1;
                        const displayQty = qty / factor;
                        const displayUnit = conversion.from_unit_symbol || conversion.label;

                        const qtyEl = el.querySelector('.qty-value');
                        const unitEl = el.querySelector('.unit-symbol');
                        if (qtyEl) qtyEl.textContent = formatNumber(displayQty);
                        if (unitEl) unitEl.textContent = displayUnit;
                    });
                });
            });
        }
    });

    function formatNumber(num) {
        return num.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // =====================================================
    // MERMA DE MATERIAL - Inicializar modal
    // =====================================================
    document.querySelectorAll('.btn-waste-material').forEach(function(btn) {
        btn.addEventListener('click', function() {
            initWasteMaterialModal({
                variant_id: this.dataset.variantId,
                name: this.dataset.name,
                stock: this.dataset.stock,
                cost: this.dataset.cost,
                unit: this.dataset.unit
            });
        });
    });
});
</script>
@stop
