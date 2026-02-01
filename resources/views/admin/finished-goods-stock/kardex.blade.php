@extends('adminlte::page')

@section('title', 'Kardex PT - ' . $productVariant->sku_variant)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-history mr-2"></i>Kardex Producto Terminado</h1>
            <small class="text-muted">{{ $productVariant->product->name ?? 'N/A' }} - <code>{{ $productVariant->sku_variant }}</code></small>
        </div>
        <div>
            <a href="{{ route('admin.finished-goods-stock.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            <a href="{{ route('admin.finished-goods-stock.adjustment', $productVariant->id) }}" class="btn btn-warning">
                <i class="fas fa-balance-scale mr-1"></i> Ajuste
            </a>
        </div>
    </div>
@stop

@section('content')
    {{-- RESUMEN --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($currentStock, 0) }}</h3>
                    <p>Stock Actual</p>
                </div>
                <div class="icon"><i class="fas fa-cubes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $movements->total() }}</h3>
                    <p>Total Movimientos</p>
                </div>
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-light"><i class="fas fa-box"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Producto</span>
                    <span class="info-box-number">{{ $productVariant->product->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-light"><i class="fas fa-tag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Categoria</span>
                    <span class="info-box-number">{{ $productVariant->product->category->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS (sin recarga de página) --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select id="filter-type" class="form-control form-control-sm">
                        <option value="">-- Tipo Movimiento --</option>
                        <option value="Entrada">Entrada Produccion</option>
                        <option value="Salida">Salida Venta</option>
                        <option value="Ajuste">Ajuste</option>
                        <option value="Devolución">Devolucion</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="filter-search" class="form-control form-control-sm"
                           placeholder="Buscar en notas...">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-clear-filters" class="btn btn-sm btn-outline-secondary">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA KARDEX (SOLO LECTURA) --}}
    <div class="card">
        <div class="card-header py-2">
            <span class="font-weight-bold">Historial de Movimientos</span>
        </div>
        <div class="card-body table-responsive p-0">
            <table id="kardexTable" class="table table-hover table-striped mb-0" style="font-size: 16px;">
                <thead style="background: #343a40; color: white;">
                    <tr>
                        <th class="align-middle" style="color: white; width: 150px;">Fecha / Hora</th>
                        <th class="align-middle" style="color: white; width: 140px;">Tipo</th>
                        <th class="text-right align-middle" style="color: white; width: 100px;">Cantidad</th>
                        <th class="text-right align-middle" style="color: white; width: 110px;">Stock Antes</th>
                        <th class="text-right align-middle" style="color: white; width: 110px;">Stock Después</th>
                        <th class="align-middle" style="color: white;">Notas</th>
                        <th class="align-middle" style="color: white; width: 140px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                        <tr>
                            <td class="align-middle" style="color: #212529; white-space: nowrap;">
                                {{ $mov->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-{{ $mov->type_color }}" style="font-size: 14px;">
                                    <i class="{{ $mov->type_icon }} mr-1"></i>{{ $mov->type_label }}
                                </span>
                            </td>
                            <td class="text-right align-middle font-weight-bold" style="font-size: 17px; color: {{ $mov->is_entry ? '#28a745' : ($mov->type === 'adjustment' ? ($mov->quantity >= 0 ? '#28a745' : '#dc3545') : '#dc3545') }};">
                                {{ $mov->formatted_quantity }}
                            </td>
                            <td class="text-right align-middle" style="color: #212529;">
                                {{ number_format($mov->stock_before, 2) }}
                            </td>
                            <td class="text-right align-middle" style="color: #212529;">
                                {{ number_format($mov->stock_after, 2) }}
                            </td>
                            <td class="align-middle" style="color: #6c757d;">
                                {{ \Illuminate\Support\Str::limit($mov->notes, 80) }}
                            </td>
                            <td class="align-middle" style="color: #212529;">
                                {{ $mov->creator->name ?? 'Sistema' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color: #212529; font-size: 15px;">
                                No hay movimientos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@stop

@section('css')
    <style>
        #kardexTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        #kardexTable_wrapper .dt-buttons .btn {
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
    var table = $('#kardexTable').DataTable({
        "pageLength": 25,
        "language": {
            "emptyTable": "No hay movimientos registrados",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ movimientos",
            "infoEmpty": "Mostrando 0 a 0 de 0 movimientos",
            "infoFiltered": "(Filtrado de _MAX_ total movimientos)",
            "lengthMenu": "Mostrar _MENU_ movimientos",
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
        "order": [[0, 'desc']],
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
                title: 'Kardex - {{ $productVariant->sku_variant }}',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-file-csv"></i> CSV',
                extend: 'csv',
                className: 'btn btn-info',
                title: 'Kardex - {{ $productVariant->sku_variant }}',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-file-excel"></i> EXCEL',
                extend: 'excel',
                className: 'btn btn-success',
                title: 'Kardex - {{ $productVariant->sku_variant }}',
                exportOptions: { columns: ':visible' }
            },
            {
                text: '<i class="fas fa-print"></i> IMPRIMIR',
                extend: 'print',
                className: 'btn btn-default',
                title: 'Kardex - {{ $productVariant->sku_variant }}',
                exportOptions: { columns: ':visible' }
            }
        ]
    });
    table.buttons().container().appendTo('#kardexTable_wrapper .row:eq(0)');

    // =====================================================
    // FILTROS SIN RECARGA DE PÁGINA
    // =====================================================

    // Filtro por tipo de movimiento (columna 1 - badge de tipo)
    $('#filter-type').on('change', function() {
        var val = $(this).val();
        table.column(1).search(val).draw();
    });

    // Filtro por búsqueda general (notas, usuario, etc.)
    $('#filter-search').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Botón limpiar filtros
    $('#btn-clear-filters').on('click', function() {
        $('#filter-type').val('');
        $('#filter-search').val('');
        table.search('').columns().search('').draw();
    });
});
</script>
@stop
