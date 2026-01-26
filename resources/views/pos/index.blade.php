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
        background: #f1f5f9;
        overflow: hidden;
        min-width: 0;
    }

    .pos-cart-panel {
        width: 380px;
        min-width: 380px;
        max-width: 380px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-left: 1px solid #e2e8f0;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.05);
        overflow-y: auto;
    }

    /* Cart Item Styles */
    .pos-cart-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
        margin: 0 16px;
    }

    .pos-cart-item-info {
        flex: 1;
        min-width: 0;
    }

    .pos-cart-item-name {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 4px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-cart-item-price {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    .pos-cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pos-qty-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #1e293b;
        border: none;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-qty-btn:hover {
        background: #334155;
    }

    .pos-qty-btn svg {
        width: 18px;
        height: 18px;
    }

    .pos-qty-input {
        width: 56px;
        height: 36px;
        text-align: center;
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
    }

    .pos-qty-input:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .pos-remove-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ef4444;
        border: none;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-remove-btn:hover {
        background: #dc2626;
    }

    .pos-remove-btn svg {
        width: 18px;
        height: 18px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .pos-cart-panel {
            width: 340px;
            min-width: 340px;
            max-width: 340px;
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
            border-top: 1px solid #e2e8f0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
/**
 * POS 1-ITEM - FRONTEND 100% PASIVO
 *
 * REGLAS ABSOLUTAS:
 * - NO parseFloat, NO toFixed, NO parseInt sobre data.data.*
 * - NO comparaciones numéricas sobre data.data.*
 * - TODOS los valores del backend se renderizan como TEXTO OPACO
 * - quantity se envía TAL CUAL el input del usuario
 */

document.addEventListener('DOMContentLoaded', function() {

    // =========================================================================
    // ESTADO DEL CARRITO (SOLO CAPTURA - VALORES COMO STRINGS)
    // =========================================================================
    let cart = {
        item: null,  // { variant_id, name, price_original } - todos strings
        quantity: '1',
        payment_method: 'efectivo',
        apply_iva: false
    };

    // =========================================================================
    // NOTA: lastSuccessfulSale ya no se usa (cancelación movida a Historial POS)
    // Se mantiene por compatibilidad pero puede eliminarse en futuras versiones
    // =========================================================================
    let lastSuccessfulSale = null;

    // =========================================================================
    // ELEMENTOS DOM
    // =========================================================================
    const cartEmptyEl = document.getElementById('cart-empty');
    const cartItemEl = document.getElementById('cart-item');
    const unitPriceFinalInput = document.getElementById('unit-price-final');
    const discountReasonInput = document.getElementById('discount-reason');
    const applyIvaCheckbox = document.getElementById('apply-iva');
    const searchInput = document.getElementById('pos-search');

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
    // DETECCIÓN DE PRECIO DE RIESGO (R01/R02) - SOLO UX, NO BLOQUEA
    // =========================================================================
    const RISK_THRESHOLD_LOW = 0.50;  // 50% del precio original
    const RISK_THRESHOLD_HIGH = 1.50; // 150% del precio original

    function checkPriceRisk(priceOriginal, priceFinal) {
        const original = parseFloat(priceOriginal) || 0;
        const final = parseFloat(priceFinal);

        // R01: Precio en cero
        if (final === 0 || isNaN(final)) {
            return {
                isRisk: true,
                riskType: 'zero',
                title: 'PRECIO EN $0.00',
                text: 'Esta venta no generará ingreso.',
                severity: 'critical'
            };
        }

        // R02: Precio muy bajo (< 50% del original)
        if (original > 0 && final < original * RISK_THRESHOLD_LOW) {
            const percent = Math.round((final / original) * 100);
            return {
                isRisk: true,
                riskType: 'low',
                title: 'PRECIO MUY BAJO',
                text: `El precio final ($${final.toFixed(2)}) es solo el ${percent}% del precio lista ($${original.toFixed(2)}).`,
                severity: 'warning'
            };
        }

        // R02: Precio muy alto (> 150% del original)
        if (original > 0 && final > original * RISK_THRESHOLD_HIGH) {
            const percent = Math.round((final / original) * 100);
            return {
                isRisk: true,
                riskType: 'high',
                title: 'PRECIO MUY ALTO',
                text: `El precio final ($${final.toFixed(2)}) es el ${percent}% del precio lista ($${original.toFixed(2)}).`,
                severity: 'warning'
            };
        }

        return { isRisk: false, riskType: null, title: '', text: '', severity: null };
    }

    // =========================================================================
    // MOSTRAR/OCULTAR ADVERTENCIA EN INPUT DE PRECIO
    // =========================================================================
    const priceWarningEl = document.getElementById('price-warning');
    const priceWarningTitle = document.getElementById('price-warning-title');
    const priceWarningText = document.getElementById('price-warning-text');

    function updatePriceWarning() {
        if (!cart.item) {
            priceWarningEl.classList.add('hidden');
            return;
        }

        const risk = checkPriceRisk(cart.item.price_original, unitPriceFinalInput.value);

        if (risk.isRisk) {
            priceWarningEl.classList.remove('hidden');
            priceWarningTitle.textContent = risk.title;
            priceWarningText.textContent = risk.text;

            // Estilo según severidad
            priceWarningEl.classList.remove('bg-red-100', 'border-red-500', 'text-red-700',
                                            'bg-yellow-100', 'border-yellow-500', 'text-yellow-700');
            if (risk.severity === 'critical') {
                priceWarningEl.classList.add('bg-red-100', 'border-red-500', 'text-red-700');
            } else {
                priceWarningEl.classList.add('bg-yellow-100', 'border-yellow-500', 'text-yellow-700');
            }
        } else {
            priceWarningEl.classList.add('hidden');
        }
    }

    // Event listener para detectar cambios en precio final
    unitPriceFinalInput.addEventListener('input', updatePriceWarning);
    unitPriceFinalInput.addEventListener('change', updatePriceWarning);

    // =========================================================================
    // RENDERIZADO DEL CARRITO (SIN TRANSFORMACIONES)
    // =========================================================================
    function renderCart() {
        if (!cart.item) {
            cartEmptyEl.classList.remove('hidden');
            cartItemEl.classList.add('hidden');
            cartItemEl.innerHTML = '';
            unitPriceFinalInput.value = '';
        } else {
            cartEmptyEl.classList.add('hidden');
            cartItemEl.classList.remove('hidden');

            const item = cart.item;

            // Renderiza valores como texto opaco
            cartItemEl.innerHTML = `
                <div class="flex items-center justify-between p-4 border-b-2 border-black">
                    <div class="flex-1">
                        <p class="text-lg font-semibold text-black">${item.name}</p>
                        <p class="text-base text-black">Precio lista: $${item.price_original}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button data-action="decrease"
                                class="w-10 h-10 bg-black text-white text-xl font-bold rounded border-2 border-black
                                       hover:bg-gray-800 transition-colors">
                            -
                        </button>
                        <input type="number"
                               id="quantity-input"
                               min="1"
                               value="${cart.quantity}"
                               class="w-16 h-10 text-center text-lg font-bold text-black bg-white border-2 border-black rounded">
                        <button data-action="increase"
                                class="w-10 h-10 bg-black text-white text-xl font-bold rounded border-2 border-black
                                       hover:bg-gray-800 transition-colors">
                            +
                        </button>
                    </div>
                    <button data-action="remove"
                            class="ml-4 w-10 h-10 bg-red-600 text-white text-xl font-bold rounded border-2 border-red-800
                                   hover:bg-red-700 transition-colors">
                        ×
                    </button>
                </div>
            `;

            // Event listeners
            cartItemEl.querySelector('[data-action="decrease"]').addEventListener('click', decreaseQuantity);
            cartItemEl.querySelector('[data-action="increase"]').addEventListener('click', increaseQuantity);
            cartItemEl.querySelector('[data-action="remove"]').addEventListener('click', removeItem);
            cartItemEl.querySelector('#quantity-input').addEventListener('change', function() {
                // Captura tal cual sin normalizar
                cart.quantity = this.value || '1';
            });

            // Setear precio final = precio original por defecto
            if (!unitPriceFinalInput.value) {
                unitPriceFinalInput.value = item.price_original;
            }

            // Actualizar advertencia de precio
            updatePriceWarning();
        }
    }

    // =========================================================================
    // ACCIONES DEL CARRITO (SOLO CAPTURA - SIN TRANSFORMACIONES)
    // =========================================================================
    function addProduct(variantId, name, price) {
        cart.item = {
            variant_id: variantId,      // string tal cual
            name: name,                  // string tal cual
            price_original: price        // string tal cual
        };
        cart.quantity = '1';
        unitPriceFinalInput.value = price;
        discountReasonInput.value = '';
        renderCart();
    }

    function increaseQuantity() {
        if (!cart.item) return;
        const current = parseInt(cart.quantity) || 0;
        cart.quantity = String(current + 1);
        renderCart();
    }

    function decreaseQuantity() {
        if (!cart.item) return;
        const current = parseInt(cart.quantity) || 0;
        if (current > 1) {
            cart.quantity = String(current - 1);
            renderCart();
        }
    }

    function removeItem() {
        cart.item = null;
        cart.quantity = '1';
        unitPriceFinalInput.value = '';
        discountReasonInput.value = '';
        renderCart();
        updatePriceWarning();
    }

    function clearCart() {
        cart.item = null;
        cart.quantity = '1';
        cart.payment_method = 'efectivo';
        cart.apply_iva = false;
        if (unitPriceFinalInput) unitPriceFinalInput.value = '';
        if (discountReasonInput) discountReasonInput.value = '';
        if (applyIvaCheckbox) applyIvaCheckbox.checked = false;

        document.querySelectorAll('[data-payment-method]').forEach((el, idx) => {
            el.classList.remove('active');
            if (idx === 0) {
                el.classList.add('active');
            }
        });

        renderCart();
        updatePriceWarning();
    }

    // =========================================================================
    // EVENT LISTENERS - PRODUCTOS
    // =========================================================================
    document.querySelectorAll('[data-add-product]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const variantId = this.dataset.variantId;
            const name = this.dataset.name;
            const price = this.dataset.price;
            addProduct(variantId, name, price);
        });
    });

    // =========================================================================
    // EVENT LISTENERS - IVA (SOLO CAPTURA)
    // =========================================================================
    if (applyIvaCheckbox) {
        applyIvaCheckbox.addEventListener('change', function() {
            cart.apply_iva = this.checked;
        });
    }

    // =========================================================================
    // EVENT LISTENERS - MÉTODO DE PAGO (SOLO VISUAL + CAPTURA)
    // =========================================================================
    function togglePaymentMethod(btn) {
        document.querySelectorAll('[data-payment-method]').forEach(el => {
            el.classList.remove('active');
        });
        btn.classList.add('active');
        cart.payment_method = btn.dataset.paymentMethod;
    }
    window.togglePaymentMethod = togglePaymentMethod;

    // =========================================================================
    // EVENT LISTENERS - BÚSQUEDA (SOLO FILTRO DOM)
    // =========================================================================
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('[data-product-card]').forEach(card => {
            const name = (card.dataset.productName || '').toLowerCase();
            const sku = (card.dataset.productSku || '').toLowerCase();
            if (query === '' || name.includes(query) || sku.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // =========================================================================
    // MODAL CONFIRM - Mostrar datos capturados (TEXTO OPACO) + ALERTA R01/R02
    // =========================================================================
    const confirmBtn = document.querySelector('[onclick="openModal(\'modal-confirm\')"]');
    const modalPriceAlert = document.getElementById('modal-price-alert');
    const modalAlertTitle = document.getElementById('modal-alert-title');
    const modalAlertText = document.getElementById('modal-alert-text');
    const confirmPriceCheckbox = document.getElementById('confirm-price-checkbox');
    const confirmPriceLabel = document.getElementById('confirm-price-label');
    const btnExecuteSale = document.getElementById('btn-execute-sale');

    // Estado de riesgo actual (para control del checkbox)
    let currentPriceRisk = { isRisk: false };

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            if (!cart.item) {
                e.preventDefault();
                e.stopPropagation();
                alert('Agrega un producto al carrito primero.');
                return;
            }

            const item = cart.item;
            const unitPriceFinal = unitPriceFinalInput.value || item.price_original;
            const discountReason = discountReasonInput.value || '';

            // Mostrar SOLO datos capturados como TEXTO OPACO
            let html = `
                <div class="space-y-3">
                    <div class="text-lg">
                        <strong>Producto:</strong> ${item.name}
                    </div>
                    <div class="text-lg">
                        <strong>Cantidad:</strong> ${cart.quantity}
                    </div>
                    <div class="text-lg">
                        <strong>Precio original:</strong> $${item.price_original} c/u
                    </div>
                    <div class="text-lg">
                        <strong>Precio final:</strong> $${unitPriceFinal} c/u
                    </div>
            `;

            if (discountReason) {
                html += `
                    <div class="text-lg">
                        <strong>Motivo descuento:</strong> ${discountReason}
                    </div>
                `;
            }

            html += `
                    <div class="text-lg">
                        <strong>Método de pago:</strong> ${cart.payment_method.toUpperCase()}
                    </div>
                    <div class="text-lg">
                        <strong>Aplicar IVA:</strong> ${cart.apply_iva ? 'SÍ' : 'NO'}
                    </div>

                    <div class="border-t-2 border-black pt-4 mt-4 bg-gray-100 p-3 rounded">
                        <p class="text-center text-gray-700 font-medium">
                            El total será calculado por el servidor.
                        </p>
                    </div>
                </div>
            `;

            document.getElementById('modal-confirm-content').innerHTML = html;

            // =====================================================================
            // R01/R02: Verificar precio de riesgo y mostrar alerta en modal
            // =====================================================================
            currentPriceRisk = checkPriceRisk(item.price_original, unitPriceFinal);

            if (currentPriceRisk.isRisk) {
                // Mostrar alerta
                modalPriceAlert.classList.remove('hidden');
                modalAlertTitle.textContent = currentPriceRisk.title;
                modalAlertText.textContent = currentPriceRisk.text;

                // Configurar checkbox y label
                const finalPrice = parseFloat(unitPriceFinal) || 0;
                confirmPriceLabel.textContent = `Confirmo que el precio $${finalPrice.toFixed(2)} es correcto`;
                confirmPriceCheckbox.checked = false;

                // DESHABILITAR botón hasta confirmar
                btnExecuteSale.disabled = true;
            } else {
                // Ocultar alerta, habilitar botón
                modalPriceAlert.classList.add('hidden');
                confirmPriceCheckbox.checked = false;
                btnExecuteSale.disabled = false;
            }
        });
    }

    // =========================================================================
    // CHECKBOX DE CONFIRMACIÓN - Controla habilitación del botón
    // =========================================================================
    confirmPriceCheckbox.addEventListener('change', function() {
        if (currentPriceRisk.isRisk) {
            btnExecuteSale.disabled = !this.checked;
        }
    });

    // =========================================================================
    // EJECUTAR VENTA - POST /pos/sale (ENVÍA VALORES TAL CUAL)
    // =========================================================================
    document.getElementById('btn-execute-sale').addEventListener('click', async function() {
        if (!cart.item) return;

        this.disabled = true;
        this.textContent = 'PROCESANDO...';

        const item = cart.item;
        const unitPriceFinal = unitPriceFinalInput.value || item.price_original;
        const discountReason = discountReasonInput.value || null;

        // PAYLOAD: valores tal cual capturados
        const payload = {
            product_variant_id: item.variant_id,
            quantity: cart.quantity,
            unit_price_original: item.price_original,
            unit_price_final: unitPriceFinal,
            discount_reason: discountReason,
            payment_method_note: cart.payment_method,
            apply_iva: cart.apply_iva
        };

        try {
            const response = await fetch('{{ route("pos.sale") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            closeModal('modal-confirm');

            // RENDERIZAR RESPUESTA DEL BACKEND COMO TEXTO OPACO
            showResult(data.success, data);

        } catch (error) {
            closeModal('modal-confirm');
            showResult(false, { error: 'Error de conexión: ' + error.message });
        }

        this.disabled = false;
        this.textContent = 'EJECUTAR VENTA';
    });

    // =========================================================================
    // MOSTRAR RESULTADO (RENDERIZA data.data.* COMO TEXTO OPACO)
    // =========================================================================
    function showResult(success, data) {
        const header = document.getElementById('modal-result-header');
        const title = document.getElementById('modal-result-title');
        const content = document.getElementById('modal-result-content');
        const footer = document.getElementById('modal-result-footer');

        if (success) {
            header.classList.remove('pos-modal-header-error');
            header.classList.add('pos-modal-header-success');
            title.textContent = 'VENTA EXITOSA';

            lastSuccessfulSale = {
                order_id: data.data.order_id,
                order_number: data.data.order_number,
                product_name: cart.item ? cart.item.name : 'Producto',
                quantity: data.data.quantity,
                total: data.data.total
            };

            let resultHtml = `
                <div class="pos-result-icon pos-result-icon-success">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="pos-result-title pos-result-title-success">VENTA REGISTRADA</p>
                <div class="pos-result-details">
                    <p><strong>Pedido:</strong> ${data.data.order_number}</p>
                    <p><strong>Cantidad:</strong> ${data.data.quantity}</p>
                    <p><strong>Subtotal:</strong> $${data.data.subtotal}</p>
            `;

            if (data.data.discount_display) {
                resultHtml += `<p><strong>Descuento:</strong> $${data.data.discount}</p>`;
            }

            if (data.data.iva_display) {
                resultHtml += `<p><strong>IVA (${data.data.iva_rate}%):</strong> $${data.data.iva_amount}</p>`;
            }

            resultHtml += `
                    <p class="pos-result-total"><strong>TOTAL:</strong> $${data.data.total}</p>
                    <p><strong>Stock restante:</strong> ${data.data.stock_after}</p>
                    <p><strong>Vendedor:</strong> ${data.data.seller_name}</p>
                </div>
            `;

            content.innerHTML = resultHtml;

            footer.innerHTML = `
                <button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full">
                    CERRAR
                </button>
            `;

            document.getElementById('btn-result-close').addEventListener('click', handleResultClose);

        } else {
            header.classList.remove('pos-modal-header-success');
            header.classList.add('pos-modal-header-error');
            title.textContent = 'ERROR';

            content.innerHTML = `
                <div class="pos-result-icon pos-result-icon-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="pos-result-title pos-result-title-error">NO SE PUDO REGISTRAR</p>
                <p class="pos-modal-text-secondary">${data.error || 'Error desconocido'}</p>
            `;

            footer.innerHTML = `
                <button id="btn-result-close" class="pos-modal-btn pos-modal-btn-primary pos-modal-btn-full">
                    CERRAR
                </button>
            `;

            document.getElementById('btn-result-close').addEventListener('click', handleResultClose);
            lastSuccessfulSale = null;
        }

        openModal('modal-result');
    }

    // =========================================================================
    // HANDLER PARA CERRAR RESULTADO
    // =========================================================================
    function handleResultClose() {
        closeModal('modal-result');
        const header = document.getElementById('modal-result-header');
        if (header.classList.contains('pos-modal-header-success')) {
            clearCart();
            window.location.reload();
        }
    }

    // =========================================================================
    // CERRAR RESULTADO Y RESET (evento inicial, se re-bindea dinámicamente)
    // =========================================================================
    const btnResultClose = document.getElementById('btn-result-close');
    if (btnResultClose) {
        btnResultClose.addEventListener('click', handleResultClose);
    }

    // =========================================================================
    // LIMPIAR CARRITO
    // =========================================================================
    document.getElementById('btn-clear-cart').addEventListener('click', function() {
        clearCart();
        closeModal('modal-cancel');
    });

    // =========================================================================
    // NOTA: La funcionalidad de CANCELACIÓN de ventas POS fue movida al
    // Historial de Ventas POS (admin/pos-sales). El POS activo SOLO vende.
    // =========================================================================

    // =========================================================================
    // INICIALIZAR
    // =========================================================================
    renderCart();

});
</script>
@endpush
