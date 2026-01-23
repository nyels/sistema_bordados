@php
    $hasAdjustments = $order->hasPendingAdjustments();
    $hasDesignPending = $order->hasItemsPendingDesignApproval();
    $hasMeasurementsChanged = $order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $hasMissingTechnicalDesigns = $order->hasItemsMissingTechnicalDesigns();

    // === BLOQUEO POR INVENTARIO (PERSISTIDO) ===
    $hasInventoryBlock = $order->hasProductionInventoryBlock();
    $inventoryBlockDetails = $hasInventoryBlock ? $order->getLastInventoryBlockDetails() : [];

    $hasBlockers = $hasAdjustments || $hasDesignPending || $hasMeasurementsChanged || $hasMissingTechnicalDesigns || $hasInventoryBlock;
    $itemsWithDesign = $order->getItemsBlockingForDesign();
    $itemsWithMeasurements = $order->items->filter(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $itemsMissingTechnical = $order->getItemsMissingTechnicalDesigns();

    // === DETECCIÓN UX: BLOQUEO POR INVENTARIO RESUELTO ===
    // Condiciones exactas:
    // 1. Pedido status === CONFIRMED
    // 2. Existió al menos un evento TYPE_PRODUCTION_BLOCKED por inventario
    // 3. AHORA canStartProduction() === true (inventario ya disponible)
    $hadPreviousInventoryBlock = $order->events()
        ->where('event_type', \App\Models\OrderEvent::TYPE_PRODUCTION_BLOCKED)
        ->whereJsonContains('metadata->reason', 'inventory_insufficient')
        ->exists();
    $canNowStartProduction = $order->canStartProduction();
    $inventoryBlockResolved = $order->status === \App\Models\Order::STATUS_CONFIRMED
        && $hadPreviousInventoryBlock
        && $canNowStartProduction;
@endphp

{{-- ================================================================ --}}
{{-- ALERTA UX POSITIVA: INVENTARIO DISPONIBLE (BLOQUEO RESUELTO) --}}
{{-- Se muestra SOLO cuando:                                         --}}
{{-- - Pedido está en CONFIRMED                                      --}}
{{-- - Hubo bloqueo previo por inventario insuficiente               --}}
{{-- - AHORA canStartProduction() === true                           --}}
{{-- ================================================================ --}}
@if($inventoryBlockResolved)
    <div id="inventory-resolved-section" class="card mb-3" style="border: 2px solid #28a745; background: #d4edda;">
        <div class="card-body py-3">
            <div class="d-flex align-items-start">
                <div class="mr-3">
                    <span class="fa-stack fa-lg">
                        <i class="fas fa-circle fa-stack-2x" style="color: #28a745;"></i>
                        <i class="fas fa-check fa-stack-1x" style="color: white;"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-2" style="color: #155724; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-boxes mr-2"></i>Inventario disponible
                    </h5>
                    <p class="mb-3" style="color: #155724; font-size: 15px;">
                        El inventario necesario para este pedido ya está disponible.
                        Ahora puede reintentar iniciar la producción.
                    </p>
                    {{-- CTA ÚNICO: REINTENTAR PRODUCCIÓN --}}
                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_production">
                        <button type="submit" class="btn btn-success btn-lg" style="font-size: 16px; padding: 12px 28px;">
                            <i class="fas fa-play-circle mr-2"></i> Reintentar Producción
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@if($hasBlockers)
    <div id="blockers-section" class="card border-danger mb-3">
        <div class="card-header bg-danger text-white py-2">
            <i class="fas fa-hand-paper mr-2"></i>
            <strong style="font-size: 16px;">Este pedido NO puede iniciar producción</strong>
            @if(request('from') === 'queue')
                <span class="badge badge-light ml-2" style="font-size: 14px;">Desde Cola de Producción</span>
            @endif
        </div>
        <div class="card-body" style="font-size: 15px;">
            <p class="mb-3" style="color: #495057;">Resuelva los siguientes puntos antes de enviar a producción:</p>

            @if($hasAdjustments)
                <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                    <span class="badge badge-danger mr-3 mt-1" style="font-size: 14px; padding: 8px 12px;">1</span>
                    <div>
                        <strong style="color: #c62828; font-size: 16px;">Ajuste de precio pendiente</strong>
                        <p class="mb-2" style="color: #495057; font-size: 14px;">
                            Hay cambios en el precio que el cliente debe aprobar antes de continuar.
                        </p>
                        <span style="color: #1565c0; font-size: 14px;">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> Contactar al cliente para aprobar el nuevo precio.
                        </span>
                    </div>
                </div>
            @endif

            @if($hasDesignPending)
                <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                    <span class="badge badge-warning mr-3 mt-1" style="font-size: 14px; padding: 8px 12px;">{{ $hasAdjustments ? '2' : '1' }}</span>
                    <div>
                        <strong style="color: #e65100; font-size: 16px;">Diseño pendiente de aprobación</strong>
                        <p class="mb-2" style="color: #495057; font-size: 14px;">
                            El cliente debe aprobar el diseño de los siguientes productos:
                        </p>
                        <div class="mb-2">
                            @foreach($itemsWithDesign as $item)
                                <span class="badge mr-1" style="background: #6c757d; color: white; font-size: 14px; padding: 6px 10px;">{{ $item->product_name }}</span>
                            @endforeach
                        </div>
                        <span style="color: #1565c0; font-size: 14px;">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> Ir a la sección "Diseño/Personalización" más abajo para gestionar.
                        </span>
                    </div>
                </div>
            @endif

            @if($hasMeasurementsChanged)
                @php $stepNum = ($hasAdjustments ? 1 : 0) + ($hasDesignPending ? 1 : 0) + 1; @endphp
                <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                    <span class="badge badge-info mr-3 mt-1" style="font-size: 14px; padding: 8px 12px;">{{ $stepNum }}</span>
                    <div>
                        <strong style="color: #0277bd; font-size: 16px;">Medidas modificadas después de aprobar diseño</strong>
                        <p class="mb-2" style="color: #495057; font-size: 14px;">
                            Las medidas del cliente cambiaron después de aprobar el diseño en:
                        </p>
                        <div class="mb-2">
                            @foreach($itemsWithMeasurements as $item)
                                <span class="badge mr-1" style="background: #6c757d; color: white; font-size: 14px; padding: 6px 10px;">{{ $item->product_name }}</span>
                            @endforeach
                        </div>
                        <span style="color: #1565c0; font-size: 14px;">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> El diseño debe re-aprobarse con las nuevas medidas.
                        </span>
                    </div>
                </div>
            @endif

            @if($hasMissingTechnicalDesigns)
                @php $stepNum = ($hasAdjustments ? 1 : 0) + ($hasDesignPending ? 1 : 0) + ($hasMeasurementsChanged ? 1 : 0) + 1; @endphp
                <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                    <span class="badge mr-3 mt-1" style="font-size: 14px; padding: 8px 12px; background: #7b1fa2; color: white;">{{ $stepNum }}</span>
                    <div>
                        <strong style="color: #7b1fa2; font-size: 16px;">Sin diseño técnico vinculado</strong>
                        <p class="mb-2" style="color: #495057; font-size: 14px;">
                            Los siguientes productos personalizados requieren un diseño técnico de producción:
                        </p>
                        <div class="mb-2">
                            @foreach($itemsMissingTechnical as $item)
                                <span class="badge mr-1" style="background: #6c757d; color: white; font-size: 14px; padding: 6px 10px;">
                                    {{ $item->product_name }}
                                    @if($item->embroidery_text)
                                        <small>({{ $item->embroidery_text }})</small>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        <span style="color: #1565c0; font-size: 14px;">
                            <i class="fas fa-arrow-right mr-1"></i>
                            <strong>Acción:</strong> Vincule un diseño técnico aprobado a cada producto en la sección "Productos".
                        </span>
                    </div>
                </div>
            @endif

            {{-- ================================================================ --}}
            {{-- BLOQUEO POR INVENTARIO INSUFICIENTE (INTENTO PREVIO FALLIDO) --}}
            {{-- ================================================================ --}}
            @if($hasInventoryBlock)
                @php
                    $stepNum = ($hasAdjustments ? 1 : 0) + ($hasDesignPending ? 1 : 0) + ($hasMeasurementsChanged ? 1 : 0) + ($hasMissingTechnicalDesigns ? 1 : 0) + 1;
                    $blockedAt = $inventoryBlockDetails['blocked_at'] ?? null;
                @endphp
                <div class="d-flex align-items-start mb-3 p-3 rounded" style="background: #fff3e0; border: 2px solid #ff9800;">
                    <span class="badge mr-3 mt-1" style="font-size: 14px; padding: 8px 12px; background: #ff9800; color: white;">{{ $stepNum }}</span>
                    <div class="flex-grow-1">
                        <strong style="color: #e65100; font-size: 16px;">
                            <i class="fas fa-boxes mr-1"></i> Inventario insuficiente
                        </strong>
                        @if($blockedAt)
                            <small class="text-muted ml-2">(Detectado: {{ $blockedAt->format('d/m/Y H:i') }})</small>
                        @endif

                        <div class="mt-2 p-2 rounded" style="background: #ffecb3; font-size: 14px; color: #5d4037;">
                            <i class="fas fa-exclamation-triangle mr-1" style="color: #f57c00;"></i>
                            <strong>Este pedido NO puede avanzar hasta resolver el inventario.</strong>
                        </div>

                        <p class="mt-2 mb-2" style="color: #495057; font-size: 14px;">
                            Al intentar iniciar producción, el sistema detectó materiales faltantes:
                        </p>

                        {{-- Lista de materiales faltantes --}}
                        <div class="mb-3">
                            @if(!empty($inventoryBlockDetails['missing_materials']))
                                @foreach($inventoryBlockDetails['missing_materials'] as $material)
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-times-circle text-danger mr-2"></i>
                                        <span style="font-size: 14px; color: #c62828;">{{ $material }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted" style="font-size: 14px;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    {{ $inventoryBlockDetails['message'] ?? 'Inventario insuficiente para este pedido.' }}
                                </div>
                            @endif
                        </div>

                        {{-- Nota sobre extras con inventario --}}
                        @if($order->hasExtrasWithInventory())
                            <div class="p-2 rounded mb-2" style="background: #e3f2fd; font-size: 14px; border-left: 3px solid #1976d2;">
                                <i class="fas fa-info-circle mr-1" style="color: #1976d2;"></i>
                                <span style="color: #0d47a1;">
                                    <strong>Nota:</strong> Este pedido incluye extras que consumen materiales del inventario
                                    ({{ $order->getExtrasWithInventory()->pluck('name')->implode(', ') }}).
                                </span>
                            </div>
                        @endif

                        {{-- ACCIONES DISPONIBLES --}}
                        <div class="mt-3 pt-3 border-top">
                            <p class="mb-2" style="font-size: 14px; color: #495057; font-weight: 600;">
                                <i class="fas fa-hand-point-right mr-1"></i> ACCIONES DISPONIBLES:
                            </p>
                            <div class="d-flex flex-wrap" style="gap: 10px;">
                                {{-- Botón 1: Ir a Compras (PRIMARIO) --}}
                                <a href="{{ url('admin/purchases/create') }}?ref_order={{ $order->id }}"
                                   class="btn btn-warning"
                                   style="font-size: 14px;">
                                    <i class="fas fa-truck mr-1"></i> Ir a Compras
                                </a>

                                {{-- Botón 2: Ver Inventario (SECUNDARIO) --}}
                                <a href="{{ route('admin.inventory.index') }}"
                                   class="btn btn-outline-secondary"
                                   style="font-size: 14px;">
                                    <i class="fas fa-boxes mr-1"></i> Ver Inventario
                                </a>

                                {{-- Botón 3: Reintentar Producción (DESHABILITADO) --}}
                                <button type="button"
                                        class="btn btn-outline-success"
                                        style="font-size: 14px;"
                                        disabled
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="Disponible cuando el inventario sea suficiente. Vuelva a intentar desde la Cola de Producción.">
                                    <i class="fas fa-redo mr-1"></i> Reintentar Producción
                                </button>
                            </div>

                            <p class="mt-3 mb-0" style="font-size: 14px; color: #495057;">
                                <i class="fas fa-lightbulb mr-1" style="color: #ffc107;"></i>
                                <strong>Consejo:</strong> Una vez que el inventario esté disponible, vuelva a la
                                <a href="{{ route('admin.production.queue') }}" style="color: #1565c0; font-weight: 600;">Cola de Producción</a>
                                e intente iniciar nuevamente.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <div id="blockers-section" class="alert mb-3" style="background: #28a745; border: none; padding: 16px 20px;">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3" style="color: white;"></i>
            <div>
                <strong style="color: white; font-size: 17px;">Requisitos comerciales completos</strong>
                <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                    Las validaciones comerciales y técnicas están completas. El inventario se validará al iniciar producción.
                    <a href="{{ route('admin.production.queue') }}" style="color: white; text-decoration: underline; font-weight: 600;">Ir a Cola de Producción</a>.
                </p>
            </div>
        </div>
    </div>
@endif
