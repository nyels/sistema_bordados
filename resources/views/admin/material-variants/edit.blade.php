@extends('adminlte::page')

@section('title', 'Editar Variante - ' . $variant->sku)

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
                    <li class="breadcrumb-item active">Editar: {{ $variant->sku }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-edit"></i> EDITAR VARIANTE: {{ $variant->sku }}
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.material-variants.update', [$material->id, $variant->id]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600;">
                                <i class="fas fa-barcode"></i> Identificación
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                                value="{{ old('sku', $variant->sku) }}" maxlength="30" style="text-transform: uppercase;"
                                required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($material->category->has_color ?? false)
                            <div class="form-group">
                                <label>Color</label>
                                <input type="text" name="color"
                                    class="form-control @error('color') is-invalid @enderror"
                                    value="{{ old('color', $variant->color) }}" maxlength="50">
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" name="color" value="">
                        @endif

                        <div class="form-group">
                            <label>Stock Mínimo (Alerta) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="min_stock_alert"
                                    class="form-control @error('min_stock_alert') is-invalid @enderror"
                                    value="{{ old('min_stock_alert', $variant->min_stock_alert) }}" min="0"
                                    max="999999" step="0.01" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        {{ $material->category->baseUnit->symbol ?? 'unidad' }}
                                    </span>
                                </div>
                                @error('min_stock_alert')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #17a2b8; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #17a2b8; font-weight: 600;">
                                <i class="fas fa-chart-bar"></i> Estado del Inventario
                            </h5>
                        </div>

                        {{-- INFO DE STOCK (Solo lectura) --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Stock Actual</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-weight-bold"
                                            value="{{ number_format($variant->current_stock, 2) }}" disabled>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                {{ $material->category->baseUnit->symbol ?? '' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Valor Total</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="text" class="form-control font-weight-bold"
                                            value="{{ number_format($variant->current_value, 2) }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Costo Promedio</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="{{ number_format($variant->average_cost, 2) }}" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Última Compra</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="text" class="form-control"
                                            value="{{ number_format($variant->last_purchase_cost, 2) }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- INFO --}}
                        <div class="card bg-light mt-2">
                            <div class="card-body py-2">
                                <small>
                                    <strong>UUID:</strong> <code>{{ $variant->uuid }}</code><br>
                                    <strong>Creado:</strong> {{ $variant->created_at->format('d/m/Y H:i') }}<br>
                                    <strong>Actualizado:</strong> {{ $variant->updated_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-info mt-2 mb-0">
                            <i class="fas fa-info-circle"></i>
                            <small>El stock y costos se actualizan desde el módulo de <strong>Compras</strong>.</small>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <a href="{{ route('admin.material-variants.index', $material->id) }}" class="btn btn-secondary">
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

@section('js')
    <script>
        $(function() {
            $('input[name="sku"]').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            });
        });
    </script>
@stop
