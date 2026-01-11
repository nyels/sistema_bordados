@extends('adminlte::page')

@section('title', 'Variantes - ' . $material->name)

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

    {{-- BREADCRUMB INFO --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 bg-transparent p-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.materials.index') }}">
                                    <i class="fas fa-boxes"></i> Materiales
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <span class="badge badge-primary">{{ $material->category->name ?? 'N/A' }}</span>
                            </li>
                            <li class="breadcrumb-item active">{{ $material->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-secondary">
                        <i class="fas fa-ruler"></i>
                        Unidad: {{ $material->category->baseUnit->symbol ?? 'N/A' }}
                    </span>
                    @if ($material->composition)
                        <span class="badge badge-info">
                            {{ $material->composition }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-palette"></i> VARIANTES DE: {{ $material->name }}
            </h3>
        </div>

        <div class="card-body">
            {{-- ACCIONES --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Materiales
                    </a>
                    <a href="{{ route('admin.material-variants.create', $material->id) }}" class="btn btn-info">
                        Nueva Variante <i class="fas fa-plus"></i>
                    </a>

                </div>
                <div class="col-md-6 text-right">
                    <span class="text-muted">
                        Total variantes: <strong>{{ $variants->count() }}</strong>
                    </span>
                </div>
            </div>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>SKU</th>
                            <th>Color</th>
                            <th class="text-right">Stock Actual</th>
                            <th class="text-right">Stock Mínimo</th>
                            <th class="text-right">Costo Promedio</th>
                            <th class="text-right">Valor Total</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 120px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $variant)
                            <tr class="{{ $variant->is_low_stock ? 'table-warning' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <code class="font-weight-bold">{{ $variant->sku }}</code>
                                </td>
                                <td>
                                    @if ($variant->color)
                                        <span class="badge badge-secondary">{{ $variant->color }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <strong>{{ number_format($variant->current_stock, 2) }}</strong>
                                    <small class="text-muted">{{ $material->category->baseUnit->symbol ?? '' }}</small>
                                </td>
                                <td class="text-right">
                                    {{ number_format($variant->min_stock_alert, 2) }}
                                    <small class="text-muted">{{ $material->category->baseUnit->symbol ?? '' }}</small>
                                </td>
                                <td class="text-right">
                                    ${{ number_format($variant->average_cost, 2) }}
                                </td>
                                <td class="text-right">
                                    <strong>${{ number_format($variant->current_value, 2) }}</strong>
                                </td>
                                <td class="text-center">
                                    @if ($variant->is_low_stock)
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Bajo
                                        </span>
                                    @else
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> OK
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="{{ route('admin.material-variants.edit', [$material->id, $variant->id]) }}"
                                            class="btn btn-warning btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.material-variants.confirm_delete', [$material->id, $variant->id]) }}"
                                            class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No hay variantes registradas para este material
                                    <br>
                                    <a href="{{ route('admin.material-variants.create', $material->id) }}"
                                        class="btn btn-info btn-sm mt-2">
                                        <i class="fas fa-plus"></i> Crear primera variante
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($variants->count() > 0)
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="3" class="text-right">TOTALES:</th>
                                <th class="text-right">
                                    {{ number_format($variants->sum('current_stock'), 2) }}
                                    <small>{{ $material->category->baseUnit->symbol ?? '' }}</small>
                                </th>
                                <th></th>
                                <th></th>
                                <th class="text-right">
                                    <strong>${{ number_format($variants->sum('current_value'), 2) }}</strong>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .gap-1 {
            gap: 5px;
        }

        .table-warning {
            background-color: #fff3cd !important;
        }

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
    <script>
        $(function() {
            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Variantes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Variantes",
                    "infoFiltered": "(Filtrado de _MAX_ total Variantes)",
                    "lengthMenu": "Mostrar _MENU_ Variantes",
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
