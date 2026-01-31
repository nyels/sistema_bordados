@extends('adminlte::page')

@section('title', 'Eliminar OC: ' . $purchase->purchase_number)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- BREADCRUMB --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.purchases.index') }}">
                            <i class="fas fa-shopping-cart"></i> Órdenes de Compra
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.purchases.show', $purchase->id) }}">
                            {{ $purchase->purchase_number }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Eliminar</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.purchases.destroy', $purchase->id) }}" id="deleteForm">
        @csrf
        @method('DELETE')

        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-trash"></i> ELIMINAR ORDEN DE COMPRA
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        {{-- INFORMACIÓN DE LA COMPRA --}}
                        <div class="card border-danger mb-4">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-file-invoice"></i>
                                <strong>Orden a Eliminar: {{ $purchase->purchase_number }}</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 130px;"><strong>Proveedor:</strong></td>
                                                    <td>{{ $purchase->proveedor->nombre_proveedor ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Fecha Orden:</strong></td>
                                                    <td>{{ $purchase->ordered_at ? $purchase->ordered_at->format('d/m/Y') : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Referencia:</strong></td>
                                                    <td>{{ $purchase->reference ?? '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 130px;"><strong>Estado:</strong></td>
                                                    <td>
                                                        <span class="badge badge-{{ $purchase->status_color }}">
                                                            <i class="{{ $purchase->status_icon }}"></i>
                                                            {{ $purchase->status_label }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Items:</strong></td>
                                                    <td>{{ $purchase->items->count() }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total:</strong></td>
                                                    <td class="font-weight-bold text-primary" style="font-size: 18px;">
                                                        {{ $purchase->formatted_total }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- DETALLE DE ITEMS --}}
                        @if ($purchase->items->count() > 0)
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white py-2">
                                    <strong><i class="fas fa-list"></i> Items que serán eliminados</strong>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Material</th>
                                                <th>SKU</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($purchase->items as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->materialVariant->material->name ?? 'N/A' }}</td>
                                                    <td><code>{{ $item->materialVariant->sku ?? 'N/A' }}</code></td>
                                                    <td class="text-center">
                                                        {{ number_format($item->quantity, 2) }}
                                                        {{ $item->unit->symbol ?? '' }}
                                                    </td>
                                                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- ADVERTENCIAS --}}
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-circle"></i> ¡Atención!
                            </h5>
                            <hr>
                            <p class="mb-0">
                                Está a punto de <strong>eliminar permanentemente</strong> esta orden de compra.
                                Esta acción:
                            </p>
                            <ul class="mt-2 mb-0">
                                <li>Eliminará la orden de compra y <strong>todos sus items</strong>.</li>
                                <li><strong>No se puede deshacer</strong>.</li>
                                <li>Solo es posible porque la orden está en estado <strong>Borrador</strong>.</li>
                            </ul>
                        </div>

                        {{-- CONFIRMACIÓN --}}
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="confirmDelete" required>
                            <label class="custom-control-label text-danger" for="confirmDelete">
                                <strong>Entiendo que esta acción es permanente y deseo eliminar esta orden</strong>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-md-8 offset-md-2 text-center">
                        <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger" id="btnDelete">
                            <i class="fas fa-trash"></i> Eliminar Permanentemente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(function() {
            const $confirmCheck = $('#confirmDelete');
            const $btnDelete = $('#btnDelete');

            // Validación del formulario
            $('#deleteForm').on('submit', function(e) {
                if (!$confirmCheck.is(':checked')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Confirmación requerida',
                        text: 'Debe confirmar que entiende que esta acción es permanente',
                        confirmButtonColor: '#3085d6'
                    });
                    return false;
                }

                e.preventDefault();
                var form = this;
                Swal.fire({
                    icon: 'warning',
                    title: '¿Está COMPLETAMENTE SEGURO?',
                    html: '<strong>Orden:</strong> {{ $purchase->purchase_number }}<br>' +
                          '<strong>Total:</strong> {{ $purchase->formatted_total }}<br><br>' +
                          '<span class="text-danger">Esta acción NO se puede deshacer.</span>',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
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
