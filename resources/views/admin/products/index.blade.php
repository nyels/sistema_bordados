{{-- resources/views/admin/products/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Productos')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary" bis_skin_checked="1">
        <div class="card-header" bis_skin_checked="1">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">GESTIÓN DE PRODUCTOS</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body" bis_skin_checked="1">
            <div class="row">
                <a href="{{ route('admin.products.create') }}" type="button" class="btn btn-primary">
                    Nuevo Producto <i class="fas fa-plus"></i>
                </a>
            </div>
            <hr>

            <!-- Filtros de Productos -->
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header py-2">
                    <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros de Productos</h3>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="category_filter" class="mb-1 font-weight-bold">Categoría</label>
                            <select class="form-control border" id="category_filter">
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status_filter" class="mb-1 font-weight-bold">Estado</label>
                            <select class="form-control border" id="status_filter">
                                <option value="">Todos</option>
                                <option value="Activo" selected>Activo</option>
                                <option value="Borrador">Borrador</option>
                                <option value="Descontinuado">Descontinuado</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" id="btn_reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo mr-1"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="table-responsive">
                    <table id="products-table" class="table table-bordered table-hover ">
                        <thead class="thead-dark text-center">
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Variantes</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Costo Prod.</th>
                                <th>Precio Base</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        @if ($product->primary_image_url)
                                            <img src="{{ asset($product->primary_image_url) }}" alt="{{ $product->name }}"
                                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                        @else
                                            <div class="text-center">
                                                <i class="fas fa-image fa-2x text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        <br><span class="badge badge-dark">{{ $product->sku }}</span>
                                        @if ($product->description)
                                            <br><small class="text-muted">{{ Str::limit($product->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($product->variants->count() > 0)
                                            <div class="d-flex flex-column align-items-center justify-content-center"
                                                style="gap: 3px;">
                                                @foreach ($product->variants->take(3) as $variant)
                                                    <span class="badge badge-light border text-xs"
                                                        style="font-weight: normal; font-size: 0.85em;">
                                                        {{ Str::limit($variant->attributes_display, 20) }}
                                                    </span>
                                                @endforeach

                                                @if ($product->variants->count() > 3)
                                                    <span class="badge badge-secondary text-xs">
                                                        +{{ $product->variants->count() - 3 }} más
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $product->category->name ?? 'Sin categoría' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($product->status === 'active')
                                            <span class="badge badge-success">{{ $product->status_label }}</span>
                                        @elseif($product->status === 'draft')
                                            <span class="badge badge-warning">{{ $product->status_label }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $product->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark">${{ number_format($product->production_cost ?? 0, 2) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="font-weight-bold">{{ $product->formatted_base_price }}</span>
                                    </td>
                                    <td class="text-center">{{ $product->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary btn-view-bom"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                                title="Ver BOM">
                                                <i class="fas fa-clipboard-list"></i>
                                            </button>
                                            <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-info"
                                                title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product->id) }}"
                                                class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.products.duplicate', $product->id) }}"
                                                method="POST" style="display: inline-block;"
                                                data-confirm="¿Duplicar este producto?">
                                                @csrf
                                                <button type="submit" class="btn btn-dark" title="Duplicar"
                                                    style="border-radius: 0;">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </form>

                                            @if ($product->status === 'active')
                                                <form action="{{ route('admin.products.toggle_status', $product->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    data-confirm="¿Cambiar a descontinuado?">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary" title="Descontinuar"
                                                        style="border-radius: 0;">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @elseif($product->status === 'discontinued')
                                                <form action="{{ route('admin.products.toggle_status', $product->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    data-confirm="¿Activar producto?">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" title="Activar"
                                                        style="border-radius: 0;">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($product->canDelete())
                                                <form action="{{ route('admin.products.destroy', $product->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    data-confirm="¿Eliminar este producto permanentemente?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Eliminar"
                                                        style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /.card-body -->

        </div>
    @stop

    @section('css')
        <style>
            /* Estilos para los botones de DataTables */
            #products-table_wrapper .dt-buttons {
                background-color: transparent;
                box-shadow: none;
                border: none;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
            }

            #products-table_wrapper .btn {
                color: #fff;
                border-radius: 4px;
                padding: 5px 15px;
                font-size: 14px;
            }

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
                color: #fff;
                border: none;
            }

            .product-img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 5px;
            }
        </style>
        <!-- Select2 CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css">
    @stop

    @section('js')
        <!-- DataTables & Plugins -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.0/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.0/js/buttons.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.0/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.0/js/buttons.print.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.0/js/buttons.colVis.min.js"></script>

        <!-- Select2 -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            $(document).ready(function() {
                // Initialize Select2 (Existing)
                $('.select2').select2({
                    theme: 'bootstrap4',
                    language: 'es'
                });

                // Inicializar DataTable
                var table = $('#products-table').DataTable({
                    "pageLength": 10,
                    "language": {
                        "emptyTable": "No hay información de productos",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_ productos",
                        "infoEmpty": "Mostrando 0 a 0 de 0 productos",
                        "infoFiltered": "(Filtrado de _MAX_ total productos)",
                        "lengthMenu": "Mostrar _MENU_ productos",
                        "loadingRecords": "Cargando...",
                        "processing": "Procesando...",
                        "search": "Buscador:",
                        "zeroRecords": "Sin resultados encontrados",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        }
                    },
                    "responsive": true,
                    "lengthChange": true,
                    "autoWidth": false,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "paging": true,
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
                        },
                        {
                            text: '<i class="fas fa-eye"></i> COLUMNAS',
                            extend: 'colvis',
                            className: 'btn btn-secondary'
                        }
                    ]
                });

                // Añadir los botones al contenedor
                table.buttons().container().appendTo('#products-table_wrapper .row:eq(0)');

                // Filtrar por categoría (automático al cambiar) - columna índice 4
                $('#category_filter').on('change', function() {
                    table.column(4).search(this.value).draw();
                });

                // Filtrar por estado (automático al cambiar) - columna índice 5
                $('#status_filter').on('change', function() {
                    table.column(5).search(this.value).draw();
                });

                // Aplicar filtro de estado inicial (Activo por defecto)
                table.column(5).search('Activo').draw();

                // Botón de limpiar filtros
                $('#btn_reset').on('click', function() {
                    $('#category_filter').val('');
                    $('#status_filter').val('');
                    table.columns().search('').draw();
                });

                // SweetAlert para confirmaciones
                $(document).on('submit', 'form[data-confirm]', function(e) {
                    e.preventDefault();
                    var form = this;

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: $(this).data('confirm'),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });

                // ========== MODAL BOM ==========
                $(document).on('click', '.btn-view-bom', function() {
                    const productId = $(this).data('product-id');
                    const productName = $(this).data('product-name');
                    const $btn = $(this);

                    // Loading state
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                    // Fetch BOM data
                    $.ajax({
                        url: `/admin/products/${productId}/bom`,
                        method: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            renderBomModal(data);
                            $('#modalBom').modal('show');
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'No se pudo cargar la información del producto', 'error');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-clipboard-list"></i>');
                        }
                    });
                });

                function renderBomModal(data) {
                    // Header
                    $('#bom_product_name').text(data.name);
                    $('#bom_sku').text(data.sku);
                    $('#bom_category').text(data.category);
                    $('#bom_lead_time').text(data.lead_time + ' días');

                    // Estructura de Costos
                    const grandTotal = data.costs.total || 0;
                    let costsHtml = `
                        <table class="table table-sm mb-0">
                            <thead class="text-dark font-weight-bold">
                                <tr>
                                    <th>Concepto</th>
                                    <th class="text-right">Costo</th>
                                    <th class="text-right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-boxes text-primary mr-2"></i>Materiales</td>
                                    <td class="text-right font-weight-bold">$${data.costs.materials.toFixed(2)}</td>
                                    <td class="text-right">${grandTotal > 0 ? ((data.costs.materials / grandTotal) * 100).toFixed(1) : 0}%</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-tshirt text-info mr-2"></i>Bordado</td>
                                    <td class="text-right font-weight-bold">$${data.costs.embroidery.toFixed(2)}</td>
                                    <td class="text-right">${grandTotal > 0 ? ((data.costs.embroidery / grandTotal) * 100).toFixed(1) : 0}%</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-hand-holding-usd text-warning mr-2"></i>Mano de Obra</td>
                                    <td class="text-right font-weight-bold">$${data.costs.labor.toFixed(2)}</td>
                                    <td class="text-right">${grandTotal > 0 ? ((data.costs.labor / grandTotal) * 100).toFixed(1) : 0}%</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-concierge-bell text-secondary mr-2"></i>Servicios Extras</td>
                                    <td class="text-right font-weight-bold">$${data.costs.extras.toFixed(2)}</td>
                                    <td class="text-right">${grandTotal > 0 ? ((data.costs.extras / grandTotal) * 100).toFixed(1) : 0}%</td>
                                </tr>
                                <tr class="bg-light border-top">
                                    <td class="font-weight-bold">TOTAL COSTO</td>
                                    <td class="text-right font-weight-bold text-success">$${grandTotal.toFixed(2)}</td>
                                    <td class="text-right font-weight-bold">100%</td>
                                </tr>
                            </tbody>
                        </table>
                    `;
                    $('#bom_costs_table').html(costsHtml);

                    // Receta de Materiales
                    let materialsHtml = '';
                    if (data.materials && data.materials.length > 0) {
                        materialsHtml = `
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light text-uppercase small">
                                    <tr>
                                        <th>Material</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Consumo</th>
                                        <th class="text-right">Costo Unit.</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        data.materials.forEach(function(mat) {
                            const subtotal = mat.quantity * mat.unit_cost;
                            materialsHtml += `
                                <tr>
                                    <td class="font-weight-bold">${mat.name}${mat.color ? ' <span class="text-dark">- ' + mat.color + '</span>' : ''}</td>
                                    <td><span class="badge badge-light border small">${mat.category}</span></td>
                                    <td class="text-center font-weight-bold">${parseFloat(mat.quantity)} <span class="text-dark">${mat.unit}</span></td>
                                    <td class="text-right">$${mat.unit_cost.toFixed(4)}</td>
                                    <td class="text-right font-weight-bold">$${subtotal.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        materialsHtml += `
                                </tbody>
                                <tfoot class="bg-dark text-white">
                                    <tr>
                                        <td colspan="4" class="font-weight-bold">TOTAL MATERIALES</td>
                                        <td class="text-right font-weight-bold">$${data.costs.materials.toFixed(2)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;
                    } else {
                        materialsHtml = '<div class="text-center py-4 text-dark"><i class="fas fa-box-open fa-2x mb-2"></i><p class="mb-0">Sin materiales registrados</p></div>';
                    }
                    $('#bom_materials_table').html(materialsHtml);

                    // Precios
                    $('#bom_base_price').text('$' + data.base_price.toFixed(2));
                    $('#bom_suggested_price').text('$' + data.suggested_price.toFixed(2));
                    $('#bom_margin').text(data.margin.toFixed(1) + '%');
                }
            });
        </script>

        {{-- Modal BOM --}}
        <div class="modal fade" id="modalBom" tabindex="-1" aria-labelledby="modalBomLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalBomLabel">
                            <i class="fas fa-clipboard-list mr-2"></i>Ficha Técnica del Producto
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        {{-- Header Info --}}
                        <div class="bg-light p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4 class="font-weight-bold mb-1" id="bom_product_name">-</h4>
                                    <span class="badge badge-dark mr-2" id="bom_sku">-</span>
                                    <span class="badge badge-info" id="bom_category">-</span>
                                </div>
                                <div class="col-md-6 text-md-right">
                                    <div class="d-inline-block text-center px-3 py-2 bg-warning rounded">
                                        <i class="fas fa-clock mr-1"></i>
                                        <span>Tiempo estimado de producción = </span>
                                        <span class="font-weight-bold" id="bom_lead_time">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Estructura de Costos --}}
                        <div class="p-3">
                            <h6 class="font-weight-bold text-dark mb-3">
                                <i class="fas fa-chart-pie mr-2 text-secondary"></i>Estructura de Costos
                            </h6>
                            <div id="bom_costs_table"></div>
                        </div>

                        {{-- Precios --}}
                        <div class="bg-light p-3 border-top border-bottom">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-dark d-block">Margen</small>
                                    <span class="font-weight-bold h5 text-success" id="bom_margin">-</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-dark d-block">Precio Sugerido</small>
                                    <span class="font-weight-bold h5 text-primary" id="bom_suggested_price">-</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-dark d-block">Precio Base</small>
                                    <span class="font-weight-bold h5" id="bom_base_price">-</span>
                                </div>
                            </div>
                        </div>

                        {{-- Receta de Materiales --}}
                        <div class="p-3">
                            <h6 class="font-weight-bold text-dark mb-3">
                                <i class="fas fa-scroll mr-2 text-primary"></i>Receta de Materiales (BOM)
                            </h6>
                            <div id="bom_materials_table"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @stop
