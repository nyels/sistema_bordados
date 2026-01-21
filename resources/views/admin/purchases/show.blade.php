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

    {{-- ALERTA DE COMPRA VENCIDA --}}
    @php
        // Calcular días de atraso usando solo fechas (sin horas)
        // Solo mostrar vencida si el día esperado YA PASÓ completamente
        $isOverdue = false;
        $daysOverdue = 0;
        if ($purchase->expected_at && $purchase->can_receive) {
            $todayStart = now()->startOfDay();
            $expectedStart = $purchase->expected_at->startOfDay();
            // Solo vencida si today > expected (no el mismo día)
            if ($todayStart->gt($expectedStart)) {
                $isOverdue = true;
                $daysOverdue = (int) $todayStart->diffInDays($expectedStart);
                // Para cambiar a horas: $hoursOverdue = (int) now()->diffInHours($purchase->expected_at);
            }
        }
    @endphp
    @if ($isOverdue)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle fa-pulse"></i>
                <strong>¡COMPRA VENCIDA!</strong>
            </h5>
            <hr>
            <p class="mb-0">
                Esta orden tiene <strong>{{ $daysOverdue }} día(s) de atraso</strong>.
                Fecha esperada: <strong>{{ $purchase->expected_at->format('d/m/Y') }}</strong>.
                Por favor, contacte al proveedor.
            </p>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    {{-- CABECERA --}}
    <div class="card card-primary">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-file-invoice"></i> ORDEN DE COMPRA: {{ $purchase->purchase_number }}
                </h3>
                <div>
                    {{-- BADGE DE FECHA VENCIDA --}}
                    @if ($purchase->expected_at && $purchase->status->value !== 'recibido')
                        @php
                            // Días restantes: positivo = faltan días, 0 = hoy, negativo = vencida
                            $daysRemaining = (int) now()
                                ->startOfDay()
                                ->diffInDays($purchase->expected_at->startOfDay(), false);
                        @endphp

                        @if ($daysRemaining < 0)
                            <span class="badge badge-danger mr-2" style="font-size: 12px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                VENCIDA ({{ abs($daysRemaining) }} {{ abs($daysRemaining) == 1 ? 'día' : 'días' }})
                            </span>
                        @elseif ($daysRemaining == 0)
                            <span class="badge badge-warning mr-2" style="font-size: 12px;">
                                <i class="fas fa-clock"></i>
                                VENCE HOY
                            </span>
                        @elseif ($daysRemaining <= 3)
                            <span class="badge badge-warning mr-2" style="font-size: 12px;">
                                <i class="fas fa-clock"></i>
                                Vence en {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'día' : 'días' }}
                            </span>
                        @else
                            <span class="badge badge-info mr-2" style="font-size: 12px;">
                                <i class="fas fa-calendar"></i>
                                {{ $purchase->expected_at->format('d/m/Y') }}
                            </span>
                        @endif
                    @endif

                    <span class="badge badge-{{ $purchase->status_color }}" style="font-size: 14px;">
                        <i class="{{ $purchase->status_icon }}"></i>
                        {{ $purchase->status_label }}
                    </span>
                </div>
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
                            <td>
                                @if ($purchase->expected_at)
                                    {{ $purchase->expected_at->format('d/m/Y') }}
                                    @if ($isOverdue)
                                        <span class="badge badge-danger">
                                            <i class="fas fa-exclamation-triangle"></i> Vencida
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
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

            {{-- BARRA DE PROGRESO --}}
            @if (in_array($purchase->status->value, ['pendiente', 'parcial', 'recibido']))
                @php
                    $totalOrdered = $purchase->items->sum('quantity');
                    $totalReceived = $purchase->items->sum('quantity_received');
                    $percentage = $totalOrdered > 0 ? min(100, ($totalReceived / $totalOrdered) * 100) : 0;
                @endphp
                <div class="card bg-light mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong>Progreso de Recepción</strong>
                            <span
                                class="badge badge-{{ $percentage >= 100 ? 'success' : ($percentage > 0 ? 'warning' : 'secondary') }}">
                                {{ number_format($percentage, 1) }}%
                            </span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-{{ $percentage >= 100 ? 'success' : ($percentage > 0 ? 'warning' : 'secondary') }}"
                                role="progressbar" style="width: {{ $percentage }}%;"
                                aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ number_format($percentage, 0) }}%
                            </div>
                        </div>
                        <small class="text-muted">
                            Recibido: {{ number_format($totalReceived, 2) }} de {{ number_format($totalOrdered, 2) }}
                            unidades
                        </small>
                    </div>
                </div>
            @endif

            {{-- ACCIONES --}}
            <div class="row mt-3 mb-3">
                <div class="col-12">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>

                    @if ($purchase->can_edit)
                        <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>

                        @if ($purchase->status->value === 'borrador')
                            {{-- Dropdown Confirmar Orden (hover) --}}
                            <div class="btn-group dropdown-hover">
                                <button type="button" class="btn btn-primary dropdown-toggle">
                                    <i class="fas fa-check"></i> Confirmar Orden
                                </button>
                                <div class="dropdown-menu">
                                    <button type="button" class="dropdown-item" id="btnConfirmAndReceive">
                                        <i class="fas fa-truck-loading text-success mr-2"></i> Completa/Recibido
                                        <small class="d-block text-muted">Confirmar y recibir en un paso</small>
                                    </button>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item" id="btnConfirmOrder">
                                        <i class="fas fa-clipboard-check text-primary mr-2"></i> Confirmado
                                        <small class="d-block text-muted">Solo confirmar (pendiente de recibir)</small>
                                    </button>
                                </div>
                            </div>

                            {{-- Forms ocultos para las acciones --}}
                            <form action="{{ route('admin.purchases.confirm', $purchase->id) }}" method="POST"
                                class="d-none" id="formConfirmOrder">
                                @csrf
                            </form>
                            <form action="{{ route('admin.purchases.confirm_and_receive', $purchase->id) }}" method="POST"
                                class="d-none" id="formConfirmAndReceive">
                                @csrf
                            </form>

                            <a href="{{ route('admin.purchases.confirm_delete', $purchase->id) }}" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        @endif
                    @endif

                    @if ($purchase->can_receive)
                        <a href="{{ route('admin.purchases.receive', $purchase->id) }}" class="btn btn-success">
                            <i class="fas fa-truck-loading"></i> Recibir Mercancía
                        </a>
                    @endif

                    @if ($purchase->can_cancel)
                        <a href="{{ route('admin.purchases.cancel', $purchase->id) }}" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Cancelar Orden
                        </a>
                    @endif
                </div>
            </div>

            <hr>

            {{-- COMPARATIVA PEDIDO VS RECIBIDO --}}
            @include('admin.purchases._comparison')

            {{-- TABS: ITEMS Y RECEPCIONES --}}
            <ul class="nav nav-tabs" id="purchaseTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="items-tab" data-toggle="tab" href="#items" role="tab">
                        <i class="fas fa-list"></i> Items ({{ $purchase->items->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="receptions-tab" data-toggle="tab" href="#receptions" role="tab">
                        <i class="fas fa-truck-loading"></i> Historial de Recepciones
                        @if ($receptions->count() > 0)
                            <span class="badge badge-info">{{ $receptions->count() }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="purchaseTabsContent">
                {{-- TAB: ITEMS --}}
                <div class="tab-pane fade show active" id="items" role="tabpanel">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Material</th>
                                    <th>Color / SKU</th>
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
                                    <tr class="{{ $item->is_fully_received ? 'table-success' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $item->materialVariant->material->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ $item->materialVariant->material->category->name ?? '' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($item->materialVariant->color)
                                                <span
                                                    class="badge badge-secondary">{{ $item->materialVariant->color }}</span><br>
                                            @endif
                                            <code>{{ $item->materialVariant->sku ?? 'N/A' }}</code>
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
                                            @if ($item->conversion_factor != 1 && $item->converted_quantity > 0)
                                                <br>
                                                <small class="text-muted">
                                                    ${{ number_format($item->subtotal / $item->converted_quantity, 2) }}/{{ $item->materialVariant->material->category->baseUnit->symbol ?? '' }}
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
                                    <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}"
                                        class="text-right">
                                        <strong>Subtotal:</strong>
                                    </td>
                                    <td class="text-right">${{ number_format($purchase->subtotal, 2) }}</td>
                                    @if ($purchase->status->value !== 'borrador')
                                        <td></td>
                                    @endif
                                </tr>
                                @if ($purchase->tax_rate > 0)
                                    <tr>
                                        <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}"
                                            class="text-right">
                                            <strong>IVA ({{ number_format($purchase->tax_rate, 0) }}%):</strong>
                                        </td>
                                        <td class="text-right">${{ number_format($purchase->tax_amount, 2) }}</td>
                                        @if ($purchase->status->value !== 'borrador')
                                            <td></td>
                                        @endif
                                    </tr>
                                @endif
                                @if ($purchase->discount_amount > 0)
                                    <tr>
                                        <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}"
                                            class="text-right">
                                            <strong>Descuento:</strong>
                                        </td>
                                        <td class="text-right text-danger">
                                            -${{ number_format($purchase->discount_amount, 2) }}</td>
                                        @if ($purchase->status->value !== 'borrador')
                                            <td></td>
                                        @endif
                                    </tr>
                                @endif
                                <tr class="table-primary">
                                    <td colspan="{{ $purchase->status->value !== 'borrador' ? 6 : 5 }}"
                                        class="text-right">
                                        <strong style="font-size: 16px;">TOTAL:</strong>
                                    </td>
                                    <td class="text-right">
                                        <strong
                                            style="font-size: 16px;">${{ number_format($purchase->total, 2) }}</strong>
                                    </td>
                                    @if ($purchase->status->value !== 'borrador')
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- TAB: HISTORIAL DE RECEPCIONES --}}
                <div class="tab-pane fade" id="receptions" role="tabpanel">
                    <div class="mt-3">
                        @if ($receptions->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No hay recepciones registradas para esta orden.
                            </div>
                        @else
                            {{-- TIMELINE DE RECEPCIONES --}}
                            <div class="timeline">
                                @foreach ($receptions as $reception)
                                    <div class="time-label">
                                        <span class="bg-{{ $reception->status_color }}">
                                            {{ $reception->received_at->format('d/m/y') }}
                                        </span>
                                    </div>

                                    <div>
                                        <i class="{{ $reception->status_icon }} bg-{{ $reception->status_color }}"></i>
                                        <div class="timeline-item">
                                            <span class="time">
                                                <i class="fas fa-clock"></i> {{ $reception->received_at->format('H:i') }}
                                            </span>
                                            <h3 class="timeline-header">
                                                <strong>{{ $reception->reception_number }}</strong>
                                                <span class="badge badge-{{ $reception->status_color }} ml-2">
                                                    {{ $reception->status_label }}
                                                </span>
                                                @if ($reception->delivery_note)
                                                    <small class="text-muted ml-2">
                                                        Guía: {{ $reception->delivery_note }}
                                                    </small>
                                                @endif
                                            </h3>
                                            <div class="timeline-body">
                                                <p class="mb-2">
                                                    <strong>Recibido por:</strong>
                                                    {{ $reception->receiver->name ?? 'N/A' }}
                                                </p>

                                                @if ($reception->notes)
                                                    <p class="mb-2">
                                                        <strong>Notas:</strong> {{ $reception->notes }}
                                                    </p>
                                                @endif

                                                @if ($reception->is_voided)
                                                    <div class="alert alert-danger py-1 px-2 mb-2">
                                                        <strong>Anulada por:</strong>
                                                        {{ $reception->voidedByUser->name ?? 'N/A' }}
                                                        el {{ $reception->voided_at->format('d/m/Y H:i') }}
                                                        <br>
                                                        <strong>Motivo:</strong> {{ $reception->void_reason }}
                                                    </div>
                                                @endif

                                                {{-- ITEMS DE LA RECEPCIÓN --}}
                                                <table class="table table-sm table-bordered mt-2">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Material</th>
                                                            <th>SKU</th>
                                                            <th class="text-center">Cantidad</th>
                                                            <th class="text-right">Costo Unit.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($reception->items as $item)
                                                            <tr class="{{ $reception->is_voided ? 'text-muted' : '' }}">
                                                                <td>{{ $item->materialVariant->material->name ?? 'N/A' }}
                                                                </td>
                                                                <td><code>{{ $item->materialVariant->sku ?? 'N/A' }}</code>
                                                                </td>
                                                                <td class="text-center">
                                                                    {{ number_format($item->quantity_received, 2) }}
                                                                    {{ $item->purchaseItem->unit->symbol ?? '' }}
                                                                    @if ($item->quantity_received != $item->converted_quantity)
                                                                        <br>
                                                                        <small class="text-info">
                                                                            =
                                                                            {{ number_format($item->converted_quantity, 2) }}
                                                                            {{ $item->materialVariant->material->category->baseUnit->symbol ?? '' }}
                                                                        </small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-right">
                                                                    ${{ number_format($item->unit_cost, 4) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="timeline-footer">
                                                @if ($reception->can_void)
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        data-toggle="modal" data-target="#voidModal{{ $reception->id }}">
                                                        <i class="fas fa-undo"></i> Anular Recepción
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALES DE ANULACIÓN --}}
    @foreach ($receptions->where('status', \App\Enums\ReceptionStatus::COMPLETED) as $reception)
        <div class="modal fade" id="voidModal{{ $reception->id }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ route('admin.purchases.receptions.void', [$purchase->id, $reception->id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-undo"></i> Anular Recepción
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <strong>Advertencia:</strong> Esta acción revertirá los movimientos de inventario
                                asociados a la recepción <strong>{{ $reception->reception_number }}</strong>.
                            </div>

                            <p>
                                <strong>Items afectados:</strong> {{ $reception->items->count() }}<br>
                                <strong>Fecha recepción:</strong> {{ $reception->received_at->format('d/m/Y H:i') }}
                            </p>

                            <div class="form-group">
                                <label>Motivo de Anulación <span class="text-danger">*</span></label>
                                <textarea name="void_reason" class="form-control" rows="3" required minlength="10" maxlength="500"
                                    placeholder="Explique el motivo de la anulación (mínimo 10 caracteres)..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger btn-void-reception">
                                <i class="fas fa-undo"></i> Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            // Confirmar Orden de Compra (Solo confirmar - pendiente de recibir)
            $('#btnConfirmOrder').on('click', function() {
                Swal.fire({
                    title: '¿Confirmar orden de compra?',
                    html: `
                        <div class="text-center">
                            <p>Esta acción cambiará el estado de la orden a <strong>Pendiente</strong>.</p>
                            <p class="mb-0 text-muted"><small>Una vez confirmada, no podrá eliminar la orden, solo cancelarla.</small></p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check"></i> Sí, confirmar',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'swal2-popup-custom',
                        title: 'swal2-title-custom',
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formConfirmOrder').submit();
                    }
                });
            });

            // Confirmar y Recibir en un solo paso
            $('#btnConfirmAndReceive').on('click', function() {
                Swal.fire({
                    title: '¿Confirmar y recibir orden completa?',
                    html: `
                        <div class="text-center">
                            <p>Esta acción realizará <strong>dos operaciones</strong>:</p>
                            <ol class="text-left pl-4">
                                <li>Confirmar la orden de compra</li>
                                <li>Recibir toda la mercancía automáticamente</li>
                            </ol>
                            <div class="alert alert-info py-2 mt-2">
                                <i class="fas fa-info-circle"></i>
                                El inventario se actualizará inmediatamente.
                            </div>
                            <p class="mb-0 text-muted"><small>Esta acción no se puede deshacer.</small></p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-truck-loading"></i> Sí, confirmar y recibir',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'swal2-popup-custom',
                        title: 'swal2-title-custom',
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formConfirmAndReceive').submit();
                    }
                });
            });

            // Anular Recepción
            $('.btn-void-reception').on('click', function() {
                const $form = $(this).closest('form');
                const $textarea = $form.find('textarea[name="void_reason"]');
                const reason = $textarea.val().trim();

                if (reason.length < 10) {
                    Swal.fire({
                        title: 'Motivo requerido',
                        text: 'Debe ingresar un motivo de al menos 10 caracteres.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Entendido'
                    });
                    $textarea.focus();
                    return;
                }

                Swal.fire({
                    title: '¿Anular esta recepción?',
                    html: `
                        <div class="text-left">
                            <div class="alert alert-danger py-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Advertencia:</strong> Esta acción revertirá los movimientos de inventario.
                            </div>
                            <p class="mb-0">Esta operación no se puede deshacer.</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-undo"></i> Sí, anular',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $form.submit();
                    }
                });
            });
        });
    </script>
@stop

@section('css')
    <style>
        /* SweetAlert2 Custom Styles */
        .swal2-popup {
            border-radius: 15px !important;
        }

        .swal2-title {
            font-size: 1.5rem !important;
        }

        .swal2-html-container {
            font-size: 1rem !important;
        }

        .swal2-actions {
            gap: 10px;
        }

        .swal2-actions .btn {
            margin: 0 5px;
            padding: 8px 20px;
        }

        .timeline {
            position: relative;
            margin: 0 0 30px 0;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #ddd;
            left: 31px;
            margin: 0;
            border-radius: 2px;
        }

        .timeline>div {
            position: relative;
            margin-right: 10px;
            margin-bottom: 15px;
        }

        .timeline>div>.timeline-item {
            margin-left: 60px;
            margin-right: 15px;
            margin-top: 0;
            border-radius: 3px;
            padding: 0;
            position: relative;
            background: #fff;
            border: 1px solid #ddd;
        }

        .timeline>div>.timeline-item>.time {
            color: #6c757d;
            float: right;
            padding: 10px;
            font-size: 15px;
            font-weight: bold;
        }

        .timeline>div>.timeline-item>.timeline-header {
            margin: 0;
            padding: 10px;
            border-bottom: 1px solid #f4f4f4;
            font-size: 14px;
            background: #f8f9fa;
        }

        .timeline>div>.timeline-item>.timeline-body {
            padding: 10px;
        }

        .timeline>div>.timeline-item>.timeline-footer {
            padding: 10px;
            background: #f8f9fa;
            border-top: 1px solid #f4f4f4;
        }

        .timeline>div>i {
            width: 30px;
            height: 30px;
            font-size: 15px;
            line-height: 30px;
            position: absolute;
            color: #fff;
            border-radius: 50%;
            left: 18px;
            text-align: center;
        }

        .time-label>span {
            font-weight: 600;
            padding: 5px 10px;
            display: inline-block;
            border-radius: 4px;
            color: #fff;
        }

        /* Dropdown Confirmar Orden - Hover */
        .dropdown-hover {
            position: relative;
        }

        .dropdown-hover .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 240px;
            padding: 8px 0;
            margin-top: 2px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: #fff;
            z-index: 1050;
        }

        .dropdown-hover:hover .dropdown-menu {
            display: block;
        }

        .dropdown-hover .dropdown-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.15s ease;
            white-space: nowrap;
        }

        .dropdown-hover .dropdown-item:hover {
            background-color: #f0f9ff;
        }

        .dropdown-hover .dropdown-item:active {
            background-color: #e0f2fe;
            color: inherit;
        }

        .dropdown-hover .dropdown-item small {
            font-size: 11px;
            margin-top: 2px;
        }

        .dropdown-hover .dropdown-divider {
            margin: 4px 0;
        }
    </style>
@stop
