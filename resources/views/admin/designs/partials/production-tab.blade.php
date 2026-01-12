{{-- ============================================= --}}
{{-- PRODUCTION TAB - V5 COMPLETO --}}
{{-- Filtros, agrupamiento por variante, acordeones --}}
{{-- ============================================= --}}

{{-- Fix z-index for SweetAlert2 to appear above Bootstrap modals --}}
<style>
    .swal2-container-high-zindex {
        z-index: 99999 !important;
    }
</style>

{{-- Header Compacto --}}
<div class="production-header-compact">
    <h5 class="production-title-compact">
        <i class="fas fa-industry"></i>
        Archivos de Producción
    </h5>
    <span class="production-context-compact" id="productionContext"></span>
</div>

{{-- Content --}}
<div class="production-content">
    {{-- Loading --}}
    <div id="productionLoading" class="production-state">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="mt-3 text-muted">Cargando archivos...</p>
    </div>

    {{-- Empty State - Ya no se usa, se muestra directamente el formulario --}}
    <div id="productionEmpty" class="production-state" style="display: none;">
        {{-- Este div se mantiene por compatibilidad pero el JS mostrará el formulario directamente --}}
    </div>

    {{-- Lista de Exportaciones con Filtros --}}
    <div id="productionList" class="production-list-container" style="display: none;">

        {{-- Botón Agregar Producción (ancho completo, arriba de todo) --}}
        <div class="add-production-section">
            <button type="button" class="btn-add-production-full" id="btnAddNewExport">
                <i class="fas fa-plus"></i>
                <span>Agregar archivo de producción</span>
            </button>
        </div>

        {{-- Resumen de Estados (clicables para filtrar) --}}
        <div class="summary-section" id="summarySection">
            <div class="summary-badges">
                <button type="button" class="summary-badge active" data-filter="">
                    <span class="summary-icon">📊</span>
                    <span class="summary-count" id="summaryTotal">0</span>
                    <span class="summary-label">Total</span>
                </button>
                <button type="button" class="summary-badge" data-filter="borrador">
                    <span class="summary-icon">🟤</span>
                    <span class="summary-count" id="summaryBorrador">0</span>
                    <span class="summary-label">Borrador</span>
                </button>
                <button type="button" class="summary-badge" data-filter="pendiente">
                    <span class="summary-icon">🟡</span>
                    <span class="summary-count" id="summaryPendiente">0</span>
                    <span class="summary-label">Pendiente</span>
                </button>
                <button type="button" class="summary-badge" data-filter="aprobado">
                    <span class="summary-icon">🟢</span>
                    <span class="summary-count" id="summaryAprobado">0</span>
                    <span class="summary-label">Aprobado</span>
                </button>
                <button type="button" class="summary-badge" data-filter="archivado">
                    <span class="summary-icon">⚫</span>
                    <span class="summary-count" id="summaryArchivado">0</span>
                    <span class="summary-label">Archivado</span>
                </button>
            </div>
        </div>

        {{-- Filtro de Variante (Solo en contexto diseño, debajo de estados) --}}
        <div class="variant-filter-section" id="variantFilterSection" style="display: none;">
            <div class="variant-filter-row">
                <select id="filterVariant" class="filter-select-inline">
                    <option value="">Todas las variantes</option>
                </select>
                <button type="button" id="btnClearFilters" class="btn-clear-inline" title="Limpiar filtro">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Contenedor de grupos/lista --}}
        <div class="exports-groups-container" id="exportsGroupsContainer"></div>
    </div>

    {{-- Formulario --}}
    <div id="productionForm" class="production-form-container" style="display: none;">
        <div class="form-header-bar">
            <button type="button" id="btnBackToList" class="btn-back">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span class="form-title" id="formTitle">Nueva Exportación</span>
        </div>

        <div class="form-scroll-area">
            <form id="exportForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="design_id" id="exportDesignId">
                <input type="hidden" name="design_variant_id" id="exportVariantId">
                <input type="hidden" name="image_id" id="exportImageId">
                <input type="hidden" name="export_id" id="exportEditId">
                <input type="hidden" name="colors_detected" id="hiddenColorsDetected" value="[]">
                <input type="hidden" name="auto_read_success" id="hiddenAutoRead" value="1">

                {{-- Indicador de imagen vinculada --}}
                <div id="linkedImageIndicator" style="display: none;"></div>

                {{-- Upload Zone --}}
                <div class="upload-zone" id="uploadZone">
                    <input type="file" id="exportFile" name="file" class="upload-input"
                        accept=".pes,.dst,.exp,.jef,.vp3,.vip,.xxx,.hus,.pec,.pcs,.sew,.shv,.tap,.u01">

                    <div class="upload-content" id="uploadContent">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-text">Arrastra tu archivo aquí</p>
                        <p class="upload-subtext">o haz clic para seleccionar</p>
                        <div class="upload-formats">
                            PES, DST, EXP, JEF, VP3, VIP, XXX
                        </div>
                    </div>

                    <div class="upload-analyzing" id="uploadAnalyzing" style="display: none;">
                        <div class="spinner-border text-primary spinner-border-sm"></div>
                        <span class="ml-2">Analizando archivo...</span>
                    </div>

                    <div class="upload-success" id="uploadSuccess" style="display: none;">
                        <div class="upload-success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="upload-filename" id="uploadFileName"></span>
                        <button type="button" class="btn-remove-file" id="btnRemoveFile">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="upload-error" id="uploadError" style="display: none;">
                        <div class="upload-error-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <span class="upload-filename" id="uploadErrorFileName"></span>
                        <button type="button" class="btn-remove-file" id="btnRemoveFileError">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- Vista Previa SVG - Thumbnail Compacto --}}
                <div class="svg-preview-section text-center" id="svgPreviewSection" style="display: none;">
                    <div class="svg-thumbnail-row">
                        <div class="svg-thumbnail-card" id="svgThumbnailCard" title="Clic para ampliar">
                            <div class="svg-thumbnail-inner" id="svgThumbnailContainer">
                                {{-- SVG thumbnail se inyecta aquí --}}
                            </div>
                            <div class="svg-thumbnail-hover">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </div>
                        <div class="svg-thumbnail-info">
                            <span class="svg-thumbnail-label">Vista Previa</span>
                            <span class="svg-thumbnail-hint">Clic para ampliar</span>
                        </div>
                    </div>
                </div>

                {{-- Alerta de entrada manual --}}
                <div class="alert-manual-entry" id="manualEntryAlert" style="display: none;">
                    <i class="fas fa-keyboard"></i>
                    <span>Error de lectura automática. Ingresa los datos manualmente.</span>
                </div>

                {{-- Datos Técnicos --}}
                <div class="technical-data-section" id="technicalData" style="display: none;">
                    <h6 class="section-label-large">DATOS TÉCNICOS</h6>
                    <div class="tech-cards">
                        <div class="tech-card" id="techCardStitches">
                            <i class="fas fa-hashtag"></i>
                            <span class="tech-card-label">Puntadas</span>
                            <input type="number" class="tech-card-input" id="inputStitches" name="stitches_count"
                                placeholder="0">
                        </div>
                        <div class="tech-card" id="techCardColors">
                            <i class="fas fa-palette"></i>
                            <span class="tech-card-label">Colores</span>
                            <input type="number" class="tech-card-input" id="inputColors" name="colors_count"
                                placeholder="0">
                        </div>
                        <div class="tech-card" id="techCardWidth">
                            <i class="fas fa-arrows-alt-h"></i>
                            <span class="tech-card-label">Ancho (mm)</span>
                            <input type="number" class="tech-card-input" id="inputWidth" name="width_mm"
                                placeholder="0" step="0.1">
                        </div>
                        <div class="tech-card" id="techCardHeight">
                            <i class="fas fa-arrows-alt-v"></i>
                            <span class="tech-card-label">Alto (mm)</span>
                            <input type="number" class="tech-card-input" id="inputHeight" name="height_mm"
                                placeholder="0" step="0.1">
                        </div>
                    </div>
                </div>

                {{-- Colores Detectados --}}
                <div class="colors-preview-section" id="colorsPreview" style="display: none;">
                    <h6 class="section-label-large">COLORES DETECTADOS</h6>
                    <div class="color-swatches" id="colorSwatches"></div>
                </div>

                {{-- Campos del Formulario --}}
                <div class="form-fields-section" id="formFields" style="display: none;">
                    <h6 class="section-label-large">INFORMACIÓN DE APLICACIÓN</h6>

                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">
                                Tipo de Aplicación <span class="required-mark">*</span>
                            </label>
                            <select id="applicationType" name="application_type" class="field-control" required>
                                <option value="">Seleccionar...</option>
                                @php
                                    $applicationTypes = \App\Models\Application_types::activos()
                                        ->orderBy('nombre_aplicacion')
                                        ->get();
                                @endphp
                                @foreach ($applicationTypes as $tipo)
                                    <option value="{{ $tipo->slug }}">{{ $tipo->nombre_aplicacion }}</option>
                                @endforeach
                            </select>
                            <span class="field-error" id="errorApplicationType"></span>
                        </div>
                        <div class="field-group">
                            <label class="field-label">
                                Etiqueta / Nombre <span class="required-mark">*</span>
                            </label>
                            <input type="text" id="applicationLabel" name="application_label"
                                class="field-control" placeholder="Ej: Logo frontal" required maxlength="100">
                            <span class="field-error" id="errorApplicationLabel"></span>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Descripción de Ubicación</label>
                        <input type="text" id="placementDescription" name="placement_description"
                            class="field-control" placeholder="Ej: Centrado a 5cm del cuello" maxlength="255">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Notas Adicionales</label>
                        <textarea id="exportNotes" name="notes" class="field-control field-textarea" rows="2"
                            placeholder="Observaciones para producción..." maxlength="1000"></textarea>
                    </div>
                </div>

                {{-- Error General --}}
                <div class="form-error-alert" id="formError" style="display: none;"></div>

            </form>
        </div>

        {{-- Acciones --}}
        <div class="form-actions" id="formActions" style="display: none;">
            <button type="button" id="btnCancelExport" class="btn-premium btn-premium-outline">
                Cancelar
            </button>
            <button type="button" id="btnSaveExport" class="btn-premium btn-premium-primary" disabled>
                <i class="fas fa-save"></i>
                <span id="btnSaveText">Guardar</span>
            </button>
        </div>
    </div>
</div>

{{-- Modal de Vista Previa SVG --}}
<div class="svg-modal-overlay" id="svgModalOverlay" style="display: none;">
    <div class="svg-modal-container">
        <div class="svg-modal-header">
            <h5 class="svg-modal-title">
                <i class="fas fa-eye"></i>
                Vista Previa del Bordado
            </h5>
            <button type="button" class="svg-modal-close" id="btnCloseSvgModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="svg-modal-body">
            <div class="svg-modal-canvas" id="svgModalCanvas">
                {{-- SVG completo se inyecta aquí --}}
            </div>
        </div>
        <div class="svg-modal-toolbar">
            <div class="svg-modal-tools-left">
                <button type="button" class="svg-modal-btn" id="btnModalZoomOut" title="Alejar">
                    <i class="fas fa-minus"></i>
                </button>
                <span class="svg-zoom-level" id="svgZoomLabel">100%</span>
                <button type="button" class="svg-modal-btn" id="btnModalZoomIn" title="Acercar">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="svg-modal-btn" id="btnModalZoomReset" title="Centrar">
                    <i class="fas fa-compress-arrows-alt"></i>
                </button>
            </div>
            <div class="svg-modal-tools-right">
                <button type="button" class="svg-modal-btn svg-modal-btn-primary" id="btnModalDownload"
                    title="Descargar SVG">
                    <i class="fas fa-download"></i>
                    <span>Descargar SVG</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Detalles (Overlay centrado) --}}
