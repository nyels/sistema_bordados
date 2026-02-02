{{-- Totals - Enterprise SaaS Design 2025 --}}
<div class="pos-totals-section">
    {{-- Descuento --}}
    <div class="pos-discount-section">
        <div class="pos-discount-header">
            <label class="pos-discount-label">Motivo descuento</label>
            <select id="discount-reason" class="pos-discount-reason">
                <option value="">Selecciona el motivo</option>
                @if(isset($motivosDescuento))
                    @foreach($motivosDescuento as $motivo)
                        <option value="{{ $motivo->nombre }}">{{ $motivo->nombre }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="pos-discount-controls">
            <div class="pos-discount-type">
                <button type="button" id="btn-discount-money" class="pos-discount-btn active" data-type="money">
                    <i class="fas fa-dollar-sign"></i>
                </button>
                <button type="button" id="btn-discount-percent" class="pos-discount-btn" data-type="percent">
                    <i class="fas fa-percent"></i>
                </button>
            </div>
            <div class="pos-discount-value">
                <span class="pos-discount-prefix" id="discount-prefix">$</span>
                <input type="number"
                       id="discount-value"
                       min="0"
                       step="0.01"
                       placeholder="0.00"
                       value="">
            </div>
        </div>
    </div>

    {{-- Total Display --}}
    <div class="pos-total-display" id="pos-total-display">
        <span class="pos-total-empty">Agrega productos al carrito</span>
    </div>
</div>

@push('styles')
<style>
    .pos-totals-section {
        padding: var(--pos-space-sm) var(--pos-space-md);
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: var(--pos-white);
        border-top: 1px solid var(--pos-slate-100);
    }

    /* Discount Section */
    .pos-discount-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pos-discount-header {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .pos-discount-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--pos-slate-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .pos-discount-reason {
        width: 100%;
        height: 34px;
        padding: 0 12px;
        background: var(--pos-slate-50);
        border: 1.5px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-sm);
        font-size: 13px;
        color: var(--pos-slate-700);
        transition: var(--pos-transition);
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M2 4l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
    }

    .pos-discount-reason:focus {
        outline: none;
        border-color: var(--pos-primary);
        background-color: var(--pos-white);
    }

    .pos-discount-reason option {
        color: var(--pos-slate-700);
        background: var(--pos-white);
        padding: 8px;
    }

    .pos-discount-reason option:first-child {
        color: var(--pos-slate-400);
    }

    .pos-discount-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pos-discount-type {
        display: flex;
        background: var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        padding: 3px;
        gap: 2px;
    }

    .pos-discount-btn {
        width: 38px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 2px solid transparent;
        border-radius: var(--pos-radius-sm);
        color: var(--pos-slate-500);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-discount-btn:hover {
        color: var(--pos-slate-700);
        background: rgba(255, 255, 255, 0.5);
    }

    .pos-discount-btn.active {
        background: var(--pos-primary);
        color: var(--pos-white);
        border-color: var(--pos-primary);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
    }

    .pos-discount-btn.active:hover {
        background: var(--pos-primary-dark);
        border-color: var(--pos-primary-dark);
    }

    .pos-discount-value {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 120px;
        height: 36px;
        background: var(--pos-slate-50);
        border: 1.5px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-sm);
        overflow: hidden;
        transition: var(--pos-transition);
    }

    .pos-discount-value:focus-within {
        border-color: var(--pos-primary);
        background: var(--pos-white);
    }

    .pos-discount-prefix {
        padding: 0 8px 0 12px;
        font-size: 14px;
        font-weight: 700;
        color: var(--pos-slate-600);
    }

    .pos-discount-value input {
        flex: 1;
        width: 100%;
        height: 100%;
        border: none;
        background: transparent;
        font-size: 15px;
        font-weight: 600;
        color: var(--pos-slate-800);
        outline: none;
        padding-right: 12px;
    }

    .pos-discount-value input::placeholder {
        color: var(--pos-slate-400);
        font-weight: 400;
    }

    /* Total Display */
    .pos-total-display {
        background: linear-gradient(135deg, var(--pos-primary) 0%, var(--pos-primary-dark) 100%);
        border-radius: var(--pos-radius-md);
        padding: 8px 10px;
        color: var(--pos-white);
    }

    .pos-total-empty {
        display: block;
        text-align: center;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        padding: 4px 0;
    }

    .pos-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        font-weight: 500;
        padding: 2px 0;
        color: rgba(255, 255, 255, 0.95);
    }

    .pos-total-row span:last-child {
        font-weight: 600;
    }

    .pos-total-row-subtotal {
        padding-bottom: 4px;
        margin-bottom: 4px;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
    }

    .pos-total-row-discount {
        color: var(--pos-success-light, #6ee7b7);
        padding-bottom: 4px;
        margin-bottom: 4px;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.3);
    }

    .pos-total-row-iva .pos-iva-label {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Mini Toggle for IVA */
    .pos-toggle-mini {
        position: relative;
        width: 28px;
        height: 16px;
        cursor: pointer;
    }

    .pos-toggle-mini input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .pos-toggle-mini-slider {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        transition: var(--pos-transition);
    }

    .pos-toggle-mini-slider::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 12px;
        height: 12px;
        background: var(--pos-white);
        border-radius: 50%;
        transition: var(--pos-transition);
    }

    .pos-toggle-mini input:checked + .pos-toggle-mini-slider {
        background: var(--pos-success);
    }

    .pos-toggle-mini input:checked + .pos-toggle-mini-slider::before {
        transform: translateX(12px);
    }

    .pos-total-row-final {
        margin-top: 4px;
        padding-top: 6px;
        border-top: 2px solid rgba(255, 255, 255, 0.3);
        font-size: 13px;
        font-weight: 600;
        color: var(--pos-white);
    }

    .pos-total-row-final span:last-child {
        font-size: 14px;
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnMoney = document.getElementById('btn-discount-money');
    var btnPercent = document.getElementById('btn-discount-percent');
    var discountPrefix = document.getElementById('discount-prefix');
    var discountValue = document.getElementById('discount-value');

    // Función para disparar actualización de totales
    function triggerUpdateTotals() {
        if (typeof window.updateTotals === 'function') {
            window.updateTotals();
        }
    }

    if (btnMoney && btnPercent) {
        btnMoney.addEventListener('click', function() {
            btnMoney.classList.add('active');
            btnPercent.classList.remove('active');
            discountPrefix.textContent = '$';
            discountValue.placeholder = '0.00';
            discountValue.step = '0.01';
            discountValue.removeAttribute('max');
            triggerUpdateTotals();
        });

        btnPercent.addEventListener('click', function() {
            btnPercent.classList.add('active');
            btnMoney.classList.remove('active');
            discountPrefix.textContent = '%';
            discountValue.placeholder = '0';
            discountValue.step = '1';
            discountValue.max = '100';
            triggerUpdateTotals();
        });
    }

    if (discountValue) {
        // Eventos múltiples para capturar cualquier cambio
        ['input', 'change', 'keyup'].forEach(function(eventType) {
            discountValue.addEventListener(eventType, function() {
                triggerUpdateTotals();
            });
        });
    }
});
</script>
@endpush
