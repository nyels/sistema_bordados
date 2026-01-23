@extends('adminlte::page')

@section('title', 'Editar Extra')

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
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> EDITAR EXTRA DE PRODUCTO</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.product_extras.update', $extra->id) }}" id="formExtra">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-12">

                            <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                                <h5 style="color: #ffc107; font-weight: 600;">
                                    <i class="fas fa-edit"></i> Datos del Extra
                                </h5>
                            </div>

                            {{-- Nombre --}}
                            <div class="form-group">
                                <label>Nombre del Extra <span style="color: red;">*</span></label>
                                <input type="text" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    value="{{ old('name', $extra->name) }}" required
                                    placeholder="Ej: Empaque especial, Urgencia, etc.">
                                @error('name')
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
                                                value="{{ old('cost_addition', $extra->cost_addition) }}" required
                                                step="0.01" min="0" placeholder="0.00">
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
                                                value="{{ old('price_addition', $extra->price_addition) }}" required
                                                step="0.01" min="0" placeholder="0.00">
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
                                        value="{{ old('minutes_addition', $extra->minutes_addition) }}" min="0"
                                        max="9999" step="1"
                                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"
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
                                        {{ old('consumes_inventory', $extra->consumes_inventory) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="consumes_inventory">
                                        <strong>Este extra consume materiales</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Active si este servicio requiere materiales físicos (encaje, listón, moños, etc.)
                                </small>
                            </div>

                            {{-- Sección de materiales --}}
                            <div id="materials-section"
                                style="{{ old('consumes_inventory', $extra->consumes_inventory) ? '' : 'display: none;' }}">
                                <div class="p-3 rounded mb-3" style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-info-circle text-info mr-2"></i>
                                        <small style="color: #495057;">
                                            Este servicio descontará inventario al usarse en producción.
                                        </small>
                                    </div>f
                                    {{-- Fila 1: Select de material --}}
                                    <div class="form-group mb-2">
                                        <select id="material-select" class="form-control form-control-sm">
                                            <option value="" data-unit="-" data-family="">-- Seleccione material
                                                --</option>
                                        </select>
                                    </div>

                                    {{-- Fila 2: Cantidad + Unidad + Botón Agregar --}}
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="input-group input-group-sm" style="max-width: 165px;">
                                            <span>Cantidad</span> <input type="number" id="material-quantity"
                                                class="form-control" step="1" min="1" placeholder="0"
                                                value="">
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
                                                {{-- Los materiales se cargan vía JS --}}
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
                                <a href="{{ route('admin.product_extras.index') }}" class="btn btn-secondary mr-2">
                                    <i class="fas fa-times-circle"></i> Regresar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar
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
                // === DATOS DE MATERIALES DISPONIBLES Y EXISTENTES ===
                var materialVariants = @json($materialVariants ?? []);
                var existingMaterials = @json($extraMaterials ?? []);
                var materialIndex = 0;
                var addedMaterials = []; // Track de materiales agregados

                // === FAMILIAS QUE PERMITEN DECIMALES ===
                var decimalFamilies = ['linear'];

                // === INICIALIZAR SELECT DE MATERIALES ===
                function initMaterialSelect() {
                    var $select = $('#material-select');
                    $select.empty();
                    $select.append('<option value="" data-unit="-" data-family="">-- Seleccione material --</option>');

                    materialVariants.forEach(function(v) {
                        // No mostrar materiales ya agregados
                        if (addedMaterials.includes(v.id)) return;

                        // Laravel usa camelCase para relaciones: consumptionUnit (no consumption_unit)
                        var unit = null;
                        if (v.material) {
                            unit = v.material.consumptionUnit || v.material.consumption_unit || null;
                        }
                        var unitSymbol = unit ? unit.symbol : '';
                        var unitFamily = unit ? (unit.measurement_family || '') : '';
                        var materialName = v.material ? v.material.name : 'Material #' + v.id;
                        var colorName = v.color ? ' - ' + v.color : '';
                        var label = materialName + colorName;

                        $select.append('<option value="' + v.id + '" data-unit="' + unitSymbol +
                            '" data-family="' + unitFamily + '" data-name="' + label + '">' + label +
                            '</option>');
                    });
                }

                // === ACTUALIZAR FORMATO DE CANTIDAD SEGÚN UNIDAD ===
                function updateQuantityInput() {
                    var $select = $('#material-select');
                    var $input = $('#material-quantity');
                    var $unit = $('#material-unit');

                    var unit = $select.find('option:selected').data('unit') || '-';
                    var family = $select.find('option:selected').data('family') || '';

                    $unit.text(unit);
                    $input.val('');

                    var allowsDecimals = decimalFamilies.includes(family);
                    if (allowsDecimals) {
                        $input.attr('step', '0.01').attr('min', '0.01').attr('placeholder', '0.00');
                    } else {
                        $input.attr('step', '1').attr('min', '1').attr('placeholder', '0');
                    }
                }

                // === AGREGAR MATERIAL A LA TABLA (usado para cargar existentes y agregar nuevos) ===
                function addMaterialToTable(variantId, quantity, materialName, unit) {
                    var html = '<tr data-index="' + materialIndex + '" data-variant-id="' + variantId + '">' +
                        '<td>' + materialName +
                        '<input type="hidden" name="materials[' + materialIndex + '][variant_id]" value="' + variantId +
                        '">' +
                        '</td>' +
                        '<td class="text-center">' + quantity + ' ' + unit +
                        '<input type="hidden" name="materials[' + materialIndex + '][quantity]" value="' + quantity +
                        '">' +
                        '</td>' +
                        '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-danger btn-remove-material" title="Eliminar">' +
                        '<i class="fas fa-trash-alt"></i></button>' +
                        '</td>' +
                        '</tr>';

                    $('#materials-list').append(html);
                    $('#materials-table-container').show();
                    materialIndex++;
                    addedMaterials.push(parseInt(variantId));
                }

                // === TOGGLE SECCIÓN DE MATERIALES ===
                $('#consumes_inventory').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#materials-section').slideDown(200);
                        initMaterialSelect();
                    } else {
                        $('#materials-section').slideUp(200);
                    }
                });

                // === CARGAR MATERIALES EXISTENTES ===
                if (existingMaterials && existingMaterials.length > 0) {
                    existingMaterials.forEach(function(m) {
                        var variant = materialVariants.find(function(v) {
                            return v.id == m.id;
                        });
                        var materialName = 'Material #' + m.id;
                        var unit = '';

                        if (variant) {
                            materialName = variant.material ? variant.material.name : materialName;
                            if (variant.color) materialName += ' - ' + variant.color;
                            if (variant.material && variant.material.consumption_unit) {
                                unit = variant.material.consumption_unit.symbol || '';
                            }
                        }

                        addMaterialToTable(m.id, m.pivot.quantity_required, materialName, unit);
                    });
                }

                // Inicializar select
                initMaterialSelect();

                // === CAMBIO EN SELECT DE MATERIAL ===
                $('#material-select').on('change', function() {
                    updateQuantityInput();
                });

                // === AGREGAR MATERIAL A LA TABLA ===
                $('#btn-add-material').on('click', function() {
                    var $select = $('#material-select');
                    var $quantity = $('#material-quantity');
                    var variantId = $select.val();
                    var quantity = $quantity.val();
                    var materialName = $select.find('option:selected').data('name');
                    var unit = $select.find('option:selected').data('unit') || '';

                    if (!variantId) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Seleccione un material',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        return;
                    }
                    if (!quantity || parseFloat(quantity) <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Ingrese una cantidad válida',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        return;
                    }

                    addMaterialToTable(variantId, quantity, materialName, unit);

                    // Resetear selector
                    initMaterialSelect();
                    $quantity.val('');
                    $('#material-unit').text('-');
                });

                // === ELIMINAR MATERIAL DE LA TABLA ===
                $(document).on('click', '.btn-remove-material', function() {
                    var $row = $(this).closest('tr');
                    var variantId = parseInt($row.data('variant-id'));

                    // Quitar del track
                    addedMaterials = addedMaterials.filter(function(id) {
                        return id !== variantId;
                    });

                    $row.remove();

                    // Ocultar tabla si está vacía
                    if ($('#materials-list tr').length === 0) {
                        $('#materials-table-container').hide();
                    }

                    // Actualizar select
                    initMaterialSelect();
                });

                // === VALIDACIÓN DEL FORMULARIO ===
                $('#formExtra').on('submit', function(e) {
                    var name = $('input[name="name"]').val().trim();
                    var cost = $('input[name="cost_addition"]').val();
                    var price = $('input[name="price_addition"]').val();
                    var consumesInventory = $('#consumes_inventory').is(':checked');

                    var errors = [];

                    if (!name || name === '') {
                        errors.push('El nombre del extra es obligatorio');
                    }
                    if (!cost || cost === '' || parseFloat(cost) < 0) {
                        errors.push('El costo adicional es obligatorio');
                    }
                    if (!price || price === '' || parseFloat(price) < 0) {
                        errors.push('El precio al cliente es obligatorio');
                    }

                    // Validar materiales si consume inventario
                    if (consumesInventory) {
                        if ($('#materials-list tr').length === 0) {
                            errors.push('Debe agregar al menos un material si el extra consume inventario');
                        }
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Campos requeridos',
                            html: '<ul style="text-align:left;">' + errors.map(function(err) {
                                return '<li>' + err + '</li>';
                            }).join('') + '</ul>',
                            confirmButtonText: 'Entendido'
                        });
                        return false;
                    }

                    return true;
                });
            });
        </script>
    @stop
