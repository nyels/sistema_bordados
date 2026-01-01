@extends('adminlte::page')

@section('title', 'Nuevo diseño')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="text-2xl font-weight-semibold text-gray-800">
            Crear nuevo diseño
        </h1>
        <div class="flex gap-2">
            <a id="back-btn" href="{{ route('admin.designs.index') }}" class="btn btn-secondary btn-md px-3">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
            <button id="submit-btn" form="design-form" type="submit" class="btn btn-primary btn-md px-3">
                <i class="fas fa-save"></i> Guardar diseño
            </button>
        </div>
    </div>
@stop

@section('content')
    {{-- ============================================
     SPINNER PREMIUM CON BARRA DE PROGRESO
     ============================================ --}}
    <div class="modal-loading-overlay" id="loadingOverlay">
        <div class="modal-loading-content">
            <div class="modal-loading-spinner"></div>
            <h3 class="modal-loading-title" id="loadingTitle">Guardando diseño</h3>
            <p class="modal-loading-subtitle" id="loadingSubtitle">
                Procesando tu solicitud, por favor espera...
            </p>

            {{-- CONTENEDOR DE BARRA DE PROGRESO --}}
            <div class="progress-container-premium">
                <div class="progress-bar-premium" id="progressBar">
                    <div class="progress-fill-premium" id="progressFill"></div>
                </div>
                <div class="progress-text-container">
                    <span class="progress-text" id="progressText">0%</span>
                    <span class="progress-status" id="progressStatus">Iniciando...</span>
                </div>
            </div>
        </div>
    </div>

    <form id="design-form" action="{{ route('admin.designs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Campo oculto para mantener la información de la imagen temporal --}}
        @if (isset($tempImageData))
            <input type="hidden" id="tempImageInfo" value='@json($tempImageData)'>
        @endif

        <div class="row">

            {{-- COLUMNA IZQUIERDA: IMAGEN --}}
            <div class="col-lg-5">
                <div class="surface">

                    <h5 class="section-title mb-3">
                        <i class="fas fa-image text-primary"></i> Imagen del diseño
                    </h5>

                    <div class="alert alert-info alert-modern mb-3">
                        <i class="fas fa-info-circle"></i>
                        <span>Sube la imagen principal del diseño. Para variaciones de color o estilo, podrás crear
                            <strong>variantes</strong> después de guardar.</span>
                    </div>

                    <div id="dropzone" class="dropzone @error('image') border-danger @enderror">
                        <input type="file" id="imageInput" name="image" accept="image/*" hidden>

                        <div id="dropzoneContent" class="dropzone-content">
                            <div class="dropzone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="dropzone-title">
                                Subir imagen
                            </div>
                            <div class="dropzone-sub">
                                Arrastra imagen aquí o haz clic
                            </div>
                            <div class="dropzone-formats">
                                Formatos: JPEG, PNG, SVG, AVIF, WebP
                            </div>
                        </div>

                        {{-- Contenedor para la vista previa DENTRO del dropzone --}}
                        <div id="previewContainer" class="preview-grid"></div>
                    </div>

                    {{-- Contenedor para la información del archivo (debajo del dropzone) --}}
                    <div id="fileInfoContainer"></div>
                    <div id="image-error" class="text-danger small mt-1" style="display: none;"></div>
                    @error('image')
                        <small class="text-danger d-block mt-2">{{ $message }}</small>
                    @enderror

                    {{-- Contenedor para la paleta de colores detectada (al final) --}}
                    <div id="palette-container" class="mt-3" style="display: none;">
                        <label class="label mb-2" style="font-size: 13px; color: #64748b;">Colores detectados en el
                            diseño</label>
                        <div id="palette-grid" class="d-flex flex-wrap gap-2 p-2"
                            style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                        </div>
                    </div>

                </div>
            </div>

            {{-- COLUMNA DERECHA: FORMULARIO --}}
            <div class="col-lg-7">
                <div class="surface">

                    <h5 class="section-title mb-4">
                        <i class="fas fa-info-circle text-primary"></i> Información básica
                    </h5>

                    {{-- Nombre --}}
                    <div class="mb-4">
                        <label class="label">
                            Nombre del diseño <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="input @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Ej. Mariposa floral minimalista" required>
                        <div id="name-error" class="text-danger small mt-1" style="display: none;"></div>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-4">
                        <label class="label">
                            Categorías <span class="text-danger">*</span>
                        </label>
                        <select name="categories[]" class="input @error('categories') is-invalid @enderror" multiple
                            size="6" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="categories-error" class="text-danger small mt-1" style="display: none;"></div>
                        <small class="hint">
                            Selecciona una o varias categorías
                        </small>
                        @error('categories')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="label">Descripción</label>
                        <textarea name="description" rows="5" class="input @error('description') is-invalid @enderror"
                            placeholder="Describe el estilo, uso y enfoque del diseño…">{{ old('description') }}</textarea>
                        <div id="description-error" class="text-danger small mt-1" style="display: none;"></div>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>
            </div>

        </div>
    </form>
@stop

