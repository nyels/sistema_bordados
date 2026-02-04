@extends('adminlte::page')

@section('title', 'Nuevo Nivel de Urgencia')

@section('content_header')
    <h1><i class="fas fa-clock mr-2"></i> Nuevo Nivel de Urgencia</h1>
@stop

@section('content')
    {{-- Mensajes Flash --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <form action="{{ route('admin.urgency-levels.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Datos del Nivel</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" maxlength="100" required
                                           placeholder="Ej: Urgente, Express, Normal">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">Orden de Aparición</label>
                                    <input type="number" name="sort_order" id="sort_order"
                                           class="form-control @error('sort_order') is-invalid @enderror"
                                           value="{{ old('sort_order', 0) }}" min="0">
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
                                    <label for="time_percentage">Porcentaje de Tiempo <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="time_percentage" id="time_percentage"
                                               class="form-control @error('time_percentage') is-invalid @enderror"
                                               value="{{ old('time_percentage', 100) }}" min="1" max="200" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('time_percentage')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">100% = tiempo normal, 50% = mitad del tiempo, 25% = un cuarto</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_multiplier">Multiplicador de Precio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">x</span>
                                        </div>
                                        <input type="number" name="price_multiplier" id="price_multiplier"
                                               class="form-control @error('price_multiplier') is-invalid @enderror"
                                               value="{{ old('price_multiplier', '1.00') }}" min="0.5" max="5" step="0.05" required>
                                    </div>
                                    @error('price_multiplier')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">1.00 = precio normal, 1.25 = 25% más, 1.50 = 50% más</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color">Color <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="color" id="colorPicker" value="{{ old('color', '#28a745') }}"
                                               style="width: 50px; height: 38px; padding: 2px;">
                                        <input type="text" name="color" id="color"
                                               class="form-control @error('color') is-invalid @enderror"
                                               value="{{ old('color', '#28a745') }}" maxlength="20" required>
                                    </div>
                                    @error('color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icon">Icono (FontAwesome)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i id="iconPreview" class="fas fa-clock"></i></span>
                                        </div>
                                        <input type="text" name="icon" id="icon"
                                               class="form-control @error('icon') is-invalid @enderror"
                                               value="{{ old('icon', 'fa-clock') }}" maxlength="50"
                                               placeholder="fa-clock, fa-bolt, fa-exclamation-triangle">
                                    </div>
                                    @error('icon')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ejemplos: fa-clock, fa-bolt, fa-exclamation-triangle</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea name="description" id="description" rows="2"
                                      class="form-control @error('description') is-invalid @enderror"
                                      maxlength="500" placeholder="Descripción opcional del nivel de urgencia">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Vista Previa</h3>
                    </div>
                    <div class="card-body text-center">
                        <div id="preview" class="p-3 rounded mb-3" style="background-color: #f8f9fa;">
                            <span id="previewBadge" class="badge p-2" style="background-color: #28a745; color: white; font-size: 1.1rem;">
                                <i class="fas fa-clock mr-1"></i> <span id="previewName">NORMAL</span>
                            </span>
                        </div>
                        <div class="text-left">
                            <p class="mb-1"><strong>Tiempo:</strong> <span id="previewTime">100</span>% del normal</p>
                            <p class="mb-1"><strong>Precio:</strong> x<span id="previewPrice">1.00</span></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('admin.urgency-levels.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left mr-1"></i> Regresar
                        </a>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function() {
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
                const icon = $(this).val() || 'fa-clock';
                $('#iconPreview').attr('class', 'fas ' + icon);
                updatePreview();
            });

            // Actualizar preview en tiempo real
            $('#name, #time_percentage, #price_multiplier').on('input', updatePreview);

            function updatePreview() {
                const name = $('#name').val() || 'NOMBRE';
                const color = $('#color').val() || '#28a745';
                const icon = $('#icon').val() || 'fa-clock';
                const time = $('#time_percentage').val() || 100;
                const price = $('#price_multiplier').val() || '1.00';

                $('#previewBadge').css('background-color', color);
                $('#previewBadge i').attr('class', 'fas ' + icon + ' mr-1');
                $('#previewName').text(name.toUpperCase());
                $('#previewTime').text(time);
                $('#previewPrice').text(parseFloat(price).toFixed(2));
            }

            updatePreview();
        });
    </script>
@stop
