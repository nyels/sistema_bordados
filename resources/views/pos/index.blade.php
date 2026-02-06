@extends('layouts.pos')

@section('content')
    {{-- Header POS --}}
    @include('pos.partials.header')

    {{-- Main Content --}}
    <div class="pos-main-layout">
        {{-- Left Panel: Products --}}
        <div class="pos-products-panel">
            {{-- Search --}}
            @include('pos.partials.search')

            {{-- Product Grid --}}
            @include('pos.partials.product-grid')
        </div>

        {{-- Right Panel: Cart --}}
        <div class="pos-cart-panel">
            {{-- Customer Selector --}}
            @include('pos.partials.customer-selector')

            {{-- Cart Items --}}
            @include('pos.partials.cart')

            {{-- Totals --}}
            @include('pos.partials.totals')

            {{-- Payment Method --}}
            @include('pos.partials.payment-method')

            {{-- Action Buttons --}}
            @include('pos.partials.confirm-button')
        </div>
    </div>

    {{-- Modals --}}
    @include('pos.partials.modal-confirm')
    @include('pos.partials.modal-cancel')
@endsection

@push('styles')
<style>
    /* =====================================================================
       POS INDEX - Enterprise SaaS Layout 2025
       ===================================================================== */

    /* Main Layout */
    .pos-main-layout {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    .pos-products-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--pos-slate-100);
        overflow: hidden;
        min-width: 0;
    }

    .pos-cart-panel {
        width: 420px;
        min-width: 420px;
        max-width: 420px;
        display: flex;
        flex-direction: column;
        background: var(--pos-white);
        border-left: 1px solid var(--pos-slate-200);
        box-shadow: var(--pos-shadow-xl);
        overflow-y: auto;
    }

    /* Responsive */
    @media (max-width: 1280px) {
        .pos-cart-panel {
            width: 380px;
            min-width: 380px;
            max-width: 380px;
        }
    }

    @media (max-width: 1024px) {
        .pos-cart-panel {
            width: 360px;
            min-width: 360px;
            max-width: 360px;
        }
    }

    @media (max-width: 768px) {
        .pos-main-layout {
            flex-direction: column;
        }

        .pos-products-panel {
            flex: none;
            height: 50vh;
        }

        .pos-cart-panel {
            width: 100%;
            min-width: 100%;
            max-width: 100%;
            flex: 1;
            border-left: none;
            border-top: 1px solid var(--pos-slate-200);
        }
    }

    /* =====================================================================
       Confirm Modal Content Styles
       ===================================================================== */
    .pos-confirm-details {
        display: flex;
        flex-direction: column;
        gap: var(--pos-space-sm);
        background: var(--pos-slate-50);
        padding: var(--pos-space-md);
        border-radius: var(--pos-radius-md);
        border: 1px solid var(--pos-slate-200);
    }

    .pos-confirm-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--pos-space-sm) 0;
        border-bottom: 1px solid var(--pos-slate-100);
    }

    .pos-confirm-row:last-child {
        border-bottom: none;
    }

    .pos-confirm-row-highlight {
        background: rgba(79, 70, 229, 0.05);
        margin: 0 calc(-1 * var(--pos-space-md));
        padding: var(--pos-space-sm) var(--pos-space-md);
        border-bottom: none;
    }

    .pos-confirm-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--pos-slate-500);
    }

    .pos-confirm-label i {
        font-size: 12px;
        color: var(--pos-slate-400);
    }

    .pos-confirm-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-800);
    }

    .pos-confirm-value-primary {
        color: var(--pos-primary);
        font-size: 16px;
    }

    .pos-confirm-notice {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: var(--pos-space-md);
        padding: var(--pos-space-sm) var(--pos-space-md);
        background: rgba(79, 70, 229, 0.08);
        border-radius: var(--pos-radius-md);
        font-size: 12px;
        font-weight: 500;
        color: var(--pos-primary-dark);
    }

    .pos-confirm-notice i {
        color: var(--pos-primary);
    }

    /* =====================================================================
       Cart Multi-Item Styles - With Image
       ===================================================================== */
    .pos-cart-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 8px;
    }

    .pos-cart-item {
        display: flex;
        gap: 10px;
        padding: 8px;
        background: var(--pos-white);
        border: 1px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        box-shadow: var(--pos-shadow-sm);
    }

    .pos-cart-item-image {
        width: 50px;
        height: 50px;
        min-width: 50px;
        background: var(--pos-slate-100);
        border-radius: var(--pos-radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .pos-cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pos-cart-item-image i {
        font-size: 18px;
        color: var(--pos-slate-400);
    }

    .pos-cart-item-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .pos-cart-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .pos-cart-item-name {
        font-size: 11px;
        font-weight: 700;
        color: var(--pos-slate-800);
        margin: 0;
        line-height: 1.3;
        letter-spacing: 0.02em;
        flex: 1;
        min-width: 0;
    }

    .pos-cart-item-subtotal {
        font-size: 14px;
        font-weight: 700;
        color: var(--pos-primary);
        white-space: nowrap;
    }

    .pos-cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 2px;
    }

    .pos-qty-btn {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-700);
        border: none;
        border-radius: var(--pos-radius-sm);
        color: var(--pos-white);
        font-size: 9px;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-qty-btn:hover {
        background: var(--pos-slate-600);
    }

    .pos-qty-display {
        min-width: 24px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: var(--pos-slate-800);
    }

    .pos-remove-btn {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-danger);
        border: none;
        border-radius: var(--pos-radius-sm);
        color: var(--pos-white);
        font-size: 9px;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-remove-btn:hover {
        background: var(--pos-danger-dark);
    }

    .pos-cart-item-pulse {
        animation: cartPulse 0.3s ease;
    }

    @keyframes cartPulse {
        0% { transform: scale(1); background: var(--pos-white); }
        50% { transform: scale(1.02); background: rgba(79, 70, 229, 0.1); }
        100% { transform: scale(1); background: var(--pos-white); }
    }

    .pos-cart-item-stock-limit {
        animation: stockLimitShake 0.6s ease;
    }

    @keyframes stockLimitShake {
        0%, 100% { transform: translateX(0); background: var(--pos-white); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); background: rgba(239, 68, 68, 0.15); }
        20%, 40%, 60%, 80% { transform: translateX(4px); background: rgba(239, 68, 68, 0.15); }
    }

    /* =====================================================================
       Confirm Modal Multi-Item Styles
       ===================================================================== */
    .pos-confirm-items-header {
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-700);
        padding-bottom: var(--pos-space-sm);
        border-bottom: 1px solid var(--pos-slate-200);
        margin-bottom: var(--pos-space-sm);
    }

    .pos-confirm-item {
        padding: var(--pos-space-sm) 0;
        border-bottom: 1px dashed var(--pos-slate-200);
    }

    .pos-confirm-item:last-of-type {
        border-bottom: none;
    }

    .pos-confirm-item-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--pos-slate-700);
        margin-bottom: 2px;
    }

    .pos-confirm-item-details {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: var(--pos-slate-500);
    }

    .pos-confirm-item-subtotal {
        font-weight: 600;
        color: var(--pos-slate-700);
    }
