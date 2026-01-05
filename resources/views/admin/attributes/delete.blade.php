@extends('adminlte::page')

@section('title', 'Eliminar Atributo')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ELIMINAR ATRIBUTO</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                            <p>Está a punto de eliminar el siguiente atributo. Esta acción no se puede deshacer.</p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 30%;">Nombre:</th>
                                        <td><strong>{{ $attribute->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Slug:</th>
                                        <td><code>{{ $attribute->slug }}</code></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.attributes.destroy', $attribute->id) }}">
                            @csrf
                            @method('DELETE')

                            <div class="text-center mt-4">
                                <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Confirmar Eliminación
                                </button>
                            </div>
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
@stop
