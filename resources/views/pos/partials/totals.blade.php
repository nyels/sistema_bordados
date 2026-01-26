{{-- Totals - Premium Apple/SaaS Style --}}
<div class="pos-totals-section">

    {{-- Precio Final Manual --}}
    <div class="pos-input-group">
        <label for="unit-price-final" class="pos-input-label">Precio Final $:</label>
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
            <svg fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="pos-warning-content">
            <p id="price-warning-title" class="pos-warning-title"></p>
            <p id="price-warning-text" class="pos-warning-text"></p>
        </div>
    </div>

    {{-- Discount Reason --}}
    <div class="pos-input-group">
        <label for="discount-reason" class="pos-input-label">Motivo Desc.:</label>
        <input type="text"
               id="discount-reason"
               placeholder="Razón del descuento (opcional)..."
               maxlength="255"
               class="pos-input-text">
    </div>

    {{-- IVA Toggle --}}
    <div class="pos-toggle-group">
        <span class="pos-toggle-label">Aplicar IVA:</span>
        <label class="pos-toggle">
            <input type="checkbox" id="apply-iva" class="pos-toggle-input">
            <span class="pos-toggle-slider"></span>
        </label>
    </div>

    {{-- Info Card --}}
    <div class="pos-info-card">
        <svg class="pos-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p>El total será calculado por el servidor al confirmar.</p>
    </div>
</div>

@push('styles')
<style>
    .pos-totals-section {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .pos-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-input-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .pos-input-wrapper {
        display: flex;
        align-items: center;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .pos-input-wrapper:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .pos-input-prefix,
    .pos-input-suffix {
        padding: 0 12px;
        font-size: 15px;
        font-weight: 600;
        color: #64748b;
        background: #f8fafc;
    }

    .pos-input-prefix {
        border-right: 1px solid #e2e8f0;
    }

    .pos-input-suffix {
        border-left: 1px solid #e2e8f0;
        font-size: 12px;
    }

    .pos-input-number {
        flex: 1;
        height: 48px;
        padding: 0 12px;
        border: none;
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        background: transparent;
        outline: none;
    }

    .pos-input-number::placeholder {
        color: #cbd5e1;
        font-weight: 400;
    }

    .pos-input-text {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        color: #1e293b;
        transition: all 0.2s ease;
    }

    .pos-input-text:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .pos-input-text::placeholder {
        color: #94a3b8;
    }

    /* Price Warning */
    .pos-price-warning {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border-radius: 12px;
        background: #fef3c7;
        border: 1px solid #f59e0b;
    }

    .pos-price-warning.hidden {
        display: none;
    }

    .pos-warning-icon {
        width: 22px;
        height: 22px;
        color: #d97706;
        flex-shrink: 0;
    }

    .pos-warning-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-warning-content {
        flex: 1;
    }

    .pos-warning-title {
        font-size: 14px;
        font-weight: 700;
        color: #92400e;
        margin: 0 0 2px 0;
    }

    .pos-warning-text {
        font-size: 13px;
        color: #a16207;
        margin: 0;
        line-height: 1.4;
    }

    /* Toggle */
    .pos-toggle-group {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 12px;
    }

    .pos-toggle-label {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }

    .pos-toggle {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 28px;
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
        background: #e2e8f0;
        border-radius: 28px;
        transition: all 0.3s ease;
    }

    .pos-toggle-slider::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 24px;
        height: 24px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .pos-toggle-input:checked + .pos-toggle-slider {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .pos-toggle-input:checked + .pos-toggle-slider::before {
        transform: translateX(24px);
    }

    .pos-toggle-input:focus + .pos-toggle-slider {
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    }

    /* Info Card */
    .pos-info-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #93c5fd;
        border-radius: 12px;
    }

    .pos-info-icon {
        width: 20px;
        height: 20px;
        color: #3b82f6;
        flex-shrink: 0;
    }

    .pos-info-card p {
        margin: 0;
        font-size: 13px;
        font-weight: 500;
        color: #1e40af;
    }
</style>
@endpush
