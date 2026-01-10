@extends('adminlte::page')

@section('title', 'Visualizador de Bordados')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem; letter-spacing: -0.5px;">Visualizador</h1>
            <p class="text-muted m-0 small">Analiza y visualiza archivos de bordado en tiempo real.</p>
        </div>
        <div>
            {{-- Breadcrumb o acciones globales si fueran necesarias --}}
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid pb-5">

        {{-- Main Container con Ancho Máximo Contenido --}}
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">

                {{-- ==================== ZONE 1: UPLOAD ==================== --}}
                <div class="card border-0 shadow-sm" id="uploadCard"
                    style="border-radius: 16px; overflow: hidden; transition: all 0.3s ease;">
                    <div class="card-body p-0">
                        <div class="upload-area position-relative d-flex flex-column align-items-center justify-content-center p-5"
                            style="min-height: 400px; border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.2s ease;">

                            <div class="icon-circle mb-4 d-flex align-items-center justify-content-center shadow-sm"
                                style="width: 80px; height: 80px; background: #fff; border-radius: 50%;">
                                <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 2.5rem;"></i>
                            </div>

                            <h3 class="font-weight-bold text-dark mb-2">Sube tu archivo de bordado</h3>
                            <p class="text-muted mb-4 text-center" style="max-width: 400px;">
                                Arrastra y suelta tu archivo aquí o haz clic para explorar.
                                <br><span class="small opacity-75">Soportamos .DST, .PES, .JEF, .EXP, .VP3, .XXX</span>
                            </p>

                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <span class="badge badge-light px-3 py-2 border text-muted">DST</span>
                                <span class="badge badge-light px-3 py-2 border text-muted">PES</span>
                                <span class="badge badge-light px-3 py-2 border text-muted">JEF</span>
                            </div>

                            <div class="alert alert-warning border-0 d-flex align-items-center py-2 px-3 mt-3"
                                style="background: #fffbeb; color: #92400e; border-radius: 8px; font-size: 0.85rem;">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <span>Archivos <b>.EMB (Wilcom)</b> no son soportados directamente.</span>
                            </div>

                        </div>
                        <input type="file" id="fileInput" class="d-none" accept=".dst,.pes,.jef,.exp,.vp3,.xxx,.emb">
                    </div>
                </div>

                {{-- ==================== ZONE 2: LOADING ==================== --}}
                <div id="loadingSection" class="text-center py-5 d-none bg-white shadow-sm rounded-lg"
                    style="min-height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 16px;">
                    <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <h4 class="font-weight-bold text-dark">Analizando Geometría...</h4>
                    <p class="text-muted">Extrayendo puntadas, colores y vectores.</p>
                </div>

                {{-- ==================== ZONE 3: RESULTS DASHBOARD ==================== --}}
                <div class="d-none animate__animated animate__fadeIn" id="resultCard">

                    {{-- Toolbar Superior --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 bg-white p-3 rounded shadow-sm"
                        style="border-radius: 12px !important;">
                        <div class="d-flex align-items-center mb-3 mb-md-0">
                            <div class="file-icon mr-3 d-flex align-items-center justify-content-center bg-light text-primary font-weight-bold rounded"
                                style="width: 48px; height: 48px; font-size: 1.2rem; border: 1px solid #e2e8f0;"
                                id="resFormatBadgeHeader">
                                PES
                            </div>
                            <div>
                                <h5 class="font-weight-bold m-0 text-dark text-truncate" id="resFileName"
                                    style="max-width: 300px; letter-spacing: -0.3px;">NombreArchivo.esp</h5>
                                <span class="text-muted small" id="resFileSize">250 KB</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm font-weight-bold px-3" id="btnNewUpload"
                                style="border-radius: 8px;">
                                <i class="fas fa-arrow-left mr-1"></i> Subir Otro
                            </button>
                            <button class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm" id="btnResetView"
                                style="border-radius: 8px;">
                                <i class="fas fa-compress-arrows-alt mr-1"></i> Centrar Vista
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Left Column: Visualization --}}
                        <div class="col-lg-7 mb-4">
                            <div class="card border-0 shadow-sm h-100" id="previewCard"
                                style="border-radius: 16px; overflow: hidden;">
                                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                    <h6 class="font-weight-bold text-uppercase text-muted small letter-spacing-1">Vista
                                        Previa</h6>
                                </div>
                                <div class="card-body p-0 d-flex align-items-center justify-content-center position-relative"
                                    style="background: #f8fafc; min-height: 450px;">
                                    {{-- Background Pattern for transparency feel --}}
                                    <div class="position-absolute w-100 h-100"
                                        style="opacity: 0.4; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;">
                                    </div>

                                    <div id="svgContainer" class="p-4"
                                        style="width: 100%; height: 100%; max-height: 500px; z-index: 1;">
                                        {{-- SVG Injected Here --}}
                                    </div>

                                    {{-- Floating Toolbar --}}
                                    <div id="svgToolbar"
                                        class="position-absolute d-flex gap-2 p-2 bg-white rounded shadow-sm animate__animated animate__fadeInUp"
                                        style="bottom: 20px; z-index: 10; left: 50%; transform: translateX(-50%); border: 1px solid #e2e8f0;">
                                        <button class="btn btn-sm btn-light border" id="btnZoomOut" title="Alejar"><i
                                                class="fas fa-minus text-secondary"></i></button>
                                        <button class="btn btn-sm btn-light border" id="btnZoomReset" title="Resetear"><i
                                                class="fas fa-compress-arrows-alt text-secondary"></i></button>
                                        <button class="btn btn-sm btn-light border" id="btnZoomIn" title="Acercar"><i
                                                class="fas fa-plus text-primary"></i></button>
                                        <div class="vr mx-1 bg-secondary opacity-25"></div>
                                        <button class="btn btn-sm btn-light border" id="btnFullscreen"
                                            title="Pantalla Completa"><i class="fas fa-expand text-dark"></i></button>
                                        <button class="btn btn-sm btn-primary shadow-sm" id="btnDownload"
                                            title="Descargar SVG"><i class="fas fa-download"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Data & Analytics --}}
                        <div class="col-lg-5">

                            {{-- Compatibility Card --}}
                            <div class="card border-0 shadow-sm mb-3"
                                style="border-radius: 12px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;">
                                <div class="card-body p-4 d-flex align-items-center">
                                    <div
                                        class="mr-3 p-3 rounded bg-white bg-opacity-10 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-microchip fa-lg text-white"></i>
                                    </div>
                                    <div>
                                        <div class="text-white-50 small text-uppercase font-weight-bold mb-1">Máquina
                                            Recomendada</div>
                                        <div class="h5 font-weight-bold m-0" id="resCompatibility">Brother / Babylock
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Technical Stats Grid --}}
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="stat-card p-3 bg-white shadow-sm rounded border-bottom-primary h-100"
                                        style="border-radius: 12px !important; border-bottom: 3px solid #3b82f6;">
                                        <div class="text-muted small font-weight-bold text-uppercase mb-1">Puntadas</div>
                                        <div class="h3 font-weight-bold text-dark m-0" id="resStitches">0</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card p-3 bg-white shadow-sm rounded border-bottom-success h-100"
                                        style="border-radius: 12px !important; border-bottom: 3px solid #10b981;">
                                        <div class="text-muted small font-weight-bold text-uppercase mb-1">Colores</div>
                                        <div class="h3 font-weight-bold text-dark m-0" id="resColorsCount">0</div>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="stat-card p-3 bg-white shadow-sm rounded h-100"
                                        style="border-radius: 12px !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="text-muted small font-weight-bold text-uppercase">Ancho</div>
                                            <i class="fas fa-arrows-alt-h text-muted opacity-50"></i>
                                        </div>
                                        <div class="h4 font-weight-bold text-dark m-0"><span id="resWidth">0</span> <span
                                                class="small text-muted">mm</span></div>
                                    </div>
                                </div>
                                <div class="col-6 mt-2">
                                    <div class="stat-card p-3 bg-white shadow-sm rounded h-100"
                                        style="border-radius: 12px !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="text-muted small font-weight-bold text-uppercase">Alto</div>
                                            <i class="fas fa-arrows-alt-v text-muted opacity-50"></i>
                                        </div>
                                        <div class="h4 font-weight-bold text-dark m-0"><span id="resHeight">0</span> <span
                                                class="small text-muted">mm</span></div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="stat-card p-3 bg-white shadow-sm rounded d-flex justify-content-between align-items-center"
                                        style="border-radius: 12px !important;">
                                        <div>
                                            <div class="text-muted small font-weight-bold text-uppercase">Saltos
                                                (Trims/Jumps)</div>
                                            <div class="small text-muted">Cortes de hilo estimados</div>
                                        </div>
                                        <div class="h3 font-weight-bold text-dark m-0" id="resJumps">0</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Colors Palette --}}
                            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                                <div class="card-header bg-white border-0 pt-3 px-3">
                                    <h6 class="font-weight-bold m-0 text-dark">Paleta de Hilos</h6>
                                </div>
                                <div class="card-body px-3 pb-3">
                                    <div id="resColorGrid" class="d-flex flex-wrap gap-2">
                                        {{-- Swatches injected via JS --}}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #f1f5f9;
        }

        /* Upload Area Effects */
        .upload-area:hover {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
        }

        .upload-area:hover .icon-circle {
            transform: scale(1.1);
            color: #2563eb;
        }

        .icon-circle {
            transition: transform 0.2s ease-in-out;
        }

        /* SVG Container */
        #svgContainer svg {
            max-width: 100%;
            max-height: 100%;
            filter: drop-shadow(0 10px 15px -3px rgba(0, 0, 0, 0.1));
        }

        /* Utilities */
        .letter-spacing-1 {
            letter-spacing: 1px;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .text-white-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate__fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const dropZone = $('.upload-area');
            const fileInput = $('#fileInput');

            // --- Event Handlers ---

            // Click anywhere in drop zone triggers input
            dropZone.on('click', function() {
                fileInput.click();
            });

            // Drag effects
            dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).css({
                    'border-color': '#3b82f6',
                    'background-color': '#eff6ff'
                });
            });

            dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeAttr('style'); // Revert to CSS hover class or default
            });

            // Drop
            dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeAttr('style');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    analyzeFile(files[0]);
                }
            });

            // File Input Change
            fileInput.on('change', function() {
                if (this.files.length > 0) {
                    analyzeFile(this.files[0]);
                }
            });

            // "Subir Otro" Button
            $('#btnNewUpload').on('click', function() {
                $('#resultCard').addClass('d-none');
                $('#uploadCard').removeClass('d-none');
                fileInput.val(''); // Reset input
                fileInput.click(); // Open system dialog
            });

            // "Centrar Vista" (Reset Zoom/Pan - Placeholder for now)
            $('#btnResetView').on('click', function() {
                // Future: Implement Panzoom reset here
                const svg = $('#svgContainer svg');
                if (svg.length) {
                    svg.css({
                        'transform': 'scale(1)',
                        'transition': 'transform 0.3s'
                    });
                }
            });

            // --- Main Logic ---

            function analyzeFile(file) {
                // 1. Validation Logic
                const ext = file.name.split('.').pop().toLowerCase();

                if (ext === 'emb') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Formato de Edición',
                        text: 'El archivo .EMB es editable (Wilcom) y no se puede visualizar directamente aquí. Por favor sube la versión de producción (.DST, .PES, etc).',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                const validExts = ['dst', 'pes', 'jef', 'jef+', 'exp', 'vp3', 'vip', 'xxx', 'hus', 'pec', 'sew',
                    'shv', 'tap', 'tbf', 'u01', '10o', 'emd', 'csd', 'pcs', 'ksm'
                ];
                if (!validExts.includes(ext)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formato no soportado',
                        text: 'Por favor sube un archivo de bordado válido (.DST, .PES, .JEF, etc).',
                    });
                    return;
                }

                // 2. Prepare UI
                $('#uploadCard').addClass('d-none');
                $('#loadingSection').removeClass('d-none');

                // 3. AJAX Request
                const formData = new FormData();
                formData.append('file', file);

                $.ajax({
                    url: '{{ route('admin.visualizer.analyze') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#loadingSection').addClass('d-none');
                        if (response.success) {
                            renderDashboard(response.data);
                        }
                    },
                    error: function(xhr) {
                        $('#loadingSection').addClass('d-none');
                        $('#uploadCard').removeClass('d-none');

                        let msg = 'Ocurrió un error al analizar el archivo.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }

                        // Error visual
                        Swal.fire({
                            title: 'Error de Análisis',
                            text: msg,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }

            function renderDashboard(data) {
                // Header Info
                $('#resFileName').text(data.original_name || data.file_name);
                $('#resFileSize').text(formatBytes(data.file_size));
                $('#resFormatBadgeHeader').text(data.file_format || 'N/A');

                // Compatibility
                $('#resCompatibility').text(data.machine_compatibility || 'Universal');

                // Stats
                animateValue('resStitches', 0, data.total_stitches, 1000);
                $('#resColorsCount').text(data.colors_count);
                $('#resWidth').text(data.width_mm);
                $('#resHeight').text(data.height_mm);
                $('#resJumps').text(data.jumps);

                // SVG
                const svgContainer = $('#svgContainer');
                if (data.svg_content) {
                    svgContainer.html(data.svg_content);
                    // Add responsiveness to SVG
                    svgContainer.find('svg').attr('width', '100%').attr('height', '100%');
                } else {
                    svgContainer.html(`
                        <div class="text-center text-muted opacity-50">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p>No se pudo generar la vista previa</p>
                        </div>
                    `);
                    $('#svgToolbar').addClass('d-none'); // Hide toolbar if no SVG
                }

                // Colors Palette
                const colorGrid = $('#resColorGrid');
                colorGrid.empty();

                if (data.colors && data.colors.length > 0) {
                    data.colors.forEach(color => {
                        const hex = color.hex || '#e2e8f0';
                        const name = color.name || 'Sin nombre';
                        const html = `
                            <div class="d-flex flex-column align-items-center mb-2" style="width: 80px;">
                                <div class="palette-swatch shadow-sm mb-2" title="${name} (${hex})" 
                                     style="
                                        width: 48px; height: 48px; 
                                        background: ${hex}; 
                                        border-radius: 12px; 
                                        cursor: help;
                                        border: 2px solid #fff;
                                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                        transition: transform 0.2s;
                                     "
                                     onmouseover="this.style.transform='scale(1.1)'"
                                     onmouseout="this.style.transform='scale(1)'"
                                ></div>
                                <span class="text-dark font-weight-bold bg-white px-2 py-1 rounded shadow-sm border" style="font-size: 0.85rem; font-family: monospace; letter-spacing: 0.5px;">${hex}</span>
                            </div>
                        `;
                        colorGrid.append(html);
                    });
                } else {
                    colorGrid.html(
                        '<p class="text-muted small pl-1">No hay información de colores disponible.</p>');
                }

                // Show Dashboard
                $('#resultCard').removeClass('d-none');
            }

            // --- Toolbar Logic ---

            $('#btnZoomIn').on('click', function() {
                currentScale = Math.min(5, currentScale + 0.5);
                updateTransform();
            });

            $('#btnZoomOut').on('click', function() {
                currentScale = Math.max(0.5, currentScale - 0.5);
                updateTransform();
            });

            $('#btnZoomReset').on('click', function() {
                currentScale = 1;
                transX = 0;
                transY = 0;
                updateTransform();
            });

            $('#btnDownload').on('click', function() {
                const svgNode = document.querySelector('#svgContainer svg');
                if (!svgNode) return;

                // Show loading state
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                try {
                    const svgData = new XMLSerializer().serializeToString(svgNode);
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const img = new Image();

                    // High Resolution Settings (4x Scale for crisp quality)
                    const scaleFactor = 4;

                    // Get original SVG dimensions or fallback
                    let width = 800;
                    let height = 800;

                    if (svgNode.viewBox && svgNode.viewBox.baseVal) {
                        width = svgNode.viewBox.baseVal.width || 800;
                        height = svgNode.viewBox.baseVal.height || 800;
                    } else if (svgNode.width && svgNode.width.baseVal) {
                        width = svgNode.width.baseVal.value || 800;
                        height = svgNode.height.baseVal.value || 800;
                    }

                    canvas.width = width * scaleFactor;
                    canvas.height = height * scaleFactor;

                    img.onload = function() {
                        // Optional: White background for better WhatsApp visibility (transparency can be problematic)
                        // ctx.fillStyle = '#ffffff';
                        // ctx.fillRect(0, 0, canvas.width, canvas.height);

                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                        const pngFile = canvas.toDataURL('image/png');
                        const a = document.createElement('a');
                        a.download = ($('#resFileName').text() || 'bordado') + '_HD.png';
                        a.href = pngFile;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                        // Reset button
                        btn.html(originalHtml).prop('disabled', false);
                    };

                    // Handle SVG encoding for src
                    img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));

                } catch (e) {
                    console.error('Error generating PNG:', e);
                    Swal.fire('Error', 'No se pudo generar la imagen HD. Intenta con captura de pantalla.',
                        'error');
                    btn.html(originalHtml).prop('disabled', false);
                }
            });

            $('#btnFullscreen').on('click', function() {
                const elem = document.getElementById('previewCard'); // ID agregado al card padre
                if (!document.fullscreenElement) {
                    elem.requestFullscreen().catch(err => {
                        console.error(
                            `Error attempting to enable full-screen mode: ${err.message} (${err.name})`
                        );
                    });
                } else {
                    document.exitFullscreen();
                }
            });

            // --- Zoom / Pan Logic ---
            let currentScale = 1;
            let isDragging = false;
            let startX, startY, transX = 0,
                transY = 0;
            const zoomContainer = $('#svgContainer');

            function updateTransform() {
                // Aplicamos la transformación al primer hijo (el SVG) o al contenedor si fuera necesario
                // En este caso, el SVG es inyectado dentro de #svgContainer -> svg
                const content = zoomContainer.find('svg');
                if (content.length) {
                    content.css({
                        'transform': `scale(${currentScale}) translate(${transX}px, ${transY}px)`,
                        'transform-origin': 'center',
                        'transition': isDragging ? 'none' : 'transform 0.1s ease-out'
                    });
                }
            }

            // Buttons (Global Reset removed/redirected to toolbar)
            // The old #btnResetView logic is now handled by the toolbar's #btnZoomReset
            // The original #btnResetView handler is updated above to call the new reset logic.

            // Mouse Events on Container
            zoomContainer.on('mousedown', function(e) {
                if (currentScale > 1) {
                    isDragging = true;
                    // Ajuste de coordenadas relativo al zoom
                    startX = e.pageX - (transX * currentScale);
                    startY = e.pageY - (transY * currentScale);
                    // Simplificado: usar movimiento relativo
                    startX = e.pageX;
                    startY = e.pageY;
                    // Guardar posición actual de translate para sumar delta
                    $(this).css('cursor', 'grabbing');
                }
            });

            // Corrección lógica Pan con Zoom
            let panStartX = 0,
                panStartY = 0;

            zoomContainer.on('mousedown', function(e) {
                if (currentScale > 1) {
                    isDragging = true;
                    panStartX = e.pageX - transX;
                    panStartY = e.pageY - transY;
                    $(this).css('cursor', 'grabbing');
                }
            });

            $(window).on('mousemove', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    transX = e.pageX - panStartX;
                    transY = e.pageY - panStartY;
                    updateTransform();
                }
            }).on('mouseup', function() {
                isDragging = false;
                zoomContainer.css('cursor', currentScale > 1 ? 'grab' : 'default');
            });

            zoomContainer.on('wheel', function(e) {
                e.preventDefault();
                const delta = e.originalEvent.deltaY;
                if (delta > 0) {
                    currentScale = Math.max(0.5, currentScale - 0.2);
                } else {
                    currentScale = Math.min(5, currentScale + 0.2);
                }
                updateTransform();
            });

            // --- Helpers ---

            function formatBytes(bytes, decimals = 1) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            function animateValue(id, start, end, duration) {
                const obj = document.getElementById(id);
                if (!obj) return;

                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    obj.innerHTML = new Intl.NumberFormat().format(Math.floor(progress * (end - start) +
                        start));
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }
        });
    </script>
@stop
