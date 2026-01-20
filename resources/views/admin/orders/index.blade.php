@extends('adminlte::page')

@section('title', 'Pedidos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clipboard-list mr-2"></i> Pedidos</h1>
        <div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#quickOrderModal">
                <i class="fas fa-bolt"></i> Pedido Rápido
            </button>
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </a>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Filtros REACTIVOS (sin botón) --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">-- Estado --</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                        <option value="in_production" {{ request('status') == 'in_production' ? 'selected' : '' }}>En Producción</option>
                        <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Listo</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Entregado</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterPaymentStatus" class="form-control form-control-sm">
                        <option value="">-- Pago --</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Parcial</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Pagado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                </div>
                <div class="col-md-4 text-right">
                    <span id="filterLoading" class="text-muted small" style="display:none;">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Filtrando...
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de pedidos --}}
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="bg-light">
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Items</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Pago</th>
                        <th>Fecha</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-weight-bold">
                                    {{ $order->order_number }}
                                </a>
                                @if($order->isAnnex())
                                    <span class="badge badge-info ml-1" title="Pedido Anexo">
                                        <i class="fas fa-link"></i>
                                    </span>
                                @endif
                                @if($order->urgency_level !== 'normal')
                                    <span class="badge badge-{{ $order->urgency_color }} ml-1">
                                        {{ $order->urgency_label }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</td>
                            <td>{{ $order->items->count() }} producto(s)</td>
                            <td class="text-right font-weight-bold">${{ number_format($order->total, 2) }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $order->payment_status_color }}">
                                    {{ $order->payment_status_label }}
                                </span>
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No hay pedidos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- ========================================== --}}
    {{-- MODAL: PEDIDO RÁPIDO --}}
    {{-- ========================================== --}}
    <div class="modal fade" id="quickOrderModal" tabindex="-1" role="dialog" aria-labelledby="quickOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="quickOrderModalLabel">
                        <i class="fas fa-bolt mr-2"></i> Nuevo Pedido Rápido
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickOrderForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            {{-- === COLUMNA IZQUIERDA: Cliente + Productos === --}}
                            <div class="col-lg-8">
                                {{-- SECCIÓN: CLIENTE --}}
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-user mr-1"></i> Cliente</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row align-items-end">
                                            <div class="col-md-8">
                                                <label class="mb-1">Buscar cliente</label>
                                                <select id="quickClienteSelect" name="cliente_id" class="form-control" style="width:100%">
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-outline-primary btn-block" id="btnShowQuickClient">
                                                    <i class="fas fa-user-plus"></i> Cliente Nuevo
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Formulario inline cliente nuevo --}}
                                        <div id="quickClientForm" class="mt-3 p-3 bg-light rounded" style="display:none;">
                                            <h6 class="text-primary mb-2"><i class="fas fa-user-plus mr-1"></i> Crear Cliente Rápido</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label class="mb-1">Nombre *</label>
                                                    <input type="text" class="form-control form-control-sm" id="quickClientNombre" placeholder="Nombre">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="mb-1">Apellidos</label>
                                                    <input type="text" class="form-control form-control-sm" id="quickClientApellidos" placeholder="Apellidos">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="mb-1">Teléfono *</label>
                                                    <input type="text" class="form-control form-control-sm" id="quickClientTelefono" placeholder="10 dígitos" maxlength="10">
                                                </div>
                                            </div>
                                            <div class="mt-2 text-right">
                                                <button type="button" class="btn btn-sm btn-secondary" id="btnCancelQuickClient">Cancelar</button>
                                                <button type="button" class="btn btn-sm btn-primary" id="btnSaveQuickClient">
                                                    <i class="fas fa-save"></i> Guardar y Seleccionar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECCIÓN: PRODUCTOS --}}
                                <div class="card card-outline card-info">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-box mr-1"></i> Productos</h6>
                                        <button type="button" class="btn btn-sm btn-info" id="btnAddProduct">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-bordered mb-0" id="quickProductsTable">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width:40%">Producto</th>
                                                    <th style="width:15%">Variante</th>
                                                    <th style="width:10%" class="text-center">Cant.</th>
                                                    <th style="width:15%" class="text-right">Precio</th>
                                                    <th style="width:15%" class="text-right">Subtotal</th>
                                                    <th style="width:5%"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="quickProductsBody">
                                                <tr id="noProductsRow">
                                                    <td colspan="6" class="text-center text-muted py-3">
                                                        <i class="fas fa-shopping-cart mr-1"></i> Sin productos. Haz clic en "Agregar" para comenzar.
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <td colspan="4" class="text-right font-weight-bold">Subtotal:</td>
                                                    <td class="text-right font-weight-bold" id="quickSubtotal">$0.00</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- === COLUMNA DERECHA: Pago + Urgencia === --}}
                            <div class="col-lg-4">
                                {{-- SECCIÓN: PAGO --}}
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-money-bill-wave mr-1"></i> Pago</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-2">
                                            <label class="mb-1">Método de pago</label>
                                            <select name="payment_method" id="quickPaymentMethod" class="form-control form-control-sm">
                                                <option value="">-- Sin pago inicial --</option>
                                                <option value="cash">Efectivo</option>
                                                <option value="transfer">Transferencia</option>
                                                <option value="card">Tarjeta</option>
                                                <option value="other">Otro</option>
                                            </select>
                                        </div>

                                        <div id="paymentOptions" style="display:none;">
                                            <div class="form-group mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="quickPayFull" name="pay_full" value="1">
                                                    <label class="custom-control-label" for="quickPayFull">
                                                        <strong>Pagar Total</strong>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group mb-2" id="anticipoGroup">
                                                <label class="mb-1">Anticipo</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0" name="initial_payment" id="quickAnticipo" class="form-control" placeholder="0.00">
                                                </div>
                                            </div>

                                            <div class="form-group mb-0">
                                                <label class="mb-1">Referencia (opcional)</label>
                                                <input type="text" name="payment_reference" class="form-control form-control-sm" placeholder="No. transferencia, folio, etc.">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECCIÓN: URGENCIA Y FECHA --}}
                                <div class="card card-outline card-warning mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-clock mr-1"></i> Urgencia y Entrega</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-2">
                                            <label class="mb-1">Nivel de urgencia</label>
                                            <select name="urgency_level" id="quickUrgency" class="form-control form-control-sm">
                                                <option value="normal">Normal</option>
                                                <option value="urgente">Urgente (+30% rápido)</option>
                                                <option value="express">Express (+50% rápido)</option>
                                            </select>
                                        </div>

                                        <div class="alert alert-info py-2 mb-2" id="minimumDateAlert">
                                            <small>
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Fecha mínima: <strong id="minimumDateValue">-</strong>
                                            </small>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="mb-1">Fecha prometida</label>
                                            <input type="date" name="promised_date" id="quickPromisedDate" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>

                                {{-- SECCIÓN: NOTAS --}}
                                <div class="card card-outline card-secondary">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0"><i class="fas fa-sticky-note mr-1"></i> Notas</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notas generales del pedido..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="mr-auto">
                            <span class="text-muted">Total:</span>
                            <span class="h4 mb-0 text-success font-weight-bold" id="quickTotal">$0.00</span>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnSubmitQuickOrder">
                            <i class="fas fa-save mr-1"></i> Crear Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- MODAL: AGREGAR PRODUCTO --}}
    {{-- ========================================== --}}
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-box mr-2"></i> Agregar Producto</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Buscar producto</label>
                        <select id="productSearch" class="form-control" style="width:100%"></select>
                    </div>

                    <div id="productDetails" style="display:none;">
                        <hr>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img id="productImage" src="" alt="Producto" class="img-fluid rounded" style="max-height:150px;">
                            </div>
                            <div class="col-md-8">
                                <h5 id="productName" class="mb-2"></h5>
                                <p class="text-muted mb-2" id="productSku"></p>

                                <div class="form-group" id="variantGroup" style="display:none;">
                                    <label>Variante</label>
                                    <select id="productVariant" class="form-control form-control-sm">
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Cantidad</label>
                                            <input type="number" id="productQty" class="form-control" value="1" min="1">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Precio Unitario</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="number" id="productPrice" class="form-control" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Texto a bordar (opcional)</label>
                                    <input type="text" id="productEmbroidery" class="form-control form-control-sm" placeholder="Nombre, iniciales, etc.">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btnConfirmProduct" disabled>
                        <i class="fas fa-plus mr-1"></i> Agregar al Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.8125rem + 2px) !important;
    }
    #quickProductsTable td {
        vertical-align: middle;
    }
    .product-row .btn-remove {
        opacity: 0.6;
    }
    .product-row:hover .btn-remove {
        opacity: 1;
    }
    /* FIX: Modal scroll controlado */
    #quickOrderModal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
    #quickOrderModal .modal-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
    }
    /* FIX: Select2 z-index en modales */
    .select2-container--open {
        z-index: 9999 !important;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // === ESTADO DEL PEDIDO ===
    let orderItems = [];
    let selectedProduct = null;
    let productIndex = 0;

    // === INICIALIZAR SELECT2: CLIENTES ===
    $('#quickClienteSelect').select2({
        theme: 'bootstrap4',
        placeholder: 'Escriba nombre o teléfono...',
        allowClear: true,
        dropdownParent: $('#quickOrderModal'),
        minimumInputLength: 2,
        width: '100%',
        ajax: {
            url: '{{ route("admin.orders.ajax.search-clientes") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term, page: params.page || 1 };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: { more: data.pagination.more }
                };
            },
            cache: true
        }
    }).on('select2:open', function() {
        // FIX CRÍTICO: Focus real en el input de búsqueda
        setTimeout(function() {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 0);
    });

    // === CLIENTE RÁPIDO: MOSTRAR/OCULTAR FORM ===
    $('#btnShowQuickClient').on('click', function() {
        $('#quickClientForm').slideDown();
        $(this).hide();
        $('#quickClientNombre').focus();
    });

    $('#btnCancelQuickClient').on('click', function() {
        $('#quickClientForm').slideUp();
        $('#btnShowQuickClient').show();
        $('#quickClientNombre, #quickClientApellidos, #quickClientTelefono').val('');
    });

    // === CLIENTE RÁPIDO: GUARDAR ===
    $('#btnSaveQuickClient').on('click', function() {
        const nombre = $('#quickClientNombre').val().trim();
        const apellidos = $('#quickClientApellidos').val().trim();
        const telefono = $('#quickClientTelefono').val().trim();

        if (!nombre || !telefono) {
            alert('Nombre y teléfono son obligatorios');
            return;
        }

        if (!/^[0-9]{10}$/.test(telefono)) {
            alert('El teléfono debe tener exactamente 10 dígitos');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("admin.clientes.quick-store") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                nombre: nombre,
                apellidos: apellidos,
                telefono: telefono
            },
            success: function(response) {
                if (response.success) {
                    // Crear opción y seleccionar
                    const newOption = new Option(response.text, response.id, true, true);
                    $('#quickClienteSelect').append(newOption).trigger('change');

                    // Limpiar y ocultar form
                    $('#btnCancelQuickClient').click();
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Error al crear cliente';
                alert(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar y Seleccionar');
            }
        });
    });

    // === PAGO: MOSTRAR OPCIONES ===
    $('#quickPaymentMethod').on('change', function() {
        if ($(this).val()) {
            $('#paymentOptions').slideDown();
        } else {
            $('#paymentOptions').slideUp();
            $('#quickPayFull').prop('checked', false);
            $('#quickAnticipo').val('');
        }
    });

    $('#quickPayFull').on('change', function() {
        if ($(this).is(':checked')) {
            $('#anticipoGroup').hide();
            $('#quickAnticipo').val('');
        } else {
            $('#anticipoGroup').show();
        }
    });

    // === PRODUCTOS: ABRIR MODAL ===
    $('#btnAddProduct').on('click', function() {
        selectedProduct = null;
        $('#productSearch').val(null).trigger('change');
        $('#productDetails').hide();
        $('#btnConfirmProduct').prop('disabled', true);
        $('#addProductModal').modal('show');
    });

    // === PRODUCTOS: SELECT2 BÚSQUEDA ===
    $('#productSearch').select2({
        theme: 'bootstrap4',
        placeholder: 'Escriba SKU o nombre del producto...',
        allowClear: true,
        dropdownParent: $('#addProductModal'),
        minimumInputLength: 2,
        width: '100%',
        ajax: {
            url: '{{ route("admin.orders.ajax.search-products") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term, page: params.page || 1 };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                // FIX: data.results ya viene procesado del backend
                return {
                    results: data.results.map(function(p) {
                        return {
                            id: p.id,
                            text: p.name || p.text,
                            name: p.name,
                            sku: p.sku,
                            base_price: p.base_price,
                            image_url: p.image_url,
                            variants: p.variants || []
                        };
                    }),
                    pagination: { more: data.pagination ? data.pagination.more : false }
                };
            },
            cache: true
        },
        templateResult: formatProductResult,
        templateSelection: formatProductSelection
    }).on('select2:open', function() {
        // FIX CRÍTICO: Focus real en el input de búsqueda
        setTimeout(function() {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 0);
    });

    function formatProductResult(product) {
        if (product.loading) return product.text;
        if (!product.sku) return product.text;
        return $('<span><strong>' + (product.sku || '') + '</strong> - ' + (product.name || product.text) + '</span>');
    }

    function formatProductSelection(product) {
        return product.name || product.text || product.sku || '';
    }

    // === PRODUCTOS: AL SELECCIONAR ===
    $('#productSearch').on('select2:select', function(e) {
        const data = e.params.data;
        selectedProduct = data;

        $('#productName').text(data.name);
        $('#productSku').text('SKU: ' + data.sku);
        $('#productPrice').val(data.base_price);
        $('#productQty').val(1);
        $('#productEmbroidery').val('');

        // Imagen
        // FIX: Usar asset() helper path
        if (data.image_url) {
            $('#productImage').attr('src', data.image_url).show();
        } else {
            $('#productImage').attr('src', '{{ asset("img/no-image.png") }}').show();
        }

        // Variantes
        if (data.variants && data.variants.length > 0) {
            $('#productVariant').empty().append('<option value="">-- Seleccionar --</option>');
            data.variants.forEach(function(v) {
                $('#productVariant').append(
                    '<option value="' + v.id + '" data-price="' + v.final_price + '">' +
                    v.sku_variant + ' - ' + v.attributes_text +
                    '</option>'
                );
            });
            $('#variantGroup').show();
        } else {
            $('#variantGroup').hide();
            $('#productVariant').empty();
        }

        $('#productDetails').show();
        $('#btnConfirmProduct').prop('disabled', false);
    });

    // === PRODUCTOS: CAMBIO DE VARIANTE ===
    $('#productVariant').on('change', function() {
        const price = $(this).find(':selected').data('price');
        if (price) {
            $('#productPrice').val(price);
        }
    });

    // === PRODUCTOS: CONFIRMAR AGREGAR ===
    $('#btnConfirmProduct').on('click', function() {
        if (!selectedProduct) return;

        const variantId = $('#productVariant').val() || null;
        const variantText = variantId ? $('#productVariant option:selected').text() : '-';
        const qty = parseInt($('#productQty').val()) || 1;
        const price = parseFloat($('#productPrice').val()) || 0;
        const embroidery = $('#productEmbroidery').val().trim();

        const item = {
            index: productIndex++,
            product_id: selectedProduct.id,
            product_variant_id: variantId,
            product_name: selectedProduct.name,
            product_sku: selectedProduct.sku,
            variant_text: variantText,
            quantity: qty,
            unit_price: price,
            embroidery_text: embroidery
        };

        orderItems.push(item);
        renderProductsTable();
        updateTotals();

        $('#addProductModal').modal('hide');
    });

    // === PRODUCTOS: RENDERIZAR TABLA ===
    function renderProductsTable() {
        const tbody = $('#quickProductsBody');
        tbody.empty();

        if (orderItems.length === 0) {
            tbody.html(`
                <tr id="noProductsRow">
                    <td colspan="6" class="text-center text-muted py-3">
                        <i class="fas fa-shopping-cart mr-1"></i> Sin productos. Haz clic en "Agregar" para comenzar.
                    </td>
                </tr>
            `);
            return;
        }

        orderItems.forEach(function(item, idx) {
            const subtotal = item.quantity * item.unit_price;
            tbody.append(`
                <tr class="product-row" data-index="${item.index}">
                    <td>
                        <strong>${item.product_name}</strong>
                        <br><small class="text-muted">${item.product_sku}</small>
                        ${item.embroidery_text ? '<br><small class="text-info"><i class="fas fa-pen-fancy"></i> ' + item.embroidery_text + '</small>' : ''}
                    </td>
                    <td>${item.variant_text}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">$${item.unit_price.toFixed(2)}</td>
                    <td class="text-right">$${subtotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-index="${item.index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // === PRODUCTOS: ELIMINAR ===
    $(document).on('click', '.btn-remove', function() {
        const index = $(this).data('index');
        orderItems = orderItems.filter(item => item.index !== index);
        renderProductsTable();
        updateTotals();
    });

    // === CALCULAR TOTALES ===
    function updateTotals() {
        let subtotal = 0;
        orderItems.forEach(function(item) {
            subtotal += item.quantity * item.unit_price;
        });

        $('#quickSubtotal').text('$' + subtotal.toFixed(2));
        $('#quickTotal').text('$' + subtotal.toFixed(2));

        // Actualizar fecha mínima (simplificado, se calculará en backend)
        updateMinimumDate();
    }

    // === ACTUALIZAR FECHA MÍNIMA (estimación frontend) ===
    function updateMinimumDate() {
        // Simplificación: se calcula realmente en backend
        // Aquí solo mostramos la fecha actual + días según urgencia
        const urgency = $('#quickUrgency').val();
        let days = 7; // default
        if (urgency === 'urgente') days = 5;
        if (urgency === 'express') days = 3;

        const minDate = new Date();
        minDate.setDate(minDate.getDate() + days);
        const formatted = minDate.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });

        $('#minimumDateValue').text(formatted);
        $('#quickPromisedDate').attr('min', minDate.toISOString().split('T')[0]);
    }

    $('#quickUrgency').on('change', updateMinimumDate);

    // === ENVIAR PEDIDO ===
    $('#quickOrderForm').on('submit', function(e) {
        e.preventDefault();

        const clienteId = $('#quickClienteSelect').val();
        if (!clienteId) {
            alert('Debe seleccionar un cliente');
            return;
        }

        if (orderItems.length === 0) {
            alert('Debe agregar al menos un producto');
            return;
        }

        // Preparar items para envío
        const items = orderItems.map(function(item) {
            return {
                product_id: item.product_id,
                product_variant_id: item.product_variant_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                embroidery_text: item.embroidery_text
            };
        });

        const formData = {
            _token: '{{ csrf_token() }}',
            cliente_id: clienteId,
            items: items,
            payment_method: $('#quickPaymentMethod').val() || null,
            pay_full: $('#quickPayFull').is(':checked') ? 1 : 0,
            initial_payment: $('#quickAnticipo').val() || null,
            payment_reference: $('input[name="payment_reference"]').val() || null,
            urgency_level: $('#quickUrgency').val(),
            promised_date: $('#quickPromisedDate').val() || null,
            notes: $('textarea[name="notes"]').val() || null
        };

        const btn = $('#btnSubmitQuickOrder');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("admin.orders.ajax.store-quick") }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url;
                }
            },
            error: function(xhr) {
                let msg = 'Error al crear el pedido';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                } else if (xhr.responseJSON?.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Crear Pedido');
            }
        });
    });

    // === RESET AL CERRAR MODAL ===
    $('#quickOrderModal').on('hidden.bs.modal', function() {
        orderItems = [];
        productIndex = 0;
        selectedProduct = null;
        $('#quickClienteSelect').val(null).trigger('change');
        $('#quickOrderForm')[0].reset();
        $('#paymentOptions').hide();
        $('#quickClientForm').hide();
        $('#btnShowQuickClient').show();
        renderProductsTable();
        updateTotals();
    });

    // Inicializar
    updateMinimumDate();

    // ===========================================
    // FILTROS REACTIVOS (sin botón submit)
    // ===========================================
    $('#filterStatus, #filterPaymentStatus').on('change', function() {
        const status = $('#filterStatus').val();
        const paymentStatus = $('#filterPaymentStatus').val();

        // Construir URL con parámetros
        let url = '{{ route("admin.orders.index") }}';
        const params = [];
        if (status) params.push('status=' + status);
        if (paymentStatus) params.push('payment_status=' + paymentStatus);
        if (params.length > 0) url += '?' + params.join('&');

        // Mostrar loading y redirigir
        $('#filterLoading').show();
        window.location.href = url;
    });

    // ===========================================
    // FIX: Focus automático REAL en Select2 dentro de modal
    // ===========================================
    $('#quickOrderModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            $('#quickClienteSelect').select2('open');
            // Focus real en el input de búsqueda
            setTimeout(function() {
                document.querySelector('.select2-search__field')?.focus();
            }, 50);
        }, 150);
    });

    $('#addProductModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            $('#productSearch').select2('open');
            setTimeout(function() {
                document.querySelector('.select2-search__field')?.focus();
            }, 50);
        }, 150);
    });
});
</script>
@stop
