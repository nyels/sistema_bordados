/**
 * Sistema de Notificaciones en Tiempo Real
 * ERP Sistema de Bordados
 *
 * Implementa polling AJAX con notificaciones toast
 * para cambios en pedidos, bloqueos y mensajes operativos.
 */

(function() {
    'use strict';

    // Configuración
    const POLL_INTERVAL = 30000; // 30 segundos
    const NOTIFICATION_DURATION = 5000;

    // Estado local para detectar cambios
    let lastCheck = null;
    let notificationCount = 0;

    /**
     * Mostrar toast de notificación
     */
    function showToast(options) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: options.timer || NOTIFICATION_DURATION,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: options.icon || 'info',
            title: options.title || 'Notificación',
            html: options.html || options.text || ''
        });
    }

    /**
     * Verificar nuevas notificaciones
     */
    function checkNotifications() {
        // Solo ejecutar si hay una ruta de notificaciones disponible
        if (typeof window.notificationsUrl === 'undefined') {
            return;
        }

        fetch(window.notificationsUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    showToast({
                        icon: notification.type || 'info',
                        title: notification.title,
                        html: notification.message
                    });
                });
            }
            lastCheck = new Date();
        })
        .catch(error => {
            console.log('Error checking notifications:', error);
        });
    }

    /**
     * Mostrar notificación de bloqueo de producción
     */
    window.notificarBloqueo = function(pedido, motivo) {
        showToast({
            icon: 'warning',
            title: 'Producción Bloqueada',
            html: `<strong>${pedido}</strong><br><small>${motivo}</small>`,
            timer: 8000
        });
    };

    /**
     * Mostrar notificación de liberación
     */
    window.notificarLiberacion = function(pedido) {
        showToast({
            icon: 'success',
            title: 'Pedido Liberado',
            html: `<strong>${pedido}</strong> puede continuar producción`
        });
    };

    /**
     * Mostrar notificación de mensaje operativo
     */
    window.notificarMensaje = function(pedido, remitente, mensaje) {
        showToast({
            icon: 'info',
            title: 'Nuevo Mensaje',
            html: `<strong>${pedido}</strong><br><small>${remitente}: ${mensaje.substring(0, 50)}...</small>`,
            timer: 6000
        });
    };

    /**
     * Notificación de cambio de estado
     */
    window.notificarCambioEstado = function(pedido, nuevoEstado) {
        const icons = {
            'confirmed': 'info',
            'in_production': 'warning',
            'ready': 'success',
            'delivered': 'success',
            'cancelled': 'error'
        };
        const labels = {
            'confirmed': 'Confirmado',
            'in_production': 'En Producción',
            'ready': 'Listo para Entregar',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado'
        };

        showToast({
            icon: icons[nuevoEstado] || 'info',
            title: 'Estado Actualizado',
            html: `<strong>${pedido}</strong> → ${labels[nuevoEstado] || nuevoEstado}`
        });
    };

    /**
     * Actualizar badge de notificaciones
     */
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    /**
     * Inicializar sistema de notificaciones
     */
    function init() {
        // Verificar soporte de notificaciones del navegador
        if ('Notification' in window && Notification.permission === 'default') {
            // Podríamos pedir permiso para notificaciones del sistema
            // Notification.requestPermission();
        }

        // Iniciar polling si está configurado
        if (window.enableNotificationPolling) {
            setInterval(checkNotifications, POLL_INTERVAL);
            // Primera verificación después de 5 segundos
            setTimeout(checkNotifications, 5000);
        }

        console.log('Sistema de notificaciones inicializado');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
