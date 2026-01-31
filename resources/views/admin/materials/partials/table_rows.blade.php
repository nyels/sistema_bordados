@foreach ($materials as $material)
    <tr>
        <td class="text-center align-middle">{{ $loop->iteration }}</td>
        <td class="text-center align-middle">
            <span class="badge badge-primary">{{ $material->category->name ?? 'N/A' }}</span>
        </td>
        <td class="text-center align-middle">
            <strong>{{ $material->name }}</strong>
            @if ($material->description)
                <br><small style="color: #333;">{{ Str::limit($material->description, 50) }}</small>
            @endif
        </td>

        <td class="text-center align-middle">{{ $material->composition ?? '-' }}</td>
        <td class="text-center align-middle">
            <span class="badge badge-success" style="font-size: 14px; padding: 6px 10px;" title="Unidad de Compra (empaque del proveedor)">
                {{ $material->baseUnit->symbol ?? 'N/A' }}
            </span>
        </td>
        <td class="text-center align-middle py-2" style="min-width: 205px;">
            @if ($material->unitConversions->count() > 0)
                <div class="d-flex flex-column align-items-center" style="gap: 2px;">
                    @foreach ($material->unitConversions as $conversion)
                        @php
                            $breakdown = $conversion->getBreakdownData();
                            $baseSymbol = $material->baseUnit->symbol ?? '';
                        @endphp
                        <div class="text-sm mb-1" style="line-height: 1.2;">
                            <span class="text-uppercase font-weight-bold"
                                style="color: #333;">{{ $conversion->fromUnit->name }}</span> -

                            @if ($breakdown['has_breakdown'])
                                <span class="font-weight-bold">{{ number_format($breakdown['qty'], 0) }}
                                    {{ $breakdown['unit_symbol'] }}</span>
                                <span
                                    class="text-muted ml-1">({{ number_format($breakdown['each_value'], 0, '.', ',') }}
                                    {{ $baseSymbol }})</span>
                                <span class="mx-1 text-muted">=</span>
                            @endif

                            <span
                                class="font-weight-bold text-dark">{{ number_format($conversion->conversion_factor, 0, '.', ',') }}
                                {{ $baseSymbol }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <span class="badge badge-warning" title="Configure cómo se compra este material antes de usarlo">
                    <i class="fas fa-exclamation-triangle"></i> Requiere configuración
                </span>
            @endif
        </td>
        <td class="text-center align-middle">
            @if ($material->has_color)
                @if ($material->unitConversions->count() > 0)
                    <button type="button" class="btn btn-info btn-sm btn-variants-modal"
                        data-material-id="{{ $material->id }}"
                        data-material-name="{{ $material->name }}"
                        style="font-size: 14px; padding: 4px 12px; min-width: 40px;"
                        title="Click para ver variantes">
                        {{ $material->active_variants_count }}
                    </button>
                @else
                    <span class="badge badge-secondary" style="font-size: 14px; padding: 6px 10px;"
                        title="Configure primero cómo se compra este material">
                        {{ $material->active_variants_count }}
                    </span>
                @endif
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        <td class="text-center align-middle">
            <div class="dropdown">
                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button"
                    id="dropdownMaterial{{ $material->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-cog"></i> Acciones
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="dropdownMaterial{{ $material->id }}">
                    {{-- SECCIÓN: OPERACIÓN --}}
                    <h6 class="dropdown-header text-info">
                        <i class="fas fa-box"></i> Operación
                    </h6>
                    @if ($material->unitConversions->count() > 0)
                        <a class="dropdown-item" href="{{ route('admin.material-variants.index', $material->id) }}">
                            <i class="fas fa-palette text-info mr-2"></i> Variantes
                            @if ($material->has_color)
                                <span class="badge badge-info badge-sm ml-1">{{ $material->active_variants_count }}</span>
                            @endif
                        </a>
                    @else
                        <span class="dropdown-item text-muted" style="cursor: not-allowed;"
                            title="Configure primero cómo se compra este material">
                            <i class="fas fa-palette text-muted mr-2"></i> Variantes
                            <i class="fas fa-lock text-warning ml-1" style="font-size: 0.7rem;"></i>
                        </span>
                    @endif

                    <div class="dropdown-divider"></div>

                    {{-- SECCIÓN: CONFIGURACIÓN --}}
                    <h6 class="dropdown-header text-secondary">
                        <i class="fas fa-cog"></i> Configuración
                    </h6>
                    @if ($material->unitConversions->count() == 0)
                        <a class="dropdown-item text-warning font-weight-bold" href="{{ route('admin.material-conversions.index', $material->id) }}">
                            <i class="fas fa-exclamation-triangle text-warning mr-2"></i> Configurar compra
                            <small class="text-muted d-block ml-4">Requerido para operar</small>
                        </a>
                    @else
                        <a class="dropdown-item" href="{{ route('admin.material-conversions.index', $material->id) }}">
                            <i class="fas fa-exchange-alt text-secondary mr-2"></i> Cómo se compra
                        </a>
                    @endif
                    <a class="dropdown-item" href="{{ route('admin.materials.edit', $material->id) }}">
                        <i class="fas fa-edit text-warning mr-2"></i> Editar Material
                    </a>

                    <div class="dropdown-divider"></div>

                    {{-- SECCIÓN: RIESGO --}}
                    <h6 class="dropdown-header text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Zona de Riesgo
                    </h6>
                    <a class="dropdown-item text-danger" href="{{ route('admin.materials.confirm_delete', $material->id) }}">
                        <i class="fas fa-trash mr-2"></i> Eliminar
                    </a>
                </div>
            </div>
        </td>
    </tr>
@endforeach
