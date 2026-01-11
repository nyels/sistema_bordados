@extends('adminlte::page')

@section('title', 'CREAR TIPO DE APLICACIÓN')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-12 col-md-6 col-lg-4">

        {{-- MENSAJES FLASH --}}
        @foreach (['success', 'error', 'info'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }}">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="card card-primary " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">

                <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO TIPO DE APLICACIÓN</h3>
                <!-- /.card-tools -->
            </div>
            <!-- /.card-header -->
            <div class="card-body" bis_skin_checked="1">
                <form method="post" action="{{ route('admin.tipos_aplicacion.store') }}">
                    @csrf
                    @method('POST')
                    <div class="col-md-12">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Tipo de Aplicación
                            </h5>
                        </div>
                        <div class="form-group @error('nombre_aplicacion') is-invalid @enderror">
                            <label for="nombre_aplicacion">Nombre <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="nombre_aplicacion"
                                name="nombre_aplicacion" placeholder="Ej: MANGA IZQUIERDA" required
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                value="{{ old('nombre_aplicacion') }}"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                            @error('nombre_aplicacion')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-right">
                                    <a href="{{ route('admin.tipos_aplicacion.index') }}" class="btn btn-secondary"
                                        style="margin-right: 10px; padding: 8px 20px;">
                                        <i class="fas fa-times-circle"></i> Regresar
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">
                                        <i class="fas fa-save"></i> Guardar
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
    <script></script>
@stop
