@extends('adminlte::page')

@section('title', 'Recibir OC: ' . $purchase->purchase_number)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- ERRORES --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchases.receive.store', $purchase->id) }}" id="receiveForm">
        @csrf

        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-truck-loading"></i> RECIBIR MERCANCÍA: {{ $purchase->purchase_number }}
                </h3>
            </div>

            <div class="card-body">
                {{-- INFO DE LA COMPRA --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td style="width: 150px;"><strong>Proveedor:</strong></td>
                                <td>{{ $purchase->proveedor->nombre_proveedor ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha Orden:</strong></td>
                                <td>{{ $purchase->ordered_at?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td class="font-weight-bold">{{ $purchase->formatted_total }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Recepción <span class="text-danger">*</span></label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="receive_complete" name="receive_type" value="complete"
                                    class="custom-control-input" checked>
                                <label class="custom-control-label" for="receive_complete">
                                    <strong>Recepción Completa</strong>
                                    <small class="text-muted d-block">Recibir todos los items pendientes</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio mt-2">
                                <input type="radio" id="receive_partial" name="receive_type" value="partial"
                                    class="custom-control-input">
                                <label class="custom-control-label" for="receive_partial">
                                    <strong>Recepción Parcial</strong>
                                    <small class="text-muted d-block">Especificar cantidad por item</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                {{-- TABLA DE ITEMS --}}
                <h5 class="mb-3"><i class="fas fa-list"></i> Items a Recibir</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Material</th>
                                <th>SKU</th>
                                <th class="text-center">Ordenado</th>
                                <th class="text-center">Recibido</th>
                                <th class="text-center">Pendiente</th>
                                <th class="text-center" style="width: 150px;">A Recibir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->items as $item)
                                <tr class="{{ $item->is_fully_received ? 'table-success' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $item->materialVariant->material->name ?? 'N/A' }}</strong>
                                        @if ($item->materialVariant->color)
                                            <br><span
                                                class="badge badge-secondary">{{ $item->materialVariant->color }}</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $item->materialVariant->sku ?? 'N/A' }}</code></td>
                                    <td class="text-center">
                                        {{ number_format($item->quantity, 2) }} {{ $item->unit->symbol ?? '' }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($item->quantity_received, 2) }} {{ $item->unit->symbol ?? '' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($item->is_fully_received)
                                            <span class="badge badge-success">Completo</span>
                                        @else
                                            <span class="badge badge-warning">
                                                {{ number_format($item->pending_quantity, 2) }}
                                                {{ $item->unit->symbol ?? '' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->is_fully_received)
                                            <span class="text-success"><i class="fas fa-check"></i></span>
                                        @else
                                            <input type="hidden" name="items[{{ $loop->index }}][item_id]"
                                                value="{{ $item->id }}">
                                            <input type="number" name="items[{{ $loop->index }}][quantity]"
                                                class="form-control form-control-sm text-center partial-quantity"
                                                value="{{ $item->pending_quantity }}" min="0"
                                                max="{{ $item->pending_quantity }}" step="0.01" disabled>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- GUÍA Y NOTAS --}}
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Número de Guía / Remisión</label>
                            <input type="text" name="delivery_note" class="form-control" maxlength="100"
                                placeholder="Ej: GR-12345" value="{{ old('delivery_note') }}">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Notas de Recepción</label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                                placeholder="Observaciones sobre la recepción...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-center">
                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmar Recepción
                </button>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(function() {
            const $partialInputs = $('.partial-quantity');

            $('input[name="receive_type"]').on('change', function() {
                if ($(this).val() === 'partial') {
                    $partialInputs.prop('disabled', false);
                } else {
                    $partialInputs.prop('disabled', true);
                }
            });
        });
    </script>
@stop
