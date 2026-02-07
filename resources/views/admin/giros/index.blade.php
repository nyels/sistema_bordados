@extends('adminlte::page')

@section('title', 'GIROS')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> GIROS DE PROVEEDOR</h3>
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
                                <th>Nombre del giro</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($giros as $giro)
                                <tr style="text-align: center;" data-id="{{ $giro->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="nombre-giro">{{ $giro->nombre_giro }}</td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            data-id="{{ $giro->id }}"
                                            data-nombre="{{ $giro->nombre_giro }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-delete"
                                            data-id="{{ $giro->id }}"
                                            data-nombre="{{ $giro->nombre_giro }}">
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

    {{-- MODAL NUEVO --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus-circle mr-2"></i> NUEVO GIRO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-briefcase" style="margin-right: 10px;"></i>
                                Datos del Giro
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_nombre_giro"
                                name="nombre_giro" placeholder="Ej: COMERCIO"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" required>
                            <div class="invalid-feedback" id="create_error_nombre"></div>
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
                        <i class="fas fa-edit mr-2"></i> EDITAR GIRO
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
                                <i class="fas fa-briefcase" style="margin-right: 10px;"></i>
                                Datos del Giro
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_nombre_giro"
                                name="nombre_giro" placeholder="Ej: COMERCIO"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" required>
                            <div class="invalid-feedback" id="edit_error_nombre"></div>
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR GIRO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-briefcase" style="margin-right: 10px;"></i>
                            Datos del Giro
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_nombre_giro" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este giro?</p>
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
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Giros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Giros",
                    "infoFiltered": "(Filtrado de _MAX_ total Giros)",
                    "lengthMenu": "Mostrar _MENU_ Giros",
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

            // === NUEVO ===
            $('#btnNuevo').on('click', function() {
                $('#create_nombre_giro').val('').removeClass('is-invalid');
                $('#create_error_nombre').text('');
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var nombre = $('#create_nombre_giro').val().trim();
                if (!nombre) {
                    $('#create_nombre_giro').addClass('is-invalid');
                    $('#create_error_nombre').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.giros.store") }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', nombre_giro: nombre },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            // Agregar nueva fila a la tabla
                            var newRow = table.row.add([
                                table.rows().count() + 1,
                                '<span class="nombre-giro">' + response.data.nombre_giro + '</span>',
                                '<button type="button" class="btn btn-warning btn-edit" data-id="' + response.data.id + '" data-nombre="' + response.data.nombre_giro + '"><i class="fas fa-edit"></i></button> ' +
                                '<button type="button" class="btn btn-danger btn-delete" data-id="' + response.data.id + '" data-nombre="' + response.data.nombre_giro + '"><i class="fas fa-trash"></i></button>'
                            ]).draw(false).node();
                            $(newRow).attr('data-id', response.data.id).css('text-align', 'center');
                            Swal.fire({
                                icon: 'success',
                                title: 'Guardado',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            if (errors && errors.nombre_giro) {
                                $('#create_nombre_giro').addClass('is-invalid');
                                $('#create_error_nombre').text(errors.nombre_giro[0]);
                            } else if (xhr.responseJSON.error) {
                                $('#create_nombre_giro').addClass('is-invalid');
                                $('#create_error_nombre').text(xhr.responseJSON.error);
                            }
                        } else {
                            $('#modalCreate').modal('hide');
                            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error al guardar' });
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
                $('#edit_id').val(id);
                $('#edit_nombre_giro').val(nombre).removeClass('is-invalid');
                $('#edit_error_nombre').text('');
                $('#modalEdit').modal('show');
            });

            $('#formEdit').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_id').val();
                var nombre = $('#edit_nombre_giro').val().trim();
                if (!nombre) {
                    $('#edit_nombre_giro').addClass('is-invalid');
                    $('#edit_error_nombre').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/giros/edit/' + id,
                    type: 'PUT',
                    data: { _token: '{{ csrf_token() }}', nombre_giro: nombre },
                    success: function(response) {
                        $('#modalEdit').modal('hide');
                        if (response.success) {
                            var row = $('tr[data-id="' + id + '"]');
                            row.find('.nombre-giro').text(response.data.nombre_giro);
                            row.find('.btn-edit').data('nombre', response.data.nombre_giro);
                            row.find('.btn-delete').data('nombre', response.data.nombre_giro);
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
                            if (errors.nombre_giro) {
                                $('#edit_nombre_giro').addClass('is-invalid');
                                $('#edit_error_nombre').text(errors.nombre_giro[0]);
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
                $('#delete_id').val(id);
                $('#delete_nombre_giro').val(nombre);
                $('#modalDelete').modal('show');
            });

            $('#btnConfirmDelete').on('click', function() {
                var id = $('#delete_id').val();
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
                        $('#modalDelete').modal('hide');
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/giros/delete/' + id,
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
