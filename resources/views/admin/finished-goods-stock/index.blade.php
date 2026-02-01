@extends('adminlte::page')

@section('title', 'Stock Producto Terminado')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-boxes mr-2"></i>Stock de Producto Terminado</h1>
    </div>
@stop

@section('content')
    {{-- RESUMEN (SIN DATOS FINANCIEROS - I8) --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totals['total_variants'] }}</h3>
                    <p>Variantes Activas</p>
                </div>
                <div class="icon"><i class="fas fa-tags"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($totals['total_stock'], 0) }}</h3>
                    <p>Stock Total (unidades)</p>
                </div>
                <div class="icon"><i class="fas fa-cubes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $totals['low_stock'] > 0 ? 'bg-warning' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $totals['low_stock'] }}</h3>
                    <p>Stock Bajo</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $totals['out_of_stock'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $totals['out_of_stock'] }}</h3>
                    <p>Agotados</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>
    </div>

    {{-- FILTROS (sin recarga de página) --}}
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
                        <option value="OK">OK</option>
                        <option value="Bajo">Bajo</option>
                        <option value="Agotado">Agotado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="filter-search" class="form-control form-control-sm"
                        placeholder="Buscar producto / SKU...">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-clear-filters" class="btn btn-sm btn-outline-secondary">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA PRINCIPAL (SOLO LECTURA - I4, I9) --}}
    <div class="card">
        <div class="card-header py-2">
            <span class="font-weight-bold">Listado de Stock - Productos Terminados</span>
        </div>
        <div class="card-body table-responsive p-0">
            <table id="stockTable" class="table table-hover table-striped mb-0" style="font-size: 16px;">
                <thead style="background: #343a40; color: white;">
                    <tr>
                        <th class="align-middle" style="color: white;">Producto</th>
                        <th class="align-middle" style="color: white;">SKU Variante</th>
                        <th class="align-middle" style="color: white;">Atributos</th>
                        <th class="text-right align-middle" style="color: white;">Stock Actual</th>
                        <th class="text-right align-middle" style="color: white;">Nivel Alerta</th>
                        <th class="text-center align-middle" style="color: white;">Estado</th>
                        <th class="text-center align-middle" style="color: white; width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        @php
                            $rowClass = match($variant->stock_status) {
                                'agotado' => 'table-danger',
                                'bajo' => 'table-warning',
                                default => '',
                            };
                        @endphp
                        <tr class="{{ $rowClass }}">
                            {{-- Columna 1: Producto --}}
                            <td class="align-middle" style="color: #212529;">
                                <strong>{{ $variant->product?->name ?? 'N/A' }}</strong>
                                <br>
                                <small style="color: #6c757d;">{{ $variant->product?->category?->name ?? '' }}</small>
                            </td>

                            {{-- Columna 2: SKU Variante --}}
                            <td class="align-middle" style="color: #212529;">
                                <code style="font-size: 15px;">{{ $variant->sku_variant }}</code>
                            </td>

                            {{-- Columna 3: Atributos --}}
                            <td class="align-middle">
                                @if ($variant->attributes_display)
                                    <span class="badge badge-light border" style="font-size: 14px; color: #212529;">{{ $variant->attributes_display }}</span>
                                @else
                                    <span style="color: #212529;">-</span>
                                @endif
                            </td>

                            {{-- Columna 4: Stock Actual (calculado desde ledger - I1, I2, I5) --}}
                            <td class="text-right align-middle">
                                <strong style="font-size: 17px; color: {{ $variant->stock_status === 'agotado' ? '#dc3545' : ($variant->stock_status === 'bajo' ? '#ffc107' : '#212529') }};">
                                    {{ number_format($variant->calculated_stock, 0) }}
                                </strong>
                            </td>

                            {{-- Columna 5: Nivel de Alerta --}}
                            <td class="text-right align-middle" style="color: #212529;">
                                {{ $variant->stock_alert ?? 0 }}
                            </td>

                            {{-- Columna 6: Estado --}}
                            <td class="text-center align-middle">
                                <span class="badge badge-{{ $variant->stock_status_color }}" style="font-size: 16px;">
                                    {{ $variant->stock_status_label }}
                                </span>
                            </td>

                            {{-- Columna 7: Acciones --}}
                            <td class="text-center align-middle text-nowrap">
                                <a href="{{ route('admin.finished-goods-stock.kardex', $variant->id) }}"
                                   class="btn btn-sm btn-info" title="Kardex">
                                    <i class="fas fa-history"></i>
                                </a>
                                <a href="{{ route('admin.finished-goods-stock.adjustment', $variant->id) }}"
                                   class="btn btn-sm btn-warning" title="Ajuste">
                                    <i class="fas fa-balance-scale"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger btn-waste-pt"
                                        title="Registrar Merma"
                                        data-toggle="modal"
                                        data-target="#modalWasteFinishedProduct"
                                        data-variant-id="{{ $variant->id }}"
                                        data-name="{{ $variant->product?->name ?? 'N/A' }}"
                                        data-sku="{{ $variant->sku_variant }}"
                                        data-stock="{{ number_format($variant->calculated_stock, 0) }}"
                                        data-cost="${{ number_format($variant->product?->production_cost ?? 0, 2) }}"
                                        data-cost-raw="{{ $variant->product?->production_cost ?? 0 }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color: #212529; font-size: 15px;">
                                No hay variantes de producto terminado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal de Merma de Producto Terminado --}}
    @include('admin.waste._modal-finished-product')
