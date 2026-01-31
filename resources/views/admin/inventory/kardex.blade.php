@extends('adminlte::page')

@section('title', 'Kardex - ' . $variant->display_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-history mr-2"></i>Kardex</h1>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
    {{-- INFO DEL MATERIAL --}}
    <div class="card card-outline card-info mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="mb-1">{{ $variant->material?->name }}</h5>
                    <p class="mb-0 text-muted">
                        <code>{{ $variant->sku }}</code>
                        @if ($variant->color)
                            <span class="badge badge-secondary ml-2">{{ $variant->color }}</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-2 text-center border-left">
                    <small class="text-muted d-block">Stock Fisico</small>
                    <h4 class="mb-0">{{ number_format($variant->current_stock, 2) }}</h4>
                </div>
                <div class="col-md-2 text-center border-left">
                    <small class="text-muted d-block">Reservado</small>
                    <h4 class="mb-0 text-warning">{{ number_format($summary['reserved'], 2) }}</h4>
                </div>
                <div class="col-md-2 text-center border-left">
                    <small class="text-muted d-block">Disponible</small>
                    <h4 class="mb-0 text-success">{{ number_format($summary['available'], 2) }}</h4>
                </div>
                <div class="col-md-2 text-center border-left">
                    <small class="text-muted d-block">Valor</small>
                    <h4 class="mb-0">${{ number_format($variant->current_value, 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- RESUMEN HISTORICO --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Entradas (historico)</span>
                    <span class="info-box-number">{{ number_format($summary['total_entries'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Salidas (historico)</span>
                    <span class="info-box-number">{{ number_format($summary['total_exits'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <select id="filter-type" class="form-control form-control-sm">
                        <option value="">-- Tipo --</option>
                        <option value="Entrada">Entradas</option>
                        <option value="Salida">Salidas</option>
                        <option value="Ajuste +">Ajuste +</option>
                        <option value="Ajuste -">Ajuste -</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="filter-from" class="form-control form-control-sm" placeholder="Desde">
                </div>
                <div class="col-md-2">
                    <input type="date" id="filter-to" class="form-control form-control-sm" placeholder="Hasta">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-filter" class="btn btn-sm btn-primary">Filtrar</button>
                    <button type="button" id="btn-clear-filters" class="btn btn-sm btn-outline-secondary">Limpiar</button>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('admin.inventory.adjustment', $variant) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit mr-1"></i> Registrar Ajuste
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA KARDEX --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Movimientos</h5>
        </div>
        <div class="card-body p-0">
            <table id="kardexTable" class="table table-sm table-hover mb-0" style="font-size: 16px;">
                <thead style="background-color: #000; color: #fff;">
                    <tr>
                        <th style="width: 140px;">Fecha</th>
                        <th style="width: 100px;">Tipo</th>
                        <th class="text-center" style="width: 120px;">Cantidad</th>
                        <th class="text-center" style="width: 120px;">Costo Unit.</th>
                        <th class="text-center" style="width: 120px;">Costo Total</th>
                        <th class="text-center" style="width: 120px;">Saldo</th>
                        <th>Referencia</th>
                        <th style="width: 120px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                        @php
                            $isEntry = in_array($mov->type->value, [
                                'entrada',
                                'ajuste_positivo',
                                'devolucion_produccion',
                            ]);
                        @endphp
                        <tr>
                            <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge badge-{{ $mov->type_color }}">
                                    <i class="fas fa-{{ $mov->type_icon }} mr-1"></i>{{ $mov->type_label }}
                                </span>
                            </td>
                            @php
                                $unitSymbol = $variant->material?->consumptionUnit?->symbol
                                           ?? $variant->material?->baseUnit?->symbol
                                           ?? '';
                            @endphp
                            <td class="text-center {{ $isEntry ? 'text-success' : 'text-danger' }}">
                                {{ $isEntry ? '+' : '-' }}{{ number_format($mov->quantity, 2) }}
                                {{ $unitSymbol }}
                            </td>
                            <td class="text-center">${{ number_format($mov->unit_cost, 4) }}</td>
                            <td class="text-center">${{ number_format($mov->total_cost, 2) }}</td>
                            <td class="text-center"><strong>{{ number_format($mov->stock_after, 2) }}
                                    {{ $unitSymbol }}</strong></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div>
                                        @if (in_array($mov->reference_type, ['App\Models\PurchaseItem', 'App\Models\Purchase']))
                                            <a href="#" class="btn-purchase-details text-primary" style="cursor: pointer; text-decoration: underline;"
                                               data-reference-type="{{ $mov->reference_type }}"
                                               data-reference-id="{{ $mov->reference_id }}">
                                                <strong>{{ $mov->reference_label }}</strong>
                                                @if ($mov->reference_id)
                                                    <span class="ml-1">#{{ $mov->reference_id }}</span>
                                                @endif
                                            </a>
                                        @else
                                            <strong>{{ $mov->reference_label }}</strong>
                                            @if ($mov->reference_id)
                                                <span class="ml-1">#{{ $mov->reference_id }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    @if ($mov->notes)
                                        <div class="text-muted">{{ Str::limit($mov->notes, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                {{ $mov->creator?->name ?? 'Sistema' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay movimientos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Paginación manejada por DataTables --}}
    </div>

    {{-- Modal de Detalles de Compra --}}
    <div class="modal fade" id="purchaseDetailsModal" tabindex="-1" role="dialog" aria-labelledby="purchaseDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="purchaseDetailsModalLabel">
                        <i class="fas fa-file-invoice mr-2"></i>Detalles de Orden de Compra
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="purchaseDetailsContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando datos...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* DataTables - Botones de exportación (igual que proveedores) */
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
$(function() {
    // Inicializar DataTable igual que proveedores
    var table = $('#kardexTable').DataTable({
        "pageLength": 10,
        "order": [[0, 'desc']], // Ordenar por fecha descendente por defecto
        "language": {
            "emptyTable": "No hay movimientos registrados",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ Movimientos",
            "infoEmpty": "Mostrando 0 a 0 de 0 Movimientos",
            "infoFiltered": "(Filtrado de _MAX_ total Movimientos)",
            "lengthMenu": "Mostrar _MENU_ Movimientos",
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
                title: 'Kardex - {{ $variant->display_name }}'
            },
            {
                text: '<i class="fas fa-file-csv"></i> CSV',
                extend: 'csv',
                className: 'btn btn-info',
                title: 'Kardex - {{ $variant->display_name }}'
            },
            {
                text: '<i class="fas fa-file-excel"></i> EXCEL',
                extend: 'excel',
                className: 'btn btn-success',
                title: 'Kardex - {{ $variant->display_name }}'
            },
            {
                text: '<i class="fas fa-print"></i> IMPRIMIR',
                extend: 'print',
                className: 'btn btn-default',
                title: 'Kardex - {{ $variant->display_name }}'
            }
        ]
    });
    table.buttons().container().appendTo('#kardexTable_wrapper .row:eq(0)');

    // =====================================================
    // FILTROS - Filtrado por DataTables (sin recargar página)
    // =====================================================
    var filterType = document.getElementById('filter-type');
    var filterFrom = document.getElementById('filter-from');
    var filterTo = document.getElementById('filter-to');
    var btnFilter = document.getElementById('btn-filter');
    var btnClear = document.getElementById('btn-clear-filters');

    // Filtro personalizado para fechas
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'kardexTable') return true;

        var fromDate = filterFrom.value ? new Date(filterFrom.value) : null;
        var toDate = filterTo.value ? new Date(filterTo.value) : null;

        // La fecha está en columna 0, formato dd/mm/yyyy HH:mm
        var dateStr = data[0];
        if (!dateStr) return true;

        // Parsear fecha dd/mm/yyyy
        var parts = dateStr.split(' ')[0].split('/');
        if (parts.length !== 3) return true;

        var rowDate = new Date(parts[2], parts[1] - 1, parts[0]);

        if (fromDate && rowDate < fromDate) return false;
        if (toDate && rowDate > toDate) return false;

        return true;
    });

    function applyFilters() {
        // Filtro por tipo (columna 1)
        var typeVal = filterType.value;
        table.column(1).search(typeVal, false, true);
        table.draw();
    }

    // Eventos de filtros
    filterType.addEventListener('change', applyFilters);
    btnFilter.addEventListener('click', applyFilters);

    // Limpiar filtros
    btnClear.addEventListener('click', function() {
        filterType.value = '';
        filterFrom.value = '';
        filterTo.value = '';
        table.search('').columns().search('').draw();
    });

    // =====================================================
    // MODAL DE DETALLES DE COMPRA
    // =====================================================
    $(document).on('click', '.btn-purchase-details', function(e) {
        e.preventDefault();
        var referenceType = $(this).data('reference-type');
        var referenceId = $(this).data('reference-id');

        // Mostrar modal con loading
        $('#purchaseDetailsContent').html(`
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Cargando datos...</p>
            </div>
        `);
        $('#purchaseDetailsModal').modal('show');

        // Cargar datos via AJAX
        $.ajax({
            url: '{{ route("admin.inventory.purchase.details") }}',
            method: 'GET',
            data: {
                reference_type: referenceType,
                reference_id: referenceId
            },
            success: function(response) {
                var purchase = response.purchase;
                var items = response.items;
                var highlightedItemId = response.highlighted_item_id;

                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary mb-3">
                                <div class="card-header py-2">
                                    <h6 class="mb-0"><i class="fas fa-file-alt mr-1"></i>Orden de Compra</h6>
                                </div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Número:</td>
                                            <td><strong>${purchase.purchase_number}</strong></td>
                                        </tr>
                                        ${purchase.reference ? `<tr><td class="text-muted">Referencia:</td><td>${purchase.reference}</td></tr>` : ''}
                                        <tr>
                                            <td class="text-muted">Estado:</td>
                                            <td><span class="badge badge-${purchase.status_color}">${purchase.status}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Fecha Orden:</td>
                                            <td>${purchase.ordered_at || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Fecha Recepción:</td>
                                            <td>${purchase.received_at || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Creado por:</td>
                                            <td>${purchase.creator || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Recibido por:</td>
                                            <td>${purchase.receiver || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-success mb-3">
                                <div class="card-header py-2">
                                    <h6 class="mb-0"><i class="fas fa-truck mr-1"></i>Proveedor</h6>
                                </div>
                                <div class="card-body py-2">
                                    ${purchase.proveedor ? `
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">Nombre:</td>
                                                <td><strong>${purchase.proveedor.name}</strong></td>
                                            </tr>
                                            ${purchase.proveedor.contact ? `<tr><td class="text-muted">Contacto:</td><td>${purchase.proveedor.contact}</td></tr>` : ''}
                                            ${purchase.proveedor.phone ? `<tr><td class="text-muted">Teléfono:</td><td>${purchase.proveedor.phone}</td></tr>` : ''}
                                            ${purchase.proveedor.email ? `<tr><td class="text-muted">Email:</td><td>${purchase.proveedor.email}</td></tr>` : ''}
                                        </table>
                                    ` : '<p class="text-muted mb-0">Sin proveedor asignado</p>'}
                                </div>
                            </div>
                            <div class="card card-outline card-warning">
                                <div class="card-header py-2">
                                    <h6 class="mb-0"><i class="fas fa-dollar-sign mr-1"></i>Totales</h6>
                                </div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted">Subtotal:</td>
                                            <td class="text-right">$${purchase.subtotal}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Impuestos:</td>
                                            <td class="text-right">$${purchase.tax_amount}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Descuento:</td>
                                            <td class="text-right text-danger">-$${purchase.discount_amount}</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td><strong>Total:</strong></td>
                                            <td class="text-right"><strong>$${purchase.total}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${purchase.notes ? `
                        <div class="alert alert-info py-2 mb-3">
                            <i class="fas fa-sticky-note mr-1"></i><strong>Notas:</strong> ${purchase.notes}
                        </div>
                    ` : ''}

                    <div class="card card-outline card-dark">
                        <div class="card-header py-2">
                            <h6 class="mb-0"><i class="fas fa-list mr-1"></i>Items de la Compra</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="bg-secondary text-white">
                                    <tr>
                                        <th>Material</th>
                                        <th>SKU</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">P. Unit.</th>
                                        <th class="text-center">Subtotal</th>
                                        <th class="text-center">Recibido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(function(item) {
                                        var isHighlighted = highlightedItemId && item.id == highlightedItemId;
                                        return `
                                            <tr class="${isHighlighted ? 'table-warning' : ''}">
                                                <td>
                                                    ${item.material}
                                                    ${item.variant && item.variant !== '-' ? `<br><small class="text-muted">${item.variant}</small>` : ''}
                                                    ${isHighlighted ? '<i class="fas fa-arrow-left text-warning ml-2" title="Item de este movimiento"></i>' : ''}
                                                </td>
                                                <td><code>${item.sku}</code></td>
                                                <td class="text-center">${item.quantity} ${item.unit}</td>
                                                <td class="text-center">$${item.unit_price}</td>
                                                <td class="text-center">$${item.subtotal}</td>
                                                <td class="text-center">${item.quantity_received} ${item.unit}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                $('#purchaseDetailsContent').html(html);
            },
            error: function(xhr) {
                $('#purchaseDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Error al cargar los detalles de la compra.
                    </div>
                `);
            }
        });
    });
});
</script>
@stop
