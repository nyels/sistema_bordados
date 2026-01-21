/**
 * Sistema de Confirmaciones con SweetAlert2
 * ERP Sistema de Bordados
 *
 * Uso:
 * <form data-confirm="cancelar" data-confirm-title="¿Cancelar pedido?" data-confirm-text="Esta acción no se puede deshacer">
 * <button data-confirm="delete" data-confirm-title="¿Eliminar?" ...>
 */

document.addEventListener('DOMContentLoaded', function() {

    // Configuración de tipos de confirmación
    const confirmTypes = {
        // Cancelar pedido
        'cancelar': {
            icon: 'warning',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener'
        },
        // Eliminar registro
        'delete': {
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        },
        // Iniciar producción
        'produccion': {
            icon: 'question',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Sí, iniciar producción',
            cancelButtonText: 'Cancelar'
        },
        // Marcar como listo
        'listo': {
            icon: 'success',
            confirmButtonColor: '#007bff',
            confirmButtonText: 'Sí, marcar como listo',
            cancelButtonText: 'Cancelar'
        },
        // Acción genérica
        'default': {
            icon: 'question',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }
    };

    // Manejar formularios con confirmación
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const type = form.dataset.confirm || 'default';
            const config = confirmTypes[type] || confirmTypes['default'];
            const title = form.dataset.confirmTitle || '¿Está seguro?';
            const text = form.dataset.confirmText || '';
            const impact = form.dataset.confirmImpact || '';

            let htmlContent = text;
            if (impact) {
                htmlContent += '<br><br><small class="text-muted"><i class="fas fa-info-circle"></i> ' + impact + '</small>';
            }

            Swal.fire({
                title: title,
                html: htmlContent,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: config.confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: config.cancelButtonText,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Manejar botones con confirmación (para enlaces o acciones JS)
    document.querySelectorAll('[data-confirm-btn]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const type = btn.dataset.confirmBtn || 'default';
            const config = confirmTypes[type] || confirmTypes['default'];
            const title = btn.dataset.confirmTitle || '¿Está seguro?';
            const text = btn.dataset.confirmText || '';
            const href = btn.getAttribute('href');

            Swal.fire({
                title: title,
                text: text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: config.confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: config.cancelButtonText,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed && href) {
                    window.location.href = href;
                }
            });
        });
    });
});

/**
 * Función global para confirmaciones programáticas
 */
window.confirmarAccion = function(options) {
    return Swal.fire({
        title: options.title || '¿Está seguro?',
        html: options.html || options.text || '',
        icon: options.icon || 'question',
        showCancelButton: true,
        confirmButtonColor: options.confirmColor || '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: options.confirmText || 'Confirmar',
        cancelButtonText: options.cancelText || 'Cancelar',
        reverseButtons: true
    });
};

/**
 * Toast de notificación
 */
window.notificar = function(options) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: options.timer || 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: options.icon || 'success',
        title: options.title || 'Operación exitosa'
    });
};

/**
 * Alerta de éxito
 */
window.alertaExito = function(mensaje, titulo) {
    return Swal.fire({
        icon: 'success',
        title: titulo || '¡Completado!',
        text: mensaje,
        confirmButtonColor: '#28a745'
    });
};

/**
 * Alerta de error
 */
window.alertaError = function(mensaje, titulo) {
    return Swal.fire({
        icon: 'error',
        title: titulo || 'Error',
        text: mensaje,
        confirmButtonColor: '#dc3545'
    });
};
