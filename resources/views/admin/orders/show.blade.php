@extends('adminlte::page')

@section('title', 'Pedido ' . $order->order_number)

@php
    use App\Models\Order;
    $status = $order->status;
@endphp

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-clipboard-list mr-2"></i> {{ $order->order_number }}
                @if ($order->isAnnex())
                    <span class="badge badge-info ml-2">ANEXO</span>
                @endif
            </h1>
            <span style="font-size: 14px; color: #212529;">Creado: {{ $order->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div>
            @if(request('from') === 'queue')
                <a href="{{ route('admin.production.queue') }}" class="btn btn-info">
                    <i class="fas fa-arrow-left"></i> Volver a Cola
                </a>
            @else
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            @endif
            @if ($status === Order::STATUS_DRAFT)
                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- TIMELINE: Ocultar en DRAFT --}}
    @if($status !== Order::STATUS_DRAFT)
        @include('admin.orders._timeline')
    @endif

    {{-- NOTA: Los blockers se muestran DESPUÉS de la tabla de productos --}}

    {{-- ALERTA CONTEXTUAL: Cuando viene de Cola de Producción --}}
    @if(request('from') === 'queue' && $status === Order::STATUS_CONFIRMED)
        <div class="alert alert-warning alert-dismissible fade show" style="border-left: 4px solid #f57c00;">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-industry mr-2"></i>
            <strong>Viniste desde la Cola de Producción.</strong>
            Revisa la sección de <a href="#blockers-section" class="alert-link">bloqueos</a> más abajo para ver qué impide iniciar este pedido.
        </div>
    @endif

    @if ($order->isAnnex() && $order->parentOrder)
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Pedido anexo de
            <a href="{{ route('admin.orders.show', $order->parentOrder) }}" class="alert-link">
                {{ $order->parentOrder->order_number }}
            </a>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- V5: TRAZABILIDAD POST-VENTA (READ-ONLY) --}}
    {{-- ================================================================ --}}
    @if ($order->isPostSale() && $order->relatedOrder)
        <div class="alert mb-3" style="background: #e3f2fd; border: 1px solid #90caf9; border-left: 4px solid #1976d2;">
            <div class="d-flex align-items-center">
                <i class="fas fa-link mr-2" style="color: #1976d2; font-size: 18px;"></i>
                <div>
                    <strong style="color: #0d47a1;">Pedido Post-Venta</strong>
                    <br>
                    <span style="color: #1565c0; font-size: 15px;">
                        Creado como seguimiento de
                        <a href="{{ route('admin.orders.show', $order->relatedOrder) }}"
                           class="font-weight-bold" style="color: #0d47a1; text-decoration: underline;">
                            {{ $order->relatedOrder->order_number }}
                        </a>
                        <span class="badge badge-{{ $order->relatedOrder->status_color }} ml-1" style="font-size: 14px;">
                            {{ $order->relatedOrder->status_label }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- V5: Pedidos post-venta derivados de este pedido --}}
    @if ($order->postSaleOrders->count() > 0)
        <div class="alert mb-3" style="background: #f3e5f5; border: 1px solid #ce93d8; border-left: 4px solid #7b1fa2;">
            <div class="d-flex align-items-start">
                <i class="fas fa-project-diagram mr-2 mt-1" style="color: #7b1fa2; font-size: 18px;"></i>
                <div style="flex: 1;">
                    <strong style="color: #4a148c;">Pedidos Post-Venta Derivados</strong>
                    <span class="badge badge-secondary ml-1">{{ $order->postSaleOrders->count() }}</span>
                    <div class="mt-2">
                        @foreach ($order->postSaleOrders as $postSale)
                            <div class="d-inline-block mr-3 mb-1">
                                <a href="{{ route('admin.orders.show', $postSale) }}"
                                   style="color: #6a1b9a; text-decoration: none;">
                                    <i class="fas fa-file-alt mr-1"></i>
                                    <strong>{{ $postSale->order_number }}</strong>
                                </a>
                                <span class="badge badge-{{ $postSale->status_color }}" style="font-size: 14px;">
                                    {{ $postSale->status_label }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ESTILO UNIFICADO ERP INDUSTRIAL --}}
    <style>
        .card-section-cliente .card-header,
        .card-section-productos .card-header,
        .card-section-diseno .card-header,
        .card-section-inventario .card-header,
        .card-section-materiales .card-header,
        .card-section-pagos .card-header,
        .card-section-eventos .card-header,
        .card-section-mensajes .card-header,
        .card-section-estado .card-header,
        .card-section-fechas .card-header,
        .card-section-notas .card-header {
            background: #343a40 !important;
            color: #ffffff !important;
        }
    </style>

    <div class="row">
        <div class="col-lg-8">
            {{-- 1. CLIENTE: Visible si existe (no aplica en modo stock) --}}
            @if($order->cliente)
            <div class="card card-section-cliente">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i> Cliente</h5>
                </div>
                <div class="card-body" style="font-size: 15px;">
                    <strong>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</strong><br>
                    <i class="fas fa-phone mr-1"></i> {{ $order->cliente->telefono }}
                    @if ($order->cliente->email)
                        <br><i class="fas fa-envelope mr-1"></i> {{ $order->cliente->email }}
                    @endif
                </div>
            </div>
            @else
            <div class="card card-section-cliente border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-industry mr-2"></i> Producción para Stock</h5>
                </div>
                <div class="card-body" style="font-size: 15px;">
                    <span style="color: #212529;"><i class="fas fa-warehouse mr-1"></i> Este pedido es para inventario interno (sin cliente asignado)</span>
                </div>
            </div>
            @endif

            {{-- ESTADO OPERATIVO: Solo en DRAFT (antes de confirmar) --}}
            @if($status === Order::STATUS_DRAFT)
                @include('admin.orders._order-readiness')
            @endif

            {{-- BLOCKERS: Solo en CONFIRMED (antes de productos) --}}
            @if($status === Order::STATUS_CONFIRMED)
                @include('admin.orders._blockers')
            @endif

            {{-- MENSAJES/NOTAS: Visible desde IN_PRODUCTION en adelante --}}
            @if(in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._messages')
            @endif

            {{-- 2. PRODUCTOS: Siempre visible (solo lectura post-draft) --}}
            @include('admin.orders._items-table')

            {{-- FASE Y: Modal de edición de medidas por item --}}
            @include('admin.orders._item-measurements-modal')

            {{-- ================================================================ --}}
            {{-- FASE X-A: AJUSTE BOM POR MEDIDAS (PRE-PRODUCCIÓN) --}}
            {{-- Solo visible en CONFIRMED - Sin persistencia (validación UX) --}}
            {{-- ================================================================ --}}
            @if($status === Order::STATUS_CONFIRMED)
                <div id="bomAdjustmentSection">
                    @include('admin.orders._bom-adjustment')
                </div>
            @endif

            {{-- 3. DISEÑO: Visible en DRAFT, CONFIRMED e IN_PRODUCTION --}}
            @if(in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION]))
                @php
                    $designItems = $order->items->filter(fn($i) => $i->personalization_type === 'design');
                    $hasDesignItems = $designItems->count() > 0;
                    $allDesignsApproved = $hasDesignItems ? $designItems->every(fn($i) => $i->design_approved) : true;
                @endphp
                @if($hasDesignItems)
                    <div class="card card-section-diseno-wrapper">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center"
                             style="background: #343a40; color: white; cursor: pointer;"
                             data-toggle="collapse" data-target="#collapseDesignSection"
                             aria-expanded="false" aria-controls="collapseDesignSection">
                            <h5 class="mb-0" style="font-size: 16px;">
                                <i class="fas fa-palette mr-2"></i>
                                Diseño / Personalización
                                <span class="ml-2 font-weight-normal" style="font-size: 14px;">
                                    ({{ $designItems->count() }} {{ $designItems->count() === 1 ? 'item' : 'items' }})
                                </span>
                            </h5>
                            <div class="d-flex align-items-center">
                                @if($allDesignsApproved)
                                    <span class="badge badge-success mr-2" style="font-size: 14px;">
                                        <i class="fas fa-check"></i> Aprobados
                                    </span>
                                @else
                                    <span class="badge badge-warning mr-2" style="font-size: 14px;">
                                        <i class="fas fa-clock"></i> Pendientes
                                    </span>
                                @endif
                                <i class="fas fa-chevron-down design-collapse-icon" style="transition: transform 0.2s;"></i>
                            </div>
                        </div>
                        <div class="collapse" id="collapseDesignSection">
                            @include('admin.orders._design-section')
                        </div>
                    </div>
                    <style>
                        [aria-expanded="true"] .design-collapse-icon { transform: rotate(180deg); }
                    </style>
                @endif
            @endif

            {{-- 3.5 NOTA SOBRE COSTOS: Solo en CONFIRMED --}}
            @if($status === Order::STATUS_CONFIRMED)
                <div class="card">
                    <div class="card-header" style="background: #5c6bc0; color: white;">
                        <h5 class="mb-0" style="font-size: 16px;">
                            <i class="fas fa-info-circle mr-2"></i> Sobre el Costo del Pedido
                        </h5>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        <div class="p-3 rounded" style="background: #fff8e1; border-left: 4px solid #ffc107;">
                            <div style="color: #5d4037;">
                                <i class="fas fa-info-circle mr-1" style="color: #f57c00;"></i>
                                <strong>Los precios mostrados son estimaciones comerciales.</strong>
                            </div>
                            <p class="mb-0 mt-2" style="color: #212529; font-size: 14px;">
                                El costo real se determina al iniciar producción, con base en diseño final, medidas definitivas y consumo real de materiales.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 3.6 COSTEO REAL ACTIVO: Solo en IN_PRODUCTION --}}
            @if($status === Order::STATUS_IN_PRODUCTION)
                <div class="card">
                    <div class="card-header" style="background: #43a047; color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" style="font-size: 16px;">
                                <i class="fas fa-industry mr-2"></i> Pedido en Producción
                            </h5>
                            <span class="badge badge-light" style="font-size: 13px; color: #2e7d32;">
                                <i class="fas fa-check-circle mr-1"></i> Costeo Real Activo
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="p-3 rounded" style="background: #e8f5e9; border-left: 4px solid #43a047; font-size: 14px; color: #1b5e20;">
                            <i class="fas fa-cogs mr-1"></i>
                            <strong>Pedido congelado técnicamente.</strong>
                            El costeo real se calcula a partir del consumo y tiempo real de producción.
                        </div>
                        <div class="mt-3" style="font-size: 14px; color: #212529;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Los materiales reservados y el tiempo de máquina se reflejarán en el costo final al marcar como listo.
                        </div>

                        {{-- ACCIÓN: Registrar Merma en Proceso (WIP) --}}
                        <div class="mt-3 pt-3 border-top">
                            <button type="button"
                                    class="btn btn-outline-danger"
                                    data-toggle="modal"
                                    data-target="#modalWasteWip">
                                <i class="fas fa-trash-alt mr-1"></i> Registrar Merma en Proceso
                            </button>
                            <small class="ml-2" style="color: #212529;">
                                Documente materiales perdidos durante la producción.
                            </small>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ================================================================ --}}
            {{-- FASE 3.5: COSTO REAL DE FABRICACIÓN (MATERIALES + BORDADO) --}}
            {{-- Visible en: IN_PRODUCTION, READY, DELIVERED (post-producción) --}}
            {{-- DATO INTERNO: No visible al cliente, solo para auditoría/análisis --}}
            {{-- ================================================================ --}}
            @if($order->has_manufacturing_cost && in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @php
                    $isAtRealLoss = $order->is_at_real_loss;
                    $realMarginPct = $order->real_margin_percent;
                @endphp
                <div class="card" style="border: 2px solid {{ $isAtRealLoss ? '#c62828' : '#1565c0' }};">
                    <div class="card-header py-2" style="background: {{ $isAtRealLoss ? '#c62828' : '#1565c0' }}; color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" style="font-size: 16px;">
                                <i class="fas fa-chart-line mr-2"></i> Análisis de Rentabilidad
                            </h5>
                            <div>
                                @if($isAtRealLoss)
                                    <span class="badge badge-danger" style="font-size: 12px;">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> PÉRDIDA
                                    </span>
                                @else
                                    <span class="badge badge-light" style="font-size: 12px; color: #1565c0;">
                                        <i class="fas fa-lock mr-1"></i> Snapshot Inmutable
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- ================================================================ --}}
                        {{-- FASE 3.6: CINCO COLUMNAS - MATERIALES / BORDADO / SERVICIOS / PRECIO / MARGEN --}}
                        {{-- ================================================================ --}}
                        @php
                            $hasServices = ($order->services_cost_snapshot ?? 0) > 0;
                        @endphp
                        <div class="row text-center">
                            {{-- COLUMNA 1: COSTO DE MATERIALES --}}
                            <div class="col-6 {{ $hasServices ? 'col-md-2' : 'col-md-3' }} mb-3">
                                <div class="p-3 rounded h-100" style="background: #e3f2fd; border: 1px solid #90caf9;">
                                    <p class="mb-1" style="font-size: 13px; color: #1565c0; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-layer-group mr-1"></i> Materiales
                                    </p>
                                    <h4 class="mb-1" style="color: #0d47a1; font-weight: 700; font-size: 18px;">
                                        {{ $order->formatted_manufacturing_cost }}
                                    </h4>
                                    <span style="color: #1976d2; font-size: 12px;">BOM + Extras</span>
                                </div>
                            </div>

                            {{-- COLUMNA 2: COSTO DE BORDADO --}}
                            <div class="col-6 {{ $hasServices ? 'col-md-2' : 'col-md-3' }} mb-3">
                                <div class="p-3 rounded h-100" style="background: #f3e5f5; border: 1px solid #ce93d8;">
                                    <p class="mb-1" style="font-size: 13px; color: #7b1fa2; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-pencil-ruler mr-1"></i> Bordado
                                    </p>
                                    <h4 class="mb-1" style="color: #6a1b9a; font-weight: 700; font-size: 18px;">
                                        {{ $order->formatted_embroidery_cost }}
                                    </h4>
                                    <span style="color: #8e24aa; font-size: 12px;">
                                        {{ $order->formatted_total_stitches }} pts
                                    </span>
                                </div>
                            </div>

                            {{-- COLUMNA 3: COSTO DE SERVICIOS (solo si existe) --}}
                            @if($hasServices)
                            <div class="col-6 col-md-2 mb-3">
                                <div class="p-3 rounded h-100" style="background: #e1f5fe; border: 1px solid #4fc3f7;">
                                    <p class="mb-1" style="font-size: 13px; color: #0277bd; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-concierge-bell mr-1"></i> Servicios
                                    </p>
                                    <h4 class="mb-1" style="color: #01579b; font-weight: 700; font-size: 18px;">
                                        {{ $order->formatted_services_cost }}
                                    </h4>
                                    <span style="color: #0288d1; font-size: 12px;">Mano de obra</span>
                                </div>
                            </div>
                            @endif

                            {{-- COLUMNA 4: PRECIO DE VENTA --}}
                            <div class="col-6 {{ $hasServices ? 'col-md-3' : 'col-md-3' }} mb-3">
                                <div class="p-3 rounded h-100" style="background: #f5f5f5; border: 1px solid #e0e0e0;">
                                    <p class="mb-1" style="font-size: 13px; color: #212529; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-tag mr-1"></i> Precio Venta
                                    </p>
                                    <h4 class="mb-1" style="color: #212529; font-weight: 700; font-size: 18px;">
                                        ${{ number_format($order->total, 2) }}
                                    </h4>
                                    <span style="color: #212529; font-size: 12px;">
                                        @if($order->requires_invoice)
                                            Incluye IVA
                                        @else
                                            Sin IVA
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- COLUMNA 5: MARGEN REAL --}}
                            <div class="col-6 col-md-3 mb-3">
                                @php
                                    $marginBg = $isAtRealLoss ? '#ffebee' : ($realMarginPct < 20 ? '#fff8e1' : '#e8f5e9');
                                    $marginBorder = $isAtRealLoss ? '#ef9a9a' : ($realMarginPct < 20 ? '#ffe082' : '#a5d6a7');
                                    $marginColor = $isAtRealLoss ? '#c62828' : ($realMarginPct < 20 ? '#e65100' : '#2e7d32');
                                @endphp
                                <div class="p-3 rounded h-100" style="background: {{ $marginBg }}; border: 1px solid {{ $marginBorder }};">
                                    <p class="mb-1" style="font-size: 13px; color: {{ $marginColor }}; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-percentage mr-1"></i> Margen Real
                                    </p>
                                    <h4 class="mb-1" style="color: {{ $marginColor }}; font-weight: 700; font-size: 18px;">
                                        {{ $order->formatted_real_margin }}
                                    </h4>
                                    <span class="badge badge-{{ $order->real_margin_alert_level }}" style="font-size: 12px;">
                                        {{ number_format($realMarginPct ?? 0, 1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- RESUMEN: COSTO TOTAL DE FABRICACIÓN --}}
                        <div class="mt-2 p-3 rounded" style="background: #263238; color: white;">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <span style="font-size: 13px; color: #eceff1;">
                                        = Materiales ({{ $order->formatted_manufacturing_cost }})
                                        + Bordado ({{ $order->formatted_embroidery_cost }})
                                        @if($hasServices)
                                        + Servicios ({{ $order->formatted_services_cost }})
                                        @endif
                                    </span>
                                </div>
                                <div class="col-md-6 text-md-right mt-2 mt-md-0">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <i class="fas fa-industry mr-2" style="font-size: 20px; color: #90caf9;"></i>
                                        <div>
                                            <span style="font-size: 14px; color: #eceff1; text-transform: uppercase;">Costo Total Fabricación</span>
                                            <h4 class="mb-0" style="font-weight: 700; font-size: 22px;">
                                                {{ $order->formatted_total_manufacturing_cost }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ================================================================ --}}
                        {{-- ALERTAS DE MARGEN REAL --}}
                        {{-- ================================================================ --}}
                        @if($isAtRealLoss)
                            <div class="alert alert-danger mt-3 mb-0 d-flex align-items-center" style="border-left: 4px solid #c62828;">
                                <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                                <div>
                                    <strong style="font-size: 15px;">ALERTA: Precio por debajo del costo de fabricación</strong>
                                    <p class="mb-0 mt-1" style="font-size: 14px;">
                                        Este pedido genera una pérdida de <strong>{{ $order->formatted_real_margin }}</strong> considerando materiales y bordado.
                                        Revise el precio acordado con el cliente.
                                    </p>
                                </div>
                            </div>
                        @elseif($realMarginPct !== null && $realMarginPct < 20)
                            <div class="alert alert-warning mt-3 mb-0 d-flex align-items-center" style="border-left: 4px solid #f57c00;">
                                <i class="fas fa-exclamation-circle fa-2x mr-3" style="color: #e65100;"></i>
                                <div>
                                    <strong style="font-size: 15px; color: #e65100;">Margen bajo ({{ number_format($realMarginPct, 1) }}%)</strong>
                                    <p class="mb-0 mt-1" style="font-size: 14px; color: #5d4037;">
                                        El margen sobre costo de fabricación es menor al 20%. Considere que aún faltan costos de mano de obra y operación.
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Detalle informativo (colapsable) --}}
                        <div class="mt-3 pt-3 border-top">
                            <a data-toggle="collapse" href="#costDetailCollapse" role="button" aria-expanded="false" style="color: #212529; text-decoration: none; font-size: 14px;">
                                <i class="fas fa-info-circle mr-1"></i> Ver detalles del cálculo
                                <i class="fas fa-chevron-down ml-1" style="font-size: 14px;"></i>
                            </a>
                            <div class="collapse mt-2" id="costDetailCollapse">
                                <div class="row" style="font-size: 14px; color: #212529;">
                                    <div class="col-md-6">
                                        <i class="fas fa-cube mr-1"></i> <strong>Costo de fabricación incluye:</strong>
                                        <ul class="mb-0 pl-3 mt-1">
                                            <li>Materiales del BOM (ficha técnica)</li>
                                            <li>Materiales de extras con inventario</li>
                                            <li><strong style="color: #7b1fa2;">Costo de bordado ({{ $order->formatted_total_stitches }} pts)</strong></li>
                                        </ul>
                                        {{-- DESGLOSE DE TARIFAS POR ITEM --}}
                                        @php
                                            $embroideryBreakdown = [];
                                            foreach ($order->items as $item) {
                                                foreach ($item->designExports as $design) {
                                                    $stitches = $design->stitches_count ?? 0;
                                                    $rateAdjusted = $design->pivot->rate_per_thousand_adjusted;
                                                    $rateBase = $item->product?->embroidery_rate_per_thousand ?? 1.00;
                                                    $rate = $rateAdjusted ?? $rateBase;
                                                    $cost = ($stitches / 1000) * $rate * $item->quantity;
                                                    if ($stitches > 0) {
                                                        $embroideryBreakdown[] = [
                                                            'product' => $item->product_name,
                                                            'design' => $design->file_name ?? 'Diseño',
                                                            'stitches' => $stitches,
                                                            'qty' => $item->quantity,
                                                            'rate' => $rate,
                                                            'cost' => $cost,
                                                            'adjusted' => $rateAdjusted !== null,
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if(count($embroideryBreakdown) > 0)
                                            <div class="mt-3 p-3 rounded" style="background: #f3e5f5; color: #6a1b9a;">
                                                <h6 class="mb-2" style="color: #4a148c; font-weight: 700;">
                                                    <i class="fas fa-calculator mr-1"></i> Tarifas aplicadas:
                                                </h6>
                                                <table class="table table-sm mb-0" style="font-size: 14px; background: transparent;">
                                                    <thead>
                                                        <tr style="color: #6a1b9a; font-weight: 600;">
                                                            <th class="border-0 py-2">Producto</th>
                                                            <th class="border-0 py-2 text-right">Puntadas</th>
                                                            <th class="border-0 py-2 text-right">$/Millar</th>
                                                            <th class="border-0 py-2 text-right">Costo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($embroideryBreakdown as $row)
                                                            <tr style="color: #4a148c;">
                                                                <td class="border-0 py-2">
                                                                    {{ Str::limit($row['product'], 25) }}
                                                                    @if($row['qty'] > 1)
                                                                        <span style="color: #7b1fa2;">(×{{ $row['qty'] }})</span>
                                                                    @endif
                                                                </td>
                                                                <td class="border-0 py-2 text-right font-weight-bold">{{ number_format($row['stitches']) }}</td>
                                                                <td class="border-0 py-2 text-right">
                                                                    <strong>${{ number_format($row['rate'], 2) }}</strong>
                                                                    @if($row['adjusted'])
                                                                        <i class="fas fa-edit ml-1" style="font-size: 12px; color: #f57c00;" title="Tarifa ajustada manualmente"></i>
                                                                    @endif
                                                                </td>
                                                                <td class="border-0 py-2 text-right font-weight-bold" style="font-size: 15px;">${{ number_format($row['cost'], 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr style="border-top: 2px solid #ce93d8;">
                                                            <td colspan="3" class="border-0 py-2 text-right" style="color: #4a148c; font-weight: 700;">TOTAL BORDADO:</td>
                                                            <td class="border-0 py-2 text-right" style="color: #4a148c; font-weight: 700; font-size: 16px;">${{ number_format(collect($embroideryBreakdown)->sum('cost'), 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <i class="fas fa-ban mr-1"></i> <strong>No incluye:</strong>
                                        <ul class="mb-0 pl-3 mt-1">
                                            <li>Mano de obra directa</li>
                                            <li>Costos fijos de operación</li>
                                            <li>Depreciación de maquinaria</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mt-2 p-2 rounded" style="background: #e8f5e9; font-size: 14px; color: #2e7d32;">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    <strong>Dato interno:</strong> Snapshot inmutable capturado al iniciar producción. No se muestra al cliente.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 4. INVENTARIO: Solo en IN_PRODUCTION (detalle completo) --}}
            @if($status === Order::STATUS_IN_PRODUCTION)
                @include('admin.orders._inventory-section')
            @endif

            {{-- 5. MATERIALES (Resumen): --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Mostrar (levantamiento del pedido) --}}
            {{-- IN_PRODUCTION: Mostrar --}}
            {{-- READY: Mostrar (resumen final) --}}
            {{-- DELIVERED: Mostrar (historico) --}}
            @if(in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._materials')
            @endif

            {{-- ================================================================ --}}
            {{-- REGLA UX SELLADA: POST-PRODUCCIÓN = PEDIDO CERRADO --}}
            {{-- En READY y DELIVERED: Solo informativo, SIN botones de anexo --}}
            {{-- REGLA R4: Anexos NO permitidos después de IN_PRODUCTION --}}
            {{-- ================================================================ --}}
            @if(in_array($status, [Order::STATUS_READY, Order::STATUS_DELIVERED]))
                <div class="card" style="border: 2px solid #6c757d;">
                    <div class="card-header py-2" style="background: #6c757d; color: white;">
                        <h5 class="mb-0" style="font-size: 15px;">
                            <i class="fas fa-lock mr-2"></i> Pedido Cerrado
                        </h5>
                    </div>
                    <div class="card-body" style="background: #f8f9fa;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle mr-3 mt-1" style="color: #6c757d; font-size: 20px;"></i>
                            <div>
                                <p class="mb-2" style="color: #212529; font-size: 15px;">
                                    <strong>Este pedido ya completó su ciclo de producción.</strong>
                                </p>
                                <p class="mb-0" style="color: #212529; font-size: 14px;">
                                    <i class="fas fa-ban mr-1"></i>
                                    No se permiten modificaciones, anexos ni adiciones.
                                    <br>
                                    <i class="fas fa-clipboard-list mr-1"></i>
                                    Para trabajos adicionales, cree un <strong>nuevo pedido</strong> para el cliente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 6. PAGOS: Solo para ventas (NO stock_production) --}}
            {{-- REGLA: Mostrar en DRAFT también para poder gestionar anticipos y desbloquear edición --}}
            @php
                $canShowFinancials = !$order->isStockProduction() && in_array($status, [
                    Order::STATUS_DRAFT, // Permite ver/eliminar pagos para desbloquear edición
                    Order::STATUS_CONFIRMED,
                    Order::STATUS_IN_PRODUCTION,
                    Order::STATUS_READY,
                    Order::STATUS_DELIVERED,
                ]);
                $anticipoCompleto = $order->balance <= 0;
                $tieneAnticipo = $order->amount_paid > 0;
            @endphp
            @if($canShowFinancials)
            <div class="card card-section-pagos">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        {{-- Titulo contextual por estado --}}
                        @if(in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]))
                            Anticipos
                        @else
                            Pagos
                        @endif
                    </h5>
                    <div>
                        {{-- Badge contextual por estado --}}
                        @if($status === Order::STATUS_DRAFT && $tieneAnticipo)
                            @if($anticipoCompleto)
                                <span class="badge badge-warning" style="font-size: 13px;">Pago recibido sujeto a ajustes pre-producción</span>
                            @else
                                <span class="badge badge-secondary" style="font-size: 13px;">Anticipo del cliente</span>
                            @endif
                        @elseif($status === Order::STATUS_CONFIRMED && $tieneAnticipo)
                            @if($anticipoCompleto)
                                <span class="badge badge-warning" style="font-size: 13px;">Pago recibido sujeto a ajustes pre-producción</span>
                            @else
                                <span class="badge badge-info" style="font-size: 13px;">Anticipo del cliente</span>
                            @endif
                        @elseif($status === Order::STATUS_IN_PRODUCTION && $anticipoCompleto)
                            <span class="badge badge-success" style="font-size: 13px;">Pedido cubierto</span>
                        @elseif($status === Order::STATUS_READY && $anticipoCompleto)
                            <span class="badge badge-success" style="font-size: 13px;">Pedido cubierto</span>
                        @elseif($status === Order::STATUS_DELIVERED)
                            <span class="badge badge-dark" style="font-size: 13px;">Liquidado</span>
                        @endif

                        {{-- Boton registrar pago: CONFIRMED, IN_PRODUCTION o READY con saldo --}}
                        @if ($order->balance > 0 && in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION, Order::STATUS_READY]))
                            <button class="btn btn-sm btn-light ml-2" data-toggle="modal" data-target="#modalPayment" style="font-size: 14px;">
                                <i class="fas fa-plus"></i> Registrar {{ $status === Order::STATUS_CONFIRMED ? 'Anticipo' : 'Pago' }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ================================================================ --}}
                {{-- REGLA UX SELLADA: PAGOS SON ADELANTOS, NO MODIFICAN PRECIO --}}
                {{-- ================================================================ --}}
                <div class="px-3 py-2 border-bottom" style="background: #e8eaf6; font-size: 14px;">
                    <i class="fas fa-lightbulb mr-1" style="color: #5c6bc0;"></i>
                    <span style="color: #3949ab;">
                        <strong>Pagos y Adelantos:</strong>
                        Los pagos registrados son adelantos sobre el precio acordado.
                        Registrar un pago <strong>NO</strong> modifica el precio del producto.
                    </span>
                </div>

                {{-- Mensaje contextual por estado --}}
                @if($status === Order::STATUS_DRAFT && $tieneAnticipo)
                    <div class="px-3 py-2 border-bottom" style="background: #fff3cd; font-size: 14px;">
                        <i class="fas fa-info-circle text-warning mr-1"></i>
                        <span style="color: #856404;">Anticipo recibido (el pedido aún no entra a producción)</span>
                    </div>
                @elseif($status === Order::STATUS_CONFIRMED)
                    <div class="px-3 py-2 border-bottom" style="background: #fff8e1; font-size: 14px;">
                        <i class="fas fa-exclamation-triangle mr-1" style="color: #f57c00;"></i>
                        <span style="color: #e65100;">El precio puede ajustarse antes de iniciar producción si el diseño o personalización cambian.</span>
                    </div>
                    @if($tieneAnticipo)
                        <div class="px-3 py-2 border-bottom" style="background: #e3f2fd; font-size: 14px;">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            <span style="color: #0d47a1;">El monto recibido se mantiene como anticipo hasta iniciar producción</span>
                        </div>
                    @endif
                @elseif($status === Order::STATUS_IN_PRODUCTION && $tieneAnticipo)
                    <div class="px-3 py-2 border-bottom" style="background: #e8f5e9; font-size: 14px;">
                        <i class="fas fa-check-circle text-success mr-1"></i>
                        <span style="color: #2e7d32;">El anticipo ha sido aplicado al pedido</span>
                    </div>
                @elseif($status === Order::STATUS_READY)
                    @if($order->balance > 0)
                        <div class="px-3 py-2 border-bottom" style="background: #ffebee; font-size: 14px;">
                            <i class="fas fa-exclamation-circle text-danger mr-1"></i>
                            <span style="color: #c62828;"><strong>Pago pendiente.</strong> Complete el pago para poder entregar el pedido.</span>
                        </div>
                    @else
                        <div class="px-3 py-2 border-bottom" style="background: #e8f5e9; font-size: 14px;">
                            <i class="fas fa-lock text-success mr-1"></i>
                            <span style="color: #2e7d32;">Pedido cubierto. Sin movimientos pendientes</span>
                        </div>
                    @endif
                @elseif($status === Order::STATUS_DELIVERED)
                    <div class="px-3 py-2 border-bottom" style="background: #f5f5f5; font-size: 14px;">
                        <i class="fas fa-check-double mr-1" style="color: #212529;"></i>
                        <span style="color: #212529;">Pedido liquidado y entregado</span>
                    </div>
                @endif

                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size: 15px;">
                        <thead style="background: #343a40; color: white;">
                            <tr>
                                <th style="color: white;">Fecha</th>
                                <th style="color: white;">Método</th>
                                <th class="text-right" style="color: white;">Monto</th>
                                <th style="color: white;">Referencia</th>
                                <th style="color: white;">Notas</th>
                                @if($order->status === Order::STATUS_DRAFT)
                                    <th class="text-center" style="color: white; width: 60px;"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr data-payment-id="{{ $payment->id }}">
                                    <td style="color: #212529;">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                    <td style="color: #212529;">{{ $payment->method_label }}</td>
                                    <td class="text-right font-weight-bold" style="color: #28a745; font-size: 16px;">${{ number_format($payment->amount, 2) }}</td>
                                    <td style="color: #212529;">{{ $payment->reference ?? '—' }}</td>
                                    <td style="color: #212529;">{{ $payment->notes ?? '—' }}</td>
                                    @if($order->status === Order::STATUS_DRAFT)
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-pago"
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-payment-amount="{{ number_format($payment->amount, 2) }}"
                                                    title="Eliminar anticipo">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $order->status === Order::STATUS_DRAFT ? '6' : '5' }}" class="text-center py-3" style="color: #212529; font-size: 15px;">
                                        @if(in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]))
                                            Sin anticipos registrados
                                        @else
                                            Sin pagos registrados
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot style="background: #f8f9fa;">
                            <tr>
                                <td colspan="2" style="color: #212529; font-size: 15px;">
                                    <strong>
                                        @if(in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]))
                                            Anticipo:
                                        @elseif($status === Order::STATUS_DELIVERED)
                                            Total liquidado:
                                        @else
                                            Pago aplicado:
                                        @endif
                                    </strong>
                                </td>
                                <td class="text-right" style="color: #28a745; font-size: 16px;"><strong id="total-anticipos">${{ number_format($order->amount_paid, 2) }}</strong></td>
                                <td{{ $order->status === Order::STATUS_DRAFT ? ' colspan="2"' : '' }}></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="color: #212529; font-size: 15px;"><strong>Saldo:</strong></td>
                                <td class="text-right" style="color: {{ $order->balance > 0 ? '#c62828' : '#28a745' }};">
                                    <strong style="font-size: 20px;" id="saldo-pendiente">${{ number_format($order->balance, 2) }}</strong>
                                </td>
                                <td{{ $order->status === Order::STATUS_DRAFT ? ' colspan="2"' : '' }}></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif {{-- END canShowFinancials --}}

            {{-- 7. EVENTOS: --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Ocultar (eventos extensos) --}}
            {{-- IN_PRODUCTION: Mostrar (eventos operativos) --}}
            {{-- READY: Mostrar (eventos finales) --}}
            {{-- DELIVERED: Mostrar (eventos completos) --}}
            @if(in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._events')
            @endif


            {{-- ANEXOS: --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Mostrar (anexos creados antes de producción) --}}
            {{-- IN_PRODUCTION: Mostrar (solo lectura) --}}
            {{-- READY: Ocultar --}}
            {{-- DELIVERED: Ocultar --}}
            @if (in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION]) && $order->annexOrders->count() > 0)
                <div class="card">
                    <div class="card-header" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-project-diagram mr-2"></i> Pedidos Anexos ({{ $order->annexOrders->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0" style="font-size: 15px;">
                            <thead style="background: #343a40; color: white;">
                                <tr>
                                    <th style="color: white;">Número</th>
                                    <th style="color: white;">Fecha</th>
                                    <th class="text-right" style="color: white;">Total</th>
                                    <th class="text-center" style="color: white;">Estado</th>
                                    <th style="color: white;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->annexOrders as $annex)
                                    <tr>
                                        <td><strong>{{ $annex->order_number }}</strong></td>
                                        <td>{{ $annex->created_at->format('d/m/Y') }}</td>
                                        <td class="text-right">${{ number_format($annex->total, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $annex->status_color }}">{{ $annex->status_label }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $annex) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- ESTADO: Ocultar en DRAFT --}}
            @if($status !== Order::STATUS_DRAFT)
                <div class="card card-section-estado">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Estado</h5>
                    </div>
                    <div class="card-body text-center py-3">
                        <span class="badge badge-{{ $order->status_color }} p-2 mb-2" style="font-size: 17px;">
                            {{ $order->status_label }}
                        </span>
                        <br>
                        @if(!$order->isStockProduction())
                            <span class="badge badge-{{ $order->payment_status_color }} p-2 mb-2" style="font-size: 15px;">
                                Pago: {{ $order->payment_status_label }}
                            </span>
                            <br>
                        @endif
                        <span class="badge badge-{{ $order->urgency_color }} p-2" style="font-size: 15px;">
                            <i class="fas fa-clock mr-1"></i> {{ $order->urgency_label }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- ACCIONES: El partial ya controla por estado internamente --}}
            {{-- DELIVERED: No mostrar acciones --}}
            @if($status !== Order::STATUS_DELIVERED)
                @include('admin.orders._actions')
            @endif

            {{-- FECHAS: Siempre visible --}}
            <div class="card card-section-fechas">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar mr-2"></i> Fechas</h5>
                </div>
                <div class="card-body" style="font-size: 15px;">
                    <p class="mb-2" style="color: #212529;"><strong>Creado:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    @if ($order->minimum_date)
                        <p class="mb-2" style="color: #212529;"><strong>Fecha Mínima:</strong> {{ $order->minimum_date->format('d/m/Y') }}</p>
                    @endif
                    @if ($order->promised_date)
                        <p class="mb-2" style="color: #212529;">
                            <strong>Prometido:</strong> {{ $order->promised_date->format('d/m/Y') }}
                            @if ($order->promised_date < now() && !in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED]))
                                <span class="badge badge-danger" style="font-size: 14px;">VENCIDO</span>
                            @endif
                        </p>
                    @endif
                    @if ($order->delivered_date)
                        <p class="mb-0" style="color: #212529;"><strong>Entregado:</strong> {{ $order->delivered_date->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>

            {{-- DISEÑOS DEL PEDIDO: Visible en borrador, confirmado y producción --}}
            {{-- Usa el parcial _designs-sidebar para evitar código duplicado --}}
            @if(in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION]))
                @php
                    // Recolectar todos los DesignExports únicos de los items del pedido
                    // ARQUITECTURA: Solo leer de order_item_design_exports (snapshot del pedido)
                    $allDesigns = collect();
                    foreach ($order->items as $item) {
                        $itemDesigns = $item->designExports ?? collect();
                        foreach ($itemDesigns as $designExport) {
                            if (!$allDesigns->contains('id', $designExport->id)) {
                                $allDesigns->push($designExport);
                            }
                        }
                    }
                @endphp
                @include('admin.orders._designs-sidebar', ['allDesigns' => $allDesigns, 'order' => $order])
            @endif

            @if ($order->notes)
                <div class="card card-section-notas">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Notas</h5>
                    </div>
                    <div class="card-body" style="font-size: 15px; color: #212529;">
                        {{ $order->notes }}
                    </div>
                </div>
            @endif

            {{-- BOTON ANEXO: SOLO en CONFIRMED (REGLA R4 v2) --}}
            {{-- Un anexo NUNCA existe si el pedido >= IN_PRODUCTION --}}
            @if (!$order->isAnnex() && $status === Order::STATUS_CONFIRMED)
                <div class="card border-info">
                    <div class="card-header py-2" style="background: #17a2b8; color: white;">
                        <h6 class="mb-0"><i class="fas fa-plus-circle mr-1"></i> Anexo</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2" style="font-size: 14px; color: #212529;">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            Agregue productos adicionales al pedido <strong>antes de iniciar producción</strong>.
                        </p>
                        <div class="alert alert-warning py-2 mb-2" style="font-size: 14px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Importante:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <li>El anexo se procesará junto con el pedido principal</li>
                                <li>Una vez en producción, NO se permiten anexos</li>
                            </ul>
                        </div>
                        <a href="{{ route('admin.orders.create-annex', $order) }}" class="btn btn-info btn-block">
                            <i class="fas fa-plus-circle mr-1"></i> Crear Anexo
                        </a>
                    </div>
                </div>
            @endif

            {{-- ================================================================ --}}
            {{-- CIERRE CANÓNICO: CANCELAR PEDIDO --}}
            {{-- Solo visible si canCancel() = true --}}
            {{-- ================================================================ --}}
            @if ($order->canCancel())
                <div class="card border-danger">
                    <div class="card-header py-2" style="background: #ffebee; color: #c62828;">
                        <h6 class="mb-0">
                            <i class="fas fa-times-circle mr-1"></i> Cancelar Pedido
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2" style="font-size: 14px; color: #212529;">
                            <i class="fas fa-info-circle text-danger mr-1"></i>
                            Cancelar un pedido es una acción <strong>administrativa</strong>.
                        </p>
                        <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Importante:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <li>Cancelar <strong>NO</strong> registra merma automáticamente</li>
                                <li>Cancelar <strong>NO</strong> revierte materiales al inventario</li>
                                <li>Las reservas activas serán liberadas</li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-block"
                                data-toggle="modal" data-target="#modalCancelOrder">
                            <i class="fas fa-times"></i> Cancelar Pedido
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL PAGOS: Solo para ventas (NO stock_production), en CONFIRMED, IN_PRODUCTION o READY --}}
    @if(!$order->isStockProduction() && in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_PRODUCTION, Order::STATUS_READY]))
        @include('admin.orders._payment-modal')
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL: CANCELACIÓN DE PEDIDO --}}
    {{-- CIERRE CANÓNICO: Motivo obligatorio, advertencia clara --}}
    {{-- ================================================================ --}}
    @if ($order->canCancel())
    <div class="modal fade" id="modalCancelOrder" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle mr-2"></i>
                        Cancelar Pedido {{ $order->order_number }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" id="formCancelOrder">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        {{-- Advertencia clara --}}
                        <div class="alert alert-warning mb-3">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Advertencia
                            </h6>
                            <p class="mb-2" style="font-size: 14px;">
                                La cancelación es un <strong>acto administrativo</strong> que:
                            </p>
                            <ul class="mb-0" style="font-size: 13px;">
                                <li><strong>NO</strong> registra merma automáticamente</li>
                                <li><strong>NO</strong> revierte materiales consumidos</li>
                                <li><strong>SÍ</strong> libera reservas activas de inventario</li>
                            </ul>
                        </div>

                        {{-- Estado actual --}}
                        <div class="mb-3 p-2 bg-light rounded">
                            <small style="color: #212529;">Estado actual:</small>
                            <span class="badge badge-{{ $order->status_color }} ml-2">
                                {{ $order->status_label }}
                            </span>
                        </div>

                        {{-- Motivo obligatorio --}}
                        <div class="form-group">
                            <label for="cancel_reason" class="font-weight-bold">
                                <i class="fas fa-comment-alt mr-1"></i>
                                Motivo de cancelación <span class="text-danger">*</span>
                            </label>
                            <textarea name="cancel_reason"
                                      id="cancel_reason"
                                      class="form-control @error('cancel_reason') is-invalid @enderror"
                                      rows="3"
                                      required
                                      minlength="5"
                                      maxlength="255"
                                      placeholder="Ej: Cliente solicitó cancelación, cambio de especificaciones, error en captura..."></textarea>
                            <small class="form-text" style="color: #212529;">
                                Mínimo 5 caracteres. Este motivo quedará registrado en el historial.
                            </small>
                            @error('cancel_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </button>
                        <button type="submit" class="btn btn-danger" id="btnConfirmCancel">
                            <i class="fas fa-times mr-1"></i> Confirmar Cancelación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: Merma en Proceso (WIP) - Solo en IN_PRODUCTION --}}
    @if($status === Order::STATUS_IN_PRODUCTION)
        @include('admin.waste._modal-wip')
    @endif
