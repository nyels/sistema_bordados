{{-- ================================================================ --}}
{{-- FASE X-A: AJUSTE BOM POR MEDIDAS (PRE-PRODUCCIÓN) --}}
{{-- Solo visible en estado CONFIRMED --}}
{{-- Sin persistencia - Visualización y cálculo en memoria --}}
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

        $itemBom = [
            'item_id' => $item->id,
            'item_name' => $item->product_name,
            'quantity' => $item->quantity,
            'has_measurements' => $item->requires_measurements && !empty($item->measurements),
            'measurements' => $item->measurements ?? [],
            'materials' => [],
        ];

        foreach ($product->materials as $materialVariant) {
            $material = $materialVariant->material;
            $category = $material?->category;
            $categorySlug = $category?->slug ?? 'otros';

            $baseQty = (float) $materialVariant->pivot->quantity;
            $avgCost = (float) $materialVariant->average_cost;
            $isVariable = in_array($categorySlug, $categoriasVariables);

            $itemBom['materials'][] = [
                'variant_id' => $materialVariant->id,
                'name' => $materialVariant->display_name ?? $material?->name ?? 'Material',
                'category' => $category?->name ?? 'Sin categoría',
                'category_slug' => $categorySlug,
                'is_variable' => $isVariable,
                'base_quantity' => $baseQty,
                'unit' => $material?->consumptionUnit?->symbol ?? $material?->baseUnit?->symbol ?? 'u',
                'average_cost' => $avgCost,
                'base_cost_per_item' => $baseQty * $avgCost,
            ];

            $totalCostoBase += ($baseQty * $avgCost * $item->quantity);
        }

        if (!empty($itemBom['materials'])) {
            // Ordenar materiales alfabéticamente por nombre
            usort($itemBom['materials'], fn($a, $b) => strcasecmp($a['name'], $b['name']));
            $bomData[] = $itemBom;
        }
    }

    $hasVariableMaterials = collect($bomData)->flatMap(fn($i) => $i['materials'])->contains('is_variable', true);
    $hasItemsWithMeasurements = $itemsConMedidas->isNotEmpty();
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
            @if($hasVariableMaterials && $hasItemsWithMeasurements)
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
                    <strong>Materiales variables</strong> (telas, pelones, avíos) pueden ajustarse según las medidas del cliente.
                    Los cambios se reflejan en el estimado de costo pero <strong>no se guardan</strong> hasta implementar persistencia.
                @else
                    Este pedido usa el BOM estándar del producto. No hay materiales variables para ajustar.
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            @foreach($bomData as $itemBom)
                <div class="bom-item-section" data-item-id="{{ $itemBom['item_id'] }}">
                    {{-- Header del item --}}
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center"
                         style="background: #f5f5f5; border-bottom: 1px solid #e0e0e0;">
                        <div>
                            <strong style="font-size: 15px; color: #212529;">
                                {{ $itemBom['item_name'] }}
                            </strong>
                            <span class="badge badge-secondary ml-2" style="font-size: 12px;">
                                × {{ $itemBom['quantity'] }} pz
                            </span>
                        </div>
                        <div>
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
                            @foreach($itemBom['materials'] as $mat)
                                @php
                                    $canAdjust = $mat['is_variable'] && $itemBom['has_measurements'];
                                    $rowBg = $canAdjust ? '#fff8e1' : '#ffffff';
                                    $subtotalBase = $mat['base_quantity'] * $mat['average_cost'] * $itemBom['quantity'];
                                @endphp
                                <tr style="background: {{ $rowBg }};"
                                    data-variant-id="{{ $mat['variant_id'] }}"
                                    data-base-qty="{{ $mat['base_quantity'] }}"
                                    data-avg-cost="{{ $mat['average_cost'] }}"
                                    data-item-qty="{{ $itemBom['quantity'] }}">
                                    <td style="padding: 10px 12px; vertical-align: middle;">
                                        <div>
                                            <strong style="color: #212529;">{{ $mat['name'] }}</strong>
                                            @if($mat['is_variable'])
                                                <span class="badge badge-warning ml-1" style="font-size: 10px;">Variable</span>
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
                                                       class="form-control bom-adjust-input text-center"
                                                       value="{{ number_format($mat['base_quantity'], 2, '.', '') }}"
                                                       min="0"
                                                       step="0.01"
                                                       data-variant-id="{{ $mat['variant_id'] }}"
                                                       style="font-size: 14px; font-weight: 600; border-color: #ffc107;">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="font-size: 12px; background: #fff8e1;">{{ $mat['unit'] }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span style="color: #212529; font-size: 14px;">
                                                {{ number_format($mat['base_quantity'], 2) }} {{ $mat['unit'] }}
                                            </span>
                                            <span class="badge badge-light ml-1" style="font-size: 10px;">Fijo</span>
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
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{-- Resumen de impacto --}}
            <div class="px-3 py-3" style="background: #263238; color: white;">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div style="font-size: 13px; color: #eceff1; text-transform: uppercase; letter-spacing: 0.5px;">
                            Costo BOM Original
                        </div>
                        <div id="bomCostoOriginal" style="font-size: 20px; font-weight: 700;">
                            ${{ number_format($totalCostoBase, 2) }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0 text-md-center">
                        <div style="font-size: 13px; color: #eceff1; text-transform: uppercase; letter-spacing: 0.5px;">
                            Costo BOM Ajustado
                        </div>
                        <div id="bomCostoAjustado" style="font-size: 20px; font-weight: 700; color: #ffc107;">
                            ${{ number_format($totalCostoBase, 2) }}
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <div style="font-size: 13px; color: #eceff1; text-transform: uppercase; letter-spacing: 0.5px;">
                            Diferencia
                        </div>
                        <div id="bomDiferencia" style="font-size: 20px; font-weight: 700;">
                            $0.00 <span id="bomDiferenciaPct" style="font-size: 14px; color: #eceff1;">(0%)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerta de no persistencia --}}
            <div class="px-3 py-2" style="background: #fff3e0; border-top: 1px solid #ffe0b2;">
                <div class="d-flex align-items-center" style="font-size: 14px; color: #e65100;">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <div>
                        <strong>FASE X-A (Solo visualización):</strong>
                        Los ajustes realizados aquí son solo para validación UX.
                        No se guardan en base de datos hasta implementar persistencia.
                    </div>
                </div>
            </div>

            {{-- Botones de acción (deshabilitados en FASE X-A) --}}
            <div class="px-3 py-3 d-flex justify-content-between align-items-center" style="background: #fafafa; border-top: 1px solid #e0e0e0;">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetBom" disabled>
                    <i class="fas fa-undo mr-1"></i> Restaurar BOM Original
                </button>
                <div>
                    <span class="mr-2" style="font-size: 13px; color: #212529;">
                        <i class="fas fa-lock mr-1"></i> Persistencia pendiente
                    </span>
                    <button type="button" class="btn btn-success btn-sm" id="btnSaveBom" disabled>
                        <i class="fas fa-save mr-1"></i> Guardar Ajustes
                    </button>
                </div>
            </div>
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
    .bom-item-section + .bom-item-section {
        border-top: 2px solid #5c6bc0;
    }
