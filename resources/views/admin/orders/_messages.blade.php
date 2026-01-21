{{-- NOTAS OPERATIVAS / COMUNICACIÓN INTERNA --}}
@php
    $messages = $order->messages()->with('creator')->latest()->take(20)->get();
    $totalMessages = $order->messages()->count();
@endphp

<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-comments mr-2"></i>Comunicación Interna
            </h5>
            <small class="text-muted">Notas y comentarios del equipo sobre este pedido</small>
        </div>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalNewMessage"
                title="Agregar una nota para comunicar algo al equipo">
            <i class="fas fa-plus"></i> Nueva Nota
        </button>
    </div>
    <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;" id="messages-container">
        @if($messages->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fas fa-sticky-note fa-2x mb-2"></i>
                <p class="mb-0">No hay notas sobre este pedido</p>
                <small>Use las notas para comunicar problemas, cambios solicitados o cualquier información relevante.</small>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($messages as $msg)
                    <div class="list-group-item py-3">
                        <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ $msg->creator?->name ?? 'Sistema automático' }}</strong>
                                @if($msg->visibility === 'admin')
                                    <span class="badge badge-secondary ml-1" data-toggle="tooltip"
                                          title="Solo visible para administración">
                                        <i class="fas fa-lock"></i> Admin
                                    </span>
                                @elseif($msg->visibility === 'production')
                                    <span class="badge badge-warning ml-1" data-toggle="tooltip"
                                          title="Solo visible para producción">
                                        <i class="fas fa-industry"></i> Producción
                                    </span>
                                @endif
                            </div>
                            <small class="text-muted" title="{{ $msg->created_at->format('d/m/Y H:i') }}">
                                {{ $msg->created_at->diffForHumans() }}
                            </small>
                        </div>
                        <p class="mb-0" style="white-space: pre-wrap; font-size: 14px;">{{ $msg->message }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if($totalMessages > 20)
        <div class="card-footer text-muted text-center" style="font-size: 12px;">
            <i class="fas fa-info-circle mr-1"></i>
            Mostrando las últimas 20 notas de {{ $totalMessages }} totales
        </div>
    @endif
</div>

{{-- MODAL AGREGAR MENSAJE --}}
<div class="modal fade" id="modalNewMessage" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.messages.store', $order) }}" method="POST" id="formNewMessage">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Agregar Nota al Pedido
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Las notas sirven para comunicar información al equipo. No afectan el estado del pedido.
                    </p>
                    <div class="form-group">
                        <label>¿Qué quiere comunicar? <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4"
                                  placeholder="Ejemplos:&#10;• El cliente pidió cambiar el color del bordado&#10;• Falta confirmar las medidas del cuello&#10;• La máquina 2 está en mantenimiento, usar la 1&#10;• Cliente pasará a recoger el viernes por la tarde"
                                  required maxlength="1000"></textarea>
                        <small class="form-text text-muted">
                            <span id="charCount">0</span>/1000 caracteres
                        </small>
                    </div>
                    <div class="form-group mb-0">
                        <label>¿Quién debe ver esta nota?</label>
                        <select name="visibility" class="form-control">
                            <option value="both">Todo el equipo (administración y producción)</option>
                            <option value="admin">Solo administración (información confidencial o de costos)</option>
                            <option value="production">Solo producción (instrucciones técnicas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Guardar Nota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('#modalNewMessage textarea[name="message"]');
    const charCount = document.getElementById('charCount');

    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});
</script>
