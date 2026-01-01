@extends('adminlte::page')

@section('title', 'Explorador de Bordado')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center w-100 explorer-header">
        <h1 class="m-0 explorer-title">
            <i class="fas fa-search-plus text-success mr-2"></i>Escáner Técnico
        </h1>
        <a href="javascript:void(0)" onclick="window.close()" class="btn-premium-close shadow-sm">
            <i class="fas fa-times-circle"></i>
            <span class="ml-2">Cerrar Vista</span>
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0" style="font-weight: 700; color: #374151;">
                            {{ $export->variant->name ?? ($export->design->name ?? 'Diseño sin nombre') }}
                        </h5>
                        <p class="text-muted small mb-0">ID: PRD-{{ str_pad($export->id, 3, '0', STR_PAD_LEFT) }} |
                            {{ $export->stitches_count }} puntadas</p>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex">
                            <a href="{{ route('admin.produccion.preview', $export->id) }}?download=1"
                                class="btn btn-premium-download-alt mr-2 shadow-sm">
                                <i class="fas fa-file-download"></i>
                                <span class="d-none d-md-inline ml-2">SVG</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0 position-relative"
                style="background-color: #f8fafc; height: 80vh; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                {{-- Technical Canvas Background --}}
                <div
                    style="position: absolute; top:0; left:0; width:100%; height:100%; 
                     background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
                     background-size: 20px 20px; opacity: 0.5; pointer-events: none;">
                </div>

                <div id="zoomContainer" class="zoom-container"
                    style="cursor: grab; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <div id="zoomWrapper" class="zoom-wrapper"
                        style="transition: transform 0.1s ease-out; transform-origin: center; display: flex; justify-content: center; align-items: center;">
                        {!! $svgContent !!}
                    </div>
                </div>

                {{-- Controles Flotantes --}}
                <div class="zoom-controls">
                    <button type="button" class="zoom-btn" id="btnZoomIn" title="Acercar (Rueda Arriba)">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="zoom-btn" id="btnZoomOut" title="Alejar (Rueda Abajo)">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="zoom-btn" id="btnZoomReset" title="Restaurar Vista">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                {{-- Pantalla de Ayuda (Overlay) --}}
                <div id="panningHint"
                    style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.6); color: white; padding: 10px 15px; border-radius: 80px; font-size: 12px; backdrop-filter: blur(4px); pointer-events: none; transition: opacity 0.3s;">
                    <i class="fas fa-mouse mr-2 text-success"></i>Usa la <strong>rueda del mouse</strong> para zoom y
                    <strong>arrastra</strong> para mover
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .zoom-controls {
            position: absolute;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 12px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .zoom-btn {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: #374151;
            font-size: 18px;
        }

        .zoom-btn:hover {
            background: #f0fdf4;
            border-color: #10b981;
            color: #10b981;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .zoom-wrapper svg {
            max-width: 90vw;
            max-height: 70vh;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.15));
            user-select: none;
            pointer-events: none;
            /* Dejar que el contenedor maneje el arrastre */
        }

        #zoomContainer:active {
            cursor: grabbing;
        }

        /* Botón Premium Close Responsivo - SaaS Luxury Style */
        .btn-premium-close {
            background: #ffffff !important;
            color: #2563eb !important;
            border: 2px solid #eff6ff !important;
            border-radius: 14px !important;
            font-weight: 700 !important;
            padding: 12px 24px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            text-decoration: none !important;
            white-space: nowrap;
        }

        .btn-premium-close:hover {
            background: #2563eb !important;
            color: #ffffff !important;
            border-color: #2563eb !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.3) !important;
        }

        .btn-premium-close i {
            font-size: 1.2rem;
        }

        .explorer-title {
            font-weight: 800;
            color: #111827;
            font-size: 1.5rem !important;
        }

        /* Botón Descarga Secundario */
        .btn-premium-download-alt {
            background: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            padding: 8px 16px !important;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none !important;
        }

        .btn-premium-download-alt:hover {
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-1px);
        }

        .btn-premium-close i {
            font-size: 1.1rem;
        }

        /* Ajustes Responsivos para Web App */
        @media (max-width: 768px) {
            .explorer-header {
                padding: 15px 0 !important;
            }

            .explorer-title {
                font-size: 1.15rem !important;
            }

            .btn-premium-close {
                padding: 10px 18px !important;
                font-size: 0.9rem !important;
                border-radius: 12px !important;
            }

            .btn-premium-close span {
                display: none;
            }

            .btn-premium-close i {
                margin: 0 !important;
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .explorer-title {
                display: flex;
                align-items: center;
            }

            .explorer-title span {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding: 0 5px !important;
            }

            .card-body {
                height: 70vh !important;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            let currentScale = 1;
            let isDragging = false;
            let startX, startY, transX = 0,
                transY = 0;

            function updateTransform() {
                $('#zoomWrapper').css('transform', `scale(${currentScale}) translate(${transX}px, ${transY}px)`);

                // Mostrar/Ocultar hint de paneo
                if (currentScale > 1) {
                    $('#panningHint').css('opacity', '1');
                } else {
                    $('#panningHint').css('opacity', '0.7');
                }
            }

            // Zoom In/Out
            $('#btnZoomIn').on('click', () => {
                currentScale = Math.min(currentScale + 0.5, 8);
                updateTransform();
            });
            $('#btnZoomOut').on('click', () => {
                currentScale = Math.max(currentScale - 0.5, 0.5);
                updateTransform();
            });
            $('#btnZoomReset').on('click', () => {
                currentScale = 1;
                transX = 0;
                transY = 0;
                updateTransform();
            });

            // Wheel Zoom
            $('#zoomContainer').on('wheel', function(e) {
                e.preventDefault();
                const delta = e.originalEvent.deltaY;
                currentScale = delta > 0 ? Math.max(currentScale - 0.2, 0.5) : Math.min(currentScale + 0.2,
                    8);
                updateTransform();
            });

            // Dragging (Pan)
            $('#zoomContainer').on('mousedown', function(e) {
                if (currentScale > 1) {
                    isDragging = true;
                    startX = e.pageX - transX;
                    startY = e.pageY - transY;
                    $(this).css('cursor', 'grabbing');
                }
            });

            $(window).on('mousemove', function(e) {
                if (isDragging) {
                    transX = e.pageX - startX;
                    transY = e.pageY - startY;
                    updateTransform();
                }
            }).on('mouseup', function() {
                isDragging = false;
                $('#zoomContainer').css('cursor', currentScale > 1 ? 'grab' : 'default');
            });
        });
    </script>
@stop
