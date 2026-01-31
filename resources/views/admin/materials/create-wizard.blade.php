@extends('adminlte::page')

@section('title', 'Nuevo Material - Wizard')

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

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-plus-circle"></i> NUEVO MATERIAL
            </h3>
        </div>

        <div class="card-body">
            {{-- INDICADOR DE PASOS --}}
            <div class="wizard-steps mb-4">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-label">Datos</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-label">Inventario</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-label">Presentaciones</div>
                    </div>
                </div>
            </div>

            <form id="material-wizard-form" method="POST" action="{{ route('admin.materials.store-wizard') }}">
                @csrf

                {{-- ============================================================ --}}
                {{-- PASO 1: DATOS BÁSICOS --}}
                {{-- ============================================================ --}}
                <div class="wizard-step" id="step-1">
                    <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #007bff; font-weight: 600;">
                            <i class="fas fa-box"></i> Paso 1: Información del Material
                        </h5>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Categoría <span class="text-danger">*</span></label>
                                <select name="material_category_id" id="material_category_id"
                                    class="form-control @error('material_category_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            data-default-unit="{{ $category->default_inventory_unit_id }}"
                                            data-allow-override="{{ $category->allow_unit_override ? '1' : '0' }}"
                                            {{ old('material_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                            @if ($category->defaultInventoryUnit)
                                                ({{ $category->defaultInventoryUnit->symbol }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('material_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Nombre del Material <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="material_name"
                                    class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                    placeholder="Ej: Hilo Poliéster 40/2" maxlength="100" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Composición</label>
                                <input type="text" name="composition"
                                    class="form-control @error('composition') is-invalid @enderror"
                                    value="{{ old('composition') }}" placeholder="Ej: 100% Poliéster" maxlength="100">
                                @error('composition')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Descripción (opcional)</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                    maxlength="500" placeholder="Notas adicionales...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Opciones</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="has_color" name="has_color"
                                        value="1" {{ old('has_color', true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="has_color">
                                        Este material tiene variantes de color
                                    </label>
                                </div>
                                <small class="text-muted">Se crearán variantes separadas por color</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PASO 2: UNIDAD DE INVENTARIO --}}
                {{-- ============================================================ --}}
                <div class="wizard-step" id="step-2" style="display: none;">
                    <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #28a745; font-weight: 600;">
                            <i class="fas fa-warehouse"></i> Paso 2: Unidad de Inventario
                        </h5>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>El sistema controlará las existencias y calculará costos de este material en la unidad que
                            selecciones.</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-10 offset-md-1">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <strong>Categoría:</strong> <span id="display-category-name">-</span>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-0">
                                        <label><strong>Selecciona la unidad de inventario:</strong></label>
                                        <div id="inventory-units-container" class="row">
                                            {{-- Las opciones se cargarán dinámicamente en 2 columnas --}}
                                            <div class="col-12 text-muted">Selecciona una categoría primero...</div>
                                        </div>
                                        <input type="hidden" name="consumption_unit_id" id="consumption_unit_id"
                                            value="{{ old('consumption_unit_id') }}">
                                    </div>

                                    <div id="unit-override-warning" class="alert alert-warning mt-3 mb-0" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Nota:</strong> Esta categoría no permite cambiar la unidad de inventario.
                                        Se usará la unidad por defecto.
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-3">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <strong>TIP:</strong> La mayoría de los hilos se miden en metros, las telas en metros,
                                y los botones en piezas. Solo cambia esto si tu proveedor maneja unidades diferentes.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PASO 3: PRESENTACIONES DE COMPRA (CONVERSIONES) --}}
                {{-- ============================================================ --}}
                <div class="wizard-step" id="step-3" style="display: none;">
                    <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #856404; font-weight: 600;">
                            <i class="fas fa-shopping-cart"></i> Paso 3: Presentaciones de Compra
                        </h5>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Define en qué empaques compras este material y cuántas unidades contiene cada uno.
                        <strong>Esto permite al sistema calcular costos reales.</strong>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Material:</strong> <span id="display-material-name">-</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Unidad de Inventario:</strong>
                            <span id="display-inventory-unit" class="badge badge-success">-</span>
                        </div>
                    </div>

                    <div id="conversions-container">
                        {{-- Las conversiones se agregarán dinámicamente aquí --}}
                    </div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-primary" id="btn-add-conversion">
                            <i class="fas fa-plus"></i> Agregar Presentación de Compra
                        </button>
                    </div>

                    <div class="custom-control custom-checkbox mt-4">
                        <input type="checkbox" class="custom-control-input" id="skip_conversions"
                            name="skip_conversions" value="1">
                        <label class="custom-control-label" for="skip_conversions">
                            <i class="fas fa-forward"></i> Omitir este paso (configurar después)
                        </label>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- BOTONES DE NAVEGACIÓN --}}
                {{-- ============================================================ --}}
                <hr>
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" id="btn-prev" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-next">
                            Siguiente <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn btn-success" id="btn-submit" style="display: none;">
                            <i class="fas fa-save"></i> Guardar Material
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL PARA AGREGAR CONVERSIÓN --}}
    <div class="modal fade" id="addConversionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Agregar Presentación de Compra
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- SECCIÓN SUPERIOR: SELECCIÓN DE UNIDAD --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Presentación de Compra <span class="text-danger">*</span></label>
                        <select id="modal-purchase-unit" class="form-control form-control-lg">
                            <option value="">Seleccionar presentación...</option>
                        </select>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> El sistema detectará automáticamente
                            el tipo de configuración requerida.</small>
                    </div>

                    {{-- SISTEMA DE PESTAÑAS (Visualización controlada por JS) --}}
                    <ul class="nav nav-tabs nav-justified mb-3" id="wizardModeTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link disabled" id="wizard-direct-tab" data-toggle="tab" href="#wizard-direct"
                                role="tab">
                                <i class="fas fa-ruler-horizontal"></i> Cantidad Directa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link disabled" id="wizard-content-tab" data-toggle="tab"
                                href="#wizard-content" role="tab">
                                <i class="fas fa-boxes"></i> Por Contenido
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content border p-4 rounded bg-white shadow-sm" id="wizardTabContent"
                        style="min-height: 250px;">

                        {{-- ESTADO INICIAL / VACÍO --}}
                        <div id="wizard-empty-state" class="text-center py-5">
                            <i class="fas fa-arrow-up text-muted fa-3x mb-3"></i>
                            <p class="text-muted">Seleccione una presentación arriba para configurar.</p>
                        </div>

                        {{-- TAB 1: CANTIDAD DIRECTA (CONO/ROLLO) --}}
                        <div class="tab-pane fade" id="wizard-direct" role="tabpanel">
                            <div class="alert alert-info border-0 bg-light text-primary">
                                <i class="fas fa-info-circle"></i> Configuración para unidades individuales (Conos, Rollos,
                                Botes).
                            </div>

                            <div class="form-group text-center">
                                <label class="h5">¿Cuántos <span
                                        class="badge badge-primary inventory-unit-name">m</span> tiene esta unidad?</label>
                                <div class="input-group input-group-lg mt-3" style="max-width: 300px; margin: 0 auto;">
                                    <input type="number" id="modal-conversion-factor"
                                        class="form-control text-center font-weight-bold" placeholder="Ej: 5000"
                                        min="0.0001" step="0.0001">
                                    <div class="input-group-append">
                                        <span class="input-group-text inventory-unit-name font-weight-bold">m</span>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Ingrese el contenido neto total.</small>
                            </div>
                        </div>

                        {{-- TAB 2: POR CONTENIDO (CAJA/PAQUETE) --}}
                        <div class="tab-pane fade" id="wizard-content" role="tabpanel">
                            <div class="alert alert-info border-0 bg-light text-primary mb-3">
                                <i class="fas fa-calculator"></i> Configuración para contenedores (Cajas, Paquetes).
                            </div>

                            {{-- CAMPO 1: ETIQUETA (OBLIGATORIO) --}}
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre de Etiqueta <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="wizard-label-input" class="form-control"
                                    placeholder="Ej: Caja24, Paq12, Bulto50">
                                <small class="text-muted">Identificador único para inventario.</small>
                            </div>

                            <div class="row">
                                {{-- CAMPO 2: CANTIDAD INTERNA --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-primary">¿Cuántas piezas trae ESTA
                                            presentación?</label>
                                        <div class="input-group">
                                            <input type="number" id="wizard-inter-qty"
                                                class="form-control font-weight-bold" placeholder="Ej: 10, 24, 50"
                                                min="1">
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text bg-light text-dark font-weight-bold">pz</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">Si es una caja con 10 conos, pon 10 aquí.</small>
                                    </div>
                                </div>

                                {{-- CAMPO 3: CONTENIDO POR UNIDAD --}}
                                <div class="col-md-6" id="group-inter-value">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-primary">¿Cuánto mide/pesa CADA pieza?</label>
                                        <div class="input-group">
                                            <input type="number" id="wizard-inter-value"
                                                class="form-control font-weight-bold" placeholder="Ej: 1000, 5000"
                                                min="0.0001" step="0.0001">
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text bg-light text-dark inventory-unit-name">m</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">Si cada cono tiene 5000m, pon 5000 aquí.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 text-right">
                                <h5 class="mb-0 text-success">
                                    Total: <strong id="wizard-inter-total">0</strong> <span
                                        class="inventory-unit-name">m</span>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div id="modal-conversion-preview" class="alert alert-success mt-3" style="display: none;">
                        <i class="fas fa-check-circle"></i>
                        <strong>Conversión:</strong> <span id="preview-text"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-conversion">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Estilos del Wizard */
        .wizard-steps {
            padding: 20px 0;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .step-item.active .step-circle,
        .step-item.completed .step-circle {
            background: #007bff;
            color: #fff;
        }

        .step-item.completed .step-circle {
            background: #28a745;
        }

        .step-label {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .step-item.active .step-label {
            color: #007bff;
            font-weight: 600;
        }

        .step-line {
            width: 80px;
            height: 3px;
            background: #e0e0e0;
            margin: 0 10px;
            margin-bottom: 20px;
        }

        /* Tarjetas de conversión */
        .conversion-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversion-card .conversion-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .conversion-card .conversion-badge {
            font-size: 1rem;
        }

        /* Radio buttons estilizados - Compactos */
        .unit-option {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .unit-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .unit-option.selected {
            border-color: #28a745;
            background: #d4edda;
        }

        .unit-option input[type="radio"] {
            display: none;
        }

        .unit-option .unit-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .unit-option .unit-symbol {
            color: #666;
            font-size: 0.85rem;
        }

        .unit-option .recommended-badge {
            background: #28a745;
            color: #fff;
            font-size: 0.65rem;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 4px;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // =====================================================
            // ESTADO DEL WIZARD
            // =====================================================
            // Si hay errores de validación, asumimos que el usuario ya intentó enviar,
            // por lo tanto lo regresamos al último paso (Paso 3) para facilitar la corrección.
            var currentStep = {{ $errors->any() ? 3 : 1 }};
            var totalSteps = 3;
            var selectedCategoryId = null;
            var selectedInventoryUnitId = null;
            var selectedInventoryUnitName = '';
            var selectedInventoryUnitSymbol = '';
            var availablePurchaseUnits = [];
            var conversions = [];

            // Datos de categorías con sus unidades
            var categoriesData = @json($categories->keyBy('id'));
            var inventoryUnits = @json($inventoryUnits);

            // =====================================================
            // NAVEGACIÓN DEL WIZARD
            // =====================================================
            function updateWizardUI() {
                // Mostrar/ocultar pasos
                $('.wizard-step').hide();
                $('#step-' + currentStep).show();

                // Actualizar indicadores
                $('.step-item').removeClass('active completed');
                for (var i = 1; i <= totalSteps; i++) {
                    var $stepItem = $('.step-item[data-step="' + i + '"]');
                    if (i < currentStep) {
                        $stepItem.addClass('completed');
                    } else if (i === currentStep) {
                        $stepItem.addClass('active');
                    }
                }

                // Botones de navegación
                $('#btn-prev').toggle(currentStep > 1);
                $('#btn-next').toggle(currentStep < totalSteps);
                $('#btn-submit').toggle(currentStep === totalSteps);
            }

            function validateStep(step) {
                if (step === 1) {
                    var categoryId = $('#material_category_id').val();
                    var name = $('#material_name').val().trim();

                    if (!categoryId) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Debe seleccionar una categoría',
                            confirmButtonColor: '#3085d6'
                        });
                        return false;
                    }
                    if (!name) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Debe ingresar el nombre del material',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#material_name').focus();
                        return false;
                    }

                    // Guardar datos para paso 2
                    selectedCategoryId = categoryId;
                    var category = categoriesData[categoryId];
                    $('#display-category-name').text(category.name);
                    $('#display-material-name').text(name);

                    // Cargar unidades de inventario
                    loadInventoryUnits(category);

                    return true;
                }

                if (step === 2) {
                    if (!selectedInventoryUnitId) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Debe seleccionar una unidad de inventario',
                            confirmButtonColor: '#3085d6'
                        });
                        return false;
                    }

                    // Actualizar display
                    $('#display-inventory-unit').text(selectedInventoryUnitName + ' (' +
                        selectedInventoryUnitSymbol + ')');
                    $('.inventory-unit-name').text(
                        selectedInventoryUnitSymbol); // Update all elements with this class

                    // Cargar unidades de compra disponibles
                    loadPurchaseUnits();

                    return true;
                }

                return true;
            }

            $('#btn-next').on('click', function() {
                if (validateStep(currentStep)) {
                    currentStep++;
                    updateWizardUI();
                }
            });

            $('#btn-prev').on('click', function() {
                currentStep--;
                updateWizardUI();
            });

            // =====================================================
            // PASO 1: SELECCIÓN DE CATEGORÍA
            // =====================================================
            $('#material_category_id').on('change', function() {
                selectedCategoryId = $(this).val();
            });

            // =====================================================
            // PASO 2: UNIDADES DE INVENTARIO
            // =====================================================
            function loadInventoryUnits(category) {
                var $container = $('#inventory-units-container');
                $container.empty();

                var defaultUnitId = category.default_inventory_unit_id;
                var allowOverride = category.allow_unit_override;

                // Si no permite override y tiene default, mostrar solo esa
                var unitsToShow = inventoryUnits.slice(); // Copia del array
                if (!allowOverride && defaultUnitId) {
                    unitsToShow = unitsToShow.filter(u => u.id == defaultUnitId);
                    $('#unit-override-warning').show();
                } else {
                    $('#unit-override-warning').hide();
                }

                // Ordenar: recomendada primero
                if (defaultUnitId) {
                    unitsToShow.sort(function(a, b) {
                        if (a.id == defaultUnitId) return -1;
                        if (b.id == defaultUnitId) return 1;
                        return 0;
                    });
                }

                unitsToShow.forEach(function(unit) {
                    var isDefault = (unit.id == defaultUnitId);
                    var isSelected = (selectedInventoryUnitId == unit.id) || (isDefault &&
                        !selectedInventoryUnitId);

                    if (isSelected) {
                        selectedInventoryUnitId = unit.id;
                        selectedInventoryUnitName = unit.name;
                        selectedInventoryUnitSymbol = unit.symbol;
                        $('#consumption_unit_id').val(unit.id);
                    }

                    var $col = $('<div class="col-lg-3 col-md-4 col-6 mb-2"></div>');
                    var $option = $(`
                        <label class="unit-option ${isSelected ? 'selected' : ''}">
                            <input type="radio" name="inventory_unit_radio" value="${unit.id}"
                                ${isSelected ? 'checked' : ''}>
                            <span class="unit-name">${unit.name}</span>
                            <span class="unit-symbol">(${unit.symbol})</span>
                            ${isDefault ? '<span class="recommended-badge">Recomendada</span>' : ''}
                        </label>
                    `);

                    $option.on('click', function() {
                        $('.unit-option').removeClass('selected');
                        $(this).addClass('selected');
                        selectedInventoryUnitId = unit.id;
                        selectedInventoryUnitName = unit.name;
                        selectedInventoryUnitSymbol = unit.symbol;
                        $('#consumption_unit_id').val(unit.id);
                    });

                    $col.append($option);
                    $container.append($col);
                });
            }

            // =====================================================
            // PASO 3: CONVERSIONES
            // =====================================================
            function loadPurchaseUnits() {
                // Cargar unidades de compra de la categoría via AJAX
                $.ajax({
                    url: '/admin/material-categories/' + selectedCategoryId + '/get-units',
                    type: 'GET',
                    success: function(units) {
                        availablePurchaseUnits = units.filter(function(u) {
                            // Excluir la unidad de inventario seleccionada
                            return u.id != selectedInventoryUnitId;
                        });

                        // Actualizar el select del modal
                        var $select = $('#modal-purchase-unit');
                        $select.empty().append('<option value="">Seleccionar presentación...</option>');

                        availablePurchaseUnits.forEach(function(unit) {
                            // Excluir unidades ya agregadas
                            var alreadyAdded = conversions.some(c => c.from_unit_id == unit
                                .id);
                            if (!alreadyAdded) {
                                $select.append(
                                    '<option value="' + unit.id + '" data-name="' +
                                    unit.name + '" data-symbol="' + unit.symbol + '">' +
                                    unit.name + ' (' + unit.symbol + ')' +
                                    '</option>'
                                );
                            }
                        });
                    },
                    error: function() {
                        console.error('Error al cargar unidades de compra');
                    }
                });
            }

            function renderConversions() {
                var $container = $('#conversions-container');
                $container.empty();

                if (conversions.length === 0) {
                    $container.html(`
                        <div class="alert alert-light text-center">
                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">No hay presentaciones configuradas.</p>
                            <small>Agrega las presentaciones en las que compras este material.</small>
                        </div>
                    `);
                    return;
                }

                conversions.forEach(function(conv, index) {
                    $container.append(`
                        <div class="conversion-card">
                            <div class="conversion-info">
                                <span class="badge badge-primary conversion-badge">
                                    <i class="fas fa-box"></i> ${conv.from_unit_name}
                                </span>
                                <span class="text-muted">=</span>
                                <span class="badge badge-success conversion-badge">
                                    ${conv.conversion_factor} ${selectedInventoryUnitSymbol}
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-conversion"
                                data-index="${index}" title="Quitar">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" name="conversions[${index}][from_unit_id]" value="${conv.from_unit_id}">
                            <input type="hidden" name="conversions[${index}][conversion_factor]" value="${conv.conversion_factor}">
                        </div>
                    `);
                });
            }

            // Botón agregar conversión
            $('#btn-add-conversion').on('click', function() {
                // Refrescar opciones disponibles
                loadPurchaseUnits();
                $('#modal-conversion-factor').val('');
                $('#modal-conversion-preview').hide();
                $('#addConversionModal').modal('show');

                // Reset tabs to default (Manual)
                $('#wizard-direct-tab').tab('show').removeClass('disabled').parent().show();
                $('#wizard-content-tab').removeClass('disabled').parent().show();
                setWizardMode('direct');
                $('#wizard-inter-unit-group').show(); // Ensure it's visible by default
            });

            // MODO DEL MODAL DEL WIZARD
            window.wizardMode = 'direct';
            window.setWizardMode = function(mode) {
                window.wizardMode = mode;
                if (mode === 'content') {
                    updateWizardInterUnits();
                }
            };

            function updateWizardInterUnits() {
                var $select = $('#wizard-inter-unit');
                $select.empty().append('<option value="">Seleccionar contenido...</option>');

                // Opción base: La propia unidad de inventario
                $select.append(
                    `<option value="base" data-factor="1" data-symbol="${selectedInventoryUnitSymbol}">${selectedInventoryUnitName} (Base)</option>`
                );

                // Otras presentaciones ya agregadas
                conversions.forEach(function(c) {
                    $select.append(
                        `<option value="${c.from_unit_id}" data-factor="${c.conversion_factor}" data-symbol="${c.from_unit_symbol}">${c.from_unit_name}</option>`
                    );
                });
            }

            // =====================================================
            // LÓGICA V2.1: CONTROL ESTRICTO DE FLUJO (MODAL)
            // =====================================================

            // 1. CONTROL DE SELECCIÓN DE UNIDAD
            $('#modal-purchase-unit').on('change', function() {
                var $selected = $(this).find(':selected');
                var name = $selected.data('name') || '';
                var slug = name.toLowerCase();
                var unitId = $(this).val();

                $('.selected-purchase-unit-name').text(name || 'unidad');

                // Estado Vacío vs Activo
                if (unitId) {
                    $('#wizard-empty-state').hide();
                } else {
                    $('#wizard-empty-state').show();
                    $('.nav-tabs .nav-link').removeClass('active');
                    $('.tab-pane').removeClass('show active');
                    return;
                }

                // Reset de todos los inputs
                $('#modal-conversion-factor').val('');
                $('#wizard-label-input').val('');
                $('#wizard-inter-qty').val('');
                $('#wizard-inter-value').val('');
                $('#wizard-inter-total').text('0');

                // DETECCIÓN DE UNIDAD DE PIEZA (LOGICA INTELIGENTE)
                // Si la unidad de inventario es 'pieza', 'unidad', 'pz', o similar,
                // Omitimos la pregunta estúpida.
                var isPieceInventory = false;
                var invName = selectedInventoryUnitName.toLowerCase();
                if (invName.includes('pieza') || invName.includes('unidad') || invName.includes('pz')) {
                    isPieceInventory = true;
                }

                if (isPieceInventory) {
                    $('#group-inter-value').hide();
                    $('#wizard-inter-value').val(1); // Valor por defecto 1
                } else {
                    $('#group-inter-value').show();
                }

                // Detección Inteligente de Tipo (Presentación de Compra)
                if (slug.includes('caja') || slug.includes('paquete') || slug.includes('pack') || slug
                    .includes('bulto')) {
                    // --- MODO CONTENIDO (Caja/Paquete) ---
                    // Activar Tab Contenido
                    $('#wizard-content-tab').tab('show').removeClass('disabled');
                    $('#wizard-direct-tab').removeClass('active').addClass('disabled');

                    $('#wizard-content').addClass('show active');
                    $('#wizard-direct').removeClass('show active');

                    // Focus UX
                    setTimeout(() => $('#wizard-label-input').focus(), 100);

                } else {
                    // --- MODO DIRECTO (Cono/Rollo/Individual) ---
                    // Activar Tab Directa
                    $('#wizard-direct-tab').tab('show').removeClass('disabled');
                    $('#wizard-content-tab').removeClass('active').addClass('disabled');

                    $('#wizard-direct').addClass('show active');
                    $('#wizard-content').removeClass('show active');

                    // Focus UX
                    setTimeout(() => $('#modal-conversion-factor').focus(), 100);
                }
            });

            function calculateWizardTotal() {
                var qty = parseFloat($('#wizard-inter-qty').val()) || 0;
                var val = parseFloat($('#wizard-inter-value').val()) || 0;
                var total = qty * val;

                // Actualizar el total visual en el modal
                $('#wizard-inter-total').text(total.toLocaleString('es-MX'));
            }

            $('#wizard-inter-qty, #wizard-inter-value').on('input', calculateWizardTotal);

            // 2. CÁLCULO EN TIEMPO REAL (SOLO MODO CONTENIDO)
            $('#wizard-inter-qty, #wizard-inter-value').on('input', function() {
                var qty = parseFloat($('#wizard-inter-qty').val()) || 0;
                var val = parseFloat($('#wizard-inter-value').val()) || 0;
                var total = qty * val;

                $('#wizard-inter-total').text(total.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 4
                }));
            });

            // 3. CONFIRMACIÓN Y VALIDACIÓN ESTRICTA
            $('#btn-confirm-conversion').on('click', function() {
                var unitId = $('#modal-purchase-unit').val();
                var $selected = $('#modal-purchase-unit').find(':selected');
                var unitName = $selected.data('name');
                var unitSymbol = $selected.data('symbol');

                if (!unitId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo requerido',
                        text: 'Debe seleccionar una presentación de compra',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Determinar modo activo
                var isContentMode = $('#wizard-content').hasClass('active');

                var factor, label = null,
                    interQty = null,
                    interUnitId = null;

                if (isContentMode) {
                    // --- VALIDACIÓN MODO CONTENIDO ---
                    label = $('#wizard-label-input').val().trim();
                    var qty = parseFloat($('#wizard-inter-qty').val());
                    var val = parseFloat($('#wizard-inter-value').val());

                    if (!label) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Etiqueta requerida',
                            text: 'Debe asignar una Etiqueta (ej. Caja24) para identificar este contenedor en el inventario',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#wizard-label-input').focus();
                        return;
                    }
                    if (!qty || qty <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Ingrese la cantidad de unidades internas',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#wizard-inter-qty').focus();
                        return;
                    }
                    if (!val || val <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Ingrese el contenido de cada unidad interna',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#wizard-inter-value').focus();
                        return;
                    }

                    factor = qty * val; // Factor Total Calculado
                    interQty = qty;
                    // En V2.1, asumimos que Cajas contienen la unidad base del inventario.
                    // Pasamos el ID real de la unidad de inventario para la FK.
                    interUnitId = selectedInventoryUnitId;

                } else {
                    // --- VALIDACIÓN MODO DIRECTO ---
                    factor = parseFloat($('#modal-conversion-factor').val());
                    if (!factor || factor <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo requerido',
                            text: 'Ingrese el contenido total de la unidad',
                            confirmButtonColor: '#3085d6'
                        });
                        $('#modal-conversion-factor').focus();
                        return;
                    }
                }

                // Agregar objeto de conversión
                conversions.push({
                    from_unit_id: unitId,
                    from_unit_name: unitName,
                    from_unit_symbol: unitSymbol,
                    conversion_factor: factor,
                    intermediate_unit_id: interUnitId,
                    intermediate_qty: interQty,
                    label: label
                });

                renderConversions();
                $('#addConversionModal').modal('hide');

                // Reset select para forzar evento change limpio la próxima vez
                $('#modal-purchase-unit').val('').trigger('change');
            });

            // Eliminar conversión
            $(document).on('click', '.btn-remove-conversion', function() {
                var index = $(this).data('index');
                conversions.splice(index, 1);
                renderConversions();
                loadPurchaseUnits();
            });

            // =====================================================
            // SUBMIT DEL FORMULARIO
            // =====================================================
            $('#material-wizard-form').on('submit', function(e) {
                // Validación final
                if (!selectedInventoryUnitId) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo requerido',
                        text: 'Debe seleccionar una unidad de inventario',
                        confirmButtonColor: '#3085d6'
                    });
                    currentStep = 2;
                    updateWizardUI();
                    return false;
                }

                // Agregar conversiones al formulario
                var $form = $(this);

                // Eliminar inputs de conversiones anteriores
                $form.find('input[name^="conversions"]').remove();

                // Si no se omitieron, agregar las conversiones
                if (!$('#skip_conversions').is(':checked')) {
                    conversions.forEach(function(conv, index) {
                        $form.append(
                            '<input type="hidden" name="conversions[' + index +
                            '][from_unit_id]" value="' + conv.from_unit_id + '">'
                        );
                        $form.append(
                            '<input type="hidden" name="conversions[' + index +
                            '][conversion_factor]" value="' + conv.conversion_factor + '">'
                        );
                        if (conv.intermediate_unit_id) {
                            $form.append(
                                '<input type="hidden" name="conversions[' + index +
                                '][intermediate_unit_id]" value="' + conv.intermediate_unit_id +
                                '">'
                            );
                            $form.append(
                                '<input type="hidden" name="conversions[' + index +
                                '][intermediate_qty]" value="' + conv.intermediate_qty + '">'
                            );
                        }
                        if (conv.label) {
                            $form.append(
                                '<input type="hidden" name="conversions[' + index +
                                '][label]" value="' + conv.label + '">'
                            );
                        }
                    });
                }
            });

            // Inicializar
            updateWizardUI();
            renderConversions();

            // Si hay old values, restaurar
            @if (old('material_category_id'))
                selectedCategoryId = "{{ old('material_category_id') }}";
                $('#material_category_id').trigger('change');
            @endif

            // Restaurar unidad de inventario
            @if (old('consumption_unit_id'))
                selectedInventoryUnitId = "{{ old('consumption_unit_id') }}";
                // Esperar a que cargue la categoría para seleccionar la unidad
                var checkExist = setInterval(function() {
                    if ($('#inventory-units-container input[value="' + selectedInventoryUnitId + '"]')
                        .length) {
                        $('#inventory-units-container input[value="' + selectedInventoryUnitId + '"]')
                            .click();
                        clearInterval(checkExist);
                    }
                }, 100);
            @endif

            // Restaurar conversiones (Presentaciones)
            @if (old('conversions'))
                var oldConversions = @json(old('conversions'));
                // Necesitamos los nombres de las unidades, que no vienen en el old input simple
                // Así que haremos una carga asíncrona rápida o inferiremos

                // Nota: El array old('conversions') viene indexado, ej: [0 => {from_unit_id: '1', ...}]
                // Lo convertimos a array JS limpio
                Object.values(oldConversions).forEach(function(c) {
                    // Buscar el nombre de la unidad en la lista de unidades disponibles (cuando carguen)
                    // Como es asíncrono, agregamos un placeholder y lo actualizaremos
                    conversions.push({
                        from_unit_id: c.from_unit_id,
                        from_unit_name: 'Cargando...', // Se actualizará al cargar unidades
                        from_unit_symbol: '...',
                        conversion_factor: parseFloat(c.conversion_factor)
                    });
                });
                renderConversions();

                // Forzar carga de unidades de compra para corregir los nombres
                var updateNamesInt = setInterval(function() {
                    if (availablePurchaseUnits.length > 0) {
                        conversions.forEach(function(conv) {
                            var u = availablePurchaseUnits.find(u => u.id == conv.from_unit_id);
                            if (u) {
                                conv.from_unit_name = u.name;
                                conv.from_unit_symbol = u.symbol;
                            }
                        });
                        renderConversions();
                        clearInterval(updateNamesInt);
                    }
                }, 500);
            @endif
        });
    </script>
@stop
