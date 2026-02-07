{{-- Partial: Fila de item para vista desktop --}}
@php
    // Extras viene de la relación HasMany OrderItemExtra
    $extrasCollection = $item->extras ?? collect();
    $extrasSubtotal = $extrasCollection->sum('total_price');

    // Precio base del producto/variante (desde la relación, precio actual de catálogo)
    $variantBasePrice = $item->variant?->price ?? $item->product?->base_price ?? 0;

    // Subtotal = (precio variante * cantidad) + extras
    $baseSubtotal = $item->quantity * $variantBasePrice;
    $itemTotal = $baseSubtotal + $extrasSubtotal;

    $blocksR2 = $item->has_pending_adjustments;
    $blocksR3 = $item->personalization_type === \App\Models\OrderItem::PERSONALIZATION_DESIGN && !$item->design_approved;
    $blocksR4 = $item->hasMeasurementsChangedAfterApproval();
    $blocksR5 = $item->blocksProductionForTechnicalDesigns();
    $hasBlocker = $blocksR2 || $blocksR3 || $blocksR4 || $blocksR5;

    $linkedDesigns = $item->designExports;
    $requiresDesigns = $item->requiresTechnicalDesigns();
    $hasLinkedDesigns = $linkedDesigns->count() > 0;
    $hasPersonalization = !empty($item->embroidery_text) || !empty($item->customization_notes);

    $itemPuntadas = $linkedDesigns->count() > 0
        ? $linkedDesigns->sum('stitches_count')
        : ($item->product?->total_stitches ?? 0);
    $itemEstimado = ($item->product?->embroidery_cost ?? 0) * $item->quantity;

    $variantDisplay = $item->variant?->attributes_display;
    $measurements = is_array($item->measurements) ? $item->measurements : [];
    $hasMeasurements = !empty($measurements) && count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) > 0;
    $measurementCount = $hasMeasurements ? count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) : 0;
@endphp

