@extends('adminlte::page')

@section('title', 'Eliminar Categoría de Material')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="row">
        <!-- Centered Card -->
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-danger">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h3 class="card-title text-danger" style="font-weight: 700; font-size: 18px;">
                            <i class="fas fa-exclamation-triangle mr-2"></i>ELIMINAR CATEGORÍA DE MATERIAL
                        </h3>
                    </div>

                    <div class="card-body pt-3 pb-2">
                        <form id="deleteForm" method="POST"
                            action="{{ route('admin.material-categories.destroy', $category->id) }}">
                            @csrf
                            @method('DELETE')

                            <div class="form-group mb-2">
                                <label class="small text-muted mb-1">Nombre</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $category->name }}"
                                    disabled style="font-weight: 600; color: #1f2937;">
                            </div>

                            <div class="form-group mb-2">
                                <label class="small text-muted mb-1">Unidad Base</label>
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ $category->baseUnit->name ?? 'N/A' }} ({{ $category->baseUnit->symbol ?? '' }})"
                                    disabled style="font-weight: 500;">
                            </div>

                            <div class="form-group mb-2">
                                <label class="small text-muted mb-1">Descripción</label>
                                <textarea class="form-control form-control-sm" rows="2" disabled style="background-color: #f8fafc;">{{ $category->description }}</textarea>
                            </div>

                            <div class="form-group mb-2">
                                <label class="small text-muted mb-1">¿Tiene Color?</label>
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ $category->has_color ? 'Sí' : 'No' }}" disabled style="font-weight: 500;">
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <h4 style="font-weight: 700; color: #374151; margin-bottom: 20px;">
                                        ¿Deseas eliminar esta categoría?
                                    </h4>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="text-right pb-3">
                                        <a href="{{ route('admin.material-categories.index') }}"
                                            class="btn btn-secondary shadow-sm">
                                            <i class="fas fa-times-circle"></i> Regresar
                                        </a>
                                        <button type="submit" class="btn btn-danger shadow-sm ml-2">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @stop

    @section('js')
        <script>
            document.getElementById('deleteForm').addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡Eliminarás esta categoría y su contenido asociado!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
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
