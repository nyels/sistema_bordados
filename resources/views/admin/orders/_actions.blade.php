@php
    $hasAdjustments = $order->hasPendingAdjustments();
    $hasDesignPending = $order->hasItemsPendingDesignApproval();
    $hasMeasurementsChanged = $order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $hasBlockers = $hasAdjustments || $hasDesignPending || $hasMeasurementsChanged;
    $canStartProduction = $order->status === \App\Models\Order::STATUS_CONFIRMED && !$hasBlockers;
@endphp

@if(!in_array($order->status, [\App\Models\Order::STATUS_CANCELLED, \App\Models\Order::STATUS_DELIVERED]))
    <div class="card">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="fas fa-bolt mr-1"></i> Acciones</h6>
        </div>
        <div class="card-body">
            @if($order->status === \App\Models\Order::STATUS_DRAFT)
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-info btn-block">
                        <i class="fas fa-check mr-1"></i> Confirmar Pedido
                    </button>
                </form>
            @endif

            @if($order->status === \App\Models\Order::STATUS_CONFIRMED)
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_production">
                    <button type="submit" class="btn btn-warning btn-block" {{ $canStartProduction ? '' : 'disabled' }}>
                        <i class="fas fa-cogs mr-1"></i> Iniciar Produccion
                    </button>
                </form>
                @if(!$canStartProduction)
                    <small class="text-danger d-block text-center">
                        <i class="fas fa-info-circle mr-1"></i> Resuelva los bloqueos primero
                    </small>
                @endif
            @endif

            @if($order->status === \App\Models\Order::STATUS_IN_PRODUCTION)
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="ready">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-box mr-1"></i> Marcar Listo
                    </button>
                </form>
            @endif

            @if($order->status === \App\Models\Order::STATUS_READY)
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="delivered">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-truck mr-1"></i> Registrar Entrega
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
