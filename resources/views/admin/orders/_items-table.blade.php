{{-- 2. PRODUCTOS --}}
@php
    // Calcular totales técnicos globales del pedido
    $totalDisenosGlobal = 0;
    $totalPuntadasGlobal = 0;
    $totalEstimadoGlobal = 0;
    $itemsConDisenos = 0;
    $complejidadTotal = 'Baja';

    foreach ($order->items as $itm) {
        // Items con personalización (designExports vinculados)
        if ($itm->requiresTechnicalDesigns() && $itm->designExports->count() > 0) {
            $itemsConDisenos++;
            foreach ($itm->designExports as $de) {
                $totalDisenosGlobal++;
                $puntadas = $de->stitches_count ?? 0;
                $totalPuntadasGlobal += $puntadas;
            }
            // Usar embroidery_cost del producto si existe
            if ($itm->product && $itm->product->embroidery_cost > 0) {
                $totalEstimadoGlobal += $itm->product->embroidery_cost * $itm->quantity;
            }
        }
        // Productos estándar con diseño predefinido
        elseif ($itm->product && $itm->product->embroidery_cost > 0) {
            $totalPuntadasGlobal += $itm->product->total_stitches * $itm->quantity;
            $totalEstimadoGlobal += $itm->product->embroidery_cost * $itm->quantity;
        }
    }

    // Determinar complejidad basada en puntadas totales
    if ($totalPuntadasGlobal > 100000) {
        $complejidadTotal = 'Alta';
        $complejidadColor = '#c62828';
        $complejidadBg = '#ffebee';
    } elseif ($totalPuntadasGlobal > 30000) {
        $complejidadTotal = 'Media';
        $complejidadColor = '#e65100';
        $complejidadBg = '#fff3e0';
    } else {
        $complejidadTotal = 'Baja';
        $complejidadColor = '#2e7d32';
        $complejidadBg = '#e8f5e9';
    }
@endphp

