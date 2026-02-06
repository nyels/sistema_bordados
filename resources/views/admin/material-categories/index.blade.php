@extends('adminlte::page')

@section('title', 'Categorías de Materiales')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
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
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-layer-group"></i> CATEGORÍAS DE MATERIALES
            </h3>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between">
                    <button type="button" class="btn btn-primary" id="btnNuevo">
                        Nuevo <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#manageUnitsModal"
                        title="Define qué empaques de compra están disponibles para cada categoría">
                        <i class="fas fa-box"></i> Empaques por Categoría
                    </button>
                </div>
            </div>
            <hr>

            <div class="table-responsive" id="mainTableContainer">
                @include('admin.material-categories.partials.table')
            </div>
        </div>
    </div>

    {{-- MODAL NUEVO --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus-circle mr-2"></i> NUEVA CATEGORÍA DE MATERIAL
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div class="row">
                            {{-- COLUMNA IZQUIERDA: DATOS BÁSICOS --}}
                            <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 25px;">
                                <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                                    <h5 style="color: #007bff; font-weight: 600;">
                                        <i class="fas fa-layer-group"></i> Datos Básicos
                                    </h5>
                                </div>

                                <div class="form-group">
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="create_name"
                                        class="form-control form-control-sm" maxlength="50" required
                                        placeholder="Ej: Telas, Hilos, Cintas">
                                    <div class="invalid-feedback" id="create_error_name"></div>
                                </div>

                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="description" id="create_description" class="form-control form-control-sm" rows="3"
                                        maxlength="500" placeholder="Descripción opcional..."></textarea>
                                    <div class="invalid-feedback" id="create_error_description"></div>
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
                                    <select name="default_inventory_unit_id" id="create_default_inventory_unit_id"
                                        class="form-control form-control-sm">
                                        <option value="">-- Sin definir (flexible) --</option>
                                        @foreach ($inventoryUnits as $unit)
                                            <option value="{{ $unit->id }}">
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Ejemplo: HILOS → Metro, BOTONES → Pieza
                                    </small>
                                </div>

                                {{-- INFO: Configuración de Presentaciones --}}
                                <div class="alert alert-info mt-3" style="background-color: #17a2b8; border-color: #17a2b8; color: #fff;">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Siguiente paso:</strong> Después de crear la categoría, podrás
                                    configurar las presentaciones de compra permitidas (Cono, Rollo, etc.)
                                    en el módulo de <em>Unidades por Categoría</em>.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSaveCreate">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDITAR --}}
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-edit mr-2"></i> EDITAR CATEGORÍA DE MATERIAL
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEdit">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id">

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
                                        class="form-control form-control-sm" maxlength="50" required>
                                    <div class="invalid-feedback" id="edit_error_name"></div>
                                </div>

                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="description" id="edit_description" class="form-control form-control-sm" rows="3"
                                        maxlength="500"></textarea>
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
                                            <option value="{{ $unit->id }}">
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Ejemplo: HILOS → Metro, BOTONES → Pieza
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
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
    </div>

    {{-- MODAL ELIMINAR --}}
    <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-trash mr-2"></i> ELIMINAR CATEGORÍA DE MATERIAL
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">

                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-layer-group" style="margin-right: 10px;"></i>
                            Datos de la Categoría
                        </h5>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small text-muted mb-1">Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_name"
                            disabled style="font-weight: 600; color: #1f2937;">
                    </div>

                    <div class="form-group mb-2">
                        <label class="small text-muted mb-1">Descripción</label>
                        <textarea class="form-control form-control-sm" id="delete_description" rows="2" disabled style="background-color: #f8fafc;"></textarea>
                    </div>

                    <div class="alert alert-danger text-center mt-3" id="delete_materials_warning" style="display: none;">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold;">
                            Esta categoría tiene <strong id="delete_materials_count">0</strong> materiales asociados.
                        </p>
                    </div>

                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar esta categoría?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para listar materiales --}}
    <div class="modal fade" id="materialsModal" tabindex="-1" role="dialog" aria-labelledby="materialsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: none; overflow: hidden;">
                <div class="modal-header bg-primary text-white justify-content-center position-relative">
                    <h5 class="modal-title font-weight-bold" id="materialsModalLabel" style="font-size: 1.5rem;">NOMBRE
                        CATEGORIA</h5>
                    <button type="button" class="close position-absolute text-white" data-dismiss="modal"
                        aria-label="Close" style="right: 15px; top: 15px;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="text-center text-muted mb-3 font-weight-bold"
                        style="text-transform: uppercase; letter-spacing: 1px;">
                        Materiales Asociados
                    </h6>
                    <div class="table-responsive d-flex justify-content-center">
                        <table class="table table-bordered table-striped table-hover text-center" id="materialsTable"
                            style="width: 80%;">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 10%;">#</th>
                                    <th>Nombre del Material</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Contenido dinámico --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary px-5 rounded-pill shadow-sm" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL GESTIÓN DE UNIDADES (GLOBAL) --}}
    <div class="modal fade" id="manageUnitsModal" tabindex="-1" role="dialog" aria-labelledby="manageUnitsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content shadow-lg" style="border-radius: 15px; border: none;">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold" id="manageUnitsModalLabel">
                        <i class="fas fa-box mr-2"></i> Empaques de Compra Permitidos
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
                    {{-- EXPLICACIÓN DEL MÓDULO --}}
                    <div class="alert alert-light border-left border-info mb-3" style="border-left-width: 4px !important;">
                        <small>
                            <i class="fas fa-info-circle text-info"></i>
                            <strong>¿Qué son los empaques permitidos?</strong><br>
                            Controlan qué tipos de empaque (Cono, Caja, Rollo, etc.) puede usar cada categoría al crear materiales.
                            <br>
                            <span class="text-muted">Ejemplo: Categoría "Hilos" permite comprar en CONO o CARRETE.</span>
                        </small>
                    </div>
                    {{-- SECCIÓN SUPERIOR: FORMULARIO DE ASIGNACIÓN --}}
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0">
                            <h6 class="font-weight-bold text-primary mb-0">
                                <i class="fas fa-plus-circle mr-2"></i> Asignar Nueva Unidad
                            </h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-muted text-uppercase">1. Seleccionar
                                            Categoría</label>
                                        <select id="modal_category_select" class="form-control form-control-lg shadow-sm">
                                            <option value="">-- Elige una Categoría --</option>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold small text-muted text-uppercase">2. Unidad de
                                            Compra</label>
                                        <select id="modal_unit_select" class="form-control form-control-lg shadow-sm"
                                            disabled>
                                            <option value="">Primero selecciona categoría...</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" id="btn_assign_unit"
                                        class="btn btn-success btn-lg btn-block shadow-sm font-weight-bold" disabled>
                                        <i class="fas fa-save mr-1"></i> Asignar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN INFERIOR: TABLA DE UNIDADES ASIGNADAS --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="fas fa-list-ul mr-2"></i> Unidades Permitidas para: <span
                                    id="current_category_label" class="text-primary font-italic">---</span>
                            </h6>
                            <span class="badge badge-light border" id="units_count_badge">0 Unidades</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Unidad</th>
                                            <th>Símbolo</th>
                                            <th>Uso (Materiales)</th>
                                            <th style="width: 15%;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="assigned_units_table_body">
                                        <tr>
                                            <td colspan="4" class="text-muted py-4">
                                                <i class="fas fa-arrow-up mr-2"></i> Selecciona una categoría arriba para
                                                ver sus unidades.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        #example1_wrapper .btn {
            color: #fff;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
        }
        .btn-danger { background-color: #dc3545; border: none; }
        .btn-success { background-color: #28a745; border: none; }
        .btn-info { background-color: #17a2b8; border: none; }
        .btn-warning { background-color: #ffc107; color: #212529; border: none; }
        .btn-default { background-color: #6e7176; color: #212529; border: none; }
        .modal-header .close { opacity: 1; }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // =========================================================
            // DATATABLE
            // =========================================================
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorías",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Categorías",
                    "infoFiltered": "(Filtrado de _MAX_ total Categorías)",
                    "lengthMenu": "Mostrar _MENU_ Categorías",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                buttons: [
                    { text: '<i class="fas fa-copy"></i> COPIAR', extend: 'copy', className: 'btn btn-default' },
                    { text: '<i class="fas fa-file-pdf"></i> PDF', extend: 'pdf', className: 'btn btn-danger' },
                    { text: '<i class="fas fa-file-csv"></i> CSV', extend: 'csv', className: 'btn btn-info' },
                    { text: '<i class="fas fa-file-excel"></i> EXCEL', extend: 'excel', className: 'btn btn-success' },
                    { text: '<i class="fas fa-print"></i> IMPRIMIR', extend: 'print', className: 'btn btn-default' }
                ]
            });
            table.buttons().container().appendTo('#example1_wrapper .row:eq(0)');

            // =========================================================
            // NUEVO - Modal de creación AJAX
            // =========================================================
            $('#btnNuevo').on('click', function() {
                $('#create_name').val('').removeClass('is-invalid');
                $('#create_description').val('');
                $('#create_default_inventory_unit_id').val('');
                $('#create_error_name').text('');
                $('#create_error_description').text('');
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $btn = $('#btnSaveCreate');

                // Limpiar errores previos
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                var name = $('#create_name').val().trim();
                if (!name) {
                    $('#create_name').addClass('is-invalid');
                    $('#create_error_name').text('El nombre es obligatorio');
                    return;
                }

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '{{ route("admin.material-categories.store") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: $('#create_name').val(),
                        description: $('#create_description').val(),
                        default_inventory_unit_id: $('#create_default_inventory_unit_id').val()
                    },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Guardado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            if (errors) {
                                $.each(errors, function(field, messages) {
                                    $('#create_' + field).addClass('is-invalid');
                                    $('#create_error_' + field).text(messages[0]);
                                });
                            } else if (xhr.responseJSON.error) {
                                $('#create_name').addClass('is-invalid');
                                $('#create_error_name').text(xhr.responseJSON.error);
                            }
                        } else {
                            $('#modalCreate').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al guardar' });
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                    }
                });
            });

            // =========================================================
            // EDITAR - Llenar modal con data attributes
            // =========================================================
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description') || '';
                var unit = $(this).data('unit') || '';

                $('#edit_id').val(id);
                $('#edit_name').val(name).removeClass('is-invalid');
                $('#edit_description').val(description);
                $('#edit_default_inventory_unit_id').val(unit);
                $('#edit_error_name').text('');
                $('#edit_error_description').text('');

                $('#modalEdit').modal('show');
            });

            // Submit del formulario de edición via AJAX
            $('#formEdit').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var id = $('#edit_id').val();
                var $btn = $('#btnSaveEdit');

                // Limpiar errores previos
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                var name = $('#edit_name').val().trim();
                if (!name) {
                    $('#edit_name').addClass('is-invalid');
                    $('#edit_error_name').text('El nombre es obligatorio');
                    return;
                }

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '/admin/material-categories/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: $('#edit_name').val(),
                        description: $('#edit_description').val(),
                        default_inventory_unit_id: $('#edit_default_inventory_unit_id').val()
                    },
                    success: function(response) {
                        $('#modalEdit').modal('hide');
                        if (response.success) {
                            // Actualizar la fila en la tabla
                            var row = $('tr[data-id="' + id + '"]');
                            row.find('.category-name').text(response.data.name);
                            row.find('.btn-edit').data('name', response.data.name);
                            row.find('.btn-edit').data('description', response.data.description || '');
                            row.find('.btn-edit').data('unit', response.data.default_inventory_unit_id || '');
                            row.find('.btn-delete').data('name', response.data.name);
                            row.find('.btn-delete').data('description', response.data.description || '');

                            Swal.fire({
                                icon: response.type || 'success',
                                title: response.type === 'info' ? 'Aviso' : 'Actualizado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                $('#edit_' + field).addClass('is-invalid');
                                $('#edit_error_' + field).text(messages[0]);
                            });
                        } else {
                            $('#modalEdit').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al actualizar' });
                        }
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Actualizar');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Actualizar');
                    }
                });
            });

            // =========================================================
            // ELIMINAR - Llenar modal con data attributes
            // =========================================================
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description') || '';
                var materials = $(this).data('materials') || 0;

                $('#delete_id').val(id);
                $('#delete_name').val(name);
                $('#delete_description').val(description);

                if (materials > 0) {
                    $('#delete_materials_count').text(materials);
                    $('#delete_materials_warning').show();
                } else {
                    $('#delete_materials_warning').hide();
                }

                $('#modalDelete').modal('show');
            });

            // Confirmar eliminación
            $('#btnConfirmDelete').on('click', function() {
                var id = $('#delete_id').val();

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede revertir",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#modalDelete').modal('hide');
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });

                        $.ajax({
                            url: '/admin/material-categories/' + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.success) {
                                    var row = $('tr[data-id="' + id + '"]');
                                    table.row(row).remove().draw(false);
                                    Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message, timer: 2000, showConfirmButton: false });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al eliminar' });
                            }
                        });
                    }
                });
            });

            // =========================================================
            // MOSTRAR MATERIALES
            // =========================================================
            $(document).on('click', '.btn-show-materials', function() {
                var categoryId = $(this).data('id');
                var categoryName = $(this).data('name');

                $('#materialsModalLabel').text(categoryName);
                var tbody = $('#materialsTable tbody');
                tbody.html('<tr><td colspan="2"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
                $('#materialsModal').modal('show');

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/get-materials',
                    type: 'GET',
                    success: function(response) {
                        tbody.empty();
                        if (response.length > 0) {
                            $.each(response, function(index, material) {
                                tbody.append('<tr><td>' + (index + 1) + '</td><td class="font-weight-bold text-dark">' + material.name + '</td></tr>');
                            });
                        } else {
                            tbody.html('<tr><td colspan="2" class="text-muted">Sin materiales asociados</td></tr>');
                        }
                    },
                    error: function() {
                        tbody.html('<tr><td colspan="2" class="text-danger">Error al cargar datos</td></tr>');
                    }
                });
            });

            // =========================================================
            // LÓGICA GESTIÓN DE UNIDADES (MODAL)
            // =========================================================
            const $categorySelect = $('#modal_category_select');
            const $unitSelect = $('#modal_unit_select');
            const $assignBtn = $('#btn_assign_unit');
            const $tableBody = $('#assigned_units_table_body');
            const $categoryLabel = $('#current_category_label');
            const $countBadge = $('#units_count_badge');

            $categorySelect.on('change', function() {
                const categoryId = $(this).val();
                const categoryName = $(this).find('option:selected').text();

                if (!categoryId) {
                    resetUnitManager();
                    return;
                }

                $categoryLabel.text(categoryName);
                loadCategoryDetails(categoryId);
            });

            function resetUnitManager() {
                $categoryLabel.text('---');
                $unitSelect.html('<option value="">Primero selecciona categoría...</option>').prop('disabled', true);
                $assignBtn.prop('disabled', true);
                $tableBody.html('<tr><td colspan="4" class="text-muted py-4"><i class="fas fa-arrow-up mr-2"></i> Selecciona una categoría arriba.</td></tr>');
                $countBadge.text('0 Unidades');
            }

            function loadCategoryDetails(categoryId) {
                $tableBody.html('<tr><td colspan="4" class="py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></td></tr>');
                $unitSelect.prop('disabled', true).html('<option>Cargando...</option>');

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/assigned-units',
                    type: 'GET',
                    success: function(res) {
                        if (res.success) {
                            renderAssignedUnits(res.units);
                            $countBadge.text(res.total_units + ' Unidades');
                        }
                    }
                });

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/available-units',
                    type: 'GET',
                    success: function(res) {
                        if (res.success) {
                            populateUnitSelect(res.units);
                        }
                    }
                });
            }

            function renderAssignedUnits(units) {
                $tableBody.empty();
                if (units.length === 0) {
                    $tableBody.html('<tr><td colspan="4" class="text-muted font-italic">No hay unidades asignadas aún.</td></tr>');
                    return;
                }

                units.forEach(unit => {
                    let btnHtml = unit.can_remove
                        ? '<button class="btn btn-danger btn-sm btn-remove-unit shadow-sm" data-id="' + unit.id + '" data-name="' + unit.name + '"><i class="fas fa-trash"></i></button>'
                        : '<span class="text-muted" title="' + unit.materials_using + ' materiales usan esta unidad"><i class="fas fa-lock"></i></span>';

                    $tableBody.append('<tr><td class="font-weight-bold text-dark">' + unit.name + '</td><td><span class="badge badge-secondary px-2">' + unit.symbol + '</span></td><td>' + (unit.materials_using > 0 ? '<span class="badge badge-info">' + unit.materials_using + '</span>' : '<span class="text-muted">-</span>') + '</td><td>' + btnHtml + '</td></tr>');
                });
            }

            function populateUnitSelect(units) {
                $unitSelect.empty();
                if (units.length === 0) {
                    $unitSelect.append('<option value="">No hay más unidades disponibles</option>');
                    $unitSelect.prop('disabled', true);
                    $assignBtn.prop('disabled', true);
                } else {
                    $unitSelect.append('<option value="">Selecciona una unidad...</option>');
                    units.forEach(unit => {
                        $unitSelect.append('<option value="' + unit.id + '">' + unit.name + ' (' + unit.symbol + ')</option>');
                    });
                    $unitSelect.prop('disabled', false);
                }
            }

            $unitSelect.on('change', function() {
                $assignBtn.prop('disabled', !$(this).val());
            });

            $assignBtn.on('click', function() {
                const categoryId = $categorySelect.val();
                const unitId = $unitSelect.val();
                if (!categoryId || !unitId) return;

                $assignBtn.html('<i class="fas fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/units',
                    type: 'POST',
                    data: { unit_id: unitId, _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire({ icon: 'success', title: 'Guardado', text: 'Unidad asignada correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        loadCategoryDetails(categoryId);
                        $assignBtn.html('<i class="fas fa-save mr-1"></i> Asignar');
                        refreshMainTable();
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al guardar', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                        $assignBtn.html('<i class="fas fa-save mr-1"></i> Asignar').prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.btn-remove-unit', function() {
                const categoryId = $categorySelect.val();
                const unitId = $(this).data('id');
                const unitName = $(this).data('name');

                Swal.fire({
                    title: '¿Quitar unidad?',
                    text: 'Se desvinculará la unidad "' + unitName + '" de esta categoría.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, quitar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/material-categories/' + categoryId + '/units/' + unitId,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Swal.fire({ icon: 'success', title: 'Eliminado', text: 'La unidad fue desvinculada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                                loadCategoryDetails(categoryId);
                                refreshMainTable();
                            },
                            error: function(xhr) {
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'No se pudo eliminar', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                            }
                        });
                    }
                });
            });

            function refreshMainTable() {
                if ($.fn.DataTable.isDataTable('#example1')) {
                    $('#example1').DataTable().destroy();
                }
                $.get(window.location.href, function(html) {
                    var $html = $(html);
                    $("#mainTableContainer").html($html.find('#mainTableContainer').html());
                    table = $("#example1").DataTable({
                        "pageLength": 10,
                        "language": {
                            "emptyTable": "No hay información",
                            "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorías",
                            "infoEmpty": "Mostrando 0 a 0 de 0 Categorías",
                            "infoFiltered": "(Filtrado de _MAX_ total Categorías)",
                            "lengthMenu": "Mostrar _MENU_ Categorías",
                            "loadingRecords": "Cargando...",
                            "processing": "Procesando...",
                            "search": "Buscador:",
                            "zeroRecords": "Sin resultados encontrados",
                            "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
                        },
                        "responsive": true,
                        "lengthChange": true,
                        "autoWidth": false,
                        buttons: [
                            { text: '<i class="fas fa-copy"></i> COPIAR', extend: 'copy', className: 'btn btn-default' },
                            { text: '<i class="fas fa-file-pdf"></i> PDF', extend: 'pdf', className: 'btn btn-danger' },
                            { text: '<i class="fas fa-file-csv"></i> CSV', extend: 'csv', className: 'btn btn-info' },
                            { text: '<i class="fas fa-file-excel"></i> EXCEL', extend: 'excel', className: 'btn btn-success' },
                            { text: '<i class="fas fa-print"></i> IMPRIMIR', extend: 'print', className: 'btn btn-default' }
                        ]
                    });
                    table.buttons().container().appendTo('#example1_wrapper .row:eq(0)');
                });
            }
        });
    </script>
@stop
