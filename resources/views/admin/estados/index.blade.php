@extends('adminlte::page')

@section('title', 'ESTADOS')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary" bis_skin_checked="1">

        <div class="card-header" bis_skin_checked="1">

            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ESTADOS</h3>
            <!-- /.card-tools -->
        </div>
        <!-- /.card-header -->
        <div class="card-body" bis_skin_checked="1">
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
                                <th>Nombre estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($estados as $estado)
                                <tr style="text-align: center;" data-id="{{ $estado->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="nombre-estado">{{ $estado->nombre_estado }}</td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            data-id="{{ $estado->id }}"
                                            data-nombre="{{ $estado->nombre_estado }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-delete"
                                            data-id="{{ $estado->id }}"
                                            data-nombre="{{ $estado->nombre_estado }}">
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
        <!-- /.card-body -->
    </div>

    {{-- MODAL NUEVO --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus-circle mr-2"></i> NUEVO ESTADO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Estado
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="create_nombre_estado"
                                name="nombre_estado" placeholder="Ej: MEXICO"
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
                        <i class="fas fa-edit mr-2"></i> EDITAR ESTADO
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
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Estado
                            </h5>
                        </div>
                        <div class="form-group">
                            <label>Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_nombre_estado"
                                name="nombre_estado" placeholder="Ej: MEXICO"
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR ESTADO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-building" style="margin-right: 10px;"></i>
                            Datos del Estado
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_nombre_estado" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este estado?</p>
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
        /* Fondo transparente y sin borde en el contenedor */
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            /* Centrar los botones */
            gap: 10px;
            /* Espaciado entre botones */
            margin-bottom: 15px;
            /* Separar botones de la tabla */
        }

        /* Estilo personalizado para los botones */
        #example1_wrapper .btn {
            color: #fff;
            /* Color del texto en blanco */
            border-radius: 4px;
            /* Bordes redondeados */
            padding: 5px 15px;
            /* Espaciado interno */
            font-size: 14px;
            /* Tamano de fuente */
        }

        /* Colores por tipo de boton */
        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-default {
            background-color: #6e7176;
            color: #212529;
            border: none;
        }

        .modal-header.bg-warning .close,
        .modal-header.bg-danger .close {
            opacity: 1;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            // DataTable
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Estados",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Estados",
                    "infoFiltered": "(Filtrado de _MAX_ total Estados)",
                    "lengthMenu": "Mostrar _MENU_ Estados",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                buttons: [{
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default'
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger'
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info'
                    },
                    {
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-default'
                    }
                ]
            });
            table.buttons().container().appendTo('#example1_wrapper .row:eq(0)');

            // === NUEVO ===
            $('#btnNuevo').on('click', function() {
                $('#create_nombre_estado').val('').removeClass('is-invalid');
                $('#create_error_nombre').text('');
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var nombre = $('#create_nombre_estado').val().trim();
                if (!nombre) {
                    $('#create_nombre_estado').addClass('is-invalid');
                    $('#create_error_nombre').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.estados.store") }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', nombre_estado: nombre },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            // Agregar nueva fila a la tabla
                            var newRow = table.row.add([
                                table.rows().count() + 1,
                                '<span class="nombre-estado">' + response.data.nombre_estado + '</span>',
                                '<button type="button" class="btn btn-warning btn-edit" data-id="' + response.data.id + '" data-nombre="' + response.data.nombre_estado + '"><i class="fas fa-edit"></i></button> ' +
                                '<button type="button" class="btn btn-danger btn-delete" data-id="' + response.data.id + '" data-nombre="' + response.data.nombre_estado + '"><i class="fas fa-trash"></i></button>'
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
                            if (errors && errors.nombre_estado) {
                                $('#create_nombre_estado').addClass('is-invalid');
                                $('#create_error_nombre').text(errors.nombre_estado[0]);
                            } else if (xhr.responseJSON.error) {
                                $('#create_nombre_estado').addClass('is-invalid');
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
                $('#edit_nombre_estado').val(nombre).removeClass('is-invalid');
                $('#edit_error_nombre').text('');
                $('#modalEdit').modal('show');
            });

            $('#formEdit').on('submit', function(e) {
                e.preventDefault();

                var id = $('#edit_id').val();
                var nombre = $('#edit_nombre_estado').val().trim();

                if (!nombre) {
                    $('#edit_nombre_estado').addClass('is-invalid');
                    $('#edit_error_nombre').text('El nombre es obligatorio');
                    return;
                }

                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '/estados/edit/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        nombre_estado: nombre
                    },
                    success: function(response) {
                        $('#modalEdit').modal('hide');

                        if (response.success) {
                            // Actualizar la fila en la tabla
                            var row = $('tr[data-id="' + id + '"]');
                            row.find('.nombre-estado').text(response.data.nombre_estado);
                            row.find('.btn-edit').data('nombre', response.data.nombre_estado);
                            row.find('.btn-delete').data('nombre', response.data.nombre_estado);

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
                            if (errors.nombre_estado) {
                                $('#edit_nombre_estado').addClass('is-invalid');
                                $('#edit_error_nombre').text(errors.nombre_estado[0]);
                            }
                        } else {
                            $('#modalEdit').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Error al actualizar el estado'
                            });
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
                $('#delete_nombre_estado').val(nombre);
                $('#modalDelete').modal('show');
            });

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

                        Swal.fire({
                            title: 'Eliminando...',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => Swal.showLoading()
                        });

                        $.ajax({
                            url: '/estados/delete/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Eliminar la fila de la tabla
                                    var row = $('tr[data-id="' + id + '"]');
                                    table.row(row).remove().draw(false);

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Eliminado',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'Error al eliminar el estado'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@stop
