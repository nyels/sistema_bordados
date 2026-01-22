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
            <small class="text-muted">Creado: {{ $order->created_at->format('d/m/Y H:i') }}</small>
        </div>
        <div>
            @if ($status === Order::STATUS_DRAFT)
                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
            @endif
            @if(request('from') === 'queue')
                <a href="{{ route('admin.production.queue') }}" class="btn btn-info">
                    <i class="fas fa-industry"></i> Volver a Cola
                </a>
            @else
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
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
            {{-- 1. CLIENTE: Siempre visible --}}
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

            {{-- 2. PRODUCTOS: Siempre visible (solo lectura post-draft) --}}
            @include('admin.orders._items-table')

            {{-- 2.5 BLOCKERS: Solo en CONFIRMED, DESPUÉS de productos --}}
            {{-- El operador primero ve QUÉ tiene el pedido, luego QUÉ falta resolver --}}
            @if($status === Order::STATUS_CONFIRMED)
                @include('admin.orders._blockers')
            @endif

            {{-- 3. DISEÑO: Solo en CONFIRMED (gestion activa) - COLAPSADO por defecto --}}
            @if($status === Order::STATUS_CONFIRMED)
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
                            <h5 class="mb-0" style="font-size: 15px;">
                                <i class="fas fa-palette mr-2"></i>
                                Diseño / Personalización
                                <small class="ml-2 font-weight-normal" style="opacity: 0.85;">
                                    ({{ $designItems->count() }} {{ $designItems->count() === 1 ? 'item' : 'items' }})
                                </small>
                            </h5>
                            <div class="d-flex align-items-center">
                                @if($allDesignsApproved)
                                    <span class="badge badge-success mr-2" style="font-size: 12px;">
                                        <i class="fas fa-check"></i> Aprobados
                                    </span>
                                @else
                                    <span class="badge badge-warning mr-2" style="font-size: 12px;">
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
                    <div class="card-body" style="font-size: 14px;">
                        <div class="p-3 rounded" style="background: #fff8e1; border-left: 4px solid #ffc107;">
                            <div style="color: #5d4037;">
                                <i class="fas fa-info-circle mr-1" style="color: #f57c00;"></i>
                                <strong>Los precios mostrados son estimaciones comerciales.</strong>
                            </div>
                            <p class="mb-0 mt-2" style="color: #495057; font-size: 13px;">
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
                        <div class="mt-3" style="font-size: 13px; color: #495057;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Los materiales reservados y el tiempo de máquina se reflejarán en el costo final al marcar como listo.
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
            {{-- CONFIRMED: Ocultar --}}
            {{-- IN_PRODUCTION: Mostrar --}}
            {{-- READY: Mostrar (resumen final) --}}
            {{-- DELIVERED: Mostrar (historico) --}}
            @if(in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._materials')
            @endif

            {{-- ================================================================ --}}
            {{-- REGLA UX SELLADA: EXTRAS POST-PRODUCCIÓN = SERVICIOS/ANEXOS --}}
            {{-- Solo visible en READY y DELIVERED --}}
            {{-- ================================================================ --}}
            @if(in_array($status, [Order::STATUS_READY, Order::STATUS_DELIVERED]))
                <div class="card" style="border: 2px solid #7b1fa2;">
                    <div class="card-header py-2" style="background: #7b1fa2; color: white;">
                        <h5 class="mb-0" style="font-size: 15px;">
                            <i class="fas fa-plus-circle mr-2"></i> Solicitudes adicionales del cliente
                        </h5>
                    </div>
                    <div class="card-body" style="background: #f3e5f5;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle mr-3 mt-1" style="color: #7b1fa2; font-size: 20px;"></i>
                            <div>
                                <p class="mb-2" style="color: #4a148c; font-size: 14px;">
                                    Cualquier bordado, moño, ajuste o trabajo adicional solicitado
                                    <strong>DESPUÉS</strong> de producción debe registrarse como un
                                    <strong>SERVICIO ADICIONAL</strong> o como un <strong>PEDIDO ANEXO</strong>.
                                </p>
                                <p class="mb-3" style="color: #6a1b9a; font-size: 13px;">
                                    <i class="fas fa-lock mr-1"></i>
                                    El producto original y su precio permanecen intactos.
                                </p>
                                @if(!$order->isAnnex())
                                    <a href="{{ route('admin.orders.create-annex', $order) }}" class="btn btn-sm" style="background: #7b1fa2; color: white; font-size: 13px;">
                                        <i class="fas fa-plus-circle mr-1"></i> Registrar Servicio Adicional / Anexo
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 6. PAGOS: Visible en todos los estados --}}
            @php
                $anticipoCompleto = $order->balance <= 0;
                $tieneAnticipo = $order->amount_paid > 0;
            @endphp
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

                        {{-- Boton registrar pago: NO en READY, DELIVERED, CANCELLED --}}
                        @if ($order->balance > 0 && !in_array($status, [Order::STATUS_READY, Order::STATUS_CANCELLED, Order::STATUS_DELIVERED]))
                            <button class="btn btn-sm btn-light ml-2" data-toggle="modal" data-target="#modalPayment" style="font-size: 14px;">
                                <i class="fas fa-plus"></i> Registrar {{ in_array($status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]) ? 'Anticipo' : 'Pago' }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ================================================================ --}}
                {{-- REGLA UX SELLADA: PAGOS SON ADELANTOS, NO MODIFICAN PRECIO --}}
                {{-- ================================================================ --}}
                <div class="px-3 py-2 border-bottom" style="background: #e8eaf6; font-size: 13px;">
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
                    <div class="px-3 py-2 border-bottom" style="background: #e8f5e9; font-size: 14px;">
                        <i class="fas fa-lock text-success mr-1"></i>
                        <span style="color: #2e7d32;">Pedido cubierto. Sin movimientos pendientes</span>
                    </div>
                @elseif($status === Order::STATUS_DELIVERED)
                    <div class="px-3 py-2 border-bottom" style="background: #f5f5f5; font-size: 14px;">
                        <i class="fas fa-check-double mr-1" style="color: #616161;"></i>
                        <span style="color: #616161;">Pedido liquidado y entregado</span>
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td style="color: #212529;">{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                    <td style="color: #212529;">{{ $payment->method_label }}</td>
                                    <td class="text-right font-weight-bold" style="color: #28a745; font-size: 16px;">${{ number_format($payment->amount, 2) }}</td>
                                    <td style="color: #495057;">{{ $payment->reference ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3" style="color: #495057; font-size: 15px;">
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
                                <td class="text-right" style="color: #28a745; font-size: 16px;"><strong>${{ number_format($order->amount_paid, 2) }}</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="color: #212529; font-size: 15px;"><strong>Saldo:</strong></td>
                                <td class="text-right" style="color: {{ $order->balance > 0 ? '#c62828' : '#28a745' }};">
                                    <strong style="font-size: 20px;">${{ number_format($order->balance, 2) }}</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- 7. EVENTOS: --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Ocultar (eventos extensos) --}}
            {{-- IN_PRODUCTION: Mostrar (eventos operativos) --}}
            {{-- READY: Mostrar (eventos finales) --}}
            {{-- DELIVERED: Mostrar (eventos completos) --}}
            @if(in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._events')
            @endif

            {{-- 8. MENSAJES/NOTAS: --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Ocultar --}}
            {{-- IN_PRODUCTION+: Mostrar --}}
            @if(in_array($status, [Order::STATUS_IN_PRODUCTION, Order::STATUS_READY, Order::STATUS_DELIVERED]))
                @include('admin.orders._messages')
            @endif

            {{-- ANEXOS: --}}
            {{-- DRAFT: Ocultar --}}
            {{-- CONFIRMED: Ocultar --}}
            {{-- IN_PRODUCTION: Mostrar --}}
            {{-- READY: Ocultar --}}
            {{-- DELIVERED: Ocultar --}}
            @if ($status === Order::STATUS_IN_PRODUCTION && $order->annexOrders->count() > 0)
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
                        <span class="badge badge-{{ $order->payment_status_color }} p-2 mb-2" style="font-size: 15px;">
                            Pago: {{ $order->payment_status_label }}
                        </span>
                        <br>
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
                                <span class="badge badge-danger" style="font-size: 13px;">VENCIDO</span>
                            @endif
                        </p>
                    @endif
                    @if ($order->delivered_date)
                        <p class="mb-0" style="color: #212529;"><strong>Entregado:</strong> {{ $order->delivered_date->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>

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

            {{-- BOTON ANEXO: Solo en IN_PRODUCTION --}}
            @if (!$order->isAnnex() && $status === Order::STATUS_IN_PRODUCTION)
                <div class="card border-info">
                    <div class="card-body text-center">
                        <p class="mb-2" style="font-size: 14px; color: #212529;">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            El pedido está en producción. Puede crear un anexo para agregar productos adicionales.
                        </p>
                        <a href="{{ route('admin.orders.create-annex', $order) }}" class="btn btn-info btn-block">
                            <i class="fas fa-plus-circle mr-1"></i> Crear Anexo
                        </a>
                    </div>
                </div>
            @endif

            {{-- CANCELAR: No en DELIVERED ni CANCELLED --}}
            @if (!in_array($status, [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED]))
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <form action="{{ route('admin.orders.cancel', $order) }}" method="POST"
                            data-confirm="cancelar"
                            data-confirm-title="¿Cancelar pedido {{ $order->order_number }}?"
                            data-confirm-text="El pedido quedará marcado como cancelado y no podrá continuar en producción."
                            data-confirm-impact="Los materiales reservados serán liberados automáticamente.">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i> Cancelar Pedido
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL PAGOS: Solo en estados donde se permite registrar --}}
    @if(!in_array($status, [Order::STATUS_READY, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED]))
        @include('admin.orders._payment-modal')
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
            amountEl.value = parseFloat(balance).toFixed(2);
            amountEl.max = balance;
        }
        if (referenceEl) {
            referenceEl.value = '';
        }
        if (notesEl) {
            notesEl.value = '';
        }
    }
})();

</script>
@stop
