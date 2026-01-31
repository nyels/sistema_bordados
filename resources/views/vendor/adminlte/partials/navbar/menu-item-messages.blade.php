{{-- Dropdown de Mensajes con Tracking de Lectura --}}
<li class="nav-item dropdown" id="navbar-messages-dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" title="Mensajes Internos">
        <i class="far fa-comments"></i>
        <span class="badge badge-danger navbar-badge" id="messages-badge" style="display: none;">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <div class="dropdown-item dropdown-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-comments mr-1"></i> Mensajes Internos</span>
            <button type="button" class="btn btn-xs btn-outline-secondary" id="mark-all-read-btn" title="Marcar todos como leídos">
                <i class="fas fa-check-double"></i>
            </button>
        </div>
        <div class="dropdown-divider"></div>

        {{-- Lista de mensajes --}}
        <div id="messages-list" style="max-height: 350px; overflow-y: auto;">
            <div class="dropdown-item text-muted text-center" id="messages-loading">
                <i class="fas fa-spinner fa-spin mr-2"></i> Cargando...
            </div>
            <a href="#" class="dropdown-item text-muted text-center" id="no-messages" style="display: none;">
                <i class="fas fa-inbox mr-2"></i> Sin mensajes no leídos
            </a>
        </div>

        <div class="dropdown-divider"></div>

        {{-- Footer con estado de conexión --}}
        <div class="dropdown-item dropdown-footer d-flex justify-content-between align-items-center">
            <span id="ws-status" class="badge badge-secondary">
                <i class="fas fa-circle mr-1"></i>
                <span id="ws-status-text">Conectando...</span>
            </span>
            <a href="#" id="refresh-messages-btn" class="text-muted" title="Actualizar">
                <i class="fas fa-sync-alt"></i>
            </a>
        </div>
    </div>
</li>

