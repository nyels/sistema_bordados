@extends('adminlte::page')

@section('title', 'Extras de Productos')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary">

        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> EXTRAS DE PRODUCTOS</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <button type="button" class="btn btn-primary" id="btnNuevo">
                    Nuevo <i class="fas fa-plus"></i></button>
            </div>
            <hr>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Consumo</th>
                                <th>Costo</th>
                                <th>Precio</th>
                                <th>Tiempo</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($extras as $extra)
                                <tr data-id="{{ $extra->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="extra-name">{{ $extra->name }}</td>
                                    <td>{{ $extra->category->nombre ?? '-' }}</td>
                                    <td>{!! $extra->materials_summary !!}</td>
                                    <td>${{ number_format($extra->cost_addition, 2) }}</td>
                                    <td>${{ number_format($extra->price_addition, 2) }}</td>
                                    <td>{{ $extra->formatted_minutes }}</td>
                                    <td class="text-center">
                                        @php
                                            $materialsData = $extra->materials->map(function($m) {
                                                return [
                                                    'id' => $m->pivot->material_variant_id,
                                                    'quantity' => $m->pivot->quantity_required,
                                                    'name' => ($m->material->name ?? 'Material') . ($m->color ? ' - ' . $m->color : ''),
                                                    'unit' => $m->material->consumptionUnit->symbol ?? ''
                                                ];
                                            });
                                        @endphp
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <button type="button" class="btn btn-warning btn-sm btn-edit"
                                                data-id="{{ $extra->id }}"
                                                data-name="{{ $extra->name }}"
                                                data-category="{{ $extra->extra_category_id }}"
                                                data-cost="{{ $extra->cost_addition }}"
                                                data-price="{{ $extra->price_addition }}"
                                                data-minutes="{{ $extra->minutes_addition }}"
                                                data-consumes="{{ $extra->consumes_inventory ? 1 : 0 }}"
                                                data-materials="{{ $materialsData->toJson() }}"
                                                title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                data-id="{{ $extra->id }}"
                                                data-name="{{ $extra->name }}" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
                        <i class="fas fa-plus mr-2"></i> NUEVO EXTRA DE PRODUCTO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formCreate">
                    <div class="modal-body py-3">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 6px; margin-bottom: 12px;">
                            <h6 style="color: #007bff; font-weight: 600; margin: 0;">
                                <i class="fas fa-plus-circle"></i> Datos del Extra
                            </h6>
                        </div>

                        {{-- Nombre y Categoría --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Nombre del Extra <span style="color: red;">*</span></label>
                                    <input type="text" name="name" id="create_name"
                                        class="form-control form-control-sm"
                                        required placeholder="Ej: Empaque especial" maxlength="100">
                                    <div class="invalid-feedback" id="create_error_name"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Categoría</label>
                                    <select name="extra_category_id" id="create_extra_category_id" class="form-control form-control-sm">
                                        <option value="">-- Sin categoría --</option>
                                        @foreach ($categories ?? [] as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="create_error_extra_category_id"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Costo, Precio y Tiempo --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Costo <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="cost_addition" id="create_cost_addition"
                                            class="form-control form-control-sm"
                                            required step="0.01" min="0" placeholder="25.00">
                                    </div>
                                    <div class="invalid-feedback" id="create_error_cost_addition"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Precio <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="price_addition" id="create_price_addition"
                                            class="form-control form-control-sm"
                                            required step="0.01" min="0" placeholder="50.00">
                                    </div>
                                    <div class="invalid-feedback" id="create_error_price_addition"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Tiempo (min)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="minutes_addition" id="create_minutes_addition"
                                            class="form-control form-control-sm"
                                            min="0" max="9999" step="1" value="0" placeholder="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="create_error_minutes_addition"></div>
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN DE INVENTARIO --}}
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 6px; margin-bottom: 12px; margin-top: 15px;">
                            <h6 style="color: #6c757d; font-weight: 600; margin: 0;">
                                <i class="fas fa-boxes"></i> Control de Inventario
                            </h6>
                        </div>

                        {{-- Checkbox: Consume inventario --}}
                        <div class="form-group mb-2">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="create_consumes_inventory"
                                    name="consumes_inventory" value="1">
                                <label class="custom-control-label" for="create_consumes_inventory">
                                    <strong>Este extra consume materiales</strong>
                                    <small class="text-muted ml-2">(encaje, listón, moños, etc.)</small>
                                </label>
                            </div>
                        </div>

                        {{-- Sección de materiales (oculta por defecto) --}}
                        <div id="create_materials_section" style="display: none;">
                            <div class="p-3 rounded mb-3" style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle text-info mr-2"></i>
                                    <small style="color: #495057;">
                                        Este servicio descontará inventario al usarse en producción.
                                    </small>
                                </div>

                                {{-- Select + Cantidad + Botón en una fila --}}
                                <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
                                    <select id="create_material_select" class="form-control form-control-sm" style="flex: 1; min-width: 200px;">
                                        <option value="" data-unit="-" data-family="">-- Seleccione material --</option>
                                    </select>
                                    <div class="input-group input-group-sm" style="width: 130px;">
                                        <input type="number" id="create_material_quantity" class="form-control"
                                            step="1" min="1" placeholder="Cant." value="">
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="create_material_unit" style="min-width: 40px;">-</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="create_btn_add_material">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>

                                {{-- Tabla de materiales agregados --}}
                                <div id="create_materials_table_container" style="display: none;">
                                    <table class="table table-sm table-bordered mb-0" id="create_materials_table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Material</th>
                                                <th style="width: 100px;" class="text-center">Cantidad</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="create_materials_list">
                                        </tbody>
                                    </table>
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
                        <i class="fas fa-edit mr-2"></i> EDITAR EXTRA DE PRODUCTO
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEdit">
                    <input type="hidden" id="edit_id">
                    <div class="modal-body py-3">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 6px; margin-bottom: 12px;">
                            <h6 style="color: #856404; font-weight: 600; margin: 0;">
                                <i class="fas fa-edit"></i> Datos del Extra
                            </h6>
                        </div>

                        {{-- Nombre y Categoría --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Nombre del Extra <span style="color: red;">*</span></label>
                                    <input type="text" name="name" id="edit_name"
                                        class="form-control form-control-sm"
                                        required placeholder="Ej: Empaque especial" maxlength="100">
                                    <div class="invalid-feedback" id="edit_error_name"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Categoría</label>
                                    <select name="extra_category_id" id="edit_extra_category_id" class="form-control form-control-sm">
                                        <option value="">-- Sin categoría --</option>
                                        @foreach ($categories ?? [] as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="edit_error_extra_category_id"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Costo, Precio y Tiempo --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Costo <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="cost_addition" id="edit_cost_addition"
                                            class="form-control form-control-sm"
                                            required step="0.01" min="0" placeholder="25.00">
                                    </div>
                                    <div class="invalid-feedback" id="edit_error_cost_addition"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Precio <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="price_addition" id="edit_price_addition"
                                            class="form-control form-control-sm"
                                            required step="0.01" min="0" placeholder="50.00">
                                    </div>
                                    <div class="invalid-feedback" id="edit_error_price_addition"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Tiempo (min)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="minutes_addition" id="edit_minutes_addition"
                                            class="form-control form-control-sm"
                                            min="0" max="9999" step="1" value="0" placeholder="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="edit_error_minutes_addition"></div>
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN DE INVENTARIO --}}
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 6px; margin-bottom: 12px; margin-top: 15px;">
                            <h6 style="color: #6c757d; font-weight: 600; margin: 0;">
                                <i class="fas fa-boxes"></i> Control de Inventario
                            </h6>
                        </div>

                        {{-- Checkbox: Consume inventario --}}
                        <div class="form-group mb-2">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="edit_consumes_inventory"
                                    name="consumes_inventory" value="1">
                                <label class="custom-control-label" for="edit_consumes_inventory">
                                    <strong>Este extra consume materiales</strong>
                                    <small class="text-muted ml-2">(encaje, listón, moños, etc.)</small>
                                </label>
                            </div>
                        </div>

                        {{-- Sección de materiales --}}
                        <div id="edit_materials_section" style="display: none;">
                            <div class="p-3 rounded mb-3" style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle text-info mr-2"></i>
                                    <small style="color: #495057;">
                                        Este servicio descontará inventario al usarse en producción.
                                    </small>
                                </div>

                                {{-- Select + Cantidad + Botón en una fila --}}
                                <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
                                    <select id="edit_material_select" class="form-control form-control-sm" style="flex: 1; min-width: 200px;">
                                        <option value="" data-unit="-" data-family="">-- Seleccione material --</option>
                                    </select>
                                    <div class="input-group input-group-sm" style="width: 130px;">
                                        <input type="number" id="edit_material_quantity" class="form-control"
                                            step="1" min="1" placeholder="Cant." value="">
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="edit_material_unit" style="min-width: 40px;">-</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-warning" id="edit_btn_add_material">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </div>

                                {{-- Tabla de materiales agregados --}}
                                <div id="edit_materials_table_container" style="display: none;">
                                    <table class="table table-sm table-bordered mb-0" id="edit_materials_table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Material</th>
                                                <th style="width: 100px;" class="text-center">Cantidad</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="edit_materials_list">
                                        </tbody>
                                    </table>
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
                        <i class="fas fa-trash mr-2"></i> ELIMINAR EXTRA DE PRODUCTO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="delete_id">
                    <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                        <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                            <i class="fas fa-plus-circle" style="margin-right: 10px;"></i>
                            Datos del Extra
                        </h5>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="delete_name" disabled>
                    </div>
                    <div class="alert alert-warning text-center mt-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0" style="font-weight: bold; font-size: 16px;">¿Deseas eliminar este extra?</p>
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
            // ============================================================
            // DATOS GLOBALES
            // ============================================================
            var materialVariants = @json($materialVariants ?? []);
            var decimalFamilies = ['linear'];

            // Variables para modal crear
            var createMaterialIndex = 0;
            var createAddedMaterials = [];

            // Variables para modal editar
            var editMaterialIndex = 0;
            var editAddedMaterials = [];

            // ============================================================
            // FUNCION PARA RECARGAR TABLA SIN RECARGAR PAGINA
            // ============================================================
            function reloadTable() {
                $.ajax({
                    url: '/product_extras/ajax/table',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Destruir DataTable existente
                            if ($.fn.DataTable.isDataTable('#example1')) {
                                $('#example1').DataTable().destroy();
                            }
                            // Reemplazar tbody
                            $('#example1 tbody').html(response.html);
                            // Reinicializar DataTable
                            var table = $("#example1").DataTable({
                                "pageLength": 10,
                                "language": {
                                    "emptyTable": "No hay información",
                                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Extras",
                                    "infoEmpty": "Mostrando 0 a 0 de 0 Extras",
                                    "infoFiltered": "(Filtrado de _MAX_ total Extras)",
                                    "lengthMenu": "Mostrar _MENU_ Extras",
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
                        }
                    }
                });
            }

            // ============================================================
            // DATATABLE
            // ============================================================
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Extras",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Extras",
                    "infoFiltered": "(Filtrado de _MAX_ total Extras)",
                    "lengthMenu": "Mostrar _MENU_ Extras",
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

            // ============================================================
            // FUNCIONES DE MATERIALES - CREAR
            // ============================================================
            function initCreateMaterialSelect() {
                var $select = $('#create_material_select');
                $select.empty().append('<option value="" data-unit="-" data-family="">-- Seleccione material --</option>');

                materialVariants.forEach(function(v) {
                    if (createAddedMaterials.includes(v.id)) return;

                    var unit = v.material ? (v.material.consumptionUnit || v.material.consumption_unit || null) : null;
                    var unitSymbol = unit ? unit.symbol : '';
                    var unitFamily = unit ? (unit.measurement_family || '') : '';
                    var materialName = v.material ? v.material.name : 'Material #' + v.id;
                    var label = materialName + (v.color ? ' - ' + v.color : '');

                    $select.append('<option value="' + v.id + '" data-unit="' + unitSymbol +
                        '" data-family="' + unitFamily + '" data-name="' + label + '">' + label + '</option>');
                });
            }

            function updateCreateQuantityInput() {
                var $selected = $('#create_material_select option:selected');
                var unit = $selected.data('unit') || '-';
                var family = $selected.data('family') || '';

                $('#create_material_unit').text(unit);
                $('#create_material_quantity').val('');

                if (decimalFamilies.includes(family)) {
                    $('#create_material_quantity').attr({ step: '0.01', min: '0.01', placeholder: '0.00' });
                } else {
                    $('#create_material_quantity').attr({ step: '1', min: '1', placeholder: '0' });
                }
            }

            // ============================================================
            // FUNCIONES DE MATERIALES - EDITAR
            // ============================================================
            function initEditMaterialSelect() {
                var $select = $('#edit_material_select');
                $select.empty().append('<option value="" data-unit="-" data-family="">-- Seleccione material --</option>');

                materialVariants.forEach(function(v) {
                    if (editAddedMaterials.includes(v.id)) return;

                    var unit = v.material ? (v.material.consumptionUnit || v.material.consumption_unit || null) : null;
                    var unitSymbol = unit ? unit.symbol : '';
                    var unitFamily = unit ? (unit.measurement_family || '') : '';
                    var materialName = v.material ? v.material.name : 'Material #' + v.id;
                    var label = materialName + (v.color ? ' - ' + v.color : '');

                    $select.append('<option value="' + v.id + '" data-unit="' + unitSymbol +
                        '" data-family="' + unitFamily + '" data-name="' + label + '">' + label + '</option>');
                });
            }

            function updateEditQuantityInput() {
                var $selected = $('#edit_material_select option:selected');
                var unit = $selected.data('unit') || '-';
                var family = $selected.data('family') || '';

                $('#edit_material_unit').text(unit);
                $('#edit_material_quantity').val('');

                if (decimalFamilies.includes(family)) {
                    $('#edit_material_quantity').attr({ step: '0.01', min: '0.01', placeholder: '0.00' });
                } else {
                    $('#edit_material_quantity').attr({ step: '1', min: '1', placeholder: '0' });
                }
            }

            function addEditMaterialToTable(variantId, quantity, materialName, unit) {
                var html = '<tr data-variant-id="' + variantId + '">' +
                    '<td>' + materialName +
                    '<input type="hidden" name="materials[' + editMaterialIndex + '][variant_id]" value="' + variantId + '"></td>' +
                    '<td class="text-center">' + quantity + ' ' + unit +
                    '<input type="hidden" name="materials[' + editMaterialIndex + '][quantity]" value="' + quantity + '"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-edit-material">' +
                    '<i class="fas fa-trash-alt"></i></button></td></tr>';

                $('#edit_materials_list').append(html);
                $('#edit_materials_table_container').show();
                editMaterialIndex++;
                editAddedMaterials.push(parseInt(variantId));
            }

            // ============================================================
            // EVENTOS - MODAL CREAR
            // ============================================================
            $('#create_consumes_inventory').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#create_materials_section').slideDown(200);
                    initCreateMaterialSelect();
                } else {
                    $('#create_materials_section').slideUp(200);
                }
            });

            $('#create_material_select').on('change', updateCreateQuantityInput);

            $('#create_btn_add_material').on('click', function() {
                var $selected = $('#create_material_select option:selected');
                var variantId = $('#create_material_select').val();
                var quantity = $('#create_material_quantity').val();

                if (!variantId) {
                    Swal.fire({ icon: 'warning', title: 'Seleccione un material', timer: 1500, showConfirmButton: false });
                    return;
                }
                if (!quantity || parseFloat(quantity) <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Ingrese una cantidad válida', timer: 1500, showConfirmButton: false });
                    return;
                }

                var html = '<tr data-variant-id="' + variantId + '">' +
                    '<td>' + $selected.data('name') +
                    '<input type="hidden" name="materials[' + createMaterialIndex + '][variant_id]" value="' + variantId + '"></td>' +
                    '<td class="text-center">' + quantity + ' ' + ($selected.data('unit') || '') +
                    '<input type="hidden" name="materials[' + createMaterialIndex + '][quantity]" value="' + quantity + '"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove-create-material">' +
                    '<i class="fas fa-trash-alt"></i></button></td></tr>';

                $('#create_materials_list').append(html);
                $('#create_materials_table_container').show();
                createMaterialIndex++;
                createAddedMaterials.push(parseInt(variantId));
                initCreateMaterialSelect();
                $('#create_material_quantity').val('');
                $('#create_material_unit').text('-');
            });

            $(document).on('click', '.btn-remove-create-material', function() {
                var $row = $(this).closest('tr');
                var variantId = parseInt($row.data('variant-id'));
                createAddedMaterials = createAddedMaterials.filter(function(id) { return id !== variantId; });
                $row.remove();
                if ($('#create_materials_list tr').length === 0) $('#create_materials_table_container').hide();
                initCreateMaterialSelect();
            });

            // ============================================================
            // EVENTOS - MODAL EDITAR
            // ============================================================
            $('#edit_consumes_inventory').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#edit_materials_section').slideDown(200);
                    initEditMaterialSelect();
                } else {
                    $('#edit_materials_section').slideUp(200);
                }
            });

            $('#edit_material_select').on('change', updateEditQuantityInput);

            $('#edit_btn_add_material').on('click', function() {
                var $selected = $('#edit_material_select option:selected');
                var variantId = $('#edit_material_select').val();
                var quantity = $('#edit_material_quantity').val();

                if (!variantId) {
                    Swal.fire({ icon: 'warning', title: 'Seleccione un material', timer: 1500, showConfirmButton: false });
                    return;
                }
                if (!quantity || parseFloat(quantity) <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Ingrese una cantidad válida', timer: 1500, showConfirmButton: false });
                    return;
                }

                addEditMaterialToTable(variantId, quantity, $selected.data('name'), $selected.data('unit') || '');
                initEditMaterialSelect();
                $('#edit_material_quantity').val('');
                $('#edit_material_unit').text('-');
            });

            $(document).on('click', '.btn-remove-edit-material', function() {
                var $row = $(this).closest('tr');
                var variantId = parseInt($row.data('variant-id'));
                editAddedMaterials = editAddedMaterials.filter(function(id) { return id !== variantId; });
                $row.remove();
                if ($('#edit_materials_list tr').length === 0) $('#edit_materials_table_container').hide();
                initEditMaterialSelect();
            });

            // ============================================================
            // ABRIR MODAL CREAR
            // ============================================================
            $('#btnNuevo').on('click', function() {
                // Resetear formulario
                $('#formCreate')[0].reset();
                $('#create_name').val('').removeClass('is-invalid');
                $('#create_extra_category_id').val('');
                $('#create_cost_addition').val('');
                $('#create_price_addition').val('');
                $('#create_minutes_addition').val('0');
                $('#create_consumes_inventory').prop('checked', false);
                $('#create_materials_section').hide();
                $('#create_materials_list').empty();
                $('#create_materials_table_container').hide();
                createMaterialIndex = 0;
                createAddedMaterials = [];
                $('.invalid-feedback').text('');
                $('#formCreate .form-control').removeClass('is-invalid');
                $('#modalCreate').modal('show');
            });

            // ============================================================
            // SUBMIT CREAR
            // ============================================================
            $('#formCreate').on('submit', function(e) {
                e.preventDefault();

                // Validar campos
                var errors = [];
                var name = $('#create_name').val().trim();
                var cost = $('#create_cost_addition').val();
                var price = $('#create_price_addition').val();
                var consumesInventory = $('#create_consumes_inventory').is(':checked');

                if (!name) errors.push('El nombre del extra es obligatorio');
                if (!cost || parseFloat(cost) < 0) errors.push('El costo adicional es obligatorio');
                if (!price || parseFloat(price) < 0) errors.push('El precio al cliente es obligatorio');
                if (consumesInventory && $('#create_materials_list tr').length === 0) {
                    errors.push('Debe agregar al menos un material si el extra consume inventario');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos requeridos',
                        html: '<ul style="text-align:left;">' + errors.map(function(e) {
                            return '<li>' + e + '</li>';
                        }).join('') + '</ul>',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                // Preparar datos
                var formData = {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    extra_category_id: $('#create_extra_category_id').val() || null,
                    cost_addition: cost,
                    price_addition: price,
                    minutes_addition: $('#create_minutes_addition').val() || 0,
                    consumes_inventory: consumesInventory ? 1 : 0,
                    materials: []
                };

                // Recoger materiales
                $('#create_materials_list tr').each(function() {
                    formData.materials.push({
                        variant_id: $(this).find('input[name*="variant_id"]').val(),
                        quantity: $(this).find('input[name*="quantity"]').val()
                    });
                });

                $('#btnSaveCreate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $.ajax({
                    url: '{{ route("admin.product_extras.store") }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#modalCreate').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Creado',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            reloadTable();
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

            // ============================================================
            // ABRIR MODAL EDITAR (sin AJAX - datos desde data-*)
            // ============================================================
            $(document).on('click', '.btn-edit', function() {
                var $btn = $(this);

                // Resetear
                $('#formEdit')[0].reset();
                $('#edit_materials_list').empty();
                $('#edit_materials_table_container').hide();
                $('#edit_materials_section').hide();
                editMaterialIndex = 0;
                editAddedMaterials = [];
                $('.invalid-feedback').text('');
                $('#formEdit .form-control').removeClass('is-invalid');

                // Cargar datos desde data-* attributes
                $('#edit_id').val($btn.data('id'));
                $('#edit_name').val($btn.data('name'));
                $('#edit_extra_category_id').val($btn.data('category') || '');
                $('#edit_cost_addition').val($btn.data('cost'));
                $('#edit_price_addition').val($btn.data('price'));
                $('#edit_minutes_addition').val($btn.data('minutes') || 0);

                var consumesInventory = $btn.data('consumes') == 1;
                $('#edit_consumes_inventory').prop('checked', consumesInventory);

                if (consumesInventory) {
                    $('#edit_materials_section').show();
                    // Cargar materiales desde data-materials
                    var materials = $btn.data('materials') || [];
                    if (materials.length > 0) {
                        materials.forEach(function(m) {
                            addEditMaterialToTable(m.id, m.quantity, m.name, m.unit);
                        });
                    }
                    initEditMaterialSelect();
                }

                $('#modalEdit').modal('show');
            });

            // ============================================================
            // SUBMIT EDITAR
            // ============================================================
            $('#formEdit').on('submit', function(e) {
                e.preventDefault();

                var id = $('#edit_id').val();

                // Validar campos
                var errors = [];
                var name = $('#edit_name').val().trim();
                var cost = $('#edit_cost_addition').val();
                var price = $('#edit_price_addition').val();
                var consumesInventory = $('#edit_consumes_inventory').is(':checked');

                if (!name) errors.push('El nombre del extra es obligatorio');
                if (!cost || parseFloat(cost) < 0) errors.push('El costo adicional es obligatorio');
                if (!price || parseFloat(price) < 0) errors.push('El precio al cliente es obligatorio');
                if (consumesInventory && $('#edit_materials_list tr').length === 0) {
                    errors.push('Debe agregar al menos un material si el extra consume inventario');
                }

                if (errors.length > 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos requeridos',
                        html: '<ul style="text-align:left;">' + errors.map(function(e) {
                            return '<li>' + e + '</li>';
                        }).join('') + '</ul>',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }

                // Preparar datos
                var formData = {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    name: name,
                    extra_category_id: $('#edit_extra_category_id').val() || null,
                    cost_addition: cost,
                    price_addition: price,
                    minutes_addition: $('#edit_minutes_addition').val() || 0,
                    consumes_inventory: consumesInventory ? 1 : 0,
                    materials: []
                };

                // Recoger materiales
                $('#edit_materials_list tr').each(function() {
                    formData.materials.push({
                        variant_id: $(this).find('input[name*="variant_id"]').val(),
                        quantity: $(this).find('input[name*="quantity"]').val()
                    });
                });

                $('#btnSaveEdit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');

                $.ajax({
                    url: '/product_extras/edit/' + id,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#modalEdit').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Actualizado',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            reloadTable();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Error al actualizar' });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors || {};
                            if (Object.keys(errors).length > 0) {
                                $.each(errors, function(field, messages) {
                                    $('#edit_' + field).addClass('is-invalid');
                                    $('#edit_error_' + field).text(messages[0]);
                                });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Validación', text: xhr.responseJSON?.message || 'Error de validación' });
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

            // ============================================================
            // MODAL ELIMINAR
            // ============================================================
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
                            url: '/product_extras/delete/' + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message, timer: 1500, showConfirmButton: false });
                                    reloadTable();
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
