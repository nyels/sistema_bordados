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
                    <a href="{{ route('admin.material-categories.create') }}" class="btn btn-primary">
                        Nuevo <i class="fas fa-plus"></i>
                    </a>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#manageUnitsModal">
                        <i class="fas fa-link"></i> Gestionar Unidades Permitidas
                    </button>
                </div>
            </div>
            <hr>

            <div class="table-responsive" id="mainTableContainer">
                @include('admin.material-categories.partials.table')
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
            /* Centrar los botones */
            flex-wrap: wrap;
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
            /* TamaÃ±o de fuente */
        }

        /* Colores por tipo de botÃ³n */
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
    </style> {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
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
                        <i class="fas fa-link mr-2"></i> Gestión de Unidades Permitidas
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
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


    <script>
        $(function() {
            // Lógica para abrir el modal y cargar materiales
            $('.btn-show-materials').on('click', function() {
                var categoryId = $(this).data('id');
                var categoryName = $(this).data('name');

                // Configurar titulo
                $('#materialsModalLabel').text(categoryName);

                // Limpiar tabla
                var tbody = $('#materialsTable tbody');
                tbody.html(
                    '<tr><td colspan="2"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');

                // Abrir modal
                $('#materialsModal').modal('show');

                // Petición AJAX (Necesitamos una ruta para esto, la crearemos en breve)
                // Usaremos: /admin/material-categories/{id}/materials
                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/get-materials',
                    type: 'GET',
                    success: function(response) {
                        tbody.empty();
                        if (response.length > 0) {
                            $.each(response, function(index, material) {
                                tbody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td class="font-weight-bold text-dark">${material.name}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            tbody.html(
                                '<tr><td colspan="2" class="text-muted">Sin materiales asociados</td></tr>'
                            );
                        }
                    },
                    error: function() {
                        tbody.html(
                            '<tr><td colspan="2" class="text-danger">Error al cargar datos</td></tr>'
                        );
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

            // Cargar datos al cambiar categoría
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
                $unitSelect.html('<option value="">Primero selecciona categoría...</option>').prop('disabled',
                    true);
                $assignBtn.prop('disabled', true);
                $tableBody.html(
                    '<tr><td colspan="4" class="text-muted py-4"><i class="fas fa-arrow-up mr-2"></i> Selecciona una categoría arriba.</td></tr>'
                );
                $countBadge.text('0 Unidades');
            }

            function loadCategoryDetails(categoryId) {
                // Mostrar Loading
                $tableBody.html(
                    '<tr><td colspan="4" class="py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></td></tr>'
                );
                $unitSelect.prop('disabled', true).html('<option>Cargando...</option>');

                // 1. Cargar Unidades Asignadas (Tabla)
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

                // 2. Cargar Unidades Disponibles (Select)
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
                    $tableBody.html(
                        '<tr><td colspan="4" class="text-muted font-italic">No hay unidades asignadas aún.</td></tr>'
                    );
                    return;
                }

                units.forEach(unit => {
                    let btnHtml = '';
                    if (unit.can_remove) {
                        btnHtml = `
                            <button class="btn btn-danger btn-sm btn-remove-unit shadow-sm" 
                                data-id="${unit.id}" data-name="${unit.name}">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    } else {
                        btnHtml = `
                            <span class="text-muted" title="${unit.materials_using} materiales usan esta unidad">
                                <i class="fas fa-lock"></i>
                            </span>
                        `;
                    }

                    $tableBody.append(`
                        <tr>
                            <td class="font-weight-bold text-dark">${unit.name}</td>
                            <td><span class="badge badge-secondary px-2">${unit.symbol}</span></td>
                            <td>${unit.materials_using > 0 
                                ? '<span class="badge badge-info">' + unit.materials_using + '</span>' 
                                : '<span class="text-muted">-</span>'}
                            </td>
                            <td>${btnHtml}</td>
                        </tr>
                    `);
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
                        $unitSelect.append(
                            `<option value="${unit.id}">${unit.name} (${unit.symbol})</option>`);
                    });
                    $unitSelect.prop('disabled', false);
                }
            }

            // Habilitar botón guardar solo si hay selección
            $unitSelect.on('change', function() {
                $assignBtn.prop('disabled', !$(this).val());
            });

            // ACCIÓN: GUARDAR ASIGNACIÓN
            $assignBtn.on('click', function() {
                const categoryId = $categorySelect.val();
                const unitId = $unitSelect.val();

                if (!categoryId || !unitId) return;

                // Deshabilitar UI
                $assignBtn.html('<i class="fas fa-spinner fa-spin"></i> Guardando...').prop('disabled',
                    true);

                $.ajax({
                    url: '/admin/material-categories/' + categoryId + '/units',
                    type: 'POST',
                    data: {
                        unit_id: unitId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        changesMade = true; // MARCAR CAMBIO

                        // Toast Success
                        Swal.fire({
                            icon: 'success',
                            title: 'Guardado',
                            text: 'Unidad asignada correctamente',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });

                        // Recargar datos
                        loadCategoryDetails(categoryId);

                        // Reset boton
                        $assignBtn.html('<i class="fas fa-save mr-1"></i> Asignar');

                        // REFRESCAR DATATABLE
                        refreshMainTable();
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Error al guardar';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        $assignBtn.html('<i class="fas fa-save mr-1"></i> Asignar').prop(
                            'disabled', false);
                    }
                });
            });

            // ACCIÓN: ELIMINAR ASIGNACIÓN
            $(document).on('click', '.btn-remove-unit', function() {
                const categoryId = $categorySelect.val();
                const unitId = $(this).data('id');
                const unitName = $(this).data('name');

                Swal.fire({
                    title: '¿Quitar unidad?',
                    text: `Se desvinculará la unidad "${unitName}" de esta categoría.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, quitar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/material-categories/' + categoryId + '/units/' +
                                unitId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                changesMade = true; // MARCAR CAMBIO

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: 'La unidad fue desvinculada',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                                loadCategoryDetails(categoryId);

                                // REFRESCAR DATATABLE
                                refreshMainTable();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'No se pudo eliminar',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 4000
                                });
                            }
                        });
                    }
                });
            });

            // FLAG PARA DETECTAR CAMBIOS
            let changesMade = false;

            // Al abrir modal, resetear flag
            $('#manageUnitsModal').on('show.bs.modal', function() {
                changesMade = false;
            });

            // Al cerrar modal, limpiar flag (ya no refrescamos aquí)
            $('#manageUnitsModal').on('hidden.bs.modal', function() {
                // Ahora el refresco es inmediato al guardar/eliminar
            });

            // FUNCION PARA REFRESCAR TABLA PRINCIPAL
            function refreshMainTable() {
                // Destruir instancia previa si existe
                if ($.fn.DataTable.isDataTable('#example1')) {
                    $('#example1').DataTable().destroy();
                }

                // Construir URL robusta
                var currentUrl = window.location.href.split('#')[0];
                var urlObj = new URL(currentUrl);
                urlObj.searchParams.set('t', new Date().getTime());
                var tableUrl = urlObj.toString();

                $.get(tableUrl, function(html) {
                    $("#mainTableContainer").html(html);

                    // Reinicializar DataTable
                    $("#example1").DataTable({
                        "pageLength": 10,
                        "language": {
                            "emptyTable": "No hay informacion",
                            "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorías",
                            "infoEmpty": "Mostrando 0 a 0 de 0 Categorías",
                            "infoFiltered": "(Filtrado de _MAX_ total Categorías)",
                            "lengthMenu": "Mostrar _MENU_ Categorías",
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
                    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                }).fail(function() {
                    console.error("Error al refrescar tabla");
                    location.reload(); // Fallback
                });
            }

            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Categorías",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Categorías",
                    "infoFiltered": "(Filtrado de _MAX_ total Categorías)",
                    "lengthMenu": "Mostrar _MENU_ Categorías",
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
            }).buttons().container().appendTo('#example1_wrapper .row:eq(0)');
        });
    </script>
@stop
