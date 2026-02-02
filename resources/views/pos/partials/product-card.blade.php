{{-- Product Card - Enterprise SaaS Design 2025 --}}
@php
    $product = $variant->product ?? null;
    $productName = $product ? $product->name : 'Producto';
    $variantName = $variant->attributes_display ?? $variant->name ?? '';
    $fullName = $productName . ($variantName ? ' - ' . $variantName : '');
    $sku = $variant->sku_variant ?? $variant->sku ?? '';
    $price = $variant->sale_price ?? $variant->price ?? 0;
    $stock = $variant->stock_finished ?? $variant->stock ?? 0;
    $hasStock = $stock > 0;
    $imageUrl = $product?->primary_image_url ?? null;
@endphp

<div class="pos-product-card {{ $hasStock ? 'has-stock' : 'no-stock' }}"
     data-product-card
     data-product-name="{{ $fullName }}"
     data-product-sku="{{ $sku }}"
     @if($hasStock)
     onclick="addProduct('{{ $variant->id }}', '{{ str_replace(["'", '"'], ["\\'", '&quot;'], $fullName) }}', '{{ $price }}', '{{ $imageUrl }}', '{{ $stock }}')"
     data-add-product
     data-variant-id="{{ $variant->id }}"
     data-name="{{ $fullName }}"
     data-price="{{ $price }}"
     data-stock="{{ $stock }}"
     @endif>

    {{-- Image --}}
    <div class="pos-product-image">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $productName }}">
        @else
            <div class="pos-product-placeholder">
                <i class="fas fa-box"></i>
            </div>
        @endif

        {{-- Stock Badge --}}
        <div class="pos-product-badge {{ $hasStock ? 'in-stock' : 'out-stock' }}">
            <i class="fas {{ $hasStock ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
            {{ $hasStock ? $stock . ' disp.' : 'Agotado' }}
        </div>

        {{-- Hover Overlay (visual only) --}}
        @if($hasStock)
        <div class="pos-product-overlay">
            <div class="pos-product-add-icon">
                <i class="fas fa-plus"></i>
            </div>
            <span>Agregar al carrito</span>
        </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="pos-product-info">
        <h3 class="pos-product-name">{{ strtoupper($productName) }}</h3>
        @if($variantName)
            <p class="pos-product-variant">{{ $variantName }}</p>
        @endif
        <div class="pos-product-price">
            ${{ number_format($price, 2) }}
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .pos-product-card {
        position: relative;
        background: var(--pos-white);
        border-radius: var(--pos-radius-lg);
        overflow: hidden;
        box-shadow: var(--pos-shadow-md);
        border: 2px solid var(--pos-slate-200);
        transition: var(--pos-transition);
    }

    .pos-product-card.has-stock {
        cursor: pointer;
    }

    .pos-product-card.has-stock:hover {
        transform: translateY(-4px);
        box-shadow: var(--pos-shadow-xl);
        border-color: var(--pos-primary);
    }

    .pos-product-card.has-stock:active {
        transform: translateY(-2px);
    }

    .pos-product-card.no-stock {
        opacity: 0.6;
        border-color: var(--pos-slate-300);
    }

    .pos-product-image {
        position: relative;
        height: 160px;
        background: linear-gradient(145deg, var(--pos-slate-50) 0%, var(--pos-slate-100) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .pos-product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: var(--pos-space-sm);
        transition: transform 0.3s ease;
    }

    .pos-product-card.has-stock:hover .pos-product-image img {
        transform: scale(1.05);
    }

    .pos-product-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        height: 72px;
        background: var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
    }

    .pos-product-placeholder i {
        font-size: 28px;
        color: var(--pos-slate-400);
    }

    .pos-product-badge {
        position: absolute;
        top: var(--pos-space-sm);
        right: var(--pos-space-sm);
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: var(--pos-radius-full);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .pos-product-badge i {
        font-size: 10px;
    }

    .pos-product-badge.in-stock {
        background: var(--pos-success);
        color: var(--pos-white);
    }

    .pos-product-badge.out-stock {
        background: var(--pos-danger);
        color: var(--pos-white);
    }

    .pos-product-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.95) 0%, rgba(67, 56, 202, 0.95) 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--pos-space-sm);
        opacity: 0;
        cursor: pointer;
        border: none;
        transition: opacity 0.2s ease;
    }

    .pos-product-card.has-stock:hover .pos-product-overlay {
        opacity: 1;
    }

    .pos-product-add-icon {
        width: 52px;
        height: 52px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: var(--pos-radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease;
    }

    .pos-product-add-icon i {
        font-size: 22px;
        color: var(--pos-white);
    }

    .pos-product-overlay:hover .pos-product-add-icon {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 0.3);
    }

    .pos-product-overlay span {
        color: var(--pos-white);
        font-size: 14px;
        font-weight: 600;
    }

    .pos-product-info {
        padding: var(--pos-space-md);
        text-align: center;
        background: var(--pos-white);
        border-top: 1px solid var(--pos-slate-100);
    }

    .pos-product-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--pos-slate-800);
        line-height: 1.4;
        margin: 0 0 4px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .pos-product-variant {
        font-size: 12px;
        color: var(--pos-slate-500);
        margin: 0 0 var(--pos-space-sm) 0;
        font-weight: 500;
    }

    .pos-product-price {
        font-size: 20px;
        font-weight: 800;
        color: var(--pos-primary);
        letter-spacing: -0.5px;
    }

    /* Touch devices */
    @media (hover: none) {
        .pos-product-overlay {
            opacity: 0;
        }
        .pos-product-card.has-stock:active .pos-product-overlay {
            opacity: 1;
        }
    }
</style>
@endpush
@endonce
