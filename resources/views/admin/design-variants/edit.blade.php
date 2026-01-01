@extends('adminlte::page')

@section('title', 'Editar Variante - ' . str_replace('_', ' ', strtoupper($design->name)))
@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h1 class="text-2xl font-weight-semibold text-gray-800">
                Editar Variante <small class="text-muted">{{ str_replace('_', ' ', $variant->sku) }}</small>
            </h1>
            <p class="text-sm text-muted mb-0">
                Diseño: <a href="{{ route('admin.designs.index') }}">{{ $design->name }}</a>
            </p>
        </div>
        <div class="flex gap-2">
            <a id="back-btn" href="{{ route('admin.designs.index') }}" class="btn btn-secondary btn-md px-3">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
            <button id="submitBtn" form="variant-form" type="submit" class="btn btn-primary btn-md px-3">
                <i class="fas fa-save"></i> Guardar Cambios
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
            <h3 class="modal-loading-title" id="loadingTitle">Actualizando variante</h3>
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

    <form id="variant-form" action="{{ route('admin.designs.variants.update', [$design, $variant]) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">

            {{-- COLUMNA IZQUIERDA: GALERÍA CON SISTEMA PREMIUM --}}
            <div class="col-lg-5">
                <div class="surface">
                    <h5 class="section-title mb-3">
                        <i class="fas fa-images text-primary"></i> Galería de la variante
                    </h5>

                    {{-- DROPZONE COMPACTO PREMIUM --}}
                    <div class="alert alert-info alert-modern mb-3">
                        <i class="fas fa-info-circle"></i>
                        Puedes agregar hasta 10 imágenes nuevas (máx. 10MB c/u) para esta variante.
                    </div>

                    <div id="dropzone" class="dropzone dropzone-compact">
                        <input type="file" id="imageInput" name="variant_images[]" multiple accept="image/*" hidden>

                        {{-- Contenido de la dropzone (texto) --}}
                        <div class="dropzone-content" id="dropzoneContent">
                            <div class="dropzone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="dropzone-title">Agregar imágenes</div>
                            <div class="dropzone-sub">Arrastra imágenes aquí o haz clic</div>
                            <div class="dropzone-formats">
                                Formatos: JPEG, PNG, SVG, AVIF, WebP, etc.
                            </div>
                        </div>
                    </div>

                    {{-- Resumen de archivos nuevos --}}
                    <div id="filesSummary" class="files-summary" style="display: none;">
                        <div class="summary-header">
                            <i class="fas fa-images text-primary"></i>
                            <span id="summaryCount">0 archivos seleccionados</span>
                        </div>
                        <div class="summary-details">
                            <span id="summaryTotal">Total: 0 imágenes nuevas</span>
                            <span id="summarySize">Tamaño total: 0 KB</span>
                        </div>
                        <div class="summary-status">
                            <i class="fas fa-check-circle text-success"></i>
                            <span id="summaryReady">Listo para subir 0 archivos</span>
                        </div>
                    </div>

                    {{-- Contenedor scrolleable para las imágenes con analizador --}}
                    <div id="imagesScrollContainer" class="images-scroll-container">
                        <div id="imagesGrid" class="images-analysis-grid">
                            {{-- Aquí se agregan las imágenes nuevas con su info --}}
                        </div>
                    </div>

                    {{-- Toast para mensajes --}}
                    <div id="appleToast" class="apple-toast">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="toastMessage" style="font-weight: 500; color: #1d1d1f;"></span>
                    </div>

                    <hr class="my-4">

                    {{-- SELECTOR DE IMAGEN PRINCIPAL (IMÁGENES EXISTENTES) --}}
                    @if ($variant->images->count() > 0)
                        <div class="mb-4">
                            <label class="label">
                                <i class="fas fa-star text-warning"></i> Imagen principal de la variante
                            </label>
                            <small class="hint mt-2">
                                <i class="fas fa-info-circle text-info"></i> Haz clic en una imagen para establecerla como
                                principal.
                            </small>
                            <input type="hidden" id="primary_image_id" name="primary_image_id"
                                value="{{ $variant->images->firstWhere('is_primary', true)?->id ?? $variant->images->first()?->id }}">

                            <div class="primary-image-grid mt-2">
                                @foreach ($variant->images as $image)
                                    <div class="primary-image-option {{ $image->is_primary ? 'selected' : '' }}"
                                        data-image-id="{{ $image->id }}"
                                        onclick="selectExistingPrimaryImage({{ $image->id }})">
                                        <img src="{{ $image->thumbnail_small ? asset('storage/' . $image->thumbnail_small) : asset('storage/' . $image->file_path) }}"
                                            alt="{{ $image->alt_text ?? 'Imagen variante' }}">

                                        <span class="image-number">{{ $loop->iteration }}</span>
                                        <span class="selected-badge"><i class="fas fa-check"></i></span>
                                        <span class="image-name">{{ $image->file_name }}</span>

                                        {{-- Botón eliminar --}}
                                        <button type="button" class="btn-delete-image"
                                            onclick="event.stopPropagation(); confirmDeleteImage('{{ route('admin.designs.variants.images.destroy', [$design, $variant, $image->id]) }}')"
                                            title="Eliminar imagen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                        <hr class="my-3">
                    @endif

                </div>
            </div>

            {{-- COLUMNA DERECHA: DATOS - ACTUALIZADO CON SKU IDÉNTICO AL CREATE --}}
            <div class="col-lg-7">
                <div class="surface">
                    <h5 class="section-title mb-4">
                        <i class="fas fa-info-circle text-primary"></i> Información General
                    </h5>

                    {{-- ============================================
                         ACTUALIZADO: Campo de nombre IDÉNTICO al create
                         ============================================ --}}
                    <div class="mb-4">
                        <label class="label">
                            Nombre de la variante <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="variant_name" name="name"
                            class="input @error('name') is-invalid @enderror" value="{{ old('name', $variant->name) }}"
                            placeholder="Ej. Sentado, Edición Especial, etc." required>
                        <div id="name-error" class="text-danger small mt-1" style="display: none;"></div>
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- ============================================
                         ACTUALIZADO: Campo SKU EXACTAMENTE IGUAL al create
                         - readonly
                         - tabindex="-1"
                         - Clase sku-readonly
                         - Misma estructura HTML
                         ============================================ --}}
                    <div class="mb-4">
                        <label class="label">SKU (Código único) </label>
                        <input type="text" id="variant_sku" name="sku"
                            class="input sku-readonly @error('sku') is-invalid @enderror" placeholder="SKU_AUTOMATICO"
                            value="{{ old('sku', $variant->sku) }}" readonly tabindex="-1">
                        <div id="sku-error" class="text-danger small mt-1" style="display: none;"></div>
                        {{-- ============================================
                             AGREGADO: Texto de ayuda IDÉNTICO al create
                             ============================================ --}}
                        <small class="hint">Código automático jerárquico profesional</small>
                        @error('sku')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </form>

    {{-- Formulario para eliminar imagen existente --}}
    <form id="deleteImageForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    {{-- ============================================
         MODAL DE CONFIRMACIÓN ELIMINAR IMAGEN CON BOTÓN X AZUL PREMIUM
         ============================================ --}}
    <div class="modal fade" id="deleteImageConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content modal-delete-apple">

                {{-- Botón Cerrar Flotante - X AZUL PREMIUM --}}
                <div style="position: absolute; top: 15px; right: 15px; z-index: 1060;">
                    <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Icono de advertencia --}}
                <div class="modal-delete-icon">
                    <div class="icon-wrapper">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>

                {{-- Título --}}
                <h3 class="modal-delete-title">
                    ¿Eliminar esta imagen?
                </h3>

                {{-- Descripción --}}
                <p class="modal-delete-description">
                    Esta acción no se puede deshacer. La imagen será eliminada permanentemente del sistema.
                </p>

                {{-- Botones de acción --}}
                <div class="modal-delete-actions">
                    <button type="button" class="btn-cancel-apple" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn-delete-apple" id="confirmDeleteImageBtn">
                        Eliminar imagen
                    </button>
                </div>

            </div>
        </div>
    </div>

