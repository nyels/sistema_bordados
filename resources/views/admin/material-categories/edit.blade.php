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

    <!-- Centered Card -->
    <!-- Centered Card -->
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">
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

                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
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
