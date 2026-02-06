@extends('adminlte::page')

@section('title', 'NIVEL DE URGENCIA')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card card-primary">

            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO NIVEL DE URGENCIA</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.urgency-levels.store') }}" id="formCreate">
                    @csrf
                    @method('POST')
                    <div class="col-md-12">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-clock" style="margin-right: 10px;"></i>
                                Datos del Nivel
                            </h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}"
                                        placeholder="Ej: URGENTE, EXPRESS, NORMAL" required maxlength="100">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">Orden de Aparición</label>
                                    <input type="number" class="form-control form-control-sm @error('sort_order') is-invalid @enderror"
                                        id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Menor número = aparece primero</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="time_percentage">Porcentaje de Tiempo <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control @error('time_percentage') is-invalid @enderror"
                                            id="time_percentage" name="time_percentage"
                                            value="{{ old('time_percentage', 100) }}" min="1" max="200" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('time_percentage')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">100% = tiempo normal, 50% = mitad del tiempo</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_multiplier">Multiplicador de Precio <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">x</span>
                                        </div>
                                        <input type="number" class="form-control @error('price_multiplier') is-invalid @enderror"
                                            id="price_multiplier" name="price_multiplier"
                                            value="{{ old('price_multiplier', '1.00') }}" min="0.5" max="5" step="0.05" required>
                                    </div>
                                    @error('price_multiplier')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">1.00 = precio normal, 1.50 = 50% más</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color">Color <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" id="colorPicker" value="{{ old('color', '#28a745') }}"
                                            style="width: 40px; height: 31px; padding: 2px; border: 1px solid #ced4da;">
                                        <input type="text" class="form-control @error('color') is-invalid @enderror"
                                            id="color" name="color" value="{{ old('color', '#28a745') }}" maxlength="20" required>
                                    </div>
                                    @error('color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icon">Icono (FontAwesome)</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i id="iconPreview" class="fas fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                            id="icon" name="icon" value="{{ old('icon', 'fa-clock') }}" maxlength="50"
                                            placeholder="fa-clock, fa-bolt, fa-exclamation-triangle">
                                    </div>
                                    @error('icon')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ejemplos: fa-clock, fa-bolt, fa-fire</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea class="form-control form-control-sm @error('description') is-invalid @enderror"
                                id="description" name="description" rows="2" maxlength="500"
                                placeholder="Descripción opcional del nivel de urgencia">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Vista Previa --}}
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px;">
                            <h5 style="color: #6c757d; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-eye" style="margin-right: 10px;"></i>
                                Vista Previa
                            </h5>
                        </div>
                        <div class="text-center p-3 rounded mb-3" style="background-color: #f8f9fa;">
                            <span id="previewBadge" class="badge p-2" style="background-color: #28a745; color: white; font-size: 1.1rem;">
                                <i class="fas fa-clock mr-1"></i> <span id="previewName">NORMAL</span>
                            </span>
                            <div class="mt-2">
                                <small><strong>Tiempo:</strong> <span id="previewTime">100</span>% | <strong>Precio:</strong> x<span id="previewPrice">1.00</span></small>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.urgency-levels.index') }}" class="btn btn-secondary mr-2"
                                style="padding: 8px 20px;">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnGuardar" style="padding: 8px 20px;">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        $(function() {
            // Sincronizar color picker
            $('#colorPicker').on('input', function() {
                $('#color').val($(this).val());
                updatePreview();
            });
            $('#color').on('input', function() {
                $('#colorPicker').val($(this).val());
                updatePreview();
            });

            // Actualizar icono preview
            $('#icon').on('input', function() {
                var icon = $(this).val() || 'fa-clock';
                $('#iconPreview').attr('class', 'fas ' + icon);
                updatePreview();
            });

            // Actualizar preview en tiempo real
            $('#name, #time_percentage, #price_multiplier').on('input', updatePreview);

            function updatePreview() {
                var name = $('#name').val() || 'NOMBRE';
                var color = $('#color').val() || '#28a745';
                var icon = $('#icon').val() || 'fa-clock';
                var time = $('#time_percentage').val() || 100;
                var price = $('#price_multiplier').val() || '1.00';

                $('#previewBadge').css('background-color', color);
                $('#previewBadge i').attr('class', 'fas ' + icon + ' mr-1');
                $('#previewName').text(name.toUpperCase());
                $('#previewTime').text(time);
                $('#previewPrice').text(parseFloat(price).toFixed(2));
            }

            updatePreview();

            // Submit con loading
            $('#formCreate').on('submit', function() {
                var $btn = $('#btnGuardar');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            });
        });
    </script>
@stop
