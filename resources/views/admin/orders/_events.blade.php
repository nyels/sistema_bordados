{{-- HISTORIAL DEL PEDIDO - Vista clara para el usuario --}}
@php
    $events = $order->events()->with('creator')->latest()->take(20)->get();
    $totalEvents = $order->events()->count();
@endphp

{{-- 7. EVENTOS --}}
<div class="card card-section-eventos">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-stream mr-2"></i>
            Historial del Pedido
        </h5>
        @if($totalEvents > 0)
            <span class="badge badge-light" style="font-size: 14px; color: #212529;"
                  data-toggle="tooltip" title="Registro automático de todas las acciones">
                {{ $totalEvents }} {{ $totalEvents === 1 ? 'registro' : 'registros' }}
            </span>
        @endif
    </div>
    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
        @if($events->isEmpty())
            <div class="text-center py-4" style="color: #495057;">
                <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                <p class="mb-0" style="font-size: 15px;">Este pedido aún no tiene historial</p>
                <span style="font-size: 14px; color: #6c757d;">Las acciones importantes se registrarán aquí automáticamente</span>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach($events as $event)
                    <li class="list-group-item py-3">
                        <div class="d-flex">
                            {{-- Icono del evento --}}
                            <div class="mr-3 text-center" style="min-width: 40px;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $event->event_color }}"
                                      style="width: 40px; height: 40px;">
                                    <i class="{{ explode(' ', $event->event_icon)[0] }} {{ explode(' ', $event->event_icon)[1] ?? '' }}" style="color: white; font-size: 16px;"></i>
                                </span>
                            </div>

                            {{-- Contenido del evento --}}
                            <div class="flex-grow-1">
                                {{-- Título y fecha --}}
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong style="font-size: 16px; color: #212529;">{{ $event->event_label }}</strong>
                                    <span style="font-size: 14px; color: #495057;">
                                        {{ $event->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                {{-- Mensaje principal --}}
                                <p class="mb-2" style="font-size: 15px; color: #495057;">
                                    {{ $event->message }}
                                </p>

                                {{-- Quién y cuándo exacto --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 14px; color: #6c757d;">
                                        <i class="fas fa-user-circle mr-1"></i>
                                        {{ $event->creator?->name ?? 'Sistema automático' }}
                                    </span>
                                    <span style="font-size: 14px; color: #6c757d;">
                                        {{ $event->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @if($totalEvents > 20)
        <div class="card-footer text-center" style="background: #f8f9fa;">
            <span style="font-size: 14px; color: #495057;">
                <i class="fas fa-info-circle mr-1"></i>
                Mostrando los últimos 20 registros de {{ $totalEvents }} totales
            </span>
        </div>
    @endif
</div>
