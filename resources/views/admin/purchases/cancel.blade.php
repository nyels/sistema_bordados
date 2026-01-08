@extends('adminlte::page')

@section('title', 'Cancelar OC: ' . $purchase->purchase_number)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

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
                    <li class="breadcrumb-item active">Cancelar</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.purchases.cancel.store', $purchase->id) }}" id="cancelForm">
        @csrf

        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-ban"></i> CANCELAR ORDEN DE COMPRA
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        {{-- INFORMACIÓN DE LA COMPRA --}}
                        <div class="card border-danger mb-4">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-file-invoice"></i>
                                <strong>Orden: {{ $purchase->purchase_number }}</strong>
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
                                                    <td style="width: 130px;"><strong>Estado Actual:</strong></td>
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

                        {{-- MOTIVO DE CANCELACIÓN --}}
                        <div class="form-group">
                            <label for="cancellation_reason">
                                Motivo de Cancelación <span class="text-danger">*</span>
                            </label>
                            <textarea name="cancellation_reason" id="cancellation_reason"
                                class="form-control @error('cancellation_reason') is-invalid @enderror" rows="4" required minlength="10"
                                maxlength="500" placeholder="Explique detalladamente el motivo de la cancelación (mínimo 10 caracteres)...">{{ old('cancellation_reason') }}</textarea>
                            @error('cancellation_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <span id="charCount">0</span> / 500 caracteres (mínimo 10)
                            </small>
                        </div>

                        {{-- ADVERTENCIAS --}}
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle"></i> Advertencia
                            </h5>
                            <hr>
                            <ul class="mb-0">
                                <li>Esta acción <strong>no se puede deshacer</strong>.</li>
                                <li>La orden quedará marcada como <strong>cancelada permanentemente</strong>.</li>
                                <li>No se podrá editar ni recibir mercancía de esta orden.</li>
                                <li>El motivo de cancelación quedará registrado en el sistema.</li>
                            </ul>
                        </div>


                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-md-8 offset-md-2 text-center">
                        <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-danger" id="btnCancel">
                            <i class="fas fa-ban"></i> Confirmar Cancelación
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
            // Contador de caracteres
            const $textarea = $('#cancellation_reason');
            const $charCount = $('#charCount');
            const $btnCancel = $('#btnCancel');

            function updateCharCount() {
                const length = $textarea.val().length;
                $charCount.text(length);

                if (length < 10) {
                    $charCount.removeClass('text-success').addClass('text-danger');
                } else {
                    $charCount.removeClass('text-danger').addClass('text-success');
                }
            }

            $textarea.on('input', updateCharCount);
            updateCharCount();

            // Validación del formulario
            // Validación del formulario con SweetAlert2
            $('#cancelForm').on('submit', function(e) {
                e.preventDefault();

                if ($textarea.val().length < 10) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: 'El motivo de cancelación debe tener al menos 10 caracteres',
                        confirmButtonText: 'Entendido'
                    });
                    $textarea.focus();
                    return false;
                }

                Swal.fire({
                    title: '¿Está seguro?',
                    text: "Esta acción marcará la orden como cancelada permanentemente y no se podrá deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Rojo
                    cancelButtonColor: '#6c757d', // Gris
                    confirmButtonText: 'Sí, cancelar orden',
                    cancelButtonText: 'Volver',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
@stop
