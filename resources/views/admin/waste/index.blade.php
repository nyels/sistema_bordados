@extends('adminlte::page')

@section('title', 'Registro de Mermas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-trash-alt mr-2" style="color: #c62828;"></i>Registro de Mermas</h1>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- ADVERTENCIA CANÓNICA --}}
    <div class="alert mb-3" style="background: #fff3e0; border: 1px solid #ffcc80; border-left: 4px solid #f57c00;">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle mr-3" style="color: #e65100; font-size: 24px;"></i>
            <div>
                <strong style="color: #e65100; font-size: 15px;">Registro Contable Inmutable</strong>
                <p class="mb-0 mt-1" style="color: #5d4037; font-size: 14px;">
                    La merma es un registro irreversible. <strong>NO ajusta inventario automáticamente.</strong>
                    Los eventos registrados no pueden editarse ni eliminarse.
                </p>
            </div>
        </div>
    </div>

    {{-- RESUMEN POR TIPO --}}
    <div class="row mb-3">
        @php
            $materialCount = $wasteEvents->where('waste_type', 'material')->count();
            $wipCount = $wasteEvents->where('waste_type', 'wip')->count();
            $ptCount = $wasteEvents->where('waste_type', 'finished_product')->count();
        @endphp
        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $materialCount }}</h3>
                    <p>Merma de Material</p>
                </div>
                <div class="icon"><i class="fas fa-cubes"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $wipCount }}</h3>
                    <p>Merma en Proceso</p>
                </div>
                <div class="icon"><i class="fas fa-industry"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-dark">
                <div class="inner">
                    <h3>{{ $ptCount }}</h3>
                    <p>Merma Prod. Terminado</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
            </div>
        </div>
    </div>

    {{-- TABLA DE EVENTOS --}}
    <div class="card">
        <div class="card-header py-2" style="background: #263238; color: white;">
            <span class="font-weight-bold">Historial de Mermas</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table id="wasteTable" class="table table-hover table-sm mb-0" style="font-size: 16px;">
                <thead style="background-color: #000; color: #fff;">
                    <tr>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Material</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Costo Est.</th>
                        <th class="text-center">Motivo</th>
                        <th class="text-center">Registrado por</th>
                        <th class="text-center" style="width: 80px;">Ver</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wasteEvents as $event)
                        <tr>
                            <td class="text-center">
                                {{ $event->created_at->format('d/m/Y') }}
                                <br>
                                <span class="text-muted">{{ $event->created_at->format('H:i') }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $event->type_color }}" style="font-size: 14px; padding: 6px 10px;">
                                    <i class="{{ $event->type_icon }} mr-1"></i>
                                    {{ $event->type_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($event->isMaterialWaste())
                                    @php $firstItem = $event->materialItems->first(); @endphp
                                    @if($firstItem)
                                        <strong>{{ $firstItem->materialVariant?->material?->name ?? 'Material' }}</strong>
                                        @if($event->materialItems->count() > 1)
                                            <span class="badge badge-secondary ml-1">+{{ $event->materialItems->count() - 1 }} más</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Sin items</span>
                                    @endif
                                @elseif($event->isWipWaste())
                                    @if($event->order)
                                        <strong>Pedido {{ $event->order->order_number }}</strong>
                                    @else
                                        <span class="text-muted">Pedido no especificado</span>
                                    @endif
                                @else
                                    @if($event->productVariant)
                                        <strong>{{ $event->productVariant->product?->name ?? 'Producto' }}</strong>
                                    @else
                                        <span class="text-muted">Producto no especificado</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                @if($event->isMaterialWaste() && $event->materialItems->count() > 0)
                                    @php $firstItem = $event->materialItems->first(); @endphp
                                    <strong>{{ $firstItem->formatted_quantity }}</strong>
                                    @if($event->materialItems->count() > 1)
                                        <span class="text-muted">+ más</span>
                                    @endif
                                @elseif($event->isFinishedProductWaste())
                                    <strong>{{ $event->formatted_quantity }} unidades</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <strong style="color: #c62828;">{{ $event->formatted_total_cost }}</strong>
                            </td>
                            <td class="text-center">
                                <span title="{{ $event->reason }}" style="cursor: help;">
                                    {{ Str::limit($event->reason, 30) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($event->creator)
                                    {{ $event->creator->name }}
                                @else
                                    <span class="text-muted">Sistema</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.waste.show', $event) }}"
                                   class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No hay eventos de merma registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($wasteEvents->hasPages())
            <div class="card-footer">
                {{ $wasteEvents->links() }}
            </div>
        @endif
    </div>

    {{-- NOTA INFORMATIVA --}}
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            Para registrar merma, acceda desde: Inventario de Materiales, Pedido en Producción, o Inventario de Producto Terminado.
        </small>
    </div>
@stop

@section('css')
<style>
    .small-box .icon i {
        font-size: 70px;
        top: 20px;
    }

    /* DataTables - Botones de exportación (igual que inventario) */
    #wasteTable_wrapper .dt-buttons {
        background-color: transparent;
        box-shadow: none;
        border: none;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }

    #wasteTable_wrapper .dt-buttons .btn {
        color: #fff;
        border-radius: 4px;
        padding: 5px 15px;
        font-size: 14px;
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
document.addEventListener('DOMContentLoaded', function() {
    // DataTable con botones de exportación (igual que inventario)
    var table = $('#wasteTable').DataTable({
        "pageLength": 25,
        "order": [[0, 'desc']],
        "columnDefs": [
            {
                targets: 0,
                type: 'date',
                render: function(data, type) {
                    if (type === 'sort' || type === 'type') {
                        var tmp = document.createElement('div');
                        tmp.innerHTML = data || '';
                        var text = (tmp.textContent || tmp.innerText || '').trim();
                        if (!text) return '';
                        var parts = text.match(/(\d{2})\/(\d{2})\/(\d{4})\s*(\d{2}:\d{2})?/);
                        if (parts) return parts[3] + '-' + parts[2] + '-' + parts[1] + ' ' + (parts[4] || '00:00');
                        return text;
                    }
                    return data;
                }
            },
            { targets: 7, orderable: false }
        ],
        "language": {
            "emptyTable": "No hay eventos de merma",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ eventos",
            "infoEmpty": "Mostrando 0 a 0 de 0 eventos",
            "infoFiltered": "(Filtrado de _MAX_ total eventos)",
            "lengthMenu": "Mostrar _MENU_ eventos",
            "search": "Buscador:",
            "zeroRecords": "Sin resultados",
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
        buttons: [
            {
                text: '<i class="fas fa-copy"></i> COPIAR',
                extend: 'copy',
                className: 'btn btn-default'
            },
            {
                text: '<i class="fas fa-file-pdf"></i> PDF',
                extend: 'pdf',
                className: 'btn btn-danger',
                title: 'Registro de Mermas',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                text: '<i class="fas fa-file-csv"></i> CSV',
                extend: 'csv',
                className: 'btn btn-info',
                title: 'Registro de Mermas',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                text: '<i class="fas fa-file-excel"></i> EXCEL',
                extend: 'excel',
                className: 'btn btn-success',
                title: 'Registro de Mermas',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                text: '<i class="fas fa-print"></i> IMPRIMIR',
                extend: 'print',
                className: 'btn btn-default',
                title: 'Registro de Mermas',
                exportOptions: { columns: ':not(:last-child)' }
            }
        ]
    });
    table.buttons().container().appendTo('#wasteTable_wrapper .row:eq(0)');
});
</script>
@stop
