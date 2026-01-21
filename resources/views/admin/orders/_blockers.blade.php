@php
    $hasAdjustments = $order->hasPendingAdjustments();
    $hasDesignPending = $order->hasItemsPendingDesignApproval();
    $hasMeasurementsChanged = $order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $hasBlockers = $hasAdjustments || $hasDesignPending || $hasMeasurementsChanged;
    $itemsWithDesign = $order->getItemsBlockingForDesign();
    $itemsWithMeasurements = $order->items->filter(fn($i) => $i->hasMeasurementsChangedAfterApproval());
@endphp

@if($hasBlockers)
    <div class="card border-danger mb-3">
        <div class="card-header bg-danger text-white py-2">
            <i class="fas fa-hand-paper mr-2"></i>
            <strong>Este pedido NO puede iniciar producción</strong>
        </div>
        <div class="card-body" style="font-size: 14px;">
            <p class="mb-2 text-muted">Resuelva los siguientes puntos antes de enviar a producción:</p>

            @if($hasAdjustments)
                <div class="d-flex align-items-start mb-3 p-2 bg-light rounded">
                    <span class="badge badge-danger mr-2 mt-1">1</span>
                    <div>
                        <strong class="text-danger">Ajuste de precio pendiente</strong>
                        <p class="mb-1 text-muted small">
                            Hay cambios en el precio que el cliente debe aprobar antes de continuar.
                        </p>
                        <span class="text-primary small">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> Contactar al cliente para aprobar el nuevo precio.
                        </span>
                    </div>
                </div>
            @endif

            @if($hasDesignPending)
                <div class="d-flex align-items-start mb-3 p-2 bg-light rounded">
                    <span class="badge badge-warning mr-2 mt-1">{{ $hasAdjustments ? '2' : '1' }}</span>
                    <div>
                        <strong class="text-warning">Diseño pendiente de aprobación</strong>
                        <p class="mb-1 text-muted small">
                            El cliente debe aprobar el diseño de los siguientes productos:
                        </p>
                        <div class="mb-2">
                            @foreach($itemsWithDesign as $item)
                                <span class="badge badge-secondary mr-1">{{ $item->product_name }}</span>
                            @endforeach
                        </div>
                        <span class="text-primary small">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> Ir a la sección "Diseño/Personalización" más abajo para gestionar.
                        </span>
                    </div>
                </div>
            @endif

            @if($hasMeasurementsChanged)
                @php $stepNum = ($hasAdjustments ? 1 : 0) + ($hasDesignPending ? 1 : 0) + 1; @endphp
                <div class="d-flex align-items-start mb-3 p-2 bg-light rounded">
                    <span class="badge badge-info mr-2 mt-1">{{ $stepNum }}</span>
                    <div>
                        <strong class="text-info">Medidas modificadas después de aprobar diseño</strong>
                        <p class="mb-1 text-muted small">
                            Las medidas del cliente cambiaron después de aprobar el diseño en:
                        </p>
                        <div class="mb-2">
                            @foreach($itemsWithMeasurements as $item)
                                <span class="badge badge-secondary mr-1">{{ $item->product_name }}</span>
                            @endforeach
                        </div>
                        <span class="text-primary small">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> El diseño debe re-aprobarse con las nuevas medidas.
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="alert alert-success mb-3" style="font-size: 14px;">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
            <div>
                <strong>Listo para producción</strong>
                <p class="mb-0 small text-muted">
                    Todos los requisitos cumplidos. Puede enviar este pedido a producción desde la
                    <a href="{{ route('admin.production.queue') }}">Cola de Producción</a>.
                </p>
            </div>
        </div>
    </div>
@endif
