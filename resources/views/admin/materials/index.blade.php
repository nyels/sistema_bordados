@extends('adminlte::page')

@section('title', 'Materiales')

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
                <i class="fas fa-boxes"></i> CATÁLOGO DE MATERIALES
            </h3>
        </div>

        <div class="card-body">
            {{-- FILTROS Y ACCIONES --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <a href="{{ route('admin.materials.create') }}" class="btn btn-primary">
                        Nuevo <i class="fas fa-plus"></i>
                    </a>
                    <a href="{{ route('admin.material-categories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-layer-group"></i> Categorías
                    </a>
                </div>
                <div class="col-md-6">
                    <form method="GET" action="{{ route('admin.materials.index') }}" class="form-inline justify-content-end">
                        <select name="category" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @if (request('category'))
                            <a href="{{ route('admin.materials.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Categoría</th>
                            <th>Nombre</th>

                            <th>Composición</th>
                            <th style="width: 100px;">Unidad</th>
                            <th style="width: 100px;">Variantes</th>
                            <th style="width: 120px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="badge badge-primary">{{ $material->category->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <strong>{{ $material->name }}</strong>
                                    @if ($material->description)
                                        <br><small class="text-muted">{{ Str::limit($material->description, 50) }}</small>
                                    @endif
                                </td>

                                <td>{{ $material->composition ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ $material->category->baseUnit->symbol ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $material->active_variants_count }}</span>
                                </td>

                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        {{-- Botón Variantes --}}
                                        <a href="{{ route('admin.material-variants.index', $material->id) }}"
                                            class="btn btn-info btn-sm d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;" title="Ver Variantes">
                                            <i class="fas fa-palette"></i>
                                        </a>

                                        {{-- Botón Conversiones --}}
                                        <a href="{{ route('admin.material-conversions.index', $material->id) }}"
                                            class="btn btn-secondary btn-sm d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;" title="Conversiones">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>

                                        {{-- Botón Editar --}}
                                        <a href="{{ route('admin.materials.edit', $material->id) }}"
                                            class="btn btn-warning btn-sm d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;" title="Editar">
                                            <i class="fas fa-edit text-white"></i>
                                        </a>

                                        {{-- Botón Eliminar --}}
                                        <a href="{{ route('admin.materials.confirm_delete', $material->id) }}"
                                            class="btn btn-danger btn-sm d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
            gap: 10px;
            margin-bottom: 15px;
        }

        #example1_wrapper .btn {
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

        /* Ajuste para iconos blancos en botones warning */
        .btn-warning i {
            color: white !important;
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            if ($.fn.DataTable.isDataTable('#example1')) {
                $('#example1').DataTable().destroy();
            }

            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Materiales",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Materiales",
                    "infoFiltered": "(Filtrado de _MAX_ total Materiales)",
                    "lengthMenu": "Mostrar _MENU_ Materiales",
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
                "buttons": [{
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
        });
    </script>
@stop
