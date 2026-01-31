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
    {{-- COLUMNA IZQUIERDA: Material + Advertencia --}}
    <div class="col-md-4">
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

        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle mr-1"></i> Advertencia</h6>
            <small>
                Los ajustes de inventario son operaciones sensibles que afectan directamente
                el valor contable del inventario. Solo realice ajustes cuando sea absolutamente
                necesario y documente el motivo claramente.
            </small>
        </div>
    </div>

    {{-- COLUMNA DERECHA: Formulario de Ajuste --}}
    <div class="col-md-8">
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

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Importante:</strong> Los ajustes de inventario quedan registrados permanentemente para auditoria.
                    </div>

                    {{-- TIPO DE AJUSTE --}}
                    <div class="form-group">
                        <label>Tipo de Ajuste <span class="text-danger">*</span></label>
                        <input type="hidden" name="type" id="type-input" value="{{ old('type', 'positive') }}">
                        <div class="d-flex">
                            <button type="button" id="btn-positive" class="btn flex-fill mr-1">
                                <i class="fas fa-plus mr-1"></i> Entrada (Ajuste Positivo)
                            </button>
                            <button type="button" id="btn-negative" class="btn flex-fill ml-1">
                                <i class="fas fa-minus mr-1"></i> Salida (Ajuste Negativo)
                            </button>
                        </div>
                        @error('type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        {{-- CANTIDAD --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cantidad <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                                           value="{{ old('quantity') }}" step="0.0001" min="0.0001" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ $variant->material?->consumptionUnit?->symbol ?? $variant->material?->baseUnit?->symbol ?? 'u' }}</span>
                                    </div>
                                </div>
                                @error('quantity')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        {{-- COSTO UNITARIO --}}
                        <div class="col-md-6">
                            <div class="form-group" id="cost-group">
                                <label>Costo Unitario <span class="text-danger" id="cost-required">*</span></label>
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

                    {{-- MOTIVO --}}
                    <div class="form-group">
                        <label>Motivo del Ajuste <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3" minlength="10" maxlength="500" required
                                  placeholder="Describa el motivo del ajuste (minimo 10 caracteres). Ej: Conteo fisico detecta diferencia, Merma por dano, etc.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-secondary mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Registrar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function() {
    var $typeInput = $('#type-input');
    var $btnPositive = $('#btn-positive');
    var $btnNegative = $('#btn-negative');
    var $costGroup = $('#cost-group');
    var $costInput = $('#unit_cost');
    var $costRequired = $('#cost-required');

    function setType(type) {
        $typeInput.val(type);

        if (type === 'positive') {
            $btnPositive.removeClass('btn-outline-success').addClass('btn-success');
            $btnNegative.removeClass('btn-danger').addClass('btn-outline-danger');
            $costGroup.css('opacity', '1');
            $costInput.prop('disabled', false);
            $costRequired.show();
        } else {
            $btnPositive.removeClass('btn-success').addClass('btn-outline-success');
            $btnNegative.removeClass('btn-outline-danger').addClass('btn-danger');
            $costGroup.css('opacity', '0.5');
            $costInput.prop('disabled', true);
            $costRequired.hide();
        }
    }

    $btnPositive.on('click', function() {
        setType('positive');
    });

    $btnNegative.on('click', function() {
        setType('negative');
    });

    // Inicializar con el valor actual
    setType($typeInput.val());
});
</script>
@stop
