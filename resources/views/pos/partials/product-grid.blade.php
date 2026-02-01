{{-- Product Grid - Enterprise SaaS Design 2025 --}}
<div class="pos-products-grid">
    <div class="pos-products-container">
        @forelse($variants ?? [] as $variant)
            @include('pos.partials.product-card', ['variant' => $variant])
        @empty
            <div class="pos-empty-products">
                <div class="pos-empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3 class="pos-empty-title">No hay productos disponibles</h3>
                <p class="pos-empty-subtitle">Agrega productos terminados al inventario para comenzar a vender</p>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
    .pos-products-grid {
        flex: 1;
        overflow-y: auto;
        padding: var(--pos-space-lg);
        background: var(--pos-slate-100);
    }

    .pos-products-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: var(--pos-space-md);
    }

    @media (min-width: 1280px) {
        .pos-products-container {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 1536px) {
        .pos-products-container {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .pos-empty-products {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        text-align: center;
        padding: var(--pos-space-2xl);
    }

    .pos-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100px;
        height: 100px;
        background: var(--pos-slate-200);
        border-radius: var(--pos-radius-xl);
        margin-bottom: var(--pos-space-lg);
    }

    .pos-empty-icon i {
        font-size: 42px;
        color: var(--pos-slate-400);
    }

    .pos-empty-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--pos-slate-700);
        margin: 0 0 var(--pos-space-sm) 0;
    }

    .pos-empty-subtitle {
        font-size: 15px;
        color: var(--pos-slate-500);
        margin: 0;
        max-width: 300px;
    }
</style>
@endpush
