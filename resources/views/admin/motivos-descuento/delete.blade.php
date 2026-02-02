@extends('adminlte::page')

@section('title', 'ELIMINAR MOTIVO DE DESCUENTO')

@section('content_header')
@stop

@section('content')
    <br>
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

        <div class="card card-danger " bis_skin_checked="1">

            <div class="card-header" bis_skin_checked="1">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                    ELIMINAR MOTIVO DE DESCUENTO
                </h3>
            </div>

            <div class="card-body" bis_skin_checked="1">
                <form id="deleteForm" method="post"
                    action="{{ route('admin.motivos-descuento.destroy', $motivoDescuento->id) }}">
                    @csrf
                    @method('DELETE')

                    <div class="col-md-12">

                        <div style="border-bottom: 3px solid #dc3545; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #dc3545; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-percent" style="margin-right: 10px;"></i>
                                Datos del Motivo de Descuento
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>
                                Nombre <span style="color: red;">*</span>
                            </label>

                            <input type="text"
                                class="form-control form-control-sm"
                                id="nombre"
                                name="nombre"
                                value="{{ $motivoDescuento->nombre }}"
                                disabled>
                        </div>

                        <div class="d-flex justify-content-end align-items-center mt-4">
                            <a href="{{ route('admin.motivos-descuento.index') }}" class="btn btn-secondary mr-2"
                                style="padding: 8px 20px;">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-danger" style="padding: 8px 20px;">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>

                    </div>
                </form>
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
