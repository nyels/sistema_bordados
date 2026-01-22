@extends('adminlte::page')

@section('title', isset($isEdit) ? 'Editar Pedido' : 'Nuevo Pedido')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clipboard-list mr-2"></i> {{ isset($isEdit) ? 'Editar Pedido #' . $order->order_number : 'Nuevo Pedido' }}</h1>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <style>
        /* ================================================== */
        /* === ERP LAYOUT: ALTURAS FIJAS, SIN SALTOS ======== */
        /* ================================================== */

        /* Cards con altura mínima para estabilidad */
        .card-erp {
            margin-bottom: 1rem;
        }

        .card-erp .card-body {
            min-height: 80px;
        }

        /* Secciones deshabilitadas (siempre visibles) */
        .section-disabled {
            opacity: 0.5;
            pointer-events: none;
            user-select: none;
        }

        .section-disabled .form-control,
        .section-disabled .custom-control-input {
            background-color: #e9ecef;
        }

        /* ================================================== */
        /* === 1. CLIENTE: BOTÓN SELECTOR (NO SELECT2) ====== */
        /* ================================================== */
        .cliente-selector-btn {
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s ease;
            min-height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cliente-selector-btn:hover {
            border-color: #7f00ff;
            background: #faf5ff;
        }

        .cliente-selector-btn.has-client {
            border-style: solid;
            border-color: #28a745;
            background: #f0fff4;
        }

        .cliente-selector-btn .cliente-info {
            display: flex;
            flex-direction: column;
        }

        .cliente-selector-btn .cliente-nombre {
            font-weight: 600;
            color: #212529;
        }

        .cliente-selector-btn .cliente-telefono {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .cliente-selector-btn .placeholder-text {
            color: #6c757d;
        }

        /* ================================================== */
        /* === MODAL BÚSQUEDA CLIENTES ERP ================== */
        /* ================================================== */
        #clientSearchModal .modal-body {
            padding: 0;
        }

        .client-search-container {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .client-search-input {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }

        .client-results-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .client-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            transition: background 0.15s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .client-result-item:hover {
            background: #f8f9fa;
        }

        .client-result-item:last-child {
            border-bottom: none;
        }

        .client-result-item .client-name {
            font-weight: 600;
            color: #212529;
        }

        .client-result-item .client-phone {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .client-result-item .no-phone-badge {
            font-size: 0.75rem;
            color: #dc3545;
        }

        .client-results-empty {
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }

        .client-results-loading {
            padding: 2rem;
            text-align: center;
        }

        .client-create-footer {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        /* ================================================== */
        /* === 2. MEDIDAS: ALTURA FIJA ====================== */
        /* ================================================== */
        .medidas-section {
            min-height: 120px;
        }

        .medidas-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 80px;
            color: #adb5bd;
            font-style: italic;
        }

        /* ================================================== */
        /* === 5. PRODUCTOS: SCROLL FIJO ==================== */
        /* ================================================== */
        .products-scroll-container {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .products-scroll-container::-webkit-scrollbar {
            width: 6px;
        }

        .products-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .products-scroll-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .product-table th,
        .product-table td {
            vertical-align: middle;
        }

        .product-image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        /* Contador de items */
        .items-counter {
            font-size: 0.85rem;
            padding: 0.25em 0.5em;
        }

        /* Header productos con flex */
        .productos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* ================================================== */
        /* === 3. PAGO: SIEMPRE VISIBLE ===================== */
        /* ================================================== */
        .payment-section .form-group {
            margin-bottom: 0.75rem;
        }

        /* ================================================== */
        /* === 4. ENTREGA: SIEMPRE VISIBLE ================== */
        /* ================================================== */
        .urgency-badge {
            font-size: 0.9rem;
            padding: 0.4em 0.8em;
        }

        /* ================================================== */
        /* === 6. RESUMEN CON IVA INTEGRADO ================= */
        /* ================================================== */
        .resumen-card {
            position: relative;
        }

        .resumen-totals {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }

        .resumen-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
        }

        .resumen-row.total-final {
            border-top: 2px solid #212529;
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.25rem;
        }

        /* IVA row en resumen */
        .iva-row-resumen {
            padding: 0.5rem 0;
            border-top: 1px dashed #dee2e6;
            border-bottom: 1px dashed #dee2e6;
            margin: 0.25rem 0;
        }

        .iva-row-resumen .custom-control-label {
            font-weight: 500;
        }

        .iva-amount-disabled {
            color: #adb5bd;
        }

        .iva-amount-active {
            color: #212529;
            font-weight: 600;
        }

        /* ================================================== */
        /* === LAYOUT RESPONSIVO ============================ */
        /* ================================================== */

        /* Móvil/Tablet: usar flexbox con order para secuencia correcta */
        @media (max-width: 991px) {
            .erp-row {
                display: flex;
                flex-direction: column;
            }

            /* Orden: 1.Cliente → 2.Productos → 3.Pago → 4.Entrega → 5.Resumen → 6.Notas → 7.Botón */
            .order-mobile-1 { order: 1; }
            .order-mobile-2 { order: 2; }
            .order-mobile-3 { order: 3; }
            .order-mobile-4 { order: 4; }
            .order-mobile-5 { order: 5; }
            .order-mobile-6 { order: 6; }
            .order-mobile-7 { order: 7; }

            /* Las columnas deben ser hijos directos para que order funcione */
            .main-column,
            .sidebar-column {
                display: contents;
            }

            .products-scroll-container {
                max-height: 250px;
            }
        }

        @media (min-width: 992px) {
            .main-column {
                padding-right: 0.5rem;
            }

            .sidebar-column {
                padding-left: 0.5rem;
            }
        }

        @media (max-width: 767px) {
            .content-header h1 {
                font-size: 1.25rem;
            }

            .card-body {
                padding: 0.75rem;
            }

            .product-table thead {
                display: none;
            }

            .product-table tbody tr {
                display: flex;
                flex-wrap: wrap;
                padding: 0.75rem;
                border-bottom: 1px solid #dee2e6;
                position: relative;
            }

            .product-table tbody tr td {
                border: none !important;
                padding: 0.25rem !important;
            }

            .product-table tbody tr td:nth-child(1) {
                width: 60px;
                order: 1;
            }

            .product-table tbody tr td:nth-child(2) {
                width: calc(100% - 100px);
                order: 2;
                padding-left: 0.5rem !important;
            }

            .product-table tbody tr td:nth-child(6) {
                width: 40px;
                order: 3;
                position: absolute;
                right: 0.5rem;
                top: 0.75rem;
            }

            .product-table tbody tr td:nth-child(3),
            .product-table tbody tr td:nth-child(4),
            .product-table tbody tr td:nth-child(5) {
                width: 33%;
                order: 4;
                margin-top: 0.5rem;
            }

            #noItemsRow td {
                width: 100% !important;
                display: block !important;
            }

            .productos-header {
                flex-direction: column;
                align-items: stretch !important;
            }

            .productos-header h5 {
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .productos-header .btn {
                width: 100%;
            }
        }

        /* ================================================== */
        /* === CSS MODAL MEDIDAS EXTERNO — ELIMINADO (FASE 1)
             El modal #measurementsModal ya no existe.
             Clases eliminadas: .medida-card, .medida-img,
             .medida-input, .medida-label, .medida-hint, .medidas-grid,
             .measurement-history-item
             ================================================== */

        /* Badge requiere medidas (se mantiene para tabla de items) */
        .badge-requires-measurements {
            background: #343a40;
            color: #fff;
            font-size: 0.7rem;
            padding: 0.2em 0.5em;
            border-radius: 3px;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-xs {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.3;
        }

        /* ================================================== */
        /* === MODAL PRODUCTO (PRESERVADO) ================== */
        /* ================================================== */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--open {
            z-index: 9999 !important;
        }

        #addProductModal .modal-body,
        #quickClientModal .modal-body {
            max-height: 65vh;
            overflow-y: auto;
        }

        #addProductModal .modal-footer,
        #quickClientModal .modal-footer {
            position: sticky;
            bottom: 0;
            background: #fff;
        }

        @media (max-width: 768px) {
            #addProductModal .modal-dialog {
                max-width: 95vw;
            }
            /* REQUISITOS DEL PRODUCTO - Mobile */
            #measurementsSection .card-header {
                padding: 10px 12px !important;
            }
            #measurementsSection .card-header strong {
                font-size: 12px !important;
            }
            #measurementsSection .card-header small {
                font-size: 10px !important;
            }
            #measurementsSection .card-body {
                padding: 12px !important;
            }
            #measurementsSection #btnOpenMeasurementsModal {
                width: 100% !important;
                margin-top: 12px !important;
                padding: 12px 16px !important;
                font-size: 13px !important;
            }
            #measurementsStatusBadge {
                display: block !important;
                margin-top: 6px !important;
                margin-left: 0 !important;
            }
            /* Separador semántico - Mobile */
            #systemClientDivider span {
                font-size: 10px !important;
            }
        }

        @media (max-width: 576px) {
            /* REQUISITOS - Extra small */
            #measurementsSection .card-body > .d-flex {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            #measurementsSection .card-body > .d-flex > div:first-child {
                margin-bottom: 12px !important;
            }
        }

        /* ================================================== */
        /* === MEDIDAS MODAL: CARDS CON IMÁGENES ============ */
        /* ================================================== */
        #measurementsModal .medida-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 10px 10px;
            background: #ffffff;
            transition:
                box-shadow 0.25s ease,
                transform 0.25s ease,
                border-color 0.25s ease;
            cursor: pointer;
            position: relative;
        }

        #measurementsModal .medida-card:hover {
            transform: translateY(-4px);
            border-color: #dee2e6;
            box-shadow:
                0 10px 22px rgba(0, 0, 0, 0.06),
                0 4px 8px rgba(0, 0, 0, 0.04);
        }

        #measurementsModal .medida-img {
            width: 100%;
            max-height: 80px;
            object-fit: contain;
            margin-bottom: 8px;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        #measurementsModal .medida-card:hover .medida-img {
            transform: scale(1.03);
            opacity: 0.95;
        }

        #measurementsModal .medida-input {
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition:
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        #measurementsModal .medida-input:focus {
            border-color: #dee2e6;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.15);
        }

        #measurementsModal .medida-label {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
            color: #212529;
            font-size: 0.75rem;
        }

        #measurementsModal .medida-input.is-invalid {
            border-color: #dc3545;
            box-shadow: none;
        }

        /* Touch: mejoras táctiles para móviles */
        #measurementsModal .medida-card {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
        }

        #measurementsModal .medida-card .medida-input {
            user-select: text;
            -webkit-user-select: text;
            touch-action: auto;
        }

        @media (hover: none) and (pointer: coarse) {
            #measurementsModal .medida-card:hover {
                transform: none;
                border-color: #e5e7eb;
                box-shadow: none;
            }

            #measurementsModal .medida-card:hover .medida-img {
                transform: none;
                opacity: 1;
            }

            #measurementsModal .medida-card:active,
            #measurementsModal .medida-card:focus-within {
                border-color: #dee2e6;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            #measurementsModal .medida-input {
                min-height: 38px;
                font-size: 16px;
            }
        }
    </style>
@stop

