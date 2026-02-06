@extends('adminlte::page')

@section('title', 'Ventas Consolidadas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line mr-2"></i> Ventas Consolidadas</h1>
        <div>
            <a href="{{ route('pos.index') }}" class="btn btn-success mr-2">
                <i class="fas fa-cash-register"></i> POS
            </a>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-primary">
                <i class="fas fa-clipboard-list"></i> Pedidos
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
         KPIs ERP CANONICOS
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
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>${{ number_format($kpis['venta_neta_hoy'], 2) }}</h3>
                    <p>Venta Neta Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $kpis['ventas_pos_hoy'] }} <small class="text-white-50">/ {{ $kpis['pedidos_hoy'] }}</small></h3>
                    <p>POS / Pedidos Hoy</p>
                </div>
                <div class="icon"><i class="fas fa-balance-scale"></i></div>
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
         TABLA DE VENTAS CONSOLIDADAS
    ======================================== --}}
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                <i class="fas fa-list-alt mr-2"></i>TODAS LAS VENTAS (POS + PEDIDOS)
            </h3>
        </div>
        <div class="card-body">
            {{-- FILTROS CLIENT-SIDE (sin recargar) --}}
            <div class="row mb-3" id="filters-row">
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Fecha Desde</label>
                    <input type="date" class="form-control form-control-sm filter-input" id="filter-fecha-desde">
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Fecha Hasta</label>
                    <input type="date" class="form-control form-control-sm filter-input" id="filter-fecha-hasta">
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Vendedor</label>
                    <select class="form-control form-control-sm filter-input" id="filter-vendedor">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->name }}">{{ $vendedor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Origen</label>
                    <select class="form-control form-control-sm filter-input" id="filter-origen">
                        <option value="">Todos</option>
                        <option value="POS">POS</option>
                        <option value="PEDIDO">Pedido</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Cliente</label>
                    <select class="form-control form-control-sm filter-input" id="filter-cliente">
                        <option value="">Todos</option>
                        <option value="Venta Libre">Venta Libre</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->nombre }} {{ $cliente->apellidos }}">
                                {{ $cliente->nombre }} {{ $cliente->apellidos }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary btn-sm btn-block" id="filter-clear">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                </div>
            </div>
            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table id="salesTable" class="table table-bordered table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Folio</th>
                            <th>Origen</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Subtotal</th>
                            <th>Descuento</th>
                            <th>Venta Neta</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th style="text-align: center;">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            @php
                                $esPOS = str_contains($sale->notes ?? '', '[VENTA POS MOSTRADOR');
                                $origen = $esPOS ? 'POS' : 'PEDIDO';
                                $fechaVenta = $sale->sold_at ?? $sale->delivered_date;
                                $ventaNeta = $sale->subtotal - $sale->discount;
                                $vendedor = $sale->seller_name ?? ($sale->creator?->name ?? 'N/A');
                                $clienteNombre = $sale->cliente ? ($sale->cliente->nombre . ' ' . $sale->cliente->apellidos) : 'Venta Libre';
                            @endphp
                            <tr data-fecha="{{ $fechaVenta?->format('Y-m-d') }}"
                                data-vendedor="{{ $vendedor }}"
                                data-origen="{{ $origen }}"
                                data-cliente="{{ $clienteNombre }}">
                                <td>{{ $fechaVenta?->format('d/m/Y') ?? '--' }}</td>
                                <td>{{ $fechaVenta?->format('H:i') ?? '--' }}</td>
                                <td>
                                    <a href="{{ route('admin.sales.show', $sale) }}" class="font-weight-bold">
                                        {{ $sale->order_number }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if ($esPOS)
                                        <span class="badge badge-success">POS</span>
                                    @else
                                        <span class="badge badge-info">PEDIDO</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sale->cliente)
                                        {{ $sale->cliente->nombre }} {{ $sale->cliente->apellidos }}
                                    @else
                                        <span class="text-muted">Venta Libre</span>
                                    @endif
                                </td>
                                <td>{{ $vendedor }}</td>
                                <td class="text-right">${{ number_format($sale->subtotal, 2) }}</td>
                                <td class="text-right">
                                    @if ($sale->discount > 0)
                                        <span class="text-danger">-${{ number_format($sale->discount, 2) }}</span>
                                    @else
                                        $0.00
                                    @endif
                                </td>
                                <td class="text-right">
                                    <strong class="text-primary">${{ number_format($ventaNeta, 2) }}</strong>
                                </td>
                                <td class="text-right">
                                    @if ($sale->iva_amount > 0)
                                        ${{ number_format($sale->iva_amount, 2) }}
                                    @else
                                        $0.00
                                    @endif
                                </td>
                                <td class="text-right">
                                    <strong class="text-success">${{ number_format($sale->total, 2) }}</strong>
                                </td>
                                <td class="text-center">
                                    {{ $sale->payment_method ? ucfirst($sale->payment_method) : '--' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.sales.show', $sale) }}"
                                       class="btn btn-info btn-sm" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">
                                    No hay ventas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($sales->count() > 0)
                        <tfoot class="bg-light font-weight-bold">
                            @php
                                $totalSubtotal = $sales->sum('subtotal');
                                $totalDescuento = $sales->sum('discount');
                                $totalVentaNeta = $totalSubtotal - $totalDescuento;
                                $totalIVA = $sales->sum('iva_amount');
                                $totalCobrado = $sales->sum('total');
                            @endphp
                            <tr>
                                <td colspan="6" class="text-right">TOTALES:</td>
                                <td class="text-right">${{ number_format($totalSubtotal, 2) }}</td>
                                <td class="text-right text-danger">-${{ number_format($totalDescuento, 2) }}</td>
                                <td class="text-right text-primary">${{ number_format($totalVentaNeta, 2) }}</td>
                                <td class="text-right">${{ number_format($totalIVA, 2) }}</td>
                                <td class="text-right text-success">${{ number_format($totalCobrado, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- LEYENDA ERP --}}
    <div class="card card-secondary card-outline">
        <div class="card-header py-2">
            <h6 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Reglas Contables ERP</h6>
        </div>
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted">
                        <strong class="text-primary">Venta Neta</strong> = Subtotal - Descuento
                        <br><em>Ingreso real del negocio.</em>
                    </small>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">
                        <strong>IVA</strong> = Impuesto retenido
                        <br><em>NO es ingreso, es obligacion fiscal.</em>
                    </small>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">
                        <strong class="text-success">Total</strong> = Venta Neta + IVA
                        <br><em>Dinero recibido del cliente.</em>
                    </small>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box .inner h3 {
            font-size: 2rem;
            font-weight: bold;
        }

        .small-box .inner p {
            font-size: 14px;
        }

        /* Filtros */
        #filters-row label {
            font-weight: 600;
        }

        #filters-row .form-control-sm {
            height: calc(1.8rem + 2px);
        }

        /* Fondo transparente y sin borde en el contenedor de botones DataTables */
        #salesTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        /* Estilo para los botones */
        #salesTable_wrapper .dt-buttons .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        /* Colores por tipo de boton */
        #salesTable_wrapper .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        #salesTable_wrapper .btn-success {
            background-color: #28a745;
            border: none;
        }

        #salesTable_wrapper .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        #salesTable_wrapper .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        #salesTable_wrapper .btn-default {
            background-color: #6e7176;
            color: #fff;
            border: none;
        }

        /* Ocultar buscador nativo de DataTables */
        #salesTable_filter {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .small-box .inner h3 {
                font-size: 1.5rem;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var dataTable = $("#salesTable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Ventas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Ventas",
                    "infoFiltered": "(Filtrado de _MAX_ total Ventas)",
                    "lengthMenu": "Mostrar _MENU_ Ventas",
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
                "searching": true, // Necesario para que funcionen los filtros personalizados
                "order": [[0, 'desc']],
                "columnDefs": [
                    { "orderable": false, "targets": [12] }
                ],
                buttons: [
                    {
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-default',
                        exportOptions: { columns: ':not(:last-child)' }
                    }
                ]
            }).buttons().container().appendTo('#salesTable_wrapper .row:eq(0)');

            // ========================================
            // FILTROS CLIENT-SIDE (sin recargar)
            // ========================================

            // Registrar filtro personalizado UNA sola vez
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                // Solo aplicar a nuestra tabla
                if (settings.sTableId !== 'salesTable') {
                    return true;
                }

                var $row = $(dataTable.row(dataIndex).node());
                var rowFecha = $row.attr('data-fecha') || '';
                var rowVendedor = $row.attr('data-vendedor') || '';
                var rowOrigen = $row.attr('data-origen') || '';
                var rowCliente = $row.attr('data-cliente') || '';

                var fechaDesde = $('#filter-fecha-desde').val();
                var fechaHasta = $('#filter-fecha-hasta').val();
                var vendedor = $('#filter-vendedor').val();
                var origen = $('#filter-origen').val();
                var cliente = $('#filter-cliente').val();

                // Filtro fecha desde
                if (fechaDesde && rowFecha && rowFecha < fechaDesde) {
                    return false;
                }

                // Filtro fecha hasta
                if (fechaHasta && rowFecha && rowFecha > fechaHasta) {
                    return false;
                }

                // Filtro vendedor
                if (vendedor && rowVendedor !== vendedor) {
                    return false;
                }

                // Filtro origen
                if (origen && rowOrigen !== origen) {
                    return false;
                }

                // Filtro cliente
                if (cliente && rowCliente !== cliente) {
                    return false;
                }

                return true;
            });

            // Eventos de filtros (instantaneos)
            $('.filter-input').on('change', function() {
                dataTable.draw();
            });

            // Limpiar filtros
            $('#filter-clear').on('click', function() {
                $('#filter-fecha-desde').val('');
                $('#filter-fecha-hasta').val('');
                $('#filter-vendedor').val('');
                $('#filter-origen').val('');
                $('#filter-cliente').val('');
                dataTable.draw();
            });
        });
    </script>
@stop
