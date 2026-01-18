@extends('adminlte::page')

@section('title', 'Nueva Variante - ' . $material->name)

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

    {{-- BREADCRUMB INFO --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.materials.index') }}">
                            <i class="fas fa-boxes"></i> Materiales
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.material-variants.index', $material->id) }}">
                            {{ $material->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Nueva Variante</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-plus-circle"></i> NUEVA VARIANTE DE: {{ $material->name }}
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.material-variants.index', $material->id) }}" id="btn-back"
                    class="btn btn-secondary font-weight-bold" title="Regresar">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
                <button type="submit" form="variant-form" id="btn-save-variant"
                    class="btn btn-outline-light font-weight-bold ml-2" title="Guardar Variante">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>

        <div class="card-body">
            <form id="variant-form" method="POST" action="{{ route('admin.material-variants.store', $material->id) }}">
                @csrf
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-barcode"></i> Identificación
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                                value="{{ old('sku', $suggestedSku) }}" placeholder="Ej: TEL-MANT-001" maxlength="30"
                                style="text-transform: uppercase; background-color: #e9ecef;" readonly required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-magic"></i> Generado automáticamente
                            </small>
                        </div>

                        @if ($material->has_color)
                            <div class="form-group">
                                <label>Color</label>
                                <input type="text" name="color"
                                    class="form-control @error('color') is-invalid @enderror" value="{{ old('color') }}"
                                    placeholder="Ej: Blanco, Rojo, Azul Marino" maxlength="50">
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Color o variación del material
                                </small>
                            </div>
                        @else
                            <input type="hidden" name="color" value="">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Este material no maneja variantes de color.
                            </div>
                        @endif
                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-warehouse"></i> Configuración de Inventario
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Stock Mínimo (Alerta) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="min_stock_alert"
                                    class="form-control @error('min_stock_alert') is-invalid @enderror"
                                    value="{{ old('min_stock_alert', 0) }}" min="0" max="999999" step="0.01"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        {{ $material->baseUnit->symbol ?? 'unidad' }}
                                    </span>
                                </div>
                                @error('min_stock_alert')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Se alertará cuando el stock sea igual o menor a este valor
                            </small>
                        </div>

                        {{-- INFO --}}
                        <div class="card bg-light mt-4">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="fas fa-info-circle text-info"></i> Información</h6>
                                <small>
                                    <strong>Material:</strong> {{ $material->name }}<br>
                                    <strong>Categoría:</strong> {{ $material->category->name ?? 'N/A' }}<br>
                                    <strong>Unidad:</strong> {{ $material->baseUnit->name ?? 'N/A' }}
                                    ({{ $material->baseUnit->symbol ?? '' }})<br>
                                    @if ($material->composition)
                                        <strong>Composición:</strong> {{ $material->composition }}
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Nota:</strong> El stock inicial será 0.
                            Use el módulo de <strong>Compras</strong> para registrar entradas de inventario.
                        </div>
                    </div>
                </div>


            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            // Convertir SKU a mayúsculas en tiempo real
            $('input[name="sku"]').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            });

            // Evitar doble submit y mostrar loading
            $('#variant-form').on('submit', function() {
                var $btnSave = $('#btn-save-variant');
                var $btnBack = $('#btn-back');

                $btnSave.prop('disabled', true);
                $btnBack.addClass('disabled');
                $btnSave.html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            });
        });
    </script>
@stop
