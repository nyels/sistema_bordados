@extends('adminlte::page')

@section('title', 'PERSONAL')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"><i class="fas fa-id-badge mr-2"></i> PERSONAL DE LA EMPRESA</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <button type="button" class="btn btn-primary" id="btnNuevo">
                    Nuevo <i class="fas fa-plus"></i></button>
            </div>
            <hr>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-hover table-condensed">
                        <thead class="thead-dark">
                            <tr style="text-align: center;">
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Puesto</th>
                                <th style="text-align: center;">Usuario</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($staff as $person)
                                <tr style="text-align: center;" data-id="{{ $person->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="staff-nombre font-weight-bold">{{ $person->name }}</td>
                                    <td class="staff-puesto">{{ $person->position ?? '-' }}</td>
                                    <td class="staff-usuario" style="text-align: center;">
                                        @if($person->user)
                                            <span class="badge badge-info">{{ $person->user->email }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="staff-estado" style="text-align: center;">
                                        @if($person->is_active)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            data-id="{{ $person->id }}"
                                            data-nombre="{{ $person->name }}"
                                            data-puesto="{{ $person->position }}"
                                            data-activo="{{ $person->is_active ? '1' : '0' }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-delete"
                                            data-id="{{ $person->id }}"
                                            data-nombre="{{ $person->name }}"
                                            data-tiene-usuario="{{ $person->user ? '1' : '0' }}">
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

    {{-- MODAL CREAR --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-id-badge mr-2"></i> NUEVO PERSONAL
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #0056b3; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-id-badge" style="margin-right: 10px;"></i>
                                Datos del Personal
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_name"
                                name="name" placeholder="Ej: Juan Pérez" required>
                            <div class="invalid-feedback" id="create_error_nombre"></div>
                        </div>
                        <div class="form-group">
                            <label>Puesto</label>
                            <input type="text" class="form-control form-control-sm" id="create_position"
                                name="position" placeholder="Ej: Bordador">
                            <div class="invalid-feedback" id="create_error_puesto"></div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="create_is_active" name="is_active" checked>
                                <label class="custom-control-label" for="create_is_active">Personal Activo</label>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-edit mr-2"></i> EDITAR PERSONAL
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEdit">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-id-badge" style="margin-right: 10px;"></i>
                                Datos del Personal
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_name"
                                name="name" placeholder="Ej: Juan Pérez" required>
                            <div class="invalid-feedback" id="edit_error_nombre"></div>
                        </div>
                        <div class="form-group">
                            <label>Puesto</label>
                            <input type="text" class="form-control form-control-sm" id="edit_position"
                                name="position" placeholder="Ej: Bordador">
                            <div class="invalid-feedback" id="edit_error_puesto"></div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active">
                                <label class="custom-control-label" for="edit_is_active">Personal Activo</label>
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR PERSONAL
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-id-badge" style="margin-right: 10px;"></i>
                            Datos del Personal
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_name" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar a este personal?</p>
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
        .modal-header.bg-primary .close, .modal-header.bg-warning .close, .modal-header.bg-danger .close { opacity: 1; color: #fff; }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Personal",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Personal",
                    "infoFiltered": "(Filtrado de _MAX_ total Personal)",
                    "lengthMenu": "Mostrar _MENU_ Personal",
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

            // === CREAR ===
            $('#btnNuevo').on('click', function() {
                $('#formCreate')[0].reset();
                $('#create_name').removeClass('is-invalid');
                $('#create_position').removeClass('is-invalid');
                $('#create_error_nombre').text('');
                $('#create_error_puesto').text('');
                $('#create_is_active').prop('checked', true);
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var nombre = $('#create_name').val().trim();
                var puesto = $('#create_position').val().trim();
                var activo = $('#create_is_active').is(':checked') ? 1 : 0;

                // Reset validaciones
                $('#create_name').removeClass('is-invalid');
                $('#create_position').removeClass('is-invalid');
                $('#create_error_nombre').text('');
                $('#create_error_puesto').text('');

                if (!nombre) {
                    $('#create_name').addClass('is-invalid');
                    $('#create_error_nombre').text('El nombre es obligatorio');
                    return;
                }

                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.staff.store") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: nombre,
                        position: puesto,
                        is_active: activo
                    },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            // Agregar nueva fila a la tabla
                            var newRow = table.row.add([
                                table.rows().count() + 1,
                                '<span class="font-weight-bold">' + response.data.name + '</span>',
                                response.data.position || '-',
                                '<span class="text-muted">-</span>',
                                response.data.is_active
                                    ? '<span class="badge badge-success">Activo</span>'
                                    : '<span class="badge badge-secondary">Inactivo</span>',
                                '<button type="button" class="btn btn-warning btn-edit" ' +
                                    'data-id="' + response.data.id + '" ' +
                                    'data-nombre="' + response.data.name + '" ' +
                                    'data-puesto="' + (response.data.position || '') + '" ' +
                                    'data-activo="' + (response.data.is_active ? '1' : '0') + '">' +
                                    '<i class="fas fa-edit"></i></button> ' +
                                '<button type="button" class="btn btn-danger btn-delete" ' +
                                    'data-id="' + response.data.id + '" ' +
                                    'data-nombre="' + response.data.name + '" ' +
                                    'data-tiene-usuario="0">' +
                                    '<i class="fas fa-trash"></i></button>'
                            ]).draw(false).node();

                            $(newRow).attr('data-id', response.data.id).css('text-align', 'center');
                            $(newRow).find('td:eq(1)').addClass('staff-nombre');
                            $(newRow).find('td:eq(2)').addClass('staff-puesto');
                            $(newRow).find('td:eq(3)').addClass('staff-usuario');
                            $(newRow).find('td:eq(4)').addClass('staff-estado');

                            Swal.fire({
                                icon: 'success',
                                title: 'Creado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            if (errors) {
                                if (errors.name) {
                                    $('#create_name').addClass('is-invalid');
                                    $('#create_error_nombre').text(errors.name[0]);
                                }
                                if (errors.position) {
                                    $('#create_position').addClass('is-invalid');
                                    $('#create_error_puesto').text(errors.position[0]);
                                }
                            } else if (xhr.responseJSON.message) {
                                $('#modalCreate').modal('hide');
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message });
                            }
                        } else {
                            $('#modalCreate').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al crear' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveCreate').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
                    }
                });
            });

            // === EDITAR ===
            $(document).on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var puesto = $(this).data('puesto') || '';
                var activo = $(this).data('activo') === 1 || $(this).data('activo') === '1';

                $('#edit_id').val(id);
                $('#edit_name').val(nombre).removeClass('is-invalid');
                $('#edit_position').val(puesto).removeClass('is-invalid');
                $('#edit_is_active').prop('checked', activo);
                $('#edit_error_nombre').text('');
                $('#edit_error_puesto').text('');
                $('#modalEdit').modal('show');
            });

            $('#formEdit').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_id').val();
                var nombre = $('#edit_name').val().trim();
                var puesto = $('#edit_position').val().trim();
                var activo = $('#edit_is_active').is(':checked') ? 1 : 0;

                if (!nombre) {
                    $('#edit_name').addClass('is-invalid');
                    $('#edit_error_nombre').text('El nombre es obligatorio');
                    return;
                }

                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/admin/staff/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: nombre,
                        position: puesto,
                        is_active: activo
                    },
                    success: function(response) {
                        $('#modalEdit').modal('hide');
                        if (response.success) {
                            var row = $('tr[data-id="' + id + '"]');
                            row.find('.staff-nombre').text(response.data.name);
                            row.find('.staff-puesto').text(response.data.position || '-');

                            // Update estado badge
                            var estadoCell = row.find('.staff-estado');
                            if (response.data.is_active) {
                                estadoCell.html('<span class="badge badge-success">Activo</span>');
                            } else {
                                estadoCell.html('<span class="badge badge-secondary">Inactivo</span>');
                            }

                            // Update data attributes
                            row.find('.btn-edit').data('nombre', response.data.name);
                            row.find('.btn-edit').data('puesto', response.data.position);
                            row.find('.btn-edit').data('activo', response.data.is_active ? '1' : '0');
                            row.find('.btn-delete').data('nombre', response.data.name);

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
                            if (errors) {
                                if (errors.name) {
                                    $('#edit_name').addClass('is-invalid');
                                    $('#edit_error_nombre').text(errors.name[0]);
                                }
                                if (errors.position) {
                                    $('#edit_position').addClass('is-invalid');
                                    $('#edit_error_puesto').text(errors.position[0]);
                                }
                            } else if (xhr.responseJSON.message) {
                                $('#modalEdit').modal('hide');
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message });
                            }
                        } else {
                            $('#modalEdit').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al actualizar' });
                        }
                    },
                    complete: function() {
                        $('#btnSaveEdit').prop('disabled', false).html('<i class="fas fa-save"></i> Actualizar');
                    }
                });
            });

            // === ELIMINAR ===
            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var tieneUsuario = $(this).data('tiene-usuario') === 1 || $(this).data('tiene-usuario') === '1';

                if (tieneUsuario) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se puede eliminar',
                        text: 'Este personal tiene un usuario vinculado. Primero debe desvincular el usuario.'
                    });
                    return;
                }

                $('#delete_id').val(id);
                $('#delete_name').val(nombre);
                $('#modalDelete').modal('show');
            });

            $('#btnConfirmDelete').on('click', function() {
                var id = $('#delete_id').val();
                Swal.fire({
                    title: '¿Estas seguro?',
                    text: "Se eliminará el registro de este personal",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#modalDelete').modal('hide');
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/admin/staff/' + id,
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
        });
    </script>
@stop
