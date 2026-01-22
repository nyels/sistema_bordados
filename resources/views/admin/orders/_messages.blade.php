{{-- NOTAS OPERATIVAS / COMUNICACIÓN INTERNA --}}
@php
    $messages = $order->messages()->with('creator')->latest()->take(20)->get();
    $totalMessages = $order->messages()->count();
@endphp

{{-- 8. MENSAJES --}}
<div class="card card-section-mensajes">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-comments mr-2"></i>Comunicación Interna
            </h5>
            <span style="font-size: 14px; opacity: 0.9;">Notas y comentarios del equipo</span>
        </div>
        <button class="btn btn-sm btn-light" data-toggle="modal" data-target="#modalNewMessage"
                title="Agregar una nota" style="font-size: 14px; color: #212529;">
            <i class="fas fa-plus"></i> Nueva Nota
        </button>
    </div>
    <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;" id="messages-container">
        @if($messages->isEmpty())
            <div class="text-center py-4" style="color: #495057;">
                <i class="fas fa-sticky-note fa-2x mb-2"></i>
                <p class="mb-0" style="font-size: 15px;">No hay notas sobre este pedido</p>
                <span style="font-size: 14px; color: #6c757d;">Use las notas para comunicar problemas, cambios o información relevante.</span>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($messages as $msg)
                    <div class="list-group-item py-3">
                        <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                            <div>
                                <strong style="font-size: 15px; color: #212529;">{{ $msg->creator?->name ?? 'Sistema automático' }}</strong>
                                @if($msg->visibility === 'admin')
                                    <span class="badge ml-1" style="background: #6c757d; color: white; font-size: 12px;"
                                          data-toggle="tooltip" title="Solo visible para administración">
                                        <i class="fas fa-lock"></i> Admin
                                    </span>
                                @elseif($msg->visibility === 'production')
                                    <span class="badge ml-1" style="background: #e65100; color: white; font-size: 12px;"
                                          data-toggle="tooltip" title="Solo visible para producción">
                                        <i class="fas fa-industry"></i> Producción
                                    </span>
                                @endif
                            </div>
                            <span style="font-size: 14px; color: #495057;" title="{{ $msg->created_at->format('d/m/Y H:i') }}">
                                {{ $msg->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="mb-0" style="white-space: pre-wrap; font-size: 15px; color: #212529;">{{ $msg->message }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if($totalMessages > 20)
        <div class="card-footer text-center" style="background: #f8f9fa;">
            <span style="font-size: 14px; color: #495057;">
                <i class="fas fa-info-circle mr-1"></i>
                Mostrando las últimas 20 notas de {{ $totalMessages }} totales
            </span>
        </div>
    @endif
</div>

{{-- MODAL AGREGAR MENSAJE --}}
<div class="modal fade" id="modalNewMessage" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.messages.store', $order) }}" method="POST" id="formNewMessage">
                @csrf
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Agregar Nota al Pedido
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" style="font-size: 14px; color: #495057;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Las notas sirven para comunicar información al equipo. No afectan el estado del pedido.
                    </p>
                    <div class="form-group">
                        <label style="font-size: 15px; color: #212529;">¿Qué quiere comunicar? <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4" style="font-size: 15px;"
                                  placeholder="Ejemplos:&#10;• El cliente pidió cambiar el color del bordado&#10;• Falta confirmar las medidas del cuello&#10;• La máquina 2 está en mantenimiento, usar la 1&#10;• Cliente pasará a recoger el viernes por la tarde"
                                  required maxlength="1000"></textarea>
                        <small style="font-size: 13px; color: #6c757d;">
                            <span id="charCount">0</span>/1000 caracteres
                        </small>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size: 15px; color: #212529;">¿Quién debe ver esta nota?</label>
                        <select name="visibility" class="form-control" style="font-size: 15px;">
                            <option value="both">Todo el equipo (administración y producción)</option>
                            <option value="admin">Solo administración (información confidencial)</option>
                            <option value="production">Solo producción (instrucciones técnicas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="font-size: 15px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="font-size: 15px;">
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