@section('css')
    <style>
        /* ============================================
               MATERIAL DESIGN / APPLE HIG - BASE STYLES
               ============================================ */
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --font-size-xs: 11px;
            --font-size-sm: 13px;
            --font-size-base: 15px;
            --font-size-lg: 17px;
            --font-size-xl: 20px;
        }

        .surface {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow-md);
        }

        .section-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: var(--font-size-base);
        }

        .label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
            display: block;
            font-size: var(--font-size-base);
        }

        .input {
            width: 100%;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            font-size: var(--font-size-base);
            background: var(--gray-50);
            transition: all 0.2s ease;
        }

        .input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .is-invalid {
            border-color: var(--danger) !important;
            background: #fef2f2;
        }

        .hint {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
        }

        /* ============================================
               ALERTA MODERNA
               ============================================ */
        .alert-modern {
            font-size: var(--font-size-sm);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            border: none;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-modern i {
            margin-top: 2px;
        }

        /* ============================================
               DROPZONE - DISEÑO MODERNO
               ============================================ */
        .dropzone {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: var(--gray-50);
        }

        .dropzone:hover {
            border-color: var(--primary);
            background-color: var(--gray-100);
        }

        .dropzone.border-danger {
            border-color: var(--danger);
            background: #fef2f2;
        }

        /* Cuando hay imagen, cambiar estilo del dropzone */
        .dropzone.has-preview {
            min-height: 80px;
            padding: 20px;
            border: 2px solid var(--primary);
            background: #eff6ff;
        }

        .dropzone.has-preview .dropzone-content {
            display: none;
        }

        .dropzone.has-preview::after {
            content: 'Archivo seleccionado ✓';
            font-size: var(--font-size-sm);
            color: var(--primary);
            font-weight: 500;
        }

        .dropzone-content {
            color: var(--gray-500);
            text-align: center;
        }

        .dropzone-icon {
            font-size: 42px;
            color: var(--gray-400);
            margin-bottom: 12px;
        }

        .dropzone-title {
            font-weight: 600;
            font-size: var(--font-size-lg);
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .dropzone-sub {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .dropzone-formats {
            font-size: var(--font-size-xs);
            color: var(--gray-400);
            background: var(--gray-100);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .preview-grid {
            display: none;
            justify-content: center;
            width: 100%;
        }

        .preview-grid.active {
            display: flex;
        }

        .preview-item {
            width: 150px;
            height: 150px;
            background: var(--gray-800);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .preview-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .remove {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .remove:hover {
            background: #fef2f2;
            transform: scale(1.1);
        }

        /* ============================================
               IMAGE ANALYSIS CARD - DISEÑO DESCRIPTIVO (foto 2)
               ============================================ */
        .image-analysis-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-top: 16px;
            transition: all 0.2s ease;
        }

        .image-analysis-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        /* Preview de imagen dentro de la card */
        .image-card-preview {
            position: relative;
            width: 100%;
            height: 200px;
            background: var(--gray-50);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid var(--primary);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }

        .image-card-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Botón de eliminar en preview */
        .image-card-remove {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--danger);
            color: white;
            border: 2px solid white;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
        }

        .image-card-remove:hover {
            background: #b91c1c;
            transform: scale(1.1);
        }

        /* Info del análisis */
        .image-card-info {
            padding: 16px;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .image-card-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 12px;
        }

        .image-card-name i {
            color: var(--primary);
        }

        .image-card-name span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .image-card-details {
            display: flex;
            justify-content: space-between;
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }

        .image-card-dimensions {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: var(--font-size-sm);
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .image-card-dimensions .dim-label {
            color: var(--gray-500);
            font-weight: 500;
        }

        .image-card-format {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: var(--font-size-sm);
            color: var(--success);
            font-weight: 500;
        }

        .image-card-format i {
            font-size: var(--font-size-xs);
        }

        /* Alerta de extensión diferente */
        .image-card-warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            margin-top: 12px;
            font-size: var(--font-size-xs);
            color: #92400e;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .image-card-warning i {
            color: var(--warning);
            margin-top: 1px;
            flex-shrink: 0;
        }

        .image-card-warning div {
            line-height: 1.4;
        }

        /* Iconos para tipos de archivo */
        .file-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        /* Estilos específicos para vista previa de bordados */
        .embroidery-preview {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            text-align: center;
            padding: 20px;
        }

        /* Para SVGs con fondo blanco */
        .svg-preview {
            background: white;
            padding: 10px;
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        /* Estilos para botones deshabilitados */
        .disabled-btn {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* ============================================
                                                                                                           ESTILOS DEL SPINNER PREMIUM (AGREGADOS)
                                                                                                           ============================================ */
        .modal-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal-loading-content {
            text-align: center;
            padding: 40px;
            max-width: 320px;
        }

        .modal-loading-spinner {
            width: 64px;
            height: 64px;
            border: 3px solid rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            border-top-color: #2563eb;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .modal-loading-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .modal-loading-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            line-height: 1.5;
        }

        /* ============================================
                                                                                                           BARRA DE PROGRESO PREMIUM
                                                                                                           ============================================ */
        .progress-container-premium {
            width: 100%;
            max-width: 320px;
            margin: 24px auto 0;
        }

        .progress-bar-premium {
            width: 100%;
            height: 8px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin-bottom: 12px;
        }

        .progress-fill-premium {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg,
                    #2563eb 0%,
                    #3b82f6 25%,
                    #60a5fa 50%,
                    #93c5fd 75%,
                    #bfdbfe 100%);
            border-radius: 10px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
        }

        /* Efecto de brillo animado */
        .progress-fill-premium::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.4) 50%,
                    transparent 100%);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Texto de progreso */
        .progress-text-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .progress-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2563eb;
            text-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            min-width: 60px;
        }

        .progress-status {
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 500;
            text-align: right;
            flex: 1;
            padding-left: 16px;
        }

        /* Estados de completado */
        .progress-complete .progress-fill-premium {
            background: linear-gradient(90deg,
                    #059669 0%,
                    #10b981 25%,
                    #34d399 50%,
                    #6ee7b7 75%,
                    #a7f3d0 100%);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        .progress-complete .progress-text {
            color: #059669;
        }

        /* Estados de error */
        .progress-error .progress-fill-premium {
            background: linear-gradient(90deg,
                    #dc2626 0%,
                    #ef4444 25%,
                    #f87171 50%,
                    #fca5a5 75%,
                    #fecaca 100%);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.3);
        }

        .progress-error .progress-text {
            color: #dc2626;
        }
    </style>
@stop

@section('js')
    {{-- AGREGADO: Librería Color Thief para extraer paletas de colores --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>

    <script>
        // ============================================
        // VALIDADOR MEJORADO DE ARCHIVOS DE DISEÑO
        // ============================================
        class DesignFileValidator {
            constructor() {
                // Extensiones permitidas (como referencia)
                this.allowedExtensions = [
                    // Imágenes
                    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'tiff',
                    // Vectoriales
                    'svg', 'svgz',
                    // Bordados
                    'pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv', 'csd', '10o', 'bro'
                ];
                // Tamaños máximos por tipo (en bytes)
                this.maxSizes = {
                    'image': 10 * 1024 * 1024, // 10MB para imágenes
                    'vector': 5 * 1024 * 1024, // 5MB para SVG
                    'embroidery': 50 * 1024 * 1024 // 50MB para archivos de bordado
                };
            }

            /**
             * Obtiene los primeros bytes del archivo para analizar la firma
             */
            async getFileBytes(file, bytes = 64) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = (e) => {
                        try {
                            const arr = new Uint8Array(e.target.result);
                            resolve(arr);
                        } catch (error) {
                            resolve(new Uint8Array());
                        }
                    };
                    reader.onerror = () => resolve(new Uint8Array());
                    const slice = file.slice(0, bytes);
                    reader.readAsArrayBuffer(slice);
                });
            }

            /**
             * Detecta el tipo de archivo basado en su contenido (firma)
             */
            async detectFileTypeByContent(file) {
                const bytes = await this.getFileBytes(file, 64);
                if (bytes.length === 0) return null;

                // Convertir a hexadecimal para comparación
                let hexHeader = '';
                for (let i = 0; i < bytes.length; i++) {
                    hexHeader += bytes[i].toString(16).padStart(2, '0');
                }

                // Análisis de firmas mágicas (SINCRONIZADO CON BACKEND)
                const signatures = {
                    // JPEG - múltiples variantes
                    'jpeg': [
                        'ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3',
                        'ffd8ffe8', 'ffd8ffdb', 'ffd8ffee'
                    ],
                    // PNG - firma única
                    'png': ['89504e470d0a1a0a'],
                    // GIF - GIF87a y GIF89a
                    'gif': ['474946383761', '474946383961'],
                    // BMP
                    'bmp': ['424d'],
                    // WebP - RIFF (requiere validación adicional)
                    'webp': ['52494646'],
                    // TIFF - Little y Big endian
                    'tiff': ['49492a00', '4d4d002a'],
                    // SVG
                    'svg': ['3c737667', '3c3f786d6c', '3c21444f4354595045'],
                    // Bordado PES
                    'pes': ['23504553', '50455330', '43455031'],
                    // Bordado DST
                    'dst': ['4154414a494d41', '4c414a494d41'],
                    // Otros formatos de bordado
                    'exp': ['45787031', '45787032'],
                    'xxx': ['585858', '53494e474552'],
                    'jef': ['4a4546', '4a454631'],
                    'vp3': ['567033', '4856534d'],
                    'hus': ['4846475631', '4846475632'],
                    'pec': ['504543', '50454330', '43455031'],
                    'phc': ['504843', '43455031'],
                    'sew': ['534557', '53455730'],
                    'shv': ['534856', '53485630'],
                    'csd': ['436865727279'],
                    '10o': ['31306f'],
                    'bro': ['42524f']
                };

                // Verificar WebP específicamente (tiene más validación)
                if (hexHeader.startsWith('52494646')) {
                    const webpBytes = await this.getFileBytes(file, 16);
                    if (webpBytes.length >= 12) {
                        const webpCheck = String.fromCharCode(webpBytes[8], webpBytes[9], webpBytes[10], webpBytes[11]);
                        if (webpCheck === 'WEBP') {
                            return 'webp';
                        }
                    }
                }

                // Verificar AVIF específicamente (debe ser exacto en la posición correcta)
                if (hexHeader.startsWith('0000001') || hexHeader.startsWith('0000002')) {
                    const avifBytes = await this.getFileBytes(file, 12);
                    if (avifBytes.length >= 12) {
                        const ftypPos = String.fromCharCode(avifBytes[4], avifBytes[5], avifBytes[6], avifBytes[7]);
                        const brand = String.fromCharCode(avifBytes[8], avifBytes[9], avifBytes[10], avifBytes[11]);
                        if (ftypPos === 'ftyp' && (brand === 'avif' || brand === 'avis' || brand === 'mif1')) {
                            return 'avif';
                        }
                    }
                }

                // Verificar otros formatos
                for (const [type, sigs] of Object.entries(signatures)) {
                    for (const sig of sigs) {
                        if (hexHeader.startsWith(sig)) {
                            // Verificación adicional para SVG
                            if (type === 'svg') {
                                const isSvg = await this.validateSVGContent(file);
                                if (isSvg) return 'svg';
                            } else {
                                return type;
                            }
                        }
                    }
                }

                return null;
            }

            /**
             * Valida que el contenido sea un SVG válido
             */
            async validateSVGContent(file) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = (e) => {
                        try {
                            const content = e.target.result;
                            // Verificar que contenga elementos SVG
                            const hasSvgTag = content.includes('<svg') || content.includes('<SVG');
                            resolve(hasSvgTag);
                        } catch (error) {
                            resolve(false);
                        }
                    };
                    reader.onerror = () => resolve(false);
                    // Leer solo los primeros 2KB para verificar
                    reader.readAsText(file.slice(0, 2048));
                });
            }

            /**
             * Determina la categoría del archivo (imagen, vector, bordado)
             */
            getFileCategory(fileType) {
                const imageTypes = ['jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'tiff'];
                const vectorTypes = ['svg'];
                const embroideryTypes = ['pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv',
                    'csd', '10o', 'bro'
                ];

                if (imageTypes.includes(fileType)) return 'image';
                if (vectorTypes.includes(fileType)) return 'vector';
                if (embroideryTypes.includes(fileType)) return 'embroidery';

                return 'unknown';
            }

            /**
             * Valida el archivo completo
             */
            async validateDesignFile(file) {
                // 1. Validar extensión (solo como referencia inicial)
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.split('.').pop();

                // 2. Detectar tipo real por contenido
                const detectedType = await this.detectFileTypeByContent(file);
                if (!detectedType) {
                    // Si no se pudo detectar por contenido, verificar extensión
                    if (this.allowedExtensions.includes(fileExtension)) {
                        return {
                            valid: true,
                            type: this.getFileCategory(fileExtension),
                            subtype: fileExtension,
                            detectedBy: 'extension',
                            reason: `Archivo aceptado por extensión (.${fileExtension})`
                        };
                    }
                    return {
                        valid: false,
                        reason: 'Formato de archivo no reconocido. Asegúrate de subir una imagen, SVG o archivo de bordado válido.'
                    };
                }

                // 3. Determinar categoría basada en tipo detectado
                const category = this.getFileCategory(detectedType);

                // 4. Validar tamaño según categoría
                const sizeValidation = this.validateFileSize(file, category);
                if (!sizeValidation.valid) {
                    return sizeValidation;
                }

                // 5. Para imágenes, validar dimensiones mínimas
                if (category === 'image' && detectedType !== 'svg') {
                    const dimensionValidation = await this.validateImageDimensions(file, detectedType);
                    if (!dimensionValidation.valid) {
                        return dimensionValidation;
                    }
                }

                // 6. Verificar si la extensión coincide con el tipo detectado
                let reason = '';
                if (detectedType !== fileExtension) {
                    reason =
                        `Formato detectado: ${detectedType.toUpperCase()} (archivo con extensión .${fileExtension})`;
                } else {
                    reason = `Archivo ${detectedType.toUpperCase()} válido`;
                }

                return {
                    valid: true,
                    type: category,
                    subtype: detectedType,
                    detectedBy: 'signature',
                    reason: reason,
                    actualExtension: fileExtension
                };
            }

            /**
             * Valida tamaño según la categoría del archivo
             */
            validateFileSize(file, category) {
                const maxSize = this.maxSizes[category] || 10 * 1024 * 1024;

                if (file.size > maxSize) {
                    const sizeMB = (maxSize / (1024 * 1024)).toFixed(1);
                    let fileTypeName = '';
                    switch (category) {
                        case 'image':
                            fileTypeName = 'imágenes';
                            break;
                        case 'vector':
                            fileTypeName = 'SVG';
                            break;
                        case 'embroidery':
                            fileTypeName = 'archivos de bordado';
                            break;
                        default:
                            fileTypeName = 'archivos';
                    }
                    return {
                        valid: false,
                        reason: `El archivo es demasiado grande. Máximo ${sizeMB}MB para ${fileTypeName}.`
                    };
                }
                return {
                    valid: true,
                    reason: 'Tamaño válido'
                };
            }

            /**
             * Valida dimensiones de imágenes (excepto SVG y algunos formatos especiales)
             */
            async validateImageDimensions(file, detectedType) {
                // Para SVG, AVIF y algunos formatos, no validamos dimensiones
                if (detectedType === 'svg' || detectedType === 'avif' || detectedType === 'webp') {
                    return {
                        valid: true,
                        reason: `${detectedType.toUpperCase()} (dimensiones no verificables en este navegador)`
                    };
                }

                return new Promise((resolve) => {
                    const img = new Image();
                    const url = URL.createObjectURL(file);

                    img.onload = () => {
                        URL.revokeObjectURL(url);
                        const minWidth = 100,
                            minHeight = 100;
                        const maxWidth = 10000,
                            maxHeight = 10000;

                        if (img.width < minWidth || img.height < minHeight) {
                            resolve({
                                valid: false,
                                reason: `Imagen demasiado pequeña (${img.width}x${img.height} píxeles). Mínimo ${minWidth}x${minHeight} píxeles.`
                            });
                        } else if (img.width > maxWidth || img.height > maxHeight) {
                            resolve({
                                valid: false,
                                reason: `Imagen demasiado grande (${img.width}x${img.height} píxeles). Máximo ${maxWidth}x${maxHeight} píxeles.`
                            });
                        } else {
                            resolve({
                                valid: true,
                                width: img.width,
                                height: img.height,
                                reason: `Dimensiones: ${img.width}x${img.height} píxeles`
                            });
                        }
                    };

                    img.onerror = () => {
                        URL.revokeObjectURL(url);
                        // Si falla la carga, puede ser un formato que el navegador no soporta
                        // pero que ya validamos por firma, así que lo aceptamos
                        resolve({
                            valid: true,
                            reason: 'Imagen válida (no se pudieron verificar dimensiones)'
                        });
                    };

                    img.src = url;
                });
            }

            /**
             * Obtiene un icono apropiado para el tipo de archivo
             */
            getFileIcon(fileType) {
                const icons = {
                    'image': 'image',
                    'vector': 'vector-square',
                    'embroidery': 'vest',
                    'jpeg': 'file-image',
                    'png': 'file-image',
                    'gif': 'file-image',
                    'bmp': 'file-image',
                    'webp': 'file-image',
                    'avif': 'file-image',
                    'svg': 'vector-square',
                    'pes': 'vest',
                    'dst': 'vest',
                    'default': 'file'
                };

                return icons[fileType] || icons['default'];
            }
        }

        // ============================================
        // CÓDIGO PRINCIPAL DE LA VISTA
        // ============================================

        const form = document.getElementById('design-form');
        const dropzone = document.getElementById('dropzone');
        const dropzoneContent = document.getElementById('dropzoneContent');
        const input = document.getElementById('imageInput');
        const preview = document.getElementById('previewContainer');
        const fileInfoContainer = document.getElementById('fileInfoContainer');
        const submitBtn = document.getElementById('submit-btn');
        const backBtn = document.getElementById('back-btn');
        const tempImageInfo = document.getElementById('tempImageInfo');

        let isSubmitting = false;
        let currentObjectURL = null;

        const fileValidator = new DesignFileValidator();

        // AGREGADO: Función para extraer y mostrar colores
        function extractColors(imgElement) {
            try {
                const colorThief = new ColorThief();
                const palette = colorThief.getPalette(imgElement, 20);
                const paletteGrid = document.getElementById('palette-grid');
                const paletteContainer = document.getElementById('palette-container');

                paletteGrid.innerHTML = '';
                paletteContainer.style.display = 'block';

                palette.forEach(rgb => {
                    const hex = '#' + rgb.map(x => x.toString(16).padStart(2, '0')).join('');
                    paletteGrid.insertAdjacentHTML('beforeend', `
                        <div class="d-flex align-items-center bg-white px-2 py-1 rounded shadow-sm border" style="font-size:11px; font-weight:700;">
                            <div style="width:16px; height:16px; background:${hex}; border-radius:4px; margin-right:8px; border:1px solid #ddd;"></div>
                            <span>${hex.toUpperCase()}</span>
                        </div>
                    `);
                });
            } catch (e) {
                console.error("No se pudo extraer la paleta de colores", e);
            }
        }

        // Función para deshabilitar todos los botones y preparar spinner
        function disableAllButtons() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            submitBtn.classList.add('disabled-btn');

            backBtn.disabled = true;
            backBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Bloqueado';
            backBtn.classList.add('disabled-btn');
            backBtn.style.pointerEvents = 'none';

            // Preparar spinner de carga
            showLoadingState();
        }

        // Función para habilitar todos los botones
        function enableAllButtons() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar diseño';
            submitBtn.classList.remove('disabled-btn');

            backBtn.disabled = false;
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Regresar';
            backBtn.classList.remove('disabled-btn');
            backBtn.style.pointerEvents = 'auto';
        }

        // Función para limpiar todo el contenido anterior
        function clearPreviousContent() {
            preview.innerHTML = '';
            fileInfoContainer.innerHTML = '';
            document.getElementById('palette-container').style.display = 'none';

            if (currentObjectURL) {
                URL.revokeObjectURL(currentObjectURL);
                currentObjectURL = null;
            }

            dropzone.classList.remove('border-danger');
            dropzone.classList.remove('has-preview');

            // Mostrar contenido original del dropzone
            dropzoneContent.style.display = 'block';
            preview.classList.remove('active');

            // Eliminar todos los mensajes de error
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());
        }

        // Función para mostrar la vista previa dentro del dropzone
        function showPreviewInDropzone() {
            dropzoneContent.style.display = 'none';
            preview.classList.add('active');
            dropzone.classList.add('has-preview');
        }

        /**
         * Obtiene las dimensiones de una imagen
         */
        function getImageDimensions(file) {
            return new Promise((resolve) => {
                if (!file.type.startsWith('image/')) {
                    resolve({
                        width: null,
                        height: null
                    });
                    return;
                }

                const img = new Image();
                const url = URL.createObjectURL(file);

                img.onload = () => {
                    URL.revokeObjectURL(url);
                    resolve({
                        width: img.naturalWidth,
                        height: img.naturalHeight
                    });
                };

                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    resolve({
                        width: null,
                        height: null
                    });
                };

                img.src = url;
            });
        }

        /**
         * Formatea el tamaño de archivo de forma legible
         */
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        // Función para mostrar información del archivo (diseño descriptivo como foto 2)
        async function showFileInfo(file, validationResult, isTempFile = false) {
            // Obtener dimensiones de la imagen
            const dimensions = await getImageDimensions(file);

            const fileSize = formatFileSize(file.size);
            const fileExt = file.name.split('.').pop().toLowerCase();
            const detectedFormat = validationResult.subtype || fileExt;

            // Determinar tipo de archivo
            let fileTypeName = 'Imagen';
            if (validationResult.type === 'vector') fileTypeName = 'Vector';
            else if (validationResult.type === 'embroidery') fileTypeName = 'Bordado';

            // Construir HTML de dimensiones
            let dimensionsHTML = '';
            if (dimensions.width && dimensions.height) {
                dimensionsHTML = `
                    <div class="image-card-dimensions">
                        <span class="dim-label">Dimensiones:</span>
                        ${dimensions.width} × ${dimensions.height} px
                    </div>
                `;
            }

            // Alerta de extensión diferente al tipo real detectado (diseño como foto 2)
            let warningHTML = '';
            if (validationResult.detectedBy === 'signature' &&
                validationResult.subtype &&
                validationResult.actualExtension &&
                validationResult.subtype.toLowerCase() !== validationResult.actualExtension.toLowerCase()) {
                warningHTML = `
                    <div class="image-card-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Nota:</strong> El archivo tiene extensión .${validationResult.actualExtension} pero es un formato ${validationResult.subtype.toUpperCase()}. Se guardará con la extensión correcta (.${validationResult.subtype}).
                        </div>
                    </div>
                `;
            }

            // Crear la URL de la imagen para preview
            const previewURL = URL.createObjectURL(file);

            const fileInfoHTML = `
                <div class="image-analysis-card fade-in">
                    <div class="image-card-preview">
                        <img src="${previewURL}" alt="${file.name}">
                        <button type="button" class="image-card-remove" onclick="removeFile(${isTempFile})" title="Eliminar imagen">×</button>
                    </div>
                    <div class="image-card-info">
                        <div class="image-card-name">
                            <i class="fas fa-file-image"></i>
                            <span title="${file.name}">${file.name}</span>
                        </div>
                        <div class="image-card-details">
                            <span>${fileTypeName} (${detectedFormat.toUpperCase()})</span>
                            <span>Tamaño: ${fileSize}</span>
                        </div>
                        ${dimensionsHTML}
                        <div class="image-card-format">
                            <i class="fas fa-check-circle"></i>
                            Formato detectado: ${detectedFormat.toUpperCase()} (archivo con extensión .${fileExt})
                        </div>
                        ${warningHTML}
                    </div>
                </div>
            `;

            fileInfoContainer.innerHTML = fileInfoHTML;
        }

        // Función auxiliar para crear el botón de eliminar con stopPropagation
        function createRemoveButton(isTempFile) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'Eliminar imagen';

            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                removeFile(isTempFile);
            });

            return removeBtn;
        }

        // Función para crear vista previa
        function createPreview(file, validationResult, isTempFile = false) {
            const div = document.createElement('div');
            div.className = 'preview-item';

            if (validationResult.type === 'embroidery') {
                // Icono para archivos de bordado
                div.innerHTML = `
                    <div class="embroidery-preview">
                        <i class="fas fa-vest" style="font-size: 48px; margin-bottom: 10px;"></i>
                        <div style="font-size: 14px;">${validationResult.subtype.toUpperCase()}</div>
                        <div style="font-size: 12px; opacity: 0.8;">${file.name}</div>
                    </div>
                `;
                div.appendChild(createRemoveButton(isTempFile));
            } else if (validationResult.type === 'vector') {
                // Vista previa para SVG
                currentObjectURL = URL.createObjectURL(file);
                div.innerHTML = `<img src="${currentObjectURL}" alt="Vista previa SVG">`;
                div.classList.add('svg-preview');
                div.appendChild(createRemoveButton(isTempFile));
            } else {
                // Intentar crear vista previa para imágenes
                currentObjectURL = URL.createObjectURL(file);
                const img = document.createElement('img');
                img.src = currentObjectURL;
                img.alt = "Vista previa";

                img.onload = () => {
                    // MODIFICADO: Extraer colores si es una imagen real cargada
                    extractColors(img);
                };

                img.onerror = () => {
                    // Si falla la carga (ej: AVIF en navegadores antiguos)
                    div.innerHTML = `
                        <div style="text-align: center; color: white; padding: 20px;">
                            <i class="fas fa-file-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <div style="font-size: 14px;">${validationResult.subtype.toUpperCase()}</div>
                            <div style="font-size: 12px; opacity: 0.8;">Vista previa no disponible</div>
                        </div>
                    `;
                    div.appendChild(createRemoveButton(isTempFile));
                };

                div.appendChild(img);
                div.appendChild(createRemoveButton(isTempFile));
            }

            preview.appendChild(div);

            // Mostrar vista previa dentro del dropzone
            showPreviewInDropzone();
        }

        // Función para remover el archivo
        function removeFile(isTempFile = false) {
            clearPreviousContent();
            input.value = '';
            submitBtn.disabled = false;

            // Si es un archivo temporal, eliminar la cookie/sesión
            if (isTempFile) {
                // Enviar solicitud para limpiar el archivo temporal
                fetch('{{ route('admin.designs.clear-temp-image') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        clear: true
                    })
                });
            }
        }

        // Función para mostrar error en un campo específico
        function showFieldError(field, message) {
            field.classList.add('is-invalid');

            // Buscar si ya existe un mensaje de error para este campo
            let errorElement = field.parentElement.querySelector('.field-error');
            if (!errorElement) {
                errorElement = document.createElement('small');
                errorElement.className = 'text-danger d-block mt-1 field-error';
                field.parentElement.appendChild(errorElement);
            }
            errorElement.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${message}`;
        }

        // Función para limpiar todos los errores de validación
        function clearValidationErrors() {
            // Limpiar errores de campos (clases is-invalid)
            form.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });

            // Limpiar errores dinámicos (.field-error)
            form.querySelectorAll('.field-error').forEach(error => {
                error.remove();
            });

            // Limpiar errores de los divs con ID (name-error, categories-error, etc.)
            document.querySelectorAll('[id$="-error"]').forEach(el => {
                el.style.display = 'none';
                el.innerHTML = '';
            });

            // Limpiar errores del dropzone
            dropzone.classList.remove('border-danger');
            const imageErrors = document.querySelectorAll('.image-error');
            imageErrors.forEach(error => error.remove());
        }

        // Función para mostrar error en el dropzone
        function showImageError(message) {
            // Eliminar errores anteriores de imagen
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());

            dropzone.classList.add('border-danger');

            const error = document.createElement('small');
            error.className = 'text-danger d-block mt-2 image-error';
            error.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${message}`;

            // Insertar después del dropzone
            dropzone.parentElement.insertBefore(error, dropzone.nextSibling);
        }

        // Función para mostrar errores de validación del servidor en los campos
        // Usa los divs con IDs predefinidos (igual que edit.blade.php)
        function showServerValidationErrors(errors) {
            // Limpiar errores anteriores de los divs con ID
            document.querySelectorAll('[id$="-error"]').forEach(el => {
                el.style.display = 'none';
                el.innerHTML = '';
            });

            // Remover clases de error de los inputs
            document.querySelectorAll('.input.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            // Limpiar errores del dropzone
            dropzone.classList.remove('border-danger');
            const imageErrors = document.querySelectorAll('.image-error');
            imageErrors.forEach(error => error.remove());

            let firstErrorField = null;

            // Mostrar nuevos errores
            Object.keys(errors).forEach(fieldName => {
                const errorMessages = errors[fieldName];
                const errorMessage = Array.isArray(errorMessages) ? errorMessages[0] : errorMessages;

                // Manejar el campo de imagen de manera especial
                if (fieldName === 'image') {
                    const imageErrorDiv = document.getElementById('image-error');
                    if (imageErrorDiv) {
                        imageErrorDiv.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${errorMessage}`;
                        imageErrorDiv.style.display = 'block';
                    }
                    dropzone.classList.add('border-danger');
                    if (!firstErrorField) {
                        firstErrorField = dropzone;
                    }
                    return;
                }

                // Para campos normales, buscar el div de error por ID
                const errorElement = document.getElementById(fieldName + '-error');
                if (errorElement) {
                    errorElement.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${errorMessage}`;
                    errorElement.style.display = 'block';

                    // Agregar clase de error al input correspondiente
                    let inputSelector = `[name="${fieldName}"]`;
                    if (fieldName === 'categories') {
                        inputSelector = '[name="categories[]"]';
                    }
                    const inputElement = form.querySelector(inputSelector);
                    if (inputElement) {
                        inputElement.classList.add('is-invalid');
                        if (!firstErrorField) {
                            firstErrorField = inputElement;
                        }
                    }
                }
            });

            // Hacer focus en el primer campo con error
            if (firstErrorField) {
                firstErrorField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                setTimeout(() => {
                    if (firstErrorField.focus && typeof firstErrorField.focus === 'function') {
                        firstErrorField.focus();
                    }
                }, 300);
            }
        }

        // Manejadores de eventos para el dropzone
        dropzone.addEventListener('click', () => {
            if (!preview.classList.contains('active')) {
                input.click();
            }
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!preview.classList.contains('active')) {
                dropzone.style.borderColor = '#2563eb';
                dropzone.style.backgroundColor = '#f0f9ff';
            }
        });

        dropzone.addEventListener('dragleave', () => {
            if (!preview.classList.contains('active')) {
                dropzone.style.borderColor = '#d1d5db';
                dropzone.style.backgroundColor = 'transparent';
            }
        });

        dropzone.addEventListener('drop', async (e) => {
            e.preventDefault();

            if (preview.classList.contains('active')) return;

            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.backgroundColor = 'transparent';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];

                // Validar archivo usando el validador mejorado
                const validationResult = await fileValidator.validateDesignFile(file);

                if (validationResult.valid) {
                    clearPreviousContent();
                    createPreview(file, validationResult);
                    showFileInfo(file, validationResult);

                    // Actualizar el input file para que se envíe en el formulario
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                } else {
                    showImageError(validationResult.reason);
                }
            }
        });

        input.addEventListener('change', async (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];

                // Limpiar error del dropzone si existía
                dropzone.classList.remove('border-danger');
                const imageErrors = document.querySelectorAll('.image-error');
                imageErrors.forEach(error => error.remove());

                // Limpiar también el div con ID de error de imagen
                const imageErrorDiv = document.getElementById('image-error');
                if (imageErrorDiv) {
                    imageErrorDiv.style.display = 'none';
                    imageErrorDiv.innerHTML = '';
                }

                // Validar archivo usando el validador mejorado
                const validationResult = await fileValidator.validateDesignFile(file);

                if (validationResult.valid) {
                    clearPreviousContent();
                    createPreview(file, validationResult);
                    showFileInfo(file, validationResult);
                } else {
                    showImageError(validationResult.reason);
                    input.value = '';
                }
            }
        });

        // Cargar imagen temporal si existe
        if (tempImageInfo) {
            loadTempImage(tempImageInfo.value);
        }

        function loadTempImage(tempInfo) {
            try {
                const info = JSON.parse(tempInfo);

                // Crear un objeto File simulado
                const file = {
                    name: info.original_name,
                    size: info.file_size,
                    type: 'application/octet-stream' // Tipo genérico
                };

                const validationResult = {
                    valid: true,
                    type: info.detected_type,
                    subtype: info.detected_format || info.original_extension,
                    detectedBy: 'signature',
                    reason: 'Archivo temporal cargado desde sesión',
                    actualExtension: info.original_extension
                };

                // Crear vista previa especial para archivos temporales
                const div = document.createElement('div');
                div.className = 'preview-item';

                if (info.detected_type === 'embroidery') {
                    div.innerHTML = `
                        <div class="embroidery-preview">
                            <i class="fas fa-vest" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <div style="font-size: 14px;">${(info.detected_format || info.original_extension).toUpperCase()}</div>
                            <div style="font-size: 12px; opacity: 0.8;">${info.original_name}</div>
                        </div>
                        <button type="button" class="remove" onclick="removeFile(true)">×</button>
                    `;
                } else if (info.detected_type === 'vector') {
                    div.innerHTML = `
                        <div style="text-align: center; padding: 20px; color: white;">
                            <i class="fas fa-vector-square" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <div style="font-size: 14px;">SVG</div>
                            <div style="font-size: 12px; opacity: 0.8;">${info.original_name}</div>
                        </div>
                        <button type="button" class="remove" onclick="removeFile(true)">×</button>
                    `;
                } else {
                    // Para imágenes temporales usamos la ruta guardada en el servidor
                    const img = document.createElement('img');
                    img.src = info.temp_url;
                    img.alt = "Vista previa temporal";

                    img.onload = () => extractColors(img); // Extraer colores de imagen temporal

                    div.appendChild(img);
                    div.appendChild(createRemoveButton(true));
                }

                preview.appendChild(div);
                showPreviewInDropzone();
                showFileInfo(file, validationResult, true);

            } catch (e) {
                console.error("Error al cargar imagen temporal:", e);
            }
        }

        // ============================================
        // EVENTOS PARA LIMPIAR ERRORES AL INTERACTUAR
        // ============================================

        // Limpiar error del nombre cuando se escribe
        form.querySelector('input[name="name"]').addEventListener('input', function() {
            this.classList.remove('is-invalid');
            // Limpiar error dinámico
            const errorMsg = this.parentElement.querySelector('.field-error');
            if (errorMsg) errorMsg.remove();
            // Limpiar div con ID
            const nameError = document.getElementById('name-error');
            if (nameError) {
                nameError.style.display = 'none';
                nameError.innerHTML = '';
            }
        });

        // Limpiar error de categorías cuando se selecciona
        form.querySelector('select[name="categories[]"]').addEventListener('change', function() {
            this.classList.remove('is-invalid');
            // Limpiar error dinámico
            const errorMsg = this.parentElement.querySelector('.field-error');
            if (errorMsg) errorMsg.remove();
            // Limpiar div con ID
            const categoriesError = document.getElementById('categories-error');
            if (categoriesError) {
                categoriesError.style.display = 'none';
                categoriesError.innerHTML = '';
            }
        });

        // Limpiar error de descripción cuando se escribe
        form.querySelector('textarea[name="description"]').addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const descriptionError = document.getElementById('description-error');
            if (descriptionError) {
                descriptionError.style.display = 'none';
                descriptionError.innerHTML = '';
            }
        });

        // ============================================
        // LÓGICA DEL SPINNER Y ENVÍO DEL FORMULARIO CON AJAX
        // ============================================

        let isFormSubmitting = false;

        function showLoadingState() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'flex';
        }

        function hideLoadingState() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'none';

            // Resetear barra de progreso
            const fill = document.getElementById('progressFill');
            const text = document.getElementById('progressText');
            const status = document.getElementById('progressStatus');
            const progressBar = document.getElementById('progressBar');

            fill.style.width = '0%';
            text.innerText = '0%';
            status.innerText = 'Iniciando...';
            progressBar.classList.remove('progress-complete', 'progress-error');

            document.getElementById('loadingTitle').textContent = 'Guardando diseño';
            document.getElementById('loadingSubtitle').textContent = 'Procesando tu solicitud, por favor espera...';
        }

        /**
         * Actualiza la barra de progreso
         */
        function updateProgress(progress, statusText) {
            const fill = document.getElementById('progressFill');
            const text = document.getElementById('progressText');
            const status = document.getElementById('progressStatus');

            fill.style.width = progress + '%';
            text.innerText = Math.round(progress) + '%';
            status.innerText = statusText;
        }

        /**
         * Muestra estado de error en la barra de progreso
         */
        function showProgressError(message) {
            const progressBar = document.getElementById('progressBar');
            progressBar.classList.add('progress-error');

            document.getElementById('loadingTitle').textContent = '¡Error!';
            document.getElementById('loadingSubtitle').textContent = message;

            // Ocultar después de un tiempo
            setTimeout(() => {
                hideLoadingState();
                enableAllButtons();
                isFormSubmitting = false;
                isSubmitting = false;
            }, 3000);
        }

        /**
         * Envía el formulario via AJAX con progreso REAL de subida
         */
        function submitFormWithRealProgress(formData) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();

                // Evento de progreso de subida (PROGRESO REAL)
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);

                        // Mensajes según el progreso
                        let statusMessage = 'Subiendo imagen...';
                        if (percentComplete < 30) {
                            statusMessage = 'Subiendo imagen...';
                        } else if (percentComplete < 60) {
                            statusMessage = 'Procesando archivo...';
                        } else if (percentComplete < 90) {
                            statusMessage = 'Optimizando imagen...';
                        } else {
                            statusMessage = 'Finalizando subida...';
                        }

                        updateProgress(percentComplete, statusMessage);
                    }
                });

                // Evento cuando la subida se completa (espera respuesta del servidor)
                xhr.upload.addEventListener('load', function() {
                    updateProgress(100, 'Guardando en servidor...');
                });

                // Evento cuando llega la respuesta del servidor
                xhr.addEventListener('load', function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject({
                                    type: 'validation',
                                    errors: response.errors || {},
                                    message: response.message || 'Error de validación'
                                });
                            }
                        } catch (e) {
                            // Si no es JSON, asumir éxito
                            resolve({
                                success: true,
                                redirect: true
                            });
                        }
                    } else if (xhr.status === 422) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            reject({
                                type: 'validation',
                                errors: response.errors || {},
                                message: 'Error de validación'
                            });
                        } catch (e) {
                            reject({
                                type: 'server',
                                message: 'Error de validación del servidor'
                            });
                        }
                    } else {
                        reject({
                            type: 'server',
                            message: `Error del servidor: ${xhr.status}`
                        });
                    }
                });

                // Evento de error de red
                xhr.addEventListener('error', function() {
                    reject({
                        type: 'network',
                        message: 'Error de conexión. Verifica tu internet.'
                    });
                });

                // Evento de timeout
                xhr.addEventListener('timeout', function() {
                    reject({
                        type: 'timeout',
                        message: 'La solicitud tardó demasiado. Intenta de nuevo.'
                    });
                });

                // Configurar y enviar
                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.timeout = 120000; // 2 minutos de timeout

                xhr.send(formData);
            });
        }

        // ============================================
        // MANEJO DEL FORMULARIO CON AJAX Y PROGRESO REAL
        // ============================================
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }

            // Limpiar errores previos
            clearValidationErrors();

            // Validaciones básicas antes de enviar
            const nameInput = form.querySelector('input[name="name"]');
            const categoriesSelect = form.querySelector('select[name="categories[]"]');
            const name = nameInput.value.trim();
            const categories = categoriesSelect.selectedOptions;
            const hasImage = input.files.length > 0 || tempImageInfo;

            let hasErrors = false;

            // Validar nombre
            if (!name) {
                showFieldError(nameInput, 'El nombre del diseño es obligatorio.');
                hasErrors = true;
            }

            // Validar categorías
            if (categories.length === 0) {
                showFieldError(categoriesSelect, 'Debes seleccionar al menos una categoría.');
                hasErrors = true;
            }

            // Validar imagen
            if (!hasImage) {
                showImageError('Debes subir un archivo para el diseño.');
                hasErrors = true;
            }

            // Si hay errores, hacer focus en el primer campo con error
            if (hasErrors) {
                const firstError = form.querySelector('.is-invalid, .border-danger');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    if (firstError.focus) firstError.focus();
                }
                return;
            }

            isSubmitting = true;
            isFormSubmitting = true;

            // 1. Deshabilitar botones y mostrar spinner
            disableAllButtons();
            showLoadingState();
            updateProgress(0, 'Preparando subida...');

            // 2. Preparar datos del formulario
            const formData = new FormData(form);

            try {
                // 3. Enviar con progreso REAL
                const response = await submitFormWithRealProgress(formData);

                // 4. Éxito - mostrar completado y redirigir rápido
                updateProgress(100, '¡Completado!');
                document.getElementById('loadingTitle').textContent = '¡Diseño Creado!';
                document.getElementById('loadingSubtitle').textContent = 'Redirigiendo...';

                const progressBar = document.getElementById('progressBar');
                if (progressBar) {
                    progressBar.classList.add('progress-complete');
                }

                // Desactivar protección de navegación
                isFormSubmitting = false;
                isSubmitting = false;

                // Redirigir rápido (500ms)
                setTimeout(() => {
                    window.location.href = response.redirect || '{{ route('admin.designs.index') }}';
                }, 500);

            } catch (error) {
                console.error('Error en submit:', error);

                // Ocultar spinner
                hideLoadingState();
                enableAllButtons();
                isSubmitting = false;
                isFormSubmitting = false;

                if (error.type === 'validation') {
                    // Mostrar errores de validación
                    showServerValidationErrors(error.errors);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'Por favor corrige los errores marcados en el formulario.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    // Error de red o servidor
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: error.message || 'Ocurrió un error al guardar el diseño.',
                        confirmButtonColor: '#2563eb'
                    });
                }
            }
        });

        // Interceptar clicks en el botón de regreso para confirmar si se está subiendo
        backBtn.addEventListener('click', function(e) {
            if (isSubmitting) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Cancelar operación?',
                    text: 'El diseño se está guardando. ¿Estás seguro de que quieres cancelar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'Continuar guardando'
                }).then((result) => {
                    if (result.isConfirmed) {
                        isSubmitting = false;
                        isFormSubmitting = false;
                        enableAllButtons();
                        hideLoadingState();
                        window.location.href = this.href;
                    }
                });
            }
        });

        // Prevenir cierre accidental de pestaña si se está subiendo
        window.addEventListener('beforeunload', function(e) {
            if (isFormSubmitting) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Prevenir navegación por historial si se está subiendo
        window.addEventListener('popstate', function(e) {
            if (isSubmitting) {
                history.pushState(null, null, window.location.href);
                Swal.fire({
                    title: 'Guardado en progreso',
                    text: 'No puedes abandonar la página mientras se guarda el diseño.',
                    icon: 'warning',
                    confirmButtonColor: '#2563eb'
                });
            }
        });

        // Restaurar estado si hay error de validación del servidor
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                hideLoadingState();
                enableAllButtons();
                isSubmitting = false;
                isFormSubmitting = false;
            }
        });
    </script>

    {{-- SweetAlert para mensajes del sistema --}}
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                timer: 4000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: "{{ session('error') }}",
                showConfirmButton: true,
                confirmButtonColor: '#dc2626'
            });
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
    @endif
@stop
