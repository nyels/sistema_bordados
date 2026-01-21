{{-- HISTORIAL DEL PEDIDO - Vista clara para el usuario --}}
@php
    $events = $order->events()->with('creator')->latest()->take(20)->get();
    $totalEvents = $order->events()->count();
@endphp

<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-stream mr-2"></i>
            Historial del Pedido
        </h5>
        @if($totalEvents > 0)
            <span class="badge badge-secondary" data-toggle="tooltip"
                  title="Registro automático de todas las acciones realizadas en este pedido">
                {{ $totalEvents }} {{ $totalEvents === 1 ? 'registro' : 'registros' }}
            </span>
        @endif
    </div>
    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
        @if($events->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                <p class="mb-0">Este pedido aún no tiene historial</p>
                <small>Las acciones importantes se registrarán aquí automáticamente</small>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach($events as $event)
                    <li class="list-group-item py-3">
                        <div class="d-flex">
                            {{-- Icono del evento --}}
                            <div class="mr-3 text-center" style="min-width: 40px;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $event->event_color }}"
                                      style="width: 36px; height: 36px;">
                                    <i class="{{ explode(' ', $event->event_icon)[0] }} {{ explode(' ', $event->event_icon)[1] ?? '' }}" style="color: white;"></i>
                                </span>
                            </div>

                            {{-- Contenido del evento --}}
                            <div class="flex-grow-1">
                                {{-- Título y fecha --}}
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong>{{ $event->event_label }}</strong>
                                    <small class="text-muted">
                                        {{ $event->created_at->diffForHumans() }}
                                    </small>
                                </div>

                                {{-- Mensaje principal --}}
                                <p class="mb-1 text-secondary" style="font-size: 14px;">
                                    {{ $event->message }}
                                </p>

                                {{-- Quién y cuándo exacto --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user-circle mr-1"></i>
                                        {{ $event->creator?->name ?? 'Sistema automático' }}
                                    </small>
                                    <small class="text-muted">
                                        {{ $event->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @if($totalEvents > 20)
        <div class="card-footer text-center bg-light">
            <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Mostrando los últimos 20 registros de {{ $totalEvents }} totales
            </small>
        </div>
    @endif
</div>
