{{-- ============================================
    MESSAGE DRAWER - Componente Global
    Panel lateral para respuesta rápida de mensajes
    Accesible desde cualquier página del sistema
============================================ --}}

{{-- OVERLAY (fondo oscuro) --}}
<div id="message-drawer-overlay"
     class="message-drawer-overlay"
     style="display: none;"
     onclick="window.MessageDrawer.close()">
</div>

{{-- DRAWER PANEL --}}
<div id="message-drawer"
     class="message-drawer"
     style="display: none;">

    {{-- ========== HEADER ========== --}}
    <div class="message-drawer-header">
        <div class="d-flex align-items-center">
            <button type="button"
                    class="btn btn-sm btn-link text-white p-0 mr-2"
                    onclick="window.MessageDrawer.close()"
                    title="Cerrar">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h6 class="mb-0" id="drawer-order-number">PED-0000-0000</h6>
                <small id="drawer-order-status" class="badge badge-secondary">Estado</small>
            </div>
        </div>
        <button type="button"
                class="btn btn-sm btn-link text-white p-0"
                onclick="window.MessageDrawer.close()"
                title="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- ========== CONTENIDO ========== --}}
    <div class="message-drawer-body">
        {{-- Título sección --}}
        <div class="px-3 py-2 border-bottom" style="background: #f8f9fa;">
            <strong style="font-size: 14px; color: #495057;">
                <i class="fas fa-comments mr-1"></i> Comunicación Interna
            </strong>
        </div>

        {{-- Lista de mensajes (scroll) --}}
        <div id="drawer-messages-list" class="message-drawer-messages">
            <div class="text-center py-4" id="drawer-loading">
                <i class="fas fa-spinner fa-spin fa-2x mb-2" style="color: #6c757d;"></i>
                <p class="mb-0" style="color: #6c757d;">Cargando mensajes...</p>
            </div>
            <div class="text-center py-4" id="drawer-empty" style="display: none;">
                <i class="fas fa-inbox fa-2x mb-2" style="color: #adb5bd;"></i>
                <p class="mb-0" style="color: #6c757d;">No hay mensajes en este pedido</p>
            </div>
            {{-- Mensajes se insertan dinámicamente aquí --}}
        </div>
    </div>

    {{-- ========== FOOTER (Input de respuesta) ========== --}}
    <div class="message-drawer-footer">
        <form id="drawer-message-form" onsubmit="return window.MessageDrawer.sendMessage(event)">
            @csrf
            <div class="form-group mb-2">
                <textarea id="drawer-message-input"
                          class="form-control"
                          rows="2"
                          placeholder="Escribe tu mensaje..."
                          maxlength="1000"
                          required
                          style="font-size: 14px; resize: none;"></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                {{-- Selector de visibilidad --}}
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-sm btn-outline-secondary active" title="Visible para todos">
                        <input type="radio" name="visibility" value="both" checked>
                        <i class="fas fa-users"></i>
                    </label>
                    <label class="btn btn-sm btn-outline-secondary" title="Solo administración">
                        <input type="radio" name="visibility" value="admin">
                        <i class="fas fa-user-shield"></i>
                    </label>
                    <label class="btn btn-sm btn-outline-secondary" title="Solo producción">
                        <input type="radio" name="visibility" value="production">
                        <i class="fas fa-industry"></i>
                    </label>
                </div>
                {{-- Botón enviar --}}
                <button type="submit" class="btn btn-sm btn-primary" id="drawer-send-btn">
                    <i class="fas fa-paper-plane mr-1"></i> Enviar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========== ESTILOS ========== --}}
<style>
/* Overlay */
.message-drawer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.message-drawer-overlay.active {
    opacity: 1;
}

/* Drawer Panel */
.message-drawer {
    position: fixed;
    top: 0;
    right: -400px;
    width: 380px;
    max-width: 100vw;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 1050;
    display: flex;
    flex-direction: column;
    transition: right 0.3s ease;
}
.message-drawer.active {
    right: 0;
}

/* Header */
.message-drawer-header {
    background: #343a40;
    color: #fff;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.message-drawer-header h6 {
    font-size: 15px;
    font-weight: 600;
}

