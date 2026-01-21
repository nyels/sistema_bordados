{{-- Partial: Tabla de pedidos (para AJAX) --}}
<div class="card mb-0">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0" style="font-size: 14px;">
            <thead class="bg-light">
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Items</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Pago</th>
                    <th>Entrega</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $isDelayed = $order->promised_date &&
                                     $order->promised_date->lt(now()->startOfDay()) &&
                                     !in_array($order->status, [\App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_CANCELLED]);
                    @endphp
                    <tr class="{{ $isDelayed ? 'table-danger' : '' }}">
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-weight-bold" style="font-size: 15px;">
                                {{ $order->order_number }}
                            </a>
                            @if($order->isAnnex())
                                <span class="badge badge-info ml-1">
                                    <i class="fas fa-link"></i>
                                </span>
                            @endif
                            @if($order->urgency_level !== 'normal')
                                <span class="badge badge-{{ $order->urgency_color }} ml-1">
                                    {{ $order->urgency_label }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</td>
                        <td>{{ $order->items->count() }}</td>
                        <td class="text-right font-weight-bold" style="font-size: 16px;">${{ number_format($order->total, 2) }}</td>
                        <td class="text-center">
                            <span class="badge badge-{{ $order->status_color }}" style="font-size: 13px;">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $order->payment_status_color }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </td>
                        <td>
                            @if($order->promised_date)
                                @if($isDelayed)
                                    <span class="text-danger font-weight-bold">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $order->promised_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    {{ $order->promised_date->format('d/m/Y') }}
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($order->status === \App\Models\Order::STATUS_DRAFT)
                                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No hay pedidos que coincidan con los filtros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="card-footer">
            {{ $orders->withQueryString()->links() }}
        </div>
    @endif
</div>