<tr class="{{ $hasBlocker ? 'table-danger' : '' }} {{ ($requiresDesigns && !$hasLinkedDesigns) ? 'table-danger' : '' }}" data-item-row="{{ $item->id }}">
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
            <span style="background: #e7f3ff; color: #0056b3; padding: 2px 6px; border-radius: 3px; font-size: 14px; margin-left: 6px; font-weight: 600;">
                {{ $variantDisplay }}
            </span>
        @endif
        @if ($item->is_annex)
            <span class="badge badge-warning ml-1" style="font-size: 14px;">ANEXO</span>
        @endif

        {{-- Personalización inline con opción de vincular diseño --}}
        @if ($item->embroidery_text)
            <div class="mt-1 d-flex align-items-center" style="gap: 6px;">
                <span style="background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-size: 15px; display: inline-block;">
                    <i class="fas fa-pen-fancy mr-1"></i>{{ $item->embroidery_text }}
                </span>
                @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                    <button type="button" class="btn btn-sm btn-link p-0"
                        style="font-size: 16px; color: #1565c0;"
                        onclick="openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}', 'text')"
                        title="Vincular diseño para este texto">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                @endif
            </div>
        @endif

        @if ($item->customization_notes)
            <div class="mt-1 d-flex align-items-center" style="gap: 6px;">
                <span style="background: #fff3e0; color: #e65100; padding: 4px 8px; border-radius: 4px; font-size: 15px; display: inline-block;">
                    <i class="fas fa-sticky-note mr-1"></i>{{ Str::limit($item->customization_notes, 50) }}
                </span>
                @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]) && !$item->embroidery_text)
                    <button type="button" class="btn btn-sm btn-link p-0"
                        style="font-size: 16px; color: #1565c0;"
                        onclick="openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}', 'text')"
                        title="Vincular diseño para esta personalización">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                @endif
            </div>
        @endif

        {{-- DISEÑOS TÉCNICOS --}}
        <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 6px;">
            @if ($requiresDesigns || $hasLinkedDesigns || $hasPersonalization)
                @if ($hasLinkedDesigns)
                    <div class="d-inline-flex align-items-center"
                        style="background: #e8f5e9; border-radius: 4px; padding: 4px 8px; border: 1px solid #a5d6a7; cursor: pointer;"
                        data-toggle="collapse" data-target="#designDetails{{ $item->id }}"
                        data-item-designs="{{ $item->id }}">
                        <i class="fas fa-check-circle mr-1" style="color: #2e7d32; font-size: 14px;"></i>
                        <span style="font-size: 14px; color: #1b5e20; font-weight: 600;">
                            {{ $linkedDesigns->count() }} diseño(s)
                        </span>
                        <span style="font-size: 14px; color: #388e3c; margin-left: 4px;">
                            · {{ number_format($itemPuntadas) }} pts
                        </span>
                        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_IN_PRODUCTION]))
                            <span style="font-size: 14px; color: #7b1fa2; margin-left: 4px; font-weight: 600;">
                                · Est. ${{ number_format($itemEstimado, 2) }}
                            </span>
                        @endif
                        <i class="fas fa-chevron-down ml-2" style="font-size: 12px; color: #1565c0;"></i>
                    </div>
                    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                        <button type="button" class="btn btn-sm btn-link p-0 ml-1"
                            style="font-size: 14px; color: #1565c0;"
                            onclick="event.stopPropagation(); openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}', 'manual')"
                            title="Agregar diseño (texto, logo, etc.)">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    @endif
                @elseif($requiresDesigns)
                    <div class="d-inline-flex align-items-center"
                        style="background: #fff3cd; border-radius: 4px; padding: 4px 8px; border: 1px solid #ffc107;"
                        data-item-designs="{{ $item->id }}">
                        <i class="fas fa-exclamation-triangle mr-1" style="color: #856404; font-size: 14px;"></i>
                        <span style="font-size: 14px; color: #856404; font-weight: 500;">Sin diseño</span>
                        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                            <span class="ml-2" style="font-size: 14px; color: #1565c0;">
                                <i class="fas fa-link mr-1"></i>Vincular diseño (texto, logo, etc.)
                            </span>
                        @endif
                    </div>
                    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                        <button type="button" class="btn btn-sm btn-link p-0 ml-1"
                            style="font-size: 14px; color: #1565c0;"
                            onclick="openDesignSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}', 'manual')"
                            title="Agregar diseño (texto, logo, etc.)">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    @endif
                @elseif($hasPersonalization && !$hasLinkedDesigns)
                    <div class="d-inline-flex align-items-center"
                        style="background: #e3f2fd; border-radius: 4px; padding: 4px 8px; border: 1px solid #90caf9;">
                        <i class="fas fa-palette mr-1" style="color: #1565c0; font-size: 14px;"></i>
                        <span style="font-size: 14px; color: #1565c0; font-weight: 500;">Sin diseño asignado</span>
                    </div>
                @endif
            @endif
        </div>

        {{-- Panel colapsable de diseños --}}
        @if ($hasLinkedDesigns || $requiresDesigns)
            <div class="collapse mt-1" id="designDetails{{ $item->id }}">
                @if ($hasLinkedDesigns)
                <div style="background: #fafafa; border-radius: 4px; padding: 6px; border: 1px solid #e0e0e0;">
                    @foreach ($linkedDesigns as $design)
                        @php
                            $pivotNotes = $design->pivot->notes ?? '';
                            $isFromSnapshot = Str::contains($pivotNotes, 'snapshot');
                            $isFromText = Str::contains($pivotNotes, 'texto/personalización');
                            $isManual = Str::contains($pivotNotes, 'manualmente');
                            $isAdicional = Str::contains($pivotNotes, 'adicional');
                        @endphp
                        <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-1 pb-1' : '' }}"
                            style="{{ !$loop->last ? 'border-bottom: 1px solid #e0e0e0;' : '' }}">
                            <div class="d-flex align-items-center">
                                @if ($design->svg_content)
                                    <div style="width: 24px; height: 24px; background: white; border-radius: 3px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-right: 6px; border: 1px solid #e0e0e0;">
                                        {!! $design->svg_content !!}
                                    </div>
                                @else
                                    <div style="width: 24px; height: 24px; background: #1565c0; border-radius: 3px; display: flex; align-items: center; justify-content: center; margin-right: 6px;">
                                        <i class="fas fa-vector-square" style="color: white; font-size: 10px;"></i>
                                    </div>
                                @endif
                                <span style="font-size: 14px; color: #212529; font-weight: 500;">
                                    {{ $design->application_label ?? ($design->export_name ?? 'Diseño') }}
                                    @if ($isFromSnapshot)
                                        <span style="background: #e8f5e9; color: #2e7d32; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">PRODUCTO</span>
                                    @elseif ($isFromText && $isManual)
                                        <span style="background: #fff3e0; color: #e65100; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">TEXTO MANUAL</span>
                                    @elseif ($isAdicional && $isManual)
                                        <span style="background: #fff3e0; color: #e65100; padding: 1px 5px; border-radius: 3px; font-size: 10px; margin-left: 4px; font-weight: 600;">PRODUCTO MANUAL</span>
                                    @endif
                                    <span style="color: #1b5e20; font-weight: 600;">· {{ number_format($design->stitches_count ?? 0) }} pts</span>
                                    @if ($design->width_mm)
                                        <span style="color: #1565c0; font-weight: 600;">· {{ $design->width_mm }}×{{ $design->height_mm }}mm</span>
                                    @endif
                                </span>
                            </div>
                            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-link p-1"
                                        style="color: #f57c00; font-size: 14px;" title="Cambiar diseño"
                                        onclick="changeDesignForItem({{ $order->id }}, {{ $item->id }}, {{ $design->id }}, '{{ addslashes($design->application_label ?? $design->export_name) }}', '{{ addslashes($item->product_name) }}')">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link p-1"
                                        style="color: #c62828; font-size: 14px;" title="Quitar diseño"
                                        onclick="unlinkDesignFromItem({{ $order->id }}, {{ $item->id }}, {{ $design->id }}, '{{ addslashes($design->application_label ?? $design->export_name) }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Estimado técnico --}}
                    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_IN_PRODUCTION]) && $itemPuntadas > 0)
                        @php
                            $precioPorMillar = (float)($item->product?->embroidery_rate_per_thousand ?? 0);
                            $costoCalculado = ($itemPuntadas / 1000) * $precioPorMillar * $item->quantity;
                        @endphp
                        @if($precioPorMillar > 0)
                        <div class="mt-1 pt-1" style="border-top: 1px solid #e0e0e0;">
                            <span style="color: #7b1fa2; font-size: 14px; font-weight: 500;">
                                <i class="fas fa-calculator mr-1"></i>
                                {{ number_format($itemPuntadas) }} pts × ${{ number_format($precioPorMillar, 2) }}/mil = <strong>${{ number_format($costoCalculado, 2) }}</strong>
                            </span>
                        </div>
                        @endif
                    @endif
                </div>
                @endif
            </div>
        @endif

        {{-- MEDIDAS (debajo del collapse de diseños) --}}
        @if ($item->requires_measurements)
            <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 6px;">
                @if ($hasMeasurements)
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm py-1 px-2"
                            style="background: #6f42c1; color: white; font-size: 14px;"
                            data-toggle="modal" data-target="#measurementsModal{{ $item->id }}">
                            <i class="fas fa-ruler-combined mr-1"></i>{{ $measurementCount }} medidas
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
                                    data-toggle="modal" data-target="#measurementsModal{{ $item->id }}">
                                    <i class="fas fa-eye mr-2 text-secondary"></i> Ver medidas
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                        <button type="button" class="btn btn-sm py-1 px-2"
                            style="background: #e65100; color: white; font-size: 14px;"
                            onclick="openItemMeasurementsEdit({{ $item->id }}, '{{ addslashes($item->product_name) }}', null)">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Capturar medidas
                        </button>
                    @else
                        <span class="badge" style="background: #ffccbc; color: #bf360c; font-size: 14px; padding: 4px 8px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Sin medidas
                        </span>
                    @endif
                @endif
            </div>
        @endif

        {{-- Extras colapsables --}}
        <div class="mt-2 d-flex flex-wrap align-items-center" style="gap: 6px;" data-item-extras="{{ $item->id }}">
            @if ($extrasCollection->count() > 0)
                <div class="d-inline-flex align-items-center"
                    style="background: #e3f2fd; border-radius: 4px; padding: 4px 8px; border: 1px solid #90caf9; cursor: pointer;"
                    data-toggle="collapse" data-target="#extrasDetails{{ $item->id }}">
                    <i class="fas fa-box mr-1" style="color: #0277bd; font-size: 14px;"></i>
                    <span style="font-size: 14px; color: #0277bd; font-weight: 600;" data-extras-count="{{ $item->id }}">
                        {{ $extrasCollection->count() }} extra(s)
                    </span>
                    <span style="font-size: 14px; color: #1565c0; margin-left: 4px; font-weight: 600;" data-extras-total="{{ $item->id }}">
                        · +${{ number_format($extrasSubtotal, 2) }}
                    </span>
                    <i class="fas fa-chevron-down ml-2" style="font-size: 12px; color: #0277bd;"></i>
                </div>
            @else
                <div class="d-inline-flex align-items-center"
                    style="background: #f5f5f5; border-radius: 4px; padding: 4px 8px; border: 1px solid #e0e0e0;">
                    <i class="fas fa-box mr-1" style="color: #9e9e9e; font-size: 14px;"></i>
                    <span style="font-size: 14px; color: #9e9e9e; font-weight: 500;">Sin extras</span>
                </div>
            @endif
            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                <button type="button" class="btn btn-sm btn-link p-0 ml-1"
                    style="font-size: 14px; color: #0277bd;"
                    onclick="openExtraSelectorForItem({{ $item->id }}, '{{ addslashes($item->product_name) }}')"
                    title="Agregar extra">
                    <i class="fas fa-plus-circle"></i>
                </button>
            @endif
        </div>
        {{-- Panel colapsable de extras --}}
        @if ($extrasCollection->count() > 0)
            <div class="collapse mt-1" id="extrasDetails{{ $item->id }}">
                <div style="background: #fafafa; border-radius: 4px; padding: 6px; border: 1px solid #e0e0e0;" data-extras-list="{{ $item->id }}">
                    @foreach ($extrasCollection as $extra)
                        <div class="d-flex align-items-center justify-content-between {{ !$loop->last ? 'mb-1 pb-1' : '' }}"
                            style="{{ !$loop->last ? 'border-bottom: 1px solid #e0e0e0;' : '' }}"
                            data-extra-row="{{ $extra->id }}">
                            <div class="d-flex align-items-center">
                                <div style="width: 24px; height: 24px; background: #0277bd; border-radius: 3px; display: flex; align-items: center; justify-content: center; margin-right: 6px;">
                                    <i class="fas fa-box" style="color: white; font-size: 10px;"></i>
                                </div>
                                <span style="font-size: 14px; color: #212529; font-weight: 500;">
                                    {{ $extra->productExtra->name ?? 'Extra' }}
                                    <span style="color: #0277bd; font-weight: 600;"> · ${{ number_format($extra->unit_price, 2) }} c/u</span>
                                </span>
                            </div>
                            @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
                                <div class="d-flex align-items-center" style="gap: 4px;">
                                    {{-- Controles de cantidad --}}
                                    <button type="button" class="btn btn-sm p-0 btn-extra-minus"
                                        style="width: 24px; height: 24px; background: {{ $extra->quantity <= 1 ? '#e0e0e0' : '#f5f5f5' }}; border: 1px solid #e0e0e0; border-radius: 4px; color: {{ $extra->quantity <= 1 ? '#bdbdbd' : '#0277bd' }}; font-size: 12px;"
                                        onclick="updateExtraQuantity({{ $order->id }}, {{ $item->id }}, {{ $extra->id }}, {{ $extra->quantity - 1 }})"
                                        title="Disminuir cantidad"
                                        {{ $extra->quantity <= 1 ? 'disabled' : '' }}>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span style="min-width: 24px; text-align: center; font-size: 14px; font-weight: 600; color: #212529;" data-extra-qty="{{ $extra->id }}">{{ $extra->quantity }}</span>
                                    <button type="button" class="btn btn-sm p-0 btn-extra-plus"
                                        style="width: 24px; height: 24px; background: #e3f2fd; border: 1px solid #90caf9; border-radius: 4px; color: #0277bd; font-size: 12px;"
                                        onclick="updateExtraQuantity({{ $order->id }}, {{ $item->id }}, {{ $extra->id }}, {{ $extra->quantity + 1 }})"
                                        title="Aumentar cantidad">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    {{-- Botón eliminar --}}
                                    <button type="button" class="btn btn-sm btn-link p-1 ml-1"
                                        style="color: #c62828; font-size: 14px;" title="Quitar extra"
                                        onclick="removeExtraFromItem({{ $order->id }}, {{ $item->id }}, {{ $extra->id }}, '{{ addslashes($extra->productExtra->name ?? 'Extra') }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @else
                                <span style="font-size: 14px; color: #6c757d; font-weight: 500;">×{{ $extra->quantity }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </td>
    <td class="text-center align-middle" style="font-size: 16px; font-weight: 600; color: #212529;">{{ $item->quantity }}</td>
    {{-- PRECIO UNITARIO (variante/producto sin extras): Editable en DRAFT/CONFIRMED, bloqueado en IN_PRODUCTION+ --}}
    <td class="text-right align-middle">
        @if (in_array($order->status, [\App\Models\Order::STATUS_DRAFT, \App\Models\Order::STATUS_CONFIRMED]))
            <div class="input-group input-group-sm" style="width: 110px; margin-left: auto;">
                <div class="input-group-prepend">
                    <span class="input-group-text" style="padding: 0 6px; font-size: 13px;">$</span>
                </div>
                <input type="number"
                    class="form-control text-right item-unit-price-input"
                    data-item-id="{{ $item->id }}"
                    data-original-price="{{ $variantBasePrice }}"
                    data-extras-total="{{ $extrasSubtotal }}"
                    value="{{ number_format($variantBasePrice, 2, '.', '') }}"
                    step="0.01"
                    min="0"
                    style="font-size: 14px; font-weight: 600; padding: 4px 8px;">
            </div>
        @else
            <span style="font-size: 15px; font-weight: 600; color: #6c757d;" title="Precio bloqueado en producción">
                <i class="fas fa-lock mr-1" style="font-size: 11px; color: #adb5bd;"></i>${{ number_format($variantBasePrice, 2) }}
            </span>
        @endif
    </td>
    <td class="text-center align-middle">
        @if ($order->status === \App\Models\Order::STATUS_CONFIRMED)
            @if ($blocksR2)
                <span class="badge badge-danger" style="font-size: 14px;" title="Ajuste de precio pendiente">R2</span>
            @elseif($blocksR3)
                <span class="badge badge-danger" style="font-size: 14px;" title="Diseño no aprobado">R3</span>
            @elseif($blocksR4)
                <span class="badge badge-danger" style="font-size: 14px;" title="Medidas modificadas">R4</span>
            @elseif($blocksR5)
                <span class="badge badge-danger" style="font-size: 14px;" title="Sin diseño técnico">R5</span>
            @else
                <span style="color: #28a745; font-size: 16px;"><i class="fas fa-check"></i></span>
            @endif
        @else
            <span style="color: #28a745; font-size: 16px;"><i class="fas fa-check"></i></span>
        @endif
    </td>
    <td class="text-right align-middle">
        <strong style="font-size: 15px; color: #212529;" data-item-subtotal="{{ $item->id }}">${{ number_format($itemTotal, 2) }}</strong>
        <br><span style="color: #0277bd; font-size: 14px; font-weight: 500;{{ $extrasSubtotal <= 0 ? ' display: none;' : '' }}" data-item-extras-subtotal="{{ $item->id }}">+${{ number_format($extrasSubtotal, 2) }}</span>
    </td>
</tr>
