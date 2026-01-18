@extends('adminlte::page')

@section('title', 'Nuevo Proveedor')

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

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO PROVEEDOR</h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('admin.proveedores.store') }}">
                @csrf
                @method('POST')

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
                                value="{{ old('nombre_proveedor') }}" required placeholder="Nombre del proveedor">
                            @error('nombre_proveedor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Giro (Con botón de agregar rápido) --}}
                        <div class="form-group">
                            <label>Giro <span style="color: red;">*</span></label>
                            <div class="input-group input-group-sm">
                                <select name="giro_id" id="giro_id"
                                    class="form-control @error('giro_id') is-invalid @enderror" required>
                                    <option value="">Selecciona un Giro</option>
                                    @foreach ($giros as $giro)
                                        <option value="{{ $giro->id }}"
                                            {{ old('giro_id') == $giro->id ? 'selected' : '' }}>
                                            {{ $giro->nombre_giro }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-purple"
                                        style="background-color: #6f42c1; color: white;" data-toggle="modal"
                                        data-target="#modalCreateGiro" title="Nuevo Giro">
                                        <i class="fas fa-database"></i>
                                    </button>
                                </div>
                                @error('giro_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Dirección --}}
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="direccion" class="form-control form-control-sm"
                                        value="{{ old('direccion') }}" placeholder="Dirección">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>C.P.</label>
                                    <input type="text" name="codigo_postal"
                                        class="form-control form-control-sm @error('codigo_postal') is-invalid @enderror"
                                        value="{{ old('codigo_postal') }}" maxlength="5"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{5}"
                                        placeholder="Ej: 97000">
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
                                        value="{{ old('telefono') }}" required placeholder="Ej: 2233445566" maxlength="10"
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
                                        value="{{ old('email') }}" placeholder="Ej: correo@correo.com">
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
                                    class="form-control form-control-sm @error('estado_id') is-invalid @enderror" required>
                                    <option value="">Selecciona un estado</option>
                                    @foreach ($estados as $estado)
                                        <option value="{{ $estado->id }}"
                                            {{ old('estado_id') == $estado->id ? 'selected' : '' }}>
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
                                <input type="text" name="ciudad" class="form-control form-control-sm"
                                    value="{{ old('ciudad') }}" placeholder="Ej: Mérida">
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.proveedores.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
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
                                value="{{ old('nombre_contacto') }}" placeholder="Ej: Juan Perez">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label>Teléfono</label>
                                <input type="tel" name="telefono_contacto" class="form-control form-control-sm"
                                    value="{{ old('telefono_contacto') }}" maxlength="10"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{10}"
                                    placeholder="Ej: 2233445566">
                            </div>
                            <div class="col-md-6">
                                <label for="email_contacto">Correo</label>
                                <input type="email" name="email_contacto" class="form-control form-control-sm"
                                    value="{{ old('email_contacto') }}" placeholder="Ej: correo@correo.com">
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- MODAL CREAR GIRO (AJAX) --}}
    <div class="modal fade" id="modalCreateGiro" tabindex="-1" role="dialog" aria-labelledby="modalCreateGiroLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="modalCreateGiroLabel">
                        <i class="fas fa-building mr-2"></i> NUEVO GIRO
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formCreateGiro" action="{{ route('admin.giros.store') }}" method="POST">
                    <div class="modal-body">
                        @csrf
                        <div class="form-group">
                            <label for="nombre_giro_modal" class="font-weight-bold text-primary">Nombre del Giro <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre_giro_modal" name="nombre_giro"
                                placeholder="Ej: TELAS" required pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios"
                                oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '').toUpperCase()">
                            <small class="text-muted">El nombre se guardará en mayúsculas automáticamente.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" id="btnCancelGiroModal">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSaveGiroModal">
                            <i class="fas fa-save"></i> Guardar
                        </button>
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
        $(function() {
            var $modal = $('#modalCreateGiro');
            var $form = $('#formCreateGiro');
            var $btnSave = $('#btnSaveGiroModal');
            var $btnCancel = $('#btnCancelGiroModal');
            var $input = $('#nombre_giro_modal');
            var isSubmitting = false;

            // ============================================
            // FUNCIÓN MAESTRA: Habilitar botones
            // ============================================
            function enableButtons() {
                $btnSave
                    .prop('disabled', false)
                    .removeAttr('disabled')
                    .removeClass('disabled')
                    .css('pointer-events', 'auto')
                    .html('<i class="fas fa-save"></i> Guardar');

                $btnCancel
                    .prop('disabled', false)
                    .removeAttr('disabled')
                    .removeClass('disabled')
                    .css('pointer-events', 'auto');

                isSubmitting = false;
            }

            // ============================================
            // FUNCIÓN MAESTRA: Deshabilitar botones
            // ============================================
            function disableButtons() {
                isSubmitting = true;

                $btnSave
                    .prop('disabled', true)
                    .attr('disabled', 'disabled')
                    .addClass('disabled')
                    .css('pointer-events', 'none')
                    .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

                $btnCancel
                    .prop('disabled', true)
                    .attr('disabled', 'disabled')
                    .addClass('disabled')
                    .css('pointer-events', 'none');
            }

            // ============================================
            // FUNCIÓN MAESTRA: Reset completo del modal
            // ============================================
            function resetModal() {
                // 1. Limpiar formulario
                $form[0].reset();
                $input.val('');
                $form.find('.is-invalid').removeClass('is-invalid');

                // 2. Habilitar botones
                enableButtons();
            }

            // ============================================
            // EVENTOS DEL MODAL
            // ============================================

            // Al ABRIR: Reset total
            $modal.on('show.bs.modal', function() {
                resetModal();
            });

            // Al terminar de CERRAR: Reset total (garantía)
            $modal.on('hidden.bs.modal', function() {
                resetModal();
            });

            // Botón X (cerrar): permitir cierre
            $modal.find('.close').on('click', function() {
                if (!isSubmitting) {
                    $modal.modal('hide');
                }
            });

            // Botón Cancelar
            $btnCancel.on('click', function(e) {
                e.preventDefault();
                if (!isSubmitting) {
                    $modal.modal('hide');
                }
            });

            // ============================================
            // SUBMIT AJAX
            // ============================================
            $form.on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Prevenir doble submit
                if (isSubmitting) {
                    return false;
                }

                // Validar campo vacío
                var nombre = $input.val().trim();
                if (!nombre) {
                    $input.addClass('is-invalid').focus();
                    return false;
                }

                // Deshabilitar botones
                disableButtons();

                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    dataType: 'json',
                    timeout: 30000, // 30 segundos timeout
                    success: function(response) {
                        if (response.success) {
                            // Toast éxito
                            Swal.fire({
                                position: 'top-end',
                                icon: 'success',
                                title: response.message || 'Giro creado correctamente',
                                showConfirmButton: false,
                                timer: 1500,
                                toast: true
                            });

                            // Agregar al select y seleccionar
                            var newOption = new Option(response.data.nombre_giro, response.data.id, true, true);
                            $('#giro_id').append(newOption);

                            // Cerrar modal (hidden.bs.modal hará el reset)
                            $modal.modal('hide');
                        } else {
                            // Respuesta success:false del servidor
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'No se pudo guardar'
                            });
                            enableButtons();
                        }
                    },
                    error: function(xhr, status, error) {
                        var msg = 'Ocurrió un error al guardar.';

                        if (status === 'timeout') {
                            msg = 'Tiempo de espera agotado. Intente de nuevo.';
                        } else if (xhr.responseJSON) {
                            if (xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            } else if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                var firstError = Object.values(xhr.responseJSON.errors)[0];
                                msg = Array.isArray(firstError) ? firstError[0] : firstError;
                            }
                        } else if (xhr.status === 422) {
                            msg = 'Datos inválidos. Verifique el formulario.';
                        } else if (xhr.status === 500) {
                            msg = 'Error interno del servidor.';
                        } else if (xhr.status === 0) {
                            msg = 'Sin conexión al servidor.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });

                        // Reactivar botones
                        enableButtons();
                    },
                    complete: function() {
                        // Garantía: si el modal sigue abierto, asegurar estado correcto
                        if ($modal.hasClass('show') && !isSubmitting) {
                            enableButtons();
                        }
                    }
                });

                return false;
            });

            // ============================================
            // GARANTÍA EXTRA: Click directo en botón guardar
            // ============================================
            $btnSave.on('click', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Si no está en submit, dejar que el form maneje el evento
            });
        });
    </script>
@stop