<div class="detail-overlay" id="exportDetailOverlay" style="display: none;">
    <div class="detail-modal-centered">
        <button type="button" class="modal-close-premium" id="btnCloseDetailOverlay" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>

        <div class="detail-modal-body">
            {{-- Header del archivo --}}
            <div class="detail-file-header">
                <div class="detail-file-icon" id="detailFileIcon">
                    <i class="fas fa-file-code"></i>
                </div>
                <div class="detail-file-info">
                    <h5 class="detail-file-name" id="detailFileName">archivo.pes</h5>
                    <div class="detail-file-meta">
                        <span class="file-size" id="detailFileSize"
                            style="font-size: 16px; color: #6b7280; font-weight: 500;">125
                            KB</span>
                        <span class="file-format-tag" id="detailFileFormat" style="display:none;">PES</span>
                    </div>
                </div>
            </div>

            {{-- Status badge --}}
            <div class="detail-status-section">
                <div class="status-badge-detail" id="detailStatusBadge">
                    <span class="status-emoji" id="detailStatusEmoji">🟤</span>
                    <span class="status-text" id="detailStatusText">Borrador</span>
                </div>
            </div>

            {{-- Datos técnicos --}}
            <div class="detail-tech-section">
                <h6 class="section-title-sm">Datos Técnicos</h6>
                <div class="tech-grid-detail">
                    <div class="tech-item-detail">
                        <i class="fas fa-hashtag"></i>
                        <div>
                            <span class="tech-value-lg" id="detailStitches">0</span>
                            <span class="tech-label-sm">Puntadas</span>
                        </div>
                    </div>
                    <div class="tech-item-detail">
                        <i class="fas fa-palette"></i>
                        <div>
                            <span class="tech-value-lg" id="detailColors">0</span>
                            <span class="tech-label-sm">Colores</span>
                        </div>
                    </div>
                    <div class="tech-item-detail">
                        <i class="fas fa-arrows-alt-h"></i>
                        <div>
                            <span class="tech-value-lg" id="detailWidth">0</span>
                            <span class="tech-label-sm">Ancho mm</span>
                        </div>
                    </div>
                    <div class="tech-item-detail">
                        <i class="fas fa-arrows-alt-v"></i>
                        <div>
                            <span class="tech-value-lg" id="detailHeight">0</span>
                            <span class="tech-label-sm">Alto mm</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colores --}}
            <div class="detail-colors-section" id="detailColorsSection" style="display: none; margin-top: 24px;">
                <h6 class="section-title-sm">Colores Detectados</h6>
                <div id="detailColorSwatches" class="color-grid-lg"></div>
            </div>

            {{-- Información de aplicación --}}
            <div class="detail-app-section">
                <h6 class="section-title-sm">Aplicación</h6>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-key">Tipo:</span>
                        <span class="info-val" id="detailAppType">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-key">Etiqueta:</span>
                        <span class="info-val" id="detailAppLabel">-</span>
                    </div>
                    <div class="info-row" id="detailPlacementRow" style="display: none;">
                        <span class="info-key">Ubicación:</span>
                        <span class="info-val" id="detailPlacement">-</span>
                    </div>
                    <div class="info-row" id="detailNotesRow" style="display: none;">
                        <span class="info-key">Notas:</span>
                        <span class="info-val info-notes" id="detailNotes">-</span>
                    </div>
                </div>
            </div>

            {{-- Metadata --}}
            <div class="detail-meta-section">
                <div class="meta-row">
                    <i class="fas fa-user"></i>
                    <span>Creado por: <strong id="detailCreator">-</strong></span>
                </div>
                <div class="meta-row">
                    <i class="fas fa-calendar"></i>
                    <span>Fecha: <strong id="detailDate">-</strong></span>
                </div>
                <div class="meta-row" id="detailApproverRow" style="display: none;">
                    <i class="fas fa-check-circle"></i>
                    <span>Aprobado por: <strong id="detailApprover">-</strong></span>
                </div>
            </div>
        </div>

        {{-- Footer con acciones --}}
        <div class="detail-modal-footer">
            <div class="footer-actions-main">
                <a href="#" id="btnDetailDownload" class="btn-premium btn-premium-secondary" download>
                    <i class="fas fa-download"></i>
                    <span>Descargar</span>
                </a>
                <button type="button" id="btnDetailEdit" class="btn-premium btn-premium-primary">
                    <i class="fas fa-edit"></i>
                    <span>Editar</span>
                </button>
            </div>

            <div class="footer-actions-status">
                <button type="button" id="btnSendToReview" class="btn-premium btn-premium-warning"
                    style="display: none;">
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar a Revisión</span>
                </button>
                <button type="button" id="btnDetailApprove" class="btn-premium btn-premium-success"
                    style="display: none;">
                    <i class="fas fa-check-circle"></i>
                    <span>Aprobar</span>
                </button>
                <button type="button" id="btnDetailReject" class="btn-premium btn-premium-danger-outline"
                    style="display: none;">
                    <i class="fas fa-times-circle"></i>
                    <span>Rechazar</span>
                </button>
                <button type="button" id="btnDetailArchive" class="btn-premium btn-premium-dark"
                    style="display: none;">
                    <i class="fas fa-archive"></i>
                    <span>Archivar</span>
                </button>
                <button type="button" id="btnDetailRestore" class="btn-premium btn-premium-info"
                    style="display: none;">
                    <i class="fas fa-undo"></i>
                    <span>Restaurar</span>
                </button>
            </div>
        </div>

        {{-- Workflow Indicator --}}
        <div class="workflow-indicator" id="workflowIndicator">
            <div class="workflow-step" data-step="borrador">
                <div class="workflow-dot"></div>
                <span>Borrador</span>
            </div>
            <div class="workflow-line"></div>
            <div class="workflow-step" data-step="pendiente">
                <div class="workflow-dot"></div>
                <span>Revisión</span>
            </div>
            <div class="workflow-line"></div>
            <div class="workflow-step" data-step="aprobado">
                <div class="workflow-dot"></div>
                <span>Aprobado</span>
            </div>
            <div class="workflow-line"></div>
            <div class="workflow-step" data-step="archivado">
                <div class="workflow-dot"></div>
                <span>Archivado</span>
            </div>
        </div>
    </div>
</div>

