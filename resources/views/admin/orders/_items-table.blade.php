<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-box mr-2"></i> Productos</h5>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size: 14px;">
            <thead class="bg-light">
                <tr>
                    <th style="width: 60px;">Img</th>
                    <th>Producto</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-center">Bloquea</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php
                        $baseSubtotal = $item->quantity * $item->unit_price;
                        $extras = is_array($item->extras) ? $item->extras : [];
                        $extrasSubtotal = collect($extras)->sum(fn($e) => floatval($e['price'] ?? 0) * intval($e['quantity'] ?? 1));
                        $itemTotal = $baseSubtotal + $extrasSubtotal;

                        $blocksR2 = $item->has_pending_adjustments;
                        $blocksR3 = $item->personalization_type === \App\Models\OrderItem::PERSONALIZATION_DESIGN && !$item->design_approved;
                        $blocksR4 = $item->hasMeasurementsChangedAfterApproval();
                        $hasBlocker = $blocksR2 || $blocksR3 || $blocksR4;
                    @endphp
                    <tr class="{{ $hasBlocker ? 'table-danger' : '' }}">
                        <td class="align-top">
                            @if ($item->product && $item->product->primaryImage)
                                <img src="{{ $item->product->primaryImage->thumbnail_small_url }}"
                                    class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <img src="{{ asset('img/no-image.png') }}" class="img-fluid rounded"
                                    style="width: 50px; height: 50px; object-fit: cover;">
                            @endif
                        </td>
                        <td>
                            <strong style="font-size: 15px;">{{ $item->product_name }}</strong>
                            @if ($item->is_annex)
                                <span class="badge badge-warning ml-1">ANEXO</span>
                            @endif
                            @if ($item->variant_sku)
                                <br><span class="text-muted">SKU: {{ $item->variant_sku }}</span>
                            @endif

                            @if ($item->embroidery_text)
                                <div class="mt-1">
                                    <span class="text-info"><i class="fas fa-pen-fancy mr-1"></i>{{ $item->embroidery_text }}</span>
                                </div>
                            @endif

                            @if ($item->customization_notes)
                                <div class="mt-1">
                                    <span class="text-warning"><i class="fas fa-sticky-note mr-1"></i>{{ $item->customization_notes }}</span>
                                </div>
                            @endif

                            @php
                                $measurements = is_array($item->measurements) ? $item->measurements : [];
                                $hasMeasurements = !empty($measurements) && count(array_filter($measurements, fn($v) => !empty($v) && $v !== '0')) > 0;
                            @endphp
                            @if ($hasMeasurements)
                                <div class="mt-1 p-1 bg-light rounded" style="font-size: 12px;">
                                    <i class="fas fa-ruler text-success mr-1"></i>
                                    @foreach($measurements as $key => $value)
                                        @if(!empty($value) && $value !== '0')
                                            <span class="badge badge-secondary">{{ ucfirst($key) }}: {{ $value }}cm</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if (count($extras) > 0)
                                <div class="mt-1" style="font-size: 12px;">
                                    <i class="fas fa-plus-square text-info mr-1"></i>
                                    @foreach ($extras as $extra)
                                        <span class="badge badge-info">{{ $extra['name'] ?? 'Extra' }} ${{ number_format($extra['price'] ?? 0, 2) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center align-top" style="font-size: 16px;">{{ $item->quantity }}</td>
                        <td class="text-center align-top">
                            @if($blocksR2)
                                <span class="badge badge-danger" title="Ajuste de precio pendiente">R2</span>
                            @elseif($blocksR3)
                                <span class="badge badge-danger" title="Diseno no aprobado">R3</span>
                            @elseif($blocksR4)
                                <span class="badge badge-danger" title="Medidas modificadas">R4</span>
                            @else
                                <span class="text-success"><i class="fas fa-check"></i></span>
                            @endif
                        </td>
                        <td class="text-right align-top">
                            <strong style="font-size: 15px;">${{ number_format($itemTotal, 2) }}</strong>
                            @if($extrasSubtotal > 0)
                                <br><small class="text-info">+${{ number_format($extrasSubtotal, 2) }} extras</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right"><strong>${{ number_format($order->subtotal, 2) }}</strong></td>
                </tr>
                @if ($order->discount > 0)
                    <tr>
                        <td colspan="4" class="text-right text-danger">Descuento:</td>
                        <td class="text-right text-danger">-${{ number_format($order->discount, 2) }}</td>
                    </tr>
                @endif
                @if ($order->requires_invoice && $order->iva_amount > 0)
                    <tr>
                        <td colspan="4" class="text-right">IVA 16%:</td>
                        <td class="text-right">${{ number_format($order->iva_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="table-primary">
                    <td colspan="4" class="text-right"><strong style="font-size: 18px;">TOTAL:</strong></td>
                    <td class="text-right"><strong style="font-size: 24px;" class="text-primary">${{ number_format($order->total, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
