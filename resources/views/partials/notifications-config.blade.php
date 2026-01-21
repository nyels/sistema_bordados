{{-- Configuración de notificaciones en tiempo real --}}
<script>
    // Habilitar polling de notificaciones
    window.enableNotificationPolling = true;

    // URL para obtener notificaciones
    window.notificationsUrl = "{{ route('admin.notifications.recent') }}";

    // URL para marcar como leídas
    window.markReadUrl = "{{ route('admin.notifications.mark-read') }}";

    // Token CSRF para peticiones POST
    window.csrfToken = "{{ csrf_token() }}";
</script>
