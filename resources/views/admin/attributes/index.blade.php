@extends('adminlte::page')

@section('title', 'Atributos')

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

    <div class="row">

        {{-- ================== COLUMNA ATRIBUTOS ================== --}}
        <div class="col-12 col-lg-6">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">ATRIBUTOS</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="btnNuevoAtributo">
                            Nuevo Atributo <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="tableAtributos" class="table table-bordered table-hover text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attributes as $attribute)
                                    <tr data-id="{{ $attribute->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="attr-nombre">{{ $attribute->name }}</td>
                                        <td class="attr-slug"><code>{{ $attribute->slug }}</code></td>
                                        <td class="attr-tipo">
                                            @switch($attribute->type)
                                                @case('select')
                                                    <span class="badge badge-primary">Selector</span>
                                                @break
                                                @case('color')
                                                    <span class="badge badge-warning">Color</span>
                                                @break
                                                @case('text')
                                                    <span class="badge badge-secondary">Texto</span>
                                                @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm btn-edit-attr"
                                                data-id="{{ $attribute->id }}"
                                                data-nombre="{{ $attribute->name }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-attr"
                                                data-id="{{ $attribute->id }}"
                                                data-nombre="{{ $attribute->name }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        {{-- ================== COLUMNA VALORES ================== --}}
        <div class="col-12 col-lg-6">

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">VALORES DE ATRIBUTOS</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" id="btnNuevoValor">
                            Nuevo Valor <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="tableValores" class="table table-bordered table-hover text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Atributo</th>
                                    <th>Valor</th>
                                    <th>Color</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attributeValues as $value)
                                    <tr data-id="{{ $value->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="val-atributo">
                                            <span class="badge badge-info">
                                                {{ $value->attribute->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="val-valor">{{ $value->value }}</td>
                                        <td class="val-color">
                                            @if ($value->hex_color)
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="color-preview"
                                                        style="width: 22px; height: 22px; background-color: {{ $value->hex_color }}; border: 2px solid #333; border-radius: 4px; margin-right: 6px;">
                                                    </span>
                                                    <code>{{ $value->hex_color }}</code>
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm btn-edit-val"
                                                data-id="{{ $value->id }}"
                                                data-attribute-id="{{ $value->attribute_id }}"
                                                data-valor="{{ $value->value }}"
                                                data-color="{{ $value->hex_color }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-val"
                                                data-id="{{ $value->id }}"
                                                data-valor="{{ $value->value }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ================== MODAL NUEVO ATRIBUTO ================== --}}
    <div class="modal fade" id="modalCreateAttr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus-circle mr-2"></i> NUEVO ATRIBUTO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreateAttr">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-tags" style="margin-right: 10px;"></i>
                                Datos del Atributo
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_attr_name"
                                name="name" placeholder="Ej: Color, Talla, Material"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                                maxlength="100" required>
                            <div class="invalid-feedback" id="create_attr_error"></div>
                            <small class="form-text text-muted">Solo letras y espacios (max. 100 caracteres)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSaveCreateAttr">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================== MODAL EDITAR ATRIBUTO ================== --}}
    <div class="modal fade" id="modalEditAttr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-edit mr-2"></i> EDITAR ATRIBUTO
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEditAttr">
                    <div class="modal-body">
                        <input type="hidden" id="edit_attr_id">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-tags" style="margin-right: 10px;"></i>
                                Datos del Atributo
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_attr_name"
                                name="name" placeholder="Ej: TALLA"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" required>
                            <div class="invalid-feedback" id="edit_attr_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnSaveEditAttr">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================== MODAL ELIMINAR ATRIBUTO ================== --}}
    <div class="modal fade" id="modalDeleteAttr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-trash mr-2"></i> ELIMINAR ATRIBUTO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_attr_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-tags" style="margin-right: 10px;"></i>
                            Datos del Atributo
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_attr_name" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este atributo?</p>
                        <small class="text-muted">Se eliminarán también todos sus valores asociados</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteAttr">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== MODAL NUEVO VALOR ================== --}}
    <div class="modal fade" id="modalCreateVal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus-circle mr-2"></i> NUEVO VALOR
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreateVal">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-palette" style="margin-right: 10px;"></i>
                                Datos del Valor
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Atributo <span style="color: red;">*</span></label>
                            <select class="form-control form-control-sm" id="create_val_attribute" name="attribute_id" required>
                                <option value="" data-slug="">Selecciona un atributo</option>
                                @foreach($attributes as $attr)
                                    <option value="{{ $attr->id }}" data-slug="{{ $attr->slug }}">{{ $attr->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="create_val_error_attribute"></div>
                        </div>
                        <div class="form-group">
                            <label>Valor <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_val_value"
                                name="value" placeholder="Ej: GRANDE" maxlength="100" required>
                            <div class="invalid-feedback" id="create_val_error"></div>
                        </div>
                        <div class="form-group" id="create_color_group" style="display: none;">
                            <label>Color Hexadecimal <span style="color: red;">*</span></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-sm" id="create_val_color_picker" style="width: 50px; padding: 2px;" value="#000000">
                                <input type="text" class="form-control form-control-sm" id="create_val_hex" name="hex_color" placeholder="#FFFFFF" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success" id="btnSaveCreateVal">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================== MODAL EDITAR VALOR ================== --}}
    <div class="modal fade" id="modalEditVal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-edit mr-2"></i> EDITAR VALOR
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEditVal">
                    <div class="modal-body">
                        <input type="hidden" id="edit_val_id">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-palette" style="margin-right: 10px;"></i>
                                Datos del Valor
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Atributo <span style="color: red;">*</span></label>
                            <select class="form-control form-control-sm" id="edit_val_attribute" name="attribute_id" required>
                                @foreach($attributes as $attr)
                                    <option value="{{ $attr->id }}" data-slug="{{ $attr->slug }}">{{ $attr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Valor <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_val_value"
                                name="value" placeholder="Ej: GRANDE" required>
                            <div class="invalid-feedback" id="edit_val_error"></div>
                        </div>
                        <div class="form-group" id="edit_color_group" style="display: none;">
                            <label>Color Hexadecimal</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-sm" id="edit_val_color_picker" style="width: 50px; padding: 2px;">
                                <input type="text" class="form-control form-control-sm" id="edit_val_hex" name="hex_color" placeholder="#FFFFFF" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnSaveEditVal">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================== MODAL ELIMINAR VALOR ================== --}}
    <div class="modal fade" id="modalDeleteVal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-trash mr-2"></i> ELIMINAR VALOR
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_val_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-palette" style="margin-right: 10px;"></i>
                            Datos del Valor
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Valor</label>
                        <input type="text" class="form-control form-control-sm" id="delete_val_name" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este valor?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteVal">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        #tableAtributos_wrapper .dt-buttons,
        #tableValores_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        #tableAtributos_wrapper .btn,
        #tableValores_wrapper .btn {
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
        .modal-header.bg-warning .close, .modal-header.bg-danger .close { opacity: 1; }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // DataTable para Atributos
            var tableAttr = $("#tableAtributos").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Atributos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Atributos",
                    "infoFiltered": "(Filtrado de _MAX_ total Atributos)",
                    "lengthMenu": "Mostrar _MENU_ Atributos",
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
            tableAttr.buttons().container().appendTo('#tableAtributos_wrapper .row:eq(0)');

            // DataTable para Valores
            var tableVal = $("#tableValores").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Valores",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Valores",
                    "infoFiltered": "(Filtrado de _MAX_ total Valores)",
                    "lengthMenu": "Mostrar _MENU_ Valores",
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
            tableVal.buttons().container().appendTo('#tableValores_wrapper .row:eq(0)');

            // ===================== ATRIBUTOS =====================

            // NUEVO ATRIBUTO
            $('#btnNuevoAtributo').on('click', function() {
                $('#create_attr_name').val('').removeClass('is-invalid');
                $('#create_attr_error').text('');
                $('#modalCreateAttr').modal('show');
            });

            $('#formCreateAttr').on('submit', function(e) {
                e.preventDefault();
                var nombre = $('#create_attr_name').val().trim();
                if (!nombre) {
                    $('#create_attr_name').addClass('is-invalid');
                    $('#create_attr_error').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveCreateAttr').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.attributes.store") }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', name: nombre },
                    success: function(response) {
                        $('#modalCreateAttr').modal('hide');
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
                            if (errors && errors.name) {
                                $('#create_attr_name').addClass('is-invalid');
                                $('#create_attr_error').text(errors.name[0]);
                            } else if (xhr.responseJSON.message) {
                                $('#create_attr_name').addClass('is-invalid');
                                $('#create_attr_error').text(xhr.responseJSON.message);
                            }
                        } else {
                            $('#modalCreateAttr').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al guardar' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveCreateAttr').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                    }
                });
            });

            // EDITAR ATRIBUTO
            $(document).on('click', '.btn-edit-attr', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                $('#edit_attr_id').val(id);
                $('#edit_attr_name').val(nombre).removeClass('is-invalid');
                $('#edit_attr_error').text('');
                $('#modalEditAttr').modal('show');
            });

            $('#formEditAttr').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_attr_id').val();
                var nombre = $('#edit_attr_name').val().trim();
                if (!nombre) {
                    $('#edit_attr_name').addClass('is-invalid');
                    $('#edit_attr_error').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveEditAttr').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/attributes/' + id,
                    type: 'PUT',
                    data: { _token: '{{ csrf_token() }}', name: nombre },
                    success: function(response) {
                        $('#modalEditAttr').modal('hide');
                        if (response.success) {
                            var row = $('#tableAtributos tr[data-id="' + id + '"]');
                            row.find('.attr-nombre').text(response.data.name);
                            row.find('.attr-slug code').text(response.data.slug);
                            row.find('.btn-edit-attr').data('nombre', response.data.name);
                            row.find('.btn-delete-attr').data('nombre', response.data.name);
                            Swal.fire({
                                icon: response.type || 'success',
                                title: response.type === 'info' ? 'Aviso' : 'Actualizado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            if (errors && errors.name) {
                                $('#edit_attr_name').addClass('is-invalid');
                                $('#edit_attr_error').text(errors.name[0]);
                            } else if (xhr.responseJSON.message) {
                                $('#modalEditAttr').modal('hide');
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message });
                            }
                        } else {
                            $('#modalEditAttr').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al actualizar' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveEditAttr').prop('disabled', false).html('<i class="fas fa-save"></i> Actualizar');
                    }
                });
            });

            // ELIMINAR ATRIBUTO
            $(document).on('click', '.btn-delete-attr', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                $('#delete_attr_id').val(id);
                $('#delete_attr_name').val(nombre);
                $('#modalDeleteAttr').modal('show');
            });

            $('#btnConfirmDeleteAttr').on('click', function() {
                var id = $('#delete_attr_id').val();
                Swal.fire({
                    title: '¿Estas seguro?',
                    text: "Se eliminará el atributo y todos sus valores asociados",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#modalDeleteAttr').modal('hide');
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/attributes/' + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.success) {
                                    var row = $('#tableAtributos tr[data-id="' + id + '"]');
                                    tableAttr.row(row).remove().draw(false);
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

            // ===================== VALORES =====================

            // NUEVO VALOR
            $('#btnNuevoValor').on('click', function() {
                $('#create_val_attribute').val('').removeClass('is-invalid');
                $('#create_val_value').val('').removeClass('is-invalid');
                $('#create_val_hex').val('');
                $('#create_val_color_picker').val('#000000');
                $('#create_val_error').text('');
                $('#create_val_error_attribute').text('');
                $('#create_color_group').hide();
                $('#modalCreateVal').modal('show');
            });

            // Toggle color picker para crear valor
            function toggleCreateColorField() {
                var selectedOption = $('#create_val_attribute option:selected');
                var slug = selectedOption.data('slug');
                if (slug === 'color') {
                    $('#create_color_group').show();
                } else {
                    $('#create_color_group').hide();
                    $('#create_val_hex').val('');
                }
            }

            $('#create_val_attribute').on('change', toggleCreateColorField);

            // Sync color picker con hex input para crear
            $('#create_val_color_picker').on('input', function() {
                $('#create_val_hex').val($(this).val().toUpperCase());
            });
            $('#create_val_hex').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#create_val_color_picker').val(val);
                }
            });

            $('#formCreateVal').on('submit', function(e) {
                e.preventDefault();
                var attributeId = $('#create_val_attribute').val();
                var valor = $('#create_val_value').val().trim();
                var hexColor = $('#create_val_hex').val().trim();

                // Limpiar errores
                $('#create_val_attribute, #create_val_value').removeClass('is-invalid');
                $('#create_val_error, #create_val_error_attribute').text('');

                if (!attributeId) {
                    $('#create_val_attribute').addClass('is-invalid');
                    $('#create_val_error_attribute').text('Selecciona un atributo');
                    return;
                }
                if (!valor) {
                    $('#create_val_value').addClass('is-invalid');
                    $('#create_val_error').text('El valor es obligatorio');
                    return;
                }

                $('#btnSaveCreateVal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.attribute-values.store") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        attribute_id: attributeId,
                        value: valor,
                        hex_color: hexColor || null
                    },
                    success: function(response) {
                        $('#modalCreateVal').modal('hide');
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
                            if (errors && errors.value) {
                                $('#create_val_value').addClass('is-invalid');
                                $('#create_val_error').text(errors.value[0]);
                            } else if (errors && errors.attribute_id) {
                                $('#create_val_attribute').addClass('is-invalid');
                                $('#create_val_error_attribute').text(errors.attribute_id[0]);
                            } else if (xhr.responseJSON.message) {
                                $('#create_val_value').addClass('is-invalid');
                                $('#create_val_error').text(xhr.responseJSON.message);
                            }
                        } else {
                            $('#modalCreateVal').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al guardar' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveCreateVal').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                    }
                });
            });

            // Toggle color picker visibility
            function toggleColorField() {
                var selectedOption = $('#edit_val_attribute option:selected');
                var slug = selectedOption.data('slug');
                if (slug === 'color') {
                    $('#edit_color_group').show();
                } else {
                    $('#edit_color_group').hide();
                    $('#edit_val_hex').val('');
                }
            }

            $('#edit_val_attribute').on('change', toggleColorField);

            // Sync color picker with hex input
            $('#edit_val_color_picker').on('input', function() {
                $('#edit_val_hex').val($(this).val().toUpperCase());
            });
            $('#edit_val_hex').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#edit_val_color_picker').val(val);
                }
            });

            // EDITAR VALOR
            $(document).on('click', '.btn-edit-val', function() {
                var id = $(this).data('id');
                var attributeId = $(this).data('attribute-id');
                var valor = $(this).data('valor');
                var color = $(this).data('color');

                $('#edit_val_id').val(id);
                $('#edit_val_attribute').val(attributeId);
                $('#edit_val_value').val(valor).removeClass('is-invalid');
                $('#edit_val_error').text('');

                if (color) {
                    $('#edit_val_hex').val(color);
                    $('#edit_val_color_picker').val(color);
                } else {
                    $('#edit_val_hex').val('');
                    $('#edit_val_color_picker').val('#000000');
                }

                toggleColorField();
                $('#modalEditVal').modal('show');
            });

            $('#formEditVal').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_val_id').val();
                var attributeId = $('#edit_val_attribute').val();
                var valor = $('#edit_val_value').val().trim();
                var hexColor = $('#edit_val_hex').val().trim();

                if (!valor) {
                    $('#edit_val_value').addClass('is-invalid');
                    $('#edit_val_error').text('El valor es obligatorio');
                    return;
                }

                $('#btnSaveEditVal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/attribute-values/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        attribute_id: attributeId,
                        value: valor,
                        hex_color: hexColor || null
                    },
                    success: function(response) {
                        $('#modalEditVal').modal('hide');
                        if (response.success) {
                            var row = $('#tableValores tr[data-id="' + id + '"]');
                            row.find('.val-valor').text(response.data.value);
                            row.find('.val-atributo .badge').text(response.data.attribute ? response.data.attribute.name : 'N/A');

                            // Update color cell
                            var colorCell = row.find('.val-color');
                            if (response.data.hex_color) {
                                colorCell.html('<div class="d-flex align-items-center justify-content-center"><span class="color-preview" style="width: 22px; height: 22px; background-color: ' + response.data.hex_color + '; border: 2px solid #333; border-radius: 4px; margin-right: 6px;"></span><code>' + response.data.hex_color + '</code></div>');
                            } else {
                                colorCell.html('<span class="text-muted">N/A</span>');
                            }

                            // Update data attributes
                            row.find('.btn-edit-val').data('attribute-id', response.data.attribute_id);
                            row.find('.btn-edit-val').data('valor', response.data.value);
                            row.find('.btn-edit-val').data('color', response.data.hex_color);
                            row.find('.btn-delete-val').data('valor', response.data.value);

                            Swal.fire({
                                icon: response.type || 'success',
                                title: response.type === 'info' ? 'Aviso' : 'Actualizado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            if (errors && errors.value) {
                                $('#edit_val_value').addClass('is-invalid');
                                $('#edit_val_error').text(errors.value[0]);
                            } else if (xhr.responseJSON.message) {
                                $('#modalEditVal').modal('hide');
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message });
                            }
                        } else {
                            $('#modalEditVal').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al actualizar' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveEditVal').prop('disabled', false).html('<i class="fas fa-save"></i> Actualizar');
                    }
                });
            });

            // ELIMINAR VALOR
            $(document).on('click', '.btn-delete-val', function() {
                var id = $(this).data('id');
                var valor = $(this).data('valor');
                $('#delete_val_id').val(id);
                $('#delete_val_name').val(valor);
                $('#modalDeleteVal').modal('show');
            });

            $('#btnConfirmDeleteVal').on('click', function() {
                var id = $('#delete_val_id').val();
                Swal.fire({
                    title: '¿Estas seguro?',
                    text: "Esta accion no se puede revertir",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#modalDeleteVal').modal('hide');
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/attribute-values/' + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.success) {
                                    var row = $('#tableValores tr[data-id="' + id + '"]');
                                    tableVal.row(row).remove().draw(false);
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
        });
    </script>
@stop
