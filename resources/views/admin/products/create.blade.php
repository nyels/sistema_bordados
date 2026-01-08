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

    {{-- DESIGN CONFIG MODAL (IMPROVED - Uses application_types from DB) --}}
    <div class="modal fade" id="designConfigModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-tshirt mr-2"></i>Configurar Diseño</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="designModalImage" src="" alt="Preview"
                            style="max-height: 80px; max-width: 100%;" class="rounded shadow-sm">
                        <h6 id="designModalName" class="mt-2 font-weight-bold mb-0"></h6>
                        <small class="text-muted" id="designModalStitches"></small>
                    </div>

                    {{-- ALREADY CONFIGURED POSITIONS (FEEDBACK) --}}
                    <div id="designConfiguredPositions" class="mb-3 d-none">
                        <label class="font-weight-bold small text-success"><i class="fas fa-check-circle mr-1"></i>
                            Posiciones ya configuradas:</label>
                        <div id="configuredPositionsList" class="d-flex flex-wrap gap-1"></div>
                    </div>

                    {{-- POSITION SELECTION FROM DB (with Select2 search) --}}
                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-map-marker-alt mr-1"></i> Agregar en
                            Posición</label>
                        <select class="form-control select2-position" id="designPositionSelect" style="width:100%">
                            <option value="">Buscar posición...</option>
                            @foreach ($applicationTypes as $appType)
                                <option value="{{ $appType->id }}" data-slug="{{ $appType->slug }}"
                                    data-desc="{{ $appType->descripcion }}">
                                    {{ $appType->nombre_aplicacion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SCOPE SELECTION --}}
                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-bullseye mr-1"></i> Alcance</label>
                        <div class="d-flex gap-2">
                            <button type="button"
                                class="btn btn-outline-secondary btn-sm flex-fill design-scope-btn active"
                                data-scope="global" onclick="selectDesignScope('global')">
                                <i class="fas fa-globe"></i> Todas las Variantes
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill design-scope-btn"
                                data-scope="specific" onclick="selectDesignScope('specific')">
                                <i class="fas fa-filter"></i> Variantes Específicas
                            </button>
                        </div>
                        <input type="hidden" id="designScopeValue" value="global">
                        <div id="designVariantsContainer" class="d-none mt-2">
                            <select class="form-control select2" multiple id="designTargetVariants"
                                data-placeholder="Seleccione variantes..."></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-block">
                    {{-- Added positions summary --}}
                    <div id="justAddedFeedback" class="alert alert-success py-2 mb-2 d-none">
                        <i class="fas fa-check mr-1"></i> <span id="justAddedText"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="fas fa-layer-group mr-1"></i><span id="designsAddedCount">0</span> posición(es)
                            configuradas
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary px-3" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Cerrar
                            </button>
                            <button type="button" class="btn btn-info px-4" onclick="confirmAddDesign()">
                                <i class="fas fa-plus mr-1"></i>Agregar
                            </button>
                        </div>
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
                {{-- LEFT COLUMN: FORM FIELDS --}}
                <div class="col-md-8">
                {{-- ROW 1: NAME + SKU --}}
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre Comercial *</label>
                                <input type="text" class="form-control form-control-lg" name="name" id="inpName" placeholder="Ej: Guayabera Presidencial Lino" required oninput="generateSKU()">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold">SKU Base</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-uppercase bg-light" name="sku" id="inpSku" readonly>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleSkuEdit()" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Auto-generado</small>
                            </div>
                        </div>
                    </div>
                    {{-- ROW 2: CATEGORY + STATUS --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Categoría</label>
                                <select class="form-control" name="category_id" id="inpCategory">
                                    <option value="">-- Selecciona una categoría --</option>
                                    @foreach($categories ?? [] as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Estado</label>
                                <select class="form-control" name="status" id="inpStatus">
                                    <option value="">-- Selecciona un estado --</option>
                                    <option value="draft">Borrador</option>
                                    <option value="active" selected>Activo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    {{-- ROW 3: DESCRIPTION --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Descripción</label>
                                <textarea class="form-control" name="description" id="inpDesc" rows="3" placeholder="Descripción detallada del producto..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: IMAGE DROPZONE --}}
                <div class="col-md-4 d-flex flex-column">
                    <label class="font-weight-bold">Imagen del Producto</label>
                    <div class="custom-dropzone text-center p-3 flex-grow-1" id="productImageDropzone">
                        <input type="file" name="image" id="inpImage" accept="image/*" class="d-none" onchange="previewImage(this)">
                        
                        {{-- PLACEHOLDER STATE --}}
                        <div id="dropzonePlaceholder">
                            <div class="mb-2">
                                <i class="fas fa-cloud-upload-alt text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1">Arrastra tu imagen aquí</h6>
                            <p class="small text-muted mb-0">o haz clic para seleccionar</p>
                            <div class="mt-2 text-muted small">PNG, JPG, WEBP (Max 2MB)</div>
                            <div class="mt-1 text-danger small d-none" id="imageErrorMsg"></div>
                        </div>

                        {{-- PREVIEW STATE --}}
                        <div id="dropzonePreview" class="d-none position-relative w-100 h-100">
                            <img id="imgPreview" src="" class="img-fluid rounded" style="max-height: 250px; max-width: 100%; object-fit: contain;">
                            <button type="button" class="btn btn-danger btn-sm rounded-circle position-absolute" 
                                    style="top: 0; right: 0; width: 30px; height: 30px; padding: 0; line-height: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"
                                    onclick="removeImage(event)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
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
            
            <div class="row">
                {{-- LEFT COLUMN: Selectors --}}
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white font-weight-bold">Configurar Variantes</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Tallas</label>
                                <div class="position-relative">
                                    <select class="form-control select2" multiple id="selSizes" data-placeholder="Selecciona tallas...">
                                        @foreach($sizeAttribute->values ?? [] as $val)
                                            <option value="{{ $val->id }}">{{ $val->value }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fas fa-times clear-btn-internal" 
                                       onclick="$('#selSizes').val(null).trigger('change')" 
                                       title="Limpiar tallas"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Colores</label>
                                <div class="position-relative">
                                    <select class="form-control select2" multiple id="selColors" data-placeholder="Selecciona colores...">
                                        @foreach($colorAttribute->values ?? [] as $val)
                                            <option value="{{ $val->id }}" data-hex="{{ $val->hex_color }}">{{ $val->value }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fas fa-times clear-btn-internal" 
                                       onclick="$('#selColors').val(null).trigger('change')" 
                                       title="Limpiar colores"></i>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary btn-block btn-lg" onclick="generateMatrix()">
                                <i class="fas fa-bolt mr-2"></i>Generar Variantes
                            </button>
                        </div>
                    </div>
                </div>
                
                {{-- RIGHT COLUMN: Variants Table --}}
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">Variantes Generadas</span>
                            <span class="badge badge-primary" id="variantCountBadge">0</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="variantsTableContainer" class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>SKU Generado</th>
                                            <th>Variante</th>
                                            <th class="text-center" style="width:80px">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantsTableBody">
                                        <tr id="noVariantsRow">
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle mr-2"></i>Seleccione tallas y colores, luego presione Generar
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <div class="position-relative">
                            <select class="form-control select2" id="bomFamilySelector" multiple data-maximum-selection-length="1" data-placeholder="Seleccione Familia...">
                                <option value=""></option>
                                @foreach($materials ?? [] as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-times clear-btn-internal" 
                               onclick="$('#bomFamilySelector').val(null).trigger('change')" 
                               title="Limpiar selección"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Material Específico / Color</label>
                        <div class="position-relative">
                            <select class="form-control select2" id="bomMaterialSelector" multiple data-maximum-selection-length="1" disabled data-placeholder="Seleccione Material..."></select>
                            <i class="fas fa-times clear-btn-internal" 
                               onclick="$('#bomMaterialSelector').val(null).trigger('change')" 
                               title="Limpiar selección"></i>
                        </div>
                        <div id="materialInfo" class="mt-2 small d-none p-2 bg-light rounded border">
                            <div>Stock: <b id="matStock">-</b> | Costo: <b id="matCost" class="text-success">$-</b></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cantidad (Consumo)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="bomQty" placeholder="0.00" step="0.01" oninput="updateBomCostPreview()">
                            <div class="input-group-append">
                                <span class="input-group-text" id="bomUnit">unid</span>
                            </div>
                        </div>
                        <div id="bomCostPreview" class="mt-2 small text-success font-weight-bold d-none">
                            <i class="fas fa-calculator"></i> Costo Estimado: <span id="bomCostPreviewValue">$0.00</span>
                        </div>
                    </div>
                    
                    {{-- INLINE SCOPE SELECTION --}}
                    <div class="form-group">
                        <label>Alcance del Material</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill scope-inline-btn active" data-scope="global" onclick="setInlineScope('global')">
                                <i class="fas fa-globe"></i> Global
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill scope-inline-btn" data-scope="specific" onclick="setInlineScope('specific')">
                                <i class="fas fa-filter"></i> Específico
                            </button>
                        </div>
                        <input type="hidden" id="inlineScopeValue" value="global">
                        <div id="inlineVariantsContainer" class="d-none">
                            <select class="form-control select2" multiple id="inlineTargetVariants" data-placeholder="Seleccione variantes..."></select>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                         <input class="form-check-input" type="checkbox" id="materialIsPrimary">
                         <label class="form-check-label small" for="materialIsPrimary">Material Base (Principal)</label>
                    </div>
                    <button type="button" class="btn btn-success btn-block" onclick="addMaterialDirect()">
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
                <h5 class="step-title"><i class="fas fa-vector-square text-primary"></i> Biblioteca de Diseños</h5>
                <div class="d-flex align-items-center">
                    {{-- FIX: No Embroidery Toggle --}}
                    <div class="custom-control custom-switch mr-4">
                        <input type="checkbox" class="custom-control-input" id="toggleNoDesign" onchange="toggleNoDesignMode()">
                        <label class="custom-control-label font-weight-bold text-muted" for="toggleNoDesign">Producto Sin Bordado/Liso</label>
                    </div>
                    <input type="text" class="form-control" style="width: 200px;" placeholder="Buscar diseño..." id="searchDesign">
                </div>
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
                                 onclick="toggleDesign(this, {{ $design->id }}, '{{ addslashes($design->name) }}', {{ $stitchesRaw }}, '{{ $image ? asset('storage/' . $image) : '' }}')"
                                 data-design-id="{{ $design->id }}">
                                <div class="card-img-top design-thumb d-flex align-items-center justify-content-center bg-light" style="height: 140px;">
                                    @if($image)
                                        <img src="{{ asset('storage/' . $image) }}" style="max-height: 120px; max-width: 100%;">
                                    @else
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    @endif
                                </div>
                                <div class="card-body p-3 text-center">
                                    <h6 class="text-truncate mb-2 font-weight-bold" title="{{ $design->name }}">{{ $design->name }}</h6>
                                    <div class="mb-2 d-flex justify-content-center">
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
                <div class="card-header bg-primary text-white text-center py-3">
                    <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> Motor de Precios</h5>
                </div>
                <div class="card-body">
                    
                    {{-- DESGLOSE DE MATERIALES --}}
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 font-weight-bold"><i class="fas fa-boxes mr-2 text-primary"></i>Materiales (BOM)</h6>
                            <span class="h5 font-weight-bold text-primary mb-0" id="finMatCost">$0.00</span>
                        </div>
                        <div id="finMaterialsList" class="bg-light rounded p-3" style="max-height: 150px; overflow-y: auto;">
                            <span class="text-muted">Sin materiales...</span>
                        </div>
                    </div>
                    
                    {{-- BORDADOS --}}
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 font-weight-bold"><i class="fas fa-tshirt mr-2 text-info"></i>Bordados</h6>
                            <span class="h5 font-weight-bold text-info mb-0" id="finEmbCost">$0.00</span>
                        </div>
                        <div class="row bg-light rounded p-3 mx-0">
                            <div class="col-6">
                                <span class="small text-muted d-block">Total Puntadas:</span>
                                <strong class="h5 mb-0" id="finTotalStitches">0</strong>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1">Costo por 1,000 pts:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control font-weight-bold" id="finStitchRate" value="1.00" step="0.01" min="0" onchange="recalcFinance()">
                                </div>
                                <small class="text-muted"><i class="fas fa-info-circle"></i> Costo por cada 1,000 puntadas (Hilo + Desgaste)</small>
                            </div>
                        </div>
                    </div>
                    
                     {{-- MANO DE OBRA (NEW) --}}
                     <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                             <h6 class="mb-0 font-weight-bold"><i class="fas fa-hand-holding-usd mr-2 text-secondary"></i>Mano de Obra / Indirectos</h6>
                             <span class="h5 font-weight-bold text-secondary mb-0" id="finLaborCostDisplay">$0.00</span>
                        </div>
                        <div class="bg-light rounded p-3">
                             <label class="small text-muted mb-1">Costo Fijo Operativo:</label>
                             <div class="input-group">
                                 <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                 <input type="number" class="form-control font-weight-bold" id="finLaborInput" value="" placeholder="0.00" step="0.01" min="0" onchange="recalcFinance()">
                             </div>
                             <small class="text-muted mt-1">Incluye corte, confección, empaquetado, etc.</small>
                        </div>
                    </div>
                    
                    {{-- EXTRAS --}}
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-concierge-bell mr-2 text-warning"></i>Servicios Extras</h6>
                        <span class="h5 font-weight-bold text-warning mb-0" id="finExtrasTotal">$0.00</span>
                    </div>
                    
                    {{-- TOTAL --}}
                    <div class="alert alert-dark text-center py-4 mb-4">
                        <span class="text-uppercase small d-block mb-2">Costo Total de Producción</span>
                        <h2 class="font-weight-bold mb-0" id="finTotalCost">$0.00</h2>
                    </div>

                    {{-- MARGEN --}}
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Margen de Ganancia</label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control font-weight-bold text-primary text-center" id="finMargin" value="35" onkeyup="recalcFinance()">
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted mt-1 d-block">Fórmula: Costo / (1 - Margen%)</small>
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

        {{-- STEP 6: REVIEW (PROFESSIONAL - RESTRUCTURED) --}}
        <script type="text/template" id="tpl_step6">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-clipboard-check mr-2"></i>Confirmación de Ingeniería</span>
            <span class="badge badge-light px-3 py-2">Paso Final</span>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- LEFT COLUMN: All Items --}}
                <div class="col-md-6 border-right">
                    {{-- Product Identity --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-fingerprint mr-2 text-primary"></i>Identidad del Producto</h6>
                        <div class="bg-light rounded p-3">
                            <h5 id="revProductName" class="font-weight-bold mb-1"></h5>
                            <p class="mb-0"><strong>SKU:</strong> <code id="revProductSku"></code></p>
                            <p class="mb-0"><strong>Categoría:</strong> <span id="revProductCategory"></span></p>
                        </div>
                    </div>
                    
                    {{-- Variants --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-th mr-2 text-info"></i>Variantes (<span id="revVarCount">0</span>)</h6>
                        <div id="revVariants" class="bg-light rounded p-2" style="max-height:100px; overflow-y:auto"></div>
                    </div>
                    
                    {{-- BOM Materials --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-boxes mr-2 text-primary"></i>Materiales BOM (<span id="revBomCount">0</span>)</h6>
                        <div id="revBomList" class="bg-light rounded p-2" style="max-height:150px; overflow-y:auto"></div>
                    </div>
                    
                    {{-- Designs/Embroideries (MOVED HERE) --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-tshirt mr-2 text-info"></i>Bordados Configurados (<span id="revEmbCount">0</span>)</h6>
                        <div id="revDesignsList" class="bg-light rounded p-2" style="max-height:200px; overflow-y:auto"></div>
                    </div>
                    
                    {{-- Extras (MOVED HERE) --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-concierge-bell mr-2 text-warning"></i>Servicios Extras (<span id="revExtraCount">0</span>)</h6>
                        <div id="revExtrasList" class="bg-light rounded p-2" style="max-height:100px; overflow-y:auto"></div>
                    </div>
                </div>
                
                {{-- RIGHT COLUMN: Financial Summary Only --}}
                <div class="col-md-6">
                    {{-- Financial Summary --}}
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-calculator mr-2 text-success"></i>Resumen Financiero</h6>
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td class="bg-light w-50">Costo Materiales:</td>
                                    <td class="text-right font-weight-bold h6 mb-0" id="revMatCost"></td>
                                </tr>
                                <tr>
                                    <td class="bg-light">Costo Bordados:</td>
                                    <td class="text-right font-weight-bold h6 mb-0" id="revEmbCost"></td>
                                </tr>
                                <tr>
                                    <td class="bg-light">Mano de Obra:</td>
                                    <td class="text-right font-weight-bold h6 mb-0" id="revLaborCost"></td>
                                </tr>
                                <tr>
                                    <td class="bg-light">Costo Extras:</td>
                                    <td class="text-right font-weight-bold h6 mb-0" id="revExtraCost"></td>
                                </tr>
                                <tr class="table-secondary">
                                    <td><strong>Costo Total Producción:</strong></td>
                                    <td class="text-right h5 mb-0"><strong id="revTotalCost"></strong></td>
                                </tr>
                                <tr>
                                    <td class="bg-light">Margen Aplicado:</td>
                                    <td class="text-right font-weight-bold" id="revMargin"></td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>PRECIO VENTA:</strong></td>
                                    <td class="text-right text-success h3 mb-0" id="revPrice"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Summary Stats --}}
                    <div class="row text-center mt-4">
                        <div class="col-4">
                            <div class="bg-light rounded p-3">
                                <span class="small text-muted d-block">Variantes</span>
                                <strong class="h4" id="revStatVariants">0</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-3">
                                <span class="small text-muted d-block">Materiales</span>
                                <strong class="h4" id="revStatMaterials">0</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-3">
                                <span class="small text-muted d-block">Total Puntadas</span>
                                <strong class="h4" id="revTotalStitches">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Action Bar --}}
            <div class="alert alert-info mt-4 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Revise la información antes de confirmar.</strong> Una vez creado, podrá editar el producto desde el listado.
                    </div>
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

            /* Bootstrap 4 gap utility polyfill */
            .gap-1 {
                gap: 0.25rem;
            }

            .gap-2 {
                gap: 0.5rem;
            }

            .gap-3 {
                gap: 1rem;
            }

            /* INLINE SCOPE BUTTONS (NEW - for BOM form) */
            .scope-inline-btn {
                transition: all 0.2s ease;
            }

            .scope-inline-btn.active {
                background: var(--accent-ent) !important;
                border-color: var(--accent-ent) !important;
                color: #fff !important;
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

            /* POSITION OPTIONS (Design Modal) */
            .position-option {
                cursor: pointer;
                transition: all 0.2s ease;
                color: #6c757d;
            }

            .position-option:hover {
                border-color: var(--accent-ent) !important;
                color: var(--accent-ent);
            }

            .position-option.active {
                border-color: #17a2b8 !important;
                background: rgba(23, 162, 184, 0.1);
                color: #17a2b8;
            }

            .position-option.active i {
                color: #17a2b8;
            }

            /* Design Scope Buttons */
            .design-scope-btn.active {
                background: #17a2b8 !important;
                border-color: #17a2b8 !important;
                color: #fff !important;
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

            /* Select2 Customization for internal Clear Button */
            /* Select2 Customization for internal Clear Button */
            .select2-selection--multiple {
                padding-right: 30px !important;
            }

            .select2-selection--single .select2-selection__rendered {
                padding-right: 45px !important;
                /* increased to fit X + arrow */
            }

            /* PREMIUM INTERNAL CLEAR BUTTON */
            .clear-btn-internal {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                z-index: 50;
                cursor: pointer;
                color: #bdc3c7;
                /* Subtle default */
                font-size: 0.95rem;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* For Single Selects with Arrow: Move X left */
            .clear-btn-single-offset {
                right: 30px !important;
            }

            .clear-btn-internal:hover {
                transform: translateY(-50%) scale(1.15);
                color: #c0392b;
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
                cursor: not-allowed !important;
                pointer-events: none !important;
            }

            .opacity-50 {
                opacity: 0.5 !important;
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
            /* Choice (Chip) Style */
            .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background-color: var(--primary-ent, #2c3e50) !important;
                color: white !important;
                border: 1px solid #1a252f !important;
                border-radius: 4px !important;
                /* Standard Rouded Rect */
                padding: 2px 8px !important;
                font-size: 0.9rem !important;
                margin: 4px !important;
                display: inline-flex !important;
                align-items: center !important;
                flex-direction: row-reverse !important;
                /* Text Left, X Right */
                height: 28px !important;
                line-height: 1.5 !important;
            }

            /* Ensure inner text respects truncation */
            .select2-container--default .select2-selection--multiple .select2-selection__choice>span {
                margin-right: 8px !important;
                /* Space between text and X */
            }

            /* Clean Remove Button (X) */
            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                color: rgba(255, 255, 255, 0.7) !important;
                font-weight: bold !important;
                font-size: 14px !important;
                border: none !important;
                background: transparent !important;
                /* Spacing from text */
                margin-right: 0 !important;
                margin-left: 8px !important;
                padding: 0 !important;
                cursor: pointer !important;
                transition: color 0.2s;
            }

            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
                color: white !important;
                background: transparent !important;
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



            /* Custom Dropzone Styles */
            .custom-dropzone {
                border: 2px dashed #cbd5e0;
                border-radius: 8px;
                background-color: #f8f9fa;
                transition: all 0.2s ease;
                height: 100% !important;
                /* Forces fill */
                min-height: 250px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                cursor: pointer;
                /* Clickable area */
            }

            .custom-dropzone:hover,
            .custom-dropzone.dragover {
                border-color: #3490dc;
                background-color: #ebf8ff;
            }

            /* Prevent button clicks from triggering dropzone click */
            .custom-dropzone button {
                z-index: 10;
            }

            #dropzonePreview {
                width: 100%;
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
                    labor_cost: 0, // NEW
                    total_cost: 0,
                    margin: 35,
                    price: 0
                }
            };

            // Flag for async validation bypass
            let bypassBomValidation = false;

            // --- SKU GENERATION ---
            window.generateSKU = function() {
                const name = $('#inpName').val().trim();
                const categoryText = $('#inpCategory option:selected').text().trim();

                if (!name) {
                    $('#inpSku').val('');
                    return;
                }

                // Generate from first 3 letters of each word (max 3 words)
                const words = name.split(/\s+/).slice(0, 3);
                const namePart = words.map(w => w.substring(0, 3).toUpperCase()).join('-');

                // Add category abbreviation
                const catPart = categoryText.substring(0, 3).toUpperCase();

                // Add sequence number (could be from server in production)
                const seq = String(Math.floor(Math.random() * 999) + 1).padStart(3, '0');

                const sku = `${namePart}-${catPart}-${seq}`;
                $('#inpSku').val(sku);
            };

            window.toggleSkuEdit = function() {
                const input = $('#inpSku');
                const isReadonly = input.prop('readonly');

                if (isReadonly) {
                    input.prop('readonly', false).removeClass('bg-light').focus();
                } else {
                    input.prop('readonly', true).addClass('bg-light');
                }
            };

            // --- IMAGE DROPZONE LOGIC ---
            window.previewImage = function(input) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];

                    // Validation
                    if (!file.type.match('image.*')) {
                        $('#imageErrorMsg').text('Solo se permiten imágenes (JPG, PNG, WEBP)').removeClass('d-none');
                        input.value = ''; // Reset
                        return;
                    }

                    $('#imageErrorMsg').addClass('d-none');

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imgPreview').attr('src', e.target.result);
                        $('#dropzonePlaceholder').addClass('d-none');
                        $('#dropzonePreview').removeClass('d-none').addClass(
                            'd-flex align-items-center justify-content-center');
                        // Stop Dropzone click bubbling when preview is active (optional, but good for UX)
                        $('#productImageDropzone').css('cursor', 'default');
                    }
                    reader.readAsDataURL(file);
                }
            };

            window.removeImage = function(e) {
                e.preventDefault();
                e.stopPropagation(); // Crucial: Stop click from bubbling to the dropzone container

                $('#inpImage').val(''); // Clear input
                $('#imgPreview').attr('src', '');
                $('#dropzonePreview').addClass('d-none').removeClass('d-flex align-items-center justify-content-center');
                $('#dropzonePlaceholder').removeClass('d-none');
                $('#productImageDropzone').css('cursor', 'pointer');
            };

            // Drag & Drop Handlers (Attached via JS delegate to handle template re-renders if any)
            $(document).on('dragover', '#productImageDropzone', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $(document).on('dragleave', '#productImageDropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $(document).on('drop', '#productImageDropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    // Manually assign to input (works in modern browsers)
                    $('#inpImage')[0].files = files;
                    // Trigger preview
                    previewImage($('#inpImage')[0]);
                }
            });

            // Trigger input file on dropzone click (unless it's the remove button)
            $(document).on('click', '#productImageDropzone', function(e) {
                // If already has preview, don't trigger (unless they want to replace? User said X to remove. Let's allow replace if they click outside text?)
                // If preview is hidden (placeholder visible), trigger click.
                if ($('#dropzonePlaceholder').is(':visible')) {
                    $('#inpImage').click();
                }
            });

            // Prevent recursive loop if input is clicked inside dropzone
            $(document).on('click', '#inpImage', function(e) {
                e.stopPropagation();
            });

            // Generate SKU when category changes
            $(document).on('change', '#inpCategory', function() {
                generateSKU();
            });

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
                        // FIX: Smart rounding (remove decimals if .00)
                        const stockVal = parseFloat(opt.data('stock'));
                        const unit = opt.data('unit') || 'unid';

                        // Format: 1,000.5 or 1,000 (no unnecessary zeros)
                        const formattedStock = new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 4
                        }).format(stockVal);

                        $('#matStock').text(`${formattedStock} ${unit}`);
                        $('#matCost').text('$' + parseFloat(opt.data('cost')).toFixed(2));
                        // Update unit label
                        $('#bomUnit').text(unit);
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

            // --- INLINE SCOPE SELECTION (NEW - Replaces Modal for BOM) ---
            window.setInlineScope = function(scope) {
                $('.scope-inline-btn').removeClass('active btn-primary').addClass('btn-outline-secondary');
                $(`.scope-inline-btn[data-scope="${scope}"]`).removeClass('btn-outline-secondary').addClass(
                    'active btn-primary');
                $('#inlineScopeValue').val(scope);

                if (scope === 'specific') {
                    $('#inlineVariantsContainer').removeClass('d-none');
                    // Populate variants selector
                    const vSel = $('#inlineTargetVariants');
                    vSel.empty();
                    State.variants.forEach(v => {
                        vSel.append(`<option value="${v.temp_id}">${v.sku} (${v.size}/${v.color})</option>`);
                    });
                    // Initialize Select2 if not already
                    if (!vSel.hasClass('select2-hidden-accessible')) {
                        vSel.select2({
                            width: '100%',
                            placeholder: 'Seleccione variantes...'
                        });
                    }
                } else {
                    $('#inlineVariantsContainer').addClass('d-none');
                }
            };

            // Cost Preview for BOM
            window.updateBomCostPreview = function() {
                const opt = $('#bomMaterialSelector option:selected');
                const qty = parseFloat($('#bomQty').val()) || 0;
                const cost = parseFloat(opt.data('cost')) || 0;

                if (qty > 0 && cost > 0) {
                    const total = qty * cost;
                    $('#bomCostPreviewValue').text(`$${total.toFixed(2)}`);
                    $('#bomCostPreview').removeClass('d-none');
                } else {
                    $('#bomCostPreview').addClass('d-none');
                }
            };

            // Direct Add Material (No Modal)
            window.addMaterialDirect = function() {
                let matId = $('#bomMaterialSelector').val();
                if (Array.isArray(matId)) matId = matId[0];
                const qty = parseFloat($('#bomQty').val());
                const scope = $('#inlineScopeValue').val();

                if (!matId || !qty) {
                    Swal.fire('Error', 'Complete los datos del material', 'warning');
                    return;
                }

                if (!scope) {
                    Swal.fire('Error', 'Seleccione un alcance (Global o Específico)', 'warning');
                    return;
                }

                let targets = [];
                if (scope === 'specific') {
                    targets = $('#inlineTargetVariants').val() || [];
                    if (targets.length === 0) {
                        Swal.fire('Error', 'Seleccione al menos una variante destino', 'warning');
                        return;
                    }
                    targets.sort();
                }

                const opt = $('#bomMaterialSelector option:selected');
                const material = {
                    material_id: matId,
                    name: opt.data('name'),
                    cost: parseFloat(opt.data('cost')),
                    unit: opt.data('unit'),
                    qty: qty,
                    is_primary: $('#materialIsPrimary').is(':checked')
                };

                // Check for existing entry
                const existingIndex = State.bom.findIndex(item => {
                    if (item.material_id != material.material_id) return false;
                    if (item.scope !== scope) return false;
                    const itemTargets = (item.targets || []).sort().join(',');
                    const newTargets = targets.join(',');
                    return itemTargets === newTargets;
                });

                if (existingIndex > -1) {
                    // Accumulate
                    const existing = State.bom[existingIndex];
                    const newQty = (parseFloat(existing.qty) * 100 + parseFloat(material.qty) * 100) / 100;
                    existing.qty = newQty;
                    existing.calculated_total = existing.cost * existing.qty;
                    Swal.fire({
                        icon: 'info',
                        title: 'Material Actualizado',
                        text: `Nuevo total: ${existing.qty} ${existing.unit}`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Add new
                    State.bom.push({
                        ...material,
                        scope,
                        targets,
                        calculated_total: material.cost * material.qty
                    });
                }

                renderBOM();
                recalcFinance();

                // Reset form
                $('#bomQty').val('');
                $('#bomMaterialSelector').val(null).trigger('change');
                $('#bomCostPreview').addClass('d-none');
                setInlineScope('global'); // Reset to global
                $('#inlineTargetVariants').val(null).trigger('change');
                $('#materialIsPrimary').prop('checked', false);
            };

            // --- DESIGN SEARCH (NEW) ---
            $(document).on('input', '#searchDesign', function() {
                const query = $(this).val().toLowerCase().trim();
                $('#designGrid .col-md-3').each(function() {
                    const designName = $(this).find('h6').text().toLowerCase();
                    if (designName.includes(query) || query === '') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

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
                    allowClear: false // Disabled to avoid double X with custom button
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

                // Visual update based on direction
                const currentStepItem = $(`.stepper-item[data-step="${State.step}"]`);
                if (direction === 1) {
                    // Forward: Mark current as completed
                    currentStepItem.removeClass('active').addClass('completed');
                } else {
                    // Backward: Reset current to default (not completed, not active)
                    currentStepItem.removeClass('active completed');
                }

                State.step = nextStep;

                $(`#step${State.step}-content`).removeClass('d-none');
                const newStepItem = $(`.stepper-item[data-step="${State.step}"]`);
                newStepItem.addClass('active');

                // When going back, the step we're returning to should NOT be green
                if (direction === -1) {
                    newStepItem.removeClass('completed');
                }

                // Logic Hooks
                if (State.step === 2) updateVariantCountBadge();
                if (State.step === 4) syncDesignCardsWithState(); // FIX-3: Re-apply selected state
                if (State.step === 5) renderExtrasTable();
                if (State.step === 6) renderReview();

                updateButtons();
                window.scrollTo(0, 0);

                // Force AdminLTE layout recalculation
                setTimeout(() => {
                    $(window).trigger('resize');
                    if ($.fn.Layout) $('body').Layout('fix');
                }, 300);
            };

            // FIX-3: Sync design card visual state with State.designs
            function syncDesignCardsWithState() {
                // Get unique design IDs that are configured
                const configuredDesignIds = [...new Set(State.designs.map(d => d.id))];

                // Remove all selected classes first
                $('#designGrid .design-card').removeClass('selected');

                // Re-apply selected class to configured designs
                configuredDesignIds.forEach(id => {
                    const card = $(`.design-card[data-design-id="${id}"]`);
                    if (card.length) {
                        card.addClass('selected');
                        // Also update the count if we have the visual function
                        if (typeof updateDesignCardVisual === 'function') {
                            updateDesignCardVisual(id);
                        }
                    }
                });
            }

            // Update variant count badge when returning to Step 2
            function updateVariantCountBadge() {
                $('#variantCountBadge').text(State.variants.length);
                if (State.variants.length > 0) {
                    $('#noVariantsRow').hide();
                }
            }

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
                        category_id: $('#inpCategory').val(),
                        category: $('#inpCategory option:selected').text(),
                        desc: $('#inpDesc').val()
                    };
                }
                if (step === 2) {
                    if (State.variants.length === 0) {
                        // FIX: Allow single variant autofill if empty
                        const baseSku = $('#inpSku').val();
                        if (baseSku) {
                            State.variants.push({
                                temp_id: 'default',
                                sku: baseSku,
                                size: 'Unitalla',
                                color: 'N/A',
                                size_id: null,
                                color_id: null
                            });
                            updateVariantCountBadge();
                            return true;
                        }
                    }
                }
                if (step === 3) {
                    // FIX: Blocking Warning for empty BOM (Async handling)
                    if (State.bom.length === 0 && !bypassBomValidation) {
                        Swal.fire({
                            title: '¿Continuar sin Materiales?',
                            text: "El costo de materiales será $0.00. Use esto solo para servicios o productos intangibles.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, continuar',
                            cancelButtonText: 'Revisar',
                            reverseButtons: true, // Forces "Revisar" (Cancel) to Left, "Sí" (Confirm) to Right
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                bypassBomValidation = true;
                                navigate(1); // Retry navigation
                                // Reset flag after small delay or not needed if we change step
                                setTimeout(() => {
                                    bypassBomValidation = false;
                                }, 500);
                            }
                        });

                        // BLOCK navigation immediately so the stepper doesn't update to "Completed"
                        return false;
                    }
                }
                if (step === 4) {
                    const isNoDesign = $('#toggleNoDesign').is(':checked');
                    if (!isNoDesign && State.designs.length === 0) {
                        Swal.fire('Atención', 'Seleccione un diseño o active "Producto Liso"', 'warning');
                        return false;
                    }
                }
                return true;
            }

            // Feature: Toggle No Design Mode
            window.toggleNoDesignMode = function() {
                const isChecked = $('#toggleNoDesign').is(':checked');
                if (isChecked) {
                    $('#searchDesign').prop('disabled', true);
                    $('#designGrid').addClass('opacity-50 stop-propagation');
                    // Optional: Clear designs? No, maybe just ignore them or leave them as is.
                    // Better to visually disable.
                } else {
                    $('#searchDesign').prop('disabled', false);
                    $('#designGrid').removeClass('opacity-50 stop-propagation');
                }
            };

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

                // Hide placeholder row and update count badge
                $('#noVariantsRow').hide();
                $('#variantCountBadge').text(State.variants.length);

                // Clear selections after generating
                $('#selSizes').val(null).trigger('change');
                $('#selColors').val(null).trigger('change');
            };

            window.removeVariant = function(id, btn) {
                const variantToRemove = State.variants.find(v => v.temp_id === id);

                // Remove the variant
                State.variants = State.variants.filter(v => v.temp_id !== id);
                $(btn).closest('tr').remove();

                // CASCADE DELETE: Clean BOM items that targeted this variant
                State.bom = State.bom.filter(m => {
                    if (m.scope === 'global') return true;
                    if (!m.targets || m.targets.length === 0) return true;

                    // Remove this variant from targets
                    m.targets = m.targets.filter(tid => tid !== id);

                    // If no targets left, remove the material entirely
                    return m.targets.length > 0;
                });

                // CASCADE DELETE: Clean Designs that targeted this variant
                State.designs = State.designs.filter(d => {
                    if (d.scope === 'global') return true;
                    if (!d.targets || d.targets.length === 0) return true;

                    // Remove this variant from targets
                    d.targets = d.targets.filter(tid => tid !== id);

                    // If no targets left, remove the design entry
                    return d.targets.length > 0;
                });

                // Re-render BOM table and recalculate
                renderBOM();
                recalcFinance();

                // Notify user
                if (variantToRemove) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Variante Eliminada',
                        text: `${variantToRemove.size}/${variantToRemove.color} y sus asignaciones específicas fueron removidas.`,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            };

            // --- STEP 3: MATERIALS (AJAX RESTORED) ---
            // Variable temporal para el modal
            let tempMaterial = null;

            window.fetchMaterialVariants = function(familyId) {
                if (Array.isArray(familyId)) familyId = familyId[0];
                if (!familyId) {
                    $('#bomMaterialSelector').empty().prop('disabled', true);
                    return;
                }

                // URL original que usabas para traer conversiones/variantes
                const url = '{{ route('material-variants.conversiones', ['materialId' => ':id']) }}'.replace(':id',
                    familyId);

                $.get(url, function(data) {
                    const sel = $('#bomMaterialSelector');
                    sel.empty().prop('disabled', false); // Removed dummy option for Multiple Select2 compatibility

                    data.forEach(v => {
                        // Adaptar según la respuesta JSON real de tu controlador
                        // Asumiendo estructura: {id, text, cost_base, stock_real, symbol, family_name, variant_name, sku, stock_display}
                        sel.append(
                            `<option value="${v.id}"
                            data-cost="${v.cost_base}"
                            data-stock="${v.stock_real}"
                            data-unit="${v.symbol}"
                            data-name="${v.text}"
                            data-family="${v.family_name || ''}"
                            data-variant="${v.variant_name || ''}"
                            data-sku="${v.sku || ''}"
                            data-stock-text="${v.stock_display || ''}"
                            >${v.text}</option>`
                        );
                    });
                }).fail(function() {
                    Swal.fire('Error', 'No se pudieron cargar las variantes', 'error');
                });
            };

            window.prepareAddMaterial = function() {
                let matId = $('#bomMaterialSelector').val();
                if (Array.isArray(matId)) matId = matId[0];
                const qty = parseFloat($('#bomQty').val());

                if (!matId || !qty) {
                    Swal.fire('Error', 'Complete los datos del material', 'warning');
                    return;
                }

                const opt = $('#bomMaterialSelector option:selected');
                tempMaterial = {
                    material_id: matId,
                    name: opt.data('name'), // Fallback text
                    family_name: opt.data('family') || opt.data('name'), // Preferred
                    variant_name: opt.data('variant') || '',
                    sku: opt.data('sku') || '',
                    stock_text: opt.data('stock-text') || '',
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
                $('#bomMaterialSelector').val(null).trigger('change');
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
                    <div class="font-weight-bold text-dark" style="font-size: 1.1em;">${m.family_name || m.name}</div>
                    <div class="small text-muted">
                        ${m.variant_name ? `<span class="badge badge-light border text-dark">${m.variant_name}</span>` : ''}
                        <span class="ml-1">SKU: ${m.sku || '-'}</span>
                    </div>
                    ${m.is_primary ? '<span class="badge badge-warning text-white mt-1"><i class="fas fa-star text-white"></i> Principal</span>' : ''}
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

            // --- STEP 4: DESIGNS (UPDATED with Modal Config) ---
            let tempDesign = null; // Temporary storage for design being configured

            window.toggleDesign = function(el, id, name, stitches, imageUrl) {
                const card = $(el);

                // ALWAYS open config modal - whether already selected or not
                // User removes positions from INSIDE the modal using X buttons
                tempDesign = {
                    id,
                    name,
                    stitches: stitches || 0,
                    imageUrl: imageUrl || '',
                    element: el
                };

                // Populate modal
                $('#designModalName').text(name);
                $('#designModalStitches').text(`${stitches.toLocaleString()} puntadas`);
                if (imageUrl) {
                    $('#designModalImage').attr('src', imageUrl).show();
                } else {
                    $('#designModalImage').hide();
                }

                // Show already configured positions for THIS design
                showConfiguredPositions(id);

                // Reset modal state
                $('#designPositionSelect').val(null).trigger('change');
                selectDesignScope('global');
                $('#justAddedFeedback').addClass('d-none');

                // Initialize Select2 for Position (with search)
                if (!$('#designPositionSelect').hasClass('select2-hidden-accessible')) {
                    $('#designPositionSelect').select2({
                        dropdownParent: $('#designConfigModal'),
                        width: '100%',
                        placeholder: 'Buscar posición...',
                        allowClear: true
                    });
                }

                // Populate variants selector
                const vSel = $('#designTargetVariants');
                vSel.empty();
                State.variants.forEach(v => {
                    vSel.append(`<option value="${v.temp_id}">${v.sku} (${v.size}/${v.color})</option>`);
                });
                if (!vSel.hasClass('select2-hidden-accessible')) {
                    vSel.select2({
                        dropdownParent: $('#designConfigModal'),
                        width: '100%',
                        placeholder: 'Seleccione variantes...'
                    });
                }

                $('#designConfigModal').modal('show');
            };

            // Show already configured positions for this design WITH VARIANT TRACEABILITY + DELETE
            function showConfiguredPositions(designId) {
                const configured = State.designs.filter(d => d.id === designId);
                const container = $('#designConfiguredPositions');
                const list = $('#configuredPositionsList');

                if (configured.length > 0) {
                    list.empty();
                    configured.forEach((d, idx) => {
                        // Find the actual index in State.designs
                        const stateIndex = State.designs.findIndex(sd =>
                            sd.id === d.id &&
                            sd.position_id === d.position_id &&
                            sd.scope === d.scope
                        );

                        let variantInfo = '';
                        if (d.scope === 'specific' && d.targets && d.targets.length > 0) {
                            const variantNames = d.targets.map(tid => {
                                const v = State.variants.find(va => va.temp_id === tid);
                                return v ? `${v.size}/${v.color}` : '';
                            }).filter(Boolean).join(', ');
                            variantInfo = `<small class="d-block text-dark">→ ${variantNames}</small>`;
                        } else {
                            variantInfo = `<small class="d-block text-muted">→ Todas las variantes</small>`;
                        }

                        list.append(`
                        <div class="bg-light border rounded p-2 mb-1 mr-1 position-relative" style="padding-right: 30px!important;">
                            <span class="badge badge-success">${d.position_name}</span>
                            ${variantInfo}
                            <button type="button" class="btn btn-sm btn-link text-danger position-absolute" 
                                    style="top: 0; right: 0; padding: 2px 6px;"
                                    onclick="removeDesignPosition(${stateIndex})" title="Eliminar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `);
                    });
                    container.removeClass('d-none');
                } else {
                    container.addClass('d-none');
                }

                updateDesignsAddedCount();
                updateDesignCardVisual(designId);
            }

            // Remove a specific design position
            window.removeDesignPosition = function(stateIndex) {
                if (stateIndex >= 0 && stateIndex < State.designs.length) {
                    const removed = State.designs.splice(stateIndex, 1)[0];

                    // Re-show the updated list
                    if (tempDesign) {
                        showConfiguredPositions(tempDesign.id);
                    }

                    recalcFinance();

                    // Show feedback
                    if (removed) {
                        $('#justAddedText').html(`<span class="text-danger">Eliminado: ${removed.position_name}</span>`);
                        $('#justAddedFeedback').removeClass('d-none alert-success').addClass('alert-warning');
                    }
                }
            };

            // Update visual state of design card based on whether it has configs
            function updateDesignCardVisual(designId) {
                const configs = State.designs.filter(d => d.id === designId);
                const count = configs.length;
                const card = $(`.design-card[data-design-id="${designId}"]`);

                if (count > 0) {
                    card.addClass('selected');

                    // Check if badge exists, if not create it
                    let badge = card.find('.position-count-badge');
                    if (badge.length === 0) {
                        card.append(
                            '<div class="position-count-badge badge badge-primary shadow-sm" style="position: absolute; top: 10px; right: 10px; z-index: 10; font-size: 0.9rem;"></div>'
                        );
                        badge = card.find('.position-count-badge');
                    }
                    badge.html(`<i class="fas fa-layer-group mr-1"></i>${count}`).show();

                } else {
                    card.removeClass('selected');
                    card.find('.position-count-badge').hide();
                }
            }

            // Update count of designs added (for current design)
            function updateDesignsAddedCount() {
                if (tempDesign) {
                    const count = State.designs.filter(d => d.id === tempDesign.id).length;
                    $('#designsAddedCount').text(count);
                } else {
                    $('#designsAddedCount').text(State.designs.length);
                }
            }

            window.selectDesignScope = function(scope) {
                $('.design-scope-btn').removeClass('active btn-info').addClass('btn-outline-secondary');
                $(`.design-scope-btn[data-scope="${scope}"]`).removeClass('btn-outline-secondary').addClass(
                    'active btn-info');
                $('#designScopeValue').val(scope);

                if (scope === 'specific') {
                    $('#designVariantsContainer').removeClass('d-none');
                } else {
                    $('#designVariantsContainer').addClass('d-none');
                }
            };

            window.confirmAddDesign = function() {
                if (!tempDesign) return;

                const positionId = $('#designPositionSelect').val();
                const positionName = $('#designPositionSelect option:selected').text();
                const positionSlug = $('#designPositionSelect option:selected').data('slug');
                const scope = $('#designScopeValue').val();

                if (!positionId) {
                    Swal.fire('Error', 'Seleccione una posición para el bordado', 'warning');
                    return;
                }

                let targets = [];
                if (scope === 'specific') {
                    targets = $('#designTargetVariants').val() || [];
                    if (targets.length === 0) {
                        Swal.fire('Error', 'Seleccione al menos una variante', 'warning');
                        return;
                    }
                    targets.sort();
                }

                // Check for duplicate (same design + position + scope + targets)
                const existingIndex = State.designs.findIndex(d => {
                    if (d.id !== tempDesign.id) return false;
                    if (d.position_id != positionId) return false;
                    if (d.scope !== scope) return false;
                    const dTargets = (d.targets || []).sort().join(',');
                    const newTargets = targets.join(',');
                    return dTargets === newTargets;
                });

                if (existingIndex > -1) {
                    Swal.fire('Atención', 'Este diseño ya está configurado con la misma posición y alcance', 'info');
                    return;
                }

                // Add design to state (SEPARATE RECORD per position)
                State.designs.push({
                    id: tempDesign.id,
                    name: tempDesign.name,
                    stitches: tempDesign.stitches,
                    position_id: positionId,
                    position_name: positionName,
                    position_slug: positionSlug,
                    scope,
                    targets
                });

                // Mark card as selected
                $(tempDesign.element).addClass('selected');

                // Reset position select for another addition
                $('#designPositionSelect').val(null).trigger('change');
                recalcFinance();

                // Show inline feedback
                $('#justAddedText').text(`Agregado en: ${positionName.trim()}`);
                $('#justAddedFeedback').removeClass('d-none');

                // Update the configured positions list
                showConfiguredPositions(tempDesign.id);
            };

            // Render designs summary (for visibility)
            function renderDesignsSummary() {
                updateDesignsAddedCount();
            }

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

                // Obtener costo de mano de obra (NEW)
                const laborInput = parseFloat($('#finLaborInput').val()) || 0;
                f.labor_cost = laborInput;

                // Calcular costo total
                f.total_cost = f.material_cost + f.embroidery_cost + f.extras_cost + f.labor_cost;

                // Renderizar lista de materiales
                const matList = $('#finMaterialsList');
                if (State.bom.length > 0) {
                    let html = '';
                    State.bom.forEach(m => {
                        let scopeLabel = '';
                        if (m.scope === 'specific' && m.targets) {
                            const vNames = m.targets.map(tid => {
                                const v = State.variants.find(va => va.temp_id === tid);
                                return v ? `${v.size}/${v.color}` : '';
                            }).filter(Boolean).join(', ');
                            scopeLabel =
                                `<br><small class="text-info" style="font-size:0.75rem"><i class="fas fa-layer-group"></i> Aplica en: <strong>${vNames}</strong></small>`;
                        } else {
                            scopeLabel =
                                `<br><small class="text-secondary" style="font-size:0.75rem"><i class="fas fa-globe"></i> Global</small>`;
                        }

                        html += `<div class="d-flex justify-content-between py-2 border-bottom align-items-center">
                        <div style="line-height:1.2">
                            <span class="font-weight-bold" style="font-size:0.9rem">${m.family_name || m.name}</span>
                            <br>
                            <small class="text-muted" style="font-size: 0.8rem">${m.variant_name || ''}</small>
                            <br>
                            <small class="text-dark"><i class="fas fa-ruler-combined"></i> <strong>${m.qty} ${m.unit}</strong></small>
                            ${scopeLabel}
                        </div>
                        <span class="font-weight-bold text-dark">$${m.calculated_total.toFixed(2)}</span>
                    </div>`;
                    });
                    matList.html(html);
                } else {
                    matList.html('<span class="text-muted">Sin materiales...</span>');
                }

                // Actualizar UI
                $('#finMatCost').text(`$${f.material_cost.toFixed(2)}`);
                $('#finEmbCost').text(`$${f.embroidery_cost.toFixed(2)}`);
                $('#finLaborCostDisplay').text(`$${f.labor_cost.toFixed(2)}`); // Display Labor
                $('#finTotalStitches').text(totalStitches.toLocaleString());
                $('#finExtrasTotal').text(`$${f.extras_cost.toFixed(2)}`);
                $('#finTotalCost').text(`$${f.total_cost.toFixed(2)}`);
                $('#headerCost').text(`$${f.total_cost.toFixed(2)}`);

                // Calcular precio sugerido con margen
                const margin = parseFloat($('#finMargin').val()) || 0;
                f.margin = margin; // Store for review
                if (margin < 100) {
                    f.price = f.total_cost / (1 - (margin / 100));
                }
                $('#finSuggestedPrice').text(`$${f.price.toFixed(2)}`);
                $('#inpBasePrice').val(f.price.toFixed(2));
            };

            // --- STEP 6: REVIEW (PROFESSIONAL) ---
            window.renderReview = function() {
                const f = State.financials;

                // Product Identity
                $('#revProductName').text(State.definition.name || '-');
                $('#revProductSku').text(State.definition.sku || '-');
                $('#revProductCategory').text(State.definition.category || '-');

                // Variants
                $('#revVarCount').text(State.variants.length);
                if (State.variants.length > 0) {
                    let varHtml = '';
                    State.variants.forEach(v => {
                        varHtml += `<span class="badge badge-secondary mr-1 mb-1">${v.size} / ${v.color}</span>`;
                    });
                    $('#revVariants').html(varHtml);
                } else {
                    $('#revVariants').html('<span class="text-muted">Sin variantes</span>');
                }

                // BOM Materials
                $('#revBomCount').text(State.bom.length);
                if (State.bom.length > 0) {
                    let bomHtml = '';
                    State.bom.forEach(m => {
                        const scopeBadge = m.scope === 'specific' ?
                            `<span class="badge badge-warning">Específico</span>` :
                            `<span class="badge badge-light">Global</span>`;
                        bomHtml += `<div class="d-flex justify-content-between py-1 border-bottom">
                        <div>
                            <strong>${m.name}</strong>
                            <small class="text-muted d-block">${m.qty} ${m.unit} ${scopeBadge}</small>
                        </div>
                        <span class="font-weight-bold">$${m.calculated_total.toFixed(2)}</span>
                    </div>`;
                    });
                    $('#revBomList').html(bomHtml);
                } else {
                    $('#revBomList').html('<span class="text-muted">Sin materiales</span>');
                }

                // Financial
                $('#revMatCost').text(`$${f.material_cost.toFixed(2)}`);
                $('#revEmbCost').text(`$${f.embroidery_cost.toFixed(2)}`);
                $('#revLaborCost').text(`$${f.labor_cost.toFixed(2)}`); // NEW
                $('#revExtraCost').text(`$${f.extras_cost.toFixed(2)}`);
                $('#revTotalCost').text(`$${f.total_cost.toFixed(2)}`);
                $('#revMargin').text(`${f.margin}%`);
                $('#revPrice').text(`$${f.price.toFixed(2)}`);

                // Total Stitches
                const totalStitches = State.designs.reduce((sum, d) => sum + (d.stitches || 0), 0);
                $('#revTotalStitches').text(totalStitches.toLocaleString());

                // Summary Stats
                $('#revStatVariants').text(State.variants.length);
                $('#revStatMaterials').text(State.bom.length);

                // Designs WITH FULL VARIANT TRACEABILITY
                const isNoDesign = $('#toggleNoDesign').is(':checked');
                $('#revEmbCount').text(State.designs.length);

                if (isNoDesign) {
                    $('#revDesignsList').html(
                        '<div class="alert alert-secondary mb-0 py-2 small text-center"><i class="fas fa-ban mr-2"></i>Producto Liso / Sin Bordado</div>'
                    );
                } else if (State.designs.length > 0) {
                    let designsHtml = '';
                    State.designs.forEach(d => {
                        const posLabel = d.position_name ?
                            `<span class="badge badge-info">${d.position_name}</span>` : '';

                        // Full variant traceability
                        let variantInfo = '';
                        if (d.scope === 'specific' && d.targets && d.targets.length > 0) {
                            const variantNames = d.targets.map(tid => {
                                const v = State.variants.find(va => va.temp_id === tid);
                                return v ? `${v.size}/${v.color}` : '';
                            }).filter(Boolean).join(', ');
                            variantInfo =
                                `<div class="text-dark small mt-1"><i class="fas fa-arrow-right mr-1"></i>${variantNames}</div>`;
                        } else {
                            variantInfo =
                                `<div class="text-muted small mt-1"><i class="fas fa-globe mr-1"></i>Todas las variantes</div>`;
                        }

                        const stitchLabel = d.stitches ?
                            `<span class="badge badge-secondary">${d.stitches.toLocaleString()} pts</span>` : '';

                        designsHtml += `<div class="py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${d.name}</strong>
                                <div>${posLabel}</div>
                            </div>
                            ${stitchLabel}
                        </div>
                        ${variantInfo}
                    </div>`;
                    });
                    $('#revDesignsList').html(designsHtml);
                } else {
                    $('#revDesignsList').html(
                        '<span class="text-muted d-block text-center py-2">Sin diseños asignados</span>');
                }

                // BOM with variant traceability
                $('#revBomCount').text(State.bom.length);
                if (State.bom.length > 0) {
                    let bomHtml = '';
                    State.bom.forEach(m => {
                        let variantInfo = '';
                        if (m.scope === 'specific' && m.targets && m.targets.length > 0) {
                            const variantNames = m.targets.map(tid => {
                                const v = State.variants.find(va => va.temp_id === tid);
                                return v ? `${v.size}/${v.color}` : '';
                            }).filter(Boolean).join(', ');
                            variantInfo =
                                `<div class="text-dark small"><i class="fas fa-arrow-right mr-1"></i>${variantNames}</div>`;
                        } else {
                            variantInfo =
                                `<div class="text-muted small"><i class="fas fa-globe mr-1"></i>Global</div>`;
                        }

                        bomHtml += `<div class="py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-bold" style="font-size: 1rem;">${m.family_name || m.name}</div>
                                <div class="small text-muted">${m.variant_name ? 'Variante: ' + m.variant_name : ''} <span class="mx-1">|</span> SKU: ${m.sku || '-'}</div>
                            </div>
                            <span class="font-weight-bold text-dark" style="font-size: 1.1rem;">$${m.calculated_total.toFixed(2)}</span>
                        </div>
                        <div class="mt-1 d-flex justify-content-between align-items-center">
                             <div class="text-dark"><i class="fas fa-ruler-combined mr-1"></i><strong>${m.qty} ${m.unit}</strong></div>
                             ${variantInfo}
                        </div>
                    </div>`;
                    });
                    $('#revBomList').html(bomHtml);
                } else {
                    $('#revBomList').html('<span class="text-muted">Sin materiales</span>');
                }

                // Extras
                $('#revExtraCount').text(State.extras.length);
                if (State.extras.length > 0) {
                    let extrasHtml = '';
                    State.extras.forEach(e => {
                        extrasHtml += `<div class="d-flex justify-content-between py-1 border-bottom">
                        <span>${e.name}</span>
                        <span class="font-weight-bold">$${e.price.toFixed(2)}</span>
                    </div>`;
                    });
                    $('#revExtrasList').html(extrasHtml);
                } else {
                    $('#revExtrasList').html('<span class="text-muted">Sin extras</span>');
                }
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
