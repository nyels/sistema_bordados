@extends('adminlte::page')

@section('title', 'Eliminar Categoría')

@section('content_header')
@stop

@section('content')
    <br>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> ELIMINAR CATEGORÍA DE PRODUCTO</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-8 offset-md-2">

                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> ¡Atención!</h5>
                        <p>Está a punto de eliminar la siguiente categoría:</p>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Nombre:</th>
                                    <td><strong>{{ $category->name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>{{ $category->description ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Productos Asociados:</th>
                                    <td>
                                        @if ($category->products_count > 0)
                                            <span class="badge badge-danger">{{ $category->products_count }}</span>
                                            <span class="text-danger ml-2">No se puede eliminar</span>
                                        @else
                                            <span class="badge badge-success">0</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($category->products_count > 0)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-circle"></i>
                            Esta categoría tiene <strong>{{ $category->products_count }} producto(s)</strong> asociado(s).
                            Debe reasignar o eliminar los productos primero.
                        </div>
                    @else
                        <p class="text-center text-muted mt-3">
                            Esta acción no se puede deshacer. ¿Está seguro que desea continuar?
                        </p>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('admin.product_categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </a>
                        @if ($category->products_count == 0)
                            <form id="deleteForm" action="{{ route('admin.product_categories.destroy', $category->id) }}"
                                method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt"></i> Sí, Eliminar
                                </button>
                            </form>
                        @endif
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
                text: "¡Eliminarás esta categoría y su contenido asociado!",
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
