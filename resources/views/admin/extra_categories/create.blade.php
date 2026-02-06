@extends('adminlte::page')

@section('title', 'CATEGORÍA DE EXTRA')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-primary " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">

                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVA CATEGORÍA</h3>
                <!-- /.card-tools -->
            </div>
            <!-- /.card-header -->
            <div class="card-body" bis_skin_checked="1">
                <form method="post" action="{{ route('admin.extra_categories.store') }}" id="formCreate">
                    @csrf
                    @method('POST')
                    <div class="col-md-12">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-tags" style="margin-right: 10px;"></i>
                                Datos de la Categoría
                            </h5>
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="nombre" name="nombre"
                                placeholder="Ej: EMPAQUE" required pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.extra_categories.index') }}" class="btn btn-secondary mr-2"
                                style="padding: 8px 20px;">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnGuardar" style="padding: 8px 20px;">
                                <i class="fas fa-save"></i> Guardar
                            </button>
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
        $(function() {
            $('#formCreate').on('submit', function() {
                var $btn = $('#btnGuardar');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            });
        });
    </script>
@stop
