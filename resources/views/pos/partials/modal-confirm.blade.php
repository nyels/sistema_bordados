{{-- Modal Confirm Sale - Enterprise SaaS Design 2025 --}}
<div id="modal-confirm" class="pos-modal hidden">
    <div class="pos-modal-backdrop"></div>
    <div class="pos-modal-container" style="max-width: 520px;">
        <div class="pos-modal-content">
            {{-- Header --}}
            <div class="pos-modal-header pos-modal-header-success">
                <i class="fas fa-check-circle"></i>
                <h2 class="pos-modal-title">CONFIRMAR VENTA</h2>
            </div>

            {{-- Body --}}
            <div class="pos-modal-body pos-modal-body-left" style="max-height: 400px; overflow-y: auto;">
                {{-- Resumen dinamico --}}
                <div id="modal-confirm-content">
                    <p class="pos-modal-text-secondary">Cargando...</p>
                </div>

                {{-- ALERTA DE PRECIO DE RIESGO (R01/R02) --}}
                <div id="modal-price-alert" class="pos-price-alert hidden">
                    <div class="pos-price-alert-header">
                        <i class="fas fa-exclamation-triangle pos-price-alert-icon"></i>
                        <div class="pos-price-alert-content">
                            <p id="modal-alert-title" class="pos-price-alert-title"></p>
                            <p id="modal-alert-text" class="pos-price-alert-text"></p>
                        </div>
                    </div>
                    <div class="pos-price-alert-checkbox">
                        <label class="pos-checkbox-label">
                            <input type="checkbox" id="confirm-price-checkbox" class="pos-checkbox">
                            <span id="confirm-price-label" class="pos-checkbox-text"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="pos-modal-footer">
                <div class="pos-modal-buttons">
                    <button onclick="closeModal('modal-confirm')" class="pos-modal-btn pos-modal-btn-secondary">
                        <i class="fas fa-arrow-left"></i> VOLVER
                    </button>
                    <button id="btn-execute-sale" class="pos-modal-btn pos-modal-btn-success">
                        <i class="fas fa-check"></i> EJECUTAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Confirm modal specific */
    .pos-modal-body-left {
        text-align: left;
    }

    .pos-modal-btn-success {
        background: linear-gradient(135deg, var(--pos-success) 0%, var(--pos-success-dark) 100%);
        border-color: var(--pos-success);
        color: var(--pos-white);
    }

    .pos-modal-btn-success:hover {
        background: linear-gradient(135deg, var(--pos-success-dark) 0%, #047857 100%);
        border-color: var(--pos-success-dark);
    }

    .pos-modal-btn-success:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: var(--pos-slate-400);
        border-color: var(--pos-slate-400);
    }

    /* Price alert */
    .pos-price-alert {
        margin-top: var(--pos-space-md);
        padding: var(--pos-space-md);
        border-radius: var(--pos-radius-md);
        border: 2px solid var(--pos-danger);
        background: rgba(239, 68, 68, 0.08);
    }

    .pos-price-alert.hidden {
        display: none;
    }

    .pos-price-alert-header {
        display: flex;
        align-items: flex-start;
        gap: var(--pos-space-sm);
    }

    .pos-price-alert-icon {
        font-size: 24px;
        color: var(--pos-danger);
        flex-shrink: 0;
    }

    .pos-price-alert-content {
        flex: 1;
    }

    .pos-price-alert-title {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #b91c1c;
    }

    .pos-price-alert-text {
        margin: 4px 0 0 0;
        font-size: 13px;
        color: var(--pos-danger);
    }

    .pos-price-alert-checkbox {
        margin-top: var(--pos-space-md);
        padding-top: var(--pos-space-md);
        border-top: 1px solid rgba(239, 68, 68, 0.3);
    }

    .pos-checkbox-label {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        cursor: pointer;
    }

    .pos-checkbox {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid var(--pos-danger);
        accent-color: var(--pos-danger);
    }

    .pos-checkbox-text {
        font-size: 13px;
        font-weight: 600;
        color: #b91c1c;
    }

    /* Confirm content styling */
    #modal-confirm-content {
        font-size: 14px;
        line-height: 1.6;
    }

    #modal-confirm-content strong {
        font-weight: 600;
    }
</style>
@endpush
