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
         TABLA DE VENTAS POS CON FILTROS INTEGRADOS
    ======================================== --}}
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                <i class="fas fa-receipt mr-2"></i>VENTAS POS
            </h3>
        </div>
        <div class="card-body">
            {{-- FILTROS INLINE --}}
            <div class="row mb-3" id="filters-row">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="small text-muted mb-1">Fecha Desde</label>
                    <input type="date" class="form-control form-control-sm filter-input" id="filter-fecha-desde">
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
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
                    <label class="small text-muted mb-1">Estado</label>
                    <select class="form-control form-control-sm filter-input" id="filter-estado">
                        <option value="">Todos</option>
                        <option value="Activa">Activas</option>
                        <option value="Cancelada">Canceladas</option>
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
                <table id="posSalesTable" class="table table-bordered table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Fecha/Hora</th>
                            <th class="text-center">Pedido</th>
                            <th class="text-center">Cliente</th>
                            <th class="text-center">Vendedor</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr class="{{ $sale->isCancelled() ? 'table-danger' : '' }}"
                                data-fecha="{{ $sale->delivered_date?->format('Y-m-d') }}"
                                data-vendedor="{{ $sale->creator?->name ?? '' }}"
                                data-estado="{{ $sale->isCancelled() ? 'Cancelada' : 'Activa' }}">
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="text-center align-middle" data-order="{{ $sale->delivered_date?->format('Y-m-d H:i:s') }}">
                                    {{ $sale->delivered_date?->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">{{ $sale->created_at->format('H:i') }}</small>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-view-items"
                                            data-order-id="{{ $sale->id }}"
                                            data-order-number="{{ $sale->order_number }}"
                                            title="Ver productos de este pedido">
                                        <strong>{{ $sale->order_number }}</strong>
                                        <span class="badge badge-info ml-1">{{ $sale->movements_count }}</span>
                                    </button>
                                </td>
                                <td class="text-center align-middle">
                                    @if ($sale->cliente)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info btn-view-cliente"
                                                data-cliente-id="{{ $sale->cliente->id }}"
                                                data-cliente-nombre="{{ $sale->cliente->nombre }} {{ $sale->cliente->apellidos }}"
                                                title="Ver información del cliente">
                                            <i class="fas fa-user mr-1"></i>
                                            <strong>{{ $sale->cliente->nombre }} {{ $sale->cliente->apellidos }}</strong>
                                        </button>
                                    @else
                                        <span class="text-muted"><i class="fas fa-user-slash mr-1"></i>Sin cliente</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    {{ $sale->creator?->name ?? 'N/A' }}
                                </td>
                                <td class="text-center align-middle">
                                    <strong class="text-success">${{ number_format($sale->total, 2) }}</strong>
                                </td>
                                <td class="text-center align-middle">
                                    @if ($sale->isCancelled())
                                        <span class="badge badge-danger">
                                            <i class="fas fa-ban mr-1"></i>Cancelada
                                        </span>
                                        @if ($sale->cancelled_at)
                                            <br>
                                            <small class="text-muted">
                                                {{ $sale->cancelled_at->format('d/m/Y H:i') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i>Activa
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.pos-sales.show', $sale) }}"
                                           class="btn btn-info" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (!$sale->isCancelled())
                                            <button type="button"
                                                    class="btn btn-danger btn-cancel-sale"
                                                    data-order-id="{{ $sale->id }}"
                                                    data-order-number="{{ $sale->order_number }}"
                                                    data-order-date="{{ $sale->delivered_date?->format('d/m/Y H:i') }}"
                                                    data-order-total="{{ number_format($sale->total, 2) }}"
                                                    data-order-seller="{{ $sale->creator?->name ?? 'N/A' }}"
                                                    title="Cancelar venta">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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

    {{-- ========================================
         MODAL VER ITEMS DEL PEDIDO
    ======================================== --}}
    <div class="modal fade" id="modalViewItems" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-boxes mr-2"></i>
                        Productos del Pedido: <span id="items-order-number">--</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Loading indicator --}}
                    <div id="items-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 mb-0">Cargando productos...</p>
                    </div>

                    {{-- Error container --}}
                    <div id="items-error" class="alert alert-danger d-none">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span id="items-error-text"></span>
                    </div>

                    {{-- Items table --}}
                    <div id="items-content" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm" id="items-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;" class="text-center">Foto</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width: 80px;">Cant.</th>
                                        <th class="text-right" style="width: 100px;">Precio</th>
                                        <th class="text-right" style="width: 100px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="items-table-body">
                                </tbody>
                            </table>
                        </div>

                        {{-- Totales --}}
                        <div class="row mt-3">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-right"><strong>Subtotal:</strong></td>
                                        <td class="text-right" style="width: 120px;" id="items-subtotal">$0.00</td>
                                    </tr>
                                    <tr id="items-discount-row" class="d-none">
                                        <td class="text-right"><strong>Descuento:</strong></td>
                                        <td class="text-right text-danger" id="items-discount">-$0.00</td>
                                    </tr>
                                    <tr id="items-iva-row" class="d-none">
                                        <td class="text-right"><strong>IVA:</strong></td>
                                        <td class="text-right" id="items-iva">$0.00</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="text-right"><strong class="h5">TOTAL:</strong></td>
                                        <td class="text-right"><strong class="h5 text-success" id="items-total">$0.00</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         MODAL VER INFORMACION DEL CLIENTE
    ======================================== --}}
    <div class="modal fade" id="modalViewCliente" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user mr-2"></i>
                        Cliente: <span id="cliente-nombre-header">--</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Loading indicator --}}
                    <div id="cliente-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                        <p class="mt-2 mb-0">Cargando informacion del cliente...</p>
                    </div>

                    {{-- Error container --}}
                    <div id="cliente-error" class="alert alert-danger d-none">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span id="cliente-error-text"></span>
                    </div>

                    {{-- Cliente content --}}
                    <div id="cliente-content" class="d-none">
                        <div class="row">
                            {{-- Columna izquierda: Datos de contacto --}}
                            <div class="col-md-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-address-card mr-2"></i>Datos de Contacto</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted" style="width: 100px;">Nombre:</td>
                                                <td><strong id="cliente-nombre">--</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Telefono:</td>
                                                <td>
                                                    <a href="#" id="cliente-telefono-link" class="d-none">
                                                        <i class="fas fa-phone mr-1"></i><span id="cliente-telefono">--</span>
                                                    </a>
                                                    <span id="cliente-telefono-text" class="d-none">--</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Email:</td>
                                                <td>
                                                    <a href="#" id="cliente-email-link" class="d-none">
                                                        <i class="fas fa-envelope mr-1"></i><span id="cliente-email">--</span>
                                                    </a>
                                                    <span id="cliente-email-text" class="text-muted d-none">Sin email</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Cliente desde:</td>
                                                <td id="cliente-desde">--</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Estado:</td>
                                                <td>
                                                    <span id="cliente-activo-badge" class="badge">--</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                {{-- Datos fiscales --}}
                                <div class="card card-outline card-secondary mb-3" id="cliente-fiscal-card">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-file-invoice mr-2"></i>Datos Fiscales</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted" style="width: 100px;">RFC:</td>
                                                <td id="cliente-rfc">--</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Razon Social:</td>
                                                <td id="cliente-razon-social">--</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna derecha: Direccion y Estadisticas --}}
                            <div class="col-md-6">
                                {{-- Direccion --}}
                                <div class="card card-outline card-warning mb-3" id="cliente-direccion-card">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Direccion</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <p id="cliente-direccion" class="mb-1">--</p>
                                        <p class="mb-0 text-muted">
                                            <span id="cliente-estado-geo">--</span>
                                            <span id="cliente-ciudad">--</span>
                                            <span id="cliente-cp">--</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Estadisticas --}}
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Estadisticas POS</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row text-center">
                                            <div class="col-6 border-right">
                                                <h4 class="text-primary mb-0" id="cliente-total-compras">0</h4>
                                                <small class="text-muted">Compras POS</small>
                                            </div>
                                            <div class="col-6">
                                                <h4 class="text-success mb-0" id="cliente-monto-total">$0</h4>
                                                <small class="text-muted">Total Gastado</small>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="row text-center">
                                            <div class="col-6 border-right">
                                                <span class="text-muted">Ultima compra:</span><br>
                                                <strong id="cliente-ultima-compra">--</strong>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted">Pedidos (no POS):</span><br>
                                                <strong id="cliente-total-pedidos">0</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Medidas (si tiene) --}}
                        <div class="card card-outline card-info" id="cliente-medidas-card">
                            <div class="card-header py-2">
                                <h6 class="mb-0"><i class="fas fa-ruler mr-2"></i>Medidas del Cliente</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Busto</small>
                                            <strong id="cliente-busto">--</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Alto Cintura</small>
                                            <strong id="cliente-alto-cintura">--</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Cintura</small>
                                            <strong id="cliente-cintura">--</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Cadera</small>
                                            <strong id="cliente-cadera">--</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Largo</small>
                                            <strong id="cliente-largo">--</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-4 mb-2">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block">Largo Vestido</small>
                                            <strong id="cliente-largo-vestido">--</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Observaciones --}}
                        <div class="card card-outline card-secondary mt-3" id="cliente-observaciones-card">
                            <div class="card-header py-2">
                                <h6 class="mb-0"><i class="fas fa-sticky-note mr-2"></i>Observaciones</h6>
                            </div>
                            <div class="card-body py-2">
                                <p id="cliente-observaciones" class="mb-0 text-muted font-italic">Sin observaciones</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="cliente-edit-link" class="btn btn-warning" target="_blank">
                        <i class="fas fa-edit mr-1"></i> Editar Cliente
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cerrar
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

        /* Fondo transparente y sin borde en el contenedor de botones */
        #posSalesTable_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        /* Estilo personalizado para los botones */
        #posSalesTable_wrapper .dt-buttons .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }

        /* Colores por tipo de boton */
        #posSalesTable_wrapper .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        #posSalesTable_wrapper .btn-success {
            background-color: #28a745;
            border: none;
        }

        #posSalesTable_wrapper .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        #posSalesTable_wrapper .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        #posSalesTable_wrapper .btn-default {
            background-color: #6e7176;
            color: #fff;
            border: none;
        }

        /* Filtros */
        #filters-row label {
            font-weight: 600;
        }

        #filters-row .form-control-sm {
            height: calc(1.8rem + 2px);
        }

        /* Tabla responsiva y centrada */
        #posSalesTable {
            width: 100% !important;
        }

        #posSalesTable th,
        #posSalesTable td {
            vertical-align: middle !important;
            white-space: nowrap;
        }

        /* En pantallas pequeñas, permitir wrap en columnas de texto */
        @media (max-width: 768px) {
            #posSalesTable th,
            #posSalesTable td {
                white-space: normal;
                font-size: 0.85rem;
            }

            #posSalesTable .btn-group-sm .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        /* Estilos para filas canceladas */
        #posSalesTable .table-danger td {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        /* Hover en filas */
        #posSalesTable tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.075) !important;
        }

        #posSalesTable .table-danger:hover td {
            background-color: rgba(220, 53, 69, 0.2) !important;
        }

        /* Boton ver items */
        .btn-view-items {
            border-width: 2px;
            transition: all 0.2s ease;
        }

        .btn-view-items:hover {
            background-color: #007bff;
            color: #fff;
            transform: scale(1.02);
        }

        .btn-view-items .badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
        }

        /* Modal items */
        #items-table img {
            object-fit: cover;
            border-radius: 4px;
        }

        #items-table .product-placeholder {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            border-radius: 4px;
            color: #6c757d;
        }

        /* Boton ver cliente (mismo estilo que btn-view-items) */
        .btn-view-cliente {
            border-width: 2px;
            transition: all 0.2s ease;
        }

        .btn-view-cliente:hover {
            background-color: #17a2b8;
            color: #fff;
            transform: scale(1.02);
        }

        /* Modal cliente cards */
        #modalViewCliente .card {
            margin-bottom: 1rem;
        }

        #modalViewCliente .card-header {
            background-color: #f8f9fa;
        }

        #modalViewCliente .card-outline.card-primary .card-header {
            border-bottom-color: #007bff;
        }

        #modalViewCliente .card-outline.card-success .card-header {
            border-bottom-color: #28a745;
        }

        #modalViewCliente .card-outline.card-info .card-header {
            border-bottom-color: #17a2b8;
        }

        #modalViewCliente .bg-light.rounded {
            border: 1px solid #dee2e6;
        }

        /* Responsive modal cliente */
        @media (max-width: 768px) {
            #modalViewCliente .modal-dialog {
                margin: 0.5rem;
            }

            #modalViewCliente .col-md-2 {
                flex: 0 0 33.333%;
                max-width: 33.333%;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            'use strict';

            var dataTable;

            // ========================================
            // INICIALIZAR DATATABLE
            // ========================================
            dataTable = $("#posSalesTable").DataTable({
                "pageLength": 10,
                "order": [[1, 'desc']], // Ordenar por fecha descendente (columna 1)
                "language": {
                    "emptyTable": "No hay ventas POS registradas",
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
                "responsive": {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                "lengthChange": true,
                "autoWidth": false,
                "scrollX": true,
                "columnDefs": [
                    { "orderable": false, "targets": [7] }, // Deshabilitar orden en Acciones
                    { "className": "text-center", "targets": "_all" }, // Centrar todas las columnas
                    { "responsivePriority": 1, "targets": [2, 5, 7] }, // Prioridad: Pedido, Total, Acciones
                    { "responsivePriority": 2, "targets": [1, 6] }, // Prioridad: Fecha, Estado
                    { "responsivePriority": 3, "targets": [3, 4] } // Prioridad: Cliente, Vendedor
                ],
                buttons: [{
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger',
                        title: 'Historial Ventas POS',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info',
                        title: 'Historial Ventas POS',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
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
            });

            dataTable.buttons().container().appendTo('#posSalesTable_wrapper .row:eq(0)');

            // ========================================
            // FILTROS PERSONALIZADOS (sin recargar)
            // ========================================
            function applyFilters() {
                var fechaDesde = $('#filter-fecha-desde').val();
                var fechaHasta = $('#filter-fecha-hasta').val();
                var vendedor = $('#filter-vendedor').val();
                var estado = $('#filter-estado').val();

                // Filtro personalizado de DataTable
                $.fn.dataTable.ext.search.pop(); // Limpiar filtro anterior
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var row = dataTable.row(dataIndex).node();
                    var rowFecha = $(row).data('fecha') || '';
                    var rowVendedor = $(row).data('vendedor') || '';
                    var rowEstado = $(row).data('estado') || '';

                    // Filtro fecha desde
                    if (fechaDesde && rowFecha < fechaDesde) {
                        return false;
                    }

                    // Filtro fecha hasta
                    if (fechaHasta && rowFecha > fechaHasta) {
                        return false;
                    }

                    // Filtro vendedor
                    if (vendedor && rowVendedor !== vendedor) {
                        return false;
                    }

                    // Filtro estado
                    if (estado && rowEstado !== estado) {
                        return false;
                    }

                    return true;
                });

                dataTable.draw();
            }

            // Eventos de filtros (instantaneos)
            $('#filter-fecha-desde, #filter-fecha-hasta').on('change', applyFilters);
            $('#filter-vendedor, #filter-estado').on('change', applyFilters);

            // Limpiar filtros
            $('#filter-clear').on('click', function() {
                $('#filter-fecha-desde').val('');
                $('#filter-fecha-hasta').val('');
                $('#filter-vendedor').val('');
                $('#filter-estado').val('');

                // Limpiar filtro personalizado
                $.fn.dataTable.ext.search.pop();
                dataTable.draw();
            });

            // ========================================
            // BIND BOTONES DE CANCELACION
            // ========================================
            $(document).on('click', '.btn-cancel-sale', function(e) {
                e.preventDefault();
                var btn = $(this);

                $('#cancel-order-id').val(btn.data('order-id'));
                $('#cancel-order-number').text(btn.data('order-number'));
                $('#cancel-order-date').text(btn.data('order-date'));
                $('#cancel-order-total').text(btn.data('order-total'));
                $('#cancel-order-seller').text(btn.data('order-seller'));

                // Reset
                $('#cancel-reason').val('');
                $('#cancel-reason-count').text('0');
                $('#cancel-confirm-checkbox').prop('checked', false);
                $('#btn-execute-cancel').prop('disabled', true);

                $('#modalCancelSale').modal('show');
            });

            // ========================================
            // LOGICA DE CANCELACION
            // ========================================
            function updateCancelButtonState() {
                var hasReason = $('#cancel-reason').val().length >= 10;
                var hasConfirm = $('#cancel-confirm-checkbox').is(':checked');
                $('#btn-execute-cancel').prop('disabled', !(hasReason && hasConfirm));
            }

            $('#cancel-reason').on('input', function() {
                $('#cancel-reason-count').text($(this).val().length);
                updateCancelButtonState();
            });

            $('#cancel-confirm-checkbox').on('change', updateCancelButtonState);

            $('#btn-execute-cancel').on('click', async function() {
                var orderId = $('#cancel-order-id').val();
                var reason = $('#cancel-reason').val();
                var btn = $(this);

                if (!orderId || reason.length < 10 || !$('#cancel-confirm-checkbox').is(':checked')) {
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...');

                try {
                    var response = await fetch('/admin/pos-sales/' + orderId + '/cancel', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            cancel_reason: reason
                        })
                    });

                    var data = await response.json();

                    $('#modalCancelSale').modal('hide');

                    if (data.success) {
                        // Calcular total de unidades devueltas
                        var totalUnidades = 0;
                        if (data.data.returned_items && data.data.returned_items.length > 0) {
                            data.data.returned_items.forEach(function(item) {
                                totalUnidades += parseFloat(item.quantity) || 0;
                            });
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Venta cancelada',
                            text: 'Stock revertido: ' + totalUnidades + ' unidades (' + data.data.items_returned + ' productos)',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            confirmButtonColor: '#3085d6'
                        });
                    }

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexion',
                        text: error.message,
                        confirmButtonColor: '#3085d6'
                    });
                }

                btn.prop('disabled', false).html('<i class="fas fa-ban mr-1"></i> Cancelar Venta');
            });

            // ========================================
            // VER ITEMS DEL PEDIDO (AJAX)
            // ========================================
            $(document).on('click', '.btn-view-items', function(e) {
                e.preventDefault();
                var btn = $(this);
                var orderId = btn.data('order-id');
                var orderNumber = btn.data('order-number');

                // Reset modal
                $('#items-order-number').text(orderNumber);
                $('#items-loading').removeClass('d-none');
                $('#items-error').addClass('d-none');
                $('#items-content').addClass('d-none');
                $('#items-table-body').empty();

                $('#modalViewItems').modal('show');

                // Fetch items via AJAX
                $.ajax({
                    url: '/admin/pos-sales/' + orderId + '/items',
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#items-loading').addClass('d-none');

                        if (response.success) {
                            var data = response.data;
                            var tbody = $('#items-table-body');

                            // Render items
                            data.items.forEach(function(item) {
                                var imgHtml = item.image_url
                                    ? '<img src="' + item.image_url + '" alt="' + item.product_name + '" width="50" height="50">'
                                    : '<div class="product-placeholder"><i class="fas fa-image"></i></div>';

                                var variantInfo = item.variant_name !== '-'
                                    ? '<br><small class="text-muted">' + item.variant_name + '</small>'
                                    : '';

                                var row = '<tr>' +
                                    '<td class="text-center align-middle">' + imgHtml + '</td>' +
                                    '<td class="align-middle">' +
                                        '<strong>' + item.product_name + '</strong>' +
                                        variantInfo +
                                    '</td>' +
                                    '<td class="text-center align-middle"><span class="badge badge-secondary">' + item.quantity + '</span></td>' +
                                    '<td class="text-right align-middle">$' + item.unit_price.toFixed(2) + '</td>' +
                                    '<td class="text-right align-middle"><strong>$' + item.subtotal.toFixed(2) + '</strong></td>' +
                                '</tr>';

                                tbody.append(row);
                            });

                            // Render totals
                            $('#items-subtotal').text('$' + data.subtotal.toFixed(2));

                            if (data.discount > 0) {
                                $('#items-discount').text('-$' + data.discount.toFixed(2));
                                $('#items-discount-row').removeClass('d-none');
                            } else {
                                $('#items-discount-row').addClass('d-none');
                            }

                            if (data.iva_amount > 0) {
                                $('#items-iva').text('$' + data.iva_amount.toFixed(2));
                                $('#items-iva-row').removeClass('d-none');
                            } else {
                                $('#items-iva-row').addClass('d-none');
                            }

                            $('#items-total').text('$' + data.total.toFixed(2));
                            $('#items-content').removeClass('d-none');

                        } else {
                            $('#items-error-text').text(response.error || 'Error desconocido');
                            $('#items-error').removeClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        $('#items-loading').addClass('d-none');
                        var errorMsg = 'Error al cargar los productos';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        $('#items-error-text').text(errorMsg);
                        $('#items-error').removeClass('d-none');
                    }
                });
            });

            // ========================================
            // VER INFORMACION DEL CLIENTE (AJAX)
            // ========================================
            $(document).on('click', '.btn-view-cliente', function(e) {
                e.preventDefault();
                var btn = $(this);
                var clienteId = btn.data('cliente-id');
                var clienteNombre = btn.data('cliente-nombre');

                // Reset modal
                $('#cliente-nombre-header').text(clienteNombre);
                $('#cliente-loading').removeClass('d-none');
                $('#cliente-error').addClass('d-none');
                $('#cliente-content').addClass('d-none');

                $('#modalViewCliente').modal('show');

                // Fetch cliente via AJAX
                $.ajax({
                    url: '/admin/pos-sales/cliente/' + clienteId,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#cliente-loading').addClass('d-none');

                        if (response.success) {
                            var data = response.data;

                            // Datos de contacto
                            $('#cliente-nombre').text(data.nombre_completo);
                            $('#cliente-desde').text(data.created_at || '--');

                            // Telefono
                            if (data.telefono) {
                                $('#cliente-telefono').text(data.telefono);
                                $('#cliente-telefono-link')
                                    .attr('href', 'tel:' + data.telefono)
                                    .removeClass('d-none');
                                $('#cliente-telefono-text').addClass('d-none');
                            } else {
                                $('#cliente-telefono-text').text('Sin telefono').removeClass('d-none');
                                $('#cliente-telefono-link').addClass('d-none');
                            }

                            // Email
                            if (data.email) {
                                $('#cliente-email').text(data.email);
                                $('#cliente-email-link')
                                    .attr('href', 'mailto:' + data.email)
                                    .removeClass('d-none');
                                $('#cliente-email-text').addClass('d-none');
                            } else {
                                $('#cliente-email-text').removeClass('d-none');
                                $('#cliente-email-link').addClass('d-none');
                            }

                            // Estado activo
                            if (data.activo) {
                                $('#cliente-activo-badge')
                                    .text('Activo')
                                    .removeClass('badge-danger')
                                    .addClass('badge-success');
                            } else {
                                $('#cliente-activo-badge')
                                    .text('Inactivo')
                                    .removeClass('badge-success')
                                    .addClass('badge-danger');
                            }

                            // Datos fiscales
                            var hasFiscal = data.rfc || data.razon_social;
                            if (hasFiscal) {
                                $('#cliente-rfc').text(data.rfc || '--');
                                $('#cliente-razon-social').text(data.razon_social || '--');
                                $('#cliente-fiscal-card').show();
                            } else {
                                $('#cliente-fiscal-card').hide();
                            }

                            // Direccion
                            var hasDireccion = data.direccion || data.ciudad || data.estado || data.codigo_postal;
                            if (hasDireccion) {
                                $('#cliente-direccion').text(data.direccion || '--');
                                $('#cliente-estado-geo').text(data.estado ? data.estado + ', ' : '');
                                $('#cliente-ciudad').text(data.ciudad || '');
                                $('#cliente-cp').text(data.codigo_postal ? ' C.P. ' + data.codigo_postal : '');
                                $('#cliente-direccion-card').show();
                            } else {
                                $('#cliente-direccion-card').hide();
                            }

                            // Estadisticas
                            $('#cliente-total-compras').text(data.stats.total_compras_pos || 0);
                            $('#cliente-monto-total').text('$' + parseFloat(data.stats.monto_total_pos || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
                            $('#cliente-ultima-compra').text(data.stats.ultima_compra_pos || 'Ninguna');
                            $('#cliente-total-pedidos').text(data.stats.total_pedidos || 0);

                            // Medidas
                            var medidas = data.medidas;
                            var hasMedidas = medidas.busto || medidas.alto_cintura || medidas.cintura ||
                                            medidas.cadera || medidas.largo || medidas.largo_vestido;
                            if (hasMedidas) {
                                $('#cliente-busto').text(medidas.busto ? medidas.busto + ' cm' : '--');
                                $('#cliente-alto-cintura').text(medidas.alto_cintura ? medidas.alto_cintura + ' cm' : '--');
                                $('#cliente-cintura').text(medidas.cintura ? medidas.cintura + ' cm' : '--');
                                $('#cliente-cadera').text(medidas.cadera ? medidas.cadera + ' cm' : '--');
                                $('#cliente-largo').text(medidas.largo ? medidas.largo + ' cm' : '--');
                                $('#cliente-largo-vestido').text(medidas.largo_vestido ? medidas.largo_vestido + ' cm' : '--');
                                $('#cliente-medidas-card').show();
                            } else {
                                $('#cliente-medidas-card').hide();
                            }

                            // Observaciones
                            if (data.observaciones) {
                                $('#cliente-observaciones').text(data.observaciones).removeClass('text-muted font-italic');
                                $('#cliente-observaciones-card').show();
                            } else {
                                $('#cliente-observaciones-card').hide();
                            }

                            // Link de edicion
                            $('#cliente-edit-link').attr('href', '/clientes/edit/' + data.id);

                            $('#cliente-content').removeClass('d-none');

                        } else {
                            $('#cliente-error-text').text(response.error || 'Error desconocido');
                            $('#cliente-error').removeClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        $('#cliente-loading').addClass('d-none');
                        var errorMsg = 'Error al cargar la informacion del cliente';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        $('#cliente-error-text').text(errorMsg);
                        $('#cliente-error').removeClass('d-none');
                    }
                });
            });
        });
    </script>
@stop
