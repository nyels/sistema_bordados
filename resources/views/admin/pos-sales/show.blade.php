@extends('adminlte::page')

@section('title', 'Detalle Venta POS')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-receipt mr-2"></i> Venta POS: {{ $order->order_number }}</h1>
        <a href="{{ route('admin.pos-sales.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Historial
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- COLUMNA IZQUIERDA: DATOS DE LA VENTA --}}
        <div class="col-md-8">
            {{-- CARD PRINCIPAL --}}
            <div class="card {{ $order->isCancelled() ? 'card-danger' : 'card-success' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        @if ($order->isCancelled())
                            <i class="fas fa-ban mr-2"></i>VENTA CANCELADA
                        @else
                            <i class="fas fa-check-circle mr-2"></i>VENTA COMPLETADA
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt><i class="fas fa-hashtag mr-1"></i> Pedido</dt>
                                <dd class="h4">{{ $order->order_number }}</dd>

                                <dt><i class="fas fa-calendar mr-1"></i> Fecha de Venta</dt>
                                <dd>{{ $order->delivered_date?->format('d/m/Y H:i') ?? 'N/A' }}</dd>

                                <dt><i class="fas fa-user mr-1"></i> Vendedor</dt>
                                <dd>{{ $order->creator?->name ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl>
                                <dt><i class="fas fa-dollar-sign mr-1"></i> Subtotal</dt>
                                <dd>${{ number_format($order->subtotal, 2) }}</dd>

                                @if ($order->discount > 0)
                                    <dt><i class="fas fa-tags mr-1"></i> Descuento</dt>
                                    <dd class="text-danger">-${{ number_format($order->discount, 2) }}</dd>
                                @endif

                                @if ($order->iva_amount > 0)
                                    <dt><i class="fas fa-percent mr-1"></i> IVA ({{ $order->iva_rate }}%)</dt>
                                    <dd>${{ number_format($order->iva_amount, 2) }}</dd>
                                @endif

                                <dt><i class="fas fa-receipt mr-1"></i> TOTAL</dt>
                                <dd class="h3 {{ $order->isCancelled() ? 'text-danger' : 'text-success' }}">
                                    ${{ number_format($order->total, 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETALLE DEL PRODUCTO --}}
            @if ($movement)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-box mr-2"></i>Producto Vendido</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            @if ($movement->productVariant && $movement->productVariant->product)
                                <div class="col-md-2">
                                    @if ($movement->productVariant->product->primary_image_url)
                                        <img src="{{ $movement->productVariant->product->primary_image_url }}"
                                             class="img-fluid rounded" alt="Producto">
                                    @else
                                        <div class="bg-secondary text-white text-center p-4 rounded">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-1">{{ $movement->productVariant->product->name }}</h5>
                                    <p class="text-muted mb-0">
                                        SKU: {{ $movement->productVariant->sku_variant ?? 'N/A' }}
                                    </p>
                                    @if ($movement->productVariant->attributes_display)
                                        <p class="text-muted mb-0">
                                            {{ $movement->productVariant->attributes_display }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div class="col-md-8">
                                    <p class="text-muted mb-0">Producto ID: {{ $movement->product_variant_id }}</p>
                                </div>
                            @endif
                            <div class="col-md-4 text-right">
                                <p class="mb-1"><strong>Cantidad:</strong> {{ $movement->quantity }}</p>
                                <p class="mb-1">
                                    <strong>Stock antes:</strong>
                                    <span class="badge badge-info">{{ $movement->stock_before }}</span>
                                </p>
                                <p class="mb-0">
                                    <strong>Stock despues:</strong>
                                    <span class="badge badge-primary">{{ $movement->stock_after }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- NOTAS DE LA VENTA --}}
            @if ($order->notes)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Notas de la Venta</h3>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 bg-light p-3 rounded">{{ $order->notes }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- COLUMNA DERECHA: ESTADO Y CANCELACION --}}
        <div class="col-md-4">
            {{-- ESTADO --}}
            <div class="card {{ $order->isCancelled() ? 'card-danger' : 'card-success' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Estado
                    </h3>
                </div>
                <div class="card-body text-center">
                    @if ($order->isCancelled())
                        <i class="fas fa-ban fa-4x text-danger mb-3"></i>
                        <h4 class="text-danger">CANCELADA</h4>
                    @else
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="text-success">COMPLETADA</h4>
                    @endif
                </div>
            </div>

            {{-- INFO DE CANCELACION --}}
            @if ($order->isCancelled())
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Datos de Cancelacion
                        </h3>
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt><i class="fas fa-calendar-times mr-1"></i> Fecha Cancelacion</dt>
                            <dd>{{ $order->cancelled_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</dd>

                            <dt><i class="fas fa-user-slash mr-1"></i> Cancelado por</dt>
                            <dd>{{ $order->canceller?->name ?? 'N/A' }}</dd>

                            <dt><i class="fas fa-comment-alt mr-1"></i> Motivo</dt>
                            <dd class="alert alert-warning mb-0">
                                {{ $order->cancel_reason ?? 'Sin motivo especificado' }}
                            </dd>
                        </dl>

                        {{-- DEVOLUCION DE STOCK --}}
                        @if ($returnMovement)
                            <hr>
                            <h6 class="text-danger"><i class="fas fa-undo mr-1"></i> Stock Revertido</h6>
                            <p class="mb-1">
                                <strong>Cantidad devuelta:</strong> {{ $returnMovement->quantity }}
                            </p>
                            <p class="mb-0">
                                <strong>Stock resultante:</strong>
                                <span class="badge badge-success">{{ $returnMovement->stock_after }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            @else
                {{-- BOTON CANCELAR (solo si no esta cancelada) --}}
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tools mr-2"></i>Acciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <button type="button"
                                class="btn btn-danger btn-block btn-cancel-sale"
                                data-order-id="{{ $order->id }}"
                                data-order-number="{{ $order->order_number }}"
                                data-order-date="{{ $order->delivered_date?->format('d/m/Y H:i') }}"
                                data-order-total="{{ number_format($order->total, 2) }}"
                                data-order-seller="{{ $order->creator?->name ?? 'N/A' }}">
                            <i class="fas fa-ban mr-2"></i>Cancelar esta Venta
                        </button>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                            Cancelar revierte el inventario de productos terminados.
                        </small>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DE CANCELACION (reutilizado) --}}
    @if (!$order->isCancelled())
        <div class="modal fade" id="modalCancelSale" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Cancelar Venta POS
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>ADVERTENCIA:</strong> Cancelar una venta POS revierte el inventario de productos terminados.
                        </div>

                        <div class="bg-light p-3 rounded mb-3">
                            <p class="mb-1"><strong>Pedido:</strong> <span id="cancel-order-number">{{ $order->order_number }}</span></p>
                            <p class="mb-1"><strong>Fecha:</strong> <span id="cancel-order-date">{{ $order->delivered_date?->format('d/m/Y H:i') }}</span></p>
                            <p class="mb-1"><strong>Total:</strong> $<span id="cancel-order-total">{{ number_format($order->total, 2) }}</span></p>
                            <p class="mb-0"><strong>Vendedor:</strong> <span id="cancel-order-seller">{{ $order->creator?->name ?? 'N/A' }}</span></p>
                        </div>

                        <input type="hidden" id="cancel-order-id" value="{{ $order->id }}">

                        <div class="form-group">
                            <label for="cancel-reason">
                                Motivo de Cancelacion <span class="text-danger">*</span>
                                <small class="text-muted">(minimo 10 caracteres)</small>
                            </label>
                            <textarea class="form-control" id="cancel-reason" rows="3" maxlength="255"
                                placeholder="Explique el motivo de la cancelacion..."></textarea>
                            <small class="text-muted"><span id="cancel-reason-count">0</span>/255</small>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="cancel-confirm-checkbox">
                            <label class="custom-control-label text-danger" for="cancel-confirm-checkbox">
                                Entiendo que esta accion revertira el stock y no puede deshacerse.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-danger" id="btn-execute-cancel" disabled>
                            <i class="fas fa-ban mr-1"></i> Cancelar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('js')
    @if (!$order->isCancelled())
        <script>
            (function() {
                'use strict';

                document.addEventListener('DOMContentLoaded', function() {
                    // Abrir modal
                    document.querySelector('.btn-cancel-sale').addEventListener('click', function() {
                        $('#modalCancelSale').modal('show');
                    });

                    // Logica de cancelacion
                    var cancelReason = document.getElementById('cancel-reason');
                    var cancelReasonCount = document.getElementById('cancel-reason-count');
                    var cancelConfirm = document.getElementById('cancel-confirm-checkbox');
                    var btnExecute = document.getElementById('btn-execute-cancel');

                    function updateCancelButtonState() {
                        var hasReason = cancelReason.value.length >= 10;
                        var hasConfirm = cancelConfirm.checked;
                        btnExecute.disabled = !(hasReason && hasConfirm);
                    }

                    cancelReason.addEventListener('input', function() {
                        cancelReasonCount.textContent = this.value.length;
                        updateCancelButtonState();
                    });

                    cancelConfirm.addEventListener('change', updateCancelButtonState);

                    btnExecute.addEventListener('click', async function() {
                        var orderId = document.getElementById('cancel-order-id').value;
                        var reason = cancelReason.value;

                        if (!orderId || reason.length < 10 || !cancelConfirm.checked) {
                            return;
                        }

                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';

                        try {
                            var response = await fetch('/admin/pos-sales/' + orderId + '/cancel', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    cancel_reason: reason
                                })
                            });

                            var data = await response.json();

                            $('#modalCancelSale').modal('hide');

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Venta cancelada',
                                    text: 'Stock revertido: ' + data.data.quantity_returned + ' unidades',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error,
                                    confirmButtonColor: '#3085d6'
                                });
                            }

                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: error.message,
                                confirmButtonColor: '#3085d6'
                            });
                        }

                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-ban mr-1"></i> Cancelar Venta';
                    });
                });
            })();
        </script>
    @endif
@stop
