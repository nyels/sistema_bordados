@extends('adminlte::page')

@section('title', 'Diseños')

@section('content_header')
    <div class="page-header-wrapper">
        <h1 class="page-title">Diseños</h1>
        <a href="{{ route('admin.designs.create') }}" class="btn-new-design">
            <i class="fas fa-plus"></i>
            <span>Nuevo diseño</span>
        </a>
    </div>
@stop

@section('content')

    <div class="row">

        {{-- SIDEBAR --}}
        <div class="col-lg-3">

            <div class="surface mb-4">
                <form method="GET" id="searchForm">
                    {{-- Contenedor relativo para agrupar todo --}}
                    <div class="position-relative d-flex align-items-center">

                        {{-- LUPA / SPINNER (IZQUIERDA) --}}
                        <span class="position-absolute" style="left: 12px; line-height: 1; z-index: 10;">
                            <i class="fas fa-search text-primary" id="searchIcon"></i>
                            <i class="fas fa-spinner fa-spin text-primary" id="searchSpinner" style="display: none;"></i>
                        </span>

                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                            placeholder="Buscar diseño…" autocomplete="off" class="search-input w-100"
                            style="padding-left: 40px; padding-right: 40px;">

                        {{-- Botón clear estilo iOS/Apple --}}
                        <a href="{{ route('admin.designs.index') }}" id="searchClear" class="search-clear"
                            style="display: {{ request('search') ? 'flex' : 'none' }};"></a>

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
        <div class="col-lg-9" id="designsContainer">

            @if ($designs->count() > 0)
                <div class="design-grid" id="designGrid">

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
                                    <img src="{{ asset('storage/' . ($design->primaryImage->thumbnail_small ?? $design->primaryImage->file_path)) }}"
                                        data-full-src="{{ asset('storage/' . $design->primaryImage->file_path) }}"
                                        alt="{{ $design->name }}" loading="lazy"
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
            <div class="modal-content modal-premium" style="overflow: visible;">

                {{-- Botón Cerrar Flotante --}}
                <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

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

                                    {{-- Badge contador de producciones --}}
                                    <span id="mainImageProductionBadge" class="production-count-badge"
                                        data-count="0"></span>

                                    {{-- Overlay para añadir producción --}}
                                    <div class="image-production-overlay" id="mainImageOverlay">
                                        <button type="button" class="btn-add-production-overlay"
                                            onclick="addProductionFromImage('main')">
                                            <i class="fas fa-plus"></i>
                                            <span>Producción</span>
                                        </button>
                                    </div>

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
                                                <span id="variantPrice" class="price-badge" hidden>$0.00</span>
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

                                            <a href="#" id="btnEditVariants" class="btn-edit-variant disabled"
                                                style="pointer-events: none; opacity: 0.5;">
                                                <i class="fas fa-pencil-alt"></i>
                                                Editar Variante
                                            </a>
                                            <a href="#" id="btnAddVariant" class="btn-add-variant">
                                                <i class="fas fa-plus"></i>
                                                Nueva Variante
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
            <div class="modal-content modal-delete-apple" style="overflow: visible;">

                {{-- Botón Cerrar Flotante --}}
                <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

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
                {{-- Botones de acción (Vertical: Eliminar Arriba) --}}
                <div class="modal-delete-actions"
                    style="display: flex; flex-direction: column-reverse; gap: 12px; padding: 0 20px 24px;">
                    <button type="button" class="btn-cancel-apple" data-dismiss="modal"
                        style="width: 100%; justify-content: center;">
                        Cancelar
                    </button>
                    <button type="button" class="btn-delete-apple" id="confirmDeleteBtn"
                        style="width: 100%; justify-content: center;">
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

        /* ============================================
                                                                                                                                                                        BOTÓN CERRAR MODAL - ARREGLADO
                                                                                                                                                                        Estilos para el botón de cerrar (X) que estaba flotando mal
                                                                                                                                                                        ============================================ */
        .modal-close-premium {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e5e7eb;
            color: #374151;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 9999;
            /* Alto z-index para estar por encima de todo */
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            outline: none;
        }

        .modal-close-premium:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .modal-close-premium:active {
            transform: scale(0.95);
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
            padding: 24px 28px;
            position: relative;
            border-right: 1px solid #f1f5f9;
        }

        /* COLUMNA DERECHA - CON PADDING CORRECTO */
        .modal-right-column {
            display: flex;
            flex-direction: column;
            background: #f9fafb;
            overflow: hidden;
            padding: 24px 28px;
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
            padding: 16px 8px 16px 8px;
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
            justify-content: center;
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

        /* GRID DE GALERÍA - ALTURA AUTOMÁTICA */
        .gallery-grid-container {
            flex: 1;
            width: 100%;
            min-height: 60px;
            max-height: 180px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 4px;
        }

        /* Si solo hay 1-3 items (una fila), no necesita scroll */
        .gallery-grid-container:has(.gallery-grid:not(:has(.gallery-item:nth-child(4)))) {
            max-height: none;
            overflow-y: visible;
        }

        /* Cuando la galería está vacía, centrar contenido */
        .gallery-grid-container:has(.gallery-empty-state) {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Scrollbar visible y más ancho */
        .gallery-grid-container::-webkit-scrollbar {
            width: 10px;
        }

        .gallery-grid-container::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 5px;
        }

        .gallery-grid-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 5px;
            border: 2px solid #e5e7eb;
        }

        .gallery-grid-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
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

        /* ================================================
                                                                                                                                           HOVER OVERLAY PARA AÑADIR PRODUCCIÓN
                                                                                                                                           ================================================ */

        /* Overlay en IMAGEN PRINCIPAL - posicionado abajo */
        .main-image-wrapper .image-production-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            height: 70px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 16px;
            opacity: 0;
            transition: opacity 0.25s ease;
            z-index: 3;
            /* Por debajo de los botones de descarga/thumbnail */
            border-radius: 0 0 inherit inherit;
            pointer-events: none;
        }

        .main-image-wrapper:hover .image-production-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        /* Asegurar que descarga y thumbnail estén por encima */
        .main-image-wrapper .btn-download-floating,
        .main-image-wrapper .btn-back-thumbnail {
            z-index: 15 !important;
        }

        /* Botón de producción en imagen principal */
        .main-image-wrapper .btn-add-production-overlay {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .main-image-wrapper .btn-add-production-overlay:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5);
        }

        /* Badge contador en imagen principal */
        .main-image-wrapper .production-count-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            top: auto;
            min-width: 24px;
            height: 24px;
            padding: 0 6px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 6px rgba(139, 92, 246, 0.4);
        }

        /* Overlay en GALERÍA - mismo estilo que imagen principal */
        .gallery-item .image-production-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            height: 50px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 10px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 3;
            border-radius: 0 0 6px 6px;
            pointer-events: none;
        }

        .gallery-item:hover .image-production-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        /* Botón para galería - mismo diseño que imagen principal */
        .gallery-item .btn-add-production-overlay {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.4);
        }

        .gallery-item .btn-add-production-overlay:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
        }

        .gallery-item .btn-add-production-overlay i {
            font-size: 10px;
        }

        /* Badge contador en galería - posicionado abajo como imagen principal */
        .gallery-item .production-count-badge {
            position: absolute;
            bottom: 6px;
            left: 6px;
            top: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 6px rgba(139, 92, 246, 0.4);
        }

        .production-count-badge:empty,
        .production-count-badge[data-count="0"] {
            display: none;
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
                                                                                                                                           PAGE HEADER - Enterprise/SaaS Style
                                                                                                                                           ============================================ */
        .page-header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            line-height: 1.2;
        }

        .btn-new-design {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
            white-space: nowrap;
            min-height: 44px;
        }

        .btn-new-design:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-new-design:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-new-design i {
            font-size: 12px;
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
                                                                                                                                                                        RESPONSIVE - BREAKPOINTS ESTÁNDAR
                                                                                                                                                                        xs: <576px | sm: 576-767px | md: 768-991px | lg: 992-1199px | xl: ≥1200px
                                                                                                                                                                        ============================================ */

        /* ===== XL: Pantallas grandes (≥1200px) ===== */
        @media (min-width: 1200px) {
            .design-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* ===== LG: Desktop (992px - 1199px) ===== */
        @media (min-width: 992px) and (max-width: 1199px) {
            .design-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .category-list {
                max-height: 350px;
            }
        }

        /* ===== MD: Tablets (768px - 991px) ===== */
        @media (max-width: 991px) {

            /* Modal */
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

            /* Sidebar colapsable */
            .col-lg-3 {
                margin-bottom: 20px;
            }

            /* Categorías en horizontal scroll */
            .category-list {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                max-height: none;
                gap: 8px;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }

            .category-item {
                flex-shrink: 0;
                white-space: nowrap;
                padding: 8px 16px;
            }

            .category-count {
                margin-left: 8px;
            }

            /* Grid 2 columnas en tablet */
            .design-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .design-image {
                height: 160px;
            }

            /* Surface más compacta */
            .surface {
                padding: 16px;
                border-radius: 12px;
            }

            .sidebar-title {
                display: none;
            }
        }

        /* ===== SM: Móviles grandes (576px - 767px) ===== */
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

            /* Grid responsive */
            .design-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .design-card {
                border-radius: 14px;
            }

            .design-image {
                height: 140px;
                padding: 6px;
            }

            .design-body {
                padding: 12px;
            }

            .design-body .design-title {
                font-size: 0.9rem;
            }

            .design-variants {
                font-size: 12px;
            }

            .design-exports {
                font-size: 11px;
                padding: 5px 10px;
            }

            /* Search input */
            .search-input {
                padding: 12px 36px 12px 14px;
                font-size: 14px;
            }

            /* Header responsive - Tablet */
            .page-title {
                font-size: 1.5rem;
            }

            .btn-new-design {
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        /* ===== XS: Móviles pequeños (<576px) ===== */
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

            /* Grid 1 columna en móvil pequeño - Cards más grandes */
            .design-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .design-card {
                display: flex;
                flex-direction: row;
                border-radius: 12px;
            }

            .design-image {
                width: 120px;
                min-width: 120px;
                height: 120px;
                border-radius: 12px 0 0 12px;
            }

            .design-body {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: left;
                padding: 12px 16px;
            }

            .design-body .design-title {
                font-size: 1rem;
                margin-bottom: 4px;
            }

            .design-exports {
                justify-content: flex-start;
                margin-top: 6px;
            }

            /* Categorías scroll horizontal más compacto */
            .category-list {
                gap: 6px;
            }

            .category-item {
                padding: 6px 12px;
                font-size: 13px;
                border-radius: 8px;
            }

            /* Surface */
            .surface {
                padding: 12px;
                border-radius: 10px;
                margin-bottom: 12px !important;
            }

            /* Search */
            .search-input {
                padding: 10px 32px 10px 12px;
                border-radius: 10px;
            }

            /* Header - Mobile */
            .page-header-wrapper {
                gap: 12px;
            }

            .page-title {
                font-size: 1.35rem;
            }

            .btn-new-design {
                padding: 10px 14px;
                font-size: 13px;
                border-radius: 8px;
            }

            .btn-new-design i {
                font-size: 11px;
            }

            /* Alert info responsive */
            .alert-info {
                font-size: 13px;
                padding: 12px;
            }
        }

        /* ===== XXS: Móviles muy pequeños (<400px) ===== */
        @media (max-width: 400px) {

            /* Header - XXS: Stack vertical */
            .page-header-wrapper {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .page-title {
                font-size: 1.25rem;
                text-align: center;
            }

            .btn-new-design {
                justify-content: center;
                padding: 12px 16px;
                width: 100%;
            }

            .design-image {
                width: 100px;
                min-width: 100px;
                height: 100px;
            }

            .design-body {
                padding: 10px 12px;
            }

            .design-body .design-title {
                font-size: 0.9rem;
            }

            .design-variants {
                font-size: 11px;
            }

            .design-exports {
                font-size: 10px;
                padding: 4px 8px;
            }

            .category-item {
                padding: 5px 10px;
                font-size: 12px;
            }
        }

        /* ===== Scrollbar personalizado para categorías ===== */
        .category-list::-webkit-scrollbar {
            height: 4px;
        }

        .category-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 2px;
        }

        .category-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .category-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== Transiciones suaves en cambios de tamaño ===== */
        .design-card,
        .design-image,
        .design-body,
        .surface,
        .category-item {
            transition: all 0.2s ease;
        }

        /* ============================================
                                                                                                                                           WEB APP / PWA - OPTIMIZACIONES TOUCH & MOBILE
                                                                                                                                           ============================================ */

        /* Touch Target mínimo 44x44px (Apple HIG / Material Design) */
        .design-card,
        .category-item,
        .btn,
        .modal-close {
            min-height: 44px;
            min-width: 44px;
        }

        /* Mejor feedback táctil */
        .design-card {
            -webkit-tap-highlight-color: rgba(37, 99, 235, 0.1);
            touch-action: manipulation;
            user-select: none;
            -webkit-user-select: none;
        }

        .design-card:active {
            transform: scale(0.98);
            opacity: 0.9;
        }

        .category-item {
            -webkit-tap-highlight-color: rgba(37, 99, 235, 0.15);
            touch-action: manipulation;
        }

        .category-item:active {
            transform: scale(0.97);
        }

        /* Input optimizado para móvil */
        .search-input {
            font-size: 16px !important;
            /* Previene zoom en iOS */
            -webkit-appearance: none;
            appearance: none;
        }

        /* Botón clear - Estilo Apple/Premium */
        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0;
            /* Ocultar el × de texto */
            border-radius: 50%;
            background: #c4c4c6;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            z-index: 10;
        }

        /* Icono X con pseudo-elementos (estilo iOS) */
        .search-clear::before,
        .search-clear::after {
            content: '';
            position: absolute;
            width: 10px;
            height: 1.5px;
            background: #fff;
            border-radius: 1px;
        }

        .search-clear::before {
            transform: rotate(45deg);
        }

        .search-clear::after {
            transform: rotate(-45deg);
        }

        .search-clear:hover {
            background: #8e8e93;
            text-decoration: none;
        }

        .search-clear:active {
            background: #636366;
            transform: translateY(-50%) scale(0.92);
        }

        /* Safe Areas para iPhone X+ (notch) */
        @supports (padding: max(0px)) {
            .page-header-wrapper {
                padding-left: max(0px, env(safe-area-inset-left));
                padding-right: max(0px, env(safe-area-inset-right));
            }

            .modal-body {
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }

            .surface {
                margin-left: max(0px, env(safe-area-inset-left));
                margin-right: max(0px, env(safe-area-inset-right));
            }
        }

        /* Smooth scrolling nativo */
        .category-list,
        .design-grid,
        .modal-body {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Prevenir scroll bounce en iOS */
        .designs-container {
            overscroll-behavior: contain;
        }

        /* Optimización de renderizado GPU */
        .design-card,
        .modal-dialog {
            will-change: transform;
            transform: translateZ(0);
            backface-visibility: hidden;
        }

        /* Loading state visual */
        .design-card.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Skeleton loading placeholder */
        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
        }

        /* Pull-to-refresh indicator (futuro) */
        .ptr-indicator {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%) translateY(-100%);
            transition: transform 0.2s ease;
            z-index: 9999;
        }

        /* Orientation changes */
        @media (orientation: landscape) and (max-height: 500px) {
            .design-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .design-image {
                height: 100px;
            }

            .modal-dialog.modal-xl {
                max-height: 95vh;
            }
        }

        /* Tablet landscape optimization */
        @media (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
            .design-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 14px;
            }

            .design-image {
                height: 130px;
            }
        }

        /* High DPI / Retina displays */
        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            .design-card {
                border-width: 0.5px;
            }
        }

        /* Reduced motion accessibility */
        @media (prefers-reduced-motion: reduce) {

            .design-card,
            .design-image,
            .design-body,
            .surface,
            .category-item,
            .modal-dialog {
                transition: none !important;
                animation: none !important;
            }

            .design-card:active {
                transform: none;
            }
        }

        /* Dark mode support (futuro) */
        @media (prefers-color-scheme: dark) {

            /* Variables preparadas para dark mode */
            :root {
                --bg-surface: #1f2937;
                --text-primary: #f9fafb;
                --text-secondary: #9ca3af;
                --border-color: #374151;
            }
        }

        /* Hover states solo para dispositivos con hover real */
        @media (hover: hover) and (pointer: fine) {
            .design-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 40px -12px rgba(0, 0, 0, 0.25);
            }

            .category-item:hover {
                background: rgba(37, 99, 235, 0.08);
            }
        }

        /* Touch devices - no hover effects */
        @media (hover: none) {
            .design-card:hover {
                transform: none;
                box-shadow: 0 4px 20px -8px rgba(0, 0, 0, 0.12);
            }
        }

        /* Foldable devices support */
        @media (horizontal-viewport-segments: 2) {
            .design-grid {
                gap: calc(env(viewport-segment-right 0 0) - env(viewport-segment-left 0 0) + 16px);
            }
        }
    </style>
