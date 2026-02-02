{{-- Action Buttons - Enterprise SaaS Design 2025 - Compact --}}
<div class="pos-actions-section">
    {{-- ACCION PRINCIPAL: CONFIRMAR --}}
    <button onclick="openModal('modal-confirm')" class="pos-btn-confirm">
        <i class="fas fa-check-circle"></i>
        <span>CONFIRMAR VENTA</span>
    </button>

    {{-- ACCION SECUNDARIA: LIMPIAR --}}
    <button onclick="openModal('modal-cancel')" class="pos-btn-clear">
        <i class="fas fa-trash-alt"></i>
        <span>Limpiar</span>
    </button>
</div>

@push('styles')
<style>
    .pos-actions-section {
        padding: var(--pos-space-sm) var(--pos-space-md) var(--pos-space-md);
        display: flex;
        gap: 10px;
        background: var(--pos-white);
        border-top: 1px solid var(--pos-slate-100);
        margin-top: auto;
    }

    .pos-btn-confirm {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 44px;
        background: linear-gradient(135deg, var(--pos-success) 0%, var(--pos-success-dark) 100%);
        border: none;
        border-radius: var(--pos-radius-md);
        color: var(--pos-white);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.3px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        transition: var(--pos-transition);
    }

    .pos-btn-confirm i {
        font-size: 14px;
    }

    .pos-btn-confirm:hover {
        background: linear-gradient(135deg, var(--pos-success-dark) 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }

    .pos-btn-confirm:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
    }

    .pos-btn-clear {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 18px;
        height: 44px;
        background: var(--pos-slate-100);
        border: 2px solid var(--pos-slate-300);
        border-radius: var(--pos-radius-md);
        color: var(--pos-slate-600);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--pos-transition);
        white-space: nowrap;
    }

    .pos-btn-clear i {
        font-size: 13px;
        color: var(--pos-danger);
    }

    .pos-btn-clear:hover {
        border-color: var(--pos-danger);
        color: var(--pos-danger);
        background: rgba(239, 68, 68, 0.1);
    }

    .pos-btn-clear:active {
        transform: scale(0.98);
    }
</style>
@endpush
