@extends('adminlte::page')

@section('title', 'USUARIOS')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"><i class="fas fa-users mr-2"></i> USUARIOS DEL SISTEMA</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <button type="button" class="btn btn-primary" id="btnNuevo">
                    Nuevo <i class="fas fa-plus"></i>
                </button>
            </div>
            <hr>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-hover table-condensed">
                        <thead class="thead-dark">
                            <tr style="text-align: center;">
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Personal Vinculado</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr style="text-align: center;" data-id="{{ $user->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="user-nombre font-weight-bold">{{ $user->name }}</td>
                                    <td class="user-email">{{ $user->email }}</td>
                                    <td class="user-staff">
                                        @if($user->staff)
                                            <span class="badge badge-info">
                                                {{ $user->staff->name }}
                                                @if($user->staff->position)
                                                    <small>({{ $user->staff->position }})</small>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="user-estado" style="text-align: center;">
                                        @if($user->is_active)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            data-id="{{ $user->id }}"
                                            data-nombre="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-staff-id="{{ $user->staff_id }}"
                                            data-activo="{{ $user->is_active ? '1' : '0' }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <button type="button" class="btn btn-danger btn-delete"
                                                data-id="{{ $user->id }}"
                                                data-nombre="{{ $user->name }}"
                                                data-email="{{ $user->email }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
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
            <div class="modal-content" style="border: 2px solid #007bff;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" style="font-weight: bold;">
                        <i class="fas fa-user-plus mr-2"></i> NUEVO USUARIO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #004085; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-user-cog" style="margin-right: 10px;"></i>
                                Datos del Usuario
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_name"
                                name="name" placeholder="Ej: Juan Perez" required>
                            <div class="invalid-feedback" id="create_error_name"></div>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: red;">*</span></label>
                            <input type="email" class="form-control form-control-sm" id="create_email"
                                name="email" placeholder="Ej: usuario@correo.com" required>
                            <div class="invalid-feedback" id="create_error_email"></div>
                        </div>
                        <div class="form-group">
                            <label>Contrasena <span style="color: red;">*</span></label>
                            <input type="password" class="form-control form-control-sm" id="create_password"
                                name="password" placeholder="Minimo 8 caracteres" required>
                            <div class="invalid-feedback" id="create_error_password"></div>
                        </div>
                        <div class="form-group">
                            <label>Personal Vinculado</label>
                            <select class="form-control form-control-sm" id="create_staff_id" name="staff_id">
                                <option value="">-- Sin vincular --</option>
                                @foreach($staff as $person)
                                    <option value="{{ $person->id }}">{{ $person->name }} @if($person->position)({{ $person->position }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="create_is_active" name="is_active" checked>
                                <label class="custom-control-label" for="create_is_active">Usuario Activo</label>
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
                        <i class="fas fa-edit mr-2"></i> EDITAR USUARIO
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
                                <i class="fas fa-user-cog" style="margin-right: 10px;"></i>
                                Datos del Usuario
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_name"
                                name="name" placeholder="Ej: Juan Pérez" required>
                            <div class="invalid-feedback" id="edit_error_nombre"></div>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: red;">*</span></label>
                            <input type="email" class="form-control form-control-sm" id="edit_email"
                                name="email" placeholder="Ej: usuario@correo.com" required>
                            <div class="invalid-feedback" id="edit_error_email"></div>
                        </div>
                        <div class="form-group">
                            <label>Nueva Contraseña <small class="text-muted">(dejar vacío para mantener)</small></label>
                            <input type="password" class="form-control form-control-sm" id="edit_password"
                                name="password" placeholder="••••••••">
                            <div class="invalid-feedback" id="edit_error_password"></div>
                        </div>
                        <div class="form-group">
                            <label>Personal Vinculado</label>
                            <select class="form-control form-control-sm" id="edit_staff_id" name="staff_id">
                                <option value="">-- Sin vincular --</option>
                                @foreach($staff as $person)
                                    <option value="{{ $person->id }}">{{ $person->name }} @if($person->position)({{ $person->position }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active">
                                <label class="custom-control-label" for="edit_is_active">Usuario Activo</label>
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR USUARIO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-user-times" style="margin-right: 10px;"></i>
                            Datos del Usuario
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_name" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" class="form-control form-control-sm" id="delete_email" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este usuario?</p>
                        <small class="text-muted">El usuario perderá acceso al sistema.</small>
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
        .modal-header.bg-warning .close, .modal-header.bg-danger .close { opacity: 1; }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Usuarios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Usuarios",
                    "infoFiltered": "(Filtrado de _MAX_ total Usuarios)",
                    "lengthMenu": "Mostrar _MENU_ Usuarios",
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
                $('#create_name, #create_email, #create_password').removeClass('is-invalid');
                $('#create_error_name, #create_error_email, #create_error_password').text('');
                $('#create_is_active').prop('checked', true);
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var name = $('#create_name').val().trim();
                var email = $('#create_email').val().trim();
                var password = $('#create_password').val();
                var staffId = $('#create_staff_id').val();
                var isActive = $('#create_is_active').is(':checked') ? 1 : 0;

                // Limpiar errores previos
                $('#create_name, #create_email, #create_password').removeClass('is-invalid');
                $('#create_error_name, #create_error_email, #create_error_password').text('');

                // Validaciones basicas
                var hasError = false;
                if (!name) {
                    $('#create_name').addClass('is-invalid');
                    $('#create_error_name').text('El nombre es obligatorio');
                    hasError = true;
                }
                if (!email) {
                    $('#create_email').addClass('is-invalid');
                    $('#create_error_email').text('El email es obligatorio');
                    hasError = true;
                }
                if (!password) {
                    $('#create_password').addClass('is-invalid');
                    $('#create_error_password').text('La contrasena es obligatoria');
                    hasError = true;
                }
                if (hasError) return;

                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.users.store") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        email: email,
                        password: password,
                        staff_id: staffId || null,
                        is_active: isActive
                    },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            var d = response.data;
                            var staffHtml = d.staff ? '<span class="badge badge-info">' + d.staff.name + (d.staff.position ? ' <small>(' + d.staff.position + ')</small>' : '') + '</span>' : '<span class="text-muted">-</span>';
                            var estadoHtml = d.is_active ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>';
                            var newRow = table.row.add([
                                table.rows().count() + 1,
                                '<span class="font-weight-bold">' + d.name + '</span>',
                                d.email,
                                staffHtml,
                                estadoHtml,
                                '<button type="button" class="btn btn-warning btn-edit" data-id="' + d.id + '" data-nombre="' + d.name + '" data-email="' + d.email + '" data-staff-id="' + (d.staff_id || '') + '" data-activo="' + (d.is_active ? '1' : '0') + '"><i class="fas fa-edit"></i></button> ' +
                                '<button type="button" class="btn btn-danger btn-delete" data-id="' + d.id + '" data-nombre="' + d.name + '" data-email="' + d.email + '"><i class="fas fa-trash"></i></button>'
                            ]).draw(false).node();
                            $(newRow).attr('data-id', d.id).css('text-align', 'center');
                            $(newRow).find('td:eq(1)').addClass('user-nombre');
                            $(newRow).find('td:eq(2)').addClass('user-email');
                            $(newRow).find('td:eq(3)').addClass('user-staff');
                            $(newRow).find('td:eq(4)').addClass('user-estado');
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
                                    $('#create_error_name').text(errors.name[0]);
                                }
                                if (errors.email) {
                                    $('#create_email').addClass('is-invalid');
                                    $('#create_error_email').text(errors.email[0]);
                                }
                                if (errors.password) {
                                    $('#create_password').addClass('is-invalid');
                                    $('#create_error_password').text(errors.password[0]);
                                }
                            } else if (xhr.responseJSON.message) {
                                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message });
                            }
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al crear usuario' });
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
                var email = $(this).data('email');
                var staffId = $(this).data('staff-id') || '';
                var activo = $(this).data('activo') === 1 || $(this).data('activo') === '1';

                $('#edit_id').val(id);
                $('#edit_name').val(nombre).removeClass('is-invalid');
                $('#edit_email').val(email).removeClass('is-invalid');
                $('#edit_password').val('').removeClass('is-invalid');
                $('#edit_staff_id').val(staffId);
                $('#edit_is_active').prop('checked', activo);
                $('#edit_error_nombre').text('');
                $('#edit_error_email').text('');
                $('#edit_error_password').text('');
                $('#modalEdit').modal('show');
            });

            $('#formEdit').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_id').val();
                var nombre = $('#edit_name').val().trim();
                var email = $('#edit_email').val().trim();
                var password = $('#edit_password').val();
                var staffId = $('#edit_staff_id').val();
                var activo = $('#edit_is_active').is(':checked') ? 1 : 0;

                // Validaciones básicas
                var hasError = false;
                if (!nombre) {
                    $('#edit_name').addClass('is-invalid');
                    $('#edit_error_nombre').text('El nombre es obligatorio');
                    hasError = true;
                }
                if (!email) {
                    $('#edit_email').addClass('is-invalid');
                    $('#edit_error_email').text('El email es obligatorio');
                    hasError = true;
                }
                if (hasError) return;

                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/admin/users/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: nombre,
                        email: email,
                        password: password,
                        staff_id: staffId || null,
                        is_active: activo
                    },
                    success: function(response) {
                        $('#modalEdit').modal('hide');
                        if (response.success) {
                            var row = $('tr[data-id="' + id + '"]');
                            row.find('.user-nombre').text(response.data.name);
                            row.find('.user-email').text(response.data.email);

                            // Update staff badge
                            var staffCell = row.find('.user-staff');
                            if (response.data.staff) {
                                var staffText = response.data.staff.name;
                                if (response.data.staff.position) {
                                    staffText += ' <small>(' + response.data.staff.position + ')</small>';
                                }
                                staffCell.html('<span class="badge badge-info">' + staffText + '</span>');
                            } else {
                                staffCell.html('<span class="text-muted">-</span>');
                            }

                            // Update estado badge
                            var estadoCell = row.find('.user-estado');
                            if (response.data.is_active) {
                                estadoCell.html('<span class="badge badge-success">Activo</span>');
                            } else {
                                estadoCell.html('<span class="badge badge-secondary">Inactivo</span>');
                            }

                            // Update data attributes
                            row.find('.btn-edit').data('nombre', response.data.name);
                            row.find('.btn-edit').data('email', response.data.email);
                            row.find('.btn-edit').data('staff-id', response.data.staff_id || '');
                            row.find('.btn-edit').data('activo', response.data.is_active ? '1' : '0');
                            row.find('.btn-delete').data('nombre', response.data.name);
                            row.find('.btn-delete').data('email', response.data.email);

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
                                if (errors.email) {
                                    $('#edit_email').addClass('is-invalid');
                                    $('#edit_error_email').text(errors.email[0]);
                                }
                                if (errors.password) {
                                    $('#edit_password').addClass('is-invalid');
                                    $('#edit_error_password').text(errors.password[0]);
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
                var email = $(this).data('email');

                $('#delete_id').val(id);
                $('#delete_name').val(nombre);
                $('#delete_email').val(email);
                $('#modalDelete').modal('show');
            });

            $('#btnConfirmDelete').on('click', function() {
                var id = $('#delete_id').val();
                Swal.fire({
                    title: '¿Estas seguro?',
                    text: "El usuario perderá acceso al sistema",
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
                            url: '/admin/users/' + id,
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
