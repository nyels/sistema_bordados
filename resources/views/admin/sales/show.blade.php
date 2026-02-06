@extends('adminlte::page')

@section('title', 'Detalle Venta ' . $order->order_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            <i class="fas fa-receipt mr-2"></i>
            Venta {{ $order->order_number }}
        </h1>
        <div>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            @if ($origen === 'POS')
                <a href="{{ route('admin.pos-sales.show', $order) }}" class="btn btn-success">
                    <i class="fas fa-cash-register mr-1"></i> Ver en Historial POS
                </a>
            @else
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary">
                    <i class="fas fa-clipboard-list mr-1"></i> Ver Pedido Completo
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
    @php
        $fechaVenta = $order->sold_at ?? $order->delivered_date;
        $ventaNeta = $order->subtotal - $order->discount;
        $vendedor = $order->seller_name ?? ($order->creator?->name ?? 'N/A');
    @endphp

    <div class="row">
        {{-- COLUMNA IZQUIERDA: Info General --}}
        <div class="col-lg-4 col-md-6">
            {{-- Card Origen --}}
            <div class="card {{ $origen === 'POS' ? 'card-success' : 'card-info' }} card-outline">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas {{ $origen === 'POS' ? 'fa-cash-register' : 'fa-clipboard-list' }} mr-2"></i>
                        Origen: {{ $origen }}
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%;">Folio:</td>
                            <td><strong>{{ $order->order_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Fecha Venta:</td>
                            <td>{{ $fechaVenta?->format('d/m/Y') ?? '--' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Hora:</td>
                            <td>{{ $fechaVenta?->format('H:i:s') ?? '--' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Vendedor:</td>
                            <td>{{ $vendedor }}</td>
                        </tr>
                        @if ($order->payment_method)
                            <tr>
                                <td class="text-muted">Metodo Pago:</td>
                                <td>
                                    <span class="badge badge-light">
                                        {{ ucfirst($order->payment_method) }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Card Cliente --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-user mr-2"></i>Cliente
                    </h6>
                </div>
                <div class="card-body">
                    @if ($order->cliente)
                        <p class="mb-1"><strong>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</strong></p>
                        @if ($order->cliente->telefono)
                            <p class="mb-1 text-muted">
                                <i class="fas fa-phone mr-1"></i> {{ $order->cliente->telefono }}
                            </p>
                        @endif
                        @if ($order->cliente->email)
                            <p class="mb-1 text-muted">
                                <i class="fas fa-envelope mr-1"></i> {{ $order->cliente->email }}
                            </p>
                        @endif
                        <a href="{{ route('admin.clientes.edit', $order->cliente->id) }}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-external-link-alt mr-1"></i> Ver Cliente
                        </a>
                    @else
                        <p class="text-muted mb-0">
                            <i class="fas fa-user-slash mr-1"></i> Venta Libre (sin cliente)
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: Desglose Financiero --}}
        <div class="col-lg-8 col-md-6">
            {{-- Card Totales ERP --}}
            <div class="card card-primary">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-calculator mr-2"></i>Desglose Contable ERP
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="bg-light"><strong>Subtotal</strong></td>
                                    <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="bg-light">
                                        <strong>Descuento</strong>
                                        @if ($order->discount_reason)
                                            <br><small class="text-muted">{{ $order->discount_reason }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right text-danger">-${{ number_format($order->discount, 2) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>VENTA NETA</strong></td>
                                    <td class="text-right"><strong class="h5">${{ number_format($ventaNeta, 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="bg-light">
                                        <strong>IVA</strong>
                                        @if ($order->requires_invoice)
                                            <span class="badge badge-info ml-1">Facturado</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-warning">${{ number_format($order->iva_amount, 2) }}</td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>TOTAL COBRADO</strong></td>
                                    <td class="text-right"><strong class="h4">${{ number_format($order->total, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="bg-light"><strong>Estado Pago</strong></td>
                                    <td class="text-right">
                                        <span class="badge badge-{{ $order->payment_status_color }}">
                                            {{ $order->payment_status_label }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Explicacion ERP --}}
                    <div class="alert alert-info mb-0 mt-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Regla Contable:</strong>
                        Venta Neta (${{ number_format($ventaNeta, 2) }}) es el ingreso real.
                        El IVA (${{ number_format($order->iva_amount, 2) }}) es obligacion fiscal, no ingreso.
                    </div>
                </div>
            </div>

            {{-- Items de la venta (si es PEDIDO) --}}
            @if ($origen === 'PEDIDO' && $order->items->count() > 0)
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-boxes mr-2"></i>Productos del Pedido
                            <span class="badge badge-secondary ml-2">{{ $order->items->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-right">P. Unit.</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->product?->name ?? $item->product_name ?? 'Producto' }}
                                            @if ($item->variant_name)
                                                <br><small class="text-muted">{{ $item->variant_name }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-right"><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Pagos registrados --}}
            @if ($order->payments->count() > 0)
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Pagos Registrados
                            <span class="badge badge-success ml-2">{{ $order->payments->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Metodo</th>
                                    <th class="text-right">Monto</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date?->format('d/m/Y H:i') }}</td>
                                        <td>{{ ucfirst($payment->payment_method) }}</td>
                                        <td class="text-right text-success"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                        <td><small class="text-muted">{{ $payment->reference ?? '--' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="2" class="text-right"><strong>Total Pagado:</strong></td>
                                    <td class="text-right"><strong class="text-success">${{ number_format($order->amount_paid, 2) }}</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Notas --}}
    @if ($order->notes)
        <div class="card card-outline card-secondary">
            <div class="card-header py-2">
                <h6 class="card-title mb-0"><i class="fas fa-sticky-note mr-2"></i>Notas</h6>
            </div>
            <div class="card-body py-2">
                @if ($origen === 'POS' && str_contains($order->notes, '[VENTA POS MOSTRADOR'))
                    @php
                        // Parsear notas POS para mostrar estructuradas
                        $notesRaw = $order->notes;

                        // Extraer componentes
                        preg_match('/\[VENTA POS MOSTRADOR[^\]]*\]/', $notesRaw, $tipoMatch);
                        preg_match('/Fecha\/Hora:\s*([^\s]+\s+[^\s]+)/', $notesRaw, $fechaMatch);
                        preg_match('/Vendedor:\s*([^(]+)\s*\(ID:\s*(\d+)\)/', $notesRaw, $vendedorMatch);
                        preg_match('/Productos:\s*(\d+)/', $notesRaw, $cantProductosMatch);
                        preg_match('/Subtotal:\s*\$?([\d,\.]+)/', $notesRaw, $subtotalMatch);
                        preg_match('/Descuento:\s*\$?([\d,\.]+)\s*=\s*\$?([\d,\.]+)/', $notesRaw, $descuentoMatch);
                        preg_match('/Motivo:\s*([^T]+)TOTAL/', $notesRaw, $motivoMatch);
                        preg_match('/TOTAL:\s*\$?([\d,\.]+)/', $notesRaw, $totalMatch);
                        preg_match('/Método de pago:\s*(\w+)/', $notesRaw, $metodoMatch);

                        // Extraer productos (entre "---" separadores)
                        preg_match_all('/\d+\.\s*([^@]+)@\s*\$?([\d,\.]+)\s*=\s*\$?([\d,\.]+)/', $notesRaw, $productosMatches, PREG_SET_ORDER);
                    @endphp

                    <table class="table table-sm table-borderless mb-0" style="font-size: 14px;">
                        @if (!empty($tipoMatch[0]))
                            <tr>
                                <td class="text-muted" style="width: 150px;"><strong>Tipo:</strong></td>
                                <td><span class="badge badge-success">{{ trim($tipoMatch[0], '[]') }}</span></td>
                            </tr>
                        @endif
                        @if (!empty($fechaMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Fecha/Hora:</strong></td>
                                <td>{{ $fechaMatch[1] }}</td>
                            </tr>
                        @endif
                        @if (!empty($vendedorMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Vendedor:</strong></td>
                                <td>{{ trim($vendedorMatch[1]) }} <small class="text-muted">(ID: {{ $vendedorMatch[2] }})</small></td>
                            </tr>
                        @endif
                        @if (!empty($cantProductosMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Productos:</strong></td>
                                <td>{{ $cantProductosMatch[1] }} item(s)</td>
                            </tr>
                        @endif
                    </table>

                    @if (count($productosMatches) > 0)
                        <hr class="my-2">
                        <strong class="text-muted">Detalle de productos:</strong>
                        <ul class="mb-2 mt-1" style="font-size: 14px;">
                            @foreach ($productosMatches as $prod)
                                <li>{{ trim($prod[1]) }} @ ${{ $prod[2] }} = <strong>${{ $prod[3] }}</strong></li>
                            @endforeach
                        </ul>
                    @endif

                    <hr class="my-2">
                    <table class="table table-sm table-borderless mb-0" style="font-size: 14px;">
                        @if (!empty($subtotalMatch[1]))
                            <tr>
                                <td class="text-muted" style="width: 150px;"><strong>Subtotal:</strong></td>
                                <td>${{ $subtotalMatch[1] }}</td>
                            </tr>
                        @endif
                        @if (!empty($descuentoMatch[1]) && $descuentoMatch[1] !== '0')
                            <tr>
                                <td class="text-muted"><strong>Descuento:</strong></td>
                                <td class="text-danger">-${{ $descuentoMatch[1] }}</td>
                            </tr>
                        @endif
                        @if (!empty($motivoMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Motivo Desc.:</strong></td>
                                <td><em>{{ trim($motivoMatch[1]) }}</em></td>
                            </tr>
                        @endif
                        @if (!empty($totalMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Total:</strong></td>
                                <td><strong class="text-success">${{ $totalMatch[1] }}</strong></td>
                            </tr>
                        @endif
                        @if (!empty($metodoMatch[1]))
                            <tr>
                                <td class="text-muted"><strong>Metodo Pago:</strong></td>
                                <td><span class="badge badge-light">{{ ucfirst($metodoMatch[1]) }}</span></td>
                            </tr>
                        @endif
                    </table>
                @else
                    {{-- Notas normales (no POS) --}}
                    <p class="mb-0 text-muted" style="white-space: pre-line;">{{ $order->notes }}</p>
                @endif
            </div>
        </div>
    @endif
@stop

@section('css')
    <style>
        .table-bordered td, .table-bordered th {
            vertical-align: middle;
        }
    </style>
@stop
