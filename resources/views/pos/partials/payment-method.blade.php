{{-- Payment Method - Enterprise SaaS Design 2025 --}}
<div class="pos-payment-section">
    <label class="pos-section-label">
        <i class="fas fa-credit-card"></i>
        <span>METODO DE PAGO</span>
    </label>
    <div class="pos-payment-grid">
        <button data-payment-method="efectivo"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn active">
            <div class="pos-payment-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <span>Efectivo</span>
        </button>
        <button data-payment-method="tarjeta"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn">
            <div class="pos-payment-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <span>Tarjeta</span>
        </button>
        <button data-payment-method="transferencia"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn">
            <div class="pos-payment-icon">
                <i class="fas fa-university"></i>
            </div>
            <span>Transfer</span>
        </button>
    </div>
</div>

@push('styles')
<style>
    .pos-payment-section {
        padding: var(--pos-space-md);
        border-bottom: 1px solid var(--pos-slate-200);
        background: var(--pos-white);
    }

    .pos-section-label {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        font-size: 12px;
        font-weight: 700;
        color: var(--pos-slate-500);
        letter-spacing: 0.05em;
        margin-bottom: var(--pos-space-md);
    }

    .pos-section-label i {
        font-size: 14px;
        color: var(--pos-slate-400);
    }

    .pos-payment-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--pos-space-sm);
    }

    .pos-payment-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: var(--pos-space-md) var(--pos-space-sm);
        background: var(--pos-slate-50);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-payment-btn:hover {
        border-color: var(--pos-slate-300);
        background: var(--pos-slate-100);
    }

    .pos-payment-btn.active {
        background: var(--pos-primary);
        border-color: var(--pos-primary);
    }

    .pos-payment-btn.active .pos-payment-icon {
        background: rgba(255, 255, 255, 0.2);
        color: var(--pos-white);
    }

    .pos-payment-btn.active span {
        color: var(--pos-white);
    }

    .pos-payment-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-200);
        border-radius: var(--pos-radius-sm);
        color: var(--pos-slate-500);
        transition: var(--pos-transition);
    }

    .pos-payment-icon i {
        font-size: 16px;
    }

    .pos-payment-btn span {
        font-size: 12px;
        font-weight: 600;
        color: var(--pos-slate-600);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    @media (max-width: 480px) {
        .pos-payment-btn {
            padding: var(--pos-space-sm);
        }
        .pos-payment-icon {
            width: 34px;
            height: 34px;
        }
        .pos-payment-btn span {
            font-size: 10px;
        }
    }
</style>
@endpush