@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* ============================================
                                                                                       VARIABLES Y ESTILOS BASE DEL PRIMER CÓDIGO
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

        /* ============================================
                                                                                       ESTILOS DE SUPERFICIE Y SECCIONES (DEL PRIMER CÓDIGO)
                                                                                       ============================================ */
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
            display: block;
            margin-top: 4px;
        }

        /* ============================================
                                                                                       ALERTA MODERNA DEL PRIMER CÓDIGO
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
                                                                                       AGREGADO: ESTILO PARA SKU READONLY (IDÉNTICO AL CREATE)
                                                                                       ============================================ */
        .sku-readonly {
            background-color: #f1f1f2 !important;
            color: #86868b;
            cursor: not-allowed;
        }

        /* ============================================
                                                                                       ESTILOS EXISTENTES DEL SEGUNDO CÓDIGO (MANTENIDOS)
                                                                                       ============================================ */

        /* SELECTOR VISUAL DE IMAGEN PRINCIPAL */
        .primary-image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 12px;
            margin-bottom: 8px;
        }

        .primary-image-option {
            position: relative;
            border: 3px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
            aspect-ratio: 1;
            background: var(--gray-50);
        }

        .primary-image-option:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .primary-image-option.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2), 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .primary-image-option img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .primary-image-option .image-number {
            position: absolute;
            top: 6px;
            left: 6px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .primary-image-option .selected-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .primary-image-option.selected .selected-badge {
            transform: translate(-50%, -50%) scale(1);
        }

        .primary-image-option .image-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
            color: white;
            font-size: 10px;
            padding: 20px 6px 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .primary-image-option .btn-delete-image {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--danger);
            color: white;
            border: 2px solid white;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            opacity: 0;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .primary-image-option:hover .btn-delete-image {
            opacity: 1;
        }

        .primary-image-option .btn-delete-image:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* ============================================
                                                                                       SISTEMA PREMIUM - DROPZONE Y ANALIZADOR
                                                                                       ============================================ */

        /* DROPZONE COMPACTO */
        .dropzone.dropzone-compact {
            min-height: 120px;
            padding: 20px 15px;
        }

        .dropzone.dropzone-compact .dropzone-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .dropzone.dropzone-compact .dropzone-title {
            font-size: 15px;
            margin-bottom: 3px;
        }

        .dropzone.dropzone-compact .dropzone-sub {
            font-size: 12px;
            margin-bottom: 3px;
        }

        .dropzone.dropzone-compact .dropzone-formats {
            font-size: 11px;
            margin-top: 5px;
        }

        .dropzone {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius-lg);
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            background: var(--gray-50);
            transition: all .2s;
        }

        .dropzone:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .dropzone-icon {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 15px;
        }

        .dropzone-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--gray-800);
            margin-bottom: 5px;
        }

        .dropzone-sub {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }

        .dropzone-formats {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 10px;
        }

        /* CONTENEDOR SCROLLEABLE DE IMÁGENES */
        .images-scroll-container {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 15px;
            padding-right: 5px;
        }

        .images-scroll-container::-webkit-scrollbar {
            width: 6px;
        }

        .images-scroll-container::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 10px;
        }

        .images-scroll-container::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 10px;
        }

        .images-scroll-container::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* GRID DE IMÁGENES CON ANÁLISIS (2 COLUMNAS) */
        .images-analysis-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        /* CARD DE IMAGEN CON ANÁLISIS */
        .image-analysis-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .image-analysis-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .image-analysis-card.selected {
            border-color: var(--primary);
            border-width: 2px;
        }

        /* Preview de imagen dentro de la card */
        .image-card-preview {
            position: relative;
            width: 100%;
            height: 100px;
            background: var(--gray-50);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image-card-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Botón de eliminar para imágenes nuevas */
        .image-card-remove {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--danger);
            color: white;
            border: 2px solid white;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .image-card-remove:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* Info del análisis */
        .image-card-info {
            padding: 10px;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .image-card-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .image-card-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--gray-600);
            margin-bottom: 4px;
        }

        .image-card-format {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: var(--success);
            font-weight: 500;
        }

        .image-card-format i {
            font-size: 10px;
        }

        /* Alerta de extensión diferente */
        .image-card-warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 9px;
            color: #92400e;
        }

        .image-card-warning i {
            color: var(--warning);
            margin-right: 3px;
        }

        /* Resumen de archivos */
        .files-summary {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-top: 12px;
        }

        .summary-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
        }

        .summary-details {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 6px;
        }

        .summary-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--success);
            font-weight: 500;
        }

        /* Para archivos de bordado */
        .embroidery-preview {
            background: linear-gradient(45deg, #1e40af, var(--primary));
            color: white;
            text-align: center;
            padding: 15px;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .embroidery-preview i {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .embroidery-preview .format-name {
            font-size: 11px;
            font-weight: 600;
        }

        /* Para SVGs */
        .svg-preview {
            background: white;
            padding: 10px;
        }

        /* Toast */
        .apple-toast {
            position: fixed;
            top: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            border-radius: 20px;
            transform: translateX(150%);
            transition: transform 0.5s ease;
            z-index: 9999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .apple-toast.show {
            transform: translateX(0);
        }

        /* Dropzone con error */
        .dropzone.border-danger {
            border-color: var(--danger) !important;
            background: #fef2f2;
        }

        /* ============================================
                                                                                       ESTILOS DEL SPINNER PREMIUM
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
            border-top-color: var(--primary);
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
            color: var(--gray-800);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .modal-loading-subtitle {
            font-size: 0.95rem;
            color: var(--gray-600);
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
                    var(--primary) 0%,
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
            color: var(--primary);
            text-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            min-width: 60px;
        }

        .progress-status {
            font-size: 0.95rem;
            color: var(--gray-600);
            font-weight: 500;
            text-align: right;
            flex: 1;
            padding-left: 16px;
        }

        /* Estados de completado */
        .progress-complete .progress-fill-premium {
            background: linear-gradient(90deg,
                    var(--success) 0%,
                    #10b981 25%,
                    #34d399 50%,
                    #6ee7b7 75%,
                    #a7f3d0 100%);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        .progress-complete .progress-text {
            color: var(--success);
        }

        /* Estados de error */
        .progress-error .progress-fill-premium {
            background: linear-gradient(90deg,
                    var(--danger) 0%,
                    #ef4444 25%,
                    #f87171 50%,
                    #fca5a5 75%,
                    #fecaca 100%);
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.3);
        }

        .progress-error .progress-text {
            color: var(--danger);
        }

        /* Animación fade-in */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }

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

        /* Estilos para botones deshabilitados */
        .disabled-btn {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Estilos para mensajes de error */
        .image-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 8px;
            display: block;
        }

        /* Estilos para Select2 personalizados */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: var(--gray-50);
            padding: 8px;
            min-height: 48px;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default .select2-selection--multiple:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* ============================================
                                                                                       ESTILOS PARA SWEETALERT PREMIUM TIPO APPLE
                                                                                       ============================================ */
        .apple-sweet-alert {
            border-radius: 20px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: #fff !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }

        .apple-sweet-alert-title {
            font-size: 22px !important;
            font-weight: 700 !important;
            color: #1d1d1f !important;
            margin-bottom: 10px !important;
            letter-spacing: -0.3px !important;
        }

        .apple-sweet-alert-html {
            font-size: 15px !important;
            color: #86868b !important;
            line-height: 1.5 !important;
        }

        .apple-sweet-alert-actions {
            margin-top: 30px !important;
            gap: 12px !important;
            justify-content: center !important;
        }

        .apple-sweet-alert-confirm-btn {
            background: linear-gradient(135deg, #dc2626, #ef4444) !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 14px 28px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: white !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.2) !important;
            min-width: 180px !important;
        }

        .apple-sweet-alert-confirm-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3) !important;
            background: linear-gradient(135deg, #ef4444, #f87171) !important;
        }

        .apple-sweet-alert-confirm-btn:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 10px rgba(220, 38, 38, 0.2) !important;
        }

        .apple-sweet-alert-cancel-btn {
            background: #f1f1f2 !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 14px 28px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #374151 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            min-width: 120px !important;
        }

        .apple-sweet-alert-cancel-btn:hover {
            background: #e5e7eb !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
        }

        .apple-sweet-alert-cancel-btn:active {
            transform: translateY(0) !important;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05) !important;
        }

        /* Animaciones para SweetAlert */
        .animate__animated {
            animation-duration: 0.3s !important;
        }

        .animate__faster {
            animation-duration: 0.2s !important;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translate3d(0, -20px, 0);
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes fadeOutUp {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
                transform: translate3d(0, -20px, 0);
            }
        }

        .animate__fadeInDown {
            animation-name: fadeInDown;
        }

        .animate__fadeOutUp {
            animation-name: fadeOutUp;
        }

        /* ============================================
                       ESTILOS DEL BOTÓN X AZUL PREMIUM (MODAL-CLOSE-PREMIUM)
                       ============================================ */
        .modal-close-premium {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 45px;
            height: 45px;
            background: #2563eb;
            border: 2px solid #fff;
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1070;
        }

        .modal-close-premium:hover {
            background: #1d4ed8;
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
        }

        /* ============================================
                       ESTILOS DEL MODAL DE CONFIRMACIÓN ELIMINAR IMAGEN
                       ============================================ */
        .modal-delete-apple {
            border-radius: 24px;
            border: none;
            padding: 32px 28px 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25),
                0 0 1px rgba(0, 0, 0, 0.1);
            background: #ffffff;
        }

        .modal-delete-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .modal-delete-icon .icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(252, 211, 77, 0.3);
        }

        .modal-delete-icon i {
            font-size: 28px;
            color: #d97706;
        }

        .modal-delete-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }

        .modal-delete-description {
            font-size: 0.95rem;
            color: #6b7280;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .modal-delete-actions {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }

        .btn-cancel-apple {
            background: #f3f4f6;
            border: 1.5px solid #e5e7eb;
            color: #374151;
            font-weight: 600;
            font-size: 1rem;
            padding: 14px 24px;
            border-radius: 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            letter-spacing: 0.2px;
        }

        .btn-cancel-apple:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .btn-delete-apple {
            background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 14px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-delete-apple:hover {
            background: linear-gradient(180deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.3);
        }

        .btn-delete-apple.loading {
            position: relative;
            color: transparent;
        }

        .btn-delete-apple.loading::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .dropzone {
                min-height: 250px;
            }

            .dropzone-title {
                font-size: 16px;
            }
        }

        @media (max-width: 576px) {
            .images-analysis-grid {
                grid-template-columns: 1fr;
            }

            .primary-image-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
@stop

@section('js')
    {{-- Primero asegurar que jQuery esté cargado --}}
    <script>
        if (typeof jQuery === 'undefined') {
            document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
        }
    </script>

    {{-- Luego cargar Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // ============================================
        // DECLARACIONES INICIALES
        // ============================================
        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('imageInput');
        const imagesGrid = document.getElementById('imagesGrid');
        const imagesScrollContainer = document.getElementById('imagesScrollContainer');
        const filesSummary = document.getElementById('filesSummary');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('variant-form');
        const backBtn = document.getElementById('back-btn');
        const appleToast = document.getElementById('appleToast');
        const toastMessage = document.getElementById('toastMessage');
        const designBaseName = "{{ $design->name }}";
        const nameInput = document.getElementById('variant_name');
        const skuInput = document.getElementById('variant_sku');

        let isSubmitting = false;
        let currentObjectURLs = [];
        let selectedFiles = []; // Solo para nuevas imágenes
        let validationResults = [];
        let formChanged = false;
        let isFormSubmitting = false;

        // ============================================
        // VALIDADOR MEJORADO DE ARCHIVOS DE DISEÑO
        // ============================================
        class DesignFileValidator {
            constructor() {
                this.allowedExtensions = [
                    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'tiff',
                    'svg', 'svgz',
                    'pes', 'dst', 'exp', 'xxx', 'jef', 'vp3', 'hus', 'pec', 'phc', 'sew', 'shv', 'csd', '10o', 'bro'
                ];

                this.maxSizes = {
                    'image': 10 * 1024 * 1024,
                    'vector': 5 * 1024 * 1024,
                    'embroidery': 50 * 1024 * 1024
                };
            }

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

            async detectFileTypeByContent(file) {
                const bytes = await this.getFileBytes(file, 64);
                if (bytes.length === 0) return null;

                let hexHeader = '';
                for (let i = 0; i < bytes.length; i++) {
                    hexHeader += bytes[i].toString(16).padStart(2, '0');
                }

                const signatures = {
                    'jpeg': [
                        'ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3',
                        'ffd8ffe8', 'ffd8ffdb', 'ffd8ffee'
                    ],
                    'png': ['89504e470d0a1a0a'],
                    'gif': ['474946383761', '474946383961'],
                    'bmp': ['424d'],
                    'webp': ['52494646'],
                    'tiff': ['49492a00', '4d4d002a'],
                    'svg': ['3c737667', '3c3f786d6c', '3c21444f4354595045'],
                    'pes': ['23504553', '50455330', '43455031'],
                    'dst': ['4154414a494d41', '4c414a494d41'],
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

                if (hexHeader.startsWith('52494646')) {
                    const webpBytes = await this.getFileBytes(file, 16);
                    if (webpBytes.length >= 12) {
                        const webpCheck = String.fromCharCode(webpBytes[8], webpBytes[9], webpBytes[10], webpBytes[11]);
                        if (webpCheck === 'WEBP') {
                            return 'webp';
                        }
                    }
                }

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

                for (const [type, sigs] of Object.entries(signatures)) {
                    for (const sig of sigs) {
                        if (hexHeader.startsWith(sig)) {
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

            async validateSVGContent(file) {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = (e) => {
                        try {
                            const content = e.target.result;
                            const hasSvgTag = content.includes('<svg') || content.includes('<SVG');
                            resolve(hasSvgTag);
                        } catch (error) {
                            resolve(false);
                        }
                    };
                    reader.onerror = () => resolve(false);
                    reader.readAsText(file.slice(0, 2048));
                });
            }

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

            async validateDesignFile(file) {
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.split('.').pop();

                const detectedType = await this.detectFileTypeByContent(file);

                if (!detectedType) {
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

                const category = this.getFileCategory(detectedType);

                const sizeValidation = this.validateFileSize(file, category);
                if (!sizeValidation.valid) {
                    return sizeValidation;
                }

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

        // Instanciar el validador
        const fileValidator = new DesignFileValidator();

        // ============================================
        // AGREGADO: GENERACIÓN DE SKU IDÉNTICA AL CREATE
        // ============================================
        function updateSku() {
            let skuBase = designBaseName;
            let userValue = nameInput.value.trim();

            if (userValue) {
                skuBase += " " + userValue;
            }

            skuInput.value = skuBase
                .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                .replace(/\s+/g, '_')
                .replace(/[^a-zA-Z0-9_]/g, '')
                .toUpperCase();
        }

        // ============================================
        // INICIALIZACIÓN SELECT2 (con jQuery seguro)
        // ============================================
        function initializeSelect2() {
            // Esperar a que jQuery esté disponible
            if (typeof jQuery === 'undefined') {
                setTimeout(initializeSelect2, 50);
                return;
            }

            $(document).ready(function() {
                $('.select2').select2({
                    width: '100%',
                    placeholder: 'Selecciona una opción',
                    allowClear: true
                });

                // AGREGADO: Inicializar SKU al cargar la página
                updateSku();
            });
        }

        // Inicializar Select2 cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeSelect2);
        } else {
            initializeSelect2();
        }

        // ============================================
        // AGREGADO: EVENTO PARA ACTUALIZAR SKU AL ESCRIBIR
        // ============================================
        nameInput.addEventListener('input', updateSku);

        // ============================================
        // FUNCIONALIDAD DEL DROPZONE PREMIUM
        // ============================================

        // Función para limpiar todo el contenido anterior
        function clearPreviousContent() {
            imagesGrid.innerHTML = '';
            filesSummary.style.display = 'none';

            // Liberar URLs de objetos
            currentObjectURLs.forEach(url => URL.revokeObjectURL(url));
            currentObjectURLs = [];
            selectedFiles = [];
            validationResults = [];

            dropzone.classList.remove('border-danger');

            // Eliminar todos los mensajes de error
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());
        }

        // Función para mostrar toast
        function showToast(message, type = 'info') {
            toastMessage.textContent = message;
            appleToast.classList.add('show');

            setTimeout(() => {
                appleToast.classList.remove('show');
            }, 3000);
        }

        // Función para crear card de imagen con análisis
        function createImageCard(file, validationResult, index) {
            const card = document.createElement('div');
            card.className = 'image-analysis-card fade-in';
            card.dataset.index = index;

            // Preview container
            const previewDiv = document.createElement('div');
            previewDiv.className = 'image-card-preview';

            if (validationResult.type === 'embroidery') {
                previewDiv.innerHTML = `
                <div class="embroidery-preview">
                    <i class="fas fa-vest"></i>
                    <span class="format-name">${validationResult.subtype.toUpperCase()}</span>
                </div>
            `;
            } else if (validationResult.type === 'vector') {
                const objectURL = URL.createObjectURL(file);
                currentObjectURLs.push(objectURL);
                previewDiv.innerHTML = `<img src="${objectURL}" alt="Vista previa SVG">`;
                previewDiv.classList.add('svg-preview');
            } else {
                const objectURL = URL.createObjectURL(file);
                currentObjectURLs.push(objectURL);
                const img = document.createElement('img');
                img.src = objectURL;
                img.alt = "Vista previa";
                img.onerror = () => {
                    previewDiv.innerHTML = `
                    <div style="text-align: center; color: #666; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <i class="fas fa-file-image" style="font-size: 24px; margin-bottom: 5px;"></i>
                        <div style="font-size: 9px;">No disponible</div>
                    </div>
                `;
                };
                previewDiv.appendChild(img);
            }

            // Botón de eliminar (solo para nuevas imágenes)
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'image-card-remove';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'Eliminar imagen';
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                removeFile(index);
            });
            previewDiv.appendChild(removeBtn);

            // Info del análisis
            const infoDiv = document.createElement('div');
            infoDiv.className = 'image-card-info';

            const fileSize = (file.size / 1024).toFixed(1);
            const originalExtension = file.name.toLowerCase().split('.').pop();
            const detectedFormat = validationResult.subtype || originalExtension;
            let fileTypeName = '';

            switch (validationResult.type) {
                case 'image':
                    fileTypeName = 'Imagen';
                    break;
                case 'vector':
                    fileTypeName = 'Vector';
                    break;
                case 'embroidery':
                    fileTypeName = 'Bordado';
                    break;
                default:
                    fileTypeName = 'Archivo';
            }

            // Verificar si hay discrepancia de extensión
            let warningHTML = '';
            if (validationResult.detectedBy === 'signature' &&
                validationResult.subtype &&
                originalExtension !== validationResult.subtype.toLowerCase()) {
                warningHTML = `
                <div class="image-card-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Extensión .${originalExtension} → formato ${validationResult.subtype.toUpperCase()}
                </div>
            `;
            }

            infoDiv.innerHTML = `
            <div class="image-card-name" title="${file.name}">
                <i class="fas fa-${fileValidator.getFileIcon(detectedFormat)} mr-1"></i>
                ${file.name}
            </div>
            <div class="image-card-details">
                <span>${fileTypeName} (${detectedFormat.toUpperCase()})</span>
                <span>${fileSize} KB</span>
            </div>
            <div class="image-card-format">
                <i class="fas fa-check-circle"></i>
                Formato detectado: ${detectedFormat.toUpperCase()}
            </div>
            ${warningHTML}
        `;

            card.appendChild(previewDiv);
            card.appendChild(infoDiv);

            return card;
        }

        // Función para renderizar todas las cards
        function renderImageCards() {
            imagesGrid.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const card = createImageCard(file, validationResults[index], index);
                imagesGrid.appendChild(card);
            });

            // Actualizar resumen
            updateFilesSummary();
        }

        // Función para actualizar resumen de archivos
        function updateFilesSummary() {
            if (selectedFiles.length === 0) {
                filesSummary.style.display = 'none';
                return;
            }

            filesSummary.style.display = 'block';

            let totalSize = 0;
            selectedFiles.forEach(file => totalSize += file.size);
            const totalSizeKB = (totalSize / 1024).toFixed(2);
            const fileCount = selectedFiles.length;

            document.getElementById('summaryCount').textContent =
                `${fileCount} archivo${fileCount !== 1 ? 's' : ''} seleccionado${fileCount !== 1 ? 's' : ''}`;
            document.getElementById('summaryTotal').textContent =
                `Total: ${fileCount} imagen${fileCount !== 1 ? 'es' : ''} nuevas`;
            document.getElementById('summarySize').textContent =
                `Tamaño total: ${totalSizeKB} KB`;
            document.getElementById('summaryReady').textContent =
                `Listo para subir ${fileCount} archivo${fileCount !== 1 ? 's' : ''}`;
        }

        // Función para eliminar un archivo específico (solo nuevas imágenes)
        function removeFile(index) {
            // Liberar URL del objeto si existe
            if (currentObjectURLs[index]) {
                URL.revokeObjectURL(currentObjectURLs[index]);
            }

            // Eliminar de los arrays
            selectedFiles.splice(index, 1);
            validationResults.splice(index, 1);
            currentObjectURLs.splice(index, 1);

            // Re-renderizar
            if (selectedFiles.length > 0) {
                renderImageCards();
            } else {
                clearPreviousContent();
            }

            // Actualizar estado de cambios
            formChanged = true;
        }

        // Función para mostrar error
        function showImageError(message) {
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());

            dropzone.classList.add('border-danger');
            const error = document.createElement('small');
            error.className = 'text-danger d-block mt-2 image-error';
            error.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${message}`;

            dropzone.parentElement.insertBefore(error, dropzone.nextSibling);

            error.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Función para manejar clic en dropzone
        function handleDropzoneClick(e) {
            // Verificar límite de archivos (10 imágenes nuevas)
            if (selectedFiles.length >= 10) {
                showToast('Máximo 10 imágenes nuevas permitidas', 'warning');
                return;
            }

            // Abrir selector de archivos
            input.click();
        }

        // ============================================
        // EVENT LISTENERS PARA DROPZONE PREMIUM
        // ============================================

        // Event Listeners para dropzone
        dropzone.addEventListener('click', handleDropzoneClick);

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#2563eb';
            dropzone.style.backgroundColor = '#eff6ff';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.backgroundColor = 'transparent';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.backgroundColor = 'transparent';

            if (e.dataTransfer.files.length) {
                const filesArray = Array.from(e.dataTransfer.files);

                // Verificar límite de archivos
                const remainingSlots = 10 - selectedFiles.length;
                const filesToAdd = filesArray.slice(0, remainingSlots);

                if (filesToAdd.length < filesArray.length) {
                    showToast(
                        `Solo se agregaron ${filesToAdd.length} de ${filesArray.length} imágenes (límite: 10)`,
                        'warning');
                }

                // Usar DataTransfer para crear FileList
                const dt = new DataTransfer();
                filesToAdd.forEach(f => dt.items.add(f));
                input.files = dt.files;

                const changeEvent = new Event('change');
                input.dispatchEvent(changeEvent);
            }
        });

        // Evento para cambiar el archivo con validación mejorada
        input.addEventListener('change', async function(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            // Calcular cuántos archivos nuevos podemos agregar
            const remainingSlots = 10 - selectedFiles.length;
            const newFiles = Array.from(files).slice(0, remainingSlots);

            if (newFiles.length < files.length) {
                showToast(`Solo se agregaron ${newFiles.length} de ${files.length} imágenes (límite: 10)`,
                    'warning');
            }

            if (newFiles.length === 0) {
                showToast('Ya has alcanzado el límite de 10 imágenes nuevas', 'warning');
                return;
            }

            // Quitar errores previos
            dropzone.classList.remove('border-danger');
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
            submitBtn.disabled = true;
            backBtn.classList.add('disabled-btn');
            backBtn.style.pointerEvents = 'none';

            try {
                // Validar cada archivo
                const validationPromises = newFiles.map(file => fileValidator.validateDesignFile(file));
                const newValidationResults = await Promise.all(validationPromises);

                // Verificar si hay errores
                const errors = newValidationResults.filter(result => !result.valid);
                if (errors.length > 0) {
                    showImageError(errors[0].reason);
                    return;
                }

                // Agregar archivos y resultados a las listas
                selectedFiles.push(...newFiles);
                validationResults.push(...newValidationResults);

                // Renderizar cards
                renderImageCards();

                // Mostrar toast de éxito
                showToast(
                    `${newFiles.length} imagen${newFiles.length !== 1 ? 'es' : ''} agregada${newFiles.length !== 1 ? 's' : ''} correctamente`,
                    'success');

                // Marcar que el formulario ha cambiado
                formChanged = true;

            } catch (error) {
                console.error('Error en validación:', error);
                showImageError('Error inesperado al validar los archivos. Intenta con otros archivos.');
            } finally {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
                submitBtn.disabled = false;
                backBtn.classList.remove('disabled-btn');
                backBtn.style.pointerEvents = 'auto';
            }
        });

        // ============================================
        // FUNCIONES DE VALIDACIÓN SIMILARES AL CREATE
        // ============================================

        // Función para mostrar error en un campo específico
        function showFieldError(field, message) {
            field.classList.add('is-invalid');

            // Buscar el div de error por ID del campo
            const fieldName = field.getAttribute('name');
            let errorElement = document.getElementById(fieldName + '-error');

            if (errorElement) {
                errorElement.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${message}`;
                errorElement.style.display = 'block';
            }
        }

        // Función para limpiar todos los errores de validación
        function clearValidationErrors() {
            // Limpiar errores de campos (clases is-invalid)
            form.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });

            // Limpiar errores de los divs con ID (name-error, sku-error, etc.)
            document.querySelectorAll('[id$="-error"]').forEach(el => {
                el.style.display = 'none';
                el.innerHTML = '';
            });

            // Limpiar errores del dropzone
            dropzone.classList.remove('border-danger');
            const imageErrors = document.querySelectorAll('.image-error');
            imageErrors.forEach(error => error.remove());
        }

        // ============================================
        // AGREGADO: EVENTOS PARA LIMPIAR ERRORES AL INTERACTUAR
        // ============================================

        // Limpiar error del nombre cuando se escribe
        nameInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const nameError = document.getElementById('name-error');
            if (nameError) {
                nameError.style.display = 'none';
                nameError.innerHTML = '';
            }
        });

        // Limpiar error del SKU cuando se escribe
        skuInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const skuError = document.getElementById('sku-error');
            if (skuError) {
                skuError.style.display = 'none';
                skuError.innerHTML = '';
            }
        });

        // Limpiar error del precio cuando se escribe
        form.querySelector('input[name="price"]')?.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const priceError = document.getElementById('price-error');
            if (priceError) {
                priceError.style.display = 'none';
                priceError.innerHTML = '';
            }
        });

        // Limpiar error del stock cuando se escribe
        form.querySelector('input[name="stock"]')?.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const stockError = document.getElementById('stock-error');
            if (stockError) {
                stockError.style.display = 'none';
                stockError.innerHTML = '';
            }
        });

        // ============================================
        // DETECCIÓN DE CAMBIOS EN EL FORMULARIO
        // ============================================
        form.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.type !== 'file') {
                el.addEventListener('change', () => formChanged = true);
                el.addEventListener('input', () => formChanged = true);
            }
        });

        // ============================================
        // FUNCIONES PARA EL SPINNER CON BARRA DE PROGRESO
        // ============================================

        // Función para deshabilitar todos los botones y preparar spinner
        function disableAllButtons() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
            submitBtn.classList.remove('disabled-btn');

            backBtn.disabled = false;
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Regresar';
            backBtn.classList.remove('disabled-btn');
            backBtn.style.pointerEvents = 'auto';
        }

        /**
         * Muestra el spinner con barra de progreso
         */
        function showLoadingState() {
            // Resetear barra de progreso
            resetProgressBar();

            // Mostrar overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.classList.add('fade-in');
            }

            // Actualizar textos iniciales
            document.getElementById('loadingTitle').textContent = 'Actualizando variante';
            document.getElementById('loadingSubtitle').textContent = 'Procesando tu solicitud, por favor espera...';
            document.getElementById('progressStatus').textContent = 'Validando datos...';

            // Bloquear scroll del body
            document.body.style.overflow = 'hidden';

            // Iniciar progreso automático
            startProgressSimulation();
        }

        /**
         * Oculta el spinner
         */
        function hideLoadingState() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
                loadingOverlay.classList.remove('fade-in');
            }

            // Restaurar scroll del body
            document.body.style.overflow = 'auto';
        }

        /**
         * Resetea la barra de progreso a 0%
         */
        function resetProgressBar() {
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const progressBar = document.getElementById('progressBar');

            if (progressFill) progressFill.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
            if (progressBar) {
                progressBar.classList.remove('progress-complete', 'progress-error');
            }
        }

        /**
         * Actualiza la barra de progreso
         */
        function updateProgress(percentage, status = null) {
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const progressStatus = document.getElementById('progressStatus');

            // Asegurar que el porcentaje esté entre 0 y 100
            const safePercentage = Math.min(100, Math.max(0, percentage));

            // Actualizar barra visual
            if (progressFill) {
                progressFill.style.width = `${safePercentage}%`;
            }

            // Actualizar texto
            if (progressText) {
                progressText.textContent = `${Math.round(safePercentage)}%`;

                // Cambiar color cuando se complete
                if (safePercentage >= 100) {
                    const progressBar = document.getElementById('progressBar');
                    if (progressBar) {
                        progressBar.classList.add('progress-complete');
                    }
                }
            }

            // Actualizar estado si se proporciona
            if (status && progressStatus) {
                progressStatus.textContent = status;
            }
        }

        /**
         * Muestra estado de error en la barra de progreso
         */
        function showProgressError(errorMessage) {
            const progressBar = document.getElementById('progressBar');
            const progressStatus = document.getElementById('progressStatus');

            if (progressBar) {
                progressBar.classList.add('progress-error');
            }

            if (progressStatus) {
                progressStatus.textContent = errorMessage;
                progressStatus.style.color = '#dc2626';
            }

            // Actualizar título y subtítulo
            document.getElementById('loadingTitle').textContent = '¡Error!';
            document.getElementById('loadingSubtitle').textContent = 'Hubo un problema al procesar tu solicitud.';

            // Ocultar después de 3 segundos
            setTimeout(() => {
                hideLoadingState();
                enableAllButtons();
                isFormSubmitting = false;
                isSubmitting = false;
            }, 3000);
        }

        /**
         * Simula el progreso de carga
         */
        function startProgressSimulation() {
            let progress = 0;
            const statusMessages = [
                'Validando datos...',
                'Procesando imágenes...',
                'Guardando en servidor...',
                'Optimizando recursos...',
                'Finalizando proceso...',
                '¡Completado!'
            ];

            // Iniciar simulación
            const interval = setInterval(() => {
                progress += 1;

                // Cambiar mensaje según progreso
                let statusIndex = 0;
                if (progress >= 20) statusIndex = 1;
                if (progress >= 40) statusIndex = 2;
                if (progress >= 60) statusIndex = 3;
                if (progress >= 80) statusIndex = 4;
                if (progress >= 95) statusIndex = 5;

                updateProgress(progress, statusMessages[statusIndex]);

                // Cuando llegue al 100%, mantener por 1 segundo y luego dejar que el formulario continúe
                if (progress >= 100) {
                    clearInterval(interval);

                    // Cambiar a estado completado
                    document.getElementById('loadingTitle').textContent = '¡Variante Actualizada!';
                    document.getElementById('loadingSubtitle').textContent =
                        'Redirigiendo a la lista de diseños...';

                    // Marcar que el formulario se está enviando
                    isFormSubmitting = true;
                    formChanged = false;

                    // Esperar 1.5 segundos para mostrar el estado "completado"
                    setTimeout(() => {
                        // Permitir que el formulario se envíe
                        hideLoadingState();

                        // Actualizar input files antes de enviar (solo nuevas imágenes)
                        const dt = new DataTransfer();
                        selectedFiles.forEach(file => dt.items.add(file));
                        input.files = dt.files;

                        // Enviar el formulario
                        form.submit();
                    }, 1500);
                }
            }, 30);
        }

        // ============================================
        // MANEJO DEL FORMULARIO CON VALIDACIÓN
        // ============================================
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }

            // Validaciones básicas antes de enviar
            clearValidationErrors();

            const name = nameInput.value.trim();
            let hasErrors = false;

            // Validar nombre (igual que en el create)
            if (!name) {
                showFieldError(nameInput, 'El nombre de la variante es obligatorio.');
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

            // 2. Pequeña pausa para que se muestre el spinner
            await new Promise(resolve => setTimeout(resolve, 100));

            // 3. Permitir que el formulario se envíe normalmente
            // El spinner continuará mostrándose hasta que la página se recargue
        });

        // ============================================
        // FUNCIONES ORIGINALES (MANTENIDAS)
        // ============================================

        // Función para seleccionar imagen principal existente
        function selectExistingPrimaryImage(imageId) {
            // Actualizar input hidden
            document.getElementById('primary_image_id').value = imageId;

            // Quitar selección de todas
            document.querySelectorAll('.primary-image-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            // Agregar selección a la seleccionada
            const selectedOption = document.querySelector(`.primary-image-option[data-image-id="${imageId}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }

            // Marcar cambio en formulario
            formChanged = true;
        }

        // ============================================
        // NUEVA FUNCIÓN CON MODAL DE BOOTSTRAP (REEMPLAZA SWEETALERT)
        // ============================================
        function confirmDeleteImage(url) {
            // Mostrar el modal de confirmación con botón X azul
            $('#deleteImageConfirmModal').modal('show');

            // Configurar el evento del botón de confirmación
            document.getElementById('confirmDeleteImageBtn').onclick = function() {
                const form = document.getElementById('deleteImageForm');
                form.action = url;
                form.submit();
            };
        }

        // ============================================
        // LIMPIAR EVENTO AL CERRAR EL MODAL
        // ============================================
        $(document).ready(function() {
            // Limpiar el evento cuando el modal de confirmación de imagen se cierre
            $('#deleteImageConfirmModal').on('hidden.bs.modal', function() {
                document.getElementById('confirmDeleteImageBtn').onclick = null;
            });
        });

        // Manejar el evento beforeunload
        window.addEventListener('beforeunload', e => {
            if (formChanged && !isFormSubmitting) {
                e.preventDefault();
                e.returnValue = '';
                return '¿Deseas abandonar el sitio?\n\nEs posible que los cambios que implementaste no se puedan guardar.\n\n';
            }
        });

        // Manejar el botón de regresar
        backBtn.addEventListener('click', function(e) {
            if (isSubmitting) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Cancelar operación?',
                    text: 'La variante se está actualizando. ¿Estás seguro de que quieres cancelar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'Continuar actualizando',
                    customClass: {
                        popup: 'apple-sweet-alert'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Cancelar operación
                        isSubmitting = false;
                        isFormSubmitting = false;
                        enableAllButtons();
                        hideLoadingState();

                        // Redirigir
                        window.location.href = this.href;
                    }
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

        // También ocultar spinner cuando se cargue la página normalmente
        window.addEventListener('load', function() {
            setTimeout(() => {
                hideLoadingState();
                enableAllButtons();
                isSubmitting = false;
                isFormSubmitting = false;
            }, 500);
        });
    </script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                timer: 4000,
                showConfirmButton: false,
                customClass: {
                    popup: 'fade-in apple-sweet-alert'
                }
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
                confirmButtonColor: '#2563eb',
                customClass: {
                    popup: 'shake apple-sweet-alert'
                }
            });
        </script>
    @endif
@stop
