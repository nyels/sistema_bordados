@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h1>
        <small class="text-muted">{{ now()->format('d/m/Y H:i') }}</small>
    </div>
@stop

@section('content')
    {{-- Acciones Rápidas - ARRIBA DEL TODO --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-dark mb-0">
                <div class="card-header py-2">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <a href="{{ route('admin.orders.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-1"></i> Nuevo Pedido
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <a href="{{ route('admin.purchases.create') }}" class="btn btn-info btn-block">
                                <i class="fas fa-shopping-cart mr-1"></i> Nueva Compra
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <a href="{{ route('admin.production.queue') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-industry mr-1"></i> Cola Producción
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-list mr-1"></i> Todos los Pedidos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         SECCIÓN: CONTROL DE PRODUCCIÓN
    ======================================== --}}
    <div class="row mb-2">
        <div class="col-12">
            <span class="text-uppercase font-weight-bold" style="font-size: 0.95rem; color: #007bff; letter-spacing: 0.5px;">
                <i class="fas fa-industry mr-2"></i>Control de Producción
            </span>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $kpis['para_producir'] }}</h3>
                    <p>Para Producir</p>
                </div>
                <div class="icon"><i class="fas fa-play-circle"></i></div>
                <a href="{{ route('admin.orders.index', ['status' => 'confirmed']) }}" class="small-box-footer">
                    Ver listos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box bg-warning">
                <div class="inner text-white">
                    <h3>{{ $kpis['bloqueados'] }}</h3>
                    <p>Bloqueados</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
                <a href="{{ route('admin.orders.index', ['blocked' => 1]) }}" class="small-box-footer text-white">
                    Ver bloqueos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $kpis['en_produccion'] }}</h3>
                    <p>En Producción</p>
                </div>
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <a href="{{ route('admin.orders.index', ['status' => 'in_production']) }}" class="small-box-footer">
                    Ver producción <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $kpis['para_entregar'] }}</h3>
                    <p>Para Entregar</p>
                </div>
                <div class="icon"><i class="fas fa-truck"></i></div>
                <a href="{{ route('admin.orders.index', ['status' => 'ready']) }}" class="small-box-footer">
                    Ver listos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box {{ $kpis['retrasados'] > 0 ? 'bg-danger' : 'bg-maroon' }}">
                <div class="inner">
                    <h3>{{ $kpis['retrasados'] }}</h3>
                    <p>Retrasados</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
                <a href="{{ route('admin.orders.index', ['delayed' => 1]) }}" class="small-box-footer">
                    Ver retrasados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Materiales Bajo Stock - Pertenece a Producción --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
            <div class="small-box {{ $insumosEnRiesgo > 0 ? 'bg-danger' : 'bg-purple' }}">
                <div class="inner">
                    <h3>{{ $insumosEnRiesgo }}</h3>
                    <p>Materiales Bajo Stock</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="{{ route('admin.inventory.index', ['stock_status' => 'BAJO']) }}" class="small-box-footer">
                    Ver inventario <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ========================================
         SECCIÓN: VENTAS Y STOCK PT
    ======================================== --}}
    <div class="row mb-2 mt-3">
        <div class="col-12">
            <span class="text-uppercase font-weight-bold" style="font-size: 0.95rem; color: #28a745; letter-spacing: 0.5px;">
                <i class="fas fa-chart-line mr-2"></i>Ventas y Stock de Producto Terminado
            </span>
        </div>
    </div>
    <div class="row ventas-stock-row mb-3">
        {{-- Ventas del Mes - Segmentadas --}}
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-olive h-100">
                <div class="inner">
                    <h3>${{ number_format($ventasSegmentadas['total'], 0) }}</h3>
                    <p class="mb-1">Ventas {{ $nombreMes }}</p>
                    <div class="ventas-desglose">
                        <span><i class="fas fa-cash-register mr-1"></i>POS: ${{ number_format($ventasSegmentadas['pos'], 0) }}</span>
                        <span class="mx-2">|</span>
                        <span><i class="fas fa-file-invoice mr-1"></i>Pedidos: ${{ number_format($ventasSegmentadas['pedidos'], 0) }}</span>
                    </div>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="small-box-footer">
                    Ver entregados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- KPI: Bajo Stock (stock > 0 pero <= alerta) --}}
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-warning h-100">
                <div class="inner">
                    <h3 class="text-white">{{ $productosBajoStock }}</h3>
                    <p class="text-white">PT Bajo Stock</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="{{ route('admin.finished-goods-stock.index') }}" class="small-box-footer">
                    Ver stock PT <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- KPI: Agotados (stock = 0) --}}
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="small-box {{ $productosAgotados > 0 ? 'bg-danger' : 'bg-teal' }} h-100">
                <div class="inner">
                    <h3 class="text-white">{{ $productosAgotados }}</h3>
                    <p class="text-white">PT Agotados</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <a href="{{ route('admin.finished-goods-stock.index') }}" class="small-box-footer">
                    Ver stock PT <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Analytics --}}
    @if ($mesesConVentas->isNotEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Analytics de Ventas</h3>
                        <div class="card-tools">
                            <select id="mes-selector" class="form-control form-control-sm" style="width: 180px;">
                                @foreach ($mesesConVentas as $mes)
                                    <option value="{{ $mes['year'] }}-{{ $mes['month'] }}"
                                        {{ $loop->first ? 'selected' : '' }}>
                                        {{ $mes['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-12 mb-3 mb-lg-4">
                                <div class="card card-outline card-primary h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i><span class="d-none d-sm-inline">Ventas por Mes (Últimos 4 meses)</span><span class="d-sm-none">Ventas x Mes</span></h5>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chart-ventas-mes"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Solo pedidos entregados.</div>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12 mb-3 mb-lg-4">
                                <div class="card card-outline card-info h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-calendar-week mr-1"></i><span class="d-none d-sm-inline">Ventas por Semana -</span><span class="d-sm-none">Semana:</span>
                                            <span id="label-mes-semanas">{{ $analyticsData['mesSeleccionado']['label'] ?? 'Sin datos' }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chart-ventas-semana"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Solo semanas con ventas reales.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-7 col-md-7 col-12 mb-3 mb-lg-4">
                                <div class="card card-outline card-warning h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-trophy mr-1"></i><span class="d-none d-sm-inline">Top 5 Productos -</span><span class="d-sm-none">Top 5:</span>
                                            <span id="label-mes-productos">{{ $analyticsData['mesSeleccionado']['label'] ?? 'Sin datos' }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body chart-container">
                                        <canvas id="chart-top-bar"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Por cantidad de piezas.</div>
                                </div>
                            </div>

                            <div class="col-lg-5 col-md-5 col-12 mb-3 mb-lg-4">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i><span class="d-none d-sm-inline">Distribución de Ventas</span><span class="d-sm-none">Distribución</span></h5>
                                    </div>
                                    <div class="card-body chart-container d-flex align-items-center justify-content-center">
                                        <canvas id="chart-top-pie"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Por valor de venta ($).</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Sin datos de analytics.</strong> No hay pedidos entregados.
                </div>
            </div>
        </div>
    @endif

@stop

@section('meta_tags')
    {{-- PWA / Web App Meta Tags --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIS Bordados">
    <meta name="theme-color" content="#343a40">
    <meta name="format-detection" content="telephone=no">
@stop

@section('css')
    <style>
        /* ========================================
           BASE STYLES - Desktop First
        ======================================== */
        .small-box .inner h3 {
            font-size: 2.2rem;
        }

        .small-box .inner p {
            font-size: 1rem;
        }

        .small-box .icon {
            font-size: 70px;
        }

        /* Forzar texto blanco en widgets bg-warning */
        .small-box.bg-warning .inner,
        .small-box.bg-warning .inner h3,
        .small-box.bg-warning .inner p,
        .small-box.bg-warning .small-box-footer {
            color: #fff !important;
        }

        /* Desglose de ventas Pedidos/POS */
        .ventas-desglose {
            font-size: 0.85rem;
            margin-top: 5px;
            color: #fff;
        }

        /* Igualar altura de widgets en fila de ventas/stock */
        .ventas-stock-row {
            display: flex;
            flex-wrap: wrap;
        }

        .ventas-stock-row > div {
            display: flex;
        }

        .ventas-stock-row .small-box {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .ventas-stock-row .small-box .inner {
            flex: 1;
        }

        /* Contenedor de gráficas responsive */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* ========================================
           WEB APP STYLES - Touch & Mobile Friendly
        ======================================== */

        /* Botones touch-friendly */
        .btn {
            min-height: 44px;
            min-width: 44px;
        }

        /* Small-box clickable areas */
        .small-box .small-box-footer {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ========================================
           RESPONSIVE: Tablets (max-width: 991px)
        ======================================== */
        @media (max-width: 991.98px) {
            .small-box .inner h3 {
                font-size: 1.8rem;
            }

            .small-box .inner p {
                font-size: 0.9rem;
            }

            .small-box .icon {
                font-size: 55px;
            }

            /* Analytics charts responsivo */
            .chart-container {
                height: 200px;
            }
        }

        /* ========================================
           RESPONSIVE: Mobile Large (max-width: 767px)
        ======================================== */
        @media (max-width: 767.98px) {
            /* Título del dashboard */
            .content-header h1 {
                font-size: 1.5rem;
            }

            /* Widgets más compactos */
            .small-box .inner h3 {
                font-size: 1.6rem;
            }

            .small-box .inner p {
                font-size: 0.85rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .small-box .icon {
                font-size: 45px;
                right: 10px;
            }

            .small-box .inner {
                padding: 10px;
            }

            /* Ventas desglose en columna en móvil */
            .ventas-desglose {
                font-size: 0.75rem;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .ventas-desglose .mx-2 {
                display: none;
            }

            /* Botones de acciones rápidas */
            .card-body .btn-block {
                font-size: 0.85rem;
                padding: 10px 8px;
            }

            /* Section labels */
            .text-uppercase.font-weight-bold {
                font-size: 0.85rem !important;
            }

            /* Analytics cards */
            .card-title {
                font-size: 0.95rem;
            }

            .chart-container {
                height: 180px;
            }
        }

        /* ========================================
           RESPONSIVE: Mobile Small (max-width: 575px)
        ======================================== */
        @media (max-width: 575.98px) {
            /* Widgets compactos para 2 columnas */
            .small-box {
                margin-bottom: 10px;
            }

            .small-box .inner h3 {
                font-size: 1.4rem;
            }

            .small-box .inner p {
                font-size: 0.75rem;
            }

            .small-box .icon {
                font-size: 35px;
                top: 10px;
                right: 8px;
            }

            .small-box .small-box-footer {
                font-size: 0.75rem;
                padding: 8px 10px;
            }

            /* Acciones rápidas en grid compacto */
            .card-body .btn-block {
                font-size: 0.75rem;
                padding: 8px 5px;
            }

            .card-body .btn-block i {
                display: block;
                margin-bottom: 3px;
                font-size: 1.1rem;
            }

            .card-body .btn-block .mr-1 {
                margin-right: 0 !important;
            }

            /* Section labels más pequeños */
            .text-uppercase.font-weight-bold {
                font-size: 0.8rem !important;
            }

            /* Analytics responsivo */
            #mes-selector {
                width: 140px !important;
                font-size: 0.8rem;
            }

            .card-title {
                font-size: 0.85rem;
            }

            .chart-container {
                height: 160px;
            }

            .card-footer.small {
                font-size: 0.7rem;
            }
        }

        /* ========================================
           RESPONSIVE: Mobile XS (max-width: 400px)
        ======================================== */
        @media (max-width: 400px) {
            .small-box .inner h3 {
                font-size: 1.2rem;
            }

            .small-box .inner p {
                font-size: 0.7rem;
            }

            .small-box .icon {
                font-size: 28px;
            }

            /* Ventas widget especial */
            .ventas-desglose {
                font-size: 0.65rem;
            }

            .chart-container {
                height: 140px;
            }
        }

        /* ========================================
           LANDSCAPE MODE - Móviles horizontales
        ======================================== */
        @media (max-height: 500px) and (orientation: landscape) {
            .small-box {
                margin-bottom: 8px;
            }

            .small-box .inner {
                padding: 8px;
            }

            .small-box .inner h3 {
                font-size: 1.3rem;
            }

            .small-box .inner p {
                font-size: 0.8rem;
            }

            .chart-container {
                height: 150px;
            }
        }

        /* ========================================
           PWA / FULLSCREEN ADJUSTMENTS
        ======================================== */
        @media (display-mode: standalone) {
            /* Cuando se usa como PWA instalada */
            .content-wrapper {
                padding-top: env(safe-area-inset-top);
            }
        }

        /* ========================================
           TOUCH OPTIMIZATIONS
        ======================================== */
        @media (hover: none) and (pointer: coarse) {
            /* Dispositivos táctiles */
            .small-box {
                cursor: pointer;
                transition: transform 0.15s ease;
            }

            .small-box:active {
                transform: scale(0.98);
            }

            .btn {
                transition: transform 0.1s ease;
            }

            .btn:active {
                transform: scale(0.95);
            }
        }

        /* ========================================
           PRINT STYLES
        ======================================== */
        @media print {
            .small-box .small-box-footer {
                display: none;
            }

            .card-tools {
                display: none;
            }
        }
    </style>
@stop

@section('js')
    <script>
        (function() {
            'use strict';

            // GUARD: Evitar ejecución múltiple
            if (window._dashboardChartsInitialized) {
                console.warn('Dashboard charts already initialized, skipping...');
                return;
            }
            window._dashboardChartsInitialized = true;

            // Datos del servidor
            var DATA = @json($analyticsData);

            // Colores
            var C = {
                green: 'rgba(40, 167, 69, 0.7)',
                greenB: 'rgb(40, 167, 69)',
                blue: 'rgba(23, 162, 184, 0.7)',
                blueB: 'rgb(23, 162, 184)',
                yellow: 'rgba(255, 193, 7, 0.7)',
                yellowB: 'rgb(255, 193, 7)',
                pie: ['rgba(0,123,255,0.8)', 'rgba(40,167,69,0.8)', 'rgba(255,193,7,0.8)', 'rgba(220,53,69,0.8)',
                    'rgba(108,117,125,0.8)'
                ]
            };

            // Formatear moneda
            function fmt(v) {
                return '$' + Number(v).toLocaleString('es-MX', {
                    maximumFractionDigits: 0
                });
            }

            // FUNCIÓN CRÍTICA: Destruir chart existente en un canvas
            function destroyExistingChart(canvasId) {
                var canvas = document.getElementById(canvasId);
                if (!canvas) return null;

                // Método 1: Chart.getChart (Chart.js 3+)
                var existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }

                return canvas;
            }

            // ========================================
            // GRÁFICA FIJA: Ventas por Mes
            // Se crea UNA vez, NUNCA se actualiza
            // ========================================
            function initVentasMes() {
                var canvas = destroyExistingChart('chart-ventas-mes');
                if (!canvas) return;

                var d = DATA.ventasPorMes || [];

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: d.map(function(x) {
                            return x.label;
                        }),
                        datasets: [{
                            label: 'Ventas',
                            data: d.map(function(x) {
                                return x.total;
                            }),
                            backgroundColor: C.green,
                            borderColor: C.greenB,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return fmt(ctx.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(v) {
                                        return fmt(v);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ========================================
            // GRÁFICAS DINÁMICAS: Se actualizan por AJAX
            // ========================================
            function updateVentasSemana(d) {
                var canvas = destroyExistingChart('chart-ventas-semana');
                if (!canvas) return;

                d = d || [];

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: d.map(function(x) {
                            return x.label;
                        }),
                        datasets: [{
                            label: 'Ventas',
                            data: d.map(function(x) {
                                return x.total;
                            }),
                            backgroundColor: C.blue,
                            borderColor: C.blueB,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return fmt(ctx.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(v) {
                                        return fmt(v);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function updateTopBar(d) {
                var canvas = destroyExistingChart('chart-top-bar');
                if (!canvas) return;

                d = d || [];

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: d.map(function(x) {
                            return x.producto.length > 12 ? x.producto.substr(0, 12) + '...' : x
                                .producto;
                        }),
                        datasets: [{
                            label: 'Unidades',
                            data: d.map(function(x) {
                                return x.cantidad;
                            }),
                            backgroundColor: C.yellow,
                            borderColor: C.yellowB,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.raw + ' pz';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            function updateTopPie(d) {
                var canvas = destroyExistingChart('chart-top-pie');
                if (!canvas) return;

                d = d || [];

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: d.map(function(x) {
                            return x.producto.length > 12 ? x.producto.substr(0, 12) + '...' : x
                                .producto;
                        }),
                        datasets: [{
                            data: d.map(function(x) {
                                return x.valor;
                            }),
                            backgroundColor: C.pie,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 10
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.label + ': ' + fmt(ctx.raw);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function updateContextual(data) {
                var lbl = data && data.mesSeleccionado ? data.mesSeleccionado.label : 'Sin datos';

                var el1 = document.getElementById('label-mes-semanas');
                var el2 = document.getElementById('label-mes-productos');
                if (el1) el1.textContent = lbl;
                if (el2) el2.textContent = lbl;

                updateVentasSemana(data ? data.ventasPorSemana : []);
                updateTopBar(data ? data.topProductos : []);
                updateTopPie(data ? data.topProductos : []);
            }

            // ========================================
            // INICIALIZACIÓN
            // ========================================
            function init() {
                // Gráfica fija (solo una vez)
                initVentasMes();

                // Gráficas contextuales
                if (DATA && DATA.mesSeleccionado) {
                    updateContextual(DATA);
                }

                // Evento del selector
                var sel = document.getElementById('mes-selector');
                if (sel) {
                    sel.addEventListener('change', function() {
                        var parts = this.value.split('-');
                        var year = parts[0];
                        var month = parts[1];

                        this.disabled = true;

                        fetch('{{ route('home.analytics') }}?year=' + year + '&month=' + month, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(json) {
                                if (json.success) {
                                    updateContextual(json.data);
                                }
                            })
                            .catch(function(e) {
                                console.error(e);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error al cargar datos',
                                    confirmButtonColor: '#3085d6'
                                });
                            })
                            .finally(function() {
                                sel.disabled = false;
                            });
                    });
                }
            }

            // Ejecutar cuando DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@stop
