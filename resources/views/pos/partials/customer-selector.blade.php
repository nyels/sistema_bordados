{{-- Customer Selector - Enterprise SaaS Style --}}
<div class="pos-customer-section">
    <div class="pos-section-header">
        <svg class="pos-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <span>CLIENTE</span>
    </div>

    {{-- Estado: Botones --}}
    <div class="pos-customer-options" id="customer-options">
        <button type="button" id="btn-venta-libre" class="pos-option-btn pos-option-active">
            <div class="pos-option-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="pos-option-text">
                <strong>Venta Libre</strong>
                <span>Público general</span>
            </div>
            <div class="pos-option-check">
                <i class="fas fa-check-circle"></i>
            </div>
        </button>

        <button type="button" id="btn-buscar-cliente" class="pos-option-btn">
            <div class="pos-option-icon pos-option-icon-search">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="pos-option-text">
                <strong>Buscar Cliente</strong>
                <span>Cliente registrado</span>
            </div>
            <div class="pos-option-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>
    </div>

    {{-- Estado: Cliente Seleccionado --}}
    <div id="cliente-selected-card" class="pos-cliente-card" style="display: none;">
        <div class="pos-cliente-card-avatar" id="cliente-avatar">J</div>
        <div class="pos-cliente-card-info">
            <strong id="cliente-nombre">Juan Pérez</strong>
            <span id="cliente-telefono"><i class="fas fa-phone"></i> 555-1234</span>
        </div>
        <button type="button" id="btn-quitar-cliente" class="pos-cliente-card-remove">
            <i class="fas fa-times"></i>
            <span>Quitar</span>
        </button>
    </div>

    <input type="hidden" id="cliente-id" name="cliente_id" value="">
</div>

