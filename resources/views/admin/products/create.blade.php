@extends('adminlte::page')

@section('title', 'Alta de Producto - Sistema de Bordado')

@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="module-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-box-open mr-2"></i> Alta de Producto</h1>
                <p>Configurador profesional de productos con variantes y bordados</p>
            </div>
            <div>
                <a href="{{ route('admin.products.index') }}" class="btn-secondary-custom">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="content">
        <div class="container-fluid">

            <!-- Formulario -->
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" id="productForm">
                @csrf

                <!-- PASO 1: INFORMACIÓN BASE DEL PRODUCTO -->

                <!-- Navegación Global (Arriba) -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <button type="button" class="btn btn-secondary btn-navigation px-4 d-none"
                                    id="globalPrevBtn" onclick="prevStep()">
                                    <i class="fas fa-arrow-left"></i> Anterior
                                </button>
                            </div>

                            <div>
                                <button type="button" class="btn btn-primary btn-navigation px-4" id="globalNextBtn"
                                    onclick="nextStep()">
                                    Siguiente <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                        {{-- pasos 1 - 6 --}}
                        <div class="stepper-wrapper">
                            <div class="stepper-container">
                                <div class="step-item active" id="step1">
                                    <div class="step-circle">1</div>
                                    <span class="step-label">INFO. BÁSICA</span>
                                </div>
                                <div class="step-item" id="step2">
                                    <div class="step-circle">2</div>
                                    <span class="step-label">VARIANTES</span>
                                </div>
                                <div class="step-item" id="step3">
                                    <div class="step-circle">3</div>
                                    <span class="step-label">MATERIALES</span>
                                </div>
                                <div class="step-item" id="step4">
                                    <div class="step-circle">4</div>
                                    <div class="step-line"></div>
                                    <span class="step-label">BORDADOS</span>
                                </div>
                                <div class="step-item" id="step5">
                                    <div class="step-circle">5</div>
                                    <span class="step-label">EXTRAS</span>
                                </div>
                                <div class="step-item" id="step6">
                                    <div class="step-circle">6</div>
                                    <span class="step-label">REVISIÓN</span>
                                </div>
                            </div>
                        </div>
                        {{-- fin de pasos 1 - 6 --}}

                    </div>
                </div>
                <!-- ========================================= -->
                {{-- PASO 1: INFORMACIÓN BASE DEL PRODUCTO --}}
                <!-- ========================================= -->
                <div class="main-card fade-in" id="content-step1">
                    <div class="two-columns">
                        <div class="left-column">
                            <div class="section-title">
                                <i class="fas fa-tag"></i>
                                <span>Información del Producto</span>
                                <span class="badge-step">Paso 1 de 6</span>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label class="form-label">Nombre del Producto *</label>
                                    <input type="text" class="form-control form-control-lg"
                                        placeholder="Ej: Hipil Tradicional Bordado" name="name" id="productName"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">SKU Base *</label>
                                    <input type="text" class="form-control form-control-lg" placeholder="HIP-TRAD"
                                        name="sku" id="productSku" style="font-family: monospace;" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Categoría *</label>
                                    <select class="form-control" name="product_category_id" id="productCategory" required>
                                        <option value="">Seleccionar categoría...</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado Inicial</label>
                                    <select class="form-control" name="status" id="productStatus">
                                        <option value="draft">Borrador</option>
                                        <option value="active" selected>Activo</option>
                                        <option value="discontinued">Descontinuado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" rows="3" placeholder="Descripción detallada del producto..." name="description"
                                    id="productDescription"></textarea>
                            </div>

                            <!-- Campos ocultos para datos de otros pasos -->
                            <input type="hidden" name="variants_data" id="variantsData">
                            <input type="hidden" name="embroidery_data" id="embroideryData">
                            <input type="hidden" name="specifications_data" id="specificationsData">
                            <input type="hidden" name="extras_data" id="extrasData">
                            <input type="hidden" name="designs_data" id="designsData">
                        </div>

                        <div class="right-column">
                            <label class="form-label mb-3">Imagen Principal</label>
                            <div class="product-image-upload" id="imageUpload"
                                onclick="document.getElementById('imageFile').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click para subir imagen</span>
                                <small>PNG, JPG hasta 5MB</small>
                            </div>
                            <input type="file" name="image" id="imageFile" accept="image/*" style="display: none;"
                                onchange="previewImage(event)">
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 2: VARIANTES DEL PRODUCTO -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step2">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-layer-group"></i>
                            <span>Generador de Variantes</span>
                            <span class="badge-step">Paso 2 de 6</span>
                        </div>

                        <p class="text-muted mb-4">
                            Selecciona los atributos para crear combinaciones automáticas de variantes con SKU único.
                        </p>

                        <!-- Selector de Tallas -->
                        <div class="variant-generator mb-4">
                            <div class="variant-generator-title">
                                <i class="fas fa-ruler"></i> Tallas Disponibles
                            </div>

                            <div class="attribute-selector" id="sizesSelector">
                                @if ($sizeAttribute && $sizeAttribute->values->isNotEmpty())
                                    @foreach ($sizeAttribute->values as $value)
                                        <span class="attr-chip" data-attribute="talla" data-value="{{ $value->id }}">
                                            {{ $value->value }}
                                        </span>
                                    @endforeach
                                @else
                                    <small class="text-muted">No hay tallas configuradas</small>
                                @endif
                            </div>
                        </div>


                        <!-- Selector de Colores -->
                        <div class="variant-generator mb-4">
                            <div class="variant-generator-title">
                                <i class="fas fa-palette"></i> Colores Disponibles
                            </div>

                            <div class="attribute-selector" id="colorsSelector">
                                @if ($colorAttribute && $colorAttribute->values->isNotEmpty())
                                    @foreach ($colorAttribute->values as $value)
                                        <span class="attr-chip" data-attribute="color" data-value="{{ $value->id }}"
                                            style="border-color: {{ $value->hex_color ?? '#000' }}; color: {{ $value->hex_color ?? '#000' }};">
                                            ⬤ {{ $value->value }}
                                        </span>
                                    @endforeach
                                @else
                                    <small class="text-muted">No hay colores configurados</small>
                                @endif
                            </div>
                        </div>


                        <!-- Precio base por variante -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Precio Base por Variante</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" value="350.00" id="basePrice"
                                        step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock de Alerta</label>
                                <input type="number" class="form-control" value="5" id="stockAlert">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn-primary-custom w-100" onclick="generateVariants()">
                                    <i class="fas fa-magic"></i> Generar Variantes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de variantes generadas -->
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-list"></i>
                            <span>Variantes Generadas</span>
                            <span class="badge bg-primary text-white" id="variantCount">0 variantes</span>
                        </div>

                        <div class="variants-list" id="variantsList">
                            <div class="empty-state">
                                <i class="fas fa-cubes"></i>
                                <p>No hay variantes generadas aún</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 3: MATERIALES (BOM) -->
                <!-- ========================================= -->

                <div class="main-card fade-in d-none" id="content-step3">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-scroll"></i>
                            <span>Materiales Requeridos (BOM)</span>
                            <span class="badge-step">Paso 3 de 6</span>
                        </div>

                        {{-- BUSCADOR INTUITIVO UNIFICADO --}}
                        <div class="card border-primary-subtle mb-4 shadow-sm">
                            <div class="card-header bg-primary-subtle py-2">
                                <span class="fw-bold text-primary-emphasis">
                                    <i class="fas fa-search me-2"></i>Buscador de Insumos y Configuración
                                </span>
                            </div>
                            <div class="card-body bg-light-subtle">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-primary">1. Familia de Material</label>
                                        <select id="parentMaterial" class="form-select select2-custom">
                                            <option value="">Seleccione (Ej: Lino, Seda...)</option>
                                            @foreach ($materials as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary">2. Especificación (Color /
                                            SKU)</label>
                                        <select id="materialVariant" class="form-select select2-custom" disabled>
                                            <option value="">Primero seleccione familia...</option>
                                        </select>
                                        <div id="materialInfo" class="mt-1 small d-flex justify-content-between d-none">
                                            <span class="text-muted">Stock: <b id="materialStockValue"
                                                    class="text-dark">-</b></span>
                                            <span class="text-muted">Costo: <b id="materialCostValue"
                                                    class="text-success">$-</b></span>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-primary">3. Cantidad</label>
                                        <div class="input-group">
                                            <input type="number" id="qty" class="form-control" placeholder="0.00"
                                                step="0.0001">
                                            <span class="input-group-text bg-white small"
                                                id="materialUnitLabel">unid</span>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="materialIsPrimary">
                                            <label class="form-check-label small fw-bold" for="materialIsPrimary">Material
                                                Base</label>
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-primary w-100 shadow-sm" id="addBtn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- aqui inicia tabla --}}
                        <div class="table-responsive shadow-sm rounded">
                            <table class="table table-hover align-middle bg-white" id="materialsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">Material / SKU</th>
                                        <th class="text-center">Cant. Requerida</th>
                                        <th class="text-center">Costo Unit. (Snapshot)</th>
                                        <th class="text-center">Subtotal</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <tr id="noMaterialsRow">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <img src="/assets/img/empty-box.svg" alt=""
                                                style="width: 60px; opacity: 0.5" class="mb-2 d-block mx-auto">
                                            Aún no has agregado materiales a este producto.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-bold py-3">Costo de Producción (Materiales):
                                        </td>
                                        <td class="text-center py-3">
                                            <span class="badge bg-success fs-6" id="totalMaterialsCost">$0.00</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 4: ASIGNACIÓN DE BORDADOS -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step4">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-vector-square"></i>
                            <span>Asignación de Bordados</span>
                            <span class="badge-step">Paso 4 de 6</span>
                        </div>

                        <p class="text-muted mb-4">
                            Selecciona los diseños de bordado disponibles y su posición de aplicación.
                        </p>

                        <!-- Selector de posición -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Posición de Aplicación</label>
                                <select class="form-control" id="applicationPosition">
                                    <option value="">Seleccionar posición...</option>
                                    <option value="pecho_izq">Pecho Izquierdo</option>
                                    <option value="pecho_der">Pecho Derecho</option>
                                    <option value="espalda">Espalda Completa</option>
                                    <option value="manga_izq">Manga Izquierda</option>
                                    <option value="manga_der">Manga Derecha</option>
                                    <option value="cuello">Cuello</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Buscar Diseño</label>
                                <input type="text" class="form-control"
                                    placeholder="Buscar por nombre, código o cliente...">
                            </div>
                        </div>

                        <!-- Lista de bordados disponibles -->
                        <div class="embroidery-selector">
                            @forelse($designs as $design)
                                @php
                                    $export = $design->generalExports->first();
                                    $stitches = $export ? number_format($export->stitches_count) : 'N/A';
                                    $width = $export ? $export->width_mm : 0;
                                    $height = $export ? $export->height_mm : 0;
                                    $colors = $export ? $export->colors_count : 0;
                                    $format = $export ? $export->file_format : 'N/A';
                                    $image = $design->primaryImage ? $design->primaryImage->url : null;
                                    $sku = $design->slug ?? 'DES-' . $design->id;
                                @endphp
                                <div class="embroidery-card" data-id="{{ $design->id }}"
                                    data-name="{{ $design->name }}" data-stitches="{{ $stitches }}"
                                    data-dimensions="{{ $width }}×{{ $height }}"
                                    data-colors="{{ $colors }}" data-format="{{ $format }}">

                                    <div class="embroidery-thumb">
                                        @if ($image)
                                            <img src="{{ asset('storage/' . $image) }}" alt="{{ $design->name }}"
                                                style="max-width: 100%; max-height: 100%;">
                                        @else
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        @endif
                                    </div>
                                    <div class="embroidery-info">
                                        <div class="embroidery-name">{{ $design->name }}</div>
                                        <div class="embroidery-meta">
                                            <strong>{{ $stitches }}</strong> puntadas •
                                            <strong>{{ $width }}×{{ $height }}</strong> mm •
                                            <strong>{{ $colors }}</strong> colores
                                        </div>
                                        <div class="mt-2">
                                            <span class="embroidery-position text-muted">Seleccionar
                                                posición...</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="variant-sku">{{ $sku }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-4">
                                    <p class="text-muted">No hay diseños disponibles.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Métricas del bordado seleccionado -->
                        <div class="embroidery-metrics">
                            <div class="metric-card">
                                <span class="metric-value">8,500</span>
                                <span class="metric-label">Puntadas</span>
                            </div>
                            <div class="metric-card">
                                <span class="metric-value">55×82</span>
                                <span class="metric-label">Dimensiones (mm)</span>
                            </div>
                            <div class="metric-card">
                                <span class="metric-value">4</span>
                                <span class="metric-label">Colores</span>
                            </div>
                            <div class="metric-card">
                                <span class="metric-value">.DST</span>
                                <span class="metric-label">Formato</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bordados asignados -->
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-check-double"></i>
                            <span>Bordados Asignados al Producto</span>
                        </div>

                        <div class="variants-list">
                            <div class="variant-item">
                                <div class="variant-info">
                                    <span class="embroidery-position mr-3">Pecho Izquierdo</span>
                                    <span class="variant-name">Logo Corporativo - Empresa XYZ</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-muted">8,500 puntadas</span>
                                    <button type="button" class="btn-icon btn-icon-danger"><i
                                            class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 5: FICHA TÉCNICA Y EXTRAS -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step5">
                    <div class="two-columns">
                        <div class="left-column">
                            <div class="section-title">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Especificaciones Técnicas</span>
                                <span class="badge-step">Paso 5 de 6</span>
                            </div>

                            <div class="specs-grid mb-4">
                                <div>
                                    <label class="form-label">Tipo de Tela</label>
                                    <input type="text" class="form-control" value="Oxford Premium" id="tipoTela">
                                </div>
                                <div>
                                    <label class="form-label">Composición</label>
                                    <input type="text" class="form-control" value="60% Algodón / 40% Poliéster"
                                        id="material">
                                </div>
                                <div>
                                    <label class="form-label">Calibre de Hilo</label>
                                    <input type="text" class="form-control" value="120" id="hilo">
                                </div>
                            </div>

                            <div class="specs-grid cols-2 mb-4">
                                <div>
                                    <label class="form-label">Color Base de Tela</label>
                                    <input type="text" class="form-control" value="Blanco Óptico" id="colorTela">
                                </div>
                                <div>
                                    <label class="form-label">Proveedor</label>
                                    <select class="form-control" id="proveedor">
                                        <option>Textiles del Norte S.A.</option>
                                        <option>Algodones Premium</option>
                                        <option>Distribuidora Yucatán</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Notas de Producción</label>
                                <textarea class="form-control" rows="2" placeholder="Instrucciones especiales para producción..."
                                    id="notas"></textarea>
                            </div>

                            <!-- Servicios Extras -->
                            <div class="section-title mt-5">
                                <i class="fas fa-concierge-bell"></i>
                                <span>Servicios Adicionales</span>
                            </div>

                            <!-- Buscador de Extras -->
                            <div class="row mb-4">
                                <div class="col-md-9">
                                    <label class="form-label">Buscar Servicio Adicional</label>
                                    <select class="form-control select2" id="extrasSearch" style="width: 100%;">
                                        <option value="">Buscar servicio...</option>
                                        @foreach ($extras as $extra)
                                            <option value="{{ $extra->id }}" data-price="{{ $extra->price }}"
                                                data-name="{{ $extra->name }}">
                                                {{ $extra->name }} - ${{ number_format($extra->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-primary-custom w-100" id="btnAddExtra"
                                        onclick="addExtra()" disabled>
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla de Extras Asignados -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="extrasTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Servicio</th>
                                            <th>Costo Adicional</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se llena dinámicamente -->
                                        <tr id="noExtrasRow">
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fas fa-concierge-bell fa-2x mb-2 d-block"></i>
                                                No se han agregado servicios adicionales
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="text-end fw-bold">Total Extras:</td>
                                            <td class="fw-bold" id="totalExtrasCost">$0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <input type="hidden" name="extras_data" id="extrasData">
                        </div>

                        <div class="right-column">
                            <div class="section-title">
                                <i class="fas fa-code"></i>
                                <span>Vista Previa JSON</span>
                            </div>

                            <div class="json-preview" id="jsonPreview">{
                                "name": "Hipil Tradicional Bordado",
                                "sku": "HIP-TRAD",
                                "category_id": 1,
                                "specifications": {
                                "tipo_tela": "Oxford Premium",
                                "material": "60% Algodón / 40% Poliéster",
                                "hilo": "120",
                                "color": "Blanco Óptico",
                                "proveedor": "Textiles del Norte S.A.",
                                "notas": ""
                                },
                                "variants_count": 6,
                                "extras": ["Embolsado", "Alforza"],
                                "embroidery": {
                                "design_export_id": 1,
                                "position": "pecho_izquierdo"
                                }
                                }</div>

                            <div class="mt-4">
                                <div class="section-title">
                                    <i class="fas fa-calculator"></i>
                                    <span>Resumen de Costos</span>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Precio base variante:</span>
                                    <span class="font-weight-bold">$350.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Embolsado:</span>
                                    <span>+$5.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Alforza:</span>
                                    <span>+$25.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="font-weight-bold">Total por unidad:</span>
                                    <span class="font-weight-bold text-success" style="font-size: 18px;">$380.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 6: REVISIÓN FINAL -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step6">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Revisión Final</span>
                            <span class="badge-step">Paso 6 de 6</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-tag mr-2"></i> Información del Producto</h5>
                                    <div id="review-product-info"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-layer-group mr-2"></i> Variantes</h5>
                                    <div id="review-variants"></div>
                                </div>
                                <div class="review-section mt-4">
                                    <h5><i class="fas fa-scroll mr-2"></i> Materiales (BOM)</h5>
                                    <div id="review-materials"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-vector-square mr-2"></i> Bordados Asignados</h5>
                                    <div id="review-embroidery"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-clipboard-list mr-2"></i> Ficha Técnica</h5>
                                    <div id="review-specs"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-concierge-bell mr-2"></i> Servicios Adicionales</h5>
                                    <div id="review-extras"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="review-section">
                                    <h5><i class="fas fa-calculator mr-2"></i> Resumen de Costos</h5>
                                    <div id="review-costs"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Revise cuidadosamente la información antes
                                    de
                                    guardar el producto.
                                    Puede regresar a pasos anteriores si necesita realizar cambios.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="summary-footer">
                        <div class="summary-stats">
                            <div>
                                <span class="summary-stat-label">Variantes</span>
                                <span class="summary-stat-value" id="review-variants-count">0</span>
                            </div>
                            <div>
                                <span class="summary-stat-label">Bordados</span>
                                <span class="summary-stat-value" id="review-embroidery-count">0</span>
                            </div>
                            <div>
                                <span class="summary-stat-label">Extras</span>
                                <span class="summary-stat-value" id="review-extras-count">0</span>
                            </div>
                            <div>
                                <span class="summary-stat-label">Precio Final</span>
                                <span class="summary-stat-value highlight" id="review-total-price">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
    <br>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Mantener todos los estilos originales de la propuesta */
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: var(--gray-100);
            color: var(--gray-700);
            font-size: 14px;
        }

        /* Header del módulo */
        .module-header {
            background: linear-gradient(135deg, var(--dark) 0%, #334155 100%);
            padding: 24px 32px;
            color: white;
            margin: -24px -15px 24px -15px;
        }

        .module-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .module-header p {
            opacity: 0.7;
            margin: 4px 0 0;
            font-size: 14px;
        }

        /* Stepper mejorado */
        .stepper-container {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-bottom: 32px;
            padding: 0 60px;
        }

        .step-item {
            display: flex;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-item:not(:last-child)::after {
            content: '';
            flex: 1;
            height: 3px;
            background: var(--gray-200);
            margin: 0 12px;
            border-radius: 2px;
            transition: background 0.3s;
        }

        .step-item.completed:not(:last-child)::after {
            background: var(--success);
        }

        .step-item.active:not(:last-child)::after {
            background: linear-gradient(90deg, var(--primary) 50%, var(--gray-200) 50%);
        }

        .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            color: var(--gray-400);
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .step-item.active .step-circle {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        .step-item.completed .step-circle {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            position: absolute;
            top: 54px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .step-item.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .step-item.completed .step-label {
            color: var(--success);
        }

        /* Cards principales */
        .main-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        /* Secciones internas */
        .section-block {
            padding: 24px;
            border-bottom: 1px solid var(--gray-100);
        }

        .section-block:last-child {
            border-bottom: none;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 20px;
        }

        .section-title i {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .section-title .badge-step {
            background: var(--gray-100);
            color: var(--gray-500);
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: auto;
        }

        /* Form controls mejorados */
        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s;
            background: var(--gray-50);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .form-control-lg {
            padding: 14px 16px;
            font-size: 16px;
        }

        /* Imagen del producto */
        .product-image-upload {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius);
            height: auto;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .product-image-upload:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
        }

        .product-image-upload i {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 16px;
        }

        .product-image-upload span {
            color: var(--gray-500);
            font-weight: 500;
        }

        .product-image-upload small {
            color: var(--gray-400);
            margin-top: 4px;
        }

        .product-image-upload img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Generador de variantes */
        .variant-generator {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--gray-200);
        }

        .variant-generator-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .variant-generator-title i {
            color: var(--primary);
        }

        /* Lista de variantes */
        .variants-list {
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            background: white;
        }

        .variant-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.2s;
        }

        .variant-item:last-child {
            border-bottom: none;
        }

        .variant-item:hover {
            background: var(--gray-50);
        }

        .variant-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .variant-name {
            font-weight: 500;
            color: var(--gray-700);
        }

        .variant-attrs {
            display: flex;
            gap: 6px;
        }

        .variant-attr {
            background: var(--gray-100);
            color: var(--gray-600);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .variant-sku {
            font-family: 'SF Mono', 'Consolas', monospace;
            background: var(--dark);
            color: #fbbf24;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .variant-price {
            font-weight: 600;
            color: var(--success);
        }

        .variant-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-icon-light {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        .btn-icon-light:hover {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-icon-danger {
            background: #fef2f2;
            color: var(--danger);
        }

        .btn-icon-danger:hover {
            background: #fee2e2;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-400);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 13px;
        }

        /* Badges de atributo selector */
        .attribute-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .attr-chip {
            padding: 8px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .attr-chip:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .attr-chip.selected {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Paso 2: Ficha Técnica */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .specs-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .specs-grid.cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        /* Métricas de bordado */
        .embroidery-metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 16px;
        }

        .metric-card {
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }

        .metric-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-800);
            display: block;
        }

        .metric-label {
            font-size: 11px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Selector de bordados */
        .embroidery-selector {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--gray-200);
        }

        .embroidery-card {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            gap: 16px;
            margin-bottom: 12px;
        }

        .embroidery-card:hover {
            border-color: var(--primary);
        }

        .embroidery-card.selected {
            border-color: var(--primary);
            background: rgba(59, 130, 246, 0.05);
        }

        .embroidery-thumb {
            width: 80px;
            height: 80px;
            background: var(--gray-100);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .embroidery-thumb img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .embroidery-info {
            flex: 1;
        }

        .embroidery-name {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 4px;
        }

        .embroidery-meta {
            font-size: 12px;
            color: var(--gray-500);
        }

        .embroidery-position {
            background: var(--warning);
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Extras / Servicios */
        .extras-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .extra-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .extra-item:hover {
            border-color: var(--primary);
        }

        .extra-item.selected {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.05);
        }

        .extra-item.selected .extra-check {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .extra-check {
            width: 24px;
            height: 24px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            transition: all 0.2s;
        }

        .extra-name {
            font-weight: 500;
            color: var(--gray-700);
        }

        .extra-price {
            font-weight: 600;
            color: var(--success);
        }

        /* Footer de resumen */
        .summary-footer {
            background: linear-gradient(135deg, var(--dark) 0%, #334155 100%);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
        }

        .summary-stats {
            display: flex;
            gap: 32px;
        }

        .summary-stat-label {
            font-size: 11px;
            opacity: 0.7;
            text-transform: uppercase;
        }

        .summary-stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .summary-stat-value.highlight {
            color: #fbbf24;
        }

        /* Global Navigation */
        .global-navigation-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            border: 1px solid var(--gray-100);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }

        .navigation-spacer {
            flex-grow: 1;
        }

        .btn-navigation {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
        }

        .btn-prev {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-prev:hover {
            background: var(--gray-200);
            transform: translateX(-3px);
        }

        .btn-next {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-next:hover {
            opacity: 0.9;
            transform: translateX(3px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
        }

        /* Botones */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary-custom {
            background: white;
            border: 2px solid var(--gray-200);
            color: var(--gray-600);
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-secondary-custom:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
            text-decoration: none;
            color: var(--gray-600);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-success-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        /* Layout dos columnas */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 0;
        }

        .two-columns .left-column {
            padding: 24px;
        }

        .two-columns .right-column {
            background: var(--gray-50);
            border-left: 1px solid var(--gray-200);
            padding: 24px;
        }

        /* JSON Preview */
        .json-preview {
            background: var(--dark);
            border-radius: 8px;
            padding: 16px;
            font-family: 'SF Mono', 'Consolas', monospace;
            font-size: 11px;
            color: #a5f3fc;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        /* Navegación entre pasos */
        .step-navigation {
            display: flex;
            justify-content: space-between;
            padding: 20px 24px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .two-columns {
                grid-template-columns: 1fr;
            }

            .two-columns .right-column {
                border-left: none;
                border-top: 1px solid var(--gray-200);
            }

            .specs-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .embroidery-metrics {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Tabs internos */
        .internal-tabs {
            display: flex;
            gap: 4px;
            background: var(--gray-100);
            padding: 4px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .internal-tab {
            flex: 1;
            padding: 10px 16px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-weight: 500;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.2s;
        }

        .internal-tab.active {
            background: white;
            color: var(--gray-800);
            box-shadow: var(--shadow-sm);
        }

        /* Quick add */
        .quick-add-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 20px;
        }

        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.3s ease;
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

        /* Estilos para la sección de revisión */
        .review-section {
            background: var(--gray-50);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 15px;
            border: 1px solid var(--gray-200);
        }

        .review-section h5 {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gray-200);
        }

        .review-section p {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .review-section strong {
            color: var(--gray-800);
            min-width: 120px;
            display: inline-block;
        }

        .review-section .variant-review-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--primary);
        }

        .review-section .embroidery-review-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--warning);
        }

        .review-section .extra-review-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--success);
        }
    </style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />

    <script>
        let currentStep = 1;
        const totalSteps = 6;
        let productMaterials = [];
        let productExtras = [];

        // Variable para almacenar variantes de materiales
        let materialVariantsCache = {};

        function goToStep(step) {
            // Ocultar paso actual
            $(`#content-step${currentStep}`).addClass('d-none');
            $(`#step${currentStep}`).removeClass('active');

            // Marcar pasos anteriores como completados
            for (let i = 1; i < step; i++) {
                $(`#step${i}`).addClass('completed');
            }
            for (let i = step; i <= 6; i++) {
                $(`#step${i}`).removeClass('completed');
            }

            // Mostrar nuevo paso
            currentStep = step;
            $(`#content-step${currentStep}`).removeClass('d-none');
            $(`#step${currentStep}`).addClass('active');

            // Actualizar botones de navegación global
            updateGlobalNavigation();

            // Si el paso es 6, cargar la revisión
            if (step === 6) {
                loadReview();
            }

            // Scroll al inicio
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function updateGlobalNavigation() {
            // Botón Anterior
            if (currentStep === 1) {
                $('#globalPrevBtn').addClass('d-none');
            } else {
                $('#globalPrevBtn').removeClass('d-none');
            }

            // Botón Siguiente / Guardar
            const nextBtn = $('#globalNextBtn');
            if (currentStep === totalSteps) {
                nextBtn.html('<i class="fas fa-save"></i> Guardar Producto');
                nextBtn.removeClass('btn-next').addClass('btn-primary-custom');
                nextBtn.attr('onclick', 'submitForm()');
            } else {
                nextBtn.html('Siguiente <i class="fas fa-arrow-right"></i>');
                nextBtn.removeClass('btn-primary-custom').addClass('btn-next');
                nextBtn.attr('onclick', 'nextStep()');
            }
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                goToStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        }

        function submitForm() {
            // Validar antes de enviar
            if (validateForm()) {
                $('#productForm').submit();
            }
        }

        function validateForm() {
            const variants = JSON.parse($('#variantsData').val() || '[]');
            if (variants.length === 0) {
                Swal.fire('Error', 'Debe generar al menos una variante para el producto', 'error');
                goToStep(2);
                return false;
            }
            return true;
        }

        // ==========================================
        // LÓGICA DE MATERIALES (BOM) - PASO 3 (ACTUALIZADA)
        // ==========================================

        function fetchMaterialVariants(parentId) {
            /* if (materialVariantsCache[parentId]) {
                 populateMaterialVariants(materialVariantsCache[parentId]);
                 return;
             }*/

            // Hacer petición AJAX para obtener las variantes
            $.ajax({
                url: '{{ route('material-variants.conversiones', ['materialId' => 'PLACEHOLDER']) }}'.replace(
                    'PLACEHOLDER', parentId),
                method: 'GET',
                success: function(response) {
                    materialVariantsCache[parentId] = response;
                    populateMaterialVariants(response);
                },
                error: function() {
                    Swal.fire('Error', 'No se pudieron cargar las variantes del material', 'error');
                }
            });
        }

        //segundo select para variantes y el costo de varianates
        function populateMaterialVariants(variants) {
            const $select = $('#materialVariant');
            $select.empty();
            $select.append('<option value="">Seleccione una variante...</option>');

            if (variants.length === 0) {
                $select.append('<option value="" disabled>No hay variantes disponibles</option>');
                $select.prop('disabled', true);
                return;
            }

            variants.forEach(variant => {
                $select.append(`
            <option value="${variant.id}" 
                    data-cost="${variant.cost_base}" 
                    data-stock="${variant.stock_real}" 
                    data-unit="${variant.symbol}"
                    data-sku="${variant.full_name}"> 
                ${variant.text}
            </option>
        `);
            });

            $select.prop('disabled', false);
            $select.trigger('change');
        }

        function updateMaterialInfo(variantData) {
            const $info = $('#materialInfo');
            const $stock = $('#materialStockValue');
            const $cost = $('#materialCostValue');
            const $unitLabel = $('#materialUnitLabel');

            if (variantData && variantData.cost !== undefined) {
                $stock.text(variantData.stock || 0);
                $cost.text('$' + parseFloat(variantData.cost).toFixed(2));
                $unitLabel.text(variantData.unit || 'unid');
                $info.removeClass('d-none');
                $('#addBtn').prop('disabled', false);
            } else {
                $info.addClass('d-none');
                $('#addBtn').prop('disabled', true);
            }
        }

        function addMaterialFromForm() {
            const parentId = $('#parentMaterial').val();
            const variantId = $('#materialVariant').val();
            const variantText = $('#materialVariant option:selected').text();
            const variantData = $('#materialVariant option:selected').data();
            const qty = parseFloat($('#qty').val());
            const isPrimary = $('#materialIsPrimary').is(':checked');
            const parentText = $('#parentMaterial option:selected').text();

            if (!parentId || !variantId || !qty || qty <= 0) {
                Swal.fire('Error', 'Complete todos los campos requeridos', 'error');
                return;
            }

            // Verificar si ya existe
            if (productMaterials.find(m => m.variant_id == variantId)) {
                Swal.fire('Atención', 'Esta variante de material ya ha sido agregada.', 'warning');
                return;
            }

            const material = {
                parent_id: parentId,
                parent_name: parentText,
                variant_id: variantId,
                variant_name: variantText.split(' (')[0], // Remover información de stock
                sku: variantData.sku || '',
                unit: variantData.unit || 'unid',
                cost: parseFloat(variantData.cost) || 0,
                stock: parseInt(variantData.stock) || 0,
                quantity: qty,
                is_primary: isPrimary
            };

            productMaterials.push(material);
            renderMaterialsTable();
            resetMaterialForm();
        }

        function removeMaterial(variantId) {
            productMaterials = productMaterials.filter(m => m.variant_id != variantId);
            renderMaterialsTable();
        }

        function renderMaterialsTable() {
            const tbody = $('#materialsTable tbody');
            tbody.empty();

            let totalCost = 0;

            if (productMaterials.length === 0) {
                tbody.html(`
                    <tr id="noMaterialsRow">
                        <td colspan="5" class="text-center text-muted py-5">
                            <img src="/assets/img/empty-box.svg" alt=""
                                style="width: 60px; opacity: 0.5" class="mb-2 d-block mx-auto">
                            Aún no has agregado materiales a este producto.
                        </td>
                    </tr>
                `);
                $('#totalMaterialsCost').text('$0.00');
                return;
            }

            productMaterials.forEach((m, index) => {
                const subtotal = m.cost * m.quantity;
                totalCost += subtotal;

                const primaryBadge = m.is_primary ?
                    '<span class="badge bg-primary ms-1">Base</span>' : '';

                tbody.append(`
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold">${m.parent_name} - ${m.variant_name}</div>
                            <div class="text-muted small">SKU: ${m.sku}</div>
                            ${primaryBadge}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">${m.quantity} ${m.unit}</span>
                        </td>
                        <td class="text-center">
                            $${m.cost.toFixed(2)}
                        </td>
                        <td class="text-center fw-bold">
                            $${subtotal.toFixed(2)}
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="removeMaterial('${m.variant_id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            $('#totalMaterialsCost').text(`$${totalCost.toFixed(2)}`);
        }

        function resetMaterialForm() {
            $('#parentMaterial').val(null).trigger('change');
            $('#materialVariant').empty().prop('disabled', true);
            $('#qty').val('');
            $('#materialIsPrimary').prop('checked', false);
            $('#materialInfo').addClass('d-none');
            $('#addBtn').prop('disabled', true);
        }

        // ==========================================
        // LÓGICA DE EXTRAS (PASO 5)
        // ==========================================

        function addExtra() {
            const selectedOption = $('#extrasSearch').find(':selected');
            const id = $('#extrasSearch').val();
            const name = selectedOption.data('name');
            const price = parseFloat(selectedOption.data('price'));

            if (!id) return;

            // Verificar duplicados
            if (productExtras.find(e => e.id == id)) {
                Swal.fire('Atención', 'Este servicio ya ha sido agregado.', 'warning');
                return;
            }

            productExtras.push({
                id,
                name,
                price
            });
            renderExtrasTable();

            // Reset selection
            $('#extrasSearch').val(null).trigger('change');
            $('#btnAddExtra').prop('disabled', true);
        }

        function removeExtra(id) {
            productExtras = productExtras.filter(e => e.id != id);
            renderExtrasTable();
        }

        function renderExtrasTable() {
            const tbody = $('#extrasTable tbody');
            tbody.empty();
            let total = 0;

            if (productExtras.length === 0) {
                tbody.html(`
                    <tr id="noExtrasRow">
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fas fa-concierge-bell fa-2x mb-2 d-block"></i>
                            No se han agregado servicios adicionales
                        </td>
                    </tr>
                `);
                $('#totalExtrasCost').text('$0.00');
                document.getElementById('extrasData').value = '[]';
                return;
            }

            productExtras.forEach(extra => {
                total += extra.price;
                tbody.append(`
                    <tr>
                        <td>${extra.name}</td>
                        <td>+$${extra.price.toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExtra(${extra.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            $('#totalExtrasCost').text(`$${total.toFixed(2)}`);
            document.getElementById('extrasData').value = JSON.stringify(productExtras);
        }

        // ==========================================
        // LÓGICA DE VARIANTES (PASO 2)
        // ==========================================

        function generateVariants() {
            const sizes = [...document.querySelectorAll('#sizesSelector .attr-chip.selected')]
                .map(chip => ({
                    id: chip.dataset.value,
                    name: chip.textContent.trim()
                }));
            const colors = [...document.querySelectorAll('#colorsSelector .attr-chip.selected')]
                .map(chip => ({
                    id: chip.dataset.value,
                    name: chip.textContent.trim().replace('⬤ ', '')
                }));

            const basePrice = parseFloat(document.getElementById('basePrice').value) || 0;
            const baseSku = document.getElementById('productSku').value;

            if (sizes.length === 0 || colors.length === 0) {
                Swal.fire('Atención', 'Debe seleccionar al menos una talla y un color para generar variantes.', 'warning');
                return;
            }

            if (!baseSku) {
                Swal.fire('Atención', 'Debe ingresar un SKU base para el producto.', 'warning');
                return;
            }

            // Generar combinaciones
            let currentVariants = JSON.parse(document.getElementById('variantsData').value || '[]');
            const newVariants = [];

            sizes.forEach(size => {
                colors.forEach(color => {
                    const sku = `${baseSku}-${size.name}-${color.name}`.toUpperCase()
                        .replace(/\s+/g, '')
                        .replace(/[^A-Z0-9-]/g, '');

                    // Verificar si ya existe este SKU
                    if (!currentVariants.some(v => v.sku === sku)) {
                        newVariants.push({
                            size: size.id,
                            size_name: size.name,
                            color: color.id,
                            color_name: color.name,
                            sku: sku,
                            price: basePrice,
                            stock_alert: $('#stockAlert').val() || 5
                        });
                    }
                });
            });

            if (newVariants.length === 0 && currentVariants.length === 0) {
                Swal.fire('Atención', 'Las variantes seleccionadas ya han sido generadas previamente.', 'info');
                return;
            }

            currentVariants = [...currentVariants, ...newVariants];
            updateVariantsList(currentVariants);
            updateVariantCount(currentVariants.length);
            document.getElementById('variantsData').value = JSON.stringify(currentVariants);

            if (newVariants.length > 0) {
                Swal.fire('Éxito', `Se generaron ${newVariants.length} nuevas variantes.`, 'success');
            }
        }

        function removeVariant(index) {
            let variants = JSON.parse(document.getElementById('variantsData').value || '[]');
            variants.splice(index, 1);
            updateVariantsList(variants);
            updateVariantCount(variants.length);
            document.getElementById('variantsData').value = JSON.stringify(variants);
        }

        function updateVariantsList(variants) {
            const variantsList = document.getElementById('variantsList');
            variantsList.innerHTML = '';

            if (variants.length === 0) {
                variantsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-cubes"></i>
                        <p>No hay variantes generadas aún</p>
                    </div>
                `;
                return;
            }

            variants.forEach((variant, index) => {
                const variantItem = document.createElement('div');
                variantItem.className = 'variant-item';
                variantItem.innerHTML = `
                    <div class="variant-info">
                        <span class="variant-sku">${variant.sku}</span>
                        <div class="variant-attrs">
                            <span class="variant-attr">Talla: ${variant.size_name || variant.size}</span>
                            <span class="variant-attr">Color: ${variant.color_name || variant.color}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="variant-price">$${variant.price.toFixed(2)}</span>
                        <div class="variant-actions">
                            <button type="button" class="btn-icon btn-icon-danger" onclick="removeVariant(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                variantsList.appendChild(variantItem);
            });
        }

        function updateVariantCount(count) {
            document.getElementById('variantCount').textContent = `${count} variante${count !== 1 ? 's' : ''}`;
        }

        // ==========================================
        // LÓGICA DE BORDADOS (PASO 4)
        // ==========================================

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imageUpload');
                output.innerHTML = `<img src="${reader.result}" alt="Imagen del producto">`;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // ==========================================
        // LÓGICA DE REVISIÓN (PASO 6)
        // ==========================================

        function loadReview() {
            // Información del producto
            const productName = document.getElementById('productName').value;
            const productSku = document.getElementById('productSku').value;
            const productCategory = document.getElementById('productCategory').options[document.getElementById(
                'productCategory').selectedIndex].text;
            const productStatus = document.getElementById('productStatus').value;
            const productDescription = document.getElementById('productDescription').value;

            document.getElementById('review-product-info').innerHTML = `
                <p><strong>Nombre:</strong> ${productName}</p>
                <p><strong>SKU Base:</strong> ${productSku}</p>
                <p><strong>Categoría:</strong> ${productCategory}</p>
                <p><strong>Estado:</strong> ${productStatus}</p>
                <p><strong>Descripción:</strong> ${productDescription || 'Sin descripción'}</p>
            `;

            // Variantes
            const variants = JSON.parse(document.getElementById('variantsData').value || '[]');
            let variantsHtml = '';
            variants.forEach(variant => {
                variantsHtml += `
                    <div class="variant-review-item">
                        <p><strong>SKU:</strong> ${variant.sku}</p>
                        <p><strong>Atributos:</strong> Talla ${variant.size_name || variant.size}, Color ${variant.color_name || variant.color}</p>
                        <p><strong>Precio:</strong> $${variant.price.toFixed(2)}</p>
                    </div>
                `;
            });
            document.getElementById('review-variants').innerHTML = variantsHtml || '<p>No hay variantes generadas</p>';
            document.getElementById('review-variants-count').textContent = variants.length;

            // Bordados
            const selectedEmbroidery = [...document.querySelectorAll('.embroidery-card.selected')];
            let embroideryHtml = '';
            selectedEmbroidery.forEach(card => {
                const name = card.querySelector('.embroidery-name').textContent;
                const meta = card.querySelector('.embroidery-meta').textContent;
                const position = card.querySelector('.embroidery-position')?.textContent || 'Sin posición';

                embroideryHtml += `
                    <div class="embroidery-review-item">
                        <p><strong>Diseño:</strong> ${name}</p>
                        <p><strong>Especificaciones:</strong> ${meta}</p>
                        <p><strong>Posición:</strong> ${position}</p>
                    </div>
                `;
            });
            document.getElementById('review-embroidery').innerHTML = embroideryHtml || '<p>No hay bordados asignados</p>';
            document.getElementById('review-embroidery-count').textContent = selectedEmbroidery.length;

            // Materiales
            let materialsHtml = '';
            let materialsTotal = 0;

            productMaterials.forEach(m => {
                const cost = m.cost * m.quantity;
                materialsTotal += cost;
                materialsHtml += `
                    <div class="variant-review-item" style="border-left-color: #6366f1;">
                        <div class="d-flex justify-content-between">
                            <strong>${m.parent_name} - ${m.variant_name}</strong>
                            <span>${m.quantity} ${m.unit}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>${m.is_primary ? 'Principal' : 'Secundario'}</span>
                            <span>$${cost.toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });

            document.getElementById('review-materials').innerHTML = materialsHtml || '<p>No se han asignado materiales</p>';

            // Ficha técnica
            const tipoTela = document.getElementById('tipoTela')?.value || 'No especificado';
            const material = document.getElementById('material')?.value || 'No especificado';
            const hilo = document.getElementById('hilo')?.value || 'No especificado';
            const colorTela = document.getElementById('colorTela')?.value || 'No especificado';
            const proveedor = document.getElementById('proveedor')?.value || 'No especificado';
            const notas = document.getElementById('notas')?.value || 'Sin notas';

            document.getElementById('review-specs').innerHTML = `
                <p><strong>Tipo de Tela:</strong> ${tipoTela}</p>
                <p><strong>Composición:</strong> ${material}</p>
                <p><strong>Calibre de Hilo:</strong> ${hilo}</p>
                <p><strong>Color Base:</strong> ${colorTela}</p>
                <p><strong>Proveedor:</strong> ${proveedor}</p>
                <p><strong>Notas:</strong> ${notas}</p>
            `;

            // Extras
            let extrasHtml = '';
            let extrasTotal = 0;

            if (productExtras.length === 0) {
                extrasHtml = '<p>No hay servicios adicionales</p>';
            } else {
                productExtras.forEach(extra => {
                    extrasTotal += extra.price;
                    extrasHtml += `
                        <div class="extra-review-item">
                            <p><strong>Servicio:</strong> ${extra.name}</p>
                            <p><strong>Costo adicional:</strong> +$${extra.price.toFixed(2)}</p>
                        </div>
                    `;
                });
            }

            document.getElementById('review-extras').innerHTML = extrasHtml;
            document.getElementById('review-extras-count').textContent = productExtras.length;

            // Resumen de costos
            const basePrice = parseFloat(document.getElementById('basePrice')?.value || 0);
            const variantsCount = variants.length;
            const totalPrice = basePrice + extrasTotal;

            document.getElementById('review-costs').innerHTML = `
                <p><strong>Precio base por variante:</strong> $${basePrice.toFixed(2)}</p>
                <p><strong>Costo de extras:</strong> $${extrasTotal.toFixed(2)}</p>
                <p><strong>Número de variantes:</strong> ${variantsCount}</p>
                <hr>
                <p><strong>Total por unidad:</strong> $${totalPrice.toFixed(2)}</p> 
            `;

            document.getElementById('review-total-price').textContent = `$${totalPrice.toFixed(2)}`;
        }

        // ==========================================
        // INICIALIZACIÓN
        // ==========================================

        $(document).ready(function() {
            // Inicializar Select2 para Materiales (Paso 3)
            $('#parentMaterial').select2({
                placeholder: 'Seleccione familia de material...',
                allowClear: true,
                width: '100%'
            });

            $('#materialVariant').select2({
                placeholder: 'Seleccione variante...',
                disabled: true,
                width: '100%'
            });

            // Evento cuando se selecciona una familia de material
            $('#parentMaterial').on('select2:select', function(e) {
                const parentId = $(this).val();
                if (parentId) {
                    fetchMaterialVariants(parentId);
                } else {
                    $('#materialVariant').empty().prop('disabled', true);
                    $('#materialInfo').addClass('d-none');
                }
            });

            // Evento cuando se selecciona una variante de material
            $('#materialVariant').on('select2:select', function(e) {
                const selectedOption = $(this).find(':selected');
                const variantData = selectedOption.data();
                updateMaterialInfo(variantData);
            });

            // Botón Agregar Material
            $('#addBtn').click(function() {
                addMaterialFromForm();
            });

            // Inicializar Select2 para Extras (Paso 5)
            $('#extrasSearch').select2({
                placeholder: "Buscar servicio...",
                allowClear: true,
                width: '100%'
            });

            // Habilitar botón al seleccionar extra
            $('#extrasSearch').on('select2:select', function(e) {
                $('#btnAddExtra').prop('disabled', false);
            });

            // Inicializar eventos de atributos (Paso 2)
            $('.attr-chip').click(function() {
                $(this).toggleClass('selected');
            });

            // Inicializar eventos de bordados (Paso 4)
            $('.embroidery-card').click(function() {
                $('.embroidery-card').removeClass('selected');
                $(this).addClass('selected');

                // Leer datos
                const stitches = $(this).data('stitches');
                const dimensions = $(this).data('dimensions');
                const colors = $(this).data('colors');
                const format = $(this).data('format');

                // Actualizar métricas
                const metricsHtml = `
                    <div class="metric-card">
                        <span class="metric-value">${stitches}</span>
                        <span class="metric-label">Puntadas</span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-value">${dimensions}</span>
                        <span class="metric-label">Dimensiones (mm)</span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-value">${colors}</span>
                        <span class="metric-label">Colores</span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-value">${format}</span>
                        <span class="metric-label">Formato</span>
                    </div>
                `;
                $('.embroidery-metrics').html(metricsHtml);
            });

            // Validación del formulario antes de enviar
            $('#productForm').on('submit', function(e) {
                if (currentStep !== 6) {
                    e.preventDefault();
                    Swal.fire('Falta completar', 'Por favor complete todos los pasos antes de guardar',
                        'error');
                    goToStep(1);
                    return;
                }

                // Recolectar todos los datos
                const formData = {
                    name: $('#productName').val(),
                    sku: $('#productSku').val(),
                    product_category_id: $('#productCategory').val(),
                    status: $('#productStatus').val(),
                    description: $('#productDescription').val(),
                    variants: JSON.parse($('#variantsData').val() || '[]'),
                    materials: productMaterials,
                    specifications: {
                        tipo_tela: $('#tipoTela').val(),
                        material: $('#material').val(),
                        hilo: $('#hilo').val(),
                        color_tela: $('#colorTela').val(),
                        proveedor: $('#proveedor').val(),
                        notas: $('#notas').val()
                    },
                    extras: productExtras
                };

                // Llenar campos ocultos con datos JSON
                $('#specificationsData').val(JSON.stringify(formData.specifications));
                $('#extrasData').val(JSON.stringify(formData.extras));

                // Validaciones adicionales
                if (!formData.name || !formData.sku || !formData.product_category_id) {
                    e.preventDefault();
                    Swal.fire('Error', 'Por favor complete los campos obligatorios del producto', 'error');
                    goToStep(1);
                    return;
                }

                if (formData.variants.length === 0) {
                    e.preventDefault();
                    Swal.fire('Error', 'Debe generar al menos una variante para el producto', 'error');
                    goToStep(2);
                    return;
                }

                // Mostrar confirmación
                e.preventDefault();
                Swal.fire({
                    title: '¿Guardar producto?',
                    text: 'Se creará el producto con todas las configuraciones definidas.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar formulario
                        $(this).off('submit').submit();
                    }
                });
            });

            // Inicializar contador de variantes
            updateVariantCount(0);
        });
    </script>
@stop
