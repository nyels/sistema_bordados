{{-- Payment Method - Premium Apple/SaaS Style --}}
<div class="pos-payment-section">
    <label class="pos-section-label">
        <svg class="pos-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        Método de Pago
    </label>
    <div class="pos-payment-grid">
        <button data-payment-method="efectivo"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn active">
            <div class="pos-payment-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span>EFECTIVO</span>
        </button>
        <button data-payment-method="tarjeta"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn">
            <div class="pos-payment-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <span>TARJETA</span>
        </button>
        <button data-payment-method="transferencia"
                onclick="togglePaymentMethod(this)"
                class="pos-payment-btn">
            <div class="pos-payment-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <span>TRANSFER</span>
        </button>
    </div>
</div>

@push('styles')
<style>
    .pos-payment-section {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .pos-payment-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .pos-payment-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 16px 12px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-payment-btn:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .pos-payment-btn.active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-color: #2563eb;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35);
    }

    .pos-payment-btn.active .pos-payment-icon {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .pos-payment-btn.active span {
        color: #fff;
    }

    .pos-payment-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        border-radius: 10px;
        color: #64748b;
        transition: all 0.2s ease;
    }

    .pos-payment-icon svg {
        width: 22px;
        height: 22px;
    }

    .pos-payment-btn span {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        letter-spacing: 0.3px;
    }

    @media (max-width: 480px) {
        .pos-payment-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .pos-payment-btn {
            padding: 12px 8px;
        }
        .pos-payment-icon {
            width: 36px;
            height: 36px;
        }
        .pos-payment-btn span {
            font-size: 10px;
        }
    }
</style>
@endpush
