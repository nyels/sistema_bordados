@extends('adminlte::page')

@section('title', 'Detalle de Producto')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark font-weight-bold">
                <i class="fas fa-tshirt mr-2 text-primary"></i>Detalle del Producto
                <span class="text-dark ml-2">#{{ $product->id }}</span>
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Productos</a></li>
                <li class="breadcrumb-item active">{{ $product->sku }}</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- OPTIMIZATION: Load missing relationships for BOM and cost details directly in view --}}
    @php
        $product->loadMissing([
            'materials.material.category',
            'materials.material.consumptionUnit',
            'materials.material.baseUnit',
            'designs.categories',
            'designs.exports', // Load exports for preview
            'variants.attributeValues.attribute',
            'variants.designExports.designVariant',
            'images',
            'primaryImage',
        ]);

        // Calculate totals for summary if not present
        $totalMaterialCost =
            $product->materials_cost > 0
                ? $product->materials_cost
                : $product->materials->sum(function ($m) {
                    return $m->pivot->quantity * $m->pivot->unit_cost;
                });

        $totalDesignCost = $product->embroidery_cost;
        $totalLaborCost = $product->labor_cost;

        // Recalculate services cost from relation to ensure it is COST not PRICE
        $totalServicesCost = $product->extras->sum(function ($extra) {
            return $extra->pivot->snapshot_cost > 0 ? $extra->pivot->snapshot_cost : $extra->cost_addition;
        });

        // Recalculate services PRICE for total selling price (Snapshot Priority)
        $totalServicesPrice = $product->extras->sum(function ($extra) {
            return $extra->pivot->snapshot_price > 0 ? $extra->pivot->snapshot_price : $extra->price_addition;
        });

        // Always recalculate grand total for display to ensure it matches the breakdown
        $grandTotal = $totalMaterialCost + $totalDesignCost + $totalLaborCost + $totalServicesCost;

        // Total Selling Price = Base Price + Extra Services Sell Price
        $totalSellingPrice = $product->base_price + $totalServicesPrice;

        // Margin calculation
        $margin = $product->profit_margin;
        $suggestedPrice = $product->suggested_price;

        // Fetch application types for design details
        $applicationTypes = \App\Models\Application_types::all();
    @endphp

    <div class="container-fluid fade-in">

        {{-- 1. EXECUTIVE HEADER --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center">
                            {{-- Status Badge --}}
                            <span class="badge badge-{{ $product->status_color }} p-2 mr-3" style="font-size: 0.9rem;">
                                <i
                                    class="fas fa-{{ $product->status === 'active' ? 'check-circle' : ($product->status === 'draft' ? 'pencil-alt' : 'ban') }}"></i>
                                {{ strtoupper($product->status_label) }}
                            </span>
                            <div>
                                <h3 class="font-weight-bold mb-0 text-dark">
                                    {{ $product->name }}

                                </h3>
                                <div class="text-dark font-weight-bold mt-1">
                                    <i class="fas fa-barcode mr-1"></i> {{ $product->sku }}
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-tag mr-1"></i> {{ $product->category->name ?? 'Sin Categoría' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Toolbar --}}
                    <div class="col-lg-6 text-lg-right mt-3 mt-lg-0">
                        <div class="btn-group shadow-sm">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-default">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>

                            @if ($product->status !== 'discontinued')
                                <a href="{{ route('admin.products.edit', $product->id) }}"
                                    class="btn btn-warning font-weight-bold">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                            @endif

                            {{-- Toggle Status --}}
                            @if ($product->status === 'active')
                                <form action="{{ route('admin.products.toggle_status', $product->id) }}" method="POST"
                                    class="d-inline no-loader" data-confirm="¿Descontinuar este producto?">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary text-white">
                                        <i class="fas fa-ban mr-1"></i> Desactivar
                                    </button>
                                </form>
                            @elseif($product->status === 'discontinued')
                                <form action="{{ route('admin.products.toggle_status', $product->id) }}" method="POST"
                                    class="d-inline no-loader" data-confirm="¿Reactivar este producto?">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check mr-1"></i> Reactivar
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.products.duplicate', $product->id) }}" method="POST"
                                class="d-inline no-loader" data-confirm="¿Crear una copia de este producto?">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-copy mr-1"></i> Duplicar
                                </button>
                            </form>

                            @if ($product->canDelete())
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                    class="d-inline no-loader" data-confirm="¿Eliminar permanentemente?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. KPI CARDS (Financial Summary) --}}
        <div class="row mb-4">
            {{-- CARD 1: COMPOSICIÓN (GRIS - INPUT) --}}
            <div class="col-md-4 col-sm-6">
                <div class="info-box shadow-sm" style="border-left: 5px solid #6c757d;">
                    <span class="info-box-icon bg-light" style="color: #6c757d;"><i class="fas fa-cubes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Composición</span>
                        <span class="info-box-number text-dark h5 mb-0">
                            {{ $product->materials->count() }} Mats <span class="text-dark">|</span>
                            {{ $product->extras->count() }} Serv.Extras <span class="text-dark">|</span>
                            {{ $product->variants_count }} Vars
                        </span>
                    </div>
                </div>
            </div>

            {{-- CARD 2: TIEMPO PRODUCCIÓN (AMARILLO - WARNING) --}}
            <div class="col-md-4 col-sm-6">
                <div class="info-box shadow-sm" style="border-left: 5px solid #ffc107;">
                    <span class="info-box-icon bg-light" style="color: #ffc107;"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Tiempo Producción</span>
                        <span class="info-box-number text-dark h5 mb-0">
                            {{ $product->production_lead_time ?? '0' }} Días
                        </span>
                    </div>
                </div>
            </div>

            {{-- CARD 3: COSTO PRODUCCIÓN (ROJO - GASTO) --}}
            <div class="col-md-4 col-sm-6">
                <div class="info-box shadow-sm" style="border-left: 5px solid #dc3545;">
                    <span class="info-box-icon bg-light" style="color: #dc3545;"><i
                            class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Costo Producción</span>
                        <span class="info-box-number text-dark h5 mb-0">${{ number_format($grandTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- CARD 4: PRECIO SUGERIDO (AZUL - REFERENCIA) --}}
            <div class="col-md-4 col-sm-6 mt-md-3">
                <div class="info-box shadow-sm" style="border-left: 5px solid #007bff;">
                    <span class="info-box-icon bg-light" style="color: #007bff;"><i class="fas fa-tag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Precio Sugerido</span>
                        <span class="info-box-number text-dark h5 mb-0">
                            ${{ number_format($suggestedPrice, 2) }}
                            <span class="ml-1" style="color: #007bff;">({{ number_format($margin, 1) }}%)</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- CARD 5: PRECIO BASE (MORADO - ESTRATEGIA) --}}
            <div class="col-md-4 col-sm-6 mt-md-3">
                <div class="info-box shadow-sm" style="border-left: 5px solid #6f42c1;">
                    <span class="info-box-icon bg-light" style="color: #6f42c1;"><i class="fas fa-pen-fancy"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Precio Base (Usuario)</span>
                        <div class="d-flex align-items-baseline">
                            <span class="info-box-number text-dark h5 mb-0 font-weight-bold">
                                ${{ number_format($product->base_price, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 6: PRECIO VENTA TOTAL (VERDE - RESULTADO) --}}
            <div class="col-md-4 col-sm-6 mt-md-3">
                <div class="info-box shadow-sm" style="border-left: 5px solid #28a745;">
                    <span class="info-box-icon bg-light" style="color: #28a745;"><i
                            class="fas fa-cash-register"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Precio Venta Total</span>
                        <div class="d-flex align-items-baseline">
                            <span class="info-box-number text-dark h5 mb-0">
                                ${{ number_format($totalSellingPrice, 2) }}
                            </span>
                            @if ($totalServicesPrice > 0)
                                <small class="font-weight-bold ml-2" style="font-size: 0.75rem; color: #28a745;">
                                    (Base: ${{ number_format($product->base_price, 2) }} + Extras:
                                    ${{ number_format($totalServicesPrice, 2) }})
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- 3. LEFT COLUMN: ENGINEERING & COSTS (BOM) --}}
            <div class="col-lg-8">

                {{-- COST STRUCTURE BREAKDOWN --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light border-0">
                        <h3 class="card-title font-weight-bold text-dark">
                            <i class="fas fa-chart-pie mr-2 text-secondary"></i>Estructura de Costos
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead class="text-dark font-weight-bold">
                                <tr>
                                    <th class="pl-4">Concepto</th>
                                    <th class="text-right">Costo Unitario</th>
                                    <th class="text-right pr-4">% del Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="pl-4"><i class="fas fa-boxes text-primary mr-2" style="width:20px;"></i>
                                        Materiales Directos</td>
                                    <td class="text-right font-weight-bold">${{ number_format($totalMaterialCost, 2) }}
                                    </td>
                                    <td class="text-right pr-4 text-dark font-weight-bold">
                                        {{ $grandTotal > 0 ? number_format(($totalMaterialCost / $grandTotal) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pl-4"><i class="fas fa-tshirt text-info mr-2" style="width:20px;"></i>
                                        Bordado / Diseño</td>
                                    <td class="text-right font-weight-bold">${{ number_format($totalDesignCost, 2) }}</td>
                                    <td class="text-right pr-4 text-dark font-weight-bold">
                                        {{ $grandTotal > 0 ? number_format(($totalDesignCost / $grandTotal) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pl-4"><i class="fas fa-hand-holding-usd text-warning mr-2"
                                            style="width:20px;"></i> Mano de Obra</td>
                                    <td class="text-right font-weight-bold">${{ number_format($totalLaborCost, 2) }}</td>
                                    <td class="text-right pr-4 text-dark font-weight-bold">
                                        {{ $grandTotal > 0 ? number_format(($totalLaborCost / $grandTotal) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pl-4"> <i class="fas fa-concierge-bell mr-2 text-secondary"></i>
                                        Servicios Extras</td>
                                    <td class="text-right font-weight-bold">${{ number_format($totalServicesCost, 2) }}
                                    </td>
                                    <td class="text-right pr-4 text-dark font-weight-bold">
                                        {{ $grandTotal > 0 ? number_format(($totalServicesCost / $grandTotal) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                {{-- Extras removed from here to show in detailed table --}}
                                <tr class="bg-light" style="border-top: 2px solid #dee2e6;">
                                    <td class="pl-4 font-weight-bold text-uppercase">Total Costo Producción</td>
                                    <td class="text-right font-weight-bold h6 mb-0 text-success">
                                        ${{ number_format($grandTotal, 2) }}</td>
                                    <td class="text-right pr-4 font-weight-bold text-dark">100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- BILL OF MATERIALS (BOM) - AGRUPADO POR VARIANTE --}}
                @php
                    // Separar materiales globales vs específicos por variante
                    $globalMaterials = collect();
                    $materialsByVariant = [];

                    // Inicializar arrays para cada variante
                    foreach ($product->variants as $variant) {
                        $materialsByVariant[$variant->id] = [
                            'variant' => $variant,
                            'materials' => collect(),
                            'total' => 0,
                        ];
                    }

                    foreach ($product->materials as $mat) {
                        $activeForVariants = $mat->pivot->active_for_variants;

                        // Decodificar JSON si es string
                        if (is_string($activeForVariants)) {
                            $activeForVariants = json_decode($activeForVariants, true);
                        }

                        // Si no hay variantes específicas o array vacío, es global
                        if (empty($activeForVariants)) {
                            $globalMaterials->push($mat);
                        } else {
                            // Asignar a cada variante específica
                            foreach ($activeForVariants as $variantId) {
                                if (isset($materialsByVariant[$variantId])) {
                                    $materialsByVariant[$variantId]['materials']->push($mat);
                                    $materialsByVariant[$variantId]['total'] += ($mat->pivot->quantity * $mat->pivot->unit_cost);
                                }
                            }
                        }
                    }

                    // Calcular total global
                    $globalTotal = $globalMaterials->sum(function($m) {
                        return $m->pivot->quantity * $m->pivot->unit_cost;
                    });
                @endphp

                <div class="card shadow-sm mb-4">
                    <div class="card-header border-0 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold text-dark">
                                <i class="fas fa-scroll mr-2 text-primary"></i>Receta de Materiales (BOM)
                            </h3>
                            <span class="badge badge-light border">{{ $product->materials->count() }} Insumos</span>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        {{-- MATERIALES GLOBALES (aplican a todas las variantes) --}}
                        @if($globalMaterials->count() > 0)
                            <div class="bg-secondary text-white px-4 py-2 font-weight-bold">
                                <i class="fas fa-globe mr-2"></i>Materiales Comunes (Todas las Variantes)
                                <span class="badge badge-light text-dark ml-2">{{ $globalMaterials->count() }} items</span>
                                <span class="float-right">${{ number_format($globalTotal, 2) }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-light text-uppercase small">
                                        <tr>
                                            <th class="pl-4">Material</th>
                                            <th>Categoría</th>
                                            <th class="text-center">Consumo</th>
                                            <th class="text-right">Costo Unit.</th>
                                            <th class="text-right pr-4">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($globalMaterials->sortBy('material.category.name') as $mat)
                                            <tr>
                                                <td class="pl-4">
                                                    <span class="font-weight-bold">{{ $mat->material->name ?? 'N/A' }}</span>
                                                    @if ($mat->color)
                                                        <span class="text-muted">- {{ $mat->color }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light border small">{{ $mat->material->category->name ?? 'N/A' }}</span>
                                                </td>
                                                <td class="text-center font-weight-bold">
                                                    {{ floatval($mat->pivot->quantity) }}
                                                    <span class="text-muted">{{ $mat->material->consumptionUnit->symbol ?? ($mat->material->baseUnit->symbol ?? 'unid') }}</span>
                                                </td>
                                                <td class="text-right">${{ number_format($mat->pivot->unit_cost, 4) }}</td>
                                                <td class="text-right pr-4 font-weight-bold">${{ number_format($mat->pivot->quantity * $mat->pivot->unit_cost, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- MATERIALES POR VARIANTE --}}
                        @foreach($materialsByVariant as $variantId => $data)
                            @if($data['materials']->count() > 0)
                                @php
                                    $variant = $data['variant'];
                                    $attrDisplay = $variant->attributeValues->pluck('value')->join(' / ');
                                @endphp
                                <div class="bg-info text-white px-4 py-2 font-weight-bold" style="border-top: 2px solid #dee2e6;">
                                    <i class="fas fa-tag mr-2"></i>Variante: {{ $attrDisplay ?: $variant->sku_variant }}
                                    <span class="badge badge-light text-dark ml-2">{{ $data['materials']->count() }} items</span>
                                    <span class="float-right">
                                        @if($globalTotal > 0)
                                            ${{ number_format($data['total'], 2) }} + ${{ number_format($globalTotal, 2) }} global = ${{ number_format($data['total'] + $globalTotal, 2) }}
                                        @else
                                            ${{ number_format($data['total'], 2) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light text-uppercase small">
                                            <tr>
                                                <th class="pl-4">Material</th>
                                                <th>Categoría</th>
                                                <th class="text-center">Consumo</th>
                                                <th class="text-right">Costo Unit.</th>
                                                <th class="text-right pr-4">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data['materials']->sortBy('material.category.name') as $mat)
                                                <tr>
                                                    <td class="pl-4">
                                                        <span class="font-weight-bold">{{ $mat->material->name ?? 'N/A' }}</span>
                                                        @if ($mat->color)
                                                            <span class="text-muted">- {{ $mat->color }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light border small">{{ $mat->material->category->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td class="text-center font-weight-bold">
                                                        {{ floatval($mat->pivot->quantity) }}
                                                        <span class="text-muted">{{ $mat->material->consumptionUnit->symbol ?? ($mat->material->baseUnit->symbol ?? 'unid') }}</span>
                                                    </td>
                                                    <td class="text-right">${{ number_format($mat->pivot->unit_cost, 4) }}</td>
                                                    <td class="text-right pr-4 font-weight-bold">${{ number_format($mat->pivot->quantity * $mat->pivot->unit_cost, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endforeach

                        {{-- SI NO HAY MATERIALES --}}
                        @if($product->materials->count() === 0)
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2"></i>
                                <p class="mb-0">No hay materiales registrados en la receta.</p>
                            </div>
                        @endif

                        {{-- RESUMEN TOTAL BOM --}}
                        @if($product->materials->count() > 0)
                            <div class="bg-dark text-white px-4 py-3 d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-uppercase">
                                    <i class="fas fa-calculator mr-2"></i>Total Receta de Materiales
                                </span>
                                <span class="h5 mb-0 font-weight-bold">
                                    ${{ number_format($totalMaterialCost, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SERVICES EXTRAS TABLE (MIRROR BOM FORMAT) --}}
                @if ($product->extras->count() > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header border-0 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title font-weight-bold text-dark">
                                    <i class="fas fa-concierge-bell mr-2 text-warning"></i>Servicios Extras
                                </h3>
                                <span class="badge badge-light border text-dark">{{ $product->extras->count() }}
                                    Servicios</span>
                            </div>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="bg-light text-uppercase font-weight-bold text-dark">
                                    <tr>
                                        <th class="pl-4">Servicio</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Alcance</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Tiempo Estim.</th>
                                        <th class="text-right font-weight-bold">Costo Unitario</th>
                                        <th class="text-right font-weight-bold pr-4">Precio Venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($product->extras as $extra)
                                        <tr>
                                            <td class="pl-4 align-middle">
                                                <div class="font-weight-bold text-dark pb-0 mb-0">
                                                    {{ $extra->name }}
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-light border text-dark">Servicio</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge badge-secondary">Global</span>
                                            </td>
                                            <td class="align-middle text-center font-weight-bold text-dark">
                                                1 <span class="text-dark">srv</span>
                                            </td>
                                            <td class="align-middle text-center font-weight-bold text-dark">
                                                @php
                                                    $time =
                                                        $extra->pivot->snapshot_time > 0
                                                            ? $extra->pivot->snapshot_time
                                                            : $extra->minutes_addition;
                                                @endphp
                                                {{ $time }} min
                                            </td>
                                            <td class="align-middle text-right font-weight-bold text-dark">
                                                ${{ number_format($extra->pivot->snapshot_cost > 0 ? $extra->pivot->snapshot_cost : $extra->cost_addition, 2) }}
                                            </td>
                                            <td class="align-middle text-right pr-4 font-weight-bold text-success">
                                                ${{ number_format($extra->pivot->snapshot_price > 0 ? $extra->pivot->snapshot_price : $extra->price_addition, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-light border-top">
                                        <td colspan="5" class="pl-4 font-weight-bold text-dark text-right">TOTAL EXTRAS
                                        </td>
                                        <td class="text-right font-weight-bold text-dark">
                                            @php
                                                // Calculate total cost using snapshot logic
                                                $totalExtrasCost = $product->extras->sum(function ($extra) {
                                                    return $extra->pivot->snapshot_cost > 0
                                                        ? $extra->pivot->snapshot_cost
                                                        : $extra->cost_addition;
                                                });
                                            @endphp
                                            ${{ number_format($totalExtrasCost, 2) }}
                                        </td>
                                        <td>
                                            @php
                                                // Calculate total price using snapshot logic
                                                $totalExtrasPrice = $product->extras->sum(function ($extra) {
                                                    return $extra->pivot->snapshot_price > 0
                                                        ? $extra->pivot->snapshot_price
                                                        : $extra->price_addition;
                                                });
                                            @endphp
                                            <div class="text-right font-weight-bold text-success">
                                                ${{ number_format($totalExtrasPrice, 2) }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>

            {{-- 4. RIGHT COLUMN: CONTEXT & MEDIA --}}
            <div class="col-lg-4">

                {{-- PRODUCT IMAGE --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center p-2 bg-light rounded">
                        @if ($product->primary_image_url)
                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                class="img-fluid rounded shadow-sm"
                                style="max-height: 280px; width: 100%; object-fit: contain; background:white;">
                        @else
                            <div class="text-center py-5 bg-white rounded border border-light">
                                <i class="fas fa-image fa-4x text-dark"></i>
                                <p class="mt-2 text-dark font-weight-bold">Sin imagen principal</p>
                            </div>
                        @endif
                    </div>
                    @if($product->images->count() > 0)
                    <div class="card-footer bg-white p-2">
                        <span class="text-dark font-weight-bold text-uppercase d-block mb-2">Galería</span>
                        <div class="d-flex gap-2">
                            @foreach($product->images->take(4) as $img)
                                @if($img->url && file_exists(public_path($img->url)))
                                <img src="{{ asset($img->url) }}" class="rounded border"
                                    style="width: 40px; height: 40px; object-fit: cover;">
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- DESIGNS / EMBROIDERY --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h3 class="card-title font-weight-bold text-dark">
                            <i class="fas fa-vector-square mr-2 text-info"></i>Diseños
                        </h3>
                    </div>
                    <div class="card-body pt-2">
                        @forelse($product->designs as $design)
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom last-no-border">
                                <div class="mr-3 text-center bg-light rounded p-1 d-flex align-items-center justify-content-center border"
                                    style="width: 80px; height: 80px; overflow: hidden;">
                                    @php
                                        // Prioritize the latest export for preview
                                        $previewExport =
                                            $design->exports->count() > 0
                                                ? $design->exports->sortByDesc('created_at')->first()
                                                : null;
                                        // Specific general export (non-variant) logic if needed, but exports relation covers all
                                    @endphp

                                    @if ($previewExport && $previewExport->svg_content)
                                        <div
                                            style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                            <style>
                                                .design-preview-svg svg {
                                                    width: 100% !important;
                                                    height: 100% !important;
                                                    max-width: 100%;
                                                    max-height: 100%;
                                                }
                                            </style>
                                            <div class="design-preview-svg" style="width: 100%; height: 100%;">
                                                {!! $previewExport->svg_content !!}
                                            </div>
                                        </div>
                                    @elseif($previewExport && $previewExport->file_path)
                                        @php
                                            $ext = strtolower(pathinfo($previewExport->file_path, PATHINFO_EXTENSION));
                                        @endphp
                                        @if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp']))
                                            <a href="{{ asset('storage/' . $previewExport->file_path) }}" target="_blank"
                                                data-toggle="lightbox" data-title="{{ $design->name }}">
                                                <img src="{{ asset('storage/' . $previewExport->file_path) }}"
                                                    class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                            </a>
                                        @else
                                            <i class="fas fa-file-invoice fa-2x text-secondary"></i>
                                        @endif
                                    @else
                                        {{-- Fallback if no export --}}
                                        @if ($design->primaryImage)
                                            <img src="{{ asset('storage/' . $design->primaryImage->path) }}"
                                                class="img-fluid" style="max-height: 100%;">
                                        @else
                                            <i class="fas fa-palette fa-2x text-secondary"></i>
                                        @endif
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            {{-- Nombre real del archivo de producción (application_label) --}}
                                            <h5 class="font-weight-bold mb-1 text-dark">{{ $previewExport && $previewExport->application_label ? $previewExport->application_label : $design->name }}</h5>
                                            {{-- Trazabilidad: Origen del diseño (Principal o Variante) --}}
                                            <div class="mb-2" style="font-size: 0.95rem;">
                                                <i class="fas fa-sitemap mr-1 text-muted"></i>
                                                <span class="text-muted">{{ $design->name }}</span>
                                                <span class="text-muted mx-1">&rarr;</span>
                                                @if($previewExport && $previewExport->design_variant_id)
                                                    @php
                                                        $variant = \App\Models\DesignVariant::find($previewExport->design_variant_id);
                                                    @endphp
                                                    <span class="text-success font-weight-bold">{{ $variant ? $variant->name : 'Variante' }}</span>
                                                @else
                                                    <span class="text-primary font-weight-bold">Diseño Principal</span>
                                                @endif
                                            </div>
                                            <div class="mb-1">
                                                @if (isset($applicationTypes) && $design->pivot->application_type_id)
                                                    <span
                                                        class="badge badge-info">{{ $applicationTypes->firstWhere('id', $design->pivot->application_type_id)->nombre_aplicacion ?? '' }}</span>
                                                @endif
                                                @if ($design->pivot->notes)
                                                    <span class="text-dark ml-1">•
                                                        {{ Str::limit($design->pivot->notes, 30) }}</span>
                                                @endif
                                            </div>

                                            {{-- Technical Data --}}
                                            <div class="d-flex flex-wrap text-dark mt-1" style="font-size: 0.95rem;">
                                                <span class="mr-3" title="Puntadas"><i
                                                        class="fas fa-microchip mr-1 text-muted"></i>
                                                    <span class="font-weight-bold">
                                                        {{ $previewExport && $previewExport->stitches_count ? number_format($previewExport->stitches_count) : '0' }}
                                                    </span> pts
                                                </span>
                                                @if ($previewExport && $previewExport->formatted_dimensions !== 'N/A')
                                                    <span class="mr-3" title="Medidas"><i
                                                            class="fas fa-ruler-combined mr-1 text-muted"></i>
                                                        {{ $previewExport->formatted_dimensions }}
                                                    </span>
                                                @endif
                                                <span title="Colores"><i class="fas fa-palette mr-1 text-muted"></i>
                                                    {{ $previewExport ? $previewExport->colors_count : '0' }} Colores
                                                </span>
                                            </div>
                                        </div>

                                        @if ($previewExport && $previewExport->file_path)
                                            <a href="{{ asset('storage/' . $previewExport->file_path) }}"
                                                class="btn btn-sm btn-outline-primary shadow-sm" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-dark font-italic mb-0">No hay diseños asociados (Producto liso)</p>
                        @endforelse
                    </div>
                </div>

                {{-- SPECS SUMMARY --}}
                @if ($product->specifications && count($product->specifications) > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h3 class="card-title font-weight-bold text-dark text-small">Especificaciones</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped">
                                <tbody>
                                    @foreach ($product->specifications as $key => $value)
                                        <tr>
                                            <td class="pl-3 text-dark small font-weight-bold text-uppercase"
                                                style="width:40%;">{{ $key }}</td>
                                            <td class="small">
                                                @if(is_array($value))
                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                @elseif(is_object($value))
                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- 5. VARIANTS TABLE (Full Width) --}}
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4" id="variants-section">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <h3 class="card-title font-weight-bold mb-0">
                            <i class="fas fa-th mr-2"></i>Variantes del Producto ({{ $product->variants->count() }})
                        </h3>
                    </div>

                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase" style="font-size: 0.85rem;">
                                <tr>
                                    <th class="pl-3">Nombre</th>
                                    <th>Atributos (Talla / Color)</th>
                                    <th class="text-right">Precio Variante</th>
                                    <th class="text-right">Precio + Extras</th>
                                    <th class="text-center">Stock Alerta</th>
                                    <th class="text-right pr-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->variants as $variant)
                                    @php
                                        // Variant price = variant's own price (price override)
                                        $variantPrice = (float) $variant->price;
                                        // Final price = variant price + extras price additions
                                        $extrasTotal = $product->extras->sum('price_addition');
                                        $finalPrice = $variantPrice + $extrasTotal;
                                    @endphp
                                    <tr data-variant-row="{{ $variant->id }}">
                                        <td class="pl-3">
                                            <span class="font-weight-bold text-dark">{{ $product->name }}</span>
                                        </td>
                                        <td>
                                            @foreach ($variant->attributeValues as $attr)
                                                <span class="badge badge-light border text-dark mr-1">
                                                    {{ $attr->value }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="text-right text-dark variant-base-price">${{ number_format($variantPrice, 2) }}</td>
                                        <td class="text-right font-weight-bold text-success variant-final-price">
                                            ${{ number_format($finalPrice, 2) }}
                                        </td>
                                        <td class="text-center variant-stock-alert">
                                            @if ($variant->stock_alert > 0)
                                                <span class="text-warning font-weight-bold"><i
                                                        class="fas fa-exclamation-triangle mr-1"></i>{{ $variant->stock_alert }}</span>
                                            @else
                                                <span class="text-dark">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right pr-3">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-default btn-sm btn-edit-variant"
                                                    data-variant-id="{{ $variant->id }}"
                                                    data-product-id="{{ $product->id }}"
                                                    title="Editar Variante">
                                                    <i class="fas fa-pencil-alt text-warning"></i>
                                                </button>
                                                <form
                                                    action="{{ route('admin.products.variants.destroy', ['product' => $product->id, 'variant' => $variant->id]) }}"
                                                    method="POST" class="d-inline"
                                                    data-confirm="¿Eliminar esta variante permanentemente?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-default btn-sm delete-variant-btn"
                                                        title="Eliminar">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-search fa-2x text-dark mb-2 d-block"></i>
                                            <p class="text-dark mb-0">No existen variantes activas para este producto.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. FOOTER AUDIT --}}
        <div class="d-flex justify-content-between align-items-center mb-4 text-dark px-2">
            <div>
                <strong>Creado:</strong> {{ $product->created_at->format('d/m/Y H:i') }} |
                <strong>Actualizado:</strong> {{ $product->updated_at->format('d/m/Y H:i') }}
            </div>
            <div class="font-italic">
                UUID: {{ $product->uuid }}
            </div>
        </div>

    </div>
@stop

@section('css')
    <style>
        .card {
            border-radius: 8px;
        }

        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }

        .border-left-success {
            border-left: 4px solid #28a745 !important;
        }

        .border-left-info {
            border-left: 4px solid #17a2b8 !important;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }

        .info-box-icon {
            border-radius: 8px;
        }

        .last-no-border:last-child {
            border-bottom: none !important;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Global confirmation handler para forms con data-confirm
            $('form[data-confirm]').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const $btn = $(form).find('button[type="submit"]');

                Swal.fire({
                    title: '¿Confirmar acción?',
                    text: $(form).data('confirm'),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Solo mostrar loading al confirmar
                        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                        // Enviar form nativo
                        form.submit();
                    }
                    // Si cancela, no hace nada - botón permanece intacto
                });
            });

            // Handler para botón eliminar variante
            $('.delete-variant-btn').on('click', function() {
                $(this).closest('form').submit();
            });

            // ========== MODAL EDITAR VARIANTE ==========
            const $modal = $('#modalEditVariant');
            const $form = $('#formEditVariant');
            const $modalTitle = $('#modalEditVariantLabel');
            const $btnSave = $('#btnSaveVariant');

            // Abrir modal con datos de variante
            $(document).on('click', '.btn-edit-variant', function() {
                const variantId = $(this).data('variant-id');
                const productId = $(this).data('product-id');
                const $btn = $(this);

                // Loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                // Fetch datos
                $.ajax({
                    url: `/admin/products/${productId}/variants/${variantId}/json`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            const v = resp.variant;
                            $modalTitle.text('Editar: ' + (v.attributes_display || v.sku_variant));
                            $('#edit_variant_id').val(v.id);
                            $('#edit_product_id').val(productId);
                            $('#edit_sku_variant').val(v.sku_variant);
                            $('#edit_price').val(v.price.toFixed(2));
                            $('#edit_stock_alert').val(v.stock_alert);
                            $('#edit_base_price_ref').text('$' + v.product_base_price.toFixed(2));
                            $form.find('.invalid-feedback').hide();
                            $form.find('.is-invalid').removeClass('is-invalid');
                            $modal.modal('show');
                        } else {
                            Swal.fire('Error', resp.message || 'No se pudo cargar', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error de conexion', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-pencil-alt text-warning"></i>');
                    }
                });
            });

            // Guardar cambios
            $btnSave.on('click', function() {
                const variantId = $('#edit_variant_id').val();
                const productId = $('#edit_product_id').val();
                const $btn = $(this);

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');
                $form.find('.invalid-feedback').hide();
                $form.find('.is-invalid').removeClass('is-invalid');

                $.ajax({
                    url: `/admin/products/${productId}/variants/${variantId}`,
                    method: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        sku_variant: $('#edit_sku_variant').val(), // Readonly, se envía pero no cambia
                        price: $('#edit_price').val(),
                        stock_alert: $('#edit_stock_alert').val() || 0
                    },
                    dataType: 'json',
                    headers: { 'Accept': 'application/json' },
                    success: function(resp) {
                        if (resp.success) {
                            $modal.modal('hide');

                            // Actualizar fila en la tabla sin recargar
                            const $row = $(`tr[data-variant-row="${variantId}"]`);
                            if ($row.length) {
                                // Actualizar columna "Precio Variante"
                                const variantPrice = parseFloat(resp.variant.price);
                                $row.find('.variant-base-price').html('$' + variantPrice.toFixed(2));

                                // Actualizar columna "Precio + Extras" (variant price + extras)
                                const extrasTotal = parseFloat(resp.variant.extras_total || 0);
                                const finalPrice = variantPrice + extrasTotal;
                                $row.find('.variant-final-price').html('$' + finalPrice.toFixed(2));

                                // Actualizar stock alert
                                const stockAlert = parseInt(resp.variant.stock_alert) || 0;
                                if (stockAlert > 0) {
                                    $row.find('.variant-stock-alert').html(
                                        '<span class="text-warning font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i>' + stockAlert + '</span>'
                                    );
                                } else {
                                    $row.find('.variant-stock-alert').html('<span class="text-dark">-</span>');
                                }
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Guardado',
                                text: resp.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', resp.message || 'Error desconocido', 'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(field => {
                                const $input = $form.find(`[name="${field}"]`);
                                $input.addClass('is-invalid');
                                $input.siblings('.invalid-feedback').text(errors[field][0]).show();
                            });
                        } else {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Error al guardar', 'error');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Guardar');
                    }
                });
            });
        });
    </script>

    {{-- Modal Editar Variante --}}
    <div class="modal fade" id="modalEditVariant" tabindex="-1" aria-labelledby="modalEditVariantLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalEditVariantLabel">Editar Variante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditVariant">
                        <input type="hidden" id="edit_variant_id">
                        <input type="hidden" id="edit_product_id">

                        <div class="form-group">
                            <label for="edit_sku_variant" class="font-weight-bold">
                                <i class="fas fa-barcode mr-1"></i> SKU Variante
                            </label>
                            <input type="text" class="form-control text-uppercase bg-light" id="edit_sku_variant" name="sku_variant" readonly>
                            <small class="text-muted">El SKU no es editable</small>
                        </div>

                        <div class="form-group">
                            <label for="edit_price" class="font-weight-bold">
                                <i class="fas fa-dollar-sign mr-1"></i> Precio de la Variante
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                            <small class="text-muted">Precio base del producto (referencia): <strong id="edit_base_price_ref">-</strong></small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="edit_stock_alert" class="font-weight-bold">
                                <i class="fas fa-bell mr-1"></i> Alerta de Stock
                            </label>
                            <input type="number" class="form-control" id="edit_stock_alert" name="stock_alert" min="0">
                            <small class="text-muted">Notificar cuando el stock sea menor a este valor</small>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSaveVariant">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop
