{{-- Action Buttons - Premium Apple/SaaS Style --}}
<div class="pos-actions-section">
    {{-- ACCIÓN PRINCIPAL: CONFIRMAR --}}
    <button onclick="openModal('modal-confirm')" class="pos-btn-confirm">
        <svg class="pos-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                  d="M5 13l4 4L19 7"/>
        </svg>
        <span>CONFIRMAR VENTA</span>
    </button>

    {{-- ACCIÓN SECUNDARIA: LIMPIAR --}}
    <button onclick="openModal('modal-cancel')" class="pos-btn-clear">
        <svg class="pos-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        Limpiar carrito
    </button>
</div>

@push('styles')
<style>
    .pos-actions-section {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    }

    .pos-btn-confirm {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        height: 60px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: none;
        border-radius: 16px;
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0.3px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        transition: all 0.2s ease;
    }

    .pos-btn-confirm:hover {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .pos-btn-confirm:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(34, 197, 94, 0.3);
    }

    .pos-btn-icon {
        width: 26px;
        height: 26px;
    }

    .pos-btn-clear {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        height: 46px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-btn-clear:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: #fef2f2;
    }

    .pos-btn-icon-sm {
        width: 18px;
        height: 18px;
    }
</style>
@endpush
