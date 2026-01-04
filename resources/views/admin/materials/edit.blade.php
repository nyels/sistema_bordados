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
            <form method="POST" action="{{ route('materials.update', $material->id) }}">
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
                                <i class="fas fa-info-circle"></i> Información Adicional
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4"
                                maxlength="500">{{ old('description', $material->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- INFO --}}
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <small>
                                    <strong>UUID:</strong>
                                    <code>{{ $material->uuid }}</code>
                                </small>
                                <br>
                                <small>
                                    <strong>Creado:</strong>
                                    {{ $material->created_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <a href="{{ route('materials.index') }}" class="btn btn-secondary">
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