@stop

@section('js')
<script>
(function() {
    'use strict';
    var orderData = {
        id: {{ $order->id }},
        number: '{{ $order->order_number }}',
        balance: {{ $order->balance }}
    };

    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('modalPayment');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function() {
            initPaymentModal(orderData.id, orderData.number, orderData.balance);
        });

        $(modal).on('show.bs.modal', function() {
            initPaymentModal(orderData.id, orderData.number, orderData.balance);
        });
    });

    function initPaymentModal(orderId, orderNumber, balance) {
        var form = document.getElementById('paymentForm');
        var numberEl = document.getElementById('paymentOrderNumber');
        var balanceEl = document.getElementById('paymentBalance');
        var amountEl = document.getElementById('paymentAmount');
        var referenceEl = document.getElementById('paymentReference');
        var notesEl = document.getElementById('paymentNotes');

        if (form) {
            form.action = '/admin/orders/' + orderId + '/payments';
        }
        if (numberEl) {
            numberEl.textContent = orderNumber;
        }
        if (balanceEl) {
            balanceEl.textContent = '$' + parseFloat(balance).toFixed(2);
        }
        if (amountEl) {
            amountEl.value = '';
            amountEl.max = balance;
        }
        if (referenceEl) {
            referenceEl.value = '';
        }
        if (notesEl) {
            notesEl.value = '';
        }

        // Establecer saldo original, orderId para validación en tiempo real (sin AJAX, redirige normalmente)
        if (typeof window.setPaymentOriginalBalance === 'function') {
            window.setPaymentOriginalBalance(balance, orderId, false); // false = no usar AJAX
        }
    }

    // Eliminar anticipo/pago (AJAX)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-eliminar-pago');
        if (!btn) return;

        var paymentId = btn.dataset.paymentId;
        var paymentAmount = btn.dataset.paymentAmount;
        var paymentAmountNum = parseFloat(paymentAmount.replace(/,/g, ''));

        Swal.fire({
            title: '¿Eliminar anticipo?',
            html: '<p>Se eliminará el anticipo de <strong>$' + paymentAmount + '</strong>.</p>' +
                  '<p class="text-muted mb-0" style="font-size: 14px;">El monto volverá al saldo pendiente.</p>',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('/admin/orders/payments/' + paymentId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Eliminar fila de la tabla
                        var row = document.querySelector('tr[data-payment-id="' + paymentId + '"]');
                        if (row) {
                            row.remove();
                        }

                        // Verificar si quedan pagos
                        var tbody = document.querySelector('.card-section-pagos tbody');
                        var remainingRows = tbody.querySelectorAll('tr[data-payment-id]');
                        if (remainingRows.length === 0) {
                            var colSpan = document.querySelector('.card-section-pagos thead th:last-child') ? '5' : '4';
                            tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="text-center py-3" style="color: #212529; font-size: 15px;">Sin anticipos registrados</td></tr>';
                        }

                        // Actualizar totales dinámicamente
                        var totalAnticiposEl = document.getElementById('total-anticipos');
                        var saldoPendienteEl = document.getElementById('saldo-pendiente');

                        if (totalAnticiposEl && saldoPendienteEl) {
                            var currentAnticipo = parseFloat(totalAnticiposEl.textContent.replace(/[$,]/g, ''));
                            var currentSaldo = parseFloat(saldoPendienteEl.textContent.replace(/[$,]/g, ''));

                            var newAnticipo = currentAnticipo - paymentAmountNum;
                            var newSaldo = currentSaldo + paymentAmountNum;

                            totalAnticiposEl.textContent = '$' + newAnticipo.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            saldoPendienteEl.textContent = '$' + newSaldo.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                            // Actualizar color del saldo
                            saldoPendienteEl.style.color = newSaldo > 0 ? '#c62828' : '#28a745';
                        }

                        // Actualizar estado del botón eliminar pedido
                        var btnEliminarPedido = document.querySelector('.btn-eliminar-pedido');
                        if (btnEliminarPedido && remainingRows.length === 0) {
                            btnEliminarPedido.disabled = false;
                            var msgAnticipo = btnEliminarPedido.parentElement.querySelector('span.text-center');
                            if (msgAnticipo) {
                                msgAnticipo.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Esta acción no se puede deshacer';
                                msgAnticipo.style.color = '#6c757d';
                            }
                        }

                        // Actualizar orderData.balance para el modal de pagos
                        if (typeof orderData !== 'undefined' && saldoPendienteEl) {
                            orderData.balance = parseFloat(saldoPendienteEl.textContent.replace(/[$,]/g, ''));
                        }

                        var Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        Toast.fire({ icon: 'success', title: data.message });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar el anticipo.'
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Intente nuevamente.'
                    });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                });
            }
        });
    });
})();

</script>
@stop
