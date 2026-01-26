{{-- Modal Confirm Sale --}}
<div id="modal-confirm" class="pos-modal hidden">
    <div class="pos-modal-backdrop"></div>
    <div class="pos-modal-container" style="max-width: 520px;">
        <div class="pos-modal-content">
            {{-- Header --}}
            <div class="pos-modal-header pos-modal-header-success">
                <h2 class="pos-modal-title">CONFIRMAR VENTA</h2>
            </div>

            {{-- Body --}}
            <div class="pos-modal-body pos-modal-body-left" style="max-height: 400px; overflow-y: auto;">
                {{-- Resumen dinámico --}}
                <div id="modal-confirm-content">
                    <p class="pos-modal-text-secondary">Cargando...</p>
                </div>

                {{-- ALERTA DE PRECIO DE RIESGO (R01/R02) --}}
                <div id="modal-price-alert" class="pos-price-alert hidden">
                    <div class="pos-price-alert-header">
                        <svg class="pos-price-alert-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
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
                        VOLVER
                    </button>
                    <button id="btn-execute-sale" class="pos-modal-btn pos-modal-btn-success">
                        EJECUTAR VENTA
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
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-color: #22c55e;
        color: #fff;
    }

    .pos-modal-btn-success:hover {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        border-color: #16a34a;
    }

    .pos-modal-btn-success:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #9ca3af;
        border-color: #9ca3af;
    }

    /* Price alert */
    .pos-price-alert {
        margin-top: 16px;
        padding: 16px;
        border-radius: 12px;
        border: 3px solid #ef4444;
        background: #fef2f2;
    }

    .pos-price-alert.hidden {
        display: none;
    }

    .pos-price-alert-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .pos-price-alert-icon {
        width: 32px;
        height: 32px;
        color: #ef4444;
        flex-shrink: 0;
    }

    .pos-price-alert-content {
        flex: 1;
    }

    .pos-price-alert-title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #b91c1c;
    }

    .pos-price-alert-text {
        margin: 4px 0 0 0;
        font-size: 14px;
        color: #dc2626;
    }

    .pos-price-alert-checkbox {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #fca5a5;
    }

    .pos-checkbox-label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .pos-checkbox {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid #ef4444;
        accent-color: #ef4444;
    }

    .pos-checkbox-text {
        font-size: 14px;
        font-weight: 600;
        color: #b91c1c;
    }

    /* Confirm content styling */
    #modal-confirm-content {
        font-size: 15px;
        line-height: 1.6;
    }

    #modal-confirm-content strong {
        font-weight: 600;
    }
</style>
@endpush
