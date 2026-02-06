{{-- Partial: Totales de items para vista desktop --}}
<tr>
    <td colspan="5" class="text-right" style="font-size: 14px; color: #212529;">
        <strong>Subtotal:</strong>
    </td>
    <td class="text-right" style="font-size: 15px; color: #212529;">
        <strong data-order-subtotal>${{ number_format($order->subtotal, 2) }}</strong>
    </td>
</tr>
@if ($order->discount > 0)
    <tr>
        <td colspan="5" class="text-right" style="font-size: 14px; color: #c62828;">Descuento:</td>
        <td class="text-right" style="font-size: 15px; color: #c62828;" data-order-discount>
            -${{ number_format($order->discount, 2) }}
        </td>
    </tr>
@endif
@if ($order->requires_invoice && $order->iva_amount > 0)
    @php
        $ivaRate = $order->iva_rate ?? \App\Models\Order::getDefaultTaxRate();
    @endphp
    <tr>
        <td colspan="5" class="text-right" style="font-size: 14px; color: #212529;">IVA {{ number_format($ivaRate, 0) }}%:</td>
        <td class="text-right" style="font-size: 15px; color: #212529;" data-order-iva>
            ${{ number_format($order->iva_amount, 2) }}
        </td>
    </tr>
@endif
<tr style="background: #007bff;">
    <td colspan="5" class="text-right" style="color: white;">
        <strong style="font-size: 16px;">TOTAL:</strong>
    </td>
    <td class="text-right" style="color: white;" data-order-total>
        <strong style="font-size: 20px;">${{ number_format($order->total, 2) }}</strong>
    </td>
</tr>
