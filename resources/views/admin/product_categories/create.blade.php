@extends('adminlte::page')

@section('title', 'Nueva Categoría de Producto')

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

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVA CATEGORÍA DE PRODUCTO</h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('admin.product_categories.store') }}">
                @csrf
                @method('POST')

                <div class="row">
                    <div class="col-md-8 offset-md-2">

                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-tag"></i> Datos de la Categoría
                            </h5>
                        </div>

                        {{-- Nombre --}}
                        <div class="form-group">
                            <label>Nombre de la Categoría <span style="color: red;">*</span></label>
                            <input type="text" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required placeholder="Ej: Playeras, Camisas, Gorras...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Descripción --}}
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control form-control-sm @error('description') is-invalid @enderror"
                                rows="3" placeholder="Descripción opcional de la categoría...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.product_categories.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        console.log("ProductCategory Create View");
    </script>
@stop
