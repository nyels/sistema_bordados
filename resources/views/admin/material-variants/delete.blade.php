@extends('adminlte::page')

@section('title', 'Eliminar Variante - ' . $variant->sku)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- BREADCRUMB INFO --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('materials.index') }}">
                            <i class="fas fa-boxes"></i> Materiales
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('material-variants.index', $material->id) }}">
                            {{ $material->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Eliminar: {{ $variant->sku }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-trash"></i> ELIMINAR VARIANTE: {{ $variant->sku }}
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('material-variants.destroy', [$material->id, $variant->id]) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #dc3545; font-weight: 600;">
                                <i class="fas fa-barcode"></i> Identificación
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Material</label>
                            <input type="text" class="form-control" value="{{ $material->name }}" disabled>
                        </div>

                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" class="form-control font-weight-bold" value="{{ $variant->sku }}"
                                disabled>
                        </div>

                        <div class="form-group">
                            <label>Color</label>
                            <input type="text" class="form-control" value="{{ $variant->color ?? '-' }}" disabled>
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #6c757d; font-weight: 600;">
                                <i class="fas fa-warehouse"></i> Estado del Inventario
                            </h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Stock Actual</label>
                                    <input type="text" class="form-control"
                                        value="{{ number_format($variant->current_stock, 2) }} {{ $material->category->baseUnit->symbol ?? '' }}"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Valor Total</label>
                                    <input type="text" class="form-control"
                                        value="${{ number_format($variant->current_value, 2) }}" disabled>
                                </div>
                            </div>
                        </div>

                        @if ($variant->current_stock > 0)
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>No se puede eliminar.</strong><br>
                                Esta variante tiene stock disponible ({{ number_format($variant->current_stock, 2) }}
                                {{ $material->category->baseUnit->symbol ?? '' }}).
                                <br><br>
                                Debe consumir o ajustar el inventario antes de eliminar.
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Advertencia:</strong> Esta acción desactivará la variante.
                                El historial de movimientos se conservará para auditoría.
                            </div>
                        @endif
                    </div>
                </div>

                <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 20px;">
                    ¿Deseas eliminar esta variante?
                </div>

                <div class="text-center">
                    <a href="{{ route('material-variants.index', $material->id) }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    @if ($variant->current_stock <= 0)
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @else
                        <button type="button" class="btn btn-danger" disabled title="Tiene stock disponible">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@stop
