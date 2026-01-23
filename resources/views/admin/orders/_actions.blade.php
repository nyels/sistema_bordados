@php
    use App\Models\Order;
    $hasAdjustments = $order->hasPendingAdjustments();
    $hasDesignPending = $order->hasItemsPendingDesignApproval();
    $hasMeasurementsChanged = $order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $hasMissingTechnicalDesigns = $order->hasItemsMissingTechnicalDesigns();
    $hasBlockers = $hasAdjustments || $hasDesignPending || $hasMeasurementsChanged || $hasMissingTechnicalDesigns;
    $canStartProduction = $order->status === Order::STATUS_CONFIRMED && !$hasBlockers;
@endphp

@if(!in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED]))
    <div class="card">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="fas fa-bolt mr-1"></i> Acciones</h6>
        </div>
        <div class="card-body">

            {{-- ================================================================ --}}
            {{-- DRAFT: Puede confirmar --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_DRAFT)
                <div class="alert alert-info py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-edit mr-1"></i>
                    <strong>Borrador:</strong> Puede editar libremente productos, cantidades y precios.
                </div>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-info btn-block">
                        <i class="fas fa-check mr-1"></i> Confirmar Pedido
                    </button>
                </form>
                <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                    Al confirmar, el pedido queda listo para revisión de diseño y producción.
                </span>
            @endif

            {{-- ================================================================ --}}
            {{-- CONFIRMED: Puede iniciar producción (con validaciones) --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_CONFIRMED)
                {{-- Advertencia de congelamiento --}}
                <div class="mb-3 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffc107;">
                    <div style="font-size: 14px; color: #856404;">
                        <i class="fas fa-lock mr-1"></i> <strong>Al iniciar producción:</strong>
                    </div>
                    <ul class="mb-0 pl-3 mt-1" style="font-size: 14px; color: #856404;">
                        <li>El pedido queda <strong>CONGELADO</strong></li>
                        <li>Productos, medidas y diseños se sellan</li>
                        <li>Materiales se reservan del inventario</li>
                        <li><strong>NO</strong> se permiten cambios posteriores</li>
                    </ul>
                </div>

                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_production">
                    <button type="submit" class="btn btn-warning btn-block" {{ $canStartProduction ? '' : 'disabled' }}
                            title="Esta acción sella el pedido para producción">
                        <i class="fas fa-cogs mr-1"></i> Iniciar Producción
                    </button>
                </form>

                @if($canStartProduction)
                    <span style="font-size: 14px; color: #28a745;" class="d-block text-center mb-2">
                        <i class="fas fa-check-circle mr-1"></i> Pedido listo para producción
                    </span>
                @else
                    <span style="font-size: 14px; color: #c62828;" class="d-block text-center">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Resuelva los bloqueos primero (ver abajo)
                    </span>
                @endif
            @endif

            {{-- ================================================================ --}}
            {{-- IN_PRODUCTION: Puede marcar listo --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_IN_PRODUCTION)
                <div class="alert alert-warning py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-industry mr-1"></i>
                    <strong>En Producción:</strong>
                    <ul class="mb-0 pl-3 mt-1">
                        <li>Pedido congelado - sin modificaciones</li>
                        <li>Materiales reservados en inventario</li>
                        <li><strong>NO</strong> se permiten anexos ni extras</li>
                    </ul>
                </div>

                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="ready">
                    <button type="submit" class="btn btn-success btn-block"
                            data-confirm="ready"
                            data-confirm-title="¿Marcar pedido como LISTO?"
                            data-confirm-text="Esta acción consume los materiales reservados del inventario."
                            data-confirm-impact="El inventario será descontado permanentemente.">
                        <i class="fas fa-box mr-1"></i> Marcar Listo
                    </button>
                </form>
                <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                    <i class="fas fa-info-circle mr-1"></i> Esto consumirá los materiales del inventario
                </span>
            @endif

            {{-- ================================================================ --}}
            {{-- READY: Puede entregar --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_READY)
                <div class="alert alert-success py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-check-circle mr-1"></i>
                    <strong>Listo para Entrega:</strong>
                    <ul class="mb-0 pl-3 mt-1">
                        <li>Producción completada</li>
                        <li>Materiales consumidos</li>
                        <li>NO se permiten anexos</li>
                    </ul>
                </div>

                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="delivered">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-truck mr-1"></i> Registrar Entrega
                    </button>
                </form>
                <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                    <i class="fas fa-info-circle mr-1"></i> Cierra el ciclo del pedido
                </span>
            @endif

        </div>
    </div>
@endif
