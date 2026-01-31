@extends('adminlte::page')

@section('title', 'Detalle Merma #' . $wasteEvent->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            <i class="{{ $wasteEvent->type_icon }} mr-2" style="color: #c62828;"></i>
            {{ $wasteEvent->type_label }}
            <small class="text-muted ml-2">#{{ $wasteEvent->id }}</small>
        </h1>
        <a href="{{ route('admin.waste.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
@stop

@section('content')
    {{-- ADVERTENCIA INMUTABILIDAD --}}
    <div class="alert mb-3" style="background: #ffebee; border: 1px solid #ef9a9a; border-left: 4px solid #c62828;">
        <div class="d-flex align-items-center">
            <i class="fas fa-lock mr-3" style="color: #c62828; font-size: 20px;"></i>
            <div>
                <strong style="color: #c62828;">Registro Inmutable</strong>
                <span style="color: #5d4037; font-size: 14px;">
                    - Este evento de merma no puede modificarse ni eliminarse.
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- INFORMACIÓN GENERAL --}}
            <div class="card">
                <div class="card-header py-2" style="background: #263238; color: white;">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Información del Evento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0" style="font-size: 16px;">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">UUID:</td>
                                    <td><code>{{ $wasteEvent->uuid }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tipo:</td>
                                    <td>
                                        <span class="badge badge-{{ $wasteEvent->type_color }} p-2">
                                            <i class="{{ $wasteEvent->type_icon }} mr-1"></i>
                                            {{ $wasteEvent->type_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Fecha:</td>
                                    <td>{{ $wasteEvent->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Registrado por:</td>
                                    <td>
                                        @if($wasteEvent->creator)
                                            <i class="fas fa-user mr-1"></i>
                                            {{ $wasteEvent->creator->name }}
                                        @else
                                            <span class="text-muted">Sistema</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0" style="font-size: 16px;">
                                @if($wasteEvent->order)
                                    <tr>
                                        <td class="text-muted" style="width: 140px;">Pedido:</td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $wasteEvent->order) }}"
                                               class="font-weight-bold">
                                                {{ $wasteEvent->order->order_number }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                                @if($wasteEvent->productVariant)
                                    <tr>
                                        <td class="text-muted">Producto:</td>
                                        <td>
                                            <strong>{{ $wasteEvent->productVariant->product?->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">SKU: {{ $wasteEvent->productVariant->sku_variant }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Cantidad:</td>
                                        <td>
                                            <strong style="color: #c62828;">{{ $wasteEvent->formatted_quantity }}</strong>
                                            unidades
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="text-muted">Evidencia:</td>
                                    <td>
                                        @if($wasteEvent->has_evidence)
                                            <a href="{{ $wasteEvent->evidence_path }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-file-image"></i> Ver
                                            </a>
                                        @else
                                            <span class="text-muted">Sin evidencia</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MOTIVO --}}
            <div class="card">
                <div class="card-header py-2" style="background: #455a64; color: white;">
                    <h5 class="mb-0"><i class="fas fa-comment-alt mr-2"></i> Motivo de la Merma</h5>
                </div>
                <div class="card-body" style="font-size: 16px;">
                    <p class="mb-0">{{ $wasteEvent->reason }}</p>
                </div>
            </div>

            {{-- MATERIALES (si aplica) --}}
            @if($wasteEvent->materialItems->count() > 0)
                <div class="card">
                    <div class="card-header py-2" style="background: #37474f; color: white;">
                        <h5 class="mb-0">
                            <i class="fas fa-cubes mr-2"></i>
                            Materiales Afectados
                            <span class="badge badge-light ml-2">{{ $wasteEvent->materialItems->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0" style="font-size: 16px;">
                            <thead style="background: #eceff1;">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Costo Unit.</th>
                                    <th class="text-right">Costo Total</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($wasteEvent->materialItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->materialVariant?->material?->name ?? 'N/A' }}</strong>
                                            @if($item->materialVariant?->color)
                                                <span class="badge badge-secondary ml-1">{{ $item->materialVariant->color }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ $item->formatted_quantity }}</td>
                                        <td class="text-right text-muted">{{ $item->formatted_unit_cost }}</td>
                                        <td class="text-right">
                                            <strong style="color: #c62828;">{{ $item->formatted_total_cost }}</strong>
                                        </td>
                                        <td>
                                            @if($item->notes)
                                                <small class="text-muted">{{ $item->notes }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot style="background: #fafafa;">
                                <tr>
                                    <td colspan="3" class="text-right"><strong>Total Materiales:</strong></td>
                                    <td class="text-right">
                                        <strong style="color: #c62828; font-size: 16px;">
                                            ${{ number_format($wasteEvent->materialItems->sum('total_cost'), 2) }}
                                        </strong>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- COSTO TOTAL --}}
            <div class="card" style="border: 2px solid #c62828;">
                <div class="card-header py-2" style="background: #c62828; color: white;">
                    <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> Costo Estimado</h5>
                </div>
                <div class="card-body text-center py-4">
                    <h2 style="color: #c62828; font-weight: 700; font-size: 32px;">
                        {{ $wasteEvent->formatted_total_cost }}
                    </h2>
                    <p class="text-muted mb-0" style="font-size: 14px;">
                        Costo estimado de la merma
                    </p>
                </div>
            </div>

            {{-- METADATOS --}}
            <div class="card">
                <div class="card-header py-2" style="background: #546e7a; color: white;">
                    <h5 class="mb-0"><i class="fas fa-clock mr-2"></i> Registro</h5>
                </div>
                <div class="card-body" style="font-size: 14px;">
                    <p class="mb-2">
                        <strong>Creado:</strong><br>
                        {{ $wasteEvent->created_at->format('d/m/Y H:i:s') }}
                    </p>
                    <p class="mb-0">
                        <strong>Última actualización:</strong><br>
                        {{ $wasteEvent->updated_at->format('d/m/Y H:i:s') }}
                    </p>
                </div>
            </div>

            {{-- AVISO --}}
            <div class="card border-warning">
                <div class="card-body" style="background: #fffde7;">
                    <p class="mb-0" style="font-size: 13px; color: #5d4037;">
                        <i class="fas fa-info-circle mr-1" style="color: #f57c00;"></i>
                        <strong>Nota:</strong> Este registro es solo contable.
                        El ajuste de inventario físico (si aplica) debe realizarse por separado.
                    </p>
                </div>
            </div>
        </div>
    </div>
@stop
