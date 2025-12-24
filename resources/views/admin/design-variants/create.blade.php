@extends('adminlte::page')

@section('title', 'Nueva Variante')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="text-2xl font-weight-semibold text-gray-800">
            Nueva variante de: {{ $design->name }}
        </h1>
        <div class="d-flex gap-2">
            <a id="back-btn" href="{{ route('admin.designs.index') }}" class="btn btn-secondary btn-md px-3 mr-2">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
            <button id="submitBtn" form="variant-form" type="submit" class="btn btn-primary btn-md px-3">
                <i class="fas fa-save"></i> Guardar variante
            </button>
        </div>
    </div>
@stop

@section('content')
    {{-- ============================================
         SPINNER PREMIUM CON BARRA DE PROGRESO (NUEVO)
         ============================================ --}}
    <div class="modal-loading-overlay" id="loadingOverlay">
        <div class="modal-loading-content">
            <div class="modal-loading-spinner"></div>
            <h3 class="modal-loading-title" id="loadingTitle">Guardando variante</h3>
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

    <form id="variant-form" action="{{ route('admin.designs.variants.store', $design) }}" method="POST"
        enctype="multipart/form-data">
        @csrf

        <div class="row">
            {{-- COLUMNA IZQUIERDA --}}
            <div class="col-lg-5">
                <div class="surface">
                    <h5 class="mb-3">
                        <i class="fas fa-image text-primary"></i> Imagen de la variante
                    </h5>

                    <div class="alert alert-info mb-3" style="font-size: 13px; padding: 10px;">
                        <i class="fas fa-info-circle"></i>
                        Sube hasta 10 imágenes (máx. 10MB c/u) específicas para esta variante.
                    </div>

                    {{-- DROPZONE COMPACTO --}}
                    <div id="dropzone" class="dropzone dropzone-compact">
                        <input type="file" id="imageInput" name="variant_images[]" multiple accept="image/*" hidden>

                        {{-- Contenido de la dropzone (texto) --}}
                        <div class="dropzone-content" id="dropzoneContent">
                            <div class="dropzone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="dropzone-title">Subir imágenes</div>
                            <div class="dropzone-sub">Arrastra imágenes aquí o haz clic</div>
                            <div class="dropzone-formats">
                                Formatos: JPEG, PNG, SVG, AVIF, WebP, etc.
                            </div>
                        </div>
                    </div>

                    {{-- Contenedor scrolleable para las imágenes con analizador --}}
                    <div id="imagesScrollContainer" class="images-scroll-container">
                        <div id="imagesGrid" class="images-analysis-grid">
                            {{-- Aquí se agregan las imágenes con su info --}}
                        </div>
                    </div>

                    {{-- Resumen de archivos --}}
                    <div id="filesSummary" class="files-summary" style="display: none;">
                        <div class="summary-header">
                            <i class="fas fa-images text-primary"></i>
                            <span id="summaryCount">0 archivos seleccionados</span>
                        </div>
                        <div class="summary-details">
                            <span id="summaryTotal">Total: 0 imágenes</span>
                            <span id="summarySize">Tamaño total: 0 KB</span>
                        </div>
                        <div class="summary-status">
                            <i class="fas fa-check-circle text-success"></i>
                            <span id="summaryReady">Listo para subir 0 archivos</span>
                        </div>
                    </div>

                    {{-- Toast para mensajes --}}
                    <div id="appleToast" class="apple-toast">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="toastMessage" style="font-weight: 500; color: #1d1d1f;"></span>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA --}}
            <div class="col-lg-7">
                <div class="surface">
                    <h5 class="mb-4">
                        <i class="fas fa-info-circle text-primary"></i> Información básica
                    </h5>

                    <div class="mb-4">
                        <label class="label">Nombre de la variante <span class="text-danger">*</span></label>
                        <input type="text" id="variant_name" name="name"
                            class="input @error('name') is-invalid @enderror"
                            placeholder="Ej. Sentado, Edición Especial, etc." value="{{ old('name') }}" required>
                        @error('name')
                            <span class="text-danger small d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="label">SKU (Código único) </label>
                        <input type="text" id="variant_sku" name="sku"
                            class="input sku-readonly @error('sku') is-invalid @enderror" placeholder="SKU_AUTOMATICO"
                            value="{{ old('sku') }}" readonly tabindex="-1">
                        <small class="hint">Código automático jerárquico profesional</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="label">Precio <span class="text-danger">*</span></label>
                            <div class="input-group custom-unified-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text unificado-text">$</span>
                                </div>
                                <input type="number" name="price" required
                                    class="input unificado-input @error('price') is-invalid @enderror"
                                    placeholder="Ej: 150.00" step="0.01" min="0" value="{{ old('price') }}">
                            </div>
                            @error('price')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">
                        <i class="fas fa-tags text-primary"></i> Atributos de la variante *
                    </h5>

                    @foreach ($attributes as $attribute)
                        <div class="mb-4">
                            <label class="label">{{ $attribute->name }}</label>
                            @if ($attribute->type === 'color')
                                <div class="color-picker-grid">
                                    @foreach ($attribute->values as $value)
                                        <div class="color-option">
                                            <input type="checkbox" name="attribute_values[]" value="{{ $value->id }}"
                                                id="attr_{{ $value->id }}" data-name="{{ $value->value }}"
                                                class="color-checkbox attribute-checkbox"
                                                {{ in_array($value->id, old('attribute_values', [])) ? 'checked' : '' }}>
                                            <label for="attr_{{ $value->id }}" class="color-label"
                                                style="background-color: {{ $value->hex_color }};"
                                                title="{{ $value->value }}">
                                                <span class="color-name">{{ $value->value }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="attribute-options">
                                    @foreach ($attribute->values as $value)
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input attribute-checkbox"
                                                name="attribute_values[]" data-name="{{ $value->value }}"
                                                value="{{ $value->id }}" id="attr_{{ $value->id }}"
                                                {{ in_array($value->id, old('attribute_values', [])) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="attr_{{ $value->id }}">
                                                {{ $value->value }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="mt-4">
                        <div class="custom-control custom-switch mb-2">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" class="custom-control-input" id="is_default" name="is_default"
                                value="1" {{ old('is_default') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_default">
                                <strong>Variante por defecto</strong>
                            </label>
                        </div>
                        <div class="custom-control custom-switch mt-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" checked>
                            <label class="custom-control-label" for="is_active">
                                <strong>Variante activa</strong>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('css')
    <style>
        /* ESTILOS ORIGINALES (MANTENIDOS) */
        .surface {
            background: #fff;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .04);
            height: 100%;
        }

        .label {
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 6px;
            display: block;
        }

        .input {
            width: 100%;
            border: 1px solid #d2d2d7;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            background: #f5f5f7;
            transition: all 0.3s ease;
        }

        .input:focus {
            outline: none;
            border-color: #0071e3;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1);
        }

        .custom-unified-group {
            display: flex;
            align-items: stretch;
            background: #f5f5f7;
            border-radius: 12px;
            border: 1px solid #d2d2d7;
            height: 48px;
            overflow: hidden;
        }

        .unificado-text {
            background: transparent !important;
            border: none !important;
            color: #86868b;
            padding: 0 12px;
            display: flex;
            align-items: center;
        }

        .unificado-input {
            border: none !important;
            background: transparent !important;
            flex: 1;
            outline: none !important;
        }

        /* Dropzone Original (Mantenido para compatibilidad) */
        .dropzone {
            border: 2px dashed #d1d5db;
            border-radius: 18px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            background: #fafafa;
            transition: all 0.3s;
        }

        .dropzone:hover {
            border-color: #0071e3;
            background: #f5f5f7;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .preview-item {
            position: relative;
            padding-top: 100%;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .preview-item img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 90%;
            object-fit: contain;
        }

        .remove {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            cursor: pointer;
            z-index: 10;
            font-size: 18px;
            line-height: 1;
        }

        .color-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
        }

        .color-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            border: 3px solid transparent;
        }

        .color-option input {
            display: none;
        }

        .color-option input:checked+.color-label {
            border-color: #0071e3;
            transform: scale(1.05);
        }

        .color-name {
            font-size: 10px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.8);
            padding: 2px 5px;
            border-radius: 4px;
        }

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

        .sku-readonly {
            background-color: #f1f1f2 !important;
            color: #86868b;
            cursor: not-allowed;
        }

        /* ============================================
               SISTEMA PREMIUM (NUEVOS ESTILOS AGREGADOS)
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

        .dropzone-icon {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .dropzone-title {
            font-weight: 600;
            font-size: 18px;
            color: #111827;
            margin-bottom: 5px;
        }

        .dropzone-sub {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .dropzone-formats {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 10px;
        }

        /* ============================================
           CONTENEDOR SCROLLEABLE DE IMÁGENES
           ============================================ */
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
            background: #f1f1f1;
            border-radius: 10px;
        }

        .images-scroll-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .images-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
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
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .image-analysis-card:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .image-analysis-card.selected {
            border-color: #2563eb;
            border-width: 2px;
        }

        /* Preview de imagen dentro de la card */
        .image-card-preview {
            position: relative;
            width: 100%;
            height: 100px;
            background: #f8fafc;
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

        /* Botón de eliminar */
        .image-card-remove {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ef4444;
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
            border-top: 1px solid #f1f5f9;
            background: #fafafa;
        }

        .image-card-name {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .image-card-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .image-card-format {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: #059669;
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
            color: #d97706;
            margin-right: 3px;
        }

        /* Resumen de archivos */
        .files-summary {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 12px;
        }

        .summary-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .summary-details {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .summary-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #059669;
            font-weight: 500;
        }

        /* Para archivos de bordado */
        .embroidery-preview {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
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

        /* Responsive para grid de 1 columna en móvil */
        @media (max-width: 576px) {
            .images-analysis-grid {
                grid-template-columns: 1fr;
            }
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

        /* ========== ESTILOS PARA INFORMACIÓN DE ARCHIVO ========== */
        .file-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            margin-top: 12px;
        }

        .file-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .file-info-name {
            font-weight: 600;
            color: #333;
            word-break: break-all;
        }

        .file-info-details {
            display: flex;
            justify-content: space-between;
            color: #6b7280;
            font-size: 12px;
        }

        .file-info-success {
            color: #059669;
            font-weight: 500;
        }

        /* Animación fade-in */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        /* Estilos para botones deshabilitados */
        .disabled-btn {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Dropzone con error */
        .dropzone.border-danger {
            border-color: #dc2626 !important;
            background: #fef2f2;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .dropzone {
                min-height: 250px;
            }

            .dropzone-title {
                font-size: 16px;
            }

            .preview-multiple-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .preview-multiple-item {
                width: 100px;
                height: 100px;
            }
        }
    </style>
@stop

@section('js')
    <script>
        // ============================================
        // SISTEMA PREMIUM COMPLETO PARA NUEVA VARIANTE
        // ============================================

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

        // ============================================
        // CÓDIGO PRINCIPAL DE LA VISTA
        // ============================================
        const dropzone = document.getElementById('dropzone');
        const input = document.getElementById('imageInput');
        const imagesGrid = document.getElementById('imagesGrid');
        const imagesScrollContainer = document.getElementById('imagesScrollContainer');
        const filesSummary = document.getElementById('filesSummary');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('variant-form');
        const backBtn = document.getElementById('back-btn');
        const nameInput = document.getElementById('variant_name');
        const skuInput = document.getElementById('variant_sku');
        const attributeCheckboxes = document.querySelectorAll('.attribute-checkbox');
        const designBaseName = "{{ $design->name }}";
        const appleToast = document.getElementById('appleToast');
        const toastMessage = document.getElementById('toastMessage');

        let isSubmitting = false;
        let currentObjectURLs = [];
        const fileValidator = new DesignFileValidator();
        let selectedFiles = [];
        let validationResults = []; // Guardar resultados de validación

        // Variable para controlar si hay cambios en el formulario
        let formChanged = false;
        let isFormSubmitting = false;

        // ============================================
        // FUNCIONALIDAD ORIGINAL (SKU Y ATRIBUTOS) - MANTENIDA
        // ============================================

        function updateSku() {
            let selectedValues = [];
            attributeCheckboxes.forEach(cb => {
                if (cb.checked) selectedValues.push(cb.dataset.name);
            });

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

        nameInput.addEventListener('input', updateSku);
        attributeCheckboxes.forEach(cb => cb.addEventListener('change', updateSku));
        updateSku(); // Inicializar SKU

        // ============================================
        // FUNCIONALIDAD DEL DROPZONE MULTIPLE MEJORADO
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

            // Botón de eliminar
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
                case 'image': fileTypeName = 'Imagen'; break;
                case 'vector': fileTypeName = 'Vector'; break;
                case 'embroidery': fileTypeName = 'Bordado'; break;
                default: fileTypeName = 'Archivo';
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
                `Total: ${fileCount} imagen${fileCount !== 1 ? 'es' : ''}`;
            document.getElementById('summarySize').textContent =
                `Tamaño total: ${totalSizeKB} KB`;
            document.getElementById('summaryReady').textContent =
                `Listo para subir ${fileCount} archivo${fileCount !== 1 ? 's' : ''}`;
        }

        // Función para eliminar un archivo específico
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
            // Verificar límite de archivos
            if (selectedFiles.length >= 10) {
                showToast('Máximo 10 imágenes permitidas', 'warning');
                return;
            }

            // Abrir selector de archivos
            input.click();
        }

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
                showToast('Ya has alcanzado el límite de 10 imágenes', 'warning');
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
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar variante';
                submitBtn.disabled = false;
                backBtn.classList.remove('disabled-btn');
                backBtn.style.pointerEvents = 'auto';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar variante';
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
            document.getElementById('loadingTitle').textContent = 'Guardando variante';
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
         * @param {number} percentage - Porcentaje de 0 a 100
         * @param {string} status - Texto de estado (opcional)
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
         * @param {string} errorMessage - Mensaje de error
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
         * Simula el progreso de carga (para cuando no hay progreso real del servidor)
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
                    document.getElementById('loadingTitle').textContent = '¡Variante Guardada!';
                    document.getElementById('loadingSubtitle').textContent =
                        'Redirigiendo a la lista de diseños...';

                    // Marcar que el formulario se está enviando (para evitar mensaje de beforeunload)
                    isFormSubmitting = true;
                    formChanged = false;

                    // Esperar 1.5 segundos para mostrar el estado "completado"
                    setTimeout(() => {
                        // Permitir que el formulario se envíe
                        hideLoadingState();

                        // Actualizar input files antes de enviar
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
        // MANEJO DEL FORMULARIO CON SPINNER PREMIUM
        // ============================================
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) {
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

        // Manejar el evento beforeunload - SOLO si no estamos usando el botón de regresar
        window.addEventListener('beforeunload', e => {
            // No mostrar el mensaje nativo si está enviando el formulario
            if (isFormSubmitting) {
                return;
            }
            // Para otros casos de navegación (cerrar pestaña, etc)
            // Ya no mostramos el mensaje nativo, el botón de regresar tiene su propio handler
        });

        // Manejar el botón de regresar - CORREGIDO
        backBtn.addEventListener('click', function(e) {
            const backUrl = this.href;

            // Si está enviando el formulario
            if (isSubmitting) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Cancelar operación?',
                    text: 'La variante se está guardando. ¿Estás seguro de que quieres cancelar?',
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
                        window.location.href = backUrl;
                    }
                });
                return;
            }

            // Si hay cambios en el formulario (incluyendo imágenes)
            if (formChanged || selectedFiles.length > 0) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Deseas abandonar el sitio?',
                    text: 'Es posible que los cambios que implementaste no se puedan guardar.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6b7280',
                    cancelButtonColor: '#2563eb',
                    confirmButtonText: 'Abandonar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Limpiar estado y redirigir
                        formChanged = false;
                        window.location.href = backUrl;
                    }
                });
            }
            // Si no hay cambios, permitir navegación normal
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
                    popup: 'fade-in'
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
                    popup: 'shake'
                }
            });
        </script>
    @endif
@stop
