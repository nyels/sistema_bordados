@extends('adminlte::page')

@section('title', 'Eliminar Categoría de Material')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-trash"></i> ELIMINAR CATEGORÍA DE MATERIAL
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('material-categories.destroy', $category->id) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="{{ $category->name }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unidad Base</label>
                            <input type="text" class="form-control"
                                value="{{ $category->baseUnit->name ?? 'N/A' }} ({{ $category->baseUnit->symbol ?? '' }})"
                                disabled>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" rows="2" disabled>{{ $category->description }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>¿Tiene Color?</label>
                            <input type="text" class="form-control" value="{{ $category->has_color ? 'Sí' : 'No' }}"
                                disabled>
                        </div>
                    </div>
                </div>

                <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 20px;">
                    ¿Deseas eliminar esta categoría?
                </div>

                <div class="text-center">
                    <a href="{{ route('material-categories.index') }}" class="btn btn-secondary">
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
