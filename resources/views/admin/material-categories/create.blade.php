@extends('adminlte::page')

@section('title', 'Nueva Categoría de Material')

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
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                        <i class="fas fa-plus-circle"></i> NUEVA CATEGORÍA DE MATERIAL
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.material-categories.store') }}">
                        @csrf

                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Ej: Telas, Hilos, Cintas" maxlength="50" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Unidad Base <span class="text-danger">*</span></label>
                            <select name="base_unit_id" class="form-control @error('base_unit_id') is-invalid @enderror"
                                required>
                                <option value="">Seleccionar unidad...</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}"
                                        {{ old('base_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('base_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Unidad para consumo e inventario</small>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                maxlength="500" placeholder="Descripción opcional...">{{ old('description') }}</textarea>
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
                                    Materiales tienen variantes de color
                                </label>
                            </div>
                            <small class="form-text text-muted">Ej: Telas sí, Agujas no</small>
                        </div>

                        <hr>

                        <div class="text-center">
                            <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
