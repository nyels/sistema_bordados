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
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.material-conversions.store', $material->id) }}">
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
                                    @if (!in_array($unit->id, $usedUnitIds))
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
                                    @endif
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
                        <ul class="nav nav-tabs mb-3" id="conversionModeTab" role="tablist">
                            {{-- Si NO hay conversiones previas, la Directa es la única y activa --}}
                            {{-- Si HAY conversiones, la "Por Contenido" será la activa por defecto --}}

                            <li class="nav-item">
                                <a class="nav-link {{ $existingConversions->count() == 0 ? 'active' : '' }}"
                                    id="direct-tab" data-toggle="pill" href="#pills-direct" role="tab"
                                    aria-selected="{{ $existingConversions->count() == 0 ? 'true' : 'false' }}"
                                    onclick="setMode('direct')">
                                    <i class="fas fa-ruler-horizontal"></i> Manual (Directa a
                                    {{ $consumptionUnit->symbol }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $existingConversions->count() > 0 ? 'active' : '' }} {{ $existingConversions->isEmpty() ? 'disabled text-muted' : '' }}"
                                    id="content-tab" data-toggle="pill" href="#pills-content" role="tab"
                                    aria-selected="{{ $existingConversions->count() > 0 ? 'true' : 'false' }}"
                                    onclick="{{ $existingConversions->isEmpty() ? 'return false;' : "setMode('content')" }}"
                                    title="{{ $existingConversions->isEmpty() ? 'Registre primero una unidad base (ej. Cono) para habilitar esta opción.' : '' }}">
                                    <i class="fas fa-boxes"></i> <strong>Por Contenido (Recomendado)</strong>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            {{-- MODO DIRECTO --}}
                            <div class="tab-pane fade {{ $existingConversions->count() == 0 ? 'show active' : '' }}"
                                id="pills-direct" role="tabpanel">
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
                            <div class="tab-pane fade {{ $existingConversions->count() > 0 ? 'show active' : '' }}"
                                id="pills-content" role="tabpanel">
                                @if ($existingConversions->count() > 0)
                                    <div class="card bg-light border-info">
                                        <div class="card-body py-2">
                                            <div class="form-group mb-2">
                                                <label class="text-info mb-1">1. ¿Qué contiene esta unidad?</label>
                                                <select id="intermediate_unit_id" class="form-control form-control-sm">
                                                    <option value="" data-factor="0">Seleccionar contenido...
                                                    </option>
                                                    @foreach ($existingConversions as $ec)
                                                        <option value="{{ $ec->fromUnit->id }}"
                                                            data-factor="{{ $ec->conversion_factor }}"
                                                            data-symbol="{{ $ec->fromUnit->symbol }}">
                                                            {{ $ec->fromUnit->name }} (1 =
                                                            {{ number_format($ec->conversion_factor, 2) }}
                                                            {{ $consumptionUnit->symbol }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="text-info mb-1">2. ¿Cuántas piezas trae?</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="intermediate_qty" class="form-control"
                                                        placeholder="Ej: 24" min="1">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text"
                                                            id="intermediate_symbol_display">pzas</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="fas fa-calculator"></i> El sistema calculará los
                                                {{ $consumptionUnit->symbol }} totales automáticamente.
                                            </small>
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

                <hr>

                <div class="text-center">
                    <a href="{{ route('admin.material-conversions.index', $material->id) }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
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

            // Auto-llenar factor cuando se selecciona una Presentación (METRIC_PACK)
            $('#from_unit_id').on('change', function() {
                var selected = $(this).find(':selected');
                var unitType = selected.data('type');
                var defaultFactor = selected.data('factor');

                // Si es METRIC_PACK y tiene factor predeterminado, sugerirlo
                if (unitType === 'metric_pack' && defaultFactor && defaultFactor > 0) {
                    $('#conversion_factor').val(defaultFactor);
                }

                // Mostrar alerta si es Pack en modo directo
                if (unitType === 'metric_pack') {
                    $('#direct_pack_warning').slideDown();
                } else {
                    $('#direct_pack_warning').slideUp();
                }

                updatePreview();
            });

            // LÓGICA DEL WIZARD (MODO CONTENIDO)
            $('#intermediate_unit_id, #intermediate_qty').on('change keyup', function() {
                var factorBase = parseFloat($('#intermediate_unit_id option:selected').data('factor')) || 0;
                var qty = parseFloat($('#intermediate_qty').val()) || 0;
                var symbol = $('#intermediate_unit_id option:selected').data('symbol') || 'pzas';

                $('#intermediate_symbol_display').text(symbol);

                if (factorBase > 0 && qty > 0) {
                    var totalBase = factorBase * qty;
                    // Actualizar el input principal (que es el que se envía)
                    $('#conversion_factor').val(totalBase.toFixed(4));
                    // Disparar preview
                    updatePreview();
                }
            });

            // Función global para cambiar tabs (si es necesario lógica extra)
            window.setMode = function(mode) {
                if (mode === 'direct') {
                    $('#intermediate_qty').val('');
                    $('#intermediate_unit_id').val('');
                }
            };

            $('#conversion_factor').on('change keyup', updatePreview);

            // Trigger on load
            updatePreview();
        });
    </script>
@stop
