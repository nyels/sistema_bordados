{{-- Nombre del diseño para trazabilidad --}}
<div class="d-flex align-items-center mt-2 col-12 justify-content-center">
    <br><br><br>
    <i class="fas fa-palette text-muted mr-1" style="font-size: 1rem;"></i>
    <span class="text-muted" style="font-size: 1.5rem; font-weight: 500;">
        Diseño: <strong style="color: #374151;">{{ $export->design->name ?? 'Sin diseño' }}</strong>
        @if ($export->variant)
            <span class="mx-1">›</span> {{ $export->variant->name }}
        @endif
    </span>
</div>

<div class="modal-header border-0 pb-0 pt-4" style="background: #fff;">
    <div class="d-flex align-items-center w-50 justify-content-between">
        <div class="d-flex align-items-center">
            <div class="icon-box bg-primary-light rounded-lg mr-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px; background: #eff6ff; color: #3b82f6; border-radius: 14px;">
                <i class="fas fa-file-code" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h3 class="modal-title font-weight-bold mb-0"
                    style="font-size: 1.2rem; color: #111827; line-height: 1.2;">{{ $export->file_name }}</h3>
                <div class="d-flex align-items-center mt-1">
                    <span class="badge badge-pill border mr-2"
                        style="background: #f3f4f6; color: #374151; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; border-color: #e5e7eb; padding: 4px 10px;">{{ strtolower($export->file_format ?? 'PES') }}</span>
                    <span class="text-muted"
                        style="font-size: 1rem; font-weight: 500;">{{ $export->formatted_file_size }}</span>
                </div>

            </div>
        </div>
        <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<div class="modal-body pt-4 pb-3">
    <div class="mb-4">
        @php
            $statusStyles = [
                'borrador' => [
                    'bg' => '#f3f4f6',
                    'text' => '#4b5563',
                    'icon' => 'fa-pencil-alt',
                    'label' => 'Borrador',
                ],
                'pendiente' => ['bg' => '#fef3c7', 'text' => '#d97706', 'icon' => 'fa-clock', 'label' => 'Revisión'],
                'aprobado' => [
                    'bg' => '#d1fae5',
                    'text' => '#059669',
                    'icon' => 'fa-check-circle',
                    'label' => 'Aprobado',
                ],
                'archivado' => ['bg' => '#e5e7eb', 'text' => '#374151', 'icon' => 'fa-archive', 'label' => 'Archivado'],
            ];
            $currentStatus = $statusStyles[$export->status] ?? $statusStyles['borrador'];
        @endphp
        <span class="badge rounded-pill px-4 py-2 d-inline-flex align-items-center"
            style="background-color: {{ $currentStatus['bg'] }}; color: {{ $currentStatus['text'] }}; font-weight: 700; font-size: 0.8rem; border-radius: 50rem; border: 1px solid rgba(0,0,0,0.05);">
            <i class="fas {{ $currentStatus['icon'] }} mr-2" style="font-size: 0.85rem;"></i>
            {{ $currentStatus['label'] }}
        </span>
    </div>

    <ul class="nav nav-tabs border-0 mb-4" id="exportDetailTabs" role="tablist" style="gap: 20px;">
        <li class="nav-item" role="presentation">
            <a class="nav-link active p-0 pb-2 border-0 position-relative custom-tab" id="tech-tab" data-toggle="tab"
                href="#tech-content" role="tab" aria-controls="tech-content" aria-selected="true">
                Datos Técnicos
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link p-0 pb-2 border-0 position-relative custom-tab" id="history-tab" data-toggle="tab"
                href="#history-content" role="tab" aria-controls="history-content" aria-selected="false">
                Historial
            </a>
        </li>
    </ul>

    <div class="tab-content" id="exportDetailTabsContent">
        <div class="tab-pane fade show active" id="tech-content" role="tabpanel" aria-labelledby="tech-tab">
            <div class="detail-tech-section mb-4">
                <div class="tech-grid-detail" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div class="tech-item-detail"
                        style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 12px;">
                        <i class="fas fa-hashtag" style="color: #3b82f6; font-size: 18px;"></i>
                        <div>
                            <span class="tech-value-lg"
                                style="display: block; font-size: 24px; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($export->stitches_count) }}</span>
                            <span class="tech-label-sm" style="font-size: 12px; color: #6b7280;">Puntadas</span>
                        </div>
                    </div>
                    <div class="tech-item-detail"
                        style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 12px;">
                        <i class="fas fa-palette" style="color: #3b82f6; font-size: 18px;"></i>
                        <div>
                            <span class="tech-value-lg"
                                style="display: block; font-size: 24px; font-weight: 800; color: #111827; line-height: 1;">{{ $export->colors_count ?? 1 }}</span>
                            <span class="tech-label-sm" style="font-size: 12px; color: #6b7280;">Colores</span>
                        </div>
                    </div>
                    <div class="tech-item-detail"
                        style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 12px;">
                        <i class="fas fa-arrows-alt-h" style="color: #3b82f6; font-size: 18px;"></i>
                        <div>
                            <span class="tech-value-lg"
                                style="display: block; font-size: 24px; font-weight: 800; color: #111827; line-height: 1;">{{ $export->width_mm ?? 0 }}</span>
                            <span class="tech-label-sm" style="font-size: 12px; color: #6b7280;">Ancho mm</span>
                        </div>
                    </div>
                    <div class="tech-item-detail"
                        style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 12px;">
                        <i class="fas fa-arrows-alt-v" style="color: #3b82f6; font-size: 18px;"></i>
                        <div>
                            <span class="tech-value-lg"
                                style="display: block; font-size: 24px; font-weight: 800; color: #111827; line-height: 1;">{{ $export->height_mm ?? 0 }}</span>
                            <span class="tech-label-sm" style="font-size: 12px; color: #6b7280;">Alto mm</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- UBICACIÓN/APLICACIÓN - Movido arriba para mayor visibilidad --}}
            <div class="detail-app-section mb-4">
                <h6 class="section-title-sm"
                    style="font-weight: 700; color: #374151; font-size: 12px; text-transform: uppercase; margin-bottom: 12px;">
                    Aplicación</h6>
                @php
                    // Obtener el tipo de aplicación desde la relación o el campo legacy
                    $appTypeName = null;
                    if ($export->application_type_id && $export->applicationType) {
                        $appTypeName = $export->applicationType->nombre_aplicacion;
                    } elseif ($export->application_type && $export->application_type !== 'general') {
                        $appTypeName = ucfirst(str_replace('_', ' ', $export->application_type));
                    }
                @endphp
                <div class="info-grid" style="background: #f9fafb; border-radius: 12px; padding: 16px;">
                    @if ($appTypeName)
                        <div class="info-row"
                            style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                            <span class="info-key" style="font-size: 14px; color: #6b7280;">
                                <i class="fas fa-map-marker-alt mr-1" style="color: #e65100;"></i> Ubicación:
                            </span>
                            <span class="info-val"
                                style="font-size: 14px; font-weight: 600; color: #e65100;">{{ $appTypeName }}</span>
                        </div>
                    @endif
                    <div class="info-row"
                        style="display: flex; justify-content: space-between; padding: 10px 0; {{ $export->placement_description || $export->notes ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                        <span class="info-key" style="font-size: 14px; color: #6b7280;">Etiqueta:</span>
                        <span class="info-val"
                            style="font-size: 14px; font-weight: 600; color: #111827;">{{ $export->application_label ?? '-' }}</span>
                    </div>
                    @if ($export->placement_description)
                        <div class="info-row"
                            style="display: flex; justify-content: space-between; padding: 10px 0; {{ $export->notes ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                            <span class="info-key" style="font-size: 14px; color: #6b7280;">Descripción:</span>
                            <span class="info-val"
                                style="font-size: 14px; font-weight: 600; color: #111827;">{{ $export->placement_description }}</span>
                        </div>
                    @endif
                    @if ($export->notes)
                        <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0;">
                            <span class="info-key" style="font-size: 14px; color: #6b7280;">Notas:</span>
                            <span class="info-val info-notes"
                                style="font-size: 14px; font-weight: 600; color: #111827; max-width: 200px; white-space: pre-wrap; word-break: break-word;">{{ $export->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-colors-section mb-4">
                <h6 class="section-title-sm"
                    style="font-weight: 700; color: #374151; font-size: 12px; text-transform: uppercase; margin-bottom: 12px;">
                    Colores Detectados</h6>
                <div class="color-grid-lg" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @php
                        $colorsToShow = [];
                        $colorSource = 'none';

                        // 1. Intentar obtener de colors_detected del export
                        $detectedColors = $export->colors_detected;

                        // FIX: Manejar caso de doble codificación (string JSON guardado como string)
                        if (is_string($detectedColors)) {
                            $decoded = json_decode($detectedColors, true);
                            if (is_array($decoded)) {
                                $detectedColors = $decoded;
                            }
                        }

                        if ($detectedColors && is_array($detectedColors) && count($detectedColors) > 0) {
                            // Formato 1: array de objetos con 'hex' => [['hex' => '#FF0000'], ...]
                            if (
                                isset($detectedColors[0]) &&
                                is_array($detectedColors[0]) &&
                                isset($detectedColors[0]['hex'])
                            ) {
                                $colorsToShow = $detectedColors;
                                $colorSource = 'export';
                            }
                            // Formato 2: array simple de strings hex => ['#FF0000', '#00FF00', ...]
                            elseif (isset($detectedColors[0]) && is_string($detectedColors[0])) {
                                $colorsToShow = array_map(fn($hex) => ['hex' => $hex], $detectedColors);
                                $colorSource = 'export';
                            }
                            // Formato 3: array asociativo con nombres => ['Rojo' => '#FF0000', ...]
                            elseif (!isset($detectedColors[0])) {
                                $colorsToShow = array_map(
                                    fn($hex, $name) => ['hex' => $hex, 'name' => $name],
                                    $detectedColors,
                                    array_keys($detectedColors),
                                );
                                $colorSource = 'export';
                            }
                        }

                        // 2. Fallback a image.color_palette
                        if (empty($colorsToShow) && $export->image && $export->image->color_palette) {
                            $palette = is_string($export->image->color_palette)
                                ? json_decode($export->image->color_palette, true)
                                : $export->image->color_palette;
                            if (is_array($palette) && count($palette) > 0) {
                                $colorsToShow = array_map(fn($hex) => ['hex' => $hex], $palette);
                                $colorSource = 'image';
                            }
                        }

                        // 3. Fallback a image.dominant_color
                        if (empty($colorsToShow) && $export->image && $export->image->dominant_color) {
                            $colorsToShow = [['hex' => $export->image->dominant_color]];
                            $colorSource = 'dominant';
                        }
                    @endphp

                    @if (count($colorsToShow) > 0)
                        @if ($colorSource !== 'export')
                            <div class="w-100 mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Colores obtenidos de la imagen
                                </small>
                            </div>
                        @endif
                        @foreach ($colorsToShow as $color)
                            <div class="color-swatch"
                                style="display: flex; flex-direction: column; align-items: center; gap: 6px; width: 80px; margin-bottom: 8px;">
                                <div class="color-swatch-box shadow-sm mb-1"
                                    style="width: 48px; height: 48px; border-radius: 12px; background-color: {{ $color['hex'] ?? '#000' }}; border: 2px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); cursor: help; transition: transform 0.2s;"
                                    title="{{ $color['name'] ?? '' }} ({{ $color['hex'] ?? '' }})"
                                    onmouseover="this.style.transform='scale(1.1)'"
                                    onmouseout="this.style.transform='scale(1)'">
                                </div>
                                <span
                                    class="color-swatch-label text-dark font-weight-bold bg-white px-2 py-1 rounded shadow-sm border"
                                    style="font-size: 0.85rem; font-family: monospace; letter-spacing: 0.5px;">{{ $color['hex'] ?? '#000000' }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-light border-0 w-100 py-2 px-3 mb-0"
                            style="background: #f9fafb; color: #6b7280; font-size: 0.9rem; font-weight: 500; border-radius: 12px;">
                            <i class="fas fa-info-circle mr-2 opacity-50"></i> No se detectaron colores detallados.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="history-content" role="tabpanel" aria-labelledby="history-tab">
            <div class="premium-timeline py-2" style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                {{-- Historial completo de cambios de estado desde la BD (MÁS RECIENTE PRIMERO) --}}
                @if ($export->statusHistory && $export->statusHistory->count() > 0)
                    @foreach ($export->statusHistory->sortByDesc('created_at') as $historyItem)
                        <div class="timeline-item d-flex mb-4" style="gap: 16px; position: relative;">
                            <div
                                style="position: absolute; left: 13px; top: 28px; bottom: -30px; width: 2px; background: #d1d5db;">
                            </div>
                            <div
                                style="width: 28px; height: 28px; background: #fff; border: 2px solid {{ $historyItem->status_color }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1; flex-shrink: 0;">
                                <i class="fas {{ $historyItem->status_icon }}"
                                    style="font-size: 12px; color: {{ $historyItem->status_color }};"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span style="font-size: 16px; font-weight: 700; color: #111827;">
                                        {{ $historyItem->previous_status_label }} →
                                        {{ $historyItem->new_status_label }}
                                    </span>
                                    <span
                                        style="font-size: 13px; color: #374151; font-weight: 600;">{{ $historyItem->created_at->diffForHumans() }}</span>
                                </div>
                                <p style="font-size: 14px; color: #4b5563; margin: 4px 0 0; font-weight: 500;">Por
                                    {{ $historyItem->changedByUser->name ?? 'Sistema' }}</p>
                                @if ($historyItem->notes)
                                    <p style="font-size: 13px; color: #4b5563; margin: 4px 0 0; font-style: italic;">
                                        "{{ $historyItem->notes }}"</p>
                                @endif
                                <span
                                    style="font-size: 13px; color: #6b7280; font-weight: 500;">{{ $historyItem->created_at->translatedFormat('d M, Y - h:i A') }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-3">
                        <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Sin cambios de estado
                            registrados</small>
                    </div>
                @endif

                {{-- Evento de creación siempre al final (es el más antiguo) --}}
                <div class="timeline-item d-flex mb-4" style="gap: 16px; position: relative;">
                    <div
                        style="width: 28px; height: 28px; background: #fff; border: 2px solid #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 1; flex-shrink: 0;">
                        <i class="fas fa-plus-circle" style="font-size: 12px; color: #3b82f6;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <span style="font-size: 16px; font-weight: 700; color: #111827;">Archivo Creado</span>
                            <span
                                style="font-size: 13px; color: #374151; font-weight: 600;">{{ $export->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="font-size: 14px; color: #4b5563; margin: 4px 0 0; font-weight: 500;">Por
                            {{ $export->creator->name ?? 'Sistema' }}</p>
                        <span
                            style="font-size: 13px; color: #6b7280; font-weight: 500;">{{ $export->created_at->translatedFormat('d M, Y - h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-meta-section mb-4">
        <div class="meta-row"
            style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: #6b7280; padding: 8px 0;">
            <i class="fas fa-user" style="width: 16px; color: #9ca3af;"></i>
            <span>Creado por: <strong
                    style="color: #374151;">{{ $export->creator->name ?? 'Sistema' }}</strong></span>
        </div>
        <div class="meta-row"
            style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: #6b7280; padding: 8px 0;">
            <i class="fas fa-calendar" style="width: 16px; color: #9ca3af;"></i>
            <span>Fecha: <strong
                    style="color: #374151;">{{ $export->created_at->translatedFormat('d M, Y') }}</strong></span>
        </div>
    </div>

    <div class="detail-modal-footer d-flex w-100">
        <a href="{{ route('admin.production.download', $export) }}" id="btnDetailDownload"
            class="btn-download w-100 d-flex justify-content-center align-items-center" data-id="{{ $export->id }}"
            download="{{ $export->file_name }}">
            <i class="fas fa-download"></i>
            <span>Descargar</span>
        </a>
    </div>

    <div class="workflow-indicator">
        <div class="workflow-step {{ $export->status === 'borrador' ? 'active' : '' }}">
            <div class="workflow-dot"></div>
            <span>Borrador</span>
        </div>
        <div
            class="workflow-line {{ in_array($export->status, ['pendiente', 'aprobado', 'archivado']) ? 'active' : '' }}">
        </div>

        <div class="workflow-step {{ $export->status === 'pendiente' ? 'active' : '' }}">
            <div class="workflow-dot"></div>
            <span>Revisión</span>
        </div>
        <div class="workflow-line {{ in_array($export->status, ['aprobado', 'archivado']) ? 'active' : '' }}"></div>

        <div class="workflow-step {{ $export->status === 'aprobado' ? 'active' : '' }}">
            <div class="workflow-dot"></div>
            <span>Aprobado</span>
        </div>
        <div class="workflow-line {{ $export->status === 'archivado' ? 'active' : '' }}"></div>

        <div class="workflow-step {{ $export->status === 'archivado' ? 'active' : '' }}">
            <div class="workflow-dot"></div>
            <span>Archivado</span>
        </div>
    </div>

    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
        }

        /* ESTILOS PARA LAS PESTAÑAS (TABS) */
        .custom-tab {
            font-size: 13px;
            font-weight: 700;
            color: #6b7280;
            /* Color inactivo */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .custom-tab:hover {
            color: #111827;
        }

        .custom-tab.active {
            color: #3b82f6 !important;
            /* Color Azul Activo */
        }

        .custom-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #3b82f6;
            /* Raya azul inferior */
            border-radius: 2px;
        }

        .custom-tab:not(.active) {
            color: #111827;
            /* Color negro/oscuro solicitado para inactivo */
            opacity: 0.6;
        }

        /* Personalización de scrollbar para el historial */
        .premium-timeline::-webkit-scrollbar {
            width: 4px;
        }

        .premium-timeline::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .premium-timeline::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }

        /* --- TUS ESTILOS EXISTENTES (SIN MODIFICAR) --- */
        .workflow-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
            width: 100%;
            background: white;
        }

        .workflow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .workflow-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #cbd5e1;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .workflow-step span {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .workflow-line {
            height: 2px;
            background: #e2e8f0;
            width: 100px;
            margin-top: -26px;
            z-index: 1;
        }

        .workflow-step.active .workflow-dot {
            background: #fff;
            border: 4px solid #3b82f6;
            box-shadow: 0 0 0 4px #dbeafe;
            width: 12px;
            height: 12px;
        }

        .workflow-step.active span {
            color: #2563eb;
        }

        .workflow-line.active {
            background: #cbd5e1;
        }

        .btn-download {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            background-color: #f3f4f6;
            color: #262c34;
        }

        .btn-download:hover {
            background-color: #262c34;
            color: #fff;
        }

        @media (max-width: 768px) {
            .workflow-line {
                width: 40px;
            }

            .workflow-indicator {
                transform: scale(0.9);
            }
        }
    </style>
</div>
