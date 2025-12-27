@extends('adminlte::page')

@section('title', 'Diseños')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="text-xl font-weight-semibold">Diseños</h1>

        {{-- BOTÓN NUEVO DISEÑO --}}
        <a href="{{ route('admin.designs.create') }}" class="btn btn-primary">
            + Nuevo diseño
        </a>
    </div>
@stop

@section('content')

    <div class="row">

        {{-- SIDEBAR --}}
        <div class="col-lg-3">

            <div class="surface mb-4">
                <form method="GET">
                    {{-- Contenedor relativo para agrupar todo --}}
                    <div class="position-relative d-flex align-items-center">

                        {{-- LUPA DENTRO (IZQUIERDA) --}}
                        <button type="submit" class="btn btn-link p-0 text-primary position-absolute"
                            style="left: 12px; line-height: 1; z-index: 10;">
                            <i class="fas fa-search"></i>
                        </button>

                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar diseño…"
                            class="search-input w-100" style="padding-left: 40px; padding-right: 40px;">

                        {{-- "X" DENTRO (DERECHA) --}}
                        @if (request('search'))
                            <a href="{{ route('admin.designs.index', request()->except('search')) }}"
                                class="search-clear m-0" style="right: 12px; position: absolute; line-height: 1;">×</a>
                        @endif

                    </div>
                </form>
            </div>


            {{-- CATEGORIES --}}
            <div class="surface">
                <div class="sidebar-title mb-2">Categorías</div>

                <div class="category-list">

                    <a href="{{ route('admin.designs.index', request()->except('category')) }}"
                        class="category-item {{ !$activeCategory ? 'active' : '' }}">
                        <span>Todas</span>
                        <span class="category-count">{{ $designs->total() }}</span>
                    </a>

                    @foreach ($categories as $category)
                        <a href="{{ route('admin.designs.index', ['category' => $category->slug] + request()->except('category')) }}"
                            class="category-item {{ optional($activeCategory)->id === $category->id ? 'active' : '' }}">
                            <span>{{ $category->name }}</span>
                            <span class="category-count">
                                {{ $categoryCounts[$category->id] ?? 0 }}
                            </span>
                        </a>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- GRID --}}
        <div class="col-lg-9">

            @if ($designs->count() > 0)
                <div class="design-grid">

                    @foreach ($designs as $design)
                        <div class="design-card" data-design-id="{{ $design->id }}"
                            data-description="{{ $design->description ?? 'Sin descripción' }}"
                            data-name="{{ ucfirst($design->name) }}" data-variants="{{ $design->variants->count() }}"
                            data-exports="{{ $design->exports_count ?? 0 }}"
                            data-image="{{ $design->primaryImage ? asset('storage/' . $design->primaryImage->file_path) : '' }}"
                            data-edit-url="{{ route('admin.designs.edit', $design) }}"
                            data-delete-url="{{ route('admin.designs.destroy', $design) }}">

                            <div class="design-image">
                                @if ($design->primaryImage)
                                    <img src="{{ asset('storage/' . $design->primaryImage->file_path) }}"
                                        alt="{{ $design->name }}"
                                        onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="no-image" style="display: none;">
                                        <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                                        <span>Sin imagen</span>
                                    </div>
                                @else
                                    <div class="no-image">
                                        <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                                        <span>Sin imagen</span>
                                    </div>
                                @endif
                            </div>

                            <div class="design-body text-center">
                                <h6 class="design-title">{{ ucfirst($design->name) }}</h6>
                                <div class="design-variants">
                                    {{ $design->variants->count() }}
                                    variante{{ $design->variants->count() != 1 ? 's' : '' }}
                                </div>
                                {{-- Contador de exportaciones con clase para actualización --}}
                                <div class="design-exports" data-design-id="{{ $design->id }}">
                                    <i class="fas fa-industry"></i>
                                    <span class="exports-number">{{ $design->exports_count ?? 0 }}</span>
                                    <span
                                        class="exports-text">exportación{{ ($design->exports_count ?? 0) != 1 ? 'es' : '' }}</span>
                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>

                <div class="mt-4">

                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No se encontraron diseños con los filtros aplicados.
                    <a href="{{ route('admin.designs.create') }}" class="alert-link">¿Deseas crear uno?</a>
                </div>
            @endif

        </div>
    </div>

    {{-- MODAL PRINCIPAL DEL DISEÑO - CON data-backdrop="static" --}}
    <div class="modal fade" id="designModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content modal-premium">

                {{-- Botón Cerrar Flotante --}}
                <div style="position: absolute; top: 15px; right: 15px; z-index: 1060;">
                    <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body p-0 position-relative">
                    <div class="modal-content-wrapper shadow-lg" style="border-radius: 30px; overflow: hidden;">
                        <div class="row no-gutters modal-row-responsive">

                            {{-- Campos ocultos para IDs (usados por el script de producción) --}}
                            <input type="hidden" id="modalDesignId" value="">
                            <input type="hidden" id="modalVariantId" value="">

                            {{-- ============================================
                            COLUMNA IZQUIERDA: IMAGEN PRINCIPAL
                            Apple HIG: Deferencia - El contenido es protagonista
                            ============================================ --}}
                            <div class="col-lg-6 modal-left-column">

                                {{-- Loader --}}
                                <div id="modalLoader" class="modal-loader-overlay">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                </div>

                                {{-- ÁREA DE IMAGEN PRINCIPAL (Protagonista) --}}
                                <div class="main-image-wrapper">
                                    {{-- Botón de descarga --}}
                                    <a id="downloadImageBtn" href="#" class="btn-download-floating" download>
                                        <i class="fas fa-download"></i>
                                    </a>

                                    {{-- Thumbnail para volver --}}
                                    <button type="button" id="btnBackToMainImage" onclick="backToMainImage()"
                                        class="btn-back-thumbnail" title="Volver a imagen principal">
                                        <img id="mainImageThumbnail" src="" alt="Principal">
                                        <i class="fas fa-home"></i>
                                    </button>

                                    {{-- Imagen --}}
                                    <img id="mainDisplayImage" src="" alt="Diseño">

                                    {{-- Estado vacío --}}
                                    <div id="noImageLabel" class="empty-image-state">
                                        <i class="fas fa-image"></i>
                                        <span>Sin Imagen</span>
                                    </div>
                                </div>

                                {{-- INFO DEL DISEÑO (debajo de imagen - Jerarquía F) --}}
                                <div class="design-info-section">
                                    {{-- Descripción --}}
                                    <p id="modalDesignDesc" class="design-description">Sin descripción</p>

                                    {{-- Título --}}
                                    <h2 id="modalDesignTitle" class="design-title">Nombre del Diseño</h2>


                                    {{-- Navegación de fotos --}}
                                    <div class="photo-navigation">
                                        <button type="button" class="btn-nav-arrow" onclick="navigateGalleryImage(-1)">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <span id="imageDescription" class="photo-counter">Imagen principal</span>
                                        <button type="button" class="btn-nav-arrow" onclick="navigateGalleryImage(1)">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- ACCIONES (Fitts's Law: Botones grandes y accesibles) --}}
                                <div class="design-actions">
                                    <a href="#" id="btnEditDesign" class="btn-action-primary">
                                        <i class="fas fa-pencil-alt"></i>
                                        Editar Diseño
                                    </a>
                                    <button type="button" id="btnDeleteDesign" class="btn-action-danger">
                                        <i class="fas fa-trash-alt"></i>
                                        Eliminar
                                    </button>
                                </div>

                            </div>

                            {{-- ============================================
                            COLUMNA DERECHA: VARIANTES Y PRODUCCIÓN
                            ============================================ --}}
                            <div class="col-lg-6 modal-right-column">

                                {{-- TABS PARA VARIANTES Y PRODUCCIÓN --}}
                                <div class="modal-tabs-container mb-3">
                                    <ul class="nav nav-tabs" id="designModalTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" id="variants-tab" data-toggle="tab"
                                                href="#variants-content" role="tab">
                                                <i class="fas fa-layer-group mr-1"></i> Variantes
                                                <span id="variantsTotalCount" class="badge badge-light ml-1">0</span>
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" id="production-tab" data-toggle="tab"
                                                href="#production-content" role="tab">
                                                <i class="fas fa-industry mr-1"></i> Producción
                                                <span id="productionTotalCount" class="badge badge-light ml-1">0</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                {{-- CONTENIDO DE LOS TABS --}}
                                <div class="tab-content tab-content-flex">
                                    {{-- PESTAÑA DE VARIANTES (CONTENIDO EXISTENTE) --}}
                                    <div class="tab-pane fade show active" id="variants-content" role="tabpanel">
                                        {{-- SECCIÓN VARIANTES --}}
                                        <div class="variants-section">
                                            <div class="section-header">
                                                <div class="variant-nav-arrows">
                                                    <button type="button" class="btn-nav-sm"
                                                        onclick="navigateVariant(-1)" title="Variante anterior">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                    <button type="button" class="btn-nav-sm"
                                                        onclick="navigateVariant(1)" title="Variante siguiente">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </div>
                                                <h5 class="section-title">Variantes
                                                    <span id="variantsContentCount" class="variants-total-badge">0</span>
                                                </h5>
                                            </div>

                                            {{-- Tabs de Variantes --}}
                                            <div class="variant-tabs-wrapper">
                                                <div id="variantTabs" class="variant-tabs-scroll">
                                                    {{-- Se llena dinámicamente --}}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- INFO VARIANTE SELECCIONADA --}}
                                        <div class="variant-selected-info">
                                            <div class="variant-name-price">
                                                <h6 id="variantName">Selecciona una variante</h6>
                                                <span id="variantPrice" class="price-badge">$0.00</span>
                                            </div>
                                            <span id="variantSku" class="sku-text" hidden>SKU: ---</span>
                                        </div>

                                        {{-- GALERÍA --}}
                                        <div class="gallery-section">
                                            <div class="gallery-header">
                                                <span class="gallery-label">Galería</span>
                                                <span id="galleryCount" class="gallery-badge">0</span>
                                            </div>

                                            <div class="gallery-grid-container">
                                                <div id="variantGallery" class="gallery-grid">
                                                    {{-- Estado vacío inicial --}}
                                                    <div class="gallery-empty-state">
                                                        <i class="fas fa-images"></i>
                                                        <p>No hay imágenes.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ACCIONES VARIANTES --}}
                                        <div class="variant-actions">
                                            <a href="#" id="btnAddVariant" class="btn-add-variant">
                                                <i class="fas fa-plus"></i>
                                                Nueva Variante
                                            </a>
                                            <a href="#" id="btnEditVariants" class="btn-edit-variant disabled"
                                                style="pointer-events: none; opacity: 0.5;">
                                                <i class="fas fa-pencil-alt"></i>
                                                Editar Variante
                                            </a>
                                        </div>
                                    </div>

                                    {{-- PESTAÑA DE PRODUCCIÓN (NUEVA) --}}
                                    <div class="tab-pane fade" id="production-content" role="tabpanel">
                                        @include('admin.designs.partials.production-tab')
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>

                {{-- OVERLAY DE CARGA PREMIUM --}}
                <div class="modal-loading-overlay" id="loadingOverlay">
                    <div class="modal-loading-content">
                        <div class="modal-loading-spinner"></div>
                        <h3 class="modal-loading-title">Eliminando diseño</h3>
                        <p class="modal-loading-subtitle">
                            Por favor espera mientras procesamos tu solicitud.<br>
                            Esto puede tardar unos segundos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMACIÓN ELIMINAR DISEÑO --}}
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content modal-delete-apple">

                {{-- Botón Cerrar Flotante --}}
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
                    ¿Deseas eliminar este diseño?
                </h3>

                {{-- Mensaje de variantes (condicional) --}}
                <div id="variantsWarning" class="variants-warning" style="display: none;">
                    <p class="variants-message">
                        Este diseño tiene <strong id="variantsCount">0</strong> variante(s) y se eliminarán de igual
                        manera.
                    </p>
                </div>

                {{-- Descripción --}}
                <p class="modal-delete-description">
                    Esta acción no se puede deshacer. Todos los datos asociados se perderán permanentemente.
                </p>

                {{-- Botones de acción --}}
                <div class="modal-delete-actions">
                    <button type="button" class="btn-cancel-apple" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn-delete-apple" id="confirmDeleteBtn">
                        Eliminar diseño
                    </button>
                </div>

            </div>
        </div>
    </div>

