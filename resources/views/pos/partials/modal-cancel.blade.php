{{-- Modal Cancel Cart - Enterprise SaaS Design 2025 --}}
<div id="modal-cancel" class="pos-modal hidden">
    <div class="pos-modal-backdrop"></div>
    <div class="pos-modal-container">
        <div class="pos-modal-content">
            {{-- Header --}}
            <div class="pos-modal-header pos-modal-header-danger">
                <i class="fas fa-trash-alt"></i>
                <h2 class="pos-modal-title">LIMPIAR CARRITO</h2>
            </div>

            {{-- Body --}}
            <div class="pos-modal-body">
                <div class="pos-modal-icon pos-modal-icon-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="pos-modal-text-primary">Se eliminara el producto del carrito.</p>
                <p class="pos-modal-text-secondary">Esta accion no se puede deshacer.</p>
            </div>

            {{-- Footer --}}
            <div class="pos-modal-footer">
                <div class="pos-modal-buttons">
                    <button onclick="closeModal('modal-cancel')" class="pos-modal-btn pos-modal-btn-secondary">
                        <i class="fas fa-arrow-left"></i> VOLVER
                    </button>
                    <button id="btn-clear-cart" class="pos-modal-btn pos-modal-btn-danger">
                        <i class="fas fa-trash"></i> LIMPIAR
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
                <i class="fas fa-check-circle"></i>
                <h2 id="modal-result-title" class="pos-modal-title">RESULTADO</h2>
            </div>

            {{-- Body dinamico --}}
            <div id="modal-result-content" class="pos-modal-body">
                {{-- Contenido inyectado por JS --}}
            </div>

            {{-- Footer --}}
            <div id="modal-result-footer" class="pos-modal-footer">
                <button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full">
                    <i class="fas fa-times"></i> CERRAR
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* =====================================================================
       POS MODAL SYSTEM - Enterprise Design 2025
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
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(4px);
    }

    .pos-modal-container {
        position: relative;
        width: 100%;
        max-width: 420px;
        margin: 0 var(--pos-space-md);
        z-index: 1;
        animation: scaleIn 0.2s ease;
    }

    .pos-modal-content {
        background: var(--pos-white);
        border-radius: var(--pos-radius-lg);
        overflow: hidden;
        box-shadow: var(--pos-shadow-xl);
    }

    /* Headers */
    .pos-modal-header {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: var(--pos-space-md) var(--pos-space-lg);
    }

    .pos-modal-header i {
        font-size: 20px;
    }

    .pos-modal-header-danger {
        background: linear-gradient(135deg, var(--pos-danger) 0%, var(--pos-danger-dark) 100%);
        color: var(--pos-white);
    }

    .pos-modal-header-success {
        background: linear-gradient(135deg, var(--pos-success) 0%, var(--pos-success-dark) 100%);
        color: var(--pos-white);
    }

    .pos-modal-header-error {
        background: linear-gradient(135deg, var(--pos-danger) 0%, var(--pos-danger-dark) 100%);
        color: var(--pos-white);
    }

    .pos-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--pos-white);
        letter-spacing: 0.3px;
    }

    /* Body */
    .pos-modal-body {
        padding: var(--pos-space-lg);
        text-align: center;
    }

    .pos-modal-icon {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--pos-space-md);
        border-radius: var(--pos-radius-full);
    }

    .pos-modal-icon i {
        font-size: 36px;
    }

    .pos-modal-icon-danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--pos-danger);
    }

    .pos-modal-icon-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--pos-success);
    }

    .pos-modal-text-primary {
        margin: 0 0 var(--pos-space-sm) 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--pos-slate-800);
    }

    .pos-modal-text-secondary {
        margin: 0;
        font-size: 14px;
        color: var(--pos-slate-500);
    }

    /* Footer */
    .pos-modal-footer {
        padding: var(--pos-space-md) var(--pos-space-lg);
        background: var(--pos-slate-50);
        border-top: 1px solid var(--pos-slate-200);
    }

    .pos-modal-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--pos-space-sm);
    }

    /* Buttons */
    .pos-modal-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--pos-space-sm);
        height: 48px;
        padding: 0 var(--pos-space-md);
        border-radius: var(--pos-radius-md);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--pos-transition);
        border: 2px solid;
    }

    .pos-modal-btn i {
        font-size: 14px;
    }

    .pos-modal-btn-primary {
        background: var(--pos-slate-800);
        border-color: var(--pos-slate-800);
        color: var(--pos-white);
    }

    .pos-modal-btn-primary:hover {
        background: var(--pos-slate-700);
        border-color: var(--pos-slate-700);
    }

    .pos-modal-btn-secondary {
        background: var(--pos-white);
        border-color: var(--pos-slate-300);
        color: var(--pos-slate-600);
    }

    .pos-modal-btn-secondary:hover {
        background: var(--pos-slate-100);
        border-color: var(--pos-slate-400);
    }

    .pos-modal-btn-danger {
        background: var(--pos-danger);
        border-color: var(--pos-danger);
        color: var(--pos-white);
    }

    .pos-modal-btn-danger:hover {
        background: var(--pos-danger-dark);
        border-color: var(--pos-danger-dark);
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
        50% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }

    .pos-result-icon {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--pos-space-md);
        animation: checkmark 0.4s ease-out;
        border-radius: var(--pos-radius-full);
    }

    .pos-result-icon i {
        font-size: 36px;
    }

    .pos-result-icon-success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--pos-success);
    }

    .pos-result-icon-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--pos-danger);
    }

    .pos-result-title {
        margin: 0 0 var(--pos-space-md) 0;
        font-size: 20px;
        font-weight: 700;
    }

    .pos-result-title-success {
        color: var(--pos-success-dark);
    }

    .pos-result-title-error {
        color: var(--pos-danger-dark);
    }

    .pos-result-details {
        text-align: left;
        background: var(--pos-slate-50);
        padding: var(--pos-space-md);
        border-radius: var(--pos-radius-md);
        border: 1px solid var(--pos-slate-200);
    }

    .pos-result-details p {
        margin: 0 0 var(--pos-space-sm) 0;
        font-size: 14px;
        color: var(--pos-slate-700);
    }

    .pos-result-details p:last-child {
        margin-bottom: 0;
    }

    .pos-result-details strong {
        font-weight: 600;
        color: var(--pos-slate-800);
    }

    .pos-result-total {
        font-size: 16px !important;
        font-weight: 700 !important;
        padding-top: var(--pos-space-sm);
        margin-top: var(--pos-space-sm) !important;
        border-top: 1px solid var(--pos-slate-200);
        color: var(--pos-primary) !important;
    }
</style>
@endpush