{{-- Template para items --}}
<template id="exportItemTemplate">
    <div class="export-item" data-id="{id}" data-export='{export_json}'>
        <div class="export-status-indicator status-{status}"></div>
        <div class="export-item-content" role="button">
            {preview_html}
            <div class="export-item-info">
                <div class="export-item-name">{application_label}</div>
                <div class="export-item-meta">
                    <span class="meta-format">{file_format}</span>
                    <span class="meta-sep">•</span>
                    <span class="meta-stitches">{stitches_formatted} pts</span>
                    <span class="meta-sep">•</span>
                    <span class="meta-colors">{colors_count} col</span>
                    <span class="meta-sep">•</span>
                    <span class="meta-dimensions">{dimensions}</span>
                </div>
            </div>
            <div class="export-item-status">
                <span class="status-badge-emoji status-{status}">{status_emoji} {status_label}</span>
            </div>
        </div>
        <div class="export-item-actions">
            <button type="button" class="btn-action-icon btn-action-view btn-view-export" data-id="{id}"
                title="Ver detalles">
                <i class="fas fa-eye"></i>
            </button>
            <a href="{download_url}" class="btn-action-icon btn-action-download" title="Descargar" download>
                <i class="fas fa-download"></i>
            </a>
            <button type="button" class="btn-action-icon btn-action-delete btn-delete-export" data-id="{id}"
                title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<style>
    /* ============================================= */
    /* VARIABLES CSS */
    /* ============================================= */
    .production-header,
    .production-content,
    .production-footer,
    .detail-overlay {
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --primary-light: #dbeafe;
        --success: #10b981;
        --success-hover: #059669;
        --success-light: #d1fae5;
        --warning: #f59e0b;
        --warning-hover: #d97706;
        --warning-light: #fef3c7;
        --danger: #ef4444;
        --danger-hover: #dc2626;
        --danger-light: #fee2e2;
        --info: #06b6d4;
        --info-light: #cffafe;
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
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 16px;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* ============================================= */
    /* LAYOUT PRINCIPAL */
    /* ============================================= */
    .production-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
        position: relative;
    }

    /* ============================================= */
    /* HEADER COMPACTO */
    /* ============================================= */
    .production-header-compact {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 8px;
        margin-bottom: 8px;
        border-bottom: 1px solid var(--gray-200);
    }

    .production-title-compact {
        font-size: 15px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .production-title-compact i {
        color: var(--primary);
        font-size: 14px;
    }

    .production-context-compact {
        font-size: 13px;
        color: var(--gray-500);
    }

    .production-context-compact strong {
        color: var(--gray-700);
    }

    /* ============================================= */
    /* FILTRO DE VARIANTE (INLINE) */
    /* ============================================= */
    .variant-filter-section {
        margin-bottom: 12px;
    }

    .variant-filter-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-select-inline {
        flex: 1;
        max-width: 200px;
        padding: 6px 10px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 12px;
        color: var(--gray-700);
        background: #fff;
        cursor: pointer;
    }

    .filter-select-inline:focus {
        outline: none;
        border-color: var(--primary);
    }

    .btn-clear-inline {
        width: 28px;
        height: 28px;
        border: 1px solid var(--gray-300);
        background: #fff;
        border-radius: var(--radius-sm);
        color: var(--gray-400);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        transition: all 0.15s ease;
    }

    .btn-clear-inline:hover {
        background: var(--danger-light);
        border-color: var(--danger);
        color: var(--danger);
    }

    /* Legacy support */
    .filters-section {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 12px;
        margin-bottom: 12px;
    }

    .filters-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 120px;
    }

    .filter-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .filter-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 13px;
        color: var(--gray-700);
        background: #fff;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .btn-clear-filters {
        width: 36px;
        height: 36px;
        border: 1px solid var(--gray-300);
        background: #fff;
        border-radius: var(--radius-sm);
        color: var(--gray-500);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }

    .btn-clear-filters:hover {
        background: var(--danger-light);
        border-color: var(--danger);
        color: var(--danger);
    }

    /* ============================================= */
    /* BOTÓN AGREGAR PRODUCCIÓN (ANCHO COMPLETO) */
    /* ============================================= */
    .add-production-section {
        margin-bottom: 12px;
    }

    .btn-add-production-full {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 20px;
        background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
        color: #fff;
        border: none;
        border-radius: var(--radius-md);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .btn-add-production-full:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
    }

    .btn-add-production-full i {
        font-size: 12px;
    }

    /* ============================================= */
    /* RESUMEN DE ESTADOS (COMPACTO) */
    /* ============================================= */
    .summary-section {
        margin-bottom: 10px;
    }

    .summary-badges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }

    .summary-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 8px 12px;
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 58px;
        flex: 1;
        max-width: 100px;
    }

    .summary-badge:hover {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .summary-badge.active {
        border-color: var(--primary);
        background: var(--primary);
    }

    .summary-badge.active .summary-count,
    .summary-badge.active .summary-label {
        color: #fff;
    }

    .summary-icon {
        font-size: 14px;
        margin-bottom: 2px;
    }

    .summary-count {
        font-size: 16px;
        font-weight: 700;
        color: var(--gray-800);
        line-height: 1;
    }

    .summary-label {
        font-size: 9px;
        font-weight: 500;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.2px;
        margin-top: 1px;
    }

    /* ============================================= */
    /* GRUPOS ACORDEÓN */
    /* ============================================= */
    .exports-groups-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .export-group {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .export-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: var(--gray-50);
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }

    .export-group-header:hover {
        background: var(--gray-100);
    }

    .group-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .group-thumbnail {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        object-fit: cover;
        border: 1px solid var(--gray-200);
    }

    .group-thumbnail-placeholder {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        background: var(--gray-100);
        border: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-400);
    }

    .group-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .group-type-badge {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        width: fit-content;
    }

    .group-type-badge.type-design {
        background: var(--primary-light);
        color: var(--primary);
    }

    .group-type-badge.type-variant {
        background: var(--success-light);
        color: #065f46;
    }

    .group-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-800);
    }

    .group-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .group-count {
        font-size: 12px;
        color: var(--gray-500);
        font-weight: 500;
    }

    .group-toggle-icon {
        font-size: 12px;
        color: var(--gray-400);
        transition: transform 0.2s ease;
    }

    .export-group-header.collapsed .group-toggle-icon {
        transform: rotate(-90deg);
    }

    .export-group-body {
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .export-group-body.collapsed {
        display: none;
    }

    /* Botón de acción principal mini (contextual al estado) */
    .btn-action-primary-mini {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .btn-action-primary-mini[data-status="borrador"] {
        background: var(--warning-light);
        color: var(--warning);
    }

    .btn-action-primary-mini[data-status="borrador"]:hover {
        background: var(--warning);
        color: #fff;
    }

    .btn-action-primary-mini[data-status="pendiente"] {
        background: var(--success-light);
        color: var(--success);
    }

    .btn-action-primary-mini[data-status="pendiente"]:hover {
        background: var(--success);
        color: #fff;
    }

    .btn-action-primary-mini[data-status="aprobado"] {
        background: var(--gray-200);
        color: var(--gray-600);
    }

    .btn-action-primary-mini[data-status="aprobado"]:hover {
        background: var(--gray-700);
        color: #fff;
    }

    .btn-action-primary-mini[data-status="archivado"] {
        background: var(--info-light);
        color: var(--info);
    }

    .btn-action-primary-mini[data-status="archivado"]:hover {
        background: var(--info);
        color: #fff;
    }

    .status-badge-mini {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        margin-right: 4px;
    }

    .status-badge-mini.status-borrador {
        background: var(--gray-100);
        color: var(--gray-600);
    }

    .status-badge-mini.status-pendiente {
        background: var(--warning-light);
        color: #92400e;
    }

    .status-badge-mini.status-aprobado {
        background: var(--success-light);
        color: #065f46;
    }

    .status-badge-mini.status-archivado {
        background: var(--gray-800);
        color: #fff;
    }

    .export-item.filtered-out {
        display: none !important;
    }

    /* ============================================= */
    /* RESPONSIVE - WEB APP OPTIMIZADO */
    /* ============================================= */

    /* Tablet landscape y desktop pequeño */
    @media (max-width: 992px) {
        .summary-badge {
            padding: 6px 8px;
            min-width: 50px;
        }

        .summary-count {
            font-size: 14px;
        }

        .summary-label {
            font-size: 9px;
        }
    }

    /* Tablet portrait */
    @media (max-width: 768px) {
        .summary-badges {
            gap: 4px;
        }

        .summary-badge {
            padding: 6px 4px;
            min-width: 45px;
            max-width: none;
        }

        .summary-icon {
            font-size: 12px;
        }

        .summary-count {
            font-size: 13px;
        }

        .summary-label {
            font-size: 8px;
            letter-spacing: -0.3px;
        }
    }

    /* Móvil grande */
    @media (max-width: 600px) {
        .add-production-section {
            margin-bottom: 8px;
        }

        .btn-add-production-full {
            padding: 12px 16px;
            font-size: 13px;
        }

        .btn-add-production-full i {
            font-size: 14px;
        }

        .summary-badges {
            justify-content: space-between;
            gap: 3px;
        }

        .summary-badge {
            min-width: 0;
            flex: 1;
            padding: 5px 3px;
            max-width: none;
        }

        .summary-icon {
            font-size: 11px;
            margin-bottom: 1px;
        }

        .summary-count {
            font-size: 12px;
        }

        .summary-label {
            font-size: 7px;
            letter-spacing: -0.5px;
        }

        .filters-row {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .btn-clear-filters {
            align-self: flex-end;
        }
    }

    /* Móvil pequeño */
    @media (max-width: 420px) {
        .btn-add-production-full {
            padding: 10px 12px;
            font-size: 12px;
        }

        .summary-badges {
            gap: 2px;
        }

        .summary-badge {
            padding: 4px 2px;
            border-radius: 4px;
        }

        .summary-icon {
            font-size: 10px;
        }

        .summary-count {
            font-size: 11px;
        }

        .summary-label {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: -0.3px;
        }
    }

    /* Pantallas muy pequeñas - ocultar labels, solo iconos y números */
    @media (max-width: 340px) {
        .summary-label {
            display: none;
        }

        .summary-badge {
            padding: 6px 4px;
        }

        .summary-count {
            font-size: 12px;
        }

        .btn-add-production-full span {
            font-size: 11px;
        }
    }

    /* ============================================= */
    /* ESTADOS */
    /* ============================================= */
    .production-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 20px;
        text-align: center;
    }

    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-light) 0%, #eff6ff 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .empty-state-icon i {
        font-size: 32px;
        color: var(--primary);
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 8px;
    }

    .empty-state-text {
        font-size: 14px;
        color: var(--gray-500);
        margin-bottom: 24px;
    }

    /* ============================================= */
    /* BOTONES PREMIUM */
    /* ============================================= */
    .btn-premium {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none;
        min-height: 52px;
        /* Standardize height with design-actions */
    }

    .btn-premium-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }

    .btn-premium-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        color: #fff;
        text-decoration: none;
    }

    .btn-premium-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
        border: 1px solid var(--gray-200);
    }

    .btn-premium-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
        text-decoration: none;
    }

    .btn-premium-outline {
        background: transparent;
        border: 2px solid var(--gray-300);
        /* Match thickness with danger button */
        color: var(--gray-700);
    }

    .btn-premium-outline:hover {
        background: var(--gray-100);
        border-color: var(--gray-400);
    }

    .btn-premium-success {
        background: linear-gradient(135deg, var(--success) 0%, var(--success-hover) 100%);
        color: #fff;
    }

    .btn-premium-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-premium-warning {
        background: linear-gradient(135deg, var(--warning) 0%, var(--warning-hover) 100%);
        color: #fff;
    }

    .btn-premium-warning:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-premium-danger-outline {
        background: transparent;
        border: 1.5px solid var(--danger);
        color: var(--danger);
    }

    .btn-premium-danger-outline:hover {
        background: var(--danger);
        color: #fff;
    }

    .btn-premium-dark {
        background: linear-gradient(135deg, var(--gray-700) 0%, var(--gray-800) 100%);
        color: #fff;
    }

    .btn-premium-dark:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(55, 65, 81, 0.3);
    }

    .btn-premium-info {
        background: linear-gradient(135deg, var(--info) 0%, #0891b2 100%);
        color: #fff;
    }

    .btn-premium-info:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    }

    .btn-premium.btn-block {
        width: 100%;
    }

    /* ============================================= */
    /* LISTA DE EXPORTACIONES */
    /* ============================================= */
    .production-list-container {
        flex: 1;
        overflow-y: auto;
    }

    .exports-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .export-item {
        display: flex;
        align-items: stretch;
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .export-item:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
    }

    .export-status-indicator {
        width: 5px;
        flex-shrink: 0;
        align-self: stretch;
    }

    .export-status-indicator.status-borrador {
        background: var(--gray-400);
    }

    .export-status-indicator.status-pendiente {
        background: var(--warning);
    }

    .export-status-indicator.status-aprobado {
        background: var(--success);
    }

    .export-status-indicator.status-archivado {
        background: var(--gray-800);
    }

    .export-item-content {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        cursor: pointer;
    }

    .export-item-icon {
        width: 40px;
        height: 40px;
        background: var(--primary-light);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .export-item-icon i {
        font-size: 18px;
        color: var(--primary);
    }

    /* Miniatura de imagen en el item */
    .export-item-thumbnail {
        width: 56px;
        height: 56px;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        overflow: hidden;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
    }

    .export-item-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .export-item-thumbnail.no-image {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .export-item-thumbnail.no-image i {
        font-size: 20px;
        color: #94a3b8;
    }

    .export-item-info {
        flex: 1;
        min-width: 0;
    }

    .export-item-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-800);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .export-item-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 12px;
        color: var(--gray-500);
        margin-top: 2px;
    }

    .meta-sep {
        opacity: 0.5;
    }

    .export-item-status {
        flex-shrink: 0;
        margin-right: 8px;
    }

    /* Status Badges con Emojis */
    .status-badge-emoji {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 14px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-badge-emoji.status-borrador {
        background: var(--gray-100);
        color: var(--gray-600);
    }

    .status-badge-emoji.status-pendiente {
        background: var(--warning-light);
        color: #92400e;
    }

    .status-badge-emoji.status-aprobado {
        background: var(--success-light);
        color: #065f46;
    }

    .status-badge-emoji.status-archivado {
        background: var(--gray-800);
        color: #fff;
    }

    /* Botones de acción */
    .export-item-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px 8px 0;
        flex-shrink: 0;
    }

    .btn-action-icon {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 14px;
    }

    .btn-action-view {
        background: var(--primary-light);
        color: var(--primary);
    }

    .btn-action-view:hover {
        background: var(--primary);
        color: #fff;
        transform: scale(1.05);
    }

    .btn-action-download {
        background: var(--info-light);
        color: var(--info);
    }

    .btn-action-download:hover {
        background: var(--info);
        color: #fff;
        transform: scale(1.05);
    }

    .btn-action-delete {
        background: var(--danger-light);
        color: var(--danger);
    }

    .btn-action-delete:hover {
        background: var(--danger);
        color: #fff;
        transform: scale(1.05);
    }

    /* ============================================= */
    /* FORMULARIO */
    /* ============================================= */
    .production-form-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .form-header-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 14px;
        margin-bottom: 14px;
        border-bottom: 1px solid var(--gray-200);
        flex-shrink: 0;
    }

    .btn-back {
        width: 36px;
        height: 36px;
        border: none;
        background: var(--gray-100);
        color: var(--gray-600);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-back:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .form-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
    }

    .form-scroll-area {
        flex: 1;
        overflow-y: auto;
        padding: 0px 10px 0px 10px;
    }

    /* Upload Zone */
    .upload-zone {
        border: 2px dashed var(--gray-300);
        border-radius: var(--radius-md);
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 16px;
        position: relative;
    }

    .upload-zone:hover {
        border-color: var(--primary);
        background: rgba(37, 99, 235, 0.02);
    }

    .upload-zone.drag-over {
        border-color: var(--primary);
        background: rgba(37, 99, 235, 0.05);
    }

    .upload-zone.has-file {
        border-style: solid;
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.05);
    }

    .upload-zone.has-error {
        border-style: solid;
        border-color: var(--danger);
        background: var(--danger-light);
    }

    .upload-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        width: 56px;
        height: 56px;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
    }

    .upload-icon i {
        font-size: 24px;
        color: var(--primary);
    }

    .upload-text {
        font-size: 15px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 4px;
    }

    .upload-subtext {
        font-size: 13px;
        color: var(--gray-500);
        margin-bottom: 12px;
    }

    .upload-formats {
        font-size: 11px;
        color: var(--gray-400);
        font-family: 'SF Mono', 'Consolas', monospace;
    }

    .upload-analyzing {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .upload-success,
    .upload-error {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
    }

    .upload-success-icon {
        color: var(--success);
        font-size: 20px;
    }

    .upload-error-icon {
        color: var(--danger);
        font-size: 20px;
    }

    .upload-filename {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-700);
        text-align: left;
    }

    .btn-remove-file {
        width: 28px;
        height: 28px;
        border: none;
        background: var(--gray-200);
        color: var(--gray-600);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .btn-remove-file:hover {
        background: var(--danger);
        color: #fff;
    }

    /* ============================================= */
    /* SVG THUMBNAIL COMPACTO */
    /* ============================================= */
    .svg-preview-section {
        margin-bottom: 16px;
    }

    .svg-thumbnail-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
    }

    .svg-thumbnail-card {
        position: relative;
        width: 250px;
        height: 250px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        background: #fff;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .svg-thumbnail-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
    }

    .svg-thumbnail-inner {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        background-image: radial-gradient(var(--gray-200) 1px, transparent 1px);
        background-size: 8px 8px;
    }

    .svg-thumbnail-inner svg {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
    }

    .svg-thumbnail-hover {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(37, 99, 235, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        color: #fff;
        font-size: 18px;
    }

    .svg-thumbnail-card:hover .svg-thumbnail-hover {
        opacity: 1;
    }

    .svg-thumbnail-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
    }

    .svg-thumbnail-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
    }

    .svg-thumbnail-hint {
        font-size: 11px;
        color: var(--gray-400);
    }

    .svg-thumbnail-badge {
        font-size: 8px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
        padding: 2px 6px;
        border-radius: 8px;
        letter-spacing: 0.5px;
    }

    /* ============================================= */
    /* SVG MODAL OVERLAY */
    /* ============================================= */
    .svg-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.7);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: svgModalFadeIn 0.2s ease;
    }

    @keyframes svgModalFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .svg-modal-container {
        background: #fff;
        border-radius: 150px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: svgModalSlideIn 0.25s ease;
    }

    @keyframes svgModalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .svg-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-200);
    }

    .svg-modal-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .svg-modal-title i {
        color: var(--primary);
    }

    .svg-modal-close {
        width: 36px;
        height: 36px;
        border: none;
        background: var(--gray-100);
        border-radius: 8px;
        color: var(--gray-500);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }

    .svg-modal-close:hover {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .svg-modal-body {
        flex: 1;
        padding: 20px;
        overflow: hidden;
        background: #f8fafc;
        background-image: radial-gradient(var(--gray-300) 1px, transparent 1px);
        background-size: 16px 16px;
        min-height: 400px;
        max-height: 60vh;
    }

    .svg-modal-canvas {
        width: 100%;
        height: 100%;
        min-height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: grab;
        overflow: hidden;
    }

    .svg-modal-canvas:active {
        cursor: grabbing;
    }

    .svg-modal-canvas svg {
        max-width: 100%;
        max-height: 100%;
        transition: transform 0.1s ease;
    }

    .svg-modal-toolbar {
        display: flex;
        align-items: center;
        justify-content: center;
        /* Centrado de herramientas */
        gap: 20px;
        /* Espacio entre grupos */
        padding: 8px 20px;
        /* Reducido espacio vertical */
        border-top: 1px solid var(--gray-200);
        background: #fff;
        border-radius: 0 0 12px 12px;
    }

    .svg-modal-tools-left,
    .svg-modal-tools-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .svg-modal-btn {
        height: 36px;
        padding: 0 12px;
        border: 1px solid var(--gray-200);
        background: #fff;
        border-radius: 8px;
        color: var(--gray-600);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .svg-modal-btn:hover {
        background: var(--gray-100);
        border-color: var(--gray-300);
    }

    .svg-modal-btn:active {
        transform: scale(0.97);
    }

    .svg-modal-btn i {
        font-size: 14px;
    }

    .svg-modal-btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .svg-modal-btn-primary:hover {
        background: var(--primary-hover);
        border-color: var(--primary-hover);
    }

    .svg-zoom-level {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500);
        min-width: 45px;
        text-align: center;
    }

    /* Alert manual entry */
    .alert-manual-entry {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--warning-light);
        border: 1px solid #fcd34d;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
        font-size: 13px;
        color: #92400e;
    }

    .alert-manual-entry i {
        font-size: 16px;
    }

    /* Technical Data */
    .technical-data-section,
    .colors-preview-section,
    .form-fields-section {
        margin-bottom: 20px;
    }

    .section-label-large {
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .tech-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .tech-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 14px;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        text-align: center;
        transition: all 0.2s ease;
    }

    .tech-card.has-error {
        border-color: var(--danger);
        background: var(--danger-light);
    }

    .tech-card i {
        font-size: 24px;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .tech-card-label {
        font-size: 14px;
        color: var(--gray-500);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .tech-card-input {
        width: 100%;
        max-width: 120px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 8px;
        font-size: 20px;
        font-weight: 700;
        color: var(--gray-800);
        text-align: center;
        background: #fff;
        height: 48px;
    }

    .tech-card-input:focus {
        outline: none;
        border-color: var(--primary);
    }

    /* Color Swatches */
    .color-swatches {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .color-swatch {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .color-swatch-box {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-sm);
        border: 3px solid #fff;
        box-shadow: var(--shadow-md);
    }

    .color-swatch-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--gray-700);
        text-align: center;
        font-family: 'SF Mono', 'Consolas', monospace;
    }

    /* Form Fields */
    .field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 12px;
    }

    .field-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
    }

    .required-mark {
        color: var(--danger);
    }

    .field-control {
        padding: 10px 12px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 14px;
        color: var(--gray-800);
        transition: all 0.15s ease;
    }

    .field-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .field-control.has-error {
        border-color: var(--danger);
    }

    .field-textarea {
        resize: vertical;
        min-height: 60px;
    }

    .field-error {
        font-size: 12px;
        color: var(--danger);
    }

    /* Form Error */
    .form-error-alert {
        padding: 12px 16px;
        background: var(--danger-light);
        border: 1px solid #fecaca;
        border-radius: var(--radius-sm);
        color: var(--danger);
        font-size: 13px;
        margin-bottom: 16px;
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 10px;
        padding-top: 16px;
        border-top: 1px solid var(--gray-200);
        margin-top: 16px;
    }

    .form-actions .btn-premium {
        flex: 1;
    }

    /* ============================================= */
    /* MODAL DE DETALLES */
    /* ============================================= */
    .detail-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
    }

    .detail-modal-centered {
        background: #fff;
        border-radius: var(--radius-lg);
        max-width: 480px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: var(--shadow-lg);
        overflow: visible;
    }

    .detail-modal-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        border: none;
        background: var(--gray-100);
        color: var(--gray-500);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: all 0.15s ease;
    }

    .detail-modal-close:hover {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .detail-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    /* File Header */
    .detail-file-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 16px;
    }

    .detail-file-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-light);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .detail-file-icon i {
        font-size: 22px;
        color: var(--primary);
    }

    .detail-file-name {
        font-size: 20px;
        font-weight: 700;
        color: var(--gray-800);
        margin: 0 0 4px;
        word-break: break-all;
        text-transform: uppercase;
    }

    .detail-file-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .file-format-tag {
        background: var(--primary);
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .file-size {
        font-size: 16px;
        color: var(--gray-500);
        font-weight: 500;
    }

    /* Status Badge Detail */
    .detail-status-section {
        margin-bottom: 16px;
    }

    .status-badge-detail {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .status-badge-detail.status-borrador {
        background: var(--gray-100);
        color: var(--gray-600);
    }

    .status-badge-detail.status-pendiente {
        background: var(--warning-light);
        color: #92400e;
    }

    .status-badge-detail.status-aprobado {
        background: var(--success-light);
        color: #065f46;
    }

    .status-badge-detail.status-archivado {
        background: var(--gray-800);
        color: #fff;
    }

    .status-emoji {
        font-size: 16px;
    }

    /* Tech Grid Detail */
    .detail-tech-section {
        margin-bottom: 16px;
        margin-top: 24px;
    }

    .section-title-sm {
        font-size: 12px;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .tech-grid-detail {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .tech-item-detail {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
    }

    .tech-item-detail i {
        font-size: 24px;
        color: var(--primary);
    }

    .tech-value-lg {
        display: block;
        font-size: 20px;
        font-weight: 700;
        color: var(--gray-800);
    }

    .tech-label-sm {
        font-size: 14px;
        color: var(--gray-500);
        font-weight: 600;
    }

    /* Colors Grid */
    .color-grid-lg {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Info Grid */
    .detail-app-section {
        margin-bottom: 16px;
        margin-top: 24px;
    }

    .info-grid {
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        padding: 12px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--gray-200);
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-key {
        font-size: 13px;
        color: var(--gray-500);
    }

    .info-val {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-800);
        text-align: right;
    }

    .info-notes {
        max-width: 200px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Meta Section */
    .detail-meta-section {
        padding: 12px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
    }

    .meta-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--gray-600);
        padding: 6px 0;
    }

    .meta-row i {
        width: 16px;
        color: var(--gray-400);
    }

    .meta-row strong {
        color: var(--gray-800);
    }

    /* Modal Footer */
    .detail-modal-footer {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px 20px;
        border-top: 1px solid var(--gray-200);
        background: var(--gray-50);
    }

    .footer-actions-main {
        display: flex;
        gap: 10px;
    }

    .footer-actions-main .btn-premium {
        flex: 1;
    }

    .footer-actions-status {
        display: flex;
        gap: 10px;
    }

    .footer-actions-status .btn-premium {
        flex: 1;
    }

    .footer-actions-status:empty {
        display: none;
    }

    /* Workflow Indicator */
    .workflow-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 20px 16px;
        background: #fff;
        border-top: 1px solid var(--gray-100);
    }

    .workflow-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .workflow-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--gray-300);
        border: 2px solid var(--gray-300);
        transition: all 0.3s ease;
    }

    .workflow-step span {
        font-size: 10px;
        font-weight: 500;
        color: var(--gray-400);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .workflow-line {
        flex: 1;
        height: 2px;
        background: var(--gray-200);
        margin: 0 8px;
        margin-bottom: 20px;
        min-width: 30px;
    }

    .workflow-step.completed .workflow-dot {
        background: var(--success);
        border-color: var(--success);
    }

    .workflow-step.completed span {
        color: var(--success);
    }

    .workflow-step.current .workflow-dot {
        background: #fff;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    }

    .workflow-step.current span {
        color: var(--primary);
        font-weight: 600;
    }

    .workflow-line.completed {
        background: var(--success);
    }

    /* ============================================= */
    /* FOOTER PRODUCCIÓN */
    /* ============================================= */
    .production-footer {
        padding: 16px 0;
        border-top: 1px solid var(--gray-200);
        margin-top: auto;
    }

    /* ============================================= */
    /* RESPONSIVE - EXPORT ITEMS & FORM */
    /* ============================================= */

    /* Tablet */
    @media (max-width: 768px) {
        .export-item-meta .meta-dimensions {
            display: none;
        }

        .btn-action-icon {
            width: 30px;
            height: 30px;
            font-size: 12px;
        }

        .export-item-thumb {
            width: 40px;
            height: 40px;
        }

        .export-item-content {
            gap: 8px;
        }

        .export-item-name {
            font-size: 13px;
        }

        .export-item-meta span {
            font-size: 10px;
        }

        /* Form adjustments */
        .upload-zone {
            padding: 20px 15px;
        }

        .upload-zone i {
            font-size: 32px;
        }

        .tech-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .tech-card {
            padding: 10px;
        }
    }

    /* Móvil */
    @media (max-width: 576px) {
        .tech-cards {
            grid-template-columns: 1fr 1fr;
        }

        .field-row {
            grid-template-columns: 1fr;
        }

        .tech-grid-detail {
            grid-template-columns: 1fr 1fr;
        }

        .detail-modal-centered {
            max-width: 100%;
            max-height: 100%;
            border-radius: 0;
        }

        .detail-overlay {
            padding: 0;
        }

        .export-item {
            flex-direction: column;
            align-items: stretch;
        }

        .export-status-indicator {
            width: 100%;
            height: 4px;
            position: absolute;
            top: 0;
            left: 0;
        }

        .export-item-thumb {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
        }

        .export-item-content {
            flex: 1;
            min-width: 0;
        }

        .export-item-name {
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .export-item-meta {
            flex-wrap: wrap;
            gap: 4px;
        }

        .export-item-meta span {
            font-size: 9px;
        }

        .export-item-actions {
            width: 100%;
            justify-content: flex-end;
            padding: 8px 12px;
            border-top: 1px solid var(--gray-100);
            gap: 6px;
        }

        .btn-action-icon {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }

        /* Upload zone mobile */
        .upload-zone {
            padding: 16px 12px;
            min-height: 100px;
        }

        .upload-zone i {
            font-size: 28px;
            margin-bottom: 6px;
        }

        .upload-zone p {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .upload-zone small {
            font-size: 10px;
        }

        /* Form mobile */
        .form-group label {
            font-size: 12px;
        }

        .form-control {
            font-size: 14px;
            padding: 8px 10px;
        }

        .tech-cards {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .tech-card {
            padding: 8px;
        }

        .tech-card .value {
            font-size: 14px;
        }

        .tech-card .label {
            font-size: 9px;
        }

        .footer-actions-main,
        .footer-actions-status {
            flex-direction: column;
        }

        /* Empty state mobile */
        .production-state {
            padding: 32px 16px;
        }

        .empty-state-icon {
            width: 60px;
            height: 60px;
        }

        .empty-state-icon i {
            font-size: 24px;
        }

        .empty-state-title {
            font-size: 15px;
        }

        .empty-state-text {
            font-size: 12px;
        }
    }

    /* Móvil muy pequeño */
    @media (max-width: 380px) {
        .export-item-thumb {
            width: 32px;
            height: 32px;
        }

        .export-item-name {
            font-size: 11px;
        }

        .btn-action-icon {
            width: 26px;
            height: 26px;
            font-size: 10px;
        }

        .tech-cards {
            grid-template-columns: 1fr;
        }

        .upload-zone {
            padding: 14px 10px;
        }
    }

    /* Touch-friendly: aumentar áreas táctiles */
    @media (hover: none) and (pointer: coarse) {
        .summary-badge {
            min-height: 44px;
        }

        .btn-action-icon {
            min-width: 36px;
            min-height: 36px;
        }

        .export-item {
            min-height: 60px;
        }

        .btn-add-production-full {
            min-height: 48px;
        }
    }

    /* ============================================= */
    /* ACCIONES DEL FORMULARIO (MODIFICADO - MATCH INDEX) */
    /* ============================================= */
    .form-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: auto;
        padding-top: 17px;
        border-top: 1px solid var(--gray-200);
        margin-bottom: 12px;
        padding-bottom: 4px;
        width: 100%;
        padding-left: 16px;
        padding-right: 16px;
        box-sizing: border-box;
    }

    .form-actions button {
        flex: 1;
        /* Misma proporción */
        justify-content: center;
        height: 52px;
        /* Standard height */
        min-height: 52px;
    }

    /* ============================================= */
    /* VISTA PREVIA SVG (REDEFINED) */
    /* ============================================= */
    .svg-preview-section {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed var(--gray-300);
        width: 100%;
    }

    .svg-thumbnail-row {
        display: flex;
        align-items: center;
        justify-content: center;
        /* Centrado solicitado */
        gap: 15px;
        position: relative;
    }

    .svg-thumbnail-card {
        width: 180px;
        height: 180px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        background: #fff;
        padding: 4px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .svg-thumbnail-card:hover {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px var(--primary-light);
    }

    .svg-thumbnail-inner {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .svg-thumbnail-hover {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(37, 99, 235, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .svg-thumbnail-card:hover .svg-thumbnail-hover {
        opacity: 1;
    }

    .svg-thumbnail-hover i {
        color: var(--primary);
        font-size: 20px;
    }

    .svg-thumbnail-info {
        display: flex;
        flex-direction: column;
    }

    .svg-thumbnail-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-800);
    }

    .svg-thumbnail-hint {
        font-size: 12px;
        color: var(--gray-500);
    }

    /* Modal SVG */
    .svg-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .svg-modal-container {
        width: 95vw;
        height: 70vh;
        background: #fff;
        border-radius: 20px;
        /* Esquinas redondeadas solicitadas */
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .svg-modal-header {
        height: 60px;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        /* Centrado del título */
        border-bottom: 1px solid var(--gray-200);
        background: #fff;
        z-index: 10;
        flex-shrink: 0;
        position: relative;
        /* Para botón de cierre absoluto */
    }

    .svg-modal-close {
        position: absolute;
        right: 20px;
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        border-radius: 50%;
        color: var(--gray-500);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .svg-modal-body {
        flex: 1;
        background-color: #f8fafc;
        background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
        background-size: 20px 20px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }

    .svg-modal-canvas {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: grab;
    }

    .export-item-icon svg {
        width: 100%;
        height: 100%;
    }

    .export-preview-box {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: transparent !important;
        padding: 0 !important;
        border-radius: var(--radius-sm);
    }

    .export-preview-box svg {
        width: 100%;
        height: 100%;
    }
</style>

<script>
    (function() {
        function initProductionTab() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initProductionTab, 50);
                return;
            }

            var $ = jQuery;

            $(document).ready(function() {
                // Variables globales
                var currentDesignId = null;
                var currentVariantId = null;
                var currentContext = 'design';
                var currentFile = null;
                var analyzedData = null;
                var currentExportId = null;
                var isEditMode = false;
                var exportsData = {};
                var autoReadSuccess = true;
                var groupsData = [];
                var variantsList = [];
                var currentFilter = {
                    status: '',
                    variant: ''
                };

                var statusConfig = {
                    borrador: {
                        emoji: '🟤',
                        label: 'Borrador',
                        nextAction: 'pendiente',
                        actionIcon: 'fa-paper-plane',
                        actionTitle: 'Enviar a Revisión'
                    },
                    pendiente: {
                        emoji: '🟡',
                        label: 'Pendiente',
                        nextAction: 'aprobado',
                        actionIcon: 'fa-check-circle',
                        actionTitle: 'Aprobar'
                    },
                    aprobado: {
                        emoji: '🟢',
                        label: 'Aprobado',
                        nextAction: 'archivado',
                        actionIcon: 'fa-archive',
                        actionTitle: 'Archivar'
                    },
                    archivado: {
                        emoji: '⚫',
                        label: 'Archivado',
                        nextAction: 'borrador',
                        actionIcon: 'fa-undo',
                        actionTitle: 'Restaurar'
                    }
                };

                var baseUrl = '{{ url('/') }}';
                var csrfToken = '{{ csrf_token() }}';

                // URLs
                var urls = {
                    analyze: baseUrl + '/admin/exports/analyze',
                    storeAjax: baseUrl + '/admin/exports/store-ajax',
                    updateAjax: function(id) {
                        return baseUrl + '/admin/exports/' + id + '/update-ajax';
                    },
                    updateStatus: function(id) {
                        return baseUrl + '/admin/exports/' + id + '/status';
                    },
                    destroyAjax: function(id) {
                        return baseUrl + '/admin/exports/' + id + '/ajax';
                    },
                    getExport: function(id) {
                        return baseUrl + '/admin/exports/' + id + '/ajax';
                    },
                    designExports: function(designId) {
                        return baseUrl + '/admin/designs/' + designId + '/exports/ajax';
                    },
                    variantExports: function(designId, variantId) {
                        return baseUrl + '/admin/designs/' + designId + '/variants/' + variantId +
                            '/exports/ajax';
                    },
                    exportsGrouped: function(designId) {
                        return baseUrl + '/admin/designs/' + designId + '/exports-grouped';
                    },
                    exportsCount: function(designId) {
                        return baseUrl + '/admin/designs/' + designId + '/exports-count';
                    }
                };


                // ============================================
                // ENTERPRISE: Variables para cancelación de requests
                // ============================================
                var pendingProductionRequest = null;
                var loadRequestVersion = 0;

                // Cargar datos - V5 con agrupamiento + cancelación de requests
                function loadProductionData() {
                    // CRITICAL: Cancelar request anterior si existe
                    if (pendingProductionRequest && typeof pendingProductionRequest.abort === 'function') {
                        pendingProductionRequest.abort();
                        console.log('[ProductionTab] Previous request aborted - context changed');
                    }

                    currentDesignId = $('#modalDesignId').val();
                    currentVariantId = $('#modalVariantId').val();
                    currentContext = currentVariantId ? 'variant' : 'design';

                    // Incrementar versión para ignorar respuestas obsoletas
                    loadRequestVersion++;
                    var myVersion = loadRequestVersion;

                    if (!currentDesignId) {
                        showEmptyState();
                        $('#productionTotalCount').text('0');
                        return;
                    }

                    hideAllStates();
                    $('#productionLoading').show();

                    // Resetear filtros al cambiar contexto
                    currentFilter = {
                        status: '',
                        variant: ''
                    };
                    $('#filterVariant').val('');
                    $('.summary-badge').removeClass('active');
                    $('.summary-badge[data-filter=""]').addClass('active');

                    if (currentContext === 'design') {
                        // Vista principal: cargar agrupado
                        pendingProductionRequest = $.get(urls.exportsGrouped(currentDesignId))
                            .done(function(response) {
                                // Verificar que esta respuesta es para el request actual
                                if (myVersion !== loadRequestVersion) {
                                    console.log('[ProductionTab] Stale design response ignored');
                                    return;
                                }

                                $('#productionLoading').hide();

                                if (response.success) {
                                    groupsData = response.groups || [];
                                    variantsList = response.variants_list || [];
                                    exportsData = {};

                                    groupsData.forEach(function(g) {
                                        g.exports.forEach(function(e) {
                                            exportsData[e.id] = e;
                                        });
                                    });

                                    $('#productionContext').html('Diseño: <strong>' + response
                                        .design_name + '</strong>');
                                    $('#exportsCount').text(response.total_count + ' archivo' + (
                                        response.total_count !== 1 ? 's' : ''));

                                    updateSummary(response.summary);
                                    $('#productionTotalCount').text(response.total_count);
                                    updateFiltersSection();

                                    if (response.total_count > 0) {
                                        renderGroups(groupsData);
                                        $('#productionList').show();
                                    } else {
                                        showEmptyState();
                                    }
                                } else {
                                    showEmptyState();
                                }
                            })
                            .fail(function(jqXHR, textStatus) {
                                // Ignorar errores de abort (son intencionales)
                                if (textStatus === 'abort') {
                                    console.log('[ProductionTab] Design request aborted intentionally');
                                    return;
                                }
                                if (myVersion !== loadRequestVersion) return;
                                $('#productionLoading').hide();
                                showEmptyState();
                            });
                    } else {
                        // Vista de variante: lista simple
                        pendingProductionRequest = $.get(urls.variantExports(currentDesignId,
                                currentVariantId))
                            .done(function(response) {
                                // Verificar que esta respuesta es para el request actual
                                if (myVersion !== loadRequestVersion) {
                                    console.log('[ProductionTab] Stale variant response ignored');
                                    return;
                                }

                                $('#productionLoading').hide();

                                var displayCount = response.count || 0;

                                if (response.success && response.data && response.data.length > 0) {
                                    response.data.forEach(function(exp) {
                                        exportsData[exp.id] = exp;
                                    });

                                    updateSummary(response.summary || calculateSummary(response.data));
                                    $('#variantFilterSection').hide();

                                    renderSimpleList(response.data);
                                    $('#productionContext').html('Variante: <strong>' + (response
                                        .context_name || currentVariantId) + '</strong>');
                                    $('#exportsCount').text(displayCount + ' archivo' + (
                                        displayCount !== 1 ? 's' : ''));
                                    $('#productionList').show();
                                } else {
                                    showEmptyState();
                                    displayCount = 0;
                                }

                                $('#productionTotalCount').text(displayCount);
                            })
                            .fail(function(jqXHR, textStatus) {
                                // Ignorar errores de abort (son intencionales)
                                if (textStatus === 'abort') {
                                    console.log(
                                        '[ProductionTab] Variant request aborted intentionally');
                                    return;
                                }
                                if (myVersion !== loadRequestVersion) return;
                                $('#productionLoading').hide();
                                showEmptyState();
                                $('#productionTotalCount').text('0');
                            });
                    }
                }

                // Bandera para indicar que queremos ir directo al formulario (no cargar lista)
                var skipLoadAndShowForm = false;
                var pendingImageId = null;

                // Exponer funciones globalmente para que index.blade.php pueda llamarlas
                window.loadProductionData = loadProductionData;
                window.openExportForm = function() {
                    showForm(null);
                };

                // Función para preparar apertura directa al formulario
                // Se llama ANTES de cambiar a la pestaña de producción
                window.prepareDirectFormOpen = function(imageId) {
                    skipLoadAndShowForm = true;
                    pendingImageId = imageId || null;
                };

                // Función para limpiar imagen vinculada
                window.clearLinkedImage = function() {
                    $('#exportImageId').val('');
                    $('#linkedImageIndicator').hide().html('');
                };

                // Función interna para mostrar formulario directo
                function showFormDirect() {
                    // Establecer IDs del contexto actual
                    currentDesignId = $('#modalDesignId').val();
                    currentVariantId = $('#modalVariantId').val();

                    // Ocultar todo y mostrar solo el formulario
                    hideAllStates();
                    showForm(null);

                    // Establecer el image_id si viene de una imagen específica
                    if (pendingImageId) {
                        setTimeout(function() {
                            $('#exportImageId').val(pendingImageId);
                            // Mostrar indicador visual
                            $('#linkedImageIndicator').html(
                                '<div class="alert alert-info py-2 px-3 mb-2" style="font-size: 12px;">' +
                                '<i class="fas fa-link mr-1"></i> Producción vinculada a imagen #' +
                                pendingImageId +
                                '<button type="button" class="close" onclick="clearLinkedImage()" style="font-size: 14px; margin-left: 10px;">' +
                                '<span>&times;</span></button></div>'
                            ).show();
                            pendingImageId = null;
                        }, 50);
                    }
                }

                function calculateSummary(data) {
                    var summary = {
                        borrador: 0,
                        pendiente: 0,
                        aprobado: 0,
                        archivado: 0
                    };
                    data.forEach(function(e) {
                        if (summary.hasOwnProperty(e.status)) summary[e.status]++;
                    });
                    return summary;
                }

                function updateSummary(summary) {
                    var total = (summary.borrador || 0) + (summary.pendiente || 0) + (summary.aprobado ||
                        0) + (summary.archivado || 0);
                    $('#summaryTotal').text(total);
                    $('#summaryBorrador').text(summary.borrador || 0);
                    $('#summaryPendiente').text(summary.pendiente || 0);
                    $('#summaryAprobado').text(summary.aprobado || 0);
                    $('#summaryArchivado').text(summary.archivado || 0);
                }

                function updateFiltersSection() {
                    if (currentContext === 'design' && groupsData.length > 0) {
                        $('#variantFilterSection').show();
                        var $filter = $('#filterVariant');
                        $filter.find('option:not(:first)').remove();
                        $filter.append('<option value="design">📁 Diseño Principal</option>');
                        variantsList.forEach(function(v) {
                            $filter.append('<option value="' + v.id + '">🏷️ ' + v.name + ' (' + v
                                .count + ')</option>');
                        });
                    } else {
                        $('#variantFilterSection').hide();
                    }
                }

                function renderGroups(groups) {
                    var $container = $('#exportsGroupsContainer');
                    $container.empty();

                    if (!groups || groups.length === 0) {
                        showEmptyState();
                        return;
                    }

                    groups.forEach(function(group) {
                        var groupId = group.type + '_' + (group.variant_id || 'main');
                        var isDesign = group.type === 'design';
                        var typeClass = isDesign ? 'type-design' : 'type-variant';
                        var typeLabel = isDesign ? 'Diseño' : 'Variante';
                        var thumbnail = group.thumbnail || '';

                        var $group = $('<div class="export-group" data-group-id="' + groupId +
                            '" data-variant-id="' + (group.variant_id || '') + '">');

                        var headerHtml = '<div class="export-group-header">';
                        headerHtml += '<div class="group-header-left">';

                        if (thumbnail) {
                            headerHtml += '<img src="' + thumbnail +
                                '" class="group-thumbnail" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
                            headerHtml +=
                                '<div class="group-thumbnail-placeholder" style="display: none;"><i class="fas fa-' +
                                (isDesign ? 'palette' : 'layer-group') + '"></i></div>';
                        } else {
                            headerHtml +=
                                '<div class="group-thumbnail-placeholder"><i class="fas fa-' + (
                                    isDesign ? 'palette' : 'layer-group') + '"></i></div>';
                        }

                        headerHtml += '<div class="group-info">';
                        headerHtml += '<span class="group-type-badge ' + typeClass + '">' +
                            typeLabel + '</span>';
                        headerHtml += '<span class="group-name">' + escapeHtml(group.name) +
                            '</span>';
                        headerHtml += '</div></div>';
                        headerHtml += '<div class="group-header-right">';
                        headerHtml += '<span class="group-count">' + group.count + ' archivo' + (
                            group.count !== 1 ? 's' : '') + '</span>';
                        headerHtml += '<i class="fas fa-chevron-down group-toggle-icon"></i>';
                        headerHtml += '</div></div>';

                        $group.append(headerHtml);

                        var $body = $('<div class="export-group-body">');
                        group.exports.forEach(function(exp) {
                            $body.append(renderExportItemV5(exp));
                        });

                        $group.append($body);
                        $container.append($group);
                    });

                    bindV5Events();
                }

                function renderSimpleList(exports) {
                    var $container = $('#exportsGroupsContainer');
                    $container.empty();

                    var $list = $('<div class="exports-list">');
                    exports.forEach(function(exp) {
                        $list.append(renderExportItemV5(exp));
                    });

                    $container.append($list);
                    bindV5Events();
                }

                function renderExportItemV5(exp) {
                    var status = exp.status || 'borrador';
                    var config = statusConfig[status];

                    var html = '<div class="export-item" data-id="' + exp.id + '" data-status="' + status +
                        '" data-variant="' + (exp.design_variant_id || '') + '">';
                    html += '<div class="export-status-indicator status-' + status + '"></div>';
                    html += '<div class="export-item-content" role="button">';

                    // Miniatura: Prioridad SVG -> Imagen -> Icono
                    if (exp.svg_content) {
                        html += '<div class="export-item-thumbnail export-preview-box">';
                        html += exp.svg_content;
                        html += '</div>';
                    } else if (exp.image_url) {
                        html += '<div class="export-item-thumbnail">';
                        html += '<img src="' + exp.image_url +
                            '" alt="Ref" onerror="this.parentElement.innerHTML=\'<i class=\\\'fas fa-image\\\'></i>\';this.parentElement.classList.add(\'no-image\');">';
                        html += '</div>';
                    } else {
                        html +=
                            '<div class="export-item-thumbnail no-image"><i class="fas fa-image"></i></div>';
                    }
                    html += '<div class="export-item-info">';
                    html += '<div class="export-item-name">' + escapeHtml(exp.application_label ||
                        'Sin nombre') + '</div>';
                    html += '<div class="export-item-meta">';
                    html += '<span class="meta-format">' + (exp.file_format || 'PES') + '</span>';
                    html += '<span class="meta-sep">•</span>';
                    html += '<span class="meta-stitches">' + (exp.stitches_formatted || '0') +
                        ' pts</span>';
                    html += '<span class="meta-sep">•</span>';
                    html += '<span class="meta-colors">' + (exp.colors_count || 0) + ' col</span>';
                    html += '<span class="meta-sep">•</span>';
                    html += '<span class="meta-dimensions">' + (exp.dimensions_formatted || '--') +
                        '</span>';
                    html += '</div></div></div>';

                    html += '<div class="export-item-actions">';
                    html += '<span class="status-badge-mini status-' + status + '">' + config.emoji + ' ' +
                        config.label + '</span>';
                    html +=
                        '<button type="button" class="btn-action-primary-mini btn-quick-status" data-id="' +
                        exp.id + '" data-status="' + status + '" title="' + config.actionTitle + '">';
                    html += '<i class="fas ' + config.actionIcon + '"></i></button>';
                    html += '<a href="' + exp.download_url +
                        '" class="btn-action-icon btn-action-download" title="Descargar" download>';
                    html += '<i class="fas fa-download"></i></a>';
                    html +=
                        '<button type="button" class="btn-action-icon btn-action-delete btn-delete-export" data-id="' +
                        exp.id + '" title="Eliminar">';
                    html += '<i class="fas fa-trash"></i></button>';
                    html += '</div></div>';

                    return $(html);
                }

                function bindV5Events() {
                    // Toggle acordeón
                    $('.export-group-header').off('click').on('click', function() {
                        var $header = $(this);
                        var $body = $header.next('.export-group-body');
                        var $icon = $header.find('.group-toggle-icon');

                        if ($body.is(':visible')) {
                            $body.slideUp(200);
                            $header.addClass('collapsed');
                            $icon.css('transform', 'rotate(-90deg)');
                        } else {
                            $body.slideDown(200);
                            $header.removeClass('collapsed');
                            $icon.css('transform', 'rotate(0deg)');
                        }
                    });

                    // Click en item para ver detalles
                    $('.export-item-content').off('click').on('click', function() {
                        var id = $(this).closest('.export-item').data('id');
                        openDetailPanel(id);
                    });

                    // Acción rápida de estado
                    $('.btn-quick-status').off('click').on('click', function(e) {
                        e.stopPropagation();
                        var id = $(this).data('id');
                        var currentStatus = $(this).data('status');
                        var nextStatus = statusConfig[currentStatus].nextAction;
                        confirmQuickStatusChange(id, currentStatus, nextStatus);
                    });

                    // Nota: El handler de eliminar está definido globalmente con $(document).on('click', '.btn-delete-export', ...)
                }

                function confirmQuickStatusChange(exportId, currentStatus, newStatus) {
                    var messages = {
                        'pendiente': {
                            title: '¿Enviar a Revisión?',
                            text: 'El archivo pasará a revisión.',
                            icon: 'question',
                            btn: 'Sí, enviar'
                        },
                        'aprobado': {
                            title: '¿Aprobar?',
                            text: 'El archivo quedará listo para producción.',
                            icon: 'success',
                            btn: 'Sí, aprobar'
                        },
                        'archivado': {
                            title: '¿Archivar?',
                            text: 'El archivo se moverá al histórico.',
                            icon: 'warning',
                            btn: 'Sí, archivar'
                        },
                        'borrador': {
                            title: '¿Restaurar?',
                            text: 'El archivo volverá a borrador.',
                            icon: 'info',
                            btn: 'Sí, restaurar'
                        }
                    };

                    var msg = messages[newStatus] || {
                        title: '¿Cambiar?',
                        text: '',
                        icon: 'question',
                        btn: 'Confirmar'
                    };

                    Swal.fire({
                        title: msg.title,
                        text: msg.text,
                        icon: msg.icon,
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar',
                        confirmButtonText: msg.btn,
                        confirmButtonColor: '#2563eb',
                        // Fix z-index to appear above Bootstrap modal
                        customClass: {
                            container: 'swal2-container-high-zindex'
                        }
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: urls.updateStatus(exportId),
                                method: 'POST',
                                data: {
                                    _token: csrfToken,
                                    status: newStatus
                                },
                                success: function(response) {
                                    if (response.success) {
                                        toastSuccess('Estado actualizado');
                                        loadProductionData();
                                    } else {
                                        toastError(response.error || 'Error');
                                    }
                                },
                                error: function() {
                                    toastError('Error de conexión');
                                }
                            });
                        }
                    });
                }

                function applyFilters() {
                    var statusFilter = currentFilter.status;
                    var variantFilter = currentFilter.variant;

                    $('.summary-badge').removeClass('active');
                    $('.summary-badge[data-filter="' + statusFilter + '"]').addClass('active');

                    if (currentContext === 'design') {
                        $('.export-group').each(function() {
                            var $group = $(this);
                            var groupVariantId = $group.data('variant-id');
                            var isDesignGroup = groupVariantId === '' || groupVariantId ===
                                undefined;

                            var showGroup = true;
                            if (variantFilter) {
                                showGroup = variantFilter === 'design' ? isDesignGroup : (
                                    groupVariantId + '') === (variantFilter + '');
                            }

                            if (!showGroup) {
                                $group.hide();
                                return;
                            }
                            $group.show();

                            var visibleCount = 0;
                            $group.find('.export-item').each(function() {
                                var $item = $(this);
                                if (statusFilter && $item.data('status') !== statusFilter) {
                                    $item.addClass('filtered-out');
                                } else {
                                    $item.removeClass('filtered-out');
                                    visibleCount++;
                                }
                            });

                            if (visibleCount === 0) $group.hide();
                        });
                    } else {
                        $('.export-item').each(function() {
                            var $item = $(this);
                            if (statusFilter && $item.data('status') !== statusFilter) {
                                $item.addClass('filtered-out');
                            } else {
                                $item.removeClass('filtered-out');
                            }
                        });
                    }
                }

                function escapeHtml(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                function toastSuccess(msg) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: msg,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                }

                function toastError(msg) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: msg,
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }

                function updateContextText() {
                    var contextText = currentVariantId ?
                        'Variante: <strong>' + ($('#variantName').text() || 'Seleccionada') + '</strong>' :
                        'Diseño: <strong>' + ($('#modalDesignTitle').text() || 'Principal') + '</strong>';
                    $('#productionContext').html(contextText);
                }

                function hideAllStates() {
                    $('#productionLoading, #productionEmpty, #productionList, #productionForm').hide();
                }

                function showEmptyState() {
                    hideAllStates();
                    // En lugar de mostrar el empty state, mostramos directamente el formulario
                    showForm(null);
                    // Ocultar botón de volver ya que no hay lista a donde regresar
                    $('#btnBackToList').hide();
                    $('#productionTotalCount').text('0');
                    $('#exportsCount').text('0 archivos');
                    updateSummary({
                        borrador: 0,
                        pendiente: 0,
                        aprobado: 0,
                        archivado: 0
                    });
                }

                function renderExports(exports) {
                    var container = $('#exportsGroupsContainer');
                    var template = $('#exportItemTemplate').html();
                    container.empty();

                    exports.forEach(function(item) {
                        var statusEmoji = '🟤';
                        var statusLabel = 'Borrador';
                        if (item.status === 'pendiente') {
                            statusEmoji = '🟡';
                            statusLabel = 'Pendiente';
                        } else if (item.status === 'aprobado') {
                            statusEmoji = '🟢';
                            statusLabel = 'Aprobado';
                        } else if (item.status === 'archivado') {
                            statusEmoji = '⚫';
                            statusLabel = 'Archivado';
                        }

                        var dimensions = '--';
                        if (item.width_mm && item.height_mm) {
                            dimensions = item.width_mm + '×' + item.height_mm + ' mm';
                        }

                        var previewHtml =
                            '<div class="export-item-icon"><i class="fas fa-file-code"></i></div>';
                        if (item.svg_content && item.svg_content.trim() !== '') {
                            previewHtml =
                                '<div class="export-preview-box">' +
                                item.svg_content + '</div>';
                        }

                        var html = template
                            .replace(/{id}/g, item.id)
                            .replace(/{preview_html}/g, previewHtml)
                            .replace(/{application_label}/g, escapeHtml(item.application_label ||
                                'Sin nombre'))
                            .replace(/{file_format}/g, item.file_format || 'N/A')
                            .replace(/{stitches_formatted}/g, item.stitches_formatted || '0')
                            .replace(/{colors_count}/g, item.colors_count || 0)
                            .replace(/{dimensions}/g, dimensions)
                            .replace(/{status}/g, item.status || 'borrador')
                            .replace(/{status_label}/g, statusLabel)
                            .replace(/{status_emoji}/g, statusEmoji)
                            .replace(/{download_url}/g, item.download_url || '#')
                            .replace(/{export_json}/g, escapeHtml(JSON.stringify(item)));
                        container.append(html);
                    });
                }

                function escapeHtml(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                // Formulario
                function showForm(editData) {
                    editData = editData || null;
                    hideAllStates();

                    // Mostrar botón de volver por defecto
                    $('#btnBackToList').show();

                    if (editData) {
                        isEditMode = true;
                        currentExportId = editData.id;
                        $('#formTitle').text('Editar Exportación');
                        $('#btnSaveText').text('Actualizar');
                        $('#formMethod').val('PUT');
                        $('#exportEditId').val(editData.id);
                        $('#uploadZone').hide();

                        $('#applicationType').val(editData.application_type);
                        $('#applicationLabel').val(editData.application_label);
                        $('#placementDescription').val(editData.placement_description || '');
                        $('#exportNotes').val(editData.notes || '');
                        $('#inputStitches').val(editData.stitches_count || 0);
                        $('#inputColors').val(editData.colors_count || 0);
                        $('#inputWidth').val(editData.width_mm || 0);
                        $('#inputHeight').val(editData.height_mm || 0);

                        autoReadSuccess = editData.auto_read_success;
                        $('#technicalData, #formFields').show();
                        $('#formActions').css('display', 'flex');
                        $('#manualEntryAlert').hide();
                        $('#btnSaveExport').prop('disabled', false);
                    } else {
                        isEditMode = false;
                        currentExportId = null;
                        resetForm();
                        $('#formTitle').text('Nueva Exportación');
                        $('#btnSaveText').text('Guardar');
                        $('#formMethod').val('POST');
                        $('#uploadZone').show();
                    }

                    $('#exportDesignId').val(currentDesignId);
                    $('#exportVariantId').val(currentVariantId || '');
                    $('#productionForm').css('display', 'flex');
                }

                function hideForm() {
                    $('#productionForm').hide();
                    resetForm();
                }

                function resetForm() {
                    currentFile = null;
                    analyzedData = null;
                    currentExportId = null;
                    isEditMode = false;
                    autoReadSuccess = true;

                    $('#exportForm')[0].reset();
                    $('#uploadZone').removeClass('has-file has-error').show();
                    $('#uploadContent').show();
                    $('#uploadAnalyzing, #uploadSuccess, #uploadError').hide();
                    $('#technicalData, #colorsPreview, #formFields, #formActions, #manualEntryAlert, #formError')
                        .hide();
                    $('#btnSaveExport').prop('disabled', true);

                    $('.field-control').removeClass('has-error');
                    $('.tech-card').removeClass('has-error');
                    $('.field-error').text('');
                    $('#inputStitches, #inputColors, #inputWidth, #inputHeight').val('');

                    // Limpiar imagen vinculada
                    $('#exportImageId').val('');
                    $('#linkedImageIndicator').hide().html('');

                    // Reset SVG preview
                    hideSvgPreview();
                }

                // Análisis de archivo
                function analyzeFile(file) {
                    currentFile = file;
                    var fileName = file.name;

                    $('#uploadContent').hide();
                    $('#uploadAnalyzing').show();
                    $('#uploadSuccess, #uploadError').hide();
                    $('#formError, #manualEntryAlert').hide();
                    $('#uploadZone').removeClass('has-file has-error');

                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('_token', csrfToken);

                    $.ajax({
                        url: urls.analyze,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#uploadAnalyzing').hide();
                            if (response.success) {
                                analyzedData = response.data;
                                autoReadSuccess = true;
                                showAnalysisSuccess(response.data, fileName);
                            } else {
                                autoReadSuccess = false;
                                showAnalysisError(fileName, response.error);
                            }
                        },
                        error: function(xhr) {
                            $('#uploadAnalyzing').hide();
                            autoReadSuccess = false;
                            var error = (xhr.responseJSON && xhr.responseJSON.error) ? xhr
                                .responseJSON.error : 'Error al analizar';
                            showAnalysisError(fileName, error);
                        }
                    });
                }

                function showAnalysisSuccess(data, fileName) {
                    $('#uploadFileName').text(fileName);
                    $('#uploadSuccess').show();
                    $('#uploadZone').addClass('has-file');
                    $('#hiddenAutoRead').val('1');

                    $('#inputStitches').val(data.stitches_count || 0);
                    $('#inputColors').val(data.colors_count || 0);
                    $('#inputWidth').val(data.width_mm || 0);
                    $('#inputHeight').val(data.height_mm || 0);

                    if (data.colors && data.colors.length > 0) {
                        $('#hiddenColorsDetected').val(JSON.stringify(data.colors));
                        renderColorSwatches(data.colors);
                        $('#colorsPreview').show();
                    }

                    // Display SVG preview if available
                    if (data.svg_content && data.svg_content.trim()) {
                        showSvgPreview(data.svg_content);
                    } else {
                        hideSvgPreview();
                    }

                    $('#technicalData, #formFields').show();
                    $('#formActions').css('display', 'flex');
                    $('#btnSaveExport').prop('disabled', false);
                }

                function showAnalysisError(fileName, errorMsg) {
                    $('#uploadErrorFileName').text(fileName);
                    $('#uploadError').show();
                    $('#uploadZone').addClass('has-error');
                    $('#manualEntryAlert').show();
                    $('#hiddenAutoRead').val('0');

                    $('#inputStitches, #inputColors, #inputWidth, #inputHeight').val('');

                    // Hide SVG preview on error
                    hideSvgPreview();

                    $('.tech-card').addClass('has-error');
                    $('#technicalData, #formFields').show();
                    $('#formActions').css('display', 'flex');
                    $('#btnSaveExport').prop('disabled', false);
                }

                function renderColorSwatches(colors) {
                    var container = $('#colorSwatches');
                    container.empty();

                    colors.forEach(function(color) {
                        var hex = color.hex || '#ccc';
                        var swatch = $('<div class="color-swatch">' +
                            '<div class="color-swatch-box" style="background-color: ' + hex +
                            '"></div>' +
                            '<span class="color-swatch-label">' + hex.toUpperCase() +
                            '</span>' +
                            '</div>');
                        container.append(swatch);
                    });
                }

                // ============================================
                // SVG PREVIEW & MODAL FUNCTIONS
                // ============================================
                var currentSvgContent = null;
                var svgZoomLevel = 1;
                var svgPanX = 0;
                var svgPanY = 0;
                var isDraggingSvg = false;
                var lastSvgMouseX = 0;
                var lastSvgMouseY = 0;

                // Mostrar thumbnail de vista previa
                function showSvgPreview(svgContent) {
                    if (!svgContent || !svgContent.trim()) {
                        hideSvgPreview();
                        return;
                    }

                    currentSvgContent = svgContent;

                    // Insertar en thumbnail
                    var $thumbnail = $('#svgThumbnailContainer');
                    $thumbnail.html(svgContent);

                    // Ajustar SVG en thumbnail
                    var $svg = $thumbnail.find('svg');
                    if ($svg.length) {
                        $svg.css({
                            'max-width': '95%',
                            'max-height': '95%',
                            'width': 'auto',
                            'height': 'auto'
                        });
                    }

                    // Mostrar sección
                    $('#svgPreviewSection').show();
                }

                // Ocultar preview
                function hideSvgPreview() {
                    currentSvgContent = null;
                    $('#svgPreviewSection').hide();
                    $('#svgThumbnailContainer').html('');
                    closeSvgModal();
                }

                // Abrir modal con SVG
                function openSvgModal() {
                    if (!currentSvgContent) return;

                    // Mover modal al body si no está
                    var $modal = $('#svgModalOverlay');
                    if (!$modal.data('moved-to-body')) {
                        $modal.appendTo('body');
                        $modal.data('moved-to-body', true);
                    }

                    // Insertar SVG en modal
                    var $canvas = $('#svgModalCanvas');
                    $canvas.html(currentSvgContent);

                    // Ajustar SVG
                    var $svg = $canvas.find('svg');
                    if ($svg.length) {
                        $svg.css({
                            'max-width': '100%',
                            'max-height': '100%',
                            'width': '100%',
                            'height': '100%'
                        });
                        // Asegurar centrado inicial con transform limpio
                        $svg.css('transform', 'translate(0, 0) scale(1)');
                    }

                    // Reset zoom y pan
                    svgZoomLevel = 1;
                    svgPanX = 0;
                    svgPanY = 0;
                    updateModalSvgTransform();

                    // Mostrar modal
                    $modal.fadeIn(200);
                    $('body').css('overflow', 'hidden');

                    // Setup eventos de pan
                    setupModalPanEvents();
                }

                // Cerrar modal
                function closeSvgModal() {
                    $('#svgModalOverlay').fadeOut(200);
                    $('body').css('overflow', '');
                    $('#svgModalCanvas').html('');
                    svgZoomLevel = 1;
                    svgPanX = 0;
                    svgPanY = 0;
                }

                // Actualizar transform del SVG en modal
                function updateModalSvgTransform() {
                    var $svg = $('#svgModalCanvas svg');
                    if ($svg.length) {
                        $svg.css('transform', 'translate(' + svgPanX + 'px, ' + svgPanY + 'px) scale(' +
                            svgZoomLevel + ')');
                    }
                    $('#svgZoomLabel').text(Math.round(svgZoomLevel * 100) + '%');
                }

                // Zoom functions
                function modalZoomIn() {
                    svgZoomLevel = Math.min(svgZoomLevel + 0.25, 5);
                    updateModalSvgTransform();
                }

                function modalZoomOut() {
                    svgZoomLevel = Math.max(svgZoomLevel - 0.25, 0.25);
                    updateModalSvgTransform();
                }

                function modalZoomReset() {
                    svgZoomLevel = 1;
                    svgPanX = 0;
                    svgPanY = 0;
                    updateModalSvgTransform();
                }

                // Descargar SVG
                function downloadSvg() {
                    if (!currentSvgContent) return;

                    var blob = new Blob([currentSvgContent], {
                        type: 'image/svg+xml'
                    });
                    var url = URL.createObjectURL(blob);
                    var fileName = (analyzedData && analyzedData.file_name) ?
                        analyzedData.file_name.replace(/\.[^/.]+$/, '') + '.svg' :
                        'embroidery-preview.svg';

                    var link = document.createElement('a');
                    link.href = url;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }

                // Setup eventos de pan en modal
                function setupModalPanEvents() {
                    var canvas = document.getElementById('svgModalCanvas');
                    if (!canvas) return;

                    canvas.onmousedown = function(e) {
                        if (e.button === 0) {
                            isDraggingSvg = true;
                            lastSvgMouseX = e.clientX;
                            lastSvgMouseY = e.clientY;
                            canvas.style.cursor = 'grabbing';
                        }
                    };

                    canvas.onmousemove = function(e) {
                        if (isDraggingSvg) {
                            svgPanX += e.clientX - lastSvgMouseX;
                            svgPanY += e.clientY - lastSvgMouseY;
                            lastSvgMouseX = e.clientX;
                            lastSvgMouseY = e.clientY;
                            updateModalSvgTransform();
                        }
                    };

                    canvas.onmouseup = function() {
                        isDraggingSvg = false;
                        canvas.style.cursor = 'grab';
                    };

                    canvas.onmouseleave = function() {
                        isDraggingSvg = false;
                        canvas.style.cursor = 'grab';
                    };

                    canvas.onwheel = function(e) {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            modalZoomIn();
                        } else {
                            modalZoomOut();
                        }
                    };
                }

                // Event Bindings para SVG
                $(document).on('click', '#svgThumbnailCard', openSvgModal);
                $(document).on('click', '#btnCloseSvgModal', closeSvgModal);
                $(document).on('click', '#svgModalOverlay', function(e) {
                    if (e.target === this) closeSvgModal();
                });
                $(document).on('click', '#btnModalZoomIn', modalZoomIn);
                $(document).on('click', '#btnModalZoomOut', modalZoomOut);
                $(document).on('click', '#btnModalZoomReset', modalZoomReset);
                $(document).on('click', '#btnModalDownload', downloadSvg);

                // ESC para cerrar modal
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && $('#svgModalOverlay').is(':visible')) {
                        closeSvgModal();
                    }
                });

                // Validación
                function validateForm() {
                    var isValid = true;

                    $('.field-control').removeClass('has-error');
                    $('.field-error').text('');

                    if (!$('#applicationType').val()) {
                        $('#applicationType').addClass('has-error');
                        $('#errorApplicationType').text('Selecciona un tipo');
                        isValid = false;
                    }

                    var label = $('#applicationLabel').val().trim();
                    if (!label) {
                        $('#applicationLabel').addClass('has-error');
                        $('#errorApplicationLabel').text('Ingresa un nombre');
                        isValid = false;
                    } else if (label.length < 2) {
                        $('#applicationLabel').addClass('has-error');
                        $('#errorApplicationLabel').text('Mínimo 2 caracteres');
                        isValid = false;
                    }

                    if (!autoReadSuccess) {
                        var stitches = parseInt($('#inputStitches').val()) || 0;
                        if (stitches <= 0) {
                            $('#techCardStitches').addClass('has-error');
                            isValid = false;
                        }
                    }

                    return isValid;
                }

                // Guardar
                function saveExport() {
                    if (!validateForm()) return;

                    if (!isEditMode && !currentFile) {
                        showErrorAlert('Archivo requerido', 'Selecciona un archivo primero');
                        return;
                    }

                    var $btn = $('#btnSaveExport');
                    $btn.prop('disabled', true).find('span').text('Guardando...');
                    $('#formError').hide();

                    var formData = new FormData();
                    formData.append('_token', csrfToken);

                    if (isEditMode) {
                        formData.append('_method', 'PUT');
                    } else {
                        formData.append('file', currentFile);
                        formData.append('design_id', currentDesignId);
                        formData.append('design_variant_id', currentVariantId || '');
                        // Incluir image_id si está vinculado a una imagen específica
                        var imageId = $('#exportImageId').val();
                        if (imageId) {
                            formData.append('image_id', imageId);
                        }
                    }

                    formData.append('application_type', $('#applicationType').val());
                    formData.append('application_label', $('#applicationLabel').val().trim());
                    formData.append('placement_description', $('#placementDescription').val().trim());
                    formData.append('notes', $('#exportNotes').val().trim());
                    formData.append('stitches_count', $('#inputStitches').val() || 0);
                    formData.append('colors_count', $('#inputColors').val() || 0);
                    formData.append('width_mm', $('#inputWidth').val() || 0);
                    formData.append('height_mm', $('#inputHeight').val() || 0);
                    formData.append('colors_detected', $('#hiddenColorsDetected').val() || '[]');
                    // ⭐ GUARDAR CONTENIDO SVG PARA PREVISUALIZACIÓN --
                    if (currentSvgContent) {
                        formData.append('svg_content', currentSvgContent);
                    }
                    formData.append('auto_read_success', autoReadSuccess ? '1' : '0');

                    var url = isEditMode ? urls.updateAjax(currentExportId) : urls.storeAjax;

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                hideForm();
                                loadProductionData();
                                updateDesignCardCounter(isEditMode ? 'updated' : 'created');

                                // ⭐ ACTUALIZACIÓN INMEDIATA de badges y contadores (optimista)
                                if (!isEditMode) {
                                    // Incrementar contador del tab de producción y tarjeta inmediatamente
                                    if (typeof window.incrementProductionTabCounter ===
                                        'function') {
                                        window.incrementProductionTabCounter();
                                    }

                                    // Incrementar inmediatamente el badge de la imagen vinculada
                                    var linkedImageId = $('#exportImageId').val();
                                    if (linkedImageId && typeof window.incrementImageBadge ===
                                        'function') {
                                        window.incrementImageBadge(linkedImageId);
                                    }
                                    // También incrementar el badge de imagen principal si coincide
                                    if (typeof window.incrementMainImageBadgeIfMatches ===
                                        'function') {
                                        window.incrementMainImageBadgeIfMatches(linkedImageId);
                                    }
                                }

                                // Refrescar badges desde servidor (verificación en segundo plano)
                                setTimeout(function() {
                                    if (typeof refreshGalleryProductionBadges ===
                                        'function') {
                                        refreshGalleryProductionBadges();
                                    }
                                }, 500);
                                showSuccessAlert(isEditMode ? '¡Actualizado!' : '¡Guardado!',
                                    isEditMode ?
                                    'La exportación se actualizó correctamente' :
                                    'La exportación se creó correctamente');
                            } else {
                                $btn.prop('disabled', false).find('span').text(isEditMode ?
                                    'Actualizar' : 'Guardar');
                                showErrorAlert('Error', response.error || 'Error al guardar');
                            }
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).find('span').text(isEditMode ?
                                'Actualizar' : 'Guardar');
                            var errorMsg = 'Error de conexión';
                            if (xhr.status === 404) {
                                errorMsg =
                                    'Ruta no encontrada (404). Verifica que las rutas estén registradas en web.php';
                            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            }
                            showErrorAlert('Error', errorMsg);
                        }
                    });
                }

                // Alertas
                function showSuccessAlert(title, text) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: title,
                            text: text,
                            timer: 2500,
                            showConfirmButton: false,
                            // Fix z-index to appear above Bootstrap modal
                            customClass: {
                                container: 'swal2-container-high-zindex'
                            }
                        });
                    }
                }

                function showErrorAlert(title, text) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: title,
                            text: text,
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#2563eb',
                            // Fix z-index to appear above Bootstrap modal
                            customClass: {
                                container: 'swal2-container-high-zindex'
                            }
                        });
                    } else {
                        alert(title + '\n' + text);
                    }
                }

                // Modal de detalles
                function showDetailModal(exportData) {
                    currentExportId = exportData.id;

                    $('#detailFileName').text(exportData.file_name);
                    $('#detailFileFormat').text(exportData.file_format);
                    $('#detailFileSize').text(exportData.file_size_formatted || '');

                    // Preview Icon Logic
                    var $iconContainer = $('#detailFileIcon');
                    if (exportData.svg_content && exportData.svg_content.trim() !== '') {
                        $iconContainer.html(exportData.svg_content);
                        $iconContainer.find('svg').css({
                            'width': '100%',
                            'height': '100%'
                        });
                        $iconContainer.css({
                            'padding': '0',
                            'overflow': 'hidden',
                            'background-color': 'transparent'
                        });
                        // Force override with style attribute in case CSS specificity fights back
                        $iconContainer.attr('style', $iconContainer.attr('style') +
                            '; background-color: transparent !important;');
                    } else {
                        // Restore default icon
                        $iconContainer.html('<i class="fas fa-file-code"></i>');
                        $iconContainer.removeAttr('style'); // Remove inline styles
                    }


                    // Status - SIN duplicar emoji
                    var statusBadge = $('#detailStatusBadge');
                    statusBadge.removeClass(
                        'status-borrador status-pendiente status-aprobado status-archivado');
                    statusBadge.addClass('status-' + exportData.status);

                    var statusEmoji = '🟤';
                    var statusLabel = 'Borrador';
                    if (exportData.status === 'pendiente') {
                        statusEmoji = '🟡';
                        statusLabel = 'Pendiente';
                    } else if (exportData.status === 'aprobado') {
                        statusEmoji = '🟢';
                        statusLabel = 'Aprobado';
                    } else if (exportData.status === 'archivado') {
                        statusEmoji = '⚫';
                        statusLabel = 'Archivado';
                    }

                    $('#detailStatusEmoji').text(statusEmoji);
                    $('#detailStatusText').text(statusLabel);

                    // Botones según estado
                    $('#btnSendToReview, #btnDetailApprove, #btnDetailReject, #btnDetailArchive, #btnDetailRestore')
                        .hide();

                    switch (exportData.status) {
                        case 'borrador':
                            $('#btnSendToReview').show();
                            break;
                        case 'pendiente':
                            $('#btnDetailApprove, #btnDetailReject').show();
                            break;
                        case 'aprobado':
                            $('#btnDetailArchive').show();
                            break;
                        case 'archivado':
                            $('#btnDetailRestore').show();
                            break;
                    }

                    updateWorkflowIndicator(exportData.status);

                    // Datos técnicos
                    $('#detailStitches').text(exportData.stitches_formatted || '0');
                    $('#detailColors').text(exportData.colors_count || '0');
                    $('#detailWidth').text(exportData.width_mm || '0');
                    $('#detailHeight').text(exportData.height_mm || '0');

                    // Colores
                    if (exportData.colors_detected && exportData.colors_detected.length > 0) {
                        renderDetailColors(exportData.colors_detected);
                        $('#detailColorsSection').show();
                    } else {
                        $('#detailColorsSection').hide();
                    }

                    // Info
                    $('#detailAppType').text(exportData.application_type_label || exportData
                        .application_type);
                    $('#detailAppLabel').text(exportData.application_label);

                    if (exportData.placement_description) {
                        $('#detailPlacement').text(exportData.placement_description);
                        $('#detailPlacementRow').show();
                    } else {
                        $('#detailPlacementRow').hide();
                    }

                    if (exportData.notes) {
                        $('#detailNotes').text(exportData.notes);
                        $('#detailNotesRow').show();
                    } else {
                        $('#detailNotesRow').hide();
                    }

                    // Meta
                    // Swap: Label as title, filename as meta
                    $('#detailFileName').text(exportData.application_label || '-');
                    $('#detailFileFormat').text(exportData.file_format || '');

                    // Show filename in meta: Filename • Size • Format
                    var metaParts = [];
                    if (exportData.file_name) metaParts.push(exportData.file_name);
                    if (exportData.file_size_formatted) metaParts.push(exportData.file_size_formatted);

                    if (exportData.file_format) {
                        // User requested to remove distinct format: just Filename and Size
                        // metaParts.push(exportData.file_format); 
                    }

                    $('#detailFileSize').text(metaParts.join(' • '));
                    // Hide duplicate format tag since it's in text now, or keep it. User wants "Title"
                    $('#detailFileFormat').hide();

                    $('#detailCreator').text(exportData.creator_name || 'Sistema');
                    $('#detailDate').text(exportData.created_at || '-');

                    if (exportData.approver_name) {
                        $('#detailApprover').text(exportData.approver_name);
                        $('#detailApproverRow').show();
                    } else {
                        $('#detailApproverRow').hide();
                    }

                    $('#btnDetailDownload').attr('href', exportData.download_url || '#');
                    exportsData[exportData.id] = exportData;

                    // Mover overlay al body para que cubra toda la pantalla (escapar del modal padre)
                    var $overlay = $('#exportDetailOverlay');
                    if (!$overlay.data('moved-to-body')) {
                        $overlay.appendTo('body');
                        $overlay.data('moved-to-body', true);
                    }
                    $overlay.fadeIn(200);
                }

                function updateWorkflowIndicator(currentStatus) {
                    var $indicator = $('#workflowIndicator');
                    var steps = ['borrador', 'pendiente', 'aprobado', 'archivado'];
                    var currentIndex = steps.indexOf(currentStatus);

                    $indicator.find('.workflow-step').removeClass('completed current');
                    $indicator.find('.workflow-line').removeClass('completed');

                    steps.forEach(function(step, index) {
                        var $step = $indicator.find('.workflow-step[data-step="' + step + '"]');
                        var $line = $step.next('.workflow-line');

                        if (index < currentIndex) {
                            $step.addClass('completed');
                            if ($line.length) $line.addClass('completed');
                        } else if (index === currentIndex) {
                            $step.addClass('current');
                        }
                    });
                }

                function hideDetailModal() {
                    $('#exportDetailOverlay').fadeOut(200);
                    currentExportId = null;
                }

                function renderDetailColors(colors) {
                    var container = $('#detailColorSwatches');
                    container.empty();
                    colors.forEach(function(color) {
                        var hex = color.hex || '#ccc';
                        var swatch = $('<div class="color-swatch">' +
                            '<div class="color-swatch-box" style="background-color: ' + hex +
                            '"></div>' +
                            '<span class="color-swatch-label">' + hex.toUpperCase() +
                            '</span>' +
                            '</div>');
                        container.append(swatch);
                    });
                }

                // Cambio de estado con confirmación
                function confirmStatusChange(exportId, newStatus, options) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: options.title,
                            text: options.text,
                            icon: options.icon,
                            showCancelButton: true,
                            reverseButtons: true, // Cancel left, Confirm right
                            confirmButtonColor: options.confirmColor || '#2563eb',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: options.confirmText,
                            cancelButtonText: 'Cancelar',
                            // Fix z-index to appear above Bootstrap modal
                            customClass: {
                                container: 'swal2-container-high-zindex'
                            }
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                changeStatusWithFeedback(exportId, newStatus, options);
                            }
                        });
                    } else {
                        if (confirm(options.title)) {
                            changeStatusWithFeedback(exportId, newStatus, options);
                        }
                    }
                }

                function changeStatusWithFeedback(exportId, newStatus, options) {
                    $('.footer-actions-status button').prop('disabled', true);

                    $.ajax({
                        url: urls.updateStatus(exportId),
                        type: 'POST',
                        data: {
                            _token: csrfToken,
                            status: newStatus
                        },
                        success: function(response) {
                            if (response.success) {
                                hideDetailModal();
                                loadProductionData();
                                showSuccessAlert(options.successTitle, options.successText);
                            } else {
                                showErrorAlert('Error', response.error ||
                                    'No se pudo cambiar el estado');
                            }
                        },
                        error: function(xhr) {
                            var error = 'Error de conexión';
                            if (xhr.status === 404) {
                                error = 'Ruta no encontrada. Verifica web.php';
                            }
                            showErrorAlert('Error', error);
                        },
                        complete: function() {
                            $('.footer-actions-status button').prop('disabled', false);
                        }
                    });
                }

                // Eliminar
                function deleteExport(id) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Eliminar exportación?',
                            text: 'Esta acción no se puede deshacer',
                            icon: 'warning',
                            showCancelButton: true,
                            reverseButtons: true, // Cancel left, Confirm right
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            // Fix z-index to appear above Bootstrap modal
                            customClass: {
                                container: 'swal2-container-high-zindex'
                            }
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                performDelete(id);
                            }
                        });
                    } else if (confirm('¿Eliminar esta exportación?')) {
                        performDelete(id);
                    }
                }

                function performDelete(id) {
                    $.ajax({
                        url: urls.destroyAjax(id),
                        type: 'DELETE',
                        data: {
                            _token: csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                hideDetailModal();
                                loadProductionData();
                                updateDesignCardCounter('deleted');
                                showSuccessAlert('¡Eliminado!',
                                    'La exportación se eliminó correctamente');
                            } else {
                                showErrorAlert('Error', response.error || 'Error al eliminar');
                            }
                        },
                        error: function(xhr) {
                            var error = xhr.status === 404 ? 'Ruta no encontrada' :
                                'Error de conexión';
                            showErrorAlert('Error', error);
                        }
                    });
                }

                // Actualizar contador
                function updateDesignCardCounter(action) {
                    var designId = currentDesignId;
                    $(document).trigger('export' + action.charAt(0).toUpperCase() + action.slice(1), {
                        designId: designId
                    });

                    $.get(urls.exportsCount(designId))
                        .done(function(response) {
                            if (response.success && response.count !== undefined) {
                                var count = response.count;
                                var $card = $('.design-card[data-design-id="' + designId + '"]');
                                if ($card.length) {
                                    $card.attr('data-exports', count);
                                    var $exports = $card.find('.design-exports');
                                    if ($exports.length) {
                                        $exports.find('.exports-number').text(count);
                                        $exports.find('.exports-text').text(count !== 1 ?
                                            'exportaciones' : 'exportación');
                                    }
                                }
                                $('#productionTotalCount').text(count);
                            }
                        });
                }

                // Event Listeners
                $(document).on('click', '#btnAddFirstExport, #btnAddExport', function(e) {
                    e.preventDefault();
                    showForm();
                });

                $(document).on('click', '#btnBackToList, #btnCancelExport', function(e) {
                    e.preventDefault();
                    hideForm();
                    loadProductionData();
                });

                $('#exportFile').on('change', function(e) {
                    var file = e.target.files[0];
                    if (file) analyzeFile(file);
                });

                var uploadZone = document.getElementById('uploadZone');
                if (uploadZone) {
                    uploadZone.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.classList.add('drag-over');
                    });
                    uploadZone.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        this.classList.remove('drag-over');
                    });
                    uploadZone.addEventListener('drop', function(e) {
                        e.preventDefault();
                        this.classList.remove('drag-over');
                        var file = e.dataTransfer.files[0];
                        if (file) {
                            $('#exportFile')[0].files = e.dataTransfer.files;
                            analyzeFile(file);
                        }
                    });
                }

                // ⭐ Botón de agregar nueva producción (desde la lista)
                $(document).on('click', '#btnAddNewExport', function(e) {
                    e.preventDefault();
                    showForm(null);
                });

                $(document).on('click', '#btnRemoveFile, #btnRemoveFileError', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetForm();
                });

                $('#btnSaveExport').on('click', function(e) {
                    e.preventDefault();
                    saveExport();
                });

                // ⭐ V5: Event handlers para filtros (solo variante, estado por badges)
                $('#filterVariant').on('change', function() {
                    currentFilter.variant = $(this).val();
                    applyFilters();
                });

                $('#btnClearFilters').on('click', function() {
                    currentFilter = {
                        status: '',
                        variant: ''
                    };
                    $('#filterVariant').val('');
                    $('.summary-badge').removeClass('active');
                    $('.summary-badge[data-filter=""]').addClass('active');
                    applyFilters();
                });

                $(document).on('click', '.summary-badge', function() {
                    var filter = $(this).data('filter');
                    currentFilter.status = filter;
                    applyFilters();
                });

                $(document).on('click', '.export-item-content, .btn-view-export', function(e) {
                    e.preventDefault();
                    var id = $(this).closest('.export-item').data('id') || $(this).data('id');
                    var exportData = exportsData[id];
                    if (exportData) showDetailModal(exportData);
                });

                $(document).on('click', '#btnCloseDetailOverlay', function() {
                    hideDetailModal();
                });

                $(document).on('click', '#exportDetailOverlay', function(e) {
                    if (e.target === this) hideDetailModal();
                });

                $(document).on('click', '#btnDetailEdit', function() {
                    var exportData = exportsData[currentExportId];
                    if (exportData) {
                        hideDetailModal();
                        showForm(exportData);
                    }
                });

                // Botones de estado
                $(document).on('click', '#btnSendToReview', function() {
                    if (currentExportId) {
                        confirmStatusChange(currentExportId, 'pendiente', {
                            title: '¿Enviar a revisión?',
                            text: 'El archivo quedará pendiente de aprobación.',
                            icon: 'question',
                            confirmColor: '#f59e0b',
                            confirmText: 'Sí, enviar',
                            successTitle: '¡Enviado!',
                            successText: 'El archivo está pendiente de aprobación.'
                        });
                    }
                });

                $(document).on('click', '#btnDetailApprove', function() {
                    if (currentExportId) {
                        confirmStatusChange(currentExportId, 'aprobado', {
                            title: '¿Aprobar archivo?',
                            text: 'El archivo quedará listo para producción.',
                            icon: 'success',
                            confirmColor: '#10b981',
                            confirmText: 'Sí, aprobar',
                            successTitle: '¡Aprobado!',
                            successText: 'El archivo está listo para producción.'
                        });
                    }
                });

                $(document).on('click', '#btnDetailReject', function() {
                    if (currentExportId) {
                        confirmStatusChange(currentExportId, 'borrador', {
                            title: '¿Rechazar archivo?',
                            text: 'El archivo volverá a estado borrador.',
                            icon: 'warning',
                            confirmColor: '#ef4444',
                            confirmText: 'Sí, rechazar',
                            successTitle: 'Rechazado',
                            successText: 'El archivo volvió a borrador.'
                        });
                    }
                });

                $(document).on('click', '#btnDetailArchive', function() {
                    if (currentExportId) {
                        confirmStatusChange(currentExportId, 'archivado', {
                            title: '¿Archivar archivo?',
                            text: 'El archivo se moverá al histórico.',
                            icon: 'info',
                            confirmColor: '#374151',
                            confirmText: 'Sí, archivar',
                            successTitle: 'Archivado',
                            successText: 'El archivo se movió al histórico.'
                        });
                    }
                });

                $(document).on('click', '#btnDetailRestore', function() {
                    if (currentExportId) {
                        confirmStatusChange(currentExportId, 'aprobado', {
                            title: '¿Restaurar archivo?',
                            text: 'El archivo volverá a estado aprobado.',
                            icon: 'question',
                            confirmColor: '#06b6d4',
                            confirmText: 'Sí, restaurar',
                            successTitle: '¡Restaurado!',
                            successText: 'El archivo está activo nuevamente.'
                        });
                    }
                });

                $(document).on('click', '.btn-delete-export', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    deleteExport($(this).data('id'));
                });

                $(document).on('click', '#production-tab', function() {
                    // Solo cargar datos si no hay petición de ir directo al formulario
                    if (!skipLoadAndShowForm) {
                        setTimeout(loadProductionData, 100);
                    }
                });

                $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                    if (e.target.id === 'production-tab') {
                        // Verificar si debemos ir directo al formulario
                        if (skipLoadAndShowForm) {
                            skipLoadAndShowForm = false; // Resetear bandera
                            showFormDirect();
                        } else {
                            loadProductionData();
                        }
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && $('#exportDetailOverlay').is(':visible')) {
                        hideDetailModal();
                    }
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initProductionTab);
        } else {
            initProductionTab();
        }
    })();
</script>
