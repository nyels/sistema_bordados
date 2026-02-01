{{-- Cart - Enterprise SaaS Design 2025 --}}
<div class="pos-cart-section">
    <div class="pos-cart-items" id="cart-items">
        {{-- Estado vacio --}}
        <div id="cart-empty" class="pos-cart-empty">
            <div class="pos-cart-empty-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <p class="pos-cart-empty-title">Carrito vacio</p>
            <p class="pos-cart-empty-subtitle">Selecciona un producto para agregar</p>
        </div>

        {{-- Item del carrito (se renderiza dinamicamente) --}}
        <div id="cart-item" class="hidden">
            {{-- Se llena por JavaScript --}}
        </div>
    </div>
</div>

@push('styles')
<style>
    .pos-cart-section {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        border-bottom: 1px solid var(--pos-slate-200);
    }

    .pos-cart-items {
        height: 100%;
        overflow-y: auto;
        padding: var(--pos-space-md) 0;
    }

    .pos-cart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 180px;
        text-align: center;
        padding: var(--pos-space-lg);
    }

    .pos-cart-empty-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-100);
        border-radius: var(--pos-radius-md);
        margin-bottom: var(--pos-space-md);
    }

    .pos-cart-empty-icon i {
        font-size: 24px;
        color: var(--pos-slate-400);
    }

    .pos-cart-empty-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--pos-slate-500);
        margin: 0 0 4px 0;
    }

    .pos-cart-empty-subtitle {
        font-size: 13px;
        color: var(--pos-slate-400);
        margin: 0;
    }

    /* Cart Item Styles (for JS rendering) */
    .pos-cart-item {
        display: flex;
        align-items: center;
        gap: var(--pos-space-md);
        padding: var(--pos-space-md);
        margin: 0 var(--pos-space-md);
        background: var(--pos-slate-50);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
    }

    .pos-cart-item-info {
        flex: 1;
        min-width: 0;
    }

    .pos-cart-item-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-800);
        margin: 0 0 4px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-cart-item-price {
        font-size: 13px;
        color: var(--pos-slate-500);
        margin: 0;
    }

    .pos-cart-item-quantity {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
    }

    .pos-qty-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-800);
        border: none;
        border-radius: var(--pos-radius-sm);
        color: var(--pos-white);
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-qty-btn:hover {
        background: var(--pos-slate-700);
    }

    .pos-qty-input {
        width: 52px;
        height: 36px;
        text-align: center;
        font-size: 15px;
        font-weight: 600;
        color: var(--pos-slate-800);
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-sm);
    }

    .pos-qty-input:focus {
        outline: none;
        border-color: var(--pos-primary);
    }

    .pos-remove-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-danger);
        border: none;
        border-radius: var(--pos-radius-sm);
        color: var(--pos-white);
        font-size: 14px;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-remove-btn:hover {
        background: var(--pos-danger-dark);
    }
</style>
@endpush