@stop

@section('css')
    <style>
        /* ============================================
                                        PLACEHOLDERS Y UTILIDADES
                                        ============================================ */
        .no-img-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 4px;
            font-size: 14px;
        }

        /* ============================================
                                        MODAL PREMIUM - ESTRUCTURA BASE
                                        ============================================ */
        .modal-premium {
            border-radius: 30px;
            border: none;
            overflow: visible;
            box-shadow: none;
            background: transparent;
        }

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
                                        APPLE HIG MODAL - SISTEMA DE DISEÑO PREMIUM
                                        8pt Grid System | Claridad | Deferencia | Profundidad
                                        ============================================ */

        /* COLUMNA IZQUIERDA */
        .modal-left-column {
            display: flex;
            flex-direction: column;
            background: #ffffff;
            padding: 24px;
            position: relative;
            border-right: 1px solid #f1f5f9;
        }

        /* COLUMNA DERECHA - CON PADDING CORRECTO */
        .modal-right-column {
            display: flex;
            flex-direction: column;
            background: #f9fafb;
            overflow: hidden;
            padding: 24px;
            max-height: 600px;
        }

        .modal-loader-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        /* IMAGEN PRINCIPAL - Protagonista (Deferencia) */
        .main-image-wrapper {
            position: relative;
            width: 100%;
            height: 340px;
            background: #fafafa;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .main-image-wrapper>img#mainDisplayImage {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
        }

        /* Botón Descarga - Flotante */
        .btn-download-floating {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            z-index: 5;
        }

        .btn-download-floating:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            text-decoration: none;
        }

        /* Thumbnail Volver */
        .btn-back-thumbnail {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 2px solid #2563eb;
            background: white;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            transition: all 0.2s ease;
            display: none;
            z-index: 5;
        }

        .btn-back-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-back-thumbnail i {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background: #2563eb;
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .btn-back-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }

        /* Estado Vacío de Imagen */
        .empty-image-state {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }

        .empty-image-state i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-image-state span {
            font-size: 14px;
            font-weight: 500;
        }

        /* INFO DEL DISEÑO */
        .design-info-section {
            text-align: center;
            padding: 16px 0 8px;
        }

        .design-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }

        .design-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0 0 16px 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Navegación de Fotos */
        .photo-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .photo-counter {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            min-width: 120px;
            text-align: center;
        }

        .btn-nav-arrow {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-nav-arrow:hover {
            background: #e5e7eb;
            color: #374151;
            border-color: #d1d5db;
        }

        .btn-nav-arrow:active {
            background: #d1d5db;
            transform: scale(0.95);
        }

        /* ============================================
                                        BOTONES DE ACCIÓN - SISTEMA UNIFICADO PREMIUM
                                        Ambas columnas usan el mismo sistema
                                        ============================================ */

        /* Contenedor de acciones - BASE COMÚN */
        .design-actions,
        .variant-actions {
            display: flex;
            gap: 12px;
            padding: 16px 0 0 0;
            margin-top: auto;
            border-top: 1px solid #e5e7eb;
        }

        /* Botón Primario - Editar Diseño (Negro) */
        .btn-action-primary {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            min-height: 52px;
            box-shadow: 0 2px 8px rgba(17, 24, 39, 0.2);
        }

        .btn-action-primary:hover {
            background: linear-gradient(180deg, #1f2937 0%, #374151 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
        }

        /* Botón Peligro - Eliminar (Rojo outline) */
        .btn-action-danger {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: white;
            color: #dc2626;
            border: 2px solid #fecaca;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 52px;
        }

        .btn-action-danger:hover {
            background: #fef2f2;
            border-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-action-danger:active {
            transform: translateY(0);
            background: #fee2e2;
        }

        /* Botón Azul - Nueva Variante */
        .btn-add-variant {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
            min-height: 52px;
        }

        .btn-add-variant:hover {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e40af 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        /* Botón Negro - Editar Variante */
        .btn-edit-variant {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(17, 24, 39, 0.25);
            min-height: 52px;
        }

        .btn-edit-variant:hover:not(.disabled) {
            background: linear-gradient(180deg, #1f2937 0%, #374151 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 24, 39, 0.35);
        }

        .btn-edit-variant.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* ============================================
                                        COLUMNA DERECHA - VARIANTES Y PRODUCCIÓN
                                        ============================================ */

        /* Tabs del Modal */
        .modal-tabs-container {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 15px;
            flex-shrink: 0;
        }

        .modal-tabs-container .nav-tabs {
            border-bottom: none;
        }

        .modal-tabs-container .nav-tabs .nav-item {
            margin-bottom: -1px;
        }

        .modal-tabs-container .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 15px;
            transition: all 0.2s;
        }

        .modal-tabs-container .nav-tabs .nav-link:hover {
            color: #2563eb;
            border-bottom-color: #d1d5db;
        }

        .modal-tabs-container .nav-tabs .nav-link.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            background: transparent;
        }

        /* Contenedor de tabs con flexbox */
        .tab-content-flex {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }

        .tab-content-flex>.tab-pane {
            flex: 1;
            display: none !important;
            flex-direction: column;
            min-height: 0;
            overflow-y: auto;
        }

        .tab-content-flex>.tab-pane.active.show {
            display: flex !important;
        }

        /* Pestaña Variantes */
        #variants-content {
            flex: 1;
            display: flex !important;
            flex-direction: column;
        }

        #variants-content:not(.active) {
            display: none !important;
        }

        /* Pestaña Producción */
        #production-content {
            flex: 1;
            padding: 0;
        }

        #production-content:not(.active) {
            display: none !important;
        }

        #production-content.active.show {
            display: flex !important;
            flex-direction: column;
        }

        /* Sección Variantes */
        .variants-section {
            padding: 0 0 12px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            position: relative;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Badge para el total de variantes */
        .variants-total-badge {
            background: #6b7280;
            color: white;
            font-size: 14px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        .variant-nav-arrows {
            display: flex;
            gap: 6px;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        .btn-nav-sm {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-nav-sm:hover {
            background: #e5e7eb;
            color: #374151;
        }

        /* Tabs de Variantes */
        .variant-tabs-wrapper {
            overflow: hidden;
        }

        .variant-tabs-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 0;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .variant-tabs-scroll::-webkit-scrollbar {
            display: none;
        }

        .variant-tab {
            flex-shrink: 0;
            width: 72px;
            padding: 8px;
            background: #f9fafb;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }

        .variant-tab:hover {
            background: #f3f4f6;
        }

        .variant-tab.active {
            background: white;
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
        }

        .variant-tab img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 4px;
        }

        .variant-tab span {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        /* Info Variante Seleccionada */
        .variant-selected-info {
            padding: 12px 0;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .variant-name-price {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .variant-name-price h6 {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .price-badge {
            font-size: 14px;
            font-weight: 700;
            color: #059669;
            background: #d1fae5;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .sku-text {
            font-size: 11px;
            color: #9ca3af;
            font-family: 'SF Mono', monospace;
            letter-spacing: 0.3px;
        }

        /* GALERÍA */
        .gallery-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 12px 0 0;
            min-height: 120px;
            overflow: hidden;
        }

        .gallery-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .gallery-label {
            font-size: 14px;
            font-weight: 700;
            color: grey;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gallery-badge {
            width: 24px;
            height: 24px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* GRID DE GALERÍA - ALTURA FIJA */
        .gallery-grid-container {
            flex: 1;
            width: 100%;
            min-height: 80px;
            max-height: 150px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 4px;
        }

        /* Cuando la galería está vacía, centrar contenido */
        .gallery-grid-container:has(.gallery-empty-state) {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Personalización del scrollbar para que sea sutil (estilo Apple) */
        .gallery-grid-container::-webkit-scrollbar {
            width: 6px;
        }

        .gallery-grid-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .gallery-grid-container::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .gallery-grid-container::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            width: 100%;
        }

        /* Cuando la galería está vacía, centrar contenido */
        .gallery-grid:has(.gallery-empty-state) {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
        }

        .gallery-item {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            padding: 2px;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover {
            border-color: #93c5fd;
            transform: scale(1.02);
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-item.active {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .gallery-item.active::after {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        /* Estado Vacío Galería - Diseño Premium */
        .gallery-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 80px;
            padding: 16px;
            color: #9ca3af;
            text-align: center;
        }

        .gallery-empty-state i {
            font-size: 28px;
            margin-bottom: 8px;
            opacity: 0.4;
            color: #cbd5e1;
        }

        .gallery-empty-state p {
            font-size: 12px;
            font-weight: 500;
            margin: 0;
            color: #9ca3af;
        }

        /* ============================================
                                        ESTILOS DEL GRID DE TARJETAS
                                        ============================================ */
        .surface {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, .04);
        }

        .search-input {
            width: 100%;
            padding: 14px 40px 14px 16px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .category-list {
            max-height: 420px;
            overflow-y: auto;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s;
        }

        .category-item:hover {
            background: #f3f4f6;
            text-decoration: none;
        }

        .category-item.active {
            background: #2563eb;
            color: white;
        }

        .design-card {
            border-radius: 18px;
            border: 1px solid #a8a7a7;
            overflow: hidden;
            cursor: pointer;
            transition: all .25s;
            background: #fff;
        }

        .design-card:hover {
            border: 1px solid #1e5de6;
            box-shadow: 0 18px 40px rgba(37, 99, 235, .15);
            transform: translateY(-6px);
        }

        .design-image {
            height: 180px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
        }

        .design-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .no-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .no-image i {
            margin-bottom: 8px;
        }

        .design-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .design-body {
            padding: 16px;
        }

        .design-body .design-title {
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .design-variants {
            font-size: 13px;
            color: #6b7280;
        }

        /* Badge de exportaciones en tarjeta - MEJORADO */
        .design-exports {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
            padding: 6px 12px;
            background: #f3f4f6;
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .design-exports i {
            font-size: 11px;
            color: #2563eb;
        }

        .design-card:hover .design-exports {
            background: #e0e7ff;
        }

        /* Animación de actualización */
        .design-exports.updated {
            animation: pulse-exports 0.4s ease;
        }

        @keyframes pulse-exports {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
                background: #dbeafe;
            }

            100% {
                transform: scale(1);
            }
        }

        /* ============================================
                                        MODAL DE CONFIRMACIÓN ELIMINAR
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

        .variants-warning {
            background: #fef3c7;
            border: 1.5px solid #fde68a;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .variants-message {
            margin: 0;
            font-size: 0.95rem;
            color: #92400e;
            text-align: center;
            line-height: 1.5;
        }

        .variants-message strong {
            color: #78350f;
            font-weight: 700;
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

        /* ============================================
                                        ESTADOS DE CARGA
                                        ============================================ */
        .modal-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 30px;
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
        }

        .modal-loading-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            line-height: 1.5;
        }

        /* ============================================
                                        RESPONSIVE
                                        ============================================ */
        @media (max-width: 991px) {
            .modal-left-column {
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
            }

            .modal-right-column {
                padding: 20px;
                max-height: none;
            }

            .main-image-wrapper {
                height: 280px;
            }
        }

        @media (max-width: 768px) {
            .modal-dialog.modal-xl {
                max-width: 95%;
                margin: 10px auto;
            }

            .main-image-wrapper {
                height: 240px;
            }

            .design-actions,
            .variant-actions {
                flex-direction: row;
                gap: 10px;
            }

            .btn-action-primary,
            .btn-action-danger,
            .btn-add-variant,
            .btn-edit-variant {
                padding: 12px 14px;
                font-size: 13px;
                min-height: 48px;
            }
        }

        @media (max-width: 576px) {
            .modal-dialog.modal-xl {
                max-width: 100%;
                margin: 5px;
            }

            .modal-left-column,
            .modal-right-column {
                padding: 16px;
            }

            .main-image-wrapper {
                height: 200px;
            }

            .design-actions,
            .variant-actions {
                flex-direction: column;
                gap: 8px;
            }

            .btn-action-primary,
            .btn-action-danger,
            .btn-add-variant,
            .btn-edit-variant {
                width: 100%;
                flex: none;
            }
        }
    </style>
@stop
@section('js')
    <script>
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let currentDesign = null;
        let currentVariants = [];
        let currentVariantIndex = -1;
        let currentGalleryImages = [];
        let currentGalleryIndex = 0;
        let pendingDeleteUrl = null;
        let isProcessingDelete = false;
        let isViewingDesignImage = true;

        // ============================================
        // INICIALIZACIÓN: EVENT LISTENERS EN CARDS
        // ============================================
        document.querySelectorAll('.design-card').forEach(card => {
            card.addEventListener('click', function() {
                if (isProcessingDelete) return;

                const designId = this.dataset.designId;
                const showUrl = `/admin/designs/${designId}`;

                // Mostrar modal y loader
                $('#designModal').modal('show');
                const loader = document.getElementById('modalLoader');
                if (loader) {
                    loader.style.setProperty('display', 'flex', 'important');
                    loader.style.opacity = '1';
                }

                // Limpiar vista anterior  
                clearModal();

                // Fetch diseño completo
                fetch(showUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderModal(data.design);
                        } else {
                            showErrorAlert('Error', 'No se pudo cargar el diseño');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorAlert('Error de conexión',
                            'Por favor verifica tu conexión a internet');
                    })
                    .finally(() => {
                        const loaderEl = document.getElementById('modalLoader');
                        if (loaderEl) loaderEl.style.setProperty('display', 'none', 'important');
                    });
            });
        });

        // ============================================
        // FUNCIÓN: LIMPIAR MODAL
        // ============================================
        function clearModal() {
            document.getElementById('modalDesignTitle').innerText = '';
            document.getElementById('modalDesignDesc').innerText = '';
            document.getElementById('mainDisplayImage').src = '';
            document.getElementById('mainDisplayImage').style.display = 'none';
            document.getElementById('variantTabs').innerHTML = '';
            document.getElementById('variantName').innerText = 'Selecciona una variante';
            document.getElementById('variantPrice').innerText = '$0.00';
            document.getElementById('variantSku').innerText = 'SKU: ---';
            document.getElementById('galleryCount').innerText = '0';
            document.getElementById('variantsTotalCount').innerText = '0';
            document.getElementById('variantsContentCount').innerText = '0';
            document.getElementById('productionTotalCount').innerText = '0';
            document.getElementById('noImageLabel').style.setProperty('display', 'none', 'important');
            document.getElementById('imageDescription').innerText = 'Imagen Principal del Diseño';

            // Limpiar campos ocultos para pestaña de producción
            document.getElementById('modalDesignId').value = '';
            document.getElementById('modalVariantId').value = '';

            // Ocultar botón de descarga
            document.getElementById('downloadImageBtn').style.display = 'none';

            // Mostrar estado vacío de galería
            document.getElementById('variantGallery').innerHTML =
                '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';

            // Deshabilitar botón editar variante
            const btnEditVariants = document.getElementById('btnEditVariants');
            if (btnEditVariants) {
                btnEditVariants.classList.add('disabled');
                btnEditVariants.style.pointerEvents = 'none';
                btnEditVariants.style.opacity = '0.5';
                btnEditVariants.href = '#';
            }

            // RESETEAR TABS - Siempre volver a pestaña de Variantes
            const variantsTab = document.getElementById('variants-tab');
            const productionTab = document.getElementById('production-tab');
            const variantsContent = document.getElementById('variants-content');
            const productionContent = document.getElementById('production-content');

            if (variantsTab && productionTab && variantsContent && productionContent) {
                variantsTab.classList.add('active');
                productionTab.classList.remove('active');
                variantsContent.classList.add('show', 'active');
                productionContent.classList.remove('show', 'active');
            }

            // Reset índices
            currentGalleryImages = [];
            currentGalleryIndex = 0;
            isViewingDesignImage = true;
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR BOTÓN EDITAR VARIANTE
        // ============================================
        function updateEditVariantsButton() {
            const btnEditVariants = document.getElementById('btnEditVariants');

            if (!btnEditVariants || !currentDesign) return;

            if (!currentVariants.length) {
                btnEditVariants.classList.add('disabled');
                btnEditVariants.style.pointerEvents = 'none';
                btnEditVariants.style.opacity = '0.5';
                btnEditVariants.href = '#';
                return;
            }

            const activeVariant = currentVariants[currentVariantIndex];

            if (activeVariant && activeVariant.id && currentVariantIndex !== -1) {
                btnEditVariants.classList.remove('disabled');
                btnEditVariants.style.pointerEvents = 'auto';
                btnEditVariants.style.opacity = '1';
                btnEditVariants.href =
                    `/admin/designs/${currentDesign.id}/variants/${activeVariant.id}/edit`;
            } else {
                btnEditVariants.classList.add('disabled');
                btnEditVariants.style.pointerEvents = 'none';
                btnEditVariants.style.opacity = '0.5';
                btnEditVariants.href = '#';
            }
        }

        // ============================================
        // FUNCIÓN: MOSTRAR IMAGEN PRINCIPAL
        // ============================================
        function displayMainImage({
            src,
            title,
            subtitle,
            downloadName,
            showBackButton = false
        }) {
            const img = document.getElementById('mainDisplayImage');
            const noImg = document.getElementById('noImageLabel');
            const titleEl = document.getElementById('modalDesignTitle');
            const descEl = document.getElementById('imageDescription');
            const downloadBtn = document.getElementById('downloadImageBtn');
            const backBtn = document.getElementById('btnBackToMainImage');
            const thumbnail = document.getElementById('mainImageThumbnail');

            img.style.display = 'none';
            img.src = '';
            noImg.style.setProperty('display', 'none', 'important');
            downloadBtn.style.display = 'none';

            if (title) titleEl.innerText = title.toUpperCase();
            descEl.innerText = subtitle;

            if (showBackButton && currentDesign?.primaryImage?.file_path) {
                thumbnail.src = `/storage/${currentDesign.primaryImage.file_path}`;
                backBtn.style.display = 'block';
            } else {
                backBtn.style.display = 'none';
            }

            if (!src) {
                noImg.style.setProperty('display', 'flex', 'important');
                return;
            }

            img.src = src;
            img.style.display = 'block';
            noImg.style.setProperty('display', 'none', 'important');

            img.onload = () => {
                noImg.style.setProperty('display', 'none', 'important');
                downloadBtn.style.display = 'flex';
                downloadBtn.href = src;
                downloadBtn.setAttribute('download', downloadName);
            };

            img.onerror = () => {
                img.style.display = 'none';
                noImg.style.setProperty('display', 'flex', 'important');
                downloadBtn.style.display = 'none';
            };
        }

        // ============================================
        // FUNCIÓN: VOLVER A IMAGEN PRINCIPAL
        // ============================================
        function backToMainImage() {
            if (!currentDesign) return;

            isViewingDesignImage = true;
            currentGalleryImages = [];
            currentGalleryIndex = 0;
            currentVariantIndex = -1;

            document.getElementById('modalVariantId').value = '';
            updateProductionContext();

            if (currentDesign.primaryImage?.file_path) {
                const ext = currentDesign.primaryImage.file_path.split('.').pop();
                displayMainImage({
                    src: `/storage/${currentDesign.primaryImage.file_path}`,
                    title: currentDesign.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: `${currentDesign.name.replace(/\s+/g, '_')}.${ext}`,
                    showBackButton: false
                });
            } else {
                displayMainImage({
                    src: null,
                    title: currentDesign.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: null,
                    showBackButton: false
                });
            }

            document.querySelectorAll('.variant-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            const gallery = document.getElementById('variantGallery');
            if (gallery) {
                gallery.innerHTML =
                    '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';
            }
            document.getElementById('galleryCount').innerText = '0';

            updateVariantInfoForDesign();
            updateEditVariantsButton();

            // Si la pestaña de producción está activa, recargar los datos del diseño principal
            if ($('#production-tab').hasClass('active') || $('#production-content').hasClass('active show')) {
                // Disparar evento para que production-tab.blade.php recargue los datos
                if (typeof loadProductionData === 'function') {
                    loadProductionData();
                }
            }
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR CONTEXTO DE PRODUCCIÓN
        // ============================================
        function updateProductionContext() {
            const contextEl = document.getElementById('productionContext');
            if (!contextEl) return;

            const variantId = document.getElementById('modalVariantId').value;

            if (variantId && variantId !== '') {
                const variantName = document.getElementById('variantName').innerText;
                contextEl.innerHTML = 'Variante: <strong>' + variantName + '</strong>';
            } else if (currentDesign) {
                contextEl.innerHTML = 'Diseño: <strong>' + currentDesign.name.toUpperCase() + '</strong>';
            } else {
                contextEl.innerHTML = 'Cargando...';
            }
        }

        // ============================================
        // FUNCIÓN: MOSTRAR IMAGEN DE VARIANTE
        // ============================================
        function showVariantImage(variant, images, index) {
            const img = images[index];
            if (!img || !img.file_path) {
                displayMainImage({
                    src: null,
                    title: variant.name,
                    subtitle: 'Imagen de variante',
                    downloadName: null,
                    showBackButton: true
                });
                return;
            }

            const ext = img.file_path.split('.').pop();
            currentGalleryIndex = index;
            isViewingDesignImage = false;

            displayMainImage({
                src: `/storage/${img.file_path}`,
                title: variant.name,
                subtitle: `Foto ${index + 1} de variante`,
                downloadName: `${variant.name.replace(/\s+/g, '_')}_${index + 1}.${ext}`,
                showBackButton: true
            });

            updateGalleryActiveState(index);
            scrollGalleryItemIntoView(index);
        }

        // ============================================
        // FUNCIÓN: SCROLL A ITEM DE GALERÍA
        // ============================================
        function scrollGalleryItemIntoView(index) {
            const gallery = document.getElementById('variantGallery');
            if (!gallery) return;

            const items = gallery.querySelectorAll('.gallery-item');
            if (items.length <= index) return;

            const item = items[index];
            if (item) {
                item.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR ESTADO ACTIVO EN GALERÍA
        // ============================================
        function updateGalleryActiveState(activeIndex) {
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach((item, idx) => {
                if (idx === activeIndex) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // ============================================
        // FUNCIÓN: RENDERIZAR MODAL COMPLETO
        // ============================================
        function renderModal(design) {
            currentDesign = design;
            currentVariants = design.variants || [];
            currentVariantIndex = -1;
            currentGalleryImages = [];
            currentGalleryIndex = 0;
            isViewingDesignImage = true;

            // Datos del diseño
            document.getElementById('modalDesignTitle').innerText = design.name;
            document.getElementById('modalDesignDesc').innerText = design.description || 'Sin descripción';

            // Campos ocultos para producción
            document.getElementById('modalDesignId').value = design.id;
            document.getElementById('modalVariantId').value = '';

            updateProductionContext();

            // Botones de acción
            document.getElementById('btnEditDesign').href = `/admin/designs/${design.id}/edit`;
            const deleteBtn = document.getElementById('btnDeleteDesign');
            deleteBtn.onclick = () => confirmDeleteDesign(`/admin/designs/${design.id}`);
            document.getElementById('btnAddVariant').href = `/admin/designs/${design.id}/variants/create`;

            // Imagen principal
            if (design.primaryImage?.file_path) {
                const ext = design.primaryImage.file_path.split('.').pop();
                displayMainImage({
                    src: `/storage/${design.primaryImage.file_path}`,
                    title: design.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: `${design.name.replace(/\s+/g, '_')}.${ext}`
                });
            } else {
                displayMainImage({
                    src: null,
                    title: design.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: null
                });
            }

            // Contadores
            const variantsCount = currentVariants.length;
            document.getElementById('variantsTotalCount').innerText = variantsCount;
            document.getElementById('variantsContentCount').innerText = variantsCount;

            const exportsCount = design.exports_count || 0;
            document.getElementById('productionTotalCount').innerText = exportsCount;

            // Renderizar tabs de variantes
            const tabsContainer = document.getElementById('variantTabs');

            if (currentVariants.length > 0) {
                currentVariants.forEach((variant, index) => {
                    let thumbSrc = '';
                    let hasImage = false;

                    if (variant.images && variant.images.length > 0 && variant.images[0].file_path) {
                        thumbSrc = `/storage/${variant.images[0].file_path}`;
                        hasImage = true;
                    } else if (design.primaryImage && design.primaryImage.file_path) {
                        thumbSrc = `/storage/${design.primaryImage.file_path}`;
                        hasImage = true;
                    }

                    const tab = document.createElement('div');
                    tab.className = 'variant-tab';

                    if (hasImage) {
                        tab.innerHTML = `
                            <img src="${thumbSrc}"
                                 onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\\'no-img-placeholder\\'><i class=\\'fas fa-image text-muted\\'></i></div>'">
                            <span>${variant.name.toUpperCase()}</span>
                        `;
                    } else {
                        tab.innerHTML = `
                            <div class="no-img-placeholder">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                            <span>${variant.name.toUpperCase()}</span>
                        `;
                    }

                    tab.onclick = () => selectVariant(index);
                    tabsContainer.appendChild(tab);
                });

                document.getElementById('variantGallery').innerHTML =
                    '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';

                updateVariantInfoForDesign();
            } else {
                tabsContainer.innerHTML =
                    '<div class="empty-state-minimal d-flex flex-column align-items-center justify-content-center text-center w-100" style="min-height: 100px; padding: 10px; color: #94a3b8;">' +
                    '<i class="fas fa-layer-group mb-2" style="font-size: 18px; opacity: 0.5;"></i>' +
                    '<p class="m-0" style="font-size: 13px; font-weight: 400;">Sin variantes. Añade una.</p>' +
                    '</div>';
                document.getElementById('variantGallery').innerHTML =
                    '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';
                document.getElementById('galleryCount').innerText = '0';
                document.getElementById('variantName').innerText = 'Sin variantes';
                document.getElementById('variantPrice').innerText = '$0.00';
                document.getElementById('variantSku').innerText = 'SKU: ---';
                currentGalleryImages = [];
            }

            updateEditVariantsButton();
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR INFO VARIANTE PARA DISEÑO
        // ============================================
        function updateVariantInfoForDesign() {
            document.getElementById('variantName').innerText = 'Diseño Principal';
            document.getElementById('variantPrice').innerText = '$0.00';
            document.getElementById('variantSku').innerText = 'SKU: ---';
            document.getElementById('galleryCount').innerText = '0';
        }

        // ============================================
        // FUNCIÓN: SELECCIONAR VARIANTE
        // ============================================
        function selectVariant(index) {
            if (isProcessingDelete) return;

            const variant = currentVariants[index];
            if (!variant) return;

            currentVariantIndex = index;
            isViewingDesignImage = false;

            // Actualizar UI de Tabs
            const tabs = document.querySelectorAll('.variant-tab');
            tabs.forEach((t, i) => {
                t.classList.toggle('active', i === index);
            });

            if (tabs[index]) {
                tabs[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }

            // Actualizar info de variante
            document.getElementById('variantName').innerText = variant.name.toUpperCase();
            document.getElementById('variantPrice').innerText = variant.price ? `$${parseFloat(variant.price).toFixed(2)}` :
                '$0.00';
            document.getElementById('variantSku').innerText = `SKU: ${variant.sku || '---'}`;

            // Actualizar campo oculto para producción
            document.getElementById('modalVariantId').value = variant.id;
            updateProductionContext();

            // Actualizar galería
            const images = variant.images || [];
            currentGalleryImages = images;
            currentGalleryIndex = 0;
            document.getElementById('galleryCount').innerText = images.length;

            const gallery = document.getElementById('variantGallery');
            gallery.innerHTML = '';

            if (images.length > 0) {
                images.forEach((img, imgIndex) => {
                    if (img && img.file_path) {
                        const item = document.createElement('div');
                        item.className = 'gallery-item';

                        const imgElement = document.createElement('img');
                        imgElement.src = `/storage/${img.file_path}`;
                        imgElement.onerror = function() {
                            this.onerror = null;
                            this.src =
                                'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f1f5f9"/><text x="50%" y="50%" font-family="Arial" font-size="10" fill="%2394a3b8" text-anchor="middle" dy=".3em">Sin Imagen</text></svg>';
                        };

                        item.appendChild(imgElement);
                        item.onclick = () => {
                            showVariantImage(variant, images, imgIndex);
                        };
                        gallery.appendChild(item);
                    }
                });

                showVariantImage(variant, images, 0);
            } else {
                gallery.innerHTML =
                    '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';
                if (currentDesign.primaryImage?.file_path) {
                    const ext = currentDesign.primaryImage.file_path.split('.').pop();
                    displayMainImage({
                        src: `/storage/${currentDesign.primaryImage.file_path}`,
                        title: variant.name,
                        subtitle: 'Imagen de variante',
                        downloadName: `${currentDesign.name.replace(/\s+/g, '_')}.${ext}`
                    });
                } else {
                    displayMainImage({
                        src: null,
                        title: variant.name,
                        subtitle: 'Imagen de variante',
                        downloadName: null
                    });
                }
                currentGalleryImages = [];
            }

            updateEditVariantsButton();
        }

        // ============================================
        // FUNCIÓN: NAVEGAR ENTRE VARIANTES
        // ============================================
        function navigateVariant(direction) {
            if (isProcessingDelete || currentVariants.length === 0) return;

            let newIndex;

            if (currentVariantIndex === -1) {
                newIndex = 0;
            } else {
                newIndex = currentVariantIndex + direction;

                if (newIndex < 0) {
                    newIndex = currentVariants.length - 1;
                } else if (newIndex >= currentVariants.length) {
                    newIndex = 0;
                }
            }

            selectVariant(newIndex);
        }

        // ============================================
        // FUNCIÓN: NAVEGAR ENTRE IMÁGENES
        // ============================================
        function navigateGalleryImage(direction) {
            if (isProcessingDelete) return;
            if (isViewingDesignImage) return;
            if (currentGalleryImages.length <= 1) return;

            let newIndex = currentGalleryIndex + direction;

            if (newIndex < 0) {
                newIndex = currentGalleryImages.length - 1;
            } else if (newIndex >= currentGalleryImages.length) {
                newIndex = 0;
            }

            currentGalleryIndex = newIndex;
            const variant = currentVariants[currentVariantIndex];
            showVariantImage(variant, currentGalleryImages, currentGalleryIndex);
        }

        // ============================================
        // FUNCIONES DE CARGA
        // ============================================
        function showLoadingState() {
            isProcessingDelete = true;
            const loadingOverlay = document.getElementById('loadingOverlay');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }

            if (confirmBtn) {
                confirmBtn.classList.add('loading');
                confirmBtn.disabled = true;
            }
        }

        function hideLoadingState() {
            isProcessingDelete = false;
            const loadingOverlay = document.getElementById('loadingOverlay');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }

            if (confirmBtn) {
                confirmBtn.classList.remove('loading');
                confirmBtn.disabled = false;
            }
        }

        // ============================================
        // FUNCIONES DE ELIMINACIÓN
        // ============================================
        function confirmDeleteDesign(url) {
            if (isProcessingDelete) return;

            pendingDeleteUrl = url;

            const variantsCount = currentVariants ? currentVariants.length : 0;
            const warningDiv = document.getElementById('variantsWarning');
            const countSpan = document.getElementById('variantsCount');

            if (variantsCount > 0) {
                countSpan.textContent = variantsCount;
                warningDiv.style.display = 'block';
            } else {
                warningDiv.style.display = 'none';
            }

            $('#deleteConfirmModal').modal('show');
        }

        function executeDelete() {
            if (!pendingDeleteUrl || isProcessingDelete) return;

            $('#deleteConfirmModal').modal('hide');
            showLoadingState();

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = pendingDeleteUrl;
            form.style.display = 'none';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);

            setTimeout(() => {
                form.submit();
            }, 500);
        }

        function showErrorAlert(title, message) {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                showConfirmButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Entendido'
            });
        }

        // ============================================
        // ACTUALIZACIÓN EN TIEMPO REAL DE EXPORTACIONES
        // ============================================
        function updateExportsCounter(designId) {
            fetch(`/admin/designs/${designId}/exports-count`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const count = data.count || 0;

                        // Actualizar tarjeta en el grid
                        const card = document.querySelector(`.design-card[data-design-id="${designId}"]`);
                        if (card) {
                            card.dataset.exports = count;

                            const exportsDiv = card.querySelector('.design-exports');
                            if (exportsDiv) {
                                exportsDiv.querySelector('.exports-number').textContent = count;
                                exportsDiv.querySelector('.exports-text').textContent =
                                    count !== 1 ? 'exportaciones' : 'exportación';

                                // Animación
                                exportsDiv.classList.add('updated');
                                setTimeout(() => exportsDiv.classList.remove('updated'), 400);
                            }
                        }

                        // Actualizar contador en el tab de producción
                        const productionCount = document.getElementById('productionTotalCount');
                        if (productionCount) {
                            productionCount.innerText = count;
                        }

                        // Actualizar currentDesign
                        if (currentDesign && currentDesign.id == designId) {
                            currentDesign.exports_count = count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error actualizando contador:', error);
                });
        }

        // Escuchar eventos de exportación
        $(document).on('exportCreated exportDeleted exportUpdated', function(event, data) {
            if (data && data.designId) {
                updateExportsCounter(data.designId);
            }
        });

        // ============================================
        // EVENT LISTENERS
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    executeDelete();
                });
            }

            document.addEventListener('keydown', function(e) {
                if ($('#designModal').hasClass('show') && !isProcessingDelete) {
                    if (e.key === 'ArrowLeft') {
                        navigateGalleryImage(-1);
                    } else if (e.key === 'ArrowRight') {
                        navigateGalleryImage(1);
                    }
                }
            });

            $('#designModal').on('hide.bs.modal', function(e) {
                if (isProcessingDelete) {
                    e.preventDefault();
                    return false;
                }
            });

            $('#deleteConfirmModal').on('hidden.bs.modal', function() {
                if (!isProcessingDelete) {
                    pendingDeleteUrl = null;
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    if (confirmBtn) {
                        confirmBtn.classList.remove('loading');
                        confirmBtn.disabled = false;
                    }
                }
            });

            $('#designModal').on('hidden.bs.modal', function() {
                hideLoadingState();
                pendingDeleteUrl = null;
            });

            // ============================================
            // CORRECCIÓN DE ACCESIBILIDAD PARA MODALES
            // ============================================
            // Prevenir el warning de accesibilidad en modales
            // Se maneja dinámicamente el atributo aria-hidden en el wrapper
            $('#designModal').on('show.bs.modal', function() {
                // Remover aria-hidden del wrapper cuando el modal se abre
                $('.wrapper').removeAttr('aria-hidden');
            });

            $('#designModal').on('hidden.bs.modal', function() {
                // Restaurar aria-hidden cuando el modal se cierra
                $('.wrapper').attr('aria-hidden', 'true');
            });

            // También aplicar al modal de confirmación de eliminación
            $('#deleteConfirmModal').on('show.bs.modal', function() {
                $('.wrapper').removeAttr('aria-hidden');
            });

            $('#deleteConfirmModal').on('hidden.bs.modal', function() {
                $('.wrapper').attr('aria-hidden', 'true');
            });
        });
    </script>

    {{-- Bloque para ÉXITO --}}
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

    {{-- Bloque para ERROR --}}
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: "{{ session('error') }}",
                showConfirmButton: true,
                confirmButtonColor: '#2563eb'
            });
        </script>
    @endif
@stop
