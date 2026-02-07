{{--
    PASO 13-B: MODAL DE AGENDA MENSUAL (READ-ONLY · TABLET-FIRST)
    Consume: /admin/production/calendar/events (OrderCalendarReadService)
    NO escribe datos. NO modifica promised_date. NO drag & drop.
--}}

<style>
    /* ===== MODAL RESPONSIVE: TABLET/MOBILE ===== */
    @media (max-width: 991.98px) {
        #agendaMonthModal .modal-dialog {
            margin: 8px;
            max-width: calc(100% - 16px);
        }
        #agendaMonthModal .modal-body {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* ===== GRID CALENDARIO ===== */
    .agenda-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 3px;
        user-select: none;
    }
    .agenda-grid-header {
        text-align: center;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        color: #1a5276;
        padding: 8px 0;
        background: #eaf2f8;
        border-radius: 4px;
    }
    .agenda-day {
        min-height: 68px;
        padding: 5px 6px;
        border-radius: 6px;
        border: 1.5px solid #d5dbdf;
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
        background: #fff;
    }
    .agenda-day:hover {
        border-color: #2980b9;
        box-shadow: 0 2px 8px rgba(41,128,185,0.15);
    }
    .agenda-day.selected {
        border-color: #2471a3;
        background: #eaf2f8;
        box-shadow: 0 0 0 2px rgba(36,113,163,0.3);
    }
    .agenda-day.other-month {
        opacity: 0.3;
        pointer-events: none;
    }
    .agenda-day.today {
        border-color: #1a5276;
        border-width: 2.5px;
    }
    .agenda-day-number {
        font-weight: 700;
        font-size: 14px;
        color: #1a252f;
        line-height: 1;
    }
    .agenda-day.today .agenda-day-number {
        color: #1a5276;
    }
    .agenda-day-count {
        font-size: 11px;
        margin-top: 4px;
        font-weight: 600;
        color: #2c3e50;
    }
    .agenda-day-bar {
        position: absolute;
        bottom: 3px;
        left: 6px;
        right: 6px;
        height: 4px;
        border-radius: 2px;
    }

    /* Carga visual */
    .load-green  { background: #28a745; }
    .load-orange { background: #e67e22; }
    .load-red    { background: #e74c3c; }
    .text-load-green  { color: #1e8449; }
    .text-load-orange { color: #d35400; }
    .text-load-red    { color: #c0392b; }

    /* Panel detalle día */
    .agenda-day-detail {
        background: #eaf2f8;
        border-left: 4px solid #2471a3;
        border-radius: 0 8px 8px 0;
        padding: 12px 16px;
        font-size: 15px;
        color: #1a252f;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .agenda-detail-disclaimer {
        font-size: 12px;
        color: #2c3e50;
        white-space: nowrap;
        opacity: 0.7;
    }

    /* Nav mes */
    .agenda-nav-btn {
        border: none;
        background: none;
        font-size: 22px;
        padding: 4px 14px;
        cursor: pointer;
        color: #1a5276;
        border-radius: 6px;
        transition: background 0.15s;
    }
    .agenda-nav-btn:hover {
        background: #eaf2f8;
    }
    .agenda-month-title {
        font-size: 18px;
        font-weight: 700;
        color: #1a252f;
        text-transform: capitalize;
        min-width: 180px;
        text-align: center;
    }

    /* Leyenda */
    .agenda-legend {
        display: flex;
        gap: 16px;
        font-size: 14px;
        color: #2c3e50;
        flex-wrap: wrap;
    }
    .agenda-legend-dot {
        display: inline-block;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
    }

    @media (max-width: 575.98px) {
        .agenda-day {
            min-height: 52px;
            padding: 3px 4px;
        }
        .agenda-day-number {
            font-size: 12px;
        }
        .agenda-day-count {
            font-size: 10px;
        }
    }
</style>

<div class="modal fade" id="agendaMonthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border: none; box-shadow: 0 8px 30px rgba(0,0,0,0.15);">
            <div class="modal-header py-2" style="background: #1a5276; color: white;">
                <h5 class="modal-title mb-0">
                    <i class="fas fa-calendar-alt mr-2"></i> Agenda de Produccion
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-3 py-3">
                {{-- Navegacion mensual --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <button type="button" class="agenda-nav-btn" id="agendaPrev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="agenda-month-title" id="agendaMonthTitle"></span>
                    <button type="button" class="agenda-nav-btn" id="agendaNext">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                {{-- Leyenda --}}
                <div class="agenda-legend mb-3">
                    <span><span class="agenda-legend-dot load-green"></span> Disponible (&lt;70%)</span>
                    <span><span class="agenda-legend-dot load-orange"></span> Alta carga (70-99%)</span>
                    <span><span class="agenda-legend-dot load-red"></span> Saturado (100%)</span>
                </div>

                {{-- Grid --}}
                <div class="agenda-grid" id="agendaGrid"></div>

                {{-- Detalle dia seleccionado + disclaimer --}}
                <div class="agenda-day-detail mt-3" id="agendaDayDetail" style="display: none;">
                    <div id="agendaDayDetailContent"></div>
                    <div class="agenda-detail-disclaimer">
                        <i class="fas fa-lock mr-1"></i>
                        Fecha validada por el sistema al guardar.
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2" style="border-top: 1px solid #d5dbdf;">
                <a href="{{ url('admin/production/calendar') }}" target="_blank" rel="noopener"
                    class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt mr-1"></i> Abrir calendario completo
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- JS data attributes para que el script en @section('js') los lea --}}
<script>
    window.__agendaModalConfig = {
        eventsUrl: @json(route('admin.production.calendar.events')),
        calendarBaseUrl: @json(url('admin/production/calendar'))
    };
</script>
