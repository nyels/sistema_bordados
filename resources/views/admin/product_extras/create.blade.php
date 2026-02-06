@extends('adminlte::page')

@section('title', 'Nuevo Extra')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    {{-- ERRORES GENERALES --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores en el formulario:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO EXTRA DE PRODUCTO</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.product_extras.store') }}" id="formExtra">
                    @csrf
                    @method('POST')

                    <div class="row">
                        <div class="col-12">

                            <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                                <h5 style="color: #007bff; font-weight: 600;">
                                    <i class="fas fa-plus-circle"></i> Datos del Extra
                                </h5>
                            </div>

                            {{-- Nombre --}}
                            <div class="form-group">
                                <label>Nombre del Extra <span style="color: red;">*</span></label>
                                <input type="text" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required placeholder="Ej: Empaque especial, Urgencia, etc.">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Categoría --}}
                            <div class="form-group">
                                <label>Categoría</label>
                                <select name="extra_category_id" class="form-control form-control-sm @error('extra_category_id') is-invalid @enderror">
                                    <option value="">-- Sin categoría --</option>
                                    @foreach ($categories ?? [] as $cat)
                                        <option value="{{ $cat->id }}" {{ old('extra_category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('extra_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Costo y Precio --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Costo Adicional <span style="color: red;">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="cost_addition"
                                                class="form-control form-control-sm @error('cost_addition') is-invalid @enderror"
                                                value="{{ old('cost_addition') }}" required step="0.01" min="0"
                                                placeholder="Ej: 25.00">
                                            @error('cost_addition')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">Costo real del servicio/extra</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Precio al Cliente <span style="color: red;">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="price_addition"
                                                class="form-control form-control-sm @error('price_addition') is-invalid @enderror"
                                                value="{{ old('price_addition') }}" required step="0.01" min="0"
                                                placeholder="Ej: 50.00">
                                            @error('price_addition')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">Precio que se cobra al cliente</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Tiempo adicional --}}
                            <div class="form-group">
                                <label>Tiempo Adicional (minutos)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="minutes_addition"
                                        class="form-control form-control-sm @error('minutes_addition') is-invalid @enderror"
                                        value="{{ old('minutes_addition', '0') }}" min="0" max="9999"
                                        step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">minutos</span>
                                    </div>
                                    @error('minutes_addition')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Tiempo extra que agrega este servicio al proceso</small>
                            </div>

                            {{-- ================================================================ --}}
                            {{-- SECCIÓN DE INVENTARIO (EXTENSIÓN CONTROLADA) --}}
                            {{-- ================================================================ --}}
                            <div
                                style="border-bottom: 3px solid #6c757d; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px;">
                                <h5 style="color: #6c757d; font-weight: 600;">
                                    <i class="fas fa-boxes"></i> Control de Inventario
                                </h5>
                            </div>

                            {{-- Checkbox: Consume inventario --}}
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="consumes_inventory"
                                        name="consumes_inventory" value="1"
                                        {{ old('consumes_inventory') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="consumes_inventory">
                                        <strong>Este extra consume materiales</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Active si este servicio requiere materiales físicos (encaje, listón, moños, etc.)
                                </small>
                            </div>

                            {{-- Sección de materiales (oculta por defecto) --}}
                            <div id="materials-section" style="display: none;">
                                <div class="p-3 rounded mb-3" style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-info-circle text-info mr-2"></i>
                                        <small style="color: #495057;">
                                            Este servicio descontará inventario al usarse en producción.
                                        </small>
                                    </div>

                                    {{-- Fila 1: Select de material --}}
                                    <div class="form-group mb-2">
                                        <select id="material-select" class="form-control form-control-sm">
                                            <option value="" data-unit="-" data-family="">-- Seleccione material
                                                --</option>
                                        </select>
                                    </div>

                                    {{-- Fila 2: Cantidad + Unidad + Botón Agregar --}}
                                    <div class="d-flex align-items-center mb-3">
                                        <label class="mr-2 mb-0" style="font-weight: 500;">Cantidad:</label>
                                        <div class="input-group input-group-sm" style="max-width: 140px;">
                                            <input type="number" id="material-quantity" class="form-control"
                                                step="1" min="1" placeholder="0" value="">
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="material-unit"
                                                    style="min-width: 45px;">-</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary ml-2" id="btn-add-material">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>

                                    {{-- Tabla de materiales agregados --}}
                                    <div id="materials-table-container" style="display: none;">
                                        <table class="table table-sm table-bordered mb-0" id="materials-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Material</th>
                                                    <th style="width: 100px;" class="text-center">Cantidad</th>
                                                    <th style="width: 50px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="materials-list">
                                                {{-- Los materiales se agregan dinámicamente --}}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                @error('materials')
                                    <div class="alert alert-danger py-2" style="font-size: 13px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Botones --}}
                            <div class="d-flex justify-content-end align-items-center mt-4">
                                <a href="{{ route('admin.product_extras.index') }}" class="btn btn-secondary mr-2" id="btnRegresar">
                                    <i class="fas fa-times-circle"></i> Regresar
                                </a>
                                <button type="button" class="btn btn-primary" id="btnGuardar">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @stop

    @section('css')
    @stop

    @section('js')
        <script>
            $(document).ready(function() {
                // ============================================================
                // DATOS
                // ============================================================
                var materialVariants = @json($materialVariants ?? []);
                var materialIndex = 0;
                var addedMaterials = [];
                var decimalFamilies = ['linear'];

                // ============================================================
                // FUNCIONES DE MATERIALES
                // ============================================================
                function initMaterialSelect() {
                    var $select = $('#material-select');
                    $select.empty().append('<option value="" data-unit="-" data-family="">-- Seleccione material --</option>');

                    materialVariants.forEach(function(v) {
                        if (addedMaterials.includes(v.id)) return;

                        var unit = v.material ? (v.material.consumptionUnit || v.material.consumption_unit ||
                                   v.material.baseUnit || v.material.base_unit || null) : null;
                        var unitSymbol = unit ? unit.symbol : '';
                        var unitFamily = unit ? (unit.measurement_family || '') : '';
                        var materialName = v.material ? v.material.name : 'Material #' + v.id;
                        var label = materialName + (v.color ? ' - ' + v.color : '');

                        $select.append('<option value="' + v.id + '" data-unit="' + unitSymbol +
                            '" data-family="' + unitFamily + '" data-name="' + label + '">' + label + '</option>');
                    });
                }

                function updateQuantityInput() {
                    var $selected = $('#material-select option:selected');
                    var unit = $selected.data('unit') || '-';
                    var family = $selected.data('family') || '';

                    $('#material-unit').text(unit);
                    $('#material-quantity').val('');

                    if (decimalFamilies.includes(family)) {
                        $('#material-quantity').attr({ step: '0.01', min: '0.01', placeholder: '0.00' });
                    } else {
                        $('#material-quantity').attr({ step: '1', min: '1', placeholder: '0' });
                    }
                }

                // ============================================================
                // EVENTOS DE MATERIALES
                // ============================================================
                $('#consumes_inventory').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#materials-section').slideDown(200);
                        initMaterialSelect();
                    } else {
                        $('#materials-section').slideUp(200);
                    }
                });

                $('#material-select').on('change', updateQuantityInput);

                $('#btn-add-material').on('click', function() {
                    var $selected = $('#material-select option:selected');
                    var variantId = $('#material-select').val();
                    var quantity = $('#material-quantity').val();

                    if (!variantId) {
                        Swal.fire({ icon: 'warning', title: 'Seleccione un material', timer: 1500, showConfirmButton: false });
                        return;
                    }
                    if (!quantity || parseFloat(quantity) <= 0) {
                        Swal.fire({ icon: 'warning', title: 'Ingrese una cantidad válida', timer: 1500, showConfirmButton: false });
                        return;
                    }

                    var html = '<tr data-variant-id="' + variantId + '">' +
                        '<td>' + $selected.data('name') +
                        '<input type="hidden" name="materials[' + materialIndex + '][variant_id]" value="' + variantId + '"></td>' +
                        '<td class="text-center">' + quantity + ' ' + ($selected.data('unit') || '') +
                        '<input type="hidden" name="materials[' + materialIndex + '][quantity]" value="' + quantity + '"></td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-material">' +
                        '<i class="fas fa-trash-alt"></i></button></td></tr>';

                    $('#materials-list').append(html);
                    $('#materials-table-container').show();
                    materialIndex++;
                    addedMaterials.push(parseInt(variantId));
                    initMaterialSelect();
                    $('#material-quantity').val('');
                    $('#material-unit').text('-');
                });

                $(document).on('click', '.btn-remove-material', function() {
                    var $row = $(this).closest('tr');
                    var variantId = parseInt($row.data('variant-id'));
                    addedMaterials = addedMaterials.filter(function(id) { return id !== variantId; });
                    $row.remove();
                    if ($('#materials-list tr').length === 0) $('#materials-table-container').hide();
                    initMaterialSelect();
                });

                // ============================================================
                // VALIDACIÓN Y SUBMIT - LÓGICA SIMPLE Y DIRECTA
                // ============================================================
                $('#btnGuardar').on('click', function(e) {
                    e.preventDefault();

                    var $btn = $(this);
                    var $btnRegresar = $('#btnRegresar');

                    // Si ya está procesando, ignorar
                    if ($btn.prop('disabled')) return;

                    // Validar
                    var errors = [];
                    var name = $('input[name="name"]').val().trim();
                    var cost = $('input[name="cost_addition"]').val();
                    var price = $('input[name="price_addition"]').val();
                    var consumesInventory = $('#consumes_inventory').is(':checked');

                    if (!name) errors.push('El nombre del extra es obligatorio');
                    if (!cost || parseFloat(cost) < 0) errors.push('El costo adicional es obligatorio');
                    if (!price || parseFloat(price) < 0) errors.push('El precio al cliente es obligatorio');
                    if (consumesInventory && $('#materials-list tr').length === 0) {
                        errors.push('Debe agregar al menos un material si el extra consume inventario');
                    }

                    // Si hay errores, mostrar y NO HACER NADA MÁS
                    if (errors.length > 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Campos requeridos',
                            html: '<ul style="text-align:left;">' + errors.map(function(e) {
                                return '<li>' + e + '</li>';
                            }).join('') + '</ul>',
                            confirmButtonText: 'Entendido'
                        });
                        return; // SALIR - no deshabilitar nada
                    }

                    // Sin errores - AHORA SÍ deshabilitar y enviar
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                    $btnRegresar.addClass('disabled').css('pointer-events', 'none');

                    // Enviar formulario
                    $('#formExtra').submit();
                });

                // ============================================================
                // INICIALIZACIÓN
                // ============================================================
                if ($('#consumes_inventory').is(':checked')) {
                    $('#materials-section').show();
                    initMaterialSelect();
                }
            });
        </script>
    @stop
