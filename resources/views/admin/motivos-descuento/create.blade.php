@extends('adminlte::page')

@section('title', 'MOTIVO DE DESCUENTO')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-primary " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">

                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO MOTIVO DE DESCUENTO</h3>
                <!-- /.card-tools -->
            </div>
            <!-- /.card-header -->
            <div class="card-body" bis_skin_checked="1">
                <form method="post" action="{{ route('admin.motivos-descuento.store') }}">
                    @csrf
                    @method('POST')
                    <div class="col-md-12">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-percent" style="margin-right: 10px;"></i>
                                Datos del Motivo de Descuento
                            </h5>
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('nombre') is-invalid @enderror"
                                id="nombre"
                                name="nombre"
                                placeholder="Ej: PROMOCIÓN, CLIENTE FRECUENTE"
                                required
                                value="{{ old('nombre') }}"
                                maxlength="255">
                            @error('nombre')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.motivos-descuento.index') }}" class="btn btn-secondary mr-2"
                                style="padding: 8px 20px;">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">
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
@stop

@section('js')
@stop
