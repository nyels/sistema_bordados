{{-- Cart - Premium Apple/SaaS Style --}}
<div class="pos-cart-section">
    <div class="pos-cart-items" id="cart-items">
        {{-- Estado vacío --}}
        <div id="cart-empty" class="pos-cart-empty">
            <div class="pos-cart-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="pos-cart-empty-title">Carrito vacío</p>
            <p class="pos-cart-empty-subtitle">Selecciona un producto para agregar</p>
        </div>

        {{-- Item del carrito (se renderiza dinámicamente) --}}
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
        border-bottom: 1px solid #e2e8f0;
    }

    .pos-cart-items {
        height: 100%;
        overflow-y: auto;
        padding: 16px 0;
    }

    .pos-cart-items::-webkit-scrollbar {
        width: 6px;
    }

    .pos-cart-items::-webkit-scrollbar-track {
        background: transparent;
    }

    .pos-cart-items::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 3px;
    }

    .pos-cart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 200px;
        text-align: center;
        padding: 24px;
    }

    .pos-cart-empty-icon {
        width: 64px;
        height: 64px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    .pos-cart-empty-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-cart-empty-title {
        font-size: 16px;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 4px 0;
    }

    .pos-cart-empty-subtitle {
        font-size: 13px;
        color: #cbd5e1;
        margin: 0;
    }
</style>
@endpush
