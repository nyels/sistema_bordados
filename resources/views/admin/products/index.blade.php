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

            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="category_filter">Categoría</label>
                        <select class="form-control select2" id="category_filter" style="width: 100%;">
                            <option value="">Todas las categorías</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status_filter">Estado</label>
                        <select class="form-control select2" id="status_filter" style="width: 100%;">
                            <option value="">Todos los estados</option>
                            <option value="active">Activo</option>
                            <option value="draft">Borrador</option>
                            <option value="discontinued">Descontinuado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search_filter">Búsqueda</label>
                        <input type="text" class="form-control" id="search_filter" placeholder="Nombre, SKU...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group d-flex align-items-end">
                        <button type="button" id="btn_filter" class="btn btn-primary mr-2">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        <button type="button" id="btn_reset" class="btn btn-default">
                            <i class="fas fa-undo mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12">
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
                                <td>{{ $loop->iteration }}</td>
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
                                    <span class="badge badge-dark">{{ $product->sku }}</span>
                                </td>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if ($product->description)
                                        <br><small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $product->category->name ?? 'Sin categoría' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary">{{ $product->variants_count }}</span>
                                </td>
                                <td>
                                    @if ($product->status === 'active')
                                        <span class="badge badge-success">{{ $product->status_label }}</span>
                                    @elseif($product->status === 'draft')
                                        <span class="badge badge-warning">{{ $product->status_label }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $product->status_label }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-bold">{{ $product->formatted_base_price }}</span>
                                </td>
                                <td>{{ $product->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="{{ route('admin.products.show', $product->id) }}"
                                            class="btn btn-info btn-sm" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product->id) }}"
                                            class="btn btn-warning btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        @if ($product->status === 'active')
                                            <form action="{{ route('admin.products.toggle_status', $product->id) }}"
                                                method="POST" class="d-inline" data-confirm="¿Cambiar a descontinuado?">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm"
                                                    title="Descontinuar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @elseif($product->status === 'discontinued')
                                            <form action="{{ route('admin.products.toggle_status', $product->id) }}"
                                                method="POST" class="d-inline" data-confirm="¿Activar producto?">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Activar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($product->canDelete())
                                            <a href="{{ route('admin.products.confirm_delete', $product->id) }}"
                                                class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
            // Inicializar Select2
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
                let category = $('#category_filter').val();
                let status = $('#status_filter').val();
                let search = $('#search_filter').val();

                table.column(4).search(category);
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
