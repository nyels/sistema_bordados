@extends('adminlte::page')

@section('title', 'Nuevo Atributo')

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
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO ATRIBUTO</h3>
            </div>

            <div class="card-body">
                <form method="post" action="{{ route('admin.attributes.store') }}">
                    @csrf
                    @method('POST')

                    <div class="row">
                        <div class="col-md-12">
                            <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                                <h5 style="color: #007bff; font-weight: 600;">
                                    <i class="fas fa-tags"></i> Datos del Atributo
                                </h5>
                            </div>

                            {{-- Nombre --}}
                            <div class="form-group">
                                <label>Nombre del Atributo <span style="color: red;">*</span></label>
                                <input type="text" name="name"
                                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required placeholder="Ej: Color, Talla, Material"
                                    maxlength="100">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Solo letras y espacios (máx. 100 caracteres)</small>
                            </div>

                            {{-- Tipo
                        <div class="form-group">
                            <label>Tipo de Atributo <span style="color: red;">*</span></label>
                            <select name="type" class="form-control form-control-sm @error('type') is-invalid @enderror"
                                required>
                                <option value="">Selecciona un tipo</option>
                                <option value="select" {{ old('type') == 'select' ? 'selected' : '' }}>Selector (Lista
                                    desplegable)</option>
                                <option value="color" {{ old('type') == 'color' ? 'selected' : '' }}>Color (Con paleta de
                                    colores)</option>
                                <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Texto (Campo libre)
                                </option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> --}}

                            <div class="row">
                                {{-- Requerido 
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>¿Es Requerido?</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_required"
                                            name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_required">
                                            <span id="required_label">{{ old('is_required') ? 'Sí' : 'No' }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div> --}}

                                {{-- Orden 
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Orden de Visualización</label>
                                    <input type="number" name="order"
                                        class="form-control form-control-sm @error('order') is-invalid @enderror"
                                        value="{{ old('order', 0) }}" min="0" max="9999" placeholder="0">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Número menor = mayor prioridad</small>
                                </div>
                            </div>
                        </div> --}}

                                {{-- Botones --}}
                                <div class="text-center mt-4">
                                    <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
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
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Toggle label para switch de requerido
            $('#is_required').on('change', function() {
                $('#required_label').text($(this).is(':checked') ? 'Sí' : 'No');
            });
        });
    </script>
@stop
