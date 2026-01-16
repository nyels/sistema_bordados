@extends('adminlte::page')

@section('title', 'Nuevo Material')

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
            <form method="POST" action="{{ route('admin.materials.store') }}">
                @csrf

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-box"></i> Datos del Material
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Categoría <span class="text-danger">*</span></label>
                            <select name="material_category_id" id="material_category_id"
                                class="form-control @error('material_category_id') is-invalid @enderror" required>
                                <option value="">Seleccionar categoría...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" data-unit="{{ $category->baseUnit->symbol ?? '' }}"
                                        data-has-color="{{ $category->has_color ? '1' : '0' }}"
                                        {{ old('material_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('material_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Ej: Manta Cruda, Hilo Rayón, Cinta Satín"
                                maxlength="100" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Composición</label>
                            <input type="text" name="composition"
                                class="form-control @error('composition') is-invalid @enderror"
                                value="{{ old('composition') }}" placeholder="Ej: 100% Algodón, 80/20 Algodón/Poliéster"
                                maxlength="100">
                            @error('composition')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Composición del material (opcional)</small>
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-balance-scale"></i> Unidades y Presentación
                            </h5>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Unidad de Compra (Base) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select name="base_unit_id" id="base_unit_id"
                                            class="form-control @error('base_unit_id') is-invalid @enderror" required
                                            disabled>
                                            <option value="">Seleccionar categoría primero...</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn bg-purple" data-toggle="modal"
                                                data-target="#unitModal" title="Crear nueva unidad">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Ej: Cono, Rollo, Pieza</small>
                                </div>
                            </div>
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
                        </div>

                        <div class="form-group mt-3">
                            <label>Notas / Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2"
                                maxlength="500">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
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
            var categorySelect = $('#material_category_id');
            var unitSelect = $('#base_unit_id');

            // Función para cargar unidades
            function loadUnits(categoryId, selectedUnitId = null) {
                if (!categoryId) {
                    unitSelect.html('<option value="">Seleccionar categoría primero...</option>');
                    unitSelect.prop('disabled', true);
                    return;
                }

                unitSelect.html('<option value="">Cargando unidades permitidas...</option>');
                unitSelect.prop('disabled', true);

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/get-units',
                    type: 'GET',
                    success: function(units) {
                        unitSelect.empty();

                        if (units.length === 0) {
                            unitSelect.html(
                                '<option value="">Esta categoría no tiene unidades de compra asignadas</option>'
                                );
                        } else {
                            unitSelect.append('<option value="">Seleccionar Unidad Base...</option>');
                            $.each(units, function(index, unit) {
                                var symbolText = unit.symbol ? ' (' + unit.symbol + ')' : '';
                                var isSelected = (selectedUnitId == unit.id) ? 'selected' : '';
                                unitSelect.append('<option value="' + unit.id + '" ' +
                                    isSelected + '>' + unit.name + symbolText + '</option>');
                            });
                            unitSelect.prop('disabled', false);
                        }
                    },
                    error: function() {
                        unitSelect.html('<option value="">Error al cargar unidades</option>');
                    }
                });
            }

            // Evento cambio de categoría
            categorySelect.on('change', function() {
                var categoryId = $(this).val();
                loadUnits(categoryId);
            });

            // Si hay un old('category_id') por error de validación, recargar
            var oldCategory = "{{ old('material_category_id') }}";
            var oldUnit = "{{ old('base_unit_id') }}";

            if (oldCategory) {
                loadUnits(oldCategory, oldUnit);
            }
        });
    </script>
@stop
