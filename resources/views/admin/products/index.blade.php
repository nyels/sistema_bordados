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
                        <div class="col-md-3">
                            <label for="category_filter" class="mb-1 font-weight-bold">Categoría</label>
                            <select class="form-control border" id="category_filter">
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_filter" class="mb-1 font-weight-bold">Estado</label>
                            <select class="form-control border" id="status_filter">
                                <option value="">Todos</option>
                                <option value="Activo" selected>Activo</option>
                                <option value="Borrador">Borrador</option>
                                <option value="Descontinuado">Descontinuado</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search_filter" class="mb-1 font-weight-bold">Búsqueda</label>
                            <input type="text" class="form-control border" id="search_filter" placeholder="Buscar por nombre o SKU...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                            <button type="button" id="btn_filter" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Filtrar
                            </button>
                            <button type="button" id="btn_reset" class="btn btn-outline-secondary ml-2">
                                <i class="fas fa-undo mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="table-responsive">
                    <table id="products-table" class="table table-bordered table-hover ">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>SKU</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Variantes</th>
                                <th>Estado</th>
                                <th>Precio Base</th>
                                <th>Creado</th>
                                <th style="text-align: center;">Acciones</th>
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
                                    <td class="text-center">
                                        <span class="badge badge-dark">{{ $product->sku }}</span>
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ $product->name }}</strong>
                                        @if ($product->description)
                                            <br><small
                                                class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-info">{{ $product->category->name ?? 'Sin categoría' }}</span>
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
                                        @if ($product->status === 'active')
                                            <span class="badge badge-success">{{ $product->status_label }}</span>
                                        @elseif($product->status === 'draft')
                                            <span class="badge badge-warning">{{ $product->status_label }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $product->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="font-weight-bold">{{ $product->formatted_base_price }}</span>
                                    </td>
                                    <td class="text-center">{{ $product->created_at->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-info"
                                                title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product->id) }}"
                                                class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>

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

                // Filtrar por categoría
                $('#category_filter').on('change', function() {
                    table.column(4).search(this.value).draw();
                });

                // Filtrar por estado
                $('#status_filter').on('change', function() {
                    table.column(6).search(this.value).draw();
                });

                // Búsqueda general
                $('#search_filter').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // Botón de filtrar
                $('#btn_filter').on('click', function() {
                    // FIX: Usar texto del option, no el ID (DataTables busca en contenido visible)
                    let categoryText = $('#category_filter option:selected').text().trim();
                    if (categoryText === 'Todas') categoryText = '';
                    let status = $('#status_filter').val();
                    let search = $('#search_filter').val();

                    table.column(4).search(categoryText);
                    table.column(6).search(status);
                    table.search(search).draw();
                });

                // Botón de resetear filtros
                $('#btn_reset').on('click', function() {
                    $('#category_filter').val('').trigger('change');
                    $('#status_filter').val('').trigger('change');
                    $('#search_filter').val('');

                    table.columns().search('');
                    table.search('').draw();
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
            });
        </script>
    @stop
