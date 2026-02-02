{{-- Cart - Enterprise SaaS Design 2025 --}}
<div class="pos-cart-section">
    <div class="pos-cart-header">
        <i class="fas fa-shopping-cart"></i>
        <span>CARRITO</span>
        <span class="pos-cart-badge" id="cart-badge">0</span>
    </div>
    <div class="pos-cart-items" id="cart-items">
        {{-- Estado vacio --}}
        <div id="cart-empty" class="pos-cart-empty">
            <div class="pos-cart-empty-icon">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <p class="pos-cart-empty-title">Carrito vacío</p>
            <p class="pos-cart-empty-subtitle">Haz clic en un producto para agregar</p>
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
        min-height: 180px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-bottom: 1px solid var(--pos-slate-100);
        background: var(--pos-slate-50);
    }

    .pos-cart-header {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px var(--pos-space-md);
        font-size: 11px;
        font-weight: 700;
        color: var(--pos-slate-400);
        letter-spacing: 0.05em;
        background: var(--pos-white);
        border-bottom: 1px solid var(--pos-slate-100);
    }

    .pos-cart-header i {
        font-size: 12px;
        color: var(--pos-primary);
    }

    .pos-cart-badge {
        margin-left: auto;
        padding: 2px 8px;
        background: var(--pos-primary);
        color: var(--pos-white);
        border-radius: var(--pos-radius-full);
        font-size: 10px;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
    }

    .pos-cart-badge:empty,
    .pos-cart-badge[data-count="0"] {
        background: var(--pos-slate-300);
    }

    .pos-cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .pos-cart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 100px;
        text-align: center;
        padding: var(--pos-space-md);
    }

    .pos-cart-empty-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        margin-bottom: 8px;
    }

    .pos-cart-empty-icon i {
        font-size: 18px;
        color: var(--pos-slate-400);
    }

    .pos-cart-empty-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--pos-slate-500);
        margin: 0 0 2px 0;
    }

    .pos-cart-empty-subtitle {
        font-size: 11px;
        color: var(--pos-slate-400);
        margin: 0;
    }

    /* Cart Item Styles están en index.blade.php */
</style>
@endpush
