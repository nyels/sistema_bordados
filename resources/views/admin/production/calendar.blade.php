@extends('adminlte::page')

@section('title', 'Calendario de Produccion')

@section('content_header')
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css" rel="stylesheet">
<style>
    /* ============================================ */
    /* CALENDARIO ERP — ENTERPRISE SaaS 2026        */
    /* ============================================ */

    .content-wrapper { background: #f0f2f5 !important; }

    /* --- PAGE HEADER --- */
    .cal-page-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        color: #fff;
        padding: 20px 24px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .cal-page-header h1 {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .cal-page-header h1 i { color: #93c5fd; font-size: 20px; }
    .cal-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .cal-header-actions .btn {
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        border: 2px solid rgba(255,255,255,0.25);
        color: #fff;
        background: transparent;
        transition: all 0.2s;
    }
    .cal-header-actions .btn:hover {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.5);
        color: #fff;
    }

    /* --- LEYENDA ENTERPRISE --- */
    .cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        padding: 14px 20px;
        background: #f0f7ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        margin-bottom: 16px;
        align-items: center;
        box-shadow: 0 1px 3px rgba(37,99,235,0.08);
    }
    .cal-legend-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e3a5f;
        margin-right: 4px;
    }
    .cal-legend-item {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 14px;
        font-weight: 500;
        color: #334155;
    }
    .cal-legend-dot {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .cal-legend-sep {
        width: 1px;
        height: 20px;
        background: #93c5fd;
        margin: 0 4px;
    }
    .cal-legend-hint {
        font-size: 13px;
        color: #1e40af;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .cal-legend-hint i { font-size: 13px; color: #3b82f6; }

    /* --- CARD CALENDARIO --- */
    .cal-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #dbeafe;
        border-top: 3px solid #2563eb;
        box-shadow: 0 2px 12px rgba(37,99,235,0.08);
        overflow: hidden;
    }

    /* --- FULLCALENDAR OVERRIDES --- */
    #productionCalendar {
        padding: 16px 20px 20px;
        min-height: 650px;
    }

    /* Toolbar */
    #productionCalendar .fc-toolbar {
        margin-bottom: 16px !important;
    }
    #productionCalendar .fc-toolbar-title {
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #1e3a5f !important;
        text-transform: capitalize;
    }
    #productionCalendar .fc-button {
        font-size: 14px !important;
        font-weight: 600 !important;
        padding: 7px 14px !important;
        border-radius: 8px !important;
        border: 2px solid #bfdbfe !important;
        background: #fff !important;
        color: #1e40af !important;
        box-shadow: none !important;
        transition: all 0.15s !important;
    }
    #productionCalendar .fc-button:hover {
        background: #eff6ff !important;
        border-color: #60a5fa !important;
        color: #1e3a5f !important;
    }
    #productionCalendar .fc-button-active,
    #productionCalendar .fc-button:active {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #fff !important;
    }
    #productionCalendar .fc-button-group .fc-button {
        border-radius: 0 !important;
    }
    #productionCalendar .fc-button-group .fc-button:first-child {
        border-radius: 8px 0 0 8px !important;
    }
    #productionCalendar .fc-button-group .fc-button:last-child {
        border-radius: 0 8px 8px 0 !important;
    }
    #productionCalendar .fc-today-button {
        border-radius: 8px 0 0 8px !important;
    }
    #productionCalendar .fc-prev-button,
    #productionCalendar .fc-next-button {
        padding: 7px 10px !important;
    }

    /* Header columnas (días de semana) */
    #productionCalendar .fc-col-header-cell {
        background: #eff6ff;
        border-color: #dbeafe;
        padding: 10px 0 !important;
    }
    #productionCalendar .fc-col-header-cell-cushion {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #1e40af !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-decoration: none !important;
    }

    /* Celdas de día */
    #productionCalendar .fc-daygrid-day {
        border-color: #dbeafe !important;
        transition: background 0.15s;
    }
    #productionCalendar .fc-daygrid-day-number {
        font-weight: 800 !important;
        font-size: 16px !important;
        color: #1e3a5f !important;
        padding: 6px 10px !important;
        text-decoration: none !important;
    }
    #productionCalendar .fc-day-today {
        background: #eff6ff !important;
    }
    #productionCalendar .fc-day-today .fc-daygrid-day-number {
        background: #2563eb;
        color: #fff !important;
        border-radius: 8px;
        padding: 3px 10px !important;
        margin: 3px;
    }
    #productionCalendar .fc-day-other .fc-daygrid-day-number {
        color: #94a3b8 !important;
        font-weight: 600 !important;
    }

    /* Días pasados */
    .fc-daygrid-day.cal-past {
        background: #f8fafc !important;
    }
    .fc-daygrid-day.cal-past .fc-daygrid-day-number {
        color: #94a3b8 !important;
    }

    /* Capacidad semanal en celdas */
    .fc-daygrid-day.capacity-full {
        background-color: rgba(220, 53, 69, 0.06) !important;
    }
    .fc-daygrid-day.capacity-high {
        background-color: rgba(253, 126, 20, 0.05) !important;
    }

    /* --- EVENTOS (barras multi-día) --- */
    #productionCalendar .fc-event {
        font-size: 13px !important;
        padding: 4px 8px !important;
        border-radius: 6px !important;
        margin-bottom: 2px !important;
        border: none !important;
        cursor: pointer;
        transition: transform 0.1s, box-shadow 0.15s;
    }
    #productionCalendar .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        z-index: 10;
    }
    #productionCalendar .fc-daygrid-event-dot { display: none; }

    /* Barra horizontal multi-día (FullCalendar aplica fc-h-event) */
    #productionCalendar .fc-h-event {
        border: none !important;
        border-left: 4px solid var(--fc-event-border-color, #2563eb) !important;
        border-radius: 4px 6px 6px 4px !important;
        opacity: 0.92;
    }
    #productionCalendar .fc-h-event .fc-event-main {
        padding: 2px 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #productionCalendar .fc-h-event:hover {
        opacity: 1;
    }

    /* Evento arrastrable */
    .cal-evt-draggable { cursor: grab !important; }
    .cal-evt-draggable:active { cursor: grabbing !important; }

    /* Evento bloqueado */
    .cal-evt-locked {
        cursor: default !important;
        opacity: 0.8;
    }

    /* More link */
    #productionCalendar .fc-daygrid-more-link {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #2563eb !important;
        padding: 2px 6px;
    }

    /* --- LOADER --- */
    .cal-loader {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(2px);
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    .cal-loader.active { display: flex; }
    .cal-loader-inner {
        text-align: center;
        padding: 24px;
    }
    .cal-loader-inner .spinner-border {
        width: 2.5rem;
        height: 2.5rem;
        border-width: 3px;
    }
    .cal-loader-inner p {
        margin-top: 12px;
        font-size: 15px;
        font-weight: 600;
        color: #475569;
    }

    /* --- TOAST --- */
    .cal-toast {
        position: fixed;
        top: 70px;
        right: 20px;
        z-index: 9999;
        min-width: 340px;
        max-width: 460px;
        animation: calSlideIn 0.3s ease;
    }
    .cal-toast .alert {
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border: none;
        border-left: 5px solid;
    }
    @keyframes calSlideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* --- TOOLTIP --- */
    .cal-tooltip {
        background: #1e3a5f;
        color: #e0f2fe;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        max-width: 320px;
        box-shadow: 0 8px 24px rgba(30,58,95,0.45);
        z-index: 10000;
        pointer-events: none;
        line-height: 1.5;
        border: 1px solid #2563eb;
    }
    .cal-tooltip strong { color: #60a5fa; }
    .cal-tooltip .tt-row {
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cal-tooltip .tt-row i { width: 16px; text-align: center; color: #7dd3fc; font-size: 13px; }
    .cal-tooltip .tt-divider {
        border-top: 1px solid #2563eb;
        margin: 6px 0;
    }
    .cal-tooltip .tt-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
    }

    /* --- PANEL DETALLE DIA --- */
    .cal-day-detail {
        background: #f0f7ff;
        border-left: 4px solid #2563eb;
        border-radius: 0 10px 10px 0;
        padding: 14px 20px;
        font-size: 15px;
        color: #1e3a5f;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 16px;
    }
    .cal-day-detail-content {
        flex: 1;
        min-width: 200px;
        font-weight: 500;
    }
    .cal-day-detail-content strong { color: #1e40af; }
    .cal-day-detail .cap-green { color: #16a34a; font-weight: 700; }
    .cal-day-detail .cap-orange { color: #d97706; font-weight: 700; }
    .cal-day-detail .cap-red { color: #dc2626; font-weight: 700; }
    .cal-day-detail-orders {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .cal-day-detail-orders a {
        font-size: 13px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        background: #dbeafe;
        color: #1e40af;
        text-decoration: none;
        transition: background 0.15s;
    }
    .cal-day-detail-orders a:hover {
        background: #bfdbfe;
    }

    /* Celda seleccionada en calendario */
    .fc-daygrid-day.cal-day-selected {
        background: #eff6ff !important;
        box-shadow: inset 0 0 0 2px #2563eb;
    }

    /* --- RESPONSIVE MOBILE --- */
    @media (max-width: 768px) {
        .cal-page-header {
            padding: 16px;
            border-radius: 8px;
        }
        .cal-page-header h1 { font-size: 18px; }
        .cal-header-actions .btn { font-size: 13px; padding: 6px 12px; }

        .cal-legend {
            gap: 10px;
            padding: 12px 14px;
        }
        .cal-legend-title { font-size: 13px; }
        .cal-legend-item { font-size: 13px; }
        .cal-legend-sep { display: none; }
        .cal-legend-hint { font-size: 12px; }

        #productionCalendar {
            padding: 10px;
            min-height: 450px;
        }
        #productionCalendar .fc-toolbar {
            flex-direction: column;
            gap: 8px;
        }
        #productionCalendar .fc-toolbar-title {
            font-size: 17px !important;
        }
        #productionCalendar .fc-button {
            font-size: 13px !important;
            padding: 6px 10px !important;
        }
        #productionCalendar .fc-col-header-cell-cushion {
            font-size: 11px !important;
            letter-spacing: 0;
        }
        #productionCalendar .fc-daygrid-day-number {
            font-size: 14px !important;
            padding: 4px 6px !important;
        }
        #productionCalendar .fc-event {
            font-size: 11px !important;
            padding: 3px 5px !important;
        }

        .cal-toast {
            min-width: unset;
            left: 12px;
            right: 12px;
            max-width: unset;
        }
        .cal-tooltip {
            font-size: 13px;
            max-width: 260px;
        }
    }

    /* Tablets */
    @media (min-width: 769px) and (max-width: 1024px) {
        #productionCalendar .fc-toolbar-title { font-size: 18px !important; }
        #productionCalendar .fc-event { font-size: 12px !important; }
    }
</style>
@stop

@section('content')

    {{-- PAGE HEADER --}}
    <div class="cal-page-header">
        <h1><i class="fas fa-calendar-alt"></i> Calendario de Produccion</h1>
        <div class="cal-header-actions">
            <a href="{{ route('admin.production.queue') }}" class="btn">
                <i class="fas fa-industry mr-1"></i> Cola de Produccion
            </a>
        </div>
    </div>

    {{-- LEYENDA --}}
    <div class="cal-legend">
        <span class="cal-legend-title">Estado:</span>
        <div class="cal-legend-item">
            <div class="cal-legend-dot" style="background: #6c757d;"></div> Borrador
        </div>
        <div class="cal-legend-item">
            <div class="cal-legend-dot" style="background: #2563eb;"></div> Confirmado
        </div>
        <div class="cal-legend-item">
            <div class="cal-legend-dot" style="background: #7c3aed;"></div> En Produccion
        </div>
        <div class="cal-legend-sep"></div>
        <div class="cal-legend-hint">
            <i class="fas fa-grip-vertical"></i> Arrastrable = Borrador / Confirmado
        </div>
        <div class="cal-legend-hint">
            <i class="fas fa-lock"></i> Bloqueado = En Produccion+
        </div>
        <div class="cal-legend-sep"></div>
        <div class="cal-legend-hint">
            <i class="fas fa-arrows-alt-h"></i> Barra = Duracion de produccion
        </div>
    </div>

    {{-- CALENDARIO --}}
    <div class="cal-card">
        <div class="position-relative">
            <div class="cal-loader" id="calendarLoader">
                <div class="cal-loader-inner">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p>Validando con servidor...</p>
                </div>
            </div>
            <div id="productionCalendar"></div>
        </div>
    </div>

    {{-- PANEL DETALLE DIA --}}
    <div class="cal-day-detail" id="calDayDetail">
        <div class="cal-day-detail-content" id="calDayDetailContent"></div>
    </div>

    {{-- CONTENEDOR DE TOASTS --}}
    <div id="calendarToasts"></div>

    {{-- TOOLTIP FLOTANTE --}}
    <div class="cal-tooltip" id="calendarTooltip" style="display: none; position: fixed;"></div>

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
            left: 'today,dayGridWeek,dayGridMonth prev,next',
            center: 'title',
            right: '',
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

            // Con barras multi-día, calcular nueva promised_date usando delta
            const deltaDays = (info.delta.days || 0) + (info.delta.months || 0) * 30;
            const oldPromised = new Date(props.promised_date + 'T12:00:00');
            oldPromised.setDate(oldPromised.getDate() + deltaDays);
            const newDate = formatDate(oldPromised);
            const orderId = props.order_id;

            // Verificacion local de capacidad (optimista, backend confirma)
            const targetWeekKey = getIsoWeekKey(oldPromised);
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

        // --- Click en dia (panel detalle) ---
        dateClick: function(info) {
            showDayDetail(info.dateStr);
        },

        // --- Tooltip hover ---
        eventMouseEnter: function(info) {
            const props = info.event.extendedProps;
            const tooltip = document.getElementById('calendarTooltip');

            var urgencyBadge = '';
            if (props.urgency === 'express') {
                urgencyBadge = '<span class="tt-status" style="background:#fef2f2;color:#dc2626;">EXPRESS</span>';
            } else if (props.urgency === 'urgente') {
                urgencyBadge = '<span class="tt-status" style="background:#fff7ed;color:#ea580c;">URGENTE</span>';
            }

            var lockHtml = props.can_reschedule
                ? '<span style="color:#4ade80;"><i class="fas fa-unlock-alt"></i> Reprogramable</span>'
                : '<span style="color:#f87171;"><i class="fas fa-lock"></i> Congelado</span>';

            var durationHtml = '';
            if (props.lead_time_days > 1) {
                durationHtml =
                    '<div class="tt-divider"></div>' +
                    '<div class="tt-row"><i class="fas fa-clock"></i> ' + props.lead_time_days + ' dias de produccion</div>' +
                    '<div class="tt-row"><i class="fas fa-play"></i> Inicio: ' + props.production_start_date + '</div>' +
                    '<div class="tt-row"><i class="fas fa-flag-checkered"></i> Entrega: ' + props.promised_date + '</div>';
            }

            tooltip.innerHTML =
                '<div class="tt-row"><strong>' + props.order_number + '</strong> ' + urgencyBadge + '</div>' +
                '<div class="tt-divider"></div>' +
                '<div class="tt-row"><i class="fas fa-user"></i> ' + (props.client || 'Stock') + '</div>' +
                '<div class="tt-row"><i class="fas fa-tag"></i> ' + props.status_label + '</div>' +
                '<div class="tt-row"><i class="fas fa-dollar-sign"></i> $' + props.total.toLocaleString('es-MX', {minimumFractionDigits: 2}) + '</div>' +
                durationHtml +
                '<div class="tt-divider"></div>' +
                '<div class="tt-row">' + lockHtml + '</div>';

            tooltip.style.display = 'block';

            var rect = info.el.getBoundingClientRect();
            var tooltipW = 320;
            var left = rect.left;
            if (left + tooltipW > window.innerWidth - 16) left = window.innerWidth - tooltipW - 16;
            if (left < 8) left = 8;
            tooltip.style.top = (rect.bottom + 8) + 'px';
            tooltip.style.left = left + 'px';
        },

        eventMouseLeave: function() {
            document.getElementById('calendarTooltip').style.display = 'none';
        },

        // --- Rendering hooks ---
        eventContent: function(arg) {
            const props = arg.event.extendedProps;
            const isMultiDay = props.lead_time_days > 1;

            var urgIcon = '';
            if (props.urgency === 'express') {
                urgIcon = '<i class="fas fa-bolt" style="color:#fbbf24;margin-right:3px;"></i>';
            } else if (props.urgency === 'urgente') {
                urgIcon = '<i class="fas fa-exclamation-circle" style="color:#fb923c;margin-right:3px;font-size:11px;"></i>';
            }
            var lockIcon = props.can_reschedule ? '' : ' <i class="fas fa-lock" style="font-size:9px;opacity:0.5;margin-left:3px;"></i>';

            if (isMultiDay) {
                return {
                    html: '<div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
                          urgIcon + '<b>' + props.order_number + '</b>' + lockIcon +
                          ' <span style="opacity:0.8;font-size:11px;">(' + props.lead_time_days + 'd)</span>' +
                          ' — ' + (props.client || 'Stock') +
                          '</div>'
                };
            }
            return {
                html: '<div style="line-height:1.35;overflow:hidden;">' +
                      urgIcon + '<b>' + props.order_number + '</b>' + lockIcon +
                      '<br><span style="font-size:12px;opacity:0.9;">' + (props.client || 'Stock') + '</span>' +
                      '</div>'
            };
        },

        eventDidMount: function(info) {
            var cls = info.event.extendedProps.can_reschedule ? 'cal-evt-draggable' : 'cal-evt-locked';
            info.el.classList.add(cls);
        },

        // --- Dias pasados ---
        dayCellDidMount: function(arg) {
            var today = new Date();
            today.setHours(0,0,0,0);
            if (arg.date < today) {
                arg.el.classList.add('cal-past');
            }
        },

        loading: function(isLoading) {
            if (!isLoading) {
                requestAnimationFrame(() => {
                    paintCapacityIndicators();
                    showDayDetail(formatDate(new Date()));
                });
            }
        },
    });

    calendarInstance.render();

    // Forzar botón "Hoy" siempre activo y que seleccione el día actual
    var todayBtn = calendarEl.querySelector('.fc-today-button');
    if (todayBtn) {
        todayBtn.removeAttribute('disabled');
        // Observer para re-habilitar tras cada navegación
        new MutationObserver(function() {
            if (todayBtn.disabled) todayBtn.removeAttribute('disabled');
        }).observe(todayBtn, { attributes: true, attributeFilter: ['disabled'] });

        todayBtn.addEventListener('click', function() {
            showDayDetail(formatDate(new Date()));
        });
    }

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

    // =========================================================================
    // PANEL DETALLE DIA
    // =========================================================================

    function showDayDetail(dateStr) {
        const panel = document.getElementById('calDayDetail');
        const content = document.getElementById('calDayDetailContent');

        // Marcar celda seleccionada
        document.querySelectorAll('.fc-daygrid-day.cal-day-selected').forEach(el => {
            el.classList.remove('cal-day-selected');
        });
        const cell = document.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
        if (cell) cell.classList.add('cal-day-selected');

        // Nombre del dia
        const dateObj = new Date(dateStr + 'T12:00:00');
        const dayNames = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        const monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const dayName = dayNames[dateObj.getDay()];
        const dayNum = dateObj.getDate();
        const monthName = monthNames[dateObj.getMonth()];

        // Pedidos activos ese dia (barras que cubren esa fecha)
        var dayEvents = [];
        if (calendarInstance) {
            calendarInstance.getEvents().forEach(function(ev) {
                var evStart = ev.start;
                var evEnd = ev.end || evStart;
                var clickDate = new Date(dateStr + 'T00:00:00');
                var startDate = new Date(evStart.getFullYear(), evStart.getMonth(), evStart.getDate());
                // end es exclusivo en FullCalendar, así que el último día visible es end - 1
                var endDate = new Date(evEnd.getFullYear(), evEnd.getMonth(), evEnd.getDate());
                if (clickDate >= startDate && clickDate < endDate) {
                    dayEvents.push(ev.extendedProps);
                }
            });
        }

        // Pedidos activos en la semana (todos los que cruzan la semana)
        var weekKey = getIsoWeekKey(dateObj);
        var cap = weekCapacities[weekKey];
        var capMax = cap ? cap.max : WEEKLY_CAPACITY;

        // Contar pedidos activos en la semana desde los eventos del calendario
        var weekStart = getWeekMonday(dateObj);
        var weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 7);
        var weekActiveCount = 0;
        if (calendarInstance) {
            calendarInstance.getEvents().forEach(function(ev) {
                var evStart = ev.start;
                var evEnd = ev.end || new Date(evStart.getTime() + 86400000);
                var st = ev.extendedProps.status;
                if ((st === 'confirmed' || st === 'in_production') && evStart < weekEnd && evEnd > weekStart) weekActiveCount++;
            });
        }
        var capPercent = capMax > 0 ? Math.round((weekActiveCount / capMax) * 100) : 0;
        var capClass = capPercent >= 100 ? 'cap-red' : (capPercent >= 70 ? 'cap-orange' : 'cap-green');
        var capLabel = capPercent >= 100 ? 'Saturado' : (capPercent >= 70 ? 'Alta carga' : 'Disponible');

        // Construir texto
        var ordersList = dayEvents.map(function(p) {
            return '<a href="' + p.show_url + '" title="Ver ' + p.order_number + '">' + p.order_number + '</a>';
        }).join('');

        var ordersText = dayEvents.length > 0
            ? 'Pedidos: <strong>' + dayEvents.length + '</strong> (' + dayEvents.map(function(p) { return p.order_number; }).join(', ') + ')'
            : 'Sin pedidos';

        content.innerHTML =
            '<div><strong>' + dayName + ', ' + dayNum + ' de ' + monthName + '</strong> — ' +
            ordersText +
            ' · Carga semanal: <span class="' + capClass + '">' + weekActiveCount + '/' + capMax + '</span>' +
            ' · <span class="' + capClass + '">' + capLabel + ' (' + capPercent + '%)</span></div>' +
            (dayEvents.length > 0 ? '<div class="cal-day-detail-orders">' + ordersList + '</div>' : '');
    }

    function getWeekMonday(date) {
        var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        var day = d.getDay();
        var diff = day === 0 ? -6 : 1 - day; // Lunes = inicio de semana
        d.setDate(d.getDate() + diff);
        return d;
    }

    function showLoader(visible) {
        const loader = document.getElementById('calendarLoader');
        if (visible) loader.classList.add('active');
        else loader.classList.remove('active');
    }

    function showToast(type, message) {
        var container = document.getElementById('calendarToasts');
        var id = 'toast-' + Date.now();

        var colors = {
            success: { bg: '#f0fdf4', border: '#22c55e', icon: 'fa-check-circle', title: 'Reprogramado', text: '#166534' },
            error: { bg: '#fef2f2', border: '#ef4444', icon: 'fa-exclamation-circle', title: 'Error', text: '#991b1b' },
            warning: { bg: '#fffbeb', border: '#f59e0b', icon: 'fa-exclamation-triangle', title: 'Atencion', text: '#92400e' },
        };
        var c = colors[type] || colors.error;

        var toast = document.createElement('div');
        toast.id = id;
        toast.className = 'cal-toast';
        toast.innerHTML =
            '<div class="alert mb-0" style="background:' + c.bg + '; border-left-color:' + c.border + '; color:' + c.text + ';">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<div>' +
                        '<strong style="font-size:15px;"><i class="fas ' + c.icon + ' mr-1"></i> ' + c.title + '</strong>' +
                        '<div class="mt-1" style="font-size:14px;">' + message + '</div>' +
                    '</div>' +
                    '<button type="button" class="close ml-3" style="color:' + c.text + ';opacity:0.6;" onclick="this.closest(\'.cal-toast\').remove()">&times;</button>' +
                '</div>' +
            '</div>';

        container.appendChild(toast);

        setTimeout(function() {
            var el = document.getElementById(id);
            if (el) {
                el.style.transition = 'opacity 0.3s, transform 0.3s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(30px)';
                setTimeout(function() { el.remove(); }, 300);
            }
        }, type === 'success' ? 3000 : 6000);
    }
});
</script>
@stop
