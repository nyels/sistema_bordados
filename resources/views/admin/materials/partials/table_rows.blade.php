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
            <span class="badge badge-success" style="font-size: 14px; padding: 6px 10px;" title="Unidad de Inventario">
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
                <small class="text-muted font-italic">Solo directa</small>
            @endif
        </td>
        <td class="text-center align-middle">
            @if ($material->has_color)
                <span class="badge badge-info" style="font-size: 14px; padding: 6px 10px;">
                    {{ $material->active_variants_count }}
                </span>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        <td class="text-center align-middle">
            <div class="d-flex justify-content-center align-items-center gap-1">
                {{-- Botón Variantes --}}
                <a href="{{ route('admin.material-variants.index', $material->id) }}"
                    class="btn btn-info btn-sm d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;" title="Ver Variantes">
                    <i class="fas fa-palette"></i>
                </a>

                {{-- Botón Conversiones --}}
                <a href="{{ route('admin.material-conversions.index', $material->id) }}"
                    class="btn btn-secondary btn-sm d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;" title="Conversiones">
                    <i class="fas fa-exchange-alt"></i>
                </a>

                {{-- Botón Editar --}}
                <a href="{{ route('admin.materials.edit', $material->id) }}"
                    class="btn btn-warning btn-sm d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;" title="Editar">
                    <i class="fas fa-edit text-white"></i>
                </a>

                {{-- Botón Eliminar --}}
                <a href="{{ route('admin.materials.confirm_delete', $material->id) }}"
                    class="btn btn-danger btn-sm d-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </td>
    </tr>
@endforeach
