@extends('adminlte::page')

@section('title', 'Eliminar Proveedor')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    {{-- ERRORES GENERALES --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores en el formulario:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;  font-size: 20px;"> ELIMINAR PROVEEDOR</h3>
        </div>

        <div class="card-body">
            <form id="deleteForm" method="post" action="{{ route('admin.proveedores.destroy', $proveedor->id) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    {{-- DATOS DEL PROVEEDOR --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">

                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-building"></i> Datos del Proveedor
                            </h5>
                        </div>

                        {{-- Nombre --}}
                        <div class="form-group">
                            <label>Nombre Proveedor <span style="color: red;">*</span></label>
                            <input type="text" name="nombre_proveedor"
                                class="form-control form-control-sm @error('nombre_proveedor') is-invalid @enderror"
                                value="{{ old('nombre_proveedor', $proveedor->nombre_proveedor) }}"
                                placeholder="Nombre del proveedor" disabled>
                            @error('nombre_proveedor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Giro --}}
                        <div class="form-group">
                            <label>Giro <span style="color: red;">*</span></label>
                            <select name="giro_id"
                                class="form-control form-control-sm @error('giro_id') is-invalid @enderror" disabled>
                                <option value="">Selecciona un Giro</option>
                                @foreach ($giros as $giro)
                                    <option value="{{ $giro->id }}"
                                        {{ old('giro_id', $proveedor->giro_id) == $giro->id ? 'selected' : '' }}>
                                        {{ $giro->nombre_giro }}
                                    </option>
                                @endforeach
                            </select>
                            @error('giro_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="direccion" class="form-control form-control-sm"
                                        value="{{ old('direccion', $proveedor->direccion) }}" placeholder="Dirección"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>C.P.</label>
                                    <input type="text" name="codigo_postal"
                                        class="form-control form-control-sm @error('codigo_postal') is-invalid @enderror"
                                        value="{{ old('codigo_postal', $proveedor->codigo_postal) }}" placeholder="C.P"
                                        maxlength="5" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        pattern="[0-9]{5}" disabled>
                                    @error('codigo_postal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Teléfono y correo --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teléfono <span style="color: red;">*</span></label>
                                    <input type="tel" name="telefono"
                                        class="form-control form-control-sm @error('telefono') is-invalid @enderror"
                                        value="{{ old('telefono', $proveedor->telefono) }}" disabled
                                        placeholder="Ej: 2233445566" maxlength="10"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{10}">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Correo <span style="color: red;" for="email">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control form-control-sm @error('email') is-invalid @enderror"
                                        value="{{ old('email', $proveedor->email) }}" placeholder="Ej: correo@correo.com"
                                        disabled>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Estado y ciudad --}}
                        <div class="row">
                            <div class="col-md-6">
                                <label>Estado <span style="color: red;">*</span></label>
                                <select name="estado_id"
                                    class="form-control form-control-sm @error('estado_id') is-invalid @enderror" disabled>
                                    <option value="">Selecciona un estado</option>
                                    @foreach ($estados as $estado)
                                        <option value="{{ $estado->id }}"
                                            {{ old('estado_id', $proveedor->estado_id) == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre_estado }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('estado_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label>Ciudad <span style="color: red;">*</span></label>
                                <input type="text" name="ciudad" class="form-control form-control-sm" disabled
                                    value="{{ old('ciudad', $proveedor->ciudad) }}" placeholder="Ej: Merida">
                            </div>
                        </div>
                        <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 0px;margin: 0px;">
                            ¿Deseas
                            eliminar el
                            proveedor?</div>
                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.proveedores.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </div>

                    {{-- DATOS CONTACTO --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-address-book"></i> Datos de Contacto
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Nombre del contacto</label>
                            <input type="text" name="nombre_contacto" class="form-control form-control-sm"
                                value="{{ old('nombre_contacto', $proveedor->nombre_contacto) }}"
                                placeholder="Nombre del contacto" disabled>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label>Teléfono</label>
                                <input type="tel" name="telefono_contacto" class="form-control form-control-sm"
                                    value="{{ old('telefono_contacto', $proveedor->telefono_contacto) }}"
                                    placeholder="Ej: 2233445566" maxlength="10"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{10}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="email_contacto">Correo</label>
                                <input type="email" name="email_contacto" class="form-control form-control-sm"
                                    value="{{ old('email_contacto', $proveedor->email_contacto) }}"
                                    placeholder="Ej: correo@correo.com" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        // SweetAlert2 para eliminar proveedor
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡Eliminarás este proveedor y todos sus datos asociados!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Rojo
                cancelButtonColor: '#6c757d', // Gris (Secondary)
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
