@extends('adminlte::page')

@section('title', 'Eliminar Nivel de Urgencia')

@section('content_header')
    <h1><i class="fas fa-trash mr-2 text-danger"></i> Eliminar Nivel de Urgencia</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">Confirmar Eliminación</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atención:</strong> Esta acción desactivará el nivel de urgencia. Los pedidos existentes con este nivel no se verán afectados.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" class="form-control" value="{{ $urgencyLevel->name }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" class="form-control" value="{{ $urgencyLevel->slug }}" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Porcentaje de Tiempo</label>
                                <input type="text" class="form-control" value="{{ $urgencyLevel->time_percentage }}%" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Multiplicador de Precio</label>
                                <input type="text" class="form-control" value="x{{ number_format($urgencyLevel->price_multiplier, 2) }}" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" rows="2" disabled>{{ $urgencyLevel->description }}</textarea>
                    </div>

                    <div class="text-center my-4">
                        <span class="badge p-3" style="background-color: {{ $urgencyLevel->color }}; color: white; font-size: 1.2rem;">
                            @if ($urgencyLevel->icon)
                                <i class="fas {{ $urgencyLevel->icon }} mr-1"></i>
                            @endif
                            {{ $urgencyLevel->name }}
                        </span>
                    </div>

                    <hr>

                    <p class="text-center text-muted mb-0">
                        <strong>¿Deseas eliminar este nivel de urgencia?</strong>
                    </p>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('admin.urgency-levels.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Regresar
                    </a>
                    <form action="{{ route('admin.urgency-levels.destroy', $urgencyLevel->id) }}" method="POST" class="d-inline" id="deleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger" id="btnDelete">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#btnDelete').on('click', function() {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'El nivel de urgencia será desactivado',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteForm').submit();
                    }
                });
            });
        });
    </script>
@stop
