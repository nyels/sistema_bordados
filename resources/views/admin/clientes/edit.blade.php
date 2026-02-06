@extends('adminlte::page')

@section('title', 'Editar Cliente')

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

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> EDITAR CLIENTE</h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('admin.clientes.update', $cliente->id) }}">
                @csrf
                @method('PUT')

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
                                        oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')">
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
                                        oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')">
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
                                        value="{{ old('direccion', $cliente->direccion) }}" placeholder="Dirección">
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
                                    <label for="telefono">Teléfono <span style="color: red;">*</span></label>
                                    <input type="phone" name="telefono" id="telefono"
                                        class="form-control form-control-sm @error('telefono') is-invalid @enderror"
                                        value="{{ old('telefono', $cliente->telefono) }}" required
                                        placeholder="Ej: 2233445566" maxlength="10"
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
                                        value="{{ old('email', $cliente->email) }}" placeholder="Ej: correo@correo.com">
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
                                        required>
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
                                    <label for="ciudad">Ciudad</label>
                                    <input type="text" name="ciudad" id="ciudad"
                                        class="form-control form-control-sm @error('ciudad') is-invalid @enderror"
                                        value="{{ old('ciudad', $cliente->ciudad) }}" placeholder="Ej: Mérida">
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
                                        required>
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
                                        class="form-control form-control-sm @error('observaciones') is-invalid @enderror">{{ old('observaciones', $cliente->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>



                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
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

                        {{-- TABS DE MEDIDAS --}}
                        <div class="measures-edit-tabs">
                            <button type="button" class="measures-edit-tab active" data-tab="current">
                                <i class="fas fa-ruler-combined"></i> Editar Medidas
                            </button>
                            <button type="button" class="measures-edit-tab" data-tab="history">
                                <i class="fas fa-history"></i> Historial
                                @php
                                    $historyCount = $cliente->measurementHistory()->count();
                                @endphp
                                @if($historyCount > 0)
                                    <span class="history-count-badge">{{ $historyCount }}</span>
                                @endif
                            </button>
                        </div>

                        {{-- TAB: EDITAR MEDIDAS (actual) --}}
                        <div class="measures-edit-content active" data-content="current">
                        @php
                            // Obtener las últimas medidas (del historial o campos legacy)
                            $currentMeasurements = $cliente->latest_measurements;
                        @endphp
                        {{-- BUSTO --}}
                        <div class="row">
                            <div class="form-group col-md-4 text-center">
                                <label for="busto" class="medida-label">BUSTO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/busto.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="busto" id="busto"
                                        class="form-control form-control-sm medida-input @error('busto') is-invalid @enderror"
                                        value="{{ old('busto', $currentMeasurements['busto'] ?? '') }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)">
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
                                        value="{{ old('alto_cintura', $currentMeasurements['alto_cintura'] ?? '') }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)">
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
                                        value="{{ old('cintura', $currentMeasurements['cintura'] ?? '') }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)">
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
                                        value="{{ old('cadera', $currentMeasurements['cadera'] ?? '') }}" placeholder="Ej: 80.56"
                                        maxlength="6" inputmode="decimal" oninput="validateMedida(this)">
                                    @error('cadera')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- LARGO BLUSA --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="largo" class="medida-label">LARGO BLUSA</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/largo.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="largo" id="largo"
                                        class="form-control form-control-sm medida-input @error('largo') is-invalid @enderror"
                                        value="{{ old('largo', $currentMeasurements['largo'] ?? '') }}" placeholder="Ej: 80.56" maxlength="6"
                                        inputmode="decimal" oninput="validateMedida(this)">
                                    @error('largo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- LARGO VESTIDO --}}
                            <div class="form-group col-md-4 text-center">
                                <label for="largo_vestido" class="medida-label">LARGO VESTIDO</label>
                                <div class="medida-card">
                                    <img src="{{ asset('images/largo_vestido.png') }}" alt="Medidas"
                                        class="img-fluid medida-img">
                                    <input type="text" name="largo_vestido" id="largo_vestido"
                                        class="form-control form-control-sm medida-input @error('largo_vestido') is-invalid @enderror"
                                        value="{{ old('largo_vestido', $currentMeasurements['largo_vestido'] ?? '') }}"
                                        placeholder="Ej: 80.56" maxlength="6" inputmode="decimal"
                                        oninput="validateMedida(this)">
                                    @error('largo_vestido')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- FIN LARGO VESTIDO --}}
                        </div>
                        </div>

                        {{-- TAB: HISTORIAL DE MEDIDAS --}}
                        <div class="measures-edit-content" data-content="history">
                            @php
                                $allHistory = $cliente->measurementHistory()->with(['order', 'product'])->orderBy('captured_at', 'desc')->get();
                                $measurementLabels = [
                                    'busto' => 'Busto',
                                    'cintura' => 'Cintura',
                                    'cadera' => 'Cadera',
                                    'alto_cintura' => 'Alto Cintura',
                                    'largo' => 'Largo',
                                    'largo_vestido' => 'Largo Vestido',
                                ];
                                $sourceLabels = [
                                    'order' => ['label' => 'Pedido', 'icon' => 'fa-shopping-cart'],
                                    'manual' => ['label' => 'Manual', 'icon' => 'fa-user-edit'],
                                    'import' => ['label' => 'Importado', 'icon' => 'fa-file-import'],
                                ];
                            @endphp

                            @if($allHistory->count() > 0)
                                <div class="history-scroll-container-edit">
                                    @foreach($allHistory as $index => $record)
                                        @php
                                            $recSourceInfo = $sourceLabels[$record->source] ?? ['label' => 'Desconocido', 'icon' => 'fa-question'];
                                            $recMeasurements = $record->measurements ?? [];
                                        @endphp
                                        <div class="history-item-edit {{ $index === 0 ? 'history-item-current' : '' }}">
                                            <div class="history-header-edit">
                                                @if($index === 0)
                                                    <span class="history-badge-edit history-badge-current">
                                                        <i class="fas fa-star"></i> Actual
                                                    </span>
                                                @endif
                                                <span class="history-badge-edit">
                                                    <i class="fas {{ $recSourceInfo['icon'] }}"></i> {{ $recSourceInfo['label'] }}
                                                </span>
                                                <span class="history-badge-edit">
                                                    <i class="fas fa-calendar-alt"></i> {{ $record->captured_at->format('d/m/Y H:i') }}
                                                </span>
                                                @if($record->order)
                                                    <span class="history-badge-edit">
                                                        <i class="fas fa-file-invoice"></i> {{ $record->order->order_number }}
                                                    </span>
                                                @endif
                                                @if($record->product)
                                                    <span class="history-badge-edit">
                                                        <i class="fas fa-tshirt"></i> {{ $record->product->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="history-measurements-edit">
                                                @foreach($measurementLabels as $key => $label)
                                                    @if(!empty($recMeasurements[$key]) && $recMeasurements[$key] !== '0')
                                                        <div class="history-measure-edit">
                                                            {{ $label }}: <span>{{ $recMeasurements[$key] }} cm</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="history-empty-edit">
                                    <i class="fas fa-history"></i>
                                    <p>No hay historial de medidas registrado.</p>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* === TABS DE MEDIDAS === */
        .measures-edit-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 16px;
        }

        .measures-edit-tab {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #6c757d;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
        }

        .measures-edit-tab:hover {
            color: #6f42c1;
            background: #f8f9fa;
        }

        .measures-edit-tab.active {
            color: #6f42c1;
            border-bottom-color: #6f42c1;
        }

        .measures-edit-tab i {
            margin-right: 6px;
        }

        .history-count-badge {
            background: #6f42c1;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 4px;
        }

        .measures-edit-content {
            display: none;
        }

        .measures-edit-content.active {
            display: block;
        }

        /* === HISTORIAL EN EDIT === */
        .history-scroll-container-edit {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .history-scroll-container-edit::-webkit-scrollbar {
            width: 6px;
        }

        .history-scroll-container-edit::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .history-scroll-container-edit::-webkit-scrollbar-thumb {
            background: #6f42c1;
            border-radius: 3px;
        }

        .history-item-edit {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            transition: border-color 0.2s ease;
        }

        .history-item-edit:hover {
            border-color: #6f42c1;
        }

        .history-item-edit.history-item-current {
            border-color: #6f42c1;
            border-width: 2px;
        }

        .history-item-edit:last-child {
            margin-bottom: 0;
        }

        .history-header-edit {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .history-badge-edit {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            background: #f3e5f5;
            color: #6f42c1;
        }

        .history-badge-edit.history-badge-current {
            background: #6f42c1;
            color: white;
        }

        .history-measurements-edit {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .history-measure-edit {
            background: #f8f9fa;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            color: #495057;
        }

        .history-measure-edit span {
            color: #6f42c1;
            font-weight: 700;
            font-size: 17px;
        }

        .history-empty-edit {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .history-empty-edit i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.4;
            display: block;
        }

        .history-empty-edit p {
            margin: 0;
            font-size: 15px;
        }

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

        /* === TOUCH: mejoras táctiles para móviles === */
        /* Eliminar delay de 300ms en móviles */
        .medida-card {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
        }

        /* Permitir selección en inputs */
        .medida-card .medida-input {
            user-select: text;
            -webkit-user-select: text;
            touch-action: auto;
        }

        @media (hover: none) and (pointer: coarse) {

            /* Desactivar hover en dispositivos táctiles */
            .medida-card:hover {
                transform: none;
                border-color: #e5e7eb;
                box-shadow: none;
            }

            .medida-card:hover .medida-img {
                transform: none;
                opacity: 1;
            }

            /* Solo aplicar efecto visual cuando está activo (tocando) */
            .medida-card:active,
            .medida-card:focus-within {
                border-color: rgba(247, 0, 255, 0.6);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            .medida-input {
                min-height: 38px;
                font-size: 16px;
                /* Evita zoom en iOS */
            }
        }
    </style>
@stop

@section('js')
    <script>
        // === TABS DE MEDIDAS ===
        (function() {
            document.querySelectorAll('.measures-edit-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var targetTab = this.getAttribute('data-tab');

                    // Quitar active de todos los tabs y contenidos
                    document.querySelectorAll('.measures-edit-tab').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    document.querySelectorAll('.measures-edit-content').forEach(function(c) {
                        c.classList.remove('active');
                    });

                    // Activar el tab y contenido seleccionado
                    this.classList.add('active');
                    document.querySelector('[data-content="' + targetTab + '"]').classList.add('active');
                });
            });
        })();

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

        // ==========================================
        // HARD FIX: Click/Touch en card enfoca el input
        // Solución robusta para iOS/Android doble-tap
        // ==========================================
        (function() {
            const cards = document.querySelectorAll('.medida-card');

            cards.forEach(function(card) {
                // Variable para tracking
                let lastTouchTime = 0;

                // Handler unificado
                function focusInput(e) {
                    // Ignorar si el target es el input
                    if (e.target.tagName === 'INPUT') return;

                    // Encontrar el input
                    const input = card.querySelector('.medida-input');
                    if (!input) return;

                    // Prevenir default y propagación
                    e.preventDefault();
                    e.stopPropagation();

                    // Focus inmediato
                    input.focus();

                    // Seleccionar contenido después de focus
                    requestAnimationFrame(function() {
                        input.select();
                    });
                }

                // TOUCHSTART: Capturar inicio del touch
                card.addEventListener('touchstart', function(e) {
                    if (e.target.tagName === 'INPUT') return;
                    lastTouchTime = Date.now();
                }, {
                    passive: true
                });

                // TOUCHEND: Focus inmediato en touch
                card.addEventListener('touchend', function(e) {
                    if (e.target.tagName === 'INPUT') return;

                    // Solo si fue un tap rápido (< 300ms)
                    const touchDuration = Date.now() - lastTouchTime;
                    if (touchDuration < 300) {
                        focusInput(e);
                    }
                }, {
                    passive: false
                });

                // CLICK: Solo para desktop (mouse)
                card.addEventListener('click', function(e) {
                    if (e.target.tagName === 'INPUT') return;

                    // Ignorar si hubo touch reciente (evita doble disparo)
                    if (Date.now() - lastTouchTime < 500) return;

                    focusInput(e);
                });
            });
        })();
    </script>

@stop
