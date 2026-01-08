@extends('adminlte::page')

@section('title', 'Eliminar Cliente')

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
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> ELIMINAR CLIENTE</h3>
        </div>

        <div class="card-body">
            <form id="deleteForm" method="post" action="{{ route('admin.clientes.destroy', $cliente->id) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    {{-- DATOS DEL CLIENTE --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">

                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600;">
                                <i class="fas fa-user-tie"></i> Datos del Cliente
                            </h5>
                        </div>


                        {{-- Nombres y apellidos --}}
                        <div class="row">
                            {{-- Nombres --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombres <span style="color: red;">*</span></label>
                                    <input type="text" name="nombre" id="nombre"
                                        class="form-control form-control-sm @error('nombre') is-invalid @enderror"
                                        value="{{ old('nombre', $cliente->nombre) }}" required placeholder="Nombres"
                                        oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')" disabled>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- Apellidos --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellidos">Apellidos <span style="color: red;">*</span></label>
                                    <input type="text" name="apellidos" id="apellidos"
                                        class="form-control form-control-sm @error('apellidos') is-invalid @enderror"
                                        value="{{ old('apellidos', $cliente->apellidos) }}" required placeholder="Apellidos"
                                        oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')" disabled>
                                    @error('apellidos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>



                        {{-- Dirección --}}
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="direccion">Dirección</label>
                                    <input type="text" name="direccion" id="direccion"
                                        class="form-control form-control-sm @error('direccion') is-invalid @enderror"
                                        value="{{ old('direccion', $cliente->direccion) }}" placeholder="Dirección"
                                        disabled>
                                    @error('direccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="codigo_postal">C.P.</label>
                                    <input type="text" name="codigo_postal" id="codigo_postal"
                                        class="form-control form-control-sm @error('codigo_postal') is-invalid @enderror"
                                        value="{{ old('codigo_postal', $cliente->codigo_postal) }}" maxlength="5"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{5}"
                                        placeholder="Ej: 97000" disabled>
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
                                    <label for="telefono">Teléfono <span style="color: red;">*</span></label>
                                    <input type="phone" name="telefono" id="telefono"
                                        class="form-control form-control-sm @error('telefono') is-invalid @enderror"
                                        value="{{ old('telefono', $cliente->telefono) }}" required
                                        placeholder="Ej: 2233445566" maxlength="10" disabled
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="[0-9]{10}">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email"
                                        class="form-control form-control-sm @error('email') is-invalid @enderror"
                                        value="{{ old('email', $cliente->email) }}" placeholder="Ej: correo@correo.com"
                                        disabled>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Estado y ciudad --}}
                        {{-- estado --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estado_id">Estado <span style="color: red;">*</span></label>
                                    <select name="estado_id" id="estado_id"
                                        class="form-control form-control-sm @error('estado_id') is-invalid @enderror"
                                        disabled>
                                        <option value="">Selecciona un estado</option>
                                        @foreach ($estados as $estado)
                                            <option value="{{ $estado->id }}"
                                                {{ old('estado_id', $cliente->estado_id) == $estado->id ? 'selected' : '' }}>
                                                {{ $estado->nombre_estado }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('estado_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- ciudad --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ciudad">Ciudad <span style="color: red;">*</span></label>
                                    <input type="text" name="ciudad" id="ciudad"
                                        class="form-control form-control-sm @error('ciudad') is-invalid @enderror"
                                        value="{{ old('ciudad', $cliente->ciudad) }}" placeholder="Ej: Mérida" disabled>
                                    @error('ciudad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>




                        {{-- Recomendacion --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="recomendacion_id">Recomendacion <span style="color: red;">*</span></label>
                                    <select name="recomendacion_id" id="recomendacion_id"
                                        class="form-control form-control-sm @error('recomendacion_id') is-invalid @enderror"
                                        disabled>
                                        <option value="">Selecciona una recomendacion</option>
                                        @foreach ($recomendaciones as $recomendacion)
                                            <option value="{{ $recomendacion->id }}"
                                                {{ old('recomendacion_id', $cliente->recomendacion_id) == $recomendacion->id ? 'selected' : '' }}>
                                                {{ $recomendacion->nombre_recomendacion }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('recomendacion_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones <span style="color: red;">*</span></label>
                                    <textarea name="observaciones" id="observaciones" placeholder="Observaciones"
                                        class="form-control form-control-sm @error('observaciones') is-invalid @enderror" disabled>{{ old('observaciones', $cliente->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 0px;margin: 0px;">
                            ¿Deseas
                            eliminar el
                            cliente?</div>

                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </div>

                    {{-- MEDIDAS --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid rgb(225, 0, 255); padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: rgb(225, 0, 255); font-weight: 600;">
                                <i class="fas fa-ruler-vertical text-pink"></i>
                                <i class="fas fa-female fa-lg mr-2 text-pink"></i> Medidas(cm)
                            </h5>
                        </div>

                        {{-- BUSTO --}}
                        <div class="row">
                            <div class="form-group col-md-4 text-center">
                                <label for="busto" class="medida-label">BUSTO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/busto.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="busto" id="busto"
                                        class="form-control form-control-sm medida-input @error('busto') is-invalid @enderror"
                                        value="{{ old('busto', $cliente->busto) }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)" disabled>
                                    @error('busto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- ALTO CINTURA --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="alto_cintura" class="medida-label">ALTO CINTURA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/alto_cintura.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="alto_cintura" id="alto_cintura"
                                        class="form-control form-control-sm medida-input @error('alto_cintura') is-invalid @enderror"
                                        value="{{ old('alto_cintura', $cliente->alto_cintura) }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)" disabled>
                                    @error('alto_cintura')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- CINTURA --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="cintura" class="medida-label">CINTURA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/cintura.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="cintura" id="cintura"
                                        class="form-control form-control-sm medida-input @error('cintura') is-invalid @enderror"
                                        value="{{ old('cintura', $cliente->cintura) }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)" disabled>
                                    @error('cintura')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- CADERA --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="cadera" class="medida-label">CADERA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/cadera.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="cadera" id="cadera"
                                        class="form-control form-control-sm medida-input @error('cadera') is-invalid @enderror"
                                        value="{{ old('cadera', $cliente->cadera) }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)" disabled>
                                    @error('cadera')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- LARGO --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="largo" class="medida-label">LARGO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/largo.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="largo" id="largo"
                                        class="form-control form-control-sm medida-input @error('largo') is-invalid @enderror"
                                        value="{{ old('largo', $cliente->largo) }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)" disabled>
                                    @error('largo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* === CONTENEDOR PRINCIPAL === */
        .medida-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 12px 14px;
            background: #ffffff;
            transition:
                box-shadow 0.25s ease,
                transform 0.25s ease,
                border-color 0.25s ease;
            cursor: pointer;
            position: relative;
        }

        /* === HOVER PREMIUM === */
        .medida-card:hover {
            transform: translateY(-4px);
            border-color: rgba(247, 0, 255, 0.779);
            box-shadow:
                0 10px 22px rgba(0, 0, 0, 0.06),
                0 4px 8px rgba(0, 0, 0, 0.04);
        }

        /* === IMAGEN === */
        .medida-img {
            width: 55%;
            margin-bottom: 10px;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .medida-card:hover .medida-img {
            transform: scale(1.03);
            opacity: 0.95;
        }

        /* === INPUT === */
        .medida-input {
            border-radius: 8px;
            transition:
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        /* FOCUS REAL (APPLE STYLE) */
        .medida-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        /* === LABEL === */
        .medida-label {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
            color: #111827;
        }

        /* === ERROR VISUAL === */
        .medida-input.is-invalid {
            border-color: #dc3545;
            box-shadow: none;
        }

        .invalid-feedback {
            font-size: 0.75rem;
            margin-top: 6px;
        }
    </style>
@stop

@section('js')
    <script>
        function validateMedida(input) {
            let value = input.value;

            // 1. Eliminar todo excepto números y punto
            value = value.replace(/[^0-9.]/g, '');

            // 2. Evitar más de un punto
            value = value.replace(/(\..*)\./g, '$1');

            // Regex FINAL (igual al backend)
            const finalRegex = /^[1-9]\d{1,2}(\.(?:[1-9]|\d[1-9]))?$/;

            // Regex parcial permitida SOLO para escribir
            const partialRegex = /^[1-9]\d{0,2}(\.\d{0,2})?$/;

            // 3. Bloquear casos inválidos inmediatos
            if (
                value === '.' ||
                value === '0' ||
                value === '0.' ||
                value === '.0'
            ) {
                input.value = '';
                return;
            }

            // 4. Permitir escritura progresiva válida
            if (!partialRegex.test(value)) {
                input.value = value.slice(0, -1);
                return;
            }

            // 5. Validación visual final
            if (finalRegex.test(value)) {
                input.classList.remove('is-invalid');
            } else {
                input.classList.add('is-invalid');
            }

            input.value = value;
        }

        // SweetAlert2 para eliminar cliente
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡Eliminarás este cliente y todos sus datos asociados!",
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