<div class="card card-section-productos">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-box mr-2"></i> Productos</h5>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size: 15px;">
            <thead style="background: #343a40; color: white;">
                <tr>
                    <th style="width: 70px; color: white;">Img</th>
                    <th style="color: white;">Producto</th>
                    <th class="text-center" style="width: 60px; color: white;">Cant.</th>
                    <th class="text-center" style="width: 70px; color: white;">Estado</th>
                    <th class="text-right" style="width: 100px; color: white;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php
                        $baseSubtotal = $item->quantity * $item->unit_price;
                        $extras = is_array($item->extras) ? $item->extras : [];
                        $extrasSubtotal = collect($extras)->sum(
                            fn($e) => floatval($e['price'] ?? 0) * intval($e['quantity'] ?? 1),
                        );
                        $itemTotal = $baseSubtotal + $extrasSubtotal;

                        $blocksR2 = $item->has_pending_adjustments;
                        $blocksR3 =
                            $item->personalization_type === \App\Models\OrderItem::PERSONALIZATION_DESIGN &&
                            !$item->design_approved;
                        $blocksR4 = $item->hasMeasurementsChangedAfterApproval();
                        $blocksR5 = $item->blocksProductionForTechnicalDesigns();
                        $hasBlocker = $blocksR2 || $blocksR3 || $blocksR4 || $blocksR5;

                        // Datos de diseños vinculados
                        $linkedDesigns = $item->designExports;
                        $requiresDesigns = $item->requiresTechnicalDesigns();
                        $hasLinkedDesigns = $linkedDesigns->count() > 0;

                        // Calcular puntadas y costo del item
                        $itemPuntadas = $linkedDesigns->count() > 0
                            ? $linkedDesigns->sum('stitches_count')
                            : ($item->product?->total_stitches ?? 0);
                        // Usar embroidery_cost del producto
                        $itemEstimado = ($item->product?->embroidery_cost ?? 0) * $item->quantity;

                        // Obtener variante del producto (Talla/Color)
                        $variantDisplay = $item->variant?->attributes_display;

                        // Medidas del item
                        $measurements = is_array($item->measurements) ? $item->measurements : [];
                        $hasMeasurements =
                            !empty($measurements) &&
                            count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) > 0;
                        $measurementCount = $hasMeasurements
                            ? count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'))
                            : 0;
                    @endphp
                    <tr class="{{ $hasBlocker ? 'table-danger' : '' }}">
                        <td class="align-top" style="padding: 10px 8px;">
                            @if ($item->product && $item->product->primaryImage)
                                <img src="{{ $item->product->primaryImage->thumbnail_small_url }}"
                                    class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                            @else
                                <img src="{{ asset('img/no-image.png') }}" class="img-fluid rounded"
                                    style="width: 60px; height: 60px; object-fit: cover;">
                            @endif
                        </td>
                        <td style="padding: 10px 8px;">
                            {{-- Nombre y variante --}}
                            <strong style="font-size: 15px; color: #212529;">{{ $item->product_name }}</strong>
                            @if ($variantDisplay)
                                <span
                                    style="background: #e7f3ff; color: #0056b3; padding: 2px 6px; border-radius: 3px; font-size: 14px; margin-left: 6px; font-weight: 600;">
                                    {{ $variantDisplay }}
                                </span>
                            @endif
                            @if ($item->is_annex)
                                <span class="badge badge-warning ml-1" style="font-size: 14px;">ANEXO</span>
                            @endif

                            {{-- Personalización inline --}}
                            @if ($item->embroidery_text)
                                <div class="mt-1">
                                    <span
                                        style="background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-size: 15px; display: inline-block;">
                                        <i class="fas fa-pen-fancy mr-1"></i>{{ $item->embroidery_text }}
                                    </span>
                                </div>
                            @endif

                            @if ($item->customization_notes)
                                <div class="mt-1">
                                    <span
                                        style="background: #fff3e0; color: #e65100; padding: 4px 8px; border-radius: 4px; font-size: 15px; display: inline-block;">
                                        <i
                                            class="fas fa-sticky-note mr-1"></i>{{ Str::limit($item->customization_notes, 50) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Badges compactos: Medidas + Diseños --}}
                            <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 6px;">
                                @if ($item->requires_measurements)
                                    @if ($hasMeasurements)
                                        {{-- Medidas capturadas: mostrar badge con opciones --}}
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm py-1 px-2"
                                                style="background: #6f42c1; color: white; font-size: 14px;"
                                                data-toggle="modal" data-target="#measurementsModal{{ $item->id }}">
                                                <i class="fas fa-ruler-combined mr-1"></i>{{ $measurementCount }}
                                                medidas
                                            </button>
                                            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                                <button type="button"
                                                    class="btn btn-sm py-1 px-1 dropdown-toggle dropdown-toggle-split"
                                                    style="background: #5e35b1; color: white; font-size: 14px;"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="sr-only">Opciones</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        onclick="openItemMeasurementsEdit({{ $item->id }}, '{{ addslashes($item->product_name) }}', {{ json_encode($measurements) }})">
                                                        <i class="fas fa-edit mr-2 text-primary"></i> Editar medidas
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        data-toggle="modal"
                                                        data-target="#measurementsModal{{ $item->id }}">
                                                        <i class="fas fa-eye mr-2 text-secondary"></i> Ver medidas
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Sin medidas: alerta con botón de captura --}}
                                        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                            <button type="button" class="btn btn-sm py-1 px-2"
                                                style="background: #e65100; color: white; font-size: 14px;"
                                                onclick="openItemMeasurementsEdit({{ $item->id }}, '{{ addslashes($item->product_name) }}', null)">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Capturar medidas
                                            </button>
                                        @else
                                            <span class="badge"
                                                style="background: #ffccbc; color: #bf360c; font-size: 14px; padding: 4px 8px;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Sin medidas
                                            </span>
                                        @endif
                                    @endif
                                @endif

                                {{-- DISEÑOS TÉCNICOS - Versión compacta inline --}}
                                @if ($requiresDesigns)
                                    @if ($hasLinkedDesigns)
                                        {{-- Diseños vinculados: línea compacta con toggle --}}
                                        <div class="d-inline-flex align-items-center"
                                            style="background: #e8f5e9; border-radius: 4px; padding: 4px 8px; border: 1px solid #a5d6a7;">
                                            <i class="fas fa-check-circle mr-1"
                                                style="color: #2e7d32; font-size: 14px;"></i>
                                            <span style="font-size: 14px; color: #1b5e20; font-weight: 600;">
                                                {{ $linkedDesigns->count() }} diseño(s)
                                            </span>
                                            <span style="font-size: 14px; color: #388e3c; margin-left: 4px;">
                                                · {{ number_format($itemPuntadas) }} pts
                                            </span>
                                            @if ($order->status === \App\Models\Order::STATUS_CONFIRMED)
                                                <span
                                                    style="font-size: 14px; color: #7b1fa2; margin-left: 4px; font-weight: 600;">
                                                    · Est. ${{ number_format($itemEstimado, 2) }}
                                                </span>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-link p-0 ml-2"
                                                style="font-size: 14px; color: #1565c0;" data-toggle="collapse"
                                                data-target="#designDetails{{ $item->id }}">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                                <button type="button" class="btn btn-sm btn-link p-0 ml-1"
                                                    style="font-size: 14px; color: #1565c0;"
                                                    onclick="openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Sin diseños: alerta compacta --}}
                                        <div class="d-inline-flex align-items-center"
                                            style="background: #fff3cd; border-radius: 4px; padding: 4px 8px; border: 1px solid #ffc107;">
                                            <i class="fas fa-exclamation-triangle mr-1"
                                                style="color: #856404; font-size: 14px;"></i>
                                            <span style="font-size: 14px; color: #856404; font-weight: 500;">Sin
                                                diseño</span>
                                            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                                <button type="button" class="btn btn-sm btn-link p-0 ml-2"
                                                    style="font-size: 14px; color: #1565c0;"
                                                    onclick="openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}')">
                                                    <i class="fas fa-link mr-1"></i>Vincular diseño (texto, logo, etc.)
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>

                            {{-- Panel colapsable de diseños (solo si tiene diseños) --}}
                            @if ($requiresDesigns && $hasLinkedDesigns)
                                <div class="collapse mt-2" id="designDetails{{ $item->id }}">
                                    <div
                                        style="background: #fafafa; border-radius: 6px; padding: 8px; border: 1px solid #e0e0e0;">
                                        @foreach ($linkedDesigns as $design)
                                            <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-2 pb-2' : '' }}"
                                                style="{{ !$loop->last ? 'border-bottom: 1px solid #e0e0e0;' : '' }}">
                                                <div class="d-flex align-items-center">
                                                    @if ($design->svg_content)
                                                        <div
                                                            style="width: 28px; height: 28px; background: white; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-right: 8px; border: 1px solid #e0e0e0;">
                                                            {!! $design->svg_content !!}
                                                        </div>
                                                    @else
                                                        <div
                                                            style="width: 28px; height: 28px; background: #1565c0; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                                                            <i class="fas fa-vector-square"
                                                                style="color: white; font-size: 12px;"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span
                                                            style="font-size: 14px; color: #212529; font-weight: 500;">
                                                            {{ $design->application_label ?? ($design->export_name ?? 'Diseño #' . $design->id) }}
                                                        </span>
                                                        <br>
                                                        <span style="font-size: 14px; color: #495057;">
                                                            @if ($design->stitches_count)
                                                                {{ number_format($design->stitches_count) }} pts
                                                            @endif
                                                            @if ($design->width_mm)
                                                                • {{ $design->width_mm }}×{{ $design->height_mm }}mm
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-link p-1"
                                                        style="color: #1565c0; font-size: 14px;" title="Ver"
                                                        onclick="showDesignPreview({{ $design->id }}, {{ $item->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                                        <button type="button" class="btn btn-sm btn-link p-1"
                                                            style="color: #c62828; font-size: 14px;" title="Quitar"
                                                            onclick="unlinkDesignFromItem({{ $order->id }}, {{ $item->id }}, {{ $design->id }}, '{{ addslashes($design->application_label ?? $design->export_name) }}')">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach

                                        {{-- Estimado técnico inline (solo en CONFIRMED) --}}
                                        @if ($order->status === \App\Models\Order::STATUS_CONFIRMED && $itemEstimado > 0)
                                            <div class="mt-2 pt-2" style="border-top: 1px dashed #bdbdbd;">
                                                <div class="d-flex justify-content-between align-items-center"
                                                    style="font-size: 14px;">
                                                    <span style="color: #7b1fa2;">
                                                        <i class="fas fa-calculator mr-1"></i>
                                                        {{ number_format($itemPuntadas) }} pts × {{ $item->quantity }} pz
                                                    </span>
                                                    <strong style="color: #7b1fa2;">Costo bordado:
                                                        ${{ number_format($itemEstimado, 2) }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Extras compactos --}}
                            @if (count($extras) > 0)
                                <div class="mt-1" style="font-size: 14px;">
                                    @foreach ($extras as $extra)
                                        <span class="badge mr-1"
                                            style="background: #0277bd; color: white; font-size: 14px;">{{ $extra['name'] ?? 'Extra' }}
                                            +${{ number_format($extra['price'] ?? 0, 2) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center align-middle"
                            style="font-size: 16px; font-weight: 600; color: #212529;">{{ $item->quantity }}</td>
                        <td class="text-center align-middle">
                            {{-- Bloqueos R2-R5: Solo en CONFIRMED --}}
                            @if ($order->status === \App\Models\Order::STATUS_CONFIRMED)
                                @if ($blocksR2)
                                    <span class="badge badge-danger" style="font-size: 14px;"
                                        title="Ajuste de precio pendiente">R2</span>
                                @elseif($blocksR3)
                                    <span class="badge badge-danger" style="font-size: 14px;"
                                        title="Diseño no aprobado">R3</span>
                                @elseif($blocksR4)
                                    <span class="badge badge-danger" style="font-size: 14px;"
                                        title="Medidas modificadas">R4</span>
                                @elseif($blocksR5)
                                    <span class="badge badge-danger" style="font-size: 14px;"
                                        title="Sin diseño técnico">R5</span>
                                @else
                                    <span style="color: #28a745; font-size: 16px;"><i class="fas fa-check"></i></span>
                                @endif
                            @else
                                {{-- DRAFT: sin validación, IN_PRODUCTION+: check fijo (congelado) --}}
                                <span style="color: #28a745; font-size: 16px;"><i class="fas fa-check"></i></span>
                            @endif
                        </td>
                        <td class="text-right align-middle">
                            <strong
                                style="font-size: 15px; color: #212529;">${{ number_format($itemTotal, 2) }}</strong>
                            @if ($extrasSubtotal > 0)
                                <br><span
                                    style="color: #0277bd; font-size: 14px; font-weight: 500;">+${{ number_format($extrasSubtotal, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot style="background: #f8f9fa;">
                <tr>
                    <td colspan="4" class="text-right" style="font-size: 14px; color: #212529;">
                        <strong>Subtotal:</strong></td>
                    <td class="text-right" style="font-size: 15px; color: #212529;">
                        <strong>${{ number_format($order->subtotal, 2) }}</strong></td>
                </tr>
                @if ($order->discount > 0)
                    <tr>
                        <td colspan="4" class="text-right" style="font-size: 14px; color: #c62828;">Descuento:
                        </td>
                        <td class="text-right" style="font-size: 15px; color: #c62828;">
                            -${{ number_format($order->discount, 2) }}</td>
                    </tr>
                @endif
                @if ($order->requires_invoice && $order->iva_amount > 0)
                    <tr>
                        <td colspan="4" class="text-right" style="font-size: 14px; color: #212529;">IVA 16%:</td>
                        <td class="text-right" style="font-size: 15px; color: #212529;">
                            ${{ number_format($order->iva_amount, 2) }}</td>
                    </tr>
                @endif
                <tr style="background: #007bff;">
                    <td colspan="4" class="text-right" style="color: white;"><strong
                            style="font-size: 16px;">TOTAL:</strong></td>
                    <td class="text-right" style="color: white;"><strong
                            style="font-size: 20px;">${{ number_format($order->total, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- RESUMEN TÉCNICO DEL PEDIDO (Solo si hay items personalizados con diseños y en estado CONFIRMED) --}}
@if ($order->status === \App\Models\Order::STATUS_CONFIRMED && $totalDisenosGlobal > 0)
    <div class="card mt-3" style="border: 1px solid #7b1fa2;">
        <div class="card-header py-2"
            style="background: linear-gradient(135deg, #7b1fa2 0%, #6a1b9a 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="font-size: 16px;">
                    <i class="fas fa-calculator mr-2"></i> Resumen Técnico del Pedido (Pre-producción)
                </h6>
                <span class="badge"
                    style="background: {{ $complejidadBg }}; color: {{ $complejidadColor }}; font-size: 14px;">
                    Complejidad: {{ $complejidadTotal }}
                </span>
            </div>
        </div>
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-3">
                    <div style="font-size: 14px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px;">
                        Items c/diseño</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1565c0;">{{ $itemsConDisenos }}</div>
                </div>
                <div class="col-3">
                    <div style="font-size: 14px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px;">
                        Total diseños</div>
                    <div style="font-size: 20px; font-weight: 700; color: #2e7d32;">{{ $totalDisenosGlobal }}</div>
                </div>
                <div class="col-3">
                    <div style="font-size: 14px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px;">
                        Puntadas</div>
                    <div style="font-size: 20px; font-weight: 700; color: #e65100;">
                        {{ number_format($totalPuntadasGlobal) }} pts</div>
                </div>
                <div class="col-3">
                    <div style="font-size: 14px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px;">
                        Est. Técnico</div>
                    <div style="font-size: 20px; font-weight: 700; color: #7b1fa2;">
                        ${{ number_format($totalEstimadoGlobal, 2) }}</div>
                </div>
            </div>
            <div class="mt-2 py-2 px-3 rounded"
                style="background: #fff3e0; border: 1px solid #ffcc80; font-size: 14px; color: #e65100;">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Estimado técnico pre-producción.</strong> El costo real se calcula según consumo de tiempo de
                máquina e hilos durante la producción.
            </div>
        </div>
    </div>
@endif

{{-- MODALES DE MEDIDAS CON TRAZABILIDAD (FASE Y) --}}
@foreach ($order->items as $item)
    @php
        $measurements = is_array($item->measurements) ? $item->measurements : [];
        $hasMeasurements =
            !empty($measurements) && count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) > 0;
        $measurementImages = [
            'busto' => 'busto.png',
            'alto_cintura' => 'alto_cintura.png',
            'cintura' => 'cintura.png',
            'cadera' => 'cadera.png',
            'largo' => 'largo.png',
            'largo_vestido' => 'largo_vestido.png',
        ];
        // Cargar historial vinculado si existe
        $historyRecord = null;
        if ($item->measurement_history_id) {
            $historyRecord = \App\Models\ClientMeasurementHistory::with('creator')->find($item->measurement_history_id);
        }
    @endphp
    @if ($hasMeasurements)
        <div class="modal fade" id="measurementsModal{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: #6f42c1; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-ruler-combined mr-2"></i>
                            Medidas: {{ $item->product_name }}
                            @if ($item->variant?->attributes_display)
                                <span class="badge badge-light ml-2"
                                    style="font-size: 12px;">{{ $item->variant->attributes_display }}</span>
                            @endif
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        {{-- TRAZABILIDAD (FASE Y) --}}
                        @if ($historyRecord)
                            <div class="mb-3 p-2 rounded d-flex flex-wrap align-items-center justify-content-between"
                                style="background: #f3e5f5; border: 1px solid #ce93d8; gap: 8px;">
                                <div class="d-flex align-items-center" style="gap: 12px;">
                                    @php
                                        $sourceColors = [
                                            'order' => [
                                                'bg' => '#e3f2fd',
                                                'color' => '#1565c0',
                                                'icon' => 'fa-shopping-cart',
                                            ],
                                            'manual' => [
                                                'bg' => '#e8f5e9',
                                                'color' => '#2e7d32',
                                                'icon' => 'fa-user-edit',
                                            ],
                                            'import' => [
                                                'bg' => '#fff3e0',
                                                'color' => '#e65100',
                                                'icon' => 'fa-file-import',
                                            ],
                                        ];
                                        $sourceStyle = $sourceColors[$historyRecord->source] ?? [
                                            'bg' => '#f5f5f5',
                                            'color' => '#757575',
                                            'icon' => 'fa-question',
                                        ];
                                    @endphp
                                    <span class="badge"
                                        style="background: {{ $sourceStyle['bg'] }}; color: {{ $sourceStyle['color'] }}; font-size: 14px; padding: 6px 10px;">
                                        <i class="fas {{ $sourceStyle['icon'] }} mr-1"></i>
                                        {{ $historyRecord->source_label }}
                                    </span>
                                    <span style="font-size: 14px; color: #495057;">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        {{ $historyRecord->captured_at?->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <span style="font-size: 14px; color: #6a1b9a;">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $historyRecord->creator?->name ?? 'Sistema' }}
                                </span>
                            </div>
                        @endif

                        <div class="row">
                            @foreach ($measurements as $key => $value)
                                @if (!empty($value) && $value !== '0' && $key !== 'save_to_client')
                                    @php
                                        $imageName = $measurementImages[$key] ?? null;
                                        $labelText = str_replace('_', ' ', strtoupper($key));
                                    @endphp
                                    <div class="col-md-4 col-6 mb-3">
                                        <div class="medida-card-show text-center">
                                            @if ($imageName)
                                                <img src="{{ asset('images/' . $imageName) }}"
                                                    alt="{{ $labelText }}" class="medida-img-show">
                                            @endif
                                            <div class="medida-label-show">{{ $labelText }}</div>
                                            <div class="medida-value-show">
                                                {{ $value }} <span class="medida-unit">cm</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Notas de captura --}}
                        @if ($historyRecord && $historyRecord->notes)
                            <div class="mt-3 p-2 rounded" style="background: #fff8e1; border: 1px solid #ffe082;">
                                <small style="color: #f57c00;">
                                    <i class="fas fa-sticky-note mr-1"></i>
                                    <strong>Nota:</strong> {{ $historyRecord->notes }}
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer" style="background: #f8f9fa;">
                        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                            <button type="button" class="btn btn-outline-primary" style="font-size: 14px;"
                                onclick="$('#measurementsModal{{ $item->id }}').modal('hide'); openItemMeasurementsEdit({{ $item->id }}, '{{ addslashes($item->product_name) }}', {{ json_encode($measurements) }})">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                        @endif
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="font-size: 14px;">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- Estilos compactos --}}
<style>
    .medida-card-show {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 15px 10px;
        background: #ffffff;
        transition: box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .medida-card-show:hover {
        border-color: #6f42c1;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.15);
    }

    .medida-img-show {
        width: 100%;
        max-height: 80px;
        object-fit: contain;
        margin-bottom: 10px;
    }

    .medida-label-show {
        font-weight: 600;
        font-size: 14px;
        color: #495057;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .medida-value-show {
        font-size: 28px;
        font-weight: 700;
        color: #6f42c1;
    }

    .medida-value-show .medida-unit {
        font-size: 16px;
        font-weight: 400;
        color: #6c757d;
    }

    /* Collapse icon rotation */
    [data-toggle="collapse"] .fa-chevron-down {
        transition: transform 0.2s ease;
    }

    [data-toggle="collapse"][aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
    }

    /* Design items compact */
    .linked-design-item svg {
        max-width: 100%;
        max-height: 100%;
    }
</style>

{{-- MODAL: SELECTOR DE DISEÑO PARA ITEM (Solo DRAFT/CONFIRMED) --}}
@if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
    <div class="modal fade" id="selectDesignForItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); color: white;">
                    <h5 class="modal-title" id="selectDesignForItemModalTitle">
                        <i class="fas fa-link mr-2"></i> Vincular diseño (texto o logo)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentItemId" value="">
                    <input type="hidden" id="currentItemName" value="">
                    <div class="mb-3 p-2 rounded" style="background: #e3f2fd; border-left: 4px solid #1565c0;">
                        <strong style="color: #0d47a1;" id="selectingForProduct">Producto</strong>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-8 mb-2 mb-md-0">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"
                                        style="background: #e3f2fd; border-color: #90caf9;">
                                        <i class="fas fa-search" style="color: #1565c0;"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" id="searchDesignForItem"
                                    placeholder="Buscar diseño..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-block" id="btnSearchDesignForItem"
                                style="background: #1565c0; color: white;">
                                <i class="fas fa-search mr-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div id="designsForItemResults" class="list-group" style="max-height: 350px; overflow-y: auto;">
                    </div>
                    <div id="designsForItemLoader" class="text-center p-4 d-none">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2" style="color: #1565c0;">Buscando diseños...</p>
                    </div>
                    <div id="designsForItemEmpty" class="text-center p-4 d-none"
                        style="background: #fff3e0; border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color: #ff9800;"></i>
                        <p style="color: #e65100;">No se encontraron diseños aprobados</p>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f5f5f5;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: PREVIEW DE DISEÑO VINCULADO --}}
    <div class="modal fade" id="designPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: #1565c0; color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-vector-square mr-2"></i> <span id="previewDesignName">Diseño</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <div id="previewDesignImage"
                        style="min-height: 200px; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #1565c0;"></i>
                    </div>
                    <div id="previewDesignDetails" style="text-align: left;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endif {{-- Fin modales DRAFT/CONFIRMED --}}

