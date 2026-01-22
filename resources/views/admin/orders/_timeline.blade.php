@php
    $states = [
        ['key' => 'draft', 'label' => 'Borrador', 'icon' => 'fas fa-file-alt'],
        ['key' => 'confirmed', 'label' => 'Confirmado', 'icon' => 'fas fa-check'],
        ['key' => 'in_production', 'label' => 'Produccion', 'icon' => 'fas fa-cogs'],
        ['key' => 'ready', 'label' => 'Listo', 'icon' => 'fas fa-box'],
        ['key' => 'delivered', 'label' => 'Entregado', 'icon' => 'fas fa-truck'],
    ];
    $currentIndex = collect($states)->search(fn($s) => $s['key'] === $order->status);
    if ($currentIndex === false) $currentIndex = -1;
    $isCancelled = $order->status === \App\Models\Order::STATUS_CANCELLED;
    $isDelivered = $order->status === \App\Models\Order::STATUS_DELIVERED;
    $totalSteps = count($states) - 1;
@endphp

<style>
    .order-timeline-card {
        overflow: hidden;
    }
    .order-timeline-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        width: 100%;
        box-sizing: border-box;
    }
    .order-timeline-step {
        flex: 1 1 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        min-width: 0;
    }
    /* Linea base gris ENTRE nodos (::after en cada step excepto ultimo) */
    .order-timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 50%;
        width: 100%;
        height: 3px;
        background: #dee2e6;
        z-index: 0;
    }
    /* Linea de progreso verde (::before en steps completados) */
    .order-timeline-step.step-completed:not(:last-child)::before {
        content: '';
        position: absolute;
        top: 18px;
        left: 50%;
        width: 100%;
        height: 3px;
        background: #28a745;
        z-index: 1;
    }
    /* Cuando delivered, el ultimo nodo tambien tiene linea verde entrando */
    .order-timeline-step.step-final::before {
        content: '';
        position: absolute;
        top: 18px;
        right: 50%;
        width: 50%;
        height: 3px;
        background: #28a745;
        z-index: 1;
    }
    .order-timeline-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #adb5bd;
        background: #f8f9fa;
        color: #6c757d;
        font-size: 14px;
        flex-shrink: 0;
        position: relative;
        z-index: 3;
    }
    .order-timeline-icon.icon-past {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
    .order-timeline-icon.icon-active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    .order-timeline-icon.icon-final {
        background: #28a745;
        color: white;
        border-color: #28a745;
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.25);
    }
    .order-timeline-label {
        font-size: 14px;
        margin-top: 8px;
        text-align: center;
        color: #495057;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding: 0 4px;
    }
    .order-timeline-label.label-past {
        color: #28a745;
        font-weight: 500;
    }
    .order-timeline-label.label-active {
        color: #007bff;
        font-weight: 700;
    }
    .order-timeline-label.label-final {
        color: #28a745;
        font-weight: 700;
    }
    @media (max-width: 480px) {
        .order-timeline-icon {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }
        .order-timeline-step:not(:last-child)::after,
        .order-timeline-step.step-completed:not(:last-child)::before {
            top: 14px;
        }
        .order-timeline-step.step-final::before {
            top: 14px;
        }
        .order-timeline-label {
            font-size: 12px;
        }
    }
</style>

<div class="card mb-3 order-timeline-card">
    <div class="card-body py-3">
        @if($isCancelled)
            <div class="text-center">
                <span class="badge badge-danger p-2" style="font-size: 16px;">
                    <i class="fas fa-ban mr-1"></i> PEDIDO CANCELADO
                </span>
            </div>
        @else
            <div class="order-timeline-container">
                @foreach($states as $index => $state)
                    @php
                        $isActive = $state['key'] === $order->status;
                        $isPast = $index < $currentIndex;
                        $isFinal = $isDelivered && $index === $totalSteps;

                        // Step classes
                        $stepClasses = [];
                        if ($isPast) $stepClasses[] = 'step-completed';
                        if ($isFinal) $stepClasses[] = 'step-final';

                        // Icon classes
                        $iconClass = 'icon-future';
                        if ($isFinal) $iconClass = 'icon-final';
                        elseif ($isPast) $iconClass = 'icon-past';
                        elseif ($isActive) $iconClass = 'icon-active';

                        // Label classes
                        $labelClass = '';
                        if ($isFinal) $labelClass = 'label-final';
                        elseif ($isPast) $labelClass = 'label-past';
                        elseif ($isActive) $labelClass = 'label-active';
                    @endphp
                    <div class="order-timeline-step {{ implode(' ', $stepClasses) }}">
                        <div class="order-timeline-icon {{ $iconClass }}">
                            @if($isFinal)
                                <i class="fas fa-check"></i>
                            @else
                                <i class="{{ $state['icon'] }}"></i>
                            @endif
                        </div>
                        <div class="order-timeline-label {{ $labelClass }}">
                            {{ $state['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
