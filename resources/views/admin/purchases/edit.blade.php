@extends('adminlte::page')

@section('title', 'Editar OC: ' . $purchase->purchase_number)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchases.update', $purchase->id) }}" id="purchaseForm">
        @csrf
        @method('PUT')

        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-edit"></i> EDITAR ORDEN DE COMPRA: {{ $purchase->purchase_number }}
                </h3>
            </div>

            <div class="card-body">
                {{-- DATOS GENERALES --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Proveedor <span class="text-danger">*</span></label>
                            <select name="proveedor_id" id="proveedor_id"
                                class="form-control @error('proveedor_id') is-invalid @enderror" required>
                                <option value="">Seleccionar proveedor...</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}"
                                        {{ old('proveedor_id', $purchase->proveedor_id) == $proveedor->id ? 'selected' : '' }}>
                                        {{ $proveedor->nombre_proveedor }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proveedor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Fecha Orden</label>
                            <input type="date" name="ordered_at"
                                class="form-control @error('ordered_at') is-invalid @enderror"
                                value="{{ old('ordered_at', $purchase->ordered_at?->format('Y-m-d')) }}">
                            @error('ordered_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Fecha Esperada</label>
                            <input type="date" name="expected_at"
                                class="form-control @error('expected_at') is-invalid @enderror"
                                value="{{ old('expected_at', $purchase->expected_at?->format('Y-m-d')) }}">
                            @error('expected_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>IVA (%)</label>
                            <input type="number" name="tax_rate" id="tax_rate"
                                class="form-control @error('tax_rate') is-invalid @enderror"
                                value="{{ old('tax_rate', $purchase->tax_rate) }}" min="0" max="100"
                                step="0.01">
                            @error('tax_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Referencia</label>
                            <input type="text" name="reference"
                                class="form-control @error('reference') is-invalid @enderror"
                                value="{{ old('reference', $purchase->reference) }}" maxlength="100">
                            @error('reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="1000">{{ old('notes', $purchase->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Descuento ($)</label>
                            <input type="number" name="discount_amount" id="discount_amount"
                                class="form-control @error('discount_amount') is-invalid @enderror"
                                value="{{ old('discount_amount', $purchase->discount_amount) }}" min="0"
                                step="0.01">
                            @error('discount_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                {{-- AGREGAR ITEMS --}}
                <div class="card bg-light">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus"></i> Agregar Material</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select id="select_category" class="form-control form-control-sm">
                                        <option value="">Seleccionar...</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                data-base-unit="{{ $category->baseUnit->symbol ?? '' }}">
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Material</label>
                                    <select id="select_material" class="form-control form-control-sm" disabled>
                                        <option value="">Primero seleccione categoría</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Variante (SKU)</label>
                                    <select id="select_variant" class="form-control form-control-sm" disabled>
                                        <option value="">Primero seleccione material</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Unidad de Compra</label>
                                    <select id="select_unit" class="form-control form-control-sm" disabled>
                                        <option value="">Primero seleccione variante</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" id="input_quantity" class="form-control form-control-sm"
                                        min="0.0001" step="0.01" placeholder="0.00" disabled>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" id="input_price" class="form-control" min="0.0001"
                                            step="0.01" placeholder="0.00" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Conversión</label>
                                    <input type="text" id="info_conversion"
                                        class="form-control form-control-sm bg-light" readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" id="info_subtotal"
                                        class="form-control form-control-sm bg-light font-weight-bold" readonly>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-group mb-0 w-100">
                                    <button type="button" id="btn_add_item" class="btn btn-success btn-block" disabled>
                                        <i class="fas fa-plus"></i> Agregar Item
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                {{-- TABLA DE ITEMS --}}
                <h5 class="mb-3"><i class="fas fa-list"></i> Items de la Orden</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="items_table">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Material</th>
                                <th>SKU / Color</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-right">P. Unitario</th>
                                <th class="text-right">Subtotal</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items_body">
                        </tbody>
                        <tfoot class="bg-light" id="items_totals">
                            <tr>
                                <td colspan="6" class="text-right"><strong>Subtotal:</strong></td>
                                <td class="text-right" id="total_subtotal">$0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-right"><strong>IVA (<span
                                            id="tax_rate_display">16</span>%):</strong></td>
                                <td class="text-right" id="total_tax">$0.00</td>
                                <td></td>
                            </tr>
                            <tr id="discount_row" style="display: none;">
                                <td colspan="6" class="text-right"><strong>Descuento:</strong></td>
                                <td class="text-right text-danger" id="total_discount">-$0.00</td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="6" class="text-right"><strong style="font-size: 16px;">TOTAL:</strong>
                                </td>
                                <td class="text-right"><strong style="font-size: 16px;" id="total_final">$0.00</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- CONTAINER DE INPUTS HIDDEN --}}
                <div id="hidden_items_container"></div>
            </div>

            <div class="card-footer text-center">
                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-warning" id="btn_submit">
                    <i class="fas fa-save"></i> Actualizar Orden
                </button>
            </div>
        </div>
    </form>
@stop

@section('css')
    <style>
        .item-row:hover {
            background-color: #f8f9fa;
        }

        #items_table tbody tr td {
            vertical-align: middle;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // Items existentes (cargados desde el servidor)
            let items = @json($itemsForJs);

            let itemIndex = items.length;

            const csrfToken = '{{ csrf_token() }}';

            // Cache selectores
            const $selectCategory = $('#select_category');
            const $selectMaterial = $('#select_material');
            const $selectVariant = $('#select_variant');
            const $selectUnit = $('#select_unit');
            const $inputQuantity = $('#input_quantity');
            const $inputPrice = $('#input_price');
            const $infoConversion = $('#info_conversion');
            const $infoSubtotal = $('#info_subtotal');
            const $btnAddItem = $('#btn_add_item');
            const $btnSubmit = $('#btn_submit');
            const $itemsBody = $('#items_body');
            const $itemsTotals = $('#items_totals');
            const $hiddenContainer = $('#hidden_items_container');
            const $taxRate = $('#tax_rate');
            const $discountAmount = $('#discount_amount');

            // Estado temporal del item
            let currentItem = {
                category_id: null,
                category_name: '',
                material_id: null,
                material_name: '',
                variant_id: null,
                variant_sku: '',
                variant_color: '',
                unit_id: null,
                unit_name: '',
                unit_symbol: '',
                conversion_factor: 1,
                base_unit_symbol: '',
                quantity: 0,
                unit_price: 0
            };

            // Agregar index a items existentes
            items = items.map((item, idx) => ({
                ...item,
                index: idx
            }));

            // Renderizar items existentes
            renderItems();
            updateTotals();

            // ========== MISMA LÓGICA QUE CREATE ==========
            // (Copiar toda la lógica de eventos de create.blade.php aquí)
            // Por brevedad, se omite pero debe incluirse completa

            // Cambio de categoría
            $selectCategory.on('change', function() {
                const categoryId = $(this).val();
                currentItem.category_id = categoryId;
                currentItem.category_name = $(this).find('option:selected').text();
                currentItem.base_unit_symbol = $(this).find('option:selected').data('base-unit') || '';

                resetFromMaterial();

                if (!categoryId) {
                    $selectMaterial.prop('disabled', true).html(
                        '<option value="">Primero seleccione categoría</option>');
                    return;
                }

                $selectMaterial.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/materials/${categoryId}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            $selectMaterial.html('<option value="">No hay materiales</option>');
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin materiales',
                                text: 'No hay materiales registrados en esta categoría',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar material...</option>';
                        data.forEach(function(material) {
                            const composition = material.composition ?
                                ` (${material.composition})` : '';
                            options +=
                                `<option value="${material.id}">${material.name}${composition}</option>`;
                        });
                        $selectMaterial.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectMaterial.html('<option value="">Error al cargar</option>');
                        console.error('Error loading materials:', xhr);
                        let msg = 'No se pudieron cargar los materiales.';
                        if (xhr.status === 404) msg =
                            'Ruta no encontrada (404). Contacte al administrador.';
                        if (xhr.status === 500) msg = 'Error interno del servidor (500).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar materiales',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });

            // Cambio de material
            $selectMaterial.on('change', function() {
                const materialId = $(this).val();
                currentItem.material_id = materialId;
                currentItem.material_name = $(this).find('option:selected').text();

                resetFromVariant();

                if (!materialId) {
                    $selectVariant.prop('disabled', true).html(
                        '<option value="">Primero seleccione material</option>');
                    return;
                }

                $selectVariant.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/variants/${materialId}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            $selectVariant.html('<option value="">No hay variantes</option>');
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin variantes',
                                text: 'Este material no tiene variantes (SKU/colores) registradas',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar variante...</option>';
                        data.forEach(function(variant) {
                            const color = variant.color ? ` - ${variant.color}` : '';
                            const stock = variant.current_stock ?
                                ` (Stock: ${parseFloat(variant.current_stock).toFixed(2)})` :
                                '';
                            options +=
                                `<option value="${variant.id}" data-sku="${variant.sku}" data-color="${variant.color || ''}">${variant.sku}${color}${stock}</option>`;
                        });
                        $selectVariant.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectVariant.html('<option value="">Error al cargar</option>');
                        console.error('Error loading variants:', xhr);
                        let msg = 'No se pudieron cargar las variantes.';
                        if (xhr.status === 404) msg = 'Ruta no encontrada (404).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar variantes',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });

            // Cambio de variante
            $selectVariant.on('change', function() {
                const variantId = $(this).val();
                const $selected = $(this).find('option:selected');
                currentItem.variant_id = variantId;
                currentItem.variant_sku = $selected.data('sku') || '';
                currentItem.variant_color = $selected.data('color') || '';

                resetFromUnit();

                if (!variantId || !currentItem.material_id) {
                    $selectUnit.prop('disabled', true).html(
                        '<option value="">Primero seleccione variante</option>');
                    return;
                }

                $selectUnit.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/units/${currentItem.material_id}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (!data.units || data.units.length === 0) {
                            $selectUnit.html('<option value="">No hay unidades</option>');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin unidades de compra',
                                text: 'Este material no tiene unidades de compra configuradas. Configure las conversiones primero.',
                                confirmButtonColor: '#f0ad4e'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar unidad...</option>';
                        data.units.forEach(function(unit) {
                            const isBase = unit.is_base ? ' (Base)' : '';
                            options +=
                                `<option value="${unit.id}" data-factor="${unit.conversion_factor}" data-symbol="${unit.symbol}" data-name="${unit.name}">${unit.name} (${unit.symbol})${isBase}</option>`;
                        });
                        $selectUnit.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectUnit.html('<option value="">Error al cargar</option>');
                        console.error('Error loading units:', xhr);
                        let msg = 'No se pudieron cargar las unidades de compra.';
                        if (xhr.status === 404) msg = 'Ruta no encontrada (404).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar unidades',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });

            $selectUnit.on('change', function() {
                const unitId = $(this).val();
                const $selected = $(this).find('option:selected');

                currentItem.unit_id = unitId;
                currentItem.unit_symbol = $selected.data('symbol') || '';
                currentItem.conversion_factor = parseFloat($selected.data('factor')) || 1;

                if (unitId) {
                    $inputQuantity.prop('disabled', false);
                    $inputPrice.prop('disabled', false);
                } else {
                    $inputQuantity.prop('disabled', true).val('');
                    $inputPrice.prop('disabled', true).val('');
                }
                validateAddButton();
            });

            $inputQuantity.on('input', function() {
                currentItem.quantity = parseFloat($(this).val()) || 0;
                calculateItemSubtotal();
                validateAddButton();
            });

            $inputPrice.on('input', function() {
                currentItem.unit_price = parseFloat($(this).val()) || 0;
                calculateItemSubtotal();
                validateAddButton();
            });

            function calculateItemSubtotal() {
                const subtotal = currentItem.quantity * currentItem.unit_price;
                $infoSubtotal.val(subtotal > 0 ? '$' + subtotal.toFixed(2) : '');

                if (currentItem.conversion_factor != 1 && currentItem.quantity > 0) {
                    const converted = currentItem.quantity * currentItem.conversion_factor;
                    $infoConversion.val(`= ${converted.toFixed(2)} ${currentItem.base_unit_symbol}`);
                } else {
                    $infoConversion.val('');
                }
            }

            function validateAddButton() {
                const canAdd = currentItem.variant_id &&
                    currentItem.unit_id &&
                    currentItem.quantity > 0 &&
                    currentItem.unit_price > 0;
                $btnAddItem.prop('disabled', !canAdd);
            }

            $btnAddItem.on('click', function() {
                const exists = items.find(i => i.variant_id == currentItem.variant_id && i.unit_id ==
                    currentItem.unit_id);
                if (exists) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Item duplicado',
                        text: 'Este material con esta unidad ya está agregado a la orden',
                        confirmButtonColor: '#f0ad4e'
                    });
                    return;
                }

                const subtotal = currentItem.quantity * currentItem.unit_price;
                const converted_quantity = currentItem.quantity * currentItem.conversion_factor;
                // Calculate unit cost for display (avoid division by zero if quantity is somehow 0, though checked above)
                const converted_unit_cost = converted_quantity > 0 ? (subtotal / converted_quantity) : 0;

                items.push({
                    index: itemIndex++,
                    category_name: currentItem.category_name,
                    material_name: currentItem.material_name,
                    variant_id: currentItem.variant_id,
                    variant_sku: currentItem.variant_sku,
                    variant_color: currentItem.variant_color,
                    unit_id: currentItem.unit_id,
                    unit_symbol: currentItem.unit_symbol,
                    quantity: currentItem.quantity,
                    unit_price: currentItem.unit_price,
                    conversion_factor: currentItem.conversion_factor,
                    converted_quantity: converted_quantity,
                    converted_unit_cost: converted_unit_cost,
                    base_unit_symbol: currentItem.base_unit_symbol,
                    subtotal: subtotal
                });

                renderItems();
                resetForm();
                updateTotals();
            });

            function renderItems() {
                if (items.length === 0) {
                    $itemsBody.html(`
                        <tr id="no_items_row">
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No hay items agregados.
                            </td>
                        </tr>
                    `);
                    $itemsTotals.hide();
                    $btnSubmit.prop('disabled', true);
                    return;
                }

                $itemsTotals.show();
                let html = '';
                items.forEach((item, idx) => {
                    const colorBadge = item.variant_color ?
                        `<span class="badge badge-secondary">${item.variant_color}</span>` : '';

                    const conversionInfo = item.conversion_factor != 1 ?
                        `<br><small class="text-info">= ${item.converted_quantity.toFixed(2)} ${item.base_unit_symbol}</small>` :
                        '';

                    const unitCostInfo = item.conversion_factor != 1 ?
                        `<br><small class="text-muted">$${(item.converted_unit_cost || 0).toFixed(2)}/${item.base_unit_symbol}</small>` :
                        '';

                    html += `
                        <tr class="item-row" data-index="${item.index}">
                            <td>${idx + 1}</td>
                            <td>
                                <strong>${item.material_name}</strong>
                                <br><small class="text-muted">${item.category_name}</small>
                            </td>
                            <td>
                                <code>${item.variant_sku}</code>
                                ${colorBadge ? '<br>' + colorBadge : ''}
                            </td>
                            <td class="text-center">
                                ${item.quantity.toFixed(2)}
                                ${conversionInfo}
                            </td>
                            <td class="text-center">${item.unit_symbol}</td>
                            <td class="text-right">
                                $${item.unit_price.toFixed(2)}
                                ${unitCostInfo}
                            </td>
                            <td class="text-right font-weight-bold">$${item.subtotal.toFixed(2)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm btn-remove-item" data-index="${item.index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $itemsBody.html(html);
                $btnSubmit.prop('disabled', false);
                generateHiddenInputs();
            }

            function generateHiddenInputs() {
                let html = '';
                items.forEach((item, idx) => {
                    html += `
                        <input type="hidden" name="items[${idx}][material_variant_id]" value="${item.variant_id}">
                        <input type="hidden" name="items[${idx}][unit_id]" value="${item.unit_id}">
                        <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                    `;
                });
                $hiddenContainer.html(html);
            }

            $(document).on('click', '.btn-remove-item', function() {
                items = items.filter(i => i.index !== $(this).data('index'));
                renderItems();
                updateTotals();
            });

            function updateTotals() {
                const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
                const taxRate = parseFloat($taxRate.val()) || 0;
                const discount = parseFloat($discountAmount.val()) || 0;
                const tax = subtotal * (taxRate / 100);
                const total = subtotal + tax - discount;

                $('#total_subtotal').text('$' + subtotal.toFixed(2));
                $('#tax_rate_display').text(taxRate.toFixed(0));
                $('#total_tax').text('$' + tax.toFixed(2));

                if (discount > 0) {
                    $('#discount_row').show();
                    $('#total_discount').text('-$' + discount.toFixed(2));
                } else {
                    $('#discount_row').hide();
                }

                $('#total_final').text('$' + Math.max(0, total).toFixed(2));
            }

            $taxRate.on('input', updateTotals);
            $discountAmount.on('input', updateTotals);

            function resetFromMaterial() {
                $selectMaterial.val('').prop('disabled', true);
                resetFromVariant();
            }

            function resetFromVariant() {
                $selectVariant.val('').prop('disabled', true).html(
                    '<option value="">Primero seleccione material</option>');
                resetFromUnit();
            }

            function resetFromUnit() {
                $selectUnit.val('').prop('disabled', true).html(
                    '<option value="">Primero seleccione variante</option>');
                $inputQuantity.val('').prop('disabled', true);
                $inputPrice.val('').prop('disabled', true);
                $infoConversion.val('');
                $infoSubtotal.val('');
                $btnAddItem.prop('disabled', true);
            }

            function resetForm() {
                currentItem = {
                    category_id: null,
                    category_name: '',
                    material_id: null,
                    material_name: '',
                    variant_id: null,
                    variant_sku: '',
                    variant_color: '',
                    unit_id: null,
                    unit_name: '',
                    unit_symbol: '',
                    conversion_factor: 1,
                    base_unit_symbol: '',
                    quantity: 0,
                    unit_price: 0
                };
                $selectCategory.val('');
                resetFromMaterial();
            }

            $('#purchaseForm').on('submit', function(e) {
                if (items.length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un item');
                    return false;
                }
                return true;
            });
        });
    </script>
@stop
