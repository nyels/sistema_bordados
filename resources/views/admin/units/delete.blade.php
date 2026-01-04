@extends('adminlte::page')

@section('title', 'Eliminar Unidad de Medida')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ELIMINAR UNIDAD DE MEDIDA</h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('units.destroy', $unit->id) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="{{ $unit->name }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Símbolo</label>
                            <input type="text" class="form-control" value="{{ $unit->symbol }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo</label>
                            <input type="text" class="form-control"
                                value="{{ $unit->is_base ? 'Base (Consumo)' : 'Compra' }}" disabled>
                        </div>
                    </div>
                </div>

                <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 20px;">
                    ¿Deseas eliminar esta unidad de medida?
                </div>

                <div class="text-center">
                    <a href="{{ route('units.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop
