{{-- Customer Selector - Enterprise SaaS Design 2025 - Compact --}}
<div class="pos-customer-section">
    {{-- Estado: Botones --}}
    <div class="pos-customer-options" id="customer-options">
        <button type="button" id="btn-venta-libre" class="pos-option-chip pos-option-active">
            <i class="fas fa-store"></i>
            <span>Venta Libre</span>
        </button>
        <button type="button" id="btn-buscar-cliente" class="pos-option-chip pos-option-search">
            <i class="fas fa-user-plus"></i>
            <span>Cliente</span>
            <i class="fas fa-chevron-right pos-chip-arrow"></i>
        </button>
    </div>

    {{-- Estado: Cliente Seleccionado --}}
    <div id="cliente-selected-card" class="pos-cliente-chip" style="display: none;">
        <div class="pos-cliente-chip-avatar" id="cliente-avatar">J</div>
        <div class="pos-cliente-chip-info">
            <strong id="cliente-nombre">Juan Perez</strong>
            <span id="cliente-telefono">555-1234</span>
        </div>
        <button type="button" id="btn-quitar-cliente" class="pos-cliente-chip-remove">
            <i class="fas fa-times"></i>
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
                           placeholder="Buscar por nombre, apellidos o telefono..."
                           autocomplete="off">
                    <div class="pos-search-input-hint">
                        <kbd>ESC</kbd> cerrar
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
                        <span>Escribe un nombre, apellido o telefono para comenzar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ============================================
       CUSTOMER SECTION - COMPACT
       ============================================ */
    .pos-customer-section {
        padding: var(--pos-space-sm) var(--pos-space-md);
        border-bottom: 1px solid var(--pos-slate-100);
        background: var(--pos-white);
    }

    /* ============================================
       OPTION CHIPS - INLINE
       ============================================ */
    .pos-customer-options {
        display: flex;
        gap: 8px;
    }

    .pos-option-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: var(--pos-slate-50);
        border: 1.5px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-full);
        cursor: pointer;
        transition: var(--pos-transition);
        font-size: 12px;
        font-weight: 600;
        color: var(--pos-slate-600);
    }

    .pos-option-chip:hover {
        background: var(--pos-slate-100);
        border-color: var(--pos-slate-300);
    }

    .pos-option-chip.pos-option-active {
        background: var(--pos-primary);
        border-color: var(--pos-primary);
        color: var(--pos-white);
    }

    .pos-option-chip i {
        font-size: 11px;
    }

    .pos-option-chip.pos-option-active i {
        color: var(--pos-white);
    }

    .pos-option-chip.pos-option-search {
        background: var(--pos-white);
        border-color: var(--pos-success);
        color: var(--pos-success);
    }

    .pos-option-chip.pos-option-search:hover {
        background: rgba(16, 185, 129, 0.08);
    }

    .pos-chip-arrow {
        font-size: 10px !important;
        margin-left: 2px;
        transition: transform 0.15s;
    }

    .pos-option-chip:hover .pos-chip-arrow {
        transform: translateX(2px);
    }

    /* ============================================
       CLIENTE CHIP (SELECTED)
       ============================================ */
    .pos-cliente-chip {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 10px;
        background: rgba(79, 70, 229, 0.08);
        border: 1.5px solid var(--pos-primary);
        border-radius: var(--pos-radius-full);
    }

    .pos-cliente-chip-avatar {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-primary);
        border-radius: 50%;
        color: var(--pos-white);
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pos-cliente-chip-info {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pos-cliente-chip-info strong {
        font-size: 12px;
        font-weight: 600;
        color: var(--pos-primary-dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-cliente-chip-info span {
        font-size: 11px;
        color: var(--pos-slate-500);
    }

    .pos-cliente-chip-remove {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background: var(--pos-danger);
        border: none;
        border-radius: 50%;
        color: var(--pos-white);
        font-size: 10px;
        cursor: pointer;
        transition: var(--pos-transition);
        flex-shrink: 0;
    }

    .pos-cliente-chip-remove:hover {
        background: var(--pos-danger-dark);
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
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.2s ease;
    }

    .pos-search-modal-container {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 80px 20px 20px;
        overflow-y: auto;
    }

    .pos-search-modal-content {
        width: 100%;
        max-width: 520px;
        background: var(--pos-white);
        border-radius: var(--pos-radius-lg);
        box-shadow: var(--pos-shadow-xl);
        animation: slideDown 0.25s ease;
        overflow: hidden;
    }

    /* Modal Header */
    .pos-search-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--pos-space-md) var(--pos-space-lg);
        background: var(--pos-slate-900);
        border-bottom: 1px solid var(--pos-slate-700);
    }

    .pos-search-modal-title {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        color: var(--pos-white);
        font-size: 16px;
        font-weight: 600;
    }

    .pos-search-modal-title i {
        font-size: 18px;
        color: var(--pos-primary-light);
    }

    .pos-search-modal-close {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-800);
        border: 1px solid var(--pos-slate-700);
        border-radius: var(--pos-radius-sm);
        color: var(--pos-slate-300);
        font-size: 14px;
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-search-modal-close:hover {
        background: var(--pos-slate-700);
        color: var(--pos-white);
    }

    /* Modal Search */
    .pos-search-modal-search {
        padding: var(--pos-space-md) var(--pos-space-lg);
        background: var(--pos-slate-50);
        border-bottom: 1px solid var(--pos-slate-200);
    }

    .pos-search-modal-search .pos-search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .pos-search-modal-search .pos-search-input-icon {
        position: absolute;
        left: 16px;
        color: var(--pos-slate-400);
        font-size: 14px;
        pointer-events: none;
    }

    .pos-search-modal-search .pos-search-input {
        width: 100%;
        height: 48px;
        padding: 0 120px 0 44px;
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        font-size: 14px;
        color: var(--pos-slate-800);
        transition: var(--pos-transition);
    }

    .pos-search-modal-search .pos-search-input:focus {
        outline: none;
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .pos-search-modal-search .pos-search-input::placeholder {
        color: var(--pos-slate-400);
    }

    .pos-search-input-hint {
        position: absolute;
        right: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: var(--pos-slate-400);
    }

    .pos-search-input-hint kbd {
        padding: 3px 6px;
        background: var(--pos-slate-100);
        border: 1px solid var(--pos-slate-200);
        border-radius: 4px;
        font-family: inherit;
        font-size: 10px;
        font-weight: 600;
        color: var(--pos-slate-500);
    }

    /* Modal Results */
    .pos-search-modal-results {
        max-height: 350px;
        overflow-y: auto;
        padding: var(--pos-space-md) var(--pos-space-lg);
    }

    /* Placeholder State */
    .pos-search-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--pos-space-2xl) var(--pos-space-lg);
        text-align: center;
    }

    .pos-search-placeholder-icon {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--pos-slate-100);
        border-radius: var(--pos-radius-md);
        margin-bottom: var(--pos-space-md);
    }

    .pos-search-placeholder-icon i {
        font-size: 28px;
        color: var(--pos-slate-400);
    }

    .pos-search-placeholder-text strong {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: var(--pos-slate-700);
        margin-bottom: 4px;
    }

    .pos-search-placeholder-text span {
        font-size: 13px;
        color: var(--pos-slate-500);
    }

    /* Loading State */
    .pos-search-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--pos-space-2xl);
    }

    .pos-search-loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--pos-slate-200);
        border-top-color: var(--pos-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-bottom: var(--pos-space-md);
    }

    .pos-search-loading span {
        font-size: 13px;
        color: var(--pos-slate-500);
    }

    /* Result Items */
    .pos-result-list {
        display: flex;
        flex-direction: column;
        gap: var(--pos-space-sm);
    }

    .pos-result-item {
        display: flex;
        align-items: center;
        gap: var(--pos-space-md);
        padding: var(--pos-space-md);
        background: var(--pos-white);
        border: 2px solid var(--pos-slate-200);
        border-radius: var(--pos-radius-md);
        cursor: pointer;
        transition: var(--pos-transition);
    }

    .pos-result-item:hover {
        background: var(--pos-slate-50);
        border-color: var(--pos-primary);
        transform: translateX(4px);
    }

    .pos-result-item:hover .pos-result-item-arrow {
        opacity: 1;
        transform: translateX(4px);
    }

    .pos-result-item-avatar {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--pos-primary) 0%, var(--pos-primary-dark) 100%);
        border-radius: var(--pos-radius-sm);
        color: var(--pos-white);
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pos-result-item-info {
        flex: 1;
        min-width: 0;
    }

    .pos-result-item-info strong {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--pos-slate-800);
        margin-bottom: 2px;
    }

    .pos-result-item-info span {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--pos-slate-500);
    }

    .pos-result-item-info span i {
        color: var(--pos-slate-400);
    }

    .pos-result-item-arrow {
        color: var(--pos-primary);
        font-size: 14px;
        opacity: 0;
        transition: var(--pos-transition);
    }

    /* No Results */
    .pos-search-no-results {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--pos-space-2xl);
        text-align: center;
    }

    .pos-search-no-results-icon {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(239, 68, 68, 0.1);
        border-radius: var(--pos-radius-md);
        margin-bottom: var(--pos-space-md);
    }

    .pos-search-no-results-icon i {
        font-size: 28px;
        color: var(--pos-danger);
    }

    .pos-search-no-results strong {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: var(--pos-slate-700);
        margin-bottom: 4px;
    }

    .pos-search-no-results span {
        font-size: 13px;
        color: var(--pos-slate-500);
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

    if (btnVentaLibre) {
        btnVentaLibre.addEventListener('click', setVentaLibre);
    }

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

    function resetSearch() {
        if (inputBuscar) inputBuscar.value = '';
        if (resultadosDiv) {
            resultadosDiv.innerHTML = '<div class="pos-search-placeholder">' +
                '<div class="pos-search-placeholder-icon">' +
                '<i class="fas fa-users"></i>' +
                '</div>' +
                '<div class="pos-search-placeholder-text">' +
                '<strong>Buscar clientes</strong>' +
                '<span>Escribe un nombre, apellido o telefono para comenzar</span>' +
                '</div>' +
                '</div>';
        }
    }

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
                    '<strong>Error de conexion</strong>' +
                    '<span>No se pudo realizar la busqueda</span>' +
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
            var tel = c.telefono || 'Sin telefono';

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
