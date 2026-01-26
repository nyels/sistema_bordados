<div class="table-responsive">
    <table class="table table-hover table-striped mb-0">
        <thead class="thead-dark">
            <tr>
                <th>Pedido</th>
                <th>Fecha/Hora</th>
                <th>Vendedor</th>
                <th class="text-right">Total</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr class="{{ $sale->isCancelled() ? 'table-danger' : '' }}">
                    <td>
                        <strong>{{ $sale->order_number }}</strong>
                    </td>
                    <td>
                        {{ $sale->delivered_date?->format('d/m/Y') }}
                        <br>
                        <small class="text-muted">{{ $sale->created_at->format('H:i') }}</small>
                    </td>
                    <td>
                        {{ $sale->creator?->name ?? 'N/A' }}
                    </td>
                    <td class="text-right">
                        <strong>${{ number_format($sale->total, 2) }}</strong>
                    </td>
                    <td class="text-center">
                        @if ($sale->isCancelled())
                            <span class="badge badge-cancelled">
                                <i class="fas fa-ban mr-1"></i>Cancelada
                            </span>
                            @if ($sale->cancelled_at)
                                <br>
                                <small class="text-muted">
                                    {{ $sale->cancelled_at->format('d/m/Y H:i') }}
                                </small>
                            @endif
                        @else
                            <span class="badge badge-active">
                                <i class="fas fa-check mr-1"></i>Activa
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.pos-sales.show', $sale) }}"
                               class="btn btn-info" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if (!$sale->isCancelled())
                                <button type="button"
                                        class="btn btn-danger btn-cancel-sale"
                                        data-order-id="{{ $sale->id }}"
                                        data-order-number="{{ $sale->order_number }}"
                                        data-order-date="{{ $sale->delivered_date?->format('d/m/Y H:i') }}"
                                        data-order-total="{{ number_format($sale->total, 2) }}"
                                        data-order-seller="{{ $sale->creator?->name ?? 'N/A' }}"
                                        title="Cancelar venta">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No hay ventas POS registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($sales->hasPages())
    <div class="card-footer clearfix">
        {{ $sales->appends(request()->query())->links() }}
    </div>
@endif
