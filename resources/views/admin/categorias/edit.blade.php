@extends('adminlte::page')

@section('title', 'CATEGORIA')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="col-md-4">

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
                    EDITAR CATEGORIA
                </h3>
            </div>

            <div class="card-body" bis_skin_checked="1">
                <form method="post" action="{{ route('admin.categorias.update', $category->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="col-md-12">

                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos de la Categoria
                            </h5>
                        </div>

                        <div class="form-group">
                            <label for="name">
                                Nombre <span style="color: red;">*</span>
                            </label>

                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Ej: MEXICO" pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios" value="{{ $category->name }}"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')" required>

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-right">
                                <a href="{{ route('admin.categorias.index') }}" class="btn btn-secondary"
                                    style="margin-right: 10px;">
                                    <i class="fas fa-times-circle"></i> Regresar
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                            </div>
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
