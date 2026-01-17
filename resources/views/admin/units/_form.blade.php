@php
    // Detectar si la unidad está bloqueada por ser del sistema
    $isLocked = isset($unit) && $unit->exists && $unit->isSystemUnit();
@endphp

@if ($isLocked)
    <div class="alert alert-warning shadow-sm border-warning d-flex align-items-center mb-4">
        <i class="fas fa-lock fa-2x mr-3 text-warning"></i>
        <div>
            <h5 class="font-weight-bold mb-1">Unidad Bloqueada por el Sistema</h5>
            <span class="small">
                Esta es una <strong>Unidad Fundamental (Base)</strong> predefinida. No se puede modificar ni eliminar
                porque es la
                referencia para todas las conversiones del sistema.
            </span>
        </div>
    </div>
@endif

{{-- Fila 1: Información Básica --}}
<div class="row bg-white p-3 rounded shadow-sm mb-4">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name" class="font-weight-bold text-dark">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name"
                class="form-control form-control-lg @error('name') is-invalid @enderror"
                value="{{ old('name', $unit->name ?? '') }}" placeholder="Ej: Metro, Rollo 50M, Cono" required autofocus
                style="border-radius: 6px;" {{ $isLocked ? 'disabled' : '' }}>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="symbol" class="font-weight-bold text-dark">Símbolo <span class="text-danger">*</span></label>
            <input type="text" name="symbol" id="symbol"
                class="form-control form-control-lg @error('symbol') is-invalid @enderror"
                value="{{ old('symbol', $unit->symbol ?? '') }}" placeholder="Ej: m, r50m, c" required
                style="border-radius: 6px;" {{ $isLocked ? 'disabled' : '' }}>
            <small class="text-muted">Abreviación corta</small>
            @error('symbol')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="sort_order" class="font-weight-bold text-dark">Orden</label>
            <input type="number" name="sort_order" id="sort_order"
                class="form-control form-control-lg @error('sort_order') is-invalid @enderror"
                value="{{ old('sort_order', $unit->sort_order ?? 0) }}" min="0" max="999"
                style="border-radius: 6px;" {{ $isLocked ? 'disabled' : '' }}>
            @error('sort_order')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

{{-- Fila 2: Configuración de Tipo y Estado --}}
<div class="row mb-4">
    {{-- Tarjeta Izquierda: Tipo de Unidad --}}
    <div class="col-md-6 pr-md-2 mb-3 mb-md-0">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="font-weight-bold text-primary mb-3">
                    <i class="fas fa-balance-scale mr-2"></i>Naturaleza de la Unidad <span class="text-danger">*</span>
                </h6>

                <div class="d-flex align-items-center justify-content-between bg-light rounded p-2 mb-3 border">
                    <span class="text-muted small font-weight-bold text-uppercase" id="label_pack">Empaque /
                        Contenedor</span>

                    <div class="custom-control custom-switch custom-switch-md mx-2">
                        <input type="checkbox" class="custom-control-input" id="is_base" name="is_base" value="1"
                            {{ old('is_base', $unit->is_base ?? false) ? 'checked' : '' }}
                            {{ $isLocked ? 'disabled' : '' }}>
                        <label class="custom-control-label font-weight-bold text-dark" for="is_base"></label>
                    </div>

                    <span class="text-success small font-weight-bold text-uppercase" id="label_base">Unidad Base
                        (Consumo)</span>
                </div>

                {{-- Textos de ayuda dinámicos --}}
                <div id="help_is_base" class="text-muted small">
                    <div class="alert alert-success bg-white border-success text-success p-2 mb-0 shadow-sm">
                        <i class="fas fa-check-circle mr-1"></i>
                        <strong>Unidad Fundamental:</strong> Se usa para medir el consumo real en producción (Ej:
                        <em>Metro, Litro, Gramo, Pieza</em>).
                    </div>
                </div>

                <div id="help_is_pack" class="text-muted small" style="display: none;">
                    <div class="alert alert-warning bg-white border-warning text-dark p-2 mb-0 shadow-sm">
                        <i class="fas fa-box-open mr-1 text-warning"></i>
                        <strong>Contenedor de Compra:</strong> Agrupa otras unidades. NO se consume directamente, se
                        abre para sacar su contenido (Ej: <em>Caja, Rollo, Paquete</em>).
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Tarjeta Derecha: Activo --}}
    <div class="col-md-6 pl-md-2">
        <div class="card h-100 shadow-sm border-0 bg-white">
            <div class="card-body">
                <h6 class="font-weight-bold text-primary mb-3">
                    <i class="fas fa-power-off mr-2"></i>Visibilidad
                </h6>

                <div class="custom-control custom-switch custom-switch-md mb-3">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                        {{ old('is_active', $unit->activo ?? true) ? 'checked' : '' }}
                        {{ $isLocked ? 'disabled' : '' }}>
                    <label class="custom-control-label font-weight-bold text-dark" for="is_active">
                        Unidad Activa
                    </label>
                </div>

                <div class="text-muted small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Las unidades inactivas siguen existiendo pero se ocultan en los formularios de creación.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Sección Inferior: Configuración de Compra/Logística (Solo Visible si NO es Consumo) --}}
