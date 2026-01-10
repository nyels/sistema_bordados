@extends('adminlte::page')

@section('title', 'Nueva Unidad de Medida')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="row">
        <div class="col-md-5">

            {{-- MENSAJES FLASH --}}
            @foreach (['success', 'error', 'info'] as $msg)
                @if (session($msg))
                    <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show"
                        role="alert">
                        {{ session($msg) }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
            @endforeach

            {{-- ERRORES DE VALIDACIÓN --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Se encontraron errores:</strong>
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
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle mr-2"></i>Nueva Unidad de Medida
                    </h3>
                </div>

                <div class="card-body">
                    <form method="post" action="{{ route('admin.units.store') }}">
                        @csrf

                        <div class="form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Ej: Metro, Rollo, Cono" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Símbolo <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" class="form-control @error('symbol') is-invalid @enderror"
                                value="{{ old('symbol') }}" placeholder="Ej: m, pz, cono" required>
                            @error('symbol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Abreviación corta de la unidad</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_base" name="is_base"
                                    value="1" {{ old('is_base') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_base">
                                    <strong>Es unidad base</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">Marcar si es unidad de consumo (metro, pieza, cono)</small>
                        </div>

                        <div class="form-group" id="compatible_unit_section"
                            style="{{ old('is_base') ? 'display: none;' : '' }}">
                            <label>Compatible con <i class="fas fa-link text-info"></i></label>
                            <select name="compatible_base_unit_id" id="compatible_base_unit_id"
                                class="form-control @error('compatible_base_unit_id') is-invalid @enderror">
                                <option value="">-- Seleccionar unidad base --</option>
                                @foreach ($baseUnits ?? [] as $baseUnit)
                                    <option value="{{ $baseUnit->id }}"
                                        {{ old('compatible_base_unit_id') == $baseUnit->id ? 'selected' : '' }}>
                                        {{ $baseUnit->name }} ({{ $baseUnit->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('compatible_base_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ej: ROLLO es compatible con METRO</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar
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
            $('#is_base').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#compatible_unit_section').slideUp();
                    $('#compatible_base_unit_id').val('');
                } else {
                    $('#compatible_unit_section').slideDown();
                }
            });
        });
    </script>
@stop