@push('js')
{{-- Cargar Pusher via CDN --}}
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
(function() {
    'use strict';

    // Estado del módulo
    let messages = [];
    let pusher = null;
    let isLoading = false;
    let lastProcessedMessageId = null; // Evitar doble procesamiento

    const API_BASE = '/admin/notifications/messages';

    // ========================================
    // CARGAR MENSAJES NO LEÍDOS DESDE BD
    // ========================================
    function loadUnreadMessages() {
        if (isLoading) return;
        isLoading = true;

        const loadingEl = document.getElementById('messages-loading');
        const noMessagesEl = document.getElementById('no-messages');

        if (loadingEl) loadingEl.style.display = 'block';
        if (noMessagesEl) noMessagesEl.style.display = 'none';

        fetch(API_BASE + '/unread', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messages = data.messages.map(m => ({
                    id: m.id,
                    type: 'message',
                    title: m.order_number,
                    content: `${m.creator}: ${m.message.substring(0, 60)}${m.message.length > 60 ? '...' : ''}`,
                    icon: m.is_reply ? 'fas fa-reply text-info' : 'fas fa-comment text-primary',
                    timestamp: m.time_ago,
                    read: false,
                    order_id: m.order_id,
                    is_reply: m.is_reply || false,
                    parent_message_id: m.parent_message_id || null,
                    parent_preview: m.parent_preview || null,
                    parent_creator: m.parent_creator || null
                }));

                renderMessages();
                updateBadge(data.total_unread);
            }
        })
        .catch(error => console.error('[Messages] Error cargando:', error))
        .finally(() => {
            isLoading = false;
            if (loadingEl) loadingEl.style.display = 'none';
        });
    }

    // ========================================
    // MARCAR MENSAJE COMO LEÍDO
    // ========================================
    function markAsRead(messageId) {
        fetch(API_BASE + '/' + messageId + '/read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messages = messages.filter(m => m.id !== messageId);
                renderMessages();
                updateBadge(data.remaining_unread);
            }
        })
        .catch(error => console.error('[Messages] Error marcando:', error));
    }

    // ========================================
    // MARCAR TODOS COMO LEÍDOS
    // ========================================
    function markAllAsRead() {
        fetch(API_BASE + '/mark-all-read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messages = [];
                renderMessages();
                updateBadge(0);
            }
        })
        .catch(error => console.error('[Messages] Error marcando todos:', error));
    }

    // ========================================
    // INICIALIZAR PUSHER/REVERB
    // ========================================
    function initializeWebSocket() {
        if (typeof Pusher === 'undefined') {
            console.error('[WS] Pusher no está disponible');
            updateConnectionStatus('failed');
            return;
        }

        try {
            pusher = new Pusher('{{ env("REVERB_APP_KEY", "sistema_bordados_key") }}', {
                wsHost: '{{ env("REVERB_HOST", "localhost") }}',
                wsPort: {{ env("REVERB_PORT", 8080) }},
                wssPort: {{ env("REVERB_PORT", 8080) }},
                forceTLS: {{ env("REVERB_SCHEME", "http") === "https" ? 'true' : 'false' }},
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                cluster: 'mt1',
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }
            });

            pusher.connection.bind('state_change', function(states) {
                updateConnectionStatus(states.current);
            });

            pusher.connection.bind('connected', function() {
                updateConnectionStatus('connected');
                subscribeToChannels();
            });

            pusher.connection.bind('error', function(err) {
                console.error('[WS] Error:', err);
                updateConnectionStatus('failed');
            });

        } catch (error) {
            console.error('[WS] Error inicializando:', error);
            updateConnectionStatus('failed');
        }
    }

    // ========================================
    // SUSCRIBIRSE A CANALES
    // ========================================
    function subscribeToChannels() {
        if (!pusher) return;

        // Solo suscribirse a UN canal para evitar duplicados
        // El backend envía a ambos canales, pero solo necesitamos escuchar uno
        const adminChannel = pusher.subscribe('private-orders.admin');
        adminChannel.bind('pusher:subscription_succeeded', () => console.log('[WS] Suscrito a orders.admin'));
        adminChannel.bind('status.changed', handleStatusChanged);
        adminChannel.bind('message.created', onMessageCreated);
        adminChannel.bind('production.blocked', handleProductionBlocked);
    }

    // ========================================
    // MANEJADORES DE EVENTOS WEBSOCKET
    // ========================================
    function handleStatusChanged(data) {
        console.log('[WS] status.changed:', data);
        loadUnreadMessages();

        if (typeof window.notificarCambioEstado === 'function') {
            window.notificarCambioEstado(data.order_number, data.new_status);
        }
    }

    function handleProductionBlocked(data) {
        console.log('[WS] production.blocked:', data);
        loadUnreadMessages();

        if (typeof window.notificarBloqueo === 'function') {
            window.notificarBloqueo(data.order_number, data.reason);
        }
    }

    /**
     * Handler principal para message.created
     * Filtra mensajes propios y evita duplicados
     */
    function onMessageCreated(data) {
        // === FILTRO 1: Evitar procesar el mismo mensaje dos veces ===
        if (lastProcessedMessageId === data.id) {
            return;
        }
        lastProcessedMessageId = data.id;

        // Resetear después de 2 segundos
        setTimeout(() => {
            if (lastProcessedMessageId === data.id) {
                lastProcessedMessageId = null;
            }
        }, 2000);

        // === FILTRO 2: Mensaje propio - NO mostrar notificación ===
        // Debug: mostrar objeto completo para diagnóstico
        console.log('[WS] Raw data received:', JSON.stringify(data));

        // Usar parseInt para asegurar comparación numérica correcta
        const myUserId = parseInt(window.currentUserId, 10) || 0;
        const msgCreatorId = parseInt(data.created_by, 10) || 0;

        console.log('[WS] message.created - myUserId:', myUserId, 'msgCreatorId:', msgCreatorId, 'raw created_by:', data.created_by);

        // Si es mi propio mensaje, NO mostrar toast ni badge
        if (myUserId > 0 && msgCreatorId > 0 && myUserId === msgCreatorId) {
            console.log('[WS] Mensaje propio, ignorando notificación');
            return;
        }

        console.log('[WS] Mensaje de otro usuario, procesando notificación');

        // Agregar al dropdown
        const newMessage = {
            id: data.id,
            type: 'message',
            title: data.order_number,
            content: `${data.creator}: ${data.message.substring(0, 60)}${data.message.length > 60 ? '...' : ''}`,
            icon: 'fas fa-comment text-primary',
            timestamp: data.time_ago || 'Ahora',
            read: false,
            order_id: data.order_id
        };

        if (!messages.find(m => m.id === data.id)) {
            messages.unshift(newMessage);
            renderMessages();
            updateBadge(messages.length);
            flashMessagesIcon();
        }

        // Mostrar toast
        if (typeof window.notificarMensaje === 'function') {
            window.notificarMensaje(data.order_number, data.creator, data.message);
        }

        // Notificar al drawer si está abierto
        if (window.MessageDrawer && window.MessageDrawer.isOpen && window.MessageDrawer.orderId == data.order_id) {
            window.MessageDrawer.addIncomingMessage(data);
        }

        // Actualizar la sección de Comunicación Interna si estamos en la página del pedido
        if (typeof window.addMessageToOrderPage === 'function') {
            window.addMessageToOrderPage(data);
        }
    }

    // ========================================
    // FUNCIONES DE UI
    // ========================================
    function renderMessages() {
        const list = document.getElementById('messages-list');
        const noMessages = document.getElementById('no-messages');
        const loadingEl = document.getElementById('messages-loading');

        if (!list) return;

        if (loadingEl) loadingEl.style.display = 'none';
        list.querySelectorAll('.message-item').forEach(el => el.remove());

        if (messages.length === 0) {
            if (noMessages) noMessages.style.display = 'block';
            return;
        }

        if (noMessages) noMessages.style.display = 'none';

        messages.forEach((msg) => {
            const item = document.createElement('div');
            item.className = 'dropdown-item message-item' + (msg.read ? '' : ' bg-light');
            item.dataset.messageId = msg.id;
            item.dataset.orderId = msg.order_id;

            item.innerHTML = `
                <div class="media">
                    <i class="${msg.icon} mr-2 mt-1"></i>
                    <div class="media-body">
                        <h3 class="dropdown-item-title" style="font-size: 0.9rem;">
                            ${msg.title}
                            <span class="float-right">
                                <button type="button" class="btn btn-xs btn-link text-muted mark-read-btn p-0" data-id="${msg.id}" title="Marcar como leído">
                                    <i class="fas fa-check"></i>
                                </button>
                            </span>
                        </h3>
                        <p class="text-sm mb-0">${msg.content}</p>
                        <p class="text-xs text-muted mb-1">
                            <i class="far fa-clock mr-1"></i>${msg.timestamp}
                        </p>
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-xs btn-outline-info mr-1 open-drawer-btn"
                                    data-order-id="${msg.order_id}"
                                    data-order-number="${msg.title}"
                                    title="Responder rápido">
                                <i class="fas fa-reply mr-1"></i>Responder
                            </button>
                            <a href="/admin/orders/${msg.order_id}" class="btn btn-xs btn-outline-primary" title="Ver pedido">
                                <i class="fas fa-eye mr-1"></i>Ver
                            </a>
                        </div>
                    </div>
                </div>
            `;

            item.querySelector('.mark-read-btn')?.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                markAsRead(msg.id);
            });

            item.querySelector('.open-drawer-btn')?.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const orderId = this.dataset.orderId;
                const orderNumber = this.dataset.orderNumber;
                const messageId = msg.id; // ID del mensaje que disparó el drawer
                // Cerrar dropdown
                const dropdownEl = document.getElementById('navbar-messages-dropdown');
                if (dropdownEl) {
                    $(dropdownEl).find('[data-toggle="dropdown"]').dropdown('hide');
                }
                // Abrir drawer con messageId para marcar como leído después
                if (window.MessageDrawer) {
                    window.MessageDrawer.open(orderId, orderNumber, null, messageId);
                }
            });

            list.insertBefore(item, noMessages);
        });
    }

    function updateBadge(count) {
        const badge = document.getElementById('messages-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function flashMessagesIcon() {
        const dropdown = document.getElementById('navbar-messages-dropdown');
        if (dropdown) {
            dropdown.classList.add('pulse-animation');
            setTimeout(() => dropdown.classList.remove('pulse-animation'), 1000);
        }
    }

    function updateConnectionStatus(status) {
        const indicator = document.getElementById('ws-status');
        const text = document.getElementById('ws-status-text');
        if (!indicator || !text) return;

        const states = {
            'connected': { class: 'badge badge-success', text: 'En línea' },
            'connecting': { class: 'badge badge-warning', text: 'Conectando...' },
            'disconnected': { class: 'badge badge-secondary', text: 'Desconectado' },
            'failed': { class: 'badge badge-danger', text: 'Sin conexión' }
        };

        const state = states[status] || states['disconnected'];
        indicator.className = state.class;
        text.innerText = state.text;
    }

    // ========================================
    // INICIALIZACIÓN
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        loadUnreadMessages();
        setTimeout(initializeWebSocket, 500);

        document.getElementById('mark-all-read-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markAllAsRead();
        });

        document.getElementById('refresh-messages-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            loadUnreadMessages();
        });

        const dropdown = document.getElementById('navbar-messages-dropdown');
        if (dropdown) {
            $(dropdown).find('[data-toggle="dropdown"]').on('click', function() {
                setTimeout(loadUnreadMessages, 100);
            });
        }
    });

    // Exponer funciones necesarias
    window.loadUnreadMessages = loadUnreadMessages;
    window.markMessageAsRead = markAsRead;

    // Exponer pusher para obtener socket_id (usado por toOthers())
    window.getPusherSocketId = function() {
        return pusher && pusher.connection ? pusher.connection.socket_id : null;
    };

})();
</script>

<style>
.pulse-animation { animation: pulse 0.5s ease-in-out 2; }
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }

#navbar-messages-dropdown .dropdown-menu { min-width: 320px; }
#navbar-messages-dropdown .message-item {
    white-space: normal;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #f4f4f4;
}
#navbar-messages-dropdown .message-item:hover { background-color: #f8f9fa; }
#navbar-messages-dropdown .message-item.bg-light { background-color: #fff3cd !important; }
#navbar-messages-dropdown .mark-read-btn:hover { color: #28a745 !important; }
#navbar-messages-dropdown .reply-btn:hover { background-color: #007bff; color: #fff !important; }
</style>
@endpush
