{{-- Payment Method - Enterprise SaaS Design 2025 - Compact --}}
<div class="pos-payment-section">
    <label class="pos-payment-label">Método de pago</label>
    <div class="pos-payment-pills">
        <button data-payment-method="efectivo"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-pill active">
            <i class="fas fa-money-bill-wave"></i>
            <span>Efectivo</span>
        </button>
        <button data-payment-method="tarjeta"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-pill">
            <i class="fas fa-credit-card"></i>
            <span>Tarjeta</span>
        </button>
        <button data-payment-method="transferencia"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-pill">
            <i class="fas fa-university"></i>
            <span>Transfer</span>
        </button>
    </div>
</div>

@push('styles')
<style>
    .pos-payment-section {
        padding: var(--pos-space-sm) var(--pos-space-md);
        border-bottom: 1px solid var(--pos-slate-100);
        background: var(--pos-white);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-payment-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--pos-slate-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .pos-payment-pills {
        display: flex;
        gap: 6px;
        flex: 1;
    }

    .pos-payment-pill {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        background: var(--pos-slate-50);
        border: 1.5px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-full);
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-payment-pill:hover {
        border-color: var(--pos-slate-300);
        background: var(--pos-slate-100);
    }

    .pos-payment-pill.active {
        background: var(--pos-primary);
        border-color: var(--pos-primary);
        color: var(--pos-white);
    }

    .pos-payment-pill i {
        font-size: 12px;
        color: var(--pos-slate-500);
    }

    .pos-payment-pill.active i {
        color: var(--pos-white);
    }

    .pos-payment-pill span {
        font-size: 11px;
        font-weight: 600;
        color: var(--pos-slate-600);
    }

    .pos-payment-pill.active span {
        color: var(--pos-white);
    }

    @media (max-width: 400px) {
        .pos-payment-pill span {
            display: none;
        }
        .pos-payment-pill {
            padding: 8px 14px;
        }
    }
</style>
@endpush
