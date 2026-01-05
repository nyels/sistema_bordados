@extends('adminlte::page')

@section('title', 'Eliminar Valor de Atributo')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ELIMINAR VALOR DE ATRIBUTO</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                            <p>Está a punto de eliminar el siguiente valor de atributo. Esta acción no se puede deshacer.
                            </p>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 30%;">Atributo:</th>
                                        <td>
                                            <span
                                                class="badge badge-info">{{ $attributeValue->attribute->name ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Valor:</th>
                                        <td><strong>{{ $attributeValue->value }}</strong></td>
                                    </tr>
                                    @if ($attributeValue->hex_color)
                                        <tr>
                                            <th>Color:</th>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        style="
                                                display: inline-block;
                                                width: 30px;
                                                height: 30px;
                                                background-color: {{ $attributeValue->hex_color }};
                                                border: 2px solid #333;
                                                border-radius: 4px;
                                                margin-right: 10px;
                                            "></span>
                                                    <code>{{ $attributeValue->hex_color }}</code>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <th>Creado:</th>
                                        <td>{{ $attributeValue->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.attribute-values.destroy', $attributeValue->id) }}">
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
