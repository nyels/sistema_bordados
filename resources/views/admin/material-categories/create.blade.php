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
                            <label>Descripción</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                maxlength="500" placeholder="Descripción opcional...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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



@section('js')
    <script></script>
@stop
