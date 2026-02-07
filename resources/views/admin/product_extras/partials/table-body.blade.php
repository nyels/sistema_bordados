@foreach ($extras as $extra)
    <tr data-id="{{ $extra->id }}">
        <td>{{ $loop->iteration }}</td>
        <td class="extra-name">{{ $extra->name }}</td>
        <td>{{ $extra->category->nombre ?? '-' }}</td>
        <td>{!! $extra->materials_summary !!}</td>
        <td>${{ number_format($extra->cost_addition, 2) }}</td>
        <td>${{ number_format($extra->price_addition, 2) }}</td>
        <td>{{ $extra->formatted_minutes }}</td>
        <td class="text-center">
            @php
                $materialsData = $extra->materials->map(function($m) {
                    return [
                        'id' => $m->pivot->material_variant_id,
                        'quantity' => $m->pivot->quantity_required,
                        'name' => ($m->material->name ?? 'Material') . ($m->color ? ' - ' . $m->color : ''),
                        'unit' => $m->material->consumptionUnit->symbol ?? ''
                    ];
                });
            @endphp
            <div class="d-flex justify-content-center align-items-center gap-1">
                <button type="button" class="btn btn-warning btn-sm btn-edit"
                    data-id="{{ $extra->id }}"
                    data-name="{{ $extra->name }}"
                    data-category="{{ $extra->extra_category_id }}"
                    data-cost="{{ $extra->cost_addition }}"
                    data-price="{{ $extra->price_addition }}"
                    data-minutes="{{ $extra->minutes_addition }}"
                    data-consumes="{{ $extra->consumes_inventory ? 1 : 0 }}"
                    data-materials="{{ $materialsData->toJson() }}"
                    title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm btn-delete"
                    data-id="{{ $extra->id }}"
                    data-name="{{ $extra->name }}" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@endforeach
