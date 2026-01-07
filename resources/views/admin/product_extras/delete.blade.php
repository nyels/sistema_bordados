@extends('adminlte::page')

@section('title', 'Eliminar Extra')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> ELIMINAR EXTRA DE PRODUCTO</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-8 offset-md-2">

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

                    <div class="text-center mt-4">
                        <a href="{{ route('admin.product_extras.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                        <form action="{{ route('admin.product_extras.destroy', $extra->id) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Sí, Eliminar
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
        console.log("ProductExtra Delete View");
    </script>
@stop
