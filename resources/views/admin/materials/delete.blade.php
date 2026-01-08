@extends('adminlte::page')

@section('title', 'Eliminar Material')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-trash"></i> ELIMINAR MATERIAL
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.materials.destroy', $material->id) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #dc3545; font-weight: 600;">
                                <i class="fas fa-box"></i> Datos del Material
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Categoría</label>
                            <input type="text" class="form-control" value="{{ $material->category->name ?? 'N/A' }}"
                                disabled>
                        </div>

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="{{ $material->name }}" disabled>
                        </div>

                        <div class="form-group">
                            <label>Composición</label>
                            <input type="text" class="form-control" value="{{ $material->composition ?? '-' }}" disabled>
                        </div>
                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #6c757d; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #6c757d; font-weight: 600;">
                                <i class="fas fa-info-circle"></i> Información
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" rows="3" disabled>{{ $material->description ?? '-' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Variantes Activas</label>
                            <input type="text" class="form-control" value="{{ $material->active_variants_count }}"
                                disabled>
                        </div>

                        @if ($material->active_variants_count > 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Advertencia:</strong> Este material tiene variantes activas.
                                No podrá ser eliminado hasta que se desactiven.
                            </div>
                        @endif
                    </div>
                </div>

                <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 20px;">
                    ¿Deseas eliminar este material?
                </div>

                <div class="text-center">
                    <a href="{{ route('admin.materials.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    @if ($material->active_variants_count == 0)
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @else
                        <button type="button" class="btn btn-danger" disabled title="Tiene variantes activas">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@stop
