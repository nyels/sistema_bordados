<div class="card card-warning mb-0">
    <div class="card-header">
        <h3 class="card-title" style="font-weight: bold;font-size: 18px;">
            <i class="fas fa-edit"></i> EDITAR CATEGORÍA DE MATERIAL
        </h3>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="card-body">
        <form id="formEditCategory" method="POST" action="{{ route('admin.material-categories.update', $category->id) }}">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_category_id" value="{{ $category->id }}">

            <div class="row">
                {{-- COLUMNA IZQUIERDA: DATOS BÁSICOS --}}
                <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 25px;">
                    <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #856404; font-weight: 600;">
                            <i class="fas fa-folder"></i> Datos Básicos
                        </h5>
                    </div>

                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name"
                            class="form-control form-control-sm"
                            value="{{ $category->name }}" maxlength="50" required>
                        <div class="invalid-feedback" id="edit_error_name"></div>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="description" id="edit_description" class="form-control form-control-sm" rows="3"
                            maxlength="500">{{ $category->description }}</textarea>
                        <div class="invalid-feedback" id="edit_error_description"></div>
                    </div>
                </div>

                {{-- COLUMNA DERECHA: CONFIGURACIÓN DE INVENTARIO --}}
                <div class="col-md-6" style="padding-left: 25px;">
                    <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #28a745; font-weight: 600;">
                            <i class="fas fa-warehouse"></i> Configuración de Inventario
                        </h5>
                    </div>

                    <div class="form-group">
                        <label>
                            Unidad de Inventario por Defecto
                            <i class="fas fa-question-circle text-muted" data-toggle="tooltip"
                                title="El sistema controlará existencias de los materiales de esta categoría en esta unidad."></i>
                        </label>
                        <select name="default_inventory_unit_id" id="edit_default_inventory_unit_id"
                            class="form-control form-control-sm">
                            <option value="">-- Sin definir (flexible) --</option>
                            @foreach ($inventoryUnits as $unit)
                                <option value="{{ $unit->id }}"
                                    {{ $category->default_inventory_unit_id == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Ejemplo: HILOS → Metro, BOTONES → Pieza
                        </small>
                    </div>

                    <input type="hidden" name="allow_unit_override" value="1">

                    {{-- RESUMEN DE UNIDADES PERMITIDAS --}}
                    <div class="card bg-light mt-3">
                        <div class="card-body py-2">
                            <strong><i class="fas fa-shopping-cart"></i> Presentaciones de Compra:</strong>
                            <div class="mt-2">
                                @if ($category->allowedUnits->isEmpty())
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Sin configurar
                                    </span>
                                @else
                                    @foreach ($category->allowedUnits as $unit)
                                        <span class="badge badge-primary mr-1">
                                            {{ $unit->name }} ({{ $unit->symbol }})
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-warning" id="btnSaveEdit">
                    <i class="fas fa-save"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
