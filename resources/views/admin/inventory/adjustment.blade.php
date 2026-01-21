@extends('adminlte::page')

@section('title', 'Ajuste de Inventario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Ajuste Manual de Inventario</h1>
        <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Kardex
        </a>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        {{-- FORMULARIO --}}
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Registrar Ajuste</h5>
            </div>
            <form action="{{ route('admin.inventory.adjustment.store', $variant) }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Importante:</strong> Los ajustes de inventario quedan registrados permanentemente
                        para auditoria. Proporcione un motivo claro y detallado.
                    </div>

                    <div class="form-group">
                        <label>Tipo de Ajuste <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                            <label class="btn btn-outline-success flex-fill {{ old('type') == 'positive' ? 'active' : '' }}">
                                <input type="radio" name="type" value="positive" {{ old('type') == 'positive' ? 'checked' : '' }} required>
                                <i class="fas fa-plus mr-1"></i> Entrada (Ajuste Positivo)
                            </label>
                            <label class="btn btn-outline-danger flex-fill {{ old('type') == 'negative' ? 'active' : '' }}">
                                <input type="radio" name="type" value="negative" {{ old('type') == 'negative' ? 'checked' : '' }}>
                                <i class="fas fa-minus mr-1"></i> Salida (Ajuste Negativo)
                            </label>
                        </div>
                        @error('type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cantidad <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                           value="{{ old('quantity') }}" step="0.0001" min="0.0001" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ $variant->material?->baseUnit?->symbol ?? 'u' }}</span>
                                    </div>
                                </div>
                                @error('quantity')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="cost-group">
                                <label>Costo Unitario (solo para entradas)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" name="unit_cost" id="unit_cost" class="form-control @error('unit_cost') is-invalid @enderror"
                                           value="{{ old('unit_cost', $variant->average_cost) }}" step="0.0001" min="0">
                                </div>
                                <small class="text-muted">Costo promedio actual: ${{ number_format($variant->average_cost, 4) }}</small>
                                @error('unit_cost')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Motivo del Ajuste <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3" minlength="10" maxlength="500" required
                                  placeholder="Describa el motivo del ajuste (minimo 10 caracteres). Ej: Conteo fisico detecta diferencia, Merma por dano, etc.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="text-muted">Este texto quedara registrado permanentemente en el kardex.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Registrar Ajuste
                    </button>
                    <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        {{-- INFO DEL MATERIAL --}}
        <div class="card">
            <div class="card-header bg-info">
                <h5 class="mb-0 text-white"><i class="fas fa-info-circle mr-2"></i>Material</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Material:</dt>
                    <dd class="col-7">{{ $variant->material?->name }}</dd>

                    <dt class="col-5">SKU:</dt>
                    <dd class="col-7"><code>{{ $variant->sku }}</code></dd>

                    @if($variant->color)
                        <dt class="col-5">Color:</dt>
                        <dd class="col-7">{{ $variant->color }}</dd>
                    @endif

                    <dt class="col-5">Stock Fisico:</dt>
                    <dd class="col-7"><strong>{{ number_format($variant->current_stock, 2) }}</strong></dd>

                    <dt class="col-5">Reservado:</dt>
                    <dd class="col-7 text-warning">{{ number_format($variant->reserved_stock, 2) }}</dd>

                    <dt class="col-5">Disponible:</dt>
                    <dd class="col-7 text-success"><strong>{{ number_format($variant->available_stock, 2) }}</strong></dd>

                    <dt class="col-5">Costo Prom.:</dt>
                    <dd class="col-7">${{ number_format($variant->average_cost, 4) }}</dd>

                    <dt class="col-5">Valor Total:</dt>
                    <dd class="col-7"><strong>${{ number_format($variant->current_value, 2) }}</strong></dd>
                </dl>
            </div>
        </div>

        {{-- ADVERTENCIA --}}
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle mr-1"></i> Advertencia</h6>
            <small>
                Los ajustes de inventario son operaciones sensibles que afectan directamente
                el valor contable del inventario. Solo realice ajustes cuando sea absolutamente
                necesario y documente el motivo claramente.
            </small>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const costGroup = document.getElementById('cost-group');
    const costInput = document.getElementById('unit_cost');

    function toggleCost() {
        const selected = document.querySelector('input[name="type"]:checked');
        if (selected && selected.value === 'negative') {
            costGroup.style.opacity = '0.5';
            costInput.removeAttribute('required');
        } else {
            costGroup.style.opacity = '1';
            costInput.setAttribute('required', 'required');
        }
    }

    typeRadios.forEach(radio => radio.addEventListener('change', toggleCost));
    toggleCost();
});
</script>
@stop
