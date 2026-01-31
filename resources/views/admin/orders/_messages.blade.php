{{-- NOTAS OPERATIVAS / COMUNICACIÓN INTERNA - Estilo Chat --}}
@php
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
        <div>
            {{-- Botón expandir drawer --}}
            <button type="button"
                    class="btn btn-sm btn-outline-secondary mr-1"
                    onclick="window.MessageDrawer && window.MessageDrawer.open({{ $order->id }}, '{{ $order->order_number }}', '{{ $order->status }}')"
                    title="Expandir en panel lateral"
                    style="font-size: 14px;">
                <i class="fas fa-expand-alt"></i>
            </button>
            {{-- Botón nueva nota --}}
            <button class="btn btn-sm btn-light" data-toggle="modal" data-target="#modalNewMessage"
                    title="Agregar una nota" style="font-size: 14px; color: #212529;">
                <i class="fas fa-plus"></i> Nueva Nota
            </button>
        </div>
    </div>
    <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;" id="messages-container" data-order-id="{{ $order->id }}">
        @if($totalMessages == 0)
            <div class="text-center py-4" style="color: #495057;">
                <i class="fas fa-sticky-note fa-2x mb-2"></i>
                <p class="mb-0" style="font-size: 15px;">No hay notas sobre este pedido</p>
                <span style="font-size: 14px; color: #6c757d;">Use las notas para comunicar problemas, cambios o información relevante.</span>
            </div>
        @else
            @php
                // Obtener TODOS los mensajes (raíz y respuestas) ordenados por fecha
                $allMessages = $order->messages()
                    ->with(['creator', 'parent.creator'])
                    ->orderBy('created_at', 'asc')
                    ->get();
            @endphp
            <div class="list-group list-group-flush">
                @foreach($allMessages as $msg)
                    @php
                        $isOwnMessage = $msg->created_by === auth()->id();
                    @endphp
                    <div class="list-group-item py-2 message-item {{ $isOwnMessage ? 'own-message' : 'other-message' }}" data-message-id="{{ $msg->id }}">
                        <div class="message-bubble {{ $isOwnMessage ? 'bubble-own' : 'bubble-other' }}">
                            {{-- Nombre del autor (solo para mensajes de otros) --}}
                            @if(!$isOwnMessage)
                                <div style="font-size: 12px; font-weight: 600; margin-bottom: 2px;">
                                    {{ $msg->creator?->name ?? 'Sistema' }}
                                    @if($msg->visibility === 'admin')
                                        <span class="badge ml-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;">
                                            <i class="fas fa-lock"></i> Admin
                                        </span>
                                    @elseif($msg->visibility === 'production')
                                        <span class="badge ml-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;">
                                            <i class="fas fa-industry"></i> Producción
                                        </span>
                                    @endif
                                </div>
                            @endif
                            {{-- Contenido del mensaje --}}
                            <p class="mb-1" style="white-space: pre-wrap; font-size: 14px;">{{ $msg->message }}</p>
                            {{-- Tiempo abajo a la derecha (estilo WhatsApp) --}}
                            <div class="text-right" style="font-size: 11px; opacity: 0.7; margin-top: 2px;">
                                @if($isOwnMessage)
                                    @if($msg->visibility === 'admin')
                                        <span class="badge mr-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;">
                                            <i class="fas fa-lock"></i> Admin
                                        </span>
                                    @elseif($msg->visibility === 'production')
                                        <span class="badge mr-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;">
                                            <i class="fas fa-industry"></i> Producción
                                        </span>
                                    @endif
                                @endif
                                <span title="{{ $msg->created_at->format('d/m/Y H:i') }}">{{ $msg->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <style>
                .message-item { background: transparent !important; border: none !important; padding: 4px 10px !important; }
                .message-bubble { padding: 8px 12px; border-radius: 12px; max-width: 85%; word-wrap: break-word; }
                .own-message { display: flex; justify-content: flex-end; }
                .other-message { display: flex; justify-content: flex-start; }
                .bubble-own { background: #dcf8c6; color: #1a1a1a; border-bottom-right-radius: 4px; }
                .bubble-other { background: #f1f0f0; color: #1a1a1a; border-bottom-left-radius: 4px; }
            </style>
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
    const form = document.getElementById('formNewMessage');
    const modal = document.getElementById('modalNewMessage');

    // Contador de caracteres
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // Resetear estado del modal cuando se abre
    if (modal) {
        $(modal).on('show.bs.modal', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            const cancelBtn = form.querySelector('button[data-dismiss="modal"]');

            // Rehabilitar botones y quitar clases de deshabilitado
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Guardar Nota';
                submitBtn.classList.remove('disabled');
                submitBtn.style.pointerEvents = '';
            }
            if (cancelBtn) {
                cancelBtn.disabled = false;
                cancelBtn.classList.remove('disabled');
                cancelBtn.style.pointerEvents = '';
            }
        });
    }

    // Envío AJAX del formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const cancelBtn = form.querySelector('button[data-dismiss="modal"]');
            const message = form.querySelector('textarea[name="message"]').value.trim();
            const visibility = form.querySelector('select[name="visibility"]').value;

            if (!message) {
                alert('Por favor, escriba un mensaje.');
                return;
            }

            // Deshabilitar botones mientras envía
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...';
            if (cancelBtn) cancelBtn.disabled = true;

            // Enviar por AJAX
            // Obtener socket ID para excluir al remitente del broadcast (toOthers)
            var socketId = window.getPusherSocketId ? window.getPusherSocketId() : null;
            var headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            };
            if (socketId) {
                headers['X-Socket-ID'] = socketId;
            }

            fetch(form.action, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    message: message,
                    visibility: visibility
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cerrar modal
                    $('#modalNewMessage').modal('hide');

                    // Limpiar formulario
                    form.reset();
                    if (charCount) charCount.textContent = '0';

                    // Agregar mensaje al card (es mensaje propio)
                    addMessageToCard(data.data, true);

                    // Mostrar toast de éxito
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Mensaje enviado correctamente'
                        });
                    }
                } else {
                    alert('Error al enviar el mensaje');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el mensaje');
            })
            .finally(() => {
                // Rehabilitar botones
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Guardar Nota';
                if (cancelBtn) cancelBtn.disabled = false;
            });
        });
    }

    // Función para agregar mensaje al card (expuesta globalmente)
    function addMessageToCard(msg, isOwnMessage) {
        const container = document.getElementById('messages-container');
        if (!container) return;

        // Si está vacío (mostrando el mensaje "No hay notas"), reemplazar contenido
        const emptyState = container.querySelector('.text-center.py-4');
        if (emptyState) {
            container.innerHTML = '<div class="list-group list-group-flush"></div>';
            // Agregar estilos de chat si no existen
            if (!document.getElementById('chat-bubble-styles')) {
                const styles = document.createElement('style');
                styles.id = 'chat-bubble-styles';
                styles.textContent = `
                    .message-item { background: transparent !important; border: none !important; padding: 4px 10px !important; }
                    .message-bubble { padding: 8px 12px; border-radius: 12px; max-width: 85%; word-wrap: break-word; }
                    .own-message { display: flex; justify-content: flex-end; }
                    .other-message { display: flex; justify-content: flex-start; }
                    .bubble-own { background: #dcf8c6; color: #1a1a1a; border-bottom-right-radius: 4px; }
                    .bubble-other { background: #f1f0f0; color: #1a1a1a; border-bottom-left-radius: 4px; }
                `;
                document.head.appendChild(styles);
            }
        }

        // Verificar si ya existe este mensaje
        if (container.querySelector(`[data-message-id="${msg.id}"]`)) {
            return; // Ya existe, no duplicar
        }

        // Obtener o crear la lista
        let listGroup = container.querySelector('.list-group');
        if (!listGroup) {
            listGroup = document.createElement('div');
            listGroup.className = 'list-group list-group-flush';
            container.appendChild(listGroup);
        }

        // Determinar si es mensaje propio
        const isOwn = isOwnMessage !== undefined ? isOwnMessage : (msg.is_own || false);

        // Crear el elemento del mensaje estilo chat
        const item = document.createElement('div');
        item.className = 'list-group-item py-2 message-item ' + (isOwn ? 'own-message' : 'other-message');
        item.dataset.messageId = msg.id;

        // Badge de visibilidad
        let visibilityBadge = '';
        if (msg.visibility === 'admin') {
            visibilityBadge = '<span class="badge ml-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;"><i class="fas fa-lock"></i> Admin</span>';
        } else if (msg.visibility === 'production') {
            visibilityBadge = '<span class="badge ml-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;"><i class="fas fa-industry"></i> Producción</span>';
        }

        const authorName = isOwn ? 'Tú' : escapeHtml(msg.creator || 'Sistema');

        item.innerHTML = `
            <div class="message-bubble ${isOwn ? 'bubble-own' : 'bubble-other'}">
                ${!isOwn ? `<div class="msg-author" style="font-size: 12px; font-weight: 600; margin-bottom: 2px;">${authorName}${visibilityBadge}</div>` : ''}
                <p class="mb-1" style="white-space: pre-wrap; font-size: 14px;">${escapeHtml(msg.message)}</p>
                <div class="text-right" style="font-size: 11px; opacity: 0.7; margin-top: 2px;">
                    ${isOwn ? visibilityBadge : ''}
                    <span title="${escapeHtml(msg.created_at || '')}">${escapeHtml(msg.time_ago || 'Ahora')}</span>
                </div>
            </div>
        `;

        // Agregar al FINAL de la lista (estilo chat: más nuevos abajo)
        listGroup.appendChild(item);

        // Scroll abajo para ver el nuevo mensaje
        container.scrollTop = container.scrollHeight;
    }

    // Exponer función globalmente para que WebSocket pueda usarla
    window.addMessageToOrderPage = function(data) {
        const container = document.getElementById('messages-container');
        if (!container) return false;

        // Verificar si estamos en la página del pedido correcto
        const pageOrderId = container.dataset.orderId;
        if (!pageOrderId || parseInt(pageOrderId) !== parseInt(data.order_id)) {
            return false;
        }

        // Agregar el mensaje (no es propio porque viene de WebSocket)
        addMessageToCard({
            id: data.id,
            message: data.message,
            visibility: data.visibility,
            creator: data.creator,
            time_ago: data.time_ago || 'Ahora',
            created_at: data.created_at || ''
        }, false);

        return true;
    };

    // Función para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
