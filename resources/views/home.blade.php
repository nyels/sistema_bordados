@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h1>
        <small class="text-muted">{{ now()->format('d/m/Y H:i') }}</small>
    </div>
@stop

@section('content')
    {{-- KPIs OPERATIVOS - Panel de Control --}}
    <div class="row">
        {{-- Fila 1: KPIs de Producción --}}
        <div class="col-xl col-lg-4 col-md-6 col-sm-6">
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

        <div class="col-xl col-lg-4 col-md-6 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $kpis['bloqueados'] }}</h3>
                    <p>Bloqueados</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
                <a href="{{ route('admin.orders.index', ['blocked' => 1]) }}" class="small-box-footer">
                    Ver bloqueos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 col-sm-6">
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

        <div class="col-xl col-lg-4 col-md-6 col-sm-6">
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

        <div class="col-xl col-lg-4 col-md-6 col-sm-6">
            <div class="small-box {{ $kpis['retrasados'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
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
    </div>

    {{-- KPIs Secundarios --}}
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-gradient-olive">
                <div class="inner">
                    <h3>${{ number_format($ventasDelMes, 0) }}</h3>
                    <p>Ventas {{ $nombreMes }}</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('admin.orders.index', ['status' => 'delivered']) }}" class="small-box-footer">
                    Ver entregados <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="small-box {{ $insumosEnRiesgo > 0 ? 'bg-danger' : 'bg-secondary' }}">
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

        <div class="col-lg-4 col-md-6">
            <div class="small-box {{ $productosBajoStock > 0 ? 'bg-warning' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $productosBajoStock }}</h3>
                    <p>Productos Bajo Stock</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
                <a href="{{ route('admin.finished-goods-stock.index', ['stock_status' => 'bajo']) }}" class="small-box-footer">
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
                            <div class="col-lg-6 mb-4">
                                <div class="card card-outline card-primary h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i>Ventas por Mes
                                            (Últimos 4 meses)</h5>
                                    </div>
                                    <div class="card-body" style="height: 250px;">
                                        <canvas id="chart-ventas-mes"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Solo pedidos entregados.</div>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <div class="card card-outline card-info h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-calendar-week mr-1"></i>Ventas por Semana -
                                            <span
                                                id="label-mes-semanas">{{ $analyticsData['mesSeleccionado']['label'] ?? 'Sin datos' }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 250px;">
                                        <canvas id="chart-ventas-semana"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Solo semanas con ventas reales.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-7 mb-4">
                                <div class="card card-outline card-warning h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-trophy mr-1"></i>Top 5 Productos -
                                            <span
                                                id="label-mes-productos">{{ $analyticsData['mesSeleccionado']['label'] ?? 'Sin datos' }}</span>
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 250px;">
                                        <canvas id="chart-top-bar"></canvas>
                                    </div>
                                    <div class="card-footer small text-muted py-1">Por cantidad de piezas.</div>
                                </div>
                            </div>

                            <div class="col-lg-5 mb-4">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-header py-2">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i>Distribución de
                                            Ventas</h5>
                                    </div>
                                    <div class="card-body d-flex align-items-center justify-content-center"
                                        style="height: 250px;">
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

    {{-- Acciones Rápidas --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{ route('admin.orders.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-1"></i> Nuevo Pedido
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{ route('admin.purchases.create') }}" class="btn btn-info btn-block">
                                <i class="fas fa-shopping-cart mr-1"></i> Nueva Compra
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{ route('admin.production.queue') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-industry mr-1"></i> Cola Producción
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-list mr-1"></i> Todos los Pedidos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box .inner h3 {
            font-size: 2.2rem;
        }

        .small-box .inner p {
            font-size: 1rem;
        }

        .small-box .icon {
            font-size: 70px;
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
                                alert('Error al cargar datos');
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
