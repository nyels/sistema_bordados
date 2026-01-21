@extends('adminlte::page')

@section('title', 'Pedidos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clipboard-list mr-2"></i> Control de Pedidos</h1>
        <div>
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </a>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ========================================
         KPIs OPERATIVOS
    ======================================== --}}
    <div class="row">
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $kpis['para_producir'] }}</h3>
                    <p>Para Producir</p>
                </div>
                <div class="icon"><i class="fas fa-play-circle"></i></div>
                <a href="javascript:;" class="small-box-footer kpi-filter" data-status="confirmed">
                    Ver confirmados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $kpis['bloqueados'] }}</h3>
                    <p>Bloqueados</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
                <a href="javascript:;" class="small-box-footer kpi-filter" data-blocked="1">
                    Ver bloqueados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $kpis['en_produccion'] }}</h3>
                    <p>En Produccion</p>
                </div>
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <a href="javascript:;" class="small-box-footer kpi-filter" data-status="in_production">
                    Ver en produccion <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpis['para_entregar'] }}</h3>
                    <p>Para Entregar</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
                <a href="javascript:;" class="small-box-footer kpi-filter" data-status="ready">
                    Ver listos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box {{ $kpis['retrasados'] > 0 ? 'bg-maroon' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $kpis['retrasados'] }}</h3>
                    <p>Retrasados</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="javascript:;" class="small-box-footer kpi-filter" data-delayed="1">
                    Ver retrasados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ========================================
         FILTROS
    ======================================== --}}
    @include('admin.orders._filters')

    {{-- ========================================
         TABLA DE PEDIDOS (Contenedor AJAX)
    ======================================== --}}
    <div id="orders-table-container">
        @include('admin.orders._table')
    </div>
@stop

@section('css')
<style>
    .small-box .inner h3 {
        font-size: 2.2rem;
        font-weight: bold;
    }
    .small-box .inner p {
        font-size: 0.9rem;
    }
    #orders-table-container {
        position: relative;
        min-height: 200px;
    }
    #orders-table-container.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        z-index: 10;
    }
    #orders-table-container.loading::before {
        content: 'Cargando...';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 11;
        font-weight: bold;
        color: #333;
    }
    .filter-toggle.active {
        box-shadow: 0 0 0 3px rgba(0,123,255,0.5);
    }
</style>
@stop

@section('js')
<script>
(function() {
    'use strict';

    var baseUrl = '{{ route("admin.orders.index") }}';
    var container = document.getElementById('orders-table-container');
    var currentFilters = {};

    // ========================================
    // FUNCIÓN AJAX PARA CARGAR TABLA
    // ========================================
    function loadTable(filters) {
        filters = filters || {};
        currentFilters = filters;

        var params = new URLSearchParams();
        Object.keys(filters).forEach(function(key) {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        var url = baseUrl + (params.toString() ? '?' + params.toString() : '');

        container.classList.add('loading');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function(response) {
            return response.text();
        })
        .then(function(html) {
            container.innerHTML = html;
            // Actualizar URL sin recargar
            window.history.replaceState({}, '', url);
        })
        .catch(function(error) {
            console.error('Error:', error);
        })
        .finally(function() {
            container.classList.remove('loading');
        });
    }

    // ========================================
    // FUNCIÓN PARA OBTENER FILTROS ACTUALES
    // ========================================
    function getFiltersFromForm() {
        var filters = {};
        var status = document.getElementById('filter-status');
        var payment = document.getElementById('filter-payment');
        var urgency = document.getElementById('filter-urgency');
        var blocked = document.getElementById('filter-blocked');
        var delayed = document.getElementById('filter-delayed');

        if (status && status.value) filters.status = status.value;
        if (payment && payment.value) filters.payment_status = payment.value;
        if (urgency && urgency.value) filters.urgency = urgency.value;
        if (blocked && blocked.classList.contains('active')) filters.blocked = '1';
        if (delayed && delayed.classList.contains('active')) filters.delayed = '1';

        return filters;
    }

    // ========================================
    // EVENTOS DE FILTROS
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        // Selects
        ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function() {
                    loadTable(getFiltersFromForm());
                });
            }
        });

        // Toggles
        ['filter-blocked', 'filter-delayed'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.classList.toggle('active');
                    loadTable(getFiltersFromForm());
                });
            }
        });

        // Limpiar filtros
        var clearBtn = document.getElementById('filter-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Reset selects
                ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = '';
                });
                // Reset toggles
                ['filter-blocked', 'filter-delayed'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.classList.remove('active');
                });
                loadTable({});
            });
        }

        // KPI clicks
        document.querySelectorAll('.kpi-filter').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                var filters = {};

                // Reset form controls
                ['filter-status', 'filter-payment', 'filter-urgency'].forEach(function(id) {
                    var input = document.getElementById(id);
                    if (input) input.value = '';
                });
                ['filter-blocked', 'filter-delayed'].forEach(function(id) {
                    var input = document.getElementById(id);
                    if (input) input.classList.remove('active');
                });

                // Aplicar filtro del KPI
                if (this.dataset.status) {
                    filters.status = this.dataset.status;
                    var statusSelect = document.getElementById('filter-status');
                    if (statusSelect) statusSelect.value = this.dataset.status;
                }
                if (this.dataset.blocked) {
                    filters.blocked = '1';
                    var blockedBtn = document.getElementById('filter-blocked');
                    if (blockedBtn) blockedBtn.classList.add('active');
                }
                if (this.dataset.delayed) {
                    filters.delayed = '1';
                    var delayedBtn = document.getElementById('filter-delayed');
                    if (delayedBtn) delayedBtn.classList.add('active');
                }

                loadTable(filters);
            });
        });

        // Paginacion AJAX
        container.addEventListener('click', function(e) {
            var link = e.target.closest('.pagination a');
            if (link) {
                e.preventDefault();
                var url = new URL(link.href);
                var params = {};
                url.searchParams.forEach(function(value, key) {
                    params[key] = value;
                });
                loadTable(params);
            }
        });
    });
})();
</script>
@stop