{{-- MODAL BUSCAR CLIENTE --}}
<div class="pos-search-modal" id="modal-cliente">
    <div class="pos-search-modal-backdrop"></div>
    <div class="pos-search-modal-container">
        <div class="pos-search-modal-content">
            {{-- Header --}}
            <div class="pos-search-modal-header">
                <div class="pos-search-modal-title">
                    <i class="fas fa-user-plus"></i>
                    <span>Buscar Cliente</span>
                </div>
                <button type="button" class="pos-search-modal-close" id="btn-cerrar-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Search Input --}}
            <div class="pos-search-modal-search">
                <div class="pos-search-input-wrapper">
                    <i class="fas fa-search pos-search-input-icon"></i>
                    <input type="text"
                           id="input-buscar-cliente"
                           class="pos-search-input"
                           placeholder="Buscar por nombre, apellidos o teléfono..."
                           autocomplete="off">
                    <div class="pos-search-input-hint">
                        <kbd>ESC</kbd> para cerrar
                    </div>
                </div>
            </div>

            {{-- Results --}}
            <div class="pos-search-modal-results" id="resultados-clientes">
                <div class="pos-search-placeholder">
                    <div class="pos-search-placeholder-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="pos-search-placeholder-text">
                        <strong>Buscar clientes</strong>
                        <span>Escribe un nombre, apellido o teléfono para comenzar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ============================================
       CUSTOMER SECTION
       ============================================ */
    .pos-customer-section {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .pos-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        letter-spacing: 0.05em;
    }

    .pos-section-icon {
        width: 16px;
        height: 16px;
    }

    /* ============================================
       OPTION BUTTONS
       ============================================ */
    .pos-customer-options {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pos-option-btn {
        display: flex;
        align-items: center;
        gap: 14px;
        width: 100%;
        padding: 14px 16px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: left;
    }

    .pos-option-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .pos-option-btn.pos-option-active {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .pos-option-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #3b82f6;
        border-radius: 10px;
        color: #fff;
        font-size: 16px;
        flex-shrink: 0;
    }

    .pos-option-icon-search {
        background: #6366f1;
    }

    .pos-option-text {
        flex: 1;
    }

    .pos-option-text strong {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 2px;
    }

    .pos-option-text span {
        font-size: 12px;
        color: #6b7280;
    }

    .pos-option-check {
        color: #3b82f6;
        font-size: 20px;
        opacity: 0;
        transition: opacity 0.15s;
    }

    .pos-option-active .pos-option-check {
        opacity: 1;
    }

    .pos-option-arrow {
        color: #9ca3af;
        font-size: 14px;
        transition: transform 0.15s;
    }

    .pos-option-btn:hover .pos-option-arrow {
        transform: translateX(4px);
        color: #6366f1;
    }

    /* ============================================
       CLIENTE CARD (SELECTED)
       ============================================ */
    .pos-cliente-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 2px solid #3b82f6;
        border-radius: 12px;
    }

    .pos-cliente-card-avatar {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-radius: 12px;
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pos-cliente-card-info {
        flex: 1;
        min-width: 0;
    }

    .pos-cliente-card-info strong {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-cliente-card-info span {
        font-size: 13px;
        color: #3b82f6;
    }

    .pos-cliente-card-info span i {
        margin-right: 6px;
    }

    .pos-cliente-card-remove {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        color: #dc2626;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        flex-shrink: 0;
    }

    .pos-cliente-card-remove:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
    }

    /* ============================================
       MODAL - ENTERPRISE STYLE
       ============================================ */
    .pos-search-modal {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: none;
    }

    .pos-search-modal.active {
        display: block;
    }

    .pos-search-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(17, 24, 39, 0.75);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .pos-search-modal-container {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 60px 20px 20px;
        overflow-y: auto;
    }

    .pos-search-modal-content {
        width: 100%;
        max-width: 560px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25),
                    0 0 0 1px rgba(0, 0, 0, 0.05);
        animation: slideDown 0.25s ease;
        overflow: hidden;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Modal Header */
    .pos-search-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        border-bottom: 1px solid #374151;
    }

    .pos-search-modal-title {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
    }

    .pos-search-modal-title i {
        font-size: 20px;
        opacity: 0.9;
    }

    .pos-search-modal-close {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .pos-search-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* Modal Search */
    .pos-search-modal-search {
        padding: 20px 24px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    .pos-search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .pos-search-input-icon {
        position: absolute;
        left: 16px;
        color: #9ca3af;
        font-size: 16px;
        pointer-events: none;
    }

    .pos-search-input {
        width: 100%;
        height: 52px;
        padding: 0 140px 0 48px;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        color: #111827;
        transition: all 0.15s;
    }

    .pos-search-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .pos-search-input::placeholder {
        color: #9ca3af;
    }

    .pos-search-input-hint {
        position: absolute;
        right: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #9ca3af;
    }

    .pos-search-input-hint kbd {
        padding: 4px 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
    }

    /* Modal Results */
    .pos-search-modal-results {
        max-height: 400px;
        overflow-y: auto;
        padding: 16px 24px 24px;
    }

    .pos-search-modal-results::-webkit-scrollbar {
        width: 8px;
    }

    .pos-search-modal-results::-webkit-scrollbar-track {
        background: #f3f4f6;
    }

    .pos-search-modal-results::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }

    .pos-search-modal-results::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    /* Placeholder State */
    .pos-search-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        text-align: center;
    }

    .pos-search-placeholder-icon {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border-radius: 16px;
        margin-bottom: 20px;
    }

    .pos-search-placeholder-icon i {
        font-size: 32px;
        color: #9ca3af;
    }

    .pos-search-placeholder-text strong {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .pos-search-placeholder-text span {
        font-size: 14px;
        color: #6b7280;
    }

    /* Loading State */
    .pos-search-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
    }

    .pos-search-loading-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: 16px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .pos-search-loading span {
        font-size: 14px;
        color: #6b7280;
    }

    /* Result Items */
    .pos-result-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pos-result-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .pos-result-item:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        transform: translateX(4px);
    }

    .pos-result-item:hover .pos-result-item-arrow {
        opacity: 1;
        transform: translateX(4px);
    }

    .pos-result-item-avatar {
        width: 52px;
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border-radius: 12px;
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pos-result-item-info {
        flex: 1;
        min-width: 0;
    }

    .pos-result-item-info strong {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }

    .pos-result-item-info span {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #6b7280;
    }

    .pos-result-item-info span i {
        color: #9ca3af;
    }

    .pos-result-item-arrow {
        color: #3b82f6;
        font-size: 16px;
        opacity: 0;
        transition: all 0.15s;
    }

    /* No Results */
    .pos-search-no-results {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        text-align: center;
    }

    .pos-search-no-results-icon {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        border-radius: 16px;
        margin-bottom: 20px;
    }

    .pos-search-no-results-icon i {
        font-size: 32px;
        color: #ef4444;
    }

    .pos-search-no-results strong {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .pos-search-no-results span {
        font-size: 14px;
        color: #6b7280;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnVentaLibre = document.getElementById('btn-venta-libre');
    var btnBuscarCliente = document.getElementById('btn-buscar-cliente');
    var modal = document.getElementById('modal-cliente');
    var backdrop = modal ? modal.querySelector('.pos-search-modal-backdrop') : null;
    var btnCerrarModal = document.getElementById('btn-cerrar-modal');
    var inputBuscar = document.getElementById('input-buscar-cliente');
    var resultadosDiv = document.getElementById('resultados-clientes');
    var customerOptions = document.getElementById('customer-options');
    var clienteCard = document.getElementById('cliente-selected-card');
    var clienteNombre = document.getElementById('cliente-nombre');
    var clienteTelefono = document.getElementById('cliente-telefono');
    var clienteAvatar = document.getElementById('cliente-avatar');
    var clienteIdInput = document.getElementById('cliente-id');
    var btnQuitarCliente = document.getElementById('btn-quitar-cliente');

    var debounceTimer = null;

    // Venta Libre
    if (btnVentaLibre) {
        btnVentaLibre.addEventListener('click', setVentaLibre);
    }

    // Abrir modal
    if (btnBuscarCliente && modal) {
        btnBuscarCliente.addEventListener('click', function() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(function() {
                if (inputBuscar) inputBuscar.focus();
            }, 100);
            resetSearch();
        });
    }

    // Cerrar modal
    function closeModal() {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', closeModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Reset search
    function resetSearch() {
        if (inputBuscar) inputBuscar.value = '';
        if (resultadosDiv) {
            resultadosDiv.innerHTML = '<div class="pos-search-placeholder">' +
                '<div class="pos-search-placeholder-icon">' +
                '<i class="fas fa-users"></i>' +
                '</div>' +
                '<div class="pos-search-placeholder-text">' +
                '<strong>Buscar clientes</strong>' +
                '<span>Escribe un nombre, apellido o teléfono para comenzar</span>' +
                '</div>' +
                '</div>';
        }
    }

    // Búsqueda con debounce
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function() {
            var query = this.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 1) {
                resetSearch();
                return;
            }

            if (resultadosDiv) {
                resultadosDiv.innerHTML = '<div class="pos-search-loading">' +
                    '<div class="pos-search-loading-spinner"></div>' +
                    '<span>Buscando clientes...</span>' +
                    '</div>';
            }

            debounceTimer = setTimeout(function() {
                buscarClientes(query);
            }, 250);
        });
    }

    function buscarClientes(query) {
        fetch('{{ route("pos.clientes.search") }}?q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data.length > 0) {
                renderClientes(data.data);
            } else {
                if (resultadosDiv) {
                    resultadosDiv.innerHTML = '<div class="pos-search-no-results">' +
                        '<div class="pos-search-no-results-icon">' +
                        '<i class="fas fa-user-slash"></i>' +
                        '</div>' +
                        '<strong>Sin resultados</strong>' +
                        '<span>No se encontraron clientes con "' + query + '"</span>' +
                        '</div>';
                }
            }
        })
        .catch(function() {
            if (resultadosDiv) {
                resultadosDiv.innerHTML = '<div class="pos-search-no-results">' +
                    '<div class="pos-search-no-results-icon">' +
                    '<i class="fas fa-exclamation-triangle"></i>' +
                    '</div>' +
                    '<strong>Error de conexión</strong>' +
                    '<span>No se pudo realizar la búsqueda</span>' +
                    '</div>';
            }
        });
    }

    function renderClientes(clientes) {
        var html = '<div class="pos-result-list">';
        for (var i = 0; i < clientes.length; i++) {
            var c = clientes[i];
            var inicial = (c.nombre || 'C').charAt(0).toUpperCase();
            var nombre = ((c.nombre || '') + ' ' + (c.apellidos || '')).trim();
            var tel = c.telefono || 'Sin teléfono';

            html += '<div class="pos-result-item" data-id="' + c.id + '" data-nombre="' + nombre + '" data-telefono="' + tel + '">' +
                '<div class="pos-result-item-avatar">' + inicial + '</div>' +
                '<div class="pos-result-item-info">' +
                '<strong>' + nombre + '</strong>' +
                '<span><i class="fas fa-phone"></i>' + tel + '</span>' +
                '</div>' +
                '<i class="fas fa-arrow-right pos-result-item-arrow"></i>' +
                '</div>';
        }
        html += '</div>';

        if (resultadosDiv) {
            resultadosDiv.innerHTML = html;

            var items = document.querySelectorAll('.pos-result-item');
            for (var j = 0; j < items.length; j++) {
                items[j].addEventListener('click', function() {
                    seleccionarCliente(this.dataset.id, this.dataset.nombre, this.dataset.telefono);
                });
            }
        }
    }

    function seleccionarCliente(id, nombre, telefono) {
        if (clienteIdInput) clienteIdInput.value = id;
        if (clienteNombre) clienteNombre.textContent = nombre;
        if (clienteTelefono) clienteTelefono.innerHTML = '<i class="fas fa-phone"></i> ' + telefono;
        if (clienteAvatar) clienteAvatar.textContent = nombre.charAt(0).toUpperCase();

        if (customerOptions) customerOptions.style.display = 'none';
        if (clienteCard) clienteCard.style.display = 'flex';
        closeModal();
    }

    function setVentaLibre() {
        if (clienteIdInput) clienteIdInput.value = '';
        if (customerOptions) customerOptions.style.display = 'flex';
        if (clienteCard) clienteCard.style.display = 'none';
        if (btnVentaLibre) btnVentaLibre.classList.add('pos-option-active');
        if (btnBuscarCliente) btnBuscarCliente.classList.remove('pos-option-active');
    }

    if (btnQuitarCliente) {
        btnQuitarCliente.addEventListener('click', setVentaLibre);
    }
});
</script>
@endpush
