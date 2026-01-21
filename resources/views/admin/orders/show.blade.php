@extends('adminlte::page')

@section('title', 'Pedido ' . $order->order_number)

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
            @if ($order->status === \App\Models\Order::STATUS_DRAFT)
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

    @include('admin.orders._timeline')

    @if($order->status === \App\Models\Order::STATUS_CONFIRMED)
        @include('admin.orders._blockers')
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
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

            @include('admin.orders._items-table')

            @include('admin.orders._design-section')

            @include('admin.orders._materials')

            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> Pagos</h5>
                    @if ($order->balance > 0 && !in_array($order->status, ['cancelled', 'delivered']))
                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalPayment">
                            <i class="fas fa-plus"></i> Registrar Pago
                        </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0" style="font-size: 14px;">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Metodo</th>
                                <th class="text-right">Monto</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                    <td>{{ $payment->method_label }}</td>
                                    <td class="text-right text-success font-weight-bold">${{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->reference ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Sin pagos</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="2"><strong>Pagado:</strong></td>
                                <td class="text-right text-success"><strong>${{ number_format($order->amount_paid, 2) }}</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2"><strong>Saldo:</strong></td>
                                <td class="text-right {{ $order->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    <strong style="font-size: 18px;">${{ number_format($order->balance, 2) }}</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- EVENTOS DEL SISTEMA (Automáticos) --}}
            @include('admin.orders._events')

            {{-- NOTAS OPERATIVAS (Humanos) --}}
            @include('admin.orders._messages')

            @if ($order->annexOrders->count() > 0)
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-project-diagram mr-2"></i> Pedidos Anexos ({{ $order->annexOrders->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0" style="font-size: 14px;">
                            <thead class="bg-light">
                                <tr>
                                    <th>Numero</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
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
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Estado</h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge badge-{{ $order->status_color }} p-2 mb-2" style="font-size: 16px;">
                        {{ $order->status_label }}
                    </span>
                    <br>
                    <span class="badge badge-{{ $order->payment_status_color }} p-2 mb-2">
                        Pago: {{ $order->payment_status_label }}
                    </span>
                    <br>
                    <span class="badge badge-{{ $order->urgency_color }} p-2">
                        <i class="fas fa-clock mr-1"></i> {{ $order->urgency_label }}
                    </span>
                </div>
            </div>

            @include('admin.orders._actions')

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar mr-2"></i> Fechas</h5>
                </div>
                <div class="card-body" style="font-size: 14px;">
                    <p class="mb-2"><strong>Creado:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    @if ($order->minimum_date)
                        <p class="mb-2"><strong>Fecha Minima:</strong> {{ $order->minimum_date->format('d/m/Y') }}</p>
                    @endif
                    @if ($order->promised_date)
                        <p class="mb-2">
                            <strong>Prometido:</strong> {{ $order->promised_date->format('d/m/Y') }}
                            @if ($order->promised_date < now() && !in_array($order->status, ['delivered', 'cancelled']))
                                <span class="badge badge-danger">VENCIDO</span>
                            @endif
                        </p>
                    @endif
                    @if ($order->delivered_date)
                        <p class="mb-0"><strong>Entregado:</strong> {{ $order->delivered_date->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>

            @if ($order->notes)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Notas</h5>
                    </div>
                    <div class="card-body" style="font-size: 14px;">
                        {{ $order->notes }}
                    </div>
                </div>
            @endif

            @if ($order->isInProduction() && !$order->isAnnex())
                <div class="card border-info">
                    <div class="card-body text-center">
                        <p class="mb-2 text-muted" style="font-size: 13px;">
                            El pedido esta en produccion. Cree un anexo para agregar productos.
                        </p>
                        <a href="{{ route('admin.orders.create-annex', $order) }}" class="btn btn-info btn-block">
                            <i class="fas fa-plus-circle mr-1"></i> Crear Anexo
                        </a>
                    </div>
                </div>
            @endif

            @if (!in_array($order->status, ['cancelled', 'delivered']))
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

    <div class="modal fade" id="modalPayment" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.payments.store', $order) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Registrar Pago</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Saldo pendiente: <strong style="font-size: 18px;">${{ number_format($order->balance, 2) }}</strong>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Monto *</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    max="{{ $order->balance }}" value="{{ $order->balance }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Metodo *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">Efectivo</option>
                                <option value="transfer">Transferencia</option>
                                <option value="card">Tarjeta</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Referencia</label>
                            <input type="text" name="reference" class="form-control" placeholder="No. operacion, folio, etc.">
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i> Guardar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
