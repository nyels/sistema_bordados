/**
 * Sistema de Notificaciones - Funciones Helper
 * ERP Sistema de Bordados
 *
 * Proporciona funciones globales para mostrar toasts de notificación.
 * Las notificaciones en tiempo real se reciben via WebSocket (Laravel Echo/Reverb).
 * Ver: echo-notifications.js
 */

(function() {
    'use strict';

    const NOTIFICATION_DURATION = 5000;

    /**
     * Mostrar toast de notificación
     */
    function showToast(options) {
        if (typeof Swal === 'undefined') {
            console.log('[Toast]', options.title, options.html);
            return;
        }

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
    window.updateNotificationBadge = function(count) {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    };

    // Exponer showToast para uso directo
    window.showNotificationToast = showToast;

    console.log('[Notifications] Funciones de notificación cargadas');

})();
