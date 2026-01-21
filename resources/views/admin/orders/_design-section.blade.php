{{-- Sección de Diseño/Personalización para items que requieren diseño --}}
@php
    $designItems = $order->items->filter(fn($i) => $i->personalization_type === 'design');
@endphp

@if($designItems->count() > 0)
<div class="card card-purple card-outline mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-palette mr-2"></i>
            Diseño / Personalización
        </h3>
        <div class="card-tools">
            @php
                $allApproved = $designItems->every(fn($i) => $i->design_approved);
                $pendingCount = $designItems->where('design_status', 'pending')->count();
                $inReviewCount = $designItems->where('design_status', 'in_review')->count();
            @endphp
            @if($allApproved)
                <span class="badge badge-success"><i class="fas fa-check"></i> Todos Aprobados</span>
            @else
                @if($pendingCount > 0)
                    <span class="badge badge-secondary">{{ $pendingCount }} Pendientes</span>
                @endif
                @if($inReviewCount > 0)
                    <span class="badge badge-info">{{ $inReviewCount }} En Revisión</span>
                @endif
            @endif
        </div>
    </div>
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
                            <br><small class="text-muted">{{ Str::limit($item->design_notes, 50) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($item->custom_text)
                            <span class="badge badge-light">{{ $item->custom_text }}</span>
                        @elseif($item->embroidery_text)
                            <span class="badge badge-light">{{ $item->embroidery_text }}</span>
                        @else
                            <span class="text-muted">-</span>
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
        <div class="card-footer bg-warning-subtle">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            <strong>BLOQUEO:</strong> Producción no puede iniciar hasta que todos los diseños estén aprobados.
        </div>
    @endif
</div>

{{-- Modales de Upload y Rechazo --}}
@foreach($designItems as $item)
    {{-- Modal Upload --}}
    <div class="modal fade" id="uploadDesignModal{{ $item->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.orders.items.design.upload', [$order, $item]) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-primary text-white">
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
                    <div class="modal-header bg-danger text-white">
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
@endif
