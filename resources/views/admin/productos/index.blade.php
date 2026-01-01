@extends('adminlte::page')

@section('title', 'Catálogo de Productos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="font-weight-bold text-dark">
            <i class="fas fa-boxes text-primary mr-2"></i> Catálogo de Productos
        </h1>
        <a href="#" class="btn btn-primary shadow-sm px-4">
            <i class="fas fa-plus-circle mr-1"></i> Nuevo Producto
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 3px solid #007bff;">
            <div class="card-body">

                <div class="table-responsive">
                    <table id="example1" class="table table-hover w-100">
                        <thead style="background-color: #343a40; color: white;">
                            <tr>
                                <th class="py-3 px-4 border-0">#</th>
                                <th class="py-3 px-4 border-0">CATEGORÍA</th>
                                <th class="py-3 border-0">NOMBRE DEL PRODUCTO</th>
                                <th class="py-3 border-0">ESPECIFICACIONES</th>
                                <th class="py-3 border-0 text-center">EXTRAS</th>
                                <th class="py-3 border-0">PRECIO</th>
                                <th class="py-3 border-0 text-center">ESTADO</th>
                                <th class="py-3 px-4 border-0">FECHA DE CREACIÓN</th>
                                <th class="py-3 border-0 text-right px-4">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="align-middle px-4 text-muted">{{ $loop->iteration }}</td>

                                    {{-- CATEGORÍA --}}
                                    <td class="align-middle px-4">
                                        <span
                                            class="badge badge-light border text-uppercase">{{ $product->category->name ?? 'General' }}</span>
                                    </td>

                                    {{-- IMAGENES Y NOMBRE --}}
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            @php
                                                $firstExport = $product->variants->flatMap->designExports->first();
                                                $embroideryImg =
                                                    $firstExport && $firstExport->file_path
                                                        ? asset('storage/' . $firstExport->file_path)
                                                        : asset('images/no-embroidery.png');
                                                $productImg = $product->image_path
                                                    ? asset('storage/' . $product->image_path)
                                                    : asset('images/no-product.png');
                                            @endphp

                                            <div class="position-relative mr-3" title="Bordado vs Producto">
                                                <img src="{{ $productImg }}" class="rounded shadow-sm border"
                                                    style="width: 45px; height: 45px; object-fit: cover;">
                                                <img src="{{ $embroideryImg }}"
                                                    class="rounded-circle border border-white position-absolute"
                                                    style="width: 30px; height: 30px; object-fit: cover; bottom: -5px; right: -5px; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                            </div>

                                            <div>
                                                <span
                                                    class="font-weight-bold d-block text-dark product-title-saas">{{ $product->name }}</span>
                                                @if ($firstExport)
                                                    <small class="text-primary font-weight-bold">
                                                        <i
                                                            class="fas fa-microchip mr-1"></i>{{ number_format($firstExport->stitches_count) }}
                                                        puntadas
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- VARIANTES DETALLADAS --}}
                                    <td class="align-middle">
                                        @php
                                            $specs = is_array($product->specifications)
                                                ? $product->specifications
                                                : json_decode($product->specifications, true);
                                        @endphp

                                        @if ($specs)
                                            <div class="mb-2 pb-1 border-bottom spec-container">
                                                <span class="text-muted mr-2"><strong>TELA:</strong>
                                                    {{ $specs['tipo_tela'] ?? 'N/A' }}</span><br>
                                                <span class="text-muted"><strong>MATERIAL:</strong>
                                                    {{ $specs['material'] ?? 'N/A' }}</span><br>
                                                <span class="text-muted"><strong>COLOR:</strong>
                                                    {{ $specs['color'] ?? 'N/A' }}</span><br>
                                                <span class="text-muted"><strong>HILO:</strong>
                                                    {{ $specs['hilo'] ?? 'N/A' }}</span><br>
                                                <span class="text-muted"><strong>NOTAS:</strong>
                                                    {{ $specs['notas'] ?? 'N/A' }}</span>
                                            </div>
                                        @endif

                                        @foreach ($product->variants as $variant)
                                            <div class="d-flex justify-content-between align-items-center border-bottom mb-1 pb-1"
                                                style="min-width: 250px;">
                                                <div class="variant-text">
                                                    @foreach ($variant->attributes as $item)
                                                        <span
                                                            class="text-muted text-uppercase attr-label">{{ $item->attribute->name }}:</span>
                                                        <span
                                                            class="font-weight-bold mr-2 text-dark">{{ $item->value }}</span>
                                                    @endforeach
                                                    <code class="text-dark bg-light px-2 py-1 sku-style">
                                                        {{ $variant->sku_variant }}
                                                    </code>
                                                </div>
                                            </div>
                                        @endforeach
                                    </td>

                                    {{-- EXTRAS --}}
                                    <td class="align-middle text-center">
                                        @forelse ($product->extras as $extra)
                                            <span
                                                class="badge badge-info shadow-sm text-uppercase mb-1 extra-badge">{{ $extra->name }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>

                                    {{-- PRECIO --}}
                                    <td class="align-middle text-center">
                                        @forelse ($product->variants as $variant)
                                            @php
                                                $total = $variant->getTotalConExtras();
                                                $extras = $product->extras;
                                            @endphp
                                            <div class="mb-1" data-toggle="tooltip" data-html="true"
                                                title="<div class='text-left'><b>Desglose:</b><br>Base: 
                                                    ${{ number_format($variant->price, 2) }}<br>
                                                    @foreach ($extras as $e)
<small>• {{ $e->name }}: +${{ number_format($e->price_addition, 2) }}</small><br>
@endforeach
                                                    <hr class='my-1' style='border-top: 1px solid white'><b>Total: ${{ number_format($total, 2) }}</b></div>">
                                                <span class="badge badge-success shadow-sm p-2 price-tag">
                                                    ${{ number_format($total, 2) }}
                                                </span>
                                            </div>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>

                                    {{-- ESTADO --}}
                                    <td class="align-middle text-center">
                                        <span
                                            class="badge {{ $product->status == 'active' ? 'badge-success' : 'badge-danger' }} px-3 py-2 shadow-sm status-badge">
                                            {{ $product->status == 'active' ? 'ACTIVO' : 'INACTIVO' }}
                                        </span>
                                    </td>

                                    <td class="align-middle px-4">
                                        <span
                                            class="text-muted d-block date-text">{{ $product->created_at->format('d/m/Y') }}</span>
                                        <span class="text-muted date-text">{{ $product->created_at->format('H:i') }}
                                            hs</span>
                                    </td>

                                    <td class="align-middle text-right px-4">
                                        <div class="btn-group">
                                            <a href="#" class="btn btn-sm btn-primary shadow-sm"
                                                title="Ver Detalle"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="btn btn-sm btn-warning shadow-sm mx-1"
                                                title="Editar"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger shadow-sm" title="Eliminar"><i
                                                    class="fas fa-trash"></i></button>
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
@stop

@section('css')
    <style>
        /* CONFIGURACIÓN ESTÁNDAR EMPRESARIAL - MODIFICA AQUÍ EL TAMAÑO */
        :root {
            --fuente-base: 15px;
            /* Tamaño general de la tabla */
            --fuente-pequena: 13px;
            /* Para especificaciones y fechas */
            --fuente-sku: 12px;
            /* Para el código SKU */
            --fuente-titulo: 16px;
            /* Nombre del producto */
        }

        #example1 {
            font-size: var(--fuente-base) !important;
        }

        /* Ajuste de Nombre del Producto */
        .product-title-saas {
            font-size: var(--fuente-titulo) !important;
        }

        /* Ajuste de Especificaciones (antes eran <small> y casi no se veían) */
        .spec-container span,
        .variant-text span {
            font-size: var(--fuente-pequena) !important;
        }

        .attr-label {
            font-size: 11px !important;
            /* El label de 'TALLA:' etc */
        }

        .sku-style {
            font-size: var(--fuente-sku) !important;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
        }

        .extra-badge {
            font-size: 11px !important;
            padding: 5px 8px;
        }

        .price-tag {
            font-size: var(--fuente-base) !important;
            font-weight: 700;
        }

        .status-badge {
            font-size: 13px !important;
            letter-spacing: 0.5px;
        }

        .date-text {
            font-size: var(--fuente-pequena) !important;
        }

        /* Estilos de botones DataTables */
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
    </style>
@stop

@section('js')
    <script>
        $(function() {
            $('body').tooltip({
                selector: '[data-toggle="tooltip"]'
            });

            $("#example1").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay informacion",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Productos",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Productos",
                    "infoFiltered": "(Filtrado de _MAX_ total Productos)",
                    "lengthMenu": "Mostrar _MENU_ Productos",
                    "search": "Buscador:",
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