@push('js')
    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
        <script>
            (function() {
                'use strict';
                var orderId = {{ $order->id }};
                var allDesignsForItem = [];

                window.openDesignSelectorForItem = function(itemId, itemName) {
                    document.getElementById('currentItemId').value = itemId;
                    document.getElementById('currentItemName').value = itemName;
                    document.getElementById('selectingForProduct').textContent = itemName;
                    document.getElementById('selectDesignForItemModalTitle').innerHTML =
                        '<i class="fas fa-link mr-2"></i> Vincular diseño (texto o logo) a: ' + escapeHtml(itemName);
                    document.getElementById('searchDesignForItem').value = '';
                    document.getElementById('designsForItemResults').innerHTML = '';
                    document.getElementById('designsForItemResults').classList.remove('d-none');
                    document.getElementById('designsForItemLoader').classList.add('d-none');
                    document.getElementById('designsForItemEmpty').classList.add('d-none');
                    $('#selectDesignForItemModal').modal('show');
                    if (allDesignsForItem.length === 0) {
                        loadDesignsForItem();
                    } else {
                        renderDesignsForItem(allDesignsForItem);
                    }
                };

                function loadDesignsForItem() {
                    document.getElementById('designsForItemResults').classList.add('d-none');
                    document.getElementById('designsForItemLoader').classList.remove('d-none');
                    document.getElementById('designsForItemEmpty').classList.add('d-none');
                    fetch('{{ route('admin.products.ajax.approved_exports') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            allDesignsForItem = Array.isArray(data) ? data : (data.data || []);
                            filterAndRenderDesignsForItem();
                        })
                        .catch(function() {
                            document.getElementById('designsForItemLoader').classList.add('d-none');
                            document.getElementById('designsForItemEmpty').classList.remove('d-none');
                        });
                }

                function filterAndRenderDesignsForItem() {
                    document.getElementById('designsForItemLoader').classList.add('d-none');
                    var query = document.getElementById('searchDesignForItem').value.trim().toLowerCase();
                    var filtered = allDesignsForItem;
                    if (query) {
                        filtered = allDesignsForItem.filter(function(item) {
                            var text = [item.export_name || '', item.design_name || '', item.app_type_name || '']
                                .join(' ').toLowerCase();
                            return text.indexOf(query) !== -1;
                        });
                    }
                    if (filtered.length === 0) {
                        document.getElementById('designsForItemResults').classList.add('d-none');
                        document.getElementById('designsForItemEmpty').classList.remove('d-none');
                    } else {
                        renderDesignsForItem(filtered);
                        document.getElementById('designsForItemResults').classList.remove('d-none');
                        document.getElementById('designsForItemEmpty').classList.add('d-none');
                    }
                }

                function renderDesignsForItem(items) {
                    var container = document.getElementById('designsForItemResults');
                    container.innerHTML = '';
                    items.forEach(function(item) {
                        var preview = item.svg_content ?
                            '<div style="width:40px;height:40px;background:white;border-radius:4px;display:flex;align-items:center;justify-content:center;overflow:hidden;">' +
                            item.svg_content + '</div>' :
                            '<div style="width:40px;height:40px;background:#1565c0;border-radius:4px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-vector-square" style="color:white;"></i></div>';
                        var name = item.export_name || item.design_name || 'Diseño #' + item.id;
                        var details = [
                            item.stitches ? item.stitches.toLocaleString() + ' pts' : '',
                            item.dimensions_label || '',
                            item.file_format || ''
                        ].filter(Boolean).join(' • ');
                        var html =
                            '<div class="list-group-item d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="linkDesignToItemConfirm(' +
                            item.id + ', \'' + escapeHtml(name).replace(/'/g, "\\'") + '\')">' +
                            '<div class="d-flex align-items-center">' +
                            '<div class="mr-3">' + preview + '</div>' +
                            '<div>' +
                            '<strong style="font-size:14px;color:#212529;">' + escapeHtml(name) + '</strong>' +
                            '<span class="badge ml-2" style="background:#c8e6c9;color:#2e7d32;font-size:10px;">Aprobado</span>' +
                            '<br><small style="color:#757575;">' + escapeHtml(details) + '</small>' +
                            '</div>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm" style="background:#1565c0;color:white;" onclick="event.stopPropagation();linkDesignToItemConfirm(' +
                            item.id + ', \'' + escapeHtml(name).replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-link"></i>' +
                            '</button>' +
                            '</div>';
                        container.insertAdjacentHTML('beforeend', html);
                    });
                }

                window.linkDesignToItemConfirm = function(designExportId, designName) {
                    var itemId = document.getElementById('currentItemId').value;
                    var itemName = document.getElementById('currentItemName').value;
                    Swal.fire({
                        title: '¿Vincular diseño?',
                        html: '<p>Se vinculará <strong>' + escapeHtml(designName) +
                            '</strong> al producto <strong>' + escapeHtml(itemName) + '</strong></p>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#1565c0',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-link mr-1"></i> Vincular',
                        cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            linkDesignToItemExecute(itemId, designExportId, designName);
                        }
                    });
                };

                function linkDesignToItemExecute(itemId, designExportId, designName) {
                    Swal.fire({
                        title: 'Vinculando...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });
                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/link-design', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                design_export_id: designExportId
                            })
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({
                                        title: '¡Vinculado!',
                                        text: data.message,
                                        icon: 'success',
                                        timer: 2000,
                                        timerProgressBar: true
                                    })
                                    .then(function() {
                                        $('#selectDesignForItemModal').modal('hide');
                                        window.location.reload();
                                    });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(function() {
                            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                        });
                }

                window.unlinkDesignFromItem = function(orderId, itemId, designExportId, designName) {
                    Swal.fire({
                        title: '¿Desvincular diseño?',
                        html: '<p>Se desvinculará <strong>' + escapeHtml(designName) +
                            '</strong> del producto</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#c62828',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-unlink mr-1"></i> Desvincular',
                        cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Desvinculando...',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: function() {
                                    Swal.showLoading();
                                }
                            });
                            fetch('/admin/orders/' + orderId + '/items/' + itemId + '/unlink-design/' +
                                    designExportId, {
                                        method: 'DELETE',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                                .content,
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Desvinculado',
                                            icon: 'success',
                                            timer: 1500
                                        }).then(function() {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire('Error', data.message, 'error');
                                    }
                                })
                                .catch(function() {
                                    Swal.fire('Error', 'No se pudo conectar', 'error');
                                });
                        }
                    });
                };

                window.showDesignPreview = function(designExportId, itemId) {
                    $('#designPreviewModal').modal('show');
                    document.getElementById('previewDesignName').textContent = 'Cargando...';
                    document.getElementById('previewDesignImage').innerHTML =
                        '<i class="fas fa-spinner fa-spin fa-2x" style="color:#1565c0;"></i>';
                    document.getElementById('previewDesignDetails').innerHTML = '';
                    fetch('/admin/orders/' + orderId + '/items/' + itemId + '/designs', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                var design = data.data.find(function(d) {
                                    return d.id == designExportId;
                                });
                                if (design) {
                                    document.getElementById('previewDesignName').textContent = design.name ||
                                        'Diseño #' + design.id;
                                    var imgHtml = design.svg_content ?
                                        '<div style="max-width:300px;max-height:300px;margin:auto;">' + design
                                        .svg_content + '</div>' :
                                        (design.image_url ?
                                            '<img src="' + design.image_url +
                                            '" style="max-width:100%;max-height:300px;">' :
                                            '<i class="fas fa-vector-square fa-4x" style="color:#1565c0;"></i>');
                                    document.getElementById('previewDesignImage').innerHTML = imgHtml;
                                    var details = '<div class="mt-3">';
                                    if (design.stitches_formatted) details += '<p><strong>Puntadas:</strong> ' +
                                        design.stitches_formatted + '</p>';
                                    if (design.dimensions) details += '<p><strong>Dimensiones:</strong> ' + design
                                        .dimensions + '</p>';
                                    if (design.file_format) details += '<p><strong>Formato:</strong> ' + design
                                        .file_format.toUpperCase() + '</p>';
                                    if (design.application_type) details += '<p><strong>Aplicación:</strong> ' +
                                        design.application_type + '</p>';
                                    details += '</div>';
                                    document.getElementById('previewDesignDetails').innerHTML = details;
                                }
                            }
                        });
                };

                document.getElementById('btnSearchDesignForItem')?.addEventListener('click', filterAndRenderDesignsForItem);
                document.getElementById('searchDesignForItem')?.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filterAndRenderDesignsForItem();
                    }
                });
                document.getElementById('searchDesignForItem')?.addEventListener('input', function() {
                    if (allDesignsForItem.length > 0) {
                        clearTimeout(this.debounce);
                        this.debounce = setTimeout(filterAndRenderDesignsForItem, 300);
                    }
                });

                function escapeHtml(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            })();
        </script>
    @endif
@endpush
