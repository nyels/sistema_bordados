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

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.finished-goods-stock.index') }}" id="filter-form">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select name="category_id" id="filter-category" class="form-control form-control-sm">
                            <option value="">-- Categoria --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="stock_status" id="filter-stock-status" class="form-control form-control-sm">
                            <option value="">-- Estado Stock --</option>
                            <option value="ok" {{ request('stock_status') == 'ok' ? 'selected' : '' }}>OK</option>
                            <option value="bajo" {{ request('stock_status') == 'bajo' ? 'selected' : '' }}>Bajo</option>
                            <option value="agotado" {{ request('stock_status') == 'agotado' ? 'selected' : '' }}>Agotado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="filter-search" class="form-control"
                                placeholder="Buscar producto / SKU..."
                                value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.finished-goods-stock.index') }}" class="btn btn-sm btn-outline-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA PRINCIPAL (SOLO LECTURA - I4, I9) --}}
    <div class="card">
        <div class="card-header py-2">
            <span class="font-weight-bold">Listado de Stock - Productos Terminados</span>
        </div>
        <div class="card-body p-0">
            <table id="stockTable" class="table table-hover table-sm mb-0" style="font-size: 16px;">
                <thead style="background-color: #000; color: #fff;">
                    <tr>
                        <th>Producto</th>
                        <th>SKU Variante</th>
                        <th>Atributos</th>
                        <th class="text-right">Stock Actual</th>
                        <th class="text-right">Nivel Alerta</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center" style="width: 130px;">Acciones</th>
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
                            <td>
                                <strong>{{ $variant->product?->name ?? 'N/A' }}</strong>
                                <br>
                                <small class="text-muted">{{ $variant->product?->category?->name ?? '' }}</small>
                            </td>

                            {{-- Columna 2: SKU Variante --}}
                            <td>
                                <code>{{ $variant->sku_variant }}</code>
                            </td>

                            {{-- Columna 3: Atributos --}}
                            <td>
                                @if ($variant->attributeValues && $variant->attributeValues->count() > 0)
                                    @foreach ($variant->attributeValues as $attrValue)
                                        <span class="badge badge-light border">{{ $attrValue->value }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Columna 4: Stock Actual (calculado desde ledger - I1, I2, I5) --}}
                            <td class="text-right">
                                <strong class="{{ $variant->stock_status === 'agotado' ? 'text-danger' : ($variant->stock_status === 'bajo' ? 'text-warning' : 'text-dark') }}">
                                    {{ number_format($variant->calculated_stock, 0) }}
                                </strong>
                            </td>

                            {{-- Columna 5: Nivel de Alerta --}}
                            <td class="text-right text-muted">
                                {{ $variant->stock_alert ?? 0 }}
                            </td>

                            {{-- Columna 6: Estado --}}
                            <td class="text-center">
                                <span class="badge badge-{{ $variant->stock_status_color }}">
                                    {{ $variant->stock_status_label }}
                                </span>
                            </td>

                            {{-- Columna 7: Acciones --}}
                            <td class="text-center">
                                <a href="{{ route('admin.finished-goods-stock.kardex', $variant->id) }}"
                                   class="btn btn-xs btn-outline-info" title="Kardex">
                                    <i class="fas fa-history"></i>
                                </a>
                                <a href="{{ route('admin.finished-goods-stock.adjustment', $variant->id) }}"
                                   class="btn btn-xs btn-outline-warning" title="Ajuste">
                                    <i class="fas fa-balance-scale"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-xs btn-outline-danger btn-waste-pt"
                                        title="Registrar Merma"
                                        data-toggle="modal"
                                        data-target="#modalWasteFinishedProduct"
                                        data-variant-id="{{ $variant->id }}"
                                        data-name="{{ $variant->product?->name ?? 'N/A' }}"
                                        data-sku="{{ $variant->sku_variant }}"
                                        data-stock="{{ number_format($variant->calculated_stock, 0) }}"
                                        data-price="${{ number_format($variant->product?->base_price ?? 0, 2) }}"
                                        data-price-raw="{{ $variant->product?->base_price ?? 0 }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay variantes de producto terminado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- NOTA DE FUENTE DE DATOS --}}
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            Stock calculado desde movimientos de producto terminado (ledger). Fuente: <code>finished_goods_movements</code>
        </small>
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
                price: this.dataset.price,
                price_raw: this.dataset.priceRaw
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
        "order": [[3, 'desc']], // Ordenar por stock descendente
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
});
</script>
@stop
