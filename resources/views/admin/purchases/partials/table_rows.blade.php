@forelse ($purchases as $purchase)
    <tr>
        <td>
            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="font-weight-bold">
                {{ $purchase->purchase_number }}
            </a>
            @if ($purchase->reference)
                <br><small class="text-muted">Ref: {{ $purchase->reference }}</small>
            @endif
        </td>
        <td>
            {{ $purchase->proveedor->nombre_proveedor ?? 'N/A' }}
        </td>
        <td>
            {{ $purchase->ordered_at ? $purchase->ordered_at->format('d/m/Y') : '-' }}

            @if ($purchase->expected_at)
                @php
                    $orderDate = $purchase->ordered_at ? $purchase->ordered_at->format('d/m/Y') : '';
                    $expectedDate = $purchase->expected_at->format('d/m/Y');
                    $isOverdue =
                        $purchase->expected_at->startOfDay()->lt(now()->startOfDay()) && $purchase->can_receive;
                @endphp

                @if ($expectedDate !== $orderDate || $isOverdue)
                    <br>
                    <small class="{{ $isOverdue ? 'text-danger font-weight-bold' : 'text-muted' }}">
                        Espera: {{ $expectedDate }}
                    </small>
                @endif
            @endif
        </td>
        <td class="text-center">
            <span class="badge badge-{{ $purchase->status_color }}">
                <i class="{{ $purchase->status_icon }}"></i>
                {{ $purchase->status_label }}
            </span>
        </td>
        <td class="text-center align-middle">
            {{ $purchase->items->count() }}
        </td>
        <td class="text-center align-middle font-weight-bold">
            {{ $purchase->formatted_total }}
        </td>
        <td class="text-center">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-info" title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </a>

                @if ($purchase->can_edit)
                    <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-warning"
                        title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                @endif

                @if ($purchase->can_receive)
                    <a href="{{ route('admin.purchases.receive', $purchase->id) }}" class="btn btn-success"
                        title="Recibir">
                        <i class="fas fa-truck-loading"></i>
                    </a>
                @endif

                @if ($purchase->can_cancel)
                    <a href="{{ route('admin.purchases.cancel', $purchase->id) }}" class="btn btn-danger"
                        title="Cancelar">
                        <i class="fas fa-ban"></i>
                    </a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center">No se encontraron resultados</td>
    </tr>
@endforelse
