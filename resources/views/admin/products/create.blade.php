@extends('adminlte::page')

@section('title', isset($editMode) && $editMode ? 'Editar Producto - ' . $product->name : (isset($cloneMode) && $cloneMode ? 'Duplicar Producto - ' . $cloneProduct->name : 'Nuevo Producto - Enterprise Configurator'))

@section('plugins.Sweetalert2', true)
@section('plugins.Select2', true)

@section('content_header')
    <div class="module-header fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white"><i class="fas fa-cube mr-2"></i> Configurador de Productos</h1>
                @if(isset($cloneMode) && $cloneMode)
                    <p class="text-white-50 mb-0"><i class="fas fa-copy mr-1"></i> Duplicando: {{ $cloneProduct->name }} - Modifique SKU y datos necesarios</p>
                @else
                    <p class="text-white-50 mb-0">Crea tu producto paso a paso: define, configura y publica</p>
                @endif
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
        @php
            $formAction = isset($editMode) && $editMode
                ? route('admin.products.update', $product->id)
                : route('admin.products.store');
        @endphp
        <form id="productForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($editMode) && $editMode)
                @method('PUT')
                <input type="hidden" name="product_id" value="{{ $product->id }}">
            @endif

            <div class="stepper-nav-container">
                <button type="button" class="stepper-arrow stepper-arrow-prev" id="btnPrev" disabled
                    onclick="navigate(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="stepper-wrapper">
                    <div class="stepper-item active" data-step="1" onclick="navigateToStep(1)" style="cursor:pointer;">
                        <div class="step-counter">1</div>
                        <div class="step-name">Definición</div>
                    </div>
                    <div class="stepper-item" data-step="2" onclick="navigateToStep(2)" style="cursor:pointer;">
                        <div class="step-counter">2</div>
                        <div class="step-name">Presentaciones <span class="step-badge d-none" id="badgeStep2"></span></div>
                    </div>
                    <div class="stepper-item" data-step="3" onclick="navigateToStep(3)" style="cursor:pointer;">
                        <div class="step-counter">3</div>
                        <div class="step-name">Materiales <span class="step-badge d-none" id="badgeStep3"></span></div>
                    </div>
                    <div class="stepper-item" data-step="4" onclick="navigateToStep(4)" style="cursor:pointer;">
                        <div class="step-counter">4</div>
                        <div class="step-name">Bordado <span class="step-badge d-none" id="badgeStep4"></span></div>
                    </div>
                    <div class="stepper-item" data-step="5" onclick="navigateToStep(5)" style="cursor:pointer;">
                        <div class="step-counter">5</div>
                        <div class="step-name">Extras</div>
                    </div>
                    <div class="stepper-item" data-step="6" onclick="navigateToStep(6)" style="cursor:pointer;">
                        <div class="step-counter">6</div>
                        <div class="step-name">Costeo</div>
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

    {{-- MODALES ELIMINADOS - UX Simplificada --}}
    {{-- El sistema ahora usa secciones separadas para materiales comunes vs específicos --}}
    {{-- El diseño es read-only, no requiere modal de configuración --}}

    {{-- ================= TEMPLATES (DATA FROM DB) ================= --}}

    {{-- STEP 1: DEFINICIÓN --}}
    <script type="text/template" id="tpl_step1">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="step-title"><i class="fas fa-fingerprint text-primary"></i> Identidad del Producto</h5>
            <p class="step-description text-muted mb-4">
                <i class="fas fa-lightbulb text-warning mr-1"></i>
                Define la información básica que identifica tu producto. El <strong>nombre comercial</strong> es como tus clientes lo conocerán.
            </p>
            <div class="row">
                {{-- LEFT COLUMN: FORM FIELDS --}}
                <div class="col-md-8">
                {{-- ROW 1: NAME + SKU --}}
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre Comercial *</label>
                                <input type="text" class="form-control form-control-lg" name="name" id="inpName" placeholder="Ej: Guayabera Presidencial Lino" required oninput="generateSKU()" value="{{ old('name') }}">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold">SKU Base</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-uppercase bg-light" name="sku" id="inpSku" readonly value="{{ old('sku') }}">
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
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Categoría</label>
                                <select class="form-control" name="product_category_id" id="inpCategory">
                                    <option value="">-- Selecciona una categoría --</option>
                                    @foreach($categories ?? [] as $cat)
                                        <option value="{{ $cat->id }}" {{ old('product_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- HIDDEN STATUS: Always Active --}}
                        <input type="hidden" name="status" value="active">
                    </div>
                    {{-- ROW 3: DESCRIPTION --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Descripción</label>
                                <textarea class="form-control" name="description" id="inpDesc" rows="3" placeholder="Descripción detallada del producto...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: IMAGE DROPZONE --}}
                <div class="col-md-4 d-flex flex-column">
                    <label class="font-weight-bold">Imagen del Producto</label>
                    <div class="custom-dropzone text-center p-3 flex-grow-1" id="productImageDropzone">
                        <input type="file" name="primary_image" id="inpImage" accept="image/*" class="d-none" onchange="previewImage(this)">
                        
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


    {{-- STEP 2: PRESENTACIONES --}}
    <script type="text/template" id="tpl_step2">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="step-title"><i class="fas fa-th text-primary"></i> Presentaciones del Producto</h5>
            <p class="step-description text-muted mb-4">
                <i class="fas fa-lightbulb text-warning mr-1"></i>
                Las <strong>presentaciones</strong> son las combinaciones de talla y color que ofrecerás.
                Ejemplo: Si vendes en tallas S, M, L y colores Azul y Rojo, tendrás 6 presentaciones diferentes.
            </p>

            <div class="row">
                {{-- LEFT COLUMN: Selectors --}}
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white font-weight-bold">Configurar Presentaciones</div>
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
                                <i class="fas fa-bolt mr-2"></i>Generar Presentaciones
                            </button>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: Presentations Table --}}
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white align-items-center">
                            <span class="font-weight-bold" style="font-size: 1.1rem;">Presentaciones Generadas</span>
                            <span class="badge badge-primary ml-2 shadow-sm" id="variantCountBadge" style="font-size: 1.1rem; padding: 0.35em 0.6em;">0</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="variantsTableContainer" class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Presentación (Talla / Color)</th>
                                            <th>SKU Generado</th>
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

    {{-- STEP 3: MATERIALES DEL PRODUCTO (BOM) - REORGANIZADO UX --}}
    <script type="text/template" id="tpl_step3">
    {{-- BLOQUEO: Sin presentaciones --}}
    <div id="bomBlockedState" class="d-none">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-3">Define primero las presentaciones</h4>
                <p class="text-muted mb-4" style="font-size: 1.1rem;">
                    Para agregar materiales al producto, primero debes definir al menos una presentación (talla/color) en el paso anterior.
                </p>
                <button type="button" class="btn btn-primary btn-lg px-5" onclick="navigate(-1)">
                    <i class="fas fa-arrow-left mr-2"></i> Volver a Presentaciones
                </button>
            </div>
        </div>
    </div>

    {{-- CONTENIDO PRINCIPAL: Con presentaciones --}}
    <div id="bomActiveState">
        {{-- SECCIÓN A: MATERIALES COMUNES --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-boxes mr-2"></i> Materiales Comunes</h5>
                <span class="badge badge-light text-primary">Base</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle text-primary mr-1"></i>
                    Estos materiales se usan como base para todas las presentaciones.
                </p>

                <div class="row">
                    {{-- Formulario de materiales comunes --}}
                    <div class="col-md-5">
                        <div class="bg-light rounded p-3">
                            <div class="form-group mb-2">
                                <label>Familia de Material</label>
                                <div class="position-relative">
                                    <select class="form-control select2" id="bomFamilySelectorGlobal" data-placeholder="Seleccione familia...">
                                        <option value=""></option>
                                        @foreach($materials ?? [] as $m)
                                            <option value="{{ $m->id }}" data-unit="{{ $m->baseUnit->symbol ?? 'unid' }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label>Material / Variante</label>
                                <select class="form-control select2" id="bomMaterialSelectorGlobal" disabled data-placeholder="Seleccione material..."></select>
                                <div id="materialInfoGlobal" class="mt-2 small d-none p-2 bg-white rounded border">
                                    <span>Stock: <b id="matStockGlobal">-</b></span> |
                                    <span>Costo: <b id="matCostGlobal" class="text-success">$-</b></span>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label>Cantidad por unidad</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="bomQtyGlobal" placeholder="0.00" step="0.01" min="0.01">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="bomUnitGlobal">unid</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="materialIsPrimaryGlobal">
                                <label class="form-check-label small" for="materialIsPrimaryGlobal">Material principal</label>
                            </div>
                            <button type="button" class="btn btn-primary btn-block" onclick="addMaterialCommon()">
                                <i class="fas fa-plus-circle mr-1"></i> Agregar Material Común
                            </button>
                        </div>
                    </div>

                    {{-- Tabla de materiales comunes --}}
                    <div class="col-md-7">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" id="bomTableGlobal">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-center" style="width: 100px;">Cantidad</th>
                                        <th class="text-right" style="width: 90px;">Costo</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="bomTableBodyGlobal">
                                    <tr id="noMaterialsGlobalRow">
                                        <td colspan="4" class="text-center text-muted py-3">
                                            <i class="fas fa-inbox mr-1"></i> Sin materiales comunes
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="2">Total Materiales Comunes</td>
                                        <td class="text-right" id="bomTotalGlobal">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN B: MATERIALES POR PRESENTACIÓN --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-layer-group mr-2"></i> Ajustes por Presentación <small class="font-weight-normal">(opcional)</small></h5>
                <span class="badge badge-light text-info">Excepciones</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-lightbulb text-warning mr-1"></i>
                    Aquí puedes ajustar o agregar materiales solo para una presentación específica.<br>
                    <small>Si un material ya existe en comunes, este valor lo reemplaza solo para esta presentación.</small>
                </p>

                <div class="row">
                    {{-- Formulario de materiales específicos --}}
                    <div class="col-md-5">
                        <div class="bg-light rounded p-3">
                            {{-- Selector de presentación PRIMERO --}}
                            <div class="form-group mb-3">
                                <label class="text-info font-weight-bold">
                                    <i class="fas fa-tag mr-1"></i> Presentación destino
                                </label>
                                <select class="form-control select2" id="bomTargetVariant" data-placeholder="Seleccione presentación...">
                                    <option value=""></option>
                                </select>
                            </div>

                            <hr class="my-3">

                            <div class="form-group mb-2">
                                <label>Familia de Material</label>
                                <select class="form-control select2" id="bomFamilySelectorSpecific" data-placeholder="Seleccione familia...">
                                    <option value=""></option>
                                    @foreach($materials ?? [] as $m)
                                        <option value="{{ $m->id }}" data-unit="{{ $m->baseUnit->symbol ?? 'unid' }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label>Material / Variante</label>
                                <select class="form-control select2" id="bomMaterialSelectorSpecific" disabled data-placeholder="Seleccione material..."></select>
                                <div id="materialInfoSpecific" class="mt-2 small d-none p-2 bg-white rounded border">
                                    <span>Stock: <b id="matStockSpecific">-</b></span> |
                                    <span>Costo: <b id="matCostSpecific" class="text-success">$-</b></span>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label>Cantidad por unidad</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="bomQtySpecific" placeholder="0.00" step="0.01" min="0.01">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="bomUnitSpecific">unid</span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-info btn-block" onclick="addMaterialSpecific()">
                                <i class="fas fa-plus-circle mr-1"></i> Agregar a esta Presentación
                            </button>
                        </div>
                    </div>

                    {{-- Tabla de materiales específicos --}}
                    <div class="col-md-7">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" id="bomTableSpecific">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th>Material</th>
                                        <th>Presentación</th>
                                        <th class="text-center" style="width: 100px;">Cantidad</th>
                                        <th class="text-right" style="width: 90px;">Costo</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="bomTableBodySpecific">
                                    <tr id="noMaterialsSpecificRow">
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="fas fa-inbox mr-1"></i> Sin materiales específicos
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="3">Total Materiales Específicos</td>
                                        <td class="text-right" id="bomTotalSpecific">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RESUMEN TOTAL BOM --}}
        <div class="card shadow-sm border-0 mt-4 bg-dark text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-calculator mr-2"></i> Costo Total de Materiales</h5>
                        <small class="text-white-50">Suma de materiales comunes + específicos</small>
                    </div>
                    <div class="text-right">
                        <h3 class="mb-0 font-weight-bold" id="bomGrandTotal">$0.00</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 4: DISEÑO DE BORDADO - READ-ONLY --}}
    <script type="text/template" id="tpl_step4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-palette mr-2"></i> Diseños de Bordado</h5>
            <button type="button" class="btn btn-light btn-sm" onclick="openDesignModal()">
                <i class="fas fa-plus mr-1"></i> Agregar Diseño
            </button>
        </div>
        <div class="card-body">
            {{-- Encabezado informativo --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    Selecciona las producciones de bordado que se aplicarán a este producto.
                </p>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="toggleNoDesign" onchange="toggleProductoLiso()">
                    <label class="custom-control-label" for="toggleNoDesign">
                        <small class="text-muted">Producto liso (sin bordado)</small>
                    </label>
                </div>
            </div>

            {{-- Grid de diseños asignados --}}
            <div id="designCardsContainer">
                <div id="designCardsGrid" class="row">
                    {{-- Cards se renderizan dinámicamente --}}
                </div>
                <div id="noDesignsMessage" class="text-center py-5">
                    <i class="fas fa-vector-square text-muted mb-3" style="font-size: 4rem;"></i>
                    <h5 class="text-muted">Sin diseños asignados</h5>
                    <p class="text-muted mb-3">Haz clic en "Agregar Diseño" para seleccionar una producción de bordado.</p>
                    <button type="button" class="btn btn-outline-info" onclick="openDesignModal()">
                        <i class="fas fa-plus mr-1"></i> Agregar Diseño
                    </button>
                </div>
            </div>

            {{-- Resumen de puntadas totales --}}
            <div id="designSummary" class="d-none mt-4">
                <div class="bg-light rounded p-3">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-layer-group text-info mb-1" style="font-size: 1.2rem;"></i>
                            <div class="font-weight-bold" id="totalDesignsCount">0</div>
                            <small class="text-muted">Diseños</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-th text-success mb-1" style="font-size: 1.2rem;"></i>
                            <div class="font-weight-bold" id="totalStitchesCount">0</div>
                            <small class="text-muted">Puntadas Totales</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-dollar-sign text-primary mb-1" style="font-size: 1.2rem;"></i>
                            <div class="font-weight-bold" id="estimatedEmbCost">$0.00</div>
                            <small class="text-muted">Costo Estimado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- BACKUP: OLD STEP 4 - Uncomment to revert to original design-based view
            <script type="text/template" id="tpl_step4_OLD">
    <div class="card shadow-sm border-0">
        <div class="card-body">
                <h5 class="step-title"><i class="fas fa-vector-square text-primary"></i> Diseños de Bordado</h5>
                <p class="step-description text-muted mb-3">
                    <i class="fas fa-lightbulb text-warning mr-1"></i>
                    Selecciona los <strong>diseños de bordado</strong> que aplicarás a tu producto.
                    Haz clic en un diseño para configurar su posición (pecho, manga, espalda, etc.).
                </p>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="toggleNoDesign" onchange="toggleNoDesignMode()">
                        <label class="custom-control-label font-weight-bold" for="toggleNoDesign">
                            <i class="fas fa-tshirt mr-1"></i> Este producto es liso (sin bordado)
                        </label>
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
                                 onclick='toggleDesign(this, {{ $design->id }}, "{{ addslashes($design->name) }}", {{ $stitchesRaw }}, "{{ $image ? addslashes(asset("storage/" . $image)) : "" }}", "{{ $dimensions }}", {{ $colors }}, @json($design->variants))'
                                 data-design-id="{{ $design->id }}"
                                 data-app-type="{{ strtolower($appType) }}">
                                <div class="card-img-top design-thumb d-flex align-items-center justify-content-center bg-light" style="height: 140px;">
                                    @if($image)
                                        <img src="{{ asset('storage/' . $image) }}" style="max-height: 120px; max-width: 100%;">
                                    @elseif($design->variants->isNotEmpty() && $design->variants->first()->primaryImage)
                                        <img src="{{ asset('storage/' . $design->variants->first()->primaryImage->url) }}" style="max-height: 120px; max-width: 100%;">
                                    @else
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    @endif
                                </div>
                                <div class="card-body p-3 text-center">
                                    <h5 class="text-truncate mb-2 font-weight-bold" title="{{ $design->name }}" style="font-size: 1.25rem;">
                                        {{ $design->name }}
                                        @if($design->variants->isNotEmpty())
                                            <span class="badge badge-light text-muted ml-1" style="font-size: 0.7rem;">+{{ $design->variants->count() }} var</span>
                                        @endif
                                    </h5>
                                    <div class="mb-2 d-flex justify-content-center">
                                        <span class="badge badge-secondary" style="font-size: 0.95rem;">Lugar de aplicación: {{ ucfirst($appType) }}</span>
                                    </div>
                                    <div class="row text-center small">
                                        <div class="col-4">
                                            <i class="fas fa-ruler-combined text-primary d-block mb-1"></i>
                                            <strong class="d-block" style="font-size: 1rem;">{{ $dimensions }}</strong>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-palette text-info d-block mb-1"></i>
                                            <strong class="d-block" style="font-size: 1rem;">{{ $colors }} col</strong>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-th text-success d-block mb-1"></i>
                                            <strong class="d-block" style="font-size: 1rem;">{{ $stitches }}</strong>
                                        </div>
                                    </div>

                                    <div class="usage-status mt-3 badge badge-light border w-100 py-2 d-none text-left" style="font-size: 0.9rem;">
                                        <i class="fas fa-link mr-1 text-muted"></i> <span class="usage-text font-weight-bold text-dark"></span>
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
            --}}

    {{-- STEP 5: SERVICIOS EXTRAS (DOS COLUMNAS) --}}
    <script type="text/template" id="tpl_step5">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-4">
            <h5 class="step-title"><i class="fas fa-concierge-bell text-warning"></i> Servicios Adicionales</h5>
            <p class="step-description text-muted mb-0">
                <i class="fas fa-lightbulb text-warning mr-1"></i>
                Agrega servicios extras como <strong>armado</strong>, <strong>empaquetado especial</strong>, 
                <strong>etiquetado</strong>, etc. Estos costos se sumarán al precio final del producto.
            </p>
        </div>
    </div>
    <div class="row">
        {{-- LEFT COLUMN: Selector --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">
                    <i class="fas fa-plus-circle text-success mr-2"></i>Agregar Servicios
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-bold text-uppercase small">Seleccionar Servicio</label>
                        <select class="form-control select2" id="extrasSelector">
                            <option value="">Seleccionar servicio...</option>
                            @foreach($extras ?? [] as $extra)
                                <option value="{{ $extra->id }}" 
                                        data-price="{{ $extra->cost_addition }}" 
                                        data-name="{{ $extra->name }}"
                                        data-time="{{ $extra->minutes_addition ?? 0 }}">
                                    {{ $extra->name }} (+${{ number_format($extra->cost_addition, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-block" onclick="addExtra()">
                        <i class="fas fa-plus-circle mr-2"></i> Agregar Servicio
                    </button>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Table --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold section-title mb-0 d-flex align-items-center">
                        Lista de Servicios
                        <span class="badge badge-pill badge-secondary ml-2" style="font-size: 0.9rem;" id="extrasCountBadge">0</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Servicio</th>
                                    <th class="text-right">Costo</th>
                                    <th class="text-center">Tiempo</th>
                                    <th class="text-center" style="width:60px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="extrasTableBody">
                                <tr id="noExtrasRow">
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No hay servicios agregados
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td>TOTAL SERVICIOS</td>
                                    <td class="text-right" id="extrasTotalDisplay">$0.00</td>
                                    <td class="text-center" id="extrasTotalTime">0 min</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

    {{-- STEP 6: COSTEO Y CONFIRMACIÓN --}}
    <script type="text/template" id="tpl_step6">
    <div class="card shadow-sm border-0 review-premium-card">
        <div class="card-header review-premium-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calculator mr-2"></i>Costeo Final y Confirmación</span>
            <span class="review-ready-badge"><i class="fas fa-rocket mr-1"></i> Listo para Crear</span>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4" style="font-size: 0.9rem;">
                Revisa el desglose de costos y define tu margen de ganancia. 
                <strong>Una vez creado</strong>, podrás editar el producto desde el catálogo.
            </p>
            
            <div class="row">
                {{-- LEFT COLUMN: CALCULADORA COMPLETA (Classic Style) --}}
                <div class="col-lg-9 pr-lg-4">
                    <div class="card calc-premium-card shadow-sm h-100">
                        <div class="card-header calc-premium-header text-center">
                            <h5 class="mb-0"><i class="fas fa-receipt mr-2"></i> Desglose de Costos</h5>
                        </div>
                        <div class="card-body p-3">
                            
                            <div class="row">
                                {{-- LEFT COLUMN: Materiales --}}
                                <div class="col-md-6">
                                    {{-- DESGLOSE DE MATERIALES --}}
                                    <div class="calc-section h-100">
                                        <div class="calc-section-header">
                                            <span class="calc-section-title"><i class="fas fa-boxes mr-2"></i>Materiales (Receta)</span>
                                            <span class="calc-section-value text-primary" id="finMatCost">$0.00</span>
                                        </div>
                                        <div id="finMaterialsList" class="calc-scroll-list" style="max-height: 380px; overflow-y: auto;">
                                            <span class="text-muted small">Sin materiales...</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- RIGHT COLUMN: Bordados, Mano de Obra, Servicios --}}
                                <div class="col-md-6">
                                    {{-- BORDADOS --}}
                                    <div class="calc-section">
                                        <div class="calc-section-header">
                                            <span class="calc-section-title"><i class="fas fa-tshirt mr-2"></i>Bordados</span>
                                            <span class="calc-section-value text-primary" id="finEmbCost">$0.00</span>
                                        </div>
                                        {{-- Preview de diseños seleccionados --}}
                                        <div id="finDesignPreviews" class="d-flex flex-wrap gap-2 mb-2" style="gap: 8px;">
                                            <span class="text-muted small">Sin diseños seleccionados...</span>
                                        </div>
                                        <div class="calc-stitch-grid">
                                            <div class="calc-stitch-box">
                                                <div class="stitch-label">Total Puntadas</div>
                                                <div class="stitch-value" id="finTotalStitches">0</div>
                                            </div>
                                            <div class="calc-arrow"><i class="fas fa-arrow-right"></i></div>
                                            <div class="calc-stitch-box">
                                                <div class="stitch-label">Millares</div>
                                                <div class="stitch-value text-info" id="finMillares">0.000</div>
                                            </div>
                                            <div class="calc-arrow"><i class="fas fa-times"></i></div>
                                            <div class="calc-stitch-box calc-input-box">
                                                <div class="stitch-label">Precio / Millar</div>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                    <input type="number" class="form-control text-center font-weight-bold" id="finStitchRate" value="1.00" step="0.01" min="0" onchange="recalcFinance()" oninput="recalcFinance()">
                                                </div>
                                            </div>
                                        </div>
                                        {{-- TIEMPO DE BORDADO --}}
                                        <div class="row mt-3">
                                            <div class="col-12 mb-2">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="text-muted small mr-2 font-weight-bold" style="min-width: 80px; color: #1a252f;">Velocidad:</span>
                                                    <div class="input-group input-group-sm w-100">
                                                        <input type="number" class="form-control text-center font-weight-bold" id="finMachineSpeed" value="800" min="100" max="2000" step="50" onchange="recalcFinance()" oninput="recalcFinance()" style="font-size: 0.95rem;">
                                                        <div class="input-group-append"><span class="input-group-text small">p/min</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 text-right">
                                                <span class="text-muted small font-weight-bold" style="color: #1a252f;">Tiempo Estimado:</span>
                                                <span class="font-weight-bold text-success ml-2" id="finEmbroideryTime" style="font-size: 1.1rem;">0 min</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- MANO DE OBRA --}}
                                    <div class="calc-section">
                                        <div class="calc-section-header">
                                            <span class="calc-section-title"><i class="fas fa-hand-holding-usd mr-2"></i>Mano de Obra</span>
                                            <span class="calc-section-value text-primary" id="finLaborCostDisplay">$0.00</span>
                                        </div>
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                    <input type="number" class="form-control font-weight-bold" id="finLaborInput" value="" placeholder="0.00" step="0.01" min="0" onchange="recalcFinance()" oninput="recalcFinance()">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted">Corte, confección, etc.</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- SERVICIOS EXTRAS --}}
                                    <div class="calc-section">
                                        <div class="calc-section-header">
                                            <span class="calc-section-title"><i class="fas fa-concierge-bell mr-2"></i>Servicios Extras</span>
                                            <span class="calc-section-value text-primary" id="finExtrasTotal">$0.00</span>
                                        </div>
                                        <div id="finExtrasList" class="calc-scroll-list" style="max-height: 150px; overflow-y: auto;">
                                            <span class="text-muted small">Sin servicios agregados...</span>
                                        </div>
                                        <div class="d-flex justify-content-end mt-2 pt-2 border-top">
                                            <span class="text-muted mr-2">Tiempo Estimado:</span>
                                            <span class="font-weight-bold text-info" id="finExtrasTime">0 min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                    </div>
                </div>
                
                {{-- RIGHT COLUMN: Financial Summary + Editable Controls --}}
                <div class="col-lg-3">
                    <div class="review-finance-card">
                        <div class="review-finance-header">
                            <i class="fas fa-calculator mr-2"></i> Resumen Financiero
                        </div>
                        <div class="review-finance-body">
                            {{-- Cost Breakdown --}}
                            <div class="review-cost-row-lg">
                                <span>Materiales</span>
                                <span id="revMatCost">$0.00</span>
                            </div>
                            <div class="review-cost-row-lg">
                                <span>Bordados</span>
                                <span id="revEmbCost">$0.00</span>
                            </div>
                            <div class="review-cost-row-lg">
                                <span>Mano de Obra</span>
                                <span id="revLaborCost">$0.00</span>
                            </div>
                            <div class="review-cost-row-lg">
                                <span>Servicios Extras</span>
                                <span id="revExtraCost">$0.00</span>
                            </div>
                            
                            {{-- Total Cost Line --}}
                            <div class="review-total-row">
                                <span>COSTO TOTAL PRODUCCIÓN</span>
                                <span id="revTotalCost">$0.00</span>
                            </div>
                        </div>
                        
                        {{-- TIEMPOS DE PRODUCCIÓN --}}
                        <div class="review-finance-header mt-3 rounded-top">
                            <span class="mb-0 font-weight-bold"><i class="fas fa-clock mr-2"></i>Tiempos Estimados</span>
                        </div>
                        <div class="review-times-section px-3 py-3 border border-top-0 rounded-bottom mb-3" style="background: white;">

                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.95rem;">
                                <span>Bordado:</span>
                                <span class="font-weight-bold" id="revEmbTime">0 min</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.95rem;">
                                <span>Servicios Extras:</span>
                                <span class="font-weight-bold" id="revExtrasTime">0 min</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="font-size: 1rem;">
                                <span class="font-weight-bold">Total Proceso:</span>
                                <span class="font-weight-bold text-success" id="revTotalTime">0 min</span>
                            </div>
                            <div class="form-group mb-0 text-center">
                                <label class="small text-uppercase mb-1 font-weight-bold text-dark">Tiempo Producción (días)</label>
                                <input type="number" class="form-control form-control-sm text-center" id="revLeadTime" name="production_lead_time" value="1" min="1" max="365" placeholder="Días">
                            </div>
                        </div>
                        
                        {{-- MARGEN Y PRECIO --}}
                        <div class="review-finance-header rounded-top">
                            <span class="mb-0 font-weight-bold"><i class="fas fa-hand-holding-usd mr-2"></i>Rentabilidad y Precio</span>
                        </div>
                        <div class="review-pricing-controls px-3 py-3 border border-top-0 rounded-bottom" style="background: white;">
                             <div class="row mb-3">
                                <div class="col-12 mb-2">
                                    <label class="small text-uppercase font-weight-bold mb-1">Margen de Ganancia</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control font-weight-bold text-center" id="revMarginInput" value="35" min="0" max="90" step="1" onchange="recalcReviewPrice()" oninput="recalcReviewPrice()" style="height: 48px; font-size: 1.1rem;">
                                        <div class="input-group-append"><span class="input-group-text font-weight-bold">%</span></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-success text-white rounded p-1 d-flex align-items-center justify-content-between shadow-sm" style="height: 48px;">
                                        <span class="small font-weight-bold px-3 text-uppercase">Precio Sugerido:</span>
                                        <span class="h3 mb-0 font-weight-bold px-3" id="revSuggestedPrice">$0.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="small text-uppercase mb-1">Precio Final (Personalizado)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control" id="revFinalPrice" name="base_price" step="0.01" placeholder="Igual al sugerido" onchange="updateHeaderPrice()">
                                </div>
                            </div>
                        </div>
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

        /* ========================================= */
        /* DESIGN MODAL CARDS STYLES                */
        /* ========================================= */
        .design-modal-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
        }

        .design-modal-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }

        .design-modal-card.border-primary {
            border-color: #007bff !important;
        }

        .design-modal-card.border-warning {
            border-color: #ffc107 !important;
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        /* Scrollbar styling for design modal */
        #designModalGrid::-webkit-scrollbar {
            width: 8px;
        }

        #designModalGrid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #designModalGrid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        #designModalGrid::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* ========================================= */
        /* PREMIUM CALCULATOR STYLES                */
        /* ========================================= */

        .calc-premium-card {
            border: none !important;
            border-radius: 16px !important;
            overflow: hidden;
        }

        .calc-premium-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: #fff;
            padding: 14px 20px;
            border: none !important;
        }

        .calc-premium-header h5 {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Calculator Section Base */
        .calc-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .calc-section-compact {
            padding: 10px 16px;
        }

        .calc-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .calc-section-compact .calc-section-header {
            margin-bottom: 0;
        }

        .calc-section-title {
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a252f;
        }

        .calc-section-title i {
            color: inherit;
        }

        .calc-section-value {
            font-size: 1.15rem;
            font-weight: 700;
        }

        /* Materials scroll list - adequate space */
        .calc-scroll-list {
            max-height: 180px;
            overflow-y: auto;
            background: #fff;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .calc-scroll-list::-webkit-scrollbar {
            width: 6px;
        }

        .calc-scroll-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .calc-scroll-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .calc-scroll-list::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Material Item - Compact Layout */
        .calc-material-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .calc-material-item:last-child {
            border-bottom: none;
        }

        .calc-mat-name {
            font-weight: 700;
            font-size: 1rem;
            color: #1a252f;
            margin-bottom: 4px;
        }

        .calc-mat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calc-mat-qty {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .calc-mat-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a252f;
        }

        .calc-mat-scope {
            font-size: 0.85rem;
            color: #34495e;
            margin-top: 2px;
        }

        /* Stitch Grid - Millares Display */
        .calc-stitch-grid {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            /* Allow wrapping on small screens/long text */
            gap: 12px;
            /* Increased gap */
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .calc-stitch-box {
            flex: 1 1 120px;
            /* Base width */
            text-align: center;
            padding: 10px 8px;
            background: #f8f9fa;
            border-radius: 6px;
            min-width: 0;
        }

        .calc-input-box {
            background: #e3f2fd;
        }

        .stitch-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #1a252f;
            font-weight: 600;
            margin-bottom: 4px;
            white-space: nowrap;
        }

        .stitch-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a252f;
            line-height: 1.2;
        }

        .calc-arrow {
            color: #bdc3c7;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .calc-formula-hint {
            font-size: 0.9rem;
            color: #2c3e50;
            background: rgba(52, 152, 219, 0.12);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #3498db;
            font-weight: 500;
        }

        /* Total Box */
        .calc-total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 10px;
        }

        .calc-total-label {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calc-total-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Pricing Row */
        .calc-pricing-row {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }

        .calc-margin-box {
            flex: 0 0 120px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .calc-margin-label {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #1a252f;
            margin-bottom: 6px;
            display: block;
        }

        .calc-suggested-box {
            flex: 1;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: #fff;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calc-suggested-label {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calc-suggested-value {
            font-size: 1.4rem;
            font-weight: 700;
        }

        /* Final Price Box */
        .calc-final-box {
            background: #fff;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .calc-final-label {
            font-size: 1rem;
            font-weight: 700;
            color: #1a252f;
            margin-bottom: 8px;
            display: block;
            text-align: center;
        }

        .calc-final-box .input-group {
            max-width: 200px;
            margin: 0 auto;
        }

        /* Override form heights for calculator inputs */
        .calc-section input.form-control,
        .calc-margin-box input.form-control,
        .calc-final-box input.form-control {
            height: 36px !important;
            min-height: 36px !important;
            font-size: 0.95rem !important;
        }

        .calc-stitch-box .input-group-text {
            height: 32px !important;
            padding: 0 8px !important;
            font-size: 0.85rem !important;
        }

        .calc-stitch-box input.form-control {
            height: 32px !important;
            min-height: 32px !important;
            font-size: 0.9rem !important;
        }

        /* ========================================= */
        /* PREMIUM REVIEW STEP STYLES               */
        /* ========================================= */

        .review-premium-card {
            border: none !important;
            border-radius: 16px !important;
            overflow: hidden;
        }

        .review-premium-header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: #fff;
            padding: 14px 20px;
            border: none !important;
        }

        .review-ready-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Product Section */
        .review-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .review-section-header {
            margin-bottom: 10px;
        }

        .review-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        .review-product-card {
            background: #fff;
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .review-product-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .review-product-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .review-meta-item {
            font-size: 0.85rem;
        }

        .review-meta-label {
            color: #6c757d;
            margin-right: 4px;
        }

        .review-meta-item code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Compact 3-column sections */
        .review-compact-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .review-compact-header {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
        }

        .review-compact-header i {
            font-size: 0.9rem;
        }

        .review-count-badge {
            background: #2c3e50;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: auto;
        }

        .review-compact-list {
            max-height: 120px;
            overflow-y: auto;
            background: #fff;
            border-radius: 6px;
            padding: 8px;
            font-size: 0.8rem;
        }

        .review-compact-list::-webkit-scrollbar {
            width: 4px;
        }

        .review-compact-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }

        /* Extras section */
        .review-extras-section {
            padding: 10px 16px;
        }

        .review-extras-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Financial Card */
        .review-finance-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .review-finance-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: #fff;
            padding: 12px 16px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .review-finance-body {
            padding: 12px 16px;
        }

        .review-cost-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.95rem;
        }

        .review-cost-row span:first-child {
            color: #495057;
        }

        .review-cost-row span:last-child {
            font-weight: 600;
            color: #2c3e50;
        }

        .review-total-row {
            display: flex;
            justify-content: space-between;
            background: #2c3e50;
            color: #fff;
            margin: 12px -16px;
            padding: 12px 16px;
            font-weight: 600;
        }

        .review-total-row span:first-child {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .review-total-row span:last-child {
            font-size: 1.1rem;
        }

        .review-margin-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
            color: #495057;
        }

        .review-margin-row span:last-child {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Review Cost Row - Larger Text */
        .review-cost-row-lg {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
            font-size: 1rem;
        }

        .review-cost-row-lg span:first-child {
            color: #495057;
            font-weight: 500;
        }

        .review-cost-row-lg span:last-child {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.05rem;
        }

        /* Pricing Controls Section */
        .review-pricing-controls {
            padding: 16px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .pricing-row {
            margin-bottom: 16px;
        }

        .pricing-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
        }

        .pricing-input-group {
            display: flex;
            align-items: center;
        }

        .pricing-input {
            width: 80px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 8px 0 0 8px !important;
        }

        .pricing-suffix {
            background: #e9ecef;
            padding: 10px 14px;
            border: 1px solid #ced4da;
            border-left: none;
            border-radius: 0 8px 8px 0;
            font-weight: 600;
            color: #495057;
        }

        .pricing-prefix {
            background: #27ae60;
            color: #fff;
            padding: 10px 14px;
            border: 1px solid #27ae60;
            border-radius: 8px 0 0 8px;
            font-weight: 600;
        }

        .pricing-input-lg {
            flex: 1;
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            border-radius: 0 8px 8px 0 !important;
        }

        .pricing-suggested {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .pricing-suggested-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pricing-suggested-value {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .pricing-row-final {
            margin-bottom: 0;
        }

        .pricing-input-primary {
            border: 2px solid #27ae60;
            border-radius: 8px;
            overflow: hidden;
        }

        .review-price-row {
            display: flex;
            justify-content: space-between;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: #fff;
            margin: 12px -16px -12px -16px;
            padding: 14px 16px;
            font-weight: 700;
        }

        .review-price-row span:first-child {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .review-price-row span:last-child {
            font-size: 1.4rem;
        }

        /* Review Items - Compact */
        .review-item-pill {
            display: inline-block;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin: 2px;
            color: #495057;
        }

        .review-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .review-item-row:last-child {
            border-bottom: none;
        }

        .review-item-name {
            color: #2c3e50;
            font-weight: 500;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .review-item-qty {
            color: #6c757d;
            font-size: 0.75rem;
            margin-left: 8px;
            white-space: nowrap;
        }

        .review-item-badge {
            color: #6c757d;
            font-size: 0.7rem;
            white-space: nowrap;
        }

        /* Bordados - Design Items */
        .review-design-item {
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .review-design-item:last-child {
            border-bottom: none;
        }

        .review-design-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .review-design-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .review-design-info span:last-child {
            font-weight: 500;
            color: #27ae60;
        }

        /* Extras - Plain Vertical List */
        .review-extra-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.85rem;
        }

        .review-extra-item:last-child {
            border-bottom: none;
        }

        .review-extra-item span:first-child {
            color: #495057;
        }

        .review-extra-item span:last-child {
            font-weight: 600;
            color: #2c3e50;
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
            margin-right: 0 !important;
            font-size: 16px !important;
            line-height: 24px !important;
            color: #ccc !important;
        }

        /* --- STEP 6 UI POLISH OVERRIDES --- */

        /* Variants - Vertical List with Gray Outline */
        .review-variant-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
        }

        .review-variant-text {
            font-weight: 600;
            color: #343a40;
            font-size: 1rem;
        }

        .review-variant-sku {
            font-size: 0.85rem;
            color: #6c757d;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Material Items - Compact Row */
        .review-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .review-item-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }

        .review-item-qty {
            font-weight: 700;
            color: #343a40;
            font-size: 1rem;
            margin-left: 10px;
        }

        /* Design Items - Larger Text */
        .review-design-item {
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .review-design-name {
            font-weight: 700;
            font-size: 1.05rem;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        .review-design-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: #6c757d;
        }

        /* Extras - Vertical Column Name - Price */
        .review-extra-item {
            display: flex;
            flex-direction: column;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 6px;
        }

        .review-extra-item:last-child {
            border-bottom: none;
        }

        .review-extra-name {
            font-size: 1rem;
            color: #495057;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .review-extra-price {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            visibility: hidden !important;
            /* HIde the text 'x' */

            background-color: rgba(231, 76, 60, 0.1) !important;
            border-radius: 50% !important;
            z-index: 100 !important;
        }

        /* 3. Show the ICON using visibility: visible on pseudo-element */
        .select2-container--default .select2-selection--single .select2-selection__clear::after {
            content: "\f00d";

            /* Extras Grid Layout */
        }

        .review-extras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            width: 100%;
        }

        .review-extra-item {
            border-bottom: none !important;
            margin-bottom: 0 !important;
        }

        .review-extra-inline {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
            text-transform: uppercase;
        }

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
            flex-wrap !important;
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

        .clear-btn-internal {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            color: #bdc3c7;
            cursor: pointer;
            z-index: 10;
        }

        .clear-btn-internal:hover {
            color: #e74c3c;
        }

        /* --- RESPONSIVE STEPPER FOR MOBILE --- */
        @media (max-width: 768px) {
            .stepper-wrapper {
                padding: 15px 5px !important;
                border-radius: 12px !important;
            }

            #stepperContainer {
                display: flex !important;
                flex-wrap: nowrap !important;
                /* Fit everything on screen, no scroll needed if possible */
                overflow-x: hidden !important;
                justify-content: space-between !important;
                padding-bottom: 0px;
                width: 100% !important;
            }

            .stepper-item {
                flex: 0 0 auto !important;
                margin: 0 2px !important;
                /* Minimal margin to fit 6 items */
                width: auto !important;
                transform: scale(0.9);
                /* Slightly smaller circles if needed */
            }

            .step-name {
                display: none !important;
            }

            .stepper-arrow {
                display: none !important;
            }

            /* Hide the connector line to avoid visual clutter */
            .stepper-item::after {
                display: none !important;
            }
        }

        /* --- FIX BOTTOM SPACING --- */
        .content-wrapper {
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }

        .main-footer {
            display: none !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    </style>
@stop

@section('js')
    <script>
        // Global flag to control when to re-render design previews (avoids flickering)
        let shouldRenderDesigns = false;

        // --- EDIT / CLONE MODE DETECTION ---
        const isEditMode = {{ isset($editMode) && $editMode ? 'true' : 'false' }};
        const isCloneMode = {{ isset($cloneMode) && $cloneMode ? 'true' : 'false' }};
        const editProductId = {{ isset($product) && $product ? $product->id : 'null' }};

        // --- STATE MANAGEMENT ---
        @php
            // === CLONE MODE: Precargar datos desde producto origen SIN IDs ===
            if (isset($cloneMode) && $cloneMode && isset($cloneProduct)) {
                // Definición: copiar TODO excepto SKU (vacío para forzar uno nuevo)
                $defData = json_encode([
                    'name' => $cloneProduct->name . ' (Copia)',
                    'sku' => '', // FORZAR VACÍO - usuario debe asignar nuevo SKU
                    'category_id' => $cloneProduct->product_category_id,
                    'desc' => $cloneProduct->description ?? '',
                    'production_lead_time' => $cloneProduct->production_lead_time ?? 7,
                ]);

                // Variantes: copiar estructura SIN db_id (serán nuevas)
                $varIndex = 0;
                $varData = json_encode($cloneProduct->variants->map(function ($v) use (&$varIndex) {
                    $sizeVal = $v->attributeValues->first(fn($av) => ($av->attribute?->slug ?? '') === 'talla');
                    $colorVal = $v->attributeValues->first(fn($av) => ($av->attribute?->slug ?? '') === 'color');
                    $varIndex++;
                    return [
                        'temp_id' => 'clone_' . $varIndex, // ID temporal nuevo
                        // SIN db_id - variantes son nuevas
                        'sku' => '', // SKU vacío - se generará automático
                        'size' => $sizeVal?->value ?? '', 'size_id' => $sizeVal?->id ?? null,
                        'color' => $colorVal?->value ?? '', 'color_id' => $colorVal?->id ?? null,
                        'price' => (float) $v->price,
                    ];
                })->values()->toArray());

                // Diseños: copiar con export_id (son referencias, no se duplican)
                $desData = json_encode($cloneProduct->designs->map(function ($d) {
                    $exp = $d->generalExports()->where('status','aprobado')->first() ?? $d->exports()->where('status','aprobado')->first();
                    $appT = \App\Models\Application_types::find($d->pivot->application_type_id ?? 1);
                    return [
                        'export_id' => $exp?->id, 'design_id' => $d->id,
                        'name' => $exp?->application_label ?? $d->name, 'design_name' => $d->name,
                        'variant_name' => $exp?->variant?->name ?? null,
                        'app_type_slug' => $appT?->slug ?? 'general', 'app_type_name' => $appT?->nombre_aplicacion ?? 'General',
                        'stitches' => $exp?->stitches_count ?? 0, 'colors' => $exp?->colors_count ?? 0,
                        'dimensions' => $exp ? ($exp->width_mm.'x'.$exp->height_mm.' mm') : '',
                        'file_format' => strtoupper($exp?->file_format ?? ''),
                        'svg_content' => $exp?->svg_content ?? null, 'image' => $exp?->image?->display_url ?? null,
                        'scope' => 'global', 'target_variant' => null,
                    ];
                })->values()->toArray());

                // BOM: copiar materiales con cantidades y costos
                $bomData = json_encode($cloneProduct->materials->map(function ($m) {
                    $activeForVariants = $m->pivot->active_for_variants ?? null;
                    $targets = [];
                    $scope = 'global';
                    if ($activeForVariants) {
                        $decoded = is_string($activeForVariants) ? json_decode($activeForVariants, true) : $activeForVariants;
                        if (is_array($decoded) && count($decoded) > 0) {
                            $scope = 'specific';
                            // Convertir IDs a formato temp_id para clone mode
                            $targets = array_map(fn($id) => 'clone_' . $id, $decoded);
                        }
                    }
                    return [
                        'material_id' => $m->id, 'family_name' => $m->material?->name ?? '',
                        'variant_name' => $m->name ?? '',
                        'name' => ($m->material?->name ?? '').' - '.($m->name ?? ''),
                        'qty' => (float) $m->pivot->quantity, 'cost' => (float) $m->pivot->unit_cost,
                        'calculated_total' => (float) $m->pivot->total_cost,
                        'unit' => $m->material?->consumptionUnit?->symbol ?? $m->material?->baseUnit?->symbol ?? 'unid',
                        'scope' => $scope, 'targets' => $targets,
                    ];
                })->values()->toArray());

                // Extras: copiar servicios extra
                $extData = json_encode($cloneProduct->extras->map(function ($e) {
                    return [
                        'id' => $e->id, 'name' => $e->name,
                        'cost' => (float) ($e->pivot->snapshot_price ?? $e->price_addition ?? 0),
                        'price' => (float) ($e->pivot->snapshot_price ?? $e->price_addition ?? 0),
                        'time' => (int) ($e->pivot->snapshot_time ?? $e->minutes_addition ?? 0),
                    ];
                })->values()->toArray());

                // Financials: copiar costos y precios
                $finData = json_encode([
                    'material_cost' => (float) ($cloneProduct->materials_cost ?? 0),
                    'embroidery_cost' => (float) ($cloneProduct->embroidery_cost ?? 0),
                    'extras_cost' => (float) ($cloneProduct->extra_services_cost ?? 0),
                    'labor_cost' => (float) ($cloneProduct->labor_cost ?? 0),
                    'total_cost' => (float) ($cloneProduct->production_cost ?? 0),
                    'margin' => (float) ($cloneProduct->profit_margin ?? 35),
                    'price' => (float) ($cloneProduct->base_price ?? 0),
                    'lead_time' => (int) ($cloneProduct->production_lead_time ?? 7),
                ]);
            }
            // === EDIT MODE: construir datos desde $product ===
            elseif (isset($editMode) && $editMode && isset($product)) {
                $defData = json_encode([
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category_id' => $product->product_category_id,
                    'desc' => $product->description ?? '',
                    'production_lead_time' => $product->production_lead_time ?? 7,
                ]);

                $varData = json_encode($product->variants->map(function ($v) {
                    $sizeVal = $v->attributeValues->first(fn($av) => ($av->attribute?->slug ?? '') === 'talla');
                    $colorVal = $v->attributeValues->first(fn($av) => ($av->attribute?->slug ?? '') === 'color');
                    return [
                        'temp_id' => 'v_' . $v->id, 'db_id' => $v->id, 'sku' => $v->sku_variant,
                        'size' => $sizeVal?->value ?? '', 'size_id' => $sizeVal?->id ?? null,
                        'color' => $colorVal?->value ?? '', 'color_id' => $colorVal?->id ?? null,
                        'price' => (float) $v->price,
                    ];
                })->values()->toArray());

                $desData = json_encode($product->designs->map(function ($d) {
                    $exp = $d->generalExports()->where('status','aprobado')->first() ?? $d->exports()->where('status','aprobado')->first();
                    $appT = \App\Models\Application_types::find($d->pivot->application_type_id ?? 1);
                    return [
                        'export_id' => $exp?->id, 'design_id' => $d->id,
                        'name' => $exp?->application_label ?? $d->name, 'design_name' => $d->name,
                        'variant_name' => $exp?->variant?->name ?? null,
                        'app_type_slug' => $appT?->slug ?? 'general', 'app_type_name' => $appT?->nombre_aplicacion ?? 'General',
                        'stitches' => $exp?->stitches_count ?? 0, 'colors' => $exp?->colors_count ?? 0,
                        'dimensions' => $exp ? ($exp->width_mm.'x'.$exp->height_mm.' mm') : '',
                        'file_format' => strtoupper($exp?->file_format ?? ''),
                        'svg_content' => $exp?->svg_content ?? null, 'image' => $exp?->image?->display_url ?? null,
                        'scope' => 'global', 'target_variant' => null,
                    ];
                })->values()->toArray());

                $bomData = json_encode($product->materials->map(function ($m) {
                    $activeForVariants = $m->pivot->active_for_variants ?? null;
                    $targets = [];
                    $scope = 'global';
                    if ($activeForVariants) {
                        $decoded = is_string($activeForVariants) ? json_decode($activeForVariants, true) : $activeForVariants;
                        if (is_array($decoded) && count($decoded) > 0) {
                            $scope = 'specific';
                            // Convertir IDs a formato temp_id para edit mode: 'v_' + id
                            $targets = array_map(fn($id) => 'v_' . $id, $decoded);
                        }
                    }
                    return [
                        'material_id' => $m->id, 'family_name' => $m->material?->name ?? '',
                        'variant_name' => $m->name ?? '',
                        'name' => ($m->material?->name ?? '').' - '.($m->name ?? ''),
                        'qty' => (float) $m->pivot->quantity, 'cost' => (float) $m->pivot->unit_cost,
                        'calculated_total' => (float) $m->pivot->total_cost,
                        'unit' => $m->material?->consumptionUnit?->symbol ?? $m->material?->baseUnit?->symbol ?? 'unid',
                        'scope' => $scope, 'targets' => $targets,
                    ];
                })->values()->toArray());

                $extData = json_encode($product->extras->map(function ($e) {
                    return [
                        'id' => $e->id, 'name' => $e->name,
                        'cost' => (float) ($e->pivot->snapshot_price ?? $e->price_addition ?? 0),
                        'price' => (float) ($e->pivot->snapshot_price ?? $e->price_addition ?? 0),
                        'time' => (int) ($e->pivot->snapshot_time ?? $e->minutes_addition ?? 0),
                    ];
                })->values()->toArray());
            } else {
                // CREATE mode o EDIT tras error de validación
                $defData = json_encode(['name'=>old('name',''),'sku'=>old('sku',''),'category_id'=>old('product_category_id',''),'desc'=>old('description','')]);
                $varData = old('variants_json', '[]');
                $desData = old('embroideries_json', '[]');
                $bomData = old('materials_json', '[]');
                $extData = old('extras_json', '[]');
            }

            // FIX: En EDIT tras error, priorizar old() sobre datos de BD
            if (isset($editMode) && $editMode && old('variants_json')) {
                $varData = old('variants_json');
                $desData = old('embroideries_json', $desData);
                $bomData = old('materials_json', $bomData);
                $extData = old('extras_json', $extData);
            }
        @endphp

        const State = {
            step: 1,
            definition: {!! $defData !!},
            variants: {!! $varData !!},
            bom: {!! $bomData !!},
            designs: {!! $desData !!},
            extras: {!! $extData !!},
            @php
                // Financials: Clone mode ya definió $finData arriba
                if (isset($cloneMode) && $cloneMode && isset($finData)) {
                    // Ya está definido en el bloque de clone
                } elseif (isset($editMode) && $editMode && isset($product)) {
                    // Priorizar old() si hay error de validación
                    if (old('financials_json')) {
                        $finData = old('financials_json');
                    } else {
                        $finData = json_encode([
                            'material_cost' => (float) ($product->materials_cost ?? 0),
                            'embroidery_cost' => (float) ($product->embroidery_cost ?? 0),
                            'extras_cost' => (float) ($product->extra_services_cost ?? 0),
                            'labor_cost' => (float) ($product->labor_cost ?? 0),
                            'total_cost' => (float) ($product->production_cost ?? 0),
                            'margin' => (float) ($product->profit_margin ?? 35),
                            'price' => (float) ($product->base_price ?? 0),
                            'lead_time' => (int) ($product->production_lead_time ?? 7),
                        ]);
                    }
                } elseif (!isset($finData)) {
                    $finData = old('financials_json', json_encode([
                        'material_cost' => 0, 'embroidery_cost' => 0, 'extras_cost' => 0,
                        'labor_cost' => 0, 'total_cost' => 0, 'margin' => 35, 'price' => 0, 'lead_time' => 7,
                    ]));
                }
            @endphp
            financials: {!! $finData !!}
        };

        // Flag for async validation bypass
        let bypassBomValidation = false;

        // === STEPPER NAVIGATION SYSTEM ===
        const TOTAL_STEPS = 6;
        let maxVisitedStep = 1; // Track the furthest step visited

        // Navigate directly to a specific step (clicking on step numbers)
        window.navigateToStep = function(targetStep) {
            if (targetStep < 1 || targetStep > TOTAL_STEPS) return;
            if (targetStep === State.step) return; // Already on this step

            // Going BACKWARD is always allowed
            if (targetStep < State.step) {
                goToStep(targetStep);
                return;
            }

            // Going FORWARD only if we've visited that step before
            if (targetStep <= maxVisitedStep) {
                goToStep(targetStep);
            }
        };

        // Navigate by direction (-1 = prev, +1 = next)
        window.navigate = function(direction) {
            const newStep = State.step + direction;
            if (newStep < 1 || newStep > TOTAL_STEPS) return;

            // Validate current step before moving forward
            if (direction > 0 && !validateStep(State.step)) {
                return;
            }

            goToStep(newStep);
        };

        // Core function to switch steps
        function goToStep(targetStep) {
            // Run step-specific exit logic
            onStepExit(State.step);

            // CRITICAL: Hide ALL step contents first (prevents multiple visible steps)
            for (let i = 1; i <= TOTAL_STEPS; i++) {
                $(`#step${i}-content`).addClass('d-none');
            }

            // Update state
            State.step = targetStep;

            // Track furthest step visited (for click navigation)
            if (targetStep > maxVisitedStep) {
                maxVisitedStep = targetStep;
            }

            // Show only the target step content
            $(`#step${targetStep}-content`).removeClass('d-none');

            // Run step-specific enter logic
            onStepEnter(targetStep);

            // Update visual stepper
            updateStepper();

            // Update navigation buttons
            updateButtons();

            // Scroll to top
            window.scrollTo(0, 0);
        }

        // Update stepper visual indicators
        function updateStepper() {
            $('.stepper-item').each(function() {
                const stepNum = parseInt($(this).data('step'));
                $(this).removeClass('active completed');

                if (stepNum === State.step) {
                    $(this).addClass('active');
                } else if (stepNum < State.step) {
                    $(this).addClass('completed');
                }
            });
        }

        // Update prev/next button states
        function updateButtons() {
            // Prev button
            if (State.step === 1) {
                $('#btnPrev').prop('disabled', true);
            } else {
                $('#btnPrev').prop('disabled', false);
            }

            // Next button - on last step, change to submit mode
            if (State.step === TOTAL_STEPS) {
                $('#btnNext').addClass('btn-success-mode')
                    .html('<i class="fas fa-check"></i>');
                $('#btnNext').off('click').on('click', function() {
                    submitForm();
                });
            } else {
                $('#btnNext').removeClass('btn-success-mode')
                    .html('<i class="fas fa-chevron-right"></i>');
                $('#btnNext').off('click').on('click', function() {
                    navigate(1);
                });
            }
        }

        // Step validation
        function validateStep(step) {
            switch (step) {
                case 1:
                    // Validate product definition
                    const name = $('#inpName').val().trim();
                    const sku = $('#inpSku').val().trim();
                    if (!name) {
                        Swal.fire('Error', 'El nombre del producto es requerido', 'error');
                        return false;
                    }
                    if (!sku) {
                        Swal.fire('Error', 'El SKU es requerido', 'error');
                        return false;
                    }
                    // Save to state
                    State.definition.name = name;
                    State.definition.sku = sku;
                    State.definition.category = $('#inpCategory option:selected').text();
                    State.definition.category_id = $('#inpCategory').val();
                    return true;
                case 2:
                    // OBLIGATORIO: Al menos una presentación para continuar a Materiales
                    if (State.variants.length === 0) {
                        Swal.fire({
                            title: 'Presentaciones requeridas',
                            html: 'Debes agregar al menos <strong>una presentación</strong> (talla y color) antes de continuar.<br><br>Los materiales se asignan a las presentaciones del producto.',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                        return false;
                    }
                    return true;
                case 3:
                    // BOM can be optional
                    return true;
                case 4:
                    // Designs can be optional
                    return true;
                case 5:
                case 5:
                    // Validation for step 5 if needed (currently none)
                    return true;
                default:
                    return true;
            }
        }

        // Step enter logic
        function onStepEnter(step) {
            switch (step) {
                case 2:
                    if (typeof renderVariantsTable === 'function') renderVariantsTable();
                    break;
                case 3:
                    // NUEVO: Verificar si hay presentaciones para mostrar/bloquear BOM
                    if (State.variants.length === 0) {
                        $('#bomBlockedState').removeClass('d-none');
                        $('#bomActiveState').addClass('d-none');
                    } else {
                        $('#bomBlockedState').addClass('d-none');
                        $('#bomActiveState').removeClass('d-none');
                        initBomSelectors();
                        renderBOMSplit();
                    }
                    break;
                case 4:
                    // NUEVO: Diseño read-only - renderizar información
                    renderDesignReadOnly();
                    break;
                case 5:
                    recalcFinance();
                    if (typeof renderExtrasTable === 'function') renderExtrasTable();
                    break;
                case 6:
                    shouldRenderDesigns = true;
                    recalcFinance();
                    renderReview();
                    validateMaterialPrices();
                    break;
            }
        }

        // Helper: Check Prices (Promise) - Enterprise JIT Validation
        window.checkMaterialPrices = function() {
            return new Promise((resolve, reject) => {
                if (State.bom.length === 0) {
                    resolve({
                        has_changes: false,
                        changes: []
                    });
                    return;
                }

                const materials = State.bom.map(m => ({
                    id: m.material_id,
                    price: m.cost
                }));

                $.ajax({
                    url: '{{ route('admin.products.validate-prices') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        materials: materials
                    },
                    success: function(res) {
                        resolve(res);
                    },
                    error: function(err) {
                        reject(err);
                    }
                });
            });
        };

        // Validate Material Prices Function
        window.validateMaterialPrices = function() {
            if (State.bom.length === 0) return;

            const materials = State.bom.map(m => ({
                id: m.material_id,
                price: m.cost
            }));

            $.ajax({
                url: '{{ route('admin.products.validate-prices') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    materials: materials
                },
                success: function(res) {
                    if (res.has_changes) {
                        let html =
                            '<div class="text-left small mb-3"><div class="font-weight-bold mb-2">Han cambiado precios de materiales:</div><ul class="pl-3">';
                        res.changes.forEach(c => {
                            const diff = c.diff > 0 ? `+${c.diff.toFixed(2)}` : c.diff.toFixed(2);
                            const color = c.diff > 0 ? 'text-danger' : 'text-success';
                            html +=
                                `<li><strong>${c.name}</strong>: $${c.old_price.toFixed(2)} → $${c.new_price.toFixed(2)} <span class="${color}">(${diff})</span></li>`;
                        });
                        html += '</ul><p>¿Deseas actualizar los costos con los precios nuevos?</p></div>';

                        Swal.fire({
                            title: 'Actualización de Costos',
                            html: html,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, actualizar costos',
                            cancelButtonText: 'Mantener mis precios'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let updatedCount = 0;
                                res.changes.forEach(change => {
                                    // Search by material_id (check type consistency)
                                    const mat = State.bom.find(m => m.material_id == change
                                        .id);
                                    if (mat) {
                                        mat.price = parseFloat(change.new_price);
                                        // Recalculate total for this item
                                        mat.calculated_total = (mat.qty * mat.price);
                                        updatedCount++;
                                    }
                                });

                                if (updatedCount > 0) {
                                    recalcFinance();
                                    renderReview();
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                    Toast.fire({
                                        icon: 'success',
                                        title: 'Costos actualizados correctamente'
                                    });
                                }
                            }
                        });
                    }
                },
                error: function(err) {
                    console.error('Price validation error:', err);
                }
            });
        };

        // Step exit logic
        function onStepExit(step) {
            // Any cleanup needed when leaving a step
        }

        // ============================================================
        // BOM DIVIDIDO: Materiales Comunes + Por Presentación
        // Responsabilidad: Inicialización y manejo de selectores BOM
        // ============================================================

        // ============================================================
        // BOM PASO 3: INICIALIZACIÓN ROBUSTA
        // ============================================================

        // ------------------------------------------------------------
        // initBomSelectors: Punto de entrada principal para Paso 3
        // RESPONSABILIDAD: Configurar todos los selectores del BOM
        // ------------------------------------------------------------
        function initBomSelectors() {
            destroyBomSelect2Instances();
            populatePresentationSelector();
            initBomSelect2Instances();
            bindBomEventHandlers();
        }

        // ------------------------------------------------------------
        // destroyBomSelect2Instances: Limpia instancias previas
        // ------------------------------------------------------------
        function destroyBomSelect2Instances() {
            var selectors = [
                '#bomFamilySelectorGlobal',
                '#bomMaterialSelectorGlobal',
                '#bomFamilySelectorSpecific',
                '#bomMaterialSelectorSpecific',
                '#bomTargetVariant'
            ];

            selectors.forEach(function(sel) {
                var $el = $(sel);
                if ($el.length && $el.hasClass('select2-hidden-accessible')) {
                    try {
                        $el.select2('destroy');
                    } catch (e) {}
                }
            });
        }

        // ------------------------------------------------------------
        // populatePresentationSelector: Llena #bomTargetVariant
        // DEBE ejecutarse ANTES de Select2 init
        // ------------------------------------------------------------
        function populatePresentationSelector() {
            var vSel = $('#bomTargetVariant');
            if (!vSel.length) return;

            vSel.empty();
            vSel.append('<option value=""></option>');

            State.variants.forEach(function(v) {
                var label = v.size + ' / ' + v.color;
                vSel.append('<option value="' + v.temp_id + '">' + label + '</option>');
            });
        }

        // ------------------------------------------------------------
        // initBomSelect2Instances: Inicializa Select2 en selectores BOM
        // ------------------------------------------------------------
        function initBomSelect2Instances() {
            // --- Familia Global ---
            if ($('#bomFamilySelectorGlobal').length) {
                $('#bomFamilySelectorGlobal').select2({
                    width: '100%',
                    placeholder: 'Seleccione familia...',
                    allowClear: true
                });
            }

            // --- Material Global ---
            if ($('#bomMaterialSelectorGlobal').length) {
                $('#bomMaterialSelectorGlobal').select2({
                    width: '100%',
                    placeholder: 'Seleccione material...',
                    allowClear: true
                });
            }

            // --- Presentación Destino ---
            if ($('#bomTargetVariant').length) {
                $('#bomTargetVariant').select2({
                    width: '100%',
                    placeholder: 'Seleccione presentación...',
                    allowClear: true
                });
            }

            // --- Familia Específica ---
            if ($('#bomFamilySelectorSpecific').length) {
                $('#bomFamilySelectorSpecific').select2({
                    width: '100%',
                    placeholder: 'Seleccione familia...',
                    allowClear: true
                });
            }

            // --- Material Específico ---
            if ($('#bomMaterialSelectorSpecific').length) {
                $('#bomMaterialSelectorSpecific').select2({
                    width: '100%',
                    placeholder: 'Seleccione material...',
                    allowClear: true
                });
            }
        }

        // ------------------------------------------------------------
        // bindBomEventHandlers: Conecta eventos change a los selectores
        // IMPORTANTE: Select2 dispara 'change' en el elemento original
        // ------------------------------------------------------------
        function bindBomEventHandlers() {
            // Limpiar handlers previos (namespace .bom)
            $('#bomFamilySelectorGlobal').off('change.bom');
            $('#bomFamilySelectorSpecific').off('change.bom');
            $('#bomMaterialSelectorGlobal').off('change.bom');
            $('#bomMaterialSelectorSpecific').off('change.bom');

            // --- Handler: Familia GLOBAL cambia → cargar materiales ---
            $('#bomFamilySelectorGlobal').on('change.bom', function() {
                var familyId = $(this).val();
                fetchMaterialVariantsFor('Global', familyId);
            });

            // --- Handler: Familia ESPECÍFICA cambia → cargar materiales ---
            $('#bomFamilySelectorSpecific').on('change.bom', function() {
                var familyId = $(this).val();
                fetchMaterialVariantsFor('Specific', familyId);
            });

            // --- Handler: Material GLOBAL seleccionado → mostrar info ---
            $('#bomMaterialSelectorGlobal').on('change.bom', function() {
                showMaterialInfo('Global');
            });

            // --- Handler: Material ESPECÍFICO seleccionado → mostrar info ---
            $('#bomMaterialSelectorSpecific').on('change.bom', function() {
                showMaterialInfo('Specific');
            });
        }

        // ------------------------------------------------------------
        // fetchMaterialVariantsFor: Carga materiales via AJAX
        // @param suffix: 'Global' o 'Specific'
        // @param familyId: ID de la familia de material
        // ------------------------------------------------------------
        function fetchMaterialVariantsFor(suffix, familyId) {
            var sel = $('#bomMaterialSelector' + suffix);
            var infoBox = $('#materialInfo' + suffix);

            // Reset si no hay familia seleccionada
            if (!familyId) {
                sel.empty().prop('disabled', true).trigger('change');
                infoBox.addClass('d-none');
                return;
            }

            var url = '{{ route("admin.material-variants.conversiones", ["materialId" => "__ID__"]) }}'.replace('__ID__', familyId);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    sel.empty().prop('disabled', false);

                    // Agregar opción vacía para placeholder
                    sel.append('<option value=""></option>');

                    data.forEach(function(v) {
                        var opt = $('<option></option>')
                            .val(v.id)
                            .text(v.variant_name || v.text)
                            .attr('data-cost', v.cost_base || 0)
                            .attr('data-stock', v.stock_real || 0)
                            .attr('data-unit', v.symbol || 'unid')
                            .attr('data-name', v.text || '')
                            .attr('data-family', v.family_name || '')
                            .attr('data-variant', v.variant_name || '')
                            .attr('data-sku', v.sku || '');
                        sel.append(opt);
                    });

                    sel.trigger('change');
                },
                error: function() {
                    Swal.fire('Error', 'No se pudieron cargar los materiales', 'error');
                }
            });
        }

        // ------------------------------------------------------------
        // showMaterialInfo: Muestra info del material seleccionado
        // @param suffix: 'Global' o 'Specific'
        // ------------------------------------------------------------
        function showMaterialInfo(suffix) {
            var sel = $('#bomMaterialSelector' + suffix);
            var opt = sel.find('option:selected');
            var infoBox = $('#materialInfo' + suffix);

            if (!sel.val()) {
                infoBox.addClass('d-none');
                return;
            }

            var stock = parseFloat(opt.data('stock')) || 0;
            var cost = parseFloat(opt.data('cost')) || 0;
            var unit = opt.data('unit') || 'unid';

            $('#matStock' + suffix).text(stock.toLocaleString() + ' ' + unit);
            $('#matCost' + suffix).text('$' + cost.toFixed(2));
            $('#bomUnit' + suffix).text(unit);
            infoBox.removeClass('d-none');
        }

        // ------------------------------------------------------------
        // NOTA: Event handlers para BOM ahora se bindean en bindBomEventHandlers()
        // que se llama desde initBomSelectors() después de inicializar Select2
        // ------------------------------------------------------------

        // Agregar material COMÚN (scope: global)
        window.addMaterialCommon = function() {
            const matId = $('#bomMaterialSelectorGlobal').val();
            const qty = parseFloat($('#bomQtyGlobal').val());

            if (!matId || !qty || qty <= 0) {
                Swal.fire('Error', 'Seleccione un material e ingrese una cantidad válida', 'warning');
                return;
            }

            // VALIDACIÓN: No permitir duplicar material que ya existe en Específicos
            const existsInSpecific = State.bom.some(m => m.material_id == matId && m.scope === 'specific');
            if (existsInSpecific) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Material ya existe',
                    html: 'Este material ya está agregado en <strong>Ajustes por Presentación</strong>.<br><br>No puede existir en ambas secciones. Elimínelo de Ajustes primero si desea agregarlo como material común.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            const opt = $('#bomMaterialSelectorGlobal option:selected');
            const cost = parseFloat(opt.data('cost')) || 0;
            const unit = opt.data('unit') || 'unid';

            const material = {
                material_id: matId,
                name: opt.data('name') || '',
                family_name: opt.data('family') || '',
                variant_name: opt.data('variant') || '',
                sku: opt.data('sku') || '',
                cost: cost,
                unit: unit,
                qty: qty,
                is_primary: $('#materialIsPrimaryGlobal').is(':checked'),
                scope: 'global',
                targets: []
            };

            // Verificar si ya existe
            const existingIdx = State.bom.findIndex(m => m.material_id == matId && m.scope === 'global');
            if (existingIdx > -1) {
                State.bom[existingIdx].qty += qty;
                State.bom[existingIdx].calculated_total = State.bom[existingIdx].cost * State.bom[existingIdx].qty;
            } else {
                material.calculated_total = material.cost * material.qty;
                State.bom.push(material);
            }

            renderBOMSplit();
            recalcFinance();

            // Reset form
            $('#bomQtyGlobal').val('');
            $('#bomMaterialSelectorGlobal').val(null).trigger('change');
            $('#materialInfoGlobal').addClass('d-none');
            $('#materialIsPrimaryGlobal').prop('checked', false);
        };

        // Agregar material ESPECÍFICO (scope: specific)
        window.addMaterialSpecific = function() {
            const targetVariant = $('#bomTargetVariant').val();
            const matId = $('#bomMaterialSelectorSpecific').val();
            const qty = parseFloat($('#bomQtySpecific').val());

            if (!targetVariant) {
                Swal.fire('Error', 'Seleccione primero la presentación destino', 'warning');
                return;
            }

            if (!matId || !qty || qty <= 0) {
                Swal.fire('Error', 'Seleccione un material e ingrese una cantidad válida', 'warning');
                return;
            }

            // VALIDACIÓN: No permitir duplicar material que ya existe en Comunes
            const existsInGlobal = State.bom.some(m => m.material_id == matId && m.scope === 'global');
            if (existsInGlobal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Material ya existe',
                    html: 'Este material ya está agregado en <strong>Materiales Comunes</strong>.<br><br>Si necesita ajustar la cantidad para esta presentación, elimínelo de Comunes primero.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            const opt = $('#bomMaterialSelectorSpecific option:selected');
            const cost = parseFloat(opt.data('cost')) || 0;
            const unit = opt.data('unit') || 'unid';

            const material = {
                material_id: matId,
                name: opt.data('name') || '',
                family_name: opt.data('family') || '',
                variant_name: opt.data('variant') || '',
                sku: opt.data('sku') || '',
                cost: cost,
                unit: unit,
                qty: qty,
                is_primary: false,
                scope: 'specific',
                targets: [targetVariant]
            };

            // Verificar si ya existe exactamente igual
            const existingIdx = State.bom.findIndex(m =>
                m.material_id == matId &&
                m.scope === 'specific' &&
                m.targets.length === 1 &&
                m.targets[0] === targetVariant
            );

            if (existingIdx > -1) {
                State.bom[existingIdx].qty += qty;
                State.bom[existingIdx].calculated_total = State.bom[existingIdx].cost * State.bom[existingIdx].qty;
            } else {
                material.calculated_total = material.cost * material.qty;
                State.bom.push(material);
            }

            renderBOMSplit();
            recalcFinance();

            // Reset form (mantener presentación seleccionada para agregar más)
            $('#bomQtySpecific').val('');
            $('#bomMaterialSelectorSpecific').val(null).trigger('change');
            $('#materialInfoSpecific').addClass('d-none');
        };

        // Renderizar BOM dividido en dos tablas
        function renderBOMSplit() {
            const globalMaterials = State.bom.filter(m => m.scope === 'global');
            const specificMaterials = State.bom.filter(m => m.scope === 'specific');

            // Renderizar tabla GLOBAL
            const tbodyGlobal = $('#bomTableBodyGlobal');
            tbodyGlobal.empty();
            let totalGlobal = 0;

            if (globalMaterials.length === 0) {
                tbodyGlobal.html(`<tr id="noMaterialsGlobalRow">
                    <td colspan="4" class="text-center text-muted py-3">
                        <i class="fas fa-inbox mr-1"></i> Sin materiales comunes
                    </td>
                </tr>`);
            } else {
                globalMaterials.forEach((m, idx) => {
                    const realIdx = State.bom.findIndex(x => x === m);
                    totalGlobal += m.calculated_total || 0;
                    tbodyGlobal.append(`<tr>
                        <td>
                            <div class="font-weight-bold">${m.family_name || m.name}${m.variant_name ? ' - ' + m.variant_name : ''}</div>
                            <small class="text-muted">${m.sku || ''}</small>
                            ${m.is_primary ? '<span class="badge badge-warning badge-sm ml-1">Principal</span>' : ''}
                        </td>
                        <td class="text-center" style="min-width: 120px;">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm text-center bom-qty-input"
                                    value="${m.qty}" step="0.01" min="0.01"
                                    data-bom-idx="${realIdx}"
                                    onchange="updateBomQty(${realIdx}, this.value)"
                                    style="max-width: 70px;">
                                <div class="input-group-append">
                                    <span class="input-group-text" style="font-size: 0.8rem;">${m.unit}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-right font-weight-bold">$${(m.calculated_total || 0).toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBomItem(${realIdx})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`);
                });
            }
            $('#bomTotalGlobal').text('$' + totalGlobal.toFixed(2));

            // Renderizar tabla ESPECÍFICO
            const tbodySpecific = $('#bomTableBodySpecific');
            tbodySpecific.empty();
            let totalSpecific = 0;

            if (specificMaterials.length === 0) {
                tbodySpecific.html(`<tr id="noMaterialsSpecificRow">
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="fas fa-inbox mr-1"></i> Sin materiales específicos
                    </td>
                </tr>`);
            } else {
                specificMaterials.forEach((m, idx) => {
                    const realIdx = State.bom.findIndex(x => x === m);
                    totalSpecific += m.calculated_total || 0;

                    // Obtener nombre de presentación
                    const targetNames = m.targets.map(tid => {
                        const v = State.variants.find(va => va.temp_id === tid);
                        return v ? `${v.size} / ${v.color}` : '';
                    }).filter(Boolean).join(', ');

                    tbodySpecific.append(`<tr>
                        <td>
                            <div class="font-weight-bold">${m.family_name || m.name}${m.variant_name ? ' - ' + m.variant_name : ''}</div>
                            <small class="text-muted">${m.sku || ''}</small>
                        </td>
                        <td><span class="badge badge-info">${targetNames}</span></td>
                        <td class="text-center" style="min-width: 120px;">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm text-center bom-qty-input"
                                    value="${m.qty}" step="0.01" min="0.01"
                                    data-bom-idx="${realIdx}"
                                    onchange="updateBomQty(${realIdx}, this.value)"
                                    style="max-width: 70px;">
                                <div class="input-group-append">
                                    <span class="input-group-text" style="font-size: 0.8rem;">${m.unit}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-right font-weight-bold">$${(m.calculated_total || 0).toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBomItem(${realIdx})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`);
                });
            }
            $('#bomTotalSpecific').text('$' + totalSpecific.toFixed(2));

            // Total general
            $('#bomGrandTotal').text('$' + (totalGlobal + totalSpecific).toFixed(2));
        }

        // Actualizar cantidad de un material en el BOM
        window.updateBomQty = function(idx, newQty) {
            const qty = parseFloat(newQty);
            if (isNaN(qty) || qty <= 0) {
                Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'warning');
                renderBOMSplit(); // Re-render to reset input
                return;
            }

            if (idx >= 0 && idx < State.bom.length) {
                State.bom[idx].qty = qty;
                State.bom[idx].calculated_total = (parseFloat(State.bom[idx].cost) || 0) * qty;
                renderBOMSplit();
                recalcFinance();
            }
        };

        // Eliminar item del BOM
        window.removeBomItem = function(idx) {
            if (idx >= 0 && idx < State.bom.length) {
                State.bom.splice(idx, 1);
                renderBOMSplit();
                recalcFinance();
            }
        };

        // ============================================================
        // PASO 4: DISEÑOS DE BORDADO
        // ============================================================

        // URL para cargar diseños dinámicamente
        const designExportsUrl = "{{ route('admin.products.ajax.approved_exports') }}";

        // Renderizar cards de diseños/producciones asignados en el grid de Step 4
        function renderDesignCards() {
            const grid = $('#designCardsGrid');
            const noMsg = $('#noDesignsMessage');
            const summary = $('#designSummary');

            grid.empty();

            if (State.designs.length === 0) {
                noMsg.removeClass('d-none');
                summary.addClass('d-none');
                return;
            }

            noMsg.addClass('d-none');
            summary.removeClass('d-none');

            let totalStitches = 0;

            State.designs.forEach((d, idx) => {
                totalStitches += parseInt(d.stitches) || 0;

                // Badge de alcance (scope)
                const scopeBadge = d.scope === 'global'
                    ? '<span class="badge badge-dark"><i class="fas fa-globe mr-1"></i>Todas</span>'
                    : `<span class="badge badge-info"><i class="fas fa-tag mr-1"></i>${getVariantName(d.target_variant)}</span>`;

                // Badge de tipo de aplicación (ubicación)
                const appTypeBadge = d.app_type_name
                    ? `<span class="badge badge-warning"><i class="fas fa-map-marker-alt mr-1"></i>${d.app_type_name}</span>`
                    : '<span class="badge badge-secondary">General</span>';

                // Badge de formato de archivo
                const formatBadge = d.file_format
                    ? `<span class="badge badge-light border">${d.file_format}</span>`
                    : '';

                // Preview: Prioridad 1) SVG del archivo, 2) imagen referencia, 3) placeholder
                // NUNCA usar imágenes de diseño/variante
                let imgHtml;
                if (d.svg_content) {
                    // SVG inline del archivo de bordado (preview REAL)
                    imgHtml = `<div class="d-flex align-items-center justify-content-center bg-white h-100" style="overflow: hidden;">${d.svg_content}</div>`;
                } else if (d.image) {
                    imgHtml = `<img src="${d.image}" style="max-width: 100%; max-height: 70px; object-fit: contain;" alt="Imagen de referencia">`;
                } else {
                    const fmt = (d.file_format || 'EMB').toUpperCase();
                    imgHtml = `<div class="d-flex flex-column align-items-center justify-content-center bg-secondary text-white h-100 rounded" title="Sin preview">
                        <i class="fas fa-file-code" style="font-size: 1.5rem;"></i>
                        <small style="font-size: 0.7rem; font-weight: bold;">${fmt}</small>
                    </div>`;
                }

                // Trazabilidad: Diseño → Variante (si existe)
                const hasVariant = d.variant_name !== null && d.variant_name !== undefined && d.variant_name !== '';
                const originHtml = hasVariant
                    ? `<small class="text-muted d-block text-truncate" title="${d.design_name} → ${d.variant_name}"><i class="fas fa-sitemap mr-1"></i>${d.design_name} → ${d.variant_name}</small>`
                    : `<small class="text-muted d-block text-truncate" title="${d.design_name || 'Sin diseño'}"><i class="fas fa-paint-brush mr-1"></i>${d.design_name || 'Sin diseño'}</small>`;

                grid.append(`
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card h-100 border shadow-sm design-assigned-card">
                            <div class="card-body p-3">
                                <!-- Encabezado con imagen y nombre -->
                                <div class="d-flex mb-2">
                                    <div class="design-thumb bg-light rounded mr-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 70px; flex-shrink: 0; overflow: hidden;">
                                        ${imgHtml}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="font-weight-bold mb-1 text-truncate" title="${d.name || 'Sin nombre'}">${d.name || 'Sin nombre'}</h6>
                                        ${originHtml}
                                    </div>
                                </div>

                                <!-- Badges: Tipo aplicación + Formato + Alcance -->
                                <div class="mb-2">
                                    ${appTypeBadge}
                                    ${formatBadge}
                                    ${scopeBadge}
                                </div>

                                <!-- Datos técnicos -->
                                <div class="row text-center small border-top pt-2">
                                    <div class="col-4" title="Dimensiones">
                                        <i class="fas fa-ruler-combined text-primary"></i>
                                        <div class="font-weight-bold small">${d.dimensions || '-'}</div>
                                    </div>
                                    <div class="col-4" title="Colores">
                                        <i class="fas fa-palette text-info"></i>
                                        <div class="font-weight-bold small">${d.colors || 0}</div>
                                    </div>
                                    <div class="col-4" title="Puntadas">
                                        <i class="fas fa-th text-success"></i>
                                        <div class="font-weight-bold small">${(d.stitches || 0).toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top p-2 text-right">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDesign(${idx})" title="Quitar producción">
                                    <i class="fas fa-trash"></i> Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                `);
            });

            // Actualizar resumen
            $('#totalDesignsCount').text(State.designs.length);
            $('#totalStitchesCount').text(totalStitches.toLocaleString());
            const rate = parseFloat($('#finStitchRate').val()) || 1.0;
            const estCost = (totalStitches / 1000) * rate;
            $('#estimatedEmbCost').text('$' + estCost.toFixed(2));
        }

        // Obtener nombre de variante por temp_id
        function getVariantName(tempId) {
            const v = State.variants.find(va => va.temp_id === tempId);
            return v ? `${v.size} / ${v.color}` : tempId;
        }

        // Toggle producto liso
        window.toggleProductoLiso = function() {
            const isLiso = $('#toggleNoDesign').is(':checked');
            if (isLiso) {
                State.designs = [];
                renderDesignCards();
            }
        };

        // =====================================================
        // MODAL PRODUCCIONES DE BORDADO - VERSIÓN DEFINITIVA
        // =====================================================
        window.openDesignModal = function() {
            Swal.fire({
                title: 'Cargando producciones...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: designExportsUrl,
                method: 'GET',
                dataType: 'json',
                success: function(catalog) {
                    Swal.close();

                    if (!catalog || catalog.length === 0) {
                        Swal.fire('Sin producciones', 'No hay producciones de bordado aprobadas.', 'info');
                        return;
                    }

                    window._designCatalog = catalog;

                    // Extraer tipos de aplicación únicos (por slug para filtrar, nombre para mostrar)
                    const appTypesMap = {};
                    catalog.forEach(e => {
                        if (e.app_type_slug) {
                            appTypesMap[e.app_type_slug] = e.app_type_name || e.app_type_slug;
                        }
                    });
                    let appTypeOptions = '<option value="">Todas las ubicaciones</option>';
                    Object.keys(appTypesMap).sort().forEach(slug => {
                        appTypeOptions += `<option value="${slug}">${appTypesMap[slug]}</option>`;
                    });

                    // Presentaciones del producto
                    let variantOptionsHtml = '';
                    State.variants.forEach(v => {
                        variantOptionsHtml += `<option value="${v.temp_id}">${v.size} / ${v.color}</option>`;
                    });
                    const hasVariants = State.variants.length > 0;

                    Swal.fire({
                        title: '<i class="fas fa-tshirt mr-2"></i> Seleccionar Producción',
                        width: '950px',
                        showCloseButton: true,
                        html: `
                            <div class="text-left">
                                <!-- FILTROS -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            </div>
                                            <input type="text" id="designSearchInput" class="form-control" placeholder="Buscar por nombre de producción...">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <select id="filterAppType" class="form-control">
                                            ${appTypeOptions}
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-secondary btn-block" onclick="clearDesignFilters()" title="Limpiar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- CONTADOR -->
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><strong id="designResultsCount">${catalog.length}</strong> producciones</small>
                                </div>

                                <!-- GRID DE PRODUCCIONES -->
                                <div id="designModalGrid" style="max-height: 380px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px; background: #fafafa;">
                                    <div class="row" id="designCardsModalContainer"></div>
                                    <div id="noDesignResults" class="text-center py-5 d-none">
                                        <i class="fas fa-search text-muted" style="font-size: 2.5rem;"></i>
                                        <p class="text-muted mt-2 mb-0">Sin resultados</p>
                                    </div>
                                </div>

                                <!-- PANEL SELECCIÓN -->
                                <div id="designSelectionPanel" class="mt-3 p-3 border rounded d-none" style="background: #e8f4fd;">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <small class="text-muted">Seleccionado:</small>
                                            <div class="font-weight-bold" id="selectedDesignName">-</div>
                                            <input type="hidden" id="selectedDesignId">
                                        </div>
                                        <div class="col-md-7">
                                            <small class="text-muted d-block mb-1">Aplicar a:</small>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="custom-control custom-radio mr-3">
                                                    <input type="radio" id="scopeGlobal" name="designScope" class="custom-control-input" value="global" checked>
                                                    <label class="custom-control-label" for="scopeGlobal">
                                                        <i class="fas fa-globe-americas text-primary mr-1"></i> Todas las presentaciones
                                                    </label>
                                                </div>
                                                ${hasVariants ? `
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="scopeSpecific" name="designScope" class="custom-control-input" value="specific">
                                                    <label class="custom-control-label" for="scopeSpecific">
                                                        <i class="fas fa-bullseye text-warning mr-1"></i> Solo:
                                                    </label>
                                                </div>
                                                <select id="swalVariantSelect" class="form-control form-control-sm ml-2" style="width: auto; min-width: 140px;" disabled>
                                                    <option value="">Seleccione...</option>
                                                    ${variantOptionsHtml}
                                                </select>
                                                ` : '<span class="text-muted small">(Defina presentaciones primero)</span>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-plus mr-1"></i> Agregar',
                        cancelButtonText: 'Cerrar',
                        confirmButtonColor: '#28a745',
                        didOpen: () => {
                            renderDesignModalCards(catalog);
                            $('#designSearchInput').on('input', filterDesignModal);
                            $('#filterAppType').on('change', filterDesignModal);
                            $('input[name="designScope"]').on('change', function() {
                                $('#swalVariantSelect').prop('disabled', $(this).val() !== 'specific');
                                if ($(this).val() !== 'specific') $('#swalVariantSelect').val('');
                            });
                        },
                        preConfirm: () => {
                            const exportId = $('#selectedDesignId').val();
                            if (!exportId) {
                                Swal.showValidationMessage('Seleccione una producción haciendo clic en una tarjeta');
                                return false;
                            }
                            const scope = $('input[name="designScope"]:checked').val() || 'global';
                            let targetVariant = null;
                            if (scope === 'specific') {
                                targetVariant = $('#swalVariantSelect').val();
                                if (!targetVariant) {
                                    Swal.showValidationMessage('Seleccione la presentación destino');
                                    return false;
                                }
                            }
                            // Validar duplicados
                            const existingGlobal = State.designs.find(d => d.export_id == exportId && d.scope === 'global');
                            if (existingGlobal) {
                                Swal.showValidationMessage('Esta producción ya está asignada a TODAS las presentaciones');
                                return false;
                            }
                            const existingSpecific = State.designs.filter(d => d.export_id == exportId && d.scope === 'specific');
                            if (scope === 'global' && existingSpecific.length > 0) {
                                Swal.showValidationMessage('Esta producción tiene asignaciones específicas. Elimínelas primero.');
                                return false;
                            }
                            if (scope === 'specific' && existingSpecific.some(d => d.target_variant === targetVariant)) {
                                Swal.showValidationMessage('Ya asignada a esa presentación');
                                return false;
                            }
                            const sel = window._designCatalog.find(d => d.id == exportId);
                            return { exportId, scope, targetVariant, sel };
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            const { exportId, scope, targetVariant, sel } = result.value;
                            State.designs.push({
                                export_id: exportId,
                                name: sel.export_name || 'Producción',
                                stitches: parseInt(sel.stitches) || 0,
                                dimensions: sel.dimensions_label || '',
                                colors: parseInt(sel.colors) || 0,
                                app_type_slug: sel.app_type_slug || '',
                                app_type_name: sel.app_type_name || '',
                                design_name: sel.design_name || '',
                                variant_name: sel.variant_name || null,
                                // Preview: prioridad SVG > imagen > null
                                svg_content: sel.svg_content || null,
                                image: sel.image_url || null,
                                file_format: sel.file_format || '',
                                scope: scope,
                                target_variant: targetVariant
                            });
                            $('#toggleNoDesign').prop('checked', false);
                            renderDesignCards();
                            recalcFinance();
                        }
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error', 'No se pudieron cargar las producciones.', 'error');
                }
            });
        };

        // =====================================================
        // RENDERIZAR CARDS DE PRODUCCIÓN EN MODAL
        // =====================================================
        window.renderDesignModalCards = function(productions) {
            const container = $('#designCardsModalContainer');
            container.empty();

            if (!productions || productions.length === 0) {
                $('#noDesignResults').removeClass('d-none');
                $('#designResultsCount').text('0');
                return;
            }

            $('#noDesignResults').addClass('d-none');
            $('#designResultsCount').text(productions.length);

            productions.forEach(p => {
                const isAssigned = State.designs.some(d => d.export_id == p.id);
                const assignedClass = isAssigned ? 'border-warning' : '';
                const assignedBadge = isAssigned ? '<span class="badge badge-warning position-absolute" style="top: 6px; right: 6px; font-size: 0.7rem; z-index: 5;">YA ASIGNADO</span>' : '';

                // Preview: Prioridad 1) SVG del archivo, 2) imagen referencia, 3) placeholder
                // NUNCA usar imágenes de diseño/variante
                let previewHtml;
                if (p.svg_content) {
                    // SVG inline del archivo de bordado (preview REAL)
                    previewHtml = `<div class="d-flex align-items-center justify-content-center bg-white border rounded" style="width: 70px; height: 70px; overflow: hidden; flex-shrink: 0;">${p.svg_content}</div>`;
                } else if (p.image_url) {
                    // Imagen de referencia subida
                    previewHtml = `<img src="${p.image_url}" class="border rounded" style="width: 70px; height: 70px; object-fit: contain; flex-shrink: 0;" title="Imagen de referencia">`;
                } else {
                    // Placeholder neutro con formato del archivo
                    const fmt = (p.file_format || 'EMB').toUpperCase();
                    previewHtml = `<div class="d-flex flex-column align-items-center justify-content-center bg-secondary rounded text-white" style="width: 70px; height: 70px; flex-shrink: 0;" title="Sin preview">
                         <i class="fas fa-file-code" style="font-size: 1.5rem;"></i>
                         <span style="font-size: 0.7rem; font-weight: bold;">${fmt}</span>
                       </div>`;
                }

                // Trazabilidad (origen)
                const hasVariant = p.variant_id !== null && p.variant_id !== undefined;
                const originText = hasVariant
                    ? `${p.design_name} → ${p.variant_name}`
                    : p.design_name;

                const cardHtml = `
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card design-modal-card h-100 ${assignedClass}" data-id="${p.id}" onclick="selectDesignCard(${p.id})" style="cursor: pointer; transition: all 0.15s;">
                            ${assignedBadge}
                            <div class="card-body p-3">
                                <!-- HEADER: Preview + Info -->
                                <div class="d-flex align-items-start mb-2">
                                    <div class="mr-3">${previewHtml}</div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="font-weight-bold mb-1 text-truncate" style="font-size: 0.95rem;" title="${p.export_name || ''}">${p.export_name || 'Sin nombre'}</h6>
                                        <span class="badge badge-info" style="font-size: 0.75rem;">
                                            <i class="fas fa-map-marker-alt mr-1"></i>${p.app_type_name || 'General'}
                                        </span>
                                    </div>
                                </div>
                                <!-- SPECS: Formato, Puntadas, Colores, Dimensiones -->
                                <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 8px;">
                                    <span class="badge badge-dark" style="font-size: 0.75rem;">${p.file_format || 'PES'}</span>
                                    <span style="font-size: 0.85rem;"><i class="fas fa-th text-success mr-1"></i><strong>${p.stitches_formatted || '0'}</strong> pts</span>
                                    <span style="font-size: 0.85rem;"><i class="fas fa-palette text-info mr-1"></i><strong>${p.colors || 0}</strong> col</span>
                                    <span style="font-size: 0.85rem;"><i class="fas fa-ruler-combined text-primary mr-1"></i>${p.dimensions_label || '-'}</span>
                                </div>
                                <!-- ORIGEN / TRAZABILIDAD -->
                                <div class="border-top pt-2">
                                    <small class="text-muted d-block text-truncate" style="font-size: 0.8rem;" title="${originText}">
                                        <i class="fas fa-sitemap mr-1"></i>${originText}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(cardHtml);
            });
        };

        // =====================================================
        // SELECCIONAR CARD DE PRODUCCIÓN
        // =====================================================
        window.selectDesignCard = function(productionId) {
            $('.design-modal-card').removeClass('border-primary').css('box-shadow', '');
            const card = $(`.design-modal-card[data-id="${productionId}"]`);
            card.addClass('border-primary').css('box-shadow', '0 0 0 3px rgba(0,123,255,0.3)');

            const prod = window._designCatalog.find(d => d.id == productionId);
            if (prod) {
                $('#selectedDesignId').val(productionId);
                $('#selectedDesignName').html(`
                    <span class="badge badge-info mr-1">${prod.app_type_name || 'General'}</span>
                    ${prod.export_name || 'Producción'}
                    <small class="text-muted ml-2">${prod.file_format || ''} · ${prod.stitches_formatted || 0} pts</small>
                `);
                $('#designSelectionPanel').removeClass('d-none');
            }
        };

        // =====================================================
        // FILTRAR PRODUCCIONES EN MODAL
        // =====================================================
        window.filterDesignModal = function() {
            const searchTerm = String($('#designSearchInput').val() || '').toLowerCase().trim();
            const appTypeFilter = String($('#filterAppType').val() || '');

            if (!window._designCatalog) return;

            let filtered = window._designCatalog.filter(p => {
                // Filtro por nombre de producción
                const name = String(p.export_name || '').toLowerCase();
                const matchesSearch = !searchTerm || name.includes(searchTerm);

                // Filtro por tipo de aplicación (slug)
                const matchesAppType = !appTypeFilter || p.app_type_slug === appTypeFilter;

                return matchesSearch && matchesAppType;
            });

            renderDesignModalCards(filtered);

            // Limpiar selección si ya no está visible
            const selectedId = $('#selectedDesignId').val();
            if (selectedId && !filtered.some(d => d.id == selectedId)) {
                $('#selectedDesignId').val('');
                $('#selectedDesignName').html('-');
                $('#designSelectionPanel').addClass('d-none');
            }
        };

        // Limpiar filtros
        window.clearDesignFilters = function() {
            $('#designSearchInput').val('');
            $('#filterAppType').val('');
            if (window._designCatalog) {
                renderDesignModalCards(window._designCatalog);
            }
            $('#selectedDesignId').val('');
            $('#selectedDesignName').html('-');
            $('#designSelectionPanel').addClass('d-none');
        };

        // Quitar diseño
        window.removeDesign = function(idx) {
            if (idx >= 0 && idx < State.designs.length) {
                State.designs.splice(idx, 1);
                renderDesignCards();
                recalcFinance();
            }
        };

        // Compatibilidad: función anterior
        function renderDesignReadOnly() {
            renderDesignCards();
        }

        // Load templates into step containers
        function loadTemplates() {
            for (let i = 1; i <= TOTAL_STEPS; i++) {
                const template = $(`#tpl_step${i}`).html();
                if (template) {
                    $(`#step${i}-content`).html(template);
                }
                // Ensure only step 1 is visible after loading
                if (i === 1) {
                    $(`#step${i}-content`).removeClass('d-none');
                } else {
                    $(`#step${i}-content`).addClass('d-none');
                }
            }
            // Initialize stepper visual state
            updateStepper();
        }

        // Initialize plugins (Select2, etc)
        function initPlugins() {
            // Initialize Select2 on all selects EXCEPT BOM selectors
            // BOM selectors are handled by initBomSelectors() when entering Step 3
            var bomSelectorIds = [
                'bomFamilySelectorGlobal',
                'bomMaterialSelectorGlobal',
                'bomFamilySelectorSpecific',
                'bomMaterialSelectorSpecific',
                'bomTargetVariant'
            ];

            $('.select2').each(function() {
                var id = $(this).attr('id');
                // Skip BOM selectors - they are initialized in initBomSelectors()
                if (bomSelectorIds.indexOf(id) !== -1) {
                    return; // continue to next element
                }
                $(this).select2({
                    width: '100%',
                    allowClear: false
                });
            });

            // Initialize Select2 for category
            $('#inpCategory').select2({
                width: '100%',
                placeholder: 'Seleccione categoría...',
                allowClear: false
            });
        }

        // --- SKU GENERATION (ENTERPRISE-GRADE) ---
        // Format: [NAME_INITIALS]-[CATEGORY_CODE]-[UNIQUE_ID]
        // Example: GUPR-CAM-A7K2
        window.generateSKU = function() {
            const name = $('#inpName').val().trim();
            const categoryId = $('#inpCategory').val();
            const categoryText = $('#inpCategory option:selected').text().trim();

            if (!name) {
                $('#inpSku').val('');
                return;
            }

            // STEP 1: Generate name part (first letter of first 4 words, uppercase)
            const cleanName = name.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); // Remove special chars
            const words = cleanName.split(/\s+/).filter(w => w.length > 0).slice(0, 4);
            let namePart = words.map(w => {
                // Handle accented characters
                const normalized = w.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                return normalized.charAt(0).toUpperCase();
            }).join('');

            // Ensure minimum 2 characters (pad with first word letters if needed)
            if (namePart.length < 2 && words.length > 0) {
                const firstWord = words[0].normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
                namePart = firstWord.substring(0, Math.max(2, 4 - namePart.length));
            }

            // STEP 2: Generate category code (only if category is selected)
            let catPart = '';
            if (categoryId && categoryText && !categoryText.includes('--')) {
                // Take first 3 consonants or chars from category
                const cleanCat = categoryText.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
                catPart = cleanCat.replace(/[^A-Z]/g, '').substring(0, 3);
            }

            // STEP 3: Generate unique ID (timestamp-based, alphanumeric for compactness)
            // Using last 4 chars of timestamp base36 for uniqueness + randomness
            const timestamp = Date.now().toString(36).toUpperCase();
            const uniqueId = timestamp.slice(-4);

            // STEP 4: Assemble SKU (clean format, no consecutive dashes)
            let sku;
            if (catPart) {
                sku = `${namePart}-${catPart}-${uniqueId}`;
            } else {
                sku = `${namePart}-${uniqueId}`;
            }

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

            // ============================================================
            // INICIALIZACIÓN: Renderizar estado desde BD (edit) o repoblado (error validación)
            // ============================================================

            // En modo EDIT o CLONE: precargar campos del formulario
            if ((isEditMode || isCloneMode) && State.definition) {
                $('#inpName').val(State.definition.name || '');
                $('#inpSku').val(State.definition.sku || '');
                $('#inpCategory').val(State.definition.category_id || '').trigger('change');
                $('#inpDesc').val(State.definition.desc || State.definition.description || '');
                // Tiempo de producción
                const leadTimeVal = State.definition.production_lead_time || State.financials.lead_time || 7;
                $('#finLeadTime, #productionLeadTime, #revLeadTime').val(leadTimeVal);

                // Precargar imagen principal existente
                @if(isset($editMode) && $editMode && isset($product) && $product->primary_image_url)
                setTimeout(function() {
                    const existingImg = '{{ $product->primary_image_url }}';
                    if (existingImg) {
                        $('#imgPreview').attr('src', existingImg);
                        $('#dropzonePlaceholder').addClass('d-none');
                        $('#dropzonePreview').removeClass('d-none').addClass('d-flex align-items-center justify-content-center');
                    }
                }, 100);
                @endif
            }

            // En modo EDIT o CLONE: precargar campos financieros que existen en document ready
            if ((isEditMode || isCloneMode) && State.financials) {
                const fin = State.financials;
                if (fin.labor_cost && fin.labor_cost > 0) {
                    $('#finLaborInput').val(parseFloat(fin.labor_cost).toFixed(2));
                }
                if (fin.margin && fin.margin > 0) {
                    $('#revProfitMargin, #finProfitMargin').val(fin.margin);
                }
                // Tiempo de producción desde financials
                if (State.financials.lead_time > 0) {
                    $('#revLeadTime').val(State.financials.lead_time);
                }
                // NOTA: #revFinalPrice se precarga en renderReview() cuando se entra al paso 6
            }

            // Renderizar datos precargados
            if (State.variants && State.variants.length > 0) {
                if (typeof renderVariantsTable === 'function') renderVariantsTable();
            }
            if (State.bom && State.bom.length > 0) {
                if (typeof renderBOMSplit === 'function') renderBOMSplit();
            }
            if (State.designs && State.designs.length > 0) {
                if (typeof renderDesignCards === 'function') renderDesignCards();
            }
            if (State.extras && State.extras.length > 0) {
                if (typeof renderExtrasTable === 'function') renderExtrasTable();
            }
            // Recalcular finanzas si hay datos
            if ((State.bom && State.bom.length > 0) || (State.designs && State.designs.length > 0) || (State.extras && State.extras.length > 0)) {
                if (typeof recalcFinance === 'function') recalcFinance();
            }

            // Listener para cambio de Familia de Material (Paso 3)
            // Usamos delegación de eventos porque el select se crea dinámicamente
            $(document).on('change', '#bomFamilySelector', function() {
                const familyId = $(this).val();
                fetchMaterialVariants(familyId);
            });

            // Listener para cambio de variante de material
            $('#bomMaterialSelector').on('select2:select', function(e) {
                const data = e.params.data;
                const element = $(data.element); // Get the original option element

                if (data.id) {
                    // FIX: Smart rounding (remove decimals if .00)
                    const stockVal = parseFloat(element.data('stock'));
                    const unit = element.data('unit') || 'unid';

                    // Format: 1,000.5 or 1,000 (no unnecessary zeros)
                    const formattedStock = new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 4
                    }).format(stockVal);

                    $('#matStock').text(`${formattedStock} ${unit}`);
                    $('#matCost').text('$' + parseFloat(element.data('cost')).toFixed(2));
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
                    vSel.append(`<option value="${v.temp_id}">${v.size} / ${v.color}</option>`);
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
                family_name: opt.data('family'),
                variant_name: opt.data('variant'),
                sku: opt.data('sku'),
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

        // --- DESIGN SEARCH & FILTERS (NEW FLAT UI) ---
        $(document).on('input', '#searchDesign', function() {
            filterDesignExports();
        });

        $(document).on('change', '#filterDesignBase, #filterAppType', function() {
            filterDesignExports();
        });

        // Master filter function for design exports (UI Legacy - Step 4 Grid)
        // NOTA: Esta función es para el grid estático del Paso 4 (si existe)
        function filterDesignExports() {
            // Validación defensiva: verificar que los elementos existan
            const $searchDesign = $('#searchDesign');
            const $filterDesignBase = $('#filterDesignBase');
            const $filterAppType = $('#filterAppType');
            const $designGrid = $('#designGrid .design-export-item');

            // Si no existen los elementos del UI legacy, salir silenciosamente
            if ($searchDesign.length === 0 && $filterDesignBase.length === 0 && $designGrid.length === 0) {
                return;
            }

            // Obtener valores con validación defensiva
            const searchTerm = String($searchDesign.val() || '').toLowerCase().trim();
            const designFilter = String($filterDesignBase.val() || '');
            const appTypeFilter = String($filterAppType.val() || '').toLowerCase();

            let visibleCount = 0;

            $designGrid.each(function() {
                const $item = $(this);
                const name = String($item.data('name') || '').toLowerCase();
                const label = String($item.data('label') || '').toLowerCase();
                const designId = String($item.data('design-id') || '');
                const appType = String($item.data('app-type') || '').toLowerCase();

                let show = true;

                // Search filter - search in name (design name) AND label (production label)
                if (searchTerm) {
                    const matchesName = name.includes(searchTerm);
                    const matchesLabel = label.includes(searchTerm);
                    if (!matchesName && !matchesLabel) {
                        show = false;
                    }
                }

                // Design base filter
                if (designFilter && designId !== designFilter) {
                    show = false;
                }

                // Application type filter - compare normalized values
                if (appTypeFilter && appType !== appTypeFilter) {
                    show = false;
                }

                if (show) {
                    $item.show();
                    visibleCount++;
                } else {
                    $item.hide();
                }
            });

            // Update counter (solo si existe)
            const $counter = $('#designExportCounter');
            if ($counter.length) {
                $counter.html(`Mostrando <strong>${visibleCount}</strong> producciones`);
            }
        }

        // Clear all filters (Legacy UI) - NO sobrescribir window.clearDesignFilters del modal nuevo
        // Esta función se mantiene para compatibilidad con el grid estático si existe
        function clearDesignFiltersLegacy() {
            $('#searchDesign').val('');
            $('#filterDesignBase').val('');
            $('#filterAppType').val('');
            filterDesignExports();
        }

        // View mode toggle (grid/list)
        window.setDesignViewMode = function(mode) {
            const $container = $('#designGrid');
            const $items = $container.find('.design-export-item');

            if (mode === 'list') {
                $items.removeClass('col-xl-2 col-lg-3 col-md-4 col-sm-6').addClass('col-12');
                $items.find('.design-card').addClass('flex-row').css('max-height', '100px');
                $items.find('.design-thumb').css({
                    'width': '100px',
                    'height': '100px',
                    'min-width': '100px'
                });
                $items.find('.card-body').addClass('text-left d-flex align-items-center justify-content-between w-100');
                $('#btnViewList').addClass('active');
                $('#btnViewGrid').removeClass('active');
            } else {
                $items.removeClass('col-12').addClass('col-xl-2 col-lg-3 col-md-4 col-sm-6');
                $items.find('.design-card').removeClass('flex-row').css('max-height', '');
                $items.find('.design-thumb').css({
                    'width': '',
                    'height': '100px',
                    'min-width': ''
                });
                $items.find('.card-body').removeClass(
                    'text-left d-flex align-items-center justify-content-between w-100');
                $('#btnViewGrid').addClass('active');
                $('#btnViewList').removeClass('active');
            }
        };

        // --- DESIGN ZOOM MODAL ---
        window.showDesignZoom = function(imageUrl, title) {
            if (!imageUrl || imageUrl === 'undefined' || imageUrl === '') {
                Swal.fire({
                    title: title || 'Diseño',
                    text: 'No hay imagen disponible para este diseño.',
                    icon: 'info',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            Swal.fire({
                title: title || 'Vista Ampliada',
                imageUrl: imageUrl,
                imageAlt: title || 'Diseño',
                width: 'auto',
                padding: '1rem',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    image: 'swal2-design-zoom-image'
                }
            });
        };

        // NOTE: loadTemplates() and initPlugins() are defined earlier in the file (around line 3094-3131)
        // Do not duplicate them here

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
            if (State.step === 3) {
                // BOM: Verificar presentaciones y inicializar selectores
                if (State.variants.length === 0) {
                    $('#bomBlockedState').removeClass('d-none');
                    $('#bomActiveState').addClass('d-none');
                } else {
                    $('#bomBlockedState').addClass('d-none');
                    $('#bomActiveState').removeClass('d-none');
                    initBomSelectors();
                    renderBOMSplit();
                }
            }
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
        // Updated for new flat UI structure using export_id
        function syncDesignCardsWithState() {
            // Reset all first - support both old and new card classes
            $('#designGrid .design-card, #designGrid .design-export-card').removeClass('selected');
            $('#designGrid .usage-status').addClass('d-none');

            // Iterate valid assignments in State
            State.designs.forEach(d => {
                let card;

                // NEW: Try to find by export_id first (new flat UI)
                if (d.export_id) {
                    card = $(
                        `.design-export-card[data-export-id="${d.export_id}"], .design-card[data-export-id="${d.export_id}"]`
                    );
                }

                // Fallback to design_id (old UI compatibility)
                if (!card || !card.length) {
                    card = $(
                        `.design-export-card[data-design-id="${d.id}"], .design-card[data-design-id="${d.id}"]`);
                }

                if (card.length) {
                    card.addClass('selected');
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
                const categoryId = $('#inpCategory').val();
                if (!name || !categoryId) {
                    Swal.fire('Falta información', 'Nombre y Categoría son obligatorios', 'warning');
                    return false;
                }
                State.definition = {
                    name,
                    sku: $('#inpSku').val() || '', // SKU is now optional
                    category_id: categoryId,
                    category: $('#inpCategory option:selected').text(),
                    desc: $('#inpDesc').val()
                };
            }
            if (step === 2) {
                // OBLIGATORIO: Al menos una presentación para continuar a Materiales
                if (State.variants.length === 0) {
                    Swal.fire({
                        title: 'Presentaciones requeridas',
                        html: 'Debes agregar al menos <strong>una presentación</strong> (talla y color) antes de continuar.<br><br>Los materiales se asignan a las presentaciones del producto.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
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
                // SIMPLIFICADO: Diseño es read-only, siempre permite continuar
                // El toggle se marca automáticamente si no hay diseños
                if (State.designs.length === 0) {
                    $('#toggleNoDesign').prop('checked', true);
                }
                return true;
            }
            return true;
        }

        // Feature: Toggle No Design Mode
        // Updated to disable all filter controls in new UI
        window.toggleNoDesignMode = function() {
            const isChecked = $('#toggleNoDesign').is(':checked');
            if (isChecked) {
                // CLEAR existing designs when switching to Liso mode
                if (State.designs.length > 0) {
                    State.designs = [];
                    // Deselect all cards visually
                    $('#designGrid .design-card, #designGrid .design-export-card').removeClass('selected');
                    recalcFinance();
                }

                $('#searchDesign').prop('disabled', true);
                $('#filterDesignBase').prop('disabled', true);
                $('#filterAppType').prop('disabled', true);
                $('#designGrid, #designExportsContainer').addClass('opacity-50');
                $('#designGrid .design-card, #designGrid .design-export-card').css('pointer-events', 'none');
            } else {
                $('#searchDesign').prop('disabled', false);
                $('#filterDesignBase').prop('disabled', false);
                $('#filterAppType').prop('disabled', false);
                $('#designGrid, #designExportsContainer').removeClass('opacity-50');
                $('#designGrid .design-card, #designGrid .design-export-card').css('pointer-events', 'auto');
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

        // (navigateToStep function is defined earlier in the stepper navigation section)
        // --- REVOLUTIONARY: Dynamic badges in stepper ---
        function updateStepperBadges() {
            // Badge for Step 2 (Presentaciones/Variants count)
            const variantCount = State.variants.length;
            if (variantCount > 0) {
                $('#badgeStep2').text(variantCount).removeClass('d-none');
            } else {
                $('#badgeStep2').addClass('d-none');
            }

            // Badge for Step 3 (Receta/BOM cost)
            const bomCost = State.bom.reduce((sum, m) => sum + (m.cost || 0), 0);
            if (State.bom.length > 0) {
                $('#badgeStep3').text('$' + bomCost.toFixed(0)).removeClass('d-none');
            } else {
                $('#badgeStep3').addClass('d-none');
            }

            // Badge for Step 4 (Diseño/Designs count)
            const designCount = State.designs.length;
            const isNoDesign = $('#toggleNoDesign').is(':checked');
            if (designCount > 0) {
                $('#badgeStep4').text(designCount).removeClass('d-none');
            } else if (isNoDesign) {
                $('#badgeStep4').text('Liso').removeClass('d-none');
            } else {
                $('#badgeStep4').addClass('d-none');
            }
        }

        // --- STEP 2: VARIANTS ---
        window.renderVariantsTable = function() {
            const tbody = $('#variantsTableBody');
            tbody.empty();

            if (State.variants.length === 0) {
                tbody.html(`<tr id="noVariantsRow">
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle mr-2"></i>Seleccione tallas y colores, luego presione Generar
                                </td>
                            </tr>`);
                $('#noVariantsRow').show();
                $('#variantCountBadge').text('0');
                $('#variantsTableContainer').addClass('d-none'); // Optional: hide container if empty
                return;
            }

            $('#noVariantsRow').hide();
            $('#variantsTableContainer').removeClass('d-none');

            State.variants.forEach(v => {
                tbody.append(`<tr data-id="${v.temp_id}">
                        <td>${v.size} (${v.color})</td>
                        <td class="font-weight-bold text-primary">${v.sku}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="removeVariant('${v.temp_id}', this)"><i class="fas fa-trash"></i></button></td>
                    </tr>`);
            });
            $('#variantCountBadge').text(State.variants.length);
        };

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
                        <td>${s.text} (${c.text})</td>
                        <td class="font-weight-bold text-primary">${sku}</td>
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
                /* Swal.fire({
                    icon: 'success',
                    title: '¡Generado!',
                    text: `${addedCount} variantes agregadas.`,
                    timer: 1500,
                    showConfirmButton: false
                }); */
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
            const url = '{{ route('admin.material-variants.conversiones', ['materialId' => ':id']) }}'.replace(':id',
                familyId);

            $.get(url, function(data) {
                const sel = $('#bomMaterialSelector');
                sel.empty().prop('disabled', false); // Removed dummy option for Multiple Select2 compatibility

                data.forEach(v => {
                    // Adaptar según la respuesta JSON real de tu controlador
                    // Asumiendo estructura: {id, text, cost_base, stock_real, symbol, family_name, variant_name, sku, stock_display}

                    // Solo mostrar el nombre de la variante (sin familia) en el select de Material Específico
                    let displayText = v.variant_name || v.text;

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
                            >${displayText}</option>`
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

            // Validate Scope
            if (!scope) {
                Swal.fire('Error', 'Seleccione ur alcance', 'warning');
                return;
            }

            // Validate Targets for Specific
            if (scope === 'specific') {
                targets = $('#targetVariantsSelect').val();
                if (!targets || !targets.length) {
                    Swal.fire('Error', 'Seleccione variantes destino', 'warning');
                    return;
                }
                targets.sort();
            }

            // --- SMART MERGE ALGORITHM ---
            const matId = tempMaterial.material_id;

            // CASE 1: GLOBAL ADDITION
            if (scope === 'global') {
                // Find existing global
                const existingGlobalIdx = State.bom.findIndex(m => m.material_id == matId && m.scope === 'global');

                // Remove ANY specific instances of this material (Global overrides specific)
                const specificsRemoved = State.bom.filter(m => m.material_id == matId && m.scope === 'specific').length;
                State.bom = State.bom.filter(m => !(m.material_id == matId && m.scope === 'specific'));

                if (existingGlobalIdx > -1) {
                    // Update existing Global
                    const existing = State.bom[existingGlobalIdx];
                    // Logic: If user adds global again, do we sum? Yes, usually implies adding more qty.
                    existing.qty = (parseFloat(existing.qty) * 100 + parseFloat(tempMaterial.qty) * 100) / 100;
                    existing.calculated_total = existing.cost * existing.qty;

                    Swal.fire('Info',
                        `Se actualizó la cantidad Global (Anteriores específicos removidos: ${specificsRemoved})`,
                        'info');
                } else {
                    // Create new Global
                    const costTotal = tempMaterial.cost * tempMaterial.qty;
                    State.bom.push({
                        ...tempMaterial,
                        scope: 'global',
                        targets: [],
                        calculated_total: costTotal
                    });
                    if (specificsRemoved > 0) {
                        Swal.fire('Info',
                            `Se aplicó Globalmente. (Se absorbieron ${specificsRemoved} asignaciones específicas)`,
                            'success');
                    } else {
                        Swal.fire('Agregado', 'Material agregado globalmente', 'success');
                    }
                }
            }

            // CASE 2: SPECIFIC ADDITION
            else {
                // Check if GLOBAL exists
                const hasGlobal = State.bom.some(m => m.material_id == matId && m.scope === 'global');
                if (hasGlobal) {
                    Swal.fire('Conflicto',
                        'Este material ya está asignado GLOBALMENTE. Elimínelo primero si desea asignarlo específicamente.',
                        'warning');
                    return;
                }

                // Clean Overlaps: If any existing specific entry targets the same variants, remove those targets from the old entry
                let overlapped = 0;
                State.bom.forEach((m, idx) => {
                    if (m.material_id == matId && m.scope === 'specific' && m.targets) {
                        // Find intersection
                        const intersection = m.targets.filter(t => targets.includes(t));
                        if (intersection.length > 0) {
                            // Remove intersection from OLD entry
                            m.targets = m.targets.filter(t => !intersection.includes(t));
                            overlapped += intersection.length;
                        }
                    }
                });

                // Cleanup: Remove entries that became empty
                State.bom = State.bom.filter(m => {
                    if (m.scope === 'specific' && (!m.targets || m.targets.length === 0)) return false;
                    return true;
                });

                // Now Add/Update the current request
                // Search for exact match of targets? Or just add new entry?
                // Logic: "New wins". Since we stripped overlaps, we can just add a new entry for these targets.
                // BUT, if there was an EXACT match previously (now empty), we might want to sum.
                // However, since we just stripped it, it's safer to just push a new entry.
                // Or: Check if there is an existing entry with EXACTLY these targets (that wasn't stripped)?

                // Simple approach: Always push new entry (the "Strip" phase ensures no duplicates).
                // Wait, if I add [A] Qty 1, then add [A] Qty 1 again? 
                // The strip phase would remove [A] from the first entry, making it empty. Then we add new [A] Qty 1.
                // This means valid Overwrite (Replace), not Sum.
                // The user said: "si ya esta se actualiza". Update implies SUM or REPLACE?
                // In BOM, usually you correct the value. So REPLACE is safer than obscure summing.

                const costTotal = tempMaterial.cost * tempMaterial.qty;
                State.bom.push({
                    ...tempMaterial,
                    scope: 'specific',
                    targets: targets,
                    calculated_total: costTotal
                });

                if (overlapped > 0) {
                    Swal.fire('Actualizado', `Se reasignaron ${overlapped} variantes a esta nueva regla.`, 'success');
                } else {
                    Swal.fire('Agregado', 'Material agregado a variantes seleccionadas', 'success');
                }
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
                            return v ? `${v.size} (${v.color})` : '';
                        }).filter(Boolean).join(', ');

                        variantInfo =
                            `<div class="font-weight-bold text-dark" style="font-size: 0.95em;">${targetNames}</div>`;
                    } else {
                        variantInfo =
                            '<span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Sin asignación</span>';
                    }
                }

                tbody.append(`<tr>
                <td class="align-middle border-right">
                    <div class="font-weight-bold text-dark" style="font-size: 1rem;">
                        ${m.family_name || m.name} ${m.variant_name ? `- ${m.variant_name}` : ''}
                    </div>
                    <div class="text-muted text-uppercase" style="font-size: 0.9rem;">
                        ${m.sku || 'N/A'}
                    </div>
                    ${m.is_primary ? '<span class="badge badge-warning text-white mt-1"><i class="fas fa-star text-white"></i> Principal</span>' : ''}
                </td>
                <td class="align-middle border-right" style="max-width: 300px;">
                    ${variantInfo}
                </td>
                <td class="align-middle text-center border-right" style="width: 150px;">
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control text-center font-weight-bold" 
                               value="${m.qty}" step="0.01" min="0.01" 
                               onchange="updateBomQty(${idx}, this.value)"
                               style="font-size: 1rem;">
                        <div class="input-group-append">
                            <span class="input-group-text small">${m.unit}</span>
                        </div>
                    </div>
                </td>
                <td class="align-middle text-center border-right">${badge}</td>
                <td class="align-middle font-weight-bold text-center border-right" style="font-size: 1.1rem;">$${m.calculated_total.toFixed(2)}</td>
                <td class="align-middle text-center"><button class="btn btn-sm btn-outline-danger btn-icon rounded-circle" onclick="removeBOM(${idx})"><i class="fas fa-trash"></i></button></td>
            </tr>`);
            });
            State.financials.material_cost = total;
            $('#bomTotalCostBadge').text(`Total: $${total.toFixed(2)}`);
        }

        window.removeBOM = function(idx) {
            State.bom.splice(idx, 1);
            renderBOM();
            recalcFinance();
        };

        window.updateBomQty = function(idx, newQty) {
            const qty = parseFloat(newQty);
            if (qty > 0 && State.bom[idx]) {
                State.bom[idx].qty = qty;
                State.bom[idx].calculated_total = (State.bom[idx].cost * qty);
                renderBOM(); // Re-render to update cost column
                recalcFinance();
            }
        };

        // --- STEP 4: DESIGNS (UPDATED with Modal Config) ---
        let tempDesign = null; // Temporary storage for design being configured

        // NEW: Select Design Export (flat structure) - Opens config modal
        window.selectDesignExport = function(el, exportData) {
            // LISO MODE CHECK: Block selection if product is marked as "Liso"
            if ($('#toggleNoDesign').is(':checked')) {
                Swal.fire({
                    title: 'Modo Liso Activo',
                    text: 'Este producto está marcado como LISO (sin bordado). Desactiva el toggle para agregar diseños.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            const card = $(el);
            const appType = exportData.application_type || 'general';
            const appLabel = exportData.application_label || 'Sin etiqueta';

            // Build origin text
            let originText = exportData.design_name;
            if (exportData.variant_name) {
                originText += ' / ' + exportData.variant_name;
            }

            // DEFINITIVE IMAGE CAPTURE V5: Get from DOM (now uses production.preview route)
            let capturedImageUrl = '';

            // Get the img src from the clicked card (production.preview route)
            const anyImg = card.find('img').first();
            if (anyImg.length > 0 && anyImg.attr('src')) {
                capturedImageUrl = anyImg.attr('src');
            }

            // Fallback: Construct preview URL from export ID
            if (!capturedImageUrl && exportData.id) {
                capturedImageUrl = "{{ route('admin.production.preview', ['export' => '__ID__']) }}".replace('__ID__',
                    exportData.id);
            }

            // Store for modal use
            tempDesign = {
                id: exportData.design_id,
                export_id: exportData.id,
                name: appLabel, // Use label as the name
                stitches: exportData.stitches || 0,
                imageUrl: capturedImageUrl,
                element: el,
                variant_id: exportData.variant_id,
                variant_name: exportData.variant_name,
                dimensions: exportData.dimensions_label,
                colors: exportData.colors,
                variants: [],
                // NEW: Store application type info for direct add
                application_type: appType,
                application_label: appLabel,
                design_name: exportData.design_name,
                origin: originText
            };

            // Populate modal - NEW STRUCTURE
            $('#designModalLabel').text(appLabel.toUpperCase()); // Title = Label name in UPPERCASE
            $('#designModalOrigin').html(`<i class="fas fa-sitemap mr-1"></i>${originText}`); // Subtitle = Origin

            // Format position name: replace underscores/hyphens with spaces and capitalize
            const positionFormatted = appType.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            $('#designModalPosition').html(`<i class="fas fa-map-marker-alt mr-2"></i>${positionFormatted}`);

            // Technical data
            $('#designModalStitches').text(exportData.stitches_formatted || '0');
            $('#designModalDimensions').text(exportData.dimensions_label || '-');
            $('#designModalColors').text((exportData.colors || 0) + ' col');

            // Image
            if (tempDesign.imageUrl) {
                $('#designModalImage').attr('src', tempDesign.imageUrl).show();
            } else {
                $('#designModalImage').hide();
            }

            // Show configured positions
            showConfiguredPositions(exportData.design_id, exportData.id);

            // Reset modal state
            selectDesignScope('global');
            $('#justAddedFeedback').addClass('d-none');

            // Populate product variants selector
            const vSel = $('#designTargetVariants');
            vSel.empty();
            State.variants.forEach(v => {
                // User request: Show only variant name (Size / Color) without parentheses or SKU
                vSel.append(`<option value="${v.temp_id}">${v.size} (${v.color})</option>`);
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

        // NEW: Confirm add design directly (position already defined from production)
        window.confirmAddDesignDirect = function() {
            if (!tempDesign) return;

            const scope = $('#designScopeValue').val();
            let targets = [];

            if (scope === 'specific') {
                targets = $('#designTargetVariants').val() || [];
                if (targets.length === 0) {
                    Swal.fire('Error', 'Seleccione al menos una variante', 'warning');
                    return;
                }
                targets.sort();
            }

            // Use application_type as position (already defined in production)
            // Format: replace underscores/hyphens with spaces and capitalize each word
            const positionName = tempDesign.application_type ?
                tempDesign.application_type.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) :
                'General';
            const positionSlug = tempDesign.application_type || 'general';

            // Check for duplicate
            const existingIndex = State.designs.findIndex(d => {
                if (d.export_id !== tempDesign.export_id) return false;
                if (d.scope !== scope) return false;
                const dTargets = (d.targets || []).sort().join(',');
                const newTargets = targets.join(',');
                return dTargets === newTargets;
            });

            if (existingIndex > -1) {
                Swal.fire('Atención', 'Esta producción ya está configurada con el mismo alcance', 'info');
                return;
            }

            // Add design to state
            State.designs.push({
                id: tempDesign.id,
                export_id: tempDesign.export_id,
                name: tempDesign.name,
                stitches: tempDesign.stitches,
                position_id: null, // No position ID needed - using application_type
                position_name: positionName,
                position_slug: positionSlug,
                scope,
                targets,
                variant_id: tempDesign.variant_id || null,
                variant_name: tempDesign.variant_name || null,
                dimensions: tempDesign.dimensions || null,
                design_name: tempDesign.design_name,
                application_type: tempDesign.application_type,
                image_url: tempDesign.imageUrl // Fix: Add image URL for preview
            });

            // Mark card as selected
            $(tempDesign.element).addClass('selected');

            recalcFinance();

            // Show inline feedback
            $('#justAddedText').text(`Agregado: ${tempDesign.name} → ${positionName}`);
            $('#justAddedFeedback').removeClass('d-none');

            // Update the configured positions list
            showConfiguredPositions(tempDesign.id, tempDesign.export_id);
        };

        // OLD: Toggle Design (for backup/old UI compatibility)
        window.toggleDesign = function(el, id, name, stitches, imageUrl, dimensions, colors, variants = []) {
            const card = $(el);
            const appType = card.data('app-type') || ''; // Capture Application Type

            // ROBUST IMAGE CAPTURE V2: Scan all images in card
            let robustImage = imageUrl || '';
            let foundBestImage = null;

            // 1. Prioritize image that is NOT placeholder and has substantial length
            card.find('img').each(function() {
                const src = $(this).attr('src');
                if (src && src.length > 15 && !src.includes('placeholder') && !src.includes('data:image')) {
                    foundBestImage = src;
                    return false; // Break loop
                }
            });

            // 2. If found better image, use it.
            if (foundBestImage) {
                robustImage = foundBestImage;
            } else if (!robustImage || robustImage.includes('placeholder')) {
                // FALLBACK 1: Try img src (any)
                const anyImg = card.find('img').attr('src');
                if (anyImg) robustImage = anyImg;

                // FALLBACK 2: Check for background-image (common in galleries)
                if (!robustImage || robustImage.includes('placeholder')) {
                    const bgDiv = card.find('[style*="background-image"]');
                    if (bgDiv.length > 0) {
                        const style = bgDiv.css('background-image');
                        // Extract url(...) content: url("...") or url('...')
                        if (style && style.includes('url')) {
                            const match = style.match(/url\(["']?([^"']*)["']?\)/);
                            if (match && match[1]) robustImage = match[1];
                        }
                    }
                }
            }

            // ALWAYS open config modal - whether already selected or not
            // User removes positions from INSIDE the modal using X buttons
            tempDesign = {
                id,
                name,
                stitches: stitches || 0,
                imageUrl: robustImage, // Use robust image
                element: el,
                variants: variants // Store loaded variants
            };

            // Populate modal basics
            $('#designModalName').text(name);
            $('#designModalStitches').text(stitches ? stitches.toLocaleString() : 0);
            $('#designModalDimensions').text(dimensions || '-');
            $('#designModalColors').text(colors ? colors + ' col' : '0 col');

            // Show App Type Badge
            if (appType) {
                $('#designModalAppType').text(
                    `Lugar de aplicación: ${appType.charAt(0).toUpperCase() + appType.slice(1)}`).removeClass(
                    'd-none');
            } else {
                $('#designModalAppType').addClass('d-none');
            }

            // Primary Image Logic (Default to parent, override if variant selected logic later)
            if (robustImage) {
                $('#designModalImage').attr('src', robustImage).show();
            } else {
                $('#designModalImage').hide();
            }

            // --- VARIANT SELECTOR LOGIC ---
            const variantSelect = $('#designVariantSelect');
            const variantContainer = $('#designVariantContainer');

            variantSelect.empty();

            if (variants && variants.length > 0) {
                variantSelect.append('<option value="">Seleccione Versión...</option>');
                variants.forEach(v => {
                    let vImg = v.primary_image ? "{{ asset('storage') }}/" + v.primary_image.url : imageUrl;
                    variantSelect.append(
                        `<option value="${v.id}" data-image="${vImg}" data-code="${v.sku}">${v.name}</option>`
                    );
                });
                variantContainer.removeClass('d-none');
            } else {
                variantContainer.addClass('d-none');
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

            // AUTO-SELECT POSITION based on App Type (Smart Match)
            if (appType) {
                // Try to find an option that matches the App Type
                // We iterate options to match text content roughly
                let foundVal = null;
                $('#designPositionSelect option').each(function() {
                    const optText = $(this).text().toLowerCase();
                    // Loose matching: if option text contains the app type (e.g. "Pecho" in "Pecho Izquierdo")
                    // OR if app type contains the option text
                    if (optText.includes(appType) || appType.includes(optText)) {
                        foundVal = $(this).val();
                        return false; // Break loop
                    }
                });

                if (foundVal) {
                    $('#designPositionSelect').val(foundVal).trigger('change');
                }
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
        // Updated to support filtering by export_id for flat UI
        function showConfiguredPositions(designId, exportId = null) {
            let configured = State.designs.filter(d => d.id === designId);

            // If exportId is provided, also filter by it
            if (exportId) {
                configured = configured.filter(d => d.export_id === exportId || !d.export_id);
            }
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
                            return v ? `${v.size} (${v.color})` : '';
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
            updateDesignCardVisual(designId, exportId);
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
        // Updated for new flat UI with export_id support
        function updateDesignCardVisual(designId, exportId = null) {
            let configs = State.designs.filter(d => d.id === designId);

            // If exportId provided, further filter
            if (exportId) {
                configs = configs.filter(d => d.export_id === exportId);
            }

            const count = configs.length;

            // Find card - try export_id first, then design_id (support both card classes)
            let card;
            if (exportId) {
                card = $(`.design-export-card[data-export-id="${exportId}"], .design-card[data-export-id="${exportId}"]`);
            }
            if (!card || !card.length) {
                card = $(`.design-export-card[data-design-id="${designId}"], .design-card[data-design-id="${designId}"]`);
            }

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
                export_id: tempDesign.export_id || null, // NEW: Store export_id for flat UI
                name: tempDesign.name,
                stitches: tempDesign.stitches,
                position_id: positionId,
                position_name: positionName,
                position_slug: positionSlug,
                scope,
                targets,
                variant_id: tempDesign.variant_id || null, // Store if from variant
                variant_name: tempDesign.variant_name || null,
                dimensions: tempDesign.dimensions || null // Store dimensions for reference
            });

            // Mark card as selected
            $(tempDesign.element).addClass('selected');

            // Reset position select for another addition
            $('#designPositionSelect').val(null).trigger('change');
            recalcFinance();

            // Show inline feedback
            $('#justAddedText').text(`Agregado en: ${positionName.trim()}`);
            $('#justAddedFeedback').removeClass('d-none');

            // Update the configured positions list (pass export_id if available)
            showConfiguredPositions(tempDesign.id, tempDesign.export_id);
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
                price: parseFloat(sel.data('price')),
                time: parseInt(sel.data('time')) || 0
            });
            renderExtrasTable();
            recalcFinance();
            $('#extrasSelector').val(null).trigger('change');
        };

        function renderExtrasTable() {
            const tbody = $('#extrasTableBody');
            tbody.empty();
            let total = 0;
            let totalTime = 0;

            if (State.extras.length === 0) {
                tbody.append(`<tr id="noExtrasRow">
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No hay servicios agregados
                    </td>
                </tr>`);
            } else {
                State.extras.forEach((e, idx) => {
                    total += e.price;
                    totalTime += (e.time || 0);
                    tbody.append(`<tr>
                        <td class="font-weight-bold">${e.name}</td>
                        <td class="text-right">$${e.price.toFixed(2)}</td>
                        <td class="text-center text-muted">${e.time || 0} min</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExtra(${idx})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`);
                });
            }

            State.financials.extras_cost = total;
            $('#extrasTotalDisplay').text('$' + total.toFixed(2));
            $('#extrasTotalTime').text(totalTime + ' min');
            $('#extrasCountBadge').text(State.extras.length);
        }

        // Global flag moved to top of script


        // Function to render design previews in Step 6 (called only when entering the step)
        window.renderStep6Designs = function() {
            const designPreviews = $('#finDesignPreviews');
            designPreviews.empty();

            if (!State.designs || State.designs.length === 0) {
                designPreviews.html('<div class="text-muted small">Sin diseños seleccionados...</div>');
                return;
            }

            State.designs.forEach(d => {
                // Tipo de aplicación (ubicación) - usar nombre correcto de propiedad
                const appLabel = d.app_type_name || d.position_name || 'General';

                // Preview: Prioridad 1) SVG, 2) imagen, 3) placeholder
                let thumbContent;
                if (d.svg_content) {
                    // SVG inline del archivo de bordado
                    thumbContent = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; overflow:hidden;">${d.svg_content}</div>`;
                } else if (d.image || d.image_url) {
                    const imgSrc = d.image || d.image_url;
                    thumbContent = `
                        <img src="${imgSrc}" style="width:100%; height:100%; object-fit:contain;"
                            onerror="this.parentNode.innerHTML='<div class=\\'d-flex flex-column align-items-center justify-content-center h-100 bg-secondary text-white\\'><i class=\\'fas fa-file-code\\'></i><small>${d.file_format || 'EMB'}</small></div>'">
                    `;
                } else {
                    // Placeholder con formato de archivo
                    const fmt = (d.file_format || 'EMB').toUpperCase();
                    thumbContent = `
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 bg-secondary text-white">
                            <i class="fas fa-file-code"></i>
                            <small style="font-size:0.6rem;">${fmt}</small>
                        </div>
                    `;
                }

                const safeName = (d.name || 'Producción').replace(/'/g, "\\'");

                designPreviews.append(`
                    <div class="d-flex flex-column align-items-center text-center mr-2 mb-2" style="width: 120px;" title="${d.name || 'Producción'}">
                        <div style="width:75px; height:75px; border-radius:10px; overflow:hidden; border:2px solid #dee2e6; cursor:pointer; background:#fff; display:flex; align-items:center; justify-content:center;">
                            ${thumbContent}
                        </div>
                        <div class="mt-2 w-100">
                            <div style="font-size:0.95rem; color:#1a252f; font-weight:700; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${d.name || 'Producción'}</div>
                            <div class="badge w-100 text-truncate mt-1 text-white border-0" style="font-size:0.85rem; background:#117a8b; font-weight:600;">${appLabel}</div>
                        </div>
                    </div>
                `);
            });
        };
        window.removeExtra = function(idx) {
            State.extras.splice(idx, 1);
            renderExtrasTable();
            recalcFinance();
        };

        window.recalcFinance = function() {
            // 1. MATERIAL COST - Costo REAL de producción
            // TODOS los materiales suman (comunes + específicos)
            // Son materiales físicos que se compran para entregar al cliente
            let matCost = 0;
            const matList = $('#finMaterialsList');

            if (matList.length === 0) {
                console.error('CRITICAL: #finMaterialsList Not Found!');
            } else {
                matList.empty();
            }

            if (State.bom && State.bom.length > 0) {
                let html = '';

                // TODOS los materiales suman al costo
                State.bom.forEach(m => {
                    let subTotal = m.calculated_total;
                    if (subTotal === undefined || subTotal === null) {
                        subTotal = (parseFloat(m.cost) || 0) * (parseFloat(m.qty) || 0);
                    }
                    matCost += subTotal;

                    let matName = m.name || (m.family_name ? `${m.family_name} - ${m.variant_name}` : 'Material');

                    // Badge según scope (solo visual, ambos suman)
                    let scopeBadge = m.scope === 'global'
                        ? `<span class="badge badge-dark custom-scope-badge mr-1" style="font-size: 0.8rem;">Base</span>`
                        : `<span class="badge badge-info custom-scope-badge mr-1" style="font-size: 0.8rem;">Específico</span>`;

                    // Info de presentaciones para materiales específicos
                    let targetInfo = '';
                    if (m.scope === 'specific' && m.targets && m.targets.length > 0) {
                        const targetNames = m.targets.map(tid => {
                            const v = State.variants.find(va => va.temp_id === tid);
                            return v ? `${v.size}-${v.color}` : tid;
                        }).join(', ');
                        targetInfo = `<br><small class="text-info"><i class="fas fa-tag mr-1"></i>${targetNames}</small>`;
                    }

                    html += `<div class="calc-mat-item border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="flex:1;">
                                <div class="d-flex align-items-center mb-1">
                                    ${scopeBadge}
                                    <div class="font-weight-bold text-dark" style="font-size: 0.95rem; line-height:1.1;">${matName}</div>
                                </div>
                                <div class="text-muted" style="font-size: 0.85rem;">
                                    <i class="fas fa-ruler-combined mr-1"></i>${m.qty} ${m.unit || 'unid'} × $${(parseFloat(m.cost) || 0).toFixed(2)}
                                    ${targetInfo}
                                </div>
                            </div>
                            <div class="font-weight-bold text-primary pl-2" style="font-size: 1rem;">$${subTotal.toFixed(2)}</div>
                        </div>
                    </div>`;
                });

                if (matList.length > 0) matList.html(html);
            } else {
                if (matList.length > 0) matList.html(
                    '<div class="text-muted small text-center py-4">No hay materiales seleccionados.</div>');
            }

            // Update Totals
            $('#finMatCost').text('$' + matCost.toFixed(2));
            $('#revMatCost').text('$' + matCost.toFixed(2));


            // Calculate total stitches for finance
            let totalStitches = 0;
            State.designs.forEach(d => {
                totalStitches += parseInt(d.stitches) || 0;
            });

            // Update design previews:
            // 1. If flag is set (explicit update request)
            // 2. OR if we have designs but no images are shown (safety fallback)
            const previewsContainer = $('#finDesignPreviews');
            const hasImages = previewsContainer.find('img').length > 0;

            if (shouldRenderDesigns || (State.designs.length > 0 && !hasImages)) {
                renderStep6Designs();
                shouldRenderDesigns = false; // Reset flag
            }

            $('#finTotalStitches').text(totalStitches.toLocaleString());
            const millares = totalStitches / 1000;
            $('#finMillares').text(millares.toFixed(3));

            const rate = parseFloat($('#finStitchRate').val()) || 0;
            const embCost = millares * rate;

            $('#finEmbCost').text('$' + embCost.toFixed(2));
            $('#revEmbCost').text('$' + embCost.toFixed(2));


            // 3. LABOR COST
            const laborCost = parseFloat($('#finLaborInput').val()) || 0;
            $('#finLaborCostDisplay').text('$' + laborCost.toFixed(2));
            $('#revLaborCost').text('$' + laborCost.toFixed(2));


            // 4. EXTRAS COST & TIME
            let extrasCost = 0;
            let extrasTime = 0;
            const extrasList = $('#finExtrasList');
            extrasList.empty();

            if (State.extras && State.extras.length > 0) {
                let exHtml = '';
                // FIXED LOOP VARIABLE
                State.extras.forEach(ex => {
                    const thisCost = parseFloat(ex.cost || ex.price || 0);
                    extrasCost += thisCost;
                    extrasTime += parseInt(ex.time || 0);

                    exHtml += `
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2" style="font-size:0.95rem;">
                            <span class="font-weight-bold text-dark">${ex.name}</span>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-dark mr-2 p-2" style="min-width:50px;">${ex.time || 0}m</span>
                                <span class="font-weight-bold text-dark" style="font-size:1rem;">$${thisCost.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                });
                extrasList.html(exHtml);
            } else {
                extrasList.html('<span class="text-muted small">Sin servicios extras.</span>');
            }

            $('#finExtrasTotal').text('$' + extrasCost.toFixed(2));
            $('#revExtraCost').text('$' + extrasCost.toFixed(2));
            $('#finExtrasTime').text(extrasTime + ' min');
            $('#revExtrasTime').text(extrasTime + ' min');


            // 5. TIMES
            const machineSpeed = parseInt($('#finMachineSpeed').val()) || 800;
            const embTimeMinutes = machineSpeed > 0 ? Math.ceil(totalStitches / machineSpeed) : 0;
            $('#finEmbroideryTime').text(embTimeMinutes + ' min');
            $('#revEmbTime').text(embTimeMinutes + ' min');

            const totalProcessTime = embTimeMinutes + extrasTime;
            let timeStr = totalProcessTime + ' min';
            if (totalProcessTime > 60) {
                const h = Math.floor(totalProcessTime / 60);
                const m = totalProcessTime % 60;
                timeStr = `${h}h ${m}m`;
            }
            $('#revTotalTime').text(timeStr);


            // 6. TOTALS
            const totalCost = matCost + embCost + laborCost + extrasCost;
            $('#revTotalCost').text('$' + totalCost.toFixed(2));

            const leadTime = parseInt($('#revLeadTime').val()) || State.financials.lead_time || 1;

            // CRÍTICO: Preservar price y margin existentes al recalcular
            const existingPrice = State.financials.price || 0;
            const existingMargin = State.financials.margin || 35;

            State.financials = {
                material_cost: matCost,
                embroidery_cost: embCost,
                labor_cost: laborCost,
                extras_cost: extrasCost,
                total_cost: totalCost,
                lead_time: leadTime,
                price: existingPrice,
                margin: existingMargin
            };

            recalcReviewPrice();
        };

        // --- STEP 6: REVIEW (PREMIUM CLEAN) ---
        window.renderReview = function() {
            const f = State.financials;

            // Product Identity
            $('#revProductName').text(State.definition.name || '-');
            $('#revProductSku').text(State.definition.sku || '-');
            $('#revProductCategory').text(State.definition.category || '-');

            // Variants - Vertical List with Gray Outline
            $('#revVarCount').text(State.variants.length);
            if (State.variants.length > 0) {
                let varHtml = '';
                State.variants.forEach(v => {
                    varHtml += `<div class="review-variant-item">
                        <span class="review-variant-text">${v.size} (${v.color})</span>
                        <span class="review-variant-sku">SKU: ${v.sku_variant || v.sku}</span>
                    </div>`;
                });
                $('#revVariants').html(varHtml);
            } else {
                $('#revVariants').html('<span class="text-muted">Sin variantes</span>');
            }

            // Materials - Clean compact list (Less separation)
            $('#revBomCount').text(State.bom.length);
            if (State.bom.length > 0) {
                let bomHtml = '';
                State.bom.forEach(m => {
                    const name = m.family_name || m.name;
                    const variant = m.variant_name ? ` - ${m.variant_name}` : '';
                    bomHtml += `<div class="review-item-row">
                        <div class="d-flex align-items-center">
                            <span class="review-item-name mr-2">${name}${variant}</span>
                        </div>
                        <span class="review-item-qty">${m.qty} ${m.unit}</span>
                    </div>`;
                });
                $('#revBomList').html(bomHtml);
            } else {
                $('#revBomList').html('<span class="text-muted">Sin materiales</span>');
            }

            // Bordados - Clean compact list  
            const isNoDesign = $('#toggleNoDesign').is(':checked');
            $('#revEmbCount').text(State.designs.length);

            if (isNoDesign) {
                $('#revDesignsList').html('<span class="text-muted">Producto liso</span>');
            } else if (State.designs.length > 0) {
                let designsHtml = '';
                State.designs.forEach(d => {
                    const stitches = d.stitches || 0;
                    const millares = (stitches / 1000).toFixed(2);
                    designsHtml += `<div class="review-design-item">
                        <div class="review-design-name">${d.name}</div>
                        <div class="review-design-info">
                            <span>${d.position_name || 'Sin posición'}</span>
                            <span class="text-success font-weight-bold">${stitches.toLocaleString()} pts (${millares} millares)</span>
                        </div>
                    </div>`;
                });
                $('#revDesignsList').html(designsHtml);
            } else {
                $('#revDesignsList').html('<span class="text-muted">Sin bordados</span>');
            }

            // Extras - Grid Layout (2 cols) - Inline Format
            $('#revExtraCount').text(State.extras.length);
            if (State.extras.length > 0) {
                let extrasHtml = '';
                State.extras.forEach(e => {
                    extrasHtml += `<div class="review-extra-item">
                        <span class="review-extra-inline">${e.name} - $${e.price.toFixed(2)}</span>
                    </div>`;
                });
                // Add class for grid layout to the container if not exists
                $('#revExtrasList').addClass('review-extras-grid').html(extrasHtml);
                $('#revExtrasContainer').show();
            } else {
                $('#revExtrasContainer').hide();
            }

            // Financial Summary
            $('#revMatCost').text(`$${f.material_cost.toFixed(2)}`);
            $('#revEmbCost').text(`$${f.embroidery_cost.toFixed(2)}`);
            $('#revLaborCost').text(`$${f.labor_cost.toFixed(2)}`);
            $('#revExtraCost').text(`$${f.extras_cost.toFixed(2)}`);
            $('#revTotalCost').text(`$${f.total_cost.toFixed(2)}`);

            // Initialize pricing controls
            $('#revMarginInput').val(f.margin || 35);

            // CRÍTICO: Precargar precio personalizado ANTES de recalcular
            if (f.price > 0) {
                $('#revFinalPrice').val(f.price.toFixed(2));
            }

            recalcReviewPrice();
        };

        // Recalculate price in Step 6 review
        window.recalcReviewPrice = function() {
            const f = State.financials;
            const margin = parseFloat($('#revMarginInput').val()) || 0;
            f.margin = margin;

            let suggestedPrice = 0;
            if (margin < 100) {
                suggestedPrice = f.total_cost / (1 - (margin / 100));
            }
            f.suggested_price = suggestedPrice;

            $('#revSuggestedPrice').text(`$${suggestedPrice.toFixed(2)}`);
            $('#revFinalPrice').attr('placeholder', suggestedPrice.toFixed(2));

            // CRÍTICO: NO sobreescribir precio personalizado si ya existe
            // Solo establecer si no hay precio o es el primer cálculo
            const currentInputPrice = parseFloat($('#revFinalPrice').val()) || 0;
            if (currentInputPrice <= 0 && f.price <= 0) {
                f.price = suggestedPrice;
            }
        };

        // Update header when manual price is entered
        window.updateHeaderPrice = function() {
            const customPrice = parseFloat($('#revFinalPrice').val());
            if (customPrice > 0) {
                State.financials.price = customPrice;
            }
        };

        // Design Zoom Modal
        window.showDesignZoom = function(imageUrl, designName) {
            Swal.fire({
                title: designName,
                imageUrl: imageUrl,
                imageWidth: 400,
                imageHeight: 400,
                imageAlt: designName,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    image: 'design-zoom-image'
                }
            });
        };

        // --- SUBMIT ---
        window.submitForm = async function() {
            // ============================================================
            // VALIDACIONES CRÍTICAS ANTES DE GUARDAR
            // ============================================================

            // 1. Tiempo de Producción (obligatorio > 0)
            const leadTime = parseInt($('#revLeadTime').val()) || parseInt(State.financials.lead_time) || 0;
            if (leadTime < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tiempo de Producción Requerido',
                    text: 'Debe especificar el tiempo de producción (mínimo 1 día).',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // 2. Mano de Obra (obligatorio > 0)
            const laborCost = parseFloat($('#finLaborInput').val()) || parseFloat(State.financials.labor_cost) || 0;
            if (laborCost <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mano de Obra Requerida',
                    text: 'Debe especificar el costo de mano de obra (debe ser mayor a $0).',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // 3. Precio Final (obligatorio > 0)
            // CRÍTICO: Sincronizar valor del input ANTES de validar
            const inputFinalPrice = parseFloat($('#revFinalPrice').val()) || 0;
            if (inputFinalPrice > 0) {
                State.financials.price = inputFinalPrice;
            }
            const finalPrice = parseFloat(State.financials.price) || 0;
            if (finalPrice <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Precio Final Requerido',
                    text: 'Debe definir un precio final válido (debe ser mayor a $0).',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Sincronizar valores validados al State
            State.financials.lead_time = leadTime;
            State.financials.labor_cost = laborCost;
            State.financials.price = finalPrice;

            // Enterprise Concurrency Control: JIT Validation
            const actionText = isEditMode ? '¿Actualizar Producto?' : '¿Crear Producto?';
            Swal.fire({
                title: actionText,
                text: `Precio Final: $${finalPrice.toFixed(2)}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Guardar Producto'
            }).then(async (res) => {
                if (res.isConfirmed) {
                    // 1. Show Blocking Loader
                    Swal.fire({
                        title: 'Verificando precios...',
                        text: 'Asegurando consistencia de datos...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    try {
                        // 2. Perform Pre-flight Check
                        const check = await checkMaterialPrices();

                        if (check.has_changes) {
                            // 3. CONFLICT DETECTED: Abort and Show UI
                            Swal.close();
                            validateMaterialPrices(); // Re-use the existing UI handler
                            return; // STOP SUBMISSION
                        }

                        // 4. NO CONFLICT: Proceed to Submit
                        $('#h_variants').val(JSON.stringify(State.variants));
                        $('#h_materials').val(JSON.stringify(State.bom));
                        $('#h_embroideries').val(JSON.stringify(State.designs));
                        $('#h_extras').val(JSON.stringify(State.extras));
                        $('#h_financials').val(JSON.stringify(State.financials));

                        $('#productForm').submit();

                    } catch (e) {
                        Swal.close();
                        Swal.fire('Error',
                            'No se pudo verificar la consistencia de precios. Intente nuevamente.',
                            'error');
                        console.error(e);
                    }
                }
            });
        };


        // SEARCH FUNCTIONALITY FOR DESIGNS (UPDATED for new flat UI)
        // Note: Main search handler is now in filterDesignExports() function
        // This is kept for backward compatibility but delegates to the new function
        $(document).off('input', '#searchDesign').on('input', '#searchDesign', function() {
            // Call the new filter function which handles both old and new UI
            if (typeof filterDesignExports === 'function') {
                filterDesignExports();
            } else {
                // Fallback for old UI - con protección defensiva
                const term = String($(this).val() || '').toLowerCase();
                $('#designGrid .design-export-item, #designGrid .col-md-3').each(function() {
                    const card = $(this).find('.design-card');
                    const name = String($(this).data('name') || card.find('h5, h6').text() || '').toLowerCase();
                    const appType = String($(this).data('app-type') || card.data('app-type') || '').toLowerCase();

                    if (name.includes(term) || appType.includes(term) || term === '') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // SELECT2 CUSTOM FORMATTING FOR VARIANTS (Inserted Logic)
        $(function() {
            function formatDesignVariant(state) {
                if (!state.id) {
                    return state.text;
                }
                const img = $(state.element).data('image');
                const code = $(state.element).data('code') || '';

                if (!img) return state.text;

                const $state = $(
                    `<span class="d-flex align-items-center">
                                <img src="${img}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; margin-right: 10px;" />
                                <span>
                                    <div class="font-weight-bold" style="line-height: 1;">${state.text}</div>
                                    <small class="text-muted">${code}</small>
                                </span>
                            </span>`
                );
                return $state;
            }

            // Global Init
            $('.select2-design-variant').select2({
                dropdownParent: $('#designConfigModal'),
                width: '100%',
                templateResult: formatDesignVariant,
                templateSelection: formatDesignVariant,
                minimumResultsForSearch: Infinity
            });

            // --- RESTORE UI FROM STATE (After Validation Error) ---
            if (State.variants.length > 0) renderVariantsTable();
            if (State.bom.length > 0) renderBOM();
            if (State.designs.length > 0) renderDesignCards();
            if (State.extras.length > 0) renderExtrasTable();

            // Always recalc finance if we have data
            if (State.financials.total_cost > 0 || State.financials.price > 0) {
                // Restore UI values from State
                $('#revLeadTime').val(State.financials.lead_time || 1);
                $('#revMarginInput').val(State.financials.margin || 35);
                $('#revFinalPrice').val(State.financials.price || 0);

                recalcFinance();
            }

            // Re-bind on modal event to ensure width is correct
            $(document).on('shown.bs.modal', '#designConfigModal', function() {
                if (!$('#designVariantSelect').data('select2')) {
                    $('#designVariantSelect').select2({
                        dropdownParent: $('#designConfigModal'),
                        width: '100%',
                        templateResult: formatDesignVariant,
                        templateSelection: formatDesignVariant,
                        minimumResultsForSearch: Infinity
                    });
                }
            });

            // FIX: Update Unit Label and Load Variants immediately when Family is selected
            // FIX: Update Unit Label and Load Variants immediately when Family is selected
            $('#bomFamilySelector').on('change', function() {
                const selectedOption = $(this).find(':selected');
                const val = $(this).val();
                const unit = selectedOption.data('unit');

                // 1. Update Unit Label
                if (unit) {
                    $('#bomUnit').text(unit);
                } else {
                    $('#bomUnit').text('unid');
                }

                // 2. Fetch Variants (Important: Trigger logic)
                if (typeof window.fetchMaterialVariants === 'function') {
                    window.fetchMaterialVariants(val);
                }
            });

            // === FUNCTION: SAVE DRAFT ===
            window.saveAsDraft = function(action = null) {
                // Preparar datos (similar al submit form)
                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('name', State.definition.name || 'Borrador sin nombre');
                formData.append('sku', State.definition.sku || '');
                formData.append('product_category_id', State.definition.category_id || '');
                formData.append('description', State.definition.description || '');

                formData.append('materials_json', JSON.stringify(State.bom));
                formData.append('variants_json', JSON.stringify(State.variants));
                formData.append('embroideries_json', JSON.stringify(State.designs));
                formData.append('extras_json', JSON.stringify(State.extras));
                formData.append('financials_json', JSON.stringify(State.financials));

                // Append Primary Image if selected
                const fileInput = document.getElementById('inpImage');
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('primary_image', fileInput.files[0]);
                }

                Swal.fire({
                    title: 'Guardando borrador...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch("{{ route('admin.products.save-draft') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            formSubmitting = true; // Evitar disparar beforeunload de nuevo
                            Swal.fire({
                                title: 'Guardado',
                                text: 'El borrador se ha guardado correctamente.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                if (typeof action === 'function') {
                                    action();
                                } else if (typeof action === 'string' && action) {
                                    window.location.href = action;
                                } else if (data.redirect_url) {
                                    // Opción default: ir a la edición del borrador
                                    // window.location.href = data.redirect_url;
                                }
                            });
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo guardar el borrador', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Ocurrió un error de conexión', 'error');
                    });
            };

            // === EXIT CONFIRMATION (Unsaved Changes) ===
            let formSubmitting = false;

            function hasUnsavedData() {
                return State.variants.length > 0 ||
                    State.bom.length > 0 ||
                    State.designs.length > 0 ||
                    State.extras.length > 0 ||
                    State.definition.name;
            }

            // Native browser beforeunload (backup)
            window.addEventListener('beforeunload', function(e) {
                if (hasUnsavedData() && !formSubmitting) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            });

            // Intercept navigation links - EXCLUDE sidebar toggles, treeview, and menu headers
            $(document).on('click',
                'a[href]:not([href^="#"]):not([href^="javascript"]):not([data-widget]):not(.nav-link[data-toggle]):not(.has-treeview > a)',
                function(e) {
                    // Also skip if inside the stepper or form controls
                    if ($(this).closest('.stepper-nav, .step-content, .modal').length > 0) {
                        return; // Allow normal navigation within the form
                    }

                    if (hasUnsavedData() && !formSubmitting) {
                        e.preventDefault();
                        const targetUrl = $(this).attr('href');

                        Swal.fire({
                            title: '¿Salir sin guardar?',
                            text: 'Tienes cambios sin guardar. ¿Qué deseas hacer?',
                            icon: 'warning',
                            showDenyButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar Borrador',
                            denyButtonText: 'Descartar y Salir',
                            cancelButtonText: 'Continuar Editando',
                            confirmButtonColor: '#28a745',
                            denyButtonColor: '#dc3545'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Implement save as draft via AJAX
                                window.saveAsDraft(targetUrl);
                            } else if (result.isDenied) {
                                formSubmitting = true;
                                window.location.href = targetUrl;
                            }
                            // Dismiss = stay on page
                        });
                    }
                });

            // Set flag when form is submitting
            $('form').on('submit', function() {
                formSubmitting = true;
            });

            // === BROWSER BACK BUTTON HANDLER ===
            // Push initial state
            if (history.state === null) {
                history.pushState({
                    page: 'product-create'
                }, '', window.location.href);
            }

            // Handle browser back button
            window.addEventListener('popstate', function(e) {
                if (hasUnsavedData() && !formSubmitting) {
                    // Push state back to prevent navigation
                    history.pushState({
                        page: 'product-create'
                    }, '', window.location.href);

                    Swal.fire({
                        title: '¿Salir sin guardar?',
                        text: 'Tienes cambios sin guardar. ¿Qué deseas hacer?',
                        icon: 'warning',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar Borrador',
                        denyButtonText: 'Descartar y Salir',
                        cancelButtonText: 'Continuar Editando',
                        confirmButtonColor: '#28a745',
                        denyButtonColor: '#dc3545'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.saveAsDraft(null); // No redirect URL, just history.back() logic?
                            // Actually, saveAsDraft needs to handle the redirect or callback.
                            // Let's modify saveAsDraft to accept a callback or handle history.
                            // For simplicity, we can just pass 'BACK' and handle it or pass nothing and do history.back() in the promise.
                            // However, since saveAsDraft is async, we can't easily pause the popstate.
                            // But we already pushed state. So we just need to go back after save.

                            // Re-implementing explicitly here for clarity or modifying saveAsDraft?
                            // Let's modify saveAsDraft usage here to use a custom callback if possible, 
                            // OR just duplicate the fetch logic? No, let's reuse.

                            // Hack: pass a special string or function?
                            // Let's just define a specialized callback.
                            window.saveAsDraftAction(() => {
                                history.back();
                                // We need to create a wrapper or modify saveAsDraft to take a function.
                            });
                        } else if (result.isDenied) {
                            formSubmitting = true;
                            history.back();
                        }
                        // Dismiss = stay on page (state already pushed back)
                    });
                }
            });
        });

        // ============================================================
        // NOTA: Lógica de imagen dropzone está en líneas ~3335-3409
        // Este bloque duplicado fue eliminado para evitar doble click
        // ============================================================

        // ============================================================
        // MANEJO DE ERRORES DE VALIDACIÓN (Server-side)
        // Responsabilidad: Mostrar errores de Laravel al usuario
        // ============================================================
        @if ($errors->any())
        (function() {
            var errorList = @json($errors->all());
            var errorHtml = '<ul class="text-left text-danger mb-0">';
            errorList.forEach(function(err) {
                errorHtml += '<li>' + err + '</li>';
            });
            errorHtml += '</ul>';

            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '¡Atención!',
                    html: errorHtml,
                    icon: 'error',
                    confirmButtonText: 'Revisar'
                });
            });
        })();
        @endif
    </script>
@stop
