@extends('adminlte::page')

@section('title', 'Anexo para ' . $order->order_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-plus-circle mr-2"></i> Nuevo Pedido Anexo</h1>
            <span style="font-size: 14px; color: #495057;">Para el pedido principal: <strong>{{ $order->order_number }}</strong></span>
        </div>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Pedido
        </a>
    </div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
    .product-table th, .product-table td {
        vertical-align: middle;
    }
    .product-image-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
</style>
@stop

@section('content')
    <form action="{{ route('admin.orders.store-annex', $order) }}" method="POST" id="annexForm">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><i class="fas fa-exclamation-triangle mr-1"></i> Errores:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info del pedido padre --}}
        <div class="alert alert-info">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-user mr-1"></i> Cliente:</strong>
                    {{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}
                    <br>
                    <span style="font-size: 14px; color: #495057;"><i class="fas fa-phone mr-1"></i> {{ $order->cliente->telefono }}</span>
                </div>
                <div class="col-md-6 text-md-right">
                    <strong>Pedido Original:</strong> {{ $order->order_number }}<br>
                    <span class="badge badge-{{ $order->status_color }}">{{ $order->status_label }}</span>
                </div>
            </div>
        </div>

        {{-- REGLA R4 (v2): Información de anexos - Solo en CONFIRMED --}}
        <div class="alert alert-info">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle mr-3 mt-1" style="font-size: 24px;"></i>
                <div>
                    <strong style="font-size: 15px;">Información del Anexo (Pedido Confirmado)</strong>
                    <ul class="mb-0 mt-2" style="font-size: 14px;">
                        <li>Este anexo se agregará al pedido <strong>antes de iniciar producción</strong></li>
                        <li>Los productos del anexo se procesarán junto con el pedido principal</li>
                        <li>Una vez iniciada la producción, <strong>NO</strong> se permiten más anexos</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Columna principal --}}
            <div class="col-lg-8">
                {{-- PRODUCTOS --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-box mr-2"></i> Productos del Anexo</h5>
                        <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addProductModal">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover product-table mb-0" id="itemsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">Img</th>
                                        <th>Producto</th>
                                        <th style="width: 100px;">Cant.</th>
                                        <th style="width: 120px;">Precio</th>
                                        <th style="width: 120px;">Subtotal</th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <tr id="noItemsRow">
                                        <td colspan="6" class="text-center py-4" style="color: #495057;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay productos. Haga clic en "Agregar Producto"
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna lateral --}}
            <div class="col-lg-4">
                {{-- RESUMEN --}}
                <div class="card">
                    <div class="card-header" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> Resumen Anexo</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong id="subtotalDisplay">$0.00</strong>
                        </div>
                        <div class="form-group mb-2">
                            <label class="small mb-1">Descuento</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="discount" id="discount" class="form-control"
                                       value="{{ old('discount', 0) }}" min="0" step="0.01">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="h5 mb-0">Total:</span>
                            <strong class="h5 text-success mb-0" id="totalDisplay">$0.00</strong>
                        </div>
                    </div>
                </div>

                {{-- PAGO --}}
                <div class="card">
                    <div class="card-header" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> Pago</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Metodo de Pago</label>
                            <select name="payment_method" id="paymentMethod" class="form-control">
                                <option value="">-- Sin pago inicial --</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transferencia</option>
                                <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>

                        <div class="form-group" id="payFullGroup" style="display: none;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="payFull" name="pay_full" value="1"
                                       {{ old('pay_full') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="payFull">
                                    <i class="fas fa-check-circle mr-1"></i> Pagar Total
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-0" id="anticipoGroup" style="display: none;">
                            <label>Anticipo</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="initial_payment" id="initialPayment" class="form-control"
                                       value="{{ old('initial_payment') }}" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- NOTAS --}}
                <div class="card">
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label><i class="fas fa-sticky-note mr-1"></i> Notas del Anexo</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="2000"
                                      placeholder="Observaciones del anexo...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- BOTON GUARDAR --}}
                <button type="submit" class="btn btn-success btn-lg btn-block" id="submitBtn">
                    <i class="fas fa-save mr-2"></i> Crear Pedido Anexo
                </button>
            </div>
        </div>

        {{-- Hidden inputs para items --}}
        <div id="hiddenItemsContainer"></div>
    </form>

    {{-- ============================================== --}}
    {{-- MODAL: AGREGAR PRODUCTO --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-box mr-2"></i> Agregar Producto</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="productPreviewImage" src="{{ asset('img/no-image.png') }}"
                                 class="img-fluid rounded mb-2" style="max-height: 200px;">
                            <div id="productPreviewName" class="font-weight-bold">-</div>
                            <div id="productPreviewSku" class="text-muted small">-</div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Buscar Producto</label>
                                <select id="modalProductSelect" class="form-control" style="width: 100%;">
                                    <option value="">Escriba para buscar...</option>
                                </select>
                            </div>

                            <div class="form-group" id="variantGroup" style="display: none;">
                                <label class="font-weight-bold">Variante</label>
                                <select id="modalVariantSelect" class="form-control">
                                    <option value="">-- Producto base --</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Cantidad *</label>
                                        <input type="number" id="modalQuantity" class="form-control" value="1" min="1" max="999">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Precio Unitario *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" id="modalPrice" class="form-control" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- REGLA R4 (v2): Info de productos anexos --}}
                            <div class="alert alert-info py-2 mb-0" style="font-size: 13px;">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Nota:</strong>
                                El producto se agregará al pedido y se procesará junto con los demás items en producción.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="addProductBtn" disabled>
                        <i class="fas fa-plus mr-1"></i> Agregar al Anexo
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let itemIndex = 0;
    let orderItems = [];
    let selectedProduct = null;

    // ==========================================
    // SELECT2: PRODUCTO EN MODAL
    // ==========================================
    $('#modalProductSelect').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#addProductModal'),
        placeholder: 'Buscar producto por nombre o SKU...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '{{ route("admin.orders.ajax.search-products") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                // FIX: data viene como {results: [...], pagination: {...}}
                const items = data.results || data;
                return {
                    results: items.map(p => ({
                        id: p.id,
                        text: `${p.name} - $${parseFloat(p.base_price).toFixed(2)}`,
                        product: p
                    })),
                    pagination: data.pagination || { more: false }
                };
            },
            cache: true
        }
    }).on('select2:select', function(e) {
        selectedProduct = e.params.data.product;
        updateProductPreview();
    }).on('select2:clear', function() {
        selectedProduct = null;
        resetProductModal();
    });

    function updateProductPreview() {
        if (!selectedProduct) return;

        $('#productPreviewName').text(selectedProduct.name);
        $('#productPreviewSku').text(selectedProduct.sku || '-');
        $('#productPreviewImage').attr('src', selectedProduct.image_url || '{{ asset("img/no-image.png") }}');
        $('#modalPrice').val(selectedProduct.base_price);

        const $variantSelect = $('#modalVariantSelect');
        $variantSelect.empty().append('<option value="">-- Producto base --</option>');

        if (selectedProduct.variants && selectedProduct.variants.length > 0) {
            selectedProduct.variants.forEach(v => {
                $variantSelect.append(`<option value="${v.id}" data-price="${v.price}" data-sku="${v.sku}">${v.display} ($${parseFloat(v.price).toFixed(2)})</option>`);
            });
            $('#variantGroup').show();
        } else {
            $('#variantGroup').hide();
        }

        $('#addProductBtn').prop('disabled', false);
    }

    function resetProductModal() {
        $('#productPreviewName').text('-');
        $('#productPreviewSku').text('-');
        $('#productPreviewImage').attr('src', '{{ asset("img/no-image.png") }}');
        $('#modalPrice').val('');
        $('#modalQuantity').val(1);
        $('#modalVariantSelect').empty().append('<option value="">-- Producto base --</option>');
        $('#variantGroup').hide();
        $('#addProductBtn').prop('disabled', true);
        selectedProduct = null;
    }

    $('#modalVariantSelect').on('change', function() {
        const $selected = $(this).find('option:selected');
        const variantPrice = $selected.data('price');
        if (variantPrice) {
            $('#modalPrice').val(variantPrice);
        } else if (selectedProduct) {
            $('#modalPrice').val(selectedProduct.base_price);
        }
    });

    // ==========================================
    // AGREGAR PRODUCTO AL ANEXO
    // REGLA R4 (v2): Anexos solo en CONFIRMED
    // ==========================================
    $('#addProductBtn').on('click', function() {
        if (!selectedProduct) return;

        const variantId = $('#modalVariantSelect').val();
        const variantOption = $('#modalVariantSelect option:selected');
        const quantity = parseInt($('#modalQuantity').val()) || 1;
        const price = parseFloat($('#modalPrice').val()) || 0;

        // REGLA R4: Anexos NO tienen personalizaciones
        const item = {
            index: itemIndex,
            product_id: selectedProduct.id,
            product_variant_id: variantId || null,
            product_name: selectedProduct.name,
            variant_display: variantId ? variantOption.text().split(' ($')[0] : null,
            variant_sku: variantId ? variantOption.data('sku') : null,
            image_url: selectedProduct.image_url,
            quantity: quantity,
            unit_price: price,
            embroidery_text: '',           // Siempre vacío en anexos
            customization_notes: ''        // Siempre vacío en anexos
        };

        orderItems.push(item);
        itemIndex++;

        renderItemsTable();
        updateHiddenInputs();
        calculateTotals();

        $('#addProductModal').modal('hide');
        $('#modalProductSelect').val(null).trigger('change');
        resetProductModal();
    });

    // ==========================================
    // RENDERIZAR TABLA DE ITEMS
    // ==========================================
    function renderItemsTable() {
        const $tbody = $('#itemsTableBody');
        $tbody.empty();

        if (orderItems.length === 0) {
            $tbody.html(`
                <tr id="noItemsRow">
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No hay productos. Haga clic en "Agregar Producto"
                    </td>
                </tr>
            `);
            return;
        }

        orderItems.forEach((item, idx) => {
            const subtotal = item.quantity * item.unit_price;
            const variantText = item.variant_display ? `<br><small class="text-muted">${item.variant_display}</small>` : '';

            $tbody.append(`
                <tr data-index="${item.index}">
                    <td>
                        <img src="${item.image_url || '{{ asset("img/no-image.png") }}'}" class="product-image-thumb">
                    </td>
                    <td>
                        <strong>${item.product_name}</strong>
                        ${variantText}
                        <br><span class="badge badge-info" style="font-size: 10px;"><i class="fas fa-plus-circle mr-1"></i>Anexo</span>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm item-qty"
                               value="${item.quantity}" min="1" max="999" data-index="${item.index}">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" class="form-control item-price"
                                   value="${item.unit_price}" step="0.01" min="0" data-index="${item.index}">
                        </div>
                    </td>
                    <td class="font-weight-bold text-success">$${subtotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${item.index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    $(document).on('input', '.item-qty, .item-price', function() {
        const index = $(this).data('index');
        const item = orderItems.find(i => i.index === index);
        if (!item) return;

        if ($(this).hasClass('item-qty')) {
            item.quantity = parseInt($(this).val()) || 1;
        } else {
            item.unit_price = parseFloat($(this).val()) || 0;
        }

        updateHiddenInputs();
        calculateTotals();
        renderItemsTable();
    });

    $(document).on('click', '.remove-item-btn', function() {
        const index = $(this).data('index');
        orderItems = orderItems.filter(i => i.index !== index);
        renderItemsTable();
        updateHiddenInputs();
        calculateTotals();
    });

    // ==========================================
    // HIDDEN INPUTS PARA SUBMIT
    // ==========================================
    function updateHiddenInputs() {
        const $container = $('#hiddenItemsContainer');
        $container.empty();

        orderItems.forEach((item, idx) => {
            $container.append(`
                <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.product_variant_id || ''}">
                <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                <input type="hidden" name="items[${idx}][embroidery_text]" value="${item.embroidery_text || ''}">
                <input type="hidden" name="items[${idx}][customization_notes]" value="${item.customization_notes || ''}">
            `);
        });
    }

    // ==========================================
    // CALCULAR TOTALES
    // ==========================================
    function calculateTotals() {
        let subtotal = 0;
        orderItems.forEach(item => {
            subtotal += item.quantity * item.unit_price;
        });

        const discount = parseFloat($('#discount').val()) || 0;
        const total = Math.max(0, subtotal - discount);

        $('#subtotalDisplay').text('$' + subtotal.toFixed(2));
        $('#totalDisplay').text('$' + total.toFixed(2));
    }

    $('#discount').on('input', calculateTotals);

    // ==========================================
    // LOGICA DE PAGO
    // ==========================================
    $('#paymentMethod').on('change', function() {
        const hasMethod = $(this).val() !== '';
        $('#payFullGroup').toggle(hasMethod);
        $('#anticipoGroup').toggle(hasMethod && !$('#payFull').is(':checked'));
    });

    $('#payFull').on('change', function() {
        $('#anticipoGroup').toggle(!$(this).is(':checked'));
        if ($(this).is(':checked')) {
            $('#initialPayment').val('');
        }
    });

    $('#paymentMethod').trigger('change');

    // Reset modal al abrir
    $('#addProductModal').on('show.bs.modal', function() {
        resetProductModal();
    });

    // Validacion al enviar
    $('#annexForm').on('submit', function(e) {
        if (orderItems.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Anexo vacío',
                text: 'Debe agregar al menos un producto al anexo',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
    });
});
</script>
@stop
