@extends('adminlte::page')

@section('title', 'OC: ' . $purchase->purchase_number)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info', 'warning'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    {{-- CABECERA --}}
    <div class="card card-primary">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-file-invoice"></i> ORDEN DE COMPRA: {{ $purchase->purchase_number }}
                </h3>
                <span class="badge badge-{{ $purchase->status_color }}" style="font-size: 14px;">
                    <i class="{{ $purchase->status_icon }}"></i>
                    {{ $purchase->status_label }}
                </span>
            </div>
        </div>

        <div class="card-body">
            {{-- INFO GENERAL --}}
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>Proveedor:</strong></td>
                            <td>{{ $purchase->proveedor->nombre_proveedor ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Referencia:</strong></td>
                            <td>{{ $purchase->reference ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Orden:</strong></td>
                            <td>{{ $purchase->ordered_at ? $purchase->ordered_at->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Esperada:</strong></td>
                            <td>{{ $purchase->expected_at ? $purchase->expected_at->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @if ($purchase->received_at)
                            <tr>
                                <td><strong>Fecha Recepción:</strong></td>
                                <td>
                                    {{ $purchase->received_at->format('d/m/Y H:i') }}
                                    @if ($purchase->receiver)
                                        <small class="text-muted">({{ $purchase->receiver->name }})</small>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td style="width: 150px;"><strong>Creado por:</strong></td>
                            <td>{{ $purchase->creator->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Creación:</strong></td>
                            <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if ($purchase->notes)
                            <tr>
                                <td><strong>Notas:</strong></td>
                                <td>{{ $purchase->notes }}</td>
                            </tr>
                        @endif
                        @if ($purchase->cancellation_reason)
                            <tr>
                                <td><strong>Motivo Cancelación:</strong></td>
                                <td class="text-danger">{{ $purchase->cancellation_reason }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="row mt-3 mb-3">
                <div class="col-12">
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>

                    @if ($purchase->can_edit)
                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>

                        @if ($purchase->status->value === 'borrador')
                            <form action="{{ route('purchases.confirm', $purchase->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary"
                                    onclick="return confirm('¿Confirmar esta orden de compra?')">
                                    <i class="fas fa-check"></i> Confirmar Orden
                                </button>
                            </form>

                            <a href="{{ route('purchases.confirm_delete', $purchase->id) }}" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        @endif
                    @endif

                    @if ($purchase->can_receive)
                        <a href="{{ route('purchases.receive', $purchase->id) }}" class="btn btn-success">
                            <i class="fas fa-truck-loading"></i> Recibir Mercancía
                        </a>
                    @endif

                    @if ($purchase->can_cancel)
                        <a href="{{ route('purchases.cancel', $purchase->id) }}" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Cancelar Orden
                        </a>
                    @endif
                </div>
            </div>

            <hr>

            {{-- ITEMS --}}
            <h5 class="mb-3"><i class="fas fa-list"></i> Detalle de Items</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Material</th>
                            <th>SKU / Color</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Unidad</th>
                            <th class="text-right">P. Unitario</th>
                            <th class="text-right">Subtotal</th>
                            @if ($purchase->status->value !== 'borrador')
                                <th class="text-center">Recibido</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $item->materialVariant->material->name ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $item->materialVariant->material->category->name ?? '' }}
                                    </small>
                                </td>
                                <td>
                                    <code>{{ $item->materialVariant->sku ?? 'N/A' }}</code>
                                    @if ($item->materialVariant->color)
                                        <br><span class="badge badge-secondary">{{ $item->materialVariant->color }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ number_format($item->quantity, 2) }}
                                    @if ($item->conversion_factor != 1)
                                        <br>
                                        <small class="text-info">
                                            = {{ number_format($item->converted_quantity, 2) }}
                                            {{ $item->materialVariant->material->category->baseUnit->symbol ?? '' }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ $item->unit->symbol ?? 'N/A' }}
                                </td>
                                <td class="text-right">
                                    ${{ number_format($item->unit_price, 2) }}
                                    @if ($item->conversion_factor != 1)
                                        <br>
                                        <small class="text-muted">
                                            ${{ number_format($item->converted_unit_cost, 4) }}/{{ $item->materialVariant->material->category->baseUnit->symbol ?? 'u' }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold">
                                    ${{ number_format($item->subtotal, 2) }}
                                </td>
                                @if ($purchase->status->value !== 'borrador')
                                    <td class="text-center">
                                        @if ($item->is_fully_received)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Completo
                                            </span>
                                        @elseif ($item->quantity_received > 0)
                                            <span class="badge badge-warning">
                                                {{ number_format($item->quantity_received, 2) }} /
                                                {{ number_format($item->quantity, 2) }}
                                            </span>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar bg-warning"
                                                    style="width: {{ $item->received_percentage }}%"></div>
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">Pendiente</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}" class="text-right">
                                <strong>Subtotal:</strong>
                            </td>
                            <td class="text-right">
                                ${{ number_format($purchase->subtotal, 2) }}
                            </td>
                            @if ($purchase->status->value !== 'borrador')
                                <td></td>
                            @endif
                        </tr>
                        @if ($purchase->tax_rate > 0)
                            <tr>
                                <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}" class="text-right">
                                    <strong>IVA ({{ number_format($purchase->tax_rate, 0) }}%):</strong>
                                </td>
                                <td class="text-right">
                                    ${{ number_format($purchase->tax_amount, 2) }}
                                </td>
                                @if ($purchase->status->value !== 'borrador')
                                    <td></td>
                                @endif
                            </tr>
                        @endif
                        @if ($purchase->discount_amount > 0)
                            <tr>
                                <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}" class="text-right">
                                    <strong>Descuento:</strong>
                                </td>
                                <td class="text-right text-danger">
                                    -${{ number_format($purchase->discount_amount, 2) }}
                                </td>
                                @if ($purchase->status->value !== 'borrador')
                                    <td></td>
                                @endif
                            </tr>
                        @endif
                        <tr class="table-primary">
                            <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}" class="text-right">
                                <strong style="font-size: 16px;">TOTAL:</strong>
                            </td>
                            <td class="text-right">
                                <strong style="font-size: 16px;">${{ number_format($purchase->total, 2) }}</strong>
                            </td>
                            @if ($purchase->status->value !== 'borrador')
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop
