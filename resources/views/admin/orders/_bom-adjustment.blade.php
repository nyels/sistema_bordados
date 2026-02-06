{{-- ================================================================ --}}
{{-- AJUSTE BOM POR MEDIDAS (PRE-PRODUCCIÓN) --}}
{{-- Solo visible en estado CONFIRMED --}}
{{-- Persistencia via AJAX - Producción verá ajustes guardados --}}
{{-- Botones de Guardar/Restaurar por cada producto --}}
{{-- ================================================================ --}}

@php
    use App\Models\Order;

    // Categorías de materiales VARIABLES (ajustables por medidas)
    $categoriasVariables = ['telas', 'pelones', 'avios'];

    // Filtrar items que tienen medidas capturadas Y requieren medidas
    $itemsConMedidas = $order->items->filter(function($item) {
        return $item->requires_measurements && !empty($item->measurements);
    });

    // Preparar datos de BOM por item
    $bomData = [];
    $totalCostoBase = 0;

    foreach ($order->items as $item) {
        $product = $item->product;
        if (!$product || !$product->materials) continue;

        // Cargar ajustes guardados para este item
        $savedAdjustments = $item->bomAdjustments->keyBy('material_variant_id');

        // Obtener diseños asignados al item
        $itemDesignExports = $item->designExports;
        $designsData = [];
        $itemStitchesTotal = 0;
        $itemEmbroideryCostTotal = 0;
        $productEmbroideryCost = (float) ($product->embroidery_cost ?? 0);
        $baseRatePerThousand = (float) ($product->embroidery_rate_per_thousand ?? 0);

        // Si el producto tiene costo de bordado, calcular basado en diseños o en el total del producto
        if ($itemDesignExports->count() > 0) {
            foreach ($itemDesignExports as $designExport) {
                $designStitches = (int) ($designExport->stitches_count ?? 0);
                $designMillar = $designStitches / 1000;

                // Usar rate ajustado del pivot si existe, si no usar el base del producto
                $adjustedRate = $designExport->pivot->rate_per_thousand_adjusted;
                $effectiveRate = $adjustedRate !== null ? (float) $adjustedRate : $baseRatePerThousand;
                $hasRateAdjustment = $adjustedRate !== null && abs($adjustedRate - $baseRatePerThousand) > 0.0001;

                $designCostTotal = $designMillar * $effectiveRate * $item->quantity;

                // Determinar tipo de origen basándose en pivot->notes (igual que _item-row-desktop.blade.php)
                $pivotNotes = $designExport->pivot->notes ?? '';
                $isFromSnapshot = \Str::contains($pivotNotes, 'snapshot');
                $isFromText = \Str::contains($pivotNotes, 'texto/personalización');
                $isManual = \Str::contains($pivotNotes, 'manualmente');
                $isAdicional = \Str::contains($pivotNotes, 'adicional');

                // Determinar badge de origen
                $sourceType = 'producto'; // Default
                $sourceLabel = 'PRODUCTO';
                $sourceBgColor = '#e8f5e9';
                $sourceTextColor = '#2e7d32';

                if ($isFromSnapshot) {
                    $sourceType = 'producto';
                    $sourceLabel = 'PRODUCTO';
                    $sourceBgColor = '#e8f5e9';
                    $sourceTextColor = '#2e7d32';
                } elseif ($isFromText && $isManual) {
                    $sourceType = 'texto_manual';
                    $sourceLabel = 'TEXTO MANUAL';
                    $sourceBgColor = '#fff3e0';
                    $sourceTextColor = '#e65100';
                } elseif ($isAdicional && $isManual) {
                    $sourceType = 'producto_manual';
                    $sourceLabel = 'PRODUCTO MANUAL';
                    $sourceBgColor = '#fff3e0';
                    $sourceTextColor = '#e65100';
                } elseif ($isManual) {
                    // Genérico manual sin especificar tipo
                    $sourceType = 'manual';
                    $sourceLabel = 'MANUAL';
                    $sourceBgColor = '#fff3e0';
                    $sourceTextColor = '#e65100';
                }

                $designsData[] = [
                    'id' => $designExport->id,
                    'name' => $designExport->application_label ?? 'Diseño sin nombre',
                    'file_name' => $designExport->file_name,
                    'stitches' => $designStitches,
                    'millar' => $designMillar,
                    'rate_per_thousand' => $effectiveRate,
                    'base_rate' => $baseRatePerThousand,
                    'has_rate_adjustment' => $hasRateAdjustment,
                    'cost_total' => $designCostTotal,
                    'position' => $designExport->application_type ?? 'Sin ubicación',
                    // Información de origen para badges
                    'source_type' => $sourceType,
                    'source_label' => $sourceLabel,
                    'source_bg_color' => $sourceBgColor,
                    'source_text_color' => $sourceTextColor,
                ];

                $itemStitchesTotal += $designStitches * $item->quantity;
                $itemEmbroideryCostTotal += $designCostTotal;
            }
        } else {
            // Fallback: usar datos del producto si no hay diseños asignados
            $productStitches = $product->total_stitches ?? 0;
            $itemStitchesTotal = $productStitches * $item->quantity;
            $itemEmbroideryCostTotal = $productEmbroideryCost * $item->quantity;
        }

        $itemBom = [
            'item_id' => $item->id,
            'item_name' => $item->product_name,
            'quantity' => $item->quantity,
            'has_measurements' => $item->requires_measurements && !empty($item->measurements),
            'measurements' => $item->measurements ?? [],
            'materials' => [],
            'extras' => [], // Extras del pedido (OrderItemExtra)
            'has_saved_adjustments' => $savedAdjustments->isNotEmpty(),
            // Datos de bordado
            'designs' => $designsData,
            'stitches_total' => $itemStitchesTotal,
            'embroidery_cost_total' => $itemEmbroideryCostTotal,
            'base_rate_per_thousand' => $baseRatePerThousand,
            'has_embroidery_adjustments' => collect($designsData)->contains('has_rate_adjustment', true),
        ];

        // Cargar extras del pedido para este item (con sus materiales si consumen inventario)
        $itemExtras = $item->extras()->with(['productExtra.materials.material.consumptionUnit', 'productExtra.materials.material.baseUnit'])->get();
        $itemExtrasServiceCost = 0; // Costo de servicio (mano de obra del extra)
        $itemExtrasMaterialsCost = 0; // Costo de materiales del extra
        $itemBom['extras_materials'] = []; // Materiales de los extras (BOM de extras)

        foreach ($itemExtras as $orderItemExtra) {
            $productExtra = $orderItemExtra->productExtra;
            if (!$productExtra) continue;

            // Costo de servicio del extra (cost_addition = mano de obra/servicio)
            $serviceCost = (float) $productExtra->cost_addition;
            $serviceCostTotal = $serviceCost * $orderItemExtra->quantity * $item->quantity;
            $itemExtrasServiceCost += $serviceCostTotal;

            $extraData = [
                'id' => $orderItemExtra->id,
                'name' => $productExtra->name,
                'quantity' => $orderItemExtra->quantity,
                'unit_price' => (float) $orderItemExtra->unit_price,
                'cost_addition' => $serviceCost,
                'total_service_cost' => $serviceCostTotal,
                'consumes_inventory' => $productExtra->consumes_inventory,
                'materials' => [],
            ];

            // Si el extra consume inventario, agregar sus materiales al BOM
            if ($productExtra->consumes_inventory && $productExtra->materials->count() > 0) {
                foreach ($productExtra->materials as $materialVariant) {
                    $material = $materialVariant->material;
                    $qtyRequired = (float) $materialVariant->pivot->quantity_required;
                    $avgCost = (float) $materialVariant->average_cost;
                    // Total: qty_extra × qty_item × qty_material
                    $totalQty = $qtyRequired * $orderItemExtra->quantity * $item->quantity;
                    $materialCost = $totalQty * $avgCost;
                    $itemExtrasMaterialsCost += $materialCost;

                    $materialData = [
                        'variant_id' => $materialVariant->id,
                        'name' => $materialVariant->display_name ?? $material?->name ?? 'Material',
                        'category' => $material?->category?->name ?? 'Sin categoría',
                        'unit' => $material?->consumptionUnit?->symbol ?? $material?->baseUnit?->symbol ?? 'u',
                        'quantity_per_extra' => $qtyRequired,
                        'total_quantity' => $totalQty,
                        'average_cost' => $avgCost,
                        'total_cost' => $materialCost,
                        'extra_name' => $productExtra->name,
                    ];

                    $extraData['materials'][] = $materialData;
                    $itemBom['extras_materials'][] = $materialData;
                }
            }

            $itemBom['extras'][] = $extraData;
        }
        $itemBom['extras_service_cost'] = $itemExtrasServiceCost; // Costo de servicio (mano de obra)
        $itemBom['extras_materials_cost'] = $itemExtrasMaterialsCost; // Costo de materiales de extras

        foreach ($product->materials as $materialVariant) {
            $material = $materialVariant->material;
            $category = $material?->category;
            $categorySlug = $category?->slug ?? 'otros';

            $baseQty = (float) $materialVariant->pivot->quantity;
            $avgCost = (float) $materialVariant->average_cost;
            $isVariable = in_array($categorySlug, $categoriasVariables);

            // Verificar si hay ajuste guardado
            $savedAdj = $savedAdjustments->get($materialVariant->id);
            $adjustedQty = $savedAdj ? (float) $savedAdj->adjusted_quantity : $baseQty;
            $hasChange = $savedAdj && $savedAdj->hasChange();

            $itemBom['materials'][] = [
                'variant_id' => $materialVariant->id,
                'name' => $materialVariant->display_name ?? $material?->name ?? 'Material',
                'category' => $category?->name ?? 'Sin categoría',
                'category_slug' => $categorySlug,
                'is_variable' => $isVariable,
                'base_quantity' => $baseQty,
                'adjusted_quantity' => $adjustedQty,
                'has_saved_change' => $hasChange,
                'unit' => $material?->consumptionUnit?->symbol ?? $material?->baseUnit?->symbol ?? 'u',
                'average_cost' => $avgCost,
                'base_cost_per_item' => $baseQty * $avgCost,
            ];

            // Usar cantidad ajustada para el total
            $totalCostoBase += ($adjustedQty * $avgCost * $item->quantity);
        }

        if (!empty($itemBom['materials'])) {
            // Ordenar materiales alfabéticamente por nombre
            usort($itemBom['materials'], fn($a, $b) => strcasecmp($a['name'], $b['name']));
            $bomData[] = $itemBom;
        }
    }

    $hasVariableMaterials = collect($bomData)->flatMap(fn($i) => $i['materials'])->contains('is_variable', true);
    $hasItemsWithMeasurements = $itemsConMedidas->isNotEmpty();
    $hasSavedAdjustments = collect($bomData)->contains('has_saved_adjustments', true);

    // Calcular costo original (sin ajustes)
    $totalCostoOriginal = 0;
    foreach ($bomData as $itemBom) {
        foreach ($itemBom['materials'] as $mat) {
            $totalCostoOriginal += ($mat['base_quantity'] * $mat['average_cost'] * $itemBom['quantity']);
        }
    }

    // Calcular totales de extras del pedido
    $totalExtrasServiceCost = collect($bomData)->sum('extras_service_cost'); // Mano de obra/servicio
    $totalExtrasMaterialsCost = collect($bomData)->sum('extras_materials_cost'); // Materiales de extras
    $totalExtras = $totalExtrasServiceCost + $totalExtrasMaterialsCost; // Total extras
    $hasExtras = collect($bomData)->flatMap(fn($i) => $i['extras'])->isNotEmpty();
    $hasExtrasMaterials = $totalExtrasMaterialsCost > 0;

    // Calcular totales de bordado
    $totalStitches = collect($bomData)->sum('stitches_total');
    $totalEmbroideryCost = collect($bomData)->sum('embroidery_cost_total');
    $hasEmbroidery = $totalStitches > 0 || $totalEmbroideryCost > 0;

    // Permitir edición solo en estados draft y confirmed
    $canEdit = in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]);