/* Body (mensajes) */
.message-drawer-body {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Lista de mensajes con scroll */
.message-drawer-messages {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

/* Item de mensaje - Estilo chat */
.drawer-message-item {
    padding: 6px 15px;
    display: flex;
}
.drawer-message-item.msg-own {
    justify-content: flex-end;
}
.drawer-message-item.msg-other {
    justify-content: flex-start;
}
.drawer-message-item .msg-bubble {
    max-width: 85%;
    padding: 8px 12px;
    border-radius: 12px;
    word-break: break-word;
}
.drawer-message-item .bubble-own {
    background: #dcf8c6;
    color: #1a1a1a;
    border-bottom-right-radius: 4px;
}
.drawer-message-item .bubble-other {
    background: #f1f0f0;
    color: #1a1a1a;
    border-bottom-left-radius: 4px;
}
.drawer-message-item .msg-author {
    font-weight: 600;
    font-size: 12px;
    color: #1a1a1a;
    margin-bottom: 2px;
}
.drawer-message-item .msg-content {
    font-size: 14px;
    color: #1a1a1a;
    white-space: pre-wrap;
    margin-bottom: 4px;
}
.drawer-message-item .msg-time-bottom {
    font-size: 11px;
    opacity: 0.7;
    text-align: right;
    margin-top: 2px;
}
.drawer-message-item .msg-visibility {
    font-size: 10px;
    margin-right: 4px;
    opacity: 0.8;
}

/* Footer */
.message-drawer-footer {
    background: #f8f9fa;
    padding: 12px 15px;
    border-top: 1px solid #dee2e6;
    flex-shrink: 0;
}

/* Toggle buttons estilo */
.message-drawer-footer .btn-group-toggle .btn {
    padding: 4px 10px;
    font-size: 12px;
}
.message-drawer-footer .btn-group-toggle .btn.active {
    background: #343a40;
    border-color: #343a40;
    color: #fff;
}

/* Referencia a mensaje respondido (estilo WhatsApp/Telegram) */
.reply-reference {
    background: #e9ecef;
    border-left: 3px solid #007bff;
    padding: 6px 10px;
    margin-bottom: 8px;
    border-radius: 0 4px 4px 0;
    font-size: 12px;
}
.reply-reference .reply-to-label {
    color: #007bff;
    font-weight: 600;
    margin-bottom: 2px;
}
.reply-reference .reply-to-preview {
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Botón responder */
.msg-actions {
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px dashed #e9ecef;
}
.reply-to-msg-btn {
    font-size: 12px;
    padding: 2px 6px;
}
.reply-to-msg-btn:hover {
    background: #e3f2fd;
    border-radius: 4px;
}

/* Indicador de respuesta en footer */
.reply-indicator {
    background: #e3f2fd;
    padding: 8px 10px;
    margin-bottom: 8px;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}
.reply-indicator small {
    font-size: 12px;
}
.cancel-reply-btn {
    padding: 0 4px;
}

/* Responsive */
@media (max-width: 576px) {
    .message-drawer {
        width: 100vw;
        right: -100vw;
    }
    .drawer-replies {
        margin-left: 10px;
    }
}
</style>

{{-- ========== JAVASCRIPT ========== --}}
<script>
(function() {
    'use strict';

    // ========================================
    // ESTADO GLOBAL DEL DRAWER
    // ========================================
    window.MessageDrawer = {
        isOpen: false,
        orderId: null,
        orderNumber: null,
        orderStatus: null,
        triggerMessageId: null, // ID del mensaje que disparó el drawer (para marcar como leído)
        messages: [],
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || ''
    };

    // ========================================
    // ABRIR DRAWER
    // ========================================
    window.MessageDrawer.open = function(orderId, orderNumber, orderStatus, triggerMessageId) {
        if (!orderId) {
            console.error('[Drawer] orderId es requerido');
            return;
        }

        // Guardar estado
        this.orderId = orderId;
        this.orderNumber = orderNumber || 'Pedido #' + orderId;
        this.orderStatus = orderStatus || 'unknown';
        this.triggerMessageId = triggerMessageId || null; // Mensaje que disparó el drawer
        this.isOpen = true;

        // Actualizar header
        document.getElementById('drawer-order-number').textContent = this.orderNumber;
        var statusBadge = document.getElementById('drawer-order-status');
        statusBadge.textContent = this.getStatusLabel(this.orderStatus);
        statusBadge.className = 'badge badge-' + this.getStatusColor(this.orderStatus);

        // Mostrar overlay y drawer
        var overlay = document.getElementById('message-drawer-overlay');
        var drawer = document.getElementById('message-drawer');

        overlay.style.display = 'block';
        drawer.style.display = 'flex';

        // Forzar reflow para animación
        overlay.offsetHeight;
        drawer.offsetHeight;

        setTimeout(function() {
            overlay.classList.add('active');
            drawer.classList.add('active');
        }, 10);

        // Cargar mensajes
        this.loadMessages();

        // Bloquear scroll del body
        document.body.style.overflow = 'hidden';

        // Listener para ESC
        document.addEventListener('keydown', this._escHandler);

        console.log('[Drawer] Abierto para pedido:', this.orderNumber);
    };

    // ========================================
    // CERRAR DRAWER
    // ========================================
    window.MessageDrawer.close = function() {
        var overlay = document.getElementById('message-drawer-overlay');
        var drawer = document.getElementById('message-drawer');

        overlay.classList.remove('active');
        drawer.classList.remove('active');

        setTimeout(function() {
            overlay.style.display = 'none';
            drawer.style.display = 'none';
        }, 300);

        // Restaurar scroll
        document.body.style.overflow = '';

        // Remover listener ESC
        document.removeEventListener('keydown', this._escHandler);

        // Limpiar estado
        this.isOpen = false;
        this.orderId = null;
        this.triggerMessageId = null;

        // Refrescar la lista del dropdown para mostrar cambios
        if (typeof window.loadUnreadMessages === 'function') {
            window.loadUnreadMessages();
        }

        console.log('[Drawer] Cerrado');
    };

    // Handler para tecla ESC
    window.MessageDrawer._escHandler = function(e) {
        if (e.key === 'Escape' && window.MessageDrawer.isOpen) {
            window.MessageDrawer.close();
        }
    };

    // ========================================
    // CARGAR MENSAJES DESDE API
    // ========================================
    window.MessageDrawer.loadMessages = function() {
        var self = this;
        var listEl = document.getElementById('drawer-messages-list');
        var loadingEl = document.getElementById('drawer-loading');
        var emptyEl = document.getElementById('drawer-empty');

        // Mostrar loading
        loadingEl.style.display = 'block';
        emptyEl.style.display = 'none';

        // Limpiar mensajes anteriores
        listEl.querySelectorAll('.drawer-message-item').forEach(function(el) {
            el.remove();
        });

        // Fetch mensajes
        fetch('/admin/orders/' + this.orderId + '/messages', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            loadingEl.style.display = 'none';

            if (data.success && data.data && data.data.length > 0) {
                self.messages = data.data;
                self.renderMessages();
            } else {
                emptyEl.style.display = 'block';
            }
        })
        .catch(function(error) {
            console.error('[Drawer] Error cargando mensajes:', error);
            loadingEl.style.display = 'none';
            emptyEl.style.display = 'block';
            emptyEl.querySelector('p').textContent = 'Error al cargar mensajes';
        });
    };

    // ========================================
    // RENDERIZAR MENSAJES ESTILO CHAT
    // ========================================
    window.MessageDrawer.renderMessages = function() {
        var self = this;
        var listEl = document.getElementById('drawer-messages-list');
        var emptyEl = document.getElementById('drawer-empty');

        // Limpiar mensajes anteriores
        listEl.querySelectorAll('.drawer-message-item').forEach(function(el) {
            el.remove();
        });

        if (this.messages.length === 0) {
            emptyEl.style.display = 'block';
            return;
        }

        emptyEl.style.display = 'none';

        // Ordenar mensajes: más viejos arriba, más nuevos abajo (estilo chat)
        // Reversamos porque la API devuelve newest-first
        var sortedMessages = this.messages.slice().reverse();

        // Crear elementos de mensaje (estilo chat lineal)
        sortedMessages.forEach(function(msg) {
            var item = document.createElement('div');
            // Determinar si es mensaje propio
            var isOwn = msg.is_own || false;
            item.className = 'drawer-message-item ' + (isOwn ? 'msg-own' : 'msg-other');
            item.dataset.messageId = msg.id;

            var visibilityBadge = self.getVisibilityBadge(msg.visibility);
            var authorName = escapeHtml(msg.creator || 'Sistema');

            // Estilo WhatsApp: nombre arriba (solo otros), mensaje, tiempo abajo
            var authorHtml = isOwn ? '' : '<div class="msg-author">' + authorName + visibilityBadge + '</div>';
            var timeHtml = '<div class="msg-time-bottom">' + (isOwn ? visibilityBadge : '') + escapeHtml(msg.time_ago || 'Ahora') + '</div>';

            item.innerHTML =
                '<div class="msg-bubble ' + (isOwn ? 'bubble-own' : 'bubble-other') + '">' +
                    authorHtml +
                    '<div class="msg-content">' + escapeHtml(msg.message || '') + '</div>' +
                    timeHtml +
                '</div>';

            listEl.appendChild(item);
        });

        // Scroll al final
        listEl.scrollTop = listEl.scrollHeight;
    };

    // ========================================
    // OBTENER BADGE DE VISIBILIDAD
    // ========================================
    window.MessageDrawer.getVisibilityBadge = function(visibility) {
        if (visibility === 'admin') {
            return '<span class="badge badge-secondary msg-visibility"><i class="fas fa-lock"></i> Admin</span>';
        } else if (visibility === 'production') {
            return '<span class="badge badge-warning msg-visibility"><i class="fas fa-industry"></i> Prod</span>';
        }
        return '';
    };

    // ========================================
    // ENVIAR MENSAJE
    // ========================================
    window.MessageDrawer.sendMessage = function(e) {
        e.preventDefault();

        var self = this;
        var input = document.getElementById('drawer-message-input');
        var sendBtn = document.getElementById('drawer-send-btn');
        var visibility = document.querySelector('input[name="visibility"]:checked').value;
        var message = input.value.trim();

        if (!message) return false;

        // Deshabilitar botón
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Obtener socket ID para excluir al remitente del broadcast (toOthers)
        var socketId = window.getPusherSocketId ? window.getPusherSocketId() : null;
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
        };
        if (socketId) {
            headers['X-Socket-ID'] = socketId;
        }

        fetch('/admin/orders/' + this.orderId + '/messages', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                message: message,
                visibility: visibility
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                // Limpiar input
                input.value = '';

                // Agregar mensaje al inicio del array (API devuelve newest-first)
                // Al hacer reverse() en renderMessages, quedará al final (abajo)
                self.messages.unshift({
                    id: data.data.id,
                    message: data.data.message,
                    visibility: data.data.visibility,
                    visibility_label: data.data.visibility_label,
                    creator: data.data.creator,
                    time_ago: data.data.time_ago,
                    is_own: true
                });

                self.renderMessages();

                // Actualizar la sección de mensajes del pedido si estamos en esa página
                self.refreshOrderPageMessages(data.data);

                // Mostrar toast de éxito
                if (typeof Swal !== 'undefined') {
                    var Toast = Swal.mixin({
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

                // Marcar como leído el mensaje que disparó el drawer
                if (self.triggerMessageId) {
                    self.markMessageAsRead(self.triggerMessageId);
                    self.triggerMessageId = null; // Limpiar después de marcar
                }

                console.log('[Drawer] Mensaje enviado:', data.data.id);
            } else {
                alert('Error al enviar mensaje');
            }
        })
        .catch(function(error) {
            console.error('[Drawer] Error enviando:', error);
            alert('Error de conexión');
        })
        .finally(function() {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Enviar';
        });

        return false;
    };

    // ========================================
    // HELPERS
    // ========================================
    window.MessageDrawer.getStatusLabel = function(status) {
        var labels = {
            'draft': 'Borrador',
            'confirmed': 'Confirmado',
            'in_production': 'En Producción',
            'ready': 'Listo',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado'
        };
        return labels[status] || status;
    };

    window.MessageDrawer.getStatusColor = function(status) {
        var colors = {
            'draft': 'secondary',
            'confirmed': 'info',
            'in_production': 'primary',
            'ready': 'success',
            'delivered': 'dark',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    };

    // ========================================
    // MARCAR MENSAJE COMO LEÍDO
    // ========================================
    window.MessageDrawer.markMessageAsRead = function(messageId) {
        if (!messageId) return;

        fetch('/admin/notifications/messages/' + messageId + '/read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                console.log('[Drawer] Mensaje marcado como leído:', messageId);
                // Refrescar el dropdown de mensajes
                if (typeof window.loadUnreadMessages === 'function') {
                    window.loadUnreadMessages();
                }
            }
        })
        .catch(function(error) {
            console.error('[Drawer] Error marcando mensaje como leído:', error);
        });
    };

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================================
    // ACTUALIZAR SECCIÓN DE MENSAJES DEL PEDIDO
    // ========================================
    window.MessageDrawer.refreshOrderPageMessages = function(newMessage) {
        // Usar la función global si está disponible (definida en _messages.blade.php)
        if (typeof window.addMessageToOrderPage === 'function') {
            // Agregar el order_id del drawer al mensaje para que la función lo valide
            var msgData = {
                id: newMessage.id,
                order_id: this.orderId,
                message: newMessage.message,
                visibility: newMessage.visibility,
                creator: newMessage.creator,
                time_ago: newMessage.time_ago || 'Ahora',
                created_at: newMessage.created_at || ''
            };
            // Es mensaje propio (enviado desde el drawer)
            // La función addMessageToOrderPage espera is_own en el objeto o como segundo parámetro
            // Pero solo acepta data, así que vamos a usar la función local
        }

        // Buscar el contenedor de mensajes en la página del pedido
        var container = document.getElementById('messages-container');
        if (!container) return; // No estamos en la página del pedido

        // Verificar si ya existe este mensaje
        if (container.querySelector('[data-message-id="' + newMessage.id + '"]')) {
            return; // Ya existe, no duplicar
        }

        // Verificar si hay mensaje "sin notas" y removerlo
        var emptyMsg = container.querySelector('.text-center.py-4');
        if (emptyMsg) {
            container.innerHTML = '<div class="list-group list-group-flush"></div>';
            // Agregar estilos de chat si no existen
            if (!document.getElementById('chat-bubble-styles')) {
                var styles = document.createElement('style');
                styles.id = 'chat-bubble-styles';
                styles.textContent = '.message-item { background: transparent !important; border: none !important; padding: 4px 10px !important; } .message-bubble { padding: 8px 12px; border-radius: 12px; max-width: 85%; word-wrap: break-word; } .own-message { display: flex; justify-content: flex-end; } .other-message { display: flex; justify-content: flex-start; } .bubble-own { background: #dcf8c6; color: #1a1a1a; border-bottom-right-radius: 4px; } .bubble-other { background: #f1f0f0; color: #1a1a1a; border-bottom-left-radius: 4px; }';
                document.head.appendChild(styles);
            }
        }

        // Obtener o crear la lista
        var listGroup = container.querySelector('.list-group');
        if (!listGroup) {
            listGroup = document.createElement('div');
            listGroup.className = 'list-group list-group-flush';
            container.appendChild(listGroup);
        }

        // Badge de visibilidad
        var visibilityBadge = '';
        if (newMessage.visibility === 'admin') {
            visibilityBadge = '<span class="badge mr-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;"><i class="fas fa-lock"></i> Admin</span>';
        } else if (newMessage.visibility === 'production') {
            visibilityBadge = '<span class="badge mr-1" style="background: rgba(0,0,0,0.15); color: inherit; font-size: 10px;"><i class="fas fa-industry"></i> Producción</span>';
        }

        // Crear elemento del mensaje estilo WhatsApp (es mensaje propio, sin "Tú" arriba)
        var item = document.createElement('div');
        item.className = 'list-group-item py-2 message-item own-message';
        item.dataset.messageId = newMessage.id;
        item.innerHTML =
            '<div class="message-bubble bubble-own">' +
                '<p class="mb-1" style="white-space: pre-wrap; font-size: 14px;">' + escapeHtml(newMessage.message || '') + '</p>' +
                '<div class="text-right" style="font-size: 11px; opacity: 0.7; margin-top: 2px;">' +
                    visibilityBadge +
                    '<span>' + escapeHtml(newMessage.time_ago || 'Ahora') + '</span>' +
                '</div>' +
            '</div>';

        // Agregar al FINAL de la lista (estilo chat: más nuevos abajo)
        listGroup.appendChild(item);

        // Scroll abajo para ver el nuevo mensaje
        container.scrollTop = container.scrollHeight;
    };

    // ========================================
    // AGREGAR MENSAJE ENTRANTE (llamado desde menu-item-messages.blade.php)
    // ========================================
    window.MessageDrawer.addIncomingMessage = function(data) {
        if (!this.isOpen) return;

        var newMsg = {
            id: data.id,
            message: data.message,
            visibility: data.visibility,
            creator: data.creator,
            time_ago: data.time_ago || 'Ahora',
            is_own: false
        };

        // Evitar duplicados
        var exists = this.messages.some(function(m) {
            return m.id === data.id;
        });

        if (!exists) {
            // Agregar al inicio (newest-first), después de reverse() quedará al final (abajo)
            this.messages.unshift(newMsg);
            this.renderMessages();
        }
    };

})();
</script>
