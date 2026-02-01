{{-- Action Buttons - Enterprise SaaS Design 2025 --}}
<div class="pos-actions-section">
    {{-- ACCION PRINCIPAL: CONFIRMAR --}}
    <button onclick="openModal('modal-confirm')" class="pos-btn-confirm">
        <i class="fas fa-check-circle"></i>
        <span>CONFIRMAR VENTA</span>
    </button>

    {{-- ACCION SECUNDARIA: LIMPIAR --}}
    <button onclick="openModal('modal-cancel')" class="pos-btn-clear">
        <i class="fas fa-trash-alt"></i>
        <span>Limpiar carrito</span>
    </button>
</div>

@push('styles')
<style>
    .pos-actions-section {
        padding: var(--pos-space-md);
        display: flex;
        flex-direction: column;
        gap: var(--pos-space-sm);
        background: var(--pos-slate-50);
    }

    .pos-btn-confirm {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--pos-space-sm);
        width: 100%;
        height: 56px;
        background: linear-gradient(135deg, var(--pos-success) 0%, var(--pos-success-dark) 100%);
        border: none;
        border-radius: var(--pos-radius-md);
        color: var(--pos-white);
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.3px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: var(--pos-transition);
    }

    .pos-btn-confirm i {
        font-size: 18px;
    }

    .pos-btn-confirm:hover {
        background: linear-gradient(135deg, var(--pos-success-dark) 0%, #047857 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .pos-btn-confirm:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .pos-btn-clear {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--pos-space-sm);
        width: 100%;
        height: 44px;
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        color: var(--pos-slate-500);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-btn-clear i {
        font-size: 14px;
    }

    .pos-btn-clear:hover {
        border-color: var(--pos-danger);
        color: var(--pos-danger);
        background: rgba(239, 68, 68, 0.05);
    }
</style>
@endpush
