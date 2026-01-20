@extends('adminlte::page')

@section('title', 'Pedido ' . $order->order_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-clipboard-list mr-2"></i> {{ $order->order_number }}
                @if($order->isAnnex())
                    <span class="badge badge-info ml-2">ANEXO</span>
                @endif
            </h1>
            <small class="text-muted">Creado: {{ $order->created_at->format('d/m/Y H:i') }}</small>
            @if($order->creator)
                <small class="text-muted ml-2">por {{ $order->creator->name }}</small>
            @endif
        </div>
        <div>
            @if($order->isAnnex() && $order->parentOrder)
                <a href="{{ route('admin.orders.show', $order->parentOrder) }}" class="btn btn-info">
                    <i class="fas fa-level-up-alt"></i> Ver Pedido Original
                </a>
            @endif
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Alert: Pedido Padre (si es anexo) --}}
    @if($order->isAnnex() && $order->parentOrder)
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Este es un <strong>pedido anexo</strong> del pedido principal
            <a href="{{ route('admin.orders.show', $order->parentOrder) }}" class="alert-link">
                {{ $order->parentOrder->order_number }}
            </a>
        </div>
    @endif

    <div class="row">
        {{-- Columna principal --}}
        <div class="col-lg-8">
            {{-- Info del cliente --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i> Cliente</h5>
                </div>
                <div class="card-body">
                    <strong>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</strong><br>
                    <i class="fas fa-phone mr-1"></i> {{ $order->cliente->telefono }}<br>
                    @if($order->cliente->email)
                        <i class="fas fa-envelope mr-1"></i> {{ $order->cliente->email }}
                    @endif
                </div>
            </div>

            {{-- Items del pedido --}}
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box mr-2"></i> Productos</h5>
                    @if($canAddItems ?? false)
                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalAddAnnexItem">
                            <i class="fas fa-plus"></i> Agregar Item Anexo
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 60px;">Img</th>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if($item->product && $item->product->primaryImage)
                                            <img src="{{ asset('storage/' . $item->product->primaryImage->image_path) }}"
                                                 class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <img src="{{ asset('img/no-image.png') }}"
                                                 class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->is_annex)
                                            <span class="badge badge-warning ml-1" title="Agregado: {{ $item->annexed_at?->format('d/m/Y H:i') }}">
                                                <i class="fas fa-plus-circle"></i> ANEXO
                                            </span>
                                        @endif
                                        @if($item->variant_sku)
                                            <br><small class="text-muted">SKU: {{ $item->variant_sku }}</small>
                                        @endif
                                        @if($item->embroidery_text)
                                            <br><span class="badge badge-info"><i class="fas fa-pen-fancy mr-1"></i>{{ $item->embroidery_text }}</span>
                                        @endif
                                        @if($item->customization_notes)
                                            <br><small class="text-secondary"><i class="fas fa-sticky-note mr-1"></i>{{ Str::limit($item->customization_notes, 50) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
                            </tr>
                            @if($order->discount > 0)
                                <tr>
                                    <td colspan="4" class="text-right text-danger">Descuento:</td>
                                    <td class="text-right text-danger">-${{ number_format($order->discount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="text-right"><strong class="h5">Total:</strong></td>
                                <td class="text-right"><strong class="h5">${{ number_format($order->total, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Pagos --}}
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> Pagos</h5>
                    @if($order->balance > 0 && $order->status !== 'cancelled')
                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalPayment">
                            <i class="fas fa-plus"></i> Registrar Pago
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Metodo</th>
                                <th class="text-right">Monto</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                    <td><i class="fas {{ $payment->method_icon }} mr-1"></i>{{ $payment->method_label }}</td>
                                    <td class="text-right text-success font-weight-bold">${{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->reference ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Sin pagos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="2"><strong>Pagado:</strong></td>
                                <td class="text-right text-success"><strong>${{ number_format($order->amount_paid, 2) }}</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2"><strong>Saldo Pendiente:</strong></td>
                                <td class="text-right {{ $order->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    <strong>${{ number_format($order->balance, 2) }}</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Pedidos Anexos --}}
            @if($order->annexOrders && $order->annexOrders->count() > 0)
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-project-diagram mr-2"></i> Pedidos Anexos ({{ $order->annexOrders->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Numero</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->annexOrders as $annex)
                                    <tr>
                                        <td><strong>{{ $annex->order_number }}</strong></td>
                                        <td>{{ $annex->created_at->format('d/m/Y') }}</td>
                                        <td class="text-right">${{ number_format($annex->total, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $annex->status_color }}">{{ $annex->status_label }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $annex) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            {{-- Estado del pedido --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Estado</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge badge-{{ $order->status_color }} p-2" style="font-size: 1.2rem;">
                            {{ $order->status_label }}
                        </span>
                    </div>
                    <div class="text-center mb-3">
                        <span class="badge badge-{{ $order->payment_status_color }} p-2">
                            Pago: {{ $order->payment_status_label }}
                        </span>
                    </div>

                    {{-- Nivel de urgencia --}}
                    <div class="text-center mb-3">
                        <span class="badge badge-{{ $order->urgency_color }} p-2">
                            <i class="fas fa-clock mr-1"></i> {{ $order->urgency_label }}
                        </span>
                    </div>

                    @if($order->status !== 'cancelled' && $order->status !== 'delivered')
                        <hr>
                        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="form-group">
                                <label>Cambiar Estado:</label>
                                <select name="status" class="form-control form-control-sm">
                                    @if($order->isEditable())
                                        <option value="draft" {{ $order->status == 'draft' ? 'selected' : '' }}>Borrador</option>
                                        <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                                    @endif
                                    <option value="in_production" {{ $order->status == 'in_production' ? 'selected' : '' }}>En Produccion</option>
                                    <option value="ready" {{ $order->status == 'ready' ? 'selected' : '' }}>Listo</option>
                                    <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Entregado</option>
                                </select>
                                @if($order->status === 'confirmed')
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Pasar a produccion deducira materiales del inventario
                                    </small>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-sm">
                                <i class="fas fa-sync mr-1"></i> Actualizar Estado
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Fechas --}}
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar mr-2"></i> Fechas</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Creado:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    @if($order->minimum_date)
                        <p class="mb-2"><strong>Fecha Minima:</strong> {{ $order->minimum_date->format('d/m/Y') }}</p>
                    @endif
                    @if($order->promised_date)
                        <p class="mb-2">
                            <strong>Prometido:</strong> {{ $order->promised_date->format('d/m/Y') }}
                            @if($order->promised_date < now() && !in_array($order->status, ['delivered', 'cancelled']))
                                <span class="badge badge-danger ml-1">VENCIDO</span>
                            @endif
                        </p>
                    @endif
                    @if($order->delivered_date)
                        <p class="mb-0"><strong>Entregado:</strong> {{ $order->delivered_date->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>

            {{-- Notas --}}
            @if($order->notes)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Notas</h5>
                    </div>
                    <div class="card-body">
                        {{ $order->notes }}
                    </div>
                </div>
            @endif

            {{-- Crear Anexo (solo si esta en produccion o posterior) --}}
            @if($order->isInProduction() && !$order->isAnnex())
                <div class="card border-info">
                    <div class="card-body text-center">
                        <p class="mb-2 text-muted small">
                            <i class="fas fa-info-circle mr-1"></i>
                            El pedido no puede editarse. Cree un anexo para agregar mas productos.
                        </p>
                        <a href="{{ route('admin.orders.create-annex', $order) }}" class="btn btn-info btn-block">
                            <i class="fas fa-plus-circle mr-1"></i> Crear Pedido Anexo
                        </a>
                    </div>
                </div>
            @endif

            {{-- Cancelar --}}
            @if(!in_array($order->status, ['cancelled', 'delivered']))
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <form action="{{ route('admin.orders.cancel', $order) }}" method="POST"
                              onsubmit="return confirm('¿Cancelar este pedido? Esta accion no se puede deshacer.')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i> Cancelar Pedido
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Pago --}}
    <div class="modal fade" id="modalPayment" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.payments.store', $order) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Registrar Pago</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Saldo pendiente: <strong>${{ number_format($order->balance, 2) }}</strong>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Monto *</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                       max="{{ $order->balance }}" value="{{ $order->balance }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Metodo de Pago *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">Efectivo</option>
                                <option value="transfer">Transferencia</option>
                                <option value="card">Tarjeta</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Referencia</label>
                            <input type="text" name="reference" class="form-control" maxlength="100"
                                   placeholder="Numero de transferencia, voucher, etc.">
                        </div>
                        <div class="form-group mb-0">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check mr-1"></i> Registrar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Agregar Item Anexo --}}
    @if($canAddItems ?? false)
    <div class="modal fade" id="modalAddAnnexItem" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> Agregar Item Anexo</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="annexItemForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Los items anexos se agregan al pedido existente y se marcan como <strong>ANEXO</strong>.
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Buscar Producto *</label>
                            <select id="annexProductSearch" class="form-control" style="width:100%"></select>
                        </div>

                        <div id="annexProductDetails" style="display:none;">
                            <hr>
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <img id="annexProductImage" src="" class="img-fluid rounded" style="max-height:100px;">
                                </div>
                                <div class="col-md-9">
                                    <h5 id="annexProductName" class="mb-1"></h5>
                                    <p class="text-muted mb-2" id="annexProductSku"></p>

                                    <div class="form-group" id="annexVariantGroup" style="display:none;">
                                        <label>Variante</label>
                                        <select id="annexProductVariant" class="form-control form-control-sm">
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Cantidad *</label>
                                                <input type="number" id="annexProductQty" class="form-control" value="1" min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Precio Unitario *</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                    <input type="number" id="annexProductPrice" class="form-control" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Subtotal</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                    <input type="text" id="annexSubtotal" class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label>Texto a Bordar</label>
                                        <input type="text" id="annexEmbroidery" class="form-control form-control-sm" placeholder="Nombre, iniciales, etc.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-warning" id="btnAddAnnexItem" disabled>
                            <i class="fas fa-plus mr-1"></i> Agregar Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@stop

@if($canAddItems ?? false)
@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let annexSelectedProduct = null;

    // Inicializar Select2 para buscar productos
    $('#annexProductSearch').select2({
        theme: 'bootstrap4',
        placeholder: 'Escriba SKU o nombre del producto...',
        allowClear: true,
        dropdownParent: $('#modalAddAnnexItem'),
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("admin.orders.ajax.search-products") }}',
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
    });

    // Al seleccionar producto
    $('#annexProductSearch').on('select2:select', function(e) {
        const data = e.params.data;
        annexSelectedProduct = data;

        $('#annexProductName').text(data.name);
        $('#annexProductSku').text('SKU: ' + data.sku);
        $('#annexProductPrice').val(data.base_price);
        $('#annexProductQty').val(1);
        $('#annexEmbroidery').val('');

        if (data.image_url) {
            $('#annexProductImage').attr('src', data.image_url).show();
        } else {
            $('#annexProductImage').attr('src', '{{ asset("img/no-image.png") }}').show();
        }

        if (data.variants && data.variants.length > 0) {
            $('#annexProductVariant').empty().append('<option value="">-- Seleccionar --</option>');
            data.variants.forEach(function(v) {
                $('#annexProductVariant').append(
                    '<option value="' + v.id + '" data-price="' + v.final_price + '">' +
                    v.sku_variant + ' - ' + v.attributes_text +
                    '</option>'
                );
            });
            $('#annexVariantGroup').show();
        } else {
            $('#annexVariantGroup').hide();
            $('#annexProductVariant').empty();
        }

        $('#annexProductDetails').show();
        $('#btnAddAnnexItem').prop('disabled', false);
        calculateAnnexSubtotal();
    });

    // Cambio de variante
    $('#annexProductVariant').on('change', function() {
        const price = $(this).find(':selected').data('price');
        if (price) {
            $('#annexProductPrice').val(price);
            calculateAnnexSubtotal();
        }
    });

    // Calcular subtotal
    function calculateAnnexSubtotal() {
        const qty = parseInt($('#annexProductQty').val()) || 0;
        const price = parseFloat($('#annexProductPrice').val()) || 0;
        $('#annexSubtotal').val((qty * price).toFixed(2));
    }

    $('#annexProductQty, #annexProductPrice').on('input', calculateAnnexSubtotal);

    // Agregar item anexo
    $('#btnAddAnnexItem').on('click', function() {
        if (!annexSelectedProduct) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        const itemData = {
            _token: '{{ csrf_token() }}',
            items: [{
                product_id: annexSelectedProduct.id,
                product_variant_id: $('#annexProductVariant').val() || null,
                quantity: parseInt($('#annexProductQty').val()) || 1,
                unit_price: parseFloat($('#annexProductPrice').val()) || 0,
                embroidery_text: $('#annexEmbroidery').val().trim() || null
            }]
        };

        $.ajax({
            url: '{{ route("admin.orders.store-annex-items", $order) }}',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(itemData),
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                let msg = 'Error al agregar item';
                if (xhr.responseJSON?.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                alert(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus mr-1"></i> Agregar Item');
            }
        });
    });

    // Reset al cerrar modal
    $('#modalAddAnnexItem').on('hidden.bs.modal', function() {
        annexSelectedProduct = null;
        $('#annexProductSearch').val(null).trigger('change');
        $('#annexProductDetails').hide();
        $('#btnAddAnnexItem').prop('disabled', true);
    });
});
</script>
@stop
@endif
