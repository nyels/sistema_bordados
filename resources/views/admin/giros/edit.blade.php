@extends('adminlte::page')

@section('title', 'EDITAR GIRO')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-12 col-lg-4">

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

        <div class="card card-warning " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                    EDITAR GIRO
                </h3>
            </div>

            <div class="card-body" bis_skin_checked="1">
                <form method="post" action="{{ route('admin.giros.update', $giro->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="col-md-12">

                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Giro
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>
                                Nombre <span style="color: red;">*</span>
                            </label>

                            <input type="text"
                                class="form-control form-control-sm @error('nombre_giro') is-invalid @enderror"
                                id="nombre_giro" name="nombre_giro" placeholder="Ej: MEXICO"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+" title="Solo se permiten letras y espacios"
                                value="{{ $giro->nombre_giro }}"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" required>

                            @error('nombre_giro')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.giros.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@stop


@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        console.log("Hi, I'm using the Laravel-AdminLTE package!");
    </script>
@stop
