@extends('adminlte::page')

@section('title', 'Conversiones - ' . $material->name)

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
                    <span class="badge badge-success">
                        <i class="fas fa-ruler"></i>
                        Unidad de Compra: <strong>{{ $material->baseUnit->name ?? 'N/A' }}</strong>
                        ({{ $material->baseUnit->symbol ?? '' }})
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-exchange-alt"></i> CONVERSIONES DE UNIDAD: {{ $material->name }}
            </h3>
        </div>

        <div class="card-body">
            {{-- ACCIONES --}}
            <div class="row mb-3">
                <div class="col-12 text-right">
                    <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Materiales
                    </a>
                    <a href="{{ route('admin.material-conversions.create', $material->id) }}" class="btn btn-info">
                        <i class="fas fa-plus"></i> Nueva Conversión
                    </a>
                </div>
            </div>

            {{-- EXPLICACIÓN --}}
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>¿Qué son las conversiones?</strong><br>
                Permiten calcular cuántas unidades de <strong>consumo</strong> se obtienen por cada unidad de
                <strong>compra</strong>.
                <br>
                <small>
                    Ejemplo: <strong>1 Cono</strong> (compra) → <strong>5000 Metros</strong> (consumo) en inventario.
                </small>
            </div>

            <hr>

            {{-- TABLA --}}
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-hover text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Unidad de Compra</th>
                            <th>Referencia/Etiqueta</th>
                            <th class="text-center" style="width: 80px;"><i class="fas fa-arrow-right"></i></th>
                            <th>Unidad de Inventario</th>
                            <th class="text-center">Factor</th>
                            <th>Modo</th>
                            <th>Conversión</th>
                            <th style="width: 120px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversions as $conversion)
                            <tr>
                                <td class="align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <span class="badge badge-secondary">
                                        {{ $conversion->fromUnit->name ?? 'N/A' }}
                                    </span>
                                    <small class="text-muted">({{ $conversion->fromUnit->symbol ?? '' }})</small>
                                </td>
                                <td class="align-middle">
                                    @if ($conversion->label)
                                        <span class="badge badge-light border">{{ $conversion->label }}</span>
                                    @else
                                        <small class="text-muted italic">Sin etiqueta</small>
                                    @endif

                                    @if ($conversion->intermediate_qty)
                                        @php
                                            $eachValue = $conversion->conversion_factor / $conversion->intermediate_qty;
                                        @endphp
                                        <br>
                                        <small class="text-primary font-weight-bold">
                                            <i class="fas fa-calculator"></i>
                                            {{ number_format($conversion->intermediate_qty, 0) }} pz &times;
                                            {{ number_format($eachValue, 4) }} {{ $conversion->toUnit->symbol }}
                                        </small>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <i class="fas fa-arrow-right text-primary"></i>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-info">
                                        {{ $conversion->toUnit->name ?? 'N/A' }}
                                    </span>
                                    <small class="text-muted">({{ $conversion->toUnit->symbol ?? '' }})</small>
                                </td>
                                <td class="align-middle text-center">
                                    @if ($conversion->intermediate_qty && $conversion->intermediate_qty > 0)
                                        <strong>{{ number_format($conversion->conversion_factor / $conversion->intermediate_qty, 4) }}
                                            {{ $conversion->toUnit->symbol }}</strong>
                                        <br>
                                        <small class="text-muted" style="font-size: 0.75em;">(x pz)</small>
                                    @else
                                        <strong>{{ number_format($conversion->conversion_factor, 4) }}
                                            {{ $conversion->toUnit->symbol }}</strong>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    @if ($conversion->conversion_mode === 'por_contenido')
                                        <span class="badge badge-info" title="Factor calculado por contenido (qty × valor)">
                                            <i class="fas fa-boxes"></i> Por Contenido
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" title="Factor ingresado manualmente">
                                            <i class="fas fa-ruler-horizontal"></i> Manual
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <code>1 {{ $conversion->fromUnit->name ?? '' }} =
                                        {{ number_format($conversion->conversion_factor, 2) }}
                                        {{ $conversion->toUnit->symbol ?? '' }}</code>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="{{ route('admin.material-conversions.edit', [$material->id, $conversion->id]) }}"
                                            class="btn btn-warning btn-sm btn-action" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.material-conversions.confirm_delete', [$material->id, $conversion->id]) }}"
                                            class="btn btn-danger btn-sm btn-action" title="Eliminar">
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

@section('js')
    <script>
        $(function() {
            var table = $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Proveedores",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Proveedores",
                    "infoFiltered": "(Filtrado de _MAX_ total Proveedores)",
                    "lengthMenu": "Mostrar _MENU_ Proveedores",
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
            });

            // ESTA LINEA ES CLAVE: Mueve los botones a un contenedor propio para aplicar el CSS
            table.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
        });
    </script>
@stop

@section('css')
    <style>
        /* Contenedor de botones: Centrado y espaciado como en la Imagen 1 */
        #example1_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            float: none;
            /* Quita el float por defecto de AdminLTE */
        }

        /* Estilo de los botones */
        #example1_wrapper .dt-buttons .btn {
            color: #fff !important;
            border-radius: 4px;
            padding: 5px 15px;
            font-size: 14px;
            border: none;
        }

        /* Colores específicos por clase */
        .btn-danger {
            background-color: #dc3545 !important;
        }

        .btn-success {
            background-color: #28a745 !important;
        }

        .btn-info {
            background-color: #17a2b8 !important;
        }

        .btn-default {
            background-color: #6e7176 !important;
        }

        /* Ajuste para el buscador y mostrar registros para que no se amontonen */
        .dataTables_wrapper .dataTables_filter {
            text-align: right;
        }
    </style>
@stop
