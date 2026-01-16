@extends('adminlte::page')

@section('title', 'Editar Material')

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

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-edit"></i> EDITAR MATERIAL
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.materials.update', $material->id) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600;">
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
                                        {{ old('material_category_id', $material->material_category_id) == $category->id ? 'selected' : '' }}>
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
                                value="{{ old('name', $material->name) }}" maxlength="100" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Composición</label>
                            <input type="text" name="composition"
                                class="form-control @error('composition') is-invalid @enderror"
                                value="{{ old('composition', $material->composition) }}" maxlength="100">
                            @error('composition')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                            class="form-control @error('base_unit_id') is-invalid @enderror" required>
                                            <option value="">...</option>
                                            @foreach ($baseUnits as $unit)
                                                <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}"
                                                    data-id="{{ $unit->id }}"
                                                    {{ old('base_unit_id', $material->base_unit_id) == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->name }} ({{ $unit->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn bg-purple" data-toggle="modal"
                                                data-target="#unitModal" title="Crear nueva unidad">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Opciones</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="has_color" name="has_color"
                                    value="1" {{ old('has_color', $material->has_color) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="has_color">
                                    Este material tiene variantes de color
                                </label>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label>Notas / Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2"
                                maxlength="500">{{ old('description', $material->description) }}</textarea>
                        </div>

                        {{-- INFO --}}
                        <div class="card bg-light mt-3">
                            <div class="card-body py-2">
                                <small>
                                    <strong>UUID:</strong>
                                    <code>{{ $material->uuid }}</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Actualizar
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
            var initialCategoryId = "{{ $material->material_category_id }}";
            var initialUnitId = "{{ $material->base_unit_id }}";

            // Función para cargar unidades según categoría
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

            // Evento cambio de categoría - LIMPIAR unidad previa
            categorySelect.on('change', function() {
                var categoryId = $(this).val();
                // Al cambiar categoría, NO pasamos selectedUnitId para forzar nueva selección
                loadUnits(categoryId, null);
            });

            // Si hay un old() por error de validación, usarlo
            var oldCategory = "{{ old('material_category_id', '') }}";
            var oldUnit = "{{ old('base_unit_id', '') }}";

            if (oldCategory && oldCategory !== initialCategoryId) {
                // Hubo error de validación con categoría cambiada
                loadUnits(oldCategory, oldUnit);
            }
            // Si no hay cambios, el select ya tiene las unidades correctas del servidor
        });
    </script>
@stop
