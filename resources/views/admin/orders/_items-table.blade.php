{{-- 2. PRODUCTOS - Responsive Table with Horizontal Scroll --}}
@php
    // Calcular totales técnicos globales del pedido
    $totalDisenosGlobal = 0;
    $totalPuntadasGlobal = 0;
    $totalEstimadoGlobal = 0;
    $itemsConDisenos = 0;
    $complejidadTotal = 'Baja';

    foreach ($order->items as $itm) {
        if ($itm->requiresTechnicalDesigns() && $itm->designExports->count() > 0) {
            $itemsConDisenos++;
            foreach ($itm->designExports as $de) {
                $totalDisenosGlobal++;
                $puntadas = $de->stitches_count ?? 0;
                $totalPuntadasGlobal += $puntadas;
            }
            if ($itm->product && $itm->product->embroidery_cost > 0) {
                $totalEstimadoGlobal += $itm->product->embroidery_cost * $itm->quantity;
            }
        } elseif ($itm->product && $itm->product->embroidery_cost > 0) {
            $totalPuntadasGlobal += $itm->product->total_stitches * $itm->quantity;
            $totalEstimadoGlobal += $itm->product->embroidery_cost * $itm->quantity;
        }
    }

    if ($totalPuntadasGlobal > 100000) {
        $complejidadTotal = 'Alta';
        $complejidadColor = '#c62828';
        $complejidadBg = '#ffebee';
    } elseif ($totalPuntadasGlobal > 30000) {
        $complejidadTotal = 'Media';
        $complejidadColor = '#e65100';
        $complejidadBg = '#fff3e0';
    } else {
        $complejidadTotal = 'Baja';
        $complejidadColor = '#2e7d32';
        $complejidadBg = '#e8f5e9';
    }
@endphp

<div class="card card-section-productos items-card-responsive">
    <div class="card-header items-card-header">
        <h5 class="mb-0 d-flex align-items-center justify-content-between">
            <span><i class="fas fa-box mr-2"></i> Productos</span>
            <span class="badge badge-light items-count-badge">{{ $order->items->count() }}</span>
        </h5>
    </div>
    <div class="card-body p-0">
        {{-- TABLA DE PRODUCTOS (responsive con scroll horizontal) --}}
        <div class="table-responsive">
            <table class="table mb-0 items-table-desktop">
                <thead>
                    <tr>
                        <th style="width: 70px;">Foto</th>
                        <th>Producto</th>
                        <th class="text-center" style="width: 60px;">Cant.</th>
                        <th class="text-right" style="width: 100px;">P. Unit.</th>
                        <th class="text-center" style="width: 70px;">Estado</th>
                        <th class="text-right" style="width: 100px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        @include('admin.orders._item-row-desktop', ['item' => $item, 'order' => $order])
                    @endforeach
                </tbody>
                <tfoot class="items-table-footer">
                    @include('admin.orders._items-totals', ['order' => $order])
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- RESUMEN TÉCNICO DEL PEDIDO (Responsive) --}}
@if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_IN_PRODUCTION]))
    <div class="card mt-3 {{ $totalDisenosGlobal == 0 ? 'd-none' : '' }} technical-summary-card" id="technicalSummaryCard">
        <div class="card-header technical-summary-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                <h6 class="mb-0 technical-summary-title">
                    <i class="fas fa-calculator mr-2"></i>
                    <span class="d-none d-sm-inline">Resumen Técnico del Pedido</span>
                    <span class="d-sm-none">Resumen Técnico</span>
                </h6>
                <span class="badge complexity-badge" id="complexityBadge"
                    style="background: {{ $complejidadBg }}; color: {{ $complejidadColor }};">
                    {{ $complejidadTotal }}
                </span>
            </div>
        </div>
        <div class="card-body technical-summary-body">
            <div class="technical-stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Items c/diseño</span>
                    <span class="stat-value" id="summaryItemsConDisenos">{{ $itemsConDisenos }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total diseños</span>
                    <span class="stat-value stat-designs" id="summaryTotalDisenos">{{ $totalDisenosGlobal }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Puntadas</span>
                    <span class="stat-value stat-stitches" id="summaryTotalPuntadas">{{ number_format($totalPuntadasGlobal) }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Est. Técnico</span>
                    <span class="stat-value stat-estimate" id="summaryEstimadoTecnico">${{ number_format($totalEstimadoGlobal, 2) }}</span>
                </div>
            </div>
            <div class="technical-note-mobile">
                <i class="fas fa-info-circle"></i>
                <span>Estimado pre-producción. El costo real se calcula según consumo de tiempo de máquina e hilos.</span>
            </div>
        </div>
    </div>
@endif

{{-- MODALES DE MEDIDAS --}}
@foreach ($order->items as $item)
    @php
        $measurements = is_array($item->measurements) ? $item->measurements : [];
        $hasMeasurements = !empty($measurements) && count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) > 0;
        $measurementImages = [
            'busto' => 'busto.png',
            'alto_cintura' => 'alto_cintura.png',
            'cintura' => 'cintura.png',
            'cadera' => 'cadera.png',
            'largo' => 'largo.png',
            'largo_vestido' => 'largo_vestido.png',
        ];
        $historyRecord = null;
        if ($item->measurement_history_id) {
            $historyRecord = \App\Models\ClientMeasurementHistory::with('creator')->find($item->measurement_history_id);
        }
    @endphp
    @if ($hasMeasurements)
        <div class="modal fade" id="measurementsModal{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-measurements-responsive">
                <div class="modal-content">
                    <div class="modal-header modal-header-measurements">
                        <h5 class="modal-title">
                            <i class="fas fa-ruler-combined mr-2"></i>
                            <span class="d-none d-sm-inline">Medidas: {{ $item->product_name }}</span>
                            <span class="d-sm-none">Medidas</span>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if ($historyRecord)
                            <div class="measurement-traceability-mobile">
                                @php
                                    $sourceColors = [
                                        'order' => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'fa-shopping-cart'],
                                        'manual' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'fa-user-edit'],
                                        'import' => ['bg' => '#fff3e0', 'color' => '#e65100', 'icon' => 'fa-file-import'],
                                    ];
                                    $sourceStyle = $sourceColors[$historyRecord->source] ?? ['bg' => '#f5f5f5', 'color' => '#757575', 'icon' => 'fa-question'];
                                @endphp
                                <span class="source-badge" style="background: {{ $sourceStyle['bg'] }}; color: {{ $sourceStyle['color'] }};">
                                    <i class="fas {{ $sourceStyle['icon'] }}"></i> {{ $historyRecord->source_label }}
                                </span>
                                <span class="capture-date">
                                    <i class="fas fa-calendar-alt"></i> {{ $historyRecord->captured_at?->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif

                        <div class="measurements-grid-mobile">
                            @foreach ($measurements as $key => $value)
                                @if (!empty($value) && $value !== '0' && $key !== 'save_to_client')
                                    @php
                                        $imageName = $measurementImages[$key] ?? null;
                                        $labelText = str_replace('_', ' ', strtoupper($key));
                                    @endphp
                                    <div class="measurement-card-mobile">
                                        @if ($imageName)
                                            <img src="{{ asset('images/' . $imageName) }}" alt="{{ $labelText }}" class="measurement-img-mobile">
                                        @endif
                                        <div class="measurement-label-mobile">{{ $labelText }}</div>
                                        <div class="measurement-value-mobile">{{ $value }} <span>cm</span></div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if ($historyRecord && $historyRecord->notes)
                            <div class="measurement-notes-mobile">
                                <i class="fas fa-sticky-note"></i> {{ $historyRecord->notes }}
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer modal-footer-mobile">
                        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                            <button type="button" class="btn btn-outline-primary btn-mobile-action"
                                onclick="$('#measurementsModal{{ $item->id }}').modal('hide'); openItemMeasurementsEdit({{ $item->id }}, '{{ addslashes($item->product_name) }}', {{ json_encode($measurements) }})">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                        @endif
                        <button type="button" class="btn btn-secondary btn-mobile-action" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- ESTILOS RESPONSIVOS PARA WEB APP --}}
