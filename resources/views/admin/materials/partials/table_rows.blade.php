@foreach ($materials as $material)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>
            <span class="badge badge-primary">{{ $material->category->name ?? 'N/A' }}</span>
        </td>
        <td>
            <strong>{{ $material->name }}</strong>
            @if ($material->description)
                <br><small style="color: #333;">{{ Str::limit($material->description, 50) }}</small>
            @endif
        </td>

        <td>{{ $material->composition ?? '-' }}</td>
        <td>
            <span class="badge badge-secondary" style="font-size: 14px; padding: 6px 10px;">
                {{ $material->category->baseUnit->symbol ?? 'N/A' }}
            </span>
        </td>
        <td class="text-center">
            <span class="badge badge-info" style="font-size: 14px; padding: 6px 10px;">
                {{ $material->active_variants_count }}
            </span>
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