@endphp

{{-- Solo mostrar si hay items con BOM --}}
@if(count($bomData) > 0)
<div class="card" id="bomAdjustmentCard" style="border: 2px solid #5c6bc0;">
    <div class="card-header py-2 d-flex justify-content-between align-items-center"
         style="background: #5c6bc0; color: white; cursor: pointer;"
         data-toggle="collapse" data-target="#collapseBomAdjustment"
         aria-expanded="true" aria-controls="collapseBomAdjustment">
        <h5 class="mb-0" style="font-size: 16px;">
            <i class="fas fa-ruler-combined mr-2"></i>
            Ajuste de Materiales (Pre-producción)
            @if($hasItemsWithMeasurements)
                <span class="badge badge-light ml-2" style="font-size: 12px; color: #5c6bc0;">
                    {{ $itemsConMedidas->count() }} item(s) con medidas
                </span>
            @endif
        </h5>
        <div class="d-flex align-items-center">
            @if($hasSavedAdjustments)
                <span class="badge badge-success mr-2" style="font-size: 12px;">
                    <i class="fas fa-check mr-1"></i> Ajustes guardados
                </span>
            @elseif($hasVariableMaterials && $hasItemsWithMeasurements)
                <span class="badge badge-warning mr-2" style="font-size: 12px;">
                    <i class="fas fa-edit mr-1"></i> Ajustable
                </span>
            @else
                <span class="badge badge-secondary mr-2" style="font-size: 12px;">
                    <i class="fas fa-lock mr-1"></i> BOM Fijo
                </span>
            @endif
            <i class="fas fa-chevron-down bom-collapse-icon" style="transition: transform 0.2s;"></i>
        </div>
    </div>

    <div class="collapse show" id="collapseBomAdjustment">
        {{-- Nota informativa --}}
        <div class="px-3 py-2" style="background: #e8eaf6; border-bottom: 1px solid #c5cae9;">
            <div style="font-size: 14px; color: #3949ab;">
                <i class="fas fa-info-circle mr-1"></i>
                @if($hasVariableMaterials && $hasItemsWithMeasurements)
                    <strong>Materiales ajustables</strong> según las medidas del cliente.
                    Guarde los cambios de cada producto individualmente.
                @else
                    Este pedido usa el BOM estándar del producto. No hay materiales variables para ajustar.
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            @foreach($bomData as $itemBom)
                <div class="bom-item-section" data-item-id="{{ $itemBom['item_id'] }}">
                    {{-- Header del item (clickeable para collapse) --}}
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center bom-item-header"
                         style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0; cursor: pointer;"
                         data-toggle="collapse" data-target="#bomItemContent-{{ $itemBom['item_id'] }}"
                         aria-expanded="false" aria-controls="bomItemContent-{{ $itemBom['item_id'] }}">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-chevron-right bom-item-collapse-icon mr-2" style="transition: transform 0.2s; font-size: 12px; color: #6c757d;"></i>
                            <strong style="font-size: 15px; color: #212529;">
                                {{ $itemBom['item_name'] }}
                            </strong>
                            <span class="badge badge-secondary ml-2" style="font-size: 12px;">
                                × {{ $itemBom['quantity'] }} pz
                            </span>
                        </div>
                        <div>
                            <span class="bom-item-status-{{ $itemBom['item_id'] }}">
                                @if($itemBom['has_saved_adjustments'])
                                    <span class="badge badge-success mr-1" style="font-size: 11px;">
                                        <i class="fas fa-database mr-1"></i> Guardado
                                    </span>
                                @endif
                            </span>
                            @if($itemBom['has_measurements'])
                                <span class="badge" style="background: #7b1fa2; color: white; font-size: 12px;">
                                    <i class="fas fa-ruler mr-1"></i> Con medidas
                                </span>
                            @else
                                <span class="badge badge-light" style="font-size: 12px; color: #212529;">
                                    <i class="fas fa-box mr-1"></i> Estándar
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Contenido colapsable del item --}}
                    <div class="collapse" id="bomItemContent-{{ $itemBom['item_id'] }}">
                        {{-- Medidas capturadas (si existen) --}}
                        @if($itemBom['has_measurements'] && !empty($itemBom['measurements']))
                            <div class="px-3 py-2" style="background: #f3e5f5; border-bottom: 1px solid #e1bee7;">
                                <div class="d-flex flex-wrap align-items-center" style="gap: 12px;">
                                    <span style="font-size: 14px; color: #6a1b9a; font-weight: 600;">
                                        <i class="fas fa-tape mr-1"></i> Medidas:
                                    </span>
                                    @foreach($itemBom['measurements'] as $key => $value)
                                        @if(!empty($value) && $value !== '0' && $key !== 'save_to_client')
                                            <span class="badge" style="background: white; color: #6a1b9a; border: 1px solid #ce93d8; font-size: 13px; padding: 4px 8px;">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}: <strong>{{ $value }}cm</strong>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tabla de materiales --}}
                    <table class="table table-sm mb-0" style="font-size: 14px;">
                        <thead style="background: #eeeeee;">
                            <tr>
                                <th style="width: 35%; color: #212529; font-weight: 600; padding: 8px 12px;">Material</th>
                                <th style="width: 15%; color: #212529; font-weight: 600; padding: 8px 12px; text-align: center;">BOM Base</th>
                                <th style="width: 20%; color: #212529; font-weight: 600; padding: 8px 12px; text-align: center;">Ajustado</th>
                                <th style="width: 15%; color: #212529; font-weight: 600; padding: 8px 12px; text-align: right;">Costo Unit.</th>
                                <th style="width: 15%; color: #212529; font-weight: 600; padding: 8px 12px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $itemBomTotal = 0;
                            @endphp
                            @foreach($itemBom['materials'] as $mat)
                            @php
                                // Si el producto tiene medidas, TODOS los materiales son editables
                                $canAdjust = $itemBom['has_measurements'] && $canEdit;
                                $rowBg = $mat['has_saved_change'] ? '#e8f5e9' : ($canAdjust ? '#fff8e1' : '#ffffff');
                                $subtotalBase = $mat['adjusted_quantity'] * $mat['average_cost'] * $itemBom['quantity'];
                                $itemBomTotal += $subtotalBase;
                            @endphp
                            <tr style="background: {{ $rowBg }};"
                                data-variant-id="{{ $mat['variant_id'] }}"
                                data-base-qty="{{ $mat['base_quantity'] }}"
                                data-avg-cost="{{ $mat['average_cost'] }}"
                                data-item-qty="{{ $itemBom['quantity'] }}">
                                <td style="padding: 10px 12px; vertical-align: middle;">
                                    <div>
                                        <strong style="color: #212529;">{{ $mat['name'] }}</strong>
                                        @if($mat['has_saved_change'])
                                            <i class="fas fa-check-circle text-success ml-1 saved-icon-{{ $itemBom['item_id'] }}-{{ $mat['variant_id'] }}" title="Ajuste guardado"></i>
                                        @endif
                                    </div>
                                    <small style="color: #212529;">{{ $mat['category'] }}</small>
                                </td>
                                <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                    <span style="color: #212529; font-size: 14px;">
                                        {{ number_format($mat['base_quantity'], 2) }} {{ $mat['unit'] }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                    @if($canAdjust)
                                        <div class="input-group input-group-sm" style="max-width: 120px; margin: 0 auto;">
                                            <input type="number"
                                                   class="form-control bom-adjust-input text-center {{ $mat['has_saved_change'] ? 'saved' : '' }}"
                                                   value="{{ number_format($mat['adjusted_quantity'], 2, '.', '') }}"
                                                   min="0"
                                                   step="0.01"
                                                   data-variant-id="{{ $mat['variant_id'] }}"
                                                   data-item-id="{{ $itemBom['item_id'] }}"
                                                   data-original-value="{{ number_format($mat['adjusted_quantity'], 2, '.', '') }}"
                                                   style="font-size: 14px; font-weight: 600; border-color: {{ $mat['has_saved_change'] ? '#4caf50' : '#ffc107' }};">
                                            <div class="input-group-append">
                                                <span class="input-group-text" style="font-size: 12px; background: {{ $mat['has_saved_change'] ? '#e8f5e9' : '#fff8e1' }};">{{ $mat['unit'] }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span style="color: #212529; font-size: 14px;">
                                            {{ number_format($mat['adjusted_quantity'], 2) }} {{ $mat['unit'] }}
                                        </span>
                                        @if(!$canEdit)
                                            <span class="badge badge-light ml-1" style="font-size: 10px;">Bloqueado</span>
                                        @else
                                            <span class="badge badge-light ml-1" style="font-size: 10px;">Fijo</span>
                                        @endif
                                    @endif
                                </td>
                                <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                    <span style="color: #212529; font-size: 14px;">
                                        ${{ number_format($mat['average_cost'], 2) }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                    <span class="bom-subtotal" style="color: #212529; font-weight: 600; font-size: 14px;">
                                        ${{ number_format($subtotalBase, 2) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach

                            {{-- Subtotal BOM Producto (siempre visible con clase para actualización) --}}
                            <tr class="subtotal-bom-row">
                                <td colspan="4" style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                    <strong style="color: #1565c0; font-size: 15px;">
                                        <i class="fas fa-box mr-1"></i> Subtotal BOM Producto:
                                    </strong>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                    <strong class="bom-product-subtotal" data-item-id="{{ $itemBom['item_id'] }}" style="color: #1565c0; font-size: 17px;">
                                        ${{ number_format($itemBomTotal, 2) }}
                                    </strong>
                                </td>
                            </tr>

                            {{-- === COSTO DE BORDADO (Puntadas por Diseño) === --}}
                            @if($itemBom['stitches_total'] > 0 || $itemBom['embroidery_cost_total'] > 0 || count($itemBom['designs']) > 0)
                                {{-- Header - Costo de Bordado --}}
                                <tr style="background: #7b1fa2;">
                                    <td colspan="5" style="padding: 6px 12px;">
                                        <strong style="color: white; font-size: 13px;">
                                            <i class="fas fa-pencil-ruler mr-1"></i> COSTO DE BORDADO
                                        </strong>
                                        <span style="color: white; margin-left: 8px; font-size: 14px; font-weight: bold;">(Diseños asignados × puntadas)</span>
                                    </td>
                                </tr>
                                {{-- Sub-header de columnas para diseños --}}
                                <tr style="background: #f3e5f5;">
                                    <th style="padding: 6px 12px; color: #4a148c; font-size: 11px; font-weight: 600;">
                                        <i class="fas fa-file-image mr-1"></i> Nombre Diseño
                                    </th>
                                    <th style="padding: 6px 12px; color: #4a148c; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-tag mr-1"></i> Tipo
                                    </th>
                                    <th style="padding: 6px 12px; color: #4a148c; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-hashtag mr-1"></i> Puntadas | <i class="fas fa-calculator ml-2 mr-1"></i> Millar
                                    </th>
                                    <th style="padding: 6px 12px; color: #4a148c; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-dollar-sign mr-1"></i> $/Millar
                                    </th>
                                    <th style="padding: 6px 12px; color: #4a148c; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-coins mr-1"></i> Total
                                    </th>
                                </tr>
                                @if(count($itemBom['designs']) > 0)
                                    @foreach($itemBom['designs'] as $designIndex => $design)
                                    <tr style="background: #f3e5f5;"
                                        data-design-id="{{ $design['id'] }}"
                                        data-design-stitches="{{ $design['stitches'] }}"
                                        data-design-millar="{{ $design['millar'] }}"
                                        data-item-qty="{{ $itemBom['quantity'] }}"
                                        data-item-id="{{ $itemBom['item_id'] }}">
                                        {{-- Columna 1: Nombre Diseño --}}
                                        <td style="padding: 10px 12px; vertical-align: middle;">
                                            <strong style="color: #6a1b9a; font-size: 16px;">{{ $design['name'] }}</strong>
                                            <div>
                                                <span style="color: #212529; font-size: 14px; font-weight: 600;">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $design['position'] }}
                                                </span>
                                            </div>
                                            <div>
                                                <span style="color: #212529; font-size: 14px; font-weight: 600;">
                                                    <i class="fas fa-file mr-1"></i>{{ $design['file_name'] }}
                                                </span>
                                            </div>
                                        </td>
                                        {{-- Columna 2: Tipo (Badge de origen) --}}
                                        <td style="padding: 10px 12px; vertical-align: middle; text-align: center;">
                                            <span style="background: {{ $design['source_bg_color'] }}; color: {{ $design['source_text_color'] }}; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                                                {{ $design['source_label'] }}
                                            </span>
                                        </td>
                                        {{-- Columna 3: Puntadas + Millar --}}
                                        <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                            <span style="color: #6a1b9a; font-size: 15px; font-weight: 600;">
                                                {{ number_format($design['stitches']) }} pts
                                            </span>
                                            <div style="color: #8e24aa; font-size: 15px; font-weight: 600;">
                                                {{ number_format($design['millar'], 3) }} millar
                                            </div>
                                        </td>
                                        {{-- Columna 4: $/Millar (editable) --}}
                                        <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                            @if($canEdit)
                                                <div class="input-group input-group-sm" style="max-width: 100px; margin-left: auto;">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" style="font-size: 12px; background: {{ $design['has_rate_adjustment'] ? '#e8f5e9' : '#f3e5f5' }}; border-color: {{ $design['has_rate_adjustment'] ? '#4caf50' : '#ce93d8' }}; color: #6a1b9a;">$</span>
                                                    </div>
                                                    <input type="number"
                                                           class="form-control embroidery-rate-input text-right {{ $design['has_rate_adjustment'] ? 'saved' : '' }}"
                                                           value="{{ number_format($design['rate_per_thousand'], 2, '.', '') }}"
                                                           min="0"
                                                           step="0.01"
                                                           data-design-id="{{ $design['id'] }}"
                                                           data-item-id="{{ $itemBom['item_id'] }}"
                                                           data-base-rate="{{ number_format($design['base_rate'], 2, '.', '') }}"
                                                           data-original-value="{{ number_format($design['rate_per_thousand'], 2, '.', '') }}"
                                                           style="font-size: 14px; font-weight: 600; border-color: {{ $design['has_rate_adjustment'] ? '#4caf50' : '#ce93d8' }}; color: #6a1b9a; {{ $design['has_rate_adjustment'] ? 'background-color: #e8f5e9;' : '' }}">
                                                </div>
                                                @if($design['has_rate_adjustment'])
                                                    <small class="embroidery-rate-adjusted-label" style="color: #4caf50; font-size: 10px; text-align: right; margin-top: 2px;">
                                                        <i class="fas fa-check-circle mr-1"></i>Ajustado
                                                    </small>
                                                @endif
                                            @else
                                                <span style="color: #6a1b9a; font-size: 15px;">
                                                    ${{ number_format($design['rate_per_thousand'], 2) }}
                                                </span>
                                                @if($design['has_rate_adjustment'])
                                                    <small style="color: #4caf50; font-size: 10px; display: block;">
                                                        <i class="fas fa-check-circle mr-1"></i>Ajustado
                                                    </small>
                                                @endif
                                            @endif
                                        </td>
                                        {{-- Columna 5: Costo Final --}}
                                        <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                            <span class="embroidery-design-cost" data-design-id="{{ $design['id'] }}" style="color: #6a1b9a; font-weight: 700; font-size: 16px;">
                                                ${{ number_format($design['cost_total'], 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    {{-- Si no hay diseños asignados pero hay costo de bordado del producto --}}
                                    <tr style="background: #f3e5f5;">
                                        <td colspan="5" style="padding: 12px; text-align: center; vertical-align: middle;">
                                            <div style="color: #8e24aa; font-size: 14px;">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                Sin diseños asignados - Costo basado en ficha del producto:
                                                <strong style="color: #6a1b9a;">${{ number_format($itemBom['embroidery_cost_total'], 2) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                {{-- Subtotal Bordado --}}
                                <tr>
                                    <td colspan="4" style="padding: 12px; text-align: right; vertical-align: middle;">
                                        <strong style="color: #4a148c; font-size: 15px;">
                                            <i class="fas fa-pencil-ruler mr-1"></i> Subtotal Bordado:
                                        </strong>
                                    </td>
                                    <td style="padding: 12px; text-align: right; vertical-align: middle;">
                                        <strong class="bom-embroidery-subtotal" data-item-id="{{ $itemBom['item_id'] }}" data-value="{{ $itemBom['embroidery_cost_total'] }}" style="color: #4a148c; font-size: 18px;">
                                            ${{ number_format($itemBom['embroidery_cost_total'], 2) }}
                                        </strong>
                                    </td>
                                </tr>
                                {{-- Botón independiente para guardar ajustes de tarifa de bordado --}}
                                @if($canEdit && count($itemBom['designs']) > 0)
                                <tr class="embroidery-rate-actions-row" data-item-id="{{ $itemBom['item_id'] }}" style="background: #f3e5f5;">
                                    <td colspan="5" style="padding: 8px 12px;">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-save-embroidery-rate"
                                                    data-item-id="{{ $itemBom['item_id'] }}"
                                                    data-item-name="{{ $itemBom['item_name'] }}"
                                                    disabled
                                                    style="background: #7b1fa2; border-color: #7b1fa2; color: white;">
                                                <i class="fas fa-save mr-1"></i> Guardar Tarifa Bordado
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endif

                            {{-- Botones cuando NO hay extras --}}
                            @if(count($itemBom['extras_materials']) == 0 && !$hasExtras && $canEdit && $itemBom['has_measurements'])
                            <tr style="background: #fafafa;">
                                <td colspan="5" style="padding: 10px 12px;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm btn-restore-item"
                                                data-item-id="{{ $itemBom['item_id'] }}"
                                                data-item-name="{{ $itemBom['item_name'] }}"
                                                {{ !$itemBom['has_saved_adjustments'] ? 'disabled' : '' }}>
                                            <i class="fas fa-undo mr-1"></i> Restaurar BOM Original
                                        </button>
                                        <button type="button"
                                                class="btn btn-success btn-sm btn-save-item"
                                                data-item-id="{{ $itemBom['item_id'] }}"
                                                data-item-name="{{ $itemBom['item_name'] }}"
                                                disabled>
                                            <i class="fas fa-save mr-1"></i> Guardar Ajustes
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endif

                            {{-- === MATERIALES DE EXTRAS (BOM de extras que consumen inventario) === --}}
                            @if(count($itemBom['extras_materials']) > 0)
                                {{-- Header - Materiales de Extras --}}
                                <tr style="background: #ff9800;">
                                    <td colspan="5" style="padding: 6px 12px;">
                                        <strong style="color: white; font-size: 13px;">
                                            <i class="fas fa-boxes mr-1"></i> MATERIALES DE EXTRAS
                                        </strong>
                                        <span style="color: white; margin-left: 8px; font-size: 14px; font-weight: bold;">(Inventario consumido por extras)</span>
                                    </td>
                                </tr>
                                {{-- Sub-header de columnas para materiales de extras --}}
                                <tr style="background: #fff3e0;">
                                    <th style="padding: 6px 12px; color: #e65100; font-size: 11px; font-weight: 600;">
                                        <i class="fas fa-box mr-1"></i> Material
                                    </th>
                                    <th style="padding: 6px 12px; color: #e65100; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-ruler mr-1"></i> BOM Base
                                    </th>
                                    <th style="padding: 6px 12px; color: #e65100; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-calculator mr-1"></i> Ajustado
                                    </th>
                                    <th style="padding: 6px 12px; color: #e65100; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-dollar-sign mr-1"></i> Costo Unit.
                                    </th>
                                    <th style="padding: 6px 12px; color: #e65100; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-coins mr-1"></i> Total
                                    </th>
                                </tr>
                                @foreach($itemBom['extras_materials'] as $extMat)
                                @php
                                    $canAdjustExtra = $itemBom['has_measurements'] && $canEdit;
                                @endphp
                                <tr style="background: #fff8e1;"
                                    data-variant-id="{{ $extMat['variant_id'] }}"
                                    data-base-qty="{{ $extMat['quantity_per_extra'] }}"
                                    data-avg-cost="{{ $extMat['average_cost'] }}"
                                    data-item-qty="{{ $itemBom['quantity'] }}"
                                    data-is-extra="1">
                                    <td style="padding: 10px 12px; vertical-align: middle;">
                                        <div>
                                            <strong style="color: #e65100; font-size: 16px;">{{ $extMat['name'] }}</strong>
                                            <span class="badge ml-1" style="background: #ff9800; color: white; font-size: 10px;">Extra</span>
                                        </div>
                                        <small style="color: #795548; font-size: 13px;">
                                            <i class="fas fa-tag mr-1"></i>{{ $extMat['extra_name'] }} · {{ $extMat['category'] }}
                                        </small>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                        <span style="color: #e65100; font-size: 16px;">
                                            {{ number_format($extMat['quantity_per_extra'], 2) }} {{ $extMat['unit'] }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                        @if($canAdjustExtra)
                                            <div class="input-group input-group-sm" style="max-width: 120px; margin: 0 auto;">
                                                <input type="number"
                                                       class="form-control bom-adjust-input bom-extra-input text-center"
                                                       value="{{ number_format($extMat['total_quantity'], 2, '.', '') }}"
                                                       min="0"
                                                       step="0.01"
                                                       data-variant-id="{{ $extMat['variant_id'] }}"
                                                       data-item-id="{{ $itemBom['item_id'] }}"
                                                       data-original-value="{{ number_format($extMat['total_quantity'], 2, '.', '') }}"
                                                       data-is-extra="1"
                                                       style="font-size: 15px; font-weight: 600; border-color: #ff9800;">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="font-size: 13px; background: #fff3e0;">{{ $extMat['unit'] }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span style="color: #e65100; font-size: 16px; font-weight: 600;">
                                                {{ number_format($extMat['total_quantity'], 2) }} {{ $extMat['unit'] }}
                                            </span>
                                            @if(!$canEdit)
                                                <span class="badge badge-light ml-1" style="font-size: 10px;">Bloqueado</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <span style="color: #e65100; font-size: 16px;">
                                            ${{ number_format($extMat['average_cost'], 2) }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <span class="bom-subtotal bom-extra-subtotal" style="color: #e65100; font-weight: 600; font-size: 16px;">
                                            ${{ number_format($extMat['total_cost'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                {{-- Subtotal Materiales de Extras --}}
                                <tr class="subtotal-extras-row" style="border-top: 1px solid #e0e0e0;">
                                    <td colspan="4" style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <strong style="color: #e65100; font-size: 15px;">
                                            <i class="fas fa-boxes mr-1"></i> Subtotal Materiales Extras:
                                        </strong>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <strong class="bom-extras-subtotal" data-item-id="{{ $itemBom['item_id'] }}" style="color: #e65100; font-size: 17px;">
                                            ${{ number_format($itemBom['extras_materials_cost'], 2) }}
                                        </strong>
                                    </td>
                                </tr>
                                {{-- Botones de acción POR PRODUCTO (después de materiales extras) --}}
                                @if($canEdit && $itemBom['has_measurements'])
                                <tr style="background: #fafafa;">
                                    <td colspan="5" style="padding: 10px 12px;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm btn-restore-item"
                                                    data-item-id="{{ $itemBom['item_id'] }}"
                                                    data-item-name="{{ $itemBom['item_name'] }}"
                                                    {{ !$itemBom['has_saved_adjustments'] ? 'disabled' : '' }}>
                                                <i class="fas fa-undo mr-1"></i> Restaurar BOM Original
                                            </button>
                                            <button type="button"
                                                    class="btn btn-success btn-sm btn-save-item"
                                                    data-item-id="{{ $itemBom['item_id'] }}"
                                                    data-item-name="{{ $itemBom['item_name'] }}"
                                                    disabled>
                                                <i class="fas fa-save mr-1"></i> Guardar Ajustes
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endif

                            {{-- === SERVICIOS EXTRAS (costo de mano de obra/servicio) === --}}
                            @if(count($itemBom['extras']) > 0)
                                {{-- Header - Servicios --}}
                                <tr style="background: #0288d1;">
                                    <td colspan="5" style="padding: 6px 12px;">
                                        <strong style="color: white; font-size: 13px;">
                                            <i class="fas fa-concierge-bell mr-1"></i> SERVICIOS EXTRAS
                                        </strong>
                                        <span style="color: white; margin-left: 8px; font-size: 14px; font-weight: bold;">(Mano de obra / Servicios adicionales)</span>
                                    </td>
                                </tr>
                                {{-- Sub-header de columnas para servicios --}}
                                <tr style="background: #e1f5fe;">
                                    <th style="padding: 6px 12px; color: #01579b; font-size: 11px; font-weight: 600;">
                                        <i class="fas fa-concierge-bell mr-1"></i> Servicio
                                    </th>
                                    <th style="padding: 6px 12px; color: #01579b; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-cubes mr-1"></i> Cantidad
                                    </th>
                                    <th style="padding: 6px 12px; color: #01579b; font-size: 11px; font-weight: 600; text-align: center;">
                                        <i class="fas fa-boxes mr-1"></i> Consumo
                                    </th>
                                    <th style="padding: 6px 12px; color: #01579b; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-dollar-sign mr-1"></i> Precio Unit.
                                    </th>
                                    <th style="padding: 6px 12px; color: #01579b; font-size: 11px; font-weight: 600; text-align: right;">
                                        <i class="fas fa-coins mr-1"></i> Total
                                    </th>
                                </tr>
                                @foreach($itemBom['extras'] as $extra)
                                @php
                                    // Calcular cantidad total de piezas (cantidad extra × cantidad item)
                                    $totalPiezas = $extra['quantity'] * $itemBom['quantity'];
                                    // Si el extra consume inventario, mostrar consumo de materiales, sino "-"
                                    $hasConsumption = $extra['consumes_inventory'] && count($extra['materials']) > 0;
                                @endphp
                                <tr style="background: #ffffff;">
                                    <td style="padding: 10px 12px; vertical-align: middle;">
                                        <div>
                                            <strong style="color: #212529; font-size: 15px;">{{ $extra['name'] }}</strong>
                                            <span class="badge ml-1" style="background: #0288d1; color: white; font-size: 10px;">Servicio</span>
                                            @if($extra['consumes_inventory'])
                                                <span class="badge badge-warning ml-1" style="font-size: 10px;" title="También consume inventario">
                                                    <i class="fas fa-boxes"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 600;">
                                            x{{ $totalPiezas }} pz
                                        </span>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center; vertical-align: middle;">
                                        @if($hasConsumption)
                                            @foreach($extra['materials'] as $mat)
                                                <span style="color: #212529; font-size: 15px;">
                                                    {{ number_format($mat['total_quantity'], 2) }} {{ $mat['unit'] }}
                                                </span>
                                                @if(!$loop->last)<br>@endif
                                            @endforeach
                                        @else
                                            <span style="color: #6c757d; font-size: 15px;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <span style="color: #212529; font-size: 15px;">
                                            ${{ number_format($extra['cost_addition'], 2) }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <span style="color: #212529; font-weight: 600; font-size: 15px;">
                                            ${{ number_format($extra['total_service_cost'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                {{-- Subtotal Servicios --}}
                                <tr style="border-top: 1px solid #e0e0e0;">
                                    <td colspan="4" style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <strong style="color: #01579b; font-size: 15px;">
                                            <i class="fas fa-concierge-bell mr-1"></i> Subtotal Servicios:
                                        </strong>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; vertical-align: middle;">
                                        <strong class="bom-services-subtotal" data-item-id="{{ $itemBom['item_id'] }}" data-value="{{ $itemBom['extras_service_cost'] }}" style="color: #01579b; font-size: 17px;">
                                            ${{ number_format($itemBom['extras_service_cost'], 2) }}
                                        </strong>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            @php
                                // Total incluye: BOM + Bordado + Mat.Extras + Servicios
                                $totalItemCosto = $itemBomTotal + $itemBom['embroidery_cost_total'] + $itemBom['extras_materials_cost'] + $itemBom['extras_service_cost'];
                                $hasAnyExtras = count($itemBom['extras']) > 0 || count($itemBom['extras_materials']) > 0;
                                $hasEmbroideryItem = $itemBom['embroidery_cost_total'] > 0;
                            @endphp
                            {{-- TOTAL COSTO PRODUCCIÓN --}}
                            <tr style="background: #1b5e20;">
                                <td colspan="4" style="padding: 14px; text-align: right; vertical-align: middle;">
                                    <strong style="color: white; font-size: 16px;">
                                        <i class="fas fa-calculator mr-1"></i> TOTAL COSTO PRODUCCIÓN:
                                    </strong>
                                    @if($hasAnyExtras || $hasEmbroideryItem)
                                    <div style="font-size: 14px; color: #c8e6c9; margin-top: 8px; font-weight: 600;">
                                        <span>BOM ${{ number_format($itemBomTotal, 2) }}</span>
                                        @if($hasEmbroideryItem)
                                            <span style="margin: 0 6px;">+</span>
                                            <span>Bordado ${{ number_format($itemBom['embroidery_cost_total'], 2) }}</span>
                                        @endif
                                        @if($itemBom['extras_materials_cost'] > 0)
                                            <span style="margin: 0 6px;">+</span>
                                            <span>Mat.Extras ${{ number_format($itemBom['extras_materials_cost'], 2) }}</span>
                                        @endif
                                        @if($itemBom['extras_service_cost'] > 0)
                                            <span style="margin: 0 6px;">+</span>
                                            <span>Servicios ${{ number_format($itemBom['extras_service_cost'], 2) }}</span>
                                        @endif
                                    </div>
                                    @endif
                                </td>
                                <td style="padding: 14px; text-align: right; vertical-align: middle;">
                                    <strong class="bom-item-total" style="color: #69f0ae; font-size: 24px;" data-item-id="{{ $itemBom['item_id'] }}">
                                        ${{ number_format($totalItemCosto, 2) }}
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>{{-- Fin collapse bomItemContent --}}
                </div>
            @endforeach

            {{-- Resumen de impacto --}}
            <div class="px-3 py-3" style="background: #263238; color: white;">
                @php
                    // Total producción = BOM + Bordado + Mat.Extras + Servicios
                    $totalProduccion = $totalCostoBase + $totalEmbroideryCost + $totalExtrasMaterialsCost + $totalExtrasServiceCost;
                @endphp
                <div class="row align-items-center">
                    {{-- BOM Productos --}}
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <div style="font-size: 11px; color: #90caf9; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-box mr-1"></i> BOM Productos
                        </div>
                        <div id="bomCostoAjustado" style="font-size: 16px; font-weight: 700; color: #90caf9;">
                            ${{ number_format($totalCostoBase, 2) }}
                        </div>
                    </div>
                    @if($hasEmbroidery)
                    {{-- Costo de Bordado --}}
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <div style="font-size: 11px; color: #ce93d8; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-pencil-ruler mr-1"></i> Bordado
                        </div>
                        <div id="bomBordadoTotal" style="font-size: 16px; font-weight: 700; color: #ce93d8;">
                            ${{ number_format($totalEmbroideryCost, 2) }}
                        </div>
                        <div style="font-size: 10px; color: #b39ddb;">
                            {{ number_format($totalStitches) }} pts
                        </div>
                    </div>
                    @endif
                    @if($hasExtrasMaterials)
                    {{-- Materiales Extras --}}
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <div style="font-size: 11px; color: #ffb74d; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-boxes mr-1"></i> Mat. Extras
                        </div>
                        <div id="bomExtrasAjustado" style="font-size: 16px; font-weight: 700; color: #ffb74d;">
                            ${{ number_format($totalExtrasMaterialsCost, 2) }}
                        </div>
                    </div>
                    @endif
                    @if($hasExtras)
                    {{-- Servicios Extras --}}
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <div style="font-size: 11px; color: #4fc3f7; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-concierge-bell mr-1"></i> Servicios
                        </div>
                        <div style="font-size: 16px; font-weight: 700; color: #4fc3f7;">
                            ${{ number_format($totalExtrasServiceCost, 2) }}
                        </div>
                    </div>
                    @endif
                    {{-- TOTAL PRODUCCIÓN --}}
                    <div class="col-6 col-md-2 mb-2 mb-md-0">
                        <div style="font-size: 11px; color: #69f0ae; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-industry mr-1"></i> Total Producción
                        </div>
                        <div id="bomTotalProduccion" style="font-size: 20px; font-weight: 700; color: #69f0ae;">
                            ${{ number_format($totalProduccion, 2) }}
                        </div>
                    </div>
                    {{-- Diferencia BOM --}}
                    <div class="col-12 col-md-2 mb-2 mb-md-0 text-md-right">
                        <div style="font-size: 11px; color: #eceff1; text-transform: uppercase; letter-spacing: 0.5px;">
                            Diferencia BOM
                        </div>
                        @php
                            $diferencia = $totalCostoBase - $totalCostoOriginal;
                            $signo = $diferencia >= 0 ? '+' : '';
                            $pct = $totalCostoOriginal > 0 ? (($diferencia / $totalCostoOriginal) * 100) : 0;
                        @endphp
                        <div id="bomDiferencia" style="font-size: 16px; font-weight: 700; color: {{ $diferencia > 0.01 ? '#ff9800' : ($diferencia < -0.01 ? '#4caf50' : '#eceff1') }};">
                            {{ $signo }}${{ number_format(abs($diferencia), 2) }}
                            <span id="bomDiferenciaPct" style="font-size: 11px; color: #eceff1;">({{ $signo }}{{ number_format($pct, 1) }}%)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mensaje si no puede editar --}}
            @if(!$canEdit)
            <div class="px-3 py-2" style="background: #fff3e0; border-top: 1px solid #ffe0b2;">
                <div class="d-flex align-items-center" style="font-size: 14px; color: #e65100;">
                    <i class="fas fa-lock mr-2"></i>
                    <div>
                        Los ajustes de BOM no pueden modificarse en el estado actual del pedido.
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Estilos --}}
<style>
    [aria-expanded="true"] .bom-collapse-icon {
        transform: rotate(180deg);
    }
    .bom-adjust-input:focus {
        border-color: #f57c00 !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
    }
    .bom-adjust-input.changed {
        background-color: #fff3e0 !important;
        border-color: #f57c00 !important;
    }
    .bom-adjust-input.saved {
        background-color: #e8f5e9 !important;
        border-color: #4caf50 !important;
    }
    .bom-extra-input:focus {
        border-color: #ff9800 !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.25) !important;
    }
    .bom-extra-input.changed {
        background-color: #fff3e0 !important;
        border-color: #ff9800 !important;
    }
    .bom-item-section + .bom-item-section {
        border-top: 2px solid #5c6bc0;
    }
    /* Collapse de items */
    .bom-item-header {
        user-select: none;
    }
    .bom-item-header:hover {
        background: #eeeeee !important;
    }
    .bom-item-header[aria-expanded="true"] .bom-item-collapse-icon {
        transform: rotate(90deg);
    }
    /* Estilos para input de precio por millar de bordado */
    .embroidery-rate-input {
        background-color: #f3e5f5 !important;
    }
    .embroidery-rate-input:focus {
        border-color: #7b1fa2 !important;
        box-shadow: 0 0 0 0.2rem rgba(123, 31, 162, 0.25) !important;
        background-color: #fff !important;
    }
    .embroidery-rate-input.changed {
        background-color: #fff3e0 !important;
        border-color: #ff9800 !important;
    }
    .embroidery-rate-input.saved {
        background-color: #e8f5e9 !important;
        border-color: #4caf50 !important;
    }
    /* Botón guardar tarifa bordado */
    .btn-save-embroidery-rate {
        transition: all 0.2s ease;
    }
    .btn-save-embroidery-rate:disabled {
        background: #ccc !important;
        border-color: #ccc !important;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .btn-save-embroidery-rate:not(:disabled):hover {
        background: #6a1b9a !important;
        border-color: #6a1b9a !important;
    }
</style>

{{-- JavaScript para cálculo y persistencia AJAX por producto --}}
<script>
(function() {
    'use strict';

    var orderId = {{ $order->id }};
    var costoOriginalBom = {{ $totalCostoOriginal }};
    var costoOriginalExtras = {{ $totalExtrasMaterialsCost }};
    var costoOriginalTotal = costoOriginalBom + costoOriginalExtras;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.addEventListener('DOMContentLoaded', function() {
        var inputs = document.querySelectorAll('.bom-adjust-input');

        // Escuchar cambios en inputs de BOM
        inputs.forEach(function(input) {
            var baseQty = parseFloat(input.closest('tr').dataset.baseQty);
            var itemId = input.dataset.itemId;

            input.addEventListener('input', function() {
                recalcularTotales();
                checkForChangesInItem(itemId);

                // Marcar como cambiado si difiere del original guardado
                var currentVal = parseFloat(this.value) || 0;
                var originalVal = parseFloat(this.dataset.originalValue) || baseQty;
                if (Math.abs(currentVal - originalVal) > 0.001) {
                    this.classList.add('changed');
                    this.classList.remove('saved');
                } else {
                    this.classList.remove('changed');
                }
            });

            input.addEventListener('change', function() {
                // Validar mínimo 0
                if (parseFloat(this.value) < 0 || isNaN(parseFloat(this.value))) {
                    this.value = '0.00';
                }
                recalcularTotales();
                checkForChangesInItem(itemId);
            });
        });

        // Escuchar cambios en inputs de precio por millar de bordado
        var embroideryInputs = document.querySelectorAll('.embroidery-rate-input');
        embroideryInputs.forEach(function(input) {
            var itemId = input.dataset.itemId;
            var designId = input.dataset.designId;

            input.addEventListener('input', function() {
                recalcularCostoBordado(itemId);
                recalcularTotales();
                checkForChangesInItem(itemId);
                checkForEmbroideryRateChanges(itemId); // Verificar cambios independientes para botón de tarifa

                // Marcar como cambiado si difiere del original
                var currentVal = parseFloat(this.value) || 0;
                var originalVal = parseFloat(this.dataset.originalValue) || 0;
                if (Math.abs(currentVal - originalVal) > 0.001) {
                    this.classList.add('changed');
                } else {
                    this.classList.remove('changed');
                }
            });

            input.addEventListener('change', function() {
                // Validar mínimo 0
                if (parseFloat(this.value) < 0 || isNaN(parseFloat(this.value))) {
                    this.value = '0.00';
                }
                recalcularCostoBordado(itemId);
                recalcularTotales();
                checkForChangesInItem(itemId);
                checkForEmbroideryRateChanges(itemId); // Verificar cambios independientes para botón de tarifa
            });
        });

        // Botones guardar tarifa de bordado (independiente)
        document.querySelectorAll('.btn-save-embroidery-rate').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var itemId = this.dataset.itemId;
                var itemName = this.dataset.itemName;
                saveEmbroideryRateWithUI(itemId, itemName, this);
            });
        });

        // Botones guardar por producto
        document.querySelectorAll('.btn-save-item').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var itemId = this.dataset.itemId;
                var itemName = this.dataset.itemName;
                saveBomForItemWithUI(itemId, itemName, this);
            });
        });

        // Botones restaurar por producto
        document.querySelectorAll('.btn-restore-item').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var itemId = this.dataset.itemId;
                var itemName = this.dataset.itemName;
                var btnEl = this;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Restaurar BOM?',
                        html: 'Se eliminarán los ajustes de <strong>' + itemName + '</strong> y se volverá al BOM base.',
                        icon: 'warning',
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, restaurar',
                        cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            restoreBomForItemWithUI(itemId, itemName, btnEl);
                        }
                    });
                } else {
                    if (confirm('¿Restaurar BOM de ' + itemName + '?')) {
                        restoreBomForItemWithUI(itemId, itemName, btnEl);
                    }
                }
            });
        });
    });

    // Verificar cambios solo en un item específico
    function checkForChangesInItem(itemId) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        var hasChanges = false;

        // Verificar cambios en inputs de BOM
        var bomInputs = section.querySelectorAll('.bom-adjust-input');
        bomInputs.forEach(function(input) {
            var currentVal = parseFloat(input.value) || 0;
            var originalVal = parseFloat(input.dataset.originalValue) || 0;
            if (Math.abs(currentVal - originalVal) > 0.001) {
                hasChanges = true;
            }
        });

        // Verificar cambios en inputs de precio de bordado
        var embroideryInputs = section.querySelectorAll('.embroidery-rate-input');
        embroideryInputs.forEach(function(input) {
            var currentVal = parseFloat(input.value) || 0;
            var originalVal = parseFloat(input.dataset.originalValue) || 0;
            if (Math.abs(currentVal - originalVal) > 0.001) {
                hasChanges = true;
            }
        });

        // Habilitar/deshabilitar botón guardar de este item
        var btnSave = section.querySelector('.btn-save-item');
        if (btnSave) {
            btnSave.disabled = !hasChanges;
        }
    }

    // Verificar cambios SOLO en tarifas de bordado (para botón independiente)
    function checkForEmbroideryRateChanges(itemId) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        var hasEmbroideryChanges = false;

        // Verificar cambios solo en inputs de precio de bordado
        var embroideryInputs = section.querySelectorAll('.embroidery-rate-input');
        embroideryInputs.forEach(function(input) {
            var currentVal = parseFloat(input.value) || 0;
            var originalVal = parseFloat(input.dataset.originalValue) || 0;
            if (Math.abs(currentVal - originalVal) > 0.001) {
                hasEmbroideryChanges = true;
            }
        });

        // Habilitar/deshabilitar botón independiente de guardar tarifa de bordado
        var btnSaveEmbroidery = section.querySelector('.btn-save-embroidery-rate');
        if (btnSaveEmbroidery) {
            btnSaveEmbroidery.disabled = !hasEmbroideryChanges;
        }
    }

    // Guardar tarifa de bordado de un item con feedback UI (INDEPENDIENTE)
    function saveEmbroideryRateWithUI(itemId, itemName, btnEl) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        // Recopilar solo ajustes de tarifa de bordado
        var embroideryRateAdjustments = [];
        var designRows = section.querySelectorAll('tr[data-design-id]');

        designRows.forEach(function(row) {
            var rateInput = row.querySelector('.embroidery-rate-input');
            if (rateInput) {
                var designExportId = parseInt(row.dataset.designId);
                var rate = parseFloat(rateInput.value) || 0;
                var baseRate = parseFloat(rateInput.dataset.baseRate) || 0;

                embroideryRateAdjustments.push({
                    design_export_id: designExportId,
                    rate_per_thousand: rate,
                    base_rate: baseRate
                });
            }
        });

        if (embroideryRateAdjustments.length === 0) return;

        // Mostrar loading
        btnEl.disabled = true;
        var originalHtml = btnEl.innerHTML;
        btnEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';

        fetch('/admin/orders/' + orderId + '/items/' + itemId + '/embroidery-rate-adjustments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                adjustments: embroideryRateAdjustments
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Actualizar valores originales para reflejar el guardado
                var embroideryInputs = section.querySelectorAll('.embroidery-rate-input');
                embroideryInputs.forEach(function(input) {
                    input.dataset.originalValue = input.value;
                    var baseRate = parseFloat(input.dataset.baseRate) || 0;
                    var currentVal = parseFloat(input.value) || 0;

                    // Actualizar indicador visual de ajuste
                    var row = input.closest('tr');
                    var adjustedLabel = row.querySelector('.embroidery-rate-adjusted-label');

                    if (Math.abs(currentVal - baseRate) > 0.0001) {
                        input.classList.add('saved');
                        input.classList.remove('changed');
                        input.style.borderColor = '#28a745';
                        input.style.backgroundColor = '#e8f5e9';
                        // Mostrar etiqueta "Ajustado" si no existe
                        if (!adjustedLabel) {
                            var newLabel = document.createElement('small');
                            newLabel.className = 'embroidery-rate-adjusted-label';
                            newLabel.style.cssText = 'color: #4caf50; font-size: 10px; text-align: right; margin-top: 2px; display: block;';
                            newLabel.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Ajustado';
                            input.closest('.input-group').after(newLabel);
                        } else {
                            adjustedLabel.style.display = 'block';
                        }
                    } else {
                        input.classList.remove('saved', 'changed');
                        input.style.borderColor = '#ce93d8';
                        input.style.backgroundColor = '';
                        if (adjustedLabel) {
                            adjustedLabel.style.display = 'none';
                        }
                    }
                });

                // Deshabilitar botón ya que no hay cambios pendientes
                btnEl.disabled = true;
                btnEl.innerHTML = originalHtml;

                // Notificación de éxito
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Tarifa de bordado de "' + itemName + '" actualizada.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    alert('Tarifa de bordado guardada correctamente');
                }
            } else {
                throw new Error(data.message || 'Error al guardar');
            }
        })
        .catch(function(error) {
            console.error('Error guardando tarifa de bordado:', error);
            btnEl.disabled = false;
            btnEl.innerHTML = originalHtml;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar la tarifa de bordado: ' + error.message
                });
            } else {
                alert('Error: ' + error.message);
            }
        });
    }

    // Guardar BOM de un item con feedback UI
    function saveBomForItemWithUI(itemId, itemName, btnEl) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        // Recopilar ajustes de BOM
        var adjustments = [];
        var rows = section.querySelectorAll('tbody tr[data-variant-id]');

        rows.forEach(function(row) {
            var input = row.querySelector('.bom-adjust-input');
            if (input) {
                var baseQty = parseFloat(row.dataset.baseQty) || 0;
                var adjustedQty = parseFloat(input.value) || 0;
                var avgCost = parseFloat(row.dataset.avgCost) || 0;

                adjustments.push({
                    material_variant_id: parseInt(row.dataset.variantId),
                    base_quantity: baseQty,
                    adjusted_quantity: adjustedQty,
                    unit_cost: avgCost
                });
            }
        });

        // Recopilar ajustes de tarifa de bordado
        var embroideryRateAdjustments = [];
        var designRows = section.querySelectorAll('tr[data-design-id]');

        designRows.forEach(function(row) {
            var rateInput = row.querySelector('.embroidery-rate-input');
            if (rateInput) {
                var designExportId = parseInt(row.dataset.designId);
                var rate = parseFloat(rateInput.value) || 0;
                var baseRate = parseFloat(rateInput.dataset.baseRate) || 0;

                embroideryRateAdjustments.push({
                    design_export_id: designExportId,
                    rate_per_thousand: rate,
                    base_rate: baseRate
                });
            }
        });

        // Verificar si hay algo que guardar
        var hasBomAdjustments = adjustments.length > 0;
        var hasEmbroideryAdjustments = embroideryRateAdjustments.length > 0;

        if (!hasBomAdjustments && !hasEmbroideryAdjustments) return;

        // Mostrar loading
        btnEl.disabled = true;
        btnEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';

        // Ejecutar requests en paralelo
        var promises = [];

        if (hasBomAdjustments) {
            promises.push(
                fetch('/admin/orders/' + orderId + '/items/' + itemId + '/bom-adjustments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ adjustments: adjustments })
                }).then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(data) {
                            throw new Error(data.message || 'Error al guardar BOM');
                        });
                    }
                    return response.json();
                })
            );
        }

        if (hasEmbroideryAdjustments) {
            promises.push(
                fetch('/admin/orders/' + orderId + '/items/' + itemId + '/embroidery-rate-adjustments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ adjustments: embroideryRateAdjustments })
                }).then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(data) {
                            throw new Error(data.message || 'Error al guardar tarifas de bordado');
                        });
                    }
                    return response.json();
                })
            );
        }

        Promise.all(promises)
        .then(function() {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: itemName + ': ajustes guardados'
                });
            }

            // Actualizar UI del item
            updateItemUIAfterSave(itemId);
        })
        .catch(function(error) {
            console.error('Error saving adjustments:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar: ' + error.message
                });
            } else {
                alert('Error al guardar: ' + error.message);
            }
        })
        .finally(function() {
            btnEl.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar';
            checkForChangesInItem(itemId);
        });
    }

    // Restaurar BOM de un item con feedback UI
    function restoreBomForItemWithUI(itemId, itemName, btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Restaurando...';

        fetch('/admin/orders/' + orderId + '/items/' + itemId + '/bom-adjustments', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                return response.json().then(function(data) {
                    throw new Error(data.message || 'Error del servidor');
                });
            }
            return response.json();
        })
        .then(function() {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: itemName + ': BOM restaurado'
                });
            }

            // Restaurar valores en inputs de este item
            updateItemUIAfterRestore(itemId);
        })
        .catch(function(error) {
            console.error('Error restoring BOM:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo restaurar: ' + error.message
                });
            }
        })
        .finally(function() {
            btnEl.innerHTML = '<i class="fas fa-undo mr-1"></i> Restaurar';
        });
    }

    // Actualizar UI de un item después de guardar
    function updateItemUIAfterSave(itemId) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        // Actualizar inputs de BOM
        var bomInputs = section.querySelectorAll('.bom-adjust-input');
        bomInputs.forEach(function(input) {
            input.dataset.originalValue = input.value;
            var baseQty = parseFloat(input.closest('tr').dataset.baseQty);
            var currentVal = parseFloat(input.value) || 0;

            if (Math.abs(currentVal - baseQty) > 0.001) {
                input.classList.add('saved');
                input.classList.remove('changed');
            } else {
                input.classList.remove('saved', 'changed');
            }
        });

        // Actualizar inputs de tarifa de bordado
        var embroideryInputs = section.querySelectorAll('.embroidery-rate-input');
        embroideryInputs.forEach(function(input) {
            input.dataset.originalValue = input.value;
            var baseRate = parseFloat(input.dataset.baseRate) || 0;
            var currentVal = parseFloat(input.value) || 0;

            // Actualizar indicador visual de ajuste
            var row = input.closest('tr');
            var adjustedLabel = row.querySelector('.embroidery-rate-adjusted-label');

            if (Math.abs(currentVal - baseRate) > 0.0001) {
                input.classList.add('saved');
                input.classList.remove('changed');
                input.style.borderColor = '#28a745';
                // Mostrar etiqueta "Ajustado" si existe
                if (adjustedLabel) {
                    adjustedLabel.style.display = 'inline';
                }
            } else {
                input.classList.remove('saved', 'changed');
                input.style.borderColor = '';
                // Ocultar etiqueta "Ajustado"
                if (adjustedLabel) {
                    adjustedLabel.style.display = 'none';
                }
            }
        });

        // Actualizar badge de estado
        var statusEl = document.querySelector('.bom-item-status-' + itemId);
        if (statusEl) {
            statusEl.innerHTML = '<span class="badge badge-success mr-1" style="font-size: 11px;"><i class="fas fa-database mr-1"></i> Guardado</span>';
        }

        // Habilitar botón restaurar
        var btnRestore = section.querySelector('.btn-restore-item');
        if (btnRestore) {
            btnRestore.disabled = false;
        }

        // Deshabilitar botón independiente de tarifa de bordado (ya se guardó)
        var btnSaveEmbroidery = section.querySelector('.btn-save-embroidery-rate');
        if (btnSaveEmbroidery) {
            btnSaveEmbroidery.disabled = true;
        }
    }

    // Actualizar UI de un item después de restaurar
    function updateItemUIAfterRestore(itemId) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        // Restaurar inputs de BOM
        var bomInputs = section.querySelectorAll('.bom-adjust-input');
        bomInputs.forEach(function(input) {
            var baseQty = parseFloat(input.closest('tr').dataset.baseQty);
            input.value = baseQty.toFixed(2);
            input.dataset.originalValue = baseQty.toFixed(2);
            input.classList.remove('changed', 'saved');
        });

        // Restaurar inputs de tarifa de bordado
        var embroideryInputs = section.querySelectorAll('.embroidery-rate-input');
        embroideryInputs.forEach(function(input) {
            var baseRate = parseFloat(input.dataset.baseRate) || 0;
            input.value = baseRate.toFixed(4);
            input.dataset.originalValue = baseRate.toFixed(4);
            input.classList.remove('changed', 'saved');
            input.style.borderColor = '';

            // Ocultar etiqueta "Ajustado"
            var row = input.closest('tr');
            var adjustedLabel = row.querySelector('.embroidery-rate-adjusted-label');
            if (adjustedLabel) {
                adjustedLabel.style.display = 'none';
            }
        });

        // Limpiar badge de estado
        var statusEl = document.querySelector('.bom-item-status-' + itemId);
        if (statusEl) {
            statusEl.innerHTML = '';
        }

        // Deshabilitar botón restaurar
        var btnRestore = section.querySelector('.btn-restore-item');
        if (btnRestore) {
            btnRestore.disabled = true;
        }

        // Deshabilitar botón guardar
        var btnSave = section.querySelector('.btn-save-item');
        if (btnSave) {
            btnSave.disabled = true;
        }

        // Deshabilitar botón independiente de tarifa de bordado
        var btnSaveEmbroidery = section.querySelector('.btn-save-embroidery-rate');
        if (btnSaveEmbroidery) {
            btnSaveEmbroidery.disabled = true;
        }

        // Recalcular totales (incluyendo bordado)
        recalcularCostoBordado(itemId);
        recalcularTotales();
    }

    // Recalcular costo de bordado de un item específico basado en cambios de precio por millar
    function recalcularCostoBordado(itemId) {
        var section = document.querySelector('.bom-item-section[data-item-id="' + itemId + '"]');
        if (!section) return;

        var totalEmbroideryCost = 0;
        var designRows = section.querySelectorAll('tr[data-design-id]');

        designRows.forEach(function(row) {
            var millar = parseFloat(row.dataset.designMillar) || 0;
            var itemQty = parseFloat(row.dataset.itemQty) || 1;
            var rateInput = row.querySelector('.embroidery-rate-input');

            if (rateInput) {
                var rate = parseFloat(rateInput.value) || 0;
                var designCost = millar * rate * itemQty;
                totalEmbroideryCost += designCost;

                // Actualizar costo del diseño en la fila
                var costEl = row.querySelector('.embroidery-design-cost');
                if (costEl) {
                    costEl.textContent = '$' + designCost.toFixed(2);
                }
            }
        });

        // Actualizar subtotal de bordado
        var embroiderySubtotalEl = section.querySelector('.bom-embroidery-subtotal[data-item-id="' + itemId + '"]');
        if (embroiderySubtotalEl) {
            embroiderySubtotalEl.textContent = '$' + totalEmbroideryCost.toFixed(2);
            embroiderySubtotalEl.dataset.value = totalEmbroideryCost.toFixed(2);
        }

        // Actualizar el resumen de bordado global
        actualizarResumenBordadoGlobal();
    }

    // Actualizar el resumen global de bordado en el footer
    function actualizarResumenBordadoGlobal() {
        var totalEmbroideryCostGlobal = 0;
        var totalStitchesGlobal = 0;

        var itemSections = document.querySelectorAll('.bom-item-section');
        itemSections.forEach(function(section) {
            var itemId = section.dataset.itemId;
            var embroiderySubtotalEl = section.querySelector('.bom-embroidery-subtotal[data-item-id="' + itemId + '"]');
            if (embroiderySubtotalEl && embroiderySubtotalEl.dataset.value) {
                totalEmbroideryCostGlobal += parseFloat(embroiderySubtotalEl.dataset.value) || 0;
            }

            // Sumar puntadas de todos los diseños
            var designRows = section.querySelectorAll('tr[data-design-id]');
            designRows.forEach(function(row) {
                var stitches = parseFloat(row.dataset.designStitches) || 0;
                var itemQty = parseFloat(row.dataset.itemQty) || 1;
                totalStitchesGlobal += stitches * itemQty;
            });
        });

        // Actualizar el total de bordado en el footer
        var elBordadoTotal = document.getElementById('bomBordadoTotal');
        if (elBordadoTotal) {
            elBordadoTotal.textContent = '$' + totalEmbroideryCostGlobal.toFixed(2);
        }
    }

    function recalcularTotales() {
        var totalAjustado = 0;
        var totalExtrasMaterials = 0;
        var totalEmbroideryCostGlobal = 0;

        // Recalcular por cada sección de item
        var itemSections = document.querySelectorAll('.bom-item-section');
        itemSections.forEach(function(section) {
            var itemTotal = 0;
            var itemExtrasMaterialsTotal = 0;
            var rows = section.querySelectorAll('tbody tr[data-variant-id]');

            rows.forEach(function(row) {
                var avgCost = parseFloat(row.dataset.avgCost) || 0;
                var itemQty = parseFloat(row.dataset.itemQty) || 1;
                var baseQty = parseFloat(row.dataset.baseQty) || 0;
                var isExtra = row.dataset.isExtra === '1';

                // Buscar input ajustable o usar base
                var input = row.querySelector('.bom-adjust-input');
                var adjustedQty = input ? (parseFloat(input.value) || 0) : baseQty;

                var subtotal;
                if (isExtra) {
                    // Para extras, el input ya tiene la cantidad total
                    subtotal = adjustedQty * avgCost;
                    itemExtrasMaterialsTotal += subtotal;
                    totalExtrasMaterials += subtotal;
                } else {
                    // Para BOM del producto, multiplicar por cantidad de items
                    subtotal = adjustedQty * avgCost * itemQty;
                    itemTotal += subtotal;
                    totalAjustado += subtotal;
                }

                // Actualizar subtotal en la fila
                var subtotalEl = row.querySelector('.bom-subtotal');
                if (subtotalEl) {
                    subtotalEl.textContent = '$' + subtotal.toFixed(2);
                }
            });

            // Obtener el item_id de esta sección
            var itemId = section.dataset.itemId;

            // Actualizar Subtotal BOM Producto
            var bomProductSubtotalEl = section.querySelector('.bom-product-subtotal[data-item-id="' + itemId + '"]');
            if (bomProductSubtotalEl) {
                bomProductSubtotalEl.textContent = '$' + itemTotal.toFixed(2);
            }

            // Actualizar Subtotal Materiales Extras
            var bomExtrasSubtotalEl = section.querySelector('.bom-extras-subtotal[data-item-id="' + itemId + '"]');
            if (bomExtrasSubtotalEl) {
                bomExtrasSubtotalEl.textContent = '$' + itemExtrasMaterialsTotal.toFixed(2);
            }

            // Actualizar total del BOM del item (total producción = BOM + Bordado + extras materiales + servicios)
            var itemTotalEl = section.querySelector('.bom-item-total');
            if (itemTotalEl) {
                // Obtener costo de bordado (dinámico desde el data-value actualizado)
                var embroideryCost = 0;
                var embroiderySubtotalEl = section.querySelector('.bom-embroidery-subtotal[data-item-id="' + itemId + '"]');
                if (embroiderySubtotalEl) {
                    // Usar el valor del data-value que se actualiza dinámicamente
                    embroideryCost = parseFloat(embroiderySubtotalEl.dataset.value) || 0;
                }
                // Obtener costo de servicios (fijo desde el data-value)
                var serviciosCost = 0;
                var serviciosSubtotalEl = section.querySelector('.bom-services-subtotal[data-item-id="' + itemId + '"]');
                if (serviciosSubtotalEl && serviciosSubtotalEl.dataset.value) {
                    serviciosCost = parseFloat(serviciosSubtotalEl.dataset.value) || 0;
                }
                var totalItemCosto = itemTotal + embroideryCost + itemExtrasMaterialsTotal + serviciosCost;
                itemTotalEl.textContent = '$' + totalItemCosto.toFixed(2);

                // Actualizar también el desglose en el TOTAL COSTO PRODUCCIÓN
                var desgloseEl = itemTotalEl.closest('tr')?.querySelector('td:first-child div');
                if (desgloseEl) {
                    var desgloseHTML = '<span>BOM $' + itemTotal.toFixed(2) + '</span>';
                    if (embroideryCost > 0) {
                        desgloseHTML += '<span style="margin: 0 6px;">+</span><span>Bordado $' + embroideryCost.toFixed(2) + '</span>';
                    }
                    if (itemExtrasMaterialsTotal > 0) {
                        desgloseHTML += '<span style="margin: 0 6px;">+</span><span>Mat.Extras $' + itemExtrasMaterialsTotal.toFixed(2) + '</span>';
                    }
                    if (serviciosCost > 0) {
                        desgloseHTML += '<span style="margin: 0 6px;">+</span><span>Servicios $' + serviciosCost.toFixed(2) + '</span>';
                    }
                    desgloseEl.innerHTML = desgloseHTML;
                }

                // Acumular para el total global de bordado
                totalEmbroideryCostGlobal += embroideryCost;
            }
        });

        // Actualizar el total de bordado global en el footer
        var elBordadoTotal = document.getElementById('bomBordadoTotal');
        if (elBordadoTotal) {
            elBordadoTotal.textContent = '$' + totalEmbroideryCostGlobal.toFixed(2);
        }

        // Total ajustado combinado (BOM + Materiales Extras)
        var totalAjustadoCombinado = totalAjustado + totalExtrasMaterials;

        // Actualizar resumen general
        var elAjustado = document.getElementById('bomCostoAjustado');
        var elDiferencia = document.getElementById('bomDiferencia');
        var elDiferenciaPct = document.getElementById('bomDiferenciaPct');
        var elExtrasAjustado = document.getElementById('bomExtrasAjustado');

        // Actualizar BOM Productos
        if (elAjustado) {
            elAjustado.textContent = '$' + totalAjustado.toFixed(2);

            var diferenciaBom = totalAjustado - costoOriginalBom;
            if (diferenciaBom > 0.01) {
                elAjustado.style.color = '#ff9800';
            } else if (diferenciaBom < -0.01) {
                elAjustado.style.color = '#4caf50';
            } else {
                elAjustado.style.color = '#90caf9';
            }
        }

        // Actualizar Mat. Extras (si existe el elemento)
        if (elExtrasAjustado) {
            elExtrasAjustado.textContent = '$' + totalExtrasMaterials.toFixed(2);

            var diferenciaExtras = totalExtrasMaterials - costoOriginalExtras;
            if (diferenciaExtras > 0.01) {
                elExtrasAjustado.style.color = '#ff9800';
            } else if (diferenciaExtras < -0.01) {
                elExtrasAjustado.style.color = '#4caf50';
            } else {
                elExtrasAjustado.style.color = '#ffb74d';
            }
        }

        // Calcular diferencia total (BOM + Extras)
        var diferenciaTotal = totalAjustadoCombinado - costoOriginalTotal;

        if (elDiferencia) {
            var signo = diferenciaTotal >= 0 ? '+' : '';
            elDiferencia.innerHTML = signo + '$' + Math.abs(diferenciaTotal).toFixed(2);

            if (diferenciaTotal > 0.01) {
                elDiferencia.style.color = '#ff9800';
            } else if (diferenciaTotal < -0.01) {
                elDiferencia.style.color = '#4caf50';
            } else {
                elDiferencia.style.color = '#eceff1';
            }
        }

        if (elDiferenciaPct && costoOriginalTotal > 0) {
            var pct = (diferenciaTotal / costoOriginalTotal) * 100;
            var signoPct = pct >= 0 ? '+' : '';
            elDiferenciaPct.textContent = '(' + signoPct + pct.toFixed(1) + '%)';
        }

        // Actualizar Total Producción (BOM + Bordado + Mat.Extras + Servicios)
        var elTotalProduccion = document.getElementById('bomTotalProduccion');
        if (elTotalProduccion) {
            // Obtener servicios desde el elemento fijo (no cambian dinámicamente por ahora)
            var totalServicios = {{ $totalExtrasServiceCost ?? 0 }};
            var totalProduccionNuevo = totalAjustado + totalEmbroideryCostGlobal + totalExtrasMaterials + totalServicios;
            elTotalProduccion.textContent = '$' + totalProduccionNuevo.toFixed(2);
        }
    }
})();
</script>
@endif