<style>
    /* =====================================================
       RESPONSIVE TABLE STYLES
       ===================================================== */

    /* Base Card Styles */
    .items-card-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .items-card-header {
        background: linear-gradient(135deg, #343a40 0%, #23272b 100%);
        color: white;
        padding: 12px 16px;
    }

    .items-count-badge {
        background: rgba(255,255,255,0.2);
        color: white;
        font-size: 14px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    /* Table Styles */
    .items-table-desktop {
        font-size: 14px;
    }

    .items-table-desktop thead {
        background: #343a40;
    }

    .items-table-desktop thead th {
        color: white;
        font-weight: 600;
        padding: 12px 10px;
        border: none;
        white-space: nowrap;
    }

    .items-table-desktop tbody td {
        padding: 12px 10px;
        vertical-align: middle;
    }

    .items-table-footer {
        background: #f8f9fa;
    }

    /* Responsive Table - Mobile */
    @media (max-width: 991px) {
        .items-table-desktop {
            font-size: 13px !important;
        }

        .items-table-desktop thead th,
        .items-table-desktop tbody td {
            padding: 8px 6px !important;
            white-space: nowrap;
        }
    }

    /* Technical Summary Responsive */
    .technical-summary-card {
        border: 1px solid #7b1fa2;
        border-radius: 12px;
        overflow: hidden;
    }

    .technical-summary-header {
        background: linear-gradient(135deg, #7b1fa2 0%, #6a1b9a 100%);
        color: white;
        padding: 12px 16px;
    }

    .technical-summary-title {
        font-size: 14px;
        font-weight: 600;
    }

    .complexity-badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .technical-summary-body {
        padding: 16px;
    }

    .technical-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-item {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .stat-label {
        display: block;
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #1565c0;
    }

    .stat-designs { color: #2e7d32; }
    .stat-stitches { color: #e65100; }
    .stat-estimate { color: #7b1fa2; }

    .technical-note-mobile {
        margin-top: 12px;
        padding: 10px;
        background: #fff3e0;
        border-radius: 8px;
        font-size: 12px;
        color: #e65100;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .technical-note-mobile i {
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Modal Responsive */
    .modal-measurements-responsive .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }

    .modal-header-measurements {
        background: #6f42c1;
        color: white;
        padding: 14px 16px;
    }

    .modal-header-measurements .modal-title {
        font-size: 16px;
    }

    .measurement-traceability-mobile {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        padding: 10px;
        background: #f3e5f5;
        border-radius: 8px;
    }

    .measurement-traceability-mobile .source-badge,
    .measurement-traceability-mobile .capture-date {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .measurements-grid-mobile {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .measurement-card-mobile {
        text-align: center;
        padding: 12px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .measurement-img-mobile {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-bottom: 8px;
    }

    .measurement-label-mobile {
        font-size: 11px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .measurement-value-mobile {
        font-size: 24px;
        font-weight: 700;
        color: #6f42c1;
    }

    .measurement-value-mobile span {
        font-size: 14px;
        font-weight: 400;
        color: #6c757d;
    }

    .measurement-notes-mobile {
        margin-top: 12px;
        padding: 10px;
        background: #fff8e1;
        border-radius: 8px;
        font-size: 13px;
        color: #f57c00;
    }

    .modal-footer-mobile {
        background: #f8f9fa;
        padding: 12px 16px;
        gap: 8px;
    }

    .btn-mobile-action {
        flex: 1;
        padding: 10px 16px;
        font-size: 14px;
        border-radius: 8px;
    }

    /* =====================================================
       TABLET BREAKPOINT (md)
       ===================================================== */
    @media (min-width: 576px) {
        .technical-stats-grid {
            grid-template-columns: repeat(4, 1fr);
        }

        .measurements-grid-mobile {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* =====================================================
       SAFE AREA (iPhone X+, notch devices)
       ===================================================== */
    @supports (padding: max(0px)) {
        .modal-footer-mobile {
            padding-bottom: max(12px, env(safe-area-inset-bottom));
        }
    }

    /* =====================================================
       TOUCH OPTIMIZATIONS
       ===================================================== */
    @media (hover: none) and (pointer: coarse) {
        .btn-mobile-action {
            min-height: 48px;
        }
    }
</style>

{{-- MODAL: SELECTOR DE DISEÑO PARA ITEM (Responsive) --}}
@if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
    <div class="modal fade" id="selectDesignForItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); color: white; padding: 14px 16px;">
                    <h5 class="modal-title" id="selectDesignForItemModalTitle" style="font-size: 16px;">
                        <i class="fas fa-link mr-2"></i> Vincular diseño
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 1;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 16px;">
                    <input type="hidden" id="currentItemId" value="">
                    <input type="hidden" id="currentItemName" value="">
                    <div class="mb-3 p-3 rounded" style="background: #e3f2fd; border-left: 4px solid #1565c0;">
                        <strong style="color: #0d47a1; font-size: 14px;" id="selectingForProduct">Producto</strong>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchDesignForItem"
                                placeholder="Buscar diseño..." autocomplete="off"
                                style="height: 48px; font-size: 16px; border-radius: 8px 0 0 8px;">
                            <div class="input-group-append">
                                <button type="button" class="btn" id="btnSearchDesignForItem"
                                    style="background: #1565c0; color: white; height: 48px; padding: 0 20px; border-radius: 0 8px 8px 0;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="designsForItemResults" class="design-results-mobile" style="max-height: 50vh; overflow-y: auto;"></div>
                    <div id="designsForItemLoader" class="text-center p-4 d-none">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2" style="color: #1565c0;">Buscando diseños...</p>
                    </div>
                    <div id="designsForItemEmpty" class="text-center p-4 d-none" style="background: #fff3e0; border-radius: 12px;">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color: #ff9800;"></i>
                        <p style="color: #e65100; margin: 0;">No se encontraron diseños aprobados</p>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f5f5f5; padding: 12px 16px;">
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal"
                        style="padding: 12px; font-size: 15px; border-radius: 8px;">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: PREVIEW DE DISEÑO --}}
    <div class="modal fade" id="designPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: #1565c0; color: white; padding: 14px 16px;">
                    <h5 class="modal-title" style="font-size: 16px;">
                        <i class="fas fa-vector-square mr-2"></i> <span id="previewDesignName">Diseño</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center" style="padding: 20px;">
                    <div id="previewDesignImage" style="min-height: 200px; background: #f5f5f5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #1565c0;"></i>
                    </div>
                    <div id="previewDesignDetails" style="text-align: left;"></div>
                </div>
                <div class="modal-footer" style="padding: 12px 16px;">
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal"
                        style="padding: 12px; border-radius: 8px;">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: SELECTOR DE EXTRAS PARA ITEM --}}
    <div class="modal fade" id="selectExtraForItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0277bd 0%, #01579b 100%); color: white; padding: 14px 16px;">
                    <h5 class="modal-title" id="selectExtraForItemModalTitle" style="font-size: 16px;">
                        <i class="fas fa-box mr-2"></i> Agregar extra
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 1;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 16px;">
                    <input type="hidden" id="currentExtraItemId" value="">
                    <input type="hidden" id="currentExtraItemName" value="">
                    <div class="mb-3 p-3 rounded" style="background: #e3f2fd; border-left: 4px solid #0277bd;">
                        <strong style="color: #01579b; font-size: 14px;" id="selectingExtraForProduct">Producto</strong>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchExtraForItem"
                                placeholder="Buscar extra..." autocomplete="off"
                                style="height: 48px; font-size: 16px; border-radius: 8px 0 0 8px;">
                            <div class="input-group-append">
                                <span class="input-group-text" style="border-radius: 0 8px 8px 0;">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div id="extrasForItemLoader" class="text-center py-4 d-none">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #0277bd;"></i>
                        <p class="mt-2 mb-0" style="color: #666;">Cargando extras...</p>
                    </div>
                    <div id="extrasForItemEmpty" class="text-center py-4 d-none">
                        <i class="fas fa-box-open fa-2x" style="color: #ccc;"></i>
                        <p class="mt-2 mb-0" style="color: #999;">No hay extras disponibles</p>
                    </div>
                    <div id="extrasForItemResults" class="list-group extra-results-mobile" style="max-height: 350px; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer" style="padding: 12px 16px;">
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal"
                        style="padding: 12px; font-size: 15px; border-radius: 8px;">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal fullscreen on mobile */
        @media (max-width: 575.98px) {
            .modal-fullscreen-sm-down {
                margin: 0;
                max-width: 100%;
                height: 100%;
            }
            .modal-fullscreen-sm-down .modal-content {
                height: 100%;
                border-radius: 0 !important;
            }
            .modal-fullscreen-sm-down .modal-body {
                overflow-y: auto;
            }
        }

        /* Design results mobile */
        .design-results-mobile .list-group-item {
            padding: 12px;
            border-radius: 8px !important;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
        }

        .design-results-mobile .list-group-item:active {
            background: #e3f2fd;
        }

        /* Extra results mobile */
        .extra-results-mobile .list-group-item {
            padding: 12px;
            border-radius: 8px !important;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
        }

        .extra-results-mobile .list-group-item:hover {
            background: #e3f2fd;
            border-color: #90caf9;
        }

        .extra-results-mobile .list-group-item:active {
            background: #bbdefb;
        }
    </style>
@endif

@push('js')
    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
        <script>
            (function() {
                'use strict';
                var orderId = {{ $order->id }};
                var allDesignsForItem = [];
                var currentSourceType = 'manual';

                window.openDesignSelectorForItem = function(itemId, itemName, sourceType) {
                    currentSourceType = sourceType || 'manual';
                    document.getElementById('currentItemId').value = itemId;
                    document.getElementById('currentItemName').value = itemName;
                    document.getElementById('selectingForProduct').textContent = itemName;
                    document.getElementById('selectDesignForItemModalTitle').innerHTML =
                        '<i class="fas fa-link mr-2"></i> Vincular diseño';
                    document.getElementById('searchDesignForItem').value = '';
                    document.getElementById('designsForItemResults').innerHTML = '';
                    document.getElementById('designsForItemResults').classList.remove('d-none');
                    document.getElementById('designsForItemLoader').classList.add('d-none');
                    document.getElementById('designsForItemEmpty').classList.add('d-none');
                    $('#selectDesignForItemModal').modal('show');
                    if (allDesignsForItem.length === 0) {
                        loadDesignsForItem();
                    } else {
                        renderDesignsForItem(allDesignsForItem);
                    }
                };

                function loadDesignsForItem() {
                    document.getElementById('designsForItemResults').classList.add('d-none');
                    document.getElementById('designsForItemLoader').classList.remove('d-none');
                    document.getElementById('designsForItemEmpty').classList.add('d-none');
                    fetch('{{ route('admin.products.ajax.approved_exports') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            allDesignsForItem = Array.isArray(data) ? data : (data.data || []);
                            filterAndRenderDesignsForItem();
                        })
                        .catch(function() {
                            document.getElementById('designsForItemLoader').classList.add('d-none');
                            document.getElementById('designsForItemEmpty').classList.remove('d-none');
                        });
                }

                function filterAndRenderDesignsForItem() {
                    document.getElementById('designsForItemLoader').classList.add('d-none');
                    var query = document.getElementById('searchDesignForItem').value.trim().toLowerCase();
                    var filtered = allDesignsForItem;
                    if (query) {
                        filtered = allDesignsForItem.filter(function(item) {
                            var text = [item.export_name || '', item.design_name || '', item.app_type_name || '']
                                .join(' ').toLowerCase();
                            return text.indexOf(query) !== -1;
                        });
                    }
                    if (filtered.length === 0) {
                        document.getElementById('designsForItemResults').classList.add('d-none');
                        document.getElementById('designsForItemEmpty').classList.remove('d-none');
                    } else {
                        renderDesignsForItem(filtered);
                        document.getElementById('designsForItemResults').classList.remove('d-none');
                        document.getElementById('designsForItemEmpty').classList.add('d-none');
                    }
                }

                function renderDesignsForItem(items) {
                    var container = document.getElementById('designsForItemResults');
                    container.innerHTML = '';
                    items.forEach(function(item) {
                        var preview = item.svg_content ?
                            '<div style="width:44px;height:44px;background:white;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e0e0e0;">' +
                            item.svg_content + '</div>' :
                            '<div style="width:44px;height:44px;background:#1565c0;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-vector-square" style="color:white;"></i></div>';
                        var name = item.export_name || item.design_name || 'Diseño #' + item.id;
                        var details1 = [
                            item.stitches ? item.stitches.toLocaleString() + ' pts' : '',
                            item.dimensions_label || '',
                        ].filter(Boolean).join(' • ');

                        var html =
                            '<div class="list-group-item d-flex align-items-center" style="cursor:pointer;gap:12px;" onclick="linkDesignToItemConfirm(' +
                            item.id + ', \'' + escapeHtml(name).replace(/'/g, "\\'") + '\')">' +
                            preview +
                            '<div style="flex:1;min-width:0;">' +
                            '<div style="font-size:14px;font-weight:600;color:#212529;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(name) + '</div>' +
                            '<div style="font-size:12px;color:#6c757d;">' + escapeHtml(details1) + '</div>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm" style="background:#1565c0;color:white;padding:8px 12px;border-radius:8px;" onclick="event.stopPropagation();linkDesignToItemConfirm(' +
                            item.id + ', \'' + escapeHtml(name).replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-link"></i>' +
                            '</button>' +
                            '</div>';
                        container.insertAdjacentHTML('beforeend', html);
                    });
                }

                window.linkDesignToItemConfirm = function(designExportId, designName) {
                    var itemId = document.getElementById('currentItemId').value;
                    var itemName = document.getElementById('currentItemName').value;
                    Swal.fire({
                        title: '¿Vincular diseño?',
                        html: '<p style="font-size:14px;">Se vinculará <strong>' + escapeHtml(designName) +
                            '</strong> al producto <strong>' + escapeHtml(itemName) + '</strong></p>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#1565c0',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-link mr-1"></i> Vincular',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            linkDesignToItemExecute(itemId, designExportId, designName);
                        }
                    });
                };

                function linkDesignToItemExecute(itemId, designExportId, designName) {
                    Swal.fire({
                        title: 'Vinculando...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: function() { Swal.showLoading(); }
                    });

                    var notes = currentSourceType === 'text'
                        ? 'Diseño para texto/personalización (vinculado manualmente)'
                        : 'Diseño adicional (vinculado manualmente)';

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/link-design', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ design_export_id: designExportId, notes: notes })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Vinculado!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 1500,
                                    timerProgressBar: true
                                }).then(function() {
                                    $('#selectDesignForItemModal').modal('hide');
                                    refreshItemDesigns(itemId);
                                    refreshSidebarDesigns();
                                    refreshTechnicalSummary();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(function() {
                            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                        });
                }

                window.unlinkDesignFromItem = function(orderId, itemId, designExportId, designName) {
                    Swal.fire({
                        title: '¿Desvincular diseño?',
                        html: '<p style="font-size:14px;">Se desvinculará <strong>' + escapeHtml(designName) + '</strong></p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#c62828',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-unlink mr-1"></i> Desvincular',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Desvinculando...',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: function() { Swal.showLoading(); }
                            });
                            fetch('/admin/orders/' + orderId + '/items/' + itemId + '/unlink-design/' + designExportId, {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (data.success) {
                                        Swal.fire({ title: 'Desvinculado', icon: 'success', timer: 1500 })
                                            .then(function() {
                                                refreshItemDesigns(itemId);
                                                refreshSidebarDesigns();
                                                refreshTechnicalSummary();
                                            });
                                    } else {
                                        Swal.fire('Error', data.message, 'error');
                                    }
                                })
                                .catch(function() {
                                    Swal.fire('Error', 'No se pudo conectar', 'error');
                                });
                        }
                    });
                };

                var changingDesignId = null;

                window.changeDesignForItem = function(orderId, itemId, currentDesignId, designName, productName) {
                    changingDesignId = currentDesignId;
                    document.getElementById('currentItemId').value = itemId;
                    document.getElementById('currentItemName').value = productName;
                    document.getElementById('selectingForProduct').textContent = productName;
                    document.getElementById('selectDesignForItemModalTitle').innerHTML =
                        '<i class="fas fa-exchange-alt mr-2"></i> Cambiar diseño';
                    document.getElementById('searchDesignForItem').value = '';
                    document.getElementById('designsForItemResults').innerHTML = '';
                    $('#selectDesignForItemModal').modal('show');
                    if (allDesignsForItem.length === 0) {
                        loadDesignsForItem();
                    } else {
                        renderDesignsForItem(allDesignsForItem);
                    }
                };

                var originalLinkDesignToItemConfirm = window.linkDesignToItemConfirm;
                window.linkDesignToItemConfirm = function(designExportId, designName) {
                    var itemId = document.getElementById('currentItemId').value;
                    var itemName = document.getElementById('currentItemName').value;

                    if (changingDesignId) {
                        Swal.fire({
                            title: '¿Cambiar diseño?',
                            html: '<p style="font-size:14px;">Se reemplazará el diseño actual por <strong>' + escapeHtml(designName) + '</strong></p>',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#f57c00',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-exchange-alt mr-1"></i> Cambiar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                replaceDesignForItem(itemId, changingDesignId, designExportId, designName);
                            }
                        });
                    } else {
                        Swal.fire({
                            title: '¿Vincular diseño?',
                            html: '<p style="font-size:14px;">Se vinculará <strong>' + escapeHtml(designName) +
                                '</strong> al producto <strong>' + escapeHtml(itemName) + '</strong></p>',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#1565c0',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-link mr-1"></i> Vincular',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                linkDesignToItemExecute(itemId, designExportId, designName);
                            }
                        });
                    }
                };

                function replaceDesignForItem(itemId, oldDesignId, newDesignId, newDesignName) {
                    Swal.fire({
                        title: 'Cambiando diseño...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: function() { Swal.showLoading(); }
                    });

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/unlink-design/' + oldDesignId, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            return fetch('/admin/orders/' + orderId + '/items/' + itemId + '/link-design', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ design_export_id: newDesignId })
                            });
                        } else {
                            throw new Error(data.message || 'Error al desvincular diseño');
                        }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Diseño cambiado!',
                                icon: 'success',
                                timer: 1500
                            }).then(function() {
                                $('#selectDesignForItemModal').modal('hide');
                                changingDesignId = null;
                                refreshItemDesigns(itemId);
                                refreshSidebarDesigns();
                                refreshTechnicalSummary();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo vincular el nuevo diseño', 'error');
                        }
                    })
                    .catch(function(err) {
                        Swal.fire('Error', err.message || 'No se pudo completar la operación', 'error');
                    });
                }

                $('#selectDesignForItemModal').on('hidden.bs.modal', function() {
                    changingDesignId = null;
                });

                window.showDesignPreview = function(designExportId, itemId) {
                    $('#designPreviewModal').modal('show');
                    document.getElementById('previewDesignName').textContent = 'Cargando...';
                    document.getElementById('previewDesignImage').innerHTML = '<i class="fas fa-spinner fa-spin fa-2x" style="color:#1565c0;"></i>';
                    document.getElementById('previewDesignDetails').innerHTML = '';
                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/designs', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                var design = data.data.find(function(d) { return d.id == designExportId; });
                                if (design) {
                                    document.getElementById('previewDesignName').textContent = design.name || 'Diseño #' + design.id;
                                    var imgHtml = design.svg_content ?
                                        '<div style="max-width:300px;max-height:300px;margin:auto;">' + design.svg_content + '</div>' :
                                        '<i class="fas fa-vector-square fa-4x" style="color:#1565c0;"></i>';
                                    document.getElementById('previewDesignImage').innerHTML = imgHtml;
                                }
                            }
                        });
                };

                document.getElementById('btnSearchDesignForItem')?.addEventListener('click', filterAndRenderDesignsForItem);
                document.getElementById('searchDesignForItem')?.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); filterAndRenderDesignsForItem(); }
                });
                document.getElementById('searchDesignForItem')?.addEventListener('input', function() {
                    if (allDesignsForItem.length > 0) {
                        clearTimeout(this.debounce);
                        this.debounce = setTimeout(filterAndRenderDesignsForItem, 300);
                    }
                });

                function escapeHtml(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                // Refresh functions - Actualización dinámica sin recargar página
                window.refreshItemDesigns = function(itemId) {
                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/designs', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var designs = data.data || [];
                            var totalPuntadas = data.total_stitches || 0;
                            var productName = data.product_name || '';
                            var quantity = data.quantity || 1;
                            var embroideryRatePerThousand = data.embroidery_rate_per_thousand || 0;
                            var requiresDesigns = data.requires_designs || false;
                            var costoCalculado = (totalPuntadas / 1000) * embroideryRatePerThousand * quantity;

                            // Actualizar fila (tr) - quitar/agregar clase danger
                            var itemRow = document.querySelector('[data-item-row="' + itemId + '"]');

                            // Actualizar DESKTOP
                            updateDesktopView(itemId, designs, totalPuntadas, productName, embroideryRatePerThousand, quantity, requiresDesigns, itemRow);

                            // Actualizar MOBILE
                            updateMobileView(itemId, designs, totalPuntadas, productName, embroideryRatePerThousand, quantity, requiresDesigns);
                        }
                    });
                };

                function updateDesktopView(itemId, designs, totalPuntadas, productName, embroideryRatePerThousand, quantity, requiresDesigns, itemRow) {
                    var designBadge = document.querySelector('.d-none.d-lg-block [data-item-designs="' + itemId + '"]');
                    var designDetails = document.getElementById('designDetails' + itemId);
                    var costoCalculado = (totalPuntadas / 1000) * embroideryRatePerThousand * quantity;

                    if (designs.length > 0) {
                        // Quitar clase danger de la fila
                        if (itemRow) itemRow.classList.remove('table-danger');

                        if (designBadge) {
                            // Actualizar badge con diseños
                            var badgeHtml = '<i class="fas fa-check-circle mr-1" style="color: #2e7d32; font-size: 14px;"></i>' +
                                '<span style="font-size: 14px; color: #1b5e20; font-weight: 600;">' + designs.length + ' diseño(s)</span>' +
                                '<span style="font-size: 14px; color: #388e3c; margin-left: 4px;"> · ' + numberFormat(totalPuntadas) + ' pts</span>';
                            if (embroideryRatePerThousand > 0) {
                                badgeHtml += '<span style="font-size: 14px; color: #7b1fa2; margin-left: 4px; font-weight: 600;"> · Est. $' + numberFormat2(costoCalculado) + '</span>';
                            }
                            badgeHtml += '<i class="fas fa-chevron-down ml-2" style="font-size: 12px; color: #1565c0;"></i>';
                            designBadge.innerHTML = badgeHtml;
                            designBadge.style.background = '#e8f5e9';
                            designBadge.style.border = '1px solid #a5d6a7';
                            designBadge.style.cursor = 'pointer';
                            if (!designBadge.getAttribute('data-toggle')) {
                                designBadge.setAttribute('data-toggle', 'collapse');
                                designBadge.setAttribute('data-target', '#designDetails' + itemId);
                            }
                        }

                        // Actualizar panel colapsable desktop
                        if (designDetails) {
                            designDetails.innerHTML = buildDesignsDetailHtml(designs, totalPuntadas, embroideryRatePerThousand, quantity, productName, itemId);
                        }
                    } else {
                        // Sin diseños
                        if (itemRow && requiresDesigns) itemRow.classList.add('table-danger');

                        if (designBadge) {
                            designBadge.innerHTML = '<i class="fas fa-exclamation-triangle mr-1" style="color: #856404; font-size: 14px;"></i>' +
                                '<span style="font-size: 14px; color: #856404; font-weight: 500;">Sin diseño</span>' +
                                '<span class="ml-2" style="font-size: 14px; color: #1565c0;"><i class="fas fa-link mr-1"></i>Vincular diseño (texto, logo, etc.)</span>';
                            designBadge.style.background = '#fff3cd';
                            designBadge.style.border = '1px solid #ffc107';
                            designBadge.style.cursor = 'default';
                            designBadge.removeAttribute('data-toggle');
                            designBadge.removeAttribute('data-target');
                        }
                        if (designDetails) designDetails.innerHTML = '';
                    }
                }

                function updateMobileView(itemId, designs, totalPuntadas, productName, embroideryRatePerThousand, quantity, requiresDesigns) {
                    var mobileCard = document.querySelector('.d-lg-none [data-item-row="' + itemId + '"]');
                    var mobileBadge = document.querySelector('.d-lg-none [data-item-designs="' + itemId + '"]');
                    var mobileDetails = document.getElementById('designDetailsMobile' + itemId);
                    var costoCalculado = (totalPuntadas / 1000) * embroideryRatePerThousand * quantity;

                    if (designs.length > 0) {
                        // Quitar clase danger del card móvil
                        if (mobileCard) mobileCard.classList.remove('item-card-danger');

                        if (mobileBadge) {
                            mobileBadge.className = 'badge-btn-mobile badge-designs-ok';
                            mobileBadge.innerHTML = '<i class="fas fa-check-circle"></i>' +
                                '<span>' + designs.length + ' diseño(s)</span>' +
                                '<span class="design-pts">' + numberFormat(totalPuntadas) + ' pts</span>' +
                                '<i class="fas fa-chevron-down chevron-icon"></i>';
                            if (!mobileBadge.getAttribute('data-toggle')) {
                                mobileBadge.setAttribute('data-toggle', 'collapse');
                                mobileBadge.setAttribute('data-target', '#designDetailsMobile' + itemId);
                            }
                        }

                        // Actualizar panel móvil
                        if (mobileDetails) {
                            mobileDetails.innerHTML = '<div class="designs-detail-mobile">' +
                                buildMobileDesignsHtml(designs, totalPuntadas, embroideryRatePerThousand, quantity, productName, itemId) +
                                '</div>';
                        }
                    } else {
                        // Sin diseños
                        if (mobileCard && requiresDesigns) mobileCard.classList.add('item-card-danger');

                        if (mobileBadge) {
                            mobileBadge.className = 'badge-btn-mobile badge-designs-warning';
                            mobileBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i>' +
                                '<span>Sin diseño</span>' +
                                '<span class="link-design-text">Vincular</span>';
                            mobileBadge.removeAttribute('data-toggle');
                            mobileBadge.removeAttribute('data-target');
                        }
                        if (mobileDetails) mobileDetails.innerHTML = '';
                    }
                }

                function buildDesignsDetailHtml(designs, totalPuntadas, embroideryRatePerThousand, quantity, productName, itemId) {
                    var html = '<div style="background: #fafafa; border-radius: 4px; padding: 6px; border: 1px solid #e0e0e0;">';
                    designs.forEach(function(design, idx) {
                        var isLast = idx === designs.length - 1;
                        var designName = escapeHtml(design.name || 'Diseño');
                        var designNameEscaped = designName.replace(/'/g, "\\'");
                        var pivotNotes = (design.pivot && design.pivot.notes) ? design.pivot.notes : '';
                        var isFromSnapshot = pivotNotes.indexOf('snapshot') !== -1;
                        var isFromText = pivotNotes.indexOf('texto/personalización') !== -1;
                        var isManual = pivotNotes.indexOf('manualmente') !== -1;
                        var isAdicional = pivotNotes.indexOf('adicional') !== -1;

                        var typeBadge = '';
                        if (isFromSnapshot) {
                            typeBadge = '<span style="background: #e8f5e9; color: #2e7d32; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">PRODUCTO</span>';
                        } else if (isFromText && isManual) {
                            typeBadge = '<span style="background: #fff3e0; color: #e65100; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">TEXTO MANUAL</span>';
                        } else if (isAdicional && isManual) {
                            typeBadge = '<span style="background: #fff3e0; color: #e65100; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">PRODUCTO MANUAL</span>';
                        }

                        html += '<div class="d-flex align-items-center justify-content-between ' + (!isLast ? 'mb-1 pb-1' : '') + '"' +
                            ' style="' + (!isLast ? 'border-bottom: 1px solid #e0e0e0;' : '') + '">' +
                            '<div class="d-flex align-items-center">';

                        if (design.svg_content) {
                            html += '<div style="width: 24px; height: 24px; background: white; border-radius: 3px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-right: 6px; border: 1px solid #e0e0e0;">' + design.svg_content + '</div>';
                        } else {
                            html += '<div style="width: 24px; height: 24px; background: #1565c0; border-radius: 3px; display: flex; align-items: center; justify-content: center; margin-right: 6px;"><i class="fas fa-vector-square" style="color: white; font-size: 10px;"></i></div>';
                        }

                        html += '<span style="font-size: 14px; color: #212529; font-weight: 500;">' + designName + typeBadge +
                            '<span style="color: #1b5e20; font-weight: 600;"> · ' + numberFormat(design.stitches_count || 0) + ' pts</span>';
                        if (design.dimensions) {
                            html += '<span style="color: #1565c0; font-weight: 600;"> · ' + design.dimensions + '</span>';
                        }
                        html += '</span></div>';

                        html += '<div class="d-flex align-items-center">' +
                            '<button type="button" class="btn btn-sm btn-link p-1" style="color: #f57c00; font-size: 14px;" onclick="changeDesignForItem(' + orderId + ', ' + itemId + ', ' + design.id + ', \'' + designNameEscaped + '\', \'' + escapeHtml(productName).replace(/'/g, "\\'") + '\')" title="Cambiar diseño"><i class="fas fa-exchange-alt"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-link p-1" style="color: #c62828; font-size: 14px;" onclick="unlinkDesignFromItem(' + orderId + ', ' + itemId + ', ' + design.id + ', \'' + designNameEscaped + '\')" title="Quitar diseño"><i class="fas fa-times"></i></button>' +
                            '</div></div>';
                    });

                    if (embroideryRatePerThousand > 0 && totalPuntadas > 0) {
                        var costoCalculado = (totalPuntadas / 1000) * embroideryRatePerThousand * quantity;
                        html += '<div class="mt-1 pt-1" style="border-top: 1px solid #e0e0e0;">' +
                            '<span style="color: #7b1fa2; font-size: 14px; font-weight: 500;">' +
                            '<i class="fas fa-calculator mr-1"></i>' + numberFormat(totalPuntadas) + ' pts × $' + numberFormat2(embroideryRatePerThousand) + '/mil = <strong>$' + numberFormat2(costoCalculado) + '</strong></span></div>';
                    }
                    html += '</div>';
                    return html;
                }

                function buildMobileDesignsHtml(designs, totalPuntadas, embroideryRatePerThousand, quantity, productName, itemId) {
                    var html = '';
                    designs.forEach(function(design) {
                        var designName = escapeHtml(design.name || 'Diseño');
                        var designNameEscaped = designName.replace(/'/g, "\\'");
                        var pivotNotes = (design.pivot && design.pivot.notes) ? design.pivot.notes : '';
                        var isFromSnapshot = pivotNotes.indexOf('snapshot') !== -1;
                        var isFromText = pivotNotes.indexOf('texto/personalización') !== -1;
                        var isManual = pivotNotes.indexOf('manualmente') !== -1;
                        var isAdicional = pivotNotes.indexOf('adicional') !== -1;

                        var sourceBadge = '';
                        if (isFromSnapshot) {
                            sourceBadge = '<span class="design-source-badge source-product">PRODUCTO</span>';
                        } else if (isFromText && isManual) {
                            sourceBadge = '<span class="design-source-badge source-manual">TEXTO MANUAL</span>';
                        } else if (isAdicional && isManual) {
                            sourceBadge = '<span class="design-source-badge source-manual">PRODUCTO MANUAL</span>';
                        }

                        html += '<div class="design-item-mobile">' +
                            '<div class="design-preview-mobile">' +
                            (design.svg_content ? design.svg_content : '<i class="fas fa-vector-square"></i>') +
                            '</div>' +
                            '<div class="design-info-mobile">' +
                            '<span class="design-name-mobile">' + designName + '</span>' + sourceBadge +
                            '<div class="design-meta-mobile">' +
                            '<span>' + numberFormat(design.stitches_count || 0) + ' pts</span>' +
                            (design.dimensions ? '<span>' + design.dimensions + '</span>' : '') +
                            '</div></div>' +
                            '<div class="design-actions-mobile">' +
                            '<button type="button" class="btn-action-mobile btn-change" onclick="changeDesignForItem(' + orderId + ', ' + itemId + ', ' + design.id + ', \'' + designNameEscaped + '\', \'' + escapeHtml(productName).replace(/'/g, "\\'") + '\')"><i class="fas fa-exchange-alt"></i></button>' +
                            '<button type="button" class="btn-action-mobile btn-remove" onclick="unlinkDesignFromItem(' + orderId + ', ' + itemId + ', ' + design.id + ', \'' + designNameEscaped + '\')"><i class="fas fa-times"></i></button>' +
                            '</div></div>';
                    });

                    if (embroideryRatePerThousand > 0 && totalPuntadas > 0) {
                        var costoCalculado = (totalPuntadas / 1000) * embroideryRatePerThousand * quantity;
                        html += '<div class="design-estimate-mobile"><i class="fas fa-calculator"></i> ' +
                            numberFormat(totalPuntadas) + ' pts × $' + numberFormat2(embroideryRatePerThousand) + '/mil = <strong>$' + numberFormat2(costoCalculado) + '</strong></div>';
                    }
                    return html;
                }

                window.refreshSidebarDesigns = function() {
                    fetch('/admin/orders/' + orderId + '/designs-summary', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.html) {
                            var sidebar = document.querySelector('.card-section-disenos');
                            if (sidebar) {
                                sidebar.outerHTML = data.html;
                            }
                        }
                    })
                    .catch(function(err) { console.log('No se pudo actualizar sidebar', err); });
                };

                window.refreshTechnicalSummary = function() {
                    fetch('/admin/orders/' + orderId + '/technical-summary', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.data) {
                            var d = data.data;
                            var card = document.getElementById('technicalSummaryCard');
                            if (d.total_disenos > 0) {
                                if (card) {
                                    card.classList.remove('d-none');
                                    var itemsEl = document.getElementById('summaryItemsConDisenos');
                                    var disenosEl = document.getElementById('summaryTotalDisenos');
                                    var puntadasEl = document.getElementById('summaryTotalPuntadas');
                                    var estimadoEl = document.getElementById('summaryEstimadoTecnico');
                                    var complexityBadge = document.getElementById('complexityBadge');

                                    if (itemsEl) itemsEl.textContent = d.items_con_disenos;
                                    if (disenosEl) disenosEl.textContent = d.total_disenos;
                                    if (puntadasEl) puntadasEl.textContent = d.total_puntadas_formatted;
                                    if (estimadoEl) estimadoEl.textContent = '$' + d.estimado_tecnico_formatted;
                                    if (complexityBadge) {
                                        complexityBadge.textContent = d.complejidad;
                                        complexityBadge.style.background = d.complejidad_bg;
                                        complexityBadge.style.color = d.complejidad_color;
                                    }
                                }
                            } else {
                                if (card) card.classList.add('d-none');
                            }
                        }
                    })
                    .catch(function(err) { console.log('No se pudo actualizar resumen técnico', err); });
                };

                function numberFormat(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                function numberFormat2(num) {
                    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                // =====================================================
                // GESTIÓN DE EXTRAS POR ITEM
                // =====================================================
                var allExtrasForItem = [];
                var currentExtraItemId = null;

                window.openExtraSelectorForItem = function(itemId, itemName) {
                    currentExtraItemId = itemId;
                    document.getElementById('currentExtraItemId').value = itemId;
                    document.getElementById('currentExtraItemName').value = itemName;
                    document.getElementById('selectingExtraForProduct').textContent = itemName;
                    document.getElementById('searchExtraForItem').value = '';
                    document.getElementById('extrasForItemResults').innerHTML = '';
                    document.getElementById('extrasForItemResults').classList.remove('d-none');
                    document.getElementById('extrasForItemLoader').classList.add('d-none');
                    document.getElementById('extrasForItemEmpty').classList.add('d-none');
                    $('#selectExtraForItemModal').modal('show');
                    loadAvailableExtrasForItem(itemId);
                };

                function loadAvailableExtrasForItem(itemId) {
                    document.getElementById('extrasForItemResults').classList.add('d-none');
                    document.getElementById('extrasForItemLoader').classList.remove('d-none');
                    document.getElementById('extrasForItemEmpty').classList.add('d-none');

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/available-extras', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        document.getElementById('extrasForItemLoader').classList.add('d-none');
                        if (data.success && data.extras && data.extras.length > 0) {
                            allExtrasForItem = data.extras;
                            filterAndRenderExtrasForItem();
                        } else {
                            document.getElementById('extrasForItemEmpty').classList.remove('d-none');
                        }
                    })
                    .catch(function() {
                        document.getElementById('extrasForItemLoader').classList.add('d-none');
                        document.getElementById('extrasForItemEmpty').classList.remove('d-none');
                    });
                }

                function filterAndRenderExtrasForItem() {
                    var query = document.getElementById('searchExtraForItem').value.trim().toLowerCase();
                    var filtered = allExtrasForItem;
                    if (query) {
                        filtered = allExtrasForItem.filter(function(item) {
                            return item.name.toLowerCase().indexOf(query) !== -1;
                        });
                    }
                    if (filtered.length === 0) {
                        document.getElementById('extrasForItemResults').classList.add('d-none');
                        document.getElementById('extrasForItemEmpty').classList.remove('d-none');
                    } else {
                        renderExtrasForItem(filtered);
                        document.getElementById('extrasForItemResults').classList.remove('d-none');
                        document.getElementById('extrasForItemEmpty').classList.add('d-none');
                    }
                }

                function renderExtrasForItem(extras) {
                    var container = document.getElementById('extrasForItemResults');
                    container.innerHTML = '';
                    extras.forEach(function(extra) {
                        var inventoryBadge = extra.consumes_inventory ?
                            '<span style="background:#fff3e0;color:#e65100;padding:2px 6px;border-radius:4px;font-size:11px;margin-left:6px;"><i class="fas fa-warehouse mr-1"></i>Inventario</span>' : '';

                        var html =
                            '<div class="list-group-item d-flex align-items-center justify-content-between" style="cursor:pointer;gap:12px;padding:12px;border-radius:8px;margin-bottom:8px;border:1px solid #e0e0e0;" onclick="addExtraToItemConfirm(' + extra.id + ', \'' + escapeHtml(extra.name).replace(/'/g, "\\'") + '\', ' + extra.price_addition + ')">' +
                            '<div class="d-flex align-items-center">' +
                            '<div style="width:44px;height:44px;background:#0277bd;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:12px;">' +
                            '<i class="fas fa-box" style="color:white;font-size:16px;"></i></div>' +
                            '<div>' +
                            '<strong style="font-size:15px;color:#212529;">' + escapeHtml(extra.name) + '</strong>' + inventoryBadge +
                            '<div style="font-size:13px;color:#666;">+$' + numberFormat2(extra.price_addition) + '</div>' +
                            '</div></div>' +
                            '<i class="fas fa-plus-circle" style="color:#0277bd;font-size:20px;"></i>' +
                            '</div>';
                        container.innerHTML += html;
                    });
                }

                var searchExtraInput = document.getElementById('searchExtraForItem');
                if (searchExtraInput) {
                    searchExtraInput.addEventListener('input', function() {
                        filterAndRenderExtrasForItem();
                    });
                }

                window.addExtraToItemConfirm = function(extraId, extraName, priceAddition) {
                    var itemId = document.getElementById('currentExtraItemId').value;
                    var itemName = document.getElementById('currentExtraItemName').value;

                    Swal.fire({
                        title: '¿Agregar extra?',
                        html: '<p style="font-size:14px;">Se agregará <strong>' + escapeHtml(extraName) +
                            '</strong> (+$' + numberFormat2(priceAddition) + ') al producto <strong>' + escapeHtml(itemName) + '</strong></p>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0277bd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-plus mr-1"></i> Agregar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            addExtraToItemExecute(itemId, extraId, extraName);
                        }
                    });
                };

                function addExtraToItemExecute(itemId, extraId, extraName) {
                    Swal.fire({
                        title: 'Agregando...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: function() { Swal.showLoading(); }
                    });

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/extras', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ product_extra_id: extraId, quantity: 1 })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Agregado!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500,
                                timerProgressBar: true
                            }).then(function() {
                                $('#selectExtraForItemModal').modal('hide');
                                refreshItemExtras(itemId);
                                updateOrderTotals(data.order_totals);
                                refreshBomSection(); // Actualizar sección BOM
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                    });
                }

                // Actualizar cantidad de un extra en tiempo real
                window.updateExtraQuantity = function(orderId, itemId, orderItemExtraId, newQuantity) {
                    // No permitir cantidad menor a 1 (para eliminar usar el botón X)
                    if (newQuantity < 1) {
                        return;
                    }

                    // Actualizar visualmente inmediatamente (optimistic UI)
                    var qtyEl = document.querySelector('[data-extra-qty="' + orderItemExtraId + '"]');
                    var oldQty = qtyEl ? parseInt(qtyEl.textContent) : 1;
                    if (qtyEl) qtyEl.textContent = newQuantity;

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/extras/' + orderItemExtraId, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ quantity: newQuantity })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            // Actualizar los botones +/- con las nuevas cantidades
                            var extraRow = document.querySelector('[data-extra-row="' + orderItemExtraId + '"]');
                            if (extraRow) {
                                var minusBtn = extraRow.querySelector('button[title="Disminuir cantidad"]');
                                var plusBtn = extraRow.querySelector('button[title="Aumentar cantidad"]');
                                if (minusBtn) {
                                    minusBtn.setAttribute('onclick', 'updateExtraQuantity(' + orderId + ', ' + itemId + ', ' + orderItemExtraId + ', ' + (newQuantity - 1) + ')');
                                    // Deshabilitar botón - si cantidad es 1
                                    if (newQuantity <= 1) {
                                        minusBtn.disabled = true;
                                        minusBtn.style.background = '#e0e0e0';
                                        minusBtn.style.color = '#bdbdbd';
                                    } else {
                                        minusBtn.disabled = false;
                                        minusBtn.style.background = '#f5f5f5';
                                        minusBtn.style.color = '#0277bd';
                                    }
                                }
                                if (plusBtn) {
                                    plusBtn.setAttribute('onclick', 'updateExtraQuantity(' + orderId + ', ' + itemId + ', ' + orderItemExtraId + ', ' + (newQuantity + 1) + ')');
                                }
                            }

                            // Actualizar totales de extras del item
                            var row = document.querySelector('[data-item-row="' + itemId + '"]');
                            if (row && data.item_extras_total !== undefined) {
                                // Actualizar badge en el collapse
                                var extrasTotalEl = row.querySelector('[data-extras-total="' + itemId + '"]');
                                if (extrasTotalEl) {
                                    extrasTotalEl.textContent = '· +$' + numberFormat2(data.item_extras_total);
                                }
                                // Actualizar subtotal de extras en la última columna
                                var extrasSubtotalEl = row.querySelector('[data-item-extras-subtotal="' + itemId + '"]');
                                if (extrasSubtotalEl) {
                                    extrasSubtotalEl.textContent = '+$' + numberFormat2(data.item_extras_total);
                                    extrasSubtotalEl.style.display = data.item_extras_total > 0 ? '' : 'none';
                                }
                            }

                            // Actualizar totales del pedido
                            updateOrderTotals(data.order_totals);
                            refreshBomSection();

                            // Toast SweetAlert2 arriba derecha (CSS global previene scroll)
                            var scrollPos = window.pageYOffset || document.documentElement.scrollTop;
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Cantidad actualizada',
                                showConfirmButton: false,
                                timer: 1000,
                                timerProgressBar: false,
                                didOpen: function() {
                                    // Forzar restauración de scroll
                                    setTimeout(function() { window.scrollTo(0, scrollPos); }, 0);
                                    setTimeout(function() { window.scrollTo(0, scrollPos); }, 10);
                                    setTimeout(function() { window.scrollTo(0, scrollPos); }, 50);
                                }
                            });
                        } else {
                            // Revertir si hay error
                            if (qtyEl) qtyEl.textContent = oldQty;
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(function() {
                        // Revertir si hay error
                        if (qtyEl) qtyEl.textContent = oldQty;
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                    });
                };

                window.removeExtraFromItem = function(orderId, itemId, orderItemExtraId, extraName) {
                    Swal.fire({
                        title: '¿Quitar extra?',
                        html: '<p style="font-size:14px;">Se quitará <strong>' + escapeHtml(extraName) + '</strong> del producto</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#c62828',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-times mr-1"></i> Quitar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Quitando...',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: function() { Swal.showLoading(); }
                            });

                            fetch('/admin/orders/' + orderId + '/items/' + itemId + '/extras/' + orderItemExtraId, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Quitado!',
                                        text: data.message,
                                        icon: 'success',
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).then(function() {
                                        refreshItemExtras(itemId);
                                        updateOrderTotals(data.order_totals);
                                        refreshBomSection(); // Actualizar sección BOM
                                    });
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            })
                            .catch(function() {
                                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                            });
                        }
                    });
                };

                function refreshItemExtras(itemId) {
                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/extras', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            updateExtrasUI(itemId, data.extras, data.total);
                        }
                    })
                    .catch(function(err) { console.log('No se pudo actualizar extras', err); });
                }

                // Refrescar sección BOM completa via AJAX
                function refreshBomSection() {
                    var bomContainer = document.getElementById('bomAdjustmentSection');
                    if (!bomContainer) return;

                    fetch('/admin/orders/' + orderId + '/bom-html', {
                        headers: { 'Accept': 'text/html; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) {
                        // Asegurar que la respuesta se decodifique como UTF-8
                        return r.arrayBuffer();
                    })
                    .then(function(buffer) {
                        // Decodificar explícitamente como UTF-8
                        var decoder = new TextDecoder('utf-8');
                        var html = decoder.decode(buffer);
                        if (html) {
                            bomContainer.innerHTML = html;
                            // Re-inicializar listeners después de actualizar el HTML
                            if (typeof window.initBomListeners === 'function') {
                                window.initBomListeners();
                            }
                        }
                    })
                    .catch(function(err) { console.log('No se pudo actualizar BOM', err); });
                }

                function updateExtrasUI(itemId, extras, total) {
                    var row = document.querySelector('[data-item-row="' + itemId + '"]');
                    if (!row) return;

                    var extrasContainer = row.querySelector('[data-item-extras="' + itemId + '"]');
                    var extrasList = row.querySelector('[data-extras-list="' + itemId + '"]');
                    var extrasCountEl = row.querySelector('[data-extras-count="' + itemId + '"]');
                    var extrasTotalEl = row.querySelector('[data-extras-total="' + itemId + '"]');
                    var collapseEl = document.getElementById('extrasDetails' + itemId);

                    if (extras.length === 0) {
                        // Sin extras - mostrar "Sin extras"
                        if (extrasContainer) {
                            var addBtn = extrasContainer.querySelector('button[onclick*="openExtraSelectorForItem"]');
                            var addBtnHtml = addBtn ? addBtn.outerHTML : '';
                            extrasContainer.innerHTML =
                                '<div class="d-inline-flex align-items-center" style="background:#f5f5f5;border-radius:4px;padding:4px 8px;border:1px solid #e0e0e0;">' +
                                '<i class="fas fa-box mr-1" style="color:#9e9e9e;font-size:14px;"></i>' +
                                '<span style="font-size:14px;color:#9e9e9e;font-weight:500;">Sin extras</span></div>' +
                                addBtnHtml;
                        }
                        if (collapseEl) collapseEl.remove();
                    } else {
                        // Con extras - actualizar badge y lista
                        if (extrasCountEl) extrasCountEl.textContent = extras.length + ' extra(s)';
                        if (extrasTotalEl) extrasTotalEl.textContent = '· +$' + numberFormat2(total);

                        if (extrasList) {
                            var html = '';
                            extras.forEach(function(extra, index) {
                                var isLast = index === extras.length - 1;
                                html +=
                                    '<div class="d-flex align-items-center justify-content-between ' + (!isLast ? 'mb-1 pb-1' : '') + '" ' +
                                    'style="' + (!isLast ? 'border-bottom:1px solid #e0e0e0;' : '') + '" data-extra-row="' + extra.id + '">' +
                                    '<div class="d-flex align-items-center">' +
                                    '<div style="width:24px;height:24px;background:#0277bd;border-radius:3px;display:flex;align-items:center;justify-content:center;margin-right:6px;">' +
                                    '<i class="fas fa-box" style="color:white;font-size:10px;"></i></div>' +
                                    '<span style="font-size:14px;color:#212529;font-weight:500;">' +
                                    escapeHtml(extra.name) +
                                    '<span style="color:#0277bd;font-weight:600;"> · $' + numberFormat2(extra.unit_price) + ' c/u</span>' +
                                    '</span></div>' +
                                    '<div class="d-flex align-items-center" style="gap:4px;">' +
                                    '<button type="button" class="btn btn-sm p-0" style="width:24px;height:24px;background:' + (extra.quantity <= 1 ? '#e0e0e0' : '#f5f5f5') + ';border:1px solid #e0e0e0;border-radius:4px;color:' + (extra.quantity <= 1 ? '#bdbdbd' : '#0277bd') + ';font-size:12px;" ' +
                                    'onclick="updateExtraQuantity(' + orderId + ', ' + itemId + ', ' + extra.id + ', ' + (extra.quantity - 1) + ')" title="Disminuir cantidad"' + (extra.quantity <= 1 ? ' disabled' : '') + '>' +
                                    '<i class="fas fa-minus"></i></button>' +
                                    '<span style="min-width:24px;text-align:center;font-size:14px;font-weight:600;color:#212529;" data-extra-qty="' + extra.id + '">' + extra.quantity + '</span>' +
                                    '<button type="button" class="btn btn-sm p-0" style="width:24px;height:24px;background:#e3f2fd;border:1px solid #90caf9;border-radius:4px;color:#0277bd;font-size:12px;" ' +
                                    'onclick="updateExtraQuantity(' + orderId + ', ' + itemId + ', ' + extra.id + ', ' + (extra.quantity + 1) + ')" title="Aumentar cantidad">' +
                                    '<i class="fas fa-plus"></i></button>' +
                                    '<button type="button" class="btn btn-sm btn-link p-1 ml-1" style="color:#c62828;font-size:14px;" title="Quitar extra" ' +
                                    'onclick="removeExtraFromItem(' + orderId + ', ' + itemId + ', ' + extra.id + ', \'' + escapeHtml(extra.name).replace(/'/g, "\\'") + '\')">' +
                                    '<i class="fas fa-times"></i></button></div></div>';
                            });
                            extrasList.innerHTML = html;
                        }

                        // Si no existe el collapse, crear uno
                        if (!collapseEl && extrasContainer) {
                            var newCollapse = document.createElement('div');
                            newCollapse.className = 'collapse mt-1';
                            newCollapse.id = 'extrasDetails' + itemId;
                            newCollapse.innerHTML = '<div style="background:#fafafa;border-radius:4px;padding:6px;border:1px solid #e0e0e0;" data-extras-list="' + itemId + '"></div>';
                            extrasContainer.parentNode.insertBefore(newCollapse, extrasContainer.nextSibling);
                            // Actualizar la lista recién creada
                            refreshItemExtras(itemId);
                        }

                        // Actualizar el badge si no existía
                        if (!extrasCountEl && extrasContainer) {
                            var firstChild = extrasContainer.firstElementChild;
                            if (firstChild && firstChild.textContent.indexOf('Sin extras') !== -1) {
                                var addBtn = extrasContainer.querySelector('button[onclick*="openExtraSelectorForItem"]');
                                var addBtnHtml = addBtn ? addBtn.outerHTML : '';
                                extrasContainer.innerHTML =
                                    '<div class="d-inline-flex align-items-center" style="background:#e3f2fd;border-radius:4px;padding:4px 8px;border:1px solid #90caf9;cursor:pointer;" data-toggle="collapse" data-target="#extrasDetails' + itemId + '">' +
                                    '<i class="fas fa-box mr-1" style="color:#0277bd;font-size:14px;"></i>' +
                                    '<span style="font-size:14px;color:#0277bd;font-weight:600;" data-extras-count="' + itemId + '">' + extras.length + ' extra(s)</span>' +
                                    '<span style="font-size:14px;color:#1565c0;margin-left:4px;font-weight:600;" data-extras-total="' + itemId + '">· +$' + numberFormat2(total) + '</span>' +
                                    '<i class="fas fa-chevron-down ml-2" style="font-size:12px;color:#0277bd;"></i></div>' +
                                    addBtnHtml;
                            }
                        }
                    }

                    // Actualizar subtotal de extras en la última columna
                    var extrasSubtotalEl = row.querySelector('[data-item-extras-subtotal="' + itemId + '"]');
                    if (extrasSubtotalEl) {
                        extrasSubtotalEl.textContent = '+$' + numberFormat2(total);
                        extrasSubtotalEl.style.display = total > 0 ? '' : 'none';
                    }
                }

                // =====================================================
                // EDICIÓN DE PRECIO UNITARIO
                // =====================================================
                function initUnitPriceEditing() {
                    var priceInputs = document.querySelectorAll('.item-unit-price-input');

                    priceInputs.forEach(function(input) {
                        // Guardar en blur (cuando pierde foco)
                        input.addEventListener('blur', function() {
                            saveUnitPrice(this);
                        });

                        // Guardar en Enter
                        input.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                this.blur();
                            }
                        });

                        // Detectar cambios para feedback visual
                        input.addEventListener('input', function() {
                            var original = parseFloat(this.dataset.originalPrice) || 0;
                            var current = parseFloat(this.value) || 0;
                            if (Math.abs(original - current) > 0.001) {
                                this.style.borderColor = '#ffc107';
                                this.style.background = '#fffbe6';
                            } else {
                                this.style.borderColor = '';
                                this.style.background = '';
                            }
                        });
                    });
                }

                function saveUnitPrice(input) {
                    var itemId = input.dataset.itemId;
                    var originalPrice = parseFloat(input.dataset.originalPrice) || 0;
                    var extrasTotal = parseFloat(input.dataset.extrasTotal) || 0;
                    var newBasePrice = parseFloat(input.value) || 0;

                    // Solo guardar si cambió
                    if (Math.abs(originalPrice - newBasePrice) < 0.001) {
                        input.style.borderColor = '';
                        input.style.background = '';
                        return;
                    }

                    // Validar que sea positivo
                    if (newBasePrice < 0) {
                        Swal.fire('Error', 'El precio no puede ser negativo', 'error');
                        input.value = originalPrice.toFixed(2);
                        input.style.borderColor = '';
                        input.style.background = '';
                        return;
                    }

                    // unit_price a guardar = precio base + extras
                    var unitPriceToSave = newBasePrice + extrasTotal;

                    // Mostrar indicador de guardando
                    input.disabled = true;
                    input.style.background = '#e3f2fd';

                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/price', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ unit_price: unitPriceToSave })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        input.disabled = false;
                        if (data.success) {
                            // Actualizar precio base original para futuras comparaciones
                            input.dataset.originalPrice = newBasePrice.toFixed(2);
                            input.style.borderColor = '#28a745';
                            input.style.background = '#d4edda';

                            // Actualizar subtotal del item en la UI
                            updateItemSubtotal(itemId, data.item_subtotal);

                            // Actualizar totales del pedido
                            if (data.order_totals) {
                                updateOrderTotals(data.order_totals);
                            }

                            // Feedback visual temporal
                            setTimeout(function() {
                                input.style.borderColor = '';
                                input.style.background = '';
                            }, 1500);
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo actualizar el precio', 'error');
                            input.value = originalPrice.toFixed(2); // Restaurar precio base original
                            input.style.borderColor = '';
                            input.style.background = '';
                        }
                    })
                    .catch(function(err) {
                        input.disabled = false;
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                        input.value = originalPrice.toFixed(2); // Restaurar precio base original
                        input.style.borderColor = '';
                        input.style.background = '';
                    });
                }

                function updateItemSubtotal(itemId, subtotal) {
                    var row = document.querySelector('[data-item-row="' + itemId + '"]');
                    if (row) {
                        var subtotalCell = row.querySelector('td:last-child strong');
                        if (subtotalCell) {
                            subtotalCell.textContent = '$' + numberFormat2(subtotal);
                        }
                    }
                }

                function updateOrderTotals(totals) {
                    // Actualizar subtotal
                    var subtotalEl = document.querySelector('[data-order-subtotal]');
                    if (subtotalEl && totals.subtotal !== undefined) {
                        subtotalEl.textContent = '$' + numberFormat2(totals.subtotal);
                    }

                    // Actualizar descuento
                    var discountEl = document.querySelector('[data-order-discount]');
                    if (discountEl && totals.discount !== undefined) {
                        discountEl.textContent = '-$' + numberFormat2(totals.discount);
                    }

                    // Actualizar IVA
                    var ivaEl = document.querySelector('[data-order-iva]');
                    if (ivaEl && totals.iva !== undefined) {
                        ivaEl.textContent = '$' + numberFormat2(totals.iva);
                    }

                    // Actualizar total
                    var totalEl = document.querySelector('[data-order-total]');
                    if (totalEl && totals.total !== undefined) {
                        totalEl.textContent = '$' + numberFormat2(totals.total);
                    }
                }

                // Inicializar al cargar el DOM
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initUnitPriceEditing);
                } else {
                    initUnitPriceEditing();
                }
            })();
        </script>
    @endif
@endpush
