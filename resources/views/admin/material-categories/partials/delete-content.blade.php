<div class="card card-danger mb-0">
    <div class="card-header">
        <h3 class="card-title text-white" style="font-weight: 700; font-size: 18px;">
            <i class="fas fa-exclamation-triangle mr-2"></i>ELIMINAR CATEGORÍA DE MATERIAL
        </h3>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="card-body pt-3 pb-2">
        <input type="hidden" id="delete_category_id" value="{{ $category->id }}">

        <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
            <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                <i class="fas fa-layer-group" style="margin-right: 10px;"></i>
                Datos de la Categoría
            </h5>
        </div>

        <div class="form-group mb-2">
            <label class="small text-muted mb-1">Nombre</label>
            <input type="text" class="form-control form-control-sm" value="{{ $category->name }}"
                disabled style="font-weight: 600; color: #1f2937;">
        </div>

        <div class="form-group mb-2">
            <label class="small text-muted mb-1">Descripción</label>
            <textarea class="form-control form-control-sm" rows="2" disabled style="background-color: #f8fafc;">{{ $category->description }}</textarea>
        </div>

        @if($category->materials_count > 0)
            <div class="alert alert-danger text-center mt-3">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p class="mb-0" style="font-weight: bold;">
                    Esta categoría tiene <strong>{{ $category->materials_count }}</strong> materiales asociados.
                </p>
            </div>
        @endif

        <div class="alert alert-warning text-center mt-3">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar esta categoría?</p>
        </div>

        <div class="text-right mt-4">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fas fa-times-circle"></i> Cancelar
            </button>
            <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>
