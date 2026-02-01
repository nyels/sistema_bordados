{{-- Totals - Enterprise SaaS Design 2025 --}}
<div class="pos-totals-section">
    {{-- Precio Final Manual --}}
    <div class="pos-input-group">
        <label for="unit-price-final" class="pos-input-label">
            <i class="fas fa-tag"></i> Precio Final
        </label>
        <div class="pos-input-wrapper">
            <span class="pos-input-prefix">$</span>
            <input type="number"
                   id="unit-price-final"
                   min="0"
                   step="0.01"
                   placeholder="0.00"
                   class="pos-input-number">
            <span class="pos-input-suffix">c/u</span>
        </div>
    </div>

    {{-- ADVERTENCIA DE PRECIO DE RIESGO --}}
    <div id="price-warning" class="pos-price-warning hidden">
        <div class="pos-warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="pos-warning-content">
            <p id="price-warning-title" class="pos-warning-title"></p>
            <p id="price-warning-text" class="pos-warning-text"></p>
        </div>
    </div>

    {{-- Discount Reason --}}
    <div class="pos-input-group">
        <label for="discount-reason" class="pos-input-label">
            <i class="fas fa-percent"></i> Motivo Descuento
        </label>
        <input type="text"
               id="discount-reason"
               placeholder="Razon del descuento (opcional)..."
               maxlength="255"
               class="pos-input-text">
    </div>

    {{-- IVA Toggle --}}
    <div class="pos-toggle-group">
        <span class="pos-toggle-label">
            <i class="fas fa-receipt"></i> Aplicar IVA
        </span>
        <label class="pos-toggle">
            <input type="checkbox" id="apply-iva" class="pos-toggle-input">
            <span class="pos-toggle-slider"></span>
        </label>
    </div>

    {{-- Info Card --}}
    <div class="pos-info-card">
        <i class="fas fa-info-circle"></i>
        <p>El total sera calculado por el servidor al confirmar.</p>
    </div>
</div>

@push('styles')
<style>
    .pos-totals-section {
        padding: var(--pos-space-md);
        border-bottom: 1px solid var(--pos-slate-200);
        display: flex;
        flex-direction: column;
        gap: var(--pos-space-md);
        background: var(--pos-white);
    }

    .pos-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-input-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: var(--pos-slate-500);
    }

    .pos-input-label i {
        font-size: 12px;
        color: var(--pos-slate-400);
    }

    .pos-input-wrapper {
        display: flex;
        align-items: center;
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        overflow: hidden;
        transition: var(--pos-transition);
    }

    .pos-input-wrapper:focus-within {
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .pos-input-prefix,
    .pos-input-suffix {
        padding: 0 12px;
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-500);
        background: var(--pos-slate-50);
    }

    .pos-input-prefix {
        border-right: 1px solid var(--pos-slate-200);
    }

    .pos-input-suffix {
        border-left: 1px solid var(--pos-slate-200);
        font-size: 12px;
    }

    .pos-input-number {
        flex: 1;
        height: 44px;
        padding: 0 12px;
        border: none;
        font-size: 16px;
        font-weight: 600;
        color: var(--pos-slate-800);
        background: transparent;
        outline: none;
    }

    .pos-input-number::placeholder {
        color: var(--pos-slate-400);
        font-weight: 400;
    }

    .pos-input-text {
        width: 100%;
        height: 44px;
        padding: 0 14px;
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        font-size: 14px;
        color: var(--pos-slate-800);
        transition: var(--pos-transition);
    }

    .pos-input-text:focus {
        outline: none;
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .pos-input-text::placeholder {
        color: var(--pos-slate-400);
    }

    /* Price Warning */
    .pos-price-warning {
        display: flex;
        align-items: flex-start;
        gap: var(--pos-space-sm);
        padding: var(--pos-space-md);
        border-radius: var(--pos-radius-md);
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid var(--pos-warning);
    }

    .pos-price-warning.hidden {
        display: none;
    }

    .pos-warning-icon {
        font-size: 18px;
        color: var(--pos-warning);
        flex-shrink: 0;
    }

    .pos-warning-content {
        flex: 1;
    }

    .pos-warning-title {
        font-size: 13px;
        font-weight: 700;
        color: #92400e;
        margin: 0 0 2px 0;
    }

    .pos-warning-text {
        font-size: 12px;
        color: #a16207;
        margin: 0;
        line-height: 1.4;
    }

    /* Toggle */
    .pos-toggle-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--pos-space-md);
        background: var(--pos-slate-50);
        border-radius: var(--pos-radius-md);
    }

    .pos-toggle-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-600);
    }

    .pos-toggle-label i {
        color: var(--pos-slate-400);
    }

    .pos-toggle {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
        cursor: pointer;
    }

    .pos-toggle-input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .pos-toggle-slider {
        position: absolute;
        inset: 0;
        background: var(--pos-slate-300);
        border-radius: var(--pos-radius-full);
        transition: var(--pos-transition);
    }

    .pos-toggle-slider::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 22px;
        height: 22px;
        background: var(--pos-white);
        border-radius: var(--pos-radius-full);
        box-shadow: var(--pos-shadow-sm);
        transition: var(--pos-transition);
    }

    .pos-toggle-input:checked + .pos-toggle-slider {
        background: var(--pos-primary);
    }

    .pos-toggle-input:checked + .pos-toggle-slider::before {
        transform: translateX(22px);
    }

    /* Info Card */
    .pos-info-card {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: var(--pos-space-md);
        background: rgba(79, 70, 229, 0.08);
        border: 1px solid rgba(79, 70, 229, 0.2);
        border-radius: var(--pos-radius-md);
    }

    .pos-info-card i {
        font-size: 16px;
        color: var(--pos-primary);
        flex-shrink: 0;
    }

    .pos-info-card p {
        margin: 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--pos-primary-dark);
    }
</style>
@endpush