</style>

{{-- JavaScript para cálculo en memoria --}}
<script>
(function() {
    'use strict';

    var costoOriginal = {{ $totalCostoBase }};

    document.addEventListener('DOMContentLoaded', function() {
        var inputs = document.querySelectorAll('.bom-adjust-input');

        inputs.forEach(function(input) {
            var baseQty = parseFloat(input.closest('tr').dataset.baseQty);

            input.addEventListener('input', function() {
                recalcularTotales();

                // Marcar como cambiado si difiere del base
                var currentVal = parseFloat(this.value) || 0;
                if (Math.abs(currentVal - baseQty) > 0.001) {
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
                recalcularTotales();
            });
        });

        // Botón reset
        var btnReset = document.getElementById('btnResetBom');
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                inputs.forEach(function(input) {
                    var baseQty = parseFloat(input.closest('tr').dataset.baseQty);
                    input.value = baseQty.toFixed(2);
                    input.classList.remove('changed');
                });
                recalcularTotales();
            });
        }
    });

    function recalcularTotales() {
        var totalAjustado = 0;
        var rows = document.querySelectorAll('#collapseBomAdjustment tbody tr[data-variant-id]');

        rows.forEach(function(row) {
            var avgCost = parseFloat(row.dataset.avgCost) || 0;
            var itemQty = parseFloat(row.dataset.itemQty) || 1;
            var baseQty = parseFloat(row.dataset.baseQty) || 0;

            // Buscar input ajustable o usar base
            var input = row.querySelector('.bom-adjust-input');
            var adjustedQty = input ? (parseFloat(input.value) || 0) : baseQty;

            var subtotal = adjustedQty * avgCost * itemQty;
            totalAjustado += subtotal;

            // Actualizar subtotal en la fila
            var subtotalEl = row.querySelector('.bom-subtotal');
            if (subtotalEl) {
                subtotalEl.textContent = '$' + subtotal.toFixed(2);
            }
        });

        // Actualizar resumen
        var elAjustado = document.getElementById('bomCostoAjustado');
        var elDiferencia = document.getElementById('bomDiferencia');
        var elDiferenciaPct = document.getElementById('bomDiferenciaPct');

        if (elAjustado) {
            elAjustado.textContent = '$' + totalAjustado.toFixed(2);

            // Cambiar color según diferencia
            var diferencia = totalAjustado - costoOriginal;
            if (diferencia > 0.01) {
                elAjustado.style.color = '#ff9800'; // Naranja - aumento
            } else if (diferencia < -0.01) {
                elAjustado.style.color = '#4caf50'; // Verde - reducción
            } else {
                elAjustado.style.color = '#ffc107'; // Amarillo - sin cambio
            }
        }

        if (elDiferencia) {
            var diferencia = totalAjustado - costoOriginal;
            var signo = diferencia >= 0 ? '+' : '';
            elDiferencia.innerHTML = signo + '$' + diferencia.toFixed(2);

            // Color según dirección
            if (diferencia > 0.01) {
                elDiferencia.style.color = '#ff9800';
            } else if (diferencia < -0.01) {
                elDiferencia.style.color = '#4caf50';
            } else {
                elDiferencia.style.color = '#eceff1';
            }
        }

        if (elDiferenciaPct && costoOriginal > 0) {
            var pct = ((totalAjustado - costoOriginal) / costoOriginal) * 100;
            var signoPct = pct >= 0 ? '+' : '';
            elDiferenciaPct.textContent = '(' + signoPct + pct.toFixed(1) + '%)';
        }

        // Habilitar/deshabilitar botón reset si hay cambios
        var btnReset = document.getElementById('btnResetBom');
        var hasChanges = document.querySelectorAll('.bom-adjust-input.changed').length > 0;
        if (btnReset) {
            btnReset.disabled = !hasChanges;
        }
    }
})();
</script>
@endif
