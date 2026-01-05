@extends('adminlte::page')

@section('title', 'Nuevo Valor de Atributo')

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

    <div class="col-md-4">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO VALOR DE ATRIBUTO</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.attribute-values.store') }}" id="formValorAtributo">
                    @csrf

                    <div class="row">
                        <div class="col-md-12">
                            <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                                <h5 style="color: #28a745; font-weight: 600;">
                                    <i class="fas fa-tag"></i> Datos del Valor
                                </h5>
                            </div>

                            {{-- Atributo --}}
                            <div class="form-group">
                                <label>Atributo <span style="color: red;">*</span></label>
                                <select name="attribute_id" id="attribute_id"
                                    class="form-control form-control-sm @error('attribute_id') is-invalid @enderror"
                                    required>
                                    <option value="" data-slug="">Selecciona un atributo</option>
                                    @foreach ($attributes as $attribute)
                                        <option value="{{ $attribute->id }}" data-slug="{{ $attribute->slug }}"
                                            {{ old('attribute_id') == $attribute->id ? 'selected' : '' }}>
                                            {{ $attribute->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('attribute_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Valor --}}
                            <div class="form-group">
                                <label>Nombre del Valor <span style="color: red;">*</span></label>
                                <input type="text" name="value"
                                    class="form-control form-control-sm @error('value') is-invalid @enderror"
                                    value="{{ old('value') }}" required placeholder="Ej: Rojo, Grande, Algodón"
                                    maxlength="100">
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Letras, números, espacios y guiones (máx. 100
                                    caracteres)</small>
                            </div>

                            {{-- Color Picker (Dinámico) --}}
                            <div class="form-group" id="color_picker_container" style="display: none;">
                                <label>Color Hexadecimal <span style="color: red;">*</span></label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="color" id="color_picker" class="form-control" value="#000000"
                                            style="height: 45px; padding: 2px; cursor: pointer;">
                                    </div>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">#</span>
                                            </div>
                                            <input type="text" name="hex_color" id="hex_color"
                                                class="form-control form-control-sm @error('hex_color') is-invalid @enderror"
                                                value="{{ old('hex_color', '#000000') }}" placeholder="RRGGBB"
                                                maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                            @error('hex_color')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">Formato: #RRGGBB (ej: #FF0000 para rojo)</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label>Vista previa:</label>
                                    <div id="color_preview"
                                        style="
                                    width: 100%;
                                    height: 40px;
                                    background-color: #000000;
                                    border: 2px solid #333;
                                    border-radius: 4px;
                                ">
                                    </div>
                                </div>
                            </div>

                            {{-- Orden
                            <div class="form-group">
                                <label>Orden de Visualización</label>
                                <input type="number" name="order"
                                    class="form-control form-control-sm @error('order') is-invalid @enderror"
                                    value="{{ old('order', 0) }}" min="0" max="9999" placeholder="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Número menor = mayor prioridad</small>
                            </div> --}}

                            {{-- Botones --}}
                            <div class="text-center mt-4">
                                <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times-circle"></i> Regresar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #color_picker {
            border: 2px solid #ced4da;
            border-radius: 4px;
        }

        #color_picker:hover {
            border-color: #28a745;
        }
    </style>
@stop
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chroma-js/2.4.2/chroma.min.js"></script>

    <script>
        $(document).ready(function() {
            const $attributeSelect = $('#attribute_id');
            const $colorContainer = $('#color_picker_container');
            const $hexInput = $('#hex_color');
            const $colorPicker = $('#color_picker');
            const $colorPreview = $('#color_preview');
            const $valueInput = $('input[name="value"]');

            /**
             * Lógica de Categorización por Tono (Hue)
             * Esto es mucho más preciso que comparar nombres individuales.
             */
            function getSpanishColorName(hex) {
                try {
                    const color = chroma(hex);
                    const hsl = color.hsl(); // [Hue, Saturation, Lightness]

                    const h = hsl[0]; // Tono (0-360)
                    const s = hsl[1]; // Saturación (0-1)
                    const l = hsl[2]; // Luminosidad (0-1)

                    // 1. Casos de Acromáticos (Blanco, Negro, Gris)
                    if (l < 0.15) return 'Negro';
                    if (l > 0.92) return 'Blanco';
                    if (s < 0.12) return 'Gris';

                    // 2. Clasificación por Tono (Rueda de color)
                    if (h >= 0 && h < 15) return 'Rojo';
                    if (h >= 15 && h < 45) return 'Naranja / Café';
                    if (h >= 45 && h < 65) return 'Amarillo';
                    if (h >= 65 && h < 160) return 'Verde';
                    if (h >= 160 && h < 190) return 'Cian / Turquesa';
                    if (h >= 190 && h < 260) return 'Azul';
                    if (h >= 260 && h < 290) return 'Morado';
                    if (h >= 290 && h < 335) return 'Fucsia / Rosa'; // <--- Aquí caerá el #BB3590
                    if (h >= 335 && h <= 360) return 'Rojo';

                    return 'Color Personalizado';
                } catch (e) {
                    return '';
                }
            }

            function updateUI(hex) {
                if (chroma.valid(hex)) {
                    const normalizedHex = chroma(hex).hex().toUpperCase();
                    $colorPicker.val(normalizedHex);
                    $colorPreview.css('background-color', normalizedHex);

                    // Aplicamos la lógica de nombre en español
                    $valueInput.val(getSpanishColorName(normalizedHex));
                }
            }

            function toggleDisplay() {
                const isColor = $attributeSelect.find('option:selected').data('slug') === 'color';

                if (isColor) {
                    $colorContainer.slideDown();
                    $hexInput.prop('required', true);
                    if (!$hexInput.val()) $hexInput.val('#000000');
                    updateUI($hexInput.val());
                } else {
                    // RESET TOTAL
                    $colorContainer.slideUp();
                    $hexInput.prop('required', false).val('');
                    $valueInput.val('');
                    $colorPreview.css('background-color', 'transparent');
                }
            }

            // --- EVENTOS ---
            $attributeSelect.on('change', toggleDisplay);

            $colorPicker.on('input change', function() {
                const hex = $(this).val();
                $hexInput.val(hex.toUpperCase());
                updateUI(hex);
            });

            $hexInput.on('input', function() {
                let val = $(this).val().trim();
                if (val && !val.startsWith('#')) {
                    val = '#' + val;
                    $(this).val(val);
                }
                if (val.length === 7) updateUI(val);
            });

            toggleDisplay();
        });
    </script>
@stop
