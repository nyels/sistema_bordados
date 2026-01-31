{{-- Sección de Diseño/Personalización para items que requieren diseño --}}
{{-- NOTA: Este partial ahora se usa dentro de un wrapper colapsable en show.blade.php --}}
{{-- El header principal está en el wrapper, aquí solo mostramos el contenido --}}
@php
    $designItems = $order->items->filter(fn($i) => $i->personalization_type === 'design');
@endphp

@if($designItems->count() > 0)
@php
    $allApproved = $designItems->every(fn($i) => $i->design_approved);
    $pendingCount = $designItems->where('design_status', 'pending')->count();
    $inReviewCount = $designItems->where('design_status', 'in_review')->count();
@endphp
{{-- 3. DISENO - Contenido interno (el header está en el wrapper de show.blade.php) --}}
<div class="card-section-diseno-content">

    {{-- REPOSITORIO DE DISEÑOS PERSONALIZADOS --}}
    @if(in_array($order->status, ['draft', 'confirmed']))
        <div class="card-body border-bottom" style="background: #f3e5f5;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-folder-open mr-2" style="color: #7b1fa2;"></i>
                    <strong style="color: #4a148c;">Repositorio de Diseños Personalizados</strong>
                    <p class="mb-0 mt-1" style="font-size: 14px; color: #6a1b9a;">
                        Reutilice diseños de pedidos anteriores o cree uno nuevo para guardar.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm mr-2" style="background: #7b1fa2; color: white;"
                            data-toggle="modal" data-target="#searchCustomDesignModal">
                        <i class="fas fa-search mr-1"></i> Buscar Diseño
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-toggle="modal" data-target="#createCustomDesignModal">
                        <i class="fas fa-plus mr-1"></i> Crear Nuevo
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Producto</th>
                    <th>Texto Personalizado</th>
                    <th>Archivo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($designItems as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->design_notes)
                            <br><span style="font-size: 14px; color: #495057;">{{ Str::limit($item->design_notes, 50) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($item->custom_text)
                            <span class="badge badge-light">{{ $item->custom_text }}</span>
                        @elseif($item->embroidery_text)
                            <span class="badge badge-light">{{ $item->embroidery_text }}</span>
                        @else
                            <span style="color: #495057;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->design_file)
                            <a href="{{ route('admin.orders.items.design.download', [$order, $item]) }}"
                               class="btn btn-sm btn-outline-info" title="Descargar diseño">
                                <i class="fas fa-download"></i>
                                {{ Str::limit($item->design_original_name, 20) }}
                            </a>
                        @else
                            <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Sin archivo</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @switch($item->design_status)
                            @case('pending')
                                <span class="badge badge-secondary"
                                      data-toggle="tooltip" title="Pendiente de subir o enviar a revisión">
                                    <i class="fas fa-clock"></i> Pendiente
                                </span>
                                @break
                            @case('in_review')
                                <span class="badge badge-info"
                                      data-toggle="tooltip" title="Esperando aprobación del cliente">
                                    <i class="fas fa-eye"></i> En Revisión
                                </span>
                                @break
                            @case('approved')
                                <span class="badge badge-success"
                                      data-toggle="tooltip" title="Aprobado el {{ $item->design_approved_at?->format('d/m/Y H:i') }}">
                                    <i class="fas fa-check-circle"></i> Aprobado
                                </span>
                                @break
                            @case('rejected')
                                <span class="badge badge-danger"
                                      data-toggle="tooltip" title="Rechazado - requiere corrección">
                                    <i class="fas fa-times-circle"></i> Rechazado
                                </span>
                                @break
                        @endswitch
                    </td>
                    <td class="text-center">
                        @if(in_array($order->status, ['draft', 'confirmed']))
                            {{-- Subir diseño --}}
                            @if(!$item->design_file || $item->design_status === 'rejected')
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-toggle="modal" data-target="#uploadDesignModal{{ $item->id }}">
                                    <i class="fas fa-upload"></i> Subir
                                </button>
                            @endif

                            {{-- Enviar a revisión --}}
                            @if($item->design_file && $item->design_status === 'pending')
                                <form action="{{ route('admin.orders.items.design.send-review', [$order, $item]) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info">
                                        <i class="fas fa-paper-plane"></i> Enviar
                                    </button>
                                </form>
                            @endif

                            {{-- Aprobar (si está en revisión) --}}
                            @if($item->design_status === 'in_review')
                                <form action="{{ route('admin.orders.items.design.approve', [$order, $item]) }}"
                                      method="POST" class="d-inline"
                                      data-confirm="default"
                                      data-confirm-title="¿Aprobar diseño?"
                                      data-confirm-text="Una vez aprobado, el item podrá continuar a producción.">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger"
                                        data-toggle="modal" data-target="#rejectDesignModal{{ $item->id }}">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            @endif
                        @else
                            @if($item->design_file)
                                <a href="{{ route('admin.orders.items.design.download', [$order, $item]) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(!$allApproved && in_array($order->status, ['confirmed']))
        <div class="py-2 px-3" style="background: #fff3cd; border-top: 1px solid #ffc107;">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            <strong>BLOQUEO:</strong> Producción no puede iniciar hasta que todos los diseños estén aprobados.
        </div>
    @endif
</div>{{-- Cierre card-section-diseno-content --}}

{{-- Modales de Upload y Rechazo --}}
@foreach($designItems as $item)
    {{-- Modal Upload --}}
    <div class="modal fade" id="uploadDesignModal{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.items.design.upload', [$order, $item]) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-upload mr-2"></i>Subir Diseño: {{ $item->product_name }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Archivo de Diseño *</label>
                            <input type="file" name="design_file" class="form-control-file" required
                                   accept=".ai,.dst,.png,.jpg,.jpeg,.pdf,.svg">
                            <small class="text-muted">Formatos: AI, DST, PNG, JPG, PDF, SVG. Max 10MB</small>
                        </div>
                        <div class="form-group">
                            <label>Texto Personalizado</label>
                            <input type="text" name="custom_text" class="form-control"
                                   value="{{ $item->custom_text }}" placeholder="Ej: María García">
                            <small class="text-muted">Nombre, iniciales o texto para personalizar</small>
                        </div>
                        <div class="form-group">
                            <label>Notas del Diseño</label>
                            <textarea name="design_notes" class="form-control" rows="3"
                                      placeholder="Indicaciones especiales...">{{ $item->design_notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Subir Diseño
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Rechazo --}}
    <div class="modal fade" id="rejectDesignModal{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.items.design.reject', [$order, $item]) }}" method="POST">
                    @csrf
                    <div class="modal-header" style="background: #343a40; color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-times mr-2"></i>Rechazar Diseño: {{ $item->product_name }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Motivo del Rechazo *</label>
                            <textarea name="rejection_reason" class="form-control" rows="4" required
                                      placeholder="Describa el motivo del rechazo..."></textarea>
                            <small class="text-muted">Este mensaje se mostrará al equipo para corrección.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Rechazar Diseño
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- ========================================== --}}
{{-- MODAL: BUSCAR DISEÑO PERSONALIZADO --}}
{{-- ========================================== --}}
<div class="modal fade" id="searchCustomDesignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #7b1fa2; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-search mr-2"></i> Repositorio de Diseños Personalizados
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Buscador y Filtros --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="searchDesignQuery"
                                   placeholder="Buscar por texto, nombre, cliente...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="searchDesignType">
                            <option value="">Todos los tipos</option>
                            <option value="text">Textos / Nombres</option>
                            <option value="logo">Logos Personalizados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="searchDesignClient">
                            <option value="">Todos los clientes</option>
                            <option value="{{ $order->cliente->id }}">{{ $order->cliente->nombre }} (actual)</option>
                        </select>
                    </div>
                </div>

                {{-- Tabs de clasificación --}}
                <ul class="nav nav-tabs mb-3" id="designRepoTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tabAllDesigns">
                            <i class="fas fa-folder mr-1"></i> Todos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tabTextDesigns">
                            <i class="fas fa-font mr-1"></i> Textos / Nombres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tabLogoDesigns">
                            <i class="fas fa-image mr-1"></i> Logos
                        </a>
                    </li>
                </ul>

                {{-- Contenido de tabs --}}
                <div class="tab-content">
                    {{-- Tab: Todos --}}
                    <div class="tab-pane fade show active" id="tabAllDesigns">
                        <div class="p-4 text-center" style="background: #f5f5f5; border-radius: 8px;">
                            <i class="fas fa-folder-open fa-3x mb-3" style="color: #6c757d;"></i>
                            <p style="color: #212529; font-size: 15px;">
                                El repositorio de diseños personalizados estará disponible próximamente.
                            </p>
                            <p style="color: #212529; font-size: 13px;">
                                Aquí podrá buscar y reutilizar diseños de pedidos anteriores.
                            </p>
                        </div>

                        {{-- Ejemplo de cómo se verán los resultados (UI conceptual) --}}
                        <div class="mt-3" style="opacity: 0.6;">
                            <small class="text-muted">Vista previa de resultados:</small>
                            <div class="list-group mt-2">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3 text-center" style="width: 50px; height: 50px; background: #e1bee7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-font" style="color: #7b1fa2;"></i>
                                        </div>
                                        <div>
                                            <strong style="color: #212529;">María García</strong>
                                            <span class="badge badge-info ml-2" style="font-size: 11px;">Texto</span>
                                            <br>
                                            <small style="color: #6c757d;">Fuente: Script • Bordado: Hilo dorado • Cliente: Juan Pérez</small>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary mr-1" disabled>
                                            <i class="fas fa-check"></i> Usar
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            <i class="fas fa-copy"></i> Duplicar
                                        </button>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3 text-center" style="width: 50px; height: 50px; background: #e8eaf6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #3f51b5;"></i>
                                        </div>
                                        <div>
                                            <strong style="color: #212529;">Logo Empresa ABC</strong>
                                            <span class="badge badge-primary ml-2" style="font-size: 11px;">Logo</span>
                                            <br>
                                            <small style="color: #6c757d;">Adaptación para polo • Cliente: Empresa ABC • 15/01/2026</small>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary mr-1" disabled>
                                            <i class="fas fa-check"></i> Usar
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            <i class="fas fa-copy"></i> Duplicar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Textos --}}
                    <div class="tab-pane fade" id="tabTextDesigns">
                        <div class="p-4 text-center" style="background: #fff3e0; border-radius: 8px;">
                            <i class="fas fa-font fa-3x mb-3" style="color: #ff9800;"></i>
                            <p style="color: #e65100; font-size: 15px;">Textos y Nombres Personalizados</p>
                            <p style="color: #212529; font-size: 13px;">
                                Nombres, iniciales, frases, fechas especiales...
                            </p>
                        </div>
                    </div>

                    {{-- Tab: Logos --}}
                    <div class="tab-pane fade" id="tabLogoDesigns">
                        <div class="p-4 text-center" style="background: #e3f2fd; border-radius: 8px;">
                            <i class="fas fa-image fa-3x mb-3" style="color: #1976d2;"></i>
                            <p style="color: #0d47a1; font-size: 15px;">Logos Personalizados</p>
                            <p style="color: #212529; font-size: 13px;">
                                Logos modificados, adaptaciones, versiones para prendas específicas...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f5f5f5;">
                <div class="mr-auto" style="font-size: 13px; color: #212529;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Los diseños personalizados NO aparecen en el catálogo maestro.
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL: CREAR DISEÑO PERSONALIZADO --}}
{{-- ========================================== --}}
<div class="modal fade" id="createCustomDesignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #7b1fa2; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i> Crear Diseño Personalizado
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Selector de tipo --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100" style="cursor: pointer; border: 2px solid transparent;" id="selectTextType"
                             onclick="selectDesignType('text')">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-font fa-3x mb-3" style="color: #ff9800;"></i>
                                <h5 style="color: #e65100;">Texto / Nombre</h5>
                                <p class="mb-0" style="font-size: 13px; color: #6c757d;">
                                    Nombres, iniciales, frases, fechas especiales
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100" style="cursor: pointer; border: 2px solid transparent;" id="selectLogoType"
                             onclick="selectDesignType('logo')">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-image fa-3x mb-3" style="color: #1976d2;"></i>
                                <h5 style="color: #0d47a1;">Logo Personalizado</h5>
                                <p class="mb-0" style="font-size: 13px; color: #6c757d;">
                                    Logos modificados, adaptaciones, versiones especiales
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Formulario Texto --}}
                <div id="formTextDesign" style="display: none;">
                    <h6 class="mb-3" style="color: #e65100;">
                        <i class="fas fa-font mr-2"></i> Datos del Texto Personalizado
                    </h6>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label><strong>Texto *</strong></label>
                                <input type="text" class="form-control" placeholder="Ej: María García, Gracias 2026, Boda Ana & Luis">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Tipo de Texto</strong></label>
                                <select class="form-control">
                                    <option value="name">Nombre</option>
                                    <option value="short">Texto corto</option>
                                    <option value="long">Texto largo</option>
                                    <option value="initials">Iniciales</option>
                                    <option value="date">Fecha especial</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Fuente</strong></label>
                                <select class="form-control">
                                    <option value="">Seleccionar fuente...</option>
                                    <option value="script">Script (cursiva elegante)</option>
                                    <option value="block">Block (letras gruesas)</option>
                                    <option value="serif">Serif (clásica)</option>
                                    <option value="sans">Sans-serif (moderna)</option>
                                    <option value="custom">Personalizada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Tipo de Bordado</strong></label>
                                <select class="form-control">
                                    <option value="">Seleccionar...</option>
                                    <option value="flat">Bordado plano</option>
                                    <option value="3d">Bordado 3D / Puff</option>
                                    <option value="satin">Puntada satín</option>
                                    <option value="fill">Relleno completo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Observaciones</strong></label>
                        <textarea class="form-control" rows="2" placeholder="Instrucciones especiales, colores, ubicación..."></textarea>
                    </div>
                </div>

                {{-- Formulario Logo --}}
                <div id="formLogoDesign" style="display: none;">
                    <h6 class="mb-3" style="color: #0d47a1;">
                        <i class="fas fa-image mr-2"></i> Datos del Logo Personalizado
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Archivo del Logo *</strong></label>
                                <input type="file" class="form-control-file" accept=".ai,.dst,.png,.jpg,.jpeg,.pdf,.svg">
                                <small class="text-muted">Formatos: AI, DST, PNG, JPG, PDF, SVG. Max 10MB</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Nombre descriptivo *</strong></label>
                                <input type="text" class="form-control" placeholder="Ej: Logo Empresa ABC - Versión Polo">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Origen</strong></label>
                                <select class="form-control">
                                    <option value="">Archivo nuevo</option>
                                    <option value="adaptation">Adaptación de diseño maestro</option>
                                    <option value="client">Proporcionado por cliente</option>
                                    <option value="modification">Modificación de logo existente</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Asociar a cliente</strong></label>
                                <select class="form-control">
                                    <option value="">Sin asociar (genérico)</option>
                                    <option value="{{ $order->cliente->id }}" selected>{{ $order->cliente->nombre }} {{ $order->cliente->apellidos }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Observaciones</strong></label>
                        <textarea class="form-control" rows="2" placeholder="Notas sobre colores, tamaño, ubicación..."></textarea>
                    </div>
                </div>

                {{-- Checkbox guardar para reutilizar --}}
                <div id="saveForReuseSection" style="display: none;">
                    <hr>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="saveForReuse" checked>
                        <label class="custom-control-label" for="saveForReuse" style="font-size: 15px;">
                            <i class="fas fa-save mr-1" style="color: #7b1fa2;"></i>
                            <strong>Guardar este diseño para reutilizar en futuros pedidos</strong>
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1 ml-4">
                        El diseño quedará disponible en el repositorio para búsqueda y reutilización.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <div class="mr-auto" style="font-size: 13px; color: #212529;">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Los diseños personalizados NO van al catálogo maestro.
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" style="background: #7b1fa2; color: white;" disabled id="btnCreateDesign">
                    <i class="fas fa-plus-circle mr-1"></i> Crear Diseño
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function selectDesignType(type) {
    document.getElementById('selectTextType').style.border = '2px solid transparent';
    document.getElementById('selectLogoType').style.border = '2px solid transparent';
    document.getElementById('formTextDesign').style.display = 'none';
    document.getElementById('formLogoDesign').style.display = 'none';
    document.getElementById('saveForReuseSection').style.display = 'none';
    document.getElementById('btnCreateDesign').disabled = true;

    if (type === 'text') {
        document.getElementById('selectTextType').style.border = '2px solid #ff9800';
        document.getElementById('formTextDesign').style.display = 'block';
    } else if (type === 'logo') {
        document.getElementById('selectLogoType').style.border = '2px solid #1976d2';
        document.getElementById('formLogoDesign').style.display = 'block';
    }

    document.getElementById('saveForReuseSection').style.display = 'block';
    document.getElementById('btnCreateDesign').disabled = false;
}
</script>
@endif
