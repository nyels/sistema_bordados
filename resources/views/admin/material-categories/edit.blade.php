@extends('adminlte::page')

@section('title', 'Editar Categoría de Material')

@section('content_header')
@stop

@section('content')
    <br>

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

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                        <i class="fas fa-edit"></i> EDITAR CATEGORÍA DE MATERIAL
                    </h3>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.material-categories.update', $category->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            {{-- COLUMNA IZQUIERDA: DATOS BÁSICOS --}}
                            <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 25px;">
                                <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                                    <h5 style="color: #856404; font-weight: 600;">
                                        <i class="fas fa-folder"></i> Datos Básicos
                                    </h5>
                                </div>

                                <div class="form-group">
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $category->name) }}" maxlength="50" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                        maxlength="500">{{ old('description', $category->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- COLUMNA DERECHA: CONFIGURACIÓN DE INVENTARIO --}}
                            <div class="col-md-6" style="padding-left: 25px;">
                                <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                                    <h5 style="color: #28a745; font-weight: 600;">
                                        <i class="fas fa-warehouse"></i> Configuración de Inventario
                                    </h5>
                                </div>

                                <div class="form-group">
                                    <label>
                                        Unidad de Inventario por Defecto
                                        <i class="fas fa-question-circle text-muted" data-toggle="tooltip"
                                            title="El sistema controlará existencias de los materiales de esta categoría en esta unidad."></i>
                                    </label>
                                    <select name="default_inventory_unit_id"
                                        class="form-control @error('default_inventory_unit_id') is-invalid @enderror">
                                        <option value="">-- Sin definir (flexible) --</option>
                                        @foreach ($inventoryUnits as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('default_inventory_unit_id', $category->default_inventory_unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Ejemplo: HILOS → Metro, BOTONES → Pieza
                                    </small>
                                    @error('default_inventory_unit_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="hidden" name="allow_unit_override" value="0">
                                        <input type="checkbox" class="custom-control-input" id="allow_unit_override"
                                            name="allow_unit_override" value="1"
                                            {{ old('allow_unit_override', $category->allow_unit_override ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="allow_unit_override">
                                            Permitir que materiales usen una unidad diferente
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        Si desmarcas esto, todos los materiales de esta categoría usarán obligatoriamente
                                        la unidad por defecto.
                                    </small>
                                </div>

                                {{-- RESUMEN DE UNIDADES PERMITIDAS --}}
                                <div class="card bg-light mt-3">
                                    <div class="card-body py-2">
                                        <strong><i class="fas fa-shopping-cart"></i> Presentaciones de Compra:</strong>
                                        <div class="mt-2">
                                            @if ($category->allowedUnits->isEmpty())
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Sin configurar
                                                </span>
                                            @else
                                                @foreach ($category->allowedUnits as $unit)
                                                    <span class="badge badge-primary mr-1">
                                                        {{ $unit->name }} ({{ $unit->symbol }})
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.material-category-units.index') }}"
                                            class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-cog"></i> Configurar Presentaciones
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@stop
