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
@endphp

<div class="card mb-3">
    <div class="card-body py-3">
        @if($isCancelled)
            <div class="text-center">
                <span class="badge badge-danger p-2" style="font-size: 16px;">
                    <i class="fas fa-ban mr-1"></i> PEDIDO CANCELADO
                </span>
            </div>
        @else
            <div class="d-flex justify-content-between position-relative" style="padding: 0 20px;">
                <div style="position: absolute; top: 18px; left: 40px; right: 40px; height: 3px; background: #dee2e6; z-index: 0;"></div>
                @if($currentIndex >= 0)
                    <div style="position: absolute; top: 18px; left: 40px; height: 3px; background: #28a745; z-index: 1; width: {{ $currentIndex > 0 ? (($currentIndex / (count($states) - 1)) * 100) : 0 }}%;"></div>
                @endif

                @foreach($states as $index => $state)
                    @php
                        $isActive = $state['key'] === $order->status;
                        $isPast = $index < $currentIndex;
                        $bgClass = $isPast ? 'bg-success text-white' : ($isActive ? 'bg-primary text-white' : 'bg-light text-muted');
                    @endphp
                    <div class="text-center" style="z-index: 2; flex: 1;">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle {{ $bgClass }}"
                             style="width: 36px; height: 36px; border: 2px solid {{ $isPast ? '#28a745' : ($isActive ? '#007bff' : '#adb5bd') }};">
                            <i class="{{ $state['icon'] }}" style="font-size: 14px;"></i>
                        </div>
                        <div class="mt-1">
                            <small class="{{ $isActive ? 'font-weight-bold text-primary' : ($isPast ? 'text-success' : 'text-muted') }}" style="font-size: 12px;">
                                {{ $state['label'] }}
                            </small>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
