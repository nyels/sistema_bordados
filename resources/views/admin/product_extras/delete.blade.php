@extends('adminlte::page')

@section('title', 'Eliminar Extra')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> ELIMINAR EXTRA DE PRODUCTO</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-12">

                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                            <p>Está a punto de eliminar el siguiente extra:</p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 200px;">Nombre:</th>
                                        <td><strong>{{ $extra->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Costo Adicional:</th>
                                        <td>${{ number_format($extra->cost_addition, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Precio al Cliente:</th>
                                        <td>${{ number_format($extra->price_addition, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tiempo Adicional:</th>
                                        <td>{{ $extra->formatted_minutes }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <p class="text-center text-muted mt-3">
                            Esta acción no se puede deshacer. ¿Está seguro que desea continuar?
                        </p>

                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.product_extras.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times-circle"></i> Cancelar
                            </a>
                            <form id="deleteForm" action="{{ route('admin.product_extras.destroy', $extra->id) }}"
                                method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt"></i> Sí, Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @stop

    @section('css')
    @stop

    @section('js')
        <script>
            document.getElementById('deleteForm').addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡Eliminarás este extra y no se podrá recuperar!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Rojo
                    cancelButtonColor: '#6c757d', // Gris
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        </script>
    @stop
