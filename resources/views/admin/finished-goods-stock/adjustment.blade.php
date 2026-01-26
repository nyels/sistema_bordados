@extends('adminlte::page')

@section('title', 'Ajuste PT - ' . $productVariant->sku_variant)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-balance-scale mr-2"></i>Ajuste de Inventario PT</h1>
            <small class="text-muted">{{ $productVariant->product->name ?? 'N/A' }} - <code>{{ $productVariant->sku_variant }}</code></small>
        </div>
        <a href="{{ route('admin.finished-goods-stock.kardex', $productVariant->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Kardex
        </a>
    </div>
@stop

@section('content')
    {{-- MENSAJES --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            {{-- CARD INFO ACTUAL --}}
            <div class="card card-outline card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Informacion Actual</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th style="width: 40%;">Producto:</th>
                            <td>{{ $productVariant->product->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>SKU Variante:</th>
                            <td><code>{{ $productVariant->sku_variant }}</code></td>
                        </tr>
                        <tr>
                            <th>Categoria:</th>
                            <td>{{ $productVariant->product->category->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Stock Sistema (Ledger):</th>
                            <td>
                                <span class="badge badge-primary" style="font-size: 16px;">
                                    {{ number_format($currentStock, 2) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- CARD FORMULARIO --}}
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Registrar Ajuste</h3>
                </div>
                <form action="{{ route('admin.finished-goods-stock.adjustment.store', $productVariant->id) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>ATENCION:</strong> Este ajuste se registrara en el historial (Kardex) y afectara el stock actual.
                            La diferencia entre el stock fisico y el stock del sistema se registrara como movimiento de ajuste.
                        </div>

                        <div class="form-group">
                            <label for="physical_stock">
                                <i class="fas fa-boxes mr-1"></i> Stock Fisico Contado <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   name="physical_stock"
                                   id="physical_stock"
                                   class="form-control form-control-lg @error('physical_stock') is-invalid @enderror"
                                   min="0"
                                   step="0.01"
                                   value="{{ old('physical_stock', $currentStock) }}"
                                   required
                                   autofocus>
                            @error('physical_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Ingrese la cantidad real contada fisicamente.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="adjustment_reason">
                                <i class="fas fa-comment mr-1"></i> Motivo del Ajuste <span class="text-danger">*</span>
                            </label>
                            <textarea name="adjustment_reason"
                                      id="adjustment_reason"
                                      class="form-control @error('adjustment_reason') is-invalid @enderror"
                                      rows="3"
                                      minlength="10"
                                      maxlength="255"
                                      placeholder="Describa el motivo del ajuste (minimo 10 caracteres)..."
                                      required>{{ old('adjustment_reason') }}</textarea>
                            @error('adjustment_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Ejemplos: "Conteo fisico semanal", "Correccion por merma", "Ajuste por inventario anual"
                            </small>
                        </div>

                        {{-- PREVIEW DIFERENCIA --}}
                        <div id="preview-box" class="alert alert-secondary" style="display: none;">
                            <strong>Vista previa:</strong><br>
                            <span id="preview-text"></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i> Registrar Ajuste
                        </button>
                        <a href="{{ route('admin.finished-goods-stock.kardex', $productVariant->id) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            {{-- CARD REGLAS --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book mr-1"></i> Reglas de Ajuste</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>El ajuste crea un movimiento tipo <code>adjustment</code> en el Kardex.</li>
                        <li>Si el stock fisico es <strong>mayor</strong> al sistema, se registra ajuste <span class="text-success">positivo</span>.</li>
                        <li>Si el stock fisico es <strong>menor</strong> al sistema, se registra ajuste <span class="text-danger">negativo</span>.</li>
                        <li>Si son iguales, no se crea movimiento.</li>
                        <li>El motivo es <strong>obligatorio</strong> (min. 10 caracteres).</li>
                        <li>El usuario y fecha/hora se registran automaticamente.</li>
                        <li><strong>No se permite editar ni eliminar movimientos.</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const physicalInput = document.getElementById('physical_stock');
    const previewBox = document.getElementById('preview-box');
    const previewText = document.getElementById('preview-text');
    const currentStock = {{ $currentStock }};

    function updatePreview() {
        const physical = parseFloat(physicalInput.value) || 0;
        const diff = physical - currentStock;

        if (Math.abs(diff) < 0.001) {
            previewBox.style.display = 'none';
            return;
        }

        previewBox.style.display = 'block';

        if (diff > 0) {
            previewBox.className = 'alert alert-success';
            previewText.innerHTML = `Ajuste <strong>POSITIVO</strong>: +${diff.toFixed(2)} unidades (faltante en sistema)`;
        } else {
            previewBox.className = 'alert alert-danger';
            previewText.innerHTML = `Ajuste <strong>NEGATIVO</strong>: ${diff.toFixed(2)} unidades (sobrante en sistema)`;
        }
    }

    physicalInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>
@stop
