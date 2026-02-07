@extends('adminlte::page')

@section('title', 'NIVELES DE URGENCIA')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NIVELES DE URGENCIA</h3>
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
                                <th>% Tiempo</th>
                                <th>Multiplicador</th>
                                <th>Color</th>
                                <th>Orden</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($urgencyLevels as $level)
                                <tr style="text-align: center;" data-id="{{ $level->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="level-name text-left">
                                        @if ($level->icon)
                                            <i class="fas {{ $level->icon }} mr-1" style="color: {{ $level->color }};"></i>
                                        @endif
                                        <span class="nombre-level">{{ $level->name }}</span>
                                        @if ($level->description)
                                            <small class="text-muted d-block">{{ $level->description }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-secondary">{{ $level->time_percentage }}%</span></td>
                                    <td><span class="badge badge-info">x{{ number_format($level->price_multiplier, 2) }}</span></td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $level->color }}; color: white; padding: 5px 15px;">
                                            {{ $level->color }}
                                        </span>
                                    </td>
                                    <td>{{ $level->sort_order }}</td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-warning btn-edit"
                                            data-id="{{ $level->id }}"
                                            data-name="{{ $level->name }}"
                                            data-time="{{ $level->time_percentage }}"
                                            data-multiplier="{{ $level->price_multiplier }}"
                                            data-color="{{ $level->color }}"
                                            data-icon="{{ $level->icon }}"
                                            data-order="{{ $level->sort_order }}"
                                            data-description="{{ $level->description }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-delete"
                                            data-id="{{ $level->id }}"
                                            data-name="{{ $level->name }}">
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" style="font-weight: bold;">
                        <i class="fas fa-plus mr-2"></i> NUEVO NIVEL DE URGENCIA
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #0056b3; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-clock" style="margin-right: 10px;"></i>
                                Datos del Nivel
                            </h5>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="create_name"
                                        name="name" placeholder="Ej: URGENTE" required maxlength="100">
                                    <div class="invalid-feedback" id="create_error_name"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Orden de Aparicion</label>
                                    <input type="number" class="form-control form-control-sm" id="create_sort_order"
                                        name="sort_order" min="0" value="0">
                                    <div class="invalid-feedback" id="create_error_sort_order"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Porcentaje de Tiempo <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="create_time_percentage"
                                            name="time_percentage" min="1" max="200" required value="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">100% = tiempo normal</small>
                                    <div class="invalid-feedback" id="create_error_time_percentage"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Multiplicador de Precio <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">x</span>
                                        </div>
                                        <input type="number" class="form-control" id="create_price_multiplier"
                                            name="price_multiplier" min="0.5" max="5" step="0.05" required value="1.00">
                                    </div>
                                    <small class="text-muted">1.00 = precio normal</small>
                                    <div class="invalid-feedback" id="create_error_price_multiplier"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Color <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" id="create_colorPicker" value="#28a745"
                                            style="width: 40px; height: 31px; padding: 2px; border: 1px solid #ced4da;">
                                        <input type="text" class="form-control" id="create_color"
                                            name="color" maxlength="20" required value="#28a745">
                                    </div>
                                    <div class="invalid-feedback" id="create_error_color"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Icono (FontAwesome)</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i id="create_iconPreview" class="fas fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="create_icon"
                                            name="icon" maxlength="50" placeholder="fa-clock">
                                    </div>
                                    <div class="invalid-feedback" id="create_error_icon"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Descripcion</label>
                            <textarea class="form-control form-control-sm" id="create_description"
                                name="description" rows="2" maxlength="500" placeholder="Descripcion opcional"></textarea>
                            <div class="invalid-feedback" id="create_error_description"></div>
                        </div>

                        {{-- Vista Previa --}}
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px;">
                            <h5 style="color: #6c757d; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-eye" style="margin-right: 10px;"></i>
                                Vista Previa
                            </h5>
                        </div>
                        <div class="text-center p-3 rounded mb-3" style="background-color: #f8f9fa;">
                            <span id="create_previewBadge" class="badge p-2" style="background-color: #28a745; color: white; font-size: 1.1rem;">
                                <i class="fas fa-clock mr-1"></i> <span id="create_previewName">NORMAL</span>
                            </span>
                            <div class="mt-2">
                                <small><strong>Tiempo:</strong> <span id="create_previewTime">100</span>% | <strong>Precio:</strong> x<span id="create_previewPrice">1.00</span></small>
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
                        <i class="fas fa-edit mr-2"></i> EDITAR NIVEL DE URGENCIA
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
                                <i class="fas fa-clock" style="margin-right: 10px;"></i>
                                Datos del Nivel
                            </h5>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="edit_name"
                                        name="name" placeholder="Ej: URGENTE" required maxlength="100">
                                    <div class="invalid-feedback" id="edit_error_name"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Orden de Aparición</label>
                                    <input type="number" class="form-control form-control-sm" id="edit_sort_order"
                                        name="sort_order" min="0" value="0">
                                    <div class="invalid-feedback" id="edit_error_sort_order"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Porcentaje de Tiempo <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="edit_time_percentage"
                                            name="time_percentage" min="1" max="200" required value="100">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">100% = tiempo normal</small>
                                    <div class="invalid-feedback" id="edit_error_time_percentage"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Multiplicador de Precio <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">x</span>
                                        </div>
                                        <input type="number" class="form-control" id="edit_price_multiplier"
                                            name="price_multiplier" min="0.5" max="5" step="0.05" required value="1.00">
                                    </div>
                                    <small class="text-muted">1.00 = precio normal</small>
                                    <div class="invalid-feedback" id="edit_error_price_multiplier"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Color <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="color" id="edit_colorPicker" value="#28a745"
                                            style="width: 40px; height: 31px; padding: 2px; border: 1px solid #ced4da;">
                                        <input type="text" class="form-control" id="edit_color"
                                            name="color" maxlength="20" required value="#28a745">
                                    </div>
                                    <div class="invalid-feedback" id="edit_error_color"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Icono (FontAwesome)</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i id="edit_iconPreview" class="fas fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="edit_icon"
                                            name="icon" maxlength="50" placeholder="fa-clock">
                                    </div>
                                    <div class="invalid-feedback" id="edit_error_icon"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control form-control-sm" id="edit_description"
                                name="description" rows="2" maxlength="500" placeholder="Descripción opcional"></textarea>
                            <div class="invalid-feedback" id="edit_error_description"></div>
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR NIVEL DE URGENCIA
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-clock" style="margin-right: 10px;"></i>
                            Datos del Nivel
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_name" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este nivel de urgencia?</p>
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
        .modal-header.bg-warning .close, .modal-header.bg-danger .close, .modal-header.bg-primary .close { opacity: 1; }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Niveles",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Niveles",
                    "infoFiltered": "(Filtrado de _MAX_ total Niveles)",
                    "lengthMenu": "Mostrar _MENU_ Niveles",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [[5, 'asc']],
                buttons: [
                    { text: '<i class="fas fa-copy"></i> COPIAR', extend: 'copy', className: 'btn btn-default' },
                    { text: '<i class="fas fa-file-pdf"></i> PDF', extend: 'pdf', className: 'btn btn-danger' },
                    { text: '<i class="fas fa-file-csv"></i> CSV', extend: 'csv', className: 'btn btn-info' },
                    { text: '<i class="fas fa-file-excel"></i> EXCEL', extend: 'excel', className: 'btn btn-success' },
                    { text: '<i class="fas fa-print"></i> IMPRIMIR', extend: 'print', className: 'btn btn-default' }
                ]
            });
            table.buttons().container().appendTo('#example1_wrapper .row:eq(0)');

            // === Sincronizar color picker en modal crear ===
            $('#create_colorPicker').on('input', function() {
                $('#create_color').val($(this).val());
                updateCreatePreview();
            });
            $('#create_color').on('input', function() {
                $('#create_colorPicker').val($(this).val());
                updateCreatePreview();
            });
            $('#create_icon').on('input', function() {
                var icon = $(this).val() || 'fa-clock';
                $('#create_iconPreview').attr('class', 'fas ' + icon);
                updateCreatePreview();
            });

            // Actualizar preview en tiempo real para modal crear
            $('#create_name, #create_time_percentage, #create_price_multiplier').on('input', updateCreatePreview);

            function updateCreatePreview() {
                var name = $('#create_name').val() || 'NOMBRE';
                var color = $('#create_color').val() || '#28a745';
                var icon = $('#create_icon').val() || 'fa-clock';
                var time = $('#create_time_percentage').val() || 100;
                var price = $('#create_price_multiplier').val() || '1.00';

                $('#create_previewBadge').css('background-color', color);
                $('#create_previewBadge i').attr('class', 'fas ' + icon + ' mr-1');
                $('#create_previewName').text(name.toUpperCase());
                $('#create_previewTime').text(time);
                $('#create_previewPrice').text(parseFloat(price).toFixed(2));
            }

            // === Sincronizar color picker en modal editar ===
            $('#edit_colorPicker').on('input', function() {
                $('#edit_color').val($(this).val());
            });
            $('#edit_color').on('input', function() {
                $('#edit_colorPicker').val($(this).val());
            });
            $('#edit_icon').on('input', function() {
                var icon = $(this).val() || 'fa-clock';
                $('#edit_iconPreview').attr('class', 'fas ' + icon);
            });

            // === CREAR ===
            $('#btnNuevo').on('click', function() {
                // Resetear formulario a valores por defecto
                $('#formCreate')[0].reset();
                $('#create_name').val('').removeClass('is-invalid');
                $('#create_time_percentage').val(100);
                $('#create_price_multiplier').val('1.00');
                $('#create_color').val('#28a745');
                $('#create_colorPicker').val('#28a745');
                $('#create_icon').val('');
                $('#create_iconPreview').attr('class', 'fas fa-clock');
                $('#create_sort_order').val(0);
                $('#create_description').val('');
                $('.invalid-feedback').text('');
                $('#formCreate .form-control').removeClass('is-invalid');
                updateCreatePreview();
                $('#modalCreate').modal('show');
            });

            $('#formCreate').on('submit', function(e) {
                e.preventDefault();
                var name = $('#create_name').val().trim();
                if (!name) {
                    $('#create_name').addClass('is-invalid');
                    $('#create_error_name').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '{{ route("admin.urgency-levels.store") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        time_percentage: $('#create_time_percentage').val(),
                        price_multiplier: $('#create_price_multiplier').val(),
                        color: $('#create_color').val(),
                        icon: $('#create_icon').val(),
                        sort_order: $('#create_sort_order').val(),
                        description: $('#create_description').val()
                    },
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            var d = response.data;
                            var iconHtml = d.icon ? '<i class="fas ' + d.icon + ' mr-1" style="color: ' + d.color + ';"></i>' : '';
                            var descHtml = d.description ? '<small class="text-muted d-block">' + d.description + '</small>' : '';
                            var newRow = table.row.add([
                                table.rows().count() + 1,
                                '<td class="level-name text-left">' + iconHtml + '<span class="nombre-level">' + d.name + '</span>' + descHtml + '</td>',
                                '<span class="badge badge-secondary">' + d.time_percentage + '%</span>',
                                '<span class="badge badge-info">x' + parseFloat(d.price_multiplier).toFixed(2) + '</span>',
                                '<span class="badge" style="background-color: ' + d.color + '; color: white; padding: 5px 15px;">' + d.color + '</span>',
                                d.sort_order,
                                '<button type="button" class="btn btn-warning btn-edit" data-id="' + d.id + '" data-name="' + d.name + '" data-time="' + d.time_percentage + '" data-multiplier="' + d.price_multiplier + '" data-color="' + d.color + '" data-icon="' + (d.icon || '') + '" data-order="' + d.sort_order + '" data-description="' + (d.description || '') + '"><i class="fas fa-edit"></i></button> ' +
                                '<button type="button" class="btn btn-danger btn-delete" data-id="' + d.id + '" data-name="' + d.name + '"><i class="fas fa-trash"></i></button>'
                            ]).draw(false).node();
                            $(newRow).attr('data-id', d.id).css('text-align', 'center');
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
                            var errors = xhr.responseJSON.errors || {};
                            if (xhr.responseJSON.message && !xhr.responseJSON.errors) {
                                $('#create_name').addClass('is-invalid');
                                $('#create_error_name').text(xhr.responseJSON.message);
                            } else {
                                $.each(errors, function(field, messages) {
                                    $('#create_' + field).addClass('is-invalid');
                                    $('#create_error_' + field).text(messages[0]);
                                });
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
                var $btn = $(this);
                $('#edit_id').val($btn.data('id'));
                $('#edit_name').val($btn.data('name')).removeClass('is-invalid');
                $('#edit_time_percentage').val($btn.data('time'));
                $('#edit_price_multiplier').val($btn.data('multiplier'));
                $('#edit_color').val($btn.data('color'));
                $('#edit_colorPicker').val($btn.data('color'));
                $('#edit_icon').val($btn.data('icon'));
                $('#edit_iconPreview').attr('class', 'fas ' + ($btn.data('icon') || 'fa-clock'));
                $('#edit_sort_order').val($btn.data('order'));
                $('#edit_description').val($btn.data('description'));
                $('.invalid-feedback').text('');
                $('#modalEdit').modal('show');
            });

            $('#formEdit').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_id').val();
                var name = $('#edit_name').val().trim();
                if (!name) {
                    $('#edit_name').addClass('is-invalid');
                    $('#edit_error_name').text('El nombre es obligatorio');
                    return;
                }
                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                $.ajax({
                    url: '/admin/niveles-urgencia/edit/' + id,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        time_percentage: $('#edit_time_percentage').val(),
                        price_multiplier: $('#edit_price_multiplier').val(),
                        color: $('#edit_color').val(),
                        icon: $('#edit_icon').val(),
                        sort_order: $('#edit_sort_order').val(),
                        description: $('#edit_description').val()
                    },
                    success: function(response) {
                        $('#modalEdit').modal('hide');
                        if (response.success) {
                            var d = response.data;
                            var row = $('tr[data-id="' + id + '"]');
                            var iconHtml = d.icon ? '<i class="fas ' + d.icon + ' mr-1" style="color: ' + d.color + ';"></i>' : '';
                            var descHtml = d.description ? '<small class="text-muted d-block">' + d.description + '</small>' : '';
                            row.find('.level-name').html(iconHtml + '<span class="nombre-level">' + d.name + '</span>' + descHtml);
                            row.find('td:eq(2)').html('<span class="badge badge-secondary">' + d.time_percentage + '%</span>');
                            row.find('td:eq(3)').html('<span class="badge badge-info">x' + parseFloat(d.price_multiplier).toFixed(2) + '</span>');
                            row.find('td:eq(4)').html('<span class="badge" style="background-color: ' + d.color + '; color: white; padding: 5px 15px;">' + d.color + '</span>');
                            row.find('td:eq(5)').text(d.sort_order);
                            // Actualizar data attributes en botones
                            row.find('.btn-edit').data({id: d.id, name: d.name, time: d.time_percentage, multiplier: d.price_multiplier, color: d.color, icon: d.icon || '', order: d.sort_order, description: d.description || ''});
                            row.find('.btn-delete').data({id: d.id, name: d.name});
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
                            $.each(errors, function(field, messages) {
                                $('#edit_' + field).addClass('is-invalid');
                                $('#edit_error_' + field).text(messages[0]);
                            });
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
                var name = $(this).data('name');
                $('#delete_id').val(id);
                $('#delete_name').val(name);
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
                        Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        $.ajax({
                            url: '/admin/niveles-urgencia/delete/' + id,
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