@stop

@section('css')
    <style>
        #stockTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        #stockTable_wrapper .dt-buttons .btn {
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
    // MERMA PT - Inicializar modal
    // =====================================================
    document.querySelectorAll('.btn-waste-pt').forEach(function(btn) {
        btn.addEventListener('click', function() {
            initWasteFinishedProductModal({
                variant_id: this.dataset.variantId,
                name: this.dataset.name,
                sku: this.dataset.sku,
                stock: this.dataset.stock,
                cost: this.dataset.cost,
                cost_raw: this.dataset.costRaw
            });
        });
    });

    // Inicializar DataTables
    var table = $('#stockTable').DataTable({
        "pageLength": 25,
        "language": {
            "emptyTable": "No hay productos terminados en stock",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ variantes",
            "infoEmpty": "Mostrando 0 a 0 de 0 variantes",
            "infoFiltered": "(Filtrado de _MAX_ total variantes)",
            "lengthMenu": "Mostrar _MENU_ variantes",
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
        "order": [[3, 'desc']],
        buttons: [
            {
                text: '<i class="fas fa-copy"></i> COPIAR',
                extend: 'copy',
                className: 'btn btn-default'
            },
            {
                text: '<i class="fas fa-file-pdf"></i> PDF',
                extend: 'pdf',
                className: 'btn btn-danger',
                title: 'Stock Producto Terminado',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-file-csv"></i> CSV',
                extend: 'csv',
                className: 'btn btn-info',
                title: 'Stock Producto Terminado',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-file-excel"></i> EXCEL',
                extend: 'excel',
                className: 'btn btn-success',
                title: 'Stock Producto Terminado',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-print"></i> IMPRIMIR',
                extend: 'print',
                className: 'btn btn-default',
                title: 'Stock Producto Terminado',
                exportOptions: { columns: ':visible' }
            }
        ]
    });
    table.buttons().container().appendTo('#stockTable_wrapper .row:eq(0)');

    // =====================================================
    // FILTROS SIN RECARGA DE PÁGINA
    // =====================================================

    // Filtro por categoría (columna 0 - subcategoría en small)
    $('#filter-category').on('change', function() {
        var val = $(this).val();
        table.column(0).search(val).draw();
    });

    // Filtro por estado de stock (columna 5 - badge de estado)
    $('#filter-stock-status').on('change', function() {
        var val = $(this).val();
        table.column(5).search(val).draw();
    });

    // Filtro por búsqueda general (producto / SKU)
    $('#filter-search').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Botón limpiar filtros
    $('#btn-clear-filters').on('click', function() {
        $('#filter-category').val('');
        $('#filter-stock-status').val('');
        $('#filter-search').val('');
        table.search('').columns().search('').draw();
    });
});
</script>
@stop
