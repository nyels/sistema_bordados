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
            'designs.category',
            'variants.attributes.attribute',
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
        $totalServicesCost = $product->extra_services_cost;

        // Use stored production cost if available and valid, otherwise recalc
        $grandTotal =
            $product->production_cost > 0
                ? $product->production_cost
                : $totalMaterialCost + $totalDesignCost + $totalLaborCost + $totalServicesCost;

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
                                <h3 class="font-weight-bold mb-0 text-dark">{{ $product->name }}</h3>
                                <div class="text-dark font-weight-bold mt-1">
                                    <i class="fas fa-barcode mr-1"></i> {{ $product->sku }}
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-tag mr-1"></i> {{ $product->category->name ?? 'Sin Categoría' }}
                                    @if ($product->production_lead_time)
                                        <span class="mx-2">|</span>
                                        <span class="badge badge-warning text-dark border border-dark"
                                            style="font-size: 100%;">
                                            <i class="fas fa-clock mr-1"></i> {{ $product->production_lead_time }} días
                                            producción
                                        </span>
                                    @endif
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
                                    class="d-inline" data-confirm="¿Descontinuar este producto?">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary text-white">
                                        <i class="fas fa-ban mr-1"></i> Desactivar
                                    </button>
                                </form>
                            @elseif($product->status === 'discontinued')
                                <form action="{{ route('admin.products.toggle_status', $product->id) }}" method="POST"
                                    class="d-inline" data-confirm="¿Reactivar este producto?">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check mr-1"></i> Reactivar
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.products.duplicate', $product->id) }}" method="POST"
                                class="d-inline" data-confirm="¿Crear una copia de este producto?">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-copy mr-1"></i> Duplicar
                                </button>
                            </form>

                            @if ($product->canDelete())
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                    class="d-inline" data-confirm="¿Eliminar permanentemente?">
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
            <div class="col-md-3 col-sm-6">
                <div class="info-box shadow-sm border-left-primary">
                    <span class="info-box-icon bg-light text-primary"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Costo Producción</span>
                        <span class="info-box-number text-dark h5 mb-0">${{ number_format($grandTotal, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box shadow-sm border-left-success">
                    <span class="info-box-icon bg-light text-success"><i class="fas fa-tag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Precio Sugerido</span>
                        <span class="info-box-number text-dark h5 mb-0">
                            ${{ number_format($suggestedPrice, 2) }}
                            <span class="text-success ml-1">({{ number_format($margin, 1) }}%)</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box shadow-sm border-left-info">
                    <span class="info-box-icon bg-light text-info"><i class="fas fa-cubes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Composición</span>
                        <span class="info-box-number text-dark h5 mb-0">
                            {{ $product->materials->count() }} Mats <span class="text-dark">|</span>
                            {{ $product->variants_count }} Vars
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box shadow-sm border-left-warning">
                    <span class="info-box-icon bg-light text-warning"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-uppercase text-dark font-weight-bold">Precio Final</span>
                        <span class="info-box-number text-dark h5 mb-0 font-weight-bold">
                            ${{ number_format($product->base_price, 2) }}
                            @if ($product->base_price != $suggestedPrice)
                                <span class="text-dark ml-1" style="font-size: 0.9rem;">
                                    | Dif: ${{ number_format($product->base_price - $suggestedPrice, 2) }}
                                </span>
                            @endif
                        </span>
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

                {{-- BILL OF MATERIALS (BOM) --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header border-0 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold text-dark">
                                <i class="fas fa-scroll mr-2 text-primary"></i>Receta de Materiales (BOM)
                            </h3>
                            <span class="badge badge-light border">{{ $product->materials->count() }} Insumos</span>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-light text-uppercase font-weight-bold text-dark">
                                <tr>
                                    <th class="pl-4">Material</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Alcance</th>
                                    <th class="text-center">Consumo</th>
                                    <th class="text-right">Costo Unit.</th>
                                    <th class="text-right pr-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->materials->sortBy('material.category.name') as $mat)
                                    <tr>
                                        <td class="pl-4 align-middle">
                                            <div class="font-weight-bold text-dark pb-0 mb-0" style="line-height:1.2;">
                                                {{ $mat->material->name ?? 'N/A' }}
                                                @if ($mat->color)
                                                    <span class="text-dark">- {{ $mat->color }}</span>
                                                @endif
                                            </div>
                                            @if ($mat->pivot->notes)
                                                <div class="text-dark d-block"><i
                                                        class="fas fa-info-circle mr-1"></i>{{ $mat->pivot->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <span
                                                class="badge badge-light border text-dark">{{ $mat->material->category->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            @if ($mat->pivot->active_for_variants)
                                                <span class="badge badge-info"
                                                    title="Aplica solo a algunas variantes">Específico</span>
                                            @else
                                                <span class="badge badge-secondary"
                                                    title="Aplica a todas las variantes">Global</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center font-weight-bold">
                                            {{ floatval($mat->pivot->quantity) }}
                                            <span
                                                class="text-dark">{{ $mat->material->consumptionUnit->symbol ?? ($mat->material->baseUnit->symbol ?? 'unid') }}</span>
                                        </td>
                                        <td class="align-middle text-right text-dark">
                                            ${{ number_format($mat->pivot->unit_cost, 2) }}
                                        </td>
                                        <td class="align-middle text-right pr-4 font-weight-bold text-dark">
                                            ${{ number_format($mat->pivot->quantity * $mat->pivot->unit_cost, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-box-open mr-2"></i>No hay materiales registrados en la receta.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                                        <th class="text-right">Costo Unit.</th>
                                        <th class="text-right pr-4">Subtotal</th>
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
                                            <td class="align-middle text-right text-dark">
                                                ${{ number_format($extra->price_addition, 2) }}
                                            </td>
                                            <td class="align-middle text-right pr-4 font-weight-bold text-dark">
                                                ${{ number_format($extra->price_addition, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-light border-top">
                                        <td colspan="5" class="pl-4 font-weight-bold text-dark text-right">TOTAL EXTRAS
                                        </td>
                                        <td class="text-right pr-4 font-weight-bold text-dark">
                                            ${{ number_format($product->extras->sum('price_addition'), 2) }}
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
                    <div class="card-footer bg-white p-2">
                        <span class="text-dark font-weight-bold text-uppercase d-block mb-2">Galería</span>
                        <div class="d-flex gap-2">
                            @forelse($product->images->take(4) as $img)
                                <img src="{{ asset($img->url) }}" class="rounded border"
                                    style="width: 40px; height: 40px; object-fit: cover;">
                            @empty
                                <span class="text-dark font-italic">No hay imágenes adicionales</span>
                            @endforelse
                        </div>
                    </div>
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
                                <div class="mr-3 text-center bg-light rounded p-2"
                                    style="width:50px; height:50px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-palette text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="font-weight-bold mb-0 text-dark">{{ $design->name }}</h6>
                                    <div class="text-dark d-block">
                                        {{ $design->stitch_count ? number_format($design->stitch_count) . ' pts' : 'N/A' }}
                                        @if (isset($applicationTypes) && $design->pivot->application_type_id)
                                            •
                                            {{ $applicationTypes->firstWhere('id', $design->pivot->application_type_id)->nombre_aplicacion ?? '' }}
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
                                            <td class="pl-3 text-muted small font-weight-bold text-uppercase"
                                                style="width:40%;">{{ $key }}</td>
                                            <td class="small">{{ $value }}</td>
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
        <div class="card shadow-sm border-0 mb-4" id="variants-section">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-th mr-2"></i>Variantes del Producto ({{ $product->variants->count() }})
                </h3>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small">
                        <tr>
                            <th class="pl-4">SKU Variante</th>
                            <th>Atributos (Talla / Color)</th>
                            <th class="text-right">Precio Base</th>
                            <th class="text-right">Precio Final</th>
                            <th class="text-center">Stock Alerta</th>
                            <th class="text-right pr-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->variants as $variant)
                            <tr>
                                <td class="pl-4">
                                    <span class="font-weight-bold text-dark">{{ $variant->sku_variant }}</span>
                                </td>
                                <td>
                                    @foreach ($variant->attributes as $attr)
                                        <span class="badge badge-light border text-dark mr-1">
                                            {{ $attr->value }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-right text-dark">${{ number_format($variant->price, 2) }}</td>
                                <td class="text-right font-weight-bold text-success">
                                    {{ $variant->formatted_total_with_extras }}
                                </td>
                                <td class="text-center">
                                    @if ($variant->stock_alert > 0)
                                        <span class="text-warning font-weight-bold"><i
                                                class="fas fa-exclamation-triangle mr-1"></i>{{ $variant->stock_alert }}</span>
                                    @else
                                        <span class="text-dark">-</span>
                                    @endif
                                </td>
                                <td class="text-right pr-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.products.variants.edit', ['product' => $product->id, 'variant' => $variant->id]) }}"
                                            class="btn btn-default" title="Editar Variante">
                                            <i class="fas fa-pencil-alt text-warning"></i>
                                        </a>
                                        <form
                                            action="{{ route('admin.products.variants.destroy', ['product' => $product->id, 'variant' => $variant->id]) }}"
                                            method="POST" class="d-inline"
                                            data-confirm="¿Eliminar esta variante permanentemente?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-default delete-variant-btn"
                                                title="Eliminar">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-search fa-2x text-dark mb-3 block"></i>
                                    <p class="text-dark">No existen variantes activas para este producto.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
            // Global delete confirmation
            $('form[data-confirm]').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: '¿Confirmar acción?',
                    text: $(this).data('confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Special handler for variant deletion button (which is type="button" to prevent auto submit)
            $('.delete-variant-btn').on('click', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@stop
