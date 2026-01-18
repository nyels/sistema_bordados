@extends('adminlte::page')

@section('title', 'Nueva Conversión - ' . $material->name)

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

    {{-- BREADCRUMB INFO --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.materials.index') }}">
                            <i class="fas fa-boxes"></i> Materiales
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.material-conversions.index', $material->id) }}">
                            {{ $material->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Nueva Conversión</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-plus-circle"></i> NUEVA CONVERSIÓN DE UNIDAD
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.material-conversions.index', $material->id) }}" id="btn-back"
                    class="btn btn-secondary font-weight-bold" title="Regresar">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
                <button type="submit" form="conversion-form" id="btn-save-conversion"
                    class="btn btn-outline-light font-weight-bold ml-2" title="Guardar Conversión">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>

        <div class="card-body">
            <form id="conversion-form" method="POST"
                action="{{ route('admin.material-conversions.store', $material->id) }}">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div class="row">
                    {{-- COLUMNA izquierda --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-calculator"></i> Vista Previa
                            </h5>
                        </div>

                        {{-- INFO DEL MATERIAL --}}
                        <div class="card bg-light mb-3">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="fas fa-box text-primary"></i> Material</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td style="width: 140px;"><strong>Nombre:</strong></td>
                                        <td>{{ $material->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Categoría:</strong></td>
                                        <td>{{ $material->category->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Unidad Base (Sistema):</strong></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <i class="fas fa-dolly mr-1"></i>
                                                {{ $material->baseUnit->name ?? 'N/A' }}
                                                ({{ $material->baseUnit->symbol ?? '' }})
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        @if ($consumptionUnit->id != $material->baseUnit->id)
                                    <tr>
                                        <td><strong>Unidad de Consumo:</strong></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <i class="fas fa-ruler mr-1"></i>
                                                {{ $consumptionUnit->name ?? 'N/A' }}
                                                ({{ $consumptionUnit->symbol ?? '' }})
                                            </span>
                                        </td>
                                    </tr>
                                    @endif
                                    @if ($material->conversion_factor && $material->conversion_factor != 1)
                                        <tr>
                                            <td><strong>Factor Básico:</strong></td>
                                            <td>
                                                1 {{ $material->baseUnit->symbol ?? '?' }} =
                                                <strong>{{ number_format($material->conversion_factor, 2) }}</strong>
                                                {{ $consumptionUnit->symbol ?? '?' }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>


                        {{-- INFO ADICIONAL --}}
                        <div class="card bg-light mt-3">
                            <div class="card-body py-2">
                                <small>
                                    <i class="fas fa-info-circle text-info"></i>
                                    <strong>Información:</strong> Aquí defines cómo compras el material (ej. Caja) y cómo se
                                    traduce a tu inventario (ej. Metros).
                                </small>
                            </div>
                        </div>

                        {{-- EJEMPLOS --}}
                        <div class="alert alert-secondary mt-3">
                            <strong><i class="fas fa-lightbulb"></i> Ejemplos comunes:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Origen:</strong> 1 Rollo <i class="fas fa-arrow-right"></i>
                                    <strong>Destino:</strong> 50 metros
                                </li>
                                <li><strong>Origen:</strong> 1 Caja <i class="fas fa-arrow-right"></i>
                                    <strong>Destino:</strong> 12 conos
                                </li>
                                <li><strong>Origen:</strong> 1 Pack <i class="fas fa-arrow-right"></i>
                                    <strong>Destino:</strong> 100 piezas
                                </li>
                            </ul>
                        </div>
                    </div>
                    {{-- COLUMNA derecha --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-exchange-alt"></i> Configurar Conversión
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Unidad de Compra / Presentación (Origen) <span class="text-danger">*</span></label>
                            <select name="from_unit_id" id="from_unit_id"
                                class="form-control @error('from_unit_id') is-invalid @enderror" required>
                                <option value="">Seleccionar unidad...</option>
                                @foreach ($purchaseUnits as $unit)
                                    <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}"
                                        data-factor="{{ $unit->default_conversion_factor }}"
                                        data-type="{{ $unit->unit_type?->value }}"
                                        {{ old('from_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->symbol }})
                                        @if ($unit->isMetricPack() && $unit->default_conversion_factor)
                                            = {{ number_format($unit->default_conversion_factor, 0) }}
                                            {{ $consumptionUnit->symbol }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('from_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Solo se muestran unidades de tipo <strong>Logístico/Compra</strong> que sean compatibles con
                                <strong>{{ $consumptionUnit->name ?? 'N/A' }}</strong> o universales.
                                <br>
                                <i class="fas fa-question-circle"></i> ¿No ves tu unidad?
                                <a href="{{ route('admin.units.index') }}" target="_blank">Revisa su configuración</a>
                                y asegúrate que no esté ligada a otra medida (ej. Piezas).
                            </small>
                        </div>

                        {{-- CAMPO DE ETIQUETA OPCIONAL --}}
                        {{-- CAMPO DE ETIQUETA OPCIONAL --}}
                        <div class="form-group" id="label_container" style="display: none;">
                            <label>Nombre de Presentación (Opcional)</label>
                            <input type="text" name="label" id="label_input" class="form-control"
                                placeholder="Ej: Caja (x12), Caja Master" value="{{ old('label') }}">
                            <small class="form-text text-muted">Recomendado para distinguir múltiples presentaciones (ej.
                                Caja 12 vs Caja 50).</small>
                        </div>

                        <div class="form-group">
                            <label>Unidad de Consumo (Destino)</label>
                            <input type="hidden" name="to_unit_id" value="{{ $consumptionUnit->id }}">
                            <div class="form-control bg-light" style="cursor: not-allowed;">
                                <span class="badge badge-success">
                                    <i class="fas fa-ruler mr-1"></i>
                                    {{ $consumptionUnit->name ?? 'N/A' }}
                                    ({{ $consumptionUnit->symbol ?? '' }})
                                </span>
                                <i class="fas fa-lock text-muted float-right mt-1"></i>
                            </div>
                            <small class="form-text text-muted">
                                Unidad de consumo del material. No editable.
                            </small>
                        </div>

                        {{-- TABS DE MODO DE DEFINICIÓN --}}
                        {{-- TABS DE MODO (V2.1 STRICT MODE) --}}
                        <ul class="nav nav-tabs nav-justified mb-3" id="conversionModeTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link disabled" id="direct-tab" data-toggle="pill" href="#pills-direct"
                                    role="tab" aria-selected="false">
                                    <i class="fas fa-ruler-horizontal"></i> Manual (Directa)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="content-tab" data-toggle="pill" href="#pills-content"
                                    role="tab" aria-selected="false">
                                    <i class="fas fa-boxes"></i> Por Contenido
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            {{-- MODO DIRECTO --}}
                            <div class="tab-pane fade show active" id="pills-direct" role="tabpanel">
                                <div class="form-group">
                                    <label>Factor de Conversión <span class="text-danger">*</span></label>
                                    <input type="number" name="conversion_factor" id="conversion_factor"
                                        class="form-control @error('conversion_factor') is-invalid @enderror"
                                        value="{{ old('conversion_factor') }}" placeholder="Ej: 50" min="0.0001"
                                        max="999999999" step="0.0001" required>
                                    @error('conversion_factor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        ¿Cuántros <strong>{{ $consumptionUnit->symbol ?? 'unidades' }}</strong> hay en
                                        1 unidad de compra?
                                    </small>

                                    {{-- ALERTA DE SEGURIDAD PARA CAJAS EN MODO DIRECTO --}}
                                    <div id="direct_pack_warning" class="alert alert-warning mt-3"
                                        style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>¡Cuidado!</strong>
                                        Parece que eligió una Caja/Paquete.
                                        <br>
                                        En este modo (Directo) debe ingresar el <strong>contenido TOTAL</strong> de la caja
                                        (ej. 120,000).
                                        <br>
                                        <small class="text-dark">
                                            Si prefiere sumar por conos (ej. 24 x 5000), use la pestaña <strong>"Por
                                                Contenido"</strong> (si está habilitada) o cree primero la unidad "Cono".
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- MODO CONTENIDO (WIZARD) --}}
                            <div class="tab-pane fade" id="pills-content" role="tabpanel">
                                @if ($existingConversions->count() > 0)
                                    <div class="card shadow-none border-0 m-0">
                                        <div class="card-body p-0">
                                            {{-- Campos ocultos para capturar el desglose --}}
                                            <input type="hidden" name="intermediate_unit_id"
                                                id="hidden_intermediate_unit_id">
                                            <input type="hidden" name="intermediate_qty" id="hidden_intermediate_qty">

                                            <div class="form-group mb-2">
                                                <label class="text-primary mb-1"><i class="fas fa-search"></i> 1. ¿Qué
                                                    contiene esta unidad?</label>
                                                <select id="intermediate_unit_id" class="form-control">
                                                    <option value="" data-factor="0">Seleccionar contenido...
                                                    </option>
                                                    {{-- Opción Base siempre disponible --}}
                                                    <option value="{{ $consumptionUnit->id }}" data-factor="1"
                                                        data-symbol="{{ $consumptionUnit->symbol }}" data-is-base="true">
                                                        {{ $consumptionUnit->name }} (Base)
                                                    </option>
                                                    @foreach ($existingConversions as $ec)
                                                        <option value="{{ $ec->fromUnit->id }}"
                                                            data-factor="{{ $ec->conversion_factor }}"
                                                            data-symbol="{{ $ec->fromUnit->symbol }}">
                                                            {{ $ec->fromUnit->name }} (1 =
                                                            {{ number_format($ec->conversion_factor, 2) }}
                                                            {{ $consumptionUnit->symbol }})
                                                            @if ($ec->label)
                                                                - {{ $ec->label }}
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-2">
                                                        <label class="text-primary mb-1"><i
                                                                class="fas fa-sort-amount-up"></i> 2. ¿Cuántas piezas
                                                            trae?</label>
                                                        <div class="input-group">
                                                            <input type="number" id="intermediate_qty"
                                                                class="form-control" placeholder="Ej: 24" min="1">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text bg-light font-weight-bold"
                                                                    id="selected_inter_symbol">pz</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-2">
                                                        <label class="text-primary mb-1"><i class="fas fa-ruler"></i> 3.
                                                            Valor de cada pz</label>
                                                        <div class="input-group">
                                                            <input type="number" id="intermediate_value_each"
                                                                class="form-control" placeholder="Ej: 5000"
                                                                min="0.0001" step="0.0001">
                                                            <div class="input-group-append">
                                                                <span
                                                                    class="input-group-text bg-light font-weight-bold">{{ $consumptionUnit->symbol }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 p-2 bg-light rounded text-center">
                                                <h5 class="mb-0 text-success">
                                                    Total: <strong id="content-mode-total">0</strong>
                                                    <span
                                                        class="inventory-unit-name">{{ $consumptionUnit->symbol }}</span>
                                                </h5>
                                                <small class="text-muted">Factor de Conversión Calculado</small>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Primero debe registrar una unidad
                                        básica (como Cono o Pieza) para usar esta calculadora.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- PREVIEW DE CONVERSIÓN --}}
                        <div class="card border-primary" id="previewCard" style="display: none;">
                            <div class="card-header bg-primary text-white py-2">
                                <strong><i class="fas fa-eye"></i> Resultado de Conversión</strong>
                            </div>
                            <div class="card-body text-center py-4">
                                <h4 class="mb-0">
                                    <span class="badge badge-secondary" id="previewFrom">1 ?</span>
                                    <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                    <span class="badge badge-success" id="previewTo">? unidades</span>
                                </h4>
                                <hr>
                                <p class="mb-0 text-muted">
                                    <small id="previewText">-</small>
                                </p>
                            </div>
                        </div>
                    </div>


                </div>


            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            // Datos de la unidad de consumo (destino) desde el servidor
            var toUnitName = '{{ $consumptionUnit->name ?? 'N/A' }}';
            var toUnitSymbol = '{{ $consumptionUnit->symbol ?? '' }}';
            var hasConversions = {{ $existingConversions->count() > 0 ? 'true' : 'false' }};

            // Función GLOBAL para cambiar tabs (necesaria para onclick en HTML)
            window.setMode = function(mode) {
                if (mode === 'direct') {
                    $('#intermediate_qty').val('');
                    $('#intermediate_unit_id').val('');
                }
            };

            // INICIALIZACIÓN: Deshabilitar tab de contenido por defecto
            $('#content-tab').addClass('disabled').removeAttr('data-toggle');
            $('#direct-tab').tab('show');
            setMode('direct');

            function updatePreview() {
                var fromUnit = $('#from_unit_id option:selected');
                var factor = parseFloat($('#conversion_factor').val()) || 0;

                if (fromUnit.val() && factor > 0) {
                    var fromSymbol = fromUnit.data('symbol') || fromUnit.text();
                    var fromName = fromUnit.text().split('(')[0].trim();

                    $('#previewFrom').text('1 ' + fromSymbol);
                    $('#previewTo').text(factor.toFixed(2) + ' ' + toUnitSymbol);
                    $('#previewText').text('Al comprar 1 ' + fromName +
                        ', se registrarán ' + factor.toFixed(2) + ' ' + toUnitSymbol + ' en inventario');
                    $('#previewCard').show();
                } else {
                    $('#previewCard').hide();
                }
            }

            // Al cambiar la unidad de origen (Presentación)
            $('#from_unit_id').on('change', function() {
                var selected = $(this).find('option:selected');
                var unitName = selected.text().toUpperCase();
                var slug = (selected.text() || '').toLowerCase();
                var unitType = selected.data('type');
                var defaultFactor = selected.data('factor');

                // 1. Mostrar/Ocultar etiqueta (Label)
                var isContainer = unitName.includes('CAJA') || unitName.includes('PAQUETE') || unitName
                    .includes('PACK') ||
                    unitName.includes('BULT') || unitName.includes('MASTER');

                if (isContainer) {
                    $('#label_container').slideDown();
                } else {
                    $('#label_container').slideUp();
                }

                // 2. Lógica Dinámica de TABS Strict V2.1
                // Definir IDs de Tabs
                var $directTab = $('#direct-tab');
                var $contentTab = $('#content-tab');
                var $directPane = $('#pills-direct');
                var $contentPane = $('#pills-content');

                // Reset visual
                $directTab.addClass('disabled').removeClass('active');
                $contentTab.addClass('disabled').removeClass('active');
                $directPane.removeClass('show active');
                $contentPane.removeClass('show active');

                if (slug.includes('caja') || slug.includes('paquete') || slug.includes('pack') || slug
                    .includes('bulto') || slug.includes('master')) {
                    // --- MODO CONTENIDO (CAJA/PAQUETE) ---
                    $contentTab.removeClass('disabled').addClass('active').tab('show');
                    $contentPane.addClass('show active');
                    setMode('content');

                    // User request: Para cajas, omitir "Qué contiene" (usar base)
                    $('#intermediate_unit_id').val('{{ $consumptionUnit->id }}').trigger('change');
                    $('#intermediate_unit_id').closest('.form-group')
                        .hide(); // Ocultar el dropdown de "Qué contiene"

                    // FIX: Force symbol to 'pz' to match "Piezas" label
                    $('#selected_inter_symbol').text('pz');

                    $('#direct_pack_warning').slideUp();

                } else {
                    // --- MODO DIRECTO (CONO/ROLLO/BOTE/ETC) ---
                    $directTab.removeClass('disabled').addClass('active').tab('show');
                    $directPane.addClass('show active');
                    setMode('direct');

                    $('#direct_pack_warning').slideUp();
                }

                updatePreview();
            });

            // Inicializar al cargar (por si viene de un error de validación)
            if ($('#from_unit_id').val()) {
                $('#from_unit_id').trigger('change');
            }

            // LÓGICA DEL WIZARD (MODO CONTENIDO)
            // Al cambiar la unidad interna, pre-llenar el valor unitario y actualizar símbolos
            $('#intermediate_unit_id').on('change', function() {
                var option = $('option:selected', this);
                var factor = parseFloat(option.data('factor')) || 0;
                var symbol = option.data('symbol') || 'pz';
                var isBase = option.data('is-base') === true;

                // Actualizar el símbolo que acompaña a la cantidad (ej. 24 "caja" o 20 "cono")
                $('#selected_inter_symbol').text(symbol);

                var $inputValue = $('#intermediate_value_each');

                if (isBase) {
                    // MODO ESTRICTO: Si es la unidad base, el valor ES 1 y no se puede cambiar.
                    $inputValue.val(1).prop('readonly', true).addClass('bg-light');
                } else {
                    $inputValue.prop('readonly', false).removeClass('bg-light');
                    if (factor > 0) {
                        $inputValue.val(factor);
                    }
                }

                // Disparar input para recalcular
                $inputValue.trigger('input');
            });

            // Recalcular Factor Total al cambiar Qty o Valor cada uno
            $('#intermediate_qty, #intermediate_value_each').on('input', function() {
                var qty = parseFloat($('#intermediate_qty').val()) || 0;
                var valEach = parseFloat($('#intermediate_value_each').val()) || 0;
                var total = qty * valEach;

                if (total > 0) {
                    $('#conversion_factor').val(total.toFixed(4)).trigger('input');
                    // Actualizar display visual
                    $('#content-mode-total').text(total.toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 4
                    }));
                } else {
                    $('#content-mode-total').text('0');
                }

                // Guardar metadatos ocultos
                $('#hidden_intermediate_unit_id').val($('#intermediate_unit_id').val());
                $('#hidden_intermediate_qty').val(qty);
            });

            $('#conversion_factor').on('change keyup', updatePreview);

            // Trigger on load
            if ($('#from_unit_id').val()) {
                $('#from_unit_id').trigger('change');
            } else {
                updatePreview();
            }

            // Evitar doble submit y mostrar loading
            $('#conversion-form').on('submit', function() {
                var $btnSave = $('#btn-save-conversion');
                var $btnBack = $('#btn-back');

                $btnSave.prop('disabled', true);
                $btnBack.addClass('disabled'); // Deshabilitar visual y funcionalmente el link
                $btnSave.html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            });
        });
    </script>
@stop