@section('content')
    <form action="{{ isset($isEdit) ? route('admin.orders.update', $order) : route('admin.orders.store') }}" method="POST" id="orderForm">
        @csrf
        @if(isset($isEdit))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><i class="fas fa-exclamation-triangle mr-1"></i> Errores:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row erp-row">
            {{-- ============================================== --}}
            {{-- COLUMNA IZQUIERDA: Cliente, Medidas, Pago, Entrega --}}
            {{-- ============================================== --}}
            <div class="col-lg-5 main-column">

                {{-- 1. CLIENTE --}}
                <div class="card card-erp order-mobile-1">
                    <div class="card-header py-2" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-user mr-2"></i> 1. Cliente</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="cliente_id" id="cliente_id" value="{{ old('cliente_id') }}" required>
                        @error('cliente_id')
                            <div class="alert alert-danger py-1 mb-2">{{ $message }}</div>
                        @enderror

                        <button type="button" class="cliente-selector-btn" id="btnSelectClient" data-toggle="modal"
                            data-target="#clientSearchModal">
                            <div class="cliente-info" id="clienteDisplay">
                                <span class="placeholder-text"><i class="fas fa-search mr-1"></i> Buscar cliente...</span>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </button>

                        <div class="mt-2 text-right">
                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                data-target="#quickClientModal">
                                <i class="fas fa-user-plus mr-1"></i> Cliente Rápido
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ======================================================
                     CARD MEDIDAS GLOBAL — ELIMINADO (FASE 1)
                     Las medidas ahora se capturan INLINE en el modal de producto.
                     ====================================================== --}}

                {{-- 3. PAGO --}}
                <div class="card card-erp order-mobile-3">
                    <div class="card-header py-2" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> 3. Pago</h5>
                    </div>
                    <div class="card-body payment-section">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold mb-1">Método de Pago</label>
                            <select name="payment_method" id="paymentMethod" class="form-control form-control-sm">
                                <option value="">-- Sin pago inicial --</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transferencia</option>
                                <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>

                        <div class="form-group mb-2" id="payFullGroup" style="display: none;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="payFull" name="pay_full"
                                    value="1" {{ old('pay_full') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="payFull">
                                    <i class="fas fa-check-circle mr-1"></i> Pagar Total
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-0" id="anticipoGroup" style="display: none;">
                            <label class="mb-1">Anticipo</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="initial_payment" id="initialPayment" class="form-control"
                                    value="{{ old('initial_payment') }}" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. ENTREGA --}}
                <div class="card card-erp order-mobile-4">
                    <div class="card-header py-2" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-truck mr-2"></i> 4. Entrega</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold mb-1">Nivel de Urgencia</label>
                            <select name="urgency_level" id="urgencyLevel" class="form-control form-control-sm">
                                <option value="normal" {{ old('urgency_level', 'normal') == 'normal' ? 'selected' : '' }}>Normal (100% tiempo)</option>
                                <option value="urgente" {{ old('urgency_level') == 'urgente' ? 'selected' : '' }}>Urgente (70% tiempo)</option>
                                <option value="express" {{ old('urgency_level') == 'express' ? 'selected' : '' }}>Express (50% tiempo)</option>
                            </select>
                        </div>

                        <div class="alert alert-info py-1 mb-2" id="minimumDateAlert" style="display: none;">
                            <small>
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Fecha mínima:</strong> <span id="minimumDateDisplay">-</span>
                            </small>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold mb-1">Fecha Prometida</label>
                            <input type="date" name="promised_date" id="promisedDate"
                                class="form-control form-control-sm @error('promised_date') is-invalid @enderror"
                                value="{{ old('promised_date') }}" min="{{ date('Y-m-d') }}" required>
                            @error('promised_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback" id="dateWarning" style="display: none;">
                                Fecha anterior a la mínima de producción
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ============================================== --}}
            {{-- COLUMNA DERECHA: Productos, Resumen, Notas, Botón --}}
            {{-- ============================================== --}}
            <div class="col-lg-7 sidebar-column">

                {{-- 2. PRODUCTOS --}}
                <div class="card card-erp order-mobile-2">
                    <div class="card-header py-2 productos-header" style="background: #343a40; color: white;">
                        <h5 class="mb-0">
                            <i class="fas fa-box mr-2"></i> 2. Productos
                            <span id="itemsCounter" class="badge badge-light items-counter ml-2" style="display:none;">0</span>
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" id="btnAddProduct">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive products-scroll-container">
                            <table class="table table-hover product-table mb-0" id="itemsTable">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th style="width: 60px;">Img</th>
                                        <th>Producto</th>
                                        <th style="width: 80px;">Cant.</th>
                                        <th style="width: 90px;">Días Prod.</th>
                                        <th style="width: 120px;">Subtotal</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <tr id="noItemsRow">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            Click en "Agregar" para añadir productos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 5. RESUMEN (con IVA integrado) --}}
                <div class="card card-erp resumen-card order-mobile-5">
                    <div class="card-header py-2" style="background: #343a40; color: white;">
                        <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> 5. Resumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="resumen-totals">
                            <div class="resumen-row">
                                <span>Subtotal:</span>
                                <strong id="subtotalDisplay">$0.00</strong>
                            </div>
                            <div class="resumen-row">
                                <span>Descuento:</span>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" name="discount" id="discount" class="form-control"
                                        value="{{ old('discount', 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            {{-- IVA con checkbox integrado --}}
                            <div class="resumen-row iva-row-resumen">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="requiresInvoice"
                                        name="requires_invoice" value="1" {{ old('requires_invoice') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="requiresInvoice">
                                        IVA 16% <small class="text-muted">(Requiere Factura)</small>
                                    </label>
                                </div>
                                <strong id="ivaDisplay" class="iva-amount-disabled">$0.00</strong>
                            </div>

                            <div class="resumen-row total-final">
                                <span>TOTAL:</span>
                                <strong class="text-success" id="totalDisplay">$0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. NOTAS --}}
                <div class="card card-erp order-mobile-6">
                    <div class="card-header bg-light py-2">
                        <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Notas</h5>
                    </div>
                    <div class="card-body py-2">
                        <textarea name="notes" class="form-control form-control-sm" rows="2" maxlength="2000"
                            placeholder="Observaciones generales del pedido...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- 7. BOTÓN CREAR PEDIDO --}}
                <div class="order-mobile-7">
                    <button type="submit" class="btn btn-success btn-lg btn-block" id="submitBtn">
                        <i class="fas fa-save mr-2"></i> Crear Pedido
                    </button>
                </div>

            </div>
        </div>

        {{-- Hidden inputs para items --}}
        <div id="hiddenItemsContainer"></div>
    </form>

    {{-- ============================================== --}}
    {{-- MODAL: BÚSQUEDA DE CLIENTES ERP --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="clientSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-search mr-2"></i> Buscar Cliente</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">
                    {{-- Barra de búsqueda --}}
                    <div class="client-search-container">
                        <input type="text" id="clientSearchInput" class="form-control client-search-input"
                            placeholder="Nombre, apellido o teléfono..." autocomplete="off">
                    </div>

                    {{-- Resultados --}}
                    <div class="client-results-container" id="clientResultsContainer">
                        <div class="client-results-empty">
                            <i class="fas fa-search fa-2x mb-2 d-block text-muted"></i>
                            Escriba para buscar clientes
                        </div>
                    </div>
                </div>
                <div class="client-create-footer">
                    <button type="button" class="btn btn-success btn-block" id="btnCreateFromSearch"
                        data-dismiss="modal" data-toggle="modal" data-target="#quickClientModal">
                        <i class="fas fa-user-plus mr-1"></i> Crear Cliente Nuevo
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL: CLIENTE RÁPIDO --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="quickClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i> Cliente Rápido</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nombre *</label>
                        <input type="text" id="quickClientNombre" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos</label>
                        <input type="text" id="quickClientApellidos" class="form-control" maxlength="255">
                    </div>
                    <div class="form-group mb-0">
                        <label>Teléfono <small class="text-muted">(opcional, 10 dígitos)</small></label>
                        <input type="text" id="quickClientTelefono" class="form-control" maxlength="10"
                            pattern="[0-9]{10}">
                    </div>
                    <div class="alert alert-danger mt-3 mb-0" id="quickClientError" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="saveQuickClient">
                        <i class="fas fa-save mr-1"></i> Crear y Seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL: AGREGAR PRODUCTO --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-box mr-2"></i> Agregar Producto</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        {{-- COLUMNA IZQUIERDA: Preview + Estados --}}
                        <div class="col-md-4 text-center">
                            <img id="productPreviewImage" src="{{ asset('img/no-image.png') }}"
                                class="img-fluid rounded mb-2" style="max-height: 150px;">
                            <div id="productPreviewName" class="font-weight-bold">-</div>
                            <div id="productPreviewSku" class="text-muted small">-</div>
                            <div id="productPreviewType" class="small mt-1" style="display: none;"></div>

                            {{-- Precio base vs precio final --}}
                            <div id="priceComparisonContainer" class="mt-2 p-2 rounded" style="display: none; background: #f8f9fa; font-size: 0.85rem;">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Base:</span>
                                    <span id="modalBasePriceDisplay">$0.00</span>
                                </div>
                                <div id="extrasAdditionRow" class="d-flex justify-content-between text-info" style="display: none;">
                                    <span>+ Extras:</span>
                                    <span id="modalExtrasDisplay">$0.00</span>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between font-weight-bold">
                                    <span>Final:</span>
                                    <span id="modalFinalPriceDisplay" class="text-success">$0.00</span>
                                </div>
                            </div>

                            {{-- ESTADOS MEDIDAS LEGACY — ELIMINADO (FASE 1)
                                 Ahora las medidas se capturan inline en el modal --}}

                            {{-- Alerta de precio modificado --}}
                            <div id="priceModifiedAlert" class="alert alert-info py-1 px-2 mt-2 mb-0" style="display: none; font-size: 0.75rem;">
                                <i class="fas fa-info-circle mr-1"></i> Precio ajustado manualmente
                            </div>
                        </div>

                        {{-- COLUMNA DERECHA: Formulario --}}
                        <div class="col-md-8">
                            {{-- Buscar Producto --}}
                            <div class="form-group">
                                <label class="font-weight-bold">Buscar Producto</label>
                                <select id="modalProductSelect" class="form-control" style="width: 100%;">
                                    <option value="">Escriba para buscar...</option>
                                </select>
                            </div>

                            {{-- Variante (condicional) --}}
                            <div class="form-group" id="variantGroup" style="display: none;">
                                <label class="font-weight-bold">Variante</label>
                                <select id="modalVariantSelect" class="form-control">
                                    <option value="">-- Producto base --</option>
                                </select>
                            </div>

                            {{-- Cantidad y Precio --}}
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Cantidad *</label>
                                        <input type="number" id="modalQuantity" class="form-control" value="1" min="1" max="999">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Precio Unit. *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" id="modalPrice" class="form-control" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            {{-- ESTADO DEL PRODUCTO (Indicador visual dominante)           --}}
                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            <div id="productTypeIndicator" class="mb-3" style="display: none;">
                                {{-- Producto estándar (sin medidas) --}}
                                <div id="productTypeStandard" class="d-flex align-items-center p-3 rounded" style="display: none; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border: 1px solid #a5d6a7;">
                                    <i class="fas fa-box-open fa-2x mr-3" style="color: #2e7d32;"></i>
                                    <div>
                                        <span class="badge px-3 py-2" style="background: #2e7d32; color: white; font-size: 13px;">
                                            <i class="fas fa-check-circle mr-1"></i> Producto Estándar
                                        </span>
                                        <div class="mt-1" style="color: #1b5e20; font-size: 12px;">
                                            Este producto no requiere medidas — listo para agregar
                                        </div>
                                    </div>
                                </div>
                                {{-- Producto a medida --}}
                                <div id="productTypeCustom" class="d-flex align-items-center p-3 rounded" style="display: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 1px solid #90caf9;">
                                    <i class="fas fa-ruler-combined fa-2x mr-3" style="color: #0d47a1;"></i>
                                    <div>
                                        <span class="badge px-3 py-2" style="background: #0d47a1; color: white; font-size: 13px;">
                                            <i class="fas fa-ruler mr-1"></i> Producto a Medida
                                        </span>
                                        <div class="mt-1" style="color: #0d47a1; font-size: 12px;">
                                            Requiere captura de medidas antes de agregar al pedido
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            {{-- REQUISITOS DEL PRODUCTO (Sistema) - OBLIGATORIO           --}}
                            {{-- Solo visible si el producto requiere medidas              --}}
                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            <div class="card mb-3" id="measurementsSection" style="display: none; border: 2px solid #0d47a1; border-radius: 8px; box-shadow: 0 2px 8px rgba(13,71,161,0.15);">
                                {{-- Header: REQUISITOS DEL PRODUCTO (Autoritativo) --}}
                                <div class="card-header py-2 px-3" style="background: linear-gradient(135deg, #0d47a1 0%, #1a237e 100%); color: white; border-radius: 6px 6px 0 0;">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-shield-alt mr-2" style="font-size: 16px;"></i>
                                                <strong style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Requisitos del Producto</strong>
                                            </div>
                                            <small class="d-block mt-1" style="color: rgba(255,255,255,0.85); font-size: 11px; margin-left: 26px;">
                                                <i class="fas fa-lock mr-1"></i> Requisito técnico obligatorio definido por el sistema
                                            </small>
                                        </div>
                                        <span class="badge d-none d-sm-inline-block" style="background: rgba(255,255,255,0.2); color: white; font-size: 10px;">
                                            <i class="fas fa-cog mr-1"></i> SISTEMA
                                        </span>
                                    </div>
                                </div>
                                {{-- Body: Medidas del Ítem --}}
                                <div class="card-body py-3 px-3" style="background: linear-gradient(180deg, #e3f2fd 0%, #bbdefb 100%);">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="mb-2 mb-sm-0 flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-ruler-combined mr-2" style="color: #0d47a1; font-size: 20px;"></i>
                                                <div>
                                                    <strong style="color: #0d47a1; font-size: 15px;">Medidas del Ítem</strong>
                                                    <span class="badge ml-2" id="measurementsStatusBadge" style="background: #e65100; color: white; font-size: 11px; padding: 4px 8px;">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>REQUISITO OBLIGATORIO
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2" style="margin-left: 28px; padding: 8px 12px; background: rgba(255,255,255,0.7); border-radius: 4px; border-left: 3px solid #0d47a1;">
                                                <small style="color: #37474f; font-size: 12px;">
                                                    <i class="fas fa-info-circle mr-1" style="color: #0d47a1;"></i>
                                                    <strong>Este producto no puede fabricarse sin capturar las medidas requeridas.</strong>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="mt-2 mt-sm-0 w-100 w-sm-auto" style="min-width: 220px;">
                                            <button type="button" class="btn btn-block btn-sm" id="btnOpenMeasurementsModal" style="background: #0d47a1; color: white; font-weight: 600; padding: 10px 16px;">
                                                <i class="fas fa-clipboard-check mr-1"></i>
                                                <span id="btnMeasurementsText">Completar requisito: capturar medidas</span>
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Resumen de medidas capturadas --}}
                                    <div id="measurementsSummaryBody" style="display: none;" class="mt-3 pt-2 border-top">
                                        <div class="row small" id="measurementsSummaryContent">
                                            {{-- Se llena dinámicamente --}}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            {{-- SEPARADOR SEMÁNTICO: SISTEMA vs CLIENTE                    --}}
                            {{-- ═══════════════════════════════════════════════════════════ --}}
                            <div id="systemClientDivider" class="my-3 text-center" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <hr class="flex-grow-1" style="border-color: #bdbdbd;">
                                    <span class="px-3 text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">
                                        <i class="fas fa-user mr-1"></i> Opciones del Cliente
                                    </span>
                                    <hr class="flex-grow-1" style="border-color: #bdbdbd;">
                                </div>
                            </div>

                            {{-- ═══════════════════════════════════════════ --}}
                            {{-- PERSONALIZACIÓN (Opcional - Cliente)       --}}
                            {{-- ═══════════════════════════════════════════ --}}
                            <div class="card mb-2" id="customizationCard" style="border-color: #e0e0e0;">
                                <div class="card-header py-2 px-3" style="background: #f8f9fa; cursor: pointer;" id="customizationToggle">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="custom-control custom-checkbox d-inline-block">
                                                <input type="checkbox" class="custom-control-input" id="isCustomized">
                                                <label class="custom-control-label font-weight-bold" for="isCustomized">
                                                    <i class="fas fa-magic mr-1" style="color: #7b1fa2;"></i> Agregar personalización
                                                    <span class="badge badge-light ml-1" style="font-weight: normal; font-size: 11px;">opcional</span>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ml-4" style="font-size: 11px;">
                                                Opciones estéticas solicitadas por el cliente.
                                            </small>
                                        </div>
                                        <i class="fas fa-chevron-down text-muted" id="customizationChevron"></i>
                                    </div>
                                </div>
                                <div class="card-body py-3 px-3" id="customizationBody" style="display: none;">
                                    {{-- Texto a Bordar --}}
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-pen-fancy mr-1 text-info"></i> Texto a Bordar
                                        </label>
                                        <input type="text" id="modalEmbroideryText" class="form-control" maxlength="255"
                                            placeholder="Nombre, frase, iniciales...">
                                    </div>

                                    {{-- EXTRAS: Botón + Lista de seleccionados --}}
                                    <div class="form-group mb-3" id="productExtrasSection" style="display: none;">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-plus-circle mr-1 text-success"></i> Extras
                                        </label>
                                        <div class="d-flex align-items-center mb-2">
                                            <button type="button" class="btn btn-outline-success btn-sm" id="btnOpenExtrasModal">
                                                <i class="fas fa-list-ul mr-1"></i> Seleccionar Extras
                                            </button>
                                            <span class="ml-2 text-info font-weight-bold" id="extrasSubtotalDisplay">+$0.00</span>
                                        </div>
                                        {{-- Lista de extras seleccionados --}}
                                        <div id="selectedExtrasList" class="border rounded" style="display: none; max-height: 120px; overflow-y: auto;">
                                            {{-- Se llena dinámicamente con JS --}}
                                        </div>
                                        <small class="text-muted" id="noExtrasSelectedMsg">Sin extras seleccionados</small>
                                    </div>

                                    {{-- Ajuste de precio adicional (temporal - para futuro cálculo por medidas) --}}
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-dollar-sign mr-1 text-warning"></i> Ajuste de precio adicional
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">+$</span></div>
                                            <input type="number" id="modalExtrasCost" class="form-control" step="0.01" min="0" value="0">
                                        </div>
                                        <small class="text-muted">Ajuste manual por medidas especiales o trabajos adicionales</small>
                                    </div>

                                    {{-- Notas de Personalización --}}
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold mb-1">
                                            <i class="fas fa-sticky-note mr-1 text-secondary"></i> Notas / Instrucciones
                                        </label>
                                        <textarea id="modalCustomizationNotes" class="form-control" rows="2" maxlength="1000"
                                            placeholder="Instrucciones especiales, diseño, colores..."></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- Subtotal del ítem (solo lectura) --}}
                            <div class="bg-light rounded p-2 text-right" id="itemSubtotalContainer" style="display: none;">
                                <span class="text-muted">Subtotal ítem:</span>
                                <strong class="text-success ml-2" id="itemSubtotalDisplay">$0.00</strong>
                                <small class="text-muted d-block" id="itemSubtotalDetail"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="addProductBtn" disabled
                            data-toggle="tooltip" data-placement="top" title="">
                        <i class="fas fa-plus mr-1"></i> Agregar al Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL: CAPTURA DE MEDIDAS (MORADO) --}}
    {{-- Se abre como overlay SIN cerrar el modal de producto --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="measurementsModal" tabindex="-1" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2" style="background: #343a40; color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-ruler-combined mr-2"></i>
                        <span id="measurementsModalTitle">Capturar Medidas</span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- Info del producto actual --}}
                    <div class="alert alert-info py-2 mb-3" id="measurementsProductInfo">
                        <i class="fas fa-box mr-1"></i>
                        <strong>Producto:</strong> <span id="measurementsProductName">-</span>
                    </div>

                    {{-- Selector: usar medidas existentes o capturar nuevas --}}
                    <div class="mb-3" id="measurementsSourceSelector">
                        <label class="font-weight-bold mb-2">
                            <i class="fas fa-clipboard-list mr-1"></i> Origen de las medidas:
                        </label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active" id="lblNewMeasures">
                                <input type="radio" name="measurementSource" value="new" checked>
                                <i class="fas fa-plus-circle mr-1"></i> Capturar Nuevas
                            </label>
                            <label class="btn btn-outline-primary" id="lblExistingMeasures">
                                <input type="radio" name="measurementSource" value="existing">
                                <i class="fas fa-history mr-1"></i> Usar Existentes
                            </label>
                        </div>
                    </div>

                    {{-- Panel: Capturar nuevas medidas con imágenes --}}
                    <div id="newMeasurementsPanel">
                        <div class="row">
                            {{-- BUSTO --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">BUSTO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/busto.png') }}" alt="Busto" class="img-fluid medida-img">
                                    <input type="text" id="medBusto"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 80.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                            {{-- ALTO CINTURA --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">ALTO CINTURA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura" class="img-fluid medida-img">
                                    <input type="text" id="medAltoCintura"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 40.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                            {{-- CINTURA --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">CINTURA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/cintura.png') }}" alt="Cintura" class="img-fluid medida-img">
                                    <input type="text" id="medCintura"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 70.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                            {{-- CADERA --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">CADERA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/cadera.png') }}" alt="Cadera" class="img-fluid medida-img">
                                    <input type="text" id="medCadera"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 95.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                            {{-- LARGO BLUSA --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">LARGO BLUSA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/largo.png') }}" alt="Largo Blusa" class="img-fluid medida-img">
                                    <input type="text" id="medLargo"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 60.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                            {{-- LARGO VESTIDO --}}
                            <div class="form-group col-md-4 col-6 text-center">
                                <label class="medida-label">LARGO VESTIDO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/largo_vestido.png') }}" alt="Largo Vestido" class="img-fluid medida-img">
                                    <input type="text" id="medLargoVestido"
                                        class="form-control form-control-sm medida-input"
                                        placeholder="Ej: 120.5" maxlength="6"
                                        inputmode="decimal" oninput="validateMedidaModal(this)">
                                </div>
                            </div>
                        </div>
                        {{-- Checkbox guardar en cliente --}}
                        <div class="custom-control custom-checkbox mt-2" id="saveToClientOption" style="display: none;">
                            <input type="checkbox" class="custom-control-input" id="chkSaveMeasuresToClient">
                            <label class="custom-control-label" for="chkSaveMeasuresToClient">
                                Guardar estas medidas en el perfil del cliente
                            </label>
                        </div>
                    </div>

                    {{-- Panel: Seleccionar medidas existentes del cliente --}}
                    <div id="existingMeasurementsPanel" style="display: none;">
                        <div class="text-center py-3" id="existingMeasuresLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2 mb-0 text-muted">Cargando medidas del cliente...</p>
                        </div>
                        <div id="existingMeasuresList">
                            {{-- Se llena dinámicamente --}}
                        </div>
                        <div class="alert alert-warning py-2 mt-2" id="noExistingMeasuresAlert" style="display: none;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Este cliente no tiene medidas registradas. Capture nuevas medidas.
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    {{-- Footer para modo editable --}}
                    <div id="measurementsEditFooter">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn text-white" id="btnConfirmMeasurements" style="background: #6f42c1;">
                            <i class="fas fa-check mr-1"></i> Confirmar Medidas
                        </button>
                    </div>
                    {{-- Footer para modo readonly --}}
                    <div id="measurementsReadonlyFooter" style="display: none;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL: SELECCIÓN DE EXTRAS --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="extrasSelectionModal" tabindex="-1" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> Seleccionar Extras</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">
                    {{-- Buscador de extras --}}
                    <div class="p-3 border-bottom bg-light">
                        <input type="text" id="extrasSearchInput" class="form-control" placeholder="Buscar extras...">
                    </div>
                    {{-- Tabla de extras disponibles --}}
                    <div style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0" id="extrasTable">
                            <thead class="thead-light" style="position: sticky; top: 0;">
                                <tr>
                                    <th style="width: 50px;" class="text-center">
                                        <input type="checkbox" id="selectAllExtras" title="Seleccionar todos">
                                    </th>
                                    <th>Extra</th>
                                    <th style="width: 120px;" class="text-right">Precio</th>
                                </tr>
                            </thead>
                            <tbody id="extrasTableBody">
                                {{-- Se llena dinámicamente --}}
                            </tbody>
                        </table>
                    </div>
                    {{-- Resumen de selección --}}
                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                        <span><strong id="extrasSelectedCount">0</strong> extras seleccionados</span>
                        <span class="text-success font-weight-bold" style="font-size: 1.1rem;">
                            Total: <span id="extrasSelectionTotal">$0.00</span>
                        </span>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmExtras">
                        <i class="fas fa-check mr-1"></i> Confirmar Selección
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // ==========================================
            // DATOS GLOBALES
            // ==========================================
            let itemIndex = 0;
            let orderItems = [];
            let selectedProduct = null;
            let selectedClientData = null;
            let productLeadTimes = {};
            let clientSearchTimeout = null;
            // MEDIDAS DEL ITEM ACTUAL (pertenecen al item, no al cliente)
            let currentItemMeasurements = null; // {busto, cintura, cadera, alto_cintura, largo, largo_vestido}
            let clientMeasurementsCache = null; // Cache de medidas existentes del cliente
            // FASE 4: Índice del item que se está editando (null si es CREATE)
            let editingItemIndex = null;

            const urgencyMultipliers = {
                'normal': 1.0,
                'urgente': 0.7,
                'express': 0.5
            };

            const IVA_RATE = 0.16;

            // ==========================================
            // BÚSQUEDA DE CLIENTES ERP (MODAL)
            // ==========================================
            const $searchInput = $('#clientSearchInput');
            const $resultsContainer = $('#clientResultsContainer');

            // Debounced search
            $searchInput.on('input', function() {
                const query = $(this).val().trim();

                clearTimeout(clientSearchTimeout);

                if (query.length === 0) {
                    $resultsContainer.html(`
                        <div class="client-results-empty">
                            <i class="fas fa-search fa-2x mb-2 d-block text-muted"></i>
                            Escriba para buscar clientes
                        </div>
                    `);
                    return;
                }

                $resultsContainer.html(`
                    <div class="client-results-loading">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    </div>
                `);

                clientSearchTimeout = setTimeout(function() {
                    searchClients(query);
                }, 300);
            });

            function searchClients(query) {
                $.ajax({
                    url: '{{ route('admin.orders.ajax.search-clientes') }}',
                    method: 'GET',
                    data: {
                        q: query,
                        page: 1
                    },
                    dataType: 'json',
                    success: function(response) {
                        renderClientResults(response.results || []);
                    },
                    error: function() {
                        $resultsContainer.html(`
                            <div class="client-results-empty text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                Error al buscar clientes
                            </div>
                        `);
                    }
                });
            }

            function renderClientResults(clients) {
                if (clients.length === 0) {
                    $resultsContainer.html(`
                        <div class="client-results-empty">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block text-muted"></i>
                            No se encontraron clientes
                        </div>
                    `);
                    return;
                }

                let html = '';
                clients.slice(0, 10).forEach(function(client) {
                    const phoneDisplay = client.telefono ?
                        `<span class="client-phone"><i class="fas fa-phone mr-1"></i>${client.telefono}</span>` :
                        `<span class="no-phone-badge"><i class="fas fa-phone-slash mr-1"></i>Sin teléfono</span>`;

                    html += `
                        <div class="client-result-item" data-id="${client.id}" data-text="${client.text}" data-telefono="${client.telefono || ''}">
                            <div>
                                <div class="client-name">${client.text}</div>
                                ${phoneDisplay}
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </div>
                    `;
                });

                $resultsContainer.html(html);
            }

            // Seleccionar cliente desde resultados
            $(document).on('click', '.client-result-item', function() {
                const id = $(this).data('id');
                const text = $(this).data('text');
                const telefono = $(this).data('telefono');

                selectClient(id, text, telefono);
                $('#clientSearchModal').modal('hide');
            });

            function selectClient(id, text, telefono) {
                selectedClientData = {
                    id,
                    text,
                    telefono
                };

                // Actualizar input hidden
                $('#cliente_id').val(id);

                // Actualizar display del botón
                const phoneHtml = telefono ?
                    `<span class="cliente-telefono"><i class="fas fa-phone mr-1"></i>${telefono}</span>` :
                    `<span class="cliente-telefono text-muted"><i class="fas fa-phone-slash mr-1"></i>Sin teléfono</span>`;

                $('#clienteDisplay').html(`
                    <span class="cliente-nombre">${text}</span>
                    ${phoneHtml}
                `);
                $('#btnSelectClient').addClass('has-client');

                // Limpiar cache de medidas del cliente anterior
                clearMeasurementsCache();
            }

            // Reset al abrir modal de búsqueda
            $('#clientSearchModal').on('show.bs.modal', function() {
                $searchInput.val('');
                $resultsContainer.html(`
                    <div class="client-results-empty">
                        <i class="fas fa-search fa-2x mb-2 d-block text-muted"></i>
                        Escriba para buscar clientes
                    </div>
                `);
            });

            $('#clientSearchModal').on('shown.bs.modal', function() {
                $searchInput.focus();
            });

            // ==========================================
            // CLIENTE RÁPIDO
            // ==========================================
            $('#saveQuickClient').on('click', function() {
                const nombre = $('#quickClientNombre').val().trim();
                const apellidos = $('#quickClientApellidos').val().trim();
                const telefono = $('#quickClientTelefono').val().trim();

                if (!nombre) {
                    $('#quickClientError').text('El nombre es requerido').show();
                    return;
                }

                // Validar teléfono solo si se proporciona
                if (telefono && telefono.length !== 10) {
                    $('#quickClientError').text('El teléfono debe tener 10 dígitos').show();
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $('#quickClientError').hide();

                $.ajax({
                    url: '{{ route('admin.clientes.quick-store') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        nombre: nombre,
                        apellidos: apellidos,
                        telefono: telefono || null
                    },
                    success: function(response) {
                        if (response.success) {
                            selectClient(response.id, response.text, telefono);

                            $('#quickClientNombre').val('');
                            $('#quickClientApellidos').val('');
                            $('#quickClientTelefono').val('');
                            $('#quickClientModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Cliente creado',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Error al crear cliente';
                        $('#quickClientError').text(msg).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save mr-1"></i> Crear y Seleccionar');
                    }
                });
            });

            // ==========================================
            // GESTIÓN DE MEDIDAS — ELIMINADO (FASE 1)
            // Las medidas ahora se capturan INLINE en el modal de producto.
            // Funciones eliminadas:
            // - loadClientMeasurements()
            // - selectMeasurement()
            // - buildMeasurementSummary()
            // - renderMeasurementsHistory()
            // - openMeasurementModal()
            // - validateMedida()
            // Handlers eliminados:
            // - #btnShowAllMeasurements
            // - .btn-use-measurement
            // - .btn-edit-measurement
            // - #btnEditActiveMeasurement
            // - #btnCapturarMedidas, #btnCapturarNuevas
            // - #saveMeasurementsBtn
            // - #measurementsModal .medida-input
            // - IIFE touch fix
            // ==========================================

            // ==========================================
            // SELECT2: PRODUCTO EN MODAL
            // ==========================================
            $('#modalProductSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#addProductModal'),
                placeholder: 'Buscar producto por nombre o SKU...',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: '{{ route('admin.orders.ajax.search-products') }}',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        const items = data.results || data;
                        return {
                            results: items.map(p => ({
                                id: p.id,
                                text: `${p.name} - $${parseFloat(p.base_price).toFixed(2)}`,
                                product: p
                            })),
                            pagination: data.pagination || {
                                more: false
                            }
                        };
                    },
                    cache: true
                }
            }).on('select2:open', function() {
                setTimeout(function() {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 0);
            }).on('select2:select', function(e) {
                selectedProduct = e.params.data.product;
                updateProductPreview();
            }).on('select2:clear', function() {
                selectedProduct = null;
                resetProductModal();
            });

            function updateProductPreview() {
                if (!selectedProduct) return;

                $('#productPreviewName').text(selectedProduct.name);
                $('#productPreviewSku').text(selectedProduct.sku || '-');
                $('#productPreviewImage').attr('src', selectedProduct.image_url ||
                    '{{ asset('img/no-image.png') }}');

                // Guardar precio base y establecer precio
                modalBasePrice = parseFloat(selectedProduct.base_price) || 0;
                $('#modalPrice').val(modalBasePrice);

                // Mostrar tipo de producto si existe
                if (selectedProduct.product_type_name) {
                    $('#productPreviewType')
                        .html(`<span class="badge badge-secondary">${selectedProduct.product_type_name}</span>`)
                        .show();
                } else {
                    $('#productPreviewType').hide();
                }

                // === MOSTRAR/OCULTAR SECCIÓN DE MEDIDAS ===
                updateMeasurementsSectionVisibility();

                // === CARGAR EXTRAS DEL PRODUCTO ===
                loadProductExtras();

                // === MOSTRAR COMPARACIÓN DE PRECIOS ===
                updatePriceComparison();

                const $variantSelect = $('#modalVariantSelect');
                $variantSelect.empty().append('<option value="">-- Producto base --</option>');

                if (selectedProduct.variants && selectedProduct.variants.length > 0) {
                    selectedProduct.variants.forEach(v => {
                        $variantSelect.append(
                            `<option value="${v.id}" data-price="${v.price}" data-sku="${v.sku}">${v.display} ($${parseFloat(v.price).toFixed(2)})</option>`
                            );
                    });
                    $('#variantGroup').show();
                } else {
                    $('#variantGroup').hide();
                }

                // Mostrar subtotal del ítem
                $('#itemSubtotalContainer').show();
                updateItemSubtotal();

                // Habilitar botón solo si las condiciones se cumplen
                updateAddButtonState();
            }

            // Array para trackear extras seleccionados
            let selectedExtras = [];
            // Array temporal para selección en modal de extras
            let tempSelectedExtras = [];

            // Mostrar sección de extras cuando hay producto seleccionado
            // Los extras reales se cargan por AJAX al abrir el modal
            function loadProductExtras() {
                const $section = $('#productExtrasSection');
                selectedExtras = [];

                if (!selectedProduct) {
                    $section.hide();
                    return;
                }

                // Siempre mostrar la sección - los extras se cargan por AJAX al abrir el modal
                $section.show();
                renderSelectedExtrasList();
            }

            // Renderizar lista de extras seleccionados en el modal principal
            function renderSelectedExtrasList() {
                const $list = $('#selectedExtrasList');
                const $noMsg = $('#noExtrasSelectedMsg');

                $list.empty();

                if (selectedExtras.length === 0) {
                    $list.hide();
                    $noMsg.show();
                } else {
                    $noMsg.hide();
                    $list.show();

                    selectedExtras.forEach(extra => {
                        const itemHtml = `
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom" data-extra-id="${extra.id}">
                                <span class="small">${extra.name}</span>
                                <div>
                                    <span class="text-info small mr-2">+$${extra.price.toFixed(2)}</span>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-extra-btn" data-id="${extra.id}" title="Quitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        $list.append(itemHtml);
                    });
                }

                updateExtrasSubtotal();
            }

            // Handler para quitar extra de la lista
            $(document).on('click', '.remove-extra-btn', function() {
                const extraId = $(this).data('id');
                selectedExtras = selectedExtras.filter(e => e.id !== extraId);
                renderSelectedExtrasList();
                recalculateFinalPrice();
                updatePriceComparison();
                updateItemSubtotal();
            });

            // Actualizar subtotal de extras
            function updateExtrasSubtotal() {
                const total = selectedExtras.reduce((sum, e) => sum + e.price, 0);
                $('#extrasSubtotalDisplay').text('+$' + total.toFixed(2));
            }

            // ==========================================
            // MODAL DE SELECCIÓN DE EXTRAS
            // ==========================================

            // Abrir modal de extras - CARGA POR AJAX
            $('#btnOpenExtrasModal').on('click', function() {
                if (!selectedProduct) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin producto',
                        text: 'Primero seleccione un producto.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    return;
                }

                // Mostrar loading en la tabla
                $('#extrasTableBody').html(`
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                            <p class="mt-2 mb-0 text-muted">Cargando extras...</p>
                        </td>
                    </tr>
                `);

                // Abrir modal inmediatamente
                $('#extrasSelectionModal').modal('show');

                // Cargar extras por AJAX
                $.ajax({
                    url: `/admin/orders/ajax/product/${selectedProduct.id}/extras`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Actualizar extras del producto con datos frescos de BD
                        selectedProduct.extras = response.extras || [];

                        if (selectedProduct.extras.length === 0) {
                            $('#extrasTableBody').html(`
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        Este producto no tiene extras configurados
                                    </td>
                                </tr>
                            `);
                            return;
                        }

                        // Copiar selección actual a temporal
                        tempSelectedExtras = selectedExtras.map(e => ({...e}));

                        // Llenar tabla de extras
                        populateExtrasTable();
                    },
                    error: function() {
                        $('#extrasTableBody').html(`
                            <tr>
                                <td colspan="3" class="text-center py-4 text-danger">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                    Error al cargar extras
                                </td>
                            </tr>
                        `);
                    }
                });
            });

            // Llenar tabla con extras del producto
            function populateExtrasTable() {
                const $tbody = $('#extrasTableBody');
                $tbody.empty();

                if (!selectedProduct || !selectedProduct.extras) return;

                selectedProduct.extras.forEach(extra => {
                    const isSelected = tempSelectedExtras.some(e => e.id === extra.id);
                    const rowHtml = `
                        <tr data-extra-id="${extra.id}" data-name="${extra.name}" data-price="${extra.price_addition}">
                            <td class="text-center">
                                <input type="checkbox" class="extra-table-checkbox" ${isSelected ? 'checked' : ''}>
                            </td>
                            <td>${extra.name}</td>
                            <td class="text-right text-success font-weight-bold">+$${parseFloat(extra.price_addition).toFixed(2)}</td>
                        </tr>
                    `;
                    $tbody.append(rowHtml);
                });

                updateExtrasModalSummary();
            }

            // Buscador de extras
            $('#extrasSearchInput').on('input', function() {
                const term = $(this).val().toLowerCase().trim();
                $('#extrasTableBody tr').each(function() {
                    const name = $(this).data('name').toLowerCase();
                    $(this).toggle(name.includes(term));
                });
            });

            // Checkbox en tabla de extras
            $(document).on('change', '.extra-table-checkbox', function() {
                const $row = $(this).closest('tr');
                const extraId = $row.data('extra-id');
                const extraName = $row.data('name');
                const extraPrice = parseFloat($row.data('price')) || 0;

                if ($(this).is(':checked')) {
                    if (!tempSelectedExtras.some(e => e.id === extraId)) {
                        tempSelectedExtras.push({ id: extraId, name: extraName, price: extraPrice });
                    }
                } else {
                    tempSelectedExtras = tempSelectedExtras.filter(e => e.id !== extraId);
                }

                updateExtrasModalSummary();
            });

            // Seleccionar/deseleccionar todos
            $('#selectAllExtras').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('#extrasTableBody tr:visible').each(function() {
                    const $checkbox = $(this).find('.extra-table-checkbox');
                    if ($checkbox.prop('checked') !== isChecked) {
                        $checkbox.prop('checked', isChecked).trigger('change');
                    }
                });
            });

            // Actualizar resumen en modal de extras
            function updateExtrasModalSummary() {
                const count = tempSelectedExtras.length;
                const total = tempSelectedExtras.reduce((sum, e) => sum + e.price, 0);

                $('#extrasSelectedCount').text(count);
                $('#extrasSelectionTotal').text('$' + total.toFixed(2));
            }

            // Confirmar selección de extras
            $('#btnConfirmExtras').on('click', function() {
                // Guardar selección temporal como definitiva
                selectedExtras = tempSelectedExtras.map(e => ({...e}));

                // Actualizar UI
                renderSelectedExtrasList();
                recalculateFinalPrice();
                updatePriceComparison();
                updateItemSubtotal();

                // Cerrar modal de extras
                $('#extrasSelectionModal').modal('hide');
            });

            // Limpiar búsqueda al cerrar modal
            $('#extrasSelectionModal').on('hidden.bs.modal', function() {
                $('#extrasSearchInput').val('');
                tempSelectedExtras = [];
            });

            // Recalcular precio final (base + extras + ajuste manual)
            function recalculateFinalPrice() {
                const extrasTotal = selectedExtras.reduce((sum, e) => sum + e.price, 0);
                const manualAdjust = parseFloat($('#modalExtrasCost').val()) || 0;
                const finalPrice = modalBasePrice + extrasTotal + manualAdjust;
                $('#modalPrice').val(finalPrice.toFixed(2));
            }

            // Actualizar comparación de precios
            function updatePriceComparison() {
                if (!selectedProduct) {
                    $('#priceComparisonContainer').hide();
                    return;
                }

                const extrasTotal = selectedExtras.reduce((sum, e) => sum + e.price, 0);
                const manualAdjust = parseFloat($('#modalExtrasCost').val()) || 0;
                const finalPrice = parseFloat($('#modalPrice').val()) || 0;

                $('#modalBasePriceDisplay').text('$' + modalBasePrice.toFixed(2));
                $('#modalFinalPriceDisplay').text('$' + finalPrice.toFixed(2));

                if (extrasTotal > 0 || manualAdjust > 0) {
                    $('#extrasAdditionRow').show();
                    $('#modalExtrasDisplay').text('$' + (extrasTotal + manualAdjust).toFixed(2));
                } else {
                    $('#extrasAdditionRow').hide();
                }

                $('#priceComparisonContainer').show();

                // Mostrar alerta si precio fue modificado manualmente
                const expectedPrice = modalBasePrice + extrasTotal + manualAdjust;
                if (Math.abs(finalPrice - expectedPrice) > 0.01) {
                    $('#priceModifiedAlert').show();
                } else {
                    $('#priceModifiedAlert').hide();
                }
            }

            // Actualizar subtotal del ítem
            function updateItemSubtotal() {
                const qty = parseInt($('#modalQuantity').val()) || 1;
                const price = parseFloat($('#modalPrice').val()) || 0;
                const subtotal = qty * price;

                $('#itemSubtotalDisplay').text('$' + subtotal.toFixed(2));
                $('#itemSubtotalDetail').text(qty + ' × $' + price.toFixed(2));
            }

            // ==========================================
            // GESTIÓN DE MEDIDAS DEL ITEM
            // ==========================================

            // ==========================================
            // CONTROL DE VISIBILIDAD MEDIDAS + INDICADOR TIPO
            // ==========================================
            function updateMeasurementsSectionVisibility() {
                if (!selectedProduct) {
                    // Sin producto: ocultar indicadores
                    $('#productTypeIndicator').hide();
                    $('#productTypeStandard').hide();
                    $('#productTypeCustom').hide();
                    $('#measurementsSection').hide();
                    $('#systemClientDivider').hide();
                    return;
                }

                // Mostrar indicador de tipo de producto
                $('#productTypeIndicator').show();

                if (selectedProduct.requires_measurements) {
                    // PRODUCTO A MEDIDA
                    $('#productTypeStandard').hide();
                    $('#productTypeCustom').show();
                    $('#measurementsSection').show();
                    $('#systemClientDivider').show();

                    if (currentItemMeasurements) {
                        // ✓ REQUISITO COMPLETADO
                        $('#measurementsStatusBadge')
                            .html('<i class="fas fa-check-circle mr-1"></i>REQUISITO COMPLETADO')
                            .css({'background': '#2e7d32', 'color': 'white', 'font-size': '11px', 'padding': '4px 8px'});
                        $('#btnMeasurementsText').text('Editar medidas (requisito completado)');
                        $('#btnOpenMeasurementsModal').css({'background': '#2e7d32', 'border-color': '#2e7d32'});
                        updateMeasurementsSummary(currentItemMeasurements);
                        $('#measurementsSummaryBody').show();
                    } else {
                        // ⚠ REQUISITO OBLIGATORIO - Sin medidas
                        $('#measurementsStatusBadge')
                            .html('<i class="fas fa-exclamation-circle mr-1"></i>REQUISITO OBLIGATORIO')
                            .css({'background': '#e65100', 'color': 'white', 'font-size': '11px', 'padding': '4px 8px'});
                        $('#btnMeasurementsText').text('Completar requisito: capturar medidas');
                        $('#btnOpenMeasurementsModal').css({'background': '#0d47a1', 'border-color': '#0d47a1'});
                        $('#measurementsSummaryBody').hide();
                    }
                } else {
                    // PRODUCTO ESTÁNDAR
                    $('#productTypeStandard').show();
                    $('#productTypeCustom').hide();
                    $('#measurementsSection').hide();
                    $('#systemClientDivider').hide();
                }

                // Actualizar estado del botón guardar
                updateAddButtonState();
            }

            // Abrir modal de medidas (overlay, sin cerrar modal producto)
            $('#btnOpenMeasurementsModal').on('click', function() {
                if (!selectedProduct) return;

                // Mostrar nombre del producto en el modal de medidas
                $('#measurementsProductName').text(selectedProduct.name);

                // Si hay medidas capturadas previamente, llenar los campos
                if (currentItemMeasurements) {
                    $('#medBusto').val(currentItemMeasurements.busto || '');
                    $('#medCintura').val(currentItemMeasurements.cintura || '');
                    $('#medCadera').val(currentItemMeasurements.cadera || '');
                    $('#medAltoCintura').val(currentItemMeasurements.alto_cintura || '');
                    $('#medLargo').val(currentItemMeasurements.largo || '');
                    $('#medLargoVestido').val(currentItemMeasurements.largo_vestido || '');
                } else {
                    // Limpiar campos del modal de medidas
                    $('#measurementsModal .medida-input').val('');
                }

                // Mostrar opción de guardar en cliente si hay cliente seleccionado
                if (selectedClientData) {
                    $('#saveToClientOption').show();
                    // Rehidratar checkbox desde estado persistido
                    const savedToClient = currentItemMeasurements?.save_to_client || false;
                    $('#chkSaveMeasuresToClient').prop('checked', savedToClient);
                } else {
                    $('#saveToClientOption').hide();
                }

                // Reset selector de origen
                $('input[name="measurementSource"][value="new"]').prop('checked', true);
                $('#lblNewMeasures').addClass('active');
                $('#lblExistingMeasures').removeClass('active');
                $('#newMeasurementsPanel').show();
                $('#existingMeasurementsPanel').hide();

                // Abrir modal como overlay (NO cierra #addProductModal)
                $('#measurementsModal').modal('show');
            });

            // Toggle entre capturar nuevas / usar existentes
            $('input[name="measurementSource"]').on('change', function() {
                const source = $(this).val();

                if (source === 'new') {
                    $('#newMeasurementsPanel').show();
                    $('#existingMeasurementsPanel').hide();
                } else {
                    $('#newMeasurementsPanel').hide();
                    $('#existingMeasurementsPanel').show();
                    loadClientMeasurementsForSelection();
                }
            });

            // Cargar medidas existentes del cliente
            function loadClientMeasurementsForSelection() {
                if (!selectedClientData) {
                    $('#existingMeasuresLoading').hide();
                    $('#existingMeasuresList').empty();
                    $('#noExistingMeasuresAlert').show();
                    return;
                }

                $('#existingMeasuresLoading').show();
                $('#existingMeasuresList').empty();
                $('#noExistingMeasuresAlert').hide();

                // Usar cache si existe
                if (clientMeasurementsCache !== null) {
                    renderExistingMeasurements(clientMeasurementsCache);
                    return;
                }

                // Cargar desde servidor
                $.ajax({
                    url: `/admin/orders/ajax/clientes/${selectedClientData.id}/measurements`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(measurements) {
                        clientMeasurementsCache = measurements;
                        renderExistingMeasurements(measurements);
                    },
                    error: function() {
                        $('#existingMeasuresLoading').hide();
                        $('#noExistingMeasuresAlert').show();
                    }
                });
            }

            // Renderizar lista de medidas existentes
            function renderExistingMeasurements(measurements) {
                $('#existingMeasuresLoading').hide();

                if (!measurements || measurements.length === 0) {
                    $('#noExistingMeasuresAlert').show();
                    return;
                }

                const $list = $('#existingMeasuresList');
                $list.empty();

                measurements.forEach(m => {
                    const summary = buildMeasurementSummaryText(m);
                    const isPrimary = m.is_primary ? '<span class="badge badge-info ml-1">Principal</span>' : '';
                    const label = m.label || 'Medidas registradas';

                    const itemHtml = `
                        <div class="card mb-2 measurement-option" data-measurement='${JSON.stringify(m)}' style="cursor: pointer;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${label}</strong> ${isPrimary}
                                        <div class="small text-muted">${summary}</div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    $list.append(itemHtml);
                });
            }

            // Click en medida existente
            $(document).on('click', '.measurement-option', function() {
                const measurement = $(this).data('measurement');
                if (!measurement) return;

                // Llenar campos con medida seleccionada
                $('#medBusto').val(measurement.busto || '');
                $('#medCintura').val(measurement.cintura || '');
                $('#medCadera').val(measurement.cadera || '');
                $('#medAltoCintura').val(measurement.alto_cintura || '');
                $('#medLargo').val(measurement.largo || '');
                $('#medLargoVestido').val(measurement.largo_vestido || '');

                // Cambiar a panel de captura para mostrar los valores
                $('input[name="measurementSource"][value="new"]').prop('checked', true);
                $('#lblNewMeasures').addClass('active');
                $('#lblExistingMeasures').removeClass('active');
                $('#newMeasurementsPanel').show();
                $('#existingMeasurementsPanel').hide();

                Swal.fire({
                    icon: 'success',
                    title: 'Medidas cargadas',
                    text: 'Puede ajustar los valores si es necesario.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            });

            // Confirmar medidas
            $('#btnConfirmMeasurements').on('click', function() {
                // Recoger valores de los campos
                const measurements = {
                    busto: parseFloat($('#medBusto').val()) || null,
                    cintura: parseFloat($('#medCintura').val()) || null,
                    cadera: parseFloat($('#medCadera').val()) || null,
                    alto_cintura: parseFloat($('#medAltoCintura').val()) || null,
                    largo: parseFloat($('#medLargo').val()) || null,
                    largo_vestido: parseFloat($('#medLargoVestido').val()) || null,
                    // Persistir estado del checkbox POR ITEM
                    save_to_client: $('#chkSaveMeasuresToClient').is(':checked')
                };

                // Verificar que al menos una medida tenga valor (excluir save_to_client)
                const hasAnyMeasurement = ['busto', 'cintura', 'cadera', 'alto_cintura', 'largo', 'largo_vestido']
                    .some(k => measurements[k] !== null && measurements[k] > 0);

                if (!hasAnyMeasurement) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin medidas',
                        text: 'Ingrese al menos una medida para continuar.',
                        confirmButtonColor: '#343a40'
                    });
                    return;
                }

                // Guardar medidas en variable del item actual (flujo UNIFORME)
                currentItemMeasurements = measurements;

                // Actualizar UI del modal de producto
                updateMeasurementsUIAfterCapture();

                // Guardar en cliente si checkbox está marcado
                if (measurements.save_to_client && selectedClientData) {
                    saveMeasurementsToClient(measurements);
                }

                // Cerrar SOLO el modal de medidas (el de producto sigue abierto)
                $('#measurementsModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Medidas capturadas',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            });

            // Actualizar UI después de capturar medidas
            function updateMeasurementsUIAfterCapture() {
                if (!currentItemMeasurements) return;

                // Cambiar badge a "✓ REQUISITO COMPLETADO"
                $('#measurementsStatusBadge')
                    .html('<i class="fas fa-check-circle mr-1"></i>REQUISITO COMPLETADO')
                    .css({'background': '#2e7d32', 'color': 'white', 'font-size': '11px', 'padding': '4px 8px'});

                $('#btnMeasurementsText').text('Editar medidas (requisito completado)');
                $('#btnOpenMeasurementsModal').css({'background': '#2e7d32', 'border-color': '#2e7d32'});

                // Mostrar resumen de medidas
                const summaryHtml = buildMeasurementsSummaryHtml(currentItemMeasurements);
                $('#measurementsSummaryContent').html(summaryHtml);
                $('#measurementsSummaryBody').show();

                // Actualizar estado del botón guardar
                updateAddButtonState();
            }

            // Construir texto resumen de medidas
            function buildMeasurementSummaryText(m) {
                const parts = [];
                if (m.busto) parts.push(`B: ${m.busto}`);
                if (m.cintura) parts.push(`Ci: ${m.cintura}`);
                if (m.cadera) parts.push(`Ca: ${m.cadera}`);
                if (m.alto_cintura) parts.push(`AC: ${m.alto_cintura}`);
                if (m.largo) parts.push(`L: ${m.largo}`);
                if (m.largo_vestido) parts.push(`LV: ${m.largo_vestido}`);
                return parts.length > 0 ? parts.join(' | ') : 'Sin datos';
            }

            // Construir HTML de resumen para mostrar en el modal de producto
            function buildMeasurementsSummaryHtml(m) {
                let html = '';
                const items = [
                    { label: 'Busto', value: m.busto },
                    { label: 'Cintura', value: m.cintura },
                    { label: 'Cadera', value: m.cadera },
                    { label: 'Alto Cintura', value: m.alto_cintura },
                    { label: 'Largo', value: m.largo },
                    { label: 'Largo Vestido', value: m.largo_vestido }
                ];

                items.forEach(item => {
                    if (item.value) {
                        html += `<div class="col-4 mb-1"><strong>${item.label}:</strong> ${item.value} cm</div>`;
                    }
                });

                return html || '<div class="col-12 text-muted">Sin medidas</div>';
            }

            // Actualizar UI de resumen de medidas
            function updateMeasurementsSummary(measurements) {
                const summaryHtml = buildMeasurementsSummaryHtml(measurements);
                $('#measurementsSummaryContent').html(summaryHtml);
            }

            // ══════════════════════════════════════════════════════════════
            // FUTURA ACTIVACIÓN — MODELO DE CLIENTE NO CERRADO
            // La persistencia real en cliente está DESACTIVADA hasta que:
            // - Se defina modelo de versionado de medidas
            // - Se implemente historial/rollback
            // - Se cierre arquitectura de medidas en módulo clientes
            // ══════════════════════════════════════════════════════════════
            function saveMeasurementsToClient(measurements) {
                if (!selectedClientData) return;

                // NEUTRALIZADO: No se persiste en BD del cliente
                // Las medidas SOLO quedan en el contexto del ítem (JS)

                /* CÓDIGO ORIGINAL — REACTIVAR CUANDO MODELO ESTÉ CERRADO:
                $.ajax({
                    url: `/admin/orders/ajax/cliente/${selectedClientData.id}/measurements`,
                    method: 'POST',
                    data: {
                        ...measurements,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        clientMeasurementsCache = null;
                        Swal.fire({
                            icon: 'success',
                            title: 'Medidas guardadas',
                            text: 'Las medidas se guardaron en el perfil del cliente',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    },
                    error: function(xhr) {
                        console.error('Error al guardar medidas en cliente:', xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron guardar las medidas en el cliente',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
                FIN CÓDIGO ORIGINAL */

                // COMPORTAMIENTO TEMPORAL: Feedback informativo sin persistencia
                Swal.fire({
                    icon: 'info',
                    title: 'Medidas registradas',
                    html: '<small>Las medidas se usarán para este pedido.<br>El guardado en cliente se habilitará próximamente.</small>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            }

            // Limpiar cache de medidas cuando cambia el cliente
            function clearMeasurementsCache() {
                clientMeasurementsCache = null;
                currentItemMeasurements = null;
            }

            // Habilitar/deshabilitar botón agregar
            function updateAddButtonState() {
                const $btn = $('#addProductBtn');

                if (!selectedProduct) {
                    $btn.prop('disabled', true).attr('title', '').tooltip('dispose');
                    return;
                }

                const price = parseFloat($('#modalPrice').val()) || 0;

                // Bloquear si precio es 0
                if (price <= 0) {
                    $btn.prop('disabled', true).attr('title', 'Ingrese un precio válido').tooltip('dispose').tooltip();
                    return;
                }

                // VALIDACIÓN DE MEDIDAS: Si requiere medidas y no las tiene, bloquear
                if (selectedProduct.requires_measurements && !currentItemMeasurements) {
                    $btn.prop('disabled', true)
                        .attr('title', 'Completa las medidas para continuar')
                        .tooltip('dispose')
                        .tooltip();
                    return;
                }

                // Todo OK: habilitar botón
                $btn.prop('disabled', false).attr('title', '').tooltip('dispose');
            }

            function resetProductModal() {
                $('#modalProductSelect').val(null).trigger('change.select2');
                $('#productPreviewName').text('-');
                $('#productPreviewSku').text('-');
                $('#productPreviewImage').attr('src', '{{ asset('img/no-image.png') }}');
                $('#productPreviewType').hide();
                // Reset indicador de tipo de producto
                $('#productTypeIndicator').hide();
                $('#productTypeStandard').hide();
                $('#productTypeCustom').hide();
                // Reset medidas del item
                currentItemMeasurements = null;
                $('#measurementsSection').hide();
                $('#systemClientDivider').hide();
                $('#measurementsStatusBadge')
                    .html('<i class="fas fa-exclamation-circle mr-1"></i>REQUISITO OBLIGATORIO')
                    .css({'background': '#e65100', 'color': 'white', 'font-size': '11px', 'padding': '4px 8px'});
                $('#btnMeasurementsText').text('Completar requisito: capturar medidas');
                $('#btnOpenMeasurementsModal').css({'background': '#0d47a1', 'border-color': '#0d47a1'});
                $('#measurementsSummaryBody').hide();
                $('#measurementsSummaryContent').empty();
                // Reset precio y comparación
                $('#modalPrice').val('');
                $('#priceComparisonContainer').hide();
                $('#priceModifiedAlert').hide();
                $('#itemSubtotalContainer').hide();
                // Reset personalización
                $('#isCustomized').prop('checked', false);
                $('#customizationBody').hide();
                $('#customizationChevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $('#modalEmbroideryText').val('');
                $('#modalExtrasCost').val('0');
                $('#modalCustomizationNotes').val('');
                // Reset extras - Nueva estructura
                $('#productExtrasSection').hide();
                $('#selectedExtrasList').empty().hide();
                $('#noExtrasSelectedMsg').show();
                $('#extrasSubtotalDisplay').text('+$0.00');
                selectedExtras = [];
                tempSelectedExtras = [];
                // Reset cantidad y variante
                $('#modalQuantity').val(1);
                $('#modalVariantSelect').empty().append('<option value="">-- Producto base --</option>');
                $('#variantGroup').hide();
                $('#addProductBtn').prop('disabled', true).text('Agregar al Pedido');
                selectedProduct = null;
                modalBasePrice = 0;
                // Reset modo edición
                editingItemIndex = null;
            }

            // Variable para trackear precio base
            let modalBasePrice = 0;

            $('#modalVariantSelect').on('change', function() {
                const variantPrice = $(this).find('option:selected').data('price');
                if (variantPrice) {
                    // Actualizar precio base con precio de variante
                    modalBasePrice = parseFloat(variantPrice);
                } else if (selectedProduct) {
                    // Sin variante: usar precio base del producto
                    modalBasePrice = parseFloat(selectedProduct.base_price) || 0;
                }
                // Recalcular precio final (base + extras + ajuste)
                recalculateFinalPrice();
                updatePriceComparison();
                updateItemSubtotal();
                updateAddButtonState();
            });

            // Actualizar estado del botón cuando cambia el precio
            $('#modalPrice').on('input', function() {
                updateAddButtonState();
                updatePriceComparison();
                updateItemSubtotal();
            });

            // Handler para checkbox de personalización
            $('#isCustomized').on('change', function() {
                const isChecked = $(this).is(':checked');
                if (isChecked) {
                    $('#customizationBody').slideDown(200);
                    $('#customizationChevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $('#customizationBody').slideUp(200);
                    $('#customizationChevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            });

            // Toggle por click en header de personalización
            $('#customizationToggle').on('click', function(e) {
                // Evitar toggle cuando se hace click directamente en el checkbox
                if ($(e.target).is('#isCustomized') || $(e.target).closest('label').is('[for="isCustomized"]')) {
                    return;
                }
                $('#isCustomized').prop('checked', !$('#isCustomized').is(':checked')).trigger('change');
            });

            // Handler para ajuste de precio manual
            $('#modalExtrasCost').on('input', function() {
                recalculateFinalPrice();
                updatePriceComparison();
                updateItemSubtotal();
            });

            // Handler para cambio de cantidad
            $('#modalQuantity').on('input', function() {
                updateItemSubtotal();
            });

            // ==========================================
            // AGREGAR PRODUCTO AL PEDIDO
            // ==========================================
            $('#addProductBtn').on('click', function() {
                if (!selectedProduct) return;

                // === VALIDACIÓN PREVIA: PRECIO ===
                const price = parseFloat($('#modalPrice').val()) || 0;
                if (price <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Precio requerido',
                        text: 'Ingrese un precio válido mayor a $0',
                        confirmButtonColor: '#7f00ff'
                    });
                    $('#modalPrice').focus();
                    return;
                }

                // === VALIDACIÓN MEDIDAS LEGACY ELIMINADA (FASE 1) ===
                // El flujo de medidas que cerraba el modal y abría otro fue eliminado.
                // Las medidas se capturarán inline en FASE 2.

                // Agregar producto directamente
                addProductToOrder();
            });

            // Función separada para agregar/actualizar producto
            function addProductToOrder() {
                const variantId = $('#modalVariantSelect').val() || null;
                const variantOption = $('#modalVariantSelect option:selected');
                const newQuantity = parseInt($('#modalQuantity').val()) || 1;
                const newPrice = parseFloat($('#modalPrice').val()) || 0;

                // Capturar datos de personalización
                const isCustomized = $('#isCustomized').is(':checked');
                const embroideryText = $('#modalEmbroideryText').val().trim();
                const extrasCost = parseFloat($('#modalExtrasCost').val()) || 0;
                const customizationNotes = $('#modalCustomizationNotes').val().trim();

                // Clonar extras seleccionados para este ítem
                const itemExtras = selectedExtras.map(e => ({
                    id: e.id,
                    name: e.name,
                    price: e.price
                }));

                // === MODO EDIT: Actualizar item existente ===
                if (editingItemIndex !== null) {
                    const editItem = orderItems.find(i => i.index === editingItemIndex);
                    if (editItem) {
                        // Actualizar TODOS los campos del item
                        editItem.product_id = selectedProduct.id;
                        editItem.product_variant_id = variantId;
                        editItem.product_name = selectedProduct.name;
                        editItem.variant_display = variantId ? (variantOption.text().split(' ($')[0] || editItem.variant_display) : null;
                        editItem.variant_sku = variantId ? (variantOption.data('sku') || editItem.variant_sku) : null;
                        editItem.image_url = selectedProduct.image_url;
                        editItem.quantity = newQuantity;
                        editItem.unit_price = newPrice;
                        editItem.lead_time = selectedProduct.lead_time || 0;
                        editItem.requires_measurements = selectedProduct.requires_measurements || false;
                        editItem.product_type_name = selectedProduct.product_type_name || null;
                        editItem.is_customized = isCustomized;
                        editItem.embroidery_text = embroideryText;
                        editItem.extras_cost = extrasCost;
                        editItem.customization_notes = customizationNotes;
                        editItem.selected_extras = itemExtras;
                        editItem.measurements = currentItemMeasurements ? {...currentItemMeasurements} : null;

                        Swal.fire({
                            icon: 'success',
                            title: 'Item actualizado',
                            text: `${selectedProduct.name} ha sido modificado`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                    // Reset modo edición
                    editingItemIndex = null;
                } else {
                    // === MODO CREATE ===
                    // Buscar si ya existe el mismo producto/variante en la lista
                    const existingItem = orderItems.find(item =>
                        item.product_id === selectedProduct.id &&
                        item.product_variant_id === variantId
                    );

                    if (existingItem) {
                        // Ya existe: actualizar cantidad sumando la nueva
                        existingItem.quantity += newQuantity;
                        if (newPrice > 0) existingItem.unit_price = newPrice;
                        if (isCustomized) {
                            existingItem.is_customized = true;
                            if (embroideryText) existingItem.embroidery_text = embroideryText;
                            if (extrasCost > 0) existingItem.extras_cost = extrasCost;
                            if (customizationNotes) existingItem.customization_notes = customizationNotes;
                            if (itemExtras.length > 0) existingItem.selected_extras = itemExtras;
                        }
                        if (currentItemMeasurements) {
                            existingItem.measurements = {...currentItemMeasurements};
                        }

                        Swal.fire({
                            icon: 'info',
                            title: 'Cantidad actualizada',
                            text: `${selectedProduct.name} ahora tiene ${existingItem.quantity} unidades`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    } else {
                        // No existe: crear nuevo item
                        const item = {
                            index: itemIndex,
                            product_id: selectedProduct.id,
                            product_variant_id: variantId,
                            product_name: selectedProduct.name,
                            variant_display: variantId ? variantOption.text().split(' ($')[0] : null,
                            variant_sku: variantId ? variantOption.data('sku') : null,
                            image_url: selectedProduct.image_url,
                            quantity: newQuantity,
                            unit_price: newPrice,
                            lead_time: selectedProduct.lead_time || 0,
                            requires_measurements: selectedProduct.requires_measurements || false,
                            product_type_name: selectedProduct.product_type_name || null,
                            is_customized: isCustomized,
                            embroidery_text: embroideryText,
                            extras_cost: extrasCost,
                            customization_notes: customizationNotes,
                            selected_extras: itemExtras,
                            measurements: currentItemMeasurements ? {...currentItemMeasurements} : null
                        };

                        orderItems.push(item);
                        itemIndex++;
                    }
                }

                productLeadTimes[selectedProduct.id] = selectedProduct.lead_time || 0;

                renderItemsTable();
                updateHiddenInputs();
                calculateTotals();
                calculateMinimumDate();

                $('#addProductModal').modal('hide');
                resetProductModal();
            }

            // ==========================================
            // RENDERIZAR TABLA DE ITEMS
            // ==========================================
            function renderItemsTable() {
                const $tbody = $('#itemsTableBody');
                $tbody.empty();

                if (orderItems.length > 0) {
                    const totalQty = orderItems.reduce((sum, item) => sum + item.quantity, 0);
                    $('#itemsCounter').text(totalQty + ' item' + (totalQty !== 1 ? 's' : '')).show();
                } else {
                    $('#itemsCounter').hide();
                }

                if (orderItems.length === 0) {
                    $tbody.html(`
                        <tr id="noItemsRow">
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Click en "Agregar" para añadir productos
                            </td>
                        </tr>
                    `);
                    return;
                }

                orderItems.forEach((item) => {
                    const subtotal = item.quantity * item.unit_price;
                    const leadTimeDays = item.lead_time || 0;

                    // === CONSTRUCCIÓN DE BADGES POR FILAS ===
                    // Variante
                    const variantText = item.variant_display ?
                        `<small class="text-muted d-block">${item.variant_display}</small>` : '';

                    // FILA 1: Personalizado + Medidas (2 por fila)
                    let badgesRow1 = [];
                    if (item.is_customized) {
                        badgesRow1.push(`<span class="badge badge-purple" style="background: #7f00ff; color: #fff;"><i class="fas fa-magic mr-1"></i>Personal.</span>`);
                    }
                    if (item.requires_measurements) {
                        if (item.measurements) {
                            const measureSummary = buildMeasurementSummaryText(item.measurements);
                            badgesRow1.push(`<span class="badge view-measurements-btn" style="background: #495057; color: white; cursor: pointer;" data-index="${item.index}" title="Click para ver medidas: ${measureSummary}"><i class="fas fa-ruler-combined mr-1"></i>Medidas ✓</span>`);
                        } else {
                            badgesRow1.push(`<span class="badge badge-warning text-dark"><i class="fas fa-ruler mr-1"></i>Sin medidas</span>`);
                        }
                    }

                    // FILA 2: Texto personalizado (embroidery_text)
                    let embroideryRow = '';
                    if (item.embroidery_text) {
                        embroideryRow = `<div class="mt-1" style="font-size: 1.05rem;"><strong>Texto:</strong> ${item.embroidery_text}</div>`;
                    }

                    // FILA 3: Notas
                    let notesRow = '';
                    if (item.customization_notes) {
                        notesRow = `<div class="mt-1" style="font-size: 1.05rem;"><strong>Notas:</strong> ${item.customization_notes}</div>`;
                    }

                    // Badge: Tiene extras (de BD)
                    let extrasRow = '';
                    if (item.selected_extras && item.selected_extras.length > 0) {
                        const extrasNames = item.selected_extras.map(e => e.name).join(', ');
                        const extrasTotal = item.selected_extras.reduce((sum, e) => sum + e.price, 0);
                        extrasRow = `<div class="mt-1"><span class="badge badge-success" title="${extrasNames}"><i class="fas fa-plus-circle mr-1"></i>${item.selected_extras.length} extra${item.selected_extras.length > 1 ? 's' : ''} (+$${extrasTotal.toFixed(2)})</span></div>`;
                    }

                    // Construir HTML de badges
                    const badgesRow1Html = badgesRow1.length > 0 ? `<div class="mt-1" style="line-height: 1.8;">${badgesRow1.join(' ')}</div>` : '';
                    const badgesHtml = badgesRow1Html + embroideryRow + notesRow + extrasRow;

                    $tbody.append(`
                        <tr data-index="${item.index}">
                            <td><img src="${item.image_url || '{{ asset('img/no-image.png') }}'}" class="product-image-thumb" onerror="this.src='{{ asset('img/no-image.png') }}'"></td>
                            <td>
                                <strong style="font-size: 1.05rem;">${item.product_name}</strong>
                                ${variantText}
                                ${badgesHtml}
                            </td>
                            <td><input type="number" class="form-control form-control-sm item-qty" value="${item.quantity}" min="1" max="999" data-index="${item.index}"></td>
                            <td class="text-center">
                                <span class="badge badge-secondary" style="font-size: 0.95rem;">${leadTimeDays} días</span>
                            </td>
                            <td class="font-weight-bold text-success" style="font-size: 1.05rem;">
                                <div>$${subtotal.toFixed(2)}</div>
                                <small class="text-muted">($${item.unit_price.toFixed(2)} c/u)</small>
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-item-btn mr-1" data-index="${item.index}" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${item.index}" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });
            }

            $(document).on('input', '.item-qty', function() {
                const index = $(this).data('index');
                const item = orderItems.find(i => i.index === index);
                if (!item) return;

                item.quantity = parseInt($(this).val()) || 1;

                // Actualizar solo el subtotal de esta fila (sin re-renderizar toda la tabla)
                const subtotal = item.quantity * item.unit_price;
                const $subtotalCell = $(this).closest('tr').find('td:nth-child(5)');
                $subtotalCell.html(`<div>$${subtotal.toFixed(2)}</div><small class="text-muted">($${item.unit_price.toFixed(2)} c/u)</small>`);

                // Actualizar contador de items
                const totalQty = orderItems.reduce((sum, i) => sum + i.quantity, 0);
                $('#itemsCounter').text(totalQty + ' item' + (totalQty !== 1 ? 's' : ''));

                updateHiddenInputs();
                calculateTotals();
                // NO llamar renderItemsTable() aquí - causa pérdida de foco
            });

            $(document).on('click', '.remove-item-btn', function() {
                orderItems = orderItems.filter(i => i.index !== $(this).data('index'));
                renderItemsTable();
                // updateMeasurementsSectionVisibility(); // ELIMINADO (FASE 1)
                updateHiddenInputs();
                calculateTotals();
                calculateMinimumDate();
            });

            // ==========================================
            // VER MEDIDAS EN MODO READONLY (CLICK EN BADGE)
            // ==========================================
            $(document).on('click', '.view-measurements-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const itemIdx = $(this).data('index');
                const item = orderItems.find(i => i.index === itemIdx);
                if (!item || !item.measurements) return;

                // Configurar modal en modo READONLY
                $('#measurementsModalTitle').text('Ver Medidas (Solo Lectura)');
                $('#measurementsProductName').text(item.product_name);

                // Ocultar selector de origen y panel existentes
                $('#measurementsSourceSelector').hide();
                $('#existingMeasurementsPanel').hide();
                $('#saveToClientOption').hide();

                // Mostrar panel de medidas con valores
                $('#newMeasurementsPanel').show();

                // Llenar campos con valores y deshabilitarlos
                const m = item.measurements;
                $('#medBusto').val(m.busto || '').prop('readonly', true);
                $('#medAltoCintura').val(m.alto_cintura || '').prop('readonly', true);
                $('#medCintura').val(m.cintura || '').prop('readonly', true);
                $('#medCadera').val(m.cadera || '').prop('readonly', true);
                $('#medLargo').val(m.largo || '').prop('readonly', true);
                $('#medLargoVestido').val(m.largo_vestido || '').prop('readonly', true);

                // Cambiar estilos a readonly
                $('#measurementsModal .medida-input').css({
                    'background-color': '#e9ecef',
                    'cursor': 'not-allowed'
                });

                // Mostrar footer readonly, ocultar editable
                $('#measurementsEditFooter').hide();
                $('#measurementsReadonlyFooter').show();

                // Marcar modal como readonly para reset posterior
                $('#measurementsModal').data('readonly', true);

                // Abrir modal
                $('#measurementsModal').modal('show');
            });

            // Reset modal de medidas al cerrar
            $('#measurementsModal').on('hidden.bs.modal', function() {
                if ($(this).data('readonly')) {
                    // Restaurar a modo editable para próximo uso
                    $('#measurementsModalTitle').text('Capturar Medidas');
                    $('#measurementsSourceSelector').show();
                    $('#saveToClientOption').show();

                    // Limpiar y habilitar campos
                    $('#measurementsModal .medida-input')
                        .val('')
                        .prop('readonly', false)
                        .css({
                            'background-color': '',
                            'cursor': ''
                        });

                    // Restaurar footers
                    $('#measurementsEditFooter').show();
                    $('#measurementsReadonlyFooter').hide();

                    $(this).data('readonly', false);
                }
            });

            // ==========================================
            // EDITAR ITEM COMPLETO (UNIFORME)
            // ==========================================
            $(document).on('click', '.edit-item-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const itemIdx = $(this).data('index');
                const item = orderItems.find(i => i.index === itemIdx);
                if (!item) return;

                // Guardar índice del item que se está editando
                editingItemIndex = itemIdx;

                // Fetch producto completo desde servidor (incluye variantes)
                $.ajax({
                    url: `{{ url('admin/orders/ajax/product') }}/${item.product_id}`,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        // Mostrar indicador de carga
                        Swal.fire({
                            title: 'Cargando...',
                            text: 'Obteniendo información del producto',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    },
                    success: function(product) {
                        Swal.close();

                        // Construir objeto selectedProduct CON variantes del servidor
                        selectedProduct = {
                            id: product.id,
                            name: product.name,
                            base_price: product.base_price,
                            image_url: product.image_url,
                            sku: product.sku || null,
                            requires_measurements: product.requires_measurements || false,
                            product_type_name: product.product_type_name || null,
                            lead_time: product.lead_time || 0,
                            variants: product.variants || [],
                            extras: product.extras || []
                        };

                        // Precargar precio base (del item, no del producto)
                        modalBasePrice = item.unit_price;

                        // Precargar preview del producto
                        $('#productPreviewName').text(item.product_name);
                        $('#productPreviewSku').text(item.variant_sku || '-');
                        $('#productPreviewImage').attr('src', item.image_url || '{{ asset("img/no-image.png") }}');

                        if (item.product_type_name) {
                            $('#productPreviewType')
                                .html(`<span class="badge badge-secondary">${item.product_type_name}</span>`)
                                .show();
                        } else {
                            $('#productPreviewType').hide();
                        }

                        // Precargar Select2 con producto seleccionado
                        const optionText = `${item.product_name} - $${parseFloat(item.unit_price).toFixed(2)}`;
                        const newOption = new Option(optionText, item.product_id, true, true);
                        $('#modalProductSelect').append(newOption).trigger('change');

                        // Precargar cantidad
                        $('#modalQuantity').val(item.quantity);

                        // Precargar precio
                        $('#modalPrice').val(item.unit_price);

                        // Cargar TODAS las variantes y preseleccionar la actual
                        const $variantSelect = $('#modalVariantSelect');
                        $variantSelect.empty().append('<option value="">-- Producto base --</option>');

                        if (selectedProduct.variants && selectedProduct.variants.length > 0) {
                            selectedProduct.variants.forEach(v => {
                                const isSelected = v.id == item.product_variant_id;
                                $variantSelect.append(
                                    `<option value="${v.id}" data-price="${v.price}" data-sku="${v.sku}" ${isSelected ? 'selected' : ''}>${v.display} ($${parseFloat(v.price).toFixed(2)})</option>`
                                );
                            });
                            $('#variantGroup').show();
                        } else {
                            $('#variantGroup').hide();
                        }

                        // Precargar personalización
                        if (item.is_customized) {
                            $('#isCustomized').prop('checked', true);
                            $('#customizationBody').show();
                            $('#customizationChevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                        } else {
                            $('#isCustomized').prop('checked', false);
                            $('#customizationBody').hide();
                            $('#customizationChevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                        }
                        $('#modalEmbroideryText').val(item.embroidery_text || '');
                        $('#modalExtrasCost').val(item.extras_cost || 0);
                        $('#modalCustomizationNotes').val(item.customization_notes || '');

                        // Precargar extras seleccionados
                        selectedExtras = item.selected_extras ? [...item.selected_extras] : [];
                        renderSelectedExtrasList();
                        $('#productExtrasSection').show();

                        // Precargar medidas del item
                        currentItemMeasurements = item.measurements ? {...item.measurements} : null;

                        // Actualizar sección de medidas según tipo de producto
                        updateMeasurementsSectionVisibility();

                        // Mostrar subtotal
                        $('#itemSubtotalContainer').show();
                        updateItemSubtotal();
                        updatePriceComparison();

                        // Cambiar texto del botón a "Guardar Cambios"
                        $('#addProductBtn').text('Guardar Cambios').prop('disabled', false);

                        // Abrir modal de producto
                        $('#addProductModal').modal('show');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'No se pudo cargar la información del producto', 'error');
                        console.error('Error fetching product:', xhr);
                    }
                });
            });

            // ==========================================
            // HIDDEN INPUTS
            // ==========================================
            function updateHiddenInputs() {
                const $container = $('#hiddenItemsContainer');
                $container.empty();

                orderItems.forEach((item, idx) => {
                    // Campos base del ítem
                    $container.append(`
                        <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.product_variant_id || ''}">
                        <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                        <input type="hidden" name="items[${idx}][embroidery_text]" value="${item.embroidery_text || ''}">
                        <input type="hidden" name="items[${idx}][customization_notes]" value="${item.customization_notes || ''}">
                        <input type="hidden" name="items[${idx}][extras_cost]" value="${item.extras_cost || 0}">
                        <input type="hidden" name="items[${idx}][is_customized]" value="${item.is_customized ? 1 : 0}">
                    `);

                    // Extras seleccionados (de BD)
                    if (item.selected_extras && item.selected_extras.length > 0) {
                        item.selected_extras.forEach((extra, extIdx) => {
                            $container.append(`
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][id]" value="${extra.id}">
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][name]" value="${extra.name}">
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][price]" value="${extra.price}">
                            `);
                        });
                    }

                    // Medidas del item
                    if (item.measurements) {
                        $container.append(`
                            <input type="hidden" name="items[${idx}][measurements][busto]" value="${item.measurements.busto || ''}">
                            <input type="hidden" name="items[${idx}][measurements][cintura]" value="${item.measurements.cintura || ''}">
                            <input type="hidden" name="items[${idx}][measurements][cadera]" value="${item.measurements.cadera || ''}">
                            <input type="hidden" name="items[${idx}][measurements][alto_cintura]" value="${item.measurements.alto_cintura || ''}">
                            <input type="hidden" name="items[${idx}][measurements][largo]" value="${item.measurements.largo || ''}">
                            <input type="hidden" name="items[${idx}][measurements][largo_vestido]" value="${item.measurements.largo_vestido || ''}">
                            <input type="hidden" name="items[${idx}][measurements][save_to_client]" value="${item.measurements.save_to_client ? '1' : '0'}">
                        `);
                    }
                });
            }

            // ==========================================
            // CALCULAR TOTALES CON IVA
            // ==========================================
            function calculateTotals() {
                let subtotal = 0;
                orderItems.forEach(item => {
                    subtotal += item.quantity * item.unit_price;
                });

                const discount = parseFloat($('#discount').val()) || 0;
                const subtotalAfterDiscount = Math.max(0, subtotal - discount);

                const requiresInvoice = $('#requiresInvoice').is(':checked');
                const iva = requiresInvoice ? subtotalAfterDiscount * IVA_RATE : 0;
                const total = subtotalAfterDiscount + iva;

                $('#subtotalDisplay').text('$' + subtotal.toFixed(2));

                // IVA en resumen (integrado con checkbox)
                if (requiresInvoice) {
                    $('#ivaDisplay').removeClass('iva-amount-disabled').addClass('iva-amount-active');
                    $('#ivaDisplay').text('$' + iva.toFixed(2));
                } else {
                    $('#ivaDisplay').removeClass('iva-amount-active').addClass('iva-amount-disabled');
                    $('#ivaDisplay').text('$0.00');
                }

                $('#totalDisplay').text('$' + total.toFixed(2));
            }

            $('#discount').on('input', calculateTotals);
            $('#requiresInvoice').on('change', calculateTotals);

            // ==========================================
            // LÓGICA DE PAGO
            // ==========================================
            $('#paymentMethod').on('change', function() {
                const hasMethod = $(this).val() !== '';
                $('#payFullGroup').toggle(hasMethod);
                $('#anticipoGroup').toggle(hasMethod && !$('#payFull').is(':checked'));
            });

            $('#payFull').on('change', function() {
                $('#anticipoGroup').toggle(!$(this).is(':checked'));
                if ($(this).is(':checked')) $('#initialPayment').val('');
            });

            $('#paymentMethod').trigger('change');

            // ==========================================
            // FECHA MÍNIMA Y URGENCIA
            // ==========================================
            function calculateMinimumDate() {
                if (orderItems.length === 0) {
                    $('#minimumDateAlert').hide();
                    $('#promisedDate').attr('min', '{{ date('Y-m-d') }}');
                    return;
                }

                let maxLeadTime = 0;
                orderItems.forEach(item => {
                    if (item.lead_time > maxLeadTime) maxLeadTime = item.lead_time;
                });

                const urgency = $('#urgencyLevel').val() || 'normal';
                const adjustedDays = Math.ceil(maxLeadTime * urgencyMultipliers[urgency]);

                const minDate = new Date();
                minDate.setDate(minDate.getDate() + adjustedDays);

                const minDateStr = minDate.toISOString().split('T')[0];
                const displayDate = minDate.toLocaleDateString('es-MX', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });

                $('#minimumDateDisplay').text(displayDate + ' (' + adjustedDays + ' días)');
                $('#minimumDateAlert').show();
                $('#promisedDate').attr('min', minDateStr);

                validatePromisedDate();
            }

            function validatePromisedDate() {
                const promised = $('#promisedDate').val();
                const minDate = $('#promisedDate').attr('min');

                if (promised && minDate && promised < minDate) {
                    $('#promisedDate').addClass('is-invalid');
                    $('#dateWarning').show();
                } else {
                    $('#promisedDate').removeClass('is-invalid');
                    $('#dateWarning').hide();
                }
            }

            $('#urgencyLevel').on('change', calculateMinimumDate);
            $('#promisedDate').on('change', validatePromisedDate);

            // ==========================================
            // VALIDACIÓN: CLIENTE REQUERIDO PARA AGREGAR PRODUCTOS
            // ==========================================
            $('#btnAddProduct').on('click', function() {
                if (!$('#cliente_id').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cliente requerido',
                        text: 'Debe seleccionar un cliente antes de agregar productos',
                        confirmButtonColor: '#7f00ff',
                        confirmButtonText: 'Seleccionar cliente'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#clientSearchModal').modal('show');
                        }
                    });
                    return;
                }
                $('#addProductModal').modal('show');
            });

            // ==========================================
            // MODALES - FOCUS Y RESET
            // ==========================================
            $('#addProductModal').on('show.bs.modal', function() {
                // NO resetear si estamos editando un item existente
                if (editingItemIndex === null) {
                    resetProductModal();
                }
            });
            $('#addProductModal').on('shown.bs.modal', function() {
                // Solo abrir Select2 en modo CREATE (no en EDIT)
                if (editingItemIndex === null) {
                    setTimeout(function() {
                        $('#modalProductSelect').select2('open');
                        setTimeout(function() {
                            document.querySelector('.select2-search__field')?.focus();
                        }, 50);
                    }, 150);
                }
            });

            $('#quickClientModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#quickClientNombre').focus();
                }, 150);
            });

            // HANDLER #measurementsModal ELIMINADO (FASE 1) - Modal externo ya no existe
            // HANDLER #btnGoToMeasures ELIMINADO (FASE 1) - Era el que cerraba el modal de producto

            // ==========================================
            // VISIBILIDAD SECCIÓN MEDIDAS — ELIMINADO (FASE 1)
            // Funciones eliminadas:
            // - hasProductsRequiringMeasurements()
            // - updateMeasurementsSectionVisibility()
            // El card global de medidas ya no existe.
            // ==========================================

            // ==========================================
            // VALIDACIÓN AL ENVIAR
            // ==========================================
            $('#orderForm').on('submit', function(e) {
                let errors = [];

                // 1. Validar cliente seleccionado
                if (!$('#cliente_id').val()) {
                    errors.push('<li><i class="fas fa-user mr-1"></i> Debe seleccionar un cliente</li>');
                }

                // 2. Validar al menos un producto
                if (orderItems.length === 0) {
                    errors.push('<li><i class="fas fa-box mr-1"></i> Debe agregar al menos un producto</li>');
                }

                // 3. VALIDACIÓN MEDIDAS LEGACY ELIMINADA (FASE 1)
                // Las medidas se capturarán inline por ítem en FASE 2.
                // Por ahora, no se bloquea el submit por medidas.

                // 4. Validar método de pago (solo requerido si hay anticipo)
                const initialPaymentVal = parseFloat($('#initialPayment').val()) || 0;
                const payFullChecked = $('#payFull').is(':checked');
                if ((initialPaymentVal > 0 || payFullChecked) && !$('#paymentMethod').val()) {
                    errors.push('<li><i class="fas fa-dollar-sign mr-1"></i> Debe seleccionar un método de pago para registrar el anticipo</li>');
                }

                // 5. Validar fecha prometida
                if (!$('#promisedDate').val()) {
                    errors.push('<li><i class="fas fa-calendar mr-1"></i> Debe indicar la fecha de entrega prometida</li>');
                }

                // 6. Validar que fecha prometida sea mayor o igual a la fecha mínima
                const promisedDate = $('#promisedDate').val();
                const minDate = $('#promisedDate').attr('min');
                if (promisedDate && minDate && promisedDate < minDate) {
                    const minDateFormatted = new Date(minDate + 'T00:00:00').toLocaleDateString('es-MX', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });
                    errors.push('<li><i class="fas fa-exclamation-triangle mr-1"></i> La fecha de entrega debe ser posterior o igual a ' + minDateFormatted + '</li>');
                }

                // Si hay errores, mostrar SweetAlert y cancelar envío
                if (errors.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos requeridos',
                        html: '<ul style="text-align:left;margin:0;padding-left:10px;list-style:none;">' + errors.join('') + '</ul>',
                        confirmButtonColor: '#7f00ff'
                    });
                    return false;
                }
            });

            // ==========================================
            // FASE 3: INICIALIZACIÓN MODO EDICIÓN
            // ==========================================
            @if(isset($isEdit) && isset($order) && isset($orderItems))
            (function initEditMode() {
                // Precargar cliente
                const cliente = @json($order->cliente);
                if (cliente) {
                    selectedClientData = cliente;
                    $('#cliente_id').val(cliente.id);
                    $('.cliente-selector-btn').addClass('has-client')
                        .html(`
                            <div class="cliente-info">
                                <span class="cliente-nombre">${cliente.nombre} ${cliente.apellidos || ''}</span>
                                <span class="cliente-telefono">${cliente.telefono || ''}</span>
                            </div>
                            <i class="fas fa-check-circle text-success"></i>
                        `);
                    // Habilitar secciones
                    $('#measurementSection').removeClass('section-disabled');
                    $('#paymentSection').removeClass('section-disabled');
                    $('#deliverySection').removeClass('section-disabled');
                }

                // Precargar items - CORREGIDO: usar 'index' en lugar de 'tempId'
                const existingItems = @json($orderItems);
                if (existingItems && existingItems.length > 0) {
                    existingItems.forEach(function(item) {
                        const newItem = {
                            index: itemIndex, // CRÍTICO: debe ser 'index' no 'tempId'
                            product_id: item.product_id,
                            product_variant_id: item.product_variant_id,
                            product_name: item.product_name,
                            variant_sku: item.variant_sku,
                            variant_display: item.variant_display || item.variant_sku,
                            unit_price: parseFloat(item.unit_price) || 0,
                            quantity: parseInt(item.quantity) || 1,
                            lead_time: item.lead_time || 0,
                            // Campos de tipo de producto
                            requires_measurements: item.requires_measurements || false,
                            product_type_name: item.product_type_name || null,
                            // Campos de personalización (trim para evitar falsos negativos por espacios)
                            is_customized: !!((item.embroidery_text || '').trim() || (item.customization_notes || '').trim()),
                            embroidery_text: (item.embroidery_text || '').trim(),
                            extras_cost: 0,
                            customization_notes: (item.customization_notes || '').trim(),
                            selected_extras: [],
                            // Medidas del item
                            measurements: item.measurements || null,
                            // Imagen
                            image_url: item.image_url || null
                        };

                        // Lead time en cache
                        productLeadTimes[item.product_id] = item.lead_time || 0;

                        orderItems.push(newItem);
                        itemIndex++; // Incrementar DESPUÉS de asignar
                    });

                    // Renderizar tabla usando función correcta
                    renderItemsTable();
                    updateHiddenInputs();
                    calculateTotals();
                    calculateMinimumDate();
                }

                // Precargar valores del formulario
                $('#urgency').val('{{ $order->urgency_level ?? "normal" }}').trigger('change');
                $('#promisedDate').val('{{ $order->promised_date ? \Carbon\Carbon::parse($order->promised_date)->format("Y-m-d") : "" }}');
                $('#discount').val('{{ $order->discount ?? 0 }}');
                $('#notes').val(@json($order->notes ?? ''));
                @if($order->requires_invoice)
                $('#requiresInvoice').prop('checked', true).trigger('change');
                @endif
            })();
            @endif
        });

        // ==========================================
        // VALIDACIÓN DE MEDIDAS (formato decimal)
        // ==========================================
        function validateMedidaModal(input) {
            let value = input.value;

            // 1. Eliminar todo excepto números y punto
            value = value.replace(/[^0-9.]/g, '');

            // 2. Evitar más de un punto
            value = value.replace(/(\..*)\./g, '$1');

            // Regex FINAL (igual al backend)
            const finalRegex = /^[1-9]\d{0,2}(\.(?:[1-9]|\d[1-9]))?$/;

            // Regex parcial permitida SOLO para escribir
            const partialRegex = /^[1-9]\d{0,2}(\.\d{0,2})?$/;

            // 3. Bloquear casos inválidos inmediatos
            if (
                value === '.' ||
                value === '0' ||
                value === '0.' ||
                value === '.0'
            ) {
                input.value = '';
                return;
            }

            // 4. Permitir escritura progresiva válida
            if (value && !partialRegex.test(value)) {
                input.value = value.slice(0, -1);
                return;
            }

            // 5. Validación visual final
            if (value && finalRegex.test(value)) {
                input.classList.remove('is-invalid');
            } else if (value) {
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }

            input.value = value;
        }

        // ==========================================
        // TOUCH FIX: Click/Touch en card enfoca input
        // ==========================================
        (function() {
            // Esperar a que el modal exista en el DOM
            $(document).on('shown.bs.modal', '#measurementsModal', function() {
                const cards = document.querySelectorAll('#measurementsModal .medida-card');

                cards.forEach(function(card) {
                    let lastTouchTime = 0;

                    function focusInput(e) {
                        if (e.target.tagName === 'INPUT') return;

                        const input = card.querySelector('.medida-input');
                        if (!input) return;

                        e.preventDefault();
                        e.stopPropagation();

                        input.focus();

                        requestAnimationFrame(function() {
                            input.select();
                        });
                    }

                    card.addEventListener('touchstart', function(e) {
                        if (e.target.tagName === 'INPUT') return;
                        lastTouchTime = Date.now();
                    }, { passive: true });

                    card.addEventListener('touchend', function(e) {
                        if (e.target.tagName === 'INPUT') return;

                        const touchDuration = Date.now() - lastTouchTime;
                        if (touchDuration < 300) {
                            focusInput(e);
                        }
                    }, { passive: false });

                    card.addEventListener('click', function(e) {
                        if (e.target.tagName === 'INPUT') return;

                        if (Date.now() - lastTouchTime < 500) return;

                        focusInput(e);
                    });
                });
            });
        })();
    </script>
@stop
