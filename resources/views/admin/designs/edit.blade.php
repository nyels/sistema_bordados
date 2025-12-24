@extends('adminlte::page')

@section('title', 'Editar diseño')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="text-2xl font-weight-semibold text-gray-800">
            Editar diseño: {{ $design->name }}
        </h1>
        <div class="d-flex gap-2">
            <a id="back-btn" href="{{ route('admin.designs.index') }}" class="btn btn-secondary btn-md px-3 mr-2">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
            <button id="submitBtn" form="design-form" type="submit" class="btn btn-primary btn-md px-3">
                <i class="fas fa-save"></i> Actualizar
            </button>
        </div>
    </div>

    {{-- Envolver en row y col para alinear con las tarjetas de abajo --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="variants-indicator sticky-indicator" id="variantsIndicator" style="border-radius: 15px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-layer-group text-primary mr-2"></i>
                        <strong>{{ $design->variants->count() }}</strong>
                        <span>variante{{ $design->variants->count() != 1 ? 's' : '' }}</span>
                        @if ($design->variants->count() == 0)
                            <small class="ml-2" style="color: white !important;font-weight: bold;">(Crea tu primera
                                variante abajo)</small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="scrollToVariants()">
                        <i class="fas fa-arrow-down"></i> Ver variantes
                    </button>
                </div>
            </div>
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
            <h3 class="modal-loading-title" id="loadingTitle">Actualizando diseño</h3>
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

    <form id="design-form" action="{{ route('admin.designs.update', $design) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="col-lg-7">

                <div class="surface">

                    {{-- Nombre del diseño --}}
                    <div class="mb-4">
                        <label class="label">Nombre del diseño</label>
                        <input type="text" name="name" class="input @error('name') is-invalid @enderror"
                            placeholder="Ej. Mariposa floral minimalista" value="{{ old('name', $design->name) }}" required>
                        <div id="name-error" class="text-danger small mt-1" style="display: none;"></div>
                        @error('name')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-4">
                        <label class="label">Categorías</label>
                        <select name="categories[]" class="input @error('categories') is-invalid @enderror" multiple
                            size="6">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ in_array($category->id, old('categories', $selectedCategories)) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="categories-error" class="text-danger small mt-1" style="display: none;"></div>
                        <small class="hint">
                            Selecciona una o varias categorías (mantén Ctrl/Cmd para seleccionar múltiples)
                        </small>
                        @error('categories')
                            <span class="text-danger small d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-4">
                        <label class="label">Descripción</label>
                        <textarea name="description" rows="5" class="input @error('description') is-invalid @enderror"
                            placeholder="Describe el estilo, uso y enfoque del diseño…">{{ old('description', $design->description) }}</textarea>
                        <div id="description-error" class="text-danger small mt-1" style="display: none;"></div>
                        @error('description')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Estado activo --}}
                    <div style="display: none">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" {{ old('is_active', $design->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">
                                <strong>Diseño activo</strong>
                            </label>
                        </div>
                        <small class="hint">
                            Los diseños inactivos no se mostrarán en el listado público
                        </small>
                    </div>

                </div>

            </div>

            {{-- COLUMNA DERECHA --}}
            <div class="col-lg-5">

                <div class="surface">

                    <label class="label mb-3">Imagen del diseño</label>

                    {{-- Imagen actual --}}
                    @if ($design->primaryImage)
                        <div class="current-image-container mb-3">
                            <label class="label small text-muted mb-2">Imagen actual:</label>
                            <div class="current-image-wrapper">
                                <img src="{{ asset('storage/' . $design->primaryImage->file_path) }}"
                                    alt="{{ $design->name }}" class="current-image">
                            </div>
                            <div class="image-info mt-2">
                                <small class="text-muted d-block">
                                    <i class="fas fa-file"></i> {{ $design->primaryImage->file_name }}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar"></i>
                                    Subida el {{ $design->primaryImage->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                        </div>
                    @endif

                    {{-- Alerta warning --}}
                    <div class="alert alert-warning mb-3" style="font-size: 13px; padding: 10px; border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Nota:</strong> Si subes una nueva imagen, reemplazará la actual como imagen principal.
                    </div>

                    {{-- Dropzone para nueva imagen --}}
                    <div id="dropzone" class="dropzone">
                        <input type="file" id="imageInput" name="image" accept="image/*" hidden>

                        {{-- Contenido de la dropzone (texto) --}}
                        <div class="dropzone-content" id="dropzoneContent">
                            <div class="dropzone-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="dropzone-title">
                                {{ $design->primaryImage ? 'Cambiar imagen' : 'Subir imagen' }}
                            </div>
                            <div class="dropzone-sub">
                                Arrastra una imagen aquí o haz clic
                            </div>
                            <div class="dropzone-formats">
                                Formatos: JPEG, PNG, SVG, AVIF, WebP, PES, DST, etc.
                            </div>
                        </div>

                        {{-- Contenedor para la vista previa de la imagen --}}
                        <div id="previewContainer" class="preview-container"></div>
                    </div>

                    {{-- Contenedor para la información del archivo --}}
                    <div id="fileInfoContainer" class="mt-3"></div>
                    <div id="image-error" class="text-danger small mt-1" style="display: none;"></div>

                </div>

            </div>

        </div>

    </form>

    {{-- ===== SECCIÓN DE VARIANTES ===== --}}
    <div class="row mt-4" id="variantsSection">
        <div class="col-12">
            @if ($design->variants->count() > 0)
                <div class="surface">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group text-primary"></i>
                            Variantes del diseño ({{ $design->variants->count() }})
                        </h5>
                        <a href="{{ route('admin.designs.variants.create', $design) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Agregar variante
                        </a>
                    </div>

                    <div class="variants-grid">
                        @foreach ($design->variants as $variant)
                            {{-- ⭐ ID ÚNICO PARA CADA VARIANTE --}}
                            <div class="variant-card variant-scroll-target" id="variant-{{ $variant->id }}">
                                {{-- Imagen de la variante --}}
                                <div class="variant-image">
                                    @if ($variant->primaryImage)
                                        <img src="{{ asset('storage/' . $variant->primaryImage->file_path) }}"
                                            alt="{{ $variant->primaryImage->alt_text ?? $variant->name }}">
                                    @else
                                        <div class="no-variant-image">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- Info de la variante --}}
                                <div class="variant-body">
                                    <h6 class="variant-name">{{ $variant->name }}</h6>
                                    <div class="variant-details">
                                        <small class="text-muted d-block">SKU: {{ $variant->sku }}</small>
                                        @if ($variant->price)
                                            <small class="text-success d-block">
                                                <strong>${{ number_format($variant->price, 2) }}</strong>
                                            </small>
                                        @endif
                                    </div>

                                    {{-- Badges --}}
                                    <div class="variant-badges mt-2">
                                        @if ($variant->is_default)
                                            <span class="badge badge-primary">Principal</span>
                                        @endif
                                        @if ($variant->is_active)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Acciones --}}
                                <div class="variant-actions">
                                    <a href="{{ route('admin.designs.variants.edit', [$design, $variant]) }}"
                                        class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-variant"
                                        data-url="{{ route('admin.designs.variants.destroy', [$design, $variant]) }}"
                                        title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Card para crear primera variante --}}
                <div class="surface text-center py-5 empty-variants-card">
                    <div class="empty-icon mb-3">
                        <i class="fas fa-layer-group fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">Este diseño no tiene variantes</h4>
                    <p class="text-muted mb-4">
                        Las variantes te permiten tener diferentes versiones del mismo diseño<br>
                        con diferentes colores, tamaños, estilos, precios e inventario individual.
                    </p>
                    <div class="benefits-grid mb-4">
                        <div class="benefit-item">
                            <i class="fas fa-palette text-primary"></i>
                            <small>Diferentes colores</small>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-dollar-sign text-success"></i>
                            <small>Precios individuales</small>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-boxes text-info"></i>
                            <small>Control de stock</small>
                        </div>
                    </div>
                    <a href="{{ route('admin.designs.variants.create', $design) }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> Crear primera variante
                    </a>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <style>
        .surface {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .04);
            height: 100%;
        }

        /* ========== INDICADOR STICKY DE VARIANTES ========== */
        .variants-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .variants-indicator strong {
            font-size: 20px;
            color: #fff;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 12px;
        }

        .variants-indicator .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s;
        }

        .variants-indicator .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(2px);
        }

        /* ========== LABELS E INPUTS ========== */
        .label {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            display: block;
        }

        .input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            background: #fafafa;
            transition: border .2s, background .2s;
        }

        .input:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
        }

        .input.is-invalid {
            border-color: #dc3545;
        }

        .hint {
            font-size: 13px;
            color: #6b7280;
        }

        /* ========== IMAGEN ACTUAL ========== */
        .current-image-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e5e7eb;
        }

        .current-image-wrapper {
            text-align: center;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .current-image {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
        }

        .image-info {
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        /* ========== DROPZONE ========== */
        .dropzone {
            border: 2px dashed #d1d5db;
            border-radius: 18px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: #fafafa;
            position: relative;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .dropzone:hover {
            background: #f3f4f6;
            border-color: #2563eb;
        }

        .dropzone.has-preview {
            padding: 0;
            border: 2px solid #2563eb;
            background: #f8fafc;
        }

        .dropzone.has-preview .dropzone-content {
            display: none;
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

        /* ========== PREVIEW CONTAINER ========== */
        .preview-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 16px;
        }

        .preview-container.active {
            display: flex;
        }

        .preview-item {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .preview-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Botón de eliminar (tachita) - POSICIÓN CORREGIDA */
        .remove-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ef4444;
            color: white;
            border: 2px solid white;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Icono para archivos de bordado */
        .embroidery-preview {
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            color: white;
            text-align: center;
            padding: 30px;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .embroidery-preview i {
            font-size: 60px;
            margin-bottom: 15px;
        }

        /* Para SVGs con fondo blanco */
        .svg-preview {
            background: white;
            padding: 20px;
        }

        /* ========== EMPTY STATE (SIN VARIANTES) ========== */
        .empty-variants-card {
            background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
            border: 2px dashed #d1d5db;
        }

        .empty-icon {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .benefit-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .benefit-item i {
            font-size: 24px;
        }

        .benefit-item small {
            color: #6b7280;
            font-weight: 500;
        }

        /* ========== VARIANTES GRID ========== */
        .variants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .variant-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
            scroll-margin-top: 120px;
        }

        .variant-card:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        /* ⭐ ANIMACIÓN DE HIGHLIGHT */
        .variant-card.highlight-variant {
            animation: variantHighlight 2s ease-in-out;
        }

        @keyframes variantHighlight {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
                border-color: #e5e7eb;
            }

            50% {
                box-shadow: 0 0 0 15px rgba(37, 99, 235, 0.2);
                border-color: #2563eb;
            }
        }

        .variant-image {
            height: 150px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .variant-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .no-variant-image {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .variant-body {
            padding: 12px;
            background: #fff;
        }

        .variant-name {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
            margin-bottom: 8px;
        }

        .variant-details {
            margin-bottom: 8px;
        }

        .variant-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .variant-actions {
            display: flex;
            gap: 5px;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            justify-content: center;
        }

        /* ========== SWITCH PERSONALIZADO ========== */
        .custom-control-input:checked~.custom-control-label::before {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        /* ========== SCROLL SMOOTH ========== */
        html {
            scroll-behavior: smooth;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 991px) {
            .variants-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }

            .dropzone {
                min-height: 250px;
            }

            .dropzone-title {
                font-size: 16px;
            }
        }

        .shake {
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }

        .premium-swal-popup {
            border-radius: 20px !important;
            padding: 2rem !important;
        }

        .premium-swal-confirm {
            background-color: #dc3545 !important;
            border-radius: 12px !important;
            padding: 12px 30px !important;
            font-weight: 600 !important;
        }

        .premium-swal-cancel {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
            border-radius: 12px !important;
            padding: 12px 30px !important;
            font-weight: 600 !important;
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
    <script>
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
        const previewContainer = document.getElementById('previewContainer');
        const fileInfoContainer = document.getElementById('fileInfoContainer');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('design-form');
        const backBtn = document.getElementById('back-btn');

        let isSubmitting = false;
        let currentObjectURL = null;
        const fileValidator = new DesignFileValidator();

        // Variable para controlar si hay cambios en el formulario
        let formChanged = false;
        let isFormSubmitting = false;

        function scrollToVariants() {
            const variantsSection = document.getElementById('variantsSection');
            variantsSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Función para limpiar todo el contenido anterior
        function clearPreviousContent() {
            previewContainer.innerHTML = '';
            previewContainer.classList.remove('active');
            fileInfoContainer.innerHTML = '';

            if (currentObjectURL) {
                URL.revokeObjectURL(currentObjectURL);
                currentObjectURL = null;
            }

            dropzone.classList.remove('border-danger');
            dropzone.classList.remove('has-preview');

            // Eliminar todos los mensajes de error
            const existingErrors = document.querySelectorAll('.image-error');
            existingErrors.forEach(error => error.remove());
        }

        // Función para mostrar información del archivo
        function showFileInfo(file, validationResult) {
            const fileSize = (file.size / 1024).toFixed(2);
            let fileTypeName = '';
            let extensionNote = '';

            switch (validationResult.type) {
                case 'image':
                    fileTypeName = 'Imagen';
                    break;
                case 'vector':
                    fileTypeName = 'Vector (SVG)';
                    break;
                case 'embroidery':
                    fileTypeName = 'Archivo de bordado';
                    break;
                default:
                    fileTypeName = 'Archivo';
            }

            const originalExtension = file.name.toLowerCase().split('.').pop();
            if (validationResult.detectedBy === 'signature' &&
                validationResult.subtype &&
                originalExtension !== validationResult.subtype.toLowerCase()) {

                extensionNote = `
                <div class="alert alert-warning mt-2 p-2" style="font-size: 12px; border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Nota:</strong> El archivo tiene extensión .${originalExtension}
                    pero es un formato ${validationResult.subtype.toUpperCase()}.
                    Se guardará con la extensión correcta (.${validationResult.subtype}).
                </div>
            `;
            }

            const fileInfoHTML = `
            <div class="file-info fade-in">
                <div class="file-info-header">
                    <div class="file-info-name">
                        <i class="fas fa-${fileValidator.getFileIcon(validationResult.subtype || validationResult.type)} mr-2"></i>
                        ${file.name}
                    </div>
                </div>
                <div class="file-info-details">
                    <div>
                        ${fileTypeName} (${validationResult.subtype ? validationResult.subtype.toUpperCase() : 'Desconocido'})
                    </div>
                    <div>
                        Tamaño: ${fileSize} KB
                    </div>
                </div>
                <div class="file-info-success mt-2">
                    <i class="fas fa-check-circle mr-1"></i>
                    Formato detectado: ${validationResult.subtype ? validationResult.subtype.toUpperCase() : 'Desconocido'} (archivo con extensión .${originalExtension})
                </div>
                ${extensionNote}
            </div>
        `;

            fileInfoContainer.innerHTML = fileInfoHTML;
        }

        // Función para crear vista previa dentro de la dropzone
        function createPreview(file, validationResult) {
            // Mostrar el contenedor de vista previa
            previewContainer.classList.add('active');

            // Agregar clase para indicar que hay preview
            dropzone.classList.add('has-preview');

            const div = document.createElement('div');
            div.className = 'preview-item';

            if (validationResult.type === 'embroidery') {
                // Icono para archivos de bordado
                div.innerHTML = `
                    <div class="embroidery-preview">
                        <i class="fas fa-vest"></i>
                        <div style="font-size: 16px; margin-top: 10px;">${validationResult.subtype.toUpperCase()}</div>
                        <div style="font-size: 14px; opacity: 0.9; margin-top: 5px;">${file.name}</div>
                    </div>
                `;
            } else if (validationResult.type === 'vector') {
                // Vista previa para SVG
                currentObjectURL = URL.createObjectURL(file);
                div.innerHTML = `<img src="${currentObjectURL}" alt="Vista previa SVG">`;
                div.classList.add('svg-preview');
            } else {
                // Intentar crear vista previa para imágenes
                currentObjectURL = URL.createObjectURL(file);
                const img = document.createElement('img');
                img.src = currentObjectURL;
                img.alt = "Vista previa";
                img.onload = () => {
                    // Imagen cargada correctamente
                };
                img.onerror = () => {
                    // Si falla la carga (ej: AVIF en navegadores antiguos)
                    div.innerHTML = `
                        <div style="text-align: center; color: #666; padding: 20px; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <i class="fas fa-file-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <div style="font-size: 16px;">${validationResult.subtype.toUpperCase()}</div>
                            <div style="font-size: 14px; opacity: 0.8; margin-top: 5px;">Vista previa no disponible</div>
                        </div>
                    `;
                };
                div.appendChild(img);
            }

            // Crear botón de eliminar (tachita) - SEPARADO DEL CONTENIDO DE LA VISTA PREVIA
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'Eliminar imagen';

            // IMPORTANTE: Detener la propagación del evento para que no active el input file
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Detener la propagación
                e.preventDefault(); // Prevenir comportamiento por defecto
                handleRemoveImage();
                return false;
            });

            previewContainer.appendChild(div);
            previewContainer.appendChild(removeBtn);
        }

        // Función para manejar la eliminación de imagen
        function handleRemoveImage() {
            clearPreviousContent();
            input.value = '';

            // Actualizar el estado de cambios del formulario
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

        // Función para manejar clic en dropzone - SOLO cuando NO hay imagen
        function handleDropzoneClick(e) {
            // Si ya hay una imagen, NO hacer nada (solo se elimina con la "x")
            if (dropzone.classList.contains('has-preview')) {
                // Si se hace clic en la tachita, ya se maneja por separado
                if (e.target.closest('.remove-btn')) {
                    return;
                }
                // Si se hace clic en cualquier otra parte de la dropzone, NO abrir selector
                return;
            }

            // Si no hay imagen, abrir selector de archivos
            input.click();
        }

        // Event Listeners
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
                input.files = e.dataTransfer.files;
                const changeEvent = new Event('change');
                input.dispatchEvent(changeEvent);
            }
        });

        // Evento para cambiar el archivo con validación mejorada
        input.addEventListener('change', async function(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            const file = files[0];

            // Limpiar contenido anterior
            clearPreviousContent();

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
            submitBtn.disabled = true;
            backBtn.classList.add('disabled-btn');
            backBtn.style.pointerEvents = 'none';

            try {
                // Validar el archivo
                const validationResult = await fileValidator.validateDesignFile(file);

                if (!validationResult.valid) {
                    showImageError(validationResult.reason);
                    input.value = '';
                    return;
                }

                // Crear vista previa
                createPreview(file, validationResult);

                // Mostrar información del archivo
                showFileInfo(file, validationResult);

                // Marcar que el formulario ha cambiado
                formChanged = true;

            } catch (error) {
                console.error('Error en validación:', error);
                showImageError('Error inesperado al validar el archivo. Intenta con otro archivo.');
                input.value = '';
            } finally {
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar';
                submitBtn.disabled = false;
                backBtn.classList.remove('disabled-btn');
                backBtn.style.pointerEvents = 'auto';
            }
        });

        // Detectar cambios en el formulario
        form.querySelectorAll('input, textarea, select').forEach(el => {
            // No incluir el input de archivo (ya lo manejamos por separado)
            if (el.type !== 'file') {
                el.addEventListener('change', () => {
                    formChanged = true;
                });
                el.addEventListener('input', () => {
                    formChanged = true;
                });
            }
        });

        // Manejar el evento beforeunload (para evitar el mensaje cuando el formulario se está enviando)
        window.addEventListener('beforeunload', e => {
            if (formChanged && !isFormSubmitting) {
                e.preventDefault();
                e.returnValue = '';
                return '¿Deseas abandonar el sitio?\n\nEs posible que los cambios que implementaste no se puedan guardar.\n\n';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar';
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
            document.getElementById('loadingTitle').textContent = 'Actualizando diseño';
            document.getElementById('loadingSubtitle').textContent = 'Procesando tu solicitud, por favor espera...';
            document.getElementById('progressStatus').textContent = 'Validando datos...';

            // Restablecer color del texto del estado a su valor por defecto
            document.getElementById('progressStatus').style.color = '#6b7280';

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
         * Variable para controlar el intervalo de simulación
         */
        let progressInterval = null;
        let currentProgress = 0;

        /**
         * Simula el progreso de carga hasta un límite (para AJAX)
         * @param {number} maxProgress - Progreso máximo a simular (default 90)
         */
        function startProgressSimulation(maxProgress = 90) {
            currentProgress = 0;
            const statusMessages = [
                'Validando datos...',
                'Procesando imagen...',
                'Guardando en servidor...',
                'Optimizando recursos...',
                'Esperando respuesta...'
            ];

            // Limpiar intervalo anterior si existe
            if (progressInterval) {
                clearInterval(progressInterval);
            }

            // Iniciar simulación
            progressInterval = setInterval(() => {
                // Incremento más lento al acercarse al límite
                const increment = currentProgress < 50 ? 2 : (currentProgress < 70 ? 1 : 0.5);
                currentProgress += increment;

                // Limitar al máximo especificado
                if (currentProgress >= maxProgress) {
                    currentProgress = maxProgress;
                    clearInterval(progressInterval);
                    progressInterval = null;
                }

                // Cambiar mensaje según progreso
                let statusIndex = 0;
                if (currentProgress >= 20) statusIndex = 1;
                if (currentProgress >= 40) statusIndex = 2;
                if (currentProgress >= 60) statusIndex = 3;
                if (currentProgress >= 80) statusIndex = 4;

                updateProgress(currentProgress, statusMessages[statusIndex]);
            }, 50);
        }

        /**
         * Completa el progreso al 100% después de recibir respuesta exitosa
         */
        function completeProgress(redirectUrl) {
            // Detener simulación si está corriendo
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }

            // Completar rápidamente hasta 100%
            const completeInterval = setInterval(() => {
                currentProgress += 3;

                if (currentProgress >= 100) {
                    currentProgress = 100;
                    clearInterval(completeInterval);

                    updateProgress(100, '¡Completado!');

                    // Cambiar a estado completado
                    document.getElementById('loadingTitle').textContent = '¡Diseño Actualizado!';
                    document.getElementById('loadingSubtitle').textContent = 'Redirigiendo...';

                    // Agregar clase de completado
                    const progressBar = document.getElementById('progressBar');
                    if (progressBar) {
                        progressBar.classList.add('progress-complete');
                    }

                    // Redirigir después de mostrar el estado completado
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 800);
                } else {
                    updateProgress(currentProgress, 'Finalizando...');
                }
            }, 20);
        }

        // Función para mostrar errores en los campos del formulario
        function showFieldErrors(errors) {
            // Limpiar errores anteriores
            document.querySelectorAll('.text-danger.small').forEach(el => {
                if (el.id && el.id.endsWith('-error')) {
                    el.style.display = 'none';
                    el.innerHTML = '';
                }
            });

            // Remover clases de error de los inputs
            document.querySelectorAll('.input.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            // Mostrar nuevos errores
            Object.keys(errors).forEach(fieldName => {
                const errorElement = document.getElementById(fieldName + '-error');
                if (errorElement) {
                    errorElement.innerHTML =
                        `<i class="fas fa-exclamation-triangle mr-1"></i>${errors[fieldName][0]}`;
                    errorElement.style.display = 'block';

                    // Agregar clase de error al input correspondiente
                    const inputElement = form.querySelector(`[name="${fieldName}"]`);
                    if (inputElement) {
                        inputElement.classList.add('is-invalid');
                    }
                }

                // Manejar el campo de imagen de manera especial
                if (fieldName === 'image') {
                    showImageError(errors[fieldName][0]);
                }
            });
        }

        // Validación del formulario con AJAX y spinner premium
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            isFormSubmitting = true;
            formChanged = false; // Evitar mensaje de beforeunload

            // 1. Deshabilitar botones y mostrar spinner
            disableAllButtons();

            // 2. Iniciar simulación de progreso (hasta 90%)
            startProgressSimulation(90);

            // 3. Preparar datos del formulario
            const formData = new FormData(form);

            // 4. Enviar por AJAX
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Éxito - completar progreso y redirigir
                    completeProgress(data.redirect || '{{ route('admin.designs.index') }}');
                } else {
                    // Error de validación o del servidor
                    if (progressInterval) {
                        clearInterval(progressInterval);
                        progressInterval = null;
                    }

                    showProgressError(data.message || 'Error al actualizar el diseño');

                    // Mostrar errores de validación si existen
                    if (data.errors) {
                        setTimeout(() => {
                            hideLoadingState();
                            enableAllButtons();
                            isSubmitting = false;
                            isFormSubmitting = false;

                            // Mostrar errores en los campos del formulario
                            showFieldErrors(data.errors);

                            // Mostrar SweetAlert con los errores
                            const errorMessages = Object.values(data.errors).flat().join('<br>');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de validación',
                                html: errorMessages,
                                confirmButtonColor: '#2563eb'
                            });
                        }, 2000);
                    } else {
                        // Si no hay errores específicos, solo ocultar después de 3 segundos
                        setTimeout(() => {
                            hideLoadingState();
                            enableAllButtons();
                            isSubmitting = false;
                            isFormSubmitting = false;
                        }, 3000);
                    }
                }
            } catch (error) {
                console.error('Error en la solicitud:', error);

                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }

                showProgressError('Error de conexión. Intenta de nuevo.');

                setTimeout(() => {
                    hideLoadingState();
                    enableAllButtons();
                    isSubmitting = false;
                    isFormSubmitting = false;
                }, 3000);
            }
        });

        // Manejar el botón de regresar para cancelar operación si está en progreso
        backBtn.addEventListener('click', function(e) {
            if (isSubmitting) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Cancelar operación?',
                    text: 'El diseño se está actualizando. ¿Estás seguro de que quieres cancelar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'Continuar actualizando'
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

        // Restaurar botones y ocultar spinner si hay un error de validación del servidor
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Si la página se carga desde caché (error de validación), ocultar spinner
                hideLoadingState();
                enableAllButtons();
                isSubmitting = false;
                isFormSubmitting = false;
            }
        });

        // También ocultar spinner cuando se cargue la página normalmente
        window.addEventListener('load', function() {
            // Pequeño delay para asegurar que todo esté cargado
            setTimeout(() => {
                hideLoadingState();
                enableAllButtons();
                isSubmitting = false;
                isFormSubmitting = false;
            }, 500);
        });

        $('.btn-delete-variant').on('click', function() {
            const url = $(this).data('url');

            Swal.fire({
                title: '¿Eliminar variante?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6e7881',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    popup: 'premium-swal-popup',
                    confirmButton: 'premium-swal-confirm',
                    cancelButton: 'premium-swal-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoadingState();
                    document.getElementById('loadingTitle').textContent = "Eliminando variante";
                    document.getElementById('loadingSubtitle').textContent = "Esto tomará solo un segundo";
                    document.getElementById('progressStatus').textContent = "Procesando...";

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            // Simular progreso de eliminación
                            let progress = 0;
                            const deleteInterval = setInterval(() => {
                                progress += 2;
                                updateProgress(progress, `Eliminando... ${progress}%`);

                                if (progress >= 100) {
                                    clearInterval(deleteInterval);
                                    updateProgress(100, "¡Completado!");

                                    setTimeout(() => {
                                        hideLoadingState();
                                        window.location.reload();
                                    }, 500);
                                }
                            }, 30);
                        },
                        error: function(xhr) {
                            hideLoadingState();
                            Swal.fire({
                                icon: 'error',
                                title: '¡Error!',
                                text: xhr.responseJSON?.message ||
                                    'No se pudo eliminar la variante.',
                                confirmButtonColor: '#2563eb'
                            });
                        }
                    });
                }
            });
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
