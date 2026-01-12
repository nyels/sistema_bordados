@extends('adminlte::page')

@section('title', 'Nueva Orden de Compra')

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

    <form method="POST" action="{{ route('admin.purchases.store') }}" id="purchaseForm">
        @csrf

        {{-- HEADER PROFESIONAL --}}
        <div class="purchase-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="purchase-title">Nueva Orden de Compra</h1>
                    <p class="purchase-subtitle">Complete los datos del proveedor y agregue los materiales</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.purchases.index') }}" class="btn-action btn-cancel">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-action btn-save" id="btn_submit" disabled>
                        <i class="fas fa-check"></i> Guardar Orden
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
                                            {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
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
                                        value="{{ old('ordered_at', date('Y-m-d')) }}">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Fecha Esperada</label>
                                    <input type="date" name="expected_at" class="field-control"
                                        value="{{ old('expected_at') }}">
                                </div>
                            </div>

                            {{-- IVA y Referencia --}}
                            <div class="field-row">
                                <div class="field-group">
                                    <label class="field-label">IVA</label>
                                    <div class="field-with-suffix">
                                        <input type="number" name="tax_rate" id="tax_rate" class="field-control"
                                            value="{{ old('tax_rate', 16) }}" min="0" max="100" step="0.01">
                                        <span class="field-suffix">%</span>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Referencia</label>
                                    <input type="text" name="reference" class="field-control"
                                        value="{{ old('reference') }}" maxlength="100" placeholder="Factura, pedido...">
                                </div>
                            </div>

                            {{-- Descuento --}}
                            <div class="field-group">
                                <label class="field-label">Descuento Global</label>
                                <div class="field-with-prefix">
                                    <span class="field-prefix">$</span>
                                    <input type="number" name="discount_amount" id="discount_amount" class="field-control"
                                        value="{{ old('discount_amount', 0) }}" min="0" step="0.01">
                                </div>
                            </div>

                            {{-- Notas --}}
                            <div class="field-group">
                                <label class="field-label">Notas</label>
                                <textarea name="notes" class="field-control field-textarea" rows="3" maxlength="1000"
                                    placeholder="Notas internas de la orden...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PANEL DERECHO: MATERIALES --}}
                <div class="col-12 col-xl-8">
                    <div class="panel-section panel-main">
                        {{-- SECCIÓN AGREGAR MATERIAL --}}
                        <div class="add-material-section">
                            <div class="section-header">
                                <span class="section-icon text-success"><i class="fas fa-plus-circle"></i></span>
                                <span class="section-title">Agregar Material</span>
                            </div>

                            <div class="add-material-grid">
                                {{-- Fila 1: Selección de producto --}}
                                <div class="material-row">
                                    <div class="material-field">
                                        <label class="field-label-sm">Categoría</label>
                                        <select id="select_category" class="field-control">
                                            <option value="">Seleccionar...</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    data-base-unit="{{ $category->baseUnit->symbol ?? '' }}">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="material-field">
                                        <label class="field-label-sm">Material</label>
                                        <select id="select_material" class="field-control" disabled>
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
                                    <div class="material-field">
                                        <label class="field-label-sm">Variante</label>
                                        <select id="select_variant" class="field-control" disabled>
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
                                    <div class="material-field">
                                        <label class="field-label-sm">Unidad</label>
                                        <select id="select_unit" class="field-control" disabled>
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Fila 2: Cantidad, precio y agregar --}}
                                <div class="material-row material-row-values">
                                    <div class="material-field field-qty">
                                        <label class="field-label-sm">Cantidad</label>
                                        <input type="number" id="input_quantity" class="field-control" min="0.0001"
                                            step="0.01" placeholder="0.00" disabled>
                                        <div id="info_conversion_text" class="conversion-hint"></div>
                                        <input type="hidden" id="info_conversion">
                                    </div>
                                    <div class="material-field field-price">
                                        <label class="field-label-sm">Precio Unit.</label>
                                        <div class="field-with-prefix">
                                            <span class="field-prefix">$</span>
                                            <input type="number" id="input_price" class="field-control" min="0.0001"
                                                step="0.01" placeholder="0.00" disabled>
                                        </div>
                                    </div>
                                    <div class="material-field field-subtotal">
                                        <label class="field-label-sm">Subtotal</label>
                                        <div class="subtotal-display" id="info_subtotal_display">$0.00</div>
                                        <input type="hidden" id="info_subtotal">
                                    </div>
                                    <div class="material-field field-action">
                                        <button type="button" id="btn_add_item" class="btn-add" disabled>
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>
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
                                    <span class="totals-label">IVA (<span id="tax_rate_display">16</span>%)</span>
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
            border: 1px solid var(--gray-200);
            margin-bottom: 0;
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
            color: var(--gray-500);
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
            color: var(--gray-500);
        }

        .field-control::placeholder {
            color: var(--gray-400);
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
            color: var(--gray-500);
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
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // Cargar items desde old() si existen (después de error de validación)
            let items = @json($oldItemsForJs ?? []);
            let itemIndex = items.length;

            const csrfToken = '{{ csrf_token() }}';

            // Cache selectores
            const $selectCategory = $('#select_category');
            const $selectMaterial = $('#select_material');
            const $selectVariant = $('#select_variant');
            const $selectUnit = $('#select_unit');
            const $inputQuantity = $('#input_quantity');
            const $inputPrice = $('#input_price');
            const $infoConversion = $('#info_conversion');
            const $infoConversionText = $('#info_conversion_text');
            const $infoSubtotal = $('#info_subtotal');
            const $infoSubtotalDisplay = $('#info_subtotal_display');
            const $btnAddItem = $('#btn_add_item');
            const $btnSubmit = $('#btn_submit');
            const $itemsBody = $('#items_body');
            const $itemsTotals = $('#items_totals');
            const $itemsCount = $('#items_count');
            const $hiddenContainer = $('#hidden_items_container');
            const $taxRate = $('#tax_rate');
            const $discountAmount = $('#discount_amount');

            // Estado temporal del item
            let currentItem = {
                category_id: null,
                category_name: '',
                material_id: null,
                material_name: '',
                variant_id: null,
                variant_sku: '',
                variant_color: '',
                unit_id: null,
                unit_name: '',
                unit_symbol: '',
                conversion_factor: 1,
                base_unit_symbol: '',
                quantity: 0,
                unit_price: 0
            };

            // Cambio de categoría
            $selectCategory.on('change', function() {
                const categoryId = $(this).val();
                currentItem.category_id = categoryId;
                currentItem.category_name = $(this).find('option:selected').text();
                currentItem.base_unit_symbol = $(this).find('option:selected').data('base-unit') || '';

                resetFromMaterial();

                if (!categoryId) {
                    $selectMaterial.prop('disabled', true).html(
                        '<option value="">Seleccionar...</option>');
                    return;
                }

                $selectMaterial.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/materials/${categoryId}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            $selectMaterial.html('<option value="">Sin materiales</option>');
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin materiales',
                                text: 'No hay materiales registrados en esta categoría',
                                confirmButtonColor: '#2563eb'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar...</option>';
                        data.forEach(function(material) {
                            const composition = material.composition ?
                                ` (${material.composition})` : '';
                            options +=
                                `<option value="${material.id}">${material.name}${composition}</option>`;
                        });
                        $selectMaterial.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectMaterial.html('<option value="">Error</option>');
                        let msg = 'No se pudieron cargar los materiales.';
                        if (xhr.status === 404) msg = 'Ruta no encontrada (404).';
                        if (xhr.status === 500) msg = 'Error interno del servidor (500).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar materiales',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#dc2626'
                        });
                    }
                });
            });

            // Cambio de material
            $selectMaterial.on('change', function() {
                const materialId = $(this).val();
                currentItem.material_id = materialId;
                currentItem.material_name = $(this).find('option:selected').text();

                resetFromVariant();

                if (!materialId) {
                    $selectVariant.prop('disabled', true).html(
                        '<option value="">Seleccionar...</option>');
                    return;
                }

                $selectVariant.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/variants/${materialId}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            $selectVariant.html('<option value="">Sin variantes</option>');
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin variantes',
                                text: 'Este material no tiene variantes (SKU/colores) registradas',
                                confirmButtonColor: '#2563eb'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar...</option>';
                        data.forEach(function(variant) {
                            const displayName = variant.color || 'Sin color';
                            const stock = variant.current_stock ?
                                ` (Stock: ${parseFloat(variant.current_stock).toFixed(2)})` :
                                '';
                            options +=
                                `<option value="${variant.id}" data-sku="${variant.sku}" data-color="${variant.color || ''}">${displayName}${stock}</option>`;
                        });
                        $selectVariant.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectVariant.html('<option value="">Error</option>');
                        let msg = 'No se pudieron cargar las variantes.';
                        if (xhr.status === 404) msg = 'Ruta no encontrada (404).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar variantes',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#dc2626'
                        });
                    }
                });
            });

            // Cambio de variante
            $selectVariant.on('change', function() {
                const variantId = $(this).val();
                const $selected = $(this).find('option:selected');
                currentItem.variant_id = variantId;
                currentItem.variant_sku = $selected.data('sku') || '';
                currentItem.variant_color = $selected.data('color') || '';

                resetFromUnit();

                if (!variantId || !currentItem.material_id) {
                    $selectUnit.prop('disabled', true).html(
                        '<option value="">Seleccionar...</option>');
                    return;
                }

                $selectUnit.prop('disabled', true).html('<option value="">Cargando...</option>');

                $.ajax({
                    url: `/admin/purchases/ajax/units/${currentItem.material_id}`,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        if (!data.units || data.units.length === 0) {
                            $selectUnit.html('<option value="">Sin unidades</option>');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin unidades de compra',
                                text: 'Este material no tiene unidades de compra configuradas. Configure las conversiones primero.',
                                confirmButtonColor: '#f59e0b'
                            });
                            return;
                        }
                        let options = '<option value="">Seleccionar...</option>';
                        data.units.forEach(function(unit) {
                            const isBase = unit.is_base ? ' (Base)' : '';
                            options +=
                                `<option value="${unit.id}" data-factor="${unit.conversion_factor}" data-symbol="${unit.symbol}" data-name="${unit.name}">${unit.name} (${unit.symbol})${isBase}</option>`;
                        });
                        $selectUnit.html(options).prop('disabled', false);
                    },
                    error: function(xhr) {
                        $selectUnit.html('<option value="">Error</option>');
                        let msg = 'No se pudieron cargar las unidades de compra.';
                        if (xhr.status === 404) msg = 'Ruta no encontrada (404).';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar unidades',
                            text: xhr.responseJSON?.message || msg,
                            confirmButtonColor: '#dc2626'
                        });
                    }
                });
            });

            // Cambio de unidad
            $selectUnit.on('change', function() {
                const unitId = $(this).val();
                const $selected = $(this).find('option:selected');

                currentItem.unit_id = unitId;
                currentItem.unit_name = $selected.data('name') || '';
                currentItem.unit_symbol = $selected.data('symbol') || '';
                currentItem.conversion_factor = parseFloat($selected.data('factor')) || 1;

                if (unitId) {
                    $inputQuantity.prop('disabled', false);
                    $inputPrice.prop('disabled', false);
                    updateConversionInfo();
                } else {
                    $inputQuantity.prop('disabled', true).val('');
                    $inputPrice.prop('disabled', true).val('');
                    $infoConversion.val('');
                    $infoConversionText.hide();
                    $infoSubtotal.val('');
                    $infoSubtotalDisplay.text('$0.00');
                }

                validateAddButton();
            });

            // Cambio de cantidad o precio
            $inputQuantity.on('input', function() {
                currentItem.quantity = parseFloat($(this).val()) || 0;
                updateConversionInfo();
                calculateItemSubtotal();
                validateAddButton();
            });

            $inputPrice.on('input', function() {
                currentItem.unit_price = parseFloat($(this).val()) || 0;
                calculateItemSubtotal();
                validateAddButton();
            });

            // Actualizar info de conversión
            function updateConversionInfo() {
                if (currentItem.conversion_factor && currentItem.conversion_factor != 1 && currentItem.quantity >
                    0) {
                    const converted = currentItem.quantity * currentItem.conversion_factor;
                    const text =
                        `= ${converted.toFixed(2)} ${currentItem.base_unit_symbol}`;
                    $infoConversion.val(text);
                    $infoConversionText.text(text).show();
                } else {
                    $infoConversion.val('');
                    $infoConversionText.hide();
                }
            }

            // Calcular subtotal del item actual
            function calculateItemSubtotal() {
                const subtotal = currentItem.quantity * currentItem.unit_price;
                const formatted = '$' + subtotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                $infoSubtotal.val(subtotal);
                $infoSubtotalDisplay.text(formatted);
            }

            // Validar botón agregar
            function validateAddButton() {
                const canAdd = currentItem.variant_id &&
                    currentItem.unit_id &&
                    currentItem.quantity > 0 &&
                    currentItem.unit_price > 0;

                $btnAddItem.prop('disabled', !canAdd);
            }

            // Agregar item
            $btnAddItem.on('click', function() {
                // Verificar duplicados - si existe, sumar cantidad
                const existingIndex = items.findIndex(i =>
                    i.variant_id == currentItem.variant_id &&
                    i.unit_id == currentItem.unit_id
                );

                if (existingIndex !== -1) {
                    // Sumar cantidad al item existente
                    const existingItem = items[existingIndex];
                    const cantidadAnterior = existingItem.quantity;
                    existingItem.quantity += currentItem.quantity;
                    existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
                    existingItem.converted_quantity = existingItem.quantity * existingItem
                        .conversion_factor;
                    existingItem.converted_unit_cost = existingItem.subtotal / existingItem
                        .converted_quantity;

                    renderItems();
                    resetForm();
                    updateTotals();

                    const variantInfo = existingItem.variant_color ? ` (${existingItem.variant_color})` :
                        '';
                    Swal.fire({
                        icon: 'success',
                        title: 'Cantidad actualizada',
                        html: `<strong>${existingItem.material_name}${variantInfo}</strong><br><br>` +
                            `Cantidad anterior: <strong>${cantidadAnterior.toFixed(2)} ${existingItem.unit_symbol}</strong><br>` +
                            `Se agregó: <strong>+${currentItem.quantity.toFixed(2)} ${currentItem.unit_symbol}</strong><br>` +
                            `Nueva cantidad: <strong>${existingItem.quantity.toFixed(2)} ${existingItem.unit_symbol}</strong>`,
                        confirmButtonColor: '#059669',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    return;
                }

                const subtotal = currentItem.quantity * currentItem.unit_price;
                const converted_quantity = currentItem.quantity * currentItem.conversion_factor;
                const converted_unit_cost = subtotal / converted_quantity;

                const newItem = {
                    index: itemIndex,
                    category_name: currentItem.category_name,
                    material_name: currentItem.material_name,
                    variant_id: currentItem.variant_id,
                    variant_sku: currentItem.variant_sku,
                    variant_color: currentItem.variant_color,
                    unit_id: currentItem.unit_id,
                    unit_symbol: currentItem.unit_symbol,
                    quantity: currentItem.quantity,
                    unit_price: currentItem.unit_price,
                    conversion_factor: currentItem.conversion_factor,
                    converted_quantity: converted_quantity,
                    converted_unit_cost: converted_unit_cost,
                    base_unit_symbol: currentItem.base_unit_symbol,
                    subtotal: subtotal
                };

                items.push(newItem);
                itemIndex++;

                renderItems();
                resetForm();
                updateTotals();
            });

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

                // Generar inputs hidden
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

                    // Actualizar celda de total
                    $(`#total_${index}`).text('$' + item.subtotal.toFixed(2));

                    // Actualizar conversión si existe
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

                    // Actualizar celda de total
                    $(`#total_${index}`).text('$' + item.subtotal.toFixed(2));

                    // Actualizar costo por unidad base si existe
                    if (item.conversion_factor != 1) {
                        $(`#ucost_${index}`).text(
                            `$${item.converted_unit_cost.toFixed(4)}/${item.base_unit_symbol}`);
                    }

                    generateHiddenInputs();
                    updateTotals();
                }
            });

            // Actualizar totales
            function updateTotals() {
                const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
                const taxRate = parseFloat($taxRate.val()) || 0;
                const discount = parseFloat($discountAmount.val()) || 0;
                const tax = subtotal * (taxRate / 100);
                const total = subtotal + tax - discount;

                $('#total_subtotal').text('$' + subtotal.toFixed(2));
                $('#tax_rate_display').text(taxRate.toFixed(0));
                $('#total_tax').text('$' + tax.toFixed(2));

                if (discount > 0) {
                    $('#discount_row').show();
                    $('#total_discount').text('-$' + discount.toFixed(2));
                } else {
                    $('#discount_row').hide();
                }

                $('#total_final').text('$' + Math.max(0, total).toFixed(2));
            }

            // Cambio en tasa de impuesto o descuento
            $taxRate.on('input', updateTotals);
            $discountAmount.on('input', updateTotals);

            // Reset funciones
            function resetFromMaterial() {
                currentItem.material_id = null;
                currentItem.material_name = '';
                $selectMaterial.val('').prop('disabled', true);
                resetFromVariant();
            }

            function resetFromVariant() {
                currentItem.variant_id = null;
                currentItem.variant_sku = '';
                currentItem.variant_color = '';
                $selectVariant.val('').prop('disabled', true).html(
                    '<option value="">Seleccionar...</option>');
                resetFromUnit();
            }

            function resetFromUnit() {
                currentItem.unit_id = null;
                currentItem.unit_name = '';
                currentItem.unit_symbol = '';
                currentItem.conversion_factor = 1;
                $selectUnit.val('').prop('disabled', true).html(
                    '<option value="">Seleccionar...</option>');
                $inputQuantity.val('').prop('disabled', true);
                $inputPrice.val('').prop('disabled', true);
                $infoConversion.val('');
                $infoConversionText.hide();
                $infoSubtotal.val('');
                $infoSubtotalDisplay.text('$0.00');
                $btnAddItem.prop('disabled', true);
            }

            function resetForm() {
                currentItem = {
                    category_id: null,
                    category_name: '',
                    material_id: null,
                    material_name: '',
                    variant_id: null,
                    variant_sku: '',
                    variant_color: '',
                    unit_id: null,
                    unit_name: '',
                    unit_symbol: '',
                    conversion_factor: 1,
                    base_unit_symbol: '',
                    quantity: 0,
                    unit_price: 0
                };

                $selectCategory.val('');
                $selectMaterial.val('').prop('disabled', true).html(
                    '<option value="">Seleccionar...</option>');
                $selectVariant.val('').prop('disabled', true).html(
                    '<option value="">Seleccionar...</option>');
                $selectUnit.val('').prop('disabled', true).html(
                    '<option value="">Seleccionar...</option>');
                $inputQuantity.val('').prop('disabled', true);
                $inputPrice.val('').prop('disabled', true);
                $infoConversion.val('');
                $infoConversionText.hide();
                $infoSubtotal.val('');
                $infoSubtotalDisplay.text('$0.00');
                $btnAddItem.prop('disabled', true);
            }

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

            // Si hay items precargados (desde old() después de error), renderizarlos
            if (items.length > 0) {
                items = items.map((item, idx) => ({
                    ...item,
                    index: idx,
                    converted_unit_cost: item.subtotal / item.converted_quantity
                }));
                itemIndex = items.length;
                renderItems();
                updateTotals();
            }
        });
    </script>
@stop
