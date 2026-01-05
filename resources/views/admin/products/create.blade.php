@extends('adminlte::page')

@section('title', 'Alta de Producto - Sistema de Bordado')

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
            <!-- Stepper -->
            <div class="stepper-container" style="margin-top: 20px;">
                <div class="step-item active" id="step1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Producto Base</span>
                </div>
                <div class="step-item" id="step2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Variantes</span>
                </div>
                <div class="step-item" id="step3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Bordados</span>
                </div>
                <div class="step-item" id="step4">
                    <div class="step-circle">4</div>
                    <span class="step-label">Ficha Técnica</span>
                </div>
                <div class="step-item" id="step5">
                    <div class="step-circle">5</div>
                    <span class="step-label">Revisión Final</span>
                </div>
            </div>

            <!-- Formulario -->
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data"
                id="productForm">
                @csrf

                <!-- PASO 1: INFORMACIÓN BASE DEL PRODUCTO -->
                <div class="main-card fade-in" id="content-step1">
                    <div class="two-columns">
                        <div class="left-column">
                            <div class="section-title">
                                <i class="fas fa-tag"></i>
                                <span>Información del Producto</span>
                                <span class="badge-step">Paso 1 de 5</span>
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

                    <div class="step-navigation">
                        <div></div>
                        <button type="button" class="btn-primary-custom" onclick="goToStep(2)">
                            Siguiente: Variantes <i class="fas fa-arrow-right"></i>
                        </button>
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
                            <span class="badge-step">Paso 2 de 5</span>
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
                                            style="border-color: {{ $value->hex_code ?? '#000' }}; color: {{ $value->hex_code ?? '#000' }};">
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

                    <div class="step-navigation">
                        <button type="button" class="btn-secondary-custom" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn-primary-custom" onclick="goToStep(3)">
                            Siguiente: Bordados <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 3: ASIGNACIÓN DE BORDADOS -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step3">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-vector-square"></i>
                            <span>Asignación de Bordados</span>
                            <span class="badge-step">Paso 3 de 5</span>
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
                            <div class="embroidery-card selected">
                                <div class="embroidery-thumb">
                                    <i class="fas fa-image fa-2x text-muted"></i>
                                </div>
                                <div class="embroidery-info">
                                    <div class="embroidery-name">Logo Corporativo - Empresa XYZ</div>
                                    <div class="embroidery-meta">
                                        <strong>8,500</strong> puntadas • <strong>55×82</strong> mm • <strong>4</strong>
                                        colores
                                    </div>
                                    <div class="mt-2">
                                        <span class="embroidery-position">Pecho Izquierdo</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="variant-sku">EXP-001</span>
                                </div>
                            </div>

                            <div class="embroidery-card">
                                <div class="embroidery-thumb">
                                    <i class="fas fa-image fa-2x text-muted"></i>
                                </div>
                                <div class="embroidery-info">
                                    <div class="embroidery-name">Escudo Grande - Espalda</div>
                                    <div class="embroidery-meta">
                                        <strong>24,000</strong> puntadas • <strong>180×155</strong> mm • <strong>6</strong>
                                        colores
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="variant-sku">EXP-002</span>
                                </div>
                            </div>

                            <div class="embroidery-card">
                                <div class="embroidery-thumb">
                                    <i class="fas fa-image fa-2x text-muted"></i>
                                </div>
                                <div class="embroidery-info">
                                    <div class="embroidery-name">Monograma Pequeño</div>
                                    <div class="embroidery-meta">
                                        <strong>3,200</strong> puntadas • <strong>30×30</strong> mm • <strong>2</strong>
                                        colores
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="variant-sku">EXP-003</span>
                                </div>
                            </div>
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

                    <div class="step-navigation">
                        <button type="button" class="btn-secondary-custom" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn-primary-custom" onclick="goToStep(4)">
                            Siguiente: Ficha Técnica <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 4: FICHA TÉCNICA Y EXTRAS -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step4">
                    <div class="two-columns">
                        <div class="left-column">
                            <div class="section-title">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Especificaciones Técnicas</span>
                                <span class="badge-step">Paso 4 de 5</span>
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

                            <div class="extras-list">
                                <div class="extra-item selected" onclick="toggleExtra(this)">
                                    <div class="d-flex align-items-center">
                                        <div class="extra-check"><i class="fas fa-check"></i></div>
                                        <span class="extra-name">Embolsado Individual</span>
                                    </div>
                                    <span class="extra-price">+$5.00</span>
                                </div>
                                <div class="extra-item" onclick="toggleExtra(this)">
                                    <div class="d-flex align-items-center">
                                        <div class="extra-check"></div>
                                        <span class="extra-name">Etiqueta de Marca Personalizada</span>
                                    </div>
                                    <span class="extra-price">+$12.00</span>
                                </div>
                                <div class="extra-item" onclick="toggleExtra(this)">
                                    <div class="d-flex align-items-center">
                                        <div class="extra-check"></div>
                                        <span class="extra-name">Planchado Profesional</span>
                                    </div>
                                    <span class="extra-price">+$8.00</span>
                                </div>
                                <div class="extra-item selected" onclick="toggleExtra(this)">
                                    <div class="d-flex align-items-center">
                                        <div class="extra-check"><i class="fas fa-check"></i></div>
                                        <span class="extra-name">Alforza Decorativa</span>
                                    </div>
                                    <span class="extra-price">+$25.00</span>
                                </div>
                            </div>
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

                    <div class="step-navigation">
                        <button type="button" class="btn-secondary-custom" onclick="goToStep(3)">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn-primary-custom" onclick="goToStep(5)">
                            Siguiente: Revisión Final <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- PASO 5: REVISIÓN FINAL -->
                <!-- ========================================= -->
                <div class="main-card fade-in d-none" id="content-step5">
                    <div class="section-block">
                        <div class="section-title">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Revisión Final</span>
                            <span class="badge-step">Paso 5 de 5</span>
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
                                    <i class="fas fa-info-circle"></i> Revise cuidadosamente la información antes de
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

                        <div class="d-flex gap-3">
                            <button type="button" class="btn-secondary-custom" onclick="goToStep(4)">
                                <i class="fas fa-arrow-left"></i> Anterior
                            </button>
                            <button type="submit" class="btn-success-custom">
                                <i class="fas fa-save"></i> Guardar Producto
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
            height: 100%;
            min-height: 320px;
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
    <script>
        let currentStep = 1;

        function goToStep(step) {
            // Ocultar paso actual
            $(`#content-step${currentStep}`).addClass('d-none');
            $(`#step${currentStep}`).removeClass('active');

            // Marcar pasos anteriores como completados
            for (let i = 1; i < step; i++) {
                $(`#step${i}`).addClass('completed');
            }
            for (let i = step; i <= 5; i++) {
                $(`#step${i}`).removeClass('completed');
            }

            // Mostrar nuevo paso
            currentStep = step;
            $(`#content-step${currentStep}`).removeClass('d-none');
            $(`#step${currentStep}`).addClass('active');

            // Si el paso es 5, cargar la revisión
            if (step === 5) {
                loadReview();
            }

            // Scroll al inicio
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Toggle atributos
        $('.attr-chip').click(function() {
            $(this).toggleClass('selected');
        });

        // Toggle extras
        function toggleExtra(element) {
            $(element).toggleClass('selected');
            if ($(element).hasClass('selected')) {
                $(element).find('.extra-check').html('<i class="fas fa-check"></i>');
            } else {
                $(element).find('.extra-check').html('');
            }
        }

        // Toggle bordados
        $('.embroidery-card').click(function() {
            $('.embroidery-card').removeClass('selected');
            $(this).addClass('selected');
        });

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imageUpload');
                output.innerHTML = `<img src="${reader.result}" alt="Imagen del producto">`;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function generateVariants() {
            // Lógica para generar variantes
            const sizes = [...document.querySelectorAll('#sizesSelector .attr-chip.selected')]
                .map(chip => chip.dataset.value);
            const colors = [...document.querySelectorAll('#colorsSelector .attr-chip.selected')]
                .map(chip => chip.dataset.value);
            const basePrice = parseFloat(document.getElementById('basePrice').value);
            const baseSku = document.getElementById('productSku').value;

            // Generar combinaciones
            const variants = [];
            sizes.forEach(size => {
                colors.forEach(color => {
                    variants.push({
                        size,
                        color,
                        sku: `${baseSku}-${size}-${color}`,
                        price: basePrice
                    });
                });
            });

            // Actualizar lista de variantes
            updateVariantsList(variants);
            updateVariantCount(variants.length);

            // Guardar datos en campo oculto
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

            variants.forEach(variant => {
                const variantItem = document.createElement('div');
                variantItem.className = 'variant-item';
                variantItem.innerHTML = `
                <div class="variant-info">
                    <span class="variant-sku">${variant.sku}</span>
                    <div class="variant-attrs">
                        <span class="variant-attr">Talla: ${variant.size}</span>
                        <span class="variant-attr">Color: ${variant.color}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="variant-price">$${variant.price.toFixed(2)}</span>
                    <div class="variant-actions">
                        <button type="button" class="btn-icon btn-icon-light"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn-icon btn-icon-danger"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
                variantsList.appendChild(variantItem);
            });
        }

        function updateVariantCount(count) {
            document.getElementById('variantCount').textContent = `${count} variantes`;
        }

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
                    <p><strong>Atributos:</strong> Talla ${variant.size}, Color ${variant.color}</p>
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
            const selectedExtras = [...document.querySelectorAll('.extra-item.selected')];
            let extrasHtml = '';
            let extrasTotal = 0;
            selectedExtras.forEach(extra => {
                const name = extra.querySelector('.extra-name').textContent;
                const priceText = extra.querySelector('.extra-price').textContent;
                const price = parseFloat(priceText.replace('+$', '')) || 0;
                extrasTotal += price;

                extrasHtml += `
                <div class="extra-review-item">
                    <p><strong>Servicio:</strong> ${name}</p>
                    <p><strong>Costo adicional:</strong> ${priceText}</p>
                </div>
            `;
            });
            document.getElementById('review-extras').innerHTML = extrasHtml || '<p>No hay servicios adicionales</p>';
            document.getElementById('review-extras-count').textContent = selectedExtras.length;

            // Resumen de costos
            const basePrice = parseFloat(document.getElementById('basePrice')?.value || 350);
            const totalPrice = basePrice + extrasTotal;

            document.getElementById('review-costs').innerHTML = `
            <p><strong>Precio base por variante:</strong> $${basePrice.toFixed(2)}</p>
            <p><strong>Costo de extras:</strong> $${extrasTotal.toFixed(2)}</p>
            <hr>
            <p><strong>Total por unidad:</strong> $${totalPrice.toFixed(2)}</p> 
        `;

            document.getElementById('review-total-price').textContent = `$${totalPrice.toFixed(2)}`;
        }

        // Validación del formulario antes de enviar
        document.getElementById('productForm').addEventListener('submit', function(e) {
            if (currentStep !== 5) {
                e.preventDefault();
                alert('Por favor complete todos los pasos antes de guardar');
                return;
            }

            // Recolectar todos los datos
            const formData = {
                name: document.getElementById('productName').value,
                sku: document.getElementById('productSku').value,
                product_category_id: document.getElementById('productCategory').value,
                status: document.getElementById('productStatus').value,
                description: document.getElementById('productDescription').value,
                variants: JSON.parse(document.getElementById('variantsData').value || '[]'),
                specifications: {
                    tipo_tela: document.getElementById('tipoTela')?.value,
                    material: document.getElementById('material')?.value,
                    hilo: document.getElementById('hilo')?.value,
                    color_tela: document.getElementById('colorTela')?.value,
                    proveedor: document.getElementById('proveedor')?.value,
                    notas: document.getElementById('notas')?.value
                },
                extras: [...document.querySelectorAll('.extra-item.selected')].map(extra => ({
                    name: extra.querySelector('.extra-name').textContent,
                    price: parseFloat(extra.querySelector('.extra-price').textContent.replace('+$',
                        ''))
                }))
            };

            // Llenar campos ocultos con datos JSON
            document.getElementById('specificationsData').value = JSON.stringify(formData.specifications);
            document.getElementById('extrasData').value = JSON.stringify(formData.extras);

            // Validaciones adicionales
            if (!formData.name || !formData.sku || !formData.product_category_id) {
                e.preventDefault();
                alert('Por favor complete los campos obligatorios del producto');
                return;
            }

            if (formData.variants.length === 0) {
                e.preventDefault();
                alert('Debe generar al menos una variante para el producto');
                return;
            }
        });

        // Inicializar algunos datos por defecto
        $(document).ready(function() {
            // Generar variantes iniciales basadas en selecciones por defecto
            setTimeout(() => {
                generateVariants();
            }, 500);
        });
    </script>
@stop
