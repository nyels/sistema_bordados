@extends('adminlte::page')

@section('title', 'Calendario de Produccion')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-calendar-alt mr-2"></i> Calendario de Produccion</h1>
        <a href="{{ route('admin.production.queue') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-industry mr-1"></i> Cola de Produccion
        </a>
    </div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css" rel="stylesheet">
<style>
    /* ============================================ */
    /* CALENDARIO ERP - ESTILOS ENTERPRISE          */
    /* ============================================ */

    #productionCalendar {
        background: #fff;
        border-radius: 4px;
        padding: 15px;
        min-height: 650px;
    }

    /* Leyenda de estados */
    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 15px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    .calendar-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #495057;
    }
    .calendar-legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        flex-shrink: 0;
    }

    /* Capacidad semanal */
    .week-capacity-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 0 0 3px 3px;
    }

    /* Eventos arrastrables vs bloqueados */
    .calendar-draggable {
        cursor: grab;
        border-left: 3px solid rgba(255,255,255,0.5) !important;
        transition: box-shadow 0.15s;
    }
    .calendar-draggable:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    .calendar-draggable:active {
        cursor: grabbing;
    }
    .calendar-locked {
        cursor: default;
        opacity: 0.75;
    }
    .calendar-locked::after {
        content: '\f023';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        font-size: 8px;
        position: absolute;
        top: 2px;
        right: 4px;
        opacity: 0.6;
    }

    /* Eventos con urgencia */
    .fc-event {
        font-size: 11px !important;
        padding: 2px 4px !important;
        border-radius: 3px !important;
        margin-bottom: 1px !important;
    }

    /* Loader overlay */
    .calendar-loader {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.8);
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .calendar-loader.active {
        display: flex;
    }

    /* Toast de feedback */
    .calendar-toast {
        position: fixed;
        top: 70px;
        right: 20px;
        z-index: 9999;
        min-width: 320px;
        max-width: 450px;
        animation: slideInRight 0.3s ease;
    }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Barra de capacidad en header de semana */
    .fc-col-header-cell {
        position: relative;
    }
    .capacity-indicator {
        font-size: 10px;
        font-weight: 600;
        display: block;
        line-height: 1.2;
    }
    .capacity-indicator.full { color: #dc3545; }
    .capacity-indicator.high { color: #fd7e14; }
    .capacity-indicator.normal { color: #28a745; }

    /* Day cells con color de capacidad */
    .fc-daygrid-day.capacity-full {
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
    .fc-daygrid-day.capacity-high {
        background-color: rgba(253, 126, 20, 0.04) !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #productionCalendar { padding: 8px; min-height: 500px; }
        .fc-event { font-size: 10px !important; }
        .calendar-legend { gap: 8px; }
        .calendar-legend-item { font-size: 11px; }
    }

    /* Tooltip custom */
    .calendar-tooltip {
        background: #343a40;
        color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        max-width: 280px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        pointer-events: none;
    }
    .calendar-tooltip strong { color: #ffc107; }
    .calendar-tooltip .tt-row { margin-bottom: 3px; }
</style>
@stop

@section('content')

    {{-- LEYENDA --}}
    <div class="calendar-legend">
        <div class="calendar-legend-item">
            <div class="calendar-legend-dot" style="background: #6c757d;"></div> Borrador
        </div>
        <div class="calendar-legend-item">
            <div class="calendar-legend-dot" style="background: #007bff;"></div> Confirmado
        </div>
        <div class="calendar-legend-item">
            <div class="calendar-legend-dot" style="background: #6610f2;"></div> En Produccion
        </div>
        <div class="calendar-legend-item">
            <div class="calendar-legend-dot" style="background: #28a745;"></div> Listo
        </div>
        <div class="calendar-legend-item">
            <div class="calendar-legend-dot" style="background: #17a2b8;"></div> Entregado
        </div>
        <div class="calendar-legend-item" style="margin-left: auto;">
            <i class="fas fa-grip-vertical text-muted mr-1"></i> Arrastrable = Borrador / Confirmado
        </div>
        <div class="calendar-legend-item">
            <i class="fas fa-lock text-muted mr-1"></i> Bloqueado = En Produccion+
        </div>
    </div>

    {{-- CALENDARIO --}}
    <div class="card card-outline card-primary">
        <div class="card-body p-0 position-relative">
            <div class="calendar-loader" id="calendarLoader">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Validando con servidor...</div>
                </div>
            </div>
            <div id="productionCalendar"></div>
        </div>
    </div>

    {{-- CONTENEDOR DE TOASTS --}}
    <div id="calendarToasts"></div>

    {{-- TOOLTIP FLOTANTE --}}
    <div class="calendar-tooltip" id="calendarTooltip" style="display: none; position: fixed;"></div>

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // =========================================================================
    // CONFIGURACION
    // =========================================================================

    const ROUTES = {
        events: @json(route('admin.production.calendar.events')),
        reschedule: @json(url('admin/production/calendar')),
        orderShow: @json(url('admin/orders')),
    };
    const CSRF = '{{ csrf_token() }}';
    const WEEKLY_CAPACITY = {{ $weeklyCapacity }};

    // Estado local (cache de capacidades)
    let weekCapacities = {};
    let calendarInstance = null;

    // =========================================================================
    // INICIALIZACION FULLCALENDAR
    // =========================================================================

    const calendarEl = document.getElementById('productionCalendar');

    calendarInstance = new FullCalendar.Calendar(calendarEl, {
        // --- Vista ---
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1, // Lunes
        height: 'auto',
        dayMaxEvents: 4,
        moreLinkText: function(num) { return '+' + num + ' mas'; },
        fixedWeekCount: false,

        // --- Header ---
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
        },

        // --- Fuente de datos ---
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(ROUTES.events + '?' + new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
            }), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                weekCapacities = data.week_capacities || {};
                successCallback(data.events || []);
                requestAnimationFrame(() => paintCapacityIndicators());
            })
            .catch(err => {
                console.error('[Calendar] Error cargando eventos:', err);
                failureCallback(err);
                showToast('error', 'Error al cargar el calendario. Intente recargar la pagina.');
            });
        },

        // --- Drag & Drop ---
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,
        dragScroll: true,

        eventDragStart: function(info) {
            if (!info.event.extendedProps.can_reschedule) {
                return;
            }
            calendarEl.classList.add('dragging');
        },

        eventDrop: function(info) {
            calendarEl.classList.remove('dragging');

            const event = info.event;
            const props = event.extendedProps;

            // GATE FRONTEND: Solo visual — el backend decide
            if (!props.can_reschedule) {
                info.revert();
                showToast('error', 'Este pedido no puede reprogramarse (estado: ' + props.status_label + ')');
                return;
            }

            const newDate = formatDate(event.start);
            const orderId = props.order_id;

            // Verificacion local de capacidad (optimista, backend confirma)
            const targetWeekKey = getIsoWeekKey(event.start);
            const targetCapacity = weekCapacities[targetWeekKey];
            if (targetCapacity && targetCapacity.is_full) {
                info.revert();
                showToast('warning',
                    'Semana ' + targetCapacity.week + ' llena (' + targetCapacity.used + '/' + targetCapacity.max + '). ' +
                    'No hay capacidad disponible.'
                );
                return;
            }

            // ENVIAR AL BACKEND
            showLoader(true);
            rescheduleOrder(orderId, newDate, info);
        },

        // --- Click en evento ---
        eventClick: function(info) {
            const props = info.event.extendedProps;
            window.location.href = props.show_url;
        },

        // --- Tooltip hover ---
        eventMouseEnter: function(info) {
            const props = info.event.extendedProps;
            const tooltip = document.getElementById('calendarTooltip');
            const urgencyBadge = props.urgency === 'express'
                ? '<span style="color:#dc3545;">EXPRESS</span>'
                : props.urgency === 'urgente'
                    ? '<span style="color:#fd7e14;">URGENTE</span>'
                    : '';

            const lockIcon = props.can_reschedule
                ? '<span style="color:#28a745;"><i class="fas fa-unlock-alt"></i> Reprogramable</span>'
                : '<span style="color:#dc3545;"><i class="fas fa-lock"></i> Congelado</span>';

            tooltip.innerHTML =
                '<div class="tt-row"><strong>' + props.order_number + '</strong> ' + urgencyBadge + '</div>' +
                '<div class="tt-row"><i class="fas fa-user mr-1"></i> ' + props.client + '</div>' +
                '<div class="tt-row"><i class="fas fa-tag mr-1"></i> ' + props.status_label + '</div>' +
                '<div class="tt-row"><i class="fas fa-dollar-sign mr-1"></i> $' + props.total.toLocaleString('es-MX', {minimumFractionDigits: 2}) + '</div>' +
                '<div class="tt-row">' + lockIcon + '</div>';

            tooltip.style.display = 'block';

            const rect = info.el.getBoundingClientRect();
            tooltip.style.top = (rect.bottom + 6) + 'px';
            tooltip.style.left = Math.min(rect.left, window.innerWidth - 300) + 'px';
        },

        eventMouseLeave: function() {
            document.getElementById('calendarTooltip').style.display = 'none';
        },

        // --- Rendering hooks ---
        eventContent: function(arg) {
            const props = arg.event.extendedProps;
            const urgIcon = props.urgency === 'express'
                ? '<i class="fas fa-bolt" style="color:#ffc107;"></i> '
                : props.urgency === 'urgente'
                    ? '<i class="fas fa-exclamation" style="color:#fd7e14;"></i> '
                    : '';
            const lockIcon = props.can_reschedule ? '' : ' <i class="fas fa-lock" style="font-size:8px;opacity:0.6;"></i>';

            return {
                html: urgIcon + '<b>' + props.order_number + '</b>' + lockIcon +
                      '<br><span style="font-size:10px;opacity:0.85;">' + props.client + '</span>'
            };
        },

        // --- Dias pasados ---
        dayCellDidMount: function(arg) {
            const today = new Date();
            today.setHours(0,0,0,0);
            if (arg.date < today) {
                arg.el.style.backgroundColor = 'rgba(0,0,0,0.02)';
            }
        },

        loading: function(isLoading) {
            if (!isLoading) {
                requestAnimationFrame(() => paintCapacityIndicators());
            }
        },
    });

    calendarInstance.render();

    // PASO 13: Soporte para ?focus=YYYY-MM-DD (navegación desde orders/create)
    const focusParam = new URLSearchParams(window.location.search).get('focus');
    if (focusParam && /^\d{4}-\d{2}-\d{2}$/.test(focusParam)) {
        calendarInstance.gotoDate(focusParam);
    }

    // =========================================================================
    // REPROGRAMACION BACKEND
    // =========================================================================

    function rescheduleOrder(orderId, newDate, dragInfo) {
        fetch(ROUTES.reschedule + '/' + orderId + '/reschedule', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ new_promised_date: newDate }),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            showLoader(false);

            if (ok && data.success) {
                // OK: Mover confirmado por backend
                showToast('success', data.message);
                // Refrescar capacidades
                calendarInstance.refetchEvents();
            } else {
                // ERROR: Revertir posicion
                dragInfo.revert();

                let msg = data.message || 'Error al reprogramar.';
                if (data.suggestion) {
                    msg += '<br><br><i class="fas fa-lightbulb text-warning mr-1"></i> <strong>Sugerencia:</strong> ' +
                           'Siguiente semana disponible: <strong>' + data.suggestion + '</strong>';
                }
                showToast('error', msg);
            }
        })
        .catch(err => {
            showLoader(false);
            dragInfo.revert();
            console.error('[Calendar] Error de red:', err);
            showToast('error', 'Error de conexion. Verifique su red e intente de nuevo.');
        });
    }

    // =========================================================================
    // INDICADORES DE CAPACIDAD
    // =========================================================================

    function paintCapacityIndicators() {
        // Pintar celdas de dia segun capacidad de su semana
        document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            const dateStr = cell.getAttribute('data-date');
            if (!dateStr) return;

            const date = new Date(dateStr + 'T12:00:00');
            const weekKey = getIsoWeekKey(date);
            const cap = weekCapacities[weekKey];

            cell.classList.remove('capacity-full', 'capacity-high');
            if (cap) {
                if (cap.is_full) {
                    cell.classList.add('capacity-full');
                } else if (cap.utilization_percent >= 70) {
                    cell.classList.add('capacity-high');
                }
            }
        });
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function getIsoWeekKey(date) {
        // Calculo de semana ISO
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        return d.getUTCFullYear() + '-W' + weekNo;
    }

    function showLoader(visible) {
        const loader = document.getElementById('calendarLoader');
        if (visible) loader.classList.add('active');
        else loader.classList.remove('active');
    }

    function showToast(type, message) {
        const container = document.getElementById('calendarToasts');
        const id = 'toast-' + Date.now();

        const colors = {
            success: { bg: '#d4edda', border: '#28a745', icon: 'fa-check-circle', title: 'Reprogramado' },
            error: { bg: '#f8d7da', border: '#dc3545', icon: 'fa-exclamation-circle', title: 'Error' },
            warning: { bg: '#fff3cd', border: '#ffc107', icon: 'fa-exclamation-triangle', title: 'Atencion' },
        };
        const c = colors[type] || colors.error;

        const toast = document.createElement('div');
        toast.id = id;
        toast.className = 'calendar-toast';
        toast.innerHTML =
            '<div class="alert mb-0" style="background:' + c.bg + '; border-left: 4px solid ' + c.border + '; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<div>' +
                        '<strong><i class="fas ' + c.icon + ' mr-1"></i> ' + c.title + '</strong>' +
                        '<div class="mt-1" style="font-size: 13px;">' + message + '</div>' +
                    '</div>' +
                    '<button type="button" class="close ml-3" onclick="this.closest(\'.calendar-toast\').remove()">&times;</button>' +
                '</div>' +
            '</div>';

        container.appendChild(toast);

        // Auto-dismiss
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
        }, type === 'success' ? 3000 : 6000);
    }
});
</script>
@stop
