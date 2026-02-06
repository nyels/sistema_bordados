<table id="example1" class="table table-bordered table-hover text-center">
    <thead class="thead-dark text-center">
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Unidad Base</th>
            <th>Unidades Permitidas</th>
            <th>Materiales</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($categories as $category)
            <tr data-id="{{ $category->id }}">
                <td>{{ $loop->iteration }}</td>
                <td class="category-name">{{ $category->name }}</td>
                <td>{{ $category->description ?? 'N/A' }}</td>
                <td>
                    @if ($category->defaultInventoryUnit)
                        <span class="badge badge-primary">
                            {{ $category->defaultInventoryUnit->name }}
                            ({{ $category->defaultInventoryUnit->symbol }})
                        </span>
                    @else
                        <span class="text-muted small">N/A</span>
                    @endif
                </td>
                <td>
                    @foreach ($category->allowedUnits as $unit)
                        <span class="badge badge-secondary">{{ $unit->name }}</span>
                    @endforeach
                    @if ($category->allowedUnits->isEmpty())
                        <span class="text-muted small">Sin asignar</span>
                    @endif
                </td>
                <td>
                    <button class="btn btn-info btn-sm btn-show-materials" data-id="{{ $category->id }}"
                        data-name="{{ $category->name }}" style="font-size: 1rem; font-weight: bold;">
                        {{ $category->materials_count }}
                    </button>
                </td>
                <td>
                    <div class="d-flex justify-content-center align-items-center gap-1">
                        <button type="button" class="btn btn-warning btn-sm btn-edit"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-description="{{ $category->description }}"
                            data-unit="{{ $category->default_inventory_unit_id }}"
                            title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-delete"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-description="{{ $category->description }}"
                            data-materials="{{ $category->materials_count }}"
                            title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
