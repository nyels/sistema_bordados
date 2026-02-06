@extends('adminlte::page')

@section('title', 'Editar OC: ' . $purchase->purchase_number)

@section('meta_tags')
    {{-- PWA / Web App Meta Tags --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1f2937">
@stop

@section('content_header')
@stop

@section('content')
    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0" style="border-radius: 8px;">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchases.update', $purchase->id) }}" id="purchaseForm">
        @csrf
        @method('PUT')

        {{-- HEADER PROFESIONAL --}}
        <div class="purchase-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="purchase-title">Editar Orden de Compra</h1>
                    <p class="purchase-subtitle">{{ $purchase->purchase_number }} - Modifique los datos y materiales</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn-action btn-cancel">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-action btn-save" id="btn_submit">
                        <i class="fas fa-save"></i> Actualizar Orden
                    </button>
                </div>
            </div>
        </div>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="purchase-content">
            <div class="row no-gutters">
                {{-- PANEL IZQUIERDO: DATOS DE LA ORDEN --}}
                <div class="col-12 col-xl-4">
                    <div class="panel-section">
                        <div class="section-header">
                            <span class="section-icon"><i class="fas fa-file-alt"></i></span>
                            <span class="section-title">Datos de la Orden</span>
                        </div>

                        <div class="section-body">
                            {{-- Proveedor --}}
                            <div class="field-group">
                                <label class="field-label">Proveedor <span class="required">*</span></label>
                                <select name="proveedor_id" id="proveedor_id"
                                    class="field-control @error('proveedor_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar proveedor...</option>
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}"
                                            {{ old('proveedor_id', $purchase->proveedor_id) == $proveedor->id ? 'selected' : '' }}>
                                            {{ $proveedor->nombre_proveedor }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Fechas en grid --}}
                            <div class="field-row">
                                <div class="field-group">
                                    <label class="field-label">Fecha Orden</label>
                                    <input type="date" name="ordered_at" class="field-control"
                                        value="{{ old('ordered_at', $purchase->ordered_at?->format('Y-m-d')) }}">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Fecha Esperada</label>
                                    <input type="date" name="expected_at" class="field-control"
                                        value="{{ old('expected_at', $purchase->expected_at?->format('Y-m-d')) }}">
                                </div>
                            </div>

                            {{-- IVA y Referencia --}}
                            <div class="field-row">
                                <div class="field-group">
                                    <label class="field-label">IVA</label>
                                    <div class="iva-control-wrapper">
                                        <label class="iva-toggle">
                                            <input type="checkbox" id="iva_enabled" {{ old('tax_rate', $purchase->tax_rate) > 0 ? 'checked' : '' }}>
                                            <span class="iva-toggle-slider"></span>
                                        </label>
                                        <div class="field-with-suffix" id="iva_input_wrapper">
                                            <input type="number" name="tax_rate" id="tax_rate" class="field-control"
                                                value="{{ old('tax_rate', $purchase->tax_rate) }}" min="0" max="100" step="0.01">
                                            <span class="field-suffix">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Referencia</label>
                                    <input type="text" name="reference" class="field-control"
                                        value="{{ old('reference', $purchase->reference) }}" maxlength="100"
                                        placeholder="Factura, pedido...">
                                </div>
                            </div>

                            {{-- Descuento --}}
                            <div class="field-group">
                                <label class="field-label">Descuento Global</label>
                                <div class="field-with-prefix">
                                    <span class="field-prefix">$</span>
                                    <input type="number" name="discount_amount" id="discount_amount" class="field-control"
                                        value="{{ old('discount_amount', $purchase->discount_amount) }}" min="0"
                                        step="0.01">
                                </div>
                            </div>

                            {{-- Notas --}}
                            <div class="field-group">
                                <label class="field-label">Notas</label>
                                <textarea name="notes" class="field-control field-textarea" rows="3" maxlength="1000"
                                    placeholder="Notas internas de la orden...">{{ old('notes', $purchase->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PANEL DERECHO: MATERIALES --}}
                <div class="col-12 col-xl-8">
                    <div class="panel-section panel-main">
                        {{-- SECCIÓN AGREGAR MATERIAL - NUEVA INTERFAZ --}}
                        <div class="add-material-section">
                            <div class="section-header d-flex align-content-right">
                                <button type="button" class="btn-search-material" id="btn_open_material_modal">
                                    <i class="fas fa-search"></i> Buscar Material
                                </button>
                            </div>
                        </div>

                        {{-- TABLA DE ITEMS --}}
                        <div class="items-section">
                            <div class="items-header">
                                <span class="items-title">Items de la Orden</span>
                                <span class="items-count" id="items_count">0 items</span>
                            </div>

                            <div class="items-table-wrapper">
                                <table class="items-table" id="items_table">
                                    <thead>
                                        <tr>
                                            <th class="th-num">#</th>
                                            <th class="th-material">Material</th>
                                            <th class="th-variant">Variante</th>
                                            <th class="th-qty">Cant.</th>
                                            <th class="th-unit">Unidad</th>
                                            <th class="th-price">Precio</th>
                                            <th class="th-total">Total</th>
                                            <th class="th-action"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items_body">
                                        <tr id="no_items_row">
                                            <td colspan="8" class="empty-state">
                                                <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                                                <div class="empty-text">La orden está vacía</div>
                                                <div class="empty-hint">Agregue materiales usando el formulario superior
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- TOTALES --}}
                            <div class="totals-section" id="items_totals" style="display: none;">
                                <div class="totals-row">
                                    <span class="totals-label">Subtotal</span>
                                    <span class="totals-value" id="total_subtotal">$0.00</span>
                                </div>
                                <div class="totals-row">
                                    <span class="totals-label">IVA (<span id="tax_rate_display">{{ $purchase->tax_rate ?? $defaultTaxRate }}</span>%)</span>
                                    <span class="totals-value" id="total_tax">$0.00</span>
                                </div>
                                <div class="totals-row totals-discount" id="discount_row" style="display: none;">
                                    <span class="totals-label">Descuento</span>
                                    <span class="totals-value text-danger" id="total_discount">-$0.00</span>
                                </div>
                                <div class="totals-row totals-final">
                                    <span class="totals-label">Total</span>
                                    <span class="totals-value" id="total_final">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONTAINER DE INPUTS HIDDEN --}}
        <div id="hidden_items_container"></div>
    </form>

    {{-- MODAL: Buscar y Agregar Material --}}
    <div class="modal fade" id="modalSearchMaterial" tabindex="-1" role="dialog"
        aria-labelledby="modalSearchMaterialLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                {{-- HEADER --}}
                <div class="modal-header modal-header-material">
                    <h5 class="modal-title" id="modalSearchMaterialLabel">
                        <i class="fas fa-search"></i> Buscar Material
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{-- BODY --}}
                <div class="modal-body p-0">
                    {{-- Filtros --}}
                    <div class="modal-filters">
                        <div class="filter-row">
                            <div class="filter-field">
                                <label class="filter-label">Categoría</label>
                                <select id="modal_category" class="filter-control">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            data-base-unit="{{ $category->baseUnit->symbol ?? '' }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-field filter-field-search">
                                <label class="filter-label">Buscar</label>
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="modal_search" class="filter-control filter-search"
                                        placeholder="Nombre, SKU, color...">
                                </div>
                            </div>
                            <div class="filter-field filter-field-info">
                                <span class="results-count" id="modal_results_count">0 resultados</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de resultados --}}
                    <div class="modal-table-wrapper">
                        <table class="modal-table" id="modal_materials_table">
                            <thead>
                                <tr>
                                    <th class="th-select"></th>
                                    <th class="th-category">Categoría</th>
                                    <th class="th-material">Material</th>
                                    <th class="th-variant">Variante/Color</th>
                                    <th class="th-sku">SKU</th>
                                    <th class="th-stock">Stock</th>
                                    <th class="th-unit">Presentación</th>
                                </tr>
                            </thead>
                            <tbody id="modal_materials_body">
                                <tr id="modal_empty_row">
                                    <td colspan="7" class="modal-empty-state">
                                        <div class="empty-icon"><i class="fas fa-boxes"></i></div>
                                        <div class="empty-text">Seleccione una categoría o busque un material</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Loading --}}
                    <div id="modal_loading" class="modal-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <span>Cargando materiales...</span>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="modal-footer modal-footer-material">
                    {{-- Fila con selección + inputs (aparece al seleccionar presentación) --}}
                    <div class="footer-row" id="modal_footer_row" style="display: none;">
                        {{-- Info del material seleccionado --}}
                        <div class="footer-selected-info">
                            <span class="selected-label">SELECCIONADO:</span>
                            <span class="selected-name" id="modal_selected_name">-</span>
                            <span class="selected-variant" id="modal_selected_variant"></span>
                        </div>
                        {{-- Inputs y botones en línea --}}
                        <div class="footer-inputs">
                            <div class="footer-input-group">
                                <label>CANTIDAD</label>
                                <div class="input-with-unit">
                                    <input type="number" id="modal_quantity" class="footer-control" min="0.01"
                                        step="0.01" placeholder="0.00">
                                    <span class="input-unit" id="modal_unit_symbol">-</span>
                                </div>
                            </div>
                            <div class="footer-input-group">
                                <label>PRECIO UNIT.</label>
                                <div class="input-with-currency">
                                    <span class="input-currency">$</span>
                                    <input type="number" id="modal_price" class="footer-control footer-price"
                                        min="0.01" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="footer-input-group">
                                <label>SUBTOTAL</label>
                                <div class="footer-subtotal" id="modal_subtotal">$0.00</div>
                            </div>
                            <div class="footer-input-group">
                                <label>INGRESARÁ</label>
                                <div class="footer-conversion" id="modal_conversion_info">-</div>
                            </div>
                            <div class="footer-input-group footer-buttons-group">
                                <button type="button" class="btn-modal-close" data-dismiss="modal">
                                    <i class="fas fa-times"></i> Cerrar
                                </button>
                                <button type="button" id="modal_btn_add" class="btn-modal-add" disabled>
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Footer por defecto (solo botón cerrar) --}}
                    <div class="footer-actions" id="footer_actions_default">
                        <button type="button" class="btn-modal-close" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* ============================================
           SISTEMA DE DISEÑO PROFESIONAL - SaaS Style
           ============================================ */

        /* Variables CSS */
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #059669;
            --success-hover: #047857;
            --danger: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --border-radius: 8px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Reset del content wrapper de AdminLTE */
        .content-wrapper {
            background-color: var(--gray-50) !important;
            min-height: calc(100vh - 57px) !important;
        }

        .content {
            padding: 0 !important;
        }

        /* ============================================
           HEADER
           ============================================ */
        .purchase-header {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .purchase-title {
            font-family: var(--font-sans);
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
            letter-spacing: -0.025em;
        }

        .purchase-subtitle {
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-500);
            margin: 4px 0 0 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: #fff;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-cancel:hover {
            background: var(--gray-50);
            color: var(--gray-900);
            text-decoration: none;
        }

        .btn-save {
            background: var(--primary);
            color: #fff;
        }

        .btn-save:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .btn-save:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
        }

        /* ============================================
           CONTENIDO PRINCIPAL
           ============================================ */
        .purchase-content {
            padding: 24px 32px 32px;
        }

        .panel-section {
            background: #fff;
            border-radius: var(--border-radius);
            border: 2px solid var(--gray-400);
            margin-bottom: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .panel-main {
            margin-left: 24px;
        }

        @media (max-width: 1199px) {
            .panel-main {
                margin-left: 0;
                margin-top: 24px;
            }
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
        }

        .section-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-100);
            border-radius: 8px;
            color: var(--gray-600);
            font-size: 14px;
        }

        .section-icon.text-success {
            background: #d1fae5;
            color: var(--success);
        }

        .section-title {
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .section-body {
            padding: 20px;
        }

        /* ============================================
           CAMPOS DE FORMULARIO
           ============================================ */
        .field-group {
            margin-bottom: 16px;
        }

        .field-group:last-child {
            margin-bottom: 0;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field-label {
            display: block;
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .field-label-sm {
            display: block;
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .required {
            color: var(--danger);
        }

        .field-control {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-900);
            background: #fff;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .field-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .field-control:disabled {
            background: var(--gray-50);
            color: var(--gray-600);
            border-color: var(--gray-200);
            cursor: not-allowed;
        }

        .field-control:disabled::placeholder {
            color: var(--gray-600);
        }

        .field-control::placeholder {
            color: var(--gray-600);
        }

        select.field-control {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 8px center / 20px no-repeat !important;
            padding-right: 36px;
        }

        select.field-control:disabled {
            background: var(--gray-50) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 8px center / 20px no-repeat !important;
            color: var(--gray-600);
        }

        .field-textarea {
            height: auto;
            min-height: 80px;
            padding: 10px 12px;
            resize: vertical;
        }

        .field-with-prefix,
        .field-with-suffix {
            position: relative;
            display: flex;
            align-items: center;
        }

        .field-prefix,
        .field-suffix {
            position: absolute;
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-700);
            pointer-events: none;
        }

        .field-prefix {
            left: 12px;
        }

        .field-suffix {
            right: 12px;
        }

        .field-with-prefix .field-control {
            padding-left: 28px;
        }

        .field-with-suffix .field-control {
            padding-right: 32px;
        }

        /* Toggle IVA */
        .iva-control-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .iva-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .iva-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .iva-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-400);
            transition: 0.3s;
            border-radius: 24px;
        }

        .iva-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        .iva-toggle input:checked + .iva-toggle-slider {
            background-color: var(--primary);
        }

        .iva-toggle input:checked + .iva-toggle-slider:before {
            transform: translateX(20px);
        }

        .iva-control-wrapper .field-with-suffix {
            flex: 1;
        }

        #iva_input_wrapper.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* ============================================
           SECCIÓN AGREGAR MATERIAL
           ============================================ */
        .add-material-section {
            border-bottom: 1px solid var(--gray-100);
        }

        .add-material-grid {
            padding: 20px;
        }

        .material-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .material-row:last-child {
            margin-bottom: 0;
        }

        .material-row-values {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: flex-end;
        }

        @media (max-width: 991px) {
            .material-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .material-row-values {
                grid-template-columns: 1fr 1fr;
            }

            .field-action {
                grid-column: span 2;
            }
        }

        .material-field {
            min-width: 0;
        }

        .conversion-hint {
            font-family: var(--font-sans);
            font-size: 12px;
            color: var(--primary);
            margin-top: 4px;
            font-weight: 500;
            display: none;
        }

        .subtotal-display {
            font-family: var(--font-sans);
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
            height: 40px;
            display: flex;
            align-items: center;
        }

        .btn-add {
            height: 40px;
            padding: 0 24px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            background: var(--success);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-add:hover:not(:disabled) {
            background: var(--success-hover);
        }

        .btn-add:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
        }

        /* ============================================
           TABLA DE ITEMS
           ============================================ */
        .items-section {
            display: flex;
            flex-direction: column;
        }

        .items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
        }

        .items-title {
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .items-count {
            font-family: var(--font-sans);
            font-size: 13px;
            color: var(--gray-500);
            background: var(--gray-100);
            padding: 4px 10px;
            border-radius: 12px;
        }

        .items-table-wrapper {
            overflow-x: auto;
            max-height: 320px;
            overflow-y: auto;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead {
            position: sticky;
            top: 0;
            background: var(--gray-50);
            z-index: 10;
        }

        .items-table th {
            font-family: var(--font-sans);
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .items-table th.th-num {
            width: 50px;
            text-align: center;
        }

        .items-table th.th-qty,
        .items-table th.th-unit {
            text-align: center;
        }

        .items-table th.th-price,
        .items-table th.th-total {
            text-align: right;
        }

        .items-table th.th-action {
            width: 50px;
        }

        .items-table td {
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-700);
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .items-table tbody tr:hover {
            background: var(--gray-50);
        }

        .item-material-name {
            font-weight: 500;
            color: var(--gray-900);
        }

        .item-category {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .item-variant-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-700);
        }

        /* Inputs editables en tabla */
        .input-editable {
            width: 80px;
            height: 32px;
            padding: 4px 8px;
            font-family: var(--font-sans);
            font-size: 13px;
            color: var(--gray-900);
            background: #fff;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            text-align: right;
            transition: all 0.15s ease;
        }

        .input-editable:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .input-editable:hover {
            border-color: var(--gray-400);
        }

        .input-editable-qty {
            width: 70px;
            text-align: center;
        }

        .input-editable-price {
            width: 100px;
            padding-left: 20px !important;
        }

        .input-price-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .input-price-symbol {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--gray-500);
            pointer-events: none;
        }

        .item-conversion {
            font-size: 12px;
            color: var(--primary);
            margin-top: 2px;
        }

        .item-unit-cost {
            font-size: 11px;
            color: var(--gray-500);
        }

        .td-center {
            text-align: center;
        }

        .td-right {
            text-align: right;
        }

        .td-total {
            font-weight: 600;
            color: var(--gray-900);
        }

        .btn-remove {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-radius: 6px;
            color: var(--gray-400);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-remove:hover {
            background: #fef2f2;
            color: var(--danger);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 20px !important;
        }

        .empty-icon {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        .empty-text {
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 4px;
        }

        .empty-hint {
            font-family: var(--font-sans);
            font-size: 13px;
            color: var(--gray-400);
        }

        /* ============================================
           TOTALES
           ============================================ */
        .totals-section {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            padding: 16px 20px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .totals-label {
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-600);
        }

        .totals-value {
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-800);
        }

        .totals-final {
            border-top: 1px solid var(--gray-300);
            margin-top: 8px;
            padding-top: 16px;
        }

        .totals-final .totals-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .totals-final .totals-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        /* ============================================
           SCROLLBAR PERSONALIZADA
           ============================================ */
        .items-table-wrapper::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .items-table-wrapper::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        .items-table-wrapper::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }

        .items-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .purchase-header {
                padding: 16px 20px;
                flex-wrap: wrap;
            }

            .purchase-header>div {
                flex-wrap: wrap;
                gap: 16px;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .purchase-content {
                padding: 16px;
            }

            .purchase-title {
                font-size: 20px;
            }

            .panel-main {
                margin-left: 0;
                margin-top: 16px;
            }

            .field-row {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================
           BOTÓN BUSCAR MATERIAL
           ============================================ */
        .btn-search-material {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
        }

        .btn-search-material:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
        }

        .btn-search-material:active {
            transform: translateY(0);
        }

        /* ============================================
           MODAL BUSCAR MATERIAL
           ============================================ */
        #modalSearchMaterial .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-material {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: #fff;
            padding: 16px 24px;
            border: none;
        }

        .modal-header-material .modal-title {
            font-family: var(--font-sans);
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header-material .close {
            color: #fff;
            opacity: 0.8;
            text-shadow: none;
            font-size: 28px;
            line-height: 1;
        }

        .modal-header-material .close:hover {
            opacity: 1;
        }

        /* Filtros */
        .modal-filters {
            background: var(--gray-800);
            padding: 16px 24px;
            border-bottom: 1px solid var(--gray-700);
        }

        .filter-row {
            display: flex;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-field {
            flex: 0 0 200px;
        }

        .filter-field-search {
            flex: 1;
        }

        .filter-field-info {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
        }

        .filter-label {
            display: block;
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .filter-control {
            width: 100%;
            height: 42px;
            padding: 0 14px;
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-900);
            background: #fff;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 14px;
        }

        .filter-search {
            padding-left: 40px;
        }

        .results-count {
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            padding: 8px 14px;
            border-radius: 20px;
        }

        /* Tabla del modal */
        .modal-table-wrapper {
            max-height: 350px;
            overflow-y: auto;
            overflow-x: auto;
        }

        .modal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: var(--gray-800);
        }

        .modal-table th {
            font-family: var(--font-sans);
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            white-space: nowrap;
        }

        .modal-table th.th-select {
            width: 50px;
            text-align: center;
        }

        .modal-table th.th-stock,
        .modal-table th.th-unit {
            text-align: center;
        }

        .modal-table td {
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--gray-700);
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .modal-table tbody tr {
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .modal-table tbody tr:hover {
            background: #f0f9ff;
        }

        .modal-table tbody tr.selected {
            background: #dbeafe;
            border-left: 3px solid var(--primary);
        }

        .modal-table tbody tr.selected td {
            color: var(--gray-900);
            font-weight: 500;
        }

        .modal-table .td-center {
            text-align: center;
        }

        .modal-table .material-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-table .material-composition {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .modal-table .variant-color {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .modal-table .variant-badge {
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            background: var(--gray-700);
            color: #fff;
            border-radius: 4px;
        }

        .modal-table .sku-code {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 12px;
            color: #fff;
            background: var(--gray-700);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .modal-table .stock-value {
            font-weight: 600;
        }

        .modal-table .stock-low {
            color: #dc2626;
        }

        .modal-table .stock-ok {
            color: #059669;
        }

        .modal-table .unit-select {
            min-width: 180px;
            height: 36px;
            font-size: 13px;
            padding: 0 10px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            background: #fff;
        }

        .modal-table .category-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* Radio button personalizado */
        .modal-radio {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Empty state */
        .modal-empty-state {
            text-align: center;
            padding: 60px 20px !important;
        }

        .modal-empty-state .empty-icon {
            font-size: 48px;
            color: var(--gray-600);
            margin-bottom: 16px;
        }

        .modal-empty-state .empty-text {
            font-family: var(--font-sans);
            font-size: 15px;
            color: var(--gray-700);
        }

        /* Loading */
        .modal-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--primary);
            font-family: var(--font-sans);
            font-size: 14px;
            z-index: 20;
        }

        /* Footer del modal */
        .modal-footer-material {
            background: var(--gray-800);
            border-top: 1px solid var(--gray-700);
            padding: 16px 24px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 24px;
            flex-wrap: wrap;
        }

        .footer-selected-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .selected-label {
            font-family: var(--font-sans);
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
        }

        .selected-name {
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 700;
            color: #fff;
        }

        .selected-variant {
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            padding: 4px 10px;
            border-radius: 4px;
        }

        .selected-variant:empty {
            display: none;
        }

        .footer-inputs {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer-input-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .footer-input-group label {
            font-family: var(--font-sans);
            font-size: 10px;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .input-with-unit {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-with-currency {
            display: flex;
            align-items: center;
            position: relative;
        }

        .input-currency {
            position: absolute;
            left: 12px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-600);
        }

        .input-unit {
            font-family: var(--font-sans);
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            min-width: 30px;
        }

        .footer-control {
            width: 100px;
            height: 40px;
            padding: 0 12px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-900);
            background: #fff;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            text-align: right;
        }

        .footer-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .footer-price {
            padding-left: 28px;
            width: 110px;
        }

        .footer-subtotal {
            font-family: var(--font-sans);
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            height: 40px;
            display: flex;
            align-items: center;
            min-width: 90px;
        }

        .footer-conversion {
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 600;
            color: #10b981;
            height: 40px;
            display: flex;
            align-items: center;
            min-width: 80px;
        }

        .footer-buttons-group {
            flex-direction: row;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-modal-add {
            height: 40px;
            padding: 0 24px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: var(--success);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modal-add:hover:not(:disabled) {
            background: var(--success-hover);
        }

        .btn-modal-add:disabled {
            background: var(--gray-600);
            cursor: not-allowed;
        }

        .footer-actions {
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }

        .btn-modal-close {
            height: 40px;
            padding: 0 20px;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            background: var(--gray-600);
            border: 1px solid var(--gray-500);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modal-close:hover {
            background: var(--gray-500);
            border-color: var(--gray-400);
        }

        /* Modal body position relative para loading */
        #modalSearchMaterial .modal-body {
            position: relative;
            min-height: 400px;
        }

        /* Modal tamaño fijo - no se comprime durante búsqueda */
        #modalSearchMaterial .modal-dialog {
            max-width: 1140px;
            width: 95%;
        }

        #modalSearchMaterial .modal-table-wrapper {
            min-height: 300px;
        }

        /* ============================================
           RESPONSIVE - TABLET Y MÓVIL
           ============================================ */
        @media (max-width: 991.98px) {
            #modalSearchMaterial .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
            }

            .filter-row {
                flex-wrap: wrap;
            }

            .filter-field {
                flex: 0 0 48%;
            }

            .filter-field-search {
                flex: 0 0 100%;
                order: -1;
                margin-bottom: 10px;
            }

            .filter-field-info {
                flex: 0 0 100%;
                justify-content: center;
                margin-top: 10px;
            }

            .footer-row {
                flex-wrap: wrap;
            }

            .footer-inputs {
                flex-wrap: wrap;
            }

            .footer-input-group {
                flex: 0 0 auto;
                min-width: 100px;
            }
        }

        @media (max-width: 767.98px) {
            #modalSearchMaterial .modal-dialog {
                max-width: 100%;
                margin: 0;
                min-height: 100vh;
            }

            #modalSearchMaterial .modal-content {
                border-radius: 0;
                min-height: 100vh;
            }

            #modalSearchMaterial .modal-body {
                min-height: calc(100vh - 280px);
            }

            .modal-filters {
                padding: 12px 16px;
            }

            .filter-row {
                flex-direction: column;
                gap: 12px;
            }

            .filter-field,
            .filter-field-search {
                flex: 0 0 100%;
            }

            .modal-table-wrapper {
                max-height: calc(100vh - 380px);
                min-height: 200px;
            }

            .modal-table th,
            .modal-table td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .modal-table th {
                font-size: 10px;
            }

            /* Ocultar columna SKU en móvil */
            .modal-table .th-sku,
            .modal-table td:nth-child(5) {
                display: none;
            }

            .modal-footer-material {
                padding: 12px 16px;
                overflow-x: auto;
            }

            .footer-row {
                min-width: max-content;
            }

            .footer-input-group input {
                height: 38px;
                font-size: 14px;
                min-width: 80px;
            }

            .footer-selected-info {
                font-size: 12px;
                min-width: 150px;
            }

            .btn-modal-add,
            .btn-modal-close {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 479.98px) {
            .modal-header-material {
                padding: 12px 16px;
            }

            .modal-header-material .modal-title {
                font-size: 16px;
            }

            .filter-control {
                height: 38px;
                font-size: 13px;
            }

            .modal-table-wrapper {
                max-height: calc(100vh - 420px);
            }

            .footer-subtotal,
            .footer-conversion {
                font-size: 14px;
                height: 38px;
                min-width: 70px;
            }

            .results-count {
                font-size: 12px;
                padding: 6px 12px;
            }
        }

        /* Safe area para dispositivos con notch (iPhone X+) */
        @supports (padding: max(0px)) {
            @media (max-width: 767.98px) {
                #modalSearchMaterial .modal-content {
                    padding-top: max(0px, env(safe-area-inset-top));
                    padding-bottom: max(0px, env(safe-area-inset-bottom));
                    padding-left: max(0px, env(safe-area-inset-left));
                    padding-right: max(0px, env(safe-area-inset-right));
                }
            }
        }

        /* Touch-friendly para móvil */
        @media (hover: none) and (pointer: coarse) {
            .modal-table tbody tr {
                min-height: 48px;
            }

            .modal-table td {
                padding: 14px 12px;
            }

            .filter-control,
            .unit-select {
                min-height: 44px;
            }

            .btn-modal-add,
            .btn-modal-close {
                min-height: 44px;
            }
        }

        /* Scrollbar del modal */
        .modal-table-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .modal-table-wrapper::-webkit-scrollbar-track {
            background: var(--gray-700);
        }

        .modal-table-wrapper::-webkit-scrollbar-thumb {
            background: var(--gray-500);
            border-radius: 4px;
        }

        .modal-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--gray-600);
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // Items existentes (cargados desde el servidor)
            let items = @json($itemsForJs);
            let itemIndex = items.length > 0 ? Math.max(...items.map(i => i.index || 0)) + 1 : 0;

            const csrfToken = '{{ csrf_token() }}';

            // Cache selectores
            const $btnSubmit = $('#btn_submit');
            const $itemsBody = $('#items_body');
            const $itemsTotals = $('#items_totals');
            const $itemsCount = $('#items_count');
            const $hiddenContainer = $('#hidden_items_container');
            const $taxRate = $('#tax_rate');
            const $discountAmount = $('#discount_amount');

            // Agregar index a items existentes si no lo tienen
            items = items.map((item, idx) => ({
                ...item,
                index: item.index !== undefined ? item.index : idx
            }));

            // Renderizar items existentes
            renderItems();
            updateTotals();

            // Renderizar tabla de items
            function renderItems() {
                if (items.length === 0) {
                    $itemsBody.html(`
                        <tr id="no_items_row">
                            <td colspan="8" class="empty-state">
                                <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                                <div class="empty-text">La orden está vacía</div>
                                <div class="empty-hint">Agregue materiales usando el formulario superior</div>
                            </td>
                        </tr>
                    `);
                    $itemsTotals.hide();
                    $btnSubmit.prop('disabled', true);
                    $itemsCount.text('0 items');
                    return;
                }

                let html = '';
                items.forEach((item, idx) => {
                    const variantDisplay = item.variant_color || 'Sin color';
                    const conversionInfo = item.conversion_factor != 1 ?
                        `<div class="item-conversion" id="conv_${item.index}">= ${item.converted_quantity.toFixed(2)} ${item.base_unit_symbol}</div>` :
                        '';
                    const unitCostInfo = item.conversion_factor != 1 ?
                        `<div class="item-unit-cost" id="ucost_${item.index}">$${item.converted_unit_cost.toFixed(4)}/${item.base_unit_symbol}</div>` :
                        '';

                    html += `
                        <tr class="item-row" data-index="${item.index}">
                            <td class="td-center">${idx + 1}</td>
                            <td>
                                <div class="item-material-name">${item.material_name}</div>
                                <div class="item-category">${item.category_name}</div>
                            </td>
                            <td>
                                <span class="item-variant-name">${variantDisplay}</span>
                            </td>
                            <td class="td-center">
                                <input type="number" class="input-editable input-editable-qty input-qty"
                                    data-index="${item.index}"
                                    value="${item.quantity}"
                                    min="0.01" step="0.01">
                                ${conversionInfo}
                            </td>
                            <td class="td-center">${item.unit_symbol}</td>
                            <td class="td-right">
                                <div class="input-price-wrapper">
                                    <span class="input-price-symbol">$</span>
                                    <input type="number" class="input-editable input-editable-price input-price"
                                        data-index="${item.index}"
                                        value="${item.unit_price}"
                                        min="0.01" step="0.01">
                                </div>
                                ${unitCostInfo}
                            </td>
                            <td class="td-right td-total" id="total_${item.index}">$${item.subtotal.toFixed(2)}</td>
                            <td class="td-center">
                                <button type="button" class="btn-remove btn-remove-item" data-index="${item.index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $itemsBody.html(html);
                $itemsTotals.show();
                $btnSubmit.prop('disabled', false);
                $itemsCount.text(items.length + (items.length === 1 ? ' item' : ' items'));

                generateHiddenInputs();
            }

            // Generar inputs hidden para envío
            function generateHiddenInputs() {
                let html = '';
                items.forEach((item, idx) => {
                    html += `
                        <input type="hidden" name="items[${idx}][material_variant_id]" value="${item.variant_id}">
                        <input type="hidden" name="items[${idx}][unit_id]" value="${item.unit_id}">
                        <input type="hidden" name="items[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                    `;
                });
                $hiddenContainer.html(html);
            }

            // Eliminar item
            $(document).on('click', '.btn-remove-item', function() {
                const index = $(this).data('index');
                items = items.filter(i => i.index !== index);
                renderItems();
                updateTotals();
            });

            // Editar cantidad inline
            $(document).on('input', '.input-qty', function() {
                const index = $(this).data('index');
                const newQty = parseFloat($(this).val()) || 0;

                const item = items.find(i => i.index === index);
                if (item && newQty > 0) {
                    item.quantity = newQty;
                    item.subtotal = item.quantity * item.unit_price;
                    item.converted_quantity = item.quantity * item.conversion_factor;
                    item.converted_unit_cost = item.subtotal / item.converted_quantity;

                    $(`#total_${index}`).text('$' + item.subtotal.toFixed(2));

                    if (item.conversion_factor != 1) {
                        $(`#conv_${index}`).text(
                            `= ${item.converted_quantity.toFixed(2)} ${item.base_unit_symbol}`);
                        $(`#ucost_${index}`).text(
                            `$${item.converted_unit_cost.toFixed(4)}/${item.base_unit_symbol}`);
                    }

                    generateHiddenInputs();
                    updateTotals();
                }
            });

            // Editar precio inline
            $(document).on('input', '.input-price', function() {
                const index = $(this).data('index');
                const newPrice = parseFloat($(this).val()) || 0;

                const item = items.find(i => i.index === index);
                if (item && newPrice > 0) {
                    item.unit_price = newPrice;
                    item.subtotal = item.quantity * item.unit_price;
                    item.converted_unit_cost = item.subtotal / item.converted_quantity;

                    $(`#total_${index}`).text('$' + item.subtotal.toFixed(2));

                    if (item.conversion_factor != 1) {
                        $(`#ucost_${index}`).text(
                            `$${item.converted_unit_cost.toFixed(4)}/${item.base_unit_symbol}`);
                    }

                    generateHiddenInputs();
                    updateTotals();
                }
            });

            // Formatear número con comas para miles
            function formatWithCommas(num, decimals = 2) {
                return num.toLocaleString('es-MX', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            // Actualizar totales
            function updateTotals() {
                const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
                const ivaEnabled = $('#iva_enabled').is(':checked');
                const taxRate = ivaEnabled ? (parseFloat($taxRate.val()) || 0) : 0;
                const discount = parseFloat($discountAmount.val()) || 0;
                const tax = subtotal * (taxRate / 100);
                const total = subtotal + tax - discount;

                $('#total_subtotal').text('$' + formatWithCommas(subtotal));
                $('#tax_rate_display').text(taxRate.toFixed(0));
                $('#total_tax').text('$' + formatWithCommas(tax));

                if (discount > 0) {
                    $('#discount_row').show();
                    $('#total_discount').text('-$' + formatWithCommas(discount));
                } else {
                    $('#discount_row').hide();
                }

                $('#total_final').text('$' + formatWithCommas(Math.max(0, total)));
            }

            // Toggle IVA
            $('#iva_enabled').on('change', function() {
                const isEnabled = $(this).is(':checked');
                const $wrapper = $('#iva_input_wrapper');

                if (isEnabled) {
                    $wrapper.removeClass('disabled');
                    // Restaurar valor por defecto si está en 0
                    if (parseFloat($taxRate.val()) === 0) {
                        $taxRate.val(16);
                    }
                } else {
                    $wrapper.addClass('disabled');
                }
                updateTotals();
            });

            // Inicializar estado del toggle
            if (!$('#iva_enabled').is(':checked')) {
                $('#iva_input_wrapper').addClass('disabled');
            }

            // Cambio en tasa de impuesto o descuento
            $taxRate.on('input', updateTotals);
            $discountAmount.on('input', updateTotals);

            // Validación antes de enviar
            $('#purchaseForm').on('submit', function(e) {
                if (items.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin items',
                        text: 'Debe agregar al menos un item a la orden de compra',
                        confirmButtonColor: '#f59e0b'
                    });
                    return false;
                }

                if (!$('#proveedor_id').val()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Proveedor requerido',
                        text: 'Debe seleccionar un proveedor para la orden',
                        confirmButtonColor: '#f59e0b'
                    });
                    return false;
                }

                return true;
            });

            // =====================================================
            // MODAL: Buscar Material
            // =====================================================
            const $modalSearch = $('#modalSearchMaterial');
            const $modalCategory = $('#modal_category');
            const $modalSearchInput = $('#modal_search');
            const $modalResultsCount = $('#modal_results_count');
            const $modalMaterialsBody = $('#modal_materials_body');
            const $modalLoading = $('#modal_loading');
            const $modalFooterRow = $('#modal_footer_row');
            const $modalSelectedName = $('#modal_selected_name');
            const $modalSelectedVariant = $('#modal_selected_variant');
            const $modalQuantity = $('#modal_quantity');
            const $modalPrice = $('#modal_price');
            const $modalSubtotal = $('#modal_subtotal');
            const $modalUnitSymbol = $('#modal_unit_symbol');
            const $modalBtnAdd = $('#modal_btn_add');

            let modalSearchTimeout = null;
            let modalSelectedItem = null;
            let modalCurrentPage = 1;
            let modalHasMore = false;
            let modalIsLoading = false;
            let modalTotalCount = 0;

            // Abrir modal
            $('#btn_open_material_modal').on('click', function() {
                resetModalState();
                $modalSearch.modal('show');
                // Cargar primera página
                loadModalMaterials(1, false);
            });

            // Cambio de categoría - reiniciar búsqueda
            $modalCategory.on('change', function() {
                loadModalMaterials(1, false);
            });

            // Búsqueda con debounce (server-side)
            $modalSearchInput.on('input', function() {
                clearTimeout(modalSearchTimeout);
                modalSearchTimeout = setTimeout(function() {
                    loadModalMaterials(1, false);
                }, 300); // 300ms debounce para búsqueda server-side
            });

            // Scroll infinito
            $('.modal-table-wrapper').on('scroll', function() {
                if (modalIsLoading || !modalHasMore) return;

                const $wrapper = $(this);
                const scrollTop = $wrapper.scrollTop();
                const innerHeight = $wrapper.innerHeight();
                const scrollHeight = this.scrollHeight;

                // Cargar más cuando estemos a 100px del final
                if (scrollTop + innerHeight >= scrollHeight - 100) {
                    loadModalMaterials(modalCurrentPage + 1, true);
                }
            });

            // Cargar materiales via AJAX (con paginación)
            function loadModalMaterials(page, append) {
                if (modalIsLoading) return;

                const categoryId = $modalCategory.val();
                const search = $modalSearchInput.val().trim();

                modalIsLoading = true;

                if (!append) {
                    // Limpiar selección anterior al hacer nueva búsqueda
                    clearModalSelection();

                    // Mostrar loading solo en la tabla, no en todo el modal
                    $modalMaterialsBody.html(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                                <div style="margin-top: 8px; color: var(--gray-600);">Cargando materiales...</div>
                            </td>
                        </tr>
                    `);
                    modalCurrentPage = 1;
                }

                $.ajax({
                    url: '/admin/purchases/ajax/search-materials',
                    method: 'GET',
                    data: {
                        category_id: categoryId,
                        search: search,
                        page: page
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        modalIsLoading = false;

                        if (!response.success) {
                            showModalError('Error al cargar materiales');
                            return;
                        }

                        modalCurrentPage = response.page;
                        modalHasMore = response.has_more;
                        modalTotalCount = response.total;

                        if (append) {
                            // Agregar más filas
                            appendModalResults(response.results);
                        } else {
                            // Reemplazar todo
                            renderModalResults(response.results);
                        }

                        // Actualizar contador
                        $modalResultsCount.text(modalTotalCount + ' resultado' + (modalTotalCount !==
                            1 ? 's' : ''));
                    },
                    error: function(xhr) {
                        modalIsLoading = false;
                        showModalError('Error de conexión al cargar materiales');
                    }
                });
            }

            // Renderizar resultados en la tabla (reemplaza todo)
            function renderModalResults(results) {
                // Inicializar array de resultados
                $modalMaterialsBody.data('results', results);

                if (results.length === 0) {
                    $modalMaterialsBody.html(`
                        <tr id="modal_empty_row">
                            <td colspan="7" class="modal-empty-state">
                                <div class="empty-icon"><i class="fas fa-search"></i></div>
                                <div class="empty-text">No se encontraron materiales</div>
                            </td>
                        </tr>
                    `);
                    return;
                }

                const html = buildRowsHtml(results, 0);
                $modalMaterialsBody.html(html);
            }

            // Agregar más resultados (scroll infinito)
            function appendModalResults(results) {
                if (results.length === 0) return;

                // Obtener resultados existentes y agregar nuevos
                let existingResults = $modalMaterialsBody.data('results') || [];
                const startIndex = existingResults.length;
                existingResults = existingResults.concat(results);
                $modalMaterialsBody.data('results', existingResults);

                // Agregar filas al DOM
                const html = buildRowsHtml(results, startIndex);
                $modalMaterialsBody.append(html);
            }

            // Construir HTML de filas
            function buildRowsHtml(results, startIndex) {
                let html = '';
                results.forEach(function(item, i) {
                    const index = startIndex + i;
                    const stockClass = item.is_low_stock ? 'stock-low' : 'stock-ok';
                    const variantDisplay = item.variant_color ?
                        `<span class="variant-badge">${item.variant_color}</span>` :
                        '<span style="color: var(--gray-600);">-</span>';

                    // Crear select de presentaciones
                    let unitOptionsHtml = '<option value="">Seleccionar...</option>';
                    item.unit_options.forEach(function(unit) {
                        unitOptionsHtml +=
                            `<option value="${unit.id}" data-factor="${unit.conversion_factor}" data-symbol="${unit.symbol}" data-name="${unit.name}">${unit.display}</option>`;
                    });

                    html += `
                        <tr class="modal-material-row" data-index="${index}">
                            <td class="td-center">
                                <input type="radio" name="modal_select" class="modal-radio modal-select-radio" data-index="${index}">
                            </td>
                            <td>
                                <span class="category-badge">${item.category_name}</span>
                            </td>
                            <td>
                                <div class="material-name">${item.material_name}</div>
                                ${item.material_composition ? '<div class="material-composition">' + item.material_composition + '</div>' : ''}
                            </td>
                            <td>
                                <div class="variant-color">${variantDisplay}</div>
                            </td>
                            <td>
                                <code class="sku-code">${item.variant_sku}</code>
                            </td>
                            <td class="td-center">
                                <span class="stock-value ${stockClass}">${formatNumber(item.current_stock, 2)}</span>
                                <span style="font-size: 11px; color: var(--gray-600);">${item.base_unit_symbol}</span>
                            </td>
                            <td class="td-center">
                                <select class="unit-select modal-unit-select" data-index="${index}" disabled>
                                    ${unitOptionsHtml}
                                </select>
                            </td>
                        </tr>
                    `;
                });
                return html;
            }

            // Seleccionar fila
            $(document).on('click', '.modal-material-row', function(e) {
                // No seleccionar si se hizo clic en el select
                if ($(e.target).is('select') || $(e.target).is('option')) {
                    return;
                }

                const $row = $(this);
                const index = $row.data('index');
                const results = $modalMaterialsBody.data('results');
                const item = results[index];

                // Marcar fila como seleccionada
                $('.modal-material-row').removeClass('selected');
                $row.addClass('selected');
                $row.find('.modal-select-radio').prop('checked', true);

                // Habilitar el select de unidades de esta fila
                $('.modal-unit-select').prop('disabled', true).val('');
                $row.find('.modal-unit-select').prop('disabled', false);

                // Guardar item seleccionado parcialmente
                modalSelectedItem = {
                    ...item,
                    unit_id: null,
                    unit_name: '',
                    unit_symbol: '',
                    conversion_factor: 1
                };

                // Ocultar footer hasta que se seleccione presentación
                $modalFooterRow.hide();
                $('#footer_actions_default').show();
                $modalBtnAdd.prop('disabled', true);
            });

            // Seleccionar presentación
            $(document).on('change', '.modal-unit-select', function() {
                const $select = $(this);
                const unitId = $select.val();
                const $selected = $select.find('option:selected');

                if (!unitId || !modalSelectedItem) {
                    $modalFooterRow.hide();
                    $('#footer_actions_default').show();
                    $modalBtnAdd.prop('disabled', true);
                    return;
                }

                // Actualizar item seleccionado con la unidad
                modalSelectedItem.unit_id = unitId;
                modalSelectedItem.unit_name = $selected.data('name');
                modalSelectedItem.unit_symbol = $selected.data('symbol');
                modalSelectedItem.conversion_factor = parseFloat($selected.data('factor')) || 1;

                // Actualizar info del seleccionado
                $modalSelectedName.text(modalSelectedItem.material_name);
                // Solo mostrar variante si tiene color
                if (modalSelectedItem.variant_color && modalSelectedItem.variant_color !== 'Sin color') {
                    $modalSelectedVariant.text(modalSelectedItem.variant_color).show();
                } else {
                    $modalSelectedVariant.text('').hide();
                }

                // Mostrar footer row y ocultar botón cerrar por defecto
                $modalUnitSymbol.text(modalSelectedItem.unit_symbol || '-');
                $modalQuantity.val('');
                $modalPrice.val('');
                $modalSubtotal.text('$0.00');
                $modalFooterRow.show();
                $('#footer_actions_default').hide();
                $modalQuantity.focus();

                validateModalAddButton();
            });

            // Cambio de cantidad/precio en modal
            $modalQuantity.on('input', function() {
                calculateModalSubtotal();
                validateModalAddButton();
            });

            $modalPrice.on('input', function() {
                calculateModalSubtotal();
                validateModalAddButton();
            });

            // Calcular subtotal del modal y mostrar conversión
            function calculateModalSubtotal() {
                const qty = parseFloat($modalQuantity.val()) || 0;
                const price = parseFloat($modalPrice.val()) || 0;
                const subtotal = qty * price;
                $modalSubtotal.text('$' + formatNumber(subtotal, 2));

                // Calcular y mostrar conversión
                if (modalSelectedItem && qty > 0) {
                    const factor = modalSelectedItem.conversion_factor || 1;
                    const converted = qty * factor;
                    const baseSymbol = modalSelectedItem.base_unit_symbol || '';
                    $('#modal_conversion_info').text(formatNumber(converted, 2) + ' ' + baseSymbol);
                } else {
                    $('#modal_conversion_info').text('-');
                }
            }

            // Validar botón agregar del modal
            function validateModalAddButton() {
                const qty = parseFloat($modalQuantity.val()) || 0;
                const price = parseFloat($modalPrice.val()) || 0;
                const canAdd = modalSelectedItem &&
                    modalSelectedItem.unit_id &&
                    qty > 0 &&
                    price > 0;
                $modalBtnAdd.prop('disabled', !canAdd);
            }

            // Agregar item desde modal
            $modalBtnAdd.on('click', function() {
                if (!modalSelectedItem || !modalSelectedItem.unit_id) return;

                const qty = parseFloat($modalQuantity.val()) || 0;
                const price = parseFloat($modalPrice.val()) || 0;

                if (qty <= 0 || price <= 0) return;

                // Verificar duplicados
                const existingIndex = items.findIndex(i =>
                    i.variant_id == modalSelectedItem.variant_id &&
                    i.unit_id == modalSelectedItem.unit_id
                );

                if (existingIndex !== -1) {
                    // Sumar cantidad al existente
                    const existingItem = items[existingIndex];
                    const cantidadAnterior = existingItem.quantity;
                    existingItem.quantity += qty;
                    existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
                    existingItem.converted_quantity = existingItem.quantity * existingItem
                        .conversion_factor;
                    existingItem.converted_unit_cost = existingItem.subtotal / existingItem
                        .converted_quantity;

                    renderItems();
                    updateTotals();

                    Swal.fire({
                        icon: 'success',
                        title: 'Cantidad actualizada',
                        html: `<strong>${existingItem.material_name}</strong><br><br>` +
                            `Cantidad anterior: <strong>${cantidadAnterior.toFixed(2)} ${existingItem.unit_symbol}</strong><br>` +
                            `Se agregó: <strong>+${qty.toFixed(2)} ${modalSelectedItem.unit_symbol}</strong><br>` +
                            `Nueva cantidad: <strong>${existingItem.quantity.toFixed(2)} ${existingItem.unit_symbol}</strong>`,
                        confirmButtonColor: '#059669',
                        timer: 3000,
                        timerProgressBar: true
                    });

                    // Limpiar selección para agregar más
                    clearModalSelection();
                    return;
                }

                // Crear nuevo item
                const subtotal = qty * price;
                const converted_quantity = qty * modalSelectedItem.conversion_factor;
                const converted_unit_cost = subtotal / converted_quantity;

                const newItem = {
                    index: itemIndex,
                    category_name: modalSelectedItem.category_name,
                    material_name: modalSelectedItem.material_name,
                    variant_id: modalSelectedItem.variant_id,
                    variant_sku: modalSelectedItem.variant_sku,
                    variant_color: modalSelectedItem.variant_color,
                    unit_id: modalSelectedItem.unit_id,
                    unit_symbol: modalSelectedItem.unit_symbol,
                    quantity: qty,
                    unit_price: price,
                    conversion_factor: modalSelectedItem.conversion_factor,
                    converted_quantity: converted_quantity,
                    converted_unit_cost: converted_unit_cost,
                    base_unit_symbol: modalSelectedItem.base_unit_symbol,
                    subtotal: subtotal
                };

                items.push(newItem);
                itemIndex++;

                renderItems();
                updateTotals();

                Swal.fire({
                    icon: 'success',
                    title: 'Material agregado',
                    text: `${modalSelectedItem.material_name} - ${qty} ${modalSelectedItem.unit_symbol}`,
                    confirmButtonColor: '#059669',
                    timer: 2000,
                    timerProgressBar: true
                });

                // Limpiar selección para agregar más
                clearModalSelection();
            });

            // Limpiar selección del modal (pero mantener la lista)
            function clearModalSelection() {
                modalSelectedItem = null;
                $('.modal-material-row').removeClass('selected');
                $('.modal-select-radio').prop('checked', false);
                $('.modal-unit-select').prop('disabled', true).val('');
                $modalFooterRow.hide();
                $('#footer_actions_default').show();
                $modalQuantity.val('');
                $modalPrice.val('');
                $modalSubtotal.text('$0.00');
                $('#modal_conversion_info').text('-');
                $modalSelectedName.text('-');
                $modalSelectedVariant.text('').hide();
                $modalUnitSymbol.text('-');
                $modalBtnAdd.prop('disabled', true);
            }

            // Reset completo del modal
            function resetModalState() {
                modalSelectedItem = null;
                modalCurrentPage = 1;
                modalHasMore = false;
                modalIsLoading = false;
                modalTotalCount = 0;
                $modalCategory.val('');
                $modalSearchInput.val('');
                $modalResultsCount.text('0 resultados');
                $modalMaterialsBody.data('results', []);
                $modalMaterialsBody.html(`
                    <tr id="modal_empty_row">
                        <td colspan="7" class="modal-empty-state">
                            <div class="empty-icon"><i class="fas fa-boxes"></i></div>
                            <div class="empty-text">Cargando materiales...</div>
                        </td>
                    </tr>
                `);
                $modalFooterRow.hide();
                $('#footer_actions_default').show();
                $modalQuantity.val('');
                $modalPrice.val('');
                $modalSubtotal.text('$0.00');
                $modalBtnAdd.prop('disabled', true);
            }

            // Mostrar error en modal
            function showModalError(message) {
                $modalMaterialsBody.html(`
                    <tr>
                        <td colspan="7" class="modal-empty-state">
                            <div class="empty-icon"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                            <div class="empty-text text-danger">${message}</div>
                        </td>
                    </tr>
                `);
            }

            // Helper para formatear números (reutilizable)
            function formatNumber(num, decimals) {
                decimals = decimals !== undefined ? decimals : 2;
                return parseFloat(num).toLocaleString('es-MX', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }
        });
    </script>
@stop
