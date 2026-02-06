{{-- ================================================================ --}}
{{-- PARTIAL: INDICADOR DE ESTADO OPERATIVO (READY vs PENDING)     --}}
{{-- USO: Durante captura (create) y post-captura (show)            --}}
{{-- FUENTE: Datos existentes de items (personalization_type, etc.) --}}
{{-- ================================================================ --}}

@php
    // === MODO CREATE (sin $order) ===
    // El indicador se actualiza dinámicamente vía JS
    $isCreateMode = !isset($order) || !$order->exists;

    // === MODO EDIT/SHOW (con $order existente) ===
    // IMPORTANTE: En fase DRAFT (captura/edición) NO se validan diseños técnicos.
    // Los diseños técnicos se vinculan DESPUÉS de confirmar, en la fase de producción.
    // Solo validamos: medidas pendientes y aprobación de diseño (si aplica).
    if (!$isCreateMode) {
        $items = $order->items;
        $isDraft = $order->status === \App\Models\Order::STATUS_DRAFT;

        // Items con medidas pendientes (aplica siempre)
        $itemsNeedingMeasurements = $items->filter(fn($i) =>
            $i->requires_measurements && empty($i->measurements)
        );

        // Items con diseño por aprobar (solo tipo 'design', NO texto simple)
        $itemsNeedingDesign = $items->filter(fn($i) =>
            $i->blocksProductionForDesign()
        );

        // Items sin diseño técnico vinculado (solo se valida POST-confirmación)
        // En DRAFT no se muestra este pendiente
        $itemsNeedingTechnicalDesign = $isDraft
            ? collect([])
            : $items->filter(fn($i) => $i->blocksProductionForTechnicalDesigns());

        // En DRAFT: listo si tiene items, medidas completas y diseños aprobados
        // NO se validan diseños técnicos en esta fase
        if ($isDraft) {
            $isReady = $items->count() > 0
                && $itemsNeedingMeasurements->count() === 0
                && $itemsNeedingDesign->count() === 0;
        } else {
            // Post-confirmación: validación completa incluyendo diseños técnicos
            $pendingItems = $items->filter(fn($i) => !$i->isReady());
            $isReady = $items->count() > 0 && $pendingItems->count() === 0;
        }
    }
@endphp

{{-- ================================================================ --}}
{{-- MODO CREATE: Contenedor dinámico (se actualiza vía JS)          --}}
{{-- ================================================================ --}}
@if($isCreateMode)
    <div id="orderReadinessIndicator" class="card card-erp mb-3" style="display: none;">
        <div class="card-body py-2 px-3">
            {{-- Estado dinámico --}}
            <div id="readinessContent">
                {{-- Se llena vía JavaScript --}}
            </div>
        </div>
    </div>

    <style>
        #orderReadinessIndicator.status-ready {
            border: 2px solid #28a745;
            background: #d4edda;
        }
        #orderReadinessIndicator.status-pending {
            border: 2px solid #ffc107;
            background: #fff3cd;
        }
        #orderReadinessIndicator.status-empty {
            border: 2px dashed #dee2e6;
            background: #f8f9fa;
        }
        .readiness-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
        .readiness-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .readiness-subtitle {
            font-size: 15px;
            margin-bottom: 0;
        }
        .readiness-pending-list {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
            font-size: 15px;
        }
        .readiness-pending-list li {
            margin-bottom: 0.25rem;
        }
    </style>
@endif

{{-- ================================================================ --}}
{{-- MODO SHOW: Indicador estático basado en datos reales            --}}
{{-- ================================================================ --}}
@if(!$isCreateMode && $order->status === \App\Models\Order::STATUS_DRAFT)
    <div class="card mb-3" style="border: 2px solid {{ $isReady ? '#28a745' : '#ffc107' }}; background: {{ $isReady ? '#d4edda' : '#fff3cd' }};">
        <div class="card-body py-3 px-3">
            <div class="d-flex align-items-start">
                @if($isReady)
                    <i class="fas fa-check-circle readiness-icon" style="color: #28a745;"></i>
                    <div>
                        <div class="readiness-title" style="color: #155724;">Pedido listo para confirmar</div>
                        <p class="readiness-subtitle" style="color: #155724;">
                            Todos los requisitos de captura están completos.
                        </p>
                    </div>
                @else
                    <i class="fas fa-exclamation-triangle readiness-icon" style="color: #856404;"></i>
                    <div>
                        <div class="readiness-title" style="color: #856404;">Pedido incompleto</div>
                        <p class="readiness-subtitle" style="color: #856404;">
                            Falta información antes de confirmar:
                        </p>
                        <ul class="readiness-pending-list" style="color: #856404;">
                            @if($items->count() === 0)
                                <li><strong>Sin productos:</strong> Agregue al menos un producto</li>
                            @endif
                            @if($itemsNeedingMeasurements->count() > 0)
                                <li>
                                    <strong>Medidas pendientes:</strong>
                                    {{ $itemsNeedingMeasurements->pluck('product_name')->implode(', ') }}
                                </li>
                            @endif
                            @if($itemsNeedingDesign->count() > 0)
                                <li>
                                    <strong>Diseño por aprobar:</strong>
                                    {{ $itemsNeedingDesign->pluck('product_name')->implode(', ') }}
                                </li>
                            @endif
                            @if($itemsNeedingTechnicalDesign->count() > 0)
                                <li>
                                    <strong>Diseño técnico sin vincular:</strong>
                                    {{ $itemsNeedingTechnicalDesign->pluck('product_name')->implode(', ') }}
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
