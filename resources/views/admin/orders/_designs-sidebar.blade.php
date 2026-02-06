{{-- Partial: Diseños del Pedido para sidebar (AJAX refresh) --}}
@if($allDesigns->isNotEmpty())
    <div class="card card-section-disenos">
        <div class="card-header bg-purple" style="background: #6f42c1 !important; color: white;">
            <h5 class="mb-0"><i class="fas fa-palette mr-2"></i> Diseños del Pedido</h5>
        </div>
        <div class="card-body p-3">
            @foreach($allDesigns as $designExport)
                @php
                    $design = $designExport->design;
                    $variant = $designExport->variant;
                @endphp
                <div class="design-item border rounded p-3 mb-3 bg-light {{ $loop->last ? 'mb-0' : '' }}">
                    {{-- Nombre del diseño --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark" style="font-size: 16px;">
                            <i class="fas fa-tshirt mr-2" style="color: #6f42c1;"></i>
                            {{ $designExport->application_label ?? $design->name ?? 'Diseño' }}
                        </strong>
                        @if($designExport->file_path)
                            <a href="{{ route('admin.design-exports.download', $designExport) }}"
                               class="btn btn-sm btn-primary"
                               title="Descargar archivo">
                                <i class="fas fa-download mr-1"></i> Descargar
                            </a>
                        @endif
                    </div>
                    {{-- Tipo de aplicación / Ubicación --}}
                    @php
                        $appTypeName = null;
                        if ($designExport->application_type_id && $designExport->applicationType) {
                            $appTypeName = $designExport->applicationType->nombre_aplicacion;
                        } elseif ($designExport->application_type && $designExport->application_type !== 'general') {
                            $appTypeName = ucfirst(str_replace('_', ' ', $designExport->application_type));
                        }
                    @endphp
                    @if($appTypeName)
                        <div class="mb-2" style="font-size: 14px;">
                            <i class="fas fa-map-marker-alt mr-1" style="color: #e65100;"></i>
                            <span style="color: #e65100; font-weight: 600;">{{ $appTypeName }}</span>
                        </div>
                    @endif

                    {{-- Ruta de origen --}}
                    @if($design)
                        <div class="mb-2" style="font-size: 14px;">
                            <i class="fas fa-sitemap mr-1" style="color: #1976d2;"></i>
                            <span style="color: #1976d2; font-weight: 500;">{{ $design->name }}</span>
                            @if($variant)
                                <span style="color: #1976d2; margin: 0 6px;">→</span>
                                <span style="color: #1976d2; font-weight: 500;">{{ $variant->name }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Layout compacto: Preview + Especificaciones --}}
                    <div class="d-flex align-items-start gap-2" style="gap: 12px;">
                        {{-- Preview SVG compacto --}}
                        @if($designExport->svg_content)
                            <div class="bg-white border rounded p-2 flex-shrink-0"
                                 style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <div style="max-width: 70px; max-height: 70px;">
                                    {!! preg_replace('/(<svg[^>]*)(>)/', '$1 style="width:100%;height:auto;max-height:70px;"$2', $designExport->svg_content) !!}
                                </div>
                            </div>
                        @endif

                        {{-- Especificaciones técnicas compactas --}}
                        <div class="flex-grow-1" style="font-size: 13px;">
                            @if($designExport->stitches_count)
                                <div class="mb-1">
                                    <i class="fas fa-dot-circle mr-1" style="color: #e91e63; font-size: 11px;"></i>
                                    <span class="text-muted">Puntadas:</span>
                                    <strong class="text-dark">{{ number_format($designExport->stitches_count, 0, ',', '.') }}</strong>
                                </div>
                            @endif
                            @if($designExport->width_mm && $designExport->height_mm)
                                <div class="mb-1">
                                    <i class="fas fa-ruler-combined mr-1" style="color: #2196f3; font-size: 11px;"></i>
                                    <span class="text-muted">Tamaño:</span>
                                    <strong class="text-dark">{{ $designExport->width_mm }}×{{ $designExport->height_mm }}mm</strong>
                                </div>
                            @endif
                            @if($designExport->colors_count)
                                <div class="mb-1">
                                    <i class="fas fa-palette mr-1" style="color: #7b1fa2; font-size: 11px;"></i>
                                    <span class="text-muted">Colores:</span>
                                    <strong class="text-dark">{{ $designExport->colors_count }}</strong>
                                </div>
                            @endif
                            @if($designExport->file_format)
                                <div>
                                    <i class="fas fa-file-archive mr-1" style="color: #4caf50; font-size: 11px;"></i>
                                    <span class="badge badge-dark" style="font-size: 11px;">{{ strtoupper($designExport->file_format) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
