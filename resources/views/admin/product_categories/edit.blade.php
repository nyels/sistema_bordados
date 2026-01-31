@extends('adminlte::page')

@section('title', 'Editar Categoría de Producto')

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

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> EDITAR CATEGORÍA DE PRODUCTO</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.product_categories.update', $category->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-12">

                            <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                                <h5 style="color: #ffc107; font-weight: 600;">
                                    <i class="fas fa-edit"></i> Datos de la Categoría
                                </h5>
                            </div>

                            {{-- Nombre --}}
                            <div class="form-group">
                                <label>Nombre de la Categoría <span style="color: red;">*</span></label>
                                <input type="text" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    value="{{ old('name', $category->name) }}" required
                                    placeholder="Ej: Playeras, Camisas, Gorras...">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Descripción --}}
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="description" class="form-control form-control-sm @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Descripción opcional de la categoría...">{{ old('description', $category->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Estado Activo (Hidden: Default True) --}}
                            <input type="hidden" name="is_active" value="1">

                            {{-- Soporte de Medidas --}}
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                        class="custom-control-input"
                                        id="supports_measurements"
                                        name="supports_measurements"
                                        value="1"
                                        {{ old('supports_measurements', $category->supports_measurements) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="supports_measurements">
                                        Esta categoria admite productos con medidas personalizadas
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Ej: Vestidos, faldas, prendas a medida. Si se activa, los pedidos podran solicitar medidas.
                                </small>
                            </div>

                            {{-- Botones --}}
                            <div class="d-flex justify-content-end align-items-center mt-4">
                                <a href="{{ route('admin.product_categories.index') }}" class="btn btn-secondary mr-2">
                                    <i class="fas fa-times-circle"></i> Regresar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                            </div>
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
        console.log("ProductCategory Edit View");
    </script>
@stop
