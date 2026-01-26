{{-- Product Grid - Premium Apple/SaaS Style --}}
<div class="pos-products-grid">
    <div class="pos-products-container">
        @forelse($variants ?? [] as $variant)
            @include('pos.partials.product-card', ['variant' => $variant])
        @empty
            <div class="pos-empty-products">
                <div class="pos-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="pos-empty-title">No hay productos disponibles</p>
                <p class="pos-empty-subtitle">Agrega productos terminados al inventario</p>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
    .pos-products-grid {
        flex: 1;
        overflow-y: auto;
        padding: 20px 24px;
        background: #f1f5f9;
    }

    .pos-products-grid::-webkit-scrollbar {
        width: 8px;
    }

    .pos-products-grid::-webkit-scrollbar-track {
        background: transparent;
    }

    .pos-products-grid::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .pos-products-grid::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .pos-products-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
    }

    @media (min-width: 1280px) {
        .pos-products-container {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (min-width: 1536px) {
        .pos-products-container {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    .pos-empty-products {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 300px;
        text-align: center;
    }

    .pos-empty-icon {
        width: 80px;
        height: 80px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .pos-empty-icon svg {
        width: 100%;
        height: 100%;
    }

    .pos-empty-title {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 4px 0;
    }

    .pos-empty-subtitle {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }
</style>
@endpush