</style>
@endpush

@push('scripts')
<script>
/**
 * POS MULTI-ITEM - Enterprise SaaS 2025
 *
 * Carrito profesional con múltiples ítems:
 * - Agregar productos a lista
 * - Si producto ya existe, incrementa cantidad
 * - Cantidad editable por ítem
 * - Precio editable por ítem
 * - Total estimado en frontend (backend valida)
 */

document.addEventListener('DOMContentLoaded', function() {

    // =========================================================================
    // ESTADO DEL CARRITO - MULTI-ITEM
    // =========================================================================
    let cart = {
        items: [],  // Array de { id, variant_id, name, price_original, quantity, price_final }
        payment_method: 'efectivo',
        apply_iva: false
    };

    let itemIdCounter = 0;

    // =========================================================================
    // ELEMENTOS DOM
    // =========================================================================
    const cartEmptyEl = document.getElementById('cart-empty');
    const cartItemEl = document.getElementById('cart-item');
    const cartBadgeEl = document.getElementById('cart-badge');
    const searchInput = document.getElementById('pos-search');
    const discountReasonInput = document.getElementById('discount-reason');
    const discountValueInput = document.getElementById('discount-value');

    // =========================================================================
    // HELPERS (SOLO DOM)
    // =========================================================================
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    window.openModal = openModal;
    window.closeModal = closeModal;

    // =========================================================================
    // FORMATEAR MONEDA
    // =========================================================================
    function formatMoney(value) {
        const num = parseFloat(value) || 0;
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // =========================================================================
    // CALCULAR TOTAL ESTIMADO DEL CARRITO
    // =========================================================================
    function calculateCartTotal() {
        let subtotal = 0;
        cart.items.forEach(function(item) {
            subtotal += (parseFloat(item.price_final) || 0) * (parseInt(item.quantity) || 0);
        });
        return subtotal;
    }

    // =========================================================================
    // RENDERIZADO DEL CARRITO - MULTI-ITEM
    // =========================================================================
    function renderCart() {
        // Actualizar badge con cantidad total
        var totalQty = 0;
        cart.items.forEach(function(item) {
            totalQty += parseInt(item.quantity) || 0;
        });
        if (cartBadgeEl) {
            cartBadgeEl.textContent = totalQty;
            cartBadgeEl.setAttribute('data-count', totalQty);
        }

        if (cart.items.length === 0) {
            cartEmptyEl.classList.remove('hidden');
            cartItemEl.classList.add('hidden');
            cartItemEl.innerHTML = '';
        } else {
            cartEmptyEl.classList.add('hidden');
            cartItemEl.classList.remove('hidden');

            let html = '<div class="pos-cart-list">';

            cart.items.forEach(function(item, index) {
                const itemSubtotal = (parseFloat(item.price_final) || 0) * (parseInt(item.quantity) || 0);
                const imageUrl = item.image_url || null;

                html += `
                    <div class="pos-cart-item" data-item-id="${item.id}">
                        <div class="pos-cart-item-image">
                            ${imageUrl ? '<img src="' + imageUrl + '" alt="">' : '<i class="fas fa-box"></i>'}
                        </div>
                        <div class="pos-cart-item-content">
                            <div class="pos-cart-item-row">
                                <p class="pos-cart-item-name">${item.name.toUpperCase()}</p>
                                <span class="pos-cart-item-subtotal">$${formatMoney(itemSubtotal)}</span>
                            </div>
                            <div class="pos-cart-item-row">
                                <div class="pos-cart-item-quantity">
                                    <button data-item-id="${item.id}" data-action="decrease" class="pos-qty-btn">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="pos-qty-display">${item.quantity}</span>
                                    <button data-item-id="${item.id}" data-action="increase" class="pos-qty-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button data-item-id="${item.id}" data-action="remove" class="pos-remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            cartItemEl.innerHTML = html;

            // Bind event listeners
            bindCartEvents();
        }

        // Actualizar totales en la sección inferior
        updateTotals();
    }

    // =========================================================================
    // CALCULAR Y MOSTRAR TOTALES CON DESCUENTO E IVA
    // =========================================================================
    function updateTotals() {
        var subtotal = calculateCartTotal();

        // Obtener descuento
        var discountValueInput = document.getElementById('discount-value');
        var btnDiscountPercent = document.getElementById('btn-discount-percent');
        var discountVal = parseFloat(discountValueInput ? discountValueInput.value : 0) || 0;
        var isPercent = btnDiscountPercent ? btnDiscountPercent.classList.contains('active') : false;

        // Calcular descuento
        var discount = 0;
        if (discountVal > 0) {
            if (isPercent) {
                discount = subtotal * (Math.min(discountVal, 100) / 100);
            } else {
                discount = Math.min(discountVal, subtotal);
            }
        }

        var subtotalAfterDiscount = subtotal - discount;

        // IVA desde configuración del sistema
        var ivaRate = {{ ($defaultTaxRate ?? 16) / 100 }};
        var ivaAmount = cart.apply_iva ? (subtotalAfterDiscount * ivaRate) : 0;

        var total = subtotalAfterDiscount + ivaAmount;

        // Actualizar display
        var totalDisplayEl = document.getElementById('pos-total-display');
        if (totalDisplayEl) {
            if (cart.items.length === 0) {
                totalDisplayEl.innerHTML = '<span class="pos-total-empty">Agrega productos al carrito</span>';
            } else {
                var html = '';

                // Subtotal
                html += '<div class="pos-total-row pos-total-row-subtotal"><span>Subtotal</span><span>$' + formatMoney(subtotal) + '</span></div>';

                // Descuento (siempre visible, label fijo)
                html += '<div class="pos-total-row pos-total-row-discount"><span>Descuento</span><span>-$' + formatMoney(discount) + '</span></div>';

                // IVA con toggle
                html += '<div class="pos-total-row pos-total-row-iva">';
                html += '<div class="pos-iva-label">';
                html += '<label class="pos-toggle-mini"><input type="checkbox" id="apply-iva" ' + (cart.apply_iva ? 'checked' : '') + '><span class="pos-toggle-mini-slider"></span></label>';
                html += '<span>IVA ({{ $defaultTaxRate ?? 16 }}%)</span>';
                html += '</div>';
                html += '<span class="pos-iva-amount">' + (cart.apply_iva ? '+$' + formatMoney(ivaAmount) : '$0.00') + '</span>';
                html += '</div>';

                // Total
                html += '<div class="pos-total-row pos-total-row-final"><span>TOTAL</span><span>$' + formatMoney(total) + '</span></div>';

                totalDisplayEl.innerHTML = html;

                // Bind IVA toggle event
                var ivaCheckbox = document.getElementById('apply-iva');
                if (ivaCheckbox) {
                    ivaCheckbox.addEventListener('change', function() {
                        cart.apply_iva = this.checked;
                        updateTotals();
                    });
                }
            }
        }
    }

    // =========================================================================
    // BIND EVENTOS DEL CARRITO
    // =========================================================================
    function bindCartEvents() {
        // Botones decrease
        cartItemEl.querySelectorAll('[data-action="decrease"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const itemId = parseInt(this.dataset.itemId);
                decreaseQuantity(itemId);
            });
        });

        // Botones increase
        cartItemEl.querySelectorAll('[data-action="increase"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const itemId = parseInt(this.dataset.itemId);
                increaseQuantity(itemId);
            });
        });

        // Botones remove
        cartItemEl.querySelectorAll('[data-action="remove"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const itemId = parseInt(this.dataset.itemId);
                removeItem(itemId);
            });
        });
    }

    // =========================================================================
    // ENCONTRAR ÍTEM POR ID
    // =========================================================================
    function findItemIndex(itemId) {
        for (var i = 0; i < cart.items.length; i++) {
            if (cart.items[i].id === itemId) return i;
        }
        return -1;
    }

    // =========================================================================
    // ENCONTRAR ÍTEM POR VARIANT_ID
    // =========================================================================
    function findItemByVariantId(variantId) {
        for (var i = 0; i < cart.items.length; i++) {
            if (cart.items[i].variant_id === variantId) return i;
        }
        return -1;
    }

    // =========================================================================
    // ACCIONES DEL CARRITO - MULTI-ITEM
    // =========================================================================
    function addProduct(variantId, name, price, imageUrl, stock) {
        // Obtener stock del data-attribute si no viene como parámetro
        if (!stock) {
            var productCard = document.querySelector('[data-variant-id="' + variantId + '"]');
            stock = productCard ? parseInt(productCard.dataset.stock) || 999 : 999;
        } else {
            stock = parseInt(stock) || 999;
        }

        // Verificar si ya existe
        var existingIndex = findItemByVariantId(variantId);

        if (existingIndex >= 0) {
            // Ya existe - solo mostrar feedback sutil (ya está en carrito)
            showAlreadyInCartFeedback(cart.items[existingIndex].id);
        } else {
            // Agregar nuevo con cantidad 1
            itemIdCounter++;
            cart.items.push({
                id: itemIdCounter,
                variant_id: variantId,
                name: name,
                price_original: price,
                quantity: '1',
                price_final: price,
                image_url: imageUrl || null,
                stock: stock
            });
            renderCart();
        }
    }

    function showAlreadyInCartFeedback(itemId) {
        var itemEl = cartItemEl.querySelector('[data-item-id="' + itemId + '"]');
        if (itemEl) {
            itemEl.classList.add('pos-cart-item-pulse');
            setTimeout(function() {
                itemEl.classList.remove('pos-cart-item-pulse');
            }, 300);
        }
    }

    function showStockLimitFeedback(itemId) {
        var itemEl = cartItemEl.querySelector('[data-item-id="' + itemId + '"]');
        if (itemEl) {
            itemEl.classList.add('pos-cart-item-stock-limit');
            setTimeout(function() {
                itemEl.classList.remove('pos-cart-item-stock-limit');
            }, 600);
        }
    }

    // Exponer funciones globalmente
    window.addProduct = addProduct;
    window.updateTotals = updateTotals;

    function increaseQuantity(itemId) {
        var index = findItemIndex(itemId);
        if (index < 0) return;
        var current = parseInt(cart.items[index].quantity) || 0;
        var maxStock = parseInt(cart.items[index].stock) || 999;

        if (current < maxStock) {
            cart.items[index].quantity = String(current + 1);
            renderCart();
        } else {
            showStockLimitFeedback(itemId);
        }
    }

    function decreaseQuantity(itemId) {
        var index = findItemIndex(itemId);
        if (index < 0) return;
        var current = parseInt(cart.items[index].quantity) || 0;
        if (current > 1) {
            cart.items[index].quantity = String(current - 1);
            renderCart();
        }
    }

    function changeQuantity(itemId, newQty) {
        var index = findItemIndex(itemId);
        if (index < 0) return;
        var maxStock = parseInt(cart.items[index].stock) || 999;
        var qty = newQty < 1 ? 1 : (newQty > maxStock ? maxStock : newQty);

        if (newQty > maxStock) {
            showStockLimitFeedback(itemId);
        }

        cart.items[index].quantity = String(qty);
        renderCart();
    }

    function changePrice(itemId, newPrice) {
        var index = findItemIndex(itemId);
        if (index < 0) return;
        cart.items[index].price_final = newPrice;
        renderCart();
    }

    function removeItem(itemId) {
        var index = findItemIndex(itemId);
        if (index >= 0) {
            cart.items.splice(index, 1);
        }
        renderCart();
    }

    function clearCart() {
        cart.items = [];
        cart.payment_method = 'efectivo';
        cart.apply_iva = false;

        // Limpiar campos de descuento (usan constantes globales)
        if (discountValueInput) discountValueInput.value = '';
        if (discountReasonInput) discountReasonInput.value = '';

        // Reset tipo de descuento a $ (fixed)
        var btnDiscountMoney = document.getElementById('btn-discount-money');
        var btnDiscountPercent = document.getElementById('btn-discount-percent');
        var discountPrefix = document.getElementById('discount-prefix');
        if (btnDiscountMoney && btnDiscountPercent) {
            btnDiscountMoney.classList.add('active');
            btnDiscountPercent.classList.remove('active');
            if (discountPrefix) discountPrefix.textContent = '$';
            if (discountValueInput) {
                discountValueInput.placeholder = '0.00';
                discountValueInput.step = '0.01';
                discountValueInput.removeAttribute('max');
            }
        }

        // Reset payment method
        document.querySelectorAll('[data-payment-method]').forEach(function(el, idx) {
            el.classList.remove('active');
            if (idx === 0) {
                el.classList.add('active');
            }
        });

        // Reset cliente (venta libre)
        var clienteIdInput = document.getElementById('cliente-id');
        var customerOptions = document.getElementById('customer-options');
        var clienteSelectedCard = document.getElementById('cliente-selected-card');
        var btnVentaLibre = document.getElementById('btn-venta-libre');
        if (clienteIdInput) clienteIdInput.value = '';
        if (customerOptions) customerOptions.style.display = '';
        if (clienteSelectedCard) clienteSelectedCard.style.display = 'none';
        if (btnVentaLibre) btnVentaLibre.classList.add('pos-option-active');

        renderCart();
    }

    // =========================================================================
    // EVENT LISTENERS - PRODUCTOS
    // =========================================================================
    document.querySelectorAll('[data-add-product]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var variantId = this.dataset.variantId;
            var name = this.dataset.name;
            var price = this.dataset.price;
            addProduct(variantId, name, price);
        });
    });

    // IVA toggle ahora está dentro del display de totales

    // =========================================================================
    // EVENT LISTENERS - MÉTODO DE PAGO
    // =========================================================================
    function togglePaymentMethod(btn) {
        document.querySelectorAll('[data-payment-method]').forEach(function(el) {
            el.classList.remove('active');
        });
        btn.classList.add('active');
        cart.payment_method = btn.dataset.paymentMethod;
    }
    window.togglePaymentMethod = togglePaymentMethod;

    // =========================================================================
    // EVENT LISTENERS - BÚSQUEDA
    // =========================================================================
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            document.querySelectorAll('[data-product-card]').forEach(function(card) {
                var name = (card.dataset.productName || '').toLowerCase();
                var sku = (card.dataset.productSku || '').toLowerCase();
                if (query === '' || name.includes(query) || sku.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // =========================================================================
    // MODAL CONFIRM - MULTI-ITEM
    // =========================================================================
    var confirmBtn = document.querySelector('[onclick="openModal(\'modal-confirm\')"]');
    var modalPriceAlert = document.getElementById('modal-price-alert');
    var btnExecuteSale = document.getElementById('btn-execute-sale');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            if (cart.items.length === 0) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                    icon: 'warning',
                    title: 'Carrito vacío',
                    text: 'Agrega al menos un producto al carrito',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            var discountReason = discountReasonInput ? discountReasonInput.value : '';

            // Construir resumen de múltiples ítems
            var html = '<div class="pos-confirm-details">';

            // Header de ítems
            html += '<div class="pos-confirm-items-header"><i class="fas fa-shopping-cart"></i> ' + cart.items.length + ' producto(s) en carrito</div>';

            // Lista de ítems
            cart.items.forEach(function(item, idx) {
                var itemSubtotal = (parseFloat(item.price_final) || 0) * (parseInt(item.quantity) || 0);
                html += '<div class="pos-confirm-item">';
                html += '<div class="pos-confirm-item-name">' + (idx + 1) + '. ' + item.name + '</div>';
                html += '<div class="pos-confirm-item-details">';
                html += '<span>' + item.quantity + ' x $' + formatMoney(item.price_final) + '</span>';
                html += '<span class="pos-confirm-item-subtotal">$' + formatMoney(itemSubtotal) + '</span>';
                html += '</div></div>';
            });

            // Totales
            var subtotal = calculateCartTotal();
            html += '<div class="pos-confirm-row pos-confirm-row-highlight">';
            html += '<span class="pos-confirm-label"><i class="fas fa-calculator"></i> Subtotal</span>';
            html += '<span class="pos-confirm-value pos-confirm-value-primary">$' + formatMoney(subtotal) + '</span>';
            html += '</div>';

            if (discountReason) {
                html += '<div class="pos-confirm-row">';
                html += '<span class="pos-confirm-label"><i class="fas fa-percent"></i> Motivo descuento</span>';
                html += '<span class="pos-confirm-value">' + discountReason + '</span>';
                html += '</div>';
            }

            html += '<div class="pos-confirm-row">';
            html += '<span class="pos-confirm-label"><i class="fas fa-credit-card"></i> Método pago</span>';
            html += '<span class="pos-confirm-value">' + cart.payment_method.toUpperCase() + '</span>';
            html += '</div>';

            html += '<div class="pos-confirm-row">';
            html += '<span class="pos-confirm-label"><i class="fas fa-receipt"></i> Aplicar IVA</span>';
            html += '<span class="pos-confirm-value">' + (cart.apply_iva ? 'SÍ' : 'NO') + '</span>';
            html += '</div>';

            html += '</div>';

            html += '<div class="pos-confirm-notice">';
            html += '<i class="fas fa-info-circle"></i>';
            html += '<span>El total final será calculado por el servidor.</span>';
            html += '</div>';

            document.getElementById('modal-confirm-content').innerHTML = html;

            // Ocultar alerta de precio (multi-item no usa)
            if (modalPriceAlert) {
                modalPriceAlert.classList.add('hidden');
            }
            btnExecuteSale.disabled = false;
        });
    }

    // =========================================================================
    // EJECUTAR VENTA - POST /pos/sale (ENVÍA ARRAY DE ITEMS)
    // =========================================================================
    if (btnExecuteSale) {
        btnExecuteSale.addEventListener('click', async function() {
            if (cart.items.length === 0) return;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESANDO...';

            var discountReason = discountReasonInput ? discountReasonInput.value : null;
            var discountValueRaw = discountValueInput ? discountValueInput.value : null;
            var discountValue = (discountValueRaw && discountValueRaw !== '') ? parseFloat(discountValueRaw) : null;

            // Determinar tipo de descuento (fixed=$ o percent=%)
            var btnDiscountMoney = document.getElementById('btn-discount-money');
            var discountType = (btnDiscountMoney && btnDiscountMoney.classList.contains('active')) ? 'fixed' : 'percent';
            // Si no hay valor de descuento, no enviar tipo
            if (discountValue === null || discountValue === 0) {
                discountType = null;
            }

            // Cliente seleccionado
            var clienteIdInput = document.getElementById('cliente-id');
            var clienteId = (clienteIdInput && clienteIdInput.value) ? clienteIdInput.value : null;

            // PAYLOAD: array de items
            var items = cart.items.map(function(item) {
                return {
                    product_variant_id: item.variant_id,
                    quantity: parseInt(item.quantity) || 1,
                    unit_price_original: parseFloat(item.price_original) || 0,
                    unit_price_final: parseFloat(item.price_final) || 0
                };
            });

            var payload = {
                items: items,
                discount_reason: discountReason || null,
                discount_type: discountType,
                discount_value: discountValue,
                payment_method: cart.payment_method,
                apply_iva: cart.apply_iva,
                cliente_id: clienteId
            };

            try {
                var response = await fetch('{{ route("pos.sale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                var data = await response.json();

                closeModal('modal-confirm');
                showResult(data.success, data);

            } catch (error) {
                closeModal('modal-confirm');
                showResult(false, { error: 'Error de conexión: ' + error.message });
            }

            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check"></i> EJECUTAR';
        });
    }

    // =========================================================================
    // MOSTRAR RESULTADO - MULTI-ITEM
    // =========================================================================
    function showResult(success, data) {
        var header = document.getElementById('modal-result-header');
        var title = document.getElementById('modal-result-title');
        var content = document.getElementById('modal-result-content');
        var footer = document.getElementById('modal-result-footer');

        if (success) {
            header.classList.remove('pos-modal-header-error');
            header.classList.add('pos-modal-header-success');
            title.textContent = 'VENTA EXITOSA';

            var resultHtml = '<div class="pos-result-icon pos-result-icon-success">';
            resultHtml += '<i class="fas fa-check-circle"></i>';
            resultHtml += '</div>';
            resultHtml += '<p class="pos-result-title pos-result-title-success">VENTA REGISTRADA</p>';
            resultHtml += '<div class="pos-result-details">';
            resultHtml += '<p><i class="fas fa-receipt"></i> <strong>Pedido:</strong> ' + data.data.order_number + '</p>';
            resultHtml += '<p><i class="fas fa-box"></i> <strong>Productos:</strong> ' + data.data.items_count + '</p>';
            resultHtml += '<p><i class="fas fa-coins"></i> <strong>Subtotal:</strong> $' + data.data.subtotal + '</p>';

            if (data.data.discount_display) {
                resultHtml += '<p><i class="fas fa-percent"></i> <strong>Descuento:</strong> $' + data.data.discount + '</p>';
            }

            if (data.data.iva_display) {
                resultHtml += '<p><i class="fas fa-file-invoice"></i> <strong>IVA (' + data.data.iva_rate + '%):</strong> $' + data.data.iva_amount + '</p>';
            }

            resultHtml += '<p class="pos-result-total"><i class="fas fa-dollar-sign"></i> <strong>TOTAL:</strong> $' + data.data.total + '</p>';
            resultHtml += '<p><i class="fas fa-user"></i> <strong>Vendedor:</strong> ' + data.data.seller_name + '</p>';
            resultHtml += '</div>';

            content.innerHTML = resultHtml;

            footer.innerHTML = '<button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full"><i class="fas fa-check"></i> CERRAR</button>';

            document.getElementById('btn-result-close').addEventListener('click', handleResultClose);

        } else {
            header.classList.remove('pos-modal-header-success');
            header.classList.add('pos-modal-header-error');
            title.textContent = 'ERROR';

            content.innerHTML = '<div class="pos-result-icon pos-result-icon-error"><i class="fas fa-times-circle"></i></div><p class="pos-result-title pos-result-title-error">NO SE PUDO REGISTRAR</p><p class="pos-modal-text-secondary">' + (data.error || 'Error desconocido') + '</p>';

            footer.innerHTML = '<button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full"><i class="fas fa-times"></i> CERRAR</button>';

            document.getElementById('btn-result-close').addEventListener('click', handleResultClose);
        }

        openModal('modal-result');
    }

    // =========================================================================
    // HANDLER PARA CERRAR RESULTADO
    // =========================================================================
    function handleResultClose() {
        closeModal('modal-result');
        var header = document.getElementById('modal-result-header');
        if (header.classList.contains('pos-modal-header-success')) {
            clearCart();
            window.location.reload();
        }
    }

    // =========================================================================
    // LIMPIAR CARRITO
    // =========================================================================
    var btnClearCart = document.getElementById('btn-clear-cart');
    if (btnClearCart) {
        btnClearCart.addEventListener('click', function() {
            clearCart();
            closeModal('modal-cancel');
        });
    }

    // =========================================================================
    // ALERTA DE PRODUCTO SIN STOCK
    // =========================================================================
    function showNoStockAlert(productName) {
        Swal.fire({
            icon: 'error',
            title: 'Sin Stock',
            html: '<p>No se puede agregar el producto:</p><p><strong>' + productName + '</strong></p><p>No hay unidades disponibles en inventario.</p>',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545',
            customClass: {
                popup: 'pos-swal-popup'
            }
        });
    }
    window.showNoStockAlert = showNoStockAlert;

    // =========================================================================
    // INICIALIZAR
    // =========================================================================
    renderCart();
    updateTotals();

});
</script>
@endpush
