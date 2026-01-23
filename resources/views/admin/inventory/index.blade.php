@extends('adminlte::page')

@section('title', 'Inventario General')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-warehouse mr-2"></i>Inventario General</h1>
        <a href="{{ route('admin.inventory.reservations') }}" class="btn btn-info">
            <i class="fas fa-lock mr-1"></i> Ver Reservas Activas
        </a>
    </div>
@stop

@section('content')
    {{-- RESUMEN --}}
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totals['total_items'] }}</h3>
                    <p>Variantes Activas</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($totals['total_value'], 2) }}</h3>
                    <p>Valor Total Inventario</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $totals['low_stock'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
                <div class="inner">
                    <h3>{{ $totals['low_stock'] }}</h3>
                    <p>Bajo Stock Minimo</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totals['total_reserved'], 2) }}</h3>
                    <p>Unidades Reservadas</p>
                </div>
                <div class="icon"><i class="fas fa-lock"></i></div>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="row align-items-center">
                <div class="col-md-3">
                    <select name="category_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">-- Categoria --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="stock_status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">-- Estado Stock --</option>
                        <option value="ok" {{ request('stock_status') == 'ok' ? 'selected' : '' }}>OK</option>
                        <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Bajo Minimo
                        </option>
                        <option value="zero" {{ request('stock_status') == 'zero' ? 'selected' : '' }}>Sin Stock</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control"
                            placeholder="Buscar SKU, color, material..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA PRINCIPAL --}}
    <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">Listado de Materiales</span>
            {{-- Toggle de unidades --}}
            <div class="btn-group btn-group-sm unit-toggle" role="group">
                <button type="button" class="btn btn-primary active" data-unit-mode="consumption">
                    Consumo
                </button>
                <button type="button" class="btn btn-outline-primary" data-unit-mode="base">
                    Compra
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0 materials-table" style="font-size: 16px;">
                <thead style="background-color: #000; color: #fff;">
                    <tr>
                        <th>Material</th>
                        <th>SKU</th>
                        <th>Color</th>
                        <th class="text-right">Stock Fisico</th>
                        <th class="text-right">Reservado</th>
                        <th class="text-right">Disponible</th>
                        <th class="text-right">Valor</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center" style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        @php
                            $reserved = $variant->reserved_stock;
                            $available = $variant->available_stock;
                            $isLow = $variant->current_stock <= $variant->min_stock_alert;
                            // Datos para conversión de unidades
                            $material = $variant->material;
                            $conversionFactor = $material?->conversion_factor ?? 1;
                            $unitConsumption = $material?->consumptionUnit?->symbol
                                            ?? $material?->baseUnit?->symbol
                                            ?? '';
                            $unitBase = $material?->baseUnit?->symbol ?? $unitConsumption;
                        @endphp
                        <tr class="{{ $isLow ? 'table-warning' : '' }}">
                            <td>
                                <strong>{{ $variant->material?->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $variant->material?->category?->name ?? '' }}</small>
                            </td>
                            <td><code>{{ $variant->sku }}</code></td>
                            <td>
                                @if ($variant->color)
                                    <span class="badge badge-secondary">{{ $variant->color }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right unit-convertible"
                                data-qty="{{ $variant->current_stock }}"
                                data-factor="{{ $conversionFactor }}"
                                data-unit-consumption="{{ $unitConsumption }}"
                                data-unit-base="{{ $unitBase }}">
                                <span class="qty-value">{{ number_format($variant->current_stock, 2) }}</span>
                                <span class="unit-symbol">{{ $unitConsumption }}</span>
                            </td>
                            <td class="text-right">
                                @if ($reserved > 0)
                                    <span style="color: #fd7e14; font-weight: bold;" class="unit-convertible"
                                        data-qty="{{ $reserved }}"
                                        data-factor="{{ $conversionFactor }}"
                                        data-unit-consumption="{{ $unitConsumption }}"
                                        data-unit-base="{{ $unitBase }}">
                                        <span class="qty-value">{{ number_format($reserved, 2) }}</span>
                                        <i class="fas fa-lock ml-1"></i>
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <strong class="{{ $available <= $variant->min_stock_alert ? 'text-danger' : 'text-success' }} unit-convertible"
                                    data-qty="{{ $available }}"
                                    data-factor="{{ $conversionFactor }}"
                                    data-unit-consumption="{{ $unitConsumption }}"
                                    data-unit-base="{{ $unitBase }}">
                                    <span class="qty-value">{{ number_format($available, 2) }}</span>
                                    <span class="unit-symbol">{{ $unitConsumption }}</span>
                                </strong>
                            </td>
                            <td class="text-right">${{ number_format($variant->current_value, 2) }}</td>
                            <td class="text-center">
                                @if ($isLow)
                                    <span class="badge badge-danger">BAJO</span>
                                @else
                                    <span class="badge badge-success">OK</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.inventory.kardex', $variant) }}" class="btn btn-sm btn-info"
                                    title="Ver Kardex">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.inventory.adjustment', $variant) }}"
                                    class="btn btn-sm btn-warning" title="Ajuste Manual">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay variantes de material
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($variants->hasPages())
            <div class="card-footer">
                {{ $variants->links() }}
            </div>
        @endif
    </div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // UNIT TOGGLE - Conversión de unidades en tiempo real
    // =====================================================
    document.querySelectorAll('.unit-toggle').forEach(function(toggleGroup) {
        const container = toggleGroup.closest('.card') || document;
        const buttons = toggleGroup.querySelectorAll('[data-unit-mode]');

        buttons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const mode = this.dataset.unitMode;

                // Actualizar estado visual de botones
                buttons.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-primary');
                });
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary', 'active');

                // Convertir todas las celdas
                container.querySelectorAll('.unit-convertible').forEach(function(el) {
                    const qty = parseFloat(el.dataset.qty) || 0;
                    const factor = parseFloat(el.dataset.factor) || 1;
                    const unitConsumption = el.dataset.unitConsumption || 'u';
                    const unitBase = el.dataset.unitBase || unitConsumption;

                    let displayQty, displayUnit;

                    if (mode === 'base' && factor > 1) {
                        displayQty = qty / factor;
                        displayUnit = unitBase;
                    } else {
                        displayQty = qty;
                        displayUnit = unitConsumption;
                    }

                    const qtyEl = el.querySelector('.qty-value');
                    const unitEl = el.querySelector('.unit-symbol');
                    if (qtyEl) qtyEl.textContent = formatNumber(displayQty);
                    if (unitEl) unitEl.textContent = displayUnit;
                });
            });
        });
    });

    function formatNumber(num) {
        return num.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>
@stop