@stop

@section('js')
    {{-- Tu código JavaScript existente se mantiene intacto --}}
    <script>
        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let currentDesign = null;
        let currentVariants = [];
        let currentVariantIndex = -1;
        let currentGalleryImages = [];
        let currentGalleryIndex = 0;
        let currentDisplayedImageId = null; // ID de la imagen actualmente mostrada
        let pendingDeleteUrl = null;
        let isProcessingDelete = false;
        let isViewingDesignImage = true;

        // ============================================
        // CACHE DE CONTADORES DE PRODUCCIÓN (ENTERPRISE-LEVEL)
        // Evita flickering al cambiar entre variantes/diseño
        // Includes AbortController for request cancellation on rapid context switches
        // ============================================
        const productionCountCache = {
            design: {}, // { designId: count }
            variant: {}, // { variantId: count }
            loading: new Set(), // Prevent duplicate fetches
            requestVersion: 0, // Tracks current context to cancel stale responses
            abortController: null // AbortController for cancelling pending requests
        };

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
        // CLEANUP: Limpiar cache de producción al cerrar modal
        // ============================================
        $('#designModal').on('hidden.bs.modal', function() {
            clearProductionCountCache();
            document.getElementById('productionTotalCount').innerText = '0';
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
            // NO resetear productionTotalCount aquí para evitar flickering
            // El valor se actualizará cuando se cargue el nuevo diseño
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
            originalSrc = null,
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

            // Usar thumbnail para el botón de volver
            if (showBackButton && currentDesign?.primaryImage?.file_path) {
                const thumbSrc = currentDesign.primaryImage.thumbnail_small || currentDesign.primaryImage.file_path;
                thumbnail.src = `/storage/${thumbSrc}`;
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

            // Guardar la URL original para descarga (imagen sin comprimir)
            const downloadUrl = originalSrc || src;

            img.onload = () => {
                noImg.style.setProperty('display', 'none', 'important');
                downloadBtn.style.display = 'flex';
                // IMPORTANTE: Siempre usar la imagen original para descarga
                downloadBtn.href = downloadUrl;
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
                currentDisplayedImageId = currentDesign.primaryImage.id || null; // Guardar ID de imagen principal
                // Usar thumbnail_medium para visualización, file_path para descarga
                const displaySrc = currentDesign.primaryImage.thumbnail_medium ?
                    `/storage/${currentDesign.primaryImage.thumbnail_medium}` :
                    `/storage/${currentDesign.primaryImage.file_path}`;
                const originalSrc = `/storage/${currentDesign.primaryImage.file_path}`;
                displayMainImage({
                    src: displaySrc,
                    originalSrc: originalSrc,
                    title: currentDesign.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: `${currentDesign.name.replace(/\s+/g, '_')}.${ext}`,
                    showBackButton: false
                });
            } else {
                currentDisplayedImageId = null;
                displayMainImage({
                    src: null,
                    originalSrc: null,
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
            updateMainImageProductionBadge(); // Actualizar badge de imagen principal
            updateProductionTabCount(); // Actualizar contador de producción en pestaña

            // Si la pestaña de producción está activa, recargar los datos del diseño principal
            if ($('#production-tab').hasClass('active') || $('#production-content').hasClass('active show')) {
                // Disparar recarga de datos de producción para el nuevo contexto (diseño principal)
                if (typeof window.loadProductionData === 'function') {
                    window.loadProductionData();
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
        // FUNCIÓN: AÑADIR PRODUCCIÓN DESDE IMAGEN
        // ============================================
        function addProductionFromImage(source, imageId = null, variantIndex = null) {
            // Determinar el image_id según el contexto
            let targetImageId = imageId;

            if (source === 'main') {
                // Imagen principal: usar el ID de la imagen actualmente mostrada
                targetImageId = currentDisplayedImageId;
            } else if (source === 'gallery' && imageId) {
                // Desde galería: cambiar la imagen principal a la imagen seleccionada
                const imgIndex = currentGalleryImages.findIndex(img => img.id === imageId);
                if (imgIndex !== -1 && currentVariants[currentVariantIndex]) {
                    showVariantImage(currentVariants[currentVariantIndex], currentGalleryImages, imgIndex);
                    // Actualizar el estado activo en la galería
                    updateGalleryActiveState(imgIndex);
                }
                targetImageId = imageId;
            }

            // Preparar para ir directo al formulario (ANTES de cambiar de pestaña)
            if (typeof window.prepareDirectFormOpen === 'function') {
                window.prepareDirectFormOpen(targetImageId);
            }

            // Cambiar a la pestaña de producción
            // El evento shown.bs.tab detectará la bandera y mostrará el formulario directo
            $('#production-tab').tab('show');
        }

        // ============================================
        // FUNCIÓN: CARGAR CONTADOR DE PRODUCCIONES POR IMAGEN (individual)
        // ============================================
        function loadImageProductionCount(imageId, badgeElementId) {
            if (!imageId) return;

            fetch(`/admin/images/${imageId}/exports-count`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById(badgeElementId);
                        if (badge) {
                            badge.textContent = data.count;
                            badge.dataset.count = data.count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error cargando contador de producciones:', error);
                });
        }

        // ============================================
        // FUNCIÓN: CARGAR CONTADORES BATCH (una sola petición para múltiples imágenes)
        // ============================================
        function loadGalleryProductionCounts(imageIds) {
            if (!imageIds || imageIds.length === 0) return;

            fetch('/admin/images/exports-counts-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        image_ids: imageIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.counts) {
                        Object.entries(data.counts).forEach(([imageId, count]) => {
                            const badge = document.getElementById(`gallery-badge-${imageId}`);
                            if (badge) {
                                badge.textContent = count > 0 ? count : '';
                                badge.dataset.count = count;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error cargando contadores batch:', error);
                });
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR BADGE DE IMAGEN PRINCIPAL
        // ============================================
        function updateMainImageProductionBadge() {
            const badge = document.getElementById('mainImageProductionBadge');
            if (!badge) return;

            // Si estamos viendo la imagen principal del diseño (sin variante seleccionada),
            // mostrar producciones del diseño que no tienen image_id ni variant_id
            if (isViewingDesignImage && currentDesign && currentDesign.id) {
                loadDesignMainProductionCount(currentDesign.id);
            }
            // Si hay una imagen específica de galería seleccionada (variante), cargar su contador
            else if (currentDisplayedImageId && !isViewingDesignImage) {
                loadImageProductionCount(currentDisplayedImageId, 'mainImageProductionBadge');
            } else {
                badge.textContent = '';
                badge.dataset.count = '0';
            }
        }

        // ============================================
        // FUNCIÓN: ACTUALIZAR CONTADOR DE PRODUCCIÓN EN PESTAÑA
        // Enterprise-level: Actualizaciones instantáneas con cache + AbortController
        // ============================================
        function updateProductionTabCount(forceRefresh = false) {
            const designId = currentDesign?.id;
            const variantId = document.getElementById('modalVariantId')?.value;
            const counterEl = document.getElementById('productionTotalCount');

            if (!counterEl) return;

            if (!designId) {
                counterEl.innerText = '0';
                return;
            }

            // Determinar la clave de cache
            const cacheKey = variantId && variantId !== '' ? `variant_${variantId}` : `design_${designId}`;
            const cacheType = variantId && variantId !== '' ? 'variant' : 'design';
            const cacheId = variantId && variantId !== '' ? variantId : designId;

            // ========== PASO 1: ACTUALIZACIÓN INSTANTÁNEA DESDE CACHE ==========
            if (productionCountCache[cacheType][cacheId] !== undefined && !forceRefresh) {
                counterEl.innerText = productionCountCache[cacheType][cacheId];
                return; // Cache hit - no need to fetch
            }

            // ========== PASO 2: CANCELAR REQUEST ANTERIOR SI EXISTE ==========
            if (productionCountCache.abortController) {
                productionCountCache.abortController.abort();
                console.log('[Counter] Previous request aborted - context changed');
            }

            // Crear nuevo AbortController para este request
            productionCountCache.abortController = new AbortController();
            const signal = productionCountCache.abortController.signal;

            // Incrementar versión como medida de seguridad adicional
            productionCountCache.requestVersion++;
            const myVersion = productionCountCache.requestVersion;

            // Resetear contador a 0 INMEDIATAMENTE
            counterEl.innerText = '0';

            const endpoint = variantId && variantId !== '' ?
                `/admin/designs/${designId}/variants/${variantId}/exports/ajax` :
                `/admin/designs/${designId}/exports-count`;

            fetch(endpoint, {
                    signal
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    // Doble verificación: versión + request no fue abortado
                    if (myVersion !== productionCountCache.requestVersion) {
                        return; // Versión obsoleta
                    }

                    if (data.success) {
                        const count = data.count || 0;
                        // Guardar en cache
                        productionCountCache[cacheType][cacheId] = count;
                        // Actualizar UI
                        counterEl.innerText = count;
                    }
                })
                .catch(error => {
                    // Ignorar errores de abort (son intencionales)
                    if (error.name === 'AbortError') {
                        console.log('[Counter] Request aborted intentionally');
                        return;
                    }
                    console.error('Error cargando contador de producción:', error);
                    // En caso de error real, solo resetear si somos la versión actual
                    if (myVersion === productionCountCache.requestVersion) {
                        counterEl.innerText = '0';
                    }
                });
        }

        // ============================================
        // FUNCIÓN: PRELLENAR CACHE DE PRODUCCIÓN AL ABRIR MODAL
        // Se llama cuando se carga un diseño para tener los contadores listos
        // ============================================
        function prefetchProductionCounts(designId, variants) {
            if (!designId) return;

            // Prefetch contador del diseño principal
            const designCacheKey = `design_${designId}`;
            if (!productionCountCache.loading.has(designCacheKey)) {
                productionCountCache.loading.add(designCacheKey);
                fetch(`/admin/designs/${designId}/exports-count`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            productionCountCache.design[designId] = data.count || 0;
                            // Si estamos en diseño principal, actualizar UI
                            const currentVarId = document.getElementById('modalVariantId')?.value;
                            if ((!currentVarId || currentVarId === '') && currentDesign?.id == designId) {
                                document.getElementById('productionTotalCount').innerText = data.count || 0;
                            }
                        }
                    })
                    .finally(() => productionCountCache.loading.delete(designCacheKey));
            }

            // Prefetch contadores de todas las variantes
            if (variants && variants.length > 0) {
                variants.forEach(variant => {
                    const varCacheKey = `variant_${variant.id}`;
                    if (!productionCountCache.loading.has(varCacheKey)) {
                        productionCountCache.loading.add(varCacheKey);
                        fetch(`/admin/designs/${designId}/variants/${variant.id}/exports/ajax`)
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    productionCountCache.variant[variant.id] = data.count || 0;
                                }
                            })
                            .finally(() => productionCountCache.loading.delete(varCacheKey));
                    }
                });
            }
        }

        // ============================================
        // FUNCIÓN: LIMPIAR CACHE DE PRODUCCIÓN
        // Se llama cuando se cierra el modal
        // ============================================
        function clearProductionCountCache() {
            // Cancelar cualquier request pendiente
            if (productionCountCache.abortController) {
                productionCountCache.abortController.abort();
                productionCountCache.abortController = null;
            }
            productionCountCache.design = {};
            productionCountCache.variant = {};
            productionCountCache.loading.clear();
            productionCountCache.requestVersion = 0;
        }

        // ============================================
        // FUNCIÓN: CARGAR CONTADOR DE PRODUCCIONES DEL DISEÑO (SIN IMAGE_ID)
        // ============================================
        function loadDesignMainProductionCount(designId) {
            if (!designId) return;

            fetch(`/admin/designs/${designId}/exports-without-image-count`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('mainImageProductionBadge');
                        if (badge) {
                            badge.textContent = data.count || '';
                            badge.dataset.count = data.count || '0';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error cargando contador de producciones:', error);
                });
        }

        // ============================================
        // FUNCIÓN: INDICADOR DE IMAGEN VINCULADA EN FORMULARIO
        // ============================================
        function updateProductionImageIndicator(imageId) {
            const indicator = document.getElementById('linkedImageIndicator');
            if (!indicator) return;

            if (imageId) {
                indicator.innerHTML = `
                    <div class="alert alert-info py-2 px-3 mb-2" style="font-size: 12px;">
                        <i class="fas fa-link mr-1"></i>
                        Producción vinculada a imagen #${imageId}
                        <button type="button" class="close" onclick="clearLinkedImage()" style="font-size: 14px;">
                            <span>&times;</span>
                        </button>
                    </div>
                `;
                indicator.style.display = 'block';
            } else {
                indicator.innerHTML = '';
                indicator.style.display = 'none';
            }
        }

        // ============================================
        // FUNCIÓN: LIMPIAR IMAGEN VINCULADA
        // ============================================
        function clearLinkedImage() {
            const imageIdField = document.getElementById('exportImageId');
            if (imageIdField) {
                imageIdField.value = '';
            }
            updateProductionImageIndicator(null);
        }

        // ============================================
        // FUNCIÓN: REFRESCAR TODOS LOS BADGES DE GALERÍA
        // ============================================
        function refreshGalleryProductionBadges() {
            const galleryItems = document.querySelectorAll('.gallery-item[data-image-id]');
            galleryItems.forEach(item => {
                const imageId = item.dataset.imageId;
                if (imageId) {
                    loadImageProductionCount(imageId, `gallery-badge-${imageId}`);
                }
            });
            updateMainImageProductionBadge();
        }

        // ============================================
        // ⭐ FUNCIÓN: INCREMENTAR BADGE DE IMAGEN INMEDIATAMENTE (OPTIMISTA)
        // ============================================
        window.incrementImageBadge = function(imageId) {
            if (!imageId) return;

            // Buscar el badge de la imagen en la galería
            const badge = document.getElementById(`gallery-badge-${imageId}`);
            if (badge) {
                const currentCount = parseInt(badge.dataset.count || badge.textContent || '0');
                const newCount = currentCount + 1;
                badge.textContent = newCount;
                badge.dataset.count = newCount;

                // Efecto visual de actualización
                badge.style.transform = 'scale(1.3)';
                badge.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                    badge.style.transform = 'scale(1)';
                }, 200);
            }
        };

        // ============================================
        // ⭐ FUNCIÓN: INCREMENTAR BADGE DE IMAGEN PRINCIPAL SI COINCIDE
        // ============================================
        window.incrementMainImageBadgeIfMatches = function(imageId) {
            if (!imageId || !currentDisplayedImageId) return;

            // Solo incrementar si la imagen actual es la misma
            if (currentDisplayedImageId == imageId) {
                const badge = document.getElementById('mainImageProductionBadge');
                if (badge) {
                    const currentCount = parseInt(badge.dataset.count || badge.textContent || '0');
                    const newCount = currentCount + 1;
                    badge.textContent = newCount;
                    badge.dataset.count = newCount;

                    // Efecto visual de actualización
                    badge.style.transform = 'scale(1.3)';
                    badge.style.transition = 'transform 0.2s ease';
                    setTimeout(() => {
                        badge.style.transform = 'scale(1)';
                    }, 200);
                }
            }
        };

        // ============================================
        // FUNCIÓN: MOSTRAR IMAGEN DE VARIANTE
        // ============================================
        function showVariantImage(variant, images, index) {
            const img = images[index];
            if (!img || !img.file_path) {
                currentDisplayedImageId = null;
                displayMainImage({
                    src: null,
                    originalSrc: null,
                    title: variant.name,
                    subtitle: 'Imagen de variante',
                    downloadName: null,
                    showBackButton: true
                });
                updateMainImageProductionBadge();
                return;
            }

            const ext = img.file_path.split('.').pop();
            currentGalleryIndex = index;
            isViewingDesignImage = false;
            currentDisplayedImageId = img.id || null; // Guardar ID de imagen actual

            // Usar thumbnail_medium para visualización, file_path para descarga
            const displaySrc = img.thumbnail_medium ? `/storage/${img.thumbnail_medium}` : `/storage/${img.file_path}`;
            const originalSrc = `/storage/${img.file_path}`;

            displayMainImage({
                src: displaySrc,
                originalSrc: originalSrc,
                title: variant.name,
                subtitle: `Foto ${index + 1} de variante`,
                downloadName: `${variant.name.replace(/\s+/g, '_')}_${index + 1}.${ext}`,
                showBackButton: true
            });

            updateGalleryActiveState(index);
            scrollGalleryItemIntoView(index);
            updateMainImageProductionBadge(); // Actualizar badge
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

            // ENTERPRISE: Prellenar cache de contadores de producción para actualizaciones instantáneas
            prefetchProductionCounts(design.id, currentVariants);

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

            // Imagen principal - Usar thumbnail_medium para visualización, file_path para descarga
            if (design.primaryImage?.file_path) {
                const ext = design.primaryImage.file_path.split('.').pop();
                currentDisplayedImageId = design.primaryImage.id || null; // Guardar ID de imagen principal
                const displaySrc = design.primaryImage.thumbnail_medium ?
                    `/storage/${design.primaryImage.thumbnail_medium}` :
                    `/storage/${design.primaryImage.file_path}`;
                const originalSrc = `/storage/${design.primaryImage.file_path}`;
                displayMainImage({
                    src: displaySrc,
                    originalSrc: originalSrc,
                    title: design.name,
                    subtitle: 'Imagen principal diseño',
                    downloadName: `${design.name.replace(/\s+/g, '_')}.${ext}`
                });
                updateMainImageProductionBadge(); // Cargar contador de producciones
            } else {
                currentDisplayedImageId = null;
                displayMainImage({
                    src: null,
                    originalSrc: null,
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
            // Guardar en cache para uso instantáneo
            productionCountCache.design[design.id] = exportsCount;

            // Renderizar tabs de variantes
            const tabsContainer = document.getElementById('variantTabs');

            if (currentVariants.length > 0) {
                currentVariants.forEach((variant, index) => {
                    let thumbSrc = '';
                    let hasImage = false;

                    // Usar thumbnail_small para pestañas de variantes (más rápida carga)
                    if (variant.images && variant.images.length > 0 && variant.images[0].file_path) {
                        const img = variant.images[0];
                        thumbSrc = img.thumbnail_small ?
                            `/storage/${img.thumbnail_small}` :
                            `/storage/${img.file_path}`;
                        hasImage = true;
                    } else if (design.primaryImage && design.primaryImage.file_path) {
                        thumbSrc = design.primaryImage.thumbnail_small ?
                            `/storage/${design.primaryImage.thumbnail_small}` :
                            `/storage/${design.primaryImage.file_path}`;
                        hasImage = true;
                    }

                    const tab = document.createElement('div');
                    tab.className = 'variant-tab';

                    if (hasImage) {
                        tab.innerHTML = `
                            <img src="${thumbSrc}" loading="lazy"
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
                        item.dataset.imageId = img.id || '';

                        // Badge contador de producciones
                        const badge = document.createElement('span');
                        badge.className = 'production-count-badge';
                        badge.dataset.count = '0';
                        badge.id = `gallery-badge-${img.id || imgIndex}`;
                        item.appendChild(badge);

                        // Overlay para añadir producción
                        const overlay = document.createElement('div');
                        overlay.className = 'image-production-overlay';
                        overlay.innerHTML = `
                            <button type="button" class="btn-add-production-overlay" onclick="event.stopPropagation(); addProductionFromImage('gallery', ${img.id || 0}, ${currentVariantIndex})">
                                <i class="fas fa-plus"></i>
                                <span>Producción</span>
                            </button>
                        `;
                        item.appendChild(overlay);

                        // Usar thumbnail_small para galería (carga más rápida)
                        const imgElement = document.createElement('img');
                        const imgSrc = img.thumbnail_small ?
                            `/storage/${img.thumbnail_small}` :
                            `/storage/${img.file_path}`;
                        imgElement.src = imgSrc;
                        imgElement.alt = `Imagen ${imgIndex + 1}`;
                        // Forzar carga inmediata
                        imgElement.decoding = 'async';
                        imgElement.onerror = function() {
                            console.warn('Error cargando imagen:', imgSrc);
                            this.onerror = null;
                            // Fallback al archivo original si el thumbnail falla
                            if (img.file_path && this.src.includes('_thumb_')) {
                                this.src = `/storage/${img.file_path}`;
                            } else {
                                this.src =
                                    'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f1f5f9"/><text x="50%" y="50%" font-family="Arial" font-size="10" fill="%2394a3b8" text-anchor="middle" dy=".3em">Sin Imagen</text></svg>';
                            }
                        };

                        item.appendChild(imgElement);
                        item.onclick = () => {
                            showVariantImage(variant, images, imgIndex);
                        };
                        gallery.appendChild(item);
                    }
                });

                // Cargar contadores de producción en UNA sola petición batch
                const imageIds = images.filter(img => img && img.id).map(img => img.id);
                if (imageIds.length > 0) {
                    loadGalleryProductionCounts(imageIds);
                }

                showVariantImage(variant, images, 0);
            } else {
                gallery.innerHTML =
                    '<div class="gallery-empty-state"><i class="fas fa-images"></i><p>No hay imágenes.</p></div>';
                if (currentDesign.primaryImage?.file_path) {
                    const ext = currentDesign.primaryImage.file_path.split('.').pop();
                    // Usar thumbnail_medium para visualización, file_path para descarga
                    const displaySrc = currentDesign.primaryImage.thumbnail_medium ?
                        `/storage/${currentDesign.primaryImage.thumbnail_medium}` :
                        `/storage/${currentDesign.primaryImage.file_path}`;
                    const originalSrc = `/storage/${currentDesign.primaryImage.file_path}`;
                    displayMainImage({
                        src: displaySrc,
                        originalSrc: originalSrc,
                        title: variant.name,
                        subtitle: 'Imagen de variante',
                        downloadName: `${currentDesign.name.replace(/\s+/g, '_')}.${ext}`
                    });
                } else {
                    displayMainImage({
                        src: null,
                        originalSrc: null,
                        title: variant.name,
                        subtitle: 'Imagen de variante',
                        downloadName: null
                    });
                }
                currentGalleryImages = [];
            }

            updateEditVariantsButton();
            updateProductionTabCount(); // Actualizar contador de producción en pestaña
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
        // ⭐ INCREMENTAR CONTADOR TAB PRODUCCIÓN INMEDIATAMENTE
        // ============================================
        window.incrementProductionTabCounter = function() {
            const productionCount = document.getElementById('productionTotalCount');
            if (productionCount) {
                const current = parseInt(productionCount.innerText || '0');
                productionCount.innerText = current + 1;

                // Efecto visual
                productionCount.style.transform = 'scale(1.3)';
                productionCount.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                    productionCount.style.transform = 'scale(1)';
                }, 200);
            }

            // También incrementar en la tarjeta del grid
            if (currentDesign && currentDesign.id) {
                const card = document.querySelector(`.design-card[data-design-id="${currentDesign.id}"]`);
                if (card) {
                    const currentExports = parseInt(card.dataset.exports || '0');
                    const newCount = currentExports + 1;
                    card.dataset.exports = newCount;

                    const exportsDiv = card.querySelector('.design-exports');
                    if (exportsDiv) {
                        const numberSpan = exportsDiv.querySelector('.exports-number');
                        const textSpan = exportsDiv.querySelector('.exports-text');
                        if (numberSpan) numberSpan.textContent = newCount;
                        if (textSpan) textSpan.textContent = newCount !== 1 ? 'exportaciones' : 'exportación';

                        // Animación
                        exportsDiv.classList.add('updated');
                        setTimeout(() => exportsDiv.classList.remove('updated'), 400);
                    }
                }

                // Actualizar currentDesign
                currentDesign.exports_count = (currentDesign.exports_count || 0) + 1;
            }
        };

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

    {{-- ============================================
         BÚSQUEDA EN TIEMPO REAL (AJAX)
         ============================================ --}}
    <script>
        (function() {
            // ============================================
            // CONFIGURACIÓN - Web App optimizada
            // ============================================
            const DEBOUNCE_DELAY = 200; // ms - respuesta rápida
            const MIN_CHARS = 1; // Buscar desde 1 carácter
            const SEARCH_URL = '{{ route('admin.search.ajax') }}';
            const ALL_DESIGNS_URL = '{{ route('admin.designs.ajax-list') }}';
            const STORAGE_URL = '{{ asset('storage') }}';
            const DESIGNS_SHOW_URL = '/admin/designs/';
            const INDEX_URL = '{{ route('admin.designs.index') }}';

            // Elementos DOM
            const searchInput = document.getElementById('searchInput');
            const searchIcon = document.getElementById('searchIcon');
            const searchSpinner = document.getElementById('searchSpinner');
            const searchClear = document.getElementById('searchClear');
            const searchForm = document.getElementById('searchForm');
            const designsContainer = document.getElementById('designsContainer');

            let searchTimeout;
            let currentRequest = null;

            // Prevenir submit del formulario cuando hay búsqueda activa
            searchForm.addEventListener('submit', function(e) {
                const term = searchInput.value.trim();
                if (term.length >= MIN_CHARS) {
                    e.preventDefault();
                    performSearch(term);
                }
            });

            // Búsqueda en tiempo real con debounce
            searchInput.addEventListener('input', function() {
                const term = this.value.trim();

                // Mostrar/ocultar botón limpiar
                searchClear.style.display = term.length > 0 ? 'flex' : 'none';

                // Cancelar búsqueda anterior
                clearTimeout(searchTimeout);
                if (currentRequest) {
                    currentRequest.abort();
                    currentRequest = null;
                }

                // Si está vacío, cargar todos los diseños vía AJAX (sin reload)
                if (term.length === 0) {
                    loadAllDesigns();
                    return;
                }

                // Debounce - búsqueda rápida
                searchTimeout = setTimeout(() => {
                    performSearch(term);
                }, DEBOUNCE_DELAY);
            });

            // Limpiar búsqueda con botón X - AJAX sin reload
            searchClear.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                searchClear.style.display = 'none';
                loadAllDesigns();
            });

            // Cargar todos los diseños vía AJAX (Web App - sin reload)
            function loadAllDesigns() {
                showLoading();

                const controller = new AbortController();
                currentRequest = controller;

                // Obtener categoría activa si hay una
                const urlParams = new URLSearchParams(window.location.search);
                const category = urlParams.get('category') || '';
                const url = category ? `${ALL_DESIGNS_URL}?category=${encodeURIComponent(category)}` : ALL_DESIGNS_URL;

                fetch(url, {
                        signal: controller.signal
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderResults(data.data, '');
                            updateURL(''); // Limpiar parámetro search de la URL
                        }
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            console.error('Error al cargar diseños:', error);
                        }
                    })
                    .finally(() => {
                        hideLoading();
                        currentRequest = null;
                    });
            }

            // Mostrar spinner
            function showLoading() {
                searchIcon.style.display = 'none';
                searchSpinner.style.display = 'inline-block';
            }

            // Ocultar spinner
            function hideLoading() {
                searchIcon.style.display = 'inline-block';
                searchSpinner.style.display = 'none';
            }

            // Realizar búsqueda AJAX
            function performSearch(term) {
                showLoading();

                const controller = new AbortController();
                currentRequest = controller;

                fetch(`${SEARCH_URL}?q=${encodeURIComponent(term)}&limit=50`, {
                        signal: controller.signal
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderResults(data.data, term);
                            updateURL(term);
                        }
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            console.error('Error en búsqueda:', error);
                        }
                    })
                    .finally(() => {
                        hideLoading();
                        currentRequest = null;
                    });
            }

            // Renderizar resultados en el grid
            function renderResults(designs, searchTerm) {
                if (designs.length === 0) {
                    designsContainer.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            ${searchTerm
                                ? `No se encontraron diseños para "<strong>${escapeHtml(searchTerm)}</strong>".`
                                : 'No se encontraron diseños con los filtros aplicados.'
                            }
                            <a href="{{ route('admin.designs.create') }}" class="alert-link">¿Deseas crear uno?</a>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="design-grid" id="designGrid">';

                designs.forEach(design => {
                    const imageUrl = design.image || '';
                    const variantsCount = design.variants_count || 0;
                    // Obtener exports_count del diseño (si viene en la respuesta)
                    const exportsCount = design.exports_count || 0;

                    html += `
                        <div class="design-card" data-design-id="${design.id}"
                            data-description="${escapeHtml(design.description || 'Sin descripción')}"
                            data-name="${escapeHtml(design.name)}"
                            data-variants="${variantsCount}"
                            data-exports="${exportsCount}"
                            data-image="${imageUrl}"
                            data-edit-url="/admin/designs/${design.id}/edit"
                            data-delete-url="/admin/designs/${design.id}">

                            <div class="design-image">
                                ${imageUrl
                                    ? `<img src="${imageUrl}" alt="${escapeHtml(design.name)}" loading="lazy"
                                                                                                                                                                        onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                                                                                                                       <div class="no-image" style="display: none;">
                                                                                                                                                                           <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                                                                                                                                                                           <span>Sin imagen</span>
                                                                                                                                                                       </div>`
                                    : `<div class="no-image">
                                                                                                                                                                           <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                                                                                                                                                                           <span>Sin imagen</span>
                                                                                                                                                                       </div>`
                                }
                            </div>

                            <div class="design-body text-center">
                                <h6 class="design-title">${escapeHtml(capitalizeFirst(design.name))}</h6>
                                <div class="design-variants">
                                    ${variantsCount} variante${variantsCount !== 1 ? 's' : ''}
                                </div>
                                <div class="design-exports" data-design-id="${design.id}">
                                    <i class="fas fa-industry"></i>
                                    <span class="exports-number">${exportsCount}</span>
                                    <span class="exports-text">exportación${exportsCount !== 1 ? 'es' : ''}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                designsContainer.innerHTML = html;

                // Re-bind event listeners para las nuevas cards
                bindCardEvents();
            }

            // Re-vincular eventos a las cards después de renderizar
            function bindCardEvents() {
                document.querySelectorAll('.design-card').forEach(card => {
                    card.addEventListener('click', function() {
                        if (typeof isProcessingDelete !== 'undefined' && isProcessingDelete) return;

                        const designId = this.dataset.designId;
                        const showUrl = `${DESIGNS_SHOW_URL}${designId}`;

                        // Mostrar modal y loader
                        $('#designModal').modal('show');
                        const loader = document.getElementById('modalLoader');
                        if (loader) {
                            loader.style.setProperty('display', 'flex', 'important');
                            loader.style.opacity = '1';
                        }

                        // Limpiar vista anterior
                        if (typeof clearModal === 'function') {
                            clearModal();
                        }

                        // Fetch diseño completo
                        fetch(showUrl)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && typeof renderModal === 'function') {
                                    renderModal(data.design);
                                } else if (typeof showErrorAlert === 'function') {
                                    showErrorAlert('Error', 'No se pudo cargar el diseño');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                if (typeof showErrorAlert === 'function') {
                                    showErrorAlert('Error de conexión',
                                        'Por favor verifica tu conexión');
                                }
                            })
                            .finally(() => {
                                const loaderEl = document.getElementById('modalLoader');
                                if (loaderEl) loaderEl.style.setProperty('display', 'none',
                                    'important');
                            });
                    });
                });
            }

            // Actualizar URL sin recargar (para compartir/bookmarks)
            function updateURL(term) {
                const url = new URL(window.location);
                if (term) {
                    url.searchParams.set('search', term);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.replaceState({}, '', url);
            }

            // Helpers
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function capitalizeFirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

        })();
    </script>
@stop
