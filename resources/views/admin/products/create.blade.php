@extends('adminlte::page')

@section('title', 'Nuevo Producto - Enterprise Configurator')

@section('plugins.Sweetalert2', true)
@section('plugins.Select2', true)

@section('content_header')
    <div class="module-header fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white"><i class="fas fa-cube mr-2"></i> Ingeniería de Producto</h1>
                <p class="text-white-50 mb-0">Configurador Master: BOM, Costos y Variantes</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="live-cost-badge d-none d-md-block">
                    <span class="label">Costo Producción Est.:</span>
                    <span class="value" id="headerCost">$0.00</span>
                </div>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="content-wrapper-custom">
        <form id="productForm" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="stepper-nav-container">
                <button type="button" class="stepper-arrow stepper-arrow-prev" id="btnPrev" disabled
                    onclick="navigate(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="stepper-wrapper">
                    <div class="stepper-item active" data-step="1">
                        <div class="step-counter">1</div>
                        <div class="step-name">Definición</div>
                    </div>
                    <div class="stepper-item" data-step="2">
                        <div class="step-counter">2</div>
                        <div class="step-name">Variantes</div>
                    </div>
                    <div class="stepper-item" data-step="3">
                        <div class="step-counter">3</div>
                        <div class="step-name">Ingeniería (BOM)</div>
                    </div>
                    <div class="stepper-item" data-step="4">
                        <div class="step-counter">4</div>
                        <div class="step-name">Diseño</div>
                    </div>
                    <div class="stepper-item" data-step="5">
                        <div class="step-counter">5</div>
                        <div class="step-name">Finanzas</div>
                    </div>
                    <div class="stepper-item" data-step="6">
                        <div class="step-counter">6</div>
                        <div class="step-name">Confirmar</div>
                    </div>
                </div>

                <button type="button" class="stepper-arrow stepper-arrow-next" id="btnNext" onclick="navigate(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="step-content fade-in" id="step1-content"></div>
            <div class="step-content d-none fade-in" id="step2-content"></div>
            <div class="step-content d-none fade-in" id="step3-content"></div>
            <div class="step-content d-none fade-in" id="step4-content"></div>
            <div class="step-content d-none fade-in" id="step5-content"></div>
            <div class="step-content d-none fade-in" id="step6-content"></div>

            <input type="hidden" name="variants_json" id="h_variants">
            <input type="hidden" name="materials_json" id="h_materials">
            <input type="hidden" name="embroideries_json" id="h_embroideries">
            <input type="hidden" name="extras_json" id="h_extras">
            <input type="hidden" name="financials_json" id="h_financials">
        </form>
    </div>

    {{-- MODALS --}}
    <div class="modal fade" id="materialScopeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-bullseye mr-2"></i>Definir Alcance del Material</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted mb-4" style="font-size: 1.1rem;">¿Este material se aplica a todas las variantes o
                        solo a algunas específicas?</p>

                    {{-- ALERTA EXPLICATIVA --}}
                    <div class="bg-light border rounded p-3 mb-4">
                        <h6 class="mb-2 text-center" style="font-size: 1rem;"><i
                                class="fas fa-lightbulb text-warning mr-2"></i>¿Cómo funciona?</h6>
                        <ul class="mb-2 pl-4 text-muted text-left" style="font-size: 0.95rem;">
                            <li><strong>Global:</strong> Aplica a todas las variantes</li>
                            <li><strong>Específico:</strong> Solo aplica a las variantes seleccionadas</li>
                        </ul>
                        <small class="text-secondary"><i class="fas fa-info-circle mr-1"></i>Si agregas Global +
                            Específico
                            del mismo material, los consumos se suman.</small>
                    </div>

                    {{-- TOGGLE BUTTONS --}}
                    <div class="d-flex justify-content-center mb-4 gap-3">
                        <button type="button" class="scope-toggle-btn" id="btnScopeGlobal"
                            onclick="selectScope('global')">
                            <i class="fas fa-globe mr-2"></i>GLOBAL
                        </button>
                        <button type="button" class="scope-toggle-btn" id="btnScopeSpecific"
                            onclick="selectScope('specific')">
                            <i class="fas fa-filter mr-2"></i>ESPECÍFICO
                        </button>
                    </div>
                    <input type="hidden" name="materialScope" id="materialScopeValue" value="">

                    <div id="specificVariantsContainer" class="d-none text-left">
                        <label class="font-weight-bold">Selecciona las variantes:</label>
                        <select class="form-control select2" multiple id="targetVariantsSelect"
                            style="width:100%"></select>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-top pt-3">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" data-dismiss="modal"
                        style="border-width: 2px; font-weight: 600;">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2" onclick="confirmAddMaterial()"
                        style="font-weight: 600;">
                        <i class="fas fa-plus mr-2"></i>Agregar al BOM
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= TEMPLATES (DATA FROM DB) ================= --}}

    {{-- STEP 1: DEFINICIÓN --}}
    <script type="text/template" id="tpl_step1">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="step-title"><i class="fas fa-fingerprint text-primary"></i> Identidad del Producto</h5>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="font-weight-bold">Nombre Comercial *</label>
                        <input type="text" class="form-control form-control-lg" name="name" id="inpName" placeholder="Ej: Guayabera Presidencial Lino" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">SKU Base (Prefijo) *</label>
                        <input type="text" class="form-control form-control-lg text-uppercase" name="sku" id="inpSku" placeholder="GUA-PRE-LIN" required>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="font-weight-bold">Categoría</label>
                    <select class="form-control" name="category_id" id="inpCategory">
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="font-weight-bold">Estado</label>
                    <select class="form-control" name="status" id="inpStatus">
                        <option value="draft">Borrador</option>
                        <option value="active" selected>Activo</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                     <label class="font-weight-bold">Descripción</label>
                     <textarea class="form-control" name="description" id="inpDesc" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>
    </script>
    </div>
    </div>
    </div>
    </div>
    </script>

    {{-- STEP 2: VARIANTES --}}
    <script type="text/template" id="tpl_step2">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="step-title"><i class="fas fa-th text-primary"></i> Matriz de Variantes</h5>
            
            <div class="row bg-light p-3 rounded mb-4">
                <div class="col-md-5">
                    <label class="font-weight-bold">Tallas</label>
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <select class="form-control select2" multiple id="selSizes" data-placeholder="Selecciona tallas...">
                                @foreach($sizeAttribute->values ?? [] as $val)
                                    <option value="{{ $val->id }}">{{ $val->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-danger ml-2" onclick="$('#selSizes').val(null).trigger('change')" title="Limpiar tallas" style="height: 45px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="font-weight-bold">Colores</label>
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <select class="form-control select2" multiple id="selColors" data-placeholder="Selecciona colores...">
                                @foreach($colorAttribute->values ?? [] as $val)
                                    <option value="{{ $val->id }}" data-hex="{{ $val->hex_color }}">{{ $val->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-danger ml-2" onclick="$('#selColors').val(null).trigger('change')" title="Limpiar colores" style="height: 45px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-block" onclick="generateMatrix()"><i class="fas fa-bolt"></i> Generar</button>
                </div>
            </div>

            <div id="variantsTableContainer" class="table-responsive d-none">
                <table class="table table-hover align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>SKU Generado</th>
                            <th>Variante</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="variantsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 3: BOM (ENGINEERING) --}}
    <script type="text/template" id="tpl_step3">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">Catálogo de Insumos</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Familia de Material</label>
                        <select class="form-control select2" id="bomFamilySelector">
                            <option value="">Seleccione Familia...</option>
                            @foreach($materials ?? [] as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Material Específico / Color</label>
                        <select class="form-control select2" id="bomMaterialSelector" disabled></select>
                        <div id="materialInfo" class="mt-2 small d-none p-2 bg-light rounded border">
                            <div>Stock: <b id="matStock">-</b> | Costo: <b id="matCost" class="text-success">$-</b></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cantidad (Consumo)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="bomQty" placeholder="0.00" step="0.01">
                            <div class="input-group-append">
                                <span class="input-group-text" id="bomUnit">unid</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                         <input class="form-check-input" type="checkbox" id="materialIsPrimary">
                         <label class="form-check-label small" for="materialIsPrimary">Material Base (Principal)</label>
                    </div>
                    <button type="button" class="btn btn-success btn-block" onclick="prepareAddMaterial()">
                        <i class="fas fa-plus-circle"></i> Agregar al BOM
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span class="font-weight-bold">Bill of Materials (BOM)</span>
                    <span class="badge badge-light border" id="bomTotalCostBadge">Costo Total: $0.00</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0" id="bomTable">
                        <thead class="bg-light">
                            <tr>
                                <th>Insumo</th>
                                <th>Variantes</th>
                                <th>Consumo</th>
                                <th>Alcance</th>
                                <th>Costo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="bomTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Sin materiales asignados</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 4: DESIGN (EMBROIDERY) --}}
    <script type="text/template" id="tpl_step4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="step-title"><i class="fas fa-vector-square text-primary"></i> Biblioteca de Diseños</h5>
                <input type="text" class="form-control w-25" placeholder="Buscar diseño..." id="searchDesign">
            </div>

            <div class="design-grid-container">
                <div class="row" id="designGrid">
                    @forelse($designs as $design)
                        @php
                            $export = $design->generalExports->first();
                            $stitches = $export ? number_format($export->stitches_count) : 'N/A';
                            $stitchesRaw = $export->stitches_count ?? 0;
                            $dimensions = $export ? ($export->width_mm . 'x' . $export->height_mm . ' mm') : 'N/A';
                            $colors = $export ? $export->colors_count : 0;
                            $appType = $export->application_type ?? 'General';
                            $image = $design->primaryImage ? $design->primaryImage->url : null;
                        @endphp
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card h-100 design-card" 
                                 onclick="toggleDesign(this, {{ $design->id }}, '{{ $design->name }}', {{ $stitchesRaw }})">
                                <div class="card-img-top design-thumb d-flex align-items-center justify-content-center bg-light" style="height: 140px;">
                                    @if($image)
                                        <img src="{{ asset('storage/' . $image) }}" style="max-height: 120px; max-width: 100%;">
                                    @else
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    @endif
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="text-truncate mb-2 font-weight-bold" title="{{ $design->name }}">{{ $design->name }}</h6>
                                    <div class="mb-2">
                                        <span class="badge badge-secondary">{{ ucfirst($appType) }}</span>
                                    </div>
                                    <div class="row text-center small">
                                        <div class="col-4">
                                            <i class="fas fa-ruler-combined text-primary d-block mb-1"></i>
                                            <strong class="d-block">{{ $dimensions }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-palette text-info d-block mb-1"></i>
                                            <strong class="d-block">{{ $colors }} col</strong>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-th text-success d-block mb-1"></i>
                                            <strong class="d-block">{{ $stitches }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="check-overlay"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted">No hay diseños disponibles</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 5: FINANZAS Y EXTRAS --}}
    <script type="text/template" id="tpl_step5">
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">Servicios Adicionales (Extras)</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Agregar Servicio</label>
                        <select class="form-control select2" id="extrasSelector">
                            <option value="">Seleccionar servicio...</option>
                            @foreach($extras ?? [] as $extra)
                                <option value="{{ $extra->id }}" data-price="{{ $extra->cost_addition }}" data-name="{{ $extra->name }}">
                                    {{ $extra->name }} (+${{ number_format($extra->cost_addition, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-block mb-3" onclick="addExtra()">
                        <i class="fas fa-plus-circle mr-2"></i> Agregar Servicio
                    </button>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="bg-light"><tr><th>Servicio</th><th class="text-right">Costo</th><th></th></tr></thead>
                            <tbody id="extrasTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> Motor de Precios</h5>
                </div>
                <div class="card-body">
                    
                    {{-- DESGLOSE DE MATERIALES --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-uppercase small text-muted"><i class="fas fa-boxes mr-2"></i>Materiales (BOM)</h6>
                            <span class="font-weight-bold text-primary" id="finMatCost">$0.00</span>
                        </div>
                        <div id="finMaterialsList" class="bg-light rounded p-2 small" style="max-height: 120px; overflow-y: auto;">
                            <span class="text-muted">Sin materiales...</span>
                        </div>
                    </div>
                    
                    {{-- BORDADOS --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-uppercase small text-muted"><i class="fas fa-tshirt mr-2"></i>Bordados</h6>
                            <span class="font-weight-bold text-info" id="finEmbCost">$0.00</span>
                        </div>
                        <div class="row bg-light rounded p-2 mx-0">
                            <div class="col-6 small">
                                <span class="text-muted">Total Puntadas:</span>
                                <strong class="d-block" id="finTotalStitches">0</strong>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-0">$/1000 pts:</label>
                                <input type="number" class="form-control form-control-sm" id="finStitchRate" value="1.00" step="0.01" min="0" onchange="recalcFinance()">
                            </div>
                        </div>
                    </div>
                    
                    {{-- EXTRAS --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-uppercase small text-muted"><i class="fas fa-concierge-bell mr-2"></i>Servicios Extras</h6>
                        <span class="font-weight-bold text-warning" id="finExtrasTotal">$0.00</span>
                    </div>
                    
                    {{-- TOTAL --}}
                    <div class="alert alert-dark text-center py-3 mb-3">
                        <small class="text-uppercase d-block mb-1">Costo Total de Producción</small>
                        <h3 class="font-weight-bold mb-0" id="finTotalCost">$0.00</h3>
                    </div>

                    {{-- MARGEN --}}
                    <div class="form-group">
                        <label>Margen de Ganancia (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control font-weight-bold text-primary" id="finMargin" value="35" onkeyup="recalcFinance()">
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted">Fórmula: Costo / (1 - Margen)</small>
                    </div>

                    {{-- PRECIO SUGERIDO --}}
                    <div class="alert alert-success text-center mb-3">
                        <small class="text-uppercase">Precio Sugerido</small>
                        <h2 class="font-weight-bold mb-0" id="finSuggestedPrice">$0.00</h2>
                    </div>
                    
                    {{-- PRECIO FINAL --}}
                    <div class="form-group text-center">
                        <label class="small text-muted">Precio Final (Manual)</label>
                        <input type="number" class="form-control text-center font-weight-bold form-control-lg" name="base_price" id="inpBasePrice" step="0.01">
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 6: REVIEW --}}
    <script type="text/template" id="tpl_step6">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">Confirmación de Ingeniería</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-box mr-2"></i> Producto</h6>
                    <p id="revProductInfo" class="text-muted pl-4"></p>
                    
                    <h6 class="mt-4"><i class="fas fa-th mr-2"></i> Variantes Generadas (<span id="revVarCount">0</span>)</h6>
                    <div id="revVariants" class="pl-4 small text-muted" style="max-height:100px; overflow-y:auto"></div>
                </div>
                <div class="col-md-6 border-left">
                    <h6><i class="fas fa-calculator mr-2"></i> Resumen Financiero</h6>
                    <table class="table table-sm mt-2">
                        <tr><td>Costo Materiales:</td><td class="text-right" id="revMatCost"></td></tr>
                        <tr><td>Costo Extras:</td><td class="text-right" id="revExtraCost"></td></tr>
                        <tr class="font-weight-bold table-active"><td>Precio Venta:</td><td class="text-right text-success h5" id="revPrice"></td></tr>
                    </table>
                    
                    <h6 class="mt-3"><i class="fas fa-clipboard-list mr-2"></i> Estructura</h6>
                    <ul class="small text-muted">
                        <li>Materiales en BOM: <span id="revBomCount">0</span></li>
                        <li>Bordados Asignados: <span id="revEmbCount">0</span></li>
                        <li>Servicios Extras: <span id="revExtraCount">0</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</script>

@stop

@section('css')
    <style>
        /* ESTILOS ENTERPRISE */
        :root {
            --primary-ent: #2c3e50;
            --accent-ent: #3498db;
            --success-ent: #27ae60;
        }

        /* SCOPE TOGGLE BUTTONS (Modal) */
        .scope-toggle-btn {
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 8px;
        }

        .scope-toggle-btn:hover {
            border-color: var(--accent-ent, #3498db);
            color: var(--accent-ent, #3498db);
        }

        .scope-toggle-btn.active {
            background: var(--accent-ent, #3498db);
            border-color: var(--accent-ent, #3498db);
            color: #fff;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }

        /* FIX: Hide AdminLTE footer on this page to remove white space at bottom */
        .main-footer {
            display: none !important;
        }

        /* Ensure wrapper takes full height but no more */
        .content-wrapper {
            min-height: 100vh !important;
            padding-bottom: 0 !important;
        }

        .module-header {
            background: linear-gradient(135deg, var(--primary-ent), #34495e);
            padding: 2rem;
            border-radius: 0 0 15px 15px;
            margin-bottom: 2rem;
        }

        .live-cost-badge {
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .live-cost-badge .value {
            font-weight: 700;
            color: #2ecc71;
            margin-left: 5px;
        }

        /* STEPPER NAVIGATION CONTAINER (Carousel Style) */
        .stepper-nav-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 2rem;
            padding: 0 10px;
        }

        /* PREMIUM CAROUSEL ARROWS */
        .stepper-arrow {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: #fff;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .stepper-arrow:hover:not(:disabled) {
            background: var(--accent-ent, #3498db);
            border-color: var(--accent-ent, #3498db);
            color: #fff;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        .stepper-arrow:active:not(:disabled) {
            transform: scale(0.95);
        }

        .stepper-arrow:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .stepper-arrow i {
            font-size: 1.2rem;
        }

        .stepper-arrow-next.btn-success-mode {
            background: var(--success-ent, #27ae60) !important;
            border-color: var(--success-ent, #27ae60) !important;
            color: #fff !important;
            animation: pulse-success 1.5s infinite;
        }

        @keyframes pulse-success {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(39, 174, 96, 0);
            }
        }

        /* STEPPER */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 0 10px;
            flex: 1;
            max-width: 800px;
        }

        .stepper-wrapper::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .stepper-item {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 80px;
            opacity: 0.6;
            transition: all 0.3s;
        }

        .stepper-item.active {
            opacity: 1;
            transform: scale(1.1);
        }

        .stepper-item.completed .step-counter {
            background: var(--success-ent);
            border-color: var(--success-ent);
            color: white;
        }

        .step-counter {
            width: 35px;
            height: 35px;
            background: #fff;
            border: 2px solid #bdc3c7;
            border-radius: 50%;
            margin: 0 auto 5px;
            line-height: 31px;
            font-weight: bold;
            color: #7f8c8d;
            transition: all 0.3s;
        }

        .stepper-item.active .step-counter {
            border-color: var(--accent-ent);
            color: var(--accent-ent);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
        }

        .step-name {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* CARDS & COMPONENTS */
        .card-radio label {
            cursor: pointer;
            padding: 10px 20px;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .custom-control-input:checked~.custom-control-label::before {
            background-color: var(--accent-ent);
            border-color: var(--accent-ent);
        }

        .card-radio input:checked+label {
            border-color: var(--accent-ent);
            background: rgba(52, 152, 219, 0.05);
            color: var(--accent-ent);
        }

        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
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

        .badge-scope-global {
            background: #e8f6f3;
            color: #1abc9c;
            border: 1px solid #1abc9c;
        }

        .badge-scope-specific {
            background: #fef9e7;
            color: #f1c40f;
            border: 1px solid #f1c40f;
        }

        /* DESIGN GRID */
        .design-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            position: relative;
        }

        .design-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .design-card.selected {
            border-color: var(--accent-ent);
            background: #f0f8ff;
        }

        .check-overlay {
            position: absolute;
            top: 5px;
            right: 5px;
            color: var(--success-ent);
            font-size: 1.2rem;
            display: none;
        }

        .design-card.selected .check-overlay {
            display: block;
        }

        .stop-propagation {
            cursor: default;
        }

        /* --- VISUAL NORMALIZATION (FIX) --- */

        /* Define consistent height / visual variables locally if needed, or just use values */

        /* GLOBAL: Force same height for all interactive elements in forms */
        input.form-control,
        select.form-control,
        .btn-block,
        /* Target the 'Generar' button */
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            height: 45px !important;
            /* Premium height, consistent for ALL */
            min-height: 45px !important;
            display: flex !important;
            align-items: center !important;
            font-size: 0.95rem !important;
            border-radius: 8px !important;
        }

        /* Ensure Textareas maintain their own height/rows */
        textarea.form-control {
            height: auto !important;
            min-height: 100px;
            padding: 12px !important;
            font-size: 0.95rem !important;
            align-items: flex-start !important;
        }

        /* Button specific alignment */
        .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
            font-size: 0.85rem !important;
        }

        /* --- PREMIUM SELECT2 BUTTONS (NUCLEAR FIX) --- */

        /* 1. Ensure space for the button in the text container */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 40px !important;
            line-height: 45px !important;
            /* Match height */
            padding-left: 10px !important;
            color: #495057 !important;
        }

        /* 2. Hide the original 'x' text container completely */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            height: 24px !important;
            width: 24px !important;
            padding: 0 !important;
            margin: 0 !important;

            visibility: hidden !important;
            /* HIde the text 'x' */

            background-color: rgba(231, 76, 60, 0.1) !important;
            border-radius: 50% !important;
            z-index: 100 !important;
        }

        /* 3. Show the ICON using visibility: visible on pseudo-element */
        .select2-container--default .select2-selection--single .select2-selection__clear::after {
            content: "\f00d";
            /* fa-times */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 13px !important;
            color: #e74c3c !important;

            visibility: visible !important;
            /* FORCE VISIBILITY */
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            background-color: #e74c3c !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover::after {
            color: white !important;
        }

        /* Fix Arrow Position */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px !important;
            /* Slightly less than container */
            top: 1px !important;
            right: 10px !important;
        }

        /* --- MULTIPLE SELECT FIXES --- */

        /* Container style */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da !important;
            /* Standard Bootstrap Border */
            background-color: #ffffff !important;
            /* White background (was gray) */
            padding-bottom: 0px !important;
            padding-top: 0px !important;
            transition: all 0.2s;
            overflow: hidden !important;
        }

        /* HIDE the container-level clear button (phantom X on left) */
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            display: none !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        /* Fix Multiple rendered chips alignment */
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            padding-left: 5px !important;
            min-height: 45px !important;
            /* Match container */
        }

        /* Choice (Chip) Style */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary-ent, #2c3e50) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 32px 6px 12px !important;
            /* More padding left, space for X right */
            font-size: 0.9rem !important;
            margin-top: 4px !important;
            margin-bottom: 4px !important;
            margin-left: 4px !important;
            margin-right: 4px !important;
            position: relative !important;
            height: 30px !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        /* NUCLEAR: Remove the phantom left X completely */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute !important;
            right: 4px !important;
            left: auto !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 20px !important;
            height: 20px !important;
            border: none !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, 0.2) !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 0 !important;
            /* HIDE THE TEXT X */
            color: transparent !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background: rgba(255, 255, 255, 0.4) !important;
        }

        /* Show ICON using ::after */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove::after {
            content: "\f00d";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 10px !important;
            color: white !important;
        }

        /* Search Field */
        .select2-container--default .select2-search--inline .select2-search__field {
            margin-top: 0 !important;
            height: 30px !important;
            line-height: 30px !important;
            margin-left: 5px !important;
            font-size: 0.95rem !important;
        }

        /* Standardize labels */
        label {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px;
            color: #5a6268;
            margin-bottom: 6px !important;
            text-transform: uppercase;
        }
    </style>
@stop

@section('js')
    <script>
        // --- STATE MANAGEMENT ---
        const State = {
            step: 1,
            definition: {},
            variants: [],
            bom: [],
            designs: [], // {id, name, stitches, position}
            extras: [], // {id, name, price}
            financials: {
                material_cost: 0,
                embroidery_cost: 0,
                extras_cost: 0,
                total_cost: 0,
                margin: 35,
                price: 0
            }
        };

        $(document).ready(function() {
            loadTemplates();
            initPlugins();
            updateButtons();

            // Listener para cambio de Familia de Material (Paso 3)
            // Usamos delegación de eventos porque el select se crea dinámicamente
            $(document).on('change', '#bomFamilySelector', function() {
                const familyId = $(this).val();
                fetchMaterialVariants(familyId);
            });

            // Listener para cambio de variante de material
            $(document).on('change', '#bomMaterialSelector', function() {
                const opt = $(this).find(':selected');
                if (opt.val()) {
                    $('#matStock').text(opt.data('stock'));
                    $('#matCost').text('$' + parseFloat(opt.data('cost')).toFixed(2));
                    // Update unit label
                    $('#bomUnit').text(opt.data('unit') || 'unid');
                    $('#materialInfo').removeClass('d-none');
                } else {
                    $('#materialInfo').addClass('d-none');
                    $('#bomUnit').text('unid');
                }
            });

            // Reset modal when opened - both buttons inactive
            $('#materialScopeModal').on('show.bs.modal', function() {
                $('.scope-toggle-btn').removeClass('active');
                $('#materialScopeValue').val('');
                $('#specificVariantsContainer').addClass('d-none');
            });

            // Inicializar Select2 en el modal
            $('#targetVariantsSelect').select2({
                dropdownParent: $('#materialScopeModal'),
                placeholder: "Seleccione variantes...",
                width: '100%'
            });
        });

        // Scope selection toggle function
        window.selectScope = function(scope) {
            // Remove active from all buttons
            $('.scope-toggle-btn').removeClass('active');

            // Add active to clicked button
            if (scope === 'global') {
                $('#btnScopeGlobal').addClass('active');
                $('#specificVariantsContainer').addClass('d-none');
            } else {
                $('#btnScopeSpecific').addClass('active');
                $('#specificVariantsContainer').removeClass('d-none');
            }

            // Set value
            $('#materialScopeValue').val(scope);
        };

        function loadTemplates() {
            try {
                $('#step1-content').html($('#tpl_step1').html());
            } catch (e) {}
            try {
                $('#step2-content').html($('#tpl_step2').html());
            } catch (e) {}
            try {
                $('#step3-content').html($('#tpl_step3').html());
            } catch (e) {}
            try {
                $('#step4-content').html($('#tpl_step4').html());
            } catch (e) {}
            try {
                $('#step5-content').html($('#tpl_step5').html());
            } catch (e) {}
            try {
                $('#step6-content').html($('#tpl_step6').html());
            } catch (e) {}
        }

        function initPlugins() {
            $('.select2').select2({
                width: '100%',
                placeholder: "Seleccione...",
                allowClear: true
            });
        }

        // --- NAVIGATION ---
        window.navigate = function(direction) {
            if (direction === 1 && !validateStep(State.step)) return;
            const nextStep = State.step + direction;
            if (nextStep > 6) {
                submitForm();
                return;
            }
            if (nextStep < 1) return;

            $(`#step${State.step}-content`).addClass('d-none');
            $(`.stepper-item[data-step="${State.step}"]`).removeClass('active').addClass('completed');

            State.step = nextStep;

            $(`#step${State.step}-content`).removeClass('d-none');
            $(`.stepper-item[data-step="${State.step}"]`).addClass('active');

            // Logic Hooks
            if (State.step === 5) renderExtrasTable(); // Render table if logic needed
            if (State.step === 6) renderReview();

            updateButtons();
            window.scrollTo(0, 0);
        };

        function validateStep(step) {
            if (step === 1) {
                const name = $('#inpName').val();
                const sku = $('#inpSku').val();
                if (!name || !sku) {
                    Swal.fire('Falta información', 'Nombre y SKU son obligatorios', 'warning');
                    return false;
                }
                State.definition = {
                    name,
                    sku,
                    category: $('#inpCategory option:selected').text(),
                    desc: $('#inpDesc').val()
                };
            }
            if (step === 2 && State.variants.length === 0) {
                Swal.fire('Atención', 'Genere al menos una variante', 'warning');
                return false;
            }
            if (step === 4 && State.designs.length === 0) {
                Swal.fire('Atención', 'Seleccione al menos un diseño o bordado', 'warning');
                return false;
            }
            return true;
            return true;
        }

        function updateButtons() {
            $('#btnPrev').prop('disabled', State.step === 1);
            if (State.step === 6) {
                $('#btnNext').html('<i class="fas fa-check"></i>').addClass('btn-success-mode');
            } else {
                $('#btnNext').html('<i class="fas fa-chevron-right"></i>').removeClass('btn-success-mode');
            }
        }

        // --- STEP 2: VARIANTS ---
        window.generateMatrix = function() {
            const sizes = $('#selSizes').select2('data');
            const colors = $('#selColors').select2('data');
            const baseSku = $('#inpSku').val();

            if (!sizes.length || !colors.length || !baseSku) {
                Swal.fire('Error', 'Verifique SKU base, tallas y colores', 'error');
                return;
            }

            const tbody = $('#variantsTableBody');
            let addedCount = 0;
            let duplicateCount = 0;

            sizes.forEach(s => {
                colors.forEach(c => {
                    // Check if this combination already exists
                    const exists = State.variants.find(v =>
                        v.size_id == s.id && v.color_id == c.id
                    );

                    if (exists) {
                        duplicateCount++;
                        return; // Skip duplicate
                    }

                    const sku = `${baseSku}-${s.text.charAt(0)}-${c.text.substring(0,3)}`.toUpperCase();
                    const tempId = Math.random().toString(36).substr(2, 9);

                    State.variants.push({
                        temp_id: tempId,
                        sku,
                        size: s.text,
                        color: c.text,
                        size_id: s.id,
                        color_id: c.id
                    });

                    tbody.append(`<tr data-id="${tempId}">
                        <td class="font-weight-bold text-primary">${sku}</td>
                        <td>${s.text} / ${c.text}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="removeVariant('${tempId}', this)"><i class="fas fa-trash"></i></button></td>
                    </tr>`);

                    addedCount++;
                });
            });

            $('#variantsTableContainer').removeClass('d-none');

            // Feedback to user
            if (duplicateCount > 0 && addedCount > 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Variantes generadas',
                    html: `<b>${addedCount}</b> variantes nuevas agregadas.<br><b>${duplicateCount}</b> duplicadas omitidas.`,
                    timer: 2500,
                    showConfirmButton: false
                });
            } else if (duplicateCount > 0 && addedCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin cambios',
                    text: 'Todas las combinaciones ya existen.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else if (addedCount > 0) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Generado!',
                    text: `${addedCount} variantes agregadas.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            // Clear selections after generating
            $('#selSizes').val(null).trigger('change');
            $('#selColors').val(null).trigger('change');
        };

        window.removeVariant = function(id, btn) {
            State.variants = State.variants.filter(v => v.temp_id !== id);
            $(btn).closest('tr').remove();
        };

        // --- STEP 3: MATERIALS (AJAX RESTORED) ---
        // Variable temporal para el modal
        let tempMaterial = null;

        window.fetchMaterialVariants = function(familyId) {
            if (!familyId) {
                $('#bomMaterialSelector').empty().prop('disabled', true);
                return;
            }

            // URL original que usabas para traer conversiones/variantes
            const url = '{{ route('material-variants.conversiones', ['materialId' => ':id']) }}'.replace(':id',
                familyId);

            $.get(url, function(data) {
                const sel = $('#bomMaterialSelector');
                sel.empty().prop('disabled', false).append('<option value="">Seleccione...</option>');

                data.forEach(v => {
                    // Adaptar según la respuesta JSON real de tu controlador
                    // Asumiendo estructura: {id, text, cost_base, stock_real, symbol}
                    sel.append(
                        `<option value="${v.id}" data-cost="${v.cost_base}" data-stock="${v.stock_real}" data-unit="${v.symbol}" data-name="${v.text}">${v.text}</option>`
                    );
                });
            }).fail(function() {
                Swal.fire('Error', 'No se pudieron cargar las variantes', 'error');
            });
        };

        window.prepareAddMaterial = function() {
            const matId = $('#bomMaterialSelector').val();
            const qty = parseFloat($('#bomQty').val());

            if (!matId || !qty) {
                Swal.fire('Error', 'Complete los datos del material', 'warning');
                return;
            }

            const opt = $('#bomMaterialSelector option:selected');
            tempMaterial = {
                material_id: matId,
                name: opt.data('name'),
                cost: parseFloat(opt.data('cost')),
                unit: opt.data('unit'),
                qty: qty,
                is_primary: $('#materialIsPrimary').is(':checked')
            };

            // Llenar selector de variantes en modal
            const vSel = $('#targetVariantsSelect');
            vSel.empty();
            State.variants.forEach(v => {
                vSel.append(`<option value="${v.temp_id}">${v.sku} (${v.size}/${v.color})</option>`);
            });

            $('#materialScopeModal').modal('show');
        };

        window.confirmAddMaterial = function() {
            const scope = $('#materialScopeValue').val();
            let targets = [];

            // Validate that scope was selected
            if (!scope) {
                Swal.fire('Error', 'Seleccione un alcance (Global o Específico)', 'warning');
                return;
            }

            // Validate Scope Targets
            if (scope === 'specific') {
                targets = $('#targetVariantsSelect').val();
                if (!targets || !targets.length) {
                    Swal.fire('Error', 'Seleccione variantes destino', 'warning');
                    return;
                }
                // Sort targets to ensure consistency in comparison
                targets.sort();
            }

            // Check if Material + Scope + Targets combination exists (LOGIC FIX)
            const existingIndex = State.bom.findIndex(item => {
                if (item.material_id != tempMaterial.material_id) return false;
                if (item.scope !== scope) return false;

                // Compare arrays for specific scope
                // Using join to create a comparable string signature
                const itemTargets = (item.targets || []).sort().join(',');
                const newTargets = targets.join(',');

                return itemTargets === newTargets;
            });

            if (existingIndex > -1) {
                // ACCUMULATE (UPDATE EXISTING)
                const existing = State.bom[existingIndex];

                // Precise JS addition (avoid floating point errors)
                const currentQty = parseFloat(existing.qty);
                const addedQty = parseFloat(tempMaterial.qty);
                const newQty = (currentQty * 100 + addedQty * 100) / 100;

                existing.qty = newQty;
                existing.calculated_total = existing.cost * existing.qty;

                Swal.fire({
                    icon: 'info',
                    title: 'Material Actualizado',
                    text: `Se sumaron las cantidades. Nuevo total: ${existing.qty} ${existing.unit}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                // ADD NEW
                const costTotal = tempMaterial.cost * tempMaterial.qty;
                State.bom.push({
                    ...tempMaterial,
                    scope,
                    targets,
                    calculated_total: costTotal
                });
            }

            $('#materialScopeModal').modal('hide');

            renderBOM();
            recalcFinance();

            // Reset form
            $('#bomQty').val('');
            $('#bomMaterialSelector').val('').trigger('change');
            $('input[name="materialScope"][value="global"]').prop('checked', true).trigger('change');
            $('#targetVariantsSelect').val(null).trigger('change');
        };

        function renderBOM() {
            const tbody = $('#bomTableBody');
            tbody.empty();
            let total = 0;

            State.bom.forEach((m, idx) => {
                total += m.calculated_total;

                let badge = '';
                let variantInfo = '';

                if (m.scope === 'global') {
                    badge = '<span class="badge badge-pill badge-secondary">Global</span>';
                    variantInfo = '<span class="text-muted small">Aplica a todas</span>';
                } else {
                    badge = '<span class="badge badge-pill badge-info">Específico</span>';

                    if (m.targets && m.targets.length > 0) {
                        const targetNames = m.targets.map(tid => {
                            const v = State.variants.find(va => va.temp_id === tid);
                            // ONLY Name/Color, NO SKU
                            return v ?
                                `<span class="badge badge-light border mr-1 mb-1" style="font-size: 0.85rem;">${v.size} / ${v.color}</span>` :
                                '';
                        }).join('');

                        variantInfo =
                            `<div class="small text-muted mb-1"><strong>Aplica en:</strong></div><div class="d-flex flex-wrap">${targetNames}</div>`;
                    } else {
                        variantInfo =
                            '<span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Sin asignación</span>';
                    }
                }

                tbody.append(`<tr>
                <td class="align-middle border-right">
                    <div class="font-weight-bold text-dark">${m.name}</div>
                    ${m.is_primary ? '<span class="badge badge-warning text-white"><i class="fas fa-star text-white"></i> Principal</span>' : ''}
                </td>
                <td class="align-middle border-right" style="max-width: 300px;">
                    ${variantInfo}
                </td>
                <td class="align-middle font-weight-bold text-center">${m.qty} ${m.unit}</td>
                <td class="align-middle text-center">${badge}</td>
                <td class="align-middle font-weight-bold text-right">$${m.calculated_total.toFixed(2)}</td>
                <td class="align-middle text-center"><button class="btn btn-sm btn-outline-danger btn-icon rounded-circle" onclick="removeBOM(${idx})"><i class="fas fa-trash"></i></button></td>
            </tr>`);
            });
            State.financials.material_cost = total;
            $('#bomTotalCostBadge').text(`Costo Total: $${total.toFixed(2)}`);
        }

        window.removeBOM = function(idx) {
            State.bom.splice(idx, 1);
            renderBOM();
            recalcFinance();
        };

        // --- STEP 4: DESIGNS ---
        window.toggleDesign = function(el, id, name, stitches) {
            const card = $(el);
            const isSelected = card.hasClass('selected');

            if (isSelected) {
                card.removeClass('selected');
                State.designs = State.designs.filter(d => d.id !== id);
            } else {
                card.addClass('selected');
                State.designs.push({
                    id,
                    name,
                    stitches: stitches || 0
                });
            }

            // Recalcular finanzas inmediatamente
            recalcFinance();
        };

        window.updateDesignPosition = function(id, pos) {
            const d = State.designs.find(d => d.id === id);
            if (d) d.position = pos;
        };

        // --- STEP 5: EXTRAS & FINANCE ---
        window.addExtra = function() {
            const sel = $('#extrasSelector option:selected');
            const id = $('#extrasSelector').val();

            if (!id) return;
            if (State.extras.find(e => e.id == id)) {
                Swal.fire('Ya agregado', '', 'info');
                return;
            }

            State.extras.push({
                id,
                name: sel.data('name'),
                price: parseFloat(sel.data('price'))
            });
            renderExtrasTable();
            recalcFinance();
        };

        function renderExtrasTable() {
            const tbody = $('#extrasTableBody');
            tbody.empty();
            let total = 0;
            State.extras.forEach((e, idx) => {
                total += e.price;
                tbody.append(`<tr>
                <td>${e.name}</td>
                <td class="text-right">$${e.price.toFixed(2)}</td>
                <td><i class="fas fa-trash text-danger" style="cursor:pointer" onclick="removeExtra(${idx})"></i></td>
            </tr>`);
            });
            State.financials.extras_cost = total;
        }

        window.removeExtra = function(idx) {
            State.extras.splice(idx, 1);
            renderExtrasTable();
            recalcFinance();
        };

        window.recalcFinance = function() {
            const f = State.financials;

            // Calcular total de puntadas de diseños seleccionados
            const totalStitches = State.designs.reduce((sum, d) => sum + (d.stitches || 0), 0);

            // Obtener la tasa por 1000 puntadas (default 1.00)
            const stitchRate = parseFloat($('#finStitchRate').val()) || 1.00;

            // Calcular costo de bordado: (puntadas / 1000) * tasa
            f.embroidery_cost = (totalStitches / 1000) * stitchRate;

            // Calcular costo total
            f.total_cost = f.material_cost + f.embroidery_cost + f.extras_cost;

            // Renderizar lista de materiales
            const matList = $('#finMaterialsList');
            if (State.bom.length > 0) {
                let html = '';
                State.bom.forEach(m => {
                    html += `<div class="d-flex justify-content-between py-1 border-bottom">
                        <span>${m.name} <small class="text-muted">(${m.qty} ${m.unit})</small></span>
                        <span class="font-weight-bold">$${m.calculated_total.toFixed(2)}</span>
                    </div>`;
                });
                matList.html(html);
            } else {
                matList.html('<span class="text-muted">Sin materiales...</span>');
            }

            // Actualizar UI
            $('#finMatCost').text(`$${f.material_cost.toFixed(2)}`);
            $('#finEmbCost').text(`$${f.embroidery_cost.toFixed(2)}`);
            $('#finTotalStitches').text(totalStitches.toLocaleString());
            $('#finExtrasTotal').text(`$${f.extras_cost.toFixed(2)}`);
            $('#finTotalCost').text(`$${f.total_cost.toFixed(2)}`);
            $('#headerCost').text(`$${f.total_cost.toFixed(2)}`);

            // Calcular precio sugerido con margen
            const margin = parseFloat($('#finMargin').val()) || 0;
            if (margin < 100) {
                f.price = f.total_cost / (1 - (margin / 100));
            }
            $('#finSuggestedPrice').text(`$${f.price.toFixed(2)}`);
            $('#inpBasePrice').val(f.price.toFixed(2));
        };

        // --- STEP 6: REVIEW ---
        window.renderReview = function() {
            $('#revProductInfo').html(`
            <b>${State.definition.name}</b><br>
            SKU: ${State.definition.sku}<br>
            Categoría: ${State.definition.category}
        `);

            $('#revVarCount').text(State.variants.length);
            $('#revVariants').html(State.variants.map(v => `${v.size} / ${v.color}`).join('<br>'));

            $('#revMatCost').text(`$${State.financials.material_cost.toFixed(2)}`);
            $('#revExtraCost').text(`$${State.financials.extras_cost.toFixed(2)}`);
            $('#revPrice').text(`$${State.financials.price.toFixed(2)}`);

            $('#revBomCount').text(State.bom.length);
            $('#revEmbCount').text(State.designs.length);
            $('#revExtraCount').text(State.extras.length);
        };

        // --- SUBMIT ---
        window.submitForm = function() {
            $('#h_variants').val(JSON.stringify(State.variants));
            $('#h_materials').val(JSON.stringify(State.bom));
            $('#h_embroideries').val(JSON.stringify(State.designs));
            $('#h_extras').val(JSON.stringify(State.extras));
            $('#h_financials').val(JSON.stringify(State.financials));

            Swal.fire({
                title: '¿Crear Producto?',
                text: `Precio Final: $${State.financials.price.toFixed(2)}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Fabricar'
            }).then((res) => {
                if (res.isConfirmed) $('#productForm').submit();
            });
        };
    </script>
@stop
