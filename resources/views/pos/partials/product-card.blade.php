{{-- Product Card - Premium Apple/SaaS Style --}}
@php
    $product = $variant->product ?? null;
    $name = $product ? $product->name . ' - ' . $variant->name : $variant->name;
    $sku = $variant->sku ?? '';
    $price = $variant->sale_price ?? $variant->price ?? 0;
    $stock = $variant->stock_finished ?? $variant->stock ?? 0;
    $hasStock = $stock > 0;
    $imageUrl = $variant->image_url ?? $product?->image_url ?? null;
@endphp

<div class="pos-product-card {{ $hasStock ? 'has-stock' : 'no-stock' }}"
     data-product-card
     data-product-name="{{ $name }}"
     data-product-sku="{{ $sku }}">

    {{-- Image --}}
    <div class="pos-product-image">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $name }}">
        @else
            <div class="pos-product-placeholder">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        @endif

        {{-- Stock Badge --}}
        <div class="pos-product-badge {{ $hasStock ? 'in-stock' : 'out-stock' }}">
            {{ $hasStock ? $stock . ' disp.' : 'Agotado' }}
        </div>

        {{-- Hover Overlay --}}
        @if($hasStock)
        <button data-add-product
                data-variant-id="{{ $variant->id }}"
                data-name="{{ $name }}"
                data-price="{{ $price }}"
                data-stock="{{ $stock }}"
                class="pos-product-overlay">
            <div class="pos-product-add-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <span>Agregar</span>
        </button>
        @endif
    </div>

    {{-- Info --}}
    <div class="pos-product-info">
        <h3 class="pos-product-name">{{ $name }}</h3>
        @if($sku)
            <p class="pos-product-sku">SKU: {{ $sku }}</p>
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
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.04);
        border: 1px solid #f1f5f9;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .pos-product-card.has-stock:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.06);
        border-color: #e2e8f0;
    }

    .pos-product-card.no-stock {
        opacity: 0.6;
    }

    .pos-product-image {
        position: relative;
        height: 140px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .pos-product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .pos-product-card.has-stock:hover .pos-product-image img {
        transform: scale(1.05);
    }

    .pos-product-placeholder {
        width: 64px;
        height: 64px;
        color: #cbd5e1;
    }

    .pos-product-placeholder svg {
        width: 100%;
        height: 100%;
    }

    .pos-product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .pos-product-badge.in-stock {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .pos-product-badge.out-stock {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .pos-product-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.95) 0%, rgba(37, 99, 235, 0.95) 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        opacity: 0;
        cursor: pointer;
        border: none;
        transition: opacity 0.2s ease;
    }

    .pos-product-card.has-stock:hover .pos-product-overlay {
        opacity: 1;
    }

    .pos-product-add-icon {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: transform 0.2s ease;
    }

    .pos-product-add-icon svg {
        width: 28px;
        height: 28px;
    }

    .pos-product-overlay:hover .pos-product-add-icon {
        transform: scale(1.1);
    }

    .pos-product-overlay span {
        color: #fff;
        font-size: 14px;
        font-weight: 600;
    }

    .pos-product-info {
        padding: 14px 16px 16px;
    }

    .pos-product-name {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.4;
        margin: 0 0 4px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .pos-product-sku {
        font-size: 12px;
        color: #94a3b8;
        margin: 0 0 8px 0;
    }

    .pos-product-price {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
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
