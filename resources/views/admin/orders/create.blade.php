@extends('adminlte::page')

@section('title', 'Nuevo Pedido')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clipboard-list mr-2"></i> Nuevo Pedido</h1>
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
        /* === 3. PRODUCTOS: SCROLL FIJO ==================== */
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
        /* === 4. PAGO: SIEMPRE VISIBLE ===================== */
        /* ================================================== */
        .payment-section .form-group {
            margin-bottom: 0.75rem;
        }

        /* ================================================== */
        /* === 5. ENTREGA: SIEMPRE VISIBLE ================== */
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

            /* Orden móvil: 1.Cliente → 2.Medidas → 3.Productos → 4.Pago → 5.Entrega → 6.Resumen → 7.Notas → 8.Botón */
            .order-mobile-1 { order: 1; }
            .order-mobile-2 { order: 2; }
            .order-mobile-3 { order: 3; }
            .order-mobile-4 { order: 4; }
            .order-mobile-5 { order: 5; }
            .order-mobile-6 { order: 6; }
            .order-mobile-7 { order: 7; }
            .order-mobile-8 { order: 8; }

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
        /* === MODAL MEDIDAS (PRESERVADO) =================== */
        /* ================================================== */
        #measurementsModal .modal-dialog {
            margin: 0.5rem auto;
            max-width: 95vw;
        }

        #measurementsModal .modal-body {
            padding: 0.75rem;
        }

        #measurementsModal .modal-header {
            padding: 0.5rem 1rem;
        }

        .medida-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 6px 10px;
            background: #ffffff;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            cursor: pointer;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .medida-card:hover,
        .medida-card:focus-within {
            border-color: rgba(247, 0, 255, 0.6);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .medida-img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .medida-input {
            border-radius: 6px;
            font-size: 0.9rem;
            padding: 0.35rem 0.5rem;
            text-align: center;
            user-select: text;
            -webkit-user-select: text;
            touch-action: auto;
        }

        .medida-input:focus {
            border-color: #7f00ff;
            box-shadow: 0 0 0 2px rgba(127, 0, 255, 0.2);
        }

        .medida-label {
            font-weight: 600;
            font-size: 0.7rem;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            display: block;
            color: #111827;
            text-transform: uppercase;
        }

        .medida-hint {
            display: block;
            font-size: 0.85rem;
            color: #7f00ff;
            margin-top: 8px;
            font-weight: 700;
        }

        .medidas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        @media (min-width: 992px) {
            #measurementsModal .modal-dialog {
                max-width: 800px;
            }

            .medida-img {
                width: 100px;
                height: 100px;
            }

            .medida-label {
                font-size: 0.85rem;
            }

            .medida-card {
                padding: 16px 12px 18px;
            }

            .medidas-grid {
                gap: 16px;
            }

            .medida-input {
                font-size: 1rem;
                padding: 0.5rem 0.6rem;
            }
        }

        @media (max-width: 400px) {
            .medidas-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .medida-img {
                width: 40px;
                height: 40px;
            }

            .medida-label {
                font-size: 0.65rem;
            }
        }

        @media (hover: none) and (pointer: coarse) {
            .medida-card {
                min-height: 90px;
            }

            .medida-card:hover {
                border-color: #e5e7eb;
                box-shadow: none;
            }

            .medida-card:active,
            .medida-card:focus-within {
                border-color: rgba(247, 0, 255, 0.6);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            .medida-input {
                min-height: 38px;
                font-size: 16px;
            }
        }

        /* Historial medidas */
        .measurement-history-item {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fff;
            transition: all 0.2s ease;
        }

        .measurement-history-item:hover {
            border-color: #7f00ff;
            background: #faf5ff;
        }

        .measurement-history-item.active {
            border-color: #28a745;
            background: #f0fff4;
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
        }
    </style>
@stop

@section('content')
    <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
        @csrf

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
                    <div class="card-header bg-primary text-white py-2">
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

                {{-- 2. MEDIDAS --}}
                <div class="card card-erp order-mobile-2">
                    <div class="card-header bg-purple text-white py-2"
                        style="background: linear-gradient(135deg, #e100ff 0%, #7f00ff 100%);">
                        <h5 class="mb-0"><i class="fas fa-ruler mr-2"></i> 2. Medidas</h5>
                    </div>
                    <div class="card-body medidas-section">
                        <input type="hidden" name="client_measurement_id" id="client_measurement_id" value="">

                        <div id="medidasPlaceholder" class="medidas-placeholder">
                            <span><i class="fas fa-user-slash mr-2"></i> Seleccione un cliente primero</span>
                        </div>

                        <div id="clientMeasuresSection" style="display: none;">
                            <div id="measurementsEmpty" style="display: none;">
                                <div class="alert alert-warning mb-2 py-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>Cliente sin medidas registradas.</strong>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="btnCapturarMedidas">
                                    <i class="fas fa-plus mr-1"></i> Capturar Medidas
                                </button>
                            </div>

                            <div id="measurementsList" style="display: none;">
                                <div id="activeMeasurement" class="border rounded p-2 mb-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge badge-success mr-1">ACTIVA</span>
                                            <small class="text-muted" id="activeMeasurementDate"></small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-xs btn-outline-primary mr-1"
                                                id="btnEditActiveMeasurement" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary"
                                                id="btnShowAllMeasurements" title="Ver todas">
                                                <i class="fas fa-list"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="activeMeasurementSummary" class="mt-1 small"></div>
                                </div>

                                <div id="measurementsHistory" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted font-weight-bold">HISTORIAL</small>
                                        <button type="button" class="btn btn-outline-primary btn-xs"
                                            id="btnCapturarNuevas">
                                            <i class="fas fa-plus"></i> Nueva
                                        </button>
                                    </div>
                                    <div id="measurementsHistoryList"></div>
                                </div>
                            </div>

                            <div id="measurementsLoading" class="text-center py-2" style="display: none;">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Cargando...
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. PAGO --}}
                <div class="card card-erp order-mobile-4">
                    <div class="card-header bg-info text-white py-2">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> 4. Pago</h5>
                    </div>
                    <div class="card-body payment-section">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold mb-1">Método de Pago</label>
                            <select name="payment_method" id="paymentMethod" class="form-control form-control-sm" required>
                                <option value="">-- Selecciona una opción --</option>
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

                {{-- 5. ENTREGA --}}
                <div class="card card-erp order-mobile-5">
                    <div class="card-header bg-warning py-2">
                        <h5 class="mb-0"><i class="fas fa-truck mr-2"></i> 5. Entrega</h5>
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

                {{-- 3. PRODUCTOS --}}
                <div class="card card-erp order-mobile-3">
                    <div class="card-header bg-primary text-white py-2 productos-header">
                        <h5 class="mb-0">
                            <i class="fas fa-box mr-2"></i> 3. Productos
                            <span id="itemsCounter" class="badge badge-light items-counter ml-2" style="display:none;">0</span>
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addProductModal">
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
                                        <th style="width: 100px;">Precio</th>
                                        <th style="width: 100px;">Subtotal</th>
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

                {{-- 6. RESUMEN (con IVA integrado) --}}
                <div class="card card-erp resumen-card order-mobile-6">
                    <div class="card-header bg-success text-white py-2">
                        <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> 6. Resumen</h5>
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

                {{-- NOTAS --}}
                <div class="card card-erp order-mobile-7">
                    <div class="card-header bg-light py-2">
                        <h5 class="mb-0"><i class="fas fa-sticky-note mr-2"></i> Notas</h5>
                    </div>
                    <div class="card-body py-2">
                        <textarea name="notes" class="form-control form-control-sm" rows="2" maxlength="2000"
                            placeholder="Observaciones generales del pedido...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- BOTÓN CREAR PEDIDO --}}
                <div class="order-mobile-8">
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
                <div class="modal-header bg-primary text-white py-2">
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
                <div class="modal-header bg-success text-white">
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-box mr-2"></i> Agregar Producto</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="productPreviewImage" src="{{ asset('img/no-image.png') }}"
                                class="img-fluid rounded mb-2" style="max-height: 200px;">
                            <div id="productPreviewName" class="font-weight-bold">-</div>
                            <div id="productPreviewSku" class="text-muted small">-</div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Buscar Producto</label>
                                <select id="modalProductSelect" class="form-control" style="width: 100%;">
                                    <option value="">Escriba para buscar...</option>
                                </select>
                            </div>

                            <div class="form-group" id="variantGroup" style="display: none;">
                                <label class="font-weight-bold">Variante</label>
                                <select id="modalVariantSelect" class="form-control">
                                    <option value="">-- Producto base --</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Cantidad *</label>
                                        <input type="number" id="modalQuantity" class="form-control" value="1"
                                            min="1" max="999">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Precio Unitario *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" id="modalPrice" class="form-control" step="0.01"
                                                min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Texto a Bordar</label>
                                <input type="text" id="modalEmbroideryText" class="form-control" maxlength="255"
                                    placeholder="Nombre, frase, iniciales...">
                            </div>

                            <div class="form-group mb-0">
                                <label>Notas de Personalización</label>
                                <textarea id="modalCustomizationNotes" class="form-control" rows="2" maxlength="1000"
                                    placeholder="Instrucciones especiales..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="addProductBtn" disabled>
                        <i class="fas fa-plus mr-1"></i> Agregar al Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MODAL: CAPTURAR MEDIDAS --}}
    {{-- ============================================== --}}
    <div class="modal fade" id="measurementsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #e100ff 0%, #7f00ff 100%);">
                    <h5 class="modal-title text-white" style="font-size: 1.2rem;">
                        <i class="fas fa-ruler-vertical mr-1"></i>
                        <i class="fas fa-female mr-1"></i>
                        Medidas (cm)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"
                        style="font-size: 1.2rem;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="medidas-grid">
                        {{-- BUSTO --}}
                        <div class="text-center">
                            <label class="medida-label">BUSTO</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/busto.png') }}" alt="Busto" class="medida-img">
                                <input type="text" id="measure_busto"
                                    class="form-control form-control-sm medida-input" placeholder="80.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">30 - 200 cm</small>
                            </div>
                        </div>
                        {{-- ALTO CINTURA --}}
                        <div class="text-center">
                            <label class="medida-label">ALTO CINT.</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura" class="medida-img">
                                <input type="text" id="measure_alto_cintura"
                                    class="form-control form-control-sm medida-input" placeholder="40.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">10 - 100 cm</small>
                            </div>
                        </div>
                        {{-- CINTURA --}}
                        <div class="text-center">
                            <label class="medida-label">CINTURA</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/cintura.png') }}" alt="Cintura" class="medida-img">
                                <input type="text" id="measure_cintura"
                                    class="form-control form-control-sm medida-input" placeholder="70.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">30 - 200 cm</small>
                            </div>
                        </div>
                        {{-- CADERA --}}
                        <div class="text-center">
                            <label class="medida-label">CADERA</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/cadera.png') }}" alt="Cadera" class="medida-img">
                                <input type="text" id="measure_cadera"
                                    class="form-control form-control-sm medida-input" placeholder="95.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">30 - 200 cm</small>
                            </div>
                        </div>
                        {{-- LARGO BLUSA --}}
                        <div class="text-center">
                            <label class="medida-label">LARGO BL.</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/largo.png') }}" alt="Largo Blusa" class="medida-img">
                                <input type="text" id="measure_largo"
                                    class="form-control form-control-sm medida-input" placeholder="60.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">30 - 200 cm</small>
                            </div>
                        </div>
                        {{-- LARGO VESTIDO --}}
                        <div class="text-center">
                            <label class="medida-label">LARGO VEST.</label>
                            <div class="medida-card">
                                <img src="{{ asset('images/largo_vestido.png') }}" alt="Largo Vestido"
                                    class="medida-img">
                                <input type="text" id="measure_largo_vestido"
                                    class="form-control form-control-sm medida-input" placeholder="100.5" maxlength="6"
                                    inputmode="decimal">
                                <small class="medida-hint">30 - 200 cm</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3" style="gap: 10px;">
                        <div class="flex-grow-1">
                            <input type="text" id="measure_notes" class="form-control"
                                placeholder="Notas / Observaciones..." maxlength="500" style="font-size: 0.85rem;">
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="button" class="btn" id="saveMeasurementsBtn"
                            style="background: linear-gradient(135deg, #e100ff 0%, #7f00ff 100%); border: none; color: #fff;">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
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
            let clientMeasurements = [];
            let selectedMeasurementId = null;
            let editingMeasurementId = null;
            let clientSearchTimeout = null;

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

                // Ocultar placeholder y mostrar sección de medidas
                $('#medidasPlaceholder').hide();
                loadClientMeasurements(id);
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
            // GESTIÓN DE MEDIDAS
            // ==========================================
            function loadClientMeasurements(clienteId) {
                clientMeasurements = [];
                selectedMeasurementId = null;
                editingMeasurementId = null;
                $('#client_measurement_id').val('');

                $('#clientMeasuresSection').show();
                $('#measurementsLoading').show();
                $('#measurementsEmpty').hide();
                $('#measurementsList').hide();
                $('#measurementsHistory').hide();

                $.ajax({
                    url: '{{ url('admin/client-measurements/cliente') }}/' + clienteId,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        $('#measurementsLoading').hide();
                        clientMeasurements = response.measurements || [];

                        if (clientMeasurements.length === 0) {
                            $('#measurementsEmpty').show();
                            $('#measurementsList').hide();
                        } else {
                            $('#measurementsEmpty').hide();
                            $('#measurementsList').show();

                            const primary = clientMeasurements.find(m => m.is_primary) ||
                                clientMeasurements[0];
                            selectMeasurement(primary.id);
                            renderMeasurementsHistory();
                        }
                    },
                    error: function() {
                        $('#measurementsLoading').hide();
                        $('#measurementsEmpty').show();
                    }
                });
            }

            function selectMeasurement(measurementId) {
                selectedMeasurementId = measurementId;
                $('#client_measurement_id').val(measurementId);

                const measurement = clientMeasurements.find(m => m.id === measurementId);
                if (!measurement) return;

                $('#activeMeasurementDate').text(measurement.created_at_short || measurement.created_at);
                $('#activeMeasurementSummary').html(buildMeasurementSummary(measurement));
                renderMeasurementsHistory();
            }

            function buildMeasurementSummary(m) {
                const fields = [{
                        key: 'busto',
                        label: 'Bu'
                    },
                    {
                        key: 'alto_cintura',
                        label: 'AC'
                    },
                    {
                        key: 'cintura',
                        label: 'Ci'
                    },
                    {
                        key: 'cadera',
                        label: 'Ca'
                    },
                    {
                        key: 'largo',
                        label: 'LB'
                    },
                    {
                        key: 'largo_vestido',
                        label: 'LV'
                    }
                ];
                let parts = [];
                fields.forEach(f => {
                    if (m[f.key]) parts.push(`<strong>${f.label}:</strong>${m[f.key]}`);
                });
                return parts.join(' | ') || '<em class="text-muted">Sin medidas</em>';
            }

            function renderMeasurementsHistory() {
                const $list = $('#measurementsHistoryList');
                $list.empty();

                clientMeasurements.forEach(m => {
                    const isActive = m.id === selectedMeasurementId;
                    const isPrimary = m.is_primary;

                    let badges = '';
                    if (isActive) badges += '<span class="badge badge-success mr-1">EN USO</span>';
                    if (isPrimary && !isActive) badges +=
                        '<span class="badge badge-info mr-1">PRINCIPAL</span>';

                    $list.append(`
                        <div class="measurement-history-item ${isActive ? 'active' : ''}" data-id="${m.id}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    ${badges}
                                    <small class="text-muted">${m.created_at_short || m.created_at}</small>
                                </div>
                                <div>
                                    ${!isActive ? `<button type="button" class="btn btn-xs btn-outline-success btn-use-measurement mr-1" data-id="${m.id}"><i class="fas fa-check"></i></button>` : ''}
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-edit-measurement" data-id="${m.id}"><i class="fas fa-edit"></i></button>
                                </div>
                            </div>
                            <div class="small mt-1">${buildMeasurementSummary(m)}</div>
                        </div>
                    `);
                });
            }

            $('#btnShowAllMeasurements').on('click', function() {
                $('#measurementsHistory').slideToggle(200);
            });

            $(document).on('click', '.btn-use-measurement', function() {
                selectMeasurement($(this).data('id'));
                Swal.fire({
                    icon: 'success',
                    title: 'Medida seleccionada',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
            });

            $(document).on('click', '.btn-edit-measurement', function() {
                openMeasurementModal($(this).data('id'));
            });

            $('#btnEditActiveMeasurement').on('click', function() {
                if (selectedMeasurementId) openMeasurementModal(selectedMeasurementId);
            });

            $('#btnCapturarMedidas, #btnCapturarNuevas').on('click', function() {
                openMeasurementModal(null);
            });

            function openMeasurementModal(measurementId = null) {
                editingMeasurementId = measurementId;
                $('#measurementsModal .medida-input').val('');
                $('#measure_notes').val('');

                if (measurementId) {
                    const m = clientMeasurements.find(x => x.id === measurementId);
                    if (m) {
                        $('#measure_busto').val(m.busto || '');
                        $('#measure_cintura').val(m.cintura || '');
                        $('#measure_cadera').val(m.cadera || '');
                        $('#measure_alto_cintura').val(m.alto_cintura || '');
                        $('#measure_largo').val(m.largo || '');
                        $('#measure_largo_vestido').val(m.largo_vestido || '');
                        $('#measure_notes').val(m.notes || '');
                    }
                }

                $('#measurementsModal').modal('show');
            }

            $('#saveMeasurementsBtn').on('click', function() {
                const clienteId = $('#cliente_id').val();
                if (!clienteId) return;

                const $btn = $(this);
                $btn.prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

                const measureData = {
                    busto: parseFloat($('#measure_busto').val().replace(',', '.')) || null,
                    cintura: parseFloat($('#measure_cintura').val().replace(',', '.')) || null,
                    cadera: parseFloat($('#measure_cadera').val().replace(',', '.')) || null,
                    alto_cintura: parseFloat($('#measure_alto_cintura').val().replace(',', '.')) ||
                        null,
                    largo: parseFloat($('#measure_largo').val().replace(',', '.')) || null,
                    largo_vestido: parseFloat($('#measure_largo_vestido').val().replace(',', '.')) ||
                        null,
                    notes: $('#measure_notes').val().trim() || null
                };

                const url = editingMeasurementId ?
                    '{{ url('admin/client-measurements') }}/' + editingMeasurementId :
                    '{{ route('admin.client-measurements.store') }}';

                const method = editingMeasurementId ? 'PUT' : 'POST';

                const data = editingMeasurementId ?
                    {
                        _token: '{{ csrf_token() }}',
                        ...measureData
                    } :
                    {
                        _token: '{{ csrf_token() }}',
                        cliente_id: clienteId,
                        is_primary: clientMeasurements.length === 0 ? 1 : 0,
                        ...measureData
                    };

                $.ajax({
                    url: url,
                    method: method,
                    data: data,
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        $('#measurementsModal').modal('hide');
                        loadClientMeasurements(clienteId);
                        Swal.fire({
                            icon: 'success',
                            title: editingMeasurementId ? 'Medidas actualizadas' :
                                'Medidas guardadas',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON
                            .errors).flat() : [xhr.responseJSON?.message ||
                            'Error al guardar'
                        ];
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: '<ul style="text-align:left;margin:0;padding-left:20px;">' +
                                errors.map(e => '<li>' + e + '</li>').join('') +
                                '</ul>',
                            confirmButtonColor: '#7f00ff'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save mr-1"></i> Guardar');
                    }
                });
            });

            // Validación de inputs de medidas
            function validateMedida(input) {
                let value = input.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
                if (value === '.' || value === '0' || value === '0.' || value === '.0') {
                    input.value = '';
                    return;
                }
                const partialRegex = /^[1-9]\d{0,2}(\.\d{0,2})?$/;
                if (value && !partialRegex.test(value)) {
                    input.value = value.slice(0, -1);
                    return;
                }
                input.value = value;
            }

            $('#measurementsModal .medida-input').on('input', function() {
                validateMedida(this);
            });

            // Touch fix para medidas
            (function() {
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
                    }, {
                        passive: true
                    });
                    card.addEventListener('touchend', function(e) {
                        if (e.target.tagName === 'INPUT') return;
                        if (Date.now() - lastTouchTime < 300) focusInput(e);
                    }, {
                        passive: false
                    });
                    card.addEventListener('click', function(e) {
                        if (e.target.tagName === 'INPUT') return;
                        if (Date.now() - lastTouchTime < 500) return;
                        focusInput(e);
                    });
                });
            })();

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
                $('#modalPrice').val(selectedProduct.base_price);

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

                $('#addProductBtn').prop('disabled', false);
            }

            function resetProductModal() {
                $('#modalProductSelect').val(null).trigger('change.select2');
                $('#productPreviewName').text('-');
                $('#productPreviewSku').text('-');
                $('#productPreviewImage').attr('src', '{{ asset('img/no-image.png') }}');
                $('#modalPrice').val('');
                $('#modalQuantity').val(1);
                $('#modalEmbroideryText').val('');
                $('#modalCustomizationNotes').val('');
                $('#modalVariantSelect').empty().append('<option value="">-- Producto base --</option>');
                $('#variantGroup').hide();
                $('#addProductBtn').prop('disabled', true);
                selectedProduct = null;
            }

            $('#modalVariantSelect').on('change', function() {
                const variantPrice = $(this).find('option:selected').data('price');
                if (variantPrice) {
                    $('#modalPrice').val(variantPrice);
                } else if (selectedProduct) {
                    $('#modalPrice').val(selectedProduct.base_price);
                }
            });

            // ==========================================
            // AGREGAR PRODUCTO AL PEDIDO
            // ==========================================
            $('#addProductBtn').on('click', function() {
                if (!selectedProduct) return;

                const variantId = $('#modalVariantSelect').val() || null;
                const variantOption = $('#modalVariantSelect option:selected');
                const newQuantity = parseInt($('#modalQuantity').val()) || 1;

                // Buscar si ya existe el mismo producto/variante en la lista
                const existingItem = orderItems.find(item =>
                    item.product_id === selectedProduct.id &&
                    item.product_variant_id === variantId
                );

                if (existingItem) {
                    // Ya existe: actualizar cantidad sumando la nueva
                    existingItem.quantity += newQuantity;

                    // Actualizar precio si cambió
                    const newPrice = parseFloat($('#modalPrice').val()) || 0;
                    if (newPrice > 0) {
                        existingItem.unit_price = newPrice;
                    }

                    // Actualizar texto de bordado si se proporcionó uno nuevo
                    const newEmbroideryText = $('#modalEmbroideryText').val().trim();
                    if (newEmbroideryText) {
                        existingItem.embroidery_text = newEmbroideryText;
                    }

                    // Actualizar notas de personalización si se proporcionaron nuevas
                    const newCustomizationNotes = $('#modalCustomizationNotes').val().trim();
                    if (newCustomizationNotes) {
                        existingItem.customization_notes = newCustomizationNotes;
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
                        unit_price: parseFloat($('#modalPrice').val()) || 0,
                        embroidery_text: $('#modalEmbroideryText').val().trim(),
                        customization_notes: $('#modalCustomizationNotes').val().trim(),
                        lead_time: selectedProduct.lead_time || 0
                    };

                    orderItems.push(item);
                    itemIndex++;
                }

                productLeadTimes[selectedProduct.id] = selectedProduct.lead_time || 0;

                renderItemsTable();
                updateHiddenInputs();
                calculateTotals();
                calculateMinimumDate();

                $('#addProductModal').modal('hide');
                resetProductModal();
            });

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
                    const variantText = item.variant_display ?
                        `<br><small class="text-muted">${item.variant_display}</small>` : '';
                    const embroideryBadge = item.embroidery_text ?
                        `<br><span class="badge badge-info" title="Texto"><i class="fas fa-pen-fancy mr-1"></i>${item.embroidery_text}</span>` :
                        '';

                    $tbody.append(`
                        <tr data-index="${item.index}">
                            <td><img src="${item.image_url || '{{ asset('img/no-image.png') }}'}" class="product-image-thumb" onerror="this.src='{{ asset('img/no-image.png') }}'"></td>
                            <td><strong>${item.product_name}</strong>${variantText}${embroideryBadge}</td>
                            <td><input type="number" class="form-control form-control-sm item-qty" value="${item.quantity}" min="1" max="999" data-index="${item.index}"></td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control item-price" value="${item.unit_price}" step="0.01" min="0" data-index="${item.index}">
                                </div>
                            </td>
                            <td class="font-weight-bold text-success">$${subtotal.toFixed(2)}</td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${item.index}"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    `);
                });
            }

            $(document).on('input', '.item-qty, .item-price', function() {
                const index = $(this).data('index');
                const item = orderItems.find(i => i.index === index);
                if (!item) return;

                if ($(this).hasClass('item-qty')) {
                    item.quantity = parseInt($(this).val()) || 1;
                } else {
                    item.unit_price = parseFloat($(this).val()) || 0;
                }

                updateHiddenInputs();
                calculateTotals();
                renderItemsTable();
            });

            $(document).on('click', '.remove-item-btn', function() {
                orderItems = orderItems.filter(i => i.index !== $(this).data('index'));
                renderItemsTable();
                updateHiddenInputs();
                calculateTotals();
                calculateMinimumDate();
            });

            // ==========================================
            // HIDDEN INPUTS
            // ==========================================
            function updateHiddenInputs() {
                const $container = $('#hiddenItemsContainer');
                $container.empty();

                orderItems.forEach((item, idx) => {
                    $container.append(`
                        <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.product_variant_id || ''}">
                        <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                        <input type="hidden" name="items[${idx}][embroidery_text]" value="${item.embroidery_text || ''}">
                        <input type="hidden" name="items[${idx}][customization_notes]" value="${item.customization_notes || ''}">
                    `);
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
            // MODALES - FOCUS Y RESET
            // ==========================================
            $('#addProductModal').on('show.bs.modal', function() {
                resetProductModal();
            });
            $('#addProductModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#modalProductSelect').select2('open');
                    setTimeout(function() {
                        document.querySelector('.select2-search__field')?.focus();
                    }, 50);
                }, 150);
            });

            $('#quickClientModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#quickClientNombre').focus();
                }, 150);
            });

            $('#measurementsModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#measure_busto').focus();
                }, 150);
            });

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

                // 3. Validar método de pago
                if (!$('#paymentMethod').val()) {
                    errors.push('<li><i class="fas fa-dollar-sign mr-1"></i> Debe seleccionar un método de pago</li>');
                }

                // 4. Validar fecha prometida
                if (!$('#promisedDate').val()) {
                    errors.push('<li><i class="fas fa-calendar mr-1"></i> Debe indicar la fecha de entrega prometida</li>');
                }

                // 5. Validar que fecha prometida sea mayor o igual a la fecha mínima
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
        });
    </script>
@stop
