@extends('adminlte::page')

@section('title', isset($isEdit) ? 'Editar Pedido' : (isset($relatedOrder) ? 'Post-Venta de ' .
    $relatedOrder->order_number : 'Nuevo Pedido'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        @if (isset($relatedOrder))
            <h1>
                <i class="fas fa-redo mr-2" style="color: #6f42c1;"></i>
                Nuevo Pedido Post-Venta
                <small class="text-muted" style="font-size: 0.6em;">de {{ $relatedOrder->order_number }}</small>
            </h1>
        @else
            <h1><i class="fas fa-clipboard-list mr-2"></i>
                {{ isset($isEdit) ? 'Editar Pedido #' . $order->order_number : 'Nuevo Pedido' }}</h1>
        @endif
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary mr-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            @if (isset($relatedOrder))
                <button type="submit" form="orderForm" class="btn shadow-sm"
                    style="background: linear-gradient(135deg, #6f42c1 0%, #8969c7 100%); color: white; border: none;">
                    <i class="fas fa-redo mr-2"></i> Crear Pedido Post-Venta
                </button>
            @else
                <button type="submit" form="orderForm" class="btn btn-success shadow-sm">
                    <i class="fas fa-save mr-2"></i> Crear Pedido
                </button>
            @endif
        </div>
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
            min-height: 280px;
            max-height: 400px;
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
        /* === RESUMEN INTEGRADO EN FOOTER DE PRODUCTOS ===== */
        /* ================================================== */
        .resumen-footer {
            padding: 0.75rem 1rem;
        }

        .resumen-footer .resumen-totals {
            background: transparent;
            padding: 0;
        }

        .resumen-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.2rem 0;
            font-size: 1rem;
        }

        .resumen-row.total-final {
            border-top: 2px solid #212529;
            margin-top: 0.4rem;
            padding-top: 0.5rem;
            font-size: 1.1rem;
        }

        /* IVA row en resumen */
        .iva-row-resumen {
            padding: 0.3rem 0;
            border-top: 1px dashed #dee2e6;
            border-bottom: 1px dashed #dee2e6;
            margin: 0.2rem 0;
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

        /* ================================================== */
        /* === PWA / WEB APP OPTIMIZATIONS ================== */
        /* ================================================== */

        /* Touch-friendly interactions */
        .btn,
        .form-control,
        .custom-control-label,
        .cliente-selector-btn,
        .nav-link {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        /* Prevent text selection on interactive elements */
        .btn,
        .badge,
        .card-header {
            user-select: none;
            -webkit-user-select: none;
        }

        /* Smooth scrolling for iOS */
        .products-scroll-container,
        .modal-body {
            -webkit-overflow-scrolling: touch;
        }

        /* Safe area insets for notched devices */
        @supports (padding: max(0px)) {
            .content-header {
                padding-left: max(1rem, env(safe-area-inset-left));
                padding-right: max(1rem, env(safe-area-inset-right));
            }

            .card-erp {
                margin-left: max(0px, env(safe-area-inset-left));
                margin-right: max(0px, env(safe-area-inset-right));
            }
        }

        /* ================================================== */
        /* === DESKTOP (992px+) ============================= */
        /* ================================================== */
        @media (min-width: 992px) {
            .main-column {
                padding-right: 0.5rem;
            }

            .sidebar-column {
                padding-left: 0.5rem;
            }

            /* Altura fija para alinear visualmente ambas columnas */
            .products-scroll-container {
                min-height: 320px;
            }
        }

        /* ================================================== */
        /* === TABLET (768px - 991px) ======================= */
        /* ================================================== */
        @media (max-width: 991px) {
            .erp-row {
                display: flex;
                flex-direction: column;
            }

            /* Orden móvil: 1.Cliente → 2.Productos → 3.Pago/Prioridad → 4.Notas */
            .order-mobile-1 {
                order: 1;
            }

            .order-mobile-2 {
                order: 2;
            }

            .order-mobile-3 {
                order: 3;
            }

            .order-mobile-4 {
                order: 4;
            }

            .order-mobile-5 {
                order: 5;
            }

            .order-mobile-6 {
                order: 6;
            }

            .order-mobile-7 {
                order: 7;
            }

            /* Las columnas deben ser hijos directos para que order funcione */
            .main-column,
            .sidebar-column {
                display: contents;
            }

            .products-scroll-container {
                min-height: 200px;
                max-height: 300px;
            }

            /* Cards de Pago y Prioridad en columna */
            .row>.col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }

            /* Ajustar resumen footer */
            .resumen-footer {
                padding: 0.5rem 0.75rem;
            }

            .resumen-row {
                font-size: 0.95rem;
            }
        }

        /* ================================================== */
        /* === MOBILE (< 768px) ============================= */
        /* ================================================== */
        @media (max-width: 767px) {

            /* Header compacto */
            .content-header h1 {
                font-size: 1.1rem;
            }

            .content-header .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }

            /* Cards más compactas */
            .card-erp {
                margin-bottom: 0.75rem;
            }

            .card-erp .card-header {
                padding: 0.5rem 0.75rem;
            }

            .card-erp .card-header h5 {
                font-size: 0.95rem;
            }

            .card-body {
                padding: 0.75rem;
            }

            /* Tabla de productos responsive */
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
                width: 50px;
                order: 1;
            }

            .product-table tbody tr td:nth-child(2) {
                width: calc(100% - 90px);
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
                text-align: center;
                font-size: 0.85rem;
            }

            #noItemsRow td {
                width: 100% !important;
                display: block !important;
            }

            /* Header de productos responsive */
            .productos-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 0.5rem;
            }

            .productos-header h5 {
                text-align: center;
                margin-bottom: 0;
            }

            .productos-header .btn {
                width: 100%;
            }

            /* Resumen compacto */
            .resumen-footer {
                padding: 0.5rem;
            }

            .resumen-row {
                font-size: 0.9rem;
                padding: 0.15rem 0;
            }

            .resumen-row.total-final {
                font-size: 1rem;
                margin-top: 0.3rem;
                padding-top: 0.4rem;
            }

            /* Inputs más grandes para touch */
            .form-control,
            .form-control-sm {
                min-height: 44px;
                font-size: 16px !important;
                /* Previene zoom en iOS */
            }

            select.form-control {
                min-height: 44px;
            }

            /* Botones touch-friendly */
            .btn {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-sm {
                min-height: 38px;
            }

            /* Cliente selector más grande */
            .cliente-selector-btn {
                min-height: 60px;
                padding: 0.5rem 0.75rem;
            }

            /* Checkboxes más grandes */
            .custom-control {
                min-height: 44px;
                display: flex;
                align-items: center;
            }

            .custom-control-label::before,
            .custom-control-label::after {
                width: 1.25rem;
                height: 1.25rem;
                top: 50%;
                transform: translateY(-50%);
            }

            /* Products scroll menor en móvil */
            .products-scroll-container {
                min-height: 150px;
                max-height: 250px;
            }
        }

        /* ================================================== */
        /* === EXTRA SMALL MOBILE (< 480px) ================= */
        /* ================================================== */
        @media (max-width: 480px) {
            .content-header h1 {
                font-size: 1rem;
            }

            .content-header .d-flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .content-header .btn {
                flex: 1;
                min-width: 120px;
            }

            /* Ocultar texto en botones, solo iconos */
            .productos-header .btn span {
                display: none;
            }

            /* Resumen ultra compacto */
            .resumen-row {
                font-size: 0.85rem;
            }

            .resumen-row .input-group {
                width: 80px !important;
            }

            /* Card headers más compactos */
            .card-erp .card-header h5 {
                font-size: 0.9rem;
            }

            .card-erp .card-header h5 i {
                margin-right: 0.25rem !important;
            }
        }

        /* ================================================== */
        /* === LANDSCAPE MOBILE ============================= */
        /* ================================================== */
        @media (max-height: 500px) and (orientation: landscape) {
            .products-scroll-container {
                min-height: 120px;
                max-height: 180px;
            }

            .card-erp .card-body {
                min-height: 60px;
            }

            .modal-body {
                max-height: 60vh;
                overflow-y: auto;
            }
        }

        /* ================================================== */
        /* === HIGH DPI / RETINA DISPLAYS =================== */
        /* ================================================== */
        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            .card-erp {
                border-width: 0.5px;
            }

            .table td,
            .table th {
                border-width: 0.5px;
            }
        }

        /* ================================================== */
        /* === MODALS RESPONSIVE ============================ */
        /* ================================================== */
        @media (max-width: 767px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-dialog-centered {
                min-height: calc(100% - 1rem);
            }

            .modal-xl,
            .modal-lg {
                max-width: calc(100% - 1rem);
            }

            .modal-content {
                border-radius: 12px;
            }

            .modal-header {
                padding: 0.75rem 1rem;
            }

            .modal-body {
                padding: 0.75rem;
                max-height: 70vh;
                overflow-y: auto;
                overscroll-behavior: contain;
            }

            .modal-footer {
                padding: 0.75rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .modal-footer .btn {
                flex: 1;
                min-width: 100px;
            }

            /* Client search modal */
            .client-results-container {
                max-height: 50vh;
            }

            .client-result-item {
                padding: 1rem;
            }

            /* Product modal */
            #productModal .modal-body {
                padding: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .modal-header h5 {
                font-size: 1rem;
            }

            .modal-footer .btn {
                width: 100%;
                margin: 0;
            }
        }

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

        /* D6: SCROLL SEGURO - Modal nunca depende del body */
        #addProductModal .modal-body,
        #quickClientModal .modal-body,
        #measurementsModal .modal-body,
        #clientSearchModal .modal-body {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            /* Prevenir scroll chaining al body cuando el modal llega al final */
            overscroll-behavior: contain;
        }

        #addProductModal .modal-footer,
        #quickClientModal .modal-footer {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #dee2e6;
            z-index: 10;
        }

        /* Selector de intención */
        .intent-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .intent-btn:active {
            transform: translateY(-1px);
        }

        /* Responsive para REQUISITOS DEL PRODUCTO */
        @media (max-width: 768px) {
            #measurementsSection #btnOpenMeasurementsModal {
                width: 100% !important;
                margin-top: 8px !important;
            }

            #systemClientDivider span {
                font-size: 10px !important;
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
    <form action="{{ isset($isEdit) ? route('admin.orders.update', $order) : route('admin.orders.store') }}" method="POST"
        id="orderForm">
        @csrf
        @if (isset($isEdit))
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

        {{-- ============================================== --}}
        {{-- BLOQUE POST-VENTA: Pedido relacionado         --}}
        {{-- UX REFINADO: Explicación clara del flujo      --}}
        {{-- ============================================== --}}
        @if (isset($relatedOrder))
            <input type="hidden" name="related_order_id" value="{{ $relatedOrder->id }}">
            <div class="alert mb-3"
                style="background: linear-gradient(135deg, #6f42c1 0%, #8969c7 100%); color: white; border: none; border-radius: 8px;">
                <div class="d-flex align-items-start">
                    <div class="mr-3 mt-1">
                        <i class="fas fa-redo fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1 font-weight-bold">
                            <i class="fas fa-link mr-1"></i> Nuevo Pedido Post-Venta
                        </h5>
                        <p class="mb-1" style="font-size: 14px;">
                            Relacionado con <strong>{{ $relatedOrder->order_number }}</strong>
                            <span class="badge badge-light text-dark ml-1">{{ $relatedOrder->status_label }}</span>
                        </p>
                        {{-- MICROCOPY UX: Explicación clara --}}
                        <div class="mt-2 p-2 rounded" style="background: rgba(255,255,255,0.15); font-size: 13px;">
                            <div class="mb-1">
                                <i class="fas fa-check-circle mr-1"></i>
                                <strong>El cliente ya está seleccionado</strong> — heredado del pedido original
                            </div>
                            <div class="mb-1">
                                <i class="fas fa-edit mr-1"></i>
                                <strong>Los productos son nuevos</strong> — agrega lo que el cliente necesita ahora
                            </div>
                            <div>
                                <i class="fas fa-shield-alt mr-1"></i>
                                <strong>El pedido original no se modifica</strong> — este es completamente independiente
                            </div>
                        </div>
                    </div>
                    <div class="ml-2">
                        <a href="{{ route('admin.orders.show', $relatedOrder) }}" class="btn btn-sm btn-light"
                            target="_blank" title="Ver pedido original">
                            <i class="fas fa-external-link-alt"></i> Ver Original
                        </a>
                    </div>
                </div>
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
                        {{-- POST-VENTA: Cliente prellenado y bloqueado --}}
                        @if (isset($relatedOrder))
                            <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $relatedOrder->cliente_id }}"
                                required>

                            {{-- Display bloqueado con estilo visual claro --}}
                            <div class="cliente-selector-btn has-client"
                                style="cursor: default; border-color: #6f42c1; background: #f8f5ff;">
                                <div class="cliente-info">
                                    <span class="cliente-nombre">
                                        <i class="fas fa-lock mr-1" style="color: #6f42c1; font-size: 12px;"></i>
                                        {{ $relatedOrder->cliente->nombre }} {{ $relatedOrder->cliente->apellidos }}
                                    </span>
                                    <span class="cliente-telefono">
                                        <i
                                            class="fas fa-phone mr-1"></i>{{ $relatedOrder->cliente->telefono ?? 'Sin teléfono' }}
                                    </span>
                                </div>
                                <span class="badge" style="background: #6f42c1; color: white; font-size: 11px;">
                                    <i class="fas fa-redo mr-1"></i> Post-venta
                                </span>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 12px;">
                                <i class="fas fa-info-circle mr-1" style="color: #6f42c1;"></i>
                                Cliente heredado del pedido original. No se puede cambiar.
                            </small>
                        @else
                            {{-- ================================================ --}}
                            {{-- SWITCH: PRODUCCIÓN PARA STOCK + BOTÓN CLIENTE    --}}
                            {{-- ================================================ --}}
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="custom-control custom-switch" id="stockSwitchContainer">
                                    <input type="checkbox" class="custom-control-input" id="forStockSwitch" name="for_stock"
                                        value="1" {{ old('for_stock') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="forStockSwitch">
                                        <i class="fas fa-boxes mr-1 text-info"></i> Producción para stock
                                        <small class="text-muted d-block" style="font-weight: normal; font-size: 11px;">
                                            Sin cliente — para inventario de productos terminados
                                        </small>
                                    </label>
                                </div>
                                <div id="quickClientBtnContainer">
                                    <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal"
                                        data-target="#quickClientModal" id="btnQuickClient">
                                        <i class="fas fa-user-plus mr-1"></i> Cliente Rápido
                                    </button>
                                </div>
                            </div>

                            {{-- Modo normal: selector de cliente --}}
                            <div id="clienteSelectorSection">
                                <input type="hidden" name="cliente_id" id="cliente_id"
                                    value="{{ old('cliente_id') }}">
                                @error('cliente_id')
                                    <div class="alert alert-danger py-1 mb-2">{{ $message }}</div>
                                @enderror

                                <button type="button" class="cliente-selector-btn" id="btnSelectClient"
                                    data-toggle="modal" data-target="#clientSearchModal">
                                    <div class="cliente-info" id="clienteDisplay">
                                        <span class="placeholder-text"><i class="fas fa-search mr-1"></i> Buscar
                                            cliente...</span>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            </div>

                            {{-- Display cuando está en modo STOCK --}}
                            <div id="stockModeDisplay" style="display: none;">
                                <div class="alert alert-info mb-0 py-2" style="border-left: 4px solid #17a2b8;">
                                    <i class="fas fa-boxes mr-2"></i>
                                    <strong>Modo Producción para Stock</strong>
                                    <p class="mb-0 mt-1" style="font-size: 12px;">
                                        Este pedido se guardará sin cliente y producirá para inventario.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 3. PAGO Y 4. FECHA DE ENTREGA - EN LA MISMA LÍNEA --}}
                <div class="row">
                    {{-- 3. PAGO --}}
                    <div class="col-md-6">
                        <div class="card card-erp order-mobile-3">
                            <div class="card-header py-2" style="background: #343a40; color: white;">
                                <h5 class="mb-0"><i class="fas fa-dollar-sign mr-2"></i> 3. Pago</h5>
                            </div>
                            <div class="card-body payment-section">
                                {{-- POST-VENTA: Microcopy indicando que es editable --}}
                                @if (isset($relatedOrder))
                                    <div class="mb-2 py-1 px-2 rounded"
                                        style="background: #e8f5e9; font-size: 12px; color: #2e7d32;">
                                        <i class="fas fa-edit mr-1"></i>
                                        Configura el pago para este nuevo pedido
                                    </div>
                                @endif
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold mb-1">Método de Pago <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method" id="paymentMethod" class="form-control form-control-sm"
                                        required>
                                        <option value="">-- Seleccionar --</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>
                                            Efectivo
                                        </option>
                                        <option value="transfer"
                                            {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>
                                            Transferencia</option>
                                        <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>
                                            Tarjeta
                                        </option>
                                        <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>
                                            Otro
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group mb-2" id="payFullGroup" style="display: none;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="payFull"
                                            name="pay_full" value="1" {{ old('pay_full') ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-success" for="payFull">
                                            <i class="fas fa-check-circle mr-1"></i> Pagar Total
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group mb-0" id="anticipoGroup" style="display: none;">
                                    <label class="mb-1">Anticipo</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" name="initial_payment" id="initialPayment"
                                            class="form-control" value="{{ old('initial_payment') }}" min="0"
                                            step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. PRIORIDAD DEL PEDIDO --}}
                    <div class="col-md-6">
                        <div class="card card-erp order-mobile-4">
                            <div class="card-header py-2" style="background: #343a40; color: white;">
                                <h5 class="mb-0"><i class="fas fa-clock mr-2"></i> 4. Prioridad del Pedido</h5>
                            </div>
                            <div class="card-body">
                                {{-- POST-VENTA: Microcopy indicando que es editable --}}
                                @if (isset($relatedOrder))
                                    <div class="mb-2 py-1 px-2 rounded"
                                        style="background: #e8f5e9; font-size: 12px; color: #2e7d32;">
                                        <i class="fas fa-edit mr-1"></i>
                                        Define la urgencia y fecha para este pedido
                                    </div>
                                @endif
                                <div class="form-group mb-2">
                                    <label class="font-weight-bold mb-1">Nivel de Urgencia</label>
                                    <select name="urgency_level" id="urgencyLevel" class="form-control form-control-sm">
                                        @foreach ($urgencyLevels as $level)
                                            <option value="{{ $level->slug }}"
                                                data-time="{{ $level->time_percentage }}"
                                                data-multiplier="{{ $level->price_multiplier }}"
                                                data-color="{{ $level->color }}"
                                                {{ old('urgency_level', $order->urgency_level ?? 'normal') == $level->slug ? 'selected' : '' }}>
                                                {{ $level->name }} ({{ $level->time_percentage }}% tiempo)
                                            </option>
                                        @endforeach
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

                                {{-- FEEDBACK VISUAL DE CAPACIDAD SEMANAL --}}
                                @if (isset($capacityInfo) && $capacityInfo)
                                    <div id="capacityFeedback" class="mt-2">
                                        @if ($capacityInfo['is_full'])
                                            <div class="text-danger" style="font-size: 14px;">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                <strong>{{ $capacityInfo['week_label'] }}</strong> a capacidad máxima.
                                                El sistema asignará la siguiente semana disponible.
                                            </div>
                                        @elseif($capacityInfo['is_high_load'])
                                            <div class="text-warning" style="font-size: 14px;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                <strong>{{ $capacityInfo['week_label'] }}</strong>
                                                &middot; {{ $capacityInfo['used'] }}/{{ $capacityInfo['max'] }} pedidos
                                                <span class="text-muted">(alta carga)</span>
                                            </div>
                                        @else
                                            <div class="text-dark" style="font-size: 14px;">
                                                <i class="fas fa-calendar-check mr-1 text-info"></i>
                                                <strong>{{ $capacityInfo['week_label'] }}</strong>
                                                &middot; {{ $capacityInfo['used'] }}/{{ $capacityInfo['max'] }} pedidos
                                                asignados
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>{{-- Cierre de row de Pago y Fecha de Entrega --}}

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
                            <span id="itemsCounter" class="badge badge-light items-counter ml-2"
                                style="display:none;">0</span>
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" id="btnAddProduct">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                    {{-- POST-VENTA: Microcopy indicando que productos son nuevos --}}
                    @if (isset($relatedOrder))
                        <div class="px-3 py-2 border-bottom"
                            style="background: #fff8e1; font-size: 12px; color: #f57c00;">
                            <i class="fas fa-lightbulb mr-1"></i>
                            <strong>Agrega los productos nuevos</strong> que el cliente necesita.
                            Los productos del pedido original NO se copian.
                        </div>
                    @endif
                    <div class="card-body p-0">
                        <div class="table-responsive products-scroll-container">
                            <table class="table table-hover product-table mb-0" id="itemsTable"
                                style="table-layout: fixed;">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th style="width: 70px; text-align: center;">Foto</th>
                                        <th style="width: auto;">Producto</th>
                                        <th style="width: 75px; text-align: center;">Cant.</th>
                                        <th style="width: 85px; text-align: center;">Días Prod.</th>
                                        <th style="width: 110px; text-align: right;">Subtotal</th>
                                        <th style="width: 85px; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <tr id="noItemsRow">
                                        <td colspan="6" class="text-center text-muted" style="height: 280px; vertical-align: middle;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            Click en "Agregar" para añadir productos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- FOOTER: Notas (izq) + Resumen (der) --}}
                    <div class="card-footer resumen-footer" style="background: #f8f9fa; border-top: 2px solid #dee2e6;">
                        <div class="row">
                            {{-- COLUMNA IZQUIERDA: NOTAS --}}
                            <div class="col-md-6 pr-md-3">
                                <label class="font-weight-bold mb-1" style="font-size: 0.9rem;">
                                    <i class="fas fa-sticky-note mr-1"></i> Notas
                                </label>
                                <textarea name="notes" class="form-control form-control-sm" rows="4" maxlength="2000"
                                    placeholder="Observaciones generales del pedido...">{{ old('notes') }}</textarea>
                            </div>
                            {{-- COLUMNA DERECHA: RESUMEN --}}
                            <div class="col-md-6 pl-md-3">
                                <div class="resumen-totals">
                                    <div class="resumen-row">
                                        <span>Subtotal:</span>
                                        <strong id="subtotalDisplay">$0.00</strong>
                                    </div>
                                    <div class="resumen-row">
                                        <span>Descuento:</span>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <div class="input-group-prepend"><span class="input-group-text py-0 px-2">$</span></div>
                                            <input type="number" name="discount" id="discount" class="form-control py-0"
                                                value="{{ old('discount', 0) }}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="resumen-row iva-row-resumen">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="requiresInvoice"
                                                name="requires_invoice" value="1"
                                                {{ old('requires_invoice') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="requiresInvoice">
                                                IVA 16% <small class="text-muted">(Factura)</small>
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
                    </div>
                </div>

            </div>
        </div>

        {{-- INDICADOR DE ESTADO OPERATIVO (READY/PENDING) --}}
        @include('admin.orders._order-readiness')

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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-box mr-2"></i> Agregar Producto</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- D2: SELECTOR DE INTENCIÓN (Solo visible en post-venta)      --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    <div id="modalIntentSelector" style="display: none;">
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <i class="fas fa-hand-pointer fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-dark">¿Qué deseas agregar al pedido?</h5>
                                <p class="text-muted mb-0">Selecciona una opción para continuar</p>
                            </div>
                            <div class="row justify-content-center">
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <button type="button" class="btn btn-lg btn-block py-4 intent-btn"
                                        id="btnIntentProduct"
                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;">
                                        <i class="fas fa-box fa-2x mb-2 d-block"></i>
                                        <strong style="font-size: 16px;">Producto</strong>
                                        <small class="d-block mt-1" style="opacity: 0.9;">Con o sin extras</small>
                                    </button>
                                </div>
                                <div class="col-md-5">
                                    <button type="button" class="btn btn-lg btn-block py-4 intent-btn"
                                        id="btnIntentExtrasOnly"
                                        style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;">
                                        <i class="fas fa-plus-circle fa-2x mb-2 d-block"></i>
                                        <strong style="font-size: 16px;">Solo Extras</strong>
                                        <small class="d-block mt-1" style="opacity: 0.9;">Servicios adicionales</small>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 text-muted" style="font-size: 12px;">
                                <i class="fas fa-info-circle mr-1"></i>
                                Pedido post-venta: puede agregar productos o servicios adicionales
                            </div>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- SECCIÓN: AGREGAR PRODUCTO (flujo normal)                    --}}
                    {{-- Layout: Split View (Col Izq fija + Col Der con scroll)      --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    <div id="modalProductSection">
                        <div class="row" style="min-height: 480px;">
                            {{-- ══════════════════════════════════════════════════════════════════ --}}
                            {{-- COLUMNA IZQUIERDA: Contexto Visual (1/3, Fija)                    --}}
                            {{-- ══════════════════════════════════════════════════════════════════ --}}
                            <div class="col-md-4 d-flex flex-column"
                                style="background: #f8f9fa; border-right: 1px solid #dee2e6;">
                                <div class="p-3 d-flex flex-column h-100">
                                    {{-- Imagen del Producto --}}
                                    <div class="text-center mb-3">
                                        <img id="productPreviewImage" src="{{ asset('img/no-image.png') }}"
                                            class="img-fluid rounded" style="max-height: 150px;">
                                    </div>

                                    {{-- Nombre (MAYÚSCULAS), Categoría y SKU --}}
                                    <div id="productPreviewName" class="font-weight-bold text-center text-uppercase"
                                        style="font-size: 1.1rem; line-height: 1.2;">-</div>
                                    <div id="productPreviewCategory" class="text-muted small text-center mb-1">-</div>
                                    <div id="productPreviewSku" class="text-center">
                                        <span class="badge badge-secondary" style="font-size: 0.75rem;">-</span>
                                    </div>

                                    {{-- Resumen de Precios (mt-auto = se pega abajo) --}}
                                    <div id="priceComparisonContainer"
                                        style="display: none; background: #fff; font-size: 0.85rem; border: 1px solid #e9ecef;">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Base:</span>
                                            <span id="modalBasePriceDisplay">$0.00</span>
                                        </div>
                                        <div id="extrasAdditionRow" class="d-flex justify-content-between text-info"
                                            style="display: none;">
                                            <span>+ Extras:</span>
                                            <span id="modalExtrasDisplay">$0.00</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between font-weight-bold">
                                            <span>TOTAL:</span>
                                            <span id="modalFinalPriceDisplay" class="text-success">$0.00</span>
                                        </div>
                                    </div>

                                    {{-- Alerta de precio modificado 
                                    <div id="priceModifiedAlert" class="alert alert-info py-1 px-2 mt-2 mb-0"
                                        style="display: none; font-size: 0.75rem;">
                                        <i class="fas fa-info-circle mr-1"></i> Precio ajustado manualmente
                                    </div> --}}
                                </div>
                            </div>

                            {{-- ══════════════════════════════════════════════════════════════════ --}}
                            {{-- COLUMNA DERECHA: Configuración (2/3, Con scroll)                  --}}
                            {{-- ══════════════════════════════════════════════════════════════════ --}}
                            <div class="col-md-8 p-0">
                                <div class="p-3" style="max-height: 480px; overflow-y: auto;">

                                    {{-- Buscar Producto --}}
                                    <div class="form-group">
                                        <label class="font-weight-bold">Buscar Producto</label>
                                        <select id="modalProductSelect" class="form-control" style="width: 100%;">
                                            <option value="">Escriba para buscar...</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        {{-- Variante (condicional) --}}
                                        <div class="col-6">
                                            <div class="form-group" id="variantGroup">
                                                <label class="font-weight-bold">Variante</label>
                                                <select id="modalVariantSelect" class="form-control">
                                                    <option value="">-- Seleccionar el producto --</option>
                                                </select>
                                            </div>

                                        </div>

                                        {{-- Cantidad y Precio --}}

                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Cantidad *</label>
                                                <input type="number" id="modalQuantity" class="form-control"
                                                    value="1" min="1" max="999">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Precio Unit. *</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span
                                                            class="input-group-text">$</span></div>
                                                    <input type="number" id="modalPrice" class="form-control"
                                                        step="0.01" min="0">
                                                </div>
                                                {{-- Indicadores debajo del precio --}}
                                                <div id="priceIndicators" class="mt-1" style="font-size: 13px;">
                                                    <span id="extrasIndicator" class="text-success font-weight-bold"
                                                        style="display: none;"></span>
                                                    <span id="estimatedPriceIndicator"
                                                        style="display: none; color: #fd7e14; font-weight: 500;">
                                                        <i class="fas fa-info-circle"></i> Precio estimado
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ═══════════════════════════════════════════════════════════ --}}
                                    {{-- BLOQUE SISTÉMICO: PRODUCTO A MEDIDA                          --}}
                                    {{-- Contiene: Checkbox + Botón Capturar Medidas                  --}}
                                    {{-- ═══════════════════════════════════════════════════════════ --}}
                                    <div class="form-group mb-3" id="measurementsSection" style="display: none;">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input"
                                                id="chkRequiresMeasurements">
                                            <label class="custom-control-label" for="chkRequiresMeasurements"
                                                style="font-size: 15px; color: #495057;">
                                                <i class="fas fa-ruler-combined mr-1" style="color: #0d47a1;"></i>
                                                Requiere medidas del cliente
                                            </label>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            id="btnOpenMeasurementsModal" style="font-weight: 500; padding: 8px 16px;"
                                            disabled>
                                            <i class="fas fa-tape mr-1"></i>
                                            <span id="btnMeasurementsText">Capturar Medidas</span>
                                        </button>

                                        {{-- Resumen de medidas capturadas --}}
                                        <div id="measurementsSummaryBody" style="display: none;"
                                            class="mt-3 pt-2 border-top">
                                            <div class="row" id="measurementsSummaryContent" style="font-size: 14px;">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ═══════════════════════════════════════════════════════════ --}}
                                    {{-- SECCIÓN PERSONALIZACIÓN (NO BLOQUE AZUL - FORMULARIO NORMAL) --}}
                                    {{-- Contiene: Checkbox requiere diseño + Input texto a bordar   --}}
                                    {{-- ═══════════════════════════════════════════════════════════ --}}
                                    <div class="form-group mb-3" id="personalizationSection">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input" id="chkRequiresDesign">
                                            <label class="custom-control-label" for="chkRequiresDesign"
                                                style="font-size: 15px; color: #495057;">
                                                <i class="fas fa-palette mr-1" style="color: #7b1fa2;"></i> Requiere
                                                personalización (texto, logo o diseño)
                                            </label>
                                        </div>
                                        <div id="embroideryTextContainer">
                                            <label class="font-weight-bold mb-1" style="font-size: 14px; color: #495057;">
                                                <i class="fas fa-pen-fancy mr-1 text-info"></i> Texto a Bordar
                                            </label>
                                            <input type="text" id="modalEmbroideryText" class="form-control"
                                                maxlength="255" placeholder="Nombre, frase, iniciales..." disabled>
                                            <small id="embroideryTextHint"
                                                style="display: none; font-size: 14px; color: #495057;">
                                                El diseño se vinculará después de crear el pedido
                                            </small>
                                        </div>
                                    </div>

                                    {{-- SEPARADOR: Opciones Adicionales --}}
                                    <div id="systemClientDivider" class="my-3 text-center" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <hr class="flex-grow-1" style="border-color: #bdbdbd;">
                                            <span class="px-3"
                                                style="font-size: 14px; color: #495057; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">
                                                <i class="fas fa-cogs mr-1"></i> Opciones Adicionales
                                            </span>
                                            <hr class="flex-grow-1" style="border-color: #bdbdbd;">
                                        </div>
                                    </div>

                                    {{-- Contenedor 2 columnas: Extras | Ajuste+Notas+Subtotal --}}
                                    <div class="row">
                                        {{-- Columna Izquierda: Extras --}}
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group mb-0" id="productExtrasSection"
                                                style="display: none;">
                                                <label class="font-weight-bold mb-1">
                                                    <i class="fas fa-plus-circle mr-1 text-success"></i> Extras
                                                    <span class="ml-2 text-info font-weight-bold"
                                                        id="extrasSubtotalDisplay">+$0.00</span>
                                                </label>
                                                <div class="d-flex align-items-center mb-2">
                                                    <button type="button" class="btn btn-outline-success btn-sm"
                                                        id="btnOpenExtrasModal">
                                                        <i class="fas fa-list-ul mr-1"></i> Seleccionar Extras
                                                    </button>
                                                </div>
                                                <div id="selectedExtrasList" class="border rounded"
                                                    style="display: none; max-height: 150px; overflow-y: auto;">
                                                    {{-- Se llena dinámicamente con JS --}}
                                                </div>
                                                <small id="noExtrasSelectedMsg"
                                                    style="font-size: 14px; color: #495057;">Sin extras
                                                    seleccionados</small>
                                            </div>

                                        </div>

                                        {{-- Columna Derecha: Ajuste + Notas + Subtotal --}}
                                        <div class="col-md-6">
                                            {{-- Ajuste de precio --}}
                                            <div class="form-group mb-3">
                                                <label class="font-weight-bold mb-1">
                                                    <i class="fas fa-dollar-sign mr-1 text-warning"></i> Ajuste de precio
                                                    adicional
                                                </label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span
                                                            class="input-group-text">+$</span></div>
                                                    <input type="number" id="modalExtrasCost" class="form-control"
                                                        step="0.01" min="0" value="0">
                                                </div>
                                                <small style="font-size: 14px; color: #495057;">Ajuste manual por medidas
                                                    especiales o trabajos
                                                    adicionales</small>
                                            </div>

                                            {{-- Notas --}}
                                            <div class="form-group mb-3">
                                                <label class="font-weight-bold mb-1">
                                                    <i class="fas fa-sticky-note mr-1 text-secondary"></i> Notas /
                                                    Instrucciones
                                                </label>
                                                <textarea id="modalCustomizationNotes" class="form-control" rows="2" maxlength="1000"
                                                    placeholder="Instrucciones especiales, diseño, colores..."></textarea>
                                            </div>


                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>{{-- Fin #modalProductSection --}}

                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    {{-- D5: SECCIÓN SOLO EXTRAS (post-venta sin producto nuevo)     --}}
                    {{-- ═══════════════════════════════════════════════════════════ --}}
                    <div id="modalExtrasOnlySection" style="display: none;">
                        <div class="row">
                            <div class="col-12">
                                {{-- Botón volver a selección de intención --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-3"
                                    id="btnBackToIntent">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver
                                </button>

                                {{-- Info contextual --}}
                                <div class="alert py-2 mb-3"
                                    style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border: 1px solid #a5d6a7;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-concierge-bell mr-2"
                                            style="color: #2e7d32; font-size: 20px;"></i>
                                        <div>
                                            <strong style="color: #1b5e20;">Servicios Adicionales</strong>
                                            <div class="small" style="color: #388e3c;">
                                                Agregue servicios como empaque especial, urgencia u otros extras al pedido.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Buscador de extras --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-search mr-1 text-muted"></i> Buscar Extras
                                    </label>
                                    <input type="text" id="extrasOnlySearchInput" class="form-control"
                                        placeholder="Escriba para filtrar extras...">
                                </div>

                                {{-- Tabla de extras disponibles --}}
                                <div class="border rounded" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-hover table-sm mb-0" id="extrasOnlyTable">
                                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                            <tr>
                                                <th style="width: 50px;" class="text-center">
                                                    <input type="checkbox" id="selectAllExtrasOnly"
                                                        title="Seleccionar todos">
                                                </th>
                                                <th>Extra</th>
                                                <th style="width: 120px;" class="text-right">Precio</th>
                                            </tr>
                                        </thead>
                                        <tbody id="extrasOnlyTableBody">
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">
                                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                                    <p class="mt-2 mb-0">Cargando extras disponibles...</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Resumen de selección --}}
                                <div class="mt-3 p-3 rounded d-flex justify-content-between align-items-center"
                                    style="background: #f8f9fa;">
                                    <span><strong id="extrasOnlySelectedCount">0</strong> extras seleccionados</span>
                                    <span class="text-success font-weight-bold" style="font-size: 1.2rem;">
                                        Total: <span id="extrasOnlyTotal">$0.00</span>
                                    </span>
                                </div>

                                {{-- Notas opcionales --}}
                                <div class="form-group mt-3 mb-0">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-sticky-note mr-1 text-muted"></i> Notas (opcional)
                                    </label>
                                    <textarea id="extrasOnlyNotes" class="form-control" rows="2" maxlength="500"
                                        placeholder="Instrucciones especiales para estos extras..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>{{-- Fin #modalExtrasOnlySection --}}
                </div>
                <div class="modal-footer">
                    {{-- Subtotal del ítem --}}
                    <div class="bg-light rounded p-2 text-right" id="itemSubtotalContainer" style="display: none;">
                        <small class="text-muted d-block" style="font-weight: bold;font-size:18px;color:black"
                            id="itemSubtotalDetail"></small>
                        <span class="text-muted" style="font-weight: bold;font-size:18px;color:black">Total:</span>
                        <strong class="text-success ml-2" id="itemSubtotalDisplay" style="font-size:18px;">$0.00</strong>

                    </div>
                    {{-- aqui termina subtotal --}}
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="addProductBtn" disabled data-toggle="tooltip"
                        data-placement="top" title="">
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
                    {{-- Info del cliente --}}
                    <div class="alert py-2 mb-2" style="background: #17a2b8; border: none;">
                        <i class="fas fa-user mr-1 text-white"></i>
                        <strong class="text-white">Capturar medidas de:</strong>
                        <span id="measurementsClientName" class="text-white font-weight-bold">-</span>
                    </div>

                    {{-- Info del producto actual --}}
                    <div class="alert alert-light py-2 mb-3 border" id="measurementsProductInfo">
                        <i class="fas fa-box mr-1 text-info"></i>
                        <strong>Producto:</strong> <span id="measurementsProductName">-</span>
                    </div>

                    {{-- TABS: Capturar Nuevas | Usar Existentes (N) --}}
                    <ul class="nav nav-tabs mb-3" id="measurementsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-new-measures" data-toggle="tab"
                                href="#panel-new-measures" role="tab">
                                <i class="fas fa-plus-circle mr-1"></i> Capturar nuevas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-existing-measures" data-toggle="tab"
                                href="#panel-existing-measures" role="tab">
                                <i class="fas fa-history mr-1"></i> Usar existentes (<span
                                    id="existingMeasuresCount">0</span>)
                            </a>
                        </li>
                    </ul>

                    {{-- Tab Content --}}
                    <div class="tab-content" id="measurementsTabContent">
                        {{-- Panel: Capturar nuevas medidas con imágenes --}}
                        <div class="tab-pane fade show active" id="panel-new-measures" role="tabpanel">
                            <div class="row">
                                {{-- BUSTO --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">BUSTO</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/busto.png') }}" alt="Busto"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medBusto"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 80.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                                {{-- ALTO CINTURA --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">ALTO CINTURA</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medAltoCintura"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 40.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                                {{-- CINTURA --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">CINTURA</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/cintura.png') }}" alt="Cintura"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medCintura"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 70.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                                {{-- CADERA --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">CADERA</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/cadera.png') }}" alt="Cadera"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medCadera"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 95.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                                {{-- LARGO BLUSA --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">LARGO BLUSA</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/largo.png') }}" alt="Largo Blusa"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medLargo"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 60.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                                {{-- LARGO VESTIDO --}}
                                <div class="form-group col-md-4 col-6 text-center">
                                    <label class="medida-label">LARGO VESTIDO</label>
                                    <div class="medida-card">
                                        <img src="{{ asset('images/largo_vestido.png') }}" alt="Largo Vestido"
                                            class="img-fluid medida-img">
                                        <input type="text" id="medLargoVestido"
                                            class="form-control form-control-sm medida-input" placeholder="Ej: 120.5"
                                            maxlength="6" inputmode="decimal" oninput="validateMedidaModal(this)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Panel: Seleccionar medidas existentes del cliente --}}
                        <div class="tab-pane fade" id="panel-existing-measures" role="tabpanel">
                            <div class="text-center py-3" id="existingMeasuresLoading">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2 mb-0" style="color: #495057;">Cargando historial de medidas...</p>
                            </div>
                            <div id="existingMeasuresList" style="max-height: 350px; overflow-y: auto;">
                                {{-- Se llena dinámicamente con historial enriquecido --}}
                            </div>
                            <div class="alert alert-secondary py-3 mt-2 text-center" id="noExistingMeasuresAlert"
                                style="display: none;">
                                <i class="fas fa-inbox fa-2x mb-2 d-block" style="color: #6c757d;"></i>
                                <span style="color: #495057; font-size: 14px;">Este cliente no tiene medidas
                                    registradas en el historial.</span>
                            </div>
                        </div>
                    </div>{{-- fin tab-content --}}
                </div>
                <div class="modal-footer py-2">
                    {{-- Footer para modo editable --}}
                    <div id="measurementsEditFooter">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn text-white" id="btnConfirmMeasurements"
                            style="background: #6f42c1;">
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
        <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
            <div class="modal-content">
                <div class="modal-header py-2" style="background: #343a40; color: white;">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> Seleccionar Extras</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">
                    {{-- Buscador de extras --}}
                    <div class="p-3 border-bottom bg-light">
                        <input type="text" id="extrasSearchInput" class="form-control"
                            placeholder="Buscar extras...">
                    </div>
                    {{-- Lista de extras disponibles (cards en lugar de tabla) --}}
                    <div style="max-height: 350px; overflow-y: auto; padding: 10px;" id="extrasListContainer">
                        {{-- Se llena dinámicamente --}}
                    </div>
                    {{-- Resumen de selección --}}
                    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
                        <span style="font-size: 15px;"><strong id="extrasSelectedCount">0</strong> extras
                            seleccionados</span>
                        <span class="text-success font-weight-bold" style="font-size: 1.2rem;">
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

            // ==========================================
            // D1 + D3: CONTEXTO Y MÁQUINA DE ESTADOS DEL MODAL
            // ==========================================
            // D1: Contexto del formulario (viene del padre, NO se infiere)
            const ORDER_CONTEXT = @json(isset($relatedOrder) ? 'post_sale' : 'normal');

            // D3: Estados explícitos del modal
            const MODAL_STATES = {
                IDLE: 'idle', // Modal cerrado o recién abierto (sin selección)
                INTENT_SELECTION: 'intent_selection', // Esperando que el operador elija intención
                ADDING_PRODUCT: 'adding_product', // Agregando producto (con o sin extras)
                ADDING_EXTRAS_ONLY: 'adding_extras_only', // Solo extras (sin producto nuevo)
                EDITING: 'editing' // Editando item existente
            };

            // Estado actual del modal (D3)
            let modalState = {
                current: MODAL_STATES.IDLE,
                context: ORDER_CONTEXT, // 'normal' o 'post_sale'
                intent: null, // 'product' o 'extras_only'
                previousState: null // Para navegación hacia atrás
            };

            // D3: Función para cambiar estado del modal con logging
            function setModalState(newState, intent = null) {
                console.log(`[Modal State] ${modalState.current} → ${newState}`, intent ? `(intent: ${intent})` :
                    '');
                modalState.previousState = modalState.current;
                modalState.current = newState;
                if (intent !== null) {
                    modalState.intent = intent;
                }
                updateModalUI();
            }

            // D3: Actualizar UI según estado del modal
            function updateModalUI() {
                const $intentSelector = $('#modalIntentSelector');
                const $productSection = $('#modalProductSection');
                const $extrasOnlySection = $('#modalExtrasOnlySection');
                const $modalTitle = $('#addProductModal .modal-title');

                // Ocultar todo por defecto
                $intentSelector.hide();
                $productSection.hide();
                $extrasOnlySection.hide();

                switch (modalState.current) {
                    case MODAL_STATES.IDLE:
                    case MODAL_STATES.INTENT_SELECTION:
                        // En post-venta: mostrar selector de intención
                        // En normal: ir directo a producto
                        if (modalState.context === 'post_sale' && editingItemIndex === null) {
                            $intentSelector.show();
                            $modalTitle.html('<i class="fas fa-question-circle mr-2"></i> ¿Qué deseas agregar?');
                        } else {
                            // Normal mode o editando: ir directo a producto
                            $productSection.show();
                            $modalTitle.html('<i class="fas fa-box mr-2"></i> Agregar Producto');
                        }
                        break;

                    case MODAL_STATES.ADDING_PRODUCT:
                    case MODAL_STATES.EDITING:
                        $productSection.show();
                        $modalTitle.html(editingItemIndex !== null ?
                            '<i class="fas fa-edit mr-2"></i> Editar Producto' :
                            '<i class="fas fa-box mr-2"></i> Agregar Producto');
                        break;

                    case MODAL_STATES.ADDING_EXTRAS_ONLY:
                        $extrasOnlySection.show();
                        $modalTitle.html(
                            '<i class="fas fa-concierge-bell mr-2"></i> Agregar Servicios Adicionales');
                        break;
                }

                updateAddButtonState();
            }

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
            // SWITCH: PRODUCCIÓN PARA STOCK (sin cliente)
            // REGLA: DESHABILITAR campos, NO ocultarlos
            // ==========================================
            @if (!isset($relatedOrder) && !isset($isEdit))
                const $forStockSwitch = $('#forStockSwitch');
                const $clienteSelectorSection = $('#clienteSelectorSection');
                const $stockModeDisplay = $('#stockModeDisplay');

                // Referencias a secciones de Pago y Entrega
                const $pagoCard = $('.order-mobile-3');
                const $entregaCard = $('.order-mobile-4');

                // Función para actualizar UI según modo stock
                function updateStockModeUI() {
                    const isStockMode = $forStockSwitch.is(':checked');

                    if (isStockMode) {
                        // ══════════════════════════════════════════
                        // MODO STOCK: DESHABILITAR (no ocultar)
                        // ══════════════════════════════════════════

                        // CLIENTE: Deshabilitar visualmente (mostrar indicador)
                        $clienteSelectorSection.addClass('section-disabled');
                        $stockModeDisplay.show();

                        // Limpiar cliente seleccionado
                        $('#cliente_id').val('');
                        selectedClientData = null;
                        clientMeasurementsCache = null;
                        $('#clienteDisplay').html(
                            '<span class="placeholder-text"><i class="fas fa-search mr-1"></i> Buscar cliente...</span>'
                            );
                        $('#btnSelectClient').removeClass('has-client');

                        // PAGO: Deshabilitar sección completa
                        $pagoCard.find('.card-body').addClass('section-disabled');
                        $pagoCard.find('select, input').prop('disabled', true);
                        $('#paymentMethod').val('');
                        $('#payFull').prop('checked', false);
                        $('#initialPayment').val('');
                        $('#payFullGroup, #anticipoGroup').hide();

                        // ENTREGA: Deshabilitar sección completa
                        $entregaCard.find('.card-body').addClass('section-disabled');
                        $entregaCard.find('select, input').prop('disabled', true);

                    } else {
                        // ══════════════════════════════════════════
                        // MODO NORMAL: RE-HABILITAR TODO
                        // ══════════════════════════════════════════

                        // CLIENTE: Habilitar
                        $clienteSelectorSection.removeClass('section-disabled');
                        $stockModeDisplay.hide();

                        // PAGO: Habilitar
                        $pagoCard.find('.card-body').removeClass('section-disabled');
                        $pagoCard.find('select, input').prop('disabled', false);

                        // ENTREGA: Habilitar
                        $entregaCard.find('.card-body').removeClass('section-disabled');
                        $entregaCard.find('select, input').prop('disabled', false);
                    }
                }

                // Inicializar estado
                updateStockModeUI();

                // Handler del switch
                $forStockSwitch.on('change', function() {
                    updateStockModeUI();

                    // Feedback visual
                    if ($(this).is(':checked')) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Producción para Stock',
                            text: 'Pedido sin cliente, pago ni entrega. Se producirá para inventario.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            @endif

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
                                text: p.name.toUpperCase(),
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
                // FASE 5: Limpiar estado del producto ANTERIOR antes de asignar nuevo
                resetProductDependentState();
                selectedProduct = e.params.data.product;
                updateProductPreview();
            }).on('select2:clear select2:unselect', function(e) {
                // RESET COMPLETO: El modal debe quedar exactamente como cuando se abre
                console.log('[Select2] Clear/Unselect triggered:', e.type);
                selectedProduct = null;
                resetProductModal(true); // true = skip select2 reset (ya está cleared)
                // Volver al estado inicial del modal (selector de intención o producto según contexto)
                if (modalState.context === 'post_sale' && editingItemIndex === null) {
                    setModalState(MODAL_STATES.INTENT_SELECTION);
                } else {
                    setModalState(MODAL_STATES.ADDING_PRODUCT);
                }
            });

            function updateProductPreview() {
                if (!selectedProduct) return;

                // Nombre en MAYÚSCULAS
                $('#productPreviewName').text(selectedProduct.name ? selectedProduct.name.toUpperCase() : '-');
                // Categoría del producto
                $('#productPreviewCategory').text(selectedProduct.category_name || '-');
                // SKU como badge
                $('#productPreviewSku').html(
                    selectedProduct.sku ?
                    `<span class="badge badge-secondary" style="font-size: 0.75rem;">${selectedProduct.sku}</span>` :
                    '-'
                );
                // Imagen
                $('#productPreviewImage').attr('src', selectedProduct.image_url ||
                    '{{ asset('img/no-image.png') }}');

                // Guardar precio base pero NO establecer precio aún (se llena al seleccionar variante)
                modalBasePrice = parseFloat(selectedProduct.base_price) || 0;

                // === MOSTRAR/OCULTAR SECCIÓN DE MEDIDAS ===
                updateMeasurementsSectionVisibility();

                // === CARGAR EXTRAS DEL PRODUCTO ===
                loadProductExtras();

                const $variantSelect = $('#modalVariantSelect');
                $variantSelect.empty().append('<option value="">-- Seleccione variante --</option>');

                if (selectedProduct.variants && selectedProduct.variants.length > 0) {
                    // Producto CON variantes: precio vacío hasta seleccionar variante
                    $('#modalPrice').val('');
                    selectedProduct.variants.forEach(v => {
                        $variantSelect.append(
                            `<option value="${v.id}" data-price="${v.price}" data-sku="${v.sku}">${v.display} ($${parseFloat(v.price).toFixed(2)})</option>`
                        );
                    });
                } else {
                    // Producto SIN variantes: usar precio base directamente
                    $('#modalPrice').val(modalBasePrice.toFixed(2));
                }

                // === MOSTRAR COMPARACIÓN DE PRECIOS ===
                updatePriceComparison();

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
                        const qty = extra.quantity || 1;
                        const unitPrice = extra.unit_price || extra.price;
                        const totalPrice = extra.price || (unitPrice * qty);
                        const qtyLabel = qty > 1 ?
                            `<span class="badge badge-secondary mr-1">${qty}x</span>` : '';

                        const itemHtml = `
                            <div class="d-flex justify-content-between align-items-center px-2 py-2 border-bottom" data-extra-id="${extra.id}">
                                <span style="font-size: 14px; font-weight: 500;">${qtyLabel}${extra.name.toUpperCase()}</span>
                                <div class="d-flex align-items-center">
                                    <span class="text-success font-weight-bold mr-2" style="font-size: 14px;">+$${totalPrice.toFixed(2)}</span>
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

                // Mostrar loading
                $('#extrasListContainer').html(`
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                        <p class="mt-2 mb-0 text-muted">Cargando extras...</p>
                    </div>
                `);

                // Abrir modal inmediatamente
                $('#extrasSelectionModal').modal('show');

                // Cargar TODOS los extras activos del catálogo
                $.ajax({
                    url: `/product_extras/ajax/all-active`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Guardar todos los extras disponibles
                        selectedProduct.extras = response.extras || [];

                        if (selectedProduct.extras.length === 0) {
                            $('#extrasListContainer').html(`
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                    No hay extras disponibles en el catálogo
                                </div>
                            `);
                            return;
                        }

                        // Copiar selección actual a temporal (con cantidad)
                        tempSelectedExtras = selectedExtras.map(e => ({
                            ...e,
                            quantity: e.quantity || 1
                        }));

                        // Llenar lista de extras
                        populateExtrasList();
                    },
                    error: function() {
                        $('#extrasListContainer').html(`
                            <div class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                Error al cargar extras
                            </div>
                        `);
                    }
                });
            });

            // Llenar lista con extras del producto (con cantidad)
            function populateExtrasList() {
                const $container = $('#extrasListContainer');
                $container.empty();

                if (!selectedProduct || !selectedProduct.extras) return;

                selectedProduct.extras.forEach(extra => {
                    const selected = tempSelectedExtras.find(e => e.id === extra.id);
                    const isSelected = !!selected;
                    const qty = selected ? selected.quantity : 1;
                    const unitPrice = parseFloat(extra.price_addition) || 0;

                    const cardHtml = `
                        <div class="extra-card d-flex align-items-center p-2 mb-2 border rounded ${isSelected ? 'border-success bg-light' : ''}"
                             data-extra-id="${extra.id}" data-name="${extra.name}" data-price="${unitPrice}"
                             style="cursor: pointer;">
                            <div class="mr-3">
                                <input type="checkbox" class="extra-checkbox" style="width: 20px; height: 20px;" ${isSelected ? 'checked' : ''}>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold" style="font-size: 15px;">${extra.name.toUpperCase()}</div>
                                <div class="text-success" style="font-size: 14px;">+$${unitPrice.toFixed(2)} c/u</div>
                            </div>
                            <div class="d-flex align-items-center extra-qty-controls" style="display: ${isSelected ? 'flex' : 'none'} !important;">
                                <button type="button" class="btn btn-sm btn-outline-secondary extra-qty-minus" style="width: 32px; height: 32px;">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control form-control-sm text-center extra-qty-input mx-1"
                                       value="${qty}" min="1" max="99" style="width: 50px; font-size: 15px; font-weight: bold;">
                                <button type="button" class="btn btn-sm btn-outline-secondary extra-qty-plus" style="width: 32px; height: 32px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="ml-3 text-right" style="min-width: 80px;">
                                <span class="extra-subtotal font-weight-bold text-success" style="font-size: 16px;">
                                    ${isSelected ? '+$' + (unitPrice * qty).toFixed(2) : ''}
                                </span>
                            </div>
                        </div>
                    `;
                    $container.append(cardHtml);
                });

                updateExtrasModalSummary();
            }

            // Buscador de extras
            $('#extrasSearchInput').on('input', function() {
                const term = $(this).val().toLowerCase().trim();
                $('.extra-card').each(function() {
                    const name = $(this).data('name').toLowerCase();
                    $(this).toggle(name.includes(term));
                });
            });

            // Click en card de extra (toggle selección)
            $(document).on('click', '.extra-card', function(e) {
                // Evitar toggle si se hace click en controles de cantidad o checkbox directamente
                if ($(e.target).closest('.extra-qty-controls').length || $(e.target).is(
                    '.extra-checkbox')) {
                    return;
                }
                $(this).find('.extra-checkbox').prop('checked', !$(this).find('.extra-checkbox').prop(
                    'checked')).trigger('change');
            });

            // Checkbox de extra
            $(document).on('change', '.extra-checkbox', function() {
                const $card = $(this).closest('.extra-card');
                const extraId = $card.data('extra-id');
                const extraName = $card.data('name');
                const extraPrice = parseFloat($card.data('price')) || 0;
                const qty = parseInt($card.find('.extra-qty-input').val()) || 1;

                if ($(this).is(':checked')) {
                    $card.addClass('border-success bg-light');
                    $card.find('.extra-qty-controls').css('display', 'flex');
                    $card.find('.extra-subtotal').text('+$' + (extraPrice * qty).toFixed(2));

                    if (!tempSelectedExtras.some(e => e.id === extraId)) {
                        tempSelectedExtras.push({
                            id: extraId,
                            name: extraName,
                            price: extraPrice * qty,
                            unit_price: extraPrice,
                            quantity: qty
                        });
                    }
                } else {
                    $card.removeClass('border-success bg-light');
                    $card.find('.extra-qty-controls').css('display', 'none');
                    $card.find('.extra-subtotal').text('');
                    tempSelectedExtras = tempSelectedExtras.filter(e => e.id !== extraId);
                }

                updateExtrasModalSummary();
            });

            // Botones de cantidad +/-
            $(document).on('click', '.extra-qty-minus', function(e) {
                e.stopPropagation();
                const $input = $(this).siblings('.extra-qty-input');
                let val = parseInt($input.val()) || 1;
                if (val > 1) $input.val(val - 1).trigger('input');
            });

            $(document).on('click', '.extra-qty-plus', function(e) {
                e.stopPropagation();
                const $input = $(this).siblings('.extra-qty-input');
                let val = parseInt($input.val()) || 1;
                if (val < 99) $input.val(val + 1).trigger('input');
            });

            // Cambio en input de cantidad
            $(document).on('input', '.extra-qty-input', function(e) {
                e.stopPropagation();
                const $card = $(this).closest('.extra-card');
                const extraId = $card.data('extra-id');
                const extraPrice = parseFloat($card.data('price')) || 0;
                let qty = parseInt($(this).val()) || 1;
                if (qty < 1) qty = 1;
                if (qty > 99) qty = 99;
                $(this).val(qty);

                // Actualizar subtotal visual
                $card.find('.extra-subtotal').text('+$' + (extraPrice * qty).toFixed(2));

                // Actualizar en tempSelectedExtras
                const idx = tempSelectedExtras.findIndex(e => e.id === extraId);
                if (idx !== -1) {
                    tempSelectedExtras[idx].quantity = qty;
                    tempSelectedExtras[idx].price = extraPrice * qty;
                }

                updateExtrasModalSummary();
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
                selectedExtras = tempSelectedExtras.map(e => ({
                    ...e
                }));

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

            // Actualizar indicadores de precio (extras y estimado)
            // REGLA: El precio del campo es SOLO el del producto/variante
            // Los extras se muestran como información adicional, NO se suman al campo
            function recalculateFinalPrice() {
                const extrasTotal = selectedExtras.reduce((sum, e) => sum + e.price, 0);
                const manualAdjust = parseFloat($('#modalExtrasCost').val()) || 0;
                const requiresDesign = $('#chkRequiresDesign').is(':checked');

                // NO modificar el precio del campo - queda el precio base del producto/variante
                // Solo actualizar indicadores visuales

                // Indicador de extras
                if (extrasTotal > 0) {
                    $('#extrasIndicator').text('+ Extras $' + extrasTotal.toFixed(2)).show();
                } else {
                    $('#extrasIndicator').hide();
                }

                // Indicador de precio estimado (cuando requiere personalización)
                if (requiresDesign) {
                    $('#estimatedPriceIndicator').show();
                } else {
                    $('#estimatedPriceIndicator').hide();
                }

                // Actualizar subtotal visual
                updateItemSubtotal();
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

                //                $('#priceComparisonContainer').show();

                // Mostrar alerta si precio fue modificado manualmente
                const expectedPrice = modalBasePrice + extrasTotal + manualAdjust;
                if (Math.abs(finalPrice - expectedPrice) > 0.01) {
                    //  $('#priceModifiedAlert').show();
                } else {
                    //   $('#priceModifiedAlert').hide();
                }
            }

            // Actualizar subtotal del ítem
            // El subtotal incluye precio base + extras para mostrar el total real
            function updateItemSubtotal() {
                const qty = parseInt($('#modalQuantity').val()) || 1;
                const price = parseFloat($('#modalPrice').val()) || 0;
                const extrasTotal = selectedExtras.reduce((sum, e) => sum + e.price, 0);
                const manualAdjust = parseFloat($('#modalExtrasCost').val()) || 0;

                // Precio total por unidad = precio producto + extras + ajuste manual
                const pricePerUnit = price + extrasTotal + manualAdjust;
                const subtotal = qty * pricePerUnit;

                $('#itemSubtotalDisplay').text('$' + subtotal.toFixed(2));

                // Mostrar detalle con desglose si hay extras
                if (extrasTotal > 0 || manualAdjust > 0) {
                    const extrasInfo = extrasTotal > 0 ? ` + $${extrasTotal.toFixed(2)} extras` : '';
                    const adjustInfo = manualAdjust > 0 ? ` + $${manualAdjust.toFixed(2)} ajuste` : '';
                    $('#itemSubtotalDetail').text(`${qty} × ($${price.toFixed(2)}${extrasInfo}${adjustInfo})`);
                } else {
                    $('#itemSubtotalDetail').text(qty + ' × $' + price.toFixed(2));
                }
            }

            // ==========================================
            // GESTIÓN DE MEDIDAS DEL ITEM
            // ==========================================

            // ==========================================
            // CONTROL DE VISIBILIDAD MEDIDAS (ÚNICO BLOQUE)
            // ==========================================
            function updateMeasurementsSectionVisibility() {
                if (!selectedProduct) {
                    // Sin producto: ocultar bloque de medidas
                    $('#measurementsSection').hide();
                    $('#systemClientDivider').hide();
                    // Deshabilitar botón de medidas y resetear checkbox
                    $('#chkRequiresMeasurements').prop('checked', false);
                    $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass('btn-primary btn-success')
                        .addClass('btn-secondary');
                    return;
                }

                console.log('[DEBUG] Producto:', selectedProduct.name, '| requires_measurements:', selectedProduct
                    .requires_measurements);

                if (selectedProduct.requires_measurements) {
                    // PRODUCTO A MEDIDA - mostrar ÚNICO bloque sistémico
                    $('#measurementsSection').show();
                    $('#systemClientDivider').show();

                    // Reset checkbox y botón (deshabilitado hasta marcar checkbox)
                    $('#chkRequiresMeasurements').prop('checked', false);
                    $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass('btn-success btn-primary')
                        .addClass('btn-secondary');

                    if (currentItemMeasurements) {
                        // Medidas capturadas: marcar checkbox, habilitar y actualizar botón
                        $('#chkRequiresMeasurements').prop('checked', true);
                        $('#btnMeasurementsText').text('Editar Medidas');
                        $('#btnOpenMeasurementsModal').prop('disabled', false).removeClass('btn-secondary')
                            .addClass('btn-success');
                        updateMeasurementsSummary(currentItemMeasurements);
                        $('#measurementsSummaryBody').show();
                    } else {
                        // Sin medidas: botón deshabilitado (gris)
                        $('#btnMeasurementsText').text('Capturar Medidas');
                        $('#measurementsSummaryBody').hide();
                    }
                    // Cargar conteo de medidas existentes del cliente vía AJAX
                    loadMeasurementsCountForTabs();
                } else {
                    // PRODUCTO ESTÁNDAR - ocultar bloque de medidas
                    $('#measurementsSection').hide();
                    $('#systemClientDivider').hide();
                }

                // Actualizar estado del botón guardar (NUNCA se bloquea por medidas)
                updateAddButtonState();
            }

            // Cargar conteo de medidas existentes para mostrar en tab
            function loadMeasurementsCountForTabs() {
                if (!selectedClientData) {
                    $('#existingMeasuresCount').text('0');
                    return;
                }
                // Usar cache si existe
                if (clientMeasurementsCache !== null) {
                    $('#existingMeasuresCount').text(clientMeasurementsCache.length || 0);
                    return;
                }
                // Cargar desde servidor
                $.ajax({
                    url: `/admin/orders/ajax/cliente/${selectedClientData.id}/measurements`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(measurements) {
                        clientMeasurementsCache = measurements;
                        $('#existingMeasuresCount').text(measurements.length || 0);
                    },
                    error: function() {
                        $('#existingMeasuresCount').text('0');
                    }
                });
            }

            // Habilitar/deshabilitar botón de medidas según checkbox
            $('#chkRequiresMeasurements').on('change', function() {
                if ($(this).is(':checked')) {
                    // Habilitar botón con estilo azul
                    $('#btnOpenMeasurementsModal').prop('disabled', false).removeClass('btn-secondary')
                        .addClass('btn-primary');
                } else {
                    // Deshabilitar botón y resetear medidas
                    $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass(
                        'btn-primary btn-success').addClass('btn-secondary');
                    // Limpiar medidas capturadas
                    currentItemMeasurements = null;
                    $('#measurementsSummaryBody').hide();
                    $('#measurementsSummaryContent').empty();
                    $('#btnMeasurementsText').text('Capturar Medidas');
                }
            });

            // Abrir modal de medidas (overlay, sin cerrar modal producto)
            $('#btnOpenMeasurementsModal').on('click', function() {
                if (!selectedProduct) return;

                // Mostrar nombre del cliente y producto en el modal de medidas
                $('#measurementsClientName').text(selectedClientData ? selectedClientData.text : '-');
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

                // Reset tabs al primer tab (Capturar nuevas)
                $('#tab-new-measures').tab('show');

                // Actualizar conteo de medidas existentes
                loadMeasurementsCountForTabs();

                // Abrir modal como overlay (NO cierra #addProductModal)
                $('#measurementsModal').modal('show');
            });

            // Al cambiar a tab "Usar existentes", cargar medidas
            $('#tab-existing-measures').on('shown.bs.tab', function() {
                loadClientMeasurementsForSelection();
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
                    url: `/admin/orders/ajax/cliente/${selectedClientData.id}/measurements`,
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

            // Renderizar lista de medidas existentes (con historial enriquecido)
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

                    // Badge de fuente con colores según tipo
                    let sourceBadge = '';
                    if (m.source === 'order') {
                        sourceBadge =
                            '<span class="badge" style="background: #e3f2fd; color: #1565c0; font-size: 11px;">Pedido</span>';
                    } else if (m.source === 'manual') {
                        sourceBadge =
                            '<span class="badge" style="background: #e8f5e9; color: #2e7d32; font-size: 11px;">Manual</span>';
                    } else if (m.source === 'profile' || m.source === 'legacy') {
                        sourceBadge =
                            '<span class="badge" style="background: #f3e5f5; color: #7b1fa2; font-size: 11px;">Perfil</span>';
                    }

                    // Badge principal
                    const isPrimary = m.is_primary ?
                        '<span class="badge badge-info ml-1" style="font-size: 11px;">Principal</span>' :
                        '';

                    // Info del pedido/producto si existe
                    let contextInfo = '';
                    if (m.order_number) {
                        contextInfo +=
                            `<span class="text-primary" style="font-size: 12px;"><i class="fas fa-file-alt mr-1"></i>#${m.order_number}</span>`;
                    }
                    if (m.product_name) {
                        contextInfo +=
                            `<span class="text-secondary ml-2" style="font-size: 12px;"><i class="fas fa-box mr-1"></i>${m.product_name}</span>`;
                    }

                    // Fecha relativa
                    const dateInfo = m.captured_at_relative ?
                        `<span class="text-muted" style="font-size: 11px;"><i class="fas fa-clock mr-1"></i>${m.captured_at_relative}</span>` :
                        '';

                    const itemHtml = `
                        <div class="card mb-2 measurement-option" data-measurement='${JSON.stringify(m)}' style="cursor: pointer; border-left: 3px solid ${m.source === 'order' ? '#1565c0' : (m.source === 'manual' ? '#2e7d32' : '#7b1fa2')};">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1;">
                                        <div class="d-flex align-items-center flex-wrap mb-1">
                                            ${sourceBadge} ${isPrimary}
                                            <span class="ml-2 font-weight-bold" style="font-size: 13px;">${m.label || 'Medidas registradas'}</span>
                                        </div>
                                        ${contextInfo ? `<div class="mb-1">${contextInfo}</div>` : ''}
                                        <div class="text-dark" style="font-size: 13px;">
                                            <i class="fas fa-ruler-combined mr-1 text-info"></i>${summary}
                                        </div>
                                    </div>
                                    <div class="text-right ml-2">
                                        ${dateInfo}
                                        <div class="mt-1">
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </div>
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
                };

                // Verificar que al menos una medida tenga valor
                const hasAnyMeasurement = ['busto', 'cintura', 'cadera', 'alto_cintura', 'largo',
                        'largo_vestido'
                    ]
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

                // Guardar medidas en variable del item actual
                // Las medidas se guardan SOLO al crear el pedido (backend)
                currentItemMeasurements = measurements;

                // Actualizar UI del modal de producto
                updateMeasurementsUIAfterCapture();

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

            // Actualizar UI después de capturar medidas (SIN badges de estado)
            function updateMeasurementsUIAfterCapture() {
                if (!currentItemMeasurements) return;

                // Marcar que las medidas fueron confirmadas (para no resetear al cerrar modal)
                $('#measurementsModal').data('confirmed', true);

                // Cambiar texto y color del botón a verde (éxito)
                $('#btnMeasurementsText').text('Editar Medidas');
                $('#btnOpenMeasurementsModal').removeClass('btn-secondary btn-primary').addClass('btn-success');

                // Mostrar resumen de medidas
                const summaryHtml = buildMeasurementsSummaryHtml(currentItemMeasurements);
                $('#measurementsSummaryContent').html(summaryHtml);
                $('#measurementsSummaryBody').show();

                // Actualizar estado del botón guardar (NUNCA se bloquea por medidas)
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
                const items = [{
                        label: 'Busto',
                        value: m.busto
                    },
                    {
                        label: 'Cintura',
                        value: m.cintura
                    },
                    {
                        label: 'Cadera',
                        value: m.cadera
                    },
                    {
                        label: 'Alto Cintura',
                        value: m.alto_cintura
                    },
                    {
                        label: 'Largo',
                        value: m.largo
                    },
                    {
                        label: 'Largo Vestido',
                        value: m.largo_vestido
                    }
                ];

                items.forEach(item => {
                    if (item.value) {
                        html +=
                            `<div class="col-4 mb-1"><strong>${item.label}:</strong> ${item.value} cm</div>`;
                    }
                });

                return html || '<div class="col-12 text-muted">Sin medidas</div>';
            }

            // Actualizar UI de resumen de medidas
            function updateMeasurementsSummary(measurements) {
                const summaryHtml = buildMeasurementsSummaryHtml(measurements);
                $('#measurementsSummaryContent').html(summaryHtml);
            }

            // Limpiar cache de medidas cuando cambia el cliente
            function clearMeasurementsCache() {
                clientMeasurementsCache = null;
                currentItemMeasurements = null;
            }

            // Habilitar/deshabilitar botón agregar (D3: según estado del modal)
            function updateAddButtonState() {
                const $btn = $('#addProductBtn');

                // D3: Comportamiento según estado del modal
                switch (modalState.current) {
                    case MODAL_STATES.IDLE:
                    case MODAL_STATES.INTENT_SELECTION:
                        // En selección de intención: botón deshabilitado
                        $btn.prop('disabled', true).attr('title', '').tooltip('dispose');
                        $btn.html('<i class="fas fa-plus mr-1"></i> Agregar al Pedido');
                        return;

                    case MODAL_STATES.ADDING_EXTRAS_ONLY:
                        // D5: Modo solo extras - habilitar si hay extras seleccionados
                        if (extrasOnlySelected.length === 0) {
                            $btn.prop('disabled', true)
                                .attr('title', 'Seleccione al menos un servicio')
                                .tooltip('dispose').tooltip();
                        } else {
                            $btn.prop('disabled', false).attr('title', '').tooltip('dispose');
                        }
                        $btn.html('<i class="fas fa-concierge-bell mr-1"></i> Agregar Servicios');
                        return;

                    case MODAL_STATES.ADDING_PRODUCT:
                    case MODAL_STATES.EDITING:
                        // Flujo normal de producto
                        break;
                }

                // Flujo ADDING_PRODUCT / EDITING: validación estándar
                if (!selectedProduct) {
                    $btn.prop('disabled', true).attr('title', '').tooltip('dispose');
                    $btn.html('<i class="fas fa-plus mr-1"></i> Agregar al Pedido');
                    return;
                }

                const price = parseFloat($('#modalPrice').val()) || 0;
                const hasVariants = selectedProduct.variants && selectedProduct.variants.length > 0;
                const variantSelected = $('#modalVariantSelect').val();

                // Bloquear si el producto tiene variantes y no se ha seleccionado una
                if (hasVariants && !variantSelected) {
                    $btn.prop('disabled', true).attr('title', 'Seleccione una variante').tooltip('dispose')
                        .tooltip();
                    return;
                }

                // Bloquear si precio es 0 o vacío
                if (price <= 0) {
                    $btn.prop('disabled', true).attr('title', 'Ingrese un precio válido').tooltip('dispose')
                        .tooltip();
                    return;
                }

                // ═══════════════════════════════════════════════════════════
                // REGLA DE ORO: El botón NUNCA se bloquea por medidas faltantes
                // Las validaciones duras ocurren en el backend al confirmar
                // El operador DECIDE, el sistema DECLARA estados (PENDING)
                // ═══════════════════════════════════════════════════════════

                // Todo OK: habilitar botón
                $btn.prop('disabled', false).attr('title', '').tooltip('dispose');
                $btn.html(editingItemIndex !== null ?
                    '<i class="fas fa-save mr-1"></i> Guardar Cambios' :
                    '<i class="fas fa-plus mr-1"></i> Agregar al Pedido');
            }

            // ==========================================
            // FASE 5: RESET DE ESTADO DEPENDIENTE DEL PRODUCTO
            // Llamado al CAMBIAR de producto (select2:select)
            // Limpia TODO lo que pertenece al producto anterior
            // ==========================================
            function resetProductDependentState() {
                // 1. Medidas del item (pertenecen al producto seleccionado)
                currentItemMeasurements = null;
                $('#measurementsSummaryBody').hide();
                $('#measurementsSummaryContent').empty();

                // 1.1 Ocultar sección de medidas (se reevalúa con el nuevo producto)
                $('#measurementsSection').hide();
                $('#systemClientDivider').hide();

                // 2. Extras seleccionados del producto anterior
                selectedExtras = [];
                tempSelectedExtras = [];
                renderSelectedExtrasList();

                // 3. Ajuste manual de precio (dependiente del contexto del producto)
                $('#modalExtrasCost').val('0');

                // 4. Reset visual del botón de medidas y checkbox
                $('#chkRequiresMeasurements').prop('checked', false);
                $('#btnMeasurementsText').text('Capturar Medidas');
                $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass('btn-primary btn-success')
                    .addClass('btn-secondary');
            }

            // D4: RESET TOTAL DE ESTADO DEL MODAL
            function resetProductModal(skipSelect2Reset = false) {
                // === RESET SECCIÓN PRODUCTO ===
                if (!skipSelect2Reset) {
                    $('#modalProductSelect').val(null).trigger('change.select2');
                }
                $('#productPreviewName').text('-');
                $('#productPreviewCategory').text('-');
                $('#productPreviewSku').html('-');
                $('#productPreviewImage').attr('src', '{{ asset('img/no-image.png') }}');
                $('#productPreviewType').hide();
                // Reset medidas del item
                currentItemMeasurements = null;
                $('#measurementsSection').hide();
                $('#systemClientDivider').hide();
                $('#chkRequiresMeasurements').prop('checked', false);
                $('#btnMeasurementsText').text('Capturar Medidas');
                $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass('btn-primary btn-success')
                    .addClass('btn-secondary');
                $('#measurementsSummaryBody').hide();
                $('#measurementsSummaryContent').empty();
                // Reset precio y comparación
                $('#modalPrice').val('');
                $('#priceComparisonContainer').hide();
                // $('#priceModifiedAlert').hide();
                $('#itemSubtotalContainer').hide();
                // Reset indicadores de precio
                $('#extrasIndicator').hide().text('');
                $('#estimatedPriceIndicator').hide();
                // Reset personalización
                $('#chkRequiresDesign').prop('checked', false);
                $('#modalEmbroideryText').prop('disabled', true).val('').attr('placeholder',
                    'Marque el checkbox para habilitar');
                $('#embroideryTextHint').hide();
                $('#modalExtrasCost').val('0');
                $('#modalCustomizationNotes').val('');
                // Reset extras del producto
                $('#productExtrasSection').hide();
                $('#selectedExtrasList').empty().hide();
                $('#noExtrasSelectedMsg').show();
                $('#extrasSubtotalDisplay').text('+$0.00');
                selectedExtras = [];
                tempSelectedExtras = [];
                // Reset cantidad y variante
                $('#modalQuantity').val(1);
                $('#modalVariantSelect').empty().append('<option value="">-- Seleccionar el producto --</option>');


                // === D4: RESET VARIABLES DE ESTADO ===
                selectedProduct = null;
                modalBasePrice = 0;
                editingItemIndex = null;

                // === D4: RESET SECCIÓN EXTRAS-ONLY (delegado a función consolidada) ===
                resetExtrasOnlySelection();
                // Reset tabla a estado inicial (carga diferida)
                $('#extrasOnlyTableBody').html(`
                    <tr>
                        <td colspan="3" class="text-center py-4" style="color: #495057;">
                            <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                            Seleccione "Solo Extras" para cargar
                        </td>
                    </tr>
                `);

                // === D4: RESET ESTADO DEL MODAL ===
                modalState.intent = null;
                modalState.previousState = null;

                // Reset botón
                $('#addProductBtn').prop('disabled', true).html(
                    '<i class="fas fa-plus mr-1"></i> Agregar al Pedido');
            }

            // Variable para trackear precio base
            let modalBasePrice = 0;

            $('#modalVariantSelect').on('change', function() {
                const variantPrice = $(this).find('option:selected').data('price');
                if (variantPrice) {
                    // Actualizar precio base con precio de variante
                    modalBasePrice = parseFloat(variantPrice);
                    // Poner el precio en el campo
                    $('#modalPrice').val(modalBasePrice.toFixed(2));
                } else if (selectedProduct) {
                    // Sin variante seleccionada: limpiar precio
                    modalBasePrice = parseFloat(selectedProduct.base_price) || 0;
                    $('#modalPrice').val('');
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

            // Handler para checkbox de personalización (requiere diseño)
            $('#chkRequiresDesign').on('change', function() {
                const isChecked = $(this).is(':checked');
                if (isChecked) {
                    // Habilitar input de texto a bordar
                    $('#modalEmbroideryText').prop('disabled', false).attr('placeholder',
                        'Nombre, frase, iniciales...');
                    $('#embroideryTextHint').show();
                } else {
                    // Deshabilitar y limpiar input de texto a bordar
                    $('#modalEmbroideryText').prop('disabled', true).val('').attr('placeholder',
                        'Marque el checkbox para habilitar');
                    $('#embroideryTextHint').hide();
                }
                // Actualizar indicador de precio estimado
                recalculateFinalPrice();
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
            // AGREGAR PRODUCTO AL PEDIDO (D3: según estado del modal)
            // ==========================================
            $('#addProductBtn').on('click', function() {
                // D3: Comportamiento según estado del modal
                if (modalState.current === MODAL_STATES.ADDING_EXTRAS_ONLY) {
                    // D5: Modo solo extras
                    if (extrasOnlySelected.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin extras seleccionados',
                            text: 'Seleccione al menos un extra para continuar',
                            confirmButtonColor: '#7f00ff'
                        });
                        return;
                    }
                    addExtrasOnlyToOrder();
                    return;
                }

                // Flujo normal: ADDING_PRODUCT o EDITING
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

                addProductToOrder();
            });

            // D5: Función para agregar solo extras (sin producto)
            function addExtrasOnlyToOrder() {
                const notes = $('#extrasOnlyNotes').val().trim();
                const totalExtras = extrasOnlySelected.reduce((sum, e) => sum + e.price, 0);

                // Crear item especial de tipo "extras_only"
                const newItem = {
                    index: itemIndex++,
                    product_id: null, // Sin producto
                    product_variant_id: null,
                    product_name: 'Extras Adicionales',
                    variant_display: null,
                    variant_sku: null,
                    image_url: null,
                    quantity: 1,
                    unit_price: totalExtras,
                    is_customized: true, // Marcar como personalizado
                    embroidery_text: '',
                    extras_cost: 0,
                    customization_notes: notes,
                    extras: extrasOnlySelected.map(e => ({
                        id: e.id,
                        name: e.name,
                        price: e.price
                    })),
                    measurements: null,
                    item_type: 'extras_only' // Tipo especial para identificar
                };

                orderItems.push(newItem);
                renderItemsTable();
                updateHiddenInputs();
                calculateTotals();

                // Cerrar modal y notificar
                $('#addProductModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Extras agregados',
                    text: `${extrasOnlySelected.length} extra(s) por $${totalExtras.toFixed(2)}`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2500
                });

                // Reset
                resetProductModal();
            }

            // Función separada para agregar/actualizar producto
            function addProductToOrder() {
                const variantId = $('#modalVariantSelect').val() || null;
                const variantOption = $('#modalVariantSelect option:selected');
                const newQuantity = parseInt($('#modalQuantity').val()) || 1;
                const basePrice = parseFloat($('#modalPrice').val()) || 0;

                // Capturar flag de personalización (requiere diseño)
                const requiresDesign = $('#chkRequiresDesign').is(':checked');
                const embroideryText = $('#modalEmbroideryText').val().trim();
                const extrasCost = parseFloat($('#modalExtrasCost').val()) || 0;
                const customizationNotes = $('#modalCustomizationNotes').val().trim();
                const isCustomized = requiresDesign || embroideryText.length > 0;

                // Calcular total de extras seleccionados
                const extrasTotal = selectedExtras.reduce((sum, e) => sum + e.price, 0);

                // Precio unitario FINAL = precio base + extras + ajuste manual
                const newPrice = basePrice + extrasTotal + extrasCost;

                // Clonar extras seleccionados para este ítem (con cantidad)
                const itemExtras = selectedExtras.map(e => ({
                    id: e.id,
                    name: e.name,
                    price: e.price,
                    quantity: e.quantity || 1
                }));

                // === MODO EDIT: Actualizar item existente ===
                if (editingItemIndex !== null) {
                    const editItem = orderItems.find(i => i.index === editingItemIndex);
                    if (editItem) {
                        // Actualizar TODOS los campos del item
                        editItem.product_id = selectedProduct.id;
                        editItem.product_variant_id = variantId;
                        editItem.product_name = selectedProduct.name;
                        editItem.variant_display = variantId ? (variantOption.text().split(' ($')[0] || editItem
                            .variant_display) : null;
                        editItem.variant_sku = variantId ? (variantOption.data('sku') || editItem.variant_sku) :
                            null;
                        editItem.image_url = selectedProduct.image_url;
                        editItem.quantity = newQuantity;
                        editItem.unit_price = newPrice;
                        editItem.lead_time = selectedProduct.lead_time || 0;
                        editItem.requires_measurements = selectedProduct.requires_measurements || false;
                        editItem.requires_design = requiresDesign;
                        editItem.product_type_name = selectedProduct.product_type_name || null;
                        editItem.is_customized = isCustomized;
                        editItem.embroidery_text = embroideryText;
                        editItem.extras_cost = extrasCost;
                        editItem.customization_notes = customizationNotes;
                        editItem.selected_extras = itemExtras;
                        editItem.measurements = currentItemMeasurements ? {
                            ...currentItemMeasurements
                        } : null;

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
                    // Buscar si ya existe un item IDÉNTICO en la lista
                    // Solo se agrupa si TODO es exactamente igual (mismo trabajo de producción)
                    const existingItem = orderItems.find(item => {
                        // 1. Mismo producto y variante
                        if (item.product_id !== selectedProduct.id) return false;
                        if (item.product_variant_id !== variantId) return false;

                        // 2. Mismo estado de personalización
                        if (item.is_customized !== isCustomized) return false;

                        // 3. Mismo texto de bordado (case-sensitive)
                        const itemText = (item.embroidery_text || '').trim();
                        const newText = (embroideryText || '').trim();
                        if (itemText !== newText) return false;

                        // 4. Mismas notas de personalización
                        const itemNotes = (item.customization_notes || '').trim();
                        const newNotes = (customizationNotes || '').trim();
                        if (itemNotes !== newNotes) return false;

                        // 5. Mismos extras (comparar IDs ordenados)
                        const itemExtrasIds = (item.selected_extras || []).map(e => e.id).sort().join(',');
                        const newExtrasIds = (itemExtras || []).map(e => e.id).sort().join(',');
                        if (itemExtrasIds !== newExtrasIds) return false;

                        // 6. Mismas medidas (si aplica)
                        const itemMeasures = JSON.stringify(item.measurements || {});
                        const newMeasures = JSON.stringify(currentItemMeasurements || {});
                        if (itemMeasures !== newMeasures) return false;

                        // Todo coincide: es el mismo item
                        return true;
                    });

                    if (existingItem) {
                        // Item idéntico existe: solo incrementar cantidad
                        existingItem.quantity += newQuantity;

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
                            requires_design: requiresDesign,
                            product_type_name: selectedProduct.product_type_name || null,
                            is_customized: isCustomized,
                            embroidery_text: embroideryText,
                            extras_cost: extrasCost,
                            customization_notes: customizationNotes,
                            selected_extras: itemExtras,
                            measurements: currentItemMeasurements ? {
                                ...currentItemMeasurements
                            } : null
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
                updateReadinessIndicator();

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

                    // D5: Renderizado especial para items de tipo "extras_only"
                    if (item.item_type === 'extras_only') {
                        // Construir lista de extras
                        let extrasListHtml = '';
                        if (item.extras && item.extras.length > 0) {
                            const extrasList = item.extras.map(e =>
                                `<li>${e.name} (+$${e.price.toFixed(2)})</li>`).join('');
                            extrasListHtml =
                                `<ul class="mb-0 mt-1 pl-3" style="font-size: 0.9rem;">${extrasList}</ul>`;
                        }

                        // Notas
                        let notesRow = '';
                        if (item.customization_notes) {
                            notesRow =
                                `<div class="mt-1" style="font-size: 0.95rem;"><strong>Notas:</strong> ${item.customization_notes}</div>`;
                        }

                        $tbody.append(`
                            <tr data-index="${item.index}" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);">
                                <td class="text-center align-middle">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 50px; height: 50px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                        <i class="fas fa-plus-circle fa-lg text-white"></i>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <strong style="font-size: 1.05rem; color: #2e7d32;">
                                        <i class="fas fa-plus-circle mr-1"></i> Extras Adicionales
                                    </strong>
                                    <span class="badge ml-1" style="background: #11998e; color: white; font-size: 10px;">POST-VENTA</span>
                                    ${extrasListHtml}
                                    ${notesRow}
                                </td>
                                <td class="text-center align-middle text-muted">-</td>
                                <td class="text-center align-middle text-muted">-</td>
                                <td class="text-right align-middle font-weight-bold text-success" style="font-size: 1.05rem;">
                                    $${subtotal.toFixed(2)}
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${item.index}" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `);
                        return; // Continuar con el siguiente item
                    }

                    // === RENDERIZADO NORMAL PARA PRODUCTOS ===
                    // Variante
                    const variantText = item.variant_display ?
                        `<small class="text-muted d-block">${item.variant_display}</small>` : '';

                    // FILA 1: Personalizado + Medidas (2 por fila)
                    let badgesRow1 = [];
                    if (item.is_customized) {
                        badgesRow1.push(
                            `<span class="badge badge-purple" style="background: #7f00ff; color: #fff;"><i class="fas fa-magic mr-1"></i>Personal.</span>`
                        );
                    }
                    if (item.requires_measurements) {
                        if (item.measurements) {
                            const measureSummary = buildMeasurementSummaryText(item.measurements);
                            badgesRow1.push(
                                `<span class="badge view-measurements-btn" style="background: #495057; color: white; cursor: pointer;" data-index="${item.index}" title="Click para ver medidas: ${measureSummary}"><i class="fas fa-ruler-combined mr-1"></i>Medidas ✓</span>`
                            );
                        } else {
                            badgesRow1.push(
                                `<span class="badge badge-warning text-dark"><i class="fas fa-ruler mr-1"></i>Sin medidas</span>`
                            );
                        }
                    }

                    // FILA 2: Texto personalizado (embroidery_text)
                    let embroideryRow = '';
                    if (item.embroidery_text) {
                        embroideryRow =
                            `<div class="mt-1" style="font-size: 1.05rem;"><strong>Texto:</strong> ${item.embroidery_text}</div>`;
                    }

                    // FILA 3: Notas
                    let notesRow = '';
                    if (item.customization_notes) {
                        notesRow =
                            `<div class="mt-1" style="font-size: 1.05rem;"><strong>Notas:</strong> ${item.customization_notes}</div>`;
                    }

                    // Badge: Tiene extras (de BD)
                    let extrasRow = '';
                    if (item.selected_extras && item.selected_extras.length > 0) {
                        const extrasNames = item.selected_extras.map(e => e.name).join(', ');
                        const extrasTotal = item.selected_extras.reduce((sum, e) => sum + e.price, 0);
                        extrasRow =
                            `<div class="mt-1"><span class="badge badge-success" title="${extrasNames}"><i class="fas fa-plus-circle mr-1"></i>${item.selected_extras.length} extra${item.selected_extras.length > 1 ? 's' : ''} (+$${extrasTotal.toFixed(2)})</span></div>`;
                    }

                    // Construir HTML de badges
                    const badgesRow1Html = badgesRow1.length > 0 ?
                        `<div class="mt-1" style="line-height: 1.8;">${badgesRow1.join(' ')}</div>` : '';
                    const badgesHtml = badgesRow1Html + embroideryRow + notesRow + extrasRow;

                    $tbody.append(`
                        <tr data-index="${item.index}">
                            <td class="text-center align-middle"><img src="${item.image_url || '{{ asset('img/no-image.png') }}'}" class="product-image-thumb" onerror="this.src='{{ asset('img/no-image.png') }}'"></td>
                            <td class="align-middle">
                                <strong style="font-size: 1.05rem;">${item.product_name}</strong>
                                ${variantText}
                                ${badgesHtml}
                            </td>
                            <td class="text-center align-middle"><input type="number" class="form-control form-control-sm item-qty text-center" value="${item.quantity}" min="1" max="999" data-index="${item.index}"></td>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary" style="font-size: 0.9rem;">${leadTimeDays} días</span>
                            </td>
                            <td class="text-right align-middle font-weight-bold text-success" style="font-size: 1.05rem;">
                                <div>$${subtotal.toFixed(2)}</div>
                                <small class="text-muted">($${item.unit_price.toFixed(2)} c/u)</small>
                            </td>
                            <td class="text-center align-middle text-nowrap">
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
                $subtotalCell.html(
                    `<div>$${subtotal.toFixed(2)}</div><small class="text-muted">($${item.unit_price.toFixed(2)} c/u)</small>`
                );

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
                updateHiddenInputs();
                calculateTotals();
                calculateMinimumDate();
                updateReadinessIndicator();
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

                // Si se canceló (no se confirmaron medidas), reiniciar el ciclo
                if (!$(this).data('confirmed') && !currentItemMeasurements) {
                    // Desmarcar checkbox y deshabilitar botón
                    $('#chkRequiresMeasurements').prop('checked', false);
                    $('#btnOpenMeasurementsModal').prop('disabled', true).removeClass(
                        'btn-primary btn-success').addClass('btn-secondary');
                    $('#btnMeasurementsText').text('Capturar Medidas');
                }

                // Resetear flag de confirmación para próximo uso
                $(this).data('confirmed', false);
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
                        $('#productPreviewImage').attr('src', item.image_url ||
                            '{{ asset('img/no-image.png') }}');

                        if (item.product_type_name) {
                            $('#productPreviewType')
                                .html(
                                    `<span class="badge badge-secondary">${item.product_type_name}</span>`
                                )
                                .show();
                        } else {
                            $('#productPreviewType').hide();
                        }

                        // Precargar Select2 con producto seleccionado
                        const optionText =
                            `${item.product_name} - $${parseFloat(item.unit_price).toFixed(2)}`;
                        const newOption = new Option(optionText, item.product_id, true, true);
                        $('#modalProductSelect').append(newOption).trigger('change');

                        // Precargar cantidad
                        $('#modalQuantity').val(item.quantity);

                        // Precargar precio
                        $('#modalPrice').val(item.unit_price);

                        // Cargar TODAS las variantes y preseleccionar la actual
                        const $variantSelect = $('#modalVariantSelect');
                        $variantSelect.empty().append(
                            '<option value="">-- Seleccionar el producto --</option>');

                        if (selectedProduct.variants && selectedProduct.variants.length > 0) {
                            selectedProduct.variants.forEach(v => {
                                const isSelected = v.id == item.product_variant_id;
                                $variantSelect.append(
                                    `<option value="${v.id}" data-price="${v.price}" data-sku="${v.sku}" ${isSelected ? 'selected' : ''}>${v.display} ($${parseFloat(v.price).toFixed(2)})</option>`
                                );
                            });

                        } else {

                        }

                        // Precargar personalización
                        if (item.is_customized || item.embroidery_text) {
                            $('#chkRequiresDesign').prop('checked', true);
                            $('#modalEmbroideryText').prop('disabled', false);
                            $('#embroideryTextHint').show();
                        }
                        $('#modalEmbroideryText').val(item.embroidery_text || '');
                        $('#modalExtrasCost').val(item.extras_cost || 0);
                        $('#modalCustomizationNotes').val(item.customization_notes || '');

                        // Precargar extras seleccionados
                        selectedExtras = item.selected_extras ? [...item.selected_extras] : [];
                        renderSelectedExtrasList();
                        $('#productExtrasSection').show();

                        // Precargar medidas del item
                        currentItemMeasurements = item.measurements ? {
                            ...item.measurements
                        } : null;

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
                        Swal.fire('Error', 'No se pudo cargar la información del producto',
                            'error');
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
                    // D5: Campos base del ítem (incluyendo item_type para extras_only)
                    $container.append(`
                        <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id || ''}">
                        <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.product_variant_id || ''}">
                        <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                        <input type="hidden" name="items[${idx}][embroidery_text]" value="${item.embroidery_text || ''}">
                        <input type="hidden" name="items[${idx}][customization_notes]" value="${item.customization_notes || ''}">
                        <input type="hidden" name="items[${idx}][extras_cost]" value="${item.extras_cost || 0}">
                        <input type="hidden" name="items[${idx}][is_customized]" value="${item.is_customized ? 1 : 0}">
                        <input type="hidden" name="items[${idx}][item_type]" value="${item.item_type || 'product'}">
                    `);

                    // D5: Extras - puede venir de selected_extras (producto) o extras (extras_only)
                    const extrasArray = item.selected_extras || item.extras || [];
                    if (extrasArray.length > 0) {
                        extrasArray.forEach((extra, extIdx) => {
                            $container.append(`
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][id]" value="${extra.id}">
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][name]" value="${extra.name}">
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][price]" value="${extra.price}">
                                <input type="hidden" name="items[${idx}][extras][${extIdx}][quantity]" value="${extra.quantity || 1}">
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
            // INDICADOR DE ESTADO OPERATIVO (READY/PENDING)
            // ==========================================
            function updateReadinessIndicator() {
                const $indicator = $('#orderReadinessIndicator');
                const $content = $('#readinessContent');

                // Si no hay items, ocultar indicador
                if (orderItems.length === 0) {
                    $indicator.hide();
                    return;
                }

                // Analizar items para detectar pendientes
                let pendingItems = [];

                orderItems.forEach(function(item) {
                    // Items que requieren medidas pero no las tienen
                    if (item.requires_measurements && !item.measurements) {
                        pendingItems.push({
                            type: 'measurements',
                            product: item.product_name,
                            message: 'Requiere medidas'
                        });
                    }

                    // Items personalizados (diseño) - en captura inicial no hay aprobación,
                    // pero sí podemos indicar que requerirá aprobación post-creación
                    if (item.personalization_type === 'design') {
                        pendingItems.push({
                            type: 'design',
                            product: item.product_name,
                            message: 'Diseño pendiente de aprobación'
                        });
                    }
                });

                // Determinar estado
                const isReady = pendingItems.length === 0;
                const hasMeasurementsPending = pendingItems.some(p => p.type === 'measurements');
                const hasDesignPending = pendingItems.some(p => p.type === 'design');

                // Actualizar clases
                $indicator
                    .removeClass('status-ready status-pending status-empty')
                    .addClass(isReady ? 'status-ready' : 'status-pending')
                    .show();

                // Construir contenido
                let html = '';

                if (isReady) {
                    html = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle readiness-icon" style="color: #28a745;"></i>
                            <div>
                                <div class="readiness-title" style="color: #155724;">Pedido listo para crear</div>
                                <p class="readiness-subtitle mb-0" style="color: #155724;">
                                    Todos los datos de captura están completos.
                                </p>
                            </div>
                        </div>
                    `;
                } else {
                    let pendingList = '';

                    if (hasMeasurementsPending) {
                        const measurementItems = pendingItems
                            .filter(p => p.type === 'measurements')
                            .map(p => p.product)
                            .join(', ');
                        pendingList += `<li><strong>Medidas pendientes:</strong> ${measurementItems}</li>`;
                    }

                    if (hasDesignPending) {
                        const designItems = pendingItems
                            .filter(p => p.type === 'design')
                            .map(p => p.product)
                            .join(', ');
                        pendingList += `<li><strong>Requiere aprobación de diseño:</strong> ${designItems}</li>`;
                    }

                    html = `
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle readiness-icon" style="color: #856404;"></i>
                            <div>
                                <div class="readiness-title" style="color: #856404;">Pedido incompleto</div>
                                <p class="readiness-subtitle mb-0" style="color: #856404;">
                                    Información pendiente:
                                </p>
                                <ul class="readiness-pending-list mb-0" style="color: #856404;">
                                    ${pendingList}
                                </ul>
                            </div>
                        </div>
                    `;
                }

                $content.html(html);
            }

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
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
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
            // (Excepto si está en modo "Producción para stock")
            // ==========================================
            $('#btnAddProduct').on('click', function() {
                const isStockMode = $('#forStockSwitch').is(':checked');
                const hasClient = $('#cliente_id').val();

                // Si NO está en modo stock Y no hay cliente → bloquear
                if (!isStockMode && !hasClient) {
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
            // MODALES - FOCUS Y RESET (D3 + D4)
            // ==========================================
            $('#addProductModal').on('show.bs.modal', function() {
                // D4: Reset total si es nuevo (no editando)
                if (editingItemIndex === null) {
                    resetProductModal(); // D4: Reset completo
                    // D3: Establecer estado inicial según contexto
                    if (modalState.context === 'post_sale') {
                        setModalState(MODAL_STATES.INTENT_SELECTION);
                    } else {
                        setModalState(MODAL_STATES.ADDING_PRODUCT, 'product');
                    }
                } else {
                    // Editando: ir directo a producto
                    setModalState(MODAL_STATES.EDITING);
                }
            });
            $('#addProductModal').on('shown.bs.modal', function() {
                // Solo abrir Select2 en modo ADDING_PRODUCT y no editando
                if (modalState.current === MODAL_STATES.ADDING_PRODUCT && editingItemIndex === null) {
                    setTimeout(function() {
                        $('#modalProductSelect').select2('open');
                        setTimeout(function() {
                            document.querySelector('.select2-search__field')?.focus();
                        }, 50);
                    }, 150);
                }
            });
            $('#addProductModal').on('hidden.bs.modal', function() {
                // D4: Reset TOTAL al cerrar modal (elimina estados fantasma)
                resetProductModal();
                setModalState(MODAL_STATES.IDLE);
            });

            // ==========================================
            // D2: HANDLERS SELECTOR DE INTENCIÓN
            // ==========================================
            $('#btnIntentProduct').on('click', function() {
                setModalState(MODAL_STATES.ADDING_PRODUCT, 'product');
                // Abrir Select2 después de transición
                setTimeout(function() {
                    $('#modalProductSelect').select2('open');
                }, 200);
            });

            $('#btnIntentExtrasOnly').on('click', function() {
                setModalState(MODAL_STATES.ADDING_EXTRAS_ONLY, 'extras_only');
                // Cargar todos los extras disponibles
                loadAllExtrasForSelection();
            });

            $('#btnBackToIntent').on('click', function() {
                // FASE 5: Volver al selector de intención
                // 1. Cambiar estado PRIMERO (para que updateAddButtonState tenga contexto correcto)
                setModalState(MODAL_STATES.INTENT_SELECTION);
                // 2. Limpiar selección de extras (después del cambio de estado)
                resetExtrasOnlySelection();
                // 3. Forzar actualización del botón con estado ya cambiado
                updateAddButtonState();
            });

            // ==========================================
            // D5: EXTRAS SIN PRODUCTO - FUNCIONES
            // ==========================================
            let extrasOnlySelected = []; // Extras seleccionados en modo solo-extras

            function loadAllExtrasForSelection() {
                const $tbody = $('#extrasOnlyTableBody');
                $tbody.html(`
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                            <p class="mt-2 mb-0 text-muted">Cargando extras disponibles...</p>
                        </td>
                    </tr>
                `);

                $.ajax({
                    url: '{{ route('admin.product-extras.all-active') }}',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const extras = response.extras || [];
                        if (extras.length === 0) {
                            $tbody.html(`
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        No hay extras disponibles
                                    </td>
                                </tr>
                            `);
                            return;
                        }

                        $tbody.empty();
                        extras.forEach(extra => {
                            const rowHtml = `
                                <tr data-extra-id="${extra.id}" data-name="${extra.name}" data-price="${extra.price_addition}">
                                    <td class="text-center">
                                        <input type="checkbox" class="extras-only-checkbox">
                                    </td>
                                    <td>${extra.name}</td>
                                    <td class="text-right text-success font-weight-bold">+$${parseFloat(extra.price_addition).toFixed(2)}</td>
                                </tr>
                            `;
                            $tbody.append(rowHtml);
                        });
                    },
                    error: function() {
                        $tbody.html(`
                            <tr>
                                <td colspan="3" class="text-center py-4 text-danger">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                    Error al cargar extras
                                </td>
                            </tr>
                        `);
                    }
                });
            }

            // Checkbox en tabla de extras-only
            $(document).on('change', '.extras-only-checkbox', function() {
                const $row = $(this).closest('tr');
                const extraId = $row.data('extra-id');
                const extraName = $row.data('name');
                const extraPrice = parseFloat($row.data('price')) || 0;

                if ($(this).is(':checked')) {
                    if (!extrasOnlySelected.some(e => e.id === extraId)) {
                        extrasOnlySelected.push({
                            id: extraId,
                            name: extraName,
                            price: extraPrice
                        });
                    }
                } else {
                    extrasOnlySelected = extrasOnlySelected.filter(e => e.id !== extraId);
                }

                updateExtrasOnlySummary();
                updateAddButtonState();
            });

            // Seleccionar todos extras-only
            $('#selectAllExtrasOnly').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('#extrasOnlyTableBody tr:visible').each(function() {
                    const $checkbox = $(this).find('.extras-only-checkbox');
                    if ($checkbox.length && $checkbox.prop('checked') !== isChecked) {
                        $checkbox.prop('checked', isChecked).trigger('change');
                    }
                });
            });

            // Buscador de extras-only
            $('#extrasOnlySearchInput').on('input', function() {
                const term = $(this).val().toLowerCase().trim();
                $('#extrasOnlyTableBody tr').each(function() {
                    const name = ($(this).data('name') || '').toLowerCase();
                    $(this).toggle(name.includes(term));
                });
            });

            function updateExtrasOnlySummary() {
                const count = extrasOnlySelected.length;
                const total = extrasOnlySelected.reduce((sum, e) => sum + e.price, 0);
                $('#extrasOnlySelectedCount').text(count);
                $('#extrasOnlyTotal').text('$' + total.toFixed(2));
            }

            function resetExtrasOnlySelection() {
                extrasOnlySelected = [];
                $('#extrasOnlySearchInput').val('');
                $('#extrasOnlyNotes').val('');
                $('#selectAllExtrasOnly').prop('checked', false);
                // Desmarcar checkboxes individuales (evita estados visuales residuales)
                $('.extras-only-checkbox').prop('checked', false);
                updateExtrasOnlySummary();
            }

            $('#quickClientModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#quickClientNombre').focus();
                }, 150);
            });

            // ==========================================
            // VALIDACIÓN AL ENVIAR
            // ==========================================
            $('#orderForm').on('submit', function(e) {
                let errors = [];

                // Detectar modo producción para stock
                const isStockMode = $('#forStockSwitch').is(':checked');

                // 1. Validar cliente (SOLO si NO es modo stock)
                if (!isStockMode && !$('#cliente_id').val()) {
                    errors.push('<li><i class="fas fa-user mr-1"></i> Debe seleccionar un cliente</li>');
                }

                // 2. Validar al menos un producto (SIEMPRE requerido)
                if (orderItems.length === 0) {
                    errors.push(
                        '<li><i class="fas fa-box mr-1"></i> Debe agregar al menos un producto</li>');
                }

                // 3. Validar método de pago (SOLO si NO es modo stock Y hay anticipo)
                if (!isStockMode) {
                    const initialPaymentVal = parseFloat($('#initialPayment').val()) || 0;
                    const payFullChecked = $('#payFull').is(':checked');
                    if ((initialPaymentVal > 0 || payFullChecked) && !$('#paymentMethod').val()) {
                        errors.push(
                            '<li><i class="fas fa-dollar-sign mr-1"></i> Debe seleccionar un método de pago para registrar el anticipo</li>'
                        );
                    }
                }

                // 4. Validar fecha prometida (SOLO si NO es modo stock)
                if (!isStockMode && !$('#promisedDate').val()) {
                    errors.push(
                        '<li><i class="fas fa-calendar mr-1"></i> Debe indicar la fecha de entrega prometida</li>'
                    );
                }

                // 5. Validar que fecha prometida sea mayor o igual a la fecha mínima (SOLO si NO es modo stock)
                if (!isStockMode) {
                    const promisedDate = $('#promisedDate').val();
                    const minDate = $('#promisedDate').attr('min');
                    if (promisedDate && minDate && promisedDate < minDate) {
                        const minDateFormatted = new Date(minDate + 'T00:00:00').toLocaleDateString(
                        'es-MX', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        });
                        errors.push(
                            '<li><i class="fas fa-exclamation-triangle mr-1"></i> La fecha de entrega debe ser posterior o igual a ' +
                            minDateFormatted + '</li>');
                    }
                }

                // Si hay errores, mostrar SweetAlert y cancelar envío
                if (errors.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos requeridos',
                        html: '<ul style="text-align:left;margin:0;padding-left:10px;list-style:none;">' +
                            errors.join('') + '</ul>',
                        confirmButtonColor: '#7f00ff'
                    });
                    return false;
                }

                // === BLOQUEAR BOTÓN PARA EVITAR DOBLE SUBMIT ===
                const $submitBtn = $('button[type="submit"][form="orderForm"]');
                $submitBtn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...');
            });

            // ==========================================
            // FASE 3: INICIALIZACIÓN MODO EDICIÓN
            // ==========================================
            @if (isset($isEdit) && isset($order) && isset($orderItems))
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
                                is_customized: !!((item.embroidery_text || '').trim() || (item
                                    .customization_notes || '').trim()),
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
                    $('#urgency').val('{{ $order->urgency_level ?? 'normal' }}').trigger('change');
                    $('#promisedDate').val(
                        '{{ $order->promised_date ? \Carbon\Carbon::parse($order->promised_date)->format('Y-m-d') : '' }}'
                    );
                    $('#discount').val('{{ $order->discount ?? 0 }}');
                    $('#notes').val(@json($order->notes ?? ''));
                    @if ($order->requires_invoice)
                        $('#requiresInvoice').prop('checked', true).trigger('change');
                    @endif
                })();
            @endif

            // ==========================================
            // RESTAURACIÓN DE DATOS DESDE old() (VALIDACIÓN FALLIDA)
            // Cuando hay error de validación backend, restaurar items y estado
            // ==========================================
            @if (!isset($isEdit) && old('items'))
                (function restoreFromOldInput() {
                    console.log('[Restore] Restaurando datos desde old() después de error de validación');

                    // Restaurar items del pedido
                    const oldItems = @json(old('items', []));
                    if (oldItems && oldItems.length > 0) {
                        // Necesitamos cargar info de productos para mostrar nombres
                        const productIds = [...new Set(oldItems.map(i => i.product_id).filter(Boolean))];

                        // Fetch async de productos para obtener nombres e imágenes
                        if (productIds.length > 0) {
                            $.ajax({
                                url: '{{ route('admin.orders.ajax.get-products-info') }}',
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    product_ids: productIds
                                },
                                success: function(productsInfo) {
                                    oldItems.forEach(function(item) {
                                        const productInfo = productsInfo[item.product_id] ||
                                        {};

                                        const restoredItem = {
                                            index: itemIndex,
                                            product_id: parseInt(item.product_id) ||
                                                null,
                                            product_variant_id: item
                                                .product_variant_id ? parseInt(item
                                                    .product_variant_id) : null,
                                            product_name: productInfo.name ||
                                                'Producto #' + item.product_id,
                                            variant_sku: productInfo.variant_sku || '',
                                            variant_display: productInfo
                                                .variant_display || '',
                                            unit_price: parseFloat(item.unit_price) ||
                                                0,
                                            quantity: parseInt(item.quantity) || 1,
                                            lead_time: productInfo.lead_time || 0,
                                            requires_measurements: productInfo
                                                .requires_measurements || false,
                                            product_type_name: productInfo
                                                .product_type_name || null,
                                            is_customized: item.is_customized == '1' ||
                                                item.is_customized === true,
                                            embroidery_text: item.embroidery_text || '',
                                            customization_notes: item
                                                .customization_notes || '',
                                            extras_cost: parseFloat(item.extras_cost) ||
                                                0,
                                            selected_extras: item.extras || [],
                                            measurements: item.measurements || null,
                                            image_url: productInfo.image_url || null,
                                            item_type: item.item_type || 'product'
                                        };

                                        if (item.product_id) {
                                            productLeadTimes[item.product_id] = productInfo
                                                .lead_time || 0;
                                        }

                                        orderItems.push(restoredItem);
                                        itemIndex++;
                                    });

                                    // Renderizar tabla
                                    renderItemsTable();
                                    updateHiddenInputs();
                                    calculateTotals();
                                    calculateMinimumDate();
                                    updateReadinessIndicator();

                                    console.log('[Restore] Items restaurados:', orderItems.length);
                                },
                                error: function() {
                                    // Fallback: restaurar sin info adicional del producto
                                    console.warn(
                                        '[Restore] No se pudo obtener info de productos, restaurando básico'
                                        );
                                    oldItems.forEach(function(item) {
                                        const restoredItem = {
                                            index: itemIndex,
                                            product_id: parseInt(item.product_id) ||
                                                null,
                                            product_variant_id: item
                                                .product_variant_id ? parseInt(item
                                                    .product_variant_id) : null,
                                            product_name: 'Producto #' + item
                                                .product_id,
                                            variant_sku: '',
                                            variant_display: '',
                                            unit_price: parseFloat(item.unit_price) ||
                                                0,
                                            quantity: parseInt(item.quantity) || 1,
                                            lead_time: 0,
                                            requires_measurements: false,
                                            product_type_name: null,
                                            is_customized: item.is_customized == '1',
                                            embroidery_text: item.embroidery_text || '',
                                            customization_notes: item
                                                .customization_notes || '',
                                            extras_cost: parseFloat(item.extras_cost) ||
                                                0,
                                            selected_extras: item.extras || [],
                                            measurements: item.measurements || null,
                                            image_url: null,
                                            item_type: item.item_type || 'product'
                                        };

                                        orderItems.push(restoredItem);
                                        itemIndex++;
                                    });

                                    renderItemsTable();
                                    updateHiddenInputs();
                                    calculateTotals();
                                }
                            });
                        }
                    }

                    // Restaurar estado del switch de stock si estaba activado
                    @if (old('for_stock'))
                        $('#forStockSwitch').prop('checked', true);
                        if (typeof updateStockModeUI === 'function') {
                            updateStockModeUI();
                        }
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
                    }, {
                        passive: true
                    });

                    card.addEventListener('touchend', function(e) {
                        if (e.target.tagName === 'INPUT') return;

                        const touchDuration = Date.now() - lastTouchTime;
                        if (touchDuration < 300) {
                            focusInput(e);
                        }
                    }, {
                        passive: false
                    });

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
