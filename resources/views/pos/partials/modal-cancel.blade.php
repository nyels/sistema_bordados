{{-- Modal Cancel Cart (limpiar carrito) --}}
<div id="modal-cancel" class="pos-modal hidden">
    <div class="pos-modal-backdrop"></div>
    <div class="pos-modal-container">
        <div class="pos-modal-content">
            {{-- Header --}}
            <div class="pos-modal-header pos-modal-header-danger">
                <h2 class="pos-modal-title">LIMPIAR CARRITO</h2>
            </div>

            {{-- Body --}}
            <div class="pos-modal-body">
                <div class="pos-modal-icon pos-modal-icon-danger">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <p class="pos-modal-text-primary">Se eliminara el producto del carrito.</p>
                <p class="pos-modal-text-secondary">Esta accion no se puede deshacer.</p>
            </div>

            {{-- Footer --}}
            <div class="pos-modal-footer">
                <div class="pos-modal-buttons">
                    <button onclick="closeModal('modal-cancel')" class="pos-modal-btn pos-modal-btn-secondary">
                        VOLVER
                    </button>
                    <button id="btn-clear-cart" class="pos-modal-btn pos-modal-btn-danger">
                        SI, LIMPIAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Result (para venta exitosa/error) --}}
<div id="modal-result" class="pos-modal hidden">
    <div class="pos-modal-backdrop"></div>
    <div class="pos-modal-container">
        <div class="pos-modal-content">
            {{-- Header dinamico --}}
            <div id="modal-result-header" class="pos-modal-header pos-modal-header-success">
                <h2 id="modal-result-title" class="pos-modal-title">RESULTADO</h2>
            </div>

            {{-- Body dinamico --}}
            <div id="modal-result-content" class="pos-modal-body">
                {{-- Contenido inyectado por JS --}}
            </div>

            {{-- Footer --}}
            <div id="modal-result-footer" class="pos-modal-footer">
                <button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full">
                    CERRAR
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* =====================================================================
       POS MODAL SYSTEM - Sin dependencias de Tailwind
       ===================================================================== */

    .pos-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pos-modal.hidden {
        display: none !important;
    }

    .pos-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
    }

    .pos-modal-container {
        position: relative;
        width: 100%;
        max-width: 420px;
        margin: 0 16px;
        z-index: 1;
    }

    .pos-modal-content {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
    }

    /* Headers */
    .pos-modal-header {
        padding: 20px 24px;
        border-bottom: 3px solid #000;
    }

    .pos-modal-header-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .pos-modal-header-success {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }

    .pos-modal-header-error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .pos-modal-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.3px;
    }

    /* Body */
    .pos-modal-body {
        padding: 32px 24px;
        text-align: center;
    }

    .pos-modal-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
    }

    .pos-modal-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-modal-icon-danger {
        color: #ef4444;
    }

    .pos-modal-icon-success {
        color: #22c55e;
    }

    .pos-modal-text-primary {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
    }

    .pos-modal-text-secondary {
        margin: 0;
        font-size: 16px;
        color: #64748b;
    }

    /* Footer */
    .pos-modal-footer {
        padding: 20px 24px;
        background: #f8fafc;
        border-top: 3px solid #e2e8f0;
    }

    .pos-modal-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    /* Buttons */
    .pos-modal-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 52px;
        padding: 0 20px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 3px solid;
    }

    .pos-modal-btn-primary {
        background: #1e293b;
        border-color: #1e293b;
        color: #fff;
    }

    .pos-modal-btn-primary:hover {
        background: #334155;
        border-color: #334155;
    }

    .pos-modal-btn-secondary {
        background: #fff;
        border-color: #1e293b;
        color: #1e293b;
    }

    .pos-modal-btn-secondary:hover {
        background: #1e293b;
        color: #fff;
    }

    .pos-modal-btn-danger {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }

    .pos-modal-btn-danger:hover {
        background: #dc2626;
        border-color: #dc2626;
    }

    .pos-modal-btn-full {
        width: 100%;
    }

    /* Result modal specific styles */
    #modal-result .pos-modal-body {
        min-height: 150px;
    }

    /* Success icon animation */
    @keyframes checkmark {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); opacity: 1; }
    }

    .pos-result-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        animation: checkmark 0.4s ease-out;
    }

    .pos-result-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-result-icon-success {
        color: #22c55e;
    }

    .pos-result-icon-error {
        color: #ef4444;
    }

    .pos-result-title {
        margin: 0 0 16px 0;
        font-size: 24px;
        font-weight: 700;
    }

    .pos-result-title-success {
        color: #16a34a;
    }

    .pos-result-title-error {
        color: #dc2626;
    }

    .pos-result-details {
        text-align: left;
        background: #f8fafc;
        padding: 16px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
    }

    .pos-result-details p {
        margin: 0 0 8px 0;
        font-size: 15px;
        color: #1e293b;
    }

    .pos-result-details p:last-child {
        margin-bottom: 0;
    }

    .pos-result-details strong {
        font-weight: 600;
    }

    .pos-result-total {
        font-size: 18px !important;
        font-weight: 700 !important;
        padding-top: 12px;
        margin-top: 12px !important;
        border-top: 2px solid #e2e8f0;
    }
</style>
@endpush
