@extends('adminlte::page')

@section('title', 'Unidades por Categoría')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info', 'warning'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    <div class="card card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-link"></i> UNIDADES PERMITIDAS POR CATEGORÍA
            </h3>
            <span class="badge badge-secondary" style="font-size: 0.9rem;">
                <i class="fas fa-cog"></i> Configuración Avanzada
            </span>
        </div>

        <div class="card-body">
            {{-- NOTA DE VISTA AVANZADA --}}
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-shield-alt"></i> Configuración Solo Administradores</strong>
                <p class="mb-0 mt-2">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    <strong>Cambios aquí afectan la creación de materiales.</strong>
                    <br>
                    Los empaques que configure aquí determinan qué opciones verán los usuarios al crear nuevos materiales en cada categoría.
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb text-primary"></i>
                        <strong>Acceso rápido:</strong> También puede usar el modal "Empaques por Categoría" desde la lista de categorías.
                    </small>
                </p>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>

            {{-- INFORMACIÓN DEL MÓDULO --}}
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-box"></i> Empaques de Compra por Categoría</strong>
                <p class="mb-0 mt-2">
                    Define en qué <strong>empaques o contenedores</strong> (Caja, Cono, Rollo, Paquete) se compran los materiales de cada categoría.
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-lightbulb text-warning"></i>
                        Las unidades de inventario (Metro, Pieza, etc.) se configuran en cada categoría, no aquí.
                    </small>
                </p>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Categorías
                    </a>
                </div>
            </div>
            <hr>

            {{-- TABLA DE CATEGORÍAS CON UNIDADES --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="categoriesTable">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 20%;">Categoría</th>
                            <th style="width: 25%;">Descripción</th>
                            <th style="width: 30%;">Presentaciones de Compra</th>
                            <th style="width: 10%;">Materiales</th>
                            <th style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr data-category-id="{{ $category->id }}">
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <strong style="font-size: 1.1rem;">{{ $category->name }}</strong>
                                    @if ($category->defaultInventoryUnit)
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-ruler"></i> Inventario: {{ $category->defaultInventoryUnit->symbol }}
                                        </small>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if ($category->description)
                                        <span style="font-size: 1rem;">{{ $category->description }}</span>
                                    @else
                                        <span class="text-muted font-italic">Sin descripción</span>
                                    @endif
                                </td>
                                <td class="units-cell" data-category-id="{{ $category->id }}">
                                    @if ($category->allowedUnits->isEmpty())
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Sin unidades asignadas
                                        </span>
                                    @else
                                        <div class="d-flex flex-wrap gap-1" id="units-container-{{ $category->id }}">
                                            @foreach ($category->allowedUnits as $unit)
                                                @php
                                                    $materialsUsing = \App\Models\Material::where(
                                                        'material_category_id',
                                                        $category->id,
                                                    )
                                                        ->where('base_unit_id', $unit->id)
                                                        ->where('activo', true)
                                                        ->count();
                                                    $canRemove = $materialsUsing === 0;
                                                @endphp
                                                <span class="badge badge-primary unit-badge mr-1 mb-1"
                                                    data-unit-id="{{ $unit->id }}"
                                                    data-materials-using="{{ $materialsUsing }}"
                                                    style="font-size: 0.9rem; padding: 8px 12px;">
                                                    {{ $unit->name }}
                                                    <small>({{ $unit->symbol }})</small>
                                                    @if ($materialsUsing > 0)
                                                        <span class="badge badge-light ml-1"
                                                            title="{{ $materialsUsing }} material(es) usando esta unidad">
                                                            {{ $materialsUsing }}
                                                        </span>
                                                    @endif
                                                    @if ($canRemove)
                                                        <button type="button" class="btn btn-xs btn-remove-unit ml-1"
                                                            data-category-id="{{ $category->id }}"
                                                            data-unit-id="{{ $unit->id }}"
                                                            data-unit-name="{{ $unit->name }}" title="Quitar unidad"
                                                            style="background: none; border: none; color: #fff; padding: 0 4px;">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @else
                                                        <span class="ml-1" title="No se puede quitar: {{ $materialsUsing }} material(es) usando esta unidad">
                                                            <i class="fas fa-lock" style="font-size: 0.7rem;"></i>
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-info" style="font-size: 1rem;">
                                        {{ $category->materials_count }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-success btn-sm btn-add-unit"
                                        data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}"
                                        title="Agregar unidad">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No hay categorías de materiales registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- LEYENDA --}}
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <small>
                                <strong>Leyenda:</strong>
                                <span class="badge badge-primary mr-2">Unidad asignada</span>
                                <span class="badge badge-light border mr-2">N</span> = Materiales usando esta unidad
                                <span class="ml-3"><i class="fas fa-lock"></i></span> = Bloqueada (hay materiales asociados)
                                <span class="ml-3"><i class="fas fa-times"></i></span> = Quitar unidad
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA AGREGAR UNIDAD --}}
    <div class="modal fade" id="addUnitModal" tabindex="-1" role="dialog" aria-labelledby="addUnitModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 15px; border: none;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="addUnitModalLabel">
                        <i class="fas fa-box"></i> Agregar Presentación de Compra
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal-category-id">
                    <div class="form-group">
                        <label class="font-weight-bold">Categoría:</label>
                        <p id="modal-category-name" class="form-control-plaintext font-weight-bold text-primary"></p>
                    </div>
                    <div class="form-group">
                        <label for="modal-unit-select" class="font-weight-bold">
                            Seleccionar Empaque/Contenedor: <span class="text-danger">*</span>
                        </label>
                        <select id="modal-unit-select" class="form-control">
                            <option value="">Cargando...</option>
                        </select>
                        <small class="text-muted" id="modal-unit-help">
                            <i class="fas fa-magic text-primary"></i> Filtrado inteligente según la unidad de inventario de la categoría.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="btn-confirm-add-unit">
                        <i class="fas fa-check"></i> Agregar Unidad
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMACIÓN PARA ELIMINAR --}}
    <div class="modal fade" id="removeUnitModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 15px; border: none;">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="remove-category-id">
                    <input type="hidden" id="remove-unit-id">
                    <p class="mb-0">
                        ¿Está seguro de quitar la unidad <strong id="remove-unit-name"></strong>?
                    </p>
                    <small class="text-muted">Esta acción solo desvincula la unidad de la categoría.</small>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-remove-unit">
                        <i class="fas fa-trash"></i> Sí, Quitar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .unit-badge {
            display: inline-flex;
            align-items: center;
        }

        .btn-remove-unit:hover {
            color: #ffc107 !important;
        }

        .units-cell .badge {
            transition: all 0.2s ease;
        }

        .units-cell .badge:hover {
            transform: scale(1.05);
        }

        #categoriesTable tbody tr:hover {
            background-color: #f8f9fa;
        }

        .gap-1 {
            gap: 0.25rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // =====================================================
            // AGREGAR UNIDAD
            // =====================================================
            $('.btn-add-unit').on('click', function() {
                var categoryId = $(this).data('category-id');
                var categoryName = $(this).data('category-name');

                $('#modal-category-id').val(categoryId);
                $('#modal-category-name').text(categoryName);

                // Cargar unidades disponibles
                var $select = $('#modal-unit-select');
                $select.html('<option value="">Cargando...</option>').prop('disabled', true);

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/available-units',
                    type: 'GET',
                    success: function(response) {
                        $select.empty();
                        if (response.units && response.units.length > 0) {
                            $select.append('<option value="">Seleccionar unidad...</option>');
                            $.each(response.units, function(i, unit) {
                                $select.append(
                                    '<option value="' + unit.id + '">' +
                                    unit.name + ' (' + unit.symbol + ')' +
                                    '</option>'
                                );
                            });
                            $select.prop('disabled', false);
                        } else {
                            $select.html(
                                '<option value="">No hay unidades disponibles</option>');
                        }
                    },
                    error: function() {
                        $select.html('<option value="">Error al cargar unidades</option>');
                    }
                });

                $('#addUnitModal').modal('show');
            });

            // Confirmar agregar unidad
            $('#btn-confirm-add-unit').on('click', function() {
                var categoryId = $('#modal-category-id').val();
                var unitId = $('#modal-unit-select').val();

                if (!unitId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo requerido',
                        text: 'Debe seleccionar una unidad',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/units',
                    type: 'POST',
                    data: {
                        unit_id: unitId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Recargar la página para mostrar los cambios
                            location.reload();
                        } else {
                            alert(response.message || 'Error al agregar la unidad.');
                            $btn.prop('disabled', false).html(
                                '<i class="fas fa-check"></i> Agregar Unidad');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Error al agregar la unidad.';
                        alert(msg);
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-check"></i> Agregar Unidad');
                    }
                });
            });

            // =====================================================
            // ELIMINAR UNIDAD
            // =====================================================
            $(document).on('click', '.btn-remove-unit', function() {
                var categoryId = $(this).data('category-id');
                var unitId = $(this).data('unit-id');
                var unitName = $(this).data('unit-name');

                $('#remove-category-id').val(categoryId);
                $('#remove-unit-id').val(unitId);
                $('#remove-unit-name').text(unitName);

                $('#removeUnitModal').modal('show');
            });

            // Confirmar eliminar unidad
            $('#btn-confirm-remove-unit').on('click', function() {
                var categoryId = $('#remove-category-id').val();
                var unitId = $('#remove-unit-id').val();

                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Eliminando...');

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/units/' + unitId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Recargar la página
                            location.reload();
                        } else {
                            alert(response.message || 'Error al eliminar la unidad.');
                            $btn.prop('disabled', false).html(
                                '<i class="fas fa-trash"></i> Sí, Quitar');
                            $('#removeUnitModal').modal('hide');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Error al eliminar la unidad.';
                        alert(msg);
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-trash"></i> Sí, Quitar');
                        $('#removeUnitModal').modal('hide');
                    }
                });
            });
        });
    </script>
@stop