<div class="card shadow-sm border-0" id="purchase_config_section" style="display: none;">
    <div class="card-header bg-warning text-dark py-2">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-boxes mr-2"></i> Configuración del Empaque / Contenedor
        </h6>
    </div>
    <div class="card-body bg-white">

        <div class="alert alert-light border-left border-warning mb-4">
            <small class="text-muted">
                <i class="fas fa-exclamation-circle text-warning mr-1"></i>
                Estás configurando un <strong>EMPAQUE</strong>. Define qué unidad base hay dentro de él.
                <br>
                <em>(Ej: Si el nombre es "Caja", el contenido podría ser "Pieza").</em>
            </small>
        </div>

        <div class="row">
            {{-- Se convierte a --}}
            <div class="col-md-6 mb-3">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-bullseye text-info mr-1"></i> Contenido (Unidad Base)
                </label>
                <select name="compatible_base_unit_id" id="compatible_base_unit_id" class="form-control"
                    style="height: 45px;" {{ $isLocked ? 'disabled' : '' }}>
                    <option value="">-- Contenido Genérico / Multiuso --</option>
                    @foreach ($canonicalUnits ?? [] as $canonicalUnit)
                        <option value="{{ $canonicalUnit->id }}"
                            {{ old('compatible_base_unit_id', $unit->compatible_base_unit_id ?? '') == $canonicalUnit->id ? 'selected' : '' }}>
                            Contiene: {{ $canonicalUnit->name }} ({{ $canonicalUnit->symbol }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mt-2">
                    ¿Qué hay dentro de este empaque?
                </small>
            </div>

            {{-- Cantidad por unidad --}}
            <div class="col-md-6 mb-3">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-sort-numeric-up text-warning mr-1"></i> Cantidad Contenida
                </label>
                <input type="number" name="default_conversion_factor" id="default_conversion_factor"
                    class="form-control"
                    value="{{ old('default_conversion_factor', $unit->default_conversion_factor ?? '') }}"
                    step="0.0001" min="0" max="999999.9999" style="height: 45px;"
                    placeholder="Variable (Se define por material)" {{ $isLocked ? 'disabled' : '' }}>
                <small class="form-text text-muted mt-2">
                    Dejar vacío si la cantidad varía según el producto.
                </small>
            </div>
        </div>

        {{-- Caja Informativa Dinámica --}}
        <div class="alert alert-light border mt-2 d-flex align-items-center" id="unit_type_indicator">
            <div id="type_icon_container" class="mr-3">
                <i class="fas fa-shopping-cart fa-2x text-primary"></i>
            </div>
            <div>
                <strong class="d-block text-primary" id="indicator_title">Tipo: LOGÍSTICO (Contenedor
                    Genérico)</strong>
                <span class="text-muted small" id="indicator_desc">Este es un contenedor variable (Ej: Caja, Bulto).
                    La cantidad exacta se define al crear cada material.</span>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .custom-switch-lg .custom-control-label::before {
            width: 3rem;
            height: 1.5rem;
            border-radius: 1rem;
        }

        .custom-switch-lg .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: 50%;
        }

        .custom-switch-lg .custom-control-input:checked~.custom-control-label::after {
            transform: translateX(1.5rem);
        }

        .input-group-text {
            font-size: 1.1rem;
        }

        .form-control-lg {
            height: calc(1.5em + 1rem + 2px);
            font-size: 1.1rem;
        }
    </style>
@endpush

@push('js')
    <script>
        $(function() {
            function updateVisibility() {
                var isConsumption = $('#is_base').is(':checked');

                if (isConsumption) {
                    // MODO UNIDAD BASE
                    $('#purchase_config_section').slideUp(200);
                    $('#help_is_base').show();
                    $('#help_is_pack').hide();
                    $('#label_base').removeClass('text-muted').addClass('text-success font-weight-bold');
                    $('#label_pack').removeClass('text-dark font-weight-bold').addClass('text-muted');
                } else {
                    // MODO EMPAQUE
                    $('#purchase_config_section').slideDown(200);
                    $('#help_is_base').hide();
                    $('#help_is_pack').show();
                    $('#label_base').removeClass('text-success font-weight-bold').addClass('text-muted');
                    $('#label_pack').removeClass('text-muted').addClass('text-dark font-weight-bold');
                }
            }

            function updateTypeIndicator() {
                var factor = $('#default_conversion_factor').val();

                var title = $('#indicator_title');
                var desc = $('#indicator_desc');
                var iconContainer = $('#type_icon_container');

                if (factor && parseFloat(factor) > 0) {
                    // PRESENTACIÓN FIJA
                    title.text('Tipo: PRESENTACIÓN FIJA (Metric Pack)');
                    title.removeClass('text-primary').addClass('text-success');
                    desc.text(
                        '- Empaque estandarizado que SIEMPRE trae la misma cantidad (Ej: Lata 355ml, Rollo 50m).'
                    );
                    iconContainer.html('<i class="fas fa-box-open fa-2x text-success"></i>');
                } else {
                    // LOGÍSTICA VARIABLE
                    title.text('Tipo: LOGÍSTICO (Contenedor Genérico)');
                    title.removeClass('text-success').addClass('text-primary');
                    desc.text(
                        '- Contenedor genérico. La cantidad puede variar por producto (Ej: Una Caja puede traer 10 o 20 piezas).'
                    );
                    iconContainer.html('<i class="fas fa-dolly fa-2x text-primary"></i>');
                }
            }

            $('#is_base').on('change', updateVisibility);
            $('#default_conversion_factor').on('input change', updateTypeIndicator);
            $('#compatible_base_unit_id').on('change', updateTypeIndicator);

            // Initial calls
            updateVisibility();
            updateTypeIndicator();
        });
    </script>
@endpush
