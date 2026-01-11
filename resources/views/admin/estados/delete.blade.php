@extends('adminlte::page')

@section('title', 'ESTADO')

@section('content_header')
@stop

@section('content')
    <br>
    <br>
    <div class="col-12 col-lg-4">
        <div class="card card-danger " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">

                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ELIMINAR ESTADO</h3>
                <!-- /.card-tools -->
            </div>
            <!-- /.card-header -->
            <div class="card-body" bis_skin_checked="1">
                <form id="deleteForm" method="post" action="{{ route('admin.estados.destroy', $estado->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="col-md-12">


                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Estado
                            </h5>
                        </div>
                        <div class="form-group">


                            <label for="nombre_estado ">

                                Nombre <span style="color: red;">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="nombre_estado"
                                name="nombre_estado" placeholder="Ej: MEXICO" pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios" value="{{ $estado->nombre_estado }}"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" disabled>
                        </div>
                        <div style="font-weight: bold;font-size: 25px;text-align: center;">¿Deseas eliminar el
                            estado?</div>
                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-right">
                                    <a href="{{ route('admin.estados.index') }}" class="btn btn-secondary"
                                        style="margin-right: 10px; padding: 8px 20px;">
                                        <i class="fas fa-times-circle"></i> Regresar
                                    </a>
                                    <button type="submit" class="btn btn-danger" style="padding: 8px 20px;">
                                        <i class="fas fa-save"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Está seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminarlo',
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
